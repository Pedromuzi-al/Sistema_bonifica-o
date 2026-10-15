<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use App\Models\VotingCycle;

class VoteAvailabilityService
{
    public function forCycle(User $user, Idea $idea, VotingCycle $cycle): array
    {
        $vote = $idea->votes()->where('voting_cycle_id', $cycle->id)->where('user_id', $user->id)->first();
        if ($vote) {
            return $this->unavailable($vote->status === 'cast' ? 'Seu voto já foi registrado. Obrigado por participar!' : 'Seu voto passou por uma revisão administrativa. Consulte suas notificações.', 'registered');
        }
        if ((int) $idea->author_id === (int) $user->id) {
            return $this->unavailable('Esta ideia é sua. Você pode acompanhar a votação, mas não votar nela.', 'own');
        }
        if ($idea->hasConflict($user)) {
            return $this->unavailable('Você declarou um conflito de interesse e não pode avaliar esta ideia.', 'conflict');
        }
        if (! $user->hasSystemPermission('vote_ideas')) {
            return $this->unavailable('Seu acesso ainda não permite votar. Fale com o administrador.', 'permission');
        }
        if ($cycle->status !== 'open' || now()->gte($cycle->ends_at)) {
            return $this->unavailable('Esta votação foi encerrada. Você pode consultar os resultados quando a apuração terminar.', 'closed');
        }
        if (now()->lt($cycle->starts_at)) {
            return $this->unavailable('Você poderá votar a partir de '.$cycle->starts_at->format('d/m/Y H:i').'.', 'scheduled');
        }
        if (! $cycle->frozen_configuration || ! $user->can('vote', $idea)) {
            return $this->unavailable('Esta ideia ainda não está disponível para receber votos.', 'unavailable');
        }

        return ['canVote' => true, 'message' => 'Leia o que mudou e escolha uma resposta para cada pergunta.', 'state' => 'available'];
    }

    private function unavailable(string $message, string $state): array
    {
        return ['canVote' => false, 'message' => $message, 'state' => $state];
    }
}
