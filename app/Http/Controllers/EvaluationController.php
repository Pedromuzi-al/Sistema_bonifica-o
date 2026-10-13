<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        $result = $this->authorizeOrRedirect('evaluate', new Idea, 'Você não tem permissão para acessar a avaliação.');

        if ($result !== true) {
            return $result;
        }

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
