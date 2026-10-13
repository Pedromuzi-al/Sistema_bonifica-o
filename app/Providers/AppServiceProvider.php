<?php

namespace App\Providers;

use App\Models\Idea;
use App\Models\User;
use App\Policies\IdeaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Idea::class, IdeaPolicy::class);
        Gate::define('manage-admin', fn (User $user): bool => $user->hasRole('admin') || $user->hasSystemPermission('manage_admin'));
    }
}
