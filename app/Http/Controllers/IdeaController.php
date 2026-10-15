<?php

namespace App\Http\Controllers;

use App\Http\Requests\CastVoteRequest;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\TransitionIdeaRequest;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use App\Services\IdeaWorkflowService;
use App\Services\VoteAvailabilityService;
use App\Services\VotingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IdeaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(array_keys(Idea::LABELS))], 'mine' => ['nullable', 'boolean'], 'implemented' => ['nullable', 'boolean']]);
        $user = Auth::user();
        $canReview = $user->hasRole(['admin', 'evaluator']) || $user->hasSystemPermission('evaluate_ideas');

        $ideas = Idea::with('originSector', 'beneficiarySectors')
            ->when(! $canReview, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('author_id', $user->id);
                    if ($user->canViewPublicIdeas()) {
                        $query->orWhere('is_public', true);
                    }
                });
            })
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request, $canReview, $user) {
                $term = '%'.$request->string('search').'%';
                $query->where('publication->title', 'like', $term)->orWhere(function ($query) use ($term, $canReview, $user) {
                    if (! $canReview) {
                        $query->where('author_id', $user->id);
                    }
                    $query->where('title', 'like', $term);
                });
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('mine'), fn ($query) => $query->where('author_id', $user->id))
            ->when($request->boolean('implemented'), fn ($query) => $query->whereNotNull('published_at')->where('is_public', true))
            ->latest()
            ->paginate(15)->withQueryString();

        return view('ideas.index', compact('ideas'));
    }

    public function create(): View|RedirectResponse
    {
        $result = $this->authorizeOrRedirect('create', Idea::class, 'Você não tem permissão para criar ideias.');

        if ($result !== true) {
            return $result;
        }

        return view('ideas.create', [
            'sectors' => Sector::all(),
        ]);
    }

    public function review(StoreIdeaRequest $request): View
    {
        $data = $request->validated();
        $originSector = Sector::findOrFail($data['origin_sector_id']);
        $beneficiarySectors = Sector::whereIn('id', $data['beneficiary_sectors'])->get();

        return view('ideas.review', compact('data', 'originSector', 'beneficiarySectors'));
    }

    public function store(StoreIdeaRequest $request, IdeaWorkflowService $workflow): RedirectResponse
    {
        if ($request->input('_action') === 'edit') {
            return redirect()->route('ideas.create')->withInput($request->validated());
        }
        $idea = $workflow->create($request->user(), $request->validated());

        return redirect()->route('ideas.show', $idea)->with('success', 'Ideia enviada! Você pode acompanhar os próximos passos nesta página.');
    }

    public function show(Idea $idea, VoteAvailabilityService $availability): View|RedirectResponse
    {
        $result = $this->authorizeOrRedirect('view', $idea, 'Você não tem permissão para visualizar esta ideia.');
        if ($result !== true) {
            return $result;
        }
        $canReview = auth()->user()->can('evaluate', $idea);
        $isOwner = (int) auth()->id() === (int) $idea->author_id;
        $cycles = VotingCycle::where('status', 'open')->whereHas('ideas', fn ($query) => $query->where('ideas.id', $idea->id))->with('criteria')->get();
        $criteria = $canReview ? EvaluationCriterion::where('active', true)->get() : collect();
        $users = $canReview ? User::orderBy('name')->get(['id', 'name']) : collect();
        $history = $canReview || $isOwner ? $idea->statusHistory()->orderBy('id')->get() : collect();
        $evaluations = $canReview ? $idea->evaluations()->with('scores.criterion')->latest()->get() : collect();

        $voteStates = $cycles->mapWithKeys(fn ($cycle) => [$cycle->id => $availability->forCycle(auth()->user(), $idea, $cycle)]);

        return view('ideas.show', compact('voteStates', 'idea', 'cycles', 'criteria', 'users', 'history', 'evaluations', 'canReview', 'isOwner'));
    }

    public function voteForm(Idea $idea, VotingCycle $cycle, VoteAvailabilityService $availability): View
    {
        $this->authorize('view', $idea);
        abort_unless($cycle->frozen_configuration && in_array($cycle->status, ['open', 'closed'], true), 404);
        $entry = $cycle->ideas()->where('ideas.id', $idea->id)->firstOrFail();
        $snapshot = json_decode($entry->pivot->snapshot, true, flags: JSON_THROW_ON_ERROR);
        $voteState = $availability->forCycle(auth()->user(), $idea, $cycle);

        return view('ideas.vote', compact('idea', 'cycle', 'snapshot', 'voteState'));
    }

    public function vote(CastVoteRequest $request, Idea $idea, VotingService $voting): RedirectResponse
    {
        $voting->cast($request->user(), $idea, $request->validated());

        return redirect()->route('ideas.vote-form', [$idea, $request->validated('voting_cycle_id')])->with('success', 'Voto registrado. Obrigado por ajudar a reconhecer boas ideias!');
    }

    public function approve(TransitionIdeaRequest $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $workflow->transition($request->user(), $idea, $request->validated('status'), $request->validated('notes'));

        return back()->with('success', 'Status atualizado com sucesso.');
    }
}
