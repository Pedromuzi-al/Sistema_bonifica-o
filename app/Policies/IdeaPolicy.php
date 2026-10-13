<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;

class IdeaPolicy
{
    public function view(User $user, Idea $idea): bool
    {
        return $user->id === $idea->author_id
            || $idea->is_public
            || $this->canReview($user);
    }

    public function create(User $user): bool
    {
        return $user->hasSystemPermission('create_ideas') || $user->hasRole(['employee', 'admin', 'evaluator']);
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
        return $user->id !== (int) $idea->author_id && $user->hasSystemPermission('vote_ideas');
    }

    public function evaluate(User $user, Idea $idea): bool
    {
        return $this->canReview($user);
    }

    public function viewAuthorIdentity(User $user, Idea $idea): bool
    {
        return $user->id === $idea->author_id || $this->canReview($user);
    }

    public function approve(User $user, Idea $idea): bool
    {
        return $this->evaluate($user, $idea);
    }

    private function canReview(User $user): bool
    {
        return $user->hasRole(['admin', 'evaluator']) || $user->hasSystemPermission('evaluate_ideas');
    }
}
