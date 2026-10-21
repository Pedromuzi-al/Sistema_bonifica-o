<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use App\Support\ImplementationStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class VotingImplementationStatusTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['planned', 'Ainda será implementada'])]
    #[TestWith(['in_progress', 'Em implementação'])]
    #[TestWith(['implemented', 'Implementada'])]
    public function test_voting_and_cycle_pages_show_the_frozen_implementation_status(string $status, string $label): void
    {
        $this->seed(DatabaseSeeder::class);
        $voter = User::where('email', 'avaliador@corp.local')->firstOrFail();
        $author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $idea = Idea::create(['author_id' => $author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Texto privado', 'problem_description' => 'Problema privado', 'solution_description' => 'Solução privada', 'expected_results' => 'Estimativa privada', 'resources_needed' => 'Recursos', 'status' => 'voting', 'is_public' => true]);
        $cycle = VotingCycle::create(['name' => 'Situação congelada', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'status' => 'open', 'frozen_configuration' => ['criteria' => [], 'allow_abstention' => false]]);
        $snapshot = ['implementation_status' => $status, 'publication' => ['title' => 'Proposta revisada', 'problem' => 'Problema público', 'solution' => 'Solução pública', 'results' => 'Benefícios revisados']];
        $cycle->ideas()->attach($idea->id, ['snapshot' => json_encode($snapshot)]);
        // A later update to the live idea must not change what this vote describes.
        $idea->update(['status' => 'completed']);
        $this->actingAs($voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertOk()->assertSeeText('Situação da implementação')->assertSeeText($label)->assertDontSeeText('Problema privado');
        $this->get(route('cycles.show', $cycle))->assertOk()->assertSeeText($label);
    }

    public function test_legacy_snapshot_recognizes_implemented_ideas_even_with_zero_results_score(): void
    {
        $this->assertSame('implemented', ImplementationStatus::fromSnapshot(['real_results' => 0]));
        $this->assertSame('implemented', ImplementationStatus::fromSnapshot(['publication' => ['implementation_completed_at' => '2026-10-18T12:00:00Z']]));
        $this->assertSame('planned', ImplementationStatus::fromSnapshot(['implementation_status' => '<script>invalid</script>']));
    }

    #[TestWith(['submitted', 'planned'])]
    #[TestWith(['approved', 'planned'])]
    #[TestWith(['implementation', 'in_progress'])]
    #[TestWith(['validation', 'implemented'])]
    #[TestWith(['completed', 'implemented'])]
    public function test_implementation_stage_is_separate_from_the_voting_stage(string $stage, string $expected): void
    {
        $idea = new Idea(['status' => $stage]);
        if ($expected === 'planned') {
            $idea->setRelation('implementationPlan', null);
        }
        $this->assertSame($expected, ImplementationStatus::forIdea($idea));
    }
}
