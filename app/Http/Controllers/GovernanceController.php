<?php

namespace App\Http\Controllers;

use App\Http\Requests\CycleScheduleRequest;
use App\Models\AuditLog;
use App\Models\BonusAward;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\User;
use App\Models\VotingCycle;
use App\Services\AdministrationService;
use App\Services\IdeaWorkflowService;
use App\Services\PrivateDocumentService;
use App\Services\RecognitionGovernanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GovernanceController extends Controller
{
    public function criterion(Request $request, EvaluationCriterion $criterion, RecognitionGovernanceService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:1000'], 'weight' => ['required', 'numeric', 'gt:0', 'max:100'], 'active' => ['required', 'boolean']]);
        $service->updateCriterion($request->user(), $criterion, $data);

        return redirect()->route('admin.criteria.index')->with('success', 'Critério atualizado. As notas e descrições dos pareceres anteriores foram preservadas.');
    }

    public function draft(CycleScheduleRequest $request, VotingCycle $cycle, RecognitionGovernanceService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->safe()->only(['name', 'starts_at', 'ends_at']);
        $service->draft($request->user(), $cycle, $data);

        return back()->with('success', 'Nome e período do rascunho atualizados.');
    }

    public function cancel(Request $request, VotingCycle $cycle, RecognitionGovernanceService $service): RedirectResponse
    {
        $service->draft($request->user(), $cycle, [], true);

        return back()->with('success', 'Rascunho cancelado. O histórico foi preservado.');
    }

    public function contributors(Request $request, Idea $idea, RecognitionGovernanceService $service): RedirectResponse
    {
        $this->authorize('manage-admin');
        $data = $request->validate(['contributors' => ['required', 'array', 'min:1', 'max:20'], 'contributors.*' => ['array:user_id,share'], 'contributors.*.user_id' => ['required', 'integer', 'distinct', 'exists:users,id'], 'contributors.*.share' => ['required', 'integer', 'between:1,100']]);
        $service->contributors($request->user(), $idea, $data['contributors']);

        return back()->with('success', 'Participantes e divisão registrados privadamente.');
    }

    public function review(Request $request, VotingCycle $cycle, Idea $idea, RecognitionGovernanceService $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'min:10', 'max:10000'], 'decision' => ['sometimes', 'in:validated,new_cycle_required']]);
        $service->review($request->user(), $cycle, $idea, $data['notes'], $data['decision'] ?? 'validated');

        return back()->with('success', 'Revisão e decisão registradas. Se necessário, recuse o prêmio proposto e realize um novo ciclo.');
    }

    public function rejectAward(Request $request, BonusAward $award, RecognitionGovernanceService $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'min:10', 'max:10000']]);
        $service->rejectAward($request->user(), $award, $data['notes']);

        return back()->with('success', 'Reconhecimento recusado com justificativa e histórico preservado.');
    }

    public function finalize(Request $request, VotingCycle $cycle, Idea $idea, RecognitionGovernanceService $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'max:10000']]);
        $service->finalize($request->user(), $cycle, $idea, $data['notes']);

        return back()->with('success', 'Processo da ideia finalizado.');
    }

    private function authorizeDocuments(Request $request, Idea $idea): void
    {
        abort_unless($idea->isContributor($request->user()) || $request->user()->can('evaluate', $idea) || app(PrivateDocumentService::class)->canReview($request->user()), 404);
    }

    public function upload(Request $request, Idea $idea, IdeaWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeDocuments($request, $idea);
        abort_unless(config('innovation.attachments_enabled'), 404);
        $request->validate(['document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,txt', 'max:10240']]);
        $path = null;
        try {
            DB::transaction(function () use ($request, $idea, $workflow, &$path) {
                $idea = $workflow->lockIdea($idea->id);
                abort_unless(in_array($idea->status, ['submitted', 'triage', 'evaluation', 'awaiting_information', 'implementation', 'validation'], true), 422, 'Esta etapa não permite adicionar documentos.');
                abort_if(DB::table('idea_attachments')->where('idea_id', $idea->id)->whereNull('deleted_at')->count() >= 10, 422, 'Limite de dez documentos por proposta.');
                $path = $request->file('document')->store('idea-documents', 'local');
                abort_unless($path, 500, 'Não foi possível armazenar o documento.');
                $id = DB::table('idea_attachments')->insertGetId(['idea_id' => $idea->id, 'uploaded_by' => $request->user()->id, 'path' => $path, 'mime' => $request->file('document')->getMimeType(), 'size' => $request->file('document')->getSize(), 'created_at' => now(), 'updated_at' => now()]);
                $workflow->audit($request->user(), $idea, 'private_document_uploaded', ['document_id' => $id]);
            }, 3);
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }

        return back()->with('success', 'Documento privado adicionado. Ele não será publicado nem incluído na votação.');
    }

    public function download(Request $request, Idea $idea, int $document, IdeaWorkflowService $workflow): StreamedResponse
    {
        $this->authorizeDocuments($request, $idea);
        $record = DB::table('idea_attachments')->where('idea_id', $idea->id)->where('id', $document)->first();
        abort_unless($record && ! $record->deleted_at && ! in_array($record->review_status, ['deleted', 'deletion_pending'], true) && Storage::disk('local')->exists($record->path), 404);
        abort_if($record->review_status === 'restricted' && ! app(PrivateDocumentService::class)->canReview($request->user()), 404);
        $extensions = ['application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg', 'text/plain' => 'txt'];
        $workflow->audit($request->user(), $idea, 'private_document_downloaded', ['document_id' => $document]);

        return Storage::disk('local')->download($record->path, 'documento-'.$record->id.'.'.($extensions[$record->mime] ?? 'bin'), ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function reviewDocument(Request $request, Idea $idea, int $document, PrivateDocumentService $documents): RedirectResponse
    {
        $this->authorize('manage-admin');
        abort_unless($documents->canReview($request->user()), 403);
        $data = $request->validate([
            'action' => ['required', 'in:approve,restrict,delete'],
            'notes' => ['required', 'string', 'min:20', 'max:5000'],
            'retention_until' => ['nullable', 'date', 'after:today'],
            'legal_hold' => ['sometimes', 'boolean'],
            'review_confirmed' => ['accepted'],
        ]);
        if (! $documents->review($request->user(), $idea, $document, $data)) {
            return back()->withErrors(['document' => 'Decisão registrada, mas a exclusão do arquivo está pendente. O download foi bloqueado e a rotina de retenção tentará novamente.']);
        }

        return back()->with('success', 'Revisão registrada. Documentos excluídos não podem ser recuperados pela aplicação.');
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $data = ['exported_at' => now()->toIso8601String(), 'account' => $user->only('name', 'email', 'created_at'), 'ideas' => Idea::where('author_id', $user->id)->get(['idea_code', 'title', 'problem_description', 'solution_description', 'expected_results', 'status', 'created_at'])->toArray(), 'participations' => DB::table('idea_contributors')->join('ideas', 'ideas.id', '=', 'idea_contributors.idea_id')->where('idea_contributors.user_id', $user->id)->get(['ideas.idea_code', 'idea_contributors.share'])->toArray(), 'privacy_requests' => DB::table('privacy_requests')->where('user_id', $user->id)->get(['id', 'status', 'reason', 'decision', 'created_at', 'reviewed_at'])->toArray(), 'votes' => $user->votes()->with('scores')->get()->makeHidden(['user_id'])->toArray(), 'notifications' => $user->notifications()->get()->toArray(), 'award_shares' => DB::table('award_allocations')->join('bonus_awards', 'bonus_awards.id', '=', 'award_allocations.bonus_award_id')->where('award_allocations.user_id', $user->id)->whereNotNull('bonus_awards.published_at')->get(['bonus_awards.category', 'bonus_awards.published_at', 'award_allocations.amount'])->toArray()];

        return response()->streamDownload(fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), 'meus-dados.json', ['Content-Type' => 'application/json', 'Cache-Control' => 'private, no-store']);
    }

    public function privacy(Request $request): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! DB::table('privacy_requests')->where('user_id', $request->user()->id)->where('status', 'pending')->exists()) {
                DB::table('privacy_requests')->insert(['user_id' => $request->user()->id, 'reason' => $data['reason'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Solicitação registrada. A administração avaliará o pedido e a necessidade de preservar os registros de negócio.');
    }

    public function privacyIndex(): View
    {
        $this->authorize('manage-admin');
        abort_unless(auth()->user()->hasSystemPermission('view_audit_logs'), 403);
        $requests = DB::table('privacy_requests')->join('users', 'users.id', '=', 'privacy_requests.user_id')->select('privacy_requests.*', 'users.name', 'users.email')->latest('privacy_requests.id')->paginate(20);

        return view('admin.privacy', compact('requests'));
    }

    public function privacyDecision(Request $request, int $privacy, AdministrationService $administration): RedirectResponse
    {
        $this->authorize('manage-admin');
        abort_unless($request->user()->hasSystemPermission('view_audit_logs'), 403);
        $data = $request->validate(['decision' => ['required', 'string', 'min:20', 'max:5000'], 'action' => ['required', 'in:retain,anonymize'], 'review_confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $privacy, $data, $administration) {
            // Use the same account-lock order as access administration.
            $administration->lockAccounts($request->user());
            $record = DB::table('privacy_requests')->where('id', $privacy)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 422);
            $target = User::withTrashed()->findOrFail($record->user_id);
            if ($data['action'] === 'anonymize') {
                abort_if($target->id === $request->user()->id, 422, 'Outro administrador deve analisar sua solicitação.');
                $ideaIds = Idea::where('author_id', $target->id)->orWhereIn('id', DB::table('idea_contributors')->where('user_id', $target->id)->select('idea_id'))->pluck('id');
                abort_if(Idea::whereIn('id', $ideaIds)->whereNotIn('status', ['finalized', 'rejected'])->exists() || BonusAward::whereIn('idea_id', $ideaIds)->whereNull('published_at')->whereNull('rejected_at')->exists(), 422, 'Conclua as propostas e os reconhecimentos pendentes antes de anonimizar a conta.');
                if (! $target->blocked_at && ! $target->trashed()) {
                    $administration->block($request->user(), $target, 'Solicitação de privacidade aprovada.');
                }
                DB::table('password_reset_tokens')->where('email', $target->email)->delete();
                DB::table('sessions')->where('user_id', $target->id)->delete();
                $target->syncRoles([]);
                $target->syncPermissions([]);
                $target->teams()->detach();
                $target->forceFill(['name' => 'Conta anonimizada', 'email' => 'anonymous-'.$target->id.'-'.Str::random(20).'@invalid.local', 'password' => Str::random(64), 'email_verified_at' => null, 'sector_id' => null, 'blocked_at' => now(), 'block_reason' => 'Conta anonimizada por solicitação.', 'remember_token' => null, 'access_version' => $target->access_version + 1, 'deleted_at' => now(), 'anonymized_at' => now()])->save();
                DB::table('notifications')->where('user_id', $target->id)->delete();
            }
            if ($data['action'] === 'retain') {
                $target->notifications()->create(['title' => 'Solicitação de privacidade analisada', 'message' => $data['decision'], 'type' => 'privacy_decision']);
            }
            DB::table('privacy_requests')->where('id', $privacy)->update(['status' => $data['action'] === 'anonymize' ? 'account_anonymized' : 'retained', 'reason' => 'Solicitação analisada; conteúdo pessoal removido após decisão.', 'decision' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'updated_at' => now()]);
            AuditLog::create(['causer_id' => $request->user()->id, 'model_type' => User::class, 'model_id' => $target->id, 'action' => 'privacy_request_reviewed', 'details' => ['request_id' => $privacy, 'decision' => $data['decision'], 'action' => $data['action']]]);
        }, 3);

        return back()->with('success', 'Decisão registrada. Documentos e registros de negócio seguem a política de retenção informada na decisão.');
    }
}
