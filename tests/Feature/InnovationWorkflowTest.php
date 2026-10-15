<?php

namespace Tests\Feature;

use App\Models\BonusAward;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\Notification;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use App\Services\CycleService;
use App\Services\IdeaScoringService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InnovationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $evaluator;

    private User $author;

    private User $voter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->evaluator = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $this->author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->voter = User::factory()->create();
        $this->voter->assignRole('employee');
    }

    private function idea(string $status = 'submitted'): Idea
    {
        return Idea::create(['author_id' => $this->author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Título interno confidencial', 'problem_description' => 'Problema interno confidencial', 'solution_description' => 'Solução interna confidencial', 'expected_results' => 'Estimativa privada', 'resources_needed' => 'Recursos', 'status' => $status]);
    }

    private function cycleData(): array
    {
        return ['name' => 'Ciclo controlado', 'starts_at' => now()->addHour()->format('Y-m-d H:i:s'), 'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'), 'weights' => ['real_results' => 50, 'technical' => 30, 'votes' => 20], 'criteria' => [['name' => 'Impacto', 'description' => 'Avalie o impacto comprovado', 'weight' => 100]], 'result_indicators' => [['indicator' => 'Tempo', 'unit' => 'horas', 'direction' => 'decrease', 'target' => 10]], 'minimum_votes' => 1, 'allow_abstention' => false, 'category' => 'Melhoria', 'minimum_score' => 50, 'reward_amount' => 250, 'max_awards' => 1];
    }

    private function measurements(): array
    {
        return [['indicator' => 'Tempo', 'before' => 20, 'after' => 12, 'unit' => 'horas', 'period_start' => now()->subDays(10)->toDateString(), 'period_end' => now()->subDay()->toDateString(), 'evidence' => 'Relatório interno de medições', 'qualitative' => null]];
    }

    private function readyIdea(float $technical = 80): Idea
    {
        $idea = $this->idea('completed');
        $idea->result()->create(['measurements' => $this->measurements(), 'impact_summary' => 'Ganho medido', 'validated_by_user_id' => $this->evaluator->id, 'validated_at' => now()]);
        $idea->evaluations()->create(['evaluator_id' => $this->evaluator->id, 'technical_score' => $technical, 'recommendation' => 'approve', 'notes' => 'Viável', 'reviewed_at' => now()]);
        $idea->update(['is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Título revisado', 'problem' => 'Problema público', 'solution' => 'Solução pública', 'results' => 'Oito horas economizadas', 'category' => 'Melhoria', 'sectors' => ['Operações']]]);

        return $idea;
    }

    private function openCycle(?Idea $idea = null, array $overrides = []): array
    {
        $idea ??= $this->readyIdea();
        $service = app(CycleService::class);
        $cycle = $service->create($this->admin, array_replace($this->cycleData(), $overrides));
        $service->open($this->admin, $cycle, [$idea->id]);
        $this->travel(61)->minutes();

        return [$idea->fresh(), $cycle->fresh()];
    }

    private function voteData(VotingCycle $cycle, mixed $score = 4): array
    {
        return ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => $score]];
    }

    public function test_full_workflow_requires_evaluation_plan_validation_and_reviewed_publication(): void
    {
        $idea = $this->idea();
        $criterion = EvaluationCriterion::create(['name' => 'Viabilidade', 'description' => 'Escala técnica', 'weight' => 100, 'active' => true]);
        $this->actingAs($this->evaluator);
        foreach (['triage', 'evaluation'] as $status) {
            $this->post(route('ideas.approve', $idea), ['status' => $status, 'notes' => 'Etapa conferida'])->assertSessionHasNoErrors();
        }
        $this->post(route('ideas.approve', $idea), ['status' => 'approved', 'notes' => 'Aprovar'])->assertSessionHasErrors('status');
        $this->post(route('ideas.evaluate', $idea), ['scores' => [$criterion->id => 5], 'recommendation' => 'approve', 'notes' => 'Parecer favorável'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'approved', 'notes' => 'Aprovação técnica'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Início'])->assertSessionHasErrors('status');
        $this->post(route('ideas.plan', $idea), ['owner_user_id' => $this->evaluator->id, 'title' => 'Executar melhoria', 'plan' => 'Medir, implementar e conferir', 'due_date' => now()->addWeek()->toDateString()])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Início'])->assertSessionHasNoErrors();
        $this->post(route('ideas.results', $idea), ['impact_summary' => 'Resultado medido', 'measurements' => $this->measurements()])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'validation', 'notes' => 'Execução concluída'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'completed', 'notes' => 'Concluir'])->assertSessionHasErrors('status');
        $this->post(route('ideas.validate-results', $idea), ['notes' => 'Evidências conferidas'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'completed', 'notes' => 'Concluída'])->assertSessionHasNoErrors();
        $this->post(route('ideas.publish', $idea), ['title' => 'Título revisado', 'problem' => 'Problema público', 'solution' => 'Solução pública', 'results' => '8 horas economizadas; período e evidências conferidos', 'category' => 'Melhoria', 'privacy_reviewed' => 1])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ideas', ['id' => $idea->id, 'status' => 'completed', 'is_public' => true]);
        $this->assertDatabaseHas('idea_evaluations', ['idea_id' => $idea->id, 'technical_score' => 100]);
        $this->assertDatabaseHas('idea_status_histories', ['idea_id' => $idea->id, 'from_status' => 'validation', 'to_status' => 'completed']);
        $result = $idea->fresh()->result;
        $this->assertSame(-8.0, (float) $result->measurements[0]['difference']);
        $this->assertSame(-40.0, (float) $result->measurements[0]['percentage']);
        $this->assertNotNull($result->validated_at);
        $this->actingAs($this->voter)->get(route('ideas.show', $idea))->assertOk()->assertSeeText('Título revisado')->assertDontSeeText('Título interno confidencial')->assertDontSeeText('Problema interno confidencial')->assertDontSeeText($this->author->name);
    }

    public function test_creation_ignores_spoofed_author_and_rejects_duplicate_sectors(): void
    {
        $data = ['title' => 'Nova proposta', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Estimativa', 'resources_needed' => 'Recursos', 'origin_sector_id' => Sector::first()->id, 'beneficiary_sectors' => [Sector::first()->id], 'author_id' => $this->admin->id, 'is_public' => true, 'status' => 'finalized'];
        $this->actingAs($this->author)->post(route('ideas.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ideas', ['title' => 'Nova proposta', 'author_id' => $this->author->id, 'is_public' => false, 'status' => 'submitted']);
        $before = Idea::count();
        $data['beneficiary_sectors'][] = Sector::first()->id;
        $this->post(route('ideas.store'), $data)->assertSessionHasErrors('beneficiary_sectors.0');
        $this->assertSame($before, Idea::count());
    }

    public function test_invalid_transition_and_rejection_without_reason_do_not_change_history(): void
    {
        $idea = $this->idea();
        $this->actingAs($this->evaluator)->post(route('ideas.approve', $idea), ['status' => 'completed', 'notes' => 'Pular etapas'])->assertSessionHasErrors('status');
        $idea->update(['status' => 'triage']);
        $this->post(route('ideas.approve', $idea), ['status' => 'rejected'])->assertSessionHasErrors('notes');
        $this->assertSame(0, $idea->statusHistory()->count());
        $this->post(route('ideas.approve', $idea), ['status' => 'rejected', 'notes' => 'Inviável com os recursos atuais'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notifications', ['user_id' => $this->author->id, 'type' => 'status_changed']);
    }

    public function test_evaluator_identity_access_is_independent_of_evaluation_permission(): void
    {
        $idea = $this->idea('evaluation');
        $this->assertTrue($this->evaluator->can('evaluate', $idea));
        $this->assertFalse($this->evaluator->can('viewAuthorIdentity', $idea));
        $this->actingAs($this->evaluator)->get(route('ideas.show', $idea))->assertOk()->assertDontSeeText($this->author->name);
        $this->evaluator->givePermissionTo('view_author_identity');
        $this->assertTrue($this->evaluator->can('viewAuthorIdentity', $idea));
    }

    public function test_account_deactivation_preserves_ideas_votes_and_history(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle))->assertSessionHasNoErrors();
        $this->actingAs($this->author)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertSoftDeleted($this->author);
        $this->assertModelExists($idea);
        $this->assertDatabaseHas('votes', ['idea_id' => $idea->id]);
        $this->assertDatabaseHas('idea_status_histories', ['idea_id' => $idea->id, 'to_status' => 'voting']);
        $this->post(route('login'), ['email' => $this->author->email, 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_private_unimplemented_and_own_ideas_cannot_receive_votes(): void
    {
        $idea = $this->idea();
        $this->actingAs($this->voter)->postJson(route('ideas.vote', $idea), [])->assertForbidden();
        [$ready, $cycle] = $this->openCycle();
        $this->actingAs($this->author)->postJson(route('ideas.vote', $ready), $this->voteData($cycle))->assertForbidden();
        $this->assertDatabaseCount('votes', 0);
    }

    #[TestWith([0])]
    #[TestWith([6])]
    #[TestWith([2.5])]
    #[TestWith(['texto'])]
    public function test_invalid_vote_scores_are_rejected_without_partial_vote(mixed $score): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle, $score))->assertSessionHasErrors('scores.'.$cycle->criteria()->first()->id);
        $this->assertDatabaseCount('votes', 0);
        $this->assertDatabaseCount('vote_scores', 0);
    }

    public function test_vote_period_and_cycle_membership_are_enforced(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->travelBack();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle))->assertSessionHasErrors('vote');
        $this->travel(120)->minutes();
        $this->post(route('ideas.vote', $idea), $this->voteData($cycle))->assertSessionHasErrors('vote');
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_criteria_from_another_cycle_and_empty_votes_are_rejected(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [999 => 4]])->assertSessionHasErrors('scores');
        $this->post(route('ideas.vote', $idea), $this->voteData($cycle, null))->assertSessionHasErrors('scores');
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_duplicate_vote_is_rejected_by_database_constraint(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle))->assertSessionHasNoErrors();
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('votes')->insert(['idea_id' => $idea->id, 'user_id' => $this->voter->id, 'voting_cycle_id' => $cycle->id, 'status' => 'cast']);
    }

    public function test_cycle_apuration_and_awards_require_explicit_approval_and_publication(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle, 5))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasErrors('cycle');
        $this->travel(60)->minutes();
        $this->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $outcome = json_decode($cycle->ideas()->first()->pivot->outcome, true);
        $this->assertSame(84.0, (float) $outcome['final_score']);
        $award = BonusAward::where('voting_cycle_id', $cycle->id)->firstOrFail();
        $this->assertNull($award->approved_at);
        $this->post(route('awards.publish', $award))->assertSessionHasErrors('award');
        $this->post(route('awards.approve', $award), ['notes' => 'Evidências e alertas revisados'])->assertSessionHasNoErrors();
        $this->assertNotNull($award->fresh()->approved_at);
        $this->assertNull($award->fresh()->published_at);
        $this->post(route('awards.publish', $award))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ideas', ['id' => $idea->id, 'status' => 'finalized']);
        $this->actingAs($this->voter)->get(route('cycles.show', $cycle))->assertOk()->assertSeeText('Divulgado')->assertDontSeeText($this->author->name)->assertDontSeeText($this->voter->email);
    }

    public function test_insufficient_votes_do_not_generate_final_score_or_bonus(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->travel(60)->minutes();
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $outcome = json_decode($cycle->ideas()->first()->pivot->outcome, true);
        $this->assertNull($outcome['final_score']);
        $this->assertFalse($outcome['eligible']);
        $this->assertDatabaseCount('bonus_awards', 0);
    }

    public function test_conflict_declaration_blocks_voting_and_evaluation(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.conflict', $idea), ['reason' => 'Relação direta com a proposta'])->assertSessionHasNoErrors();
        $this->postJson(route('ideas.vote', $idea), $this->voteData($cycle))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'conflict_declared', 'causer_id' => $this->voter->id]);
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_cycles_and_criteria_cannot_be_changed_after_opening(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->expectException(\LogicException::class);
        $cycle->update(['weight_configuration' => ['real_results' => 100]]);
    }

    public function test_reports_audit_and_notifications_enforce_access_and_csv_escapes_formulas(): void
    {
        $idea = $this->readyIdea();
        $idea->update(['publication' => [...$idea->publication, 'title' => '=HYPERLINK("malicioso")']]);
        $notification = Notification::create(['user_id' => $this->author->id, 'title' => 'Privada', 'message' => 'Privada']);
        $this->actingAs($this->voter)->getJson(route('reports.export'))->assertForbidden();
        $this->getJson(route('audit.index'))->assertForbidden();
        $this->post(route('notifications.read', $notification))->assertForbidden();
        $response = $this->actingAs($this->admin)->get(route('reports.export'))->assertOk()->assertDownload('relatorio-ideias.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString($this->author->email, $csv);
        $this->assertStringNotContainsString($this->author->name, $csv);
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('audit.index'))->assertOk();
    }

    public function test_cycle_creation_rejects_invalid_weight_totals(): void
    {
        $data = $this->cycleData();
        $data['weights']['real_results'] = 60;
        $this->actingAs($this->admin)->post(route('cycles.store'), $data)->assertSessionHasErrors('weights');
        $data = $this->cycleData();
        $data['criteria'][0]['weight'] = 80;
        $this->post(route('cycles.store'), $data)->assertSessionHasErrors('criteria');
    }

    public function test_published_html_escapes_user_content(): void
    {
        $idea = $this->readyIdea();
        $idea->update(['publication' => [...$idea->publication, 'solution' => '<script>alert(1)</script>']]);
        $this->actingAs($this->voter)->get(route('ideas.show', $idea))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_tie_breaker_prefers_real_results_before_technical_score(): void
    {
        $first = $this->readyIdea(80);
        $second = $this->readyIdea(100);
        $second->result->update(['measurements' => [[...$this->measurements()[0], 'after' => 14]]]);
        $service = app(CycleService::class);
        $cycle = $service->create($this->admin, [...$this->cycleData(), 'weights' => ['real_results' => 40, 'technical' => 40, 'votes' => 20]]);
        $service->open($this->admin, $cycle, [$first->id, $second->id]);
        $this->travel(61)->minutes();
        $this->actingAs($this->voter)->post(route('ideas.vote', $first), $this->voteData($cycle, 5))->assertSessionHasNoErrors();
        $this->post(route('ideas.vote', $second), $this->voteData($cycle, 5))->assertSessionHasNoErrors();
        $this->travel(60)->minutes();
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bonus_awards', ['idea_id' => $first->id, 'voting_cycle_id' => $cycle->id, 'score' => 84]);
        $this->assertDatabaseMissing('bonus_awards', ['idea_id' => $second->id]);
    }

    public function test_abstention_uses_only_answered_criteria_weights(): void
    {
        [$idea, $cycle] = $this->openCycle(overrides: ['allow_abstention' => true, 'criteria' => [['name' => 'Impacto', 'description' => 'Impacto medido', 'weight' => 75], ['name' => 'Qualidade', 'description' => 'Qualidade medida', 'weight' => 25]]]);
        $ids = $cycle->criteria()->pluck('id')->all();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$ids[0] => 5, $ids[1] => null]])->assertSessionHasNoErrors();
        $this->travel(60)->minutes();
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $outcome = json_decode($cycle->ideas()->first()->pivot->outcome, true);
        $this->assertSame(100.0, (float) $outcome['votes']);
        $this->assertSame(84.0, (float) $outcome['final_score']);
    }

    public function test_failed_opening_rolls_back_previously_selected_ideas(): void
    {
        $valid = $this->readyIdea();
        $invalid = $this->idea();
        $cycle = app(CycleService::class)->create($this->admin, $this->cycleData());
        $this->actingAs($this->admin)->post(route('cycles.open', $cycle), ['ideas' => [$valid->id, $invalid->id]])->assertSessionHasErrors('ideas');
        $this->assertSame('completed', $valid->fresh()->status);
        $this->assertDatabaseCount('idea_voting_cycle', 0);
        $this->assertSame('draft', $cycle->fresh()->status);
        $this->assertNull($cycle->fresh()->frozen_configuration);
    }

    public function test_empty_result_values_require_limitation_without_fabricated_score(): void
    {
        $idea = $this->idea('implementation');
        $measurements = [[...$this->measurements()[0], 'before' => null, 'after' => null]];
        $this->actingAs($this->evaluator)->post(route('ideas.results', $idea), ['impact_summary' => 'Dados insuficientes', 'measurements' => $measurements])->assertSessionHasErrors('measurements');
        $this->assertDatabaseCount('idea_results', 0);
        $this->post(route('ideas.results', $idea), ['impact_summary' => 'Dados insuficientes', 'limitations' => 'Instrumento de medição indisponível', 'measurements' => $measurements])->assertSessionHasNoErrors();
        $this->assertNull($idea->fresh()->result->measurements[0]['difference']);
        $this->assertNull(app(IdeaScoringService::class)->resultsScore($measurements, $this->cycleData()['result_indicators']));
    }

    public function test_same_employee_can_vote_in_a_later_cycle(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle))->assertSessionHasNoErrors();
        $this->travel(60)->minutes();
        app(CycleService::class)->close($this->admin, $cycle);
        $next = app(CycleService::class)->create($this->admin, $this->cycleData());
        app(CycleService::class)->open($this->admin, $next, [$idea->id]);
        $this->travel(61)->minutes();
        $this->post(route('ideas.vote', $idea), $this->voteData($next))->assertSessionHasNoErrors();
        $this->assertSame(2, $idea->votes()->where('user_id', $this->voter->id)->count());
    }

    public function test_history_cannot_be_edited_by_ordinary_model_operations(): void
    {
        $idea = $this->idea();
        $this->actingAs($this->evaluator)->post(route('ideas.approve', $idea), ['status' => 'triage', 'notes' => 'Conferência'])->assertSessionHasNoErrors();
        $this->expectException(\LogicException::class);
        $idea->statusHistory()->first()->update(['notes' => 'Apagar justificativa']);
    }

    public function test_cycle_start_in_the_past_has_a_clear_portuguese_error(): void
    {
        $data = $this->cycleData();
        $data['starts_at'] = now()->subHour()->format('Y-m-d H:i:s');

        $this->actingAs($this->admin)->post(route('cycles.store'), $data)
            ->assertSessionHasErrors(['starts_at' => 'Escolha uma data e um horário de início futuros. Você precisa de tempo para selecionar as ideias e abrir o ciclo.']);
    }

    public function test_employee_can_view_cycles_without_admin_setup(): void
    {
        $this->actingAs($this->voter)->get(route('cycles.index'))
            ->assertOk()
            ->assertSeeText('Ciclos cadastrados')
            ->assertDontSeeText('Criar um ciclo')
            ->assertViewHas('indicatorOptions', fn ($options) => $options->isEmpty());
    }

    public function test_cycle_setup_suggests_indicators_from_completed_publications(): void
    {
        $this->readyIdea();
        $this->readyIdea();
        $response = $this->actingAs($this->admin)->get(route('cycles.index'));

        $response->assertOk()->assertViewHas('indicatorOptions', fn ($options) => $options->all() === [
            ['indicator' => 'Tempo', 'unit' => 'horas'],
        ]);
        $response->assertSeeText('Ajustar regras de avaliação');
    }

    public function test_new_pages_render_with_appropriate_permissions(): void
    {
        $this->actingAs($this->admin)->get(route('cycles.index'))->assertOk()->assertSeeText('Criar um ciclo');
        $response = $this->post(route('cycles.store'), $this->cycleData())->assertSessionHasNoErrors();
        $cycle = VotingCycle::where('name', 'Ciclo controlado')->firstOrFail();
        $response->assertRedirect(route('cycles.show', $cycle));
        $this->get(route('cycles.show', $cycle))->assertOk()->assertSeeText('Último passo: escolha as ideias');
        $this->get(route('admin.index'))->assertOk();
        $this->actingAs($this->evaluator)->get(route('evaluation.index'))->assertOk();
        $this->actingAs($this->voter)->getJson(route('cycles.show', $cycle))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_unassigned_account_cannot_browse_corporate_publications(): void
    {
        $idea = $this->readyIdea();
        $unassigned = User::factory()->create();
        $this->actingAs($unassigned)->getJson(route('ideas.show', $idea))->assertForbidden();
        $this->getJson(route('cycles.index'))->assertForbidden();
        $this->get(route('ideas.index'))->assertOk()->assertDontSeeText('Título revisado');
        $this->get(route('dashboard'))->assertOk()->assertDontSeeText('Título revisado');
    }

    public function test_exceptional_vote_invalidation_is_audited_without_deleting_scores(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), $this->voteData($cycle))->assertSessionHasNoErrors();
        $vote = $idea->votes()->firstOrFail();
        $this->actingAs($this->voter)->postJson(route('votes.invalidate', $vote), ['notes' => 'Alteração indevida'])->assertForbidden();
        $this->actingAs($this->admin)->post(route('votes.invalidate', $vote), ['notes' => 'Impedimento comprovado e revisado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('votes', ['id' => $vote->id, 'status' => 'invalidated']);
        $this->assertSame(1, $vote->scores()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'vote_invalidated']);
        $this->travel(60)->minutes();
        $this->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $outcome = json_decode($cycle->ideas()->first()->pivot->outcome, true);
        $this->assertSame(0, $outcome['vote_count']);
        $this->assertNull($outcome['final_score']);
    }

    public function test_identical_vote_pattern_is_flagged_for_review_without_excluding_votes(): void
    {
        [$idea, $cycle] = $this->openCycle();
        for ($index = 0; $index < 5; $index++) {
            $voter = User::factory()->create();
            $voter->assignRole('employee');
            $this->actingAs($voter)->post(route('ideas.vote', $idea), $this->voteData($cycle, 5))->assertSessionHasNoErrors();
        }
        $this->travel(60)->minutes();
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $outcome = json_decode($cycle->ideas()->first()->pivot->outcome, true);
        $this->assertTrue($outcome['review_required']);
        $this->assertSame(5, $outcome['vote_count']);
        $this->assertSame(84.0, (float) $outcome['final_score']);
    }

    public function test_frozen_voting_criteria_cannot_be_modified(): void
    {
        [$idea, $cycle] = $this->openCycle();
        $this->expectException(\LogicException::class);
        $cycle->criteria()->first()->update(['weight' => 20]);
    }

    public function test_internal_title_cannot_be_discovered_by_public_search(): void
    {
        $idea = $this->readyIdea();
        $this->actingAs($this->voter)->get(route('ideas.index', ['search' => 'interno confidencial']))->assertOk()->assertDontSeeText('Título revisado');
        $this->get(route('ideas.index', ['search' => 'revisado']))->assertOk()->assertSeeText('Título revisado');
    }
}
