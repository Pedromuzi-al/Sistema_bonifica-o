<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $result = $this->authorizeOrRedirect('manage-admin', null, 'Você não tem permissão para acessar o painel administrativo.');

        if ($result !== true) {
            return $result;
        }

        $stats = [
            'total' => Idea::count(),
            'approved' => Idea::where(fn ($q) => $q->whereHas('statusHistory', fn ($h) => $h->where('to_status', 'approved'))->orWhereIn('status', ['approved', 'implementation', 'implemented', 'validation', 'completed', 'voting', 'voting_closed', 'finalized']))->count(),
            'implemented' => Idea::whereHas('implementationPlan', fn ($q) => $q->whereNotNull('completed_at'))->count(),
            'completed' => Idea::whereHas('result', fn ($q) => $q->whereNotNull('validated_at'))->count(),
        ];

        $ideas = Idea::with('author', 'originSector', 'beneficiarySectors')->latest()->limit(10)->get();
        $sectors = Sector::withCount('ideasAsOrigin')->get();
        $moduleCounts = ['sectors' => Sector::count(), 'users' => User::withTrashed()->count(), 'teams' => Team::count()];

        return view('admin.index', compact('stats', 'ideas', 'sectors', 'moduleCounts'));
    }
}
