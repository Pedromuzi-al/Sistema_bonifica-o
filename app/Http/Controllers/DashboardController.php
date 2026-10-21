<?php

namespace App\Http\Controllers;

use App\Models\BonusAward;
use App\Models\Idea;
use App\Models\VotingCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canReview = $user->hasRole(['admin', 'evaluator']) || $user->hasSystemPermission('evaluate_ideas');
        $ideaStats = Idea::query();

        if (! $canReview) {
            $ideaStats->where(fn ($q) => $q->where('author_id', $user->id)->orWhereIn('id', DB::table('idea_contributors')->where('user_id', $user->id)->select('idea_id')));
        }

        $stats = [
            'total' => (clone $ideaStats)->count(),
            'in_evaluation' => (clone $ideaStats)->whereIn('status', [
                Idea::STATUS_SUBMITTED,
                Idea::STATUS_TRIAGE,
                Idea::STATUS_EVALUATION,
            ])->count(),
            'approved' => (clone $ideaStats)->where('status', Idea::STATUS_APPROVED)->count(),
            'implementation' => (clone $ideaStats)->where('status', Idea::STATUS_IMPLEMENTATION)->count(),
            'completed' => (clone $ideaStats)->whereIn('status', [Idea::STATUS_COMPLETED, Idea::STATUS_VOTING, Idea::STATUS_VOTING_CLOSED, Idea::STATUS_FINALIZED])->count(),
            'open_votes' => VotingCycle::whereNotNull('frozen_configuration')->where('status', 'open')->where('starts_at', '<=', now())->where('ends_at', '>', now())->count(),
        ];

        $recentIdeas = Idea::with('originSector', 'contributors:id')
            ->when(! $canReview, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where(fn ($q) => $q->where('author_id', $user->id)->orWhereIn('id', DB::table('idea_contributors')->where('user_id', $user->id)->select('idea_id')));
                    if ($user->canViewPublicIdeas()) {
                        $query->orWhere('is_public', true);
                    }
                });
            })
            ->latest()
            ->limit(5)
            ->get();
        $notifications = $user ? $user->notifications()->latest()->limit(5)->get() : collect();

        $awards = BonusAward::where(fn ($q) => $q->where('user_id', $user->id)->orWhereIn('id', DB::table('award_allocations')->where('user_id', $user->id)->select('bonus_award_id')))->whereNotNull('published_at')->with('idea')->latest()->get();
        $allocations = DB::table('award_allocations')->where('user_id', $user->id)->pluck('amount', 'bonus_award_id');
        foreach ($awards as $award) {
            if ($allocations->has($award->id)) {
                $award->award_amount = $allocations[$award->id];
            }
        }
        $participation = $user->votes()->count();

        return view('dashboard', compact('stats', 'recentIdeas', 'notifications', 'awards', 'participation'));
    }
}
