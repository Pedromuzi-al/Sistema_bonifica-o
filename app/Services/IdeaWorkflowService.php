<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BonusAward;
use App\Models\EvaluationCriterion;
use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\Notification;
use App\Models\User;
use App\Models\VotingCycle;
use App\Support\ImplementationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class IdeaWorkflowService
{
    public const TRANSITIONS = [
        'idea_bank' => ['triage', 'evaluation'],
        'submitted' => ['triage'],
        'triage' => ['evaluation', 'awaiting_information', 'rejected'],
        'evaluation' => ['awaiting_information', 'approved', 'rejected'],
        'awaiting_information' => ['evaluation', 'rejected'],
        'approved' => ['implementation'],
        'implementation' => ['validation'],
        'implemented' => ['validation'],
        'validation' => ['implementation', 'completed'],
        'completed' => ['voting'],
        'voting' => ['voting_closed'],
        'voting_closed' => ['finalized', 'voting'],
        'finalized' => ['voting'],
    ];

    public function create(User $user, array $data): Idea
    {
        Gate::forUser($user)->authorize('create', Idea::class);

        return DB::transaction(function () use ($user, $data) {
            $data['expected_results'] = $data['expected_results'] ?? 'Benefícios a detalhar na triagem.';
            $data['resources_needed'] = $data['resources_needed'] ?? 'Recursos a definir após seleção.';
            $sectors = $data['beneficiary_sectors'];
            unset($data['beneficiary_sectors']);
            $idea = Idea::create([...$data, 'author_id' => $user->id, 'status' => Idea::STATUS_SUBMITTED, 'is_public' => false]);
            $idea->beneficiarySectors()->sync($sectors);
            IdeaStatusHistory::create(['idea_id' => $idea->id, 'from_status' => null, 'to_status' => $idea->status, 'changed_by_user_id' => $user->id, 'notes' => 'Proposta enviada.']);
            $this->audit($user, $idea, 'created');
            $this->notify($user->id, 'Ideia enviada', 'Sua proposta foi registrada.', 'idea_created');

            return $idea;
        });
    }

    public function transition(User $user, Idea $idea, string $status, string $notes, bool $cycleOperation = false): void
    {
        DB::transaction(function () use ($user, $idea, $status, $notes, $cycleOperation) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($user)->authorize($cycleOperation ? 'manage-admin' : 'approve', $cycleOperation ? null : $idea);
            if (! in_array($status, self::TRANSITIONS[$idea->status] ?? [], true) || trim($notes) === '') {
                throw ValidationException::withMessages(['status' => 'Transição inválida ou sem justificativa.']);
            }
            if (! $cycleOperation && in_array($status, ['voting', 'voting_closed', 'finalized'], true)) {
                throw ValidationException::withMessages(['status' => 'Esta etapa é controlada pelo ciclo de votação.']);
            }
            if ($status === 'approved' && $idea->evaluations()->latest('id')->value('recommendation') !== 'approve') {
                throw ValidationException::withMessages(['status' => 'Registre uma avaliação técnica favorável antes de aprovar.']);
            }
            if ($status === 'implementation' && $idea->evaluations()->latest('id')->value('recommendation') === 'reject') {
                throw ValidationException::withMessages(['status' => 'Resolva o parecer técnico desfavorável antes de iniciar ou retomar a implementação.']);
            }
            if ($status === 'implementation' && ! $idea->implementationPlan()->exists()) {
                throw ValidationException::withMessages(['status' => 'Defina o plano, responsável e prazo antes da implementação.']);
            }
            if ($status === 'completed' && ! $idea->result()->whereNotNull('validated_at')->exists()) {
                throw ValidationException::withMessages(['status' => 'Valide os resultados antes de concluir.']);
            }
            IdeaStatusHistory::record($idea, $status, $user, $notes);
            if ($status === 'validation') {
                $idea->implementationPlan()->update(['status' => 'completed', 'completed_at' => now()]);
            }
            $this->audit($user, $idea, 'status_changed', ['status' => $status, 'notes' => $notes]);
            $this->notifyContributors($idea, 'Andamento da proposta', Idea::LABELS[$status].': '.$notes, 'status_changed');
        });
    }

    public function evaluate(User $user, Idea $idea, array $data): void
    {
        DB::transaction(function () use ($user, $idea, $data) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($user)->authorize('evaluate', $idea);
            if (! $idea->canRecordTechnicalEvaluation()) {
                throw ValidationException::withMessages(['status' => 'A avaliação exige a etapa de avaliação ou uma ideia selecionada em ciclo, antes da votação de resultados.']);
            }
            $criteria = EvaluationCriterion::where('active', true)->get();
            if ($criteria->isEmpty() || array_diff($criteria->modelKeys(), array_keys($data['scores'])) || array_diff(array_keys($data['scores']), $criteria->modelKeys())) {
                throw ValidationException::withMessages(['scores' => 'Avalie todos os critérios ativos, sem incluir critérios externos.']);
            }
            $totalWeight = $criteria->sum('weight');
            if ($totalWeight <= 0) {
                throw ValidationException::withMessages(['scores' => 'Os critérios precisam de pesos positivos.']);
            }
            $score = $criteria->sum(fn ($criterion) => (($data['scores'][$criterion->id] - 1) / 4 * 100) * $criterion->weight) / $totalWeight;
            $evaluation = $idea->evaluations()->create(['evaluator_id' => $user->id, 'technical_score' => round($score, 2), 'recommendation' => $data['recommendation'], 'notes' => $data['notes'], 'reviewed_at' => now()]);
            foreach ($criteria as $criterion) {
                $evaluation->scores()->create(['criterion_id' => $criterion->id, 'score' => $data['scores'][$criterion->id], 'notes' => $criterion->name.'; peso '.number_format((float) $criterion->weight, 2, '.', '').'; orientação '.$criterion->description]);
            }
            $this->audit($user, $idea, 'evaluated', ['evaluation_id' => $evaluation->id]);
        });
    }

    public function plan(User $user, Idea $idea, array $data): void
    {
        DB::transaction(function () use ($user, $idea, $data) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($user)->authorize('evaluate', $idea);
            $this->requireStatus($idea, ['approved', 'implementation']);
            $idea->implementationPlan()->updateOrCreate(['idea_id' => $idea->id], [...$data, 'status' => 'planned']);
            $this->audit($user, $idea, 'implementation_plan_updated', $data);
        });
    }

    public function results(User $user, Idea $idea, array $data): void
    {
        DB::transaction(function () use ($user, $idea, $data) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($user)->authorize('evaluate', $idea);
            $this->requireStatus($idea, ['implementation', 'validation']);
            $keys = array_map(fn ($measurement) => $measurement['indicator'].'|'.$measurement['unit'], $data['measurements']);
            if (count($keys) !== count(array_unique($keys))) {
                throw ValidationException::withMessages(['measurements' => 'Não repita o mesmo indicador e unidade.']);
            }
            $previous = $idea->result?->toArray();
            foreach ($data['measurements'] as &$measurement) {
                $before = $measurement['before'] ?? null;
                $after = $measurement['after'] ?? null;
                if (($before === null || $after === null) && empty($measurement['qualitative']) && empty($data['limitations'])) {
                    throw ValidationException::withMessages(['measurements' => 'Informe valores medidos, uma descrição qualitativa ou a limitação dos dados.']);
                }
                $measurement['difference'] = $before !== null && $after !== null ? (float) $after - (float) $before : null;
                $measurement['percentage'] = $before !== null && (float) $before != 0 && $measurement['difference'] !== null ? round($measurement['difference'] / abs((float) $before) * 100, 2) : null;
            }
            unset($measurement);
            $idea->result()->updateOrCreate(['idea_id' => $idea->id], [...$data, 'measured_at' => now(), 'validated_at' => null, 'validated_by_user_id' => null]);
            $this->audit($user, $idea, 'results_recorded', ['previous' => $previous, 'current' => $data]);
        });
    }

    public function validateResults(User $user, Idea $idea, string $notes): void
    {
        DB::transaction(function () use ($user, $idea, $notes) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($user)->authorize('evaluate', $idea);
            $this->requireStatus($idea, ['validation']);
            $result = $idea->result;
            if (! $result || empty($result->measurements)) {
                throw ValidationException::withMessages(['results' => 'Registre indicadores e evidências antes da validação.']);
            }
            $result->update(['validated_by_user_id' => $user->id, 'validated_at' => now()]);
            $this->audit($user, $idea, 'results_validated', ['notes' => $notes]);
            $this->notifyContributors($idea, 'Resultados validados', $notes, 'results_validated');
        });
    }

    public function publish(User $user, Idea $idea, array $data): void
    {
        DB::transaction(function () use ($user, $idea, $data) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($user)->authorize('evaluate', $idea);
            $this->requireStatus($idea, ['completed']);
            if (! $idea->result?->validated_at) {
                throw ValidationException::withMessages(['publication' => 'Resultados ainda não validados.']);
            }
            $idea->update(['publication' => [...$data, 'kind' => 'results', 'sectors' => $idea->beneficiarySectors->pluck('name')->all(), 'implementation_completed_at' => $idea->implementationPlan?->completed_at?->toIso8601String()], 'is_public' => true, 'published_by_user_id' => $user->id, 'published_at' => now()]);
            $this->audit($user, $idea, 'publication_reviewed');
            $this->notifyContributors($idea, 'Ideia publicada', 'A versão revisada dos resultados está disponível.', 'published');
        });
    }

    public function publishProposal(User $actor, Idea $idea, array $data): void
    {
        DB::transaction(function () use ($actor, $idea, $data) {
            $idea = $this->lockIdea($idea->id);
            Gate::forUser($actor)->authorize('evaluate', $idea);
            $this->requireStatus($idea, ['triage', 'evaluation', 'idea_bank', 'approved', 'implementation']);
            $idea->update(['publication' => [...$data, 'kind' => 'proposal', 'sectors' => $idea->beneficiarySectors->pluck('name')->all(), 'implementation_status' => ImplementationStatus::forIdea($idea)], 'is_public' => true, 'published_by_user_id' => $actor->id, 'published_at' => now()]);
            $this->audit($actor, $idea, 'proposal_privacy_reviewed');
            $this->notifyContributors($idea, 'Proposta revisada para votação', 'Sua ideia pode participar de um ciclo de seleção.', 'proposal_published');
        }, 3);
    }

    public function lockIdea(int $id): Idea
    {
        return $this->lockRecord(Idea::class, $id);
    }

    public function lockCycle(int $id): VotingCycle
    {
        return $this->lockRecord(VotingCycle::class, $id);
    }

    public function lockAward(int $id): BonusAward
    {
        return $this->lockRecord(BonusAward::class, $id);
    }

    /**
     * SQLite on PHP 8.3 starts deferred transactions and ignores FOR UPDATE.
     * Reserve its writer before reading to avoid concurrent read-to-write upgrades.
     * MySQL uses a row lock instead.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $modelClass
     * @return T
     */
    private function lockRecord(string $modelClass, int $id): Model
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table((new $modelClass)->getTable())->where('id', $id)->update(['id' => DB::raw('id')]);
        }

        return $modelClass::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function requireStatus(Idea $idea, array $statuses): void
    {
        if (! in_array($idea->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => 'Esta operação não é permitida na etapa atual.']);
        }
    }

    public function audit(User $user, Idea $idea, string $action, array $details = []): void
    {
        AuditLog::create(['causer_id' => $user->id, 'model_type' => Idea::class, 'model_id' => $idea->id, 'action' => $action, 'details' => $details]);
    }

    public function notifyContributors(Idea $idea, string $title, string $message, string $type): void
    {
        foreach (array_keys($idea->recognitionShares()) as $userId) {
            $this->notify((int) $userId, $title, $message, $type);
        }
    }

    public function notify(int $userId, string $title, string $message, string $type): void
    {
        Notification::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'type' => $type]);
    }
}
