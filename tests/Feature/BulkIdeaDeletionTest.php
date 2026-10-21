<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkIdeaDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->author = User::where('email', 'funcionario@corp.local')->firstOrFail();
    }

    private function idea(): Idea
    {
        return Idea::create(['author_id' => $this->author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Ideia descartável de QA', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => '', 'resources_needed' => '', 'status' => 'submitted', 'is_public' => false]);
    }

    private function payload(array $ids): array
    {
        return ['ids' => $ids, 'deletion_reason' => 'Cadastros fictícios removidos depois do QA.', 'confirm_deletion' => 1];
    }

    public function test_administrator_deletes_batch_preserving_records_and_removing_routes_and_lists(): void
    {
        $first = $this->idea();
        $second = $this->idea();
        $this->actingAs($this->admin)->get(route('ideas.index'))->assertOk()->assertSeeText('Excluir ideias');
        $this->delete(route('ideas.bulk-destroy'), $this->payload([$first->id, $second->id]))->assertSessionHasNoErrors()->assertRedirect(route('ideas.index'));
        foreach ([$first, $second] as $idea) {
            $this->assertSoftDeleted($idea);
            $this->assertDatabaseHas('audit_logs', ['action' => 'idea_soft_deleted', 'model_id' => $idea->id]);
            $this->assertDatabaseHas('idea_status_histories', ['idea_id' => $idea->id, 'notes' => 'Exclusão administrativa das listagens: Cadastros fictícios removidos depois do QA.']);
            $this->actingAs($this->author)->get(route('ideas.show', $idea))->assertNotFound();
        }
        $this->actingAs($this->admin)->get(route('ideas.index'))->assertDontSeeText('Ideia descartável de QA');
        $this->get(route('evaluation.index'))->assertOk()->assertDontSeeText('Ideia descartável de QA');
    }

    public function test_employee_and_evaluator_cannot_delete_even_their_own_idea(): void
    {
        $idea = $this->idea();
        foreach ([$this->author, User::where('email', 'avaliador@corp.local')->firstOrFail()] as $user) {
            $this->actingAs($user)->get(route('ideas.index'))->assertOk()->assertDontSeeText('Excluir ideias');
            $this->deleteJson(route('ideas.bulk-destroy'), $this->payload([$idea->id]))->assertForbidden();
        }
        $this->assertNotSoftDeleted($idea);
    }

    public function test_any_cycle_link_blocks_entire_batch_without_partial_deletion(): void
    {
        $first = $this->idea();
        $protected = $this->idea();
        $cycle = VotingCycle::create(['name' => 'Ciclo protegido', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'status' => 'draft', 'weight_configuration' => []]);
        $cycle->ideas()->attach($protected->id, ['snapshot' => '{}']);
        $this->actingAs($this->admin)->delete(route('ideas.bulk-destroy'), $this->payload([$first->id, $protected->id]))->assertSessionHasErrors('ids');
        $this->assertNotSoftDeleted($first);
        $this->assertNotSoftDeleted($protected);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'idea_soft_deleted']);
        $this->get(route('ideas.index'))->assertOk()->assertSeeText('Vinculada a um ciclo ou votação');
    }

    public function test_validation_rejects_empty_duplicates_missing_ids_and_unconfirmed_deletion(): void
    {
        $idea = $this->idea();
        $this->actingAs($this->admin)->delete(route('ideas.bulk-destroy'), $this->payload([]))->assertSessionHasErrors('ids');
        $this->delete(route('ideas.bulk-destroy'), $this->payload([$idea->id, $idea->id]))->assertSessionHasErrors('ids.0');
        $this->delete(route('ideas.bulk-destroy'), $this->payload([999999]))->assertSessionHasErrors('ids.0');
        $this->delete(route('ideas.bulk-destroy'), [...$this->payload([$idea->id]), 'confirm_deletion' => 0, 'deletion_reason' => 'curta'])->assertSessionHasErrors(['confirm_deletion', 'deletion_reason']);
        $this->from(route('ideas.index'))->delete(route('ideas.bulk-destroy'), ['ids' => 'invalid'])->assertSessionHasErrors('ids');
        $this->get(route('ideas.index'))->assertOk();
        $this->assertNotSoftDeleted($idea);
    }

    public function test_legal_hold_blocks_deletion_and_old_soft_deleted_ids_cannot_be_reused(): void
    {
        $idea = $this->idea();
        DB::table('idea_attachments')->insert(['idea_id' => $idea->id, 'uploaded_by' => $this->author->id, 'path' => 'private/test.txt', 'mime' => 'text/plain', 'size' => 1, 'legal_hold' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin)->delete(route('ideas.bulk-destroy'), $this->payload([$idea->id]))->assertSessionHasErrors('ids');
        $this->assertNotSoftDeleted($idea);
        $unprotected = $this->idea();
        $this->delete(route('ideas.bulk-destroy'), $this->payload([$unprotected->id]))->assertSessionHasNoErrors();
        $this->delete(route('ideas.bulk-destroy'), $this->payload([$unprotected->id]))->assertSessionHasErrors('ids.0');
    }
}
