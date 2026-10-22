<?php

namespace Tests\Feature;

use App\Models\BonusPolicy;
use App\Models\Idea;
use App\Models\TechnicalModel;
use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialRulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('email', 'admin@corp.local')->firstOrFail();
    }

    private function cycleData(TechnicalModel $model, BonusPolicy $policy): array
    {
        return ['purpose' => 'selection', 'name' => 'Seleção com regras cadastradas', 'starts_at' => now()->addHour()->toDateTimeString(), 'ends_at' => now()->addHours(2)->toDateTimeString(), 'max_selections' => 3, 'minimum_votes' => 2, 'technical_model_id' => $model->id, 'bonus_policy_id' => $policy->id];
    }

    public function test_administrator_can_create_update_filter_and_inactivate_bonus_rules_with_audit(): void
    {
        $this->actingAs($this->admin());
        $data = BonusPolicy::factory()->make(['name' => 'Reconhecimento especial', 'reward_amount' => 101.01])->only(['name', 'description', 'reward_amount', 'minimum_score', 'allow_abstention', 'anomaly_threshold', 'active']);
        $this->post(route('financial.policies.store'), $data)->assertRedirect(route('financial.policies.index'))->assertSessionHasNoErrors();
        $policy = BonusPolicy::firstOrFail();
        $this->assertDatabaseHas('bonus_policies', ['id' => $policy->id, 'reward_amount' => 101.01]);
        $this->assertDatabaseHas('audit_logs', ['model_id' => $policy->id, 'action' => 'bonus_policy_saved']);
        $this->patch(route('financial.policies.update', $policy), [...$data, 'active' => false])->assertSessionHasNoErrors();
        $this->get(route('financial.policies.index', ['status' => 'inactive', 'search' => 'especial']))->assertOk()->assertSeeText('Reconhecimento especial');
        $this->get(route('financial.policies.index', ['status' => 'active']))->assertOk()->assertDontSeeText('Reconhecimento especial');
    }

    public function test_models_require_distinct_questions_and_weights_totaling_one_hundred(): void
    {
        $this->actingAs($this->admin());
        $data = TechnicalModel::factory()->make()->only(['name', 'description', 'active', 'criteria']);
        $data['criteria'][0]['weight'] = 40;
        $this->post(route('financial.technical.store'), $data)->assertSessionHasErrors('criteria');
        $this->assertDatabaseCount('technical_models', 0);
        $data['criteria'][0]['weight'] = 34;
        $data['criteria'][1]['name'] = $data['criteria'][0]['name'];
        $this->post(route('financial.technical.store'), $data)->assertSessionHasErrors('criteria.0.name');
        $data['criteria'][1]['name'] = 'Viabilidade';
        $this->post(route('financial.technical.store'), $data)->assertRedirect(route('financial.technical.index'))->assertSessionHasNoErrors();
        $model = TechnicalModel::firstOrFail();
        $this->patch(route('financial.technical.update', $model), [...$data, 'active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($model->fresh()->active);
        $this->assertDatabaseHas('audit_logs', ['model_id' => $model->id, 'action' => 'technical_model_saved']);
        $this->get(route('financial.technical.index', ['status' => 'inactive']))->assertOk()->assertSeeText($model->name);
    }

    public function test_invalid_financial_values_cannot_create_a_rule(): void
    {
        $this->actingAs($this->admin());
        $data = BonusPolicy::factory()->make()->only(['name', 'description', 'reward_amount', 'minimum_score', 'allow_abstention', 'anomaly_threshold', 'active']);
        $this->post(route('financial.policies.store'), [...$data, 'reward_amount' => -1, 'minimum_score' => 101, 'anomaly_threshold' => 1])->assertSessionHasErrors(['reward_amount', 'minimum_score', 'anomaly_threshold']);
        $this->post(route('financial.policies.store'), [...$data, 'reward_amount' => '1.001'])->assertSessionHasErrors('reward_amount');
        $this->assertDatabaseCount('bonus_policies', 0);
    }

    public function test_non_administrators_cannot_read_or_change_financial_settings(): void
    {
        $this->get(route('financial.index'))->assertRedirect(route('login'));
        $this->admin();
        $this->actingAs(User::where('email', 'funcionario@corp.local')->firstOrFail());
        foreach (['financial.index', 'financial.technical.index', 'financial.technical.create', 'financial.policies.index', 'financial.policies.create'] as $route) {
            $this->getJson(route($route))->assertForbidden();
        }
        $policy = BonusPolicy::factory()->create();
        $model = TechnicalModel::factory()->create();
        $this->postJson(route('financial.policies.store'), [])->assertForbidden();
        $this->patchJson(route('financial.policies.update', $policy), ['reward_amount' => 999])->assertForbidden();
        $this->postJson(route('financial.technical.store'), [])->assertForbidden();
        $this->patchJson(route('financial.technical.update', $model), ['active' => false])->assertForbidden();
        $this->assertSame('0.00', $policy->fresh()->reward_amount);
        $this->assertTrue($model->fresh()->active);
        $this->get(route('cycles.index'))->assertOk()->assertDontSee('id="financial-links"', false);
    }

    public function test_cycle_copies_selected_rules_and_ignores_forged_inline_values(): void
    {
        $this->actingAs($this->admin());
        $model = TechnicalModel::factory()->create(['name' => 'Modelo da rodada']);
        $policy = BonusPolicy::factory()->create(['name' => 'Regra da rodada', 'reward_amount' => 200, 'minimum_score' => 70, 'anomaly_threshold' => 8]);
        $this->post(route('cycles.store'), [...$this->cycleData($model, $policy), 'reward_amount' => 99999, 'minimum_score' => 0, 'criteria' => [['name' => 'Forjado', 'description' => 'Forjado', 'weight' => 100]]])->assertSessionHasNoErrors();
        $cycle = VotingCycle::latest('id')->firstOrFail();
        $this->assertSame(200.0, (float) $cycle->weight_configuration['reward_amount']);
        $this->assertSame(70.0, (float) $cycle->weight_configuration['minimum_score']);
        $this->assertCount(3, $cycle->criteria);
        $this->assertSame('Utilidade', $cycle->criteria->first()->name);
        $model->update(['name' => 'Nome alterado', 'criteria' => [['name' => 'Outra pergunta', 'description' => 'Nova orientação', 'weight' => 100]], 'active' => false]);
        $policy->update(['reward_amount' => 900, 'active' => false]);
        $this->get(route('cycles.show', $cycle))->assertOk()->assertSeeText('Modelo da rodada')->assertSeeText('Regra da rodada')->assertSeeText('200,00')->assertDontSeeText('Outra pergunta');
        $this->assertSame(200.0, (float) $cycle->fresh()->weight_configuration['reward_amount']);
        $idea = Idea::firstOrFail();
        $idea->update(['status' => 'ready_for_voting', 'is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Proposta revisada', 'problem' => 'Problema', 'solution' => 'Solução', 'results' => 'Benefício esperado', 'category' => 'Melhoria']]);
        $this->post(route('cycles.open', $cycle), ['ideas' => [$idea->id]])->assertSessionHasNoErrors();
        $this->assertSame(200.0, (float) $cycle->fresh()->frozen_configuration['reward_amount']);
        $this->assertSame('Modelo da rodada', $cycle->fresh()->frozen_configuration['technical_model']['name']);
        $this->assertCount(3, $cycle->fresh()->frozen_configuration['criteria']);

    }

    public function test_cycle_requires_both_active_templates_and_preserves_form_choices_on_error(): void
    {
        $this->actingAs($this->admin());
        $model = TechnicalModel::factory()->create();
        $policy = BonusPolicy::factory()->create();
        $data = $this->cycleData($model, $policy);
        $this->post(route('cycles.store'), array_diff_key($data, array_flip(['technical_model_id', 'bonus_policy_id'])))->assertSessionHasErrors(['technical_model_id', 'bonus_policy_id']);
        $policy->update(['active' => false]);
        $this->post(route('cycles.store'), $data)->assertSessionHasErrors('bonus_policy_id')->assertSessionHasInput('technical_model_id', $model->id);
        $policy->update(['active' => true]);
        $model->update(['active' => false]);
        $this->post(route('cycles.store'), $data)->assertSessionHasErrors('technical_model_id');
        $this->assertDatabaseCount('voting_cycles', 1);
        $this->get(route('cycles.create'))->assertOk()->assertDontSee('<option value="'.$model->id.'">'.$model->name, false)->assertDontSeeText('Reconhecimento pela ideia e regras adicionais');
    }
}
