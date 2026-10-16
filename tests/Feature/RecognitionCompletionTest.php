<?php

namespace Tests\Feature;

use App\Models\BonusAward;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Services\CycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecognitionCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function accounts(): array
    {
        $this->seed(DatabaseSeeder::class);
        $coauthor = User::factory()->create();
        $coauthor->assignRole('employee');

        return [User::where('email', 'admin@corp.local')->firstOrFail(), User::where('email', 'funcionario@corp.local')->firstOrFail(), $coauthor, User::where('email', 'avaliador@corp.local')->firstOrFail()];
    }

    private function idea(User $author, string $status = 'submitted'): Idea
    {
        return Idea::create(['author_id' => $author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Melhoria privada', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Benefícios', 'resources_needed' => 'Recursos', 'status' => $status]);
    }

    private function data(): array
    {
        return ['name' => 'Ciclo de reconhecimento', 'starts_at' => now()->addHour()->toDateTimeString(), 'ends_at' => now()->addHours(2)->toDateTimeString(), 'weights' => ['real_results' => 50, 'technical' => 30, 'votes' => 20], 'criteria' => [['name' => 'Impacto', 'description' => 'Avalie o resultado', 'weight' => 100]], 'result_indicators' => [['indicator' => 'Tempo', 'unit' => 'horas', 'direction' => 'decrease', 'target' => 10]], 'minimum_votes' => 1, 'allow_abstention' => false, 'category' => 'Destaque', 'minimum_score' => 0, 'reward_amount' => 101.01, 'max_awards' => 1];
    }

    private function ready(User $author, User $evaluator): Idea
    {
        $idea = $this->idea($author, 'completed');
        $idea->result()->create(['impact_summary' => 'Resultado', 'measurements' => [['indicator' => 'Tempo', 'before' => 20, 'after' => 10, 'unit' => 'horas', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'evidence' => 'Medição']], 'validated_by_user_id' => $evaluator->id, 'validated_at' => now()]);
        $idea->evaluations()->create(['evaluator_id' => $evaluator->id, 'technical_score' => 100, 'recommendation' => 'approve', 'notes' => 'Viável', 'reviewed_at' => now()]);
        $idea->update(['is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Melhoria pública', 'problem' => 'Problema', 'solution' => 'Solução', 'results' => 'Dez horas', 'category' => 'Processos', 'sectors' => ['Operações']]]);

        return $idea;
    }

    public function test_latest_negative_opinion_prevents_approval_and_cycle_selection(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $idea->evaluations()->create(['evaluator_id' => $evaluator->id, 'technical_score' => 0, 'recommendation' => 'reject', 'notes' => 'Nova informação inviabiliza', 'reviewed_at' => now()]);
        $idea->update(['status' => 'evaluation']);
        $this->actingAs($admin)->post(route('ideas.approve', $idea), ['status' => 'approved', 'notes' => 'Aprovar'])->assertSessionHasErrors('status');
        $this->assertSame('evaluation', $idea->fresh()->status);
        $idea->update(['status' => 'completed']);
        $cycle = app(CycleService::class)->create($admin, $this->data());
        $this->post(route('cycles.open', $cycle), ['ideas' => [$idea->id]])->assertSessionHasErrors('ideas');
        $this->assertSame('draft', $cycle->fresh()->status);
    }

    public function test_criterion_changes_preserve_historical_description_and_scores(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $criterion = EvaluationCriterion::create(['name' => 'Critério original', 'description' => 'Descrição', 'weight' => 100, 'active' => true]);
        $idea = $this->idea($author, 'evaluation');
        $this->actingAs($evaluator)->post(route('ideas.evaluate', $idea), ['scores' => [$criterion->id => 5], 'recommendation' => 'approve', 'notes' => 'Viável'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('criteria.update', $criterion), ['name' => 'Critério novo', 'description' => 'Alterado', 'weight' => 50, 'active' => false])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('idea_evaluation_scores', ['criterion_id' => $criterion->id, 'score' => 5, 'notes' => 'Critério original; peso 100.00; orientação Descrição']);
        $this->assertFalse($criterion->fresh()->active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'criterion_updated']);
    }

    public function test_employee_cannot_edit_criteria_or_define_contributors(): void
    {
        [,$author,$coauthor] = $this->accounts();
        $idea = $this->idea($author);
        $criterion = EvaluationCriterion::create(['name' => 'Viabilidade', 'description' => 'Descrição', 'weight' => 100, 'active' => true]);
        $this->actingAs($author)->patchJson(route('criteria.update', $criterion), [])->assertForbidden();
        $this->putJson(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $author->id, 'share' => 50], ['user_id' => $coauthor->id, 'share' => 50]]])->assertForbidden();
        $this->assertDatabaseCount('idea_contributors', 0);
    }

    public function test_draft_dates_can_be_recovered_and_cancelled_but_frozen_cycles_cannot_change(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $service = app(CycleService::class);
        $cycle = $service->create($admin, $this->data());
        $this->travel(90)->minutes();
        $this->actingAs($admin)->patch(route('cycles.draft', $cycle), ['name' => 'Nova programação', 'starts_at' => now()->addHour()->toDateTimeString(), 'ends_at' => now()->addHours(2)->toDateTimeString()])->assertSessionHasNoErrors();
        $this->get(route('cycles.show', $cycle))->assertOk()->assertSeeText('Nova programação');
        $service->open($admin, $cycle->fresh(), [$this->ready($author, $evaluator)->id]);
        $this->post(route('cycles.cancel', $cycle))->assertSessionHasErrors('cycle');
        $other = $service->create($admin, $this->data());
        $this->post(route('cycles.cancel', $other))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $other->fresh()->status);
    }

    public function test_coauthor_access_is_private_and_shares_must_include_author_and_total_one_hundred(): void
    {
        [$admin,$author,$coauthor] = $this->accounts();
        $idea = $this->idea($author);
        $this->actingAs($admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $coauthor->id, 'share' => 100]]])->assertSessionHasErrors('contributors');
        $this->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $author->id, 'share' => 60], ['user_id' => $coauthor->id, 'share' => 40]]])->assertSessionHasNoErrors();
        $this->actingAs($coauthor)->get(route('ideas.show', $idea))->assertOk()->assertSeeText('Melhoria privada')->assertDontSeeText($author->name);
        $this->get(route('ideas.index', ['mine' => 1]))->assertSeeText('Melhoria privada');
        $this->assertFalse($coauthor->can('evaluate', $idea));
        $idea->update(['status' => 'evaluation']);
        $this->actingAs($admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $author->id, 'share' => 100]]])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('idea_contributors', 2);
    }

    public function test_category_rewards_split_frozen_shares_without_losing_cents_and_coauthors_cannot_vote(): void
    {
        [$admin,$author,$coauthor,$evaluator] = $this->accounts();
        $idea = $this->idea($author);
        $this->actingAs($admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $author->id, 'share' => 60], ['user_id' => $coauthor->id, 'share' => 40]]])->assertSessionHasNoErrors();
        $idea->update(['status' => 'completed', 'is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Publicação', 'problem' => 'Problema', 'solution' => 'Solução', 'results' => 'Resultado', 'category' => 'Processos']]);
        $idea->result()->create(['measurements' => [['indicator' => 'Tempo', 'unit' => 'horas', 'before' => 20, 'after' => 10]], 'impact_summary' => 'Resultado', 'validated_at' => now()]);
        $idea->evaluations()->create(['evaluator_id' => $evaluator->id, 'technical_score' => 100, 'recommendation' => 'approve', 'notes' => 'Viável']);
        $data = $this->data();
        $data['categories'] = [['name' => 'Excelência', 'minimum_score' => 90, 'maximum_score' => 100, 'reward_amount' => 101.01, 'max_awards' => 2], ['name' => 'Contribuição', 'minimum_score' => 0, 'maximum_score' => 89.99, 'reward_amount' => 25, 'max_awards' => 2]];
        $cycle = app(CycleService::class)->create($admin, $data);
        app(CycleService::class)->open($admin, $cycle, [$idea->id]);
        $this->travel(61)->minutes();
        $vote = ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => 5]];
        $this->actingAs($coauthor)->postJson(route('ideas.vote', $idea), $vote)->assertForbidden();
        $voter = User::factory()->create();
        $voter->assignRole('employee');
        $this->actingAs($voter)->post(route('ideas.vote', $idea), $vote)->assertSessionHasNoErrors();
        $this->travel(60)->minutes();
        $this->actingAs($admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $award = BonusAward::where('voting_cycle_id', $cycle->id)->firstOrFail();
        $this->assertSame('Excelência', $award->category);
        $this->assertDatabaseHas('award_allocations', ['bonus_award_id' => $award->id, 'user_id' => $author->id, 'amount' => 60.60]);
        $this->assertDatabaseHas('award_allocations', ['bonus_award_id' => $award->id, 'user_id' => $coauthor->id, 'amount' => 40.41]);
        $this->post(route('awards.approve', $award), ['notes' => 'Conferido'])->assertSessionHasNoErrors();
        $this->post(route('awards.publish', $award))->assertSessionHasNoErrors();
        $this->actingAs($coauthor)->get(route('dashboard'))->assertOk()->assertViewHas('awards', fn ($awards) => (float) $awards->first()->award_amount === 40.41);
        $this->actingAs($voter)->get(route('cycles.show', $cycle))->assertOk()->assertDontSeeText($author->name)->assertDontSeeText($coauthor->name);
    }

    public function test_overlapping_reward_bands_are_rejected(): void
    {
        [$admin] = $this->accounts();
        $data = $this->data();
        $data['categories'] = [['name' => 'A', 'minimum_score' => 70, 'maximum_score' => 100, 'reward_amount' => 100, 'max_awards' => 1], ['name' => 'B', 'minimum_score' => 50, 'maximum_score' => 70, 'reward_amount' => 50, 'max_awards' => 1]];
        $this->actingAs($admin)->post(route('cycles.store'), $data)->assertSessionHasErrors('categories');
        $this->assertDatabaseMissing('voting_cycles', ['name' => $data['name']]);
    }

    public function test_non_winning_idea_can_be_finalized_after_closed_cycle(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $cycle = app(CycleService::class)->create($admin, $this->data());
        app(CycleService::class)->open($admin, $cycle, [$idea->id]);
        $this->travel(121)->minutes();
        $this->actingAs($admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $this->post(route('cycles.finalize', [$cycle, $idea]), ['notes' => 'Sem votos suficientes, processo encerrado.'])->assertSessionHasNoErrors();
        $this->assertSame('finalized', $idea->fresh()->status);
        $this->assertDatabaseHas('idea_status_histories', ['idea_id' => $idea->id, 'to_status' => 'finalized']);
    }

    public function test_voting_alert_requires_an_audited_review_before_award_approval(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $data = $this->data();
        $data['anomaly_threshold'] = 2;
        $cycle = app(CycleService::class)->create($admin, $data);
        app(CycleService::class)->open($admin, $cycle, [$idea->id]);
        $this->travel(61)->minutes();
        foreach (range(1, 2) as $i) {
            $voter = User::factory()->create();
            $voter->assignRole('employee');
            $this->actingAs($voter)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => 5]])->assertSessionHasNoErrors();
        }
        $this->travel(60)->minutes();
        $this->actingAs($admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $award = BonusAward::where('voting_cycle_id', $cycle->id)->firstOrFail();
        $this->post(route('awards.approve', $award), ['notes' => 'Conferido'])->assertSessionHasErrors('award');
        $this->post(route('cycles.review', [$cycle, $idea]), ['notes' => 'Notas coincidem, porém as evidências confirmam a validade da apuração.'])->assertSessionHasNoErrors();
        $this->post(route('awards.approve', $award), ['notes' => 'Revisão concluída'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cycle_reviews', ['idea_id' => $idea->id, 'reviewed_by' => $admin->id]);
    }

    public function test_documents_are_private_not_public_and_do_not_preserve_original_names(): void
    {
        [$admin,$author,$other] = $this->accounts();
        Storage::fake('local');
        config(['innovation.attachments_enabled' => true]);
        $idea = $this->idea($author);
        $this->actingAs($author)->post(route('ideas.documents.store', $idea), ['document' => UploadedFile::fake()->createWithContent('Nome-pessoal.txt', 'Fonte de medição')])->assertSessionHasNoErrors();
        $document = DB::table('idea_attachments')->first();
        Storage::disk('local')->assertExists($document->path);
        $this->assertStringNotContainsString('Nome-pessoal', $document->path);
        $this->get(route('ideas.documents.download', [$idea, $document->id]))->assertOk()->assertDownload('documento-'.$document->id.'.txt');
        $this->actingAs($other)->get(route('ideas.documents.download', [$idea, $document->id]))->assertNotFound();
        $this->post(route('ideas.documents.store', $idea), ['document' => UploadedFile::fake()->createWithContent('proibido.txt', 'conteúdo')])->assertNotFound();
        $idea->update(['is_public' => true, 'publication' => ['title' => 'Publicação', 'problem' => 'Problema', 'solution' => 'Solução', 'results' => 'Resultado']]);
        $this->get(route('ideas.show', $idea))->assertOk()->assertDontSeeText('Documentos privados')->assertDontSeeText('Nome-pessoal');
        $this->actingAs($admin)->get(route('ideas.documents.download', [$idea, $document->id]))->assertOk();
    }

    public function test_disabled_uploads_and_executable_files_are_rejected(): void
    {
        [,$author] = $this->accounts();
        Storage::fake('local');
        $idea = $this->idea($author);
        $this->actingAs($author)->post(route('ideas.documents.store', $idea), ['document' => UploadedFile::fake()->createWithContent('exemplo.txt', 'Texto')])->assertNotFound();
        config(['innovation.attachments_enabled' => true]);
        $this->post(route('ideas.documents.store', $idea), ['document' => UploadedFile::fake()->createWithContent('ataque.php', '<?php echo "executar";')])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('idea_attachments', 0);
    }

    public function test_report_counts_validated_ideas_after_voting_and_separates_indicator_units(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $idea->update(['status' => 'finalized']);
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertViewHas('stats', fn ($stats) => $stats['completed'] === 1 && $stats['approved'] === 1);
        $this->get(route('reports.index'))->assertOk()->assertSeeText('Evolução das contribuições')->assertViewHas('benefits', fn ($benefits) => count($benefits) === 1 && (float) array_values($benefits)[0]['difference'] === -10.0);
    }

    public function test_rejected_recognition_can_be_finalized_and_cannot_be_approved(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $cycle = app(CycleService::class)->create($admin, $this->data());
        app(CycleService::class)->open($admin, $cycle, [$idea->id]);
        $this->travel(61)->minutes();
        $this->actingAs($evaluator)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => 5]])->assertSessionHasNoErrors();
        $this->travel(60)->minutes();
        $this->actingAs($admin)->post(route('cycles.close', $cycle))->assertSessionHasNoErrors();
        $award = BonusAward::where('voting_cycle_id', $cycle->id)->firstOrFail();
        $this->post(route('cycles.finalize', [$cycle, $idea]), ['notes' => 'Encerrar'])->assertSessionHasErrors('cycle');
        $this->post(route('awards.reject', $award), ['notes' => 'Evidências insuficientes para conceder este reconhecimento.'])->assertSessionHasNoErrors();
        $this->post(route('awards.approve', $award), ['notes' => 'Aprovar depois'])->assertSessionHasErrors('award');
        $this->post(route('cycles.finalize', [$cycle, $idea]), ['notes' => 'Reconhecimento recusado e processo encerrado.'])->assertSessionHasNoErrors();
        $this->assertSame('finalized', $idea->fresh()->status);
        $this->assertNotNull($award->fresh()->rejected_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'award_rejected']);
    }

    public function test_publishing_an_old_award_cannot_finalize_a_later_cycle(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $service = app(CycleService::class);
        $first = $service->create($admin, $this->data());
        $service->open($admin, $first, [$idea->id]);
        $this->travel(61)->minutes();
        $this->actingAs($evaluator)->post(route('ideas.vote', $idea), ['voting_cycle_id' => $first->id, 'scores' => [$first->criteria()->first()->id => 5]])->assertSessionHasNoErrors();
        $this->travel(60)->minutes();
        $service->close($admin, $first);
        $award = BonusAward::where('voting_cycle_id', $first->id)->firstOrFail();
        $service->approveAward($admin, $award, 'Evidências conferidas.');
        $second = $service->create($admin, $this->data());
        $service->open($admin, $second, [$idea->id]);
        $this->travel(121)->minutes();
        $service->close($admin, $second);
        $service->publishAward($admin, $award);
        $this->assertSame('voting_closed', $idea->fresh()->status);
        $this->assertNotNull($award->fresh()->published_at);
    }

    public function test_custom_tie_breaking_uses_the_frozen_order(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $first = $this->ready($author, $evaluator);
        $second = $this->ready($author, $evaluator);
        $second->evaluations()->first()->update(['technical_score' => 50]);
        $data = $this->data();
        $data['weights'] = ['real_results' => 100, 'technical' => 0, 'votes' => 0];
        $data['tie_break_order'] = ['votes', 'technical', 'real_results'];
        $cycle = app(CycleService::class)->create($admin, $data);
        app(CycleService::class)->open($admin, $cycle, [$first->id, $second->id]);
        $this->travel(61)->minutes();
        foreach ([$first->id => 1, $second->id => 5] as $id => $score) {
            $this->actingAs($evaluator)->post(route('ideas.vote', $id), ['voting_cycle_id' => $cycle->id, 'scores' => [$cycle->criteria()->first()->id => $score]])->assertSessionHasNoErrors();
        }
        $this->travel(60)->minutes();
        app(CycleService::class)->close($admin, $cycle);
        $this->assertDatabaseHas('bonus_awards', ['voting_cycle_id' => $cycle->id, 'idea_id' => $second->id]);
        $this->assertSame(['votes', 'technical', 'real_results'], $cycle->fresh()->frozen_configuration['tie_break_order']);
    }

    public function test_evaluation_dashboard_filters_summary_and_personal_opinion_history(): void
    {
        [$admin,$author,,$evaluator] = $this->accounts();
        $idea = $this->ready($author, $evaluator);
        $this->actingAs($evaluator)->get(route('evaluation.index', ['category' => 'Processos']))->assertOk()->assertViewHas('summary', fn ($summary) => $summary->get('completed') === 1)->assertViewHas('evaluationHistory', fn ($history) => $history->count() === 1 && $history->first()->idea_id === $idea->id)->assertSeeText('Meus pareceres recentes');
        $this->get(route('evaluation.index', ['category' => 'Outra categoria']))->assertOk()->assertViewHas('summary', fn ($summary) => $summary->isEmpty())->assertViewHas('evaluationHistory', fn ($history) => $history->isEmpty());
    }

    public function test_personal_export_includes_only_the_current_contributor_share(): void
    {
        [$admin,$author,$coauthor] = $this->accounts();
        $idea = $this->idea($author);
        $this->actingAs($admin)->put(route('ideas.contributors', $idea), ['contributors' => [['user_id' => $author->id, 'share' => 60], ['user_id' => $coauthor->id, 'share' => 40]]])->assertSessionHasNoErrors();
        $response = $this->actingAs($coauthor)->withSession(['auth.password_confirmed_at' => time()])->get(route('privacy.export'))->assertOk();
        $data = json_decode($response->streamedContent(), true);
        $this->assertCount(1, $data['participations']);
        $this->assertSame($idea->idea_code, $data['participations'][0]['idea_code']);
        $this->assertSame(40, (int) $data['participations'][0]['share']);
        $this->assertStringNotContainsString($author->email, $response->streamedContent());
    }
}
