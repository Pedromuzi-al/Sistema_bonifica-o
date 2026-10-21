<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_access_seeding_creates_no_demo_accounts_or_proposals(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->seed(AccessControlSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('ideas', 0);
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('permissions', 6);
    }

    public function test_demo_seeder_is_blocked_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        (new DatabaseSeeder)->run();
    }

    public function test_public_registration_can_be_disabled_without_disabling_login(): void
    {
        config(['innovation.registration_enabled' => false]);
        $this->get(route('register'))->assertNotFound();
        $this->post(route('register'), ['name' => 'Intruso', 'email' => 'externo@example.com', 'password' => 'Password1234', 'password_confirmation' => 'Password1234'])->assertNotFound();
        $this->get(route('login'))->assertOk();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_check_fails_for_insecure_configuration_without_exposing_secrets(): void
    {
        config(['app.debug' => true, 'database.connections.mysql.password' => 'segredo-de-teste']);
        $this->artisan('app:production-check')->expectsOutput('[PENDENTE] Depuração desativada')->doesntExpectOutput('segredo-de-teste')->assertFailed();
    }

    public function test_security_headers_and_private_cache_are_applied(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('employee');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_data_export_requires_password_confirmation_and_contains_only_the_current_account(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($user)->get(route('privacy.export'))->assertRedirect(route('password.confirm'));
        $response = $this->withSession(['auth.password_confirmed_at' => time()])->get(route('privacy.export'))->assertOk()->assertDownload('meus-dados.json');
        $data = json_decode($response->streamedContent(), true);
        $this->assertSame($user->email, $data['account']['email']);
        $this->assertArrayNotHasKey('password', $data['account']);
        $this->assertStringNotContainsString('admin@corp.local', $response->streamedContent());
    }

    public function test_privacy_requests_are_private_and_account_anonymization_revokes_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole('employee');
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('privacy.request'), ['reason' => 'Solicito anonimização dos meus dados de cadastro.'])->assertSessionHasNoErrors();
        $request = DB::table('privacy_requests')->first();
        $this->getJson(route('admin.privacy'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.privacy'))->assertOk()->assertSeeText($user->name);
        $this->post(route('admin.privacy.decision', $request->id), ['action' => 'anonymize', 'decision' => 'Revisão concluída; conta sem propostas, documentos ou pendências.', 'review_confirmed' => 1])->assertSessionHasNoErrors();
        $this->post(route('admin.users.reactivate', $user), ['reason' => 'Tentar recuperar acesso'])->assertSessionHasErrors('user');
        $this->assertNotNull(User::withTrashed()->findOrFail($user->id)->anonymized_at);
        $this->assertSoftDeleted($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Conta anonimizada']);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'email' => $user->email]);
        $this->assertDatabaseHas('privacy_requests', ['id' => $request->id, 'status' => 'account_anonymized']);
        $this->assertDatabaseHas('audit_logs', ['model_id' => $user->id, 'action' => 'privacy_request_reviewed']);
    }

    public function test_privacy_anonymization_cannot_remove_account_with_pending_ideas(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->post(route('privacy.request'), ['reason' => 'Solicito anonimização.'])->assertSessionHasNoErrors();
        $id = DB::table('privacy_requests')->value('id');
        $this->actingAs($admin)->post(route('admin.privacy.decision', $id), ['action' => 'anonymize', 'decision' => 'Análise dos dados pessoais e dos registros corporativos realizada.', 'review_confirmed' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email, 'deleted_at' => null]);
    }

    public function test_notification_retention_is_opt_in_and_preserves_unread_notifications(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create(['title' => 'Lida', 'message' => 'Texto', 'type' => 'test', 'read_at' => now()->subDays(60)]);
        $user->notifications()->create(['title' => 'Não lida', 'message' => 'Texto', 'type' => 'test']);
        $this->artisan('app:prune-read-notifications')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);
        config(['innovation.retention_days' => 30]);
        $this->artisan('app:prune-read-notifications')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['title' => 'Não lida']);
    }

    public function test_administrator_bootstrap_uses_a_hidden_password_and_requires_email_verification(): void
    {
        Notification::fake();
        $this->artisan('app:create-administrator', ['email' => 'gestao@empresa.com', '--name' => 'Gestão'])
            ->expectsQuestion('Senha do novo administrador (mínimo 12 caracteres)', 'SenhaForte12345')
            ->expectsQuestion('Confirme a senha', 'SenhaForte12345')->assertSuccessful();
        $user = User::where('email', 'gestao@empresa.com')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('SenhaForte12345', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_restore_command_refuses_a_main_database_target_before_reading_any_backup(): void
    {
        $previous = config('database.default');
        try {
            config(['database.default' => 'mysql']);
            $this->artisan('app:check-backup-restore', ['directory' => '/nonexistent-backup', '--database' => 'ideias_producao'])
                ->expectsOutput('Informe um banco MySQL exclusivo terminado em _test, diferente do banco da aplicação.')->assertFailed();
        } finally {
            config(['database.default' => $previous]);
        }
    }

    public function test_report_stage_durations_include_clarification_time_without_counting_it_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $idea = Idea::firstOrFail();
        IdeaStatusHistory::where('idea_id', $idea->id)->delete();
        foreach ([['evaluation', now()->subDays(4)], ['evaluation', now()->subDays(3)], ['approved', now()->subDay()]] as [$stage,$date]) {
            DB::table('idea_status_histories')->insert(['idea_id' => $idea->id, 'to_status' => $stage, 'from_status' => null, 'changed_by_user_id' => $admin->id, 'notes' => 'Histórico', 'created_at' => $date, 'updated_at' => $date]);
        }
        $this->actingAs($admin)->get(route('reports.index', ['view' => 'general']))->assertOk()->assertViewHas('stageDurations', fn ($durations) => round($durations['evaluation']) === 72.0);
    }
}
