<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_admin_can_update_another_users_permissions(): void
    {
        Permission::firstOrCreate(['name' => 'manage_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'employee']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('employee');

        $response = $this
            ->actingAs($admin)
            ->post('/profile/permissions/' . $targetUser->id, [
                'role' => 'admin',
                'permissions' => ['manage_admin'],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/' . $targetUser->id);

        $targetUser->refresh();

        $this->assertTrue($targetUser->hasRole('admin'));
        $this->assertTrue($targetUser->hasPermissionTo('manage_admin'));
    }

    public function test_admin_can_manage_another_users_profile_without_personal_security_forms(): void
    {
        Permission::firstOrCreate(['name' => 'manage_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $targetUser = User::factory()->create(['name' => 'Colaborador Teste']);

        $this->actingAs($admin)
            ->get('/profile/' . $targetUser->id)
            ->assertOk()
            ->assertSee('Acesso de Colaborador Teste')
            ->assertDontSee('Senha da conta')
            ->assertDontSee('Excluir conta');

        $this->patch('/profile/' . $targetUser->id, [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/' . $targetUser->id);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
