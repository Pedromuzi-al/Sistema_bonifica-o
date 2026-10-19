<?php

namespace App\Services;

use App\Models\BonusAward;
use App\Models\VotingCycle;
use Illuminate\Support\Facades\DB;

class CycleReportService
{
    public function build(VotingCycle $cycle, bool $canManage): array
    {
        $closed = $cycle->status === 'closed';
        $decisions = $closed ? DB::table('cycle_idea_decisions')->where('voting_cycle_id', $cycle->id)->pluck('decision', 'idea_id') : collect();
        $awards = BonusAward::where('voting_cycle_id', $cycle->id)
            ->when(! $canManage, fn ($query) => $query->whereNotNull('published_at'))->get();
        $entries = $cycle->ideas()->get()->map(function ($idea) use ($closed, $decisions, $awards) {
            $snapshot = json_decode($idea->pivot->snapshot, true) ?? [];
            $outcome = $closed ? (json_decode($idea->pivot->outcome ?? 'null', true) ?? []) : [];
            $ideaAwards = $awards->where('idea_id', $idea->id);

            return ['id' => $idea->id, 'code' => $idea->idea_code, 'title' => $snapshot['publication']['title'] ?? 'Publicação indisponível',
                'score' => $outcome['final_score'] ?? null, 'rank' => $outcome['rank'] ?? null, 'votes' => $outcome['vote_count'] ?? null,
                'decision' => $decisions->get($idea->id), 'awards' => $ideaAwards,
                'pending_amount' => $this->amount($ideaAwards->whereNull('approved_at')->whereNull('rejected_at')),
                'approved_amount' => $this->amount($ideaAwards->whereNotNull('approved_at')->whereNull('rejected_at')),
                'published_amount' => $this->amount($ideaAwards->whereNotNull('published_at'))];
        })->sortBy(fn ($entry) => $entry['score'] !== null ? ($entry['rank'] ?? PHP_INT_MAX) : PHP_INT_MAX)->values();
        $votes = $cycle->votes()->where('status', 'cast');

        return ['entries' => $entries, 'closed' => $closed, 'idea_count' => $entries->count(),
            'people' => (clone $votes)->distinct()->count('user_id'), 'vote_count' => (clone $votes)->count(),
            'selected' => $decisions->filter(fn ($value) => $value === 'selected')->count(),
            'pending_decisions' => $cycle->isSelection() && $closed ? $entries->whereNull('decision')->count() : 0,
            'pending_amount' => $this->amount($awards->whereNull('approved_at')->whereNull('rejected_at')),
            'approved_amount' => $this->amount($awards->whereNotNull('approved_at')->whereNull('rejected_at')),
            'published_amount' => $this->amount($awards->whereNotNull('published_at'))];
    }

    private function amount($awards): float
    {
        return $awards->sum(fn ($award) => (int) round((float) $award->award_amount * 100)) / 100;
    }
}
