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
use App\Services\IdeaDeletionService;
use App\Services\IdeaWorkflowService;
use App\Services\PrivateDocumentService;
use App\Services\VoteAvailabilityService;
use App\Services\VotingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $ideas = Idea::with('originSector', 'beneficiarySectors', 'contributors:id')
            ->when(! $canReview, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where(fn ($q) => $q->where('author_id', $user->id)->orWhereIn('id', DB::table('idea_contributors')->where('user_id', $user->id)->select('idea_id')));
                    if ($user->canViewPublicIdeas()) {
                        $query->orWhere('is_public', true);
                    }
                });
            })
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request, $canReview, $user) {
                $term = '%'.$request->string('search').'%';
                $query->where('publication->title', 'like', $term)->orWhere(function ($query) use ($term, $canReview, $user) {
                    if (! $canReview) {
                        $query->where(fn ($q) => $q->where('author_id', $user->id)->orWhereIn('id', DB::table('idea_contributors')->where('user_id', $user->id)->select('idea_id')));
                    }
                    $query->where('title', 'like', $term);
                });
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('mine'), fn ($query) => $query->where(fn ($q) => $q->where('author_id', $user->id)->orWhereIn('id', DB::table('idea_contributors')->where('user_id', $user->id)->select('idea_id'))))
            ->when($request->boolean('implemented'), fn ($query) => $query->whereNotNull('published_at')->where('is_public', true)->whereHas('result', fn ($query) => $query->whereNotNull('validated_at')))
            ->latest()
            ->paginate(15)->withQueryString();

        $deletionBlockers = $user->can('manage-admin') ? $ideas->getCollection()->mapWithKeys(fn ($idea) => [$idea->id => app(IdeaDeletionService::class)->blocker($idea)]) : collect();

        return view('ideas.index', compact('ideas', 'deletionBlockers'));
    }

    public function bulkDestroy(Request $request, IdeaDeletionService $deletion): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('ideas', 'id')->whereNull('deleted_at')],
            'deletion_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'confirm_deletion' => ['accepted'],
        ]);
        $count = $deletion->delete($request->user(), $data['ids'], $data['deletion_reason']);

        return redirect()->route('ideas.index')->with('success', $count.' ideia(s) excluída(s) das listagens. O histórico foi preservado.');
    }

    public function create(): View|RedirectResponse
    {
        $result = $this->authorizeOrRedirect('create', Idea::class, 'Você não tem permissão para criar ideias.');

        if ($result !== true) {
            return $result;
        }

        return view('ideas.create', [
            'sectors' => Sector::where('is_active', true)->orderBy('name')->get(),
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
        $isOwner = $idea->isContributor(auth()->user());
        $cycles = VotingCycle::where('status', 'open')->whereHas('ideas', fn ($query) => $query->where('ideas.id', $idea->id))->with('criteria')->get();
        $criteria = $canReview ? EvaluationCriterion::where('active', true)->get() : collect();
        $users = $canReview ? User::orderBy('name')->get(['id', 'name']) : collect();
        $history = $canReview || $isOwner ? $idea->statusHistory()->orderBy('id')->get() : collect();
        $evaluations = $canReview ? $idea->evaluations()->with('scores.criterion')->latest()->get() : collect();

        $documents = $canReview || $isOwner || app(PrivateDocumentService::class)->canReview(auth()->user()) ? DB::table('idea_attachments')->where('idea_id', $idea->id)->orderBy('id')->get(['id', 'size', 'created_at', 'review_status', 'review_notes', 'reviewed_at', 'retention_until', 'legal_hold', 'deleted_at']) : collect();
        $contributors = auth()->user()->can('manage-admin') && auth()->user()->hasSystemPermission('view_author_identity') ? DB::table('idea_contributors')->where('idea_id', $idea->id)->get(['user_id', 'share'])->map(fn ($row) => (array) $row)->all() : [];
        $contributorOptions = auth()->user()->can('manage-admin') && auth()->user()->hasSystemPermission('view_author_identity') ? User::whereNull('blocked_at')->orderBy('name')->get(['id', 'name']) : collect();
        $voteStates = $cycles->mapWithKeys(fn ($cycle) => [$cycle->id => $availability->forCycle(auth()->user(), $idea, $cycle)]);

        return view('ideas.show', compact('documents', 'contributors', 'contributorOptions', 'voteStates', 'idea', 'cycles', 'criteria', 'users', 'history', 'evaluations', 'canReview', 'isOwner'));
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
