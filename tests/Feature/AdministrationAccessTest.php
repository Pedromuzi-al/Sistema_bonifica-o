<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
        $this->actingAs($this->admin)->post(route('admin.users.store'), ['name' => 'Nova Pessoa', 'email' => 'nova@corp.local', 'sector_id' => Sector::first()->id, 'teams' => [$team->id], 'role' => 'employee', 'permissions' => ['view_author_identity'], 'email_verified_at' => now(), 'blocked_at' => now()])->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('email', 'nova@corp.local')->firstOrFail();
        $this->assertTrue($user->invitation_pending);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->blocked_at);
        $this->assertTrue($user->hasRole('employee'));
        $this->assertTrue($user->hasPermissionTo('view_author_identity'));
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $user->id]);
        Notification::assertSentTo($user, ResetPassword::class);
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
        $this->actingAs($user)->withSession(['access_version' => $user->access_version])->get(route('ideas.create'))->assertRedirect(route('verification.notice'));
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
        $this->get(route('admin.users.create'))->assertOk()->assertSeeText('convite por e-mail');
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

    public function test_invitation_sets_password_verifies_email_and_cannot_be_reused(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), ['name' => 'Convidada', 'email' => 'convite@corp.local', 'role' => 'employee'])->assertSessionHasNoErrors();
        $user = User::where('email', 'convite@corp.local')->firstOrFail();
        $notification = Notification::sent($user, ResetPassword::class)->first();
        $this->assertNotNull($notification);
        auth()->logout();
        $data = ['email' => $user->email, 'token' => $notification->token, 'password' => 'minha-senha-123', 'password_confirmation' => 'minha-senha-123'];
        $this->post(route('password.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertFalse($user->fresh()->invitation_pending);
        $this->assertTrue(Hash::check($data['password'], $user->fresh()->password));
        $this->post(route('password.store'), $data)->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => $data['password']])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_blocked_and_expired_invitations_cannot_set_password(): void
    {
        $user = User::factory()->unverified()->create(['invitation_pending' => true, 'blocked_at' => now()]);
        $token = Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123'];
        $this->post(route('password.store'), $data)->assertForbidden();
        $this->assertTrue($user->fresh()->invitation_pending);
        $user->forceFill(['blocked_at' => null])->save();
        $this->travel(61)->minutes();
        $this->post(route('password.store'), $data)->assertSessionHasErrors('email');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_change_revokes_previous_sessions_and_remember_token(): void
    {
        $oldToken = $this->employee->remember_token;
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $this->employee->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->admin)->patch(route('admin.users.update', $this->employee), [...$this->userData($this->employee), 'email' => 'alterado@corp.local'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertNotSame($oldToken, $this->employee->fresh()->remember_token);
        $this->employee->fresh()->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($this->employee->fresh())->withSession(['access_version' => 0])->getJson(route('dashboard'))->assertUnauthorized();
    }

    public function test_deactivated_account_can_have_access_reviewed_before_reactivation(): void
    {
        $this->employee->delete();
        $this->actingAs($this->admin)->get(route('admin.users.edit', $this->employee->id))->assertSeeText('Salvar dados e acesso');
        $this->patch(route('admin.users.update', $this->employee->id), [...$this->userData($this->employee), 'role' => null, 'permissions' => []])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($this->employee);
        $this->post(route('admin.users.reactivate', $this->employee->id), ['reason' => 'Acessos revisados'])->assertSessionHasNoErrors();
        $this->assertFalse($this->employee->fresh()->hasRole('employee'));
    }

    public function test_empty_checkbox_selections_are_preserved_after_validation_failure(): void
    {
        $team = Team::factory()->create();
        $this->employee->teams()->attach($team);
        $this->employee->givePermissionTo('view_author_identity');
        $this->actingAs($this->admin)->from(route('admin.users.edit', $this->employee))->patch(route('admin.users.update', $this->employee), ['name' => '', 'email' => $this->employee->email, 'role' => 'employee'])->assertSessionHasErrors('name');
        $html = $this->get(route('admin.users.edit', $this->employee))->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="teams\[\]"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="permissions\[\]"[^>]*checked/', $html);
        $this->from(route('admin.teams.edit', $team))->patch(route('admin.teams.update', $team), ['name' => '', 'is_active' => true])->assertSessionHasErrors('name');
        $html = $this->get(route('admin.teams.edit', $team))->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="members\[\]"[^>]*checked/', $html);
    }

    public function test_pending_invitation_cannot_login_and_resending_invalidates_previous_link(): void
    {
        $user = User::factory()->unverified()->create(['invitation_pending' => true]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $oldToken = Password::createToken($user);
        $this->travel(61)->seconds();
        $this->actingAs($this->admin)->post(route('admin.users.verification', $user))->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertFalse(Password::tokenExists($user, $oldToken));
        $this->assertTrue($user->fresh()->invitation_pending);
    }

    public function test_password_recovery_also_revokes_existing_sessions_without_verifying_regular_users(): void
    {
        $user = User::factory()->unverified()->create();
        DB::table('sessions')->insert(['id' => 'reset-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $token = Password::createToken($user);
        $this->post(route('password.store'), ['email' => $user->email, 'token' => $token, 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'reset-session']);
        $this->assertSame(1, $user->fresh()->access_version);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
