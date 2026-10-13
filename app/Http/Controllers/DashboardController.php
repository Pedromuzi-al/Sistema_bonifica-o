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

        $stats = [
            'total' => Idea::count(),
            'in_evaluation' => Idea::whereIn('status', [
                Idea::STATUS_SUBMITTED,
                Idea::STATUS_TRIAGE,
                Idea::STATUS_EVALUATION,
            ])->count(),
            'approved' => Idea::where('status', Idea::STATUS_APPROVED)->count(),
            'implementation' => Idea::where('status', Idea::STATUS_IMPLEMENTATION)->count(),
            'completed' => Idea::where('status', Idea::STATUS_COMPLETED)->count(),
            'open_votes' => VotingCycle::where('status', 'open')->where('starts_at', '<=', now())->where('ends_at', '>=', now())->count(),
        ];

        $recentIdeas = Idea::with('author', 'originSector')->latest()->limit(5)->get();
        $notifications = $user ? $user->notifications()->latest()->limit(5)->get() : collect();

        return view('dashboard', compact('stats', 'recentIdeas', 'notifications'));
    }
}
