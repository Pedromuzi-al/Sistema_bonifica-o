<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_employee_can_create_idea(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->first();

        $this->actingAs($user)
            ->post(route('ideas.store'), [
                'title' => 'Melhoria do atendimento interno',
                'problem_description' => 'O atendimento interno demora muito por causa de filas.',
                'solution_description' => 'Automatizar o encaminhamento com classificação por urgência.',
                'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
                'beneficiary_sectors' => [Sector::where('name', 'Operações')->value('id')],
                'expected_results' => 'Redução de tempo e maior rastreabilidade.',
                'resources_needed' => 'Um desenvolvedor e uma semana de configuração.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ideas', ['title' => 'Melhoria do atendimento interno']);
    }

    public function test_employee_can_select_all_beneficiary_sectors_for_an_idea(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->first();
        $sectorIds = Sector::pluck('id')->all();

        $this->actingAs($user)
            ->get(route('ideas.create'))
            ->assertOk()
            ->assertSeeText('Todos');

        $response = $this->actingAs($user)
            ->post(route('ideas.store'), [
                'title' => 'Melhoria para todos os setores',
                'problem_description' => 'Os setores precisam de um fluxo compartilhado.',
                'solution_description' => 'Disponibilizar uma solução comum para todos.',
                'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
                'beneficiary_sectors' => $sectorIds,
                'expected_results' => 'Mais integração entre áreas.',
                'resources_needed' => 'Equipe multidisciplinar.',
            ]);

        $response->assertRedirect();

        $idea = Idea::where('title', 'Melhoria para todos os setores')->firstOrFail();

        foreach ($sectorIds as $sectorId) {
            $this->assertDatabaseHas('idea_beneficiary_sectors', [
                'idea_id' => $idea->id,
                'sector_id' => $sectorId,
            ]);
        }
    }

    public function test_duplicate_vote_is_blocked(): void
    {
        $author = User::where('email', 'funcionario@corp.local')->first();
        $voter = User::factory()->create(['email' => 'voter@corp.local', 'name' => 'Votante']);
        $voter->assignRole('employee');

        $sector = Sector::where('name', 'Operações')->value('id');
        $idea = Idea::create([
            'author_id' => $author->id,
            'origin_sector_id' => $sector,
            'title' => 'Idea para teste de voto',
            'problem_description' => 'Problema de teste',
            'solution_description' => 'Solução de teste',
            'expected_results' => 'Resultado de teste',
            'resources_needed' => 'Recurso de teste',
            'status' => Idea::STATUS_VOTING, 'is_public' => true, 'published_at' => now(),
        ]);

        $cycle = VotingCycle::create([
            'name' => 'Ciclo de teste',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'status' => 'open',
        ]);

        $cycle->criteria()->create([
            'name' => 'Impacto',
            'weight' => 1,
        ]);

        $cycle->ideas()->attach($idea->id, ['snapshot' => json_encode(['publication' => [], 'real_results' => 80, 'technical' => 80])]);
        $cycle->update(['frozen_configuration' => ['criteria' => $cycle->criteria->toArray(), 'allow_abstention' => false]]);

        $this->actingAs($voter)
            ->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => 4]])
            ->assertSessionHasNoErrors();

        $this->actingAs($voter)
            ->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => 5]])
            ->assertSessionHasErrors('vote');
    }

    public function test_author_name_is_hidden_from_other_employees(): void
    {
        $author = User::where('email', 'funcionario@corp.local')->first();
        $viewer = User::factory()->create(['email' => 'viewer@corp.local', 'name' => 'Leitor']);
        $viewer->assignRole('employee');

        $idea = Idea::create([
            'author_id' => $author->id,
            'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
            'title' => 'Ideia anonima',
            'problem_description' => 'Descrição',
            'solution_description' => 'Solução',
            'expected_results' => 'Resultado',
            'resources_needed' => 'Recurso',
            'is_public' => true,
        ]);

        $this->actingAs($viewer)
            ->get(route('ideas.show', $idea))
            ->assertDontSeeText($author->name)
            ->assertSeeText('Autor confidencial');

        $this->actingAs($viewer)
            ->get(route('ideas.index'))
            ->assertDontSeeText($author->name)
            ->assertSeeText('Autor confidencial');
    }

    public function test_employee_cannot_view_another_employees_private_idea(): void
    {
        $author = User::where('email', 'funcionario@corp.local')->first();
        $viewer = User::factory()->create(['email' => 'private-viewer@corp.local']);
        $viewer->assignRole('employee');

        $idea = Idea::create([
            'author_id' => $author->id,
            'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
            'title' => 'Proposta privada',
            'problem_description' => 'Descrição',
            'solution_description' => 'Solução',
            'expected_results' => 'Resultado',
            'resources_needed' => 'Recurso',
        ]);

        $this->actingAs($viewer)
            ->get(route('ideas.show', $idea))
            ->assertRedirect();

        $this->actingAs($viewer)
            ->get(route('ideas.index'))
            ->assertDontSeeText('Proposta privada');

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertDontSeeText('Proposta privada');
    }

    public function test_unassigned_user_cannot_create_an_idea(): void
    {
        $user = User::factory()->create(['email' => 'unassigned@corp.local']);

        $this->actingAs($user)
            ->get(route('ideas.create'))
            ->assertRedirect();
    }

    public function test_employee_cannot_access_evaluation_or_admin_dashboards(): void
    {
        $employee = User::where('email', 'funcionario@corp.local')->first();

        $this->actingAs($employee)
            ->get(route('evaluation.index'))
            ->assertRedirect();

        $this->actingAs($employee)
            ->get(route('admin.index'))
            ->assertRedirect();
    }

    public function test_unauthorized_action_redirects_back_with_permission_message(): void
    {
        $employee = User::where('email', 'funcionario@corp.local')->first();
        $idea = Idea::create([
            'author_id' => $employee->id,
            'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
            'title' => 'Ideia restrita',
            'problem_description' => 'Descrição',
            'solution_description' => 'Solução',
            'expected_results' => 'Resultado',
            'resources_needed' => 'Recurso',
            'status' => Idea::STATUS_SUBMITTED,
        ]);

        $this->actingAs($employee)
            ->from(route('ideas.index'))
            ->post(route('ideas.approve', $idea), ['status' => 'approved'])
            ->assertRedirect(route('ideas.index'))
            ->assertSessionHas('permission_error');
    }
}
