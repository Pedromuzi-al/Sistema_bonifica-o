<?php

namespace Database\Seeders;

use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use App\Models\VotingCriterion;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            ['name' => 'Operações', 'description' => 'Setor responsável pela operação da empresa.'],
            ['name' => 'Financeiro', 'description' => 'Gestão financeira e controladoria.'],
            ['name' => 'Tecnologia', 'description' => 'Inovação tecnológica e sistemas.'],
            ['name' => 'Recursos Humanos', 'description' => 'Gestão de pessoas e cultura.'],
            ['name' => 'Vendas', 'description' => 'Relacionamento com clientes e canais.'],
        ];

        foreach ($sectors as $sector) {
            Sector::firstOrCreate(['name' => $sector['name']], $sector);
        }

        $permissions = [
            'create_ideas',
            'vote_ideas',
            'evaluate_ideas',
            'manage_admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $employee = Role::firstOrCreate(['name' => 'employee']);
        $evaluator = Role::firstOrCreate(['name' => 'evaluator']);
        $admin = Role::firstOrCreate(['name' => 'admin']);

        $employee->givePermissionTo(['create_ideas', 'vote_ideas']);
        $evaluator->givePermissionTo(['create_ideas', 'vote_ideas', 'evaluate_ideas']);
        $admin->givePermissionTo(['create_ideas', 'vote_ideas', 'evaluate_ideas', 'manage_admin']);

        $adminUser = User::factory()->create([
            'name' => 'Administrador Corp',
            'email' => 'admin@corp.local',
            'sector_id' => Sector::where('name', 'Tecnologia')->value('id'),
        ]);
        $adminUser->assignRole('admin');

        $evaluatorUser = User::factory()->create([
            'name' => 'Avaliadora Técnica',
            'email' => 'avaliador@corp.local',
            'sector_id' => Sector::where('name', 'Tecnologia')->value('id'),
        ]);
        $evaluatorUser->assignRole('evaluator');

        $employeeUser = User::factory()->create([
            'name' => 'Colaborador da Operação',
            'email' => 'funcionario@corp.local',
            'sector_id' => Sector::where('name', 'Operações')->value('id'),
        ]);
        $employeeUser->assignRole('employee');

        $cycle = VotingCycle::create([
            'name' => 'Ciclo de votação Q4',
            'description' => 'Processo de avaliação popular das ideias mais relevantes.',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(8),
            'status' => 'open',
            'weight_configuration' => ['real_results' => 50, 'technical' => 30, 'votes' => 20],
        ]);

        $cycle->criteria()->createMany([
            ['name' => 'Impacto operacional', 'weight' => 0.4, 'description' => 'Impacto do ganho operacional.'],
            ['name' => 'Benefício para clientes', 'weight' => 0.3, 'description' => 'Maturidade da proposta no cliente.'],
            ['name' => 'Facilidade de execução', 'weight' => 0.3, 'description' => 'Complexidade para implementar.'],
        ]);

        $idea = Idea::create([
            'author_id' => $employeeUser->id,
            'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
            'title' => 'Automatização de triagem de pendências',
            'problem_description' => 'O setor operacional tem mais de 60 pendências diárias sem rastreio automatizado.',
            'solution_description' => 'Criar uma fila automatizada para priorizar tarefas e reduzir retrabalho.',
            'expected_results' => 'Economia de tempo e redução de falhas ao rastrear pendências.',
            'resources_needed' => 'Equipe de desenvolvimento e duas semanas para implantação.',
            'status' => Idea::STATUS_APPROVED,
            'is_public' => true,
        ]);

        $idea->beneficiarySectors()->attach([
            Sector::where('name', 'Operações')->value('id'),
            Sector::where('name', 'Tecnologia')->value('id'),
        ]);

        IdeaStatusHistory::record($idea, Idea::STATUS_APPROVED, $adminUser, 'A ideia foi aprovada após análise inicial.');
    }
}
