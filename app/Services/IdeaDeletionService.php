<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class IdeaDeletionService
{
    public function __construct(private IdeaWorkflowService $workflow) {}

    public function blocker(Idea $idea): ?string
    {
        if ($idea->votingCycles()->exists() || $idea->votes()->exists()) {
            return 'Vinculada a um ciclo ou votação; histórico protegido.';
        }
        if ($idea->bonusAwards()->exists()) {
            return 'Possui bonificação; histórico protegido.';
        }
        if (! in_array($idea->status, ['submitted', 'triage', 'evaluation', 'awaiting_information', 'ready_for_voting', 'archived', 'rejected', 'idea_bank'], true) || $idea->implementationPlan()->exists() || $idea->result()->exists()) {
            return 'Possui execução ou resultados; histórico protegido.';
        }
        if (DB::table('idea_attachments')->where('idea_id', $idea->id)->where('legal_hold', true)->exists()) {
            return 'Documento com preservação obrigatória.';
        }

        return null;
    }

    public function delete(User $actor, array $ids, string $reason): int
    {
        Gate::forUser($actor)->authorize('manage-admin');

        return DB::transaction(function () use ($actor, $ids, $reason) {
            sort($ids, SORT_NUMERIC);
            $ideas = [];
            // Check the entire batch under locks before deleting any record.
            foreach ($ids as $id) {
                $idea = $this->workflow->lockIdea((int) $id);
                Gate::forUser($actor)->authorize('delete', $idea);
                if ($message = $this->blocker($idea)) {
                    throw ValidationException::withMessages(['ids' => $idea->idea_code.': '.$message]);
                }
                $ideas[] = $idea;
            }
            foreach ($ideas as $idea) {
                IdeaStatusHistory::record($idea, $idea->status, $actor, 'Exclusão administrativa das listagens: '.$reason);
                $this->workflow->audit($actor, $idea, 'idea_soft_deleted', ['reason' => $reason, 'previous_status' => $idea->status]);
                $this->workflow->notifyContributors($idea, 'Ideia excluída das listagens', $idea->idea_code.': '.$reason, 'idea_deleted');
                $idea->delete();
            }

            return count($ideas);
        }, 3);
    }
}
