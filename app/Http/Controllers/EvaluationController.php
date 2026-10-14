<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $result = $this->authorizeOrRedirect('evaluate', new Idea, 'Você não tem permissão para acessar a avaliação.');

        if ($result !== true) {
            return $result;
        }

        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'sector' => ['nullable', 'integer', 'exists:sectors,id'], 'owner' => ['nullable', 'integer', 'exists:users,id'], 'status' => ['nullable', Rule::in(array_keys(Idea::LABELS))]]);
        $summary = Idea::select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $deadlines = Idea::with('implementationPlan')->whereHas('implementationPlan', fn ($query) => $query->whereNull('completed_at')->where('due_date', '<=', now()->addWeek()))->get();
        $ideas = Idea::with('originSector', 'beneficiarySectors', 'implementationPlan')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('sector'), fn ($q) => $q->where('origin_sector_id', $request->integer('sector')))
            ->when($request->filled('owner'), fn ($q) => $q->whereHas('implementationPlan', fn ($q) => $q->where('owner_user_id', $request->integer('owner'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->latest()->paginate(20)->withQueryString();
        $sectors = Sector::all();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('evaluation.index', compact('ideas', 'sectors', 'users', 'summary', 'deadlines'));
    }
}
