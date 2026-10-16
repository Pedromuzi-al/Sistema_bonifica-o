<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BonusAward;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\User;
use App\Models\VotingCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecognitionGovernanceService
{
    public function __construct(private IdeaWorkflowService $workflow) {}

    public function updateCriterion(User $actor, EvaluationCriterion $criterion, array $data): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $criterion, $data) {
            $criterion = EvaluationCriterion::whereKey($criterion->id)->lockForUpdate()->firstOrFail();
            $before = $criterion->only('name', 'description', 'weight', 'active');
            $criterion->update($data);
            AuditLog::create(['causer_id' => $actor->id, 'model_type' => EvaluationCriterion::class, 'model_id' => $criterion->id, 'action' => 'criterion_updated', 'details' => ['before' => $before, 'after' => $data]]);
        });
    }

    public function draft(User $actor, VotingCycle $cycle, array $data, bool $cancel = false): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $cycle, $data, $cancel) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            if ($cycle->status !== 'draft' || $cycle->frozen_configuration) {
                throw ValidationException::withMessages(['cycle' => 'Somente rascunhos podem ser editados ou cancelados.']);
            }
            $cycle->update($cancel ? ['status' => 'cancelled'] : $data);
            AuditLog::create(['causer_id' => $actor->id, 'model_type' => VotingCycle::class, 'model_id' => $cycle->id, 'action' => $cancel ? 'cycle_cancelled' : 'cycle_draft_updated', 'details' => $data]);
        }, 3);
    }

    public function contributors(User $actor, Idea $idea, array $contributors): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        abort_unless($actor->hasSystemPermission('view_author_identity'), 403);
        DB::transaction(function () use ($actor, $idea, $contributors) {
            $idea = $this->workflow->lockIdea($idea->id);
            $this->workflow->requireStatus($idea, ['submitted', 'triage']);
            $ids = array_column($contributors, 'user_id');
            if (array_sum(array_column($contributors, 'share')) !== 100 || ! in_array((int) $idea->author_id, array_map('intval', $ids), true) || count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['contributors' => 'Inclua o autor principal, sem repetir pessoas, e distribua exatamente 100%.']);
            }
            if (User::whereIn('id', $ids)->whereNull('blocked_at')->count() !== count($ids)) {
                throw ValidationException::withMessages(['contributors' => 'Selecione somente contas ativas.']);
            }
            DB::table('idea_contributors')->where('idea_id', $idea->id)->delete();
            foreach ($contributors as $row) {
                DB::table('idea_contributors')->insert(['idea_id' => $idea->id, 'user_id' => $row['user_id'], 'share' => $row['share'], 'created_at' => now(), 'updated_at' => now()]);
                $this->workflow->notify($row['user_id'], 'Participação em proposta', 'Sua participação foi registrada em '.$idea->idea_code.'. Consulte a proposta e contate a administração se precisar corrigir.', 'contribution_registered');
            }
            $this->workflow->audit($actor, $idea, 'contributors_defined', ['contributors' => $contributors]);
        }, 3);
    }

    public function review(User $actor, VotingCycle $cycle, Idea $idea, string $notes, string $decision = 'validated'): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        abort_unless($actor->hasSystemPermission('view_audit_logs'), 403);
        DB::transaction(function () use ($actor, $cycle, $idea, $notes, $decision) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            $idea = $this->workflow->lockIdea($idea->id);
            $entry = $cycle->ideas()->where('ideas.id', $idea->id)->firstOrFail();
            $outcome = json_decode($entry->pivot->outcome ?? 'null', true);
            if ($cycle->status !== 'closed' || ! ($outcome['review_required'] ?? false) || $idea->hasConflict($actor) || DB::table('cycle_reviews')->where('voting_cycle_id', $cycle->id)->where('idea_id', $idea->id)->exists()) {
                throw ValidationException::withMessages(['review' => 'Revisão indisponível, já registrada ou com conflito de interesse.']);
            }
            DB::table('cycle_reviews')->insert(['voting_cycle_id' => $cycle->id, 'idea_id' => $idea->id, 'reviewed_by' => $actor->id, 'notes' => $notes, 'decision' => $decision, 'created_at' => now(), 'updated_at' => now()]);
            $this->workflow->audit($actor, $idea, 'voting_alert_reviewed', ['cycle_id' => $cycle->id, 'notes' => $notes, 'decision' => $decision]);
        }, 3);
    }

    public function rejectAward(User $actor, BonusAward $award, string $notes): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $award, $notes) {
            $cycle = $this->workflow->lockCycle($award->voting_cycle_id);
            $award = $this->workflow->lockAward($award->id);
            $idea = $this->workflow->lockIdea($award->idea_id);
            if ($cycle->status !== 'closed' || $award->published_at || $award->approved_at || $award->rejected_at || $idea->hasConflict($actor)) {
                throw ValidationException::withMessages(['award' => 'Somente propostas de reconhecimento pendentes podem ser recusadas, sem conflito de interesse.']);
            }
            $award->update(['rejected_at' => now(), 'rejected_by' => $actor->id, 'justification' => $notes]);
            $this->workflow->audit($actor, $idea, 'award_rejected', ['award_id' => $award->id, 'notes' => $notes]);
            $this->workflow->notifyContributors($idea, 'Reconhecimento não aprovado', $notes, 'award_rejected');
        }, 3);
    }

    public function finalize(User $actor, VotingCycle $cycle, Idea $idea, string $notes): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $cycle, $idea, $notes) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            $idea = $this->workflow->lockIdea($idea->id);
            $cycle->ideas()->where('ideas.id', $idea->id)->firstOrFail();
            if ($cycle->status !== 'closed' || (int) DB::table('idea_voting_cycle')->where('idea_id', $idea->id)->latest('id')->value('voting_cycle_id') !== (int) $cycle->id || BonusAward::where('idea_id', $idea->id)->where('voting_cycle_id', $cycle->id)->whereNull('published_at')->whereNull('rejected_at')->exists() || $idea->hasConflict($actor)) {
                throw ValidationException::withMessages(['cycle' => 'Encerre o ciclo mais recente e conclua os reconhecimentos pendentes antes de finalizar.']);
            }
            $this->workflow->transition($actor, $idea, 'finalized', $notes, true);
        }, 3);
    }
}
