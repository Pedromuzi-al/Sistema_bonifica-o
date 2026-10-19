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
}
