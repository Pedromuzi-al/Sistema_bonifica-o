<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;

class IdeaPolicy
{
    public function view(User $user, Idea $idea): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_ideas') || $user->hasRole(['employee', 'admin', 'evaluator']);
    }

    public function update(User $user, Idea $idea): bool
    {
        return $user->id === $idea->author_id || $user->hasRole('admin');
    }

    public function delete(User $user, Idea $idea): bool
    {
        return $user->hasRole('admin');
    }

    public function vote(User $user, Idea $idea): bool
    {
        return $user->id !== (int) $idea->author_id && $user->hasPermissionTo('vote_ideas');
    }

    public function evaluate(User $user, Idea $idea): bool
    {
        return $user->hasRole(['admin', 'evaluator']) || $user->hasPermissionTo('evaluate_ideas');
    }

    public function viewAuthorIdentity(User $user, Idea $idea): bool
    {
        return $user->id === $idea->author_id || $user->hasRole(['admin', 'evaluator']);
    }

    public function approve(User $user, Idea $idea): bool
    {
        return $this->evaluate($user, $idea);
    }
}
