<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ManualAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['innovation.email_verification_required' => false, 'innovation.invitation_delivery' => 'manual']);
        Notification::fake();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->admin->forceFill(['email_verified_at' => null])->save();
    }

    private function invite(): User
    {
        $user = User::factory()->unverified()->create(['invitation_pending' => true]);
        $user->assignRole('employee');

        return $user;
    }

    private function link(User $user): string
    {
        $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.users.access-link', $user), ['reason' => 'Identidade conferida com o titular.'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.edit', $user));

        return session('access_link');
    }

    private function token(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_optional_verification_preserves_unverified_state_and_role_checks(): void
    {
        $this->assertTrue($this->admin->hasAdministrativeAccess());
        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSeeText('Confirmação de e-mail dispensada');
        $employee = User::factory()->unverified()->create();
        $employee->assignRole('employee');
        $this->actingAs($employee)->get(route('dashboard'))->assertOk();
        $this->get(route('ideas.create'))->assertOk();
        $this->getJson(route('admin.users.index'))->assertForbidden();
        $employee->sendEmailVerificationNotification();
        Notification::assertNothingSent();
        $this->assertFalse($employee->fresh()->hasVerifiedEmail());
        config(['innovation.email_verification_required' => true]);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->assertFalse($this->admin->fresh()->hasAdministrativeAccess());
    }

    public function test_admin_registers_pending_person_without_email_or_shared_password(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Pessoa Convidada', 'email' => 'pessoa@empresa.test', 'role' => 'employee',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('email', 'pessoa@empresa.test')->firstOrFail();
        $this->assertTrue($user->invitation_pending);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertNothingSent();
        $this->get(route('admin.users.edit', $user))->assertOk()->assertSeeText('Redefinir senha');
        $this->actingAs($user)->getJson(route('dashboard'))->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_manual_links_require_admin_recent_password_and_a_reason(): void
    {
        $user = $this->invite();
        $this->actingAs($this->admin)->post(route('admin.users.access-link', $user), ['reason' => 'Titular presente na administração.'])->assertRedirect(route('password.confirm'));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->get(route('password.confirm', ['return_to' => 'admin_user', 'user_id' => $user->id]))->assertOk();
        $this->post(route('password.confirm'), ['password' => 'password'])->assertRedirect(route('admin.users.edit', $user));
        $this->post(route('admin.users.access-link', $user), ['reason' => 'curto'])->assertSessionHasErrors('reason');
        $employee = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($employee)->withSession(['auth.password_confirmed_at' => time()])->postJson(route('admin.users.access-link', $user), ['reason' => 'Titular presente na administração.'])->assertForbidden();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_first_access_link_is_private_audited_and_single_use_without_verifying_email(): void
    {
        $user = $this->invite();
        $url = $this->link($user);
        $token = $this->token($url);
        $this->assertTrue(Password::tokenExists($user, $token));
        $log = AuditLog::where('action', 'manual_access_link_created')->firstOrFail();
        $this->assertSame('first_access', $log->details['purpose']);
        $this->assertStringNotContainsString($token, json_encode($log->details));
        $this->get(route('admin.users.edit', $user))->assertOk()->assertDontSee($url)->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('admin.users.edit', $user))->assertOk()->assertDontSee($url);
        $this->app['auth']->forgetGuards();
        $this->post(route('password.store'), ['email' => $user->email, 'token' => $token, 'password' => 'NovaSenhaForte123', 'password_confirmation' => 'NovaSenhaForte123'])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $current = $user->fresh();
        $this->assertFalse($current->invitation_pending);
        $this->assertFalse($current->hasVerifiedEmail());
        $this->assertTrue(Hash::check('NovaSenhaForte123', $current->password));
        $this->assertFalse(Password::tokenExists($current, $token));
        $this->post(route('password.store'), ['email' => $user->email, 'token' => $token, 'password' => 'OutraSenhaForte123', 'password_confirmation' => 'OutraSenhaForte123'])->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'NovaSenhaForte123'])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk();
        Notification::assertNothingSent();
    }

    public function test_renewed_link_invalidates_previous_and_expiration_is_enforced(): void
    {
        $user = $this->invite();
        $old = $this->token($this->link($user));
        $new = $this->token($this->link($user));
        $this->assertFalse(Password::tokenExists($user, $old));
        $this->assertTrue(Password::tokenExists($user, $new));
        $this->travel((int) config('auth.passwords.users.expire') + 1)->minutes();
        $this->assertFalse(Password::tokenExists($user, $new));
    }

    public function test_manual_recovery_sets_password_without_a_link_and_revokes_old_access(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $version = $user->access_version;
        $rememberToken = $user->remember_token;
        $verifiedAt = $user->email_verified_at;
        $token = Password::createToken($user);
        $this->actingAs($this->admin)->get(route('admin.users.edit', $user))
            ->assertOk()->assertSeeText('Redefinir senha')->assertDontSeeText('Confirmar minha senha para gerar link')
            ->assertDontSeeText('Motivo da redefinição')->assertDontSeeText('Sua senha de administrador');
        $this->post(route('admin.users.reset-password', $user), [
            'password' => 'NovaSenhaForte123',
            'password_confirmation' => 'NovaSenhaForte123',
        ])->assertSessionHasNoErrors()->assertSessionMissing('access_link')->assertRedirect(route('admin.users.edit', $user));
        $current = $user->fresh();
        $this->assertTrue(Hash::check('NovaSenhaForte123', $current->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertSame($version + 1, $current->access_version);
        $this->assertNotSame($rememberToken, $current->remember_token);
        $this->assertFalse(Password::tokenExists($current, $token));
        $this->assertEquals($verifiedAt, $current->email_verified_at);
        $this->assertTrue($current->hasRole('employee'));
        $log = AuditLog::where('action', 'user_password_reset')->firstOrFail();
        $this->assertSame($this->admin->id, $log->causer_id);
        $this->assertSame([], $log->details);
        $this->app['auth']->forgetGuards();
        $this->get(route('password.request'))->assertOk()->assertSeeText('Solicite à administração a redefinição')->assertDontSee('name="email"', false);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'NovaSenhaForte123'])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk();
        Notification::assertNothingSent();
    }

    public function test_direct_recovery_requires_matching_new_passwords(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $oldPassword = $user->password;
        $this->actingAs($this->admin)->from(route('admin.users.edit', $user))
            ->post(route('admin.users.reset-password', $user), [
                'password' => 'NovaSenhaForte123',
                'password_confirmation' => 'OutraSenha123',
            ])->assertSessionHasErrors(['password'])
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->assertSame($oldPassword, $user->fresh()->password);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user_password_reset']);
    }

    public function test_direct_recovery_rejects_guests_employees_and_unavailable_accounts(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $data = ['password' => 'NovaSenhaForte123',
            'password_confirmation' => 'NovaSenhaForte123'];
        $this->post(route('admin.users.reset-password', $user), $data)->assertRedirect(route('login'));
        $this->actingAs($user)->postJson(route('admin.users.reset-password', $user), $data)->assertForbidden();
        $user->forceFill(['blocked_at' => now()])->save();
        $this->actingAs($this->admin)->postJson(route('admin.users.reset-password', $user), $data)->assertUnprocessable();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user_password_reset']);
    }

    public function test_recovery_links_are_no_longer_generated_for_existing_accounts(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])
            ->postJson(route('admin.users.access-link', $user), ['reason' => 'Titular solicitou nova senha.'])->assertUnprocessable();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_admin_can_define_first_password_without_a_link_or_email_verification(): void
    {
        $user = $this->invite();
        $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user), [
            'password' => 'NovaSenhaForte123', 'password_confirmation' => 'NovaSenhaForte123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.edit', $user));
        $this->assertFalse($user->fresh()->invitation_pending);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'NovaSenhaForte123'])->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk();
        Notification::assertNothingSent();
    }

    public function test_admin_resetting_own_password_must_sign_in_again(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.reset-password', $this->admin), [
            'password' => 'NovaSenhaForte123',
            'password_confirmation' => 'NovaSenhaForte123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('NovaSenhaForte123', $this->admin->fresh()->password));
    }

    public function test_unavailable_accounts_cannot_receive_links_and_pending_admin_does_not_replace_last_admin(): void
    {
        $user = $this->invite();
        $user->forceFill(['blocked_at' => now()])->save();
        $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()])->postJson(route('admin.users.access-link', $user), ['reason' => 'Titular presente na administração.'])->assertUnprocessable();
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $pendingAdmin = $this->invite();
        $pendingAdmin->syncRoles(['admin']);
        $this->post(route('profile.permissions', $this->admin), ['role' => 'employee'])->assertSessionHasErrors('user');
        $this->assertTrue($this->admin->fresh()->hasAdministrativeAccess());
    }

    public function test_changing_email_of_manual_invitee_does_not_send_an_invitation(): void
    {
        $user = $this->invite();
        $token = Password::createToken($user);
        $this->actingAs($this->admin)->patch(route('admin.users.update', $user), ['name' => $user->name, 'email' => 'novo@empresa.test', 'role' => 'employee'])->assertSessionHasNoErrors();
        Notification::assertNothingSent();
        $this->assertTrue($user->fresh()->invitation_pending);
        $this->assertFalse(Password::tokenExists($user->fresh(), $token));
    }

    public function test_production_check_skips_smtp_only_when_email_features_are_disabled(): void
    {
        config(['mail.default' => 'log']);
        $this->artisan('app:production-check')->expectsOutput('[OK] E-mail SMTP quando habilitado')->assertFailed();
        config(['innovation.email_verification_required' => true]);
        $this->artisan('app:production-check')->expectsOutput('[PENDENTE] E-mail SMTP quando habilitado')->assertFailed();
    }
}
