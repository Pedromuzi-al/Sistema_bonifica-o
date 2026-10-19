<?php

namespace Tests\Audit;

use App\Models\Idea;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Reproduções da auditoria: PASS significa que a lacuna foi observada.
// Fora de tests/ para não transformar comportamentos inseguros em requisitos.
class AuditFindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_preserves_other_sessions_and_revocation_markers(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember-token']);
        $version = (int) $user->access_version;
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($user)->put('/password', ['current_password' => 'password', 'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('ChangedPassword123', $user->fresh()->password));
        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);
        $this->assertSame($version, (int) $user->fresh()->access_version);
        $this->assertSame('old-remember-token', $user->fresh()->remember_token);
        $this->withSession(['access_version' => $version])->get('/dashboard')->assertOk();
    }

    public function test_password_confirmation_accepts_twenty_wrong_attempts_without_throttling(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson('/confirm-password', ['password' => 'incorrect-password'])->assertUnprocessable();
        }
        $this->post('/confirm-password', ['password' => 'password'])->assertRedirect();
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_self_registration_creates_account_without_idea_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->post('/register', ['name' => 'Pessoa QA', 'email' => 'audit-registration@example.test', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertSessionHasNoErrors();
        $user = User::where('email', 'audit-registration@example.test')->firstOrFail();
        $user->markEmailAsVerified();
        $this->assertCount(0, $user->roles);
        $this->assertFalse($user->can('create', Idea::class));
        $this->actingAs($user)->getJson('/ideas/create')->assertForbidden();
        $this->get('/cycles')->assertForbidden();
    }

    public function test_email_can_be_changed_without_current_password(): void
    {
        config(['innovation.email_verification_required' => false]);
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => 'changed-by-session@example.test'])->assertSessionHasNoErrors();
        $this->assertSame('changed-by-session@example.test', $user->fresh()->email);
        $this->get('/dashboard')->assertOk();
    }

    public function test_excessive_numeric_measurement_causes_server_error(): void
    {
        $this->seed(DatabaseSeeder::class);
        $evaluator = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $idea = Idea::firstOrFail();
        $idea->update(['status' => 'implementation']);
        $this->actingAs($evaluator)->postJson('/ideas/'.$idea->id.'/results', [
            'impact_summary' => 'Teste isolado de limites numéricos.',
            'measurements' => [['indicator' => 'Tempo', 'unit' => 'horas', 'before' => '1e309', 'after' => '1', 'period_start' => now()->subDays(2)->toDateString(), 'period_end' => now()->subDay()->toDateString(), 'evidence' => 'Evidência fictícia']],
        ])->assertStatus(500);
        $this->assertDatabaseCount('idea_results', 0);
    }

    public function test_recovery_reveals_whether_email_exists(): void
    {
        config(['innovation.invitation_delivery' => 'email']);
        User::factory()->create(['email' => 'known@example.test']);
        $this->post('/forgot-password', ['email' => 'known@example.test'])->assertSessionHas('status')->assertSessionHasNoErrors();
        $this->flushSession();
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertSessionHasErrors('email');
    }

    public function test_rework_keeps_old_validation_and_allows_completion_without_revalidation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $evaluator = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $idea = Idea::firstOrFail();
        $idea->update(['status' => 'validation']);
        $oldValidation = now()->subDays(3)->startOfSecond();
        $idea->implementationPlan()->create(['title' => 'Plano antigo', 'plan' => 'Implementação original', 'status' => 'completed', 'completed_at' => $oldValidation]);
        $idea->result()->create(['impact_summary' => 'Resultado anterior', 'measurements' => [['indicator' => 'Tempo', 'unit' => 'horas', 'before' => 10, 'after' => 5]], 'validated_at' => $oldValidation, 'validated_by_user_id' => $evaluator->id]);
        $this->actingAs($evaluator);
        foreach (['implementation', 'validation', 'completed'] as $status) {
            $this->post('/ideas/'.$idea->id.'/approve', ['status' => $status, 'notes' => 'Retorno para correção e nova conferência.'])->assertSessionHasNoErrors();
        }
        $this->assertSame('completed', $idea->fresh()->status);
        $this->assertTrue($idea->fresh()->result->validated_at->equalTo($oldValidation));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'results_validated', 'model_id' => $idea->id]);
    }
}
