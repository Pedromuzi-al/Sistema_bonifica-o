<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCriterion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicalCriterionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,inactive']]);
        $criteria = EvaluationCriterion::when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('active', $request->input('status') === 'active'))->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.criteria.index', compact('criteria'));
    }

    public function create(): View
    {
        $this->authorize('manage-admin');

        return view('admin.criteria.form', ['criterion' => new EvaluationCriterion(['active' => true])]);
    }

    public function edit(EvaluationCriterion $criterion): View
    {
        $this->authorize('manage-admin');

        return view('admin.criteria.form', compact('criterion'));
    }
}
