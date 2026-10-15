<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdministrationService
{
    public function createUser(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data) {
            $this->lockAccounts($actor);
            $this->authorizeAdmin($actor);
            $this->validateAssignments($data);
            $user = User::create(collect($data)->only(['name', 'email', 'password', 'sector_id'])->all());
            $this->assignAccess($user, $data);
            $user->teams()->sync($data['teams'] ?? []);
            $this->audit($actor, $user, 'user_created', $this->userSnapshot($user));

            return $user;
        }, 3);
    }

    public function updateUser(User $actor, User $target, array $data, bool $profileOnly = false): User
    {
        return DB::transaction(function () use ($actor, $target, $data, $profileOnly) {
            $this->lockAccounts($actor);
            if (! $profileOnly || $actor->id !== $target->id) {
                $this->authorizeAdmin($actor);
            }
            $target = User::withTrashed()->findOrFail($target->id);
            if ($target->trashed()) {
                throw ValidationException::withMessages(['user' => 'Reative a conta antes de editar seus dados.']);
            }
            $hadAdministrator = $this->hasAvailableAdministrator();
            $before = $this->userSnapshot($target);
            if (! $profileOnly) {
                $this->validateAssignments($data, $target);
            }
            $oldEmail = $target->email;
            $target->fill(collect($data)->only($profileOnly ? ['name', 'email'] : ['name', 'email', 'sector_id'])->all());
            if ($target->isDirty('email')) {
                $target->email_verified_at = null;
                DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
            }
            $target->save();
            if (! $profileOnly) {
                $this->assignAccess($target, $data);
                $target->teams()->sync($data['teams'] ?? []);
            }
            $this->preserveAdministrator($hadAdministrator);
            $this->audit($actor, $target, 'user_updated', ['before' => $before, 'after' => $this->userSnapshot($target)]);

            return $target;
        }, 3);
    }

    public function permissions(User $actor, User $target, array $data): void
    {
        DB::transaction(function () use ($actor, $target, $data) {
            $this->lockAccounts($actor);
            $this->authorizeAdmin($actor);
            $target = User::findOrFail($target->id);
            $hadAdministrator = $this->hasAvailableAdministrator();
            $before = $this->userSnapshot($target);
            $this->assignAccess($target, $data);
            $this->preserveAdministrator($hadAdministrator);
            $this->audit($actor, $target, 'permissions_updated', ['before' => $before, 'after' => $this->userSnapshot($target)]);
        }, 3);
    }

    public function block(User $actor, User $target, string $reason): void
    {
        DB::transaction(function () use ($actor, $target, $reason) {
            $this->lockAccounts($actor);
            $this->authorizeAdmin($actor);
            $target = User::findOrFail($target->id);
            if ($target->id === $actor->id) {
                throw ValidationException::withMessages(['user' => 'Você não pode bloquear sua própria conta.']);
            }
            if ($target->blocked_at) {
                throw ValidationException::withMessages(['user' => 'Esta conta já está bloqueada.']);
            }
            $hadAdministrator = $this->hasAvailableAdministrator();
            $target->forceFill(['blocked_at' => now(), 'block_reason' => $reason, 'remember_token' => Str::random(60), 'access_version' => (int) $target->access_version + 1])->save();
            $this->preserveAdministrator($hadAdministrator);
            $this->revokeSessions($target);
            $this->audit($actor, $target, 'user_blocked', ['reason' => $reason]);
        }, 3);
    }

    public function reactivate(User $actor, User $target, string $reason): void
    {
        DB::transaction(function () use ($actor, $target, $reason) {
            $this->lockAccounts($actor);
            $this->authorizeAdmin($actor);
            $target = User::withTrashed()->findOrFail($target->id);
            if (! $target->trashed() && ! $target->blocked_at) {
                throw ValidationException::withMessages(['user' => 'Esta conta já está ativa.']);
            }
            $target->forceFill(['deleted_at' => null, 'blocked_at' => null, 'block_reason' => null, 'remember_token' => Str::random(60), 'access_version' => (int) $target->access_version + 1])->save();
            $this->revokeSessions($target);
            $this->audit($actor, $target, 'user_reactivated', ['reason' => $reason]);
        }, 3);
    }

    public function deactivate(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->lockAccounts($user);
            $target = User::findOrFail($user->id);
            $hadAdministrator = $this->hasAvailableAdministrator();
            $target->forceFill(['remember_token' => Str::random(60), 'access_version' => (int) $target->access_version + 1])->save();
            $target->delete();
            $this->preserveAdministrator($hadAdministrator);
            $this->revokeSessions($target);
            $this->audit($user, $target, 'account_deactivated', ['reason' => 'Solicitação do titular; histórico preservado.']);
        }, 3);
    }

    public function saveSector(User $actor, array $data, ?Sector $sector = null): Sector
    {
        return DB::transaction(function () use ($actor, $data, $sector) {
            $this->lockAccounts($actor);
            $this->authorizeAdmin($actor);
            $sector = $sector ? Sector::whereKey($sector->id)->lockForUpdate()->firstOrFail() : new Sector;
            $before = $sector->toArray();
            $sector->fill($data)->save();
            $this->audit($actor, $sector, 'sector_saved', ['before' => $before, 'after' => $sector->toArray()]);

            return $sector;
        }, 3);
    }

    public function saveTeam(User $actor, array $data, ?Team $team = null): Team
    {
        return DB::transaction(function () use ($actor, $data, $team) {
            $this->lockAccounts($actor);
            $this->authorizeAdmin($actor);
            $team = $team ? Team::whereKey($team->id)->lockForUpdate()->firstOrFail() : new Team;
            $this->validateSector($data['sector_id'] ?? null, $team->sector_id);
            $existingMembers = $team->exists ? $team->users()->pluck('users.id')->all() : [];
            if (! $data['is_active'] && array_diff($data['members'] ?? [], $existingMembers)) {
                throw ValidationException::withMessages(['members' => 'Ative a equipe antes de adicionar novos participantes.']);
            }
            if (User::onlyTrashed()->whereIn('id', array_diff($data['members'] ?? [], $existingMembers))->exists()) {
                throw ValidationException::withMessages(['members' => 'Reative a conta antes de associar uma nova pessoa desativada.']);
            }
            $before = [...$team->toArray(), 'members' => $team->exists ? $team->users()->pluck('users.id')->all() : []];
            $team->fill(collect($data)->except('members')->all())->save();
            $team->users()->sync($data['members'] ?? []);
            $this->audit($actor, $team, 'team_saved', ['before' => $before, 'after' => [...$team->toArray(), 'members' => $data['members'] ?? []]]);

            return $team;
        }, 3);
    }

    private function validateAssignments(array $data, ?User $target = null): void
    {
        $this->validateSector($data['sector_id'] ?? null, $target?->sector_id);
        $existing = $target?->teams()->pluck('teams.id')->all() ?? [];
        $newTeams = array_diff($data['teams'] ?? [], $existing);
        if (Team::whereIn('id', $newTeams)->where('is_active', false)->exists()) {
            throw ValidationException::withMessages(['teams' => 'Novas associações devem usar equipes ativas.']);
        }
    }

    private function validateSector(?int $sectorId, ?int $currentSectorId): void
    {
        if ($sectorId && $sectorId !== $currentSectorId && ! Sector::whereKey($sectorId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['sector_id' => 'Selecione um setor ativo.']);
        }
    }

    private function assignAccess(User $user, array $data): void
    {
        $user->syncRoles(empty($data['role']) ? [] : [$data['role']]);
        $user->syncPermissions($data['permissions'] ?? []);
    }

    private function lockAccounts(User $actor): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('users')->where('id', $actor->id)->update(['id' => DB::raw('id')]);
        }
        User::withTrashed()->orderBy('id')->lockForUpdate()->get(['id']);
        $current = User::find($actor->id);
        abort_unless($current && $current->isAvailable(), 403);
    }

    private function authorizeAdmin(User $actor): void
    {
        Gate::forUser(User::findOrFail($actor->id))->authorize('manage-admin');
    }

    private function hasAvailableAdministrator(): bool
    {
        return User::whereNull('blocked_at')->whereNotNull('email_verified_at')->with('roles.permissions', 'permissions')->get()->contains(fn ($user) => $user->hasAdministrativeAccess());
    }

    private function preserveAdministrator(bool $hadAdministrator): void
    {
        if ($hadAdministrator && ! $this->hasAvailableAdministrator()) {
            throw ValidationException::withMessages(['user' => 'É necessário manter pelo menos um administrador ativo com e-mail verificado. Libere outro administrador antes desta alteração.']);
        }
    }

    private function revokeSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function userSnapshot(User $user): array
    {
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return ['name' => $user->name, 'email' => $user->email, 'sector_id' => $user->sector_id, 'roles' => $user->getRoleNames()->all(), 'permissions' => $user->getDirectPermissions()->pluck('name')->all(), 'teams' => $user->teams()->pluck('teams.id')->all()];
    }

    private function audit(User $actor, Model $model, string $action, array $details): void
    {
        AuditLog::create(['causer_id' => $actor->id, 'model_type' => $model::class, 'model_id' => $model->id, 'action' => $action, 'details' => $details]);
    }
}
