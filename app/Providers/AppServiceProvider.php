<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Idea;
use App\Models\User;
use App\Policies\IdeaPolicy;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
<<<<<<< HEAD
        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

            return (new MailMessage)
                ->subject($user->invitation_pending ? 'Seu convite para a plataforma de ideias' : 'Redefina sua senha')
                ->greeting('Olá, '.$user->name.'!')
                ->line($user->invitation_pending ? 'Defina sua senha para concluir seu primeiro acesso. Este link também confirma seu e-mail.' : 'Recebemos uma solicitação para redefinir sua senha.')
                ->action($user->invitation_pending ? 'Definir minha senha' : 'Redefinir senha', $url)
                ->line('O link expira em '.config('auth.passwords.users.expire').' minutos. Se necessário, solicite outro em Esqueci minha senha.');
        });
=======
        DB::prohibitDestructiveCommands($this->app->isProduction());
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->numbers()
            : Password::min(8));
>>>>>>> 787622d (admfinaliza)
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
