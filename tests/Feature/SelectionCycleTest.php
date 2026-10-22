<?php

namespace Tests\Feature;

use App\Models\BonusAward;
use App\Models\BonusPolicy;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\Sector;
use App\Models\TechnicalModel;
use App\Models\User;
use App\Models\VotingCycle;
use App\Services\CycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SelectionCycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    private User $voter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->voter = User::where('email', 'avaliador@corp.local')->firstOrFail();
    }

    private function proposal(string $status = 'triage'): Idea
    {
        return Idea::create(['author_id' => $this->author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Nome privado', 'problem_description' => 'Problema privado', 'solution_description' => 'Texto privado', 'expected_results' => 'Estimativa privada', 'resources_needed' => 'A definir', 'status' => $status, 'is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Proposta revisada', 'problem' => 'Problema público', 'solution' => 'Solução proposta', 'results' => 'Benefício esperado', 'category' => 'Processos', 'sectors' => ['Tecnologia']]]);
    }

    private function cycle(array $overrides = []): VotingCycle
    {
        $model = TechnicalModel::factory()->create();
        $policy = BonusPolicy::factory()->create(array_intersect_key($overrides, array_flip(['reward_amount', 'minimum_score', 'anomaly_threshold', 'allow_abstention'])));
        $overrides = [...$overrides, 'technical_model_id' => $model->id, 'bonus_policy_id' => $policy->id];
        $this->actingAs($this->admin)->post(route('cycles.store'), [...['purpose' => 'selection', 'name' => 'Seleção de propostas', 'starts_at' => now()->addHour()->toDateTimeString(), 'ends_at' => now()->addHours(2)->toDateTimeString(), 'max_selections' => 1], ...$overrides])->assertSessionHasNoErrors();

        return VotingCycle::latest('id')->firstOrFail();
    }

    private function open(VotingCycle $cycle, array $ideas): void
    {
        $this->actingAs($this->admin)->post(route('cycles.open', $cycle), ['ideas' => array_map(fn ($idea) => $idea->id, $ideas)])->assertSessionHasNoErrors();
        $this->travelTo($cycle->starts_at->copy()->addMinute());
    }

    private function vote(VotingCycle $cycle, Idea $idea, ?User $voter = null, int $score = 5): void
    {
        $scores = $cycle->criteria()->pluck('id')->mapWithKeys(fn ($id) => [$id => $score])->all();
        $this->actingAs($voter ?? $this->voter)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => $scores])->assertSessionHasNoErrors();
    }

    private function close(VotingCycle $cycle): void
    {
        $this->travelTo($cycle->ends_at->copy()->addSecond());
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
    }

    private function decision(VotingCycle $cycle, Idea $idea, string $decision = 'selected', string $notes = 'Proposta viável com equipe e recursos disponíveis.')
    {
        return $this->actingAs($this->admin)->post(route('cycles.decision', [$cycle, $idea]), compact('decision', 'notes'));
    }

    public function test_quick_registration_does_not_require_resources_or_estimated_results(): void
    {
        $this->actingAs($this->author)->post(route('ideas.store'), ['title' => 'Lembrete de melhoria', 'problem_description' => 'Problema observado', 'solution_description' => 'Sugestão inicial', 'origin_sector_id' => Sector::first()->id, 'beneficiary_sectors' => [Sector::first()->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ideas', ['title' => 'Lembrete de melhoria', 'status' => 'submitted', 'is_public' => false]);
    }

    public function test_proposal_requires_triage_and_explicit_privacy_review_but_not_implementation(): void
    {
        $idea = $this->proposal('submitted');
        $data = ['title' => 'Título público', 'problem' => 'Problema revisado', 'solution' => 'Solução revisada', 'results' => 'Benefício estimado', 'category' => 'Processos', 'privacy_reviewed' => 1];
        $this->actingAs($this->admin)->post(route('ideas.publish-proposal', $idea), $data)->assertSessionHasErrors('status');
        $idea->update(['status' => 'triage', 'is_public' => false, 'published_at' => null]);
        $this->post(route('ideas.publish-proposal', $idea), [...$data, 'privacy_reviewed' => 0])->assertSessionHasErrors('privacy_reviewed');
        $this->actingAs($this->author)->postJson(route('ideas.publish-proposal', $idea), $data)->assertForbidden();
        $this->actingAs($this->admin)->post(route('ideas.publish-proposal', $idea), $data)->assertSessionHasNoErrors();
        $this->assertSame('triage', $idea->fresh()->status);
        $this->assertTrue($idea->fresh()->is_public);
        $this->actingAs($this->voter)->get(route('ideas.show', $idea))->assertOk()->assertSeeText('Proposta de melhoria');
        $this->assertDatabaseCount('idea_results', 0);
    }

    public function test_early_idea_can_be_voted_without_results_and_closing_does_not_approve_it(): void
    {
        $idea = $this->proposal();
        $cycle = $this->cycle();
        $this->assertTrue($cycle->isSelection());
        $this->assertCount(3, $cycle->criteria);
        $this->open($cycle, [$idea]);
        $this->actingAs($this->voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertOk()->assertSeeText('Esta ideia ainda não foi implementada. Avalie o benefício esperado.');
        $this->vote($cycle, $idea);
        $this->assertSame('triage', $idea->fresh()->status);
        $this->close($cycle);
        $this->get(route('cycles.show', $cycle))->assertOk()->assertSeeText('Registrar decisão da equipe');
        $this->assertSame('triage', $idea->fresh()->status);
        $this->assertDatabaseCount('bonus_awards', 0);
        $outcome = json_decode($cycle->ideas()->first()->pivot->outcome, true);
        $this->assertEquals(100, $outcome['final_score']);
        $this->assertTrue($outcome['eligible']);
        $this->assertDatabaseCount('idea_evaluations', 0);
        $this->decision($cycle, $idea)->assertSessionHasNoErrors();
        $this->assertSame('approved', $idea->fresh()->status);
        $this->assertDatabaseHas('cycle_idea_decisions', ['idea_id' => $idea->id, 'decision' => 'selected']);
        $this->assertDatabaseCount('bonus_awards', 0);
        $this->post(route('ideas.plan', $idea), ['owner_user_id' => $this->voter->id, 'title' => 'Executar proposta', 'plan' => 'Preparar e acompanhar a melhoria.', 'due_date' => now()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Iniciar execução com o responsável.'])->assertSessionHasNoErrors();
        $this->assertSame('implementation', $idea->fresh()->status);
    }

    public function test_unreviewed_proposals_and_simultaneous_cycles_are_rejected(): void
    {
        $idea = $this->proposal();
        $cycle = $this->cycle();
        $idea->update(['published_at' => null]);
        $this->actingAs($this->admin)->post(route('cycles.open', $cycle), ['ideas' => [$idea->id]])->assertSessionHasErrors('ideas');
        $idea->update(['published_at' => now()]);
        app(CycleService::class)->open($this->admin, $cycle, [$idea->id]);
        $other = $this->cycle();
        $this->get(route('cycles.show', $other))->assertOk()->assertSeeText('Esta ideia já participa de uma votação aberta ou programada. Conclua esse ciclo primeiro.')->assertViewHas('unavailableIdeas', fn ($reasons) => $reasons->has($idea->id));
        $this->post(route('cycles.open', $other), ['ideas' => [$idea->id]])->assertSessionHasErrors('ideas');
    }

    public function test_author_coauthor_and_duplicate_votes_are_blocked_and_shares_freeze(): void
    {
        $idea = $this->proposal();
        $coauthor = User::factory()->create();
        $coauthor->assignRole('employee');
        $this->actingAs($this->admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $this->author->id, 'share' => 60], ['user_id' => $coauthor->id, 'share' => 40]]])->assertSessionHasNoErrors();
        $cycle = $this->cycle();
        $this->open($cycle, [$idea]);
        $data = ['voting_cycle_id' => $cycle->id, 'scores' => $cycle->criteria()->pluck('id')->mapWithKeys(fn ($id) => [$id => 5])->all()];
        foreach ([$this->author, $coauthor] as $person) {
            $this->actingAs($person)->postJson(route('ideas.vote', $idea), $data)->assertForbidden();
        }
        $this->vote($cycle, $idea);
        $this->post(route('ideas.vote', $idea), $data)->assertSessionHasErrors('vote');
        $this->actingAs($this->admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $this->author->id, 'share' => 100]]])->assertSessionHasErrors('contributors');
        $this->assertDatabaseCount('votes', 1);
    }

    public function test_unselected_idea_stays_in_bank_and_can_enter_another_cycle(): void
    {
        $idea = $this->proposal();
        $cycle = $this->cycle();
        $this->open($cycle, [$idea]);
        $this->close($cycle);
        $next = $this->cycle();
        $this->get(route('cycles.show', $next))->assertOk()->assertSeeText('Esta ideia aguarda a decisão da equipe no ciclo anterior. Registre a decisão antes de incluí-la novamente.')->assertViewHas('unavailableIdeas', fn ($reasons) => $reasons->has($idea->id));
        $this->post(route('cycles.open', $next), ['ideas' => [$idea->id]])->assertSessionHasErrors('ideas');
        $this->decision($cycle, $idea)->assertSessionHasErrors('decision'); // no votes
        $this->decision($cycle, $idea, 'bank', 'Vamos retomar quando houver recursos.')->assertSessionHasNoErrors();
        $this->assertSame('idea_bank', $idea->fresh()->status);
        $this->get(route('cycles.show', $next))->assertOk()->assertViewHas('unavailableIdeas', fn ($reasons) => ! $reasons->has($idea->id));
        $this->post(route('cycles.open', $next), ['ideas' => [$idea->id]])->assertSessionHasNoErrors();
        $this->assertSame('idea_bank', $idea->fresh()->status);
        $this->get(route('ideas.show', $idea))->assertOk();
    }

    public function test_selection_limit_immutable_decisions_and_private_justifications(): void
    {
        $one = $this->proposal();
        $two = $this->proposal();
        $cycle = $this->cycle();
        $this->open($cycle, [$one, $two]);
        $this->vote($cycle, $one);
        $this->vote($cycle, $two);
        $this->close($cycle);
        $this->decision($cycle, $one)->assertSessionHasNoErrors();
        $this->decision($cycle, $one, 'bank')->assertSessionHasErrors('decision');
        $this->decision($cycle, $two)->assertSessionHasErrors('decision');
        $this->decision($cycle, $two, 'bank')->assertSessionHasNoErrors();
        $this->actingAs($this->voter)->get(route('cycles.show', $cycle))->assertOk()->assertDontSeeText('Proposta viável com equipe e recursos disponíveis.')->assertDontSeeText('Nome privado')->assertSeeText('Selecionada para avançar')->assertSeeText('Manter no banco de ideias');
    }

    public function test_selection_reward_needs_separate_approval_and_does_not_finalize_execution(): void
    {
        $idea = $this->proposal();
        $coauthor = User::factory()->create();
        $coauthor->assignRole('employee');
        $this->actingAs($this->admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $this->author->id, 'share' => 60], ['user_id' => $coauthor->id, 'share' => 40]]])->assertSessionHasNoErrors();
        $cycle = $this->cycle(['reward_amount' => 101.01]);
        $this->open($cycle, [$idea]);
        $this->vote($cycle, $idea);
        $this->close($cycle);
        $this->assertDatabaseCount('bonus_awards', 0);
        $this->decision($cycle, $idea)->assertSessionHasNoErrors();
        $award = BonusAward::firstOrFail();
        $this->assertSame('Ideia selecionada', $award->category);
        $this->assertNull($award->approved_at);
        $this->assertSame(10101, DB::table('award_allocations')->get()->sum(fn ($allocation) => (int) round($allocation->amount * 100)));
        $this->assertDatabaseHas('award_allocations', ['bonus_award_id' => $award->id, 'user_id' => $this->author->id, 'amount' => 60.60]);
        $this->assertDatabaseHas('award_allocations', ['bonus_award_id' => $award->id, 'user_id' => $coauthor->id, 'amount' => 40.41]);
        $this->post(route('awards.approve', $award), ['notes' => 'Contribuição selecionada e reconhecimento autorizado.'])->assertSessionHasNoErrors();
        $this->post(route('awards.publish', $award))->assertSessionHasNoErrors();
        $this->assertNotNull($award->fresh()->published_at);
        $this->assertSame('approved', $idea->fresh()->status);
        $this->post(route('cycles.finalize', [$cycle, $idea]), ['notes' => 'Encerrar processo antes de executar.'])->assertSessionHasErrors();
    }

    public function test_voting_alert_requires_review_before_selection(): void
    {
        $idea = $this->proposal();
        $cycle = $this->cycle(['anomaly_threshold' => 2]);
        $this->open($cycle, [$idea]);
        $this->vote($cycle, $idea);
        $this->vote($cycle, $idea, $this->admin);
        $this->close($cycle);
        $this->decision($cycle, $idea)->assertSessionHasErrors('decision');
        $this->post(route('cycles.review', [$cycle, $idea]), ['decision' => 'validated', 'notes' => 'As notas iguais refletem a concordância dos participantes.'])->assertSessionHasNoErrors();
        $this->decision($cycle, $idea)->assertSessionHasNoErrors();
    }

    public function test_bank_decision_does_not_revert_implementation_and_snapshot_is_preserved(): void
    {
        $idea = $this->proposal('implementation');
        $cycle = $this->cycle();
        $this->open($cycle, [$idea]);
        $idea->update(['publication' => ['title' => 'Título alterado']]);
        $this->actingAs($this->voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertOk()->assertSeeText('Em implementação')->assertSeeText('Proposta revisada')->assertDontSeeText('Título alterado');
        $this->close($cycle);
        $this->decision($cycle, $idea, 'bank')->assertSessionHasNoErrors();
        $this->assertSame('implementation', $idea->fresh()->status);
    }

    public function test_cycle_decisions_cannot_be_registered_by_participants_or_employees(): void
    {
        $idea = $this->proposal();
        $cycle = $this->cycle();
        $this->open($cycle, [$idea]);
        $this->vote($cycle, $idea);
        $this->close($cycle);
        $this->actingAs($this->author)->postJson(route('cycles.decision', [$cycle, $idea]), ['decision' => 'selected', 'notes' => 'Quero selecionar minha própria proposta.'])->assertForbidden();
        DB::table('idea_conflicts')->insert(['idea_id' => $idea->id, 'user_id' => $this->admin->id, 'reason' => 'Conflito declarado', 'created_at' => now(), 'updated_at' => now()]);
        $this->decision($cycle, $idea)->assertSessionHasErrors('decision');
        $this->assertDatabaseCount('cycle_idea_decisions', 0);
    }

    public function test_selected_proposal_can_later_be_implemented_validated_and_recognized_for_results(): void
    {
        $idea = $this->proposal();
        $this->actingAs($this->admin)->post(route('ideas.publish-proposal', $idea), [...$idea->publication, 'privacy_reviewed' => 1])->assertSessionHasNoErrors();
        $selection = $this->cycle();
        $this->open($selection, [$idea]);
        $this->vote($selection, $idea);
        $this->close($selection);
        $this->decision($selection, $idea)->assertSessionHasNoErrors();
        $this->post(route('ideas.plan', $idea), ['owner_user_id' => $this->voter->id, 'title' => 'Executar melhoria', 'plan' => 'Preparar e medir.', 'due_date' => now()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Executar proposta selecionada.'])->assertSessionHasNoErrors();
        $measurements = [['indicator' => 'Tempo', 'before' => 20, 'after' => 10, 'unit' => 'horas', 'period_start' => now()->subDay()->toDateString(), 'period_end' => now()->toDateString(), 'evidence' => 'Relatório de medição']];
        $this->post(route('ideas.results', $idea), ['impact_summary' => 'Dez horas economizadas', 'measurements' => $measurements])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'validation', 'notes' => 'Execução concluída.'])->assertSessionHasNoErrors();
        $this->post(route('ideas.validate-results', $idea), ['notes' => 'Evidências verificadas.'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'completed', 'notes' => 'Resultados concluídos.'])->assertSessionHasNoErrors();
        // Selection approval does not invent a technical score; a reviewer records it afterwards.
        $criterion = EvaluationCriterion::create(['name' => 'Viabilidade', 'description' => 'Execução e evidências', 'weight' => 100, 'active' => true]);
        $this->post(route('ideas.evaluate', $idea), ['scores' => [$criterion->id => 5], 'recommendation' => 'approve', 'notes' => 'Execução tecnicamente conferida.'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $idea->fresh()->status);
        $data = ['name' => 'Reconhecer resultados', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'weights' => ['real_results' => 50, 'technical' => 30, 'votes' => 20], 'criteria' => [['name' => 'Impacto', 'description' => 'Avalie resultados', 'weight' => 100]], 'result_indicators' => [['indicator' => 'Tempo', 'unit' => 'horas', 'direction' => 'decrease', 'target' => 10]], 'minimum_votes' => 1, 'allow_abstention' => false, 'category' => 'Resultado comprovado', 'minimum_score' => 0, 'reward_amount' => 200, 'max_awards' => 1];
        $results = app(CycleService::class)->create($this->admin, $data);
        // The early publication cannot be reused as if it already described measured results.
        $this->post(route('cycles.open', $results), ['ideas' => [$idea->id]])->assertSessionHasErrors('ideas');
        $this->post(route('ideas.publish', $idea), ['title' => 'Resultado revisado', 'problem' => 'Demora', 'solution' => 'Processo simplificado', 'results' => 'Dez horas economizadas com medição conferida.', 'category' => 'Processos', 'privacy_reviewed' => 1])->assertSessionHasNoErrors();
        $this->open($results, [$idea]);
        $this->vote($results, $idea);
        $this->close($results);
        $award = BonusAward::where('voting_cycle_id', $results->id)->firstOrFail();
        $this->post(route('awards.approve', $award), ['notes' => 'Benefício conferido e reconhecimento aprovado.'])->assertSessionHasNoErrors();
        $this->post(route('awards.publish', $award))->assertSessionHasNoErrors();
        $this->assertSame('finalized', $idea->fresh()->status);
        $this->assertDatabaseHas('cycle_idea_decisions', ['voting_cycle_id' => $selection->id, 'decision' => 'selected']);
    }

    public function test_abstentions_normalize_answered_weights_and_ties_use_idea_code(): void
    {
        $one = $this->proposal();
        $two = $this->proposal();
        $one->update(['idea_code' => 'IDE-AAAA']);
        $two->update(['idea_code' => 'IDE-ZZZZ']);
        $cycle = $this->cycle(['allow_abstention' => true]);
        $this->open($cycle, [$two, $one]);
        $ids = $cycle->criteria()->pluck('id')->all();
        foreach ([$one, $two] as $idea) {
            $this->actingAs($this->voter)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$ids[0] => null, $ids[1] => 5, $ids[2] => null]])->assertSessionHasNoErrors();
        }
        $this->close($cycle);
        $outcome = json_decode($cycle->ideas()->where('ideas.id', $one->id)->first()->pivot->outcome, true);
        $this->assertEquals(100, $outcome['final_score']);
        $this->assertSame(1, $outcome['rank']);
    }

    public function test_vote_cannot_use_another_cycles_criteria_or_an_unattached_idea(): void
    {
        $idea = $this->proposal();
        $outside = $this->proposal();
        $cycle = $this->cycle();
        $other = $this->cycle();
        $this->open($cycle, [$idea]);
        $scores = $other->criteria()->pluck('id')->mapWithKeys(fn ($id) => [$id => 5])->all();
        $this->actingAs($this->voter)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => $scores])->assertSessionHasErrors('scores');
        $scores = $cycle->criteria()->pluck('id')->mapWithKeys(fn ($id) => [$id => 5])->all();
        $this->post(route('ideas.vote', $outside), ['voting_cycle_id' => $cycle->id, 'scores' => $scores])->assertSessionHasErrors('vote');
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_new_negative_technical_opinion_blocks_execution_of_selected_idea(): void
    {
        $idea = $this->proposal();
        $cycle = $this->cycle();
        $this->open($cycle, [$idea]);
        $this->vote($cycle, $idea);
        $this->close($cycle);
        $this->decision($cycle, $idea)->assertSessionHasNoErrors();
        $criterion = EvaluationCriterion::create(['name' => 'Viabilidade', 'description' => 'Riscos da execução', 'weight' => 100, 'active' => true]);
        $this->post(route('ideas.evaluate', $idea), ['scores' => [$criterion->id => 1], 'recommendation' => 'reject', 'notes' => 'Um impedimento técnico foi encontrado.'])->assertSessionHasNoErrors();
        $this->post(route('ideas.plan', $idea), ['owner_user_id' => $this->voter->id, 'title' => 'Plano', 'plan' => 'Executar melhoria', 'due_date' => now()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Tentar iniciar mesmo com o impedimento.'])->assertSessionHasErrors('status');
        $this->assertSame('approved', $idea->fresh()->status);
        $this->post(route('ideas.evaluate', $idea), ['scores' => [$criterion->id => 5], 'recommendation' => 'approve', 'notes' => 'Impedimento resolvido e nova avaliação registrada.'])->assertSessionHasNoErrors();
        $this->post(route('ideas.approve', $idea), ['status' => 'implementation', 'notes' => 'Executar após resolver o impedimento.'])->assertSessionHasNoErrors();
        $this->assertSame('implementation', $idea->fresh()->status);
    }
}
