<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_own_password_change_revokes_old_access_and_keeps_current_session(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-token']);
        $token = Password::createToken($user);
        $version = $user->access_version;
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password', 'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $current = $user->fresh();
        $this->assertTrue(Hash::check('ChangedPassword123', $current->password));
        $this->assertSame($version + 1, $current->access_version);
        $this->assertNotSame('old-token', $current->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertFalse(Password::tokenExists($current, $token));
        $this->assertSame([], AuditLog::where('action', 'password_updated')->sole()->details);
        $this->assertSame($current->access_version, session('access_version'));
        $this->get('/dashboard')->assertOk();
        $this->withSession(['access_version' => $version])->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_wrong_current_password_does_not_revoke_sessions_or_tokens(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-token']);
        $original = $user->password;
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)->put('/password', ['current_password' => 'wrong', 'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123'])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertSame($original, $user->fresh()->password);
        $this->assertSame('old-token', $user->fresh()->remember_token);
        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'password_updated']);
    }

    public function test_malformed_password_fields_return_validation_errors_instead_of_server_errors(): void
    {
        $user = User::factory()->create();
        $original = $user->password;
        $this->actingAs($user)->postJson('/confirm-password', ['password' => ['unexpected']])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/password', ['current_password' => ['unexpected'], 'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->patchJson('/profile', ['name' => $user->name, 'email' => 'new@example.test', 'current_password' => ['unexpected']])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame($original, $user->fresh()->password);
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_email_change_requires_current_password_without_blocking_name_changes(): void
    {
        config(['innovation.email_verification_required' => false]);
        $user = User::factory()->create();
        $email = $user->email;

        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => 'new@example.test'])->assertSessionHasErrors('current_password');
        $this->assertSame($email, $user->fresh()->email);
        $this->patch('/profile', ['name' => $user->name, 'email' => 'new@example.test', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertSame($email, $user->fresh()->email);
        $this->patch('/profile', ['name' => 'Nome atualizado', 'email' => $email])->assertSessionHasNoErrors();
        $this->assertSame('Nome atualizado', $user->fresh()->name);
        $this->patch('/profile', ['name' => 'Nome atualizado', 'email' => 'new@example.test', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->get('/dashboard')->assertOk();
    }

    public function test_password_confirmation_has_account_scoped_throttle_and_recovers_after_timeout(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/confirm-password', ['password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/confirm-password', ['password' => 'password'])->assertTooManyRequests();
        $this->assertNull(session('auth.password_confirmed_at'));
        $this->actingAs($other)->post('/confirm-password', ['password' => 'password'])->assertRedirect();
        $this->travel(61)->seconds();
        $this->actingAs($user)->post('/confirm-password', ['password' => 'password'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_self_registration_receives_only_employee_access(): void
    {
        $this->seed(AccessControlSeeder::class);
        config(['innovation.email_verification_required' => false]);
        Notification::fake();

        $this->post('/register', ['name' => 'Nova Pessoa', 'email' => 'new@example.test', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'admin', 'permissions' => ['manage_admin']])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'new@example.test')->sole();
        $this->assertTrue($user->hasRole('employee'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->hasSystemPermission('vote_ideas'));
        $this->get('/ideas/create')->assertOk();
        $this->get('/cycles')->assertOk();
        $this->getJson('/admin/users')->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_registration_without_access_configuration_creates_no_partial_account(): void
    {
        $this->post('/register', ['name' => 'Nova Pessoa', 'email' => 'new@example.test', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertServiceUnavailable();
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
        $this->assertGuest();
    }

    public function test_email_recovery_does_not_reveal_account_existence_or_throttled_token(): void
    {
        config(['innovation.invitation_delivery' => 'email']);
        $user = User::factory()->create(['email' => 'known@example.test']);
        Notification::fake();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors()->assertSessionHas('status');
        $message = session('status');
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertSessionHasNoErrors()->assertSessionHas('status', $message);
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors()->assertSessionHas('status', $message);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_email_recovery_requests_are_rate_limited(): void
    {
        config(['innovation.invitation_delivery' => 'email']);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/forgot-password', ['email' => 'missing@example.test'])->assertRedirect();
        }
        $this->postJson('/forgot-password', ['email' => 'missing@example.test'])->assertTooManyRequests();
    }
}
