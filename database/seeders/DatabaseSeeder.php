<?php

namespace Database\Seeders;

use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(fn () => $this->seedDefaults());
    }

    private function seedDefaults(): void
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
            'manage_admin', 'view_author_identity', 'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $employee = Role::firstOrCreate(['name' => 'employee']);
        $evaluator = Role::firstOrCreate(['name' => 'evaluator']);
        $admin = Role::firstOrCreate(['name' => 'admin']);

        $employee->givePermissionTo(['create_ideas', 'vote_ideas']);
        $evaluator->givePermissionTo(['create_ideas', 'vote_ideas', 'evaluate_ideas']);
        $admin->givePermissionTo(['create_ideas', 'vote_ideas', 'evaluate_ideas', 'manage_admin', 'view_author_identity', 'view_audit_logs']);

        $adminUser = User::withTrashed()->firstOrCreate(['email' => 'admin@corp.local'], User::factory()->raw([
            'name' => 'Administrador Corp',
            'email' => 'admin@corp.local',
            'sector_id' => Sector::where('name', 'Tecnologia')->value('id'),
        ]));
        if ($adminUser->wasRecentlyCreated) {
            $adminUser->assignRole('admin');
        }

        $evaluatorUser = User::withTrashed()->firstOrCreate(['email' => 'avaliador@corp.local'], User::factory()->raw([
            'name' => 'Avaliadora Técnica',
            'email' => 'avaliador@corp.local',
            'sector_id' => Sector::where('name', 'Tecnologia')->value('id'),
        ]));
        if ($evaluatorUser->wasRecentlyCreated) {
            $evaluatorUser->assignRole('evaluator');
        }

        $employeeUser = User::withTrashed()->firstOrCreate(['email' => 'funcionario@corp.local'], User::factory()->raw([
            'name' => 'Colaborador da Operação',
            'email' => 'funcionario@corp.local',
            'sector_id' => Sector::where('name', 'Operações')->value('id'),
        ]));
        if ($employeeUser->wasRecentlyCreated) {
            $employeeUser->assignRole('employee');
        }

        $cycle = VotingCycle::firstOrCreate(['name' => 'Ciclo de votação Q4'], [
            'name' => 'Ciclo de votação Q4',
            'description' => 'Processo de avaliação popular das ideias mais relevantes.',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(8),
            'status' => 'draft',
            'weight_configuration' => ['real_results' => 50, 'technical' => 30, 'votes' => 20],
        ]);

        if (! $cycle->frozen_configuration) {
            foreach ([
                ['name' => 'Impacto operacional', 'weight' => 0.4, 'description' => 'Impacto do ganho operacional.'],
                ['name' => 'Benefício para clientes', 'weight' => 0.3, 'description' => 'Maturidade da proposta no cliente.'],
                ['name' => 'Facilidade de execução', 'weight' => 0.3, 'description' => 'Complexidade para implementar.'],
            ] as $criterion) {
                $cycle->criteria()->firstOrCreate(['name' => $criterion['name']], $criterion);
            }
        }

        $idea = Idea::firstOrCreate(['author_id' => $employeeUser->id, 'title' => 'Automatização de triagem de pendências'], [
            'author_id' => $employeeUser->id,
            'origin_sector_id' => Sector::where('name', 'Operações')->value('id'),
            'title' => 'Automatização de triagem de pendências',
            'problem_description' => 'O setor operacional tem mais de 60 pendências diárias sem rastreio automatizado.',
            'solution_description' => 'Criar uma fila automatizada para priorizar tarefas e reduzir retrabalho.',
            'expected_results' => 'Economia de tempo e redução de falhas ao rastrear pendências.',
            'resources_needed' => 'Equipe de desenvolvimento e duas semanas para implantação.',
            'status' => Idea::STATUS_SUBMITTED,
            'is_public' => false,
        ]);

        if ($idea->wasRecentlyCreated) {
            $idea->beneficiarySectors()->sync([
                Sector::where('name', 'Operações')->value('id'),
                Sector::where('name', 'Tecnologia')->value('id'),
            ]);
            IdeaStatusHistory::create(['idea_id' => $idea->id, 'from_status' => null, 'to_status' => Idea::STATUS_SUBMITTED, 'changed_by_user_id' => $employeeUser->id, 'notes' => 'Proposta de demonstração enviada.']);
        }
    }
}
