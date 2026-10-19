<?php

namespace Tests\Feature;

use App\Models\BonusAward;
use App\Models\BonusRule;
use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CycleReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    private VotingCycle $cycle;

    private Idea $idea;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        BonusRule::create(['name' => 'Regra de teste', 'minimum_score' => 0, 'max_awards' => 5, 'reward_amount' => 100]);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->employee = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->cycle = VotingCycle::create(['name' => 'Rodada de teste do relatório', 'starts_at' => now()->subHours(2), 'ends_at' => now()->subHour(), 'status' => 'closed', 'weight_configuration' => ['purpose' => 'selection', 'minimum_votes' => 1, 'minimum_score' => 0, 'max_selections' => 5, 'reward_amount' => 100]]);
        $this->idea = $this->entry('Título revisado congelado', 1, 87.5);
    }

    private function entry(string $title, int $rank, ?float $score): Idea
    {
        $idea = Idea::create(['author_id' => $this->employee->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Título privado alterado', 'problem_description' => 'Problema privado', 'solution_description' => 'Solução privada', 'expected_results' => '', 'resources_needed' => '', 'status' => 'ready_for_voting', 'is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Título público atual diferente']]);
        $this->cycle->ideas()->attach($idea->id, ['snapshot' => json_encode(['publication' => ['title' => $title, 'problem' => 'Problema revisado', 'solution' => 'Solução revisada', 'results' => 'Benefício esperado', 'sectors' => []]]), 'outcome' => json_encode(['rank' => $rank, 'final_score' => $score, 'vote_count' => 2, 'eligible' => $score !== null, 'review_required' => false])]);

        return $idea;
    }

    private function award(Idea $idea, int $amount, string $state): BonusAward
    {
        return BonusAward::create(['idea_id' => $idea->id, 'voting_cycle_id' => $this->cycle->id, 'rule_id' => BonusRule::firstOrFail()->id, 'user_id' => $this->employee->id, 'award_amount' => $amount, 'score' => 87.5, 'category' => 'Categoria privada '.$state, 'approved_at' => in_array($state, ['approved', 'published']) ? now() : null, 'published_at' => $state === 'published' ? now() : null, 'rejected_at' => $state === 'rejected' ? now() : null]);
    }

    public function test_report_defaults_to_latest_cycle_and_uses_frozen_title_and_zero_scores(): void
    {
        $this->entry('Ideia com zero válido', 2, 0);
        $this->entry('Ideia sem pontuação', 3, null);
        $this->actingAs($this->admin)->get(route('reports.index'))->assertOk()->assertViewHas('cycle', fn ($cycle) => $cycle->id === $this->cycle->id)->assertSeeText('Título revisado congelado')->assertDontSeeText('Título privado alterado')->assertDontSeeText('Título público atual diferente')->assertSeeText('0,00/100')->assertSeeText('Votos insuficientes')->assertSeeText('Aguardando decisão');
        $this->get(route('reports.index', ['cycle' => 999999]))->assertSessionHasErrors('cycle');
    }

    public function test_participants_are_unique_and_financial_states_are_not_added_together(): void
    {
        $second = $this->entry('Segunda ideia', 2, 80);
        $third = $this->entry('Terceira ideia', 3, 70);
        $fourth = $this->entry('Quarta ideia', 4, 60);
        foreach ([[$this->idea, $this->employee, 'cast'], [$second, $this->employee, 'cast'], [$this->idea, $this->admin, 'invalidated']] as [$idea,$user,$status]) {
            Vote::create(['idea_id' => $idea->id, 'user_id' => $user->id, 'voting_cycle_id' => $this->cycle->id, 'status' => $status]);
        }
        $this->award($this->idea, 100, 'pending');
        $this->award($second, 200, 'approved');
        $this->award($third, 300, 'published');
        $this->award($fourth, 400, 'rejected');
        DB::table('cycle_idea_decisions')->insert(['idea_id' => $second->id, 'voting_cycle_id' => $this->cycle->id, 'decided_by' => $this->admin->id, 'decision' => 'selected', 'notes' => 'JUSTIFICATIVA PRIVADA', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin)->get(route('reports.index'))->assertOk()->assertViewHas('cycleReport', fn ($data) => $data['people'] === 1 && $data['vote_count'] === 2 && $data['selected'] === 1 && $data['pending_amount'] == 100 && $data['approved_amount'] == 500 && $data['published_amount'] == 300)->assertSeeText('Não aprovada')->assertSeeText('Selecionada para avançar');
    }

    public function test_employee_sees_closed_ranking_but_only_published_rewards_and_no_private_notes(): void
    {
        $this->award($this->idea, 123, 'pending');
        DB::table('cycle_idea_decisions')->insert(['idea_id' => $this->idea->id, 'voting_cycle_id' => $this->cycle->id, 'decided_by' => $this->admin->id, 'decision' => 'selected', 'notes' => 'JUSTIFICATIVA PRIVADA', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->employee)->get(route('cycles.show', $this->cycle))->assertOk()->assertSeeText('87,50/100')->assertSeeText('Selecionada para avançar')->assertDontSeeText('JUSTIFICATIVA PRIVADA')->assertDontSeeText('Categoria privada pending')->assertDontSeeText('123,00')->assertDontSeeText('Título privado alterado');
        $this->getJson(route('reports.index'))->assertForbidden();
        $this->getJson(route('reports.cycle-export', ['cycle' => $this->cycle->id]))->assertForbidden();
    }

    public function test_open_cycle_never_exposes_ranking_or_csv_even_when_pivot_has_old_outcome(): void
    {
        DB::table('voting_cycles')->where('id', $this->cycle->id)->update(['status' => 'open']);
        $this->actingAs($this->admin)->get(route('reports.index'))->assertOk()->assertDontSeeText('87,50/100')->assertDontSeeText('Classificação das ideias')->assertSeeText('Resultado ainda não apurado');
        $this->getJson(route('reports.cycle-export', ['cycle' => $this->cycle->id]))->assertUnprocessable();
        $this->actingAs($this->employee)->get(route('cycles.show', $this->cycle))->assertOk()->assertDontSeeText('87,50/100')->assertDontSeeText('Classificação das ideias');
    }

    public function test_export_is_scoped_to_cycle_and_protects_against_spreadsheet_formulas(): void
    {
        $this->entry('=FORMULA_TESTE', 2, 50);
        $other = VotingCycle::create(['name' => 'Outro ciclo', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'status' => 'draft', 'weight_configuration' => ['purpose' => 'selection', 'minimum_votes' => 1, 'minimum_score' => 0, 'max_selections' => 5, 'reward_amount' => 100]]);
        $csv = $this->actingAs($this->admin)->get(route('reports.cycle-export', ['cycle' => $this->cycle->id]))->assertOk()->assertDownload('resultado-ciclo-'.$this->cycle->id.'.csv')->streamedContent();
        $this->assertStringContainsString("'=FORMULA_TESTE", $csv);
        $this->assertStringNotContainsString($this->employee->email, $csv);
        $this->assertStringNotContainsString('Outro ciclo', $csv);
        $this->get(route('reports.index', ['cycle' => $this->cycle->id]))->assertViewHas('cycle', fn ($value) => $value->id === $this->cycle->id);
    }

    public function test_empty_reports_show_creation_action_without_fabricated_results(): void
    {
        DB::table('idea_voting_cycle')->where('voting_cycle_id', $this->cycle->id)->delete();
        DB::table('voting_cycles')->delete();
        $this->actingAs($this->admin)->get(route('reports.index'))->assertOk()->assertSeeText('Seu primeiro relatório começa com um ciclo')->assertDontSeeText('Classificação das ideias');
    }

    public function test_award_report_is_admin_only_even_when_accessing_url_directly(): void
    {
        $this->actingAs($this->employee)->get(route('reports.awards'))->assertForbidden();
        $evaluator = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $this->actingAs($evaluator)->get(route('reports.awards'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('reports.index'))->assertSee(route('reports.awards'));
    }

    public function test_award_report_uses_recorded_allocations_and_preserves_legacy_recipient(): void
    {
        $award = $this->award($this->idea, 100, 'approved');
        DB::table('award_allocations')->insert([
            ['bonus_award_id' => $award->id, 'user_id' => $this->employee->id, 'amount' => 33.33],
            ['bonus_award_id' => $award->id, 'user_id' => $this->admin->id, 'amount' => 66.67],
        ]);
        // Today's contribution share must not change this round's stored amount.
        DB::table('idea_contributors')->insert(['idea_id' => $this->idea->id, 'user_id' => $this->employee->id, 'share' => 100]);
        $this->award($this->entry('Ideia com reconhecimento antigo', 2, 50), 200, 'published');
        $this->award($this->entry('Ideia não aprovada', 3, 40), 999, 'rejected');
        $this->actingAs($this->admin)->get(route('reports.awards'))->assertOk()
            ->assertSeeText('33,33')->assertSeeText('66,67')->assertSeeText($this->employee->name)
            ->assertSeeText($this->admin->name)->assertSeeText('Título revisado congelado')
            ->assertDontSeeText($this->employee->email)->assertDontSeeText('Título privado alterado')
            ->assertViewHas('summary', fn ($summary) => $summary['approved'] == 300 && $summary['pending'] == 0)
            ->assertViewHas('groups', fn ($groups) => $groups->first()['report']['rows']->count() === 4);
    }

    public function test_award_report_groups_cycles_and_distinguishes_votes_from_score_including_ties(): void
    {
        $popular = $this->entry('Mais votos, menor pontuação', 2, 65);
        $tie = $this->entry('Outra ideia com os mesmos votos', 3, 60);
        foreach ([$popular, $tie] as $idea) {
            $this->cycle->ideas()->updateExistingPivot($idea->id, ['outcome' => json_encode(['rank' => 2, 'final_score' => 65, 'vote_count' => 7])]);
        }
        $other = VotingCycle::create(['name' => 'Rodada independente', 'starts_at' => now()->subHours(4), 'ends_at' => now()->subHours(3), 'status' => 'closed']);
        $other->ideas()->attach($this->idea->id, ['snapshot' => json_encode(['publication' => ['title' => 'Mesma ideia em outra rodada']]), 'outcome' => json_encode(['rank' => 1, 'final_score' => 90, 'vote_count' => 3])]);
        $response = $this->actingAs($this->admin)->get(route('reports.awards'))->assertOk()->assertSeeText('Rodada independente')->assertSeeText('Empate em votos');
        $response->assertViewHas('groups', fn ($groups) => $groups->count() === 2 && $groups->first()['report']['most_voted']->pluck('id')->all() === [$popular->id, $tie->id]);
        $this->get(route('reports.awards', ['cycle' => $other->id]))->assertOk()->assertViewHas('groups', fn ($groups) => $groups->count() === 1 && $groups->first()['cycle']->id === $other->id)
            ->assertViewHas('summary', fn ($summary) => $summary['cycles'] === 1 && $summary['ideas'] === 1);
    }

    public function test_award_report_excludes_unclosed_rounds_and_filters_dates_in_company_timezone(): void
    {
        $this->cycle->update(['ends_at' => '2026-10-20 01:00:00']); // Still Oct 19 in São Paulo.
        $this->actingAs($this->admin)->get(route('reports.awards', ['from' => '2026-10-19', 'to' => '2026-10-19']))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['cycles'] === 1);
        $this->get(route('reports.awards', ['from' => '2026-10-20']))->assertOk()->assertSeeText('Nenhum ciclo apurado neste recorte');
        $this->get(route('reports.awards', ['from' => '2026-10-20', 'to' => '2026-10-19']))->assertSessionHasErrors('to');
        DB::table('voting_cycles')->where('id', $this->cycle->id)->update(['status' => 'open']);
        $this->get(route('reports.awards', ['cycle' => $this->cycle->id]))->assertOk()
            ->assertSeeText('Nenhum ciclo apurado neste recorte')->assertDontSeeText('Título revisado congelado');
    }
}
