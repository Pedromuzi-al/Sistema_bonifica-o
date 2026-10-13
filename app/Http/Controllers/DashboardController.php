<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\VotingCycle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canReview = $user->hasRole(['admin', 'evaluator']) || $user->hasSystemPermission('evaluate_ideas');
        $ideaStats = Idea::query();

        if (! $canReview) {
            $ideaStats->where('author_id', $user->id);
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
            'completed' => (clone $ideaStats)->where('status', Idea::STATUS_COMPLETED)->count(),
            'open_votes' => VotingCycle::where('status', 'open')->where('starts_at', '<=', now())->where('ends_at', '>=', now())->count(),
        ];

        $recentIdeas = Idea::with('originSector')
            ->when(! $canReview, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('is_public', true)
                        ->orWhere('author_id', $user->id);
                });
            })
            ->latest()
            ->limit(5)
            ->get();
        $notifications = $user ? $user->notifications()->latest()->limit(5)->get() : collect();

        return view('dashboard', compact('stats', 'recentIdeas', 'notifications'));
    }
}
