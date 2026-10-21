<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VotingService
{
    public function __construct(private IdeaWorkflowService $workflow) {}

    public function cast(User $user, Idea $idea, array $data): void
    {
        try {
            DB::transaction(function () use ($user, $idea, $data) {
                $cycle = $this->workflow->lockCycle($data['voting_cycle_id']);
                $idea = $this->workflow->lockIdea($idea->id);
                Gate::forUser($user)->authorize($cycle->isSelection() ? 'voteInSelection' : 'vote', $idea);
                if ($cycle->status !== 'open' || ! $cycle->frozen_configuration || now()->lt($cycle->starts_at) || now()->gte($cycle->ends_at) || ! $cycle->ideas()->where('ideas.id', $idea->id)->exists()) {
                    throw ValidationException::withMessages(['vote' => 'A ideia não está elegível em um ciclo aberto neste período.']);
                }
                $criteria = $cycle->frozen_configuration['criteria'];
                $expectedIds = array_column($criteria, 'id');
                if (array_diff($expectedIds, array_keys($data['scores'])) || array_diff(array_keys($data['scores']), $expectedIds)) {
                    throw ValidationException::withMessages(['scores' => 'Envie exatamente os critérios deste ciclo.']);
                }
                $answered = array_filter($data['scores'], fn ($value) => $value !== null);
                if (! $answered || (! $cycle->frozen_configuration['allow_abstention'] && count($answered) !== count($expectedIds))) {
                    throw ValidationException::withMessages(['scores' => 'Responda os critérios obrigatórios; ao menos uma nota é necessária.']);
                }
                if ($idea->votes()->where('user_id', $user->id)->where('voting_cycle_id', $cycle->id)->exists()) {
                    throw ValidationException::withMessages(['vote' => 'Você já registrou um voto para esta ideia neste ciclo.']);
                }
                $vote = $idea->votes()->create(['user_id' => $user->id, 'voting_cycle_id' => $cycle->id, 'status' => 'cast']);
                foreach ($answered as $criterionId => $score) {
                    $vote->scores()->create(['criterion_id' => $criterionId, 'score' => $score]);
                }
                $this->workflow->notify($user->id, 'Voto registrado', 'Sua avaliação foi contabilizada.', 'vote_received');
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['vote' => 'Você já registrou um voto para esta ideia neste ciclo.']);
        }
    }

    public function invalidate(User $user, Vote $vote, string $notes): void
    {
        Gate::forUser($user)->authorize('manage-admin');
        abort_unless($user->hasSystemPermission('view_audit_logs'), 403);
        DB::transaction(function () use ($user, $vote, $notes) {
            $cycle = $this->workflow->lockCycle($vote->voting_cycle_id);
            $vote = Vote::whereKey($vote->id)->lockForUpdate()->firstOrFail();
            $idea = $this->workflow->lockIdea($vote->idea_id);
            if ($cycle->status !== 'open' || $vote->status !== 'cast' || $idea->hasConflict($user)) {
                throw ValidationException::withMessages(['vote' => 'A correção exige ciclo ainda não apurado, voto válido e ausência de conflito.']);
            }
            $vote->update(['status' => 'invalidated', 'notes' => $notes]);
            $this->workflow->audit($user, $idea, 'vote_invalidated', ['vote_id' => $vote->id, 'cycle_id' => $cycle->id, 'notes' => $notes]);
            $this->workflow->notify($vote->user_id, 'Voto invalidado em revisão', $notes, 'vote_invalidated');
        });
    }
}
