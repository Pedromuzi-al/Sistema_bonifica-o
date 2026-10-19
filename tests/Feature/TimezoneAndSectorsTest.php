<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use App\Models\VotingCycle;
use App\Services\CycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TimezoneAndSectorsTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['name' => 'Teste rápido de hoje', 'timezone' => 'America/Sao_Paulo', 'starts_at' => '2026-10-18T14:45', 'ends_at' => '2026-10-18T15:00', 'weights' => ['real_results' => 50, 'technical' => 30, 'votes' => 20], 'criteria' => [['name' => 'Impacto', 'description' => 'Avalie o impacto', 'weight' => 100]], 'result_indicators' => [['indicator' => 'Tempo', 'unit' => 'minutos', 'direction' => 'decrease', 'target' => 10]], 'minimum_votes' => 1, 'allow_abstention' => false, 'category' => 'Destaque', 'minimum_score' => 0, 'reward_amount' => 0, 'max_awards' => 1];
    }

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);
        config(['app.timezone' => 'UTC', 'innovation.cycle_timezone' => 'America/Sao_Paulo']);

        return User::where('email', 'admin@corp.local')->firstOrFail();
    }

    #[TestWith(['2026-10-18 17:40:00', '2026-10-18T14:45', '2026-10-18T15:00', '2026-10-18 17:45:00', '2026-10-18 18:00:00'])]
    #[TestWith(['2026-10-18 00:10:00', '2026-10-17T21:15', '2026-10-17T21:20', '2026-10-18 00:15:00', '2026-10-18 00:20:00'])]
    public function test_browser_can_create_a_short_same_day_cycle_in_brasilia_time(string $now, string $start, string $end, string $storedStart, string $storedEnd): void
    {
        $admin = $this->admin();
        $this->travelTo(Carbon::parse($now, 'UTC'));
        $this->actingAs($admin)->post(route('cycles.store'), [...$this->data(), 'starts_at' => $start, 'ends_at' => $end])->assertSessionHasNoErrors()->assertRedirect();
        $cycle = VotingCycle::where('name', 'Teste rápido de hoje')->firstOrFail();
        $this->assertSame($storedStart, $cycle->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame($storedEnd, $cycle->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame($start, $cycle->local_starts_at->format('Y-m-d\TH:i'));
        $this->get(route('cycles.show', $cycle))->assertOk()->assertSee('value="'.$start.'"', false)->assertSeeText('America/Sao_Paulo');
    }

    public function test_invalid_period_keeps_the_original_local_form_values(): void
    {
        $admin = $this->admin();
        $this->travelTo(Carbon::parse('2026-10-18 17:40:00', 'UTC'));
        $this->actingAs($admin)->from(route('cycles.create', ['purpose' => 'recognition']))->post(route('cycles.store'), [...$this->data(), 'ends_at' => '2026-10-18T14:44'])->assertSessionHasErrors('ends_at')->assertSessionHasInput('starts_at', '2026-10-18T14:45');
        $this->get(route('cycles.create', ['purpose' => 'recognition']))->assertSee('value="2026-10-18T14:45"', false);
        $this->post(route('cycles.store'), [...$this->data(), 'timezone' => 'fuso-invalido'])->assertSessionHasErrors('timezone');
    }

    public function test_draft_edition_converts_local_time_using_the_same_rule_as_creation(): void
    {
        $admin = $this->admin();
        $this->travelTo(Carbon::parse('2026-10-18 17:40:00', 'UTC'));
        $this->actingAs($admin)->post(route('cycles.store'), $this->data())->assertSessionHasNoErrors();
        $cycle = VotingCycle::where('name', 'Teste rápido de hoje')->firstOrFail();
        $this->patch(route('cycles.draft', $cycle), ['name' => 'Reprogramado', 'timezone' => 'America/Sao_Paulo', 'starts_at' => '2026-10-18T14:50', 'ends_at' => '2026-10-18T15:10'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-18 17:50:00', $cycle->fresh()->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_a_same_day_cycle_opens_collects_a_vote_and_closes_after_its_local_deadline(): void
    {
        $admin = $this->admin();
        $author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $voter = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $idea = $author->ideas()->firstOrFail();
        $idea->update(['status' => 'completed', 'is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Melhoria', 'problem' => 'Problema', 'solution' => 'Solução', 'results' => 'Resultado', 'sectors' => ['Produto']]]);
        $idea->result()->create(['impact_summary' => 'Dez minutos', 'validated_at' => now(), 'measurements' => [['indicator' => 'Tempo', 'unit' => 'minutos', 'before' => 20, 'after' => 10]]]);
        $idea->evaluations()->create(['evaluator_id' => $voter->id, 'technical_score' => 100, 'recommendation' => 'approve', 'notes' => 'Favorável']);
        $this->travelTo(Carbon::parse('2026-10-18 17:40:00', 'UTC'));
        $this->actingAs($admin)->post(route('cycles.store'), $this->data())->assertSessionHasNoErrors();
        $cycle = VotingCycle::where('name', 'Teste rápido de hoje')->firstOrFail();
        app(CycleService::class)->open($admin, $cycle, [$idea->id]);
        $this->actingAs($voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertSeeText('14:45');
        $this->travelTo(Carbon::parse('2026-10-18 17:46:00', 'UTC'));
        $this->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => 5]])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('cycles.close', $cycle))->assertSessionHasErrors('cycle');
        $this->travelTo(Carbon::parse('2026-10-18 18:00:00', 'UTC'));
        $this->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $this->assertSame('closed', $cycle->fresh()->status);
    }

    public function test_sector_migration_preserves_links_and_deduplicates_beneficiaries_when_destinations_already_exist(): void
    {
        $this->seed(DatabaseSeeder::class);
        $employee = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $idea = $employee->ideas()->firstOrFail();
        $old = Sector::create(['name' => 'Operações', 'is_active' => true]);
        $finance = Sector::create(['name' => 'Financeiro', 'is_active' => true]);
        $sales = Sector::create(['name' => 'Vendas', 'is_active' => true]);
        $hr = Sector::create(['name' => 'Recursos Humanos', 'is_active' => true]);
        $employee->update(['sector_id' => $old->id]);
        $idea->update(['origin_sector_id' => $old->id, 'publication' => ['sectors' => ['Operações']]]);
        $product = Sector::where('name', 'Produto')->firstOrFail();
        $idea->beneficiarySectors()->sync([$old->id, $product->id]);
        $team = Team::create(['name' => 'Equipe financeira', 'sector_id' => $finance->id, 'is_active' => true]);
        $salesUser = User::factory()->create(['sector_id' => $sales->id]);
        $migration = require database_path('migrations/2026_10_18_180000_update_company_sectors.php');
        $migration->up();
        $this->assertSame($product->id, $employee->fresh()->sector_id);
        $this->assertSame($product->id, $idea->fresh()->origin_sector_id);
        $this->assertSame(Sector::where('name', 'Administração')->value('id'), $team->fresh()->sector_id);
        $this->assertSame(Sector::where('name', 'Sucesso do Cliente')->value('id'), $salesUser->fresh()->sector_id);
        $this->assertSame([$product->id], $idea->beneficiarySectors()->pluck('sectors.id')->all());
        $this->assertSame(['Operações'], $idea->fresh()->publication['sectors']);
        $this->assertFalse($hr->fresh()->is_active);
        $this->assertSame(['Administração', 'Material Didático', 'Produto', 'Sucesso do Cliente', 'Tecnologia'], Sector::where('is_active', true)->orderBy('name')->pluck('name')->all());
        $migration->up();
        $this->assertDatabaseCount('sectors', 9);
    }
}
