<?php

namespace App\Http\Controllers;

use App\Http\Requests\CycleRequest;
use App\Models\BonusAward;
use App\Models\Idea;
use App\Models\Vote;
use App\Models\VotingCycle;
use App\Services\CycleReportService;
use App\Services\CycleService;
use App\Services\SelectionCycleService;
use App\Services\VotingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CycleController extends Controller
{
    public function index(Request $request): View
    {
        $canManage = $request->user()->can('manage-admin');
        abort_unless($canManage || $request->user()->canViewPublicIdeas(), 403);
        $cycles = VotingCycle::when(! $canManage, fn ($query) => $query->whereIn('status', ['open', 'closed']))->latest('id')->paginate(10);

        return view('cycles.index', compact('cycles', 'canManage'));
    }

    public function create(Request $request): View
    {
        $this->authorize('manage-admin');
        $canManage = true;
        $ideas = Idea::with('result')->whereNotNull('published_at')->whereIn('status', ['completed', 'voting_closed', 'finalized'])->get();
        $indicatorOptions = $ideas->flatMap(fn ($idea) => collect($idea->result?->measurements ?? [])
            ->map(fn ($measurement) => ['indicator' => $measurement['indicator'], 'unit' => $measurement['unit']]))
            ->unique(fn ($measurement) => $measurement['indicator'].'|'.$measurement['unit'])->values();

        return view('cycles.create', compact('canManage', 'indicatorOptions'));
    }

    public function store(CycleRequest $request, CycleService $service): RedirectResponse
    {
        $cycle = $service->create($request->user(), $request->validated());

        return redirect()->route('cycles.show', $cycle)->with('success', 'Rascunho criado. Revise a metodologia e selecione as ideias antes da abertura.');
    }

    public function show(Request $request, VotingCycle $cycle, SelectionCycleService $selection, CycleReportService $reports): View
    {
        $canManage = $request->user()->can('manage-admin');
        abort_unless($canManage || ($request->user()->canViewPublicIdeas() && in_array($cycle->status, ['open', 'closed'], true)), 403);
        $entries = $cycle->ideas()->get()->map(fn ($idea) => ['idea' => $idea, 'snapshot' => json_decode($idea->pivot->snapshot, true), 'outcome' => $cycle->status === 'closed' || $canManage ? json_decode($idea->pivot->outcome ?? 'null', true) : null]);
        $entries = $entries->sortBy(fn ($entry) => $entry['outcome']['rank'] ?? PHP_INT_MAX);
        $ideas = $canManage && $cycle->status === 'draft' ? Idea::where('is_public', true)->whereNotNull('published_at')->whereIn('status', $cycle->isSelection() ? SelectionCycleService::ELIGIBLE_STATUSES : ['completed', 'voting_closed', 'finalized'])->get() : collect();
        $unavailableIdeas = $ideas->mapWithKeys(fn ($idea) => [$idea->id => $selection->participationBlocker($idea)])->filter();
        $awards = BonusAward::where('voting_cycle_id', $cycle->id)->when(! $canManage, fn ($query) => $query->whereNotNull('published_at'))->get();

        $reviewVotes = $canManage && $request->user()->hasSystemPermission('view_audit_logs') && $cycle->status === 'open'
            ? $cycle->votes()->where('status', 'cast')->get(['id', 'idea_id', 'created_at']) : collect();

        $reviews = $canManage ? DB::table('cycle_reviews')->where('voting_cycle_id', $cycle->id)->get(['idea_id', 'notes', 'decision'])->keyBy('idea_id') : collect();

        $decisions = DB::table('cycle_idea_decisions')->where('voting_cycle_id', $cycle->id)->get($canManage ? ['idea_id', 'decision', 'notes'] : ['idea_id', 'decision'])->keyBy('idea_id');

        $cycleReport = $reports->build($cycle, $canManage);

        return view('cycles.show', compact('cycleReport', 'unavailableIdeas', 'decisions', 'reviews', 'cycle', 'entries', 'ideas', 'canManage', 'awards', 'reviewVotes'));
    }

    public function open(Request $request, VotingCycle $cycle, CycleService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['ideas' => ['required', 'array', 'min:1'], 'ideas.*' => ['integer', 'distinct', 'exists:ideas,id']]);
        $service->open($request->user(), $cycle, $data['ideas']);

        return back()->with('success', 'Ciclo aberto e regras congeladas.');
    }

    public function close(Request $request, VotingCycle $cycle, CycleService $service): RedirectResponse
    {
        $service->close($request->user(), $cycle);

        return back()->with('success', $cycle->isSelection() ? 'Votação apurada. Agora a equipe decide quais ideias avançam e quais permanecem no banco.' : 'Ciclo apurado. Bonificações propostas aguardam aprovação.');
    }

    public function decide(Request $request, VotingCycle $cycle, Idea $idea, SelectionCycleService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['decision' => ['required', 'in:selected,bank'], 'notes' => ['required', 'string', 'min:10', 'max:10000']]);
        $service->decide($request->user(), $cycle, $idea, $data['decision'], $data['notes']);

        return back()->with('success', 'Decisão registrada e participantes avisados.');
    }

    public function approve(Request $request, BonusAward $award, CycleService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['notes' => ['required', 'string', 'max:10000']]);
        $service->approveAward($request->user(), $award, $data['notes']);

        return back()->with('success', 'Reconhecimento aprovado.');
    }

    public function publish(Request $request, BonusAward $award, CycleService $service): RedirectResponse
    {
        $service->publishAward($request->user(), $award);

        return back()->with('success', 'Reconhecimento divulgado.');
    }

    public function invalidateVote(Request $request, Vote $vote, VotingService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['notes' => ['required', 'string', 'max:10000']]);
        $service->invalidate($request->user(), $vote, $data['notes']);

        return back()->with('success', 'Voto invalidado. As notas foram preservadas para auditoria.');
    }
}
