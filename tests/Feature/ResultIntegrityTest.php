<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Idea;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResultIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function excessiveMeasurements(): array
    {
        return [
            'infinite input' => ['1e309', '1', 'measurements.0.before'],
            'overflow in subtraction' => ['1e308', '-1e308', 'measurements.0'],
            'overflow in percentage' => ['1e-308', '1e308', 'measurements.0'],
        ];
    }

    #[DataProvider('excessiveMeasurements')]
    public function test_unrepresentable_values_return_validation_error_without_changing_saved_results(string $before, string $after, string $error): void
    {
        $this->seed(DatabaseSeeder::class);
        $evaluator = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $idea = Idea::firstOrFail();
        $idea->update(['status' => 'implementation']);
        $idea->result()->create(['impact_summary' => 'Resultado preservado', 'measurements' => [['indicator' => 'Tempo', 'before' => 10, 'after' => 5, 'unit' => 'horas']]]);

        $this->actingAs($evaluator)->postJson(route('ideas.results', $idea), [
            'impact_summary' => 'Novo resultado',
            'measurements' => [['indicator' => 'Tempo', 'unit' => 'horas', 'before' => $before, 'after' => $after, 'period_start' => now()->subDays(2)->toDateString(), 'period_end' => now()->subDay()->toDateString(), 'evidence' => 'Evidência fictícia']],
        ])->assertUnprocessable()->assertJsonValidationErrors($error);

        $this->assertSame('Resultado preservado', $idea->fresh()->result->impact_summary);
        $this->assertDatabaseCount('idea_results', 1);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'results_recorded']);
    }

    public function test_reopened_implementation_requires_new_validation_and_preserves_old_evidence_in_audit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $evaluator = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $idea = Idea::firstOrFail();
        $idea->update(['status' => 'validation']);
        $oldValidation = now()->subDays(3)->startOfSecond();
        $idea->implementationPlan()->create(['title' => 'Plano anterior', 'plan' => 'Implementação original', 'status' => 'completed', 'completed_at' => $oldValidation]);
        $idea->result()->create(['impact_summary' => 'Resultado anterior', 'measurements' => [['indicator' => 'Tempo', 'unit' => 'horas', 'before' => 10, 'after' => 5]], 'validated_at' => $oldValidation, 'validated_by_user_id' => $evaluator->id]);

        $this->actingAs($evaluator)->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Retorno para correção.'])->assertSessionHasNoErrors();

        $current = $idea->fresh();
        $this->assertNull($current->result->validated_at);
        $this->assertNull($current->result->validated_by_user_id);
        $this->assertNull($current->implementationPlan->completed_at);
        $this->assertSame('planned', $current->implementationPlan->status);
        $audit = AuditLog::where('action', 'implementation_reopened')->sole();
        $this->assertNotNull($audit->details['previous_result']['validated_at']);
        $this->assertSame('Resultado anterior', $audit->details['previous_result']['impact_summary']);
        $this->post(route('ideas.approve', $idea), ['status' => 'validation', 'notes' => 'Correção concluída.'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'completed', 'notes' => 'Tentativa sem validação nova.'])->assertSessionHasErrors('status');
        $this->assertSame('validation', $idea->fresh()->status);
        $this->post(route('ideas.validate-results', $idea), ['notes' => 'Nova revisão dos resultados após correção.'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'completed', 'notes' => 'Validação nova concluída.'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $idea->fresh()->status);
        $this->assertTrue($idea->fresh()->result->validated_at->greaterThan($oldValidation));
    }
}
