<?php

namespace Tests\Feature;

use App\Models\BonusPolicy;
use App\Models\Idea;
use App\Models\Sector;
use App\Models\TechnicalModel;
use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SimpleIdeaReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    private Idea $idea;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->idea = Idea::create(['author_id' => $this->author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Proposta inicial', 'problem_description' => 'Problema observado', 'solution_description' => 'Simplificar a rotina', 'expected_results' => '', 'resources_needed' => '', 'status' => 'submitted', 'is_public' => false]);
    }

    private function release(): array
    {
        return ['decision' => 'release', 'title' => 'Proposta pública', 'problem' => 'Problema observado', 'solution' => 'Simplificar a rotina', 'privacy_reviewed' => 1, 'notes' => 'Proposta compreensível e conferida para divulgação.'];
    }

    public function test_submitted_idea_can_be_released_and_selected_without_technical_evaluation(): void
    {
        $this->actingAs($this->admin)->get(route('ideas.show', $this->idea))->assertOk()->assertSeeText('Liberar para votação')->assertSeeText('Pedir ajuste')->assertSeeText('Arquivar');
        $this->post(route('ideas.review-decision', $this->idea), $this->release())->assertSessionHasNoErrors();
        $this->assertSame('ready_for_voting', $this->idea->fresh()->status);
        $this->assertTrue($this->idea->fresh()->is_public);
        $this->assertSame('Melhoria', $this->idea->fresh()->publication['category']);
        $this->assertSame(0, $this->idea->evaluations()->count());
        $this->post(route('cycles.store'), ['purpose' => 'selection', 'name' => 'Ciclo revisão simples', 'starts_at' => now()->addHour()->toDateTimeString(), 'ends_at' => now()->addHours(2)->toDateTimeString(), 'max_selections' => 1, 'minimum_votes' => 1, 'technical_model_id' => TechnicalModel::factory()->create()->id, 'bonus_policy_id' => BonusPolicy::factory()->create(['reward_amount' => 100])->id])->assertSessionHasNoErrors();
        $cycle = VotingCycle::latest('id')->firstOrFail();
        $this->post(route('cycles.open', $cycle), ['ideas' => [$this->idea->id]])->assertSessionHasNoErrors();
        $this->post(route('ideas.review-decision', $this->idea), ['decision' => 'archive', 'notes' => 'Tentativa de mudar durante a votação.'])->assertSessionHasErrors('decision');
        $this->travelTo($cycle->starts_at->copy()->addMinute());
        $voter = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $scores = $cycle->criteria()->pluck('id')->mapWithKeys(fn ($id) => [$id => 5])->all();
        $this->actingAs($voter)->post(route('ideas.vote', $this->idea), ['voting_cycle_id' => $cycle->id, 'scores' => $scores])->assertSessionHasNoErrors();
        $this->travelTo($cycle->ends_at->copy()->addSecond());
        $this->actingAs($this->admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $this->post(route('cycles.decision', [$cycle, $this->idea]), ['decision' => 'selected', 'notes' => 'Proposta viável para avançar nesta rodada.'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $this->idea->fresh()->status);
        $this->assertDatabaseHas('bonus_awards', ['idea_id' => $this->idea->id, 'award_amount' => 100]);
    }

    public function test_adjustment_can_be_requested_without_publication_and_reply_returns_to_review(): void
    {
        $this->actingAs($this->admin)->post(route('ideas.review-decision', $this->idea), ['decision' => 'adjust', 'notes' => 'Explique como a solução ajudaria no atendimento.'])->assertSessionHasNoErrors();
        $this->assertSame('awaiting_information', $this->idea->fresh()->status);
        $this->assertFalse($this->idea->fresh()->is_public);
        $this->actingAs($this->author)->post(route('ideas.reply', $this->idea), ['notes' => 'A solução reduzirá o tempo de retorno ao cliente.'])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $this->idea->fresh()->status);
        $this->actingAs($this->admin)->post(route('ideas.review-decision', $this->idea), $this->release())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('idea_status_histories', ['idea_id' => $this->idea->id, 'to_status' => 'ready_for_voting']);
    }

    public function test_archiving_preserves_private_history_and_cannot_enter_selection(): void
    {
        $this->actingAs($this->admin)->post(route('ideas.review-decision', $this->idea), ['decision' => 'archive', 'notes' => 'Proposta duplicada de outra contribuição já registrada.'])->assertSessionHasNoErrors();
        $this->assertSame('archived', $this->idea->fresh()->status);
        $this->assertFalse($this->idea->fresh()->is_public);
        $this->assertNull($this->idea->fresh()->published_at);
        $this->assertDatabaseHas('audit_logs', ['model_id' => $this->idea->id, 'action' => 'proposal_review_decided']);
        $this->post(route('ideas.review-decision', $this->idea), $this->release())->assertSessionHasErrors('status');
        $this->actingAs($this->author)->get(route('ideas.show', $this->idea))->assertOk()->assertSeeText('Arquivada');
    }

    public function test_review_requires_authorization_justification_and_privacy_confirmation(): void
    {
        $this->actingAs($this->author)->postJson(route('ideas.review-decision', $this->idea), $this->release())->assertForbidden();
        $employee = User::factory()->create();
        $this->actingAs($employee)->postJson(route('ideas.review-decision', $this->idea), $this->release())->assertForbidden();
        $this->actingAs($this->admin)->post(route('ideas.review-decision', $this->idea), [...$this->release(), 'privacy_reviewed' => 0, 'notes' => 'curta'])->assertSessionHasErrors(['privacy_reviewed', 'notes']);
        $this->assertSame('submitted', $this->idea->fresh()->status);
        DB::table('idea_contributors')->insert(['idea_id' => $this->idea->id, 'user_id' => $this->admin->id, 'share' => 50]);
        $this->postJson(route('ideas.review-decision', $this->idea), $this->release())->assertForbidden();
    }
}
