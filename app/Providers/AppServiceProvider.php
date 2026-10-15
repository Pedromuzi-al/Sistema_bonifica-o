<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Idea;
use App\Models\User;
use App\Policies\IdeaPolicy;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
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
        VerifyEmail::toMailUsing(fn ($notifiable, string $url) => (new MailMessage)
            ->subject('Confirme seu e-mail na plataforma de ideias')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Confirme seu endereço de e-mail para acessar a plataforma de ideias.')
            ->action('Confirmar meu e-mail', $url)
            ->line('Se você não solicitou este cadastro, pode ignorar esta mensagem.'));
        Event::listen(Verified::class, function ($event) {
            AuditLog::create(['causer_id' => $event->user->id, 'model_type' => User::class, 'model_id' => $event->user->id, 'action' => 'email_verified']);
        });
        Gate::policy(Idea::class, IdeaPolicy::class);
        Gate::define('manage-admin', fn (User $user): bool => $user->hasAdministrativeAccess());
    }
}
