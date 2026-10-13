<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\Notification;
use App\Models\Sector;
use App\Models\Vote;
use App\Models\VotingCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IdeaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $ideas = Idea::with('author', 'originSector', 'beneficiarySectors')->latest()->get();

        return view('ideas.index', compact('ideas'));
    }

    public function create(): View
    {
        return view('ideas.create', [
            'sectors' => Sector::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'problem_description' => ['required', 'string'],
            'solution_description' => ['required', 'string'],
            'origin_sector_id' => ['required', 'exists:sectors,id'],
            'beneficiary_sectors' => ['nullable', 'array'],
            'beneficiary_sectors.*' => ['exists:sectors,id'],
            'expected_results' => ['required', 'string'],
            'resources_needed' => ['required', 'string'],
            'additional_information' => ['nullable', 'string'],
        ]);

        $idea = Idea::create([
            'author_id' => Auth::id(),
            'origin_sector_id' => $validated['origin_sector_id'],
            'title' => $validated['title'],
            'problem_description' => $validated['problem_description'],
            'solution_description' => $validated['solution_description'],
            'expected_results' => $validated['expected_results'],
            'resources_needed' => $validated['resources_needed'],
            'additional_information' => $validated['additional_information'] ?? null,
            'status' => Idea::STATUS_SUBMITTED,
            'is_public' => false,
        ]);

        foreach ($validated['beneficiary_sectors'] ?? [] as $sectorId) {
            $idea->beneficiarySectors()->attach($sectorId);
        }

        IdeaStatusHistory::record($idea, Idea::STATUS_SUBMITTED, Auth::user(), 'Ideia registrada com sucesso.');

        AuditLog::create([
            'causer_id' => Auth::id(),
            'model_type' => Idea::class,
            'model_id' => $idea->id,
            'action' => 'created',
            'details' => ['title' => $idea->title],
        ]);

        Notification::create([
            'user_id' => Auth::id(),
            'title' => 'Ideia enviada',
            'message' => 'Sua proposta foi cadastrada com sucesso e está em análise.',
            'type' => 'idea_created',
        ]);

        return redirect()->route('ideas.show', $idea)->with('success', 'Ideia cadastrada com sucesso.');
    }

    public function show(Idea $idea)
    {
        $this->authorize('view', $idea);

        return view('ideas.show', compact('idea'));
    }

    public function vote(Request $request, Idea $idea)
    {
        $this->authorize('vote', $idea);

        $cycle = VotingCycle::where('status', 'open')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        if (! $cycle) {
            return back()->withErrors(['vote' => 'Não há ciclo de votação aberto no momento.']);
        }

        if ($idea->votes()->where('user_id', Auth::id())->where('voting_cycle_id', $cycle->id)->exists()) {
            return back()->withErrors(['vote' => 'Você já registrou um voto para esta ideia neste ciclo.']);
        }

        $vote = $idea->votes()->create([
            'user_id' => Auth::id(),
            'voting_cycle_id' => $cycle->id,
            'status' => 'cast',
        ]);

        foreach ($cycle->criteria as $criterion) {
            $score = $request->input('criteria_' . $criterion->id, null);

            if ($score !== null && is_numeric($score)) {
                $vote->scores()->create([
                    'criterion_id' => $criterion->id,
                    'score' => max(0, min((float) $score, 5)),
                ]);
            }
        }

        Notification::create([
            'user_id' => Auth::id(),
            'title' => 'Voto registrado',
            'message' => 'Seu voto foi contabilizado com sucesso.',
            'type' => 'vote_received',
        ]);

        return back()->with('success', 'Voto registrado com sucesso.');
    }

    public function approve(Request $request, Idea $idea)
    {
        $this->authorize('approve', $idea);

        $request->validate([
            'status' => ['required', 'in:approved,rejected,implementation,implemented,completed'],
            'notes' => ['nullable', 'string'],
        ]);

        IdeaStatusHistory::record($idea, $request->status, Auth::user(), $request->notes ?? 'Avaliação registrada.');

        return back()->with('success', 'Status atualizado com sucesso.');
    }
}
