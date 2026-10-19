<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvaluationRequest;
use App\Http\Requests\ResultRequest;
use App\Models\AuditLog;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\Notification;
use App\Models\User;
use App\Services\IdeaWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function evaluate(EvaluationRequest $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $workflow->evaluate($request->user(), $idea, $request->validated());

        return back()->with('success', 'Parecer técnico registrado.');
    }

    public function plan(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('evaluate', $idea);
        $data = $request->validate(['owner_user_id' => ['required', 'integer', 'exists:users,id,deleted_at,NULL'], 'title' => ['required', 'string', 'max:255'], 'plan' => ['required', 'string', 'max:10000'], 'due_date' => ['required', 'date', 'after_or_equal:today']]);
        $workflow->plan($request->user(), $idea, $data);

        return back()->with('success', 'Plano de implementação registrado.');
    }

    public function results(ResultRequest $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $workflow->results($request->user(), $idea, $request->validated());

        return back()->with('success', 'Resultados e evidências registrados.');
    }

    public function validateResults(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('evaluate', $idea);
        $data = $request->validate(['notes' => ['required', 'string', 'max:10000']]);
        $workflow->validateResults($request->user(), $idea, $data['notes']);

        return back()->with('success', 'Resultados validados.');
    }

    public function publish(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('evaluate', $idea);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'problem' => ['required', 'string', 'max:10000'], 'solution' => ['required', 'string', 'max:10000'], 'results' => ['required', 'string', 'max:20000'], 'category' => ['required', 'string', 'max:255'], 'privacy_reviewed' => ['accepted']]);
        unset($data['privacy_reviewed']);
        $workflow->publish($request->user(), $idea, $data);

        return back()->with('success', 'Publicação revisada disponibilizada.');
    }

    public function publishProposal(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('evaluate', $idea);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'problem' => ['required', 'string', 'max:10000'], 'solution' => ['required', 'string', 'max:10000'], 'results' => ['required', 'string', 'max:20000'], 'category' => ['required', 'string', 'max:255'], 'privacy_reviewed' => ['accepted']]);
        unset($data['privacy_reviewed']);
        $workflow->publishProposal($request->user(), $idea, $data);

        return back()->with('success', 'Proposta revisada e disponível para seleção, sem exigir implementação.');
    }

    public function reply(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('view', $idea);
        abort_unless($idea->isContributor($request->user()), 403);
        $data = $request->validate(['notes' => ['required', 'string', 'max:10000']]);
        DB::transaction(function () use ($request, $idea, $workflow, $data) {
            $idea = $workflow->lockIdea($idea->id);
            $workflow->requireStatus($idea, ['awaiting_information']);
            $workflow->audit($request->user(), $idea, 'clarification_received', ['notes' => $data['notes']]);
            $reviewers = User::permission('evaluate_ideas')->whereNull('blocked_at')->get();
            foreach ($reviewers as $reviewer) {
                if ($reviewer->can('evaluate', $idea)) {
                    $workflow->notify($reviewer->id, 'Esclarecimento recebido', 'Há nova resposta privada em '.$idea->idea_code.'.', 'clarification_received');
                }
            }
            IdeaStatusHistory::create(['idea_id' => $idea->id, 'from_status' => $idea->status, 'to_status' => $idea->status, 'changed_by_user_id' => $request->user()->id, 'notes' => 'Resposta do autor: '.$data['notes']]);
        });

        return back()->with('success', 'Esclarecimento enviado à equipe de avaliação.');
    }

    public function conflict(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('view', $idea);
        $data = $request->validate(['reason' => ['required', 'string', 'max:10000']]);
        DB::transaction(function () use ($request, $idea, $workflow, $data) {
            $idea = $workflow->lockIdea($idea->id);
            if ($idea->votes()->where('user_id', $request->user()->id)->exists() || $idea->evaluations()->where('evaluator_id', $request->user()->id)->exists()) {
                throw ValidationException::withMessages(['reason' => 'Já houve participação. Solicite revisão administrativa auditada antes de declarar o impedimento.']);
            }
            DB::table('idea_conflicts')->updateOrInsert(['idea_id' => $idea->id, 'user_id' => $request->user()->id], ['reason' => $data['reason'], 'created_at' => now(), 'updated_at' => now()]);
            $workflow->audit($request->user(), $idea, 'conflict_declared', $data);
        });

        return back()->with('success', 'Conflito registrado. Avaliação e votação foram bloqueadas para você.');
    }

    public function criterion(Request $request): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:1000'], 'weight' => ['required', 'numeric', 'gt:0', 'max:100']]);
        DB::transaction(function () use ($request, $data) {
            $criterion = EvaluationCriterion::create([...$data, 'active' => true]);
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => EvaluationCriterion::class, 'model_id' => $criterion->id, 'action' => 'criterion_created', 'details' => $data]);
        });

        return redirect()->route('admin.criteria.index')->with('success', 'Critério técnico criado.');
    }

    public function notifications(Request $request): View
    {
        $request->validate(['unread' => ['nullable', 'boolean']]);
        $notifications = $request->user()->notifications()->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))->latest('id')->paginate(20)->withQueryString();
        $unreadCount = $request->user()->notifications()->whereNull('read_at')->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function readAllNotifications(Request $request): RedirectResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Todos os seus avisos foram marcados como lidos.');
    }

    public function readNotification(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notificação marcada como lida.');
    }
}
