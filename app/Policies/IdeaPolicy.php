<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;

class IdeaPolicy
{
    public function view(User $user, Idea $idea): bool
    {
        return $idea->isContributor($user)
            || ($idea->is_public && $user->canViewPublicIdeas())
            || $this->canReview($user);
    }

    public function create(User $user): bool
    {
        return $user->hasSystemPermission('create_ideas') || $user->hasRole(['employee', 'admin', 'evaluator']);
    }

    public function update(User $user, Idea $idea): bool
    {
        return $idea->isContributor($user) || $user->hasRole('admin');
    }

    public function delete(User $user, Idea $idea): bool
    {
        return false;
    }

    public function vote(User $user, Idea $idea): bool
    {
        return $idea->is_public && $idea->published_at !== null && $idea->status === Idea::STATUS_VOTING
            && ! $idea->hasConflict($user) && $user->hasSystemPermission('vote_ideas');
    }

    public function evaluate(User $user, Idea $idea): bool
    {
        return $this->canReview($user) && (! $idea->exists || ! $idea->hasConflict($user));
    }

    public function viewAuthorIdentity(User $user, Idea $idea): bool
    {
        return (int) $user->id === (int) $idea->author_id || $user->hasSystemPermission('view_author_identity');
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
