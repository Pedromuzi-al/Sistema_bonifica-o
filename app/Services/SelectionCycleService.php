<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BonusAward;
use App\Models\BonusRule;
use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\Notification;
use App\Models\User;
use App\Models\VotingCycle;
use App\Support\ImplementationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SelectionCycleService
{
    public const ELIGIBLE_STATUSES = ['triage', 'evaluation', 'idea_bank', 'approved', 'implementation', 'validation', 'completed', 'finalized'];

    public function __construct(private IdeaWorkflowService $workflow) {}

    public function create(User $actor, array $data): VotingCycle
    {
        Gate::forUser($actor)->authorize('manage-admin');
        $criteria = $data['criteria'] ?? [
            ['name' => 'Utilidade', 'description' => 'Quanto esta ideia pode ajudar no trabalho?', 'weight' => 34],
            ['name' => 'Relevância', 'description' => 'Qual a importância do problema que esta ideia resolve?', 'weight' => 33],
            ['name' => 'Benefício esperado', 'description' => 'Qual o potencial de melhoria apresentado pela proposta?', 'weight' => 33],
        ];
        if (! $criteria || abs(array_sum(array_column($criteria, 'weight')) - 100) > 0.001 || count(array_unique(array_column($criteria, 'name'))) !== count($criteria)) {
            throw ValidationException::withMessages(['criteria' => 'Use critérios distintos com pesos que somem 100%.']);
        }

        return DB::transaction(function () use ($actor, $data, $criteria) {
            $config = ['purpose' => 'selection', 'minimum_votes' => (int) ($data['minimum_votes'] ?? 1), 'allow_abstention' => (bool) ($data['allow_abstention'] ?? false), 'max_selections' => (int) ($data['max_selections'] ?? 5), 'minimum_score' => (float) ($data['minimum_score'] ?? 0), 'reward_amount' => (float) ($data['reward_amount'] ?? 0), 'category' => 'Ideia selecionada', 'anomaly_threshold' => (int) ($data['anomaly_threshold'] ?? 5)];
            $cycle = VotingCycle::create(['name' => $data['name'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'status' => 'draft', 'weight_configuration' => $config]);
            $cycle->criteria()->createMany($criteria);
            $this->audit($actor, $cycle, 'selection_cycle_created');

            return $cycle;
        });
    }

    public function open(User $actor, VotingCycle $cycle, array $ids): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $cycle, $ids) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            if (! $cycle->isSelection() || $cycle->status !== 'draft' || $cycle->frozen_configuration || now()->gte($cycle->starts_at) || ! $ids || count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['cycle' => 'Abra um rascunho antes do início e escolha propostas revisadas.']);
            }
            $ideas = Idea::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($ideas->count() !== count($ids)) {
                throw ValidationException::withMessages(['ideas' => 'Seleção inválida.']);
            }
            foreach ($ideas as $idea) {
                if (! $idea->is_public || ! $idea->published_at || ! $idea->publication || ! in_array($idea->status, self::ELIGIBLE_STATUSES, true)) {
                    throw ValidationException::withMessages(['ideas' => $idea->idea_code.': conclua a triagem e revise a publicação antes de incluir no ciclo.']);
                }
                if ($reason = $this->participationBlocker($idea)) {
                    throw ValidationException::withMessages(['ideas' => $idea->idea_code.': '.$reason]);
                }
                $cycle->ideas()->attach($idea->id, ['snapshot' => json_encode(['publication' => $idea->publication, 'implementation_status' => ImplementationStatus::forIdea($idea), 'contributors' => $idea->recognitionShares()], JSON_THROW_ON_ERROR)]);
                $this->workflow->notifyContributors($idea, 'Ideia em votação', 'Sua proposta participa do ciclo de seleção '.$cycle->name.'.', 'selection_opened');
            }
            $criteria = $cycle->criteria()->get(['id', 'name', 'description', 'weight'])->toArray();
            if (! $criteria || abs(array_sum(array_column($criteria, 'weight')) - 100) > 0.001) {
                throw ValidationException::withMessages(['criteria' => 'Os critérios precisam somar 100%.']);
            }
            $cycle->update(['status' => 'open', 'frozen_configuration' => [...$cycle->weight_configuration, 'criteria' => $criteria]]);
            $this->audit($actor, $cycle, 'selection_cycle_opened', ['idea_ids' => $ids]);
            User::permission('vote_ideas')->whereNull('blocked_at')->select('users.id')->chunkById(200, function ($users) use ($cycle) {
                Notification::insert($users->map(fn ($user) => ['user_id' => $user->id, 'title' => 'Ciclo de seleção de ideias', 'message' => 'Avalie o potencial das propostas: '.$cycle->name, 'type' => 'selection_opened', 'data' => json_encode(['cycle_id' => $cycle->id]), 'created_at' => now(), 'updated_at' => now()])->all());
            });
        }, 3);
    }

    public function participationBlocker(Idea $idea): ?string
    {
        $previous = DB::table('idea_voting_cycle')->join('voting_cycles', 'voting_cycles.id', '=', 'idea_voting_cycle.voting_cycle_id')->where('idea_id', $idea->id)->where('voting_cycles.status', 'open')->exists();
        $pending = DB::table('idea_voting_cycle')->join('voting_cycles', 'voting_cycles.id', '=', 'idea_voting_cycle.voting_cycle_id')->where('idea_id', $idea->id)->where('voting_cycles.status', 'closed')->where('frozen_configuration->purpose', 'selection')->whereNotExists(fn ($query) => $query->selectRaw('1')->from('cycle_idea_decisions')->whereColumn('cycle_idea_decisions.voting_cycle_id', 'voting_cycles.id')->whereColumn('cycle_idea_decisions.idea_id', 'idea_voting_cycle.idea_id'))->exists();
        if ($previous) {
            return 'Esta ideia já participa de uma votação aberta ou programada. Conclua esse ciclo primeiro.';
        }
        if ($pending) {
            return 'Esta ideia aguarda a decisão da equipe no ciclo anterior. Registre a decisão antes de incluí-la novamente.';
        }

        return null;
    }

    public function close(User $actor, VotingCycle $cycle): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $cycle) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            if (! $cycle->isSelection() || $cycle->status !== 'open' || ! $cycle->frozen_configuration || now()->lt($cycle->ends_at)) {
                throw ValidationException::withMessages(['cycle' => 'A apuração acontece após o fim da votação.']);
            }
            $config = $cycle->frozen_configuration;
            $criteria = collect($config['criteria'])->keyBy('id');
            $ranking = [];
            foreach ($cycle->ideas()->orderBy('ideas.id')->lockForUpdate()->get() as $idea) {
                $scores = [];
                $patterns = [];
                foreach ($idea->votes()->where('voting_cycle_id', $cycle->id)->where('status', 'cast')->with('scores')->get() as $vote) {
                    $weight = 0;
                    $total = 0;
                    $vector = [];
                    foreach ($vote->scores as $score) {
                        $criterion = $criteria->get($score->criterion_id);
                        if (! $criterion) {
                            throw ValidationException::withMessages(['cycle' => 'Há notas incompatíveis com os critérios congelados. Solicite revisão.']);
                        }
                        $weight += $criterion['weight'];
                        $total += ($score->score - 1) / 4 * 100 * $criterion['weight'];
                        $vector[$score->criterion_id] = $score->score;
                    }
                    if ($weight > 0) {
                        $scores[] = $total / $weight;
                        ksort($vector);
                        $key = json_encode($vector);
                        $patterns[$key] = ($patterns[$key] ?? 0) + 1;
                    }
                }
                $enough = count($scores) >= $config['minimum_votes'];
                $average = $scores ? round(array_sum($scores) / count($scores), 2) : null;
                $ranking[] = ['idea' => $idea, 'outcome' => ['votes' => $average, 'vote_count' => count($scores), 'final_score' => $enough ? $average : null, 'eligible' => $enough && $average !== null && $average >= $config['minimum_score'], 'review_required' => $patterns && max($patterns) >= $config['anomaly_threshold']]];
            }
            usort($ranking, fn ($a, $b) => (($b['outcome']['final_score'] ?? -1) <=> ($a['outcome']['final_score'] ?? -1)) ?: strcmp($a['idea']->idea_code, $b['idea']->idea_code));
            foreach ($ranking as $index => $row) {
                $cycle->ideas()->updateExistingPivot($row['idea']->id, ['outcome' => json_encode([...$row['outcome'], 'rank' => $index + 1], JSON_THROW_ON_ERROR)]);
                $this->workflow->notifyContributors($row['idea'], 'Votação apurada', 'A equipe avaliará a viabilidade e os recursos para decidir os próximos passos.', 'selection_closed');
            }
            $cycle->update(['status' => 'closed', 'closed_at' => now()]);
            $this->audit($actor, $cycle, 'selection_cycle_closed');
        }, 3);
    }

    public function decide(User $actor, VotingCycle $cycle, Idea $idea, string $decision, string $notes): void
    {
        Gate::forUser($actor)->authorize('manage-admin');
        DB::transaction(function () use ($actor, $cycle, $idea, $decision, $notes) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            $idea = $this->workflow->lockIdea($idea->id);
            $entry = $cycle->ideas()->where('ideas.id', $idea->id)->firstOrFail();
            if (! $cycle->isSelection() || $cycle->status !== 'closed' || $idea->hasConflict($actor) || ! in_array($decision, ['selected', 'bank'], true) || strlen(trim($notes)) < 10 || DB::table('cycle_idea_decisions')->where('voting_cycle_id', $cycle->id)->where('idea_id', $idea->id)->exists()) {
                throw ValidationException::withMessages(['decision' => 'Decisão indisponível, já registrada ou com conflito de interesse.']);
            }
            $outcome = json_decode($entry->pivot->outcome, true, flags: JSON_THROW_ON_ERROR);
            if ($decision === 'selected') {
                if (! $outcome['eligible'] || ! in_array($idea->status, self::ELIGIBLE_STATUSES, true) || $idea->evaluations()->latest('id')->value('recommendation') === 'reject') {
                    throw ValidationException::withMessages(['decision' => 'Confira votos mínimos, situação e viabilidade da proposta antes de selecionar.']);
                }
                if (($outcome['review_required'] ?? false) && ! DB::table('cycle_reviews')->where('voting_cycle_id', $cycle->id)->where('idea_id', $idea->id)->where('decision', 'validated')->exists()) {
                    throw ValidationException::withMessages(['decision' => 'Revise o alerta da votação antes de selecionar esta ideia.']);
                }
                if (DB::table('cycle_idea_decisions')->where('voting_cycle_id', $cycle->id)->where('decision', 'selected')->count() >= $cycle->frozen_configuration['max_selections']) {
                    throw ValidationException::withMessages(['decision' => 'O limite de ideias selecionadas deste ciclo foi atingido.']);
                }
                if (in_array($idea->status, ['triage', 'evaluation', 'idea_bank'], true)) {
                    IdeaStatusHistory::record($idea, 'approved', $actor, 'Selecionada após votação: '.$notes);
                }
                if ($cycle->frozen_configuration['reward_amount'] > 0) {
                    $this->proposeIdeaAward($actor, $cycle, $idea, $entry, $outcome);
                }
            } elseif (in_array($idea->status, ['triage', 'evaluation', 'idea_bank'], true) && $idea->status !== 'idea_bank') {
                IdeaStatusHistory::record($idea, 'idea_bank', $actor, 'Mantida para futuras oportunidades: '.$notes);
            }
            DB::table('cycle_idea_decisions')->insert(['voting_cycle_id' => $cycle->id, 'idea_id' => $idea->id, 'decided_by' => $actor->id, 'decision' => $decision, 'notes' => $notes, 'created_at' => now(), 'updated_at' => now()]);
            $this->workflow->audit($actor, $idea, 'selection_decision', ['cycle_id' => $cycle->id, 'decision' => $decision, 'notes' => $notes]);
            $this->workflow->notifyContributors($idea, $decision === 'selected' ? 'Ideia selecionada' : 'Ideia no banco de oportunidades', $notes, 'selection_decision');
        }, 3);
    }

    private function proposeIdeaAward(User $actor, VotingCycle $cycle, Idea $idea, Idea $entry, array $outcome): void
    {
        $config = $cycle->frozen_configuration;
        $rule = BonusRule::create(['name' => 'Ideia selecionada', 'minimum_score' => $config['minimum_score'], 'max_awards' => $config['max_selections'], 'reward_amount' => $config['reward_amount'], 'eligible_status' => 'approved', 'criteria' => $config, 'active' => false]);
        $award = BonusAward::create(['idea_id' => $idea->id, 'voting_cycle_id' => $cycle->id, 'rule_id' => $rule->id, 'user_id' => $idea->author_id, 'score' => $outcome['final_score'], 'award_amount' => $config['reward_amount'], 'category' => 'Ideia selecionada', 'justification' => 'Proposta selecionada pela equipe; reconhecimento da ideia aguardando aprovação.']);
        $snapshot = json_decode($entry->pivot->snapshot, true, flags: JSON_THROW_ON_ERROR);
        $shares = $snapshot['contributors'];
        $total = (int) round($config['reward_amount'] * 100);
        $remaining = $total;
        $last = array_key_last($shares);
        foreach ($shares as $user => $share) {
            $cents = $user === $last ? $remaining : (int) floor($total * $share / 100);
            $remaining -= $cents;
            DB::table('award_allocations')->insert(['bonus_award_id' => $award->id, 'user_id' => $user, 'amount' => $cents / 100]);
        }
        $this->workflow->audit($actor, $idea, 'idea_award_proposed', ['award_id' => $award->id, 'cycle_id' => $cycle->id]);
    }

    private function audit(User $actor, VotingCycle $cycle, string $action, array $details = []): void
    {
        AuditLog::create(['causer_id' => $actor->id, 'model_type' => VotingCycle::class, 'model_id' => $cycle->id, 'action' => $action, 'details' => $details]);
    }
}
