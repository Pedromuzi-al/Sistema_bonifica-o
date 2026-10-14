<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BonusAward;
use App\Models\BonusRule;
use App\Models\Idea;
use App\Models\Notification;
use App\Models\User;
use App\Models\VotingCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CycleService
{
    public function __construct(private IdeaScoringService $scoring, private IdeaWorkflowService $workflow) {}

    public function create(User $user, array $data): VotingCycle
    {
        Gate::forUser($user)->authorize('manage-admin');
        $this->scoring->validateWeights($data['weights']);
        if (abs(array_sum(array_column($data['criteria'], 'weight')) - 100) > 0.001) {
            throw ValidationException::withMessages(['criteria' => 'Os pesos dos critérios devem somar 100%.']);
        }
        $names = array_column($data['criteria'], 'name');
        $rubricKeys = array_map(fn ($rubric) => $rubric['indicator'].'|'.$rubric['unit'], $data['result_indicators']);
        if (count($names) !== count(array_unique($names)) || count($rubricKeys) !== count(array_unique($rubricKeys))) {
            throw ValidationException::withMessages(['criteria' => 'Não repita critérios ou indicadores.']);
        }

        return DB::transaction(function () use ($user, $data) {
            $cycle = VotingCycle::create(['name' => $data['name'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'status' => 'draft', 'weight_configuration' => ['weights' => $data['weights'], 'result_indicators' => $data['result_indicators'], 'minimum_votes' => $data['minimum_votes'], 'allow_abstention' => (bool) $data['allow_abstention'], 'category' => $data['category'], 'minimum_score' => $data['minimum_score'], 'reward_amount' => $data['reward_amount'], 'max_awards' => $data['max_awards'], 'tie_breakers' => ['real_results', 'technical', 'votes', 'idea_code'], 'anomaly_threshold' => 5]]);
            $cycle->criteria()->createMany($data['criteria']);
            $this->audit($user, $cycle, 'cycle_created');

            return $cycle;
        });
    }

    public function open(User $user, VotingCycle $cycle, array $ideaIds): void
    {
        Gate::forUser($user)->authorize('manage-admin');
        DB::transaction(function () use ($user, $cycle, $ideaIds) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            if ($cycle->status !== 'draft' || $cycle->frozen_configuration || now()->gte($cycle->starts_at) || ! $ideaIds) {
                throw ValidationException::withMessages(['cycle' => 'Abra um rascunho antes do início e selecione ideias elegíveis.']);
            }
            $config = $cycle->weight_configuration;
            if (empty($config['weights']) || empty($config['result_indicators'])) {
                throw ValidationException::withMessages(['cycle' => 'Este ciclo legado precisa ser substituído por um ciclo com regras completas.']);
            }
            $this->scoring->validateWeights($config['weights']);
            $criteria = $cycle->criteria()->get(['id', 'name', 'description', 'weight'])->toArray();
            if (! $criteria || abs(array_sum(array_column($criteria, 'weight')) - 100) > 0.001) {
                throw ValidationException::withMessages(['criteria' => 'Os critérios devem somar 100%.']);
            }
            $ideas = Idea::whereIn('id', $ideaIds)->orderBy('id')->lockForUpdate()->get();
            if ($ideas->count() !== count(array_unique($ideaIds))) {
                throw ValidationException::withMessages(['ideas' => 'Seleção de ideias inválida.']);
            }
            foreach ($ideas as $idea) {
                if (! $idea->is_public || ! $idea->published_at || ! $idea->result?->validated_at || ! in_array($idea->status, ['completed', 'voting_closed', 'finalized'], true)) {
                    throw ValidationException::withMessages(['ideas' => 'Selecione somente publicações revisadas com resultados validados.']);
                }
                $real = $this->scoring->resultsScore($idea->result->measurements ?? [], $config['result_indicators']);
                $technical = $idea->evaluations()->where('recommendation', 'approve')->latest('id')->value('technical_score');
                if ($real === null || $technical === null) {
                    throw ValidationException::withMessages(['ideas' => 'Cada ideia precisa de avaliação favorável e indicador compatível com a metodologia.']);
                }
                $cycle->ideas()->attach($idea->id, ['snapshot' => json_encode(['publication' => $idea->publication, 'real_results' => $real, 'technical' => (float) $technical], JSON_THROW_ON_ERROR)]);
                $this->workflow->transition($user, $idea, 'voting', 'Publicação selecionada para o ciclo '.$cycle->name, true);
                $this->workflow->notify($idea->author_id, 'Votação programada', 'Sua ideia participa do ciclo '.$cycle->name, 'voting_opened');
            }
            $cycle->update(['frozen_configuration' => [...$config, 'criteria' => $criteria], 'status' => 'open']);
            $this->announce($cycle, 'voting_opened', 'Votação programada: '.$cycle->name);
            $this->audit($user, $cycle, 'cycle_opened', ['idea_ids' => $ideaIds, 'configuration' => $cycle->frozen_configuration]);
        }, 3);
    }

    public function close(User $user, VotingCycle $cycle): void
    {
        Gate::forUser($user)->authorize('manage-admin');
        DB::transaction(function () use ($user, $cycle) {
            $cycle = $this->workflow->lockCycle($cycle->id);
            if ($cycle->status !== 'open' || ! $cycle->frozen_configuration || now()->lt($cycle->ends_at)) {
                throw ValidationException::withMessages(['cycle' => 'A apuração só pode ocorrer após o término de um ciclo aberto.']);
            }
            $config = $cycle->frozen_configuration;
            $criteria = collect($config['criteria'])->keyBy('id');
            $ranking = [];
            foreach ($cycle->ideas()->orderBy('ideas.id')->lockForUpdate()->get() as $idea) {
                $snapshot = json_decode($idea->pivot->snapshot, true, flags: JSON_THROW_ON_ERROR);
                $votes = $idea->votes()->where('voting_cycle_id', $cycle->id)->where('status', 'cast')->with('scores')->get();
                $weighted = [];
                $patterns = [];
                foreach ($votes as $vote) {
                    $sum = 0;
                    $weight = 0;
                    $vector = [];
                    foreach ($vote->scores as $score) {
                        $criterion = $criteria[$score->criterion_id];
                        $sum += (($score->score - 1) / 4 * 100) * $criterion['weight'];
                        $weight += $criterion['weight'];
                        $vector[$score->criterion_id] = $score->score;
                    }
                    if ($weight > 0) {
                        $weighted[] = $sum / $weight;
                    }
                    ksort($vector);
                    $key = json_encode($vector);
                    $patterns[$key] = ($patterns[$key] ?? 0) + 1;
                }
                $popular = $weighted ? round(array_sum($weighted) / count($weighted), 2) : null;
                $enough = count($weighted) >= $config['minimum_votes'];
                $final = $enough && $popular !== null ? $this->scoring->calculateWeightedScore($snapshot['real_results'], $snapshot['technical'], $popular, $config['weights']) : null;
                $outcome = ['real_results' => $snapshot['real_results'], 'technical' => $snapshot['technical'], 'votes' => $popular, 'vote_count' => count($weighted), 'final_score' => $final, 'eligible' => $enough && $final !== null && $final >= $config['minimum_score'], 'review_required' => $patterns && max($patterns) >= $config['anomaly_threshold']];
                $ranking[] = ['idea' => $idea, 'outcome' => $outcome];
            }
            usort($ranking, function ($a, $b) {
                foreach (['final_score', 'real_results', 'technical', 'votes'] as $field) {
                    $comparison = ($b['outcome'][$field] ?? -1) <=> ($a['outcome'][$field] ?? -1);
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return strcmp($a['idea']->idea_code, $b['idea']->idea_code);
            });
            $rule = BonusRule::create(['name' => $config['category'], 'minimum_score' => $config['minimum_score'], 'max_awards' => $config['max_awards'], 'reward_amount' => $config['reward_amount'], 'eligible_status' => 'voting_closed', 'criteria' => $config, 'active' => false]);
            $awarded = 0;
            foreach ($ranking as $position => $row) {
                $idea = $row['idea'];
                $outcome = [...$row['outcome'], 'rank' => $position + 1];
                $cycle->ideas()->updateExistingPivot($idea->id, ['outcome' => json_encode($outcome, JSON_THROW_ON_ERROR)]);
                if ($outcome['eligible'] && $awarded < $config['max_awards']) {
                    BonusAward::create(['idea_id' => $idea->id, 'voting_cycle_id' => $cycle->id, 'rule_id' => $rule->id, 'user_id' => $idea->author_id, 'score' => $outcome['final_score'], 'award_amount' => $config['reward_amount'], 'category' => $config['category'], 'justification' => 'Classificação '.$outcome['rank'].' segundo regras congeladas; aguardando aprovação.']);
                    $awarded++;
                }
                $this->workflow->transition($user, $idea, 'voting_closed', 'Apuração concluída no ciclo '.$cycle->name, true);
            }
            $cycle->update(['status' => 'closed', 'closed_at' => now()]);
            $this->announce($cycle, 'voting_closed', 'Resultados agregados disponíveis: '.$cycle->name);
            $this->audit($user, $cycle, 'cycle_closed');
        }, 3);
    }

    public function approveAward(User $user, BonusAward $award, string $notes): void
    {
        Gate::forUser($user)->authorize('manage-admin');
        DB::transaction(function () use ($user, $award, $notes) {
            $cycle = $this->workflow->lockCycle($award->voting_cycle_id);
            $award = $this->workflow->lockAward($award->id);
            $idea = $this->workflow->lockIdea($award->idea_id);
            if ($cycle->status !== 'closed' || $award->approved_at || $idea->hasConflict($user)) {
                throw ValidationException::withMessages(['award' => 'Aprovação indisponível, já realizada ou com conflito de interesse.']);
            }
            $award->update(['approved_by_user_id' => $user->id, 'approved_at' => now(), 'justification' => $notes]);
            $this->workflow->audit($user, $idea, 'award_approved', ['award_id' => $award->id, 'notes' => $notes]);
            $this->workflow->notify($award->user_id, 'Reconhecimento aprovado', 'Sua contribuição foi reconhecida. A divulgação depende da administração.', 'award_approved');
        });
    }

    public function publishAward(User $user, BonusAward $award): void
    {
        Gate::forUser($user)->authorize('manage-admin');
        DB::transaction(function () use ($user, $award) {
            $award = $this->workflow->lockAward($award->id);
            if (! $award->approved_at || $award->published_at || $award->idea->hasConflict($user)) {
                throw ValidationException::withMessages(['award' => 'A divulgação exige aprovação e ausência de conflito.']);
            }
            $award->update(['published_at' => now()]);
            if ($award->idea->status === 'voting_closed') {
                $this->workflow->transition($user, $award->idea, 'finalized', 'Reconhecimento aprovado e divulgado.', true);
            }
            $this->workflow->audit($user, $award->idea, 'award_published', ['award_id' => $award->id]);
            $this->workflow->notify($award->user_id, 'Reconhecimento divulgado', 'O reconhecimento da sua proposta foi divulgado sem identificação pública de autoria.', 'award_published');
        });
    }

    private function announce(VotingCycle $cycle, string $type, string $message): void
    {
        User::permission('vote_ideas')->select('users.id')->chunkById(200, function ($users) use ($cycle, $type, $message) {
            Notification::insert($users->map(fn ($user) => ['user_id' => $user->id, 'title' => 'Ciclo de votação', 'message' => $message, 'type' => $type, 'data' => json_encode(['cycle_id' => $cycle->id]), 'created_at' => now(), 'updated_at' => now()])->all());
        });
    }

    private function audit(User $user, VotingCycle $cycle, string $action, array $details = []): void
    {
        AuditLog::create(['causer_id' => $user->id, 'model_type' => VotingCycle::class, 'model_id' => $cycle->id, 'action' => $action, 'details' => $details]);
    }
}
