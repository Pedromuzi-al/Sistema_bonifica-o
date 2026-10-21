<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Services\PrivateDocumentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FinalDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    private Idea $idea;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['innovation.attachments_enabled' => true]);
        $this->admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->idea = $this->author->ideas()->firstOrFail();
    }

    private function document(): object
    {
        $this->actingAs($this->author)->post(route('ideas.documents.store', $this->idea), ['document' => UploadedFile::fake()->createWithContent('evidencia.txt', 'Evidência para revisão de privacidade')])->assertSessionHasNoErrors();

        return DB::table('idea_attachments')->latest('id')->first();
    }

    private function review(int $id, array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => now()->timestamp])->patch(route('ideas.documents.review', [$this->idea, $id]), [...['action' => 'approve', 'notes' => 'Revisei os dados e a necessidade de retenção deste documento.', 'review_confirmed' => 1], ...$overrides]);
    }

    public function test_document_review_restricts_access_without_exposing_original_names(): void
    {
        $document = $this->document();
        $this->review($document->id, ['action' => 'restrict'])->assertSessionHasNoErrors();
        $this->actingAs($this->author)->get(route('ideas.documents.download', [$this->idea, $document->id]))->assertNotFound();
        $this->get(route('ideas.show', $this->idea))->assertSeeText('Restrito à revisão administrativa')->assertDontSee('Baixar documento #'.$document->id);
        $this->actingAs($this->admin)->get(route('ideas.documents.download', [$this->idea, $document->id]))->assertOk();
        $this->review($document->id)->assertSessionHasNoErrors();
        $this->actingAs($this->author)->get(route('ideas.documents.download', [$this->idea, $document->id]))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'private_document_reviewed']);
    }

    public function test_only_audit_administrators_can_review_and_must_confirm_the_review(): void
    {
        $document = $this->document();
        $this->actingAs($this->author)->withSession(['auth.password_confirmed_at' => now()->timestamp])->patchJson(route('ideas.documents.review', [$this->idea, $document->id]), ['action' => 'delete'])->assertForbidden();
        $this->review($document->id, ['review_confirmed' => 0])->assertSessionHasErrors('review_confirmed');
        $this->admin->syncRoles(['employee']);
        $this->admin->syncPermissions(['manage_admin']);
        $this->actingAs($this->admin->fresh())->patchJson(route('ideas.documents.review', [$this->idea, $document->id]), [])->assertForbidden();
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_review_cannot_target_document_of_another_proposal(): void
    {
        $document = $this->document();
        $other = Idea::create(['author_id' => $this->author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Outra', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Resultado', 'resources_needed' => 'Recursos', 'status' => 'submitted']);
        $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => now()->timestamp])->patchJson(route('ideas.documents.review', [$other, $document->id]), ['action' => 'delete', 'notes' => 'Revisão autorizada para exclusão de documento.', 'review_confirmed' => 1])->assertNotFound();
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_definitive_file_deletion_keeps_only_the_business_record_and_audit(): void
    {
        $document = $this->document();
        $this->review($document->id, ['action' => 'delete'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($document->path);
        $this->assertDatabaseHas('idea_attachments', ['id' => $document->id, 'review_status' => 'deleted', 'path' => '']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'private_document_deleted']);
        $this->get(route('ideas.documents.download', [$this->idea, $document->id]))->assertNotFound();
        $this->assertFalse(app(PrivateDocumentService::class)->purge($document->id));
    }

    public function test_retention_dry_run_and_execution_respect_legal_hold(): void
    {
        $due = $this->document();
        $held = $this->document();
        $unreviewed = $this->document();
        $this->review($due->id, ['retention_until' => now()->addDays(2)->toDateString()])->assertSessionHasNoErrors();
        $this->review($held->id, ['retention_until' => now()->addDays(2)->toDateString(), 'legal_hold' => 1])->assertSessionHasNoErrors();
        $this->travel(3)->days();
        $this->artisan('app:purge-private-documents')->expectsOutput('1 documentos elegíveis. Simulação: nenhum arquivo excluído.')->assertSuccessful();
        Storage::disk('local')->assertExists($due->path);
        $this->artisan('app:purge-private-documents --execute')->expectsOutput('1 documentos excluídos.')->assertSuccessful();
        Storage::disk('local')->assertMissing($due->path);
        Storage::disk('local')->assertExists($held->path);
        Storage::disk('local')->assertExists($unreviewed->path);
        $this->review($held->id, ['action' => 'delete'])->assertSessionHasErrors('document');
    }

    public function test_anonymized_accounts_cannot_have_identity_reintroduced_by_edit(): void
    {
        $this->author->forceFill(['anonymized_at' => now(), 'deleted_at' => now(), 'name' => 'Conta anonimizada'])->save();
        $this->actingAs($this->admin)->patch(route('admin.users.update', $this->author->id), ['name' => 'Identidade reintroduzida', 'email' => 'nova@corp.local', 'role' => 'employee'])->assertSessionHasErrors('user');
        $this->assertSame('Conta anonimizada', User::withTrashed()->find($this->author->id)->name);
        $this->get(route('admin.users.edit', $this->author->id))->assertOk()->assertDontSeeText('Salvar dados e acesso')->assertDontSeeText('Reativar conta');
    }

    public function test_compatible_benefits_are_kept_separate_for_each_origin_sector(): void
    {
        foreach (Sector::take(2)->get() as $sector) {
            $idea = Idea::create(['author_id' => $this->author->id, 'origin_sector_id' => $sector->id, 'title' => 'Melhoria', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Resultado', 'resources_needed' => 'Recursos', 'status' => 'completed']);
            $idea->result()->create(['impact_summary' => 'Redução comprovada', 'validated_at' => now(), 'measurements' => [['indicator' => 'Tempo', 'unit' => 'horas', 'before' => 20, 'after' => 10, 'period_start' => '2026-09-01', 'period_end' => '2026-09-30']]]);
        }
        $this->actingAs($this->admin)->get(route('reports.index', ['view' => 'general']))->assertOk()->assertViewHas('benefits', fn ($benefits) => count($benefits) === 2 && count(array_unique(array_column($benefits, 'sector'))) === 2)->assertSeeText('Participação por ciclo');
        $csv = $this->get(route('reports.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Setor de origem', $csv);
        $this->assertStringContainsString('2026-09-01', $csv);
    }

    public function test_password_confirmation_returns_to_the_relevant_screen_before_typing_sensitive_forms(): void
    {
        $this->actingAs($this->admin)->get(route('password.confirm', ['return_to' => 'idea', 'idea_id' => $this->idea->id]))->assertOk()->assertSessionHas('url.intended', route('ideas.show', $this->idea));
        $this->post(route('password.confirm'), ['password' => 'password'])->assertRedirect(route('ideas.show', $this->idea));
        $this->get(route('password.confirm', ['return_to' => 'profile']))->assertSessionHas('url.intended', route('profile.edit'));
        $this->post(route('password.confirm'), ['password' => 'password'])->assertRedirect(route('profile.edit'));
    }

    public function test_profile_has_one_privacy_section_and_asks_for_confirmation_before_request_form(): void
    {
        $response = $this->actingAs($this->author)->get(route('profile.edit'))->assertOk()->assertSeeText('Confirmar senha para solicitar privacidade');
        $this->assertSame(1, substr_count($response->getContent(), 'Meus dados e privacidade'));
    }

    public function test_storage_failure_leaves_deletion_pending_and_blocks_download_without_a_server_error(): void
    {
        $document = $this->document();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->with($document->path)->andReturnTrue();
        $disk->shouldReceive('delete')->with($document->path)->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->review($document->id, ['action' => 'delete'])->assertRedirect()->assertSessionHasErrors('document');
        $this->assertDatabaseHas('idea_attachments', ['id' => $document->id, 'review_status' => 'deletion_pending']);
        $this->actingAs($this->author)->get(route('ideas.documents.download', [$this->idea, $document->id]))->assertNotFound();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'private_document_deleted']);
    }
}
