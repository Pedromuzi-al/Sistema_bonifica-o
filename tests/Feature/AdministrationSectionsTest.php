<?php

namespace Tests\Feature;

use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationSectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->employee = User::where('email', 'funcionario@corp.local')->firstOrFail();
    }

    public function test_administration_links_to_separate_listings_without_embedding_creation_forms(): void
    {
        $this->actingAs($this->admin)->get(route('admin.index'))->assertOk()->assertSee(route('admin.sectors.index'), false)->assertSee(route('admin.criteria.index'), false)->assertSee(route('admin.users.index'), false)->assertDontSee('action="'.route('criteria.store').'"', false);
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Controle de acesso das pessoas')->assertSeeText('Permissões efetivas');
        $this->get(route('admin.criteria.index'))->assertOk()->assertSeeText('Novo critério técnico')->assertDontSee('action="'.route('criteria.store').'"', false);
        $this->get(route('admin.criteria.create'))->assertOk()->assertSee('action="'.route('criteria.store').'"', false);
    }

    public function test_sector_listing_filters_active_and_inactive_and_detail_lists_membership(): void
    {
        $active = Sector::create(['name' => 'Setor teste ativo', 'is_active' => true]);
        $inactive = Sector::create(['name' => 'Setor teste inativo', 'is_active' => false]);
        $this->employee->update(['sector_id' => $active->id]);
        $this->actingAs($this->admin)->get(route('admin.sectors.index', ['status' => 'active']))->assertOk()->assertSeeText($active->name)->assertDontSeeText($inactive->name);
        $this->get(route('admin.sectors.index', ['status' => 'inactive']))->assertOk()->assertSeeText($inactive->name)->assertDontSeeText($active->name);
        $this->get(route('admin.sectors.show', $active))->assertOk()->assertSeeText($this->employee->email)->assertDontSeeText('Disponível para protocolos');
        $this->get(route('admin.sectors.show', [$active, 'membership' => 'unlinked']))->assertOk()->assertDontSeeText($this->employee->email);
    }

    public function test_link_transfer_and_unlink_change_only_the_users_reference_sector_and_are_audited(): void
    {
        $source = Sector::firstOrFail();
        $destination = Sector::create(['name' => 'Destino de teste', 'is_active' => true]);
        $this->employee->update(['sector_id' => $source->id]);
        $beforePermissions = $this->employee->getAllPermissions()->pluck('id')->sort()->values()->all();
        $idea = Idea::create(['author_id' => $this->employee->id, 'origin_sector_id' => $source->id, 'title' => 'Ideia histórica', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Benefícios', 'resources_needed' => 'Recursos', 'status' => 'submitted']);
        $this->actingAs($this->admin)->patch(route('admin.sectors.members', [$destination, $this->employee]), ['action' => 'link'])->assertSessionHasNoErrors();
        $this->assertEquals($destination->id, $this->employee->fresh()->sector_id);
        $this->assertEquals($source->id, $idea->fresh()->origin_sector_id);
        $this->assertSame($beforePermissions, $this->employee->fresh()->getAllPermissions()->pluck('id')->sort()->values()->all());
        $this->assertDatabaseHas('audit_logs', ['model_id' => $this->employee->id, 'action' => 'sector_membership_updated']);
        $this->patch(route('admin.sectors.members', [$source, $this->employee]), ['action' => 'unlink'])->assertSessionHasErrors('membership');
        $this->assertEquals($destination->id, $this->employee->fresh()->sector_id);
        $this->patch(route('admin.sectors.members', [$destination, $this->employee]), ['action' => 'unlink'])->assertSessionHasNoErrors();
        $this->assertNull($this->employee->fresh()->sector_id);
        $this->assertEquals($source->id, $idea->fresh()->origin_sector_id);
    }

    public function test_inactive_sector_rejects_new_links_but_existing_members_can_be_unlinked(): void
    {
        $sector = Sector::create(['name' => 'Destino inativo', 'is_active' => false]);
        $this->actingAs($this->admin)->patch(route('admin.sectors.members', [$sector, $this->employee]), ['action' => 'link'])->assertSessionHasErrors('membership');
        $this->employee->update(['sector_id' => $sector->id]);
        $this->patch(route('admin.sectors.members', [$sector, $this->employee]), ['action' => 'unlink'])->assertSessionHasNoErrors();
        $this->assertNull($this->employee->fresh()->sector_id);
    }

    public function test_non_administrators_cannot_read_or_write_the_new_sections(): void
    {
        $sector = Sector::firstOrFail();
        $criterion = EvaluationCriterion::create(['name' => 'Privado', 'description' => 'Orientação', 'weight' => 100, 'active' => true]);
        $this->actingAs($this->employee);
        foreach ([route('admin.sectors.index'), route('admin.sectors.show', $sector), route('admin.criteria.index'), route('admin.criteria.create'), route('admin.criteria.edit', $criterion)] as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->patchJson(route('admin.sectors.members', [$sector, $this->employee]), ['action' => 'link'])->assertForbidden();
    }

    public function test_criterion_create_edit_and_deactivation_return_to_the_listing(): void
    {
        $this->actingAs($this->admin)->post(route('criteria.store'), ['name' => 'Critério específico', 'description' => 'Orientação original', 'weight' => 50])->assertSessionHasNoErrors()->assertRedirect(route('admin.criteria.index'));
        $criterion = EvaluationCriterion::where('name', 'Critério específico')->firstOrFail();
        $this->get(route('admin.criteria.edit', $criterion))->assertOk()->assertSee('Orientação original');
        $this->patch(route('criteria.update', $criterion), ['name' => 'Critério específico revisado', 'description' => 'Nova orientação', 'weight' => 60, 'active' => false])->assertSessionHasNoErrors()->assertRedirect(route('admin.criteria.index'));
        $this->get(route('admin.criteria.index', ['status' => 'inactive']))->assertOk()->assertSeeText('Critério específico revisado');
        $this->get(route('admin.criteria.index', ['status' => 'active']))->assertOk()->assertDontSeeText('Critério específico revisado');
    }
}
