<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('evaluate', new Idea);

        $ideas = Idea::with('author', 'originSector', 'beneficiarySectors')
            ->whereIn('status', [
                Idea::STATUS_SUBMITTED,
                Idea::STATUS_TRIAGE,
                Idea::STATUS_EVALUATION,
                Idea::STATUS_APPROVED,
            ])
            ->latest()
            ->get();

        return view('evaluation.index', compact('ideas'));
    }
}
