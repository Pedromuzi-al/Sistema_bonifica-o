<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdministrationAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->employee = User::where('email', 'funcionario@corp.local')->firstOrFail();
    }

    private function userData(User $user): array
    {
        return ['name' => $user->name, 'email' => $user->email, 'sector_id' => $user->sector_id, 'role' => $user->getRoleNames()->first(), 'permissions' => $user->getDirectPermissions()->pluck('name')->all(), 'teams' => $user->teams()->pluck('teams.id')->all()];
    }

    public function test_only_verified_administrators_can_manage_users_and_organization(): void
    {
        $this->actingAs($this->employee)->getJson(route('admin.users.index'))->assertForbidden();
        $this->postJson(route('admin.sectors.store'), ['name' => 'Tentativa', 'is_active' => true])->assertForbidden();
        $this->postJson(route('admin.users.block', $this->admin), ['reason' => 'Tentativa'])->assertForbidden();
        $this->assertNull($this->admin->fresh()->blocked_at);
        $unverified = User::factory()->unverified()->create();
        $unverified->assignRole('admin');
        $this->actingAs($unverified)->get(route('admin.users.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_admin_creates_user_with_sector_team_permissions_and_email_verification(): void
    {
        $team = Team::factory()->create();
        $this->actingAs($this->admin)->post(route('admin.users.store'), ['name' => 'Nova Pessoa', 'email' => 'nova@corp.local', 'password' => 'senha-inicial-123', 'password_confirmation' => 'senha-inicial-123', 'sector_id' => Sector::first()->id, 'teams' => [$team->id], 'role' => 'employee', 'permissions' => ['view_author_identity'], 'email_verified_at' => now(), 'blocked_at' => now()])->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('email', 'nova@corp.local')->firstOrFail();
        $this->assertTrue(Hash::check('senha-inicial-123', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->blocked_at);
        $this->assertTrue($user->hasRole('employee'));
        $this->assertTrue($user->hasPermissionTo('view_author_identity'));
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $user->id]);
        Notification::assertSentTo($user, VerifyEmail::class);
        $log = AuditLog::where('action', 'user_created')->firstOrFail();
        $this->assertStringNotContainsString('senha-inicial-123', json_encode($log->details));
    }

    public function test_duplicate_email_invalid_permissions_and_password_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), ['name' => 'Duplicada', 'email' => $this->employee->email, 'password' => '123', 'password_confirmation' => 'outro', 'permissions' => ['permissao_inventada']])->assertSessionHasErrors(['email', 'password', 'permissions.0']);
        $this->assertDatabaseCount('users', 3);
    }

    public function test_admin_updates_user_without_resetting_password_and_changing_email_requires_confirmation(): void
    {
        $password = $this->employee->password;
        $data = [...$this->userData($this->employee), 'name' => 'Nome atualizado', 'email' => 'novo-endereco@corp.local'];
        $this->actingAs($this->admin)->patch(route('admin.users.update', $this->employee), $data)->assertSessionHasNoErrors();
        $user = $this->employee->fresh();
        $this->assertSame('Nome atualizado', $user->name);
        $this->assertSame($password, $user->password);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->actingAs($user)->get(route('ideas.create'))->assertRedirect(route('verification.notice'));
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_blocking_revokes_sessions_remember_token_and_preserves_history(): void
    {
        $token = $this->employee->remember_token;
        DB::table('sessions')->insert(['id' => 'employee-session', 'user_id' => $this->employee->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->admin)->post(route('admin.users.block', $this->employee), ['reason' => 'Afastamento temporário'])->assertSessionHasNoErrors();
        $this->assertNotNull($this->employee->fresh()->blocked_at);
        $this->assertNotSame($token, $this->employee->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'employee-session']);
        $this->assertSame(1, $this->employee->ideas()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_blocked', 'model_id' => $this->employee->id]);
        $this->actingAs($this->employee)->getJson(route('dashboard'))->assertForbidden();
        $this->assertGuest();
        $this->post(route('login'), ['email' => $this->employee->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reactivation_invalidates_old_session_and_allows_a_new_login(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.block', $this->employee), ['reason' => 'Pausa'])->assertSessionHasNoErrors();
        $this->post(route('admin.users.reactivate', $this->employee), ['reason' => 'Retorno autorizado'])->assertSessionHasNoErrors();
        $this->assertNull($this->employee->fresh()->blocked_at);
        $this->actingAs($this->employee)->withSession(['access_version' => 0])->getJson(route('dashboard'))->assertUnauthorized();
        $this->post(route('login'), ['email' => $this->employee->email, 'password' => 'password'])->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_deactivated_account_is_restored_with_associations_and_history(): void
    {
        $team = Team::factory()->create();
        $this->employee->teams()->attach($team);
        $this->actingAs($this->employee)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertSoftDeleted($this->employee);
        $this->actingAs($this->admin)->post(route('admin.users.reactivate', $this->employee->id), ['reason' => 'Retorno da pessoa'])->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($this->employee);
        $restored = $this->employee->fresh();
        $this->assertTrue($restored->hasRole('employee'));
        $this->assertTrue($restored->teams()->whereKey($team->id)->exists());
        $this->assertSame(1, $restored->ideas()->count());
    }

    public function test_self_block_and_removal_of_last_administrator_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.block', $this->admin), ['reason' => 'Teste'])->assertSessionHasErrors('user');
        $this->post(route('profile.permissions', $this->admin), ['role' => 'employee'])->assertSessionHasErrors('user');
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('user');
        $this->assertNotSoftDeleted($this->admin);
        $this->assertAuthenticated();
        $this->patch(route('admin.users.update', $this->admin), [...$this->userData($this->admin), 'email' => 'admin-alterado@corp.local'])->assertSessionHasErrors('user');
        $this->assertSame('admin@corp.local', $this->admin->fresh()->email);
    }

    public function test_second_verified_administrator_allows_an_admin_to_change_profile(): void
    {
        $second = User::factory()->create();
        $second->givePermissionTo('manage_admin');
        $this->actingAs($this->admin)->post(route('profile.permissions', $this->admin), ['role' => 'employee'])->assertSessionHasNoErrors();
        $this->assertFalse($this->admin->fresh()->hasRole('admin'));
        $this->assertTrue($second->hasAdministrativeAccess());
        $this->getJson(route('admin.users.index'))->assertForbidden();
    }

    public function test_sector_and_team_can_be_edited_and_deactivated_without_deleting_relations(): void
    {
        $this->actingAs($this->admin)->post(route('admin.sectors.store'), ['name' => 'Pesquisa', 'description' => 'Novo setor', 'is_active' => true])->assertSessionHasNoErrors();
        $sector = Sector::where('name', 'Pesquisa')->firstOrFail();
        $this->post(route('admin.teams.store'), ['name' => 'Melhorias', 'sector_id' => $sector->id, 'is_active' => true, 'members' => [$this->employee->id]])->assertSessionHasNoErrors();
        $team = Team::where('name', 'Melhorias')->firstOrFail();
        $this->patch(route('admin.teams.update', $team), ['name' => 'Melhorias', 'sector_id' => $sector->id, 'is_active' => false, 'members' => [$this->employee->id]])->assertSessionHasNoErrors();
        $this->patch(route('admin.sectors.update', $sector), ['name' => 'Pesquisa e inovação', 'is_active' => false])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $this->employee->id]);
        $new = User::factory()->create();
        $this->patch(route('admin.users.update', $new), [...$this->userData($new), 'teams' => [$team->id]])->assertSessionHasErrors('teams');
        $this->patch(route('admin.users.update', $new), [...$this->userData($new), 'sector_id' => $sector->id])->assertSessionHasErrors('sector_id');
        $this->assertDatabaseHas('audit_logs', ['action' => 'team_saved', 'model_id' => $team->id]);
    }

    public function test_inactive_sectors_are_not_offered_or_accepted_for_new_ideas(): void
    {
        $sector = Sector::first();
        $sector->update(['is_active' => false]);
        $this->actingAs($this->employee)->get(route('ideas.create'))->assertOk()->assertViewHas('sectors', fn ($sectors) => ! $sectors->contains('id', $sector->id));
        $this->post(route('ideas.store'), ['title' => 'Teste', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Benefícios', 'resources_needed' => 'Recursos', 'origin_sector_id' => $sector->id, 'beneficiary_sectors' => [$sector->id]])->assertSessionHasErrors(['origin_sector_id', 'beneficiary_sectors.0']);
    }

    public function test_administrative_pages_render_and_filters_include_deactivated_accounts(): void
    {
        $this->employee->delete();
        $this->actingAs($this->admin)->get(route('admin.users.index', ['status' => 'deactivated']))->assertOk()->assertSeeText($this->employee->name);
        $this->get(route('admin.users.edit', $this->employee->id))->assertOk()->assertSeeText('Reativar acesso');
        $this->get(route('admin.users.create'))->assertOk()->assertSeeText('Senha inicial');
        $this->get(route('admin.organization'))->assertOk();
        $this->get(route('admin.sectors.create'))->assertOk();
        $this->get(route('admin.teams.create'))->assertOk();
        $this->get(route('admin.sectors.edit', Sector::first()))->assertOk();
        $this->get(route('admin.teams.edit', Team::factory()->create()))->assertOk();
    }

    public function test_registration_sends_verification_and_unverified_user_can_correct_email(): void
    {
        $this->post(route('register'), ['name' => 'Cadastro', 'email' => 'cadastro@corp.local', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'admin'])->assertRedirect();
        $user = User::where('email', 'cadastro@corp.local')->firstOrFail();
        $this->assertFalse($user->hasRole('admin'));
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSeeText('Corrigir meu e-mail');
        $this->patch(route('profile.update'), ['name' => 'Cadastro', 'email' => 'corrigido@corp.local'])->assertSessionHasNoErrors();
        $this->assertSame('corrigido@corp.local', $user->fresh()->email);
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    public function test_verification_link_is_signed_expires_and_cannot_verify_another_account(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($this->employee)->getJson($url)->assertForbidden();
        $this->actingAs($user)->get($url)->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email_verified', 'model_id' => $user->id]);
        $second = User::factory()->unverified()->create();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $second->id, 'hash' => sha1($second->email)]);
        $this->actingAs($second)->get($expired)->assertForbidden();
        $this->assertNull($second->fresh()->email_verified_at);
    }

    public function test_deactivated_team_members_are_preserved_but_new_inactive_associations_are_rejected(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach($this->employee);
        $this->employee->delete();
        $this->actingAs($this->admin)->patch(route('admin.teams.update', $team), ['name' => $team->name, 'is_active' => true, 'members' => [$this->employee->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $this->employee->id]);
        $this->post(route('admin.teams.store'), ['name' => 'Outra equipe', 'is_active' => true, 'members' => [$this->employee->id]])->assertSessionHasErrors('members');
        $this->assertDatabaseMissing('teams', ['name' => 'Outra equipe']);
        $team->update(['is_active' => false]);
        $this->patch(route('admin.teams.update', $team), ['name' => $team->name, 'is_active' => false, 'members' => [$this->employee->id, $this->admin->id]])->assertSessionHasErrors('members');
    }

    public function test_unverified_second_administrator_does_not_allow_removal_of_last_verified_admin(): void
    {
        $second = User::factory()->unverified()->create();
        $second->assignRole('admin');
        $this->actingAs($this->admin)->patch(route('admin.users.update', $this->admin), [...$this->userData($this->admin), 'role' => 'employee'])->assertSessionHasErrors('user');
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_guest_and_employee_cannot_manage_members_or_edit_another_profile(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->actingAs($this->employee)->postJson(route('admin.teams.store'), ['name' => 'Acesso indevido', 'is_active' => true, 'members' => [$this->admin->id]])->assertForbidden();
        $this->patchJson(route('profile.update', $this->admin), ['name' => 'Alterado', 'email' => $this->admin->email])->assertForbidden();
        $this->assertSame('Administrador Corp', $this->admin->fresh()->name);
    }

    public function test_admin_can_resend_verification_but_not_confirm_on_behalf_of_user(): void
    {
        $target = User::factory()->unverified()->create();
        $this->actingAs($this->admin)->post(route('admin.users.verification', $target))->assertSessionHasNoErrors();
        Notification::assertSentTo($target, VerifyEmail::class);
        $this->assertNull($target->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'verification_requested', 'model_id' => $target->id]);
    }
}
