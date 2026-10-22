<?php

namespace App\Http\Controllers;

use App\Http\Requests\BonusPolicyRequest;
use App\Http\Requests\TechnicalModelRequest;
use App\Models\AuditLog;
use App\Models\BonusPolicy;
use App\Models\TechnicalModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialRulesController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-admin');

        return view('financial.index', ['criteriaCount' => TechnicalModel::count(), 'policiesCount' => BonusPolicy::count()]);
    }

    public function policies(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,inactive']]);
        $policies = BonusPolicy::when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('active', $request->input('status') === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('financial.policies.index', compact('policies'));
    }

    public function create(): View
    {
        $this->authorize('manage-admin');

        return view('financial.policies.form', ['policy' => new BonusPolicy(['active' => true, 'reward_amount' => 0, 'minimum_score' => 0, 'allow_abstention' => false, 'anomaly_threshold' => 5])]);
    }

    public function edit(BonusPolicy $policy): View
    {
        $this->authorize('manage-admin');

        return view('financial.policies.form', compact('policy'));
    }

    public function store(BonusPolicyRequest $request): RedirectResponse
    {
        return $this->save($request, new BonusPolicy);
    }

    public function update(BonusPolicyRequest $request, BonusPolicy $policy): RedirectResponse
    {
        return $this->save($request, $policy);
    }

    private function save(BonusPolicyRequest $request, BonusPolicy $policy): RedirectResponse
    {
        DB::transaction(function () use ($request, $policy) {
            $policy = $policy->exists ? BonusPolicy::whereKey($policy->id)->lockForUpdate()->firstOrFail() : $policy;
            $before = $policy->toArray();
            $policy->fill($request->validated())->save();
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => BonusPolicy::class, 'model_id' => $policy->id, 'action' => 'bonus_policy_saved', 'details' => ['before' => $before, 'after' => $policy->toArray()]]);
        }, 3);

        return redirect()->route('financial.policies.index')->with('success', 'Regra salva. Ciclos existentes mantêm os valores e regras já registrados.');
    }

    public function technicalModels(Request $request): View
    {
        $this->authorize('manage-admin');
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,inactive']]);
        $models = TechnicalModel::when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('active', $request->input('status') === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('financial.technical.index', compact('models'));
    }

    public function createTechnicalModel(): View
    {
        $this->authorize('manage-admin');

        return view('financial.technical.form', ['model' => new TechnicalModel(['active' => true, 'criteria' => [
            ['name' => 'Utilidade', 'description' => 'Quanto esta ideia pode ajudar no trabalho?', 'weight' => 34],
            ['name' => 'Relevância', 'description' => 'Qual a importância do problema que esta ideia resolve?', 'weight' => 33],
            ['name' => 'Benefício esperado', 'description' => 'Qual o potencial de melhoria apresentado pela proposta?', 'weight' => 33],
        ]])]);
    }

    public function editTechnicalModel(TechnicalModel $model): View
    {
        $this->authorize('manage-admin');

        return view('financial.technical.form', compact('model'));
    }

    public function storeTechnicalModel(TechnicalModelRequest $request): RedirectResponse
    {
        return $this->saveTechnicalModel($request, new TechnicalModel);
    }

    public function updateTechnicalModel(TechnicalModelRequest $request, TechnicalModel $model): RedirectResponse
    {
        return $this->saveTechnicalModel($request, $model);
    }

    private function saveTechnicalModel(TechnicalModelRequest $request, TechnicalModel $model): RedirectResponse
    {
        DB::transaction(function () use ($request, $model) {
            $model = $model->exists ? TechnicalModel::whereKey($model->id)->lockForUpdate()->firstOrFail() : $model;
            $before = $model->toArray();
            $model->fill($request->validated())->save();
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => TechnicalModel::class, 'model_id' => $model->id, 'action' => 'technical_model_saved', 'details' => ['before' => $before, 'after' => $model->toArray()]]);
        }, 3);

        return redirect()->route('financial.technical.index')->with('success', 'Modelo salvo. As perguntas e pesos dos ciclos existentes foram preservados.');
    }
}
