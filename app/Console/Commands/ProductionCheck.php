<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Verifica configuração e pré-requisitos de produção sem alterar o banco ou revelar credenciais';

    public function handle(): int
    {
        $mailer = config('mail.default');
        $checks = [
            'Ambiente production' => app()->isProduction(),
            'Depuração desativada' => ! config('app.debug'),
            'Chave de aplicação configurada' => filled(config('app.key')),
            'URL pública HTTPS' => str_starts_with((string) config('app.url'), 'https://') && ! str_contains((string) config('app.url'), '.example'),
            'Banco MySQL' => config('database.default') === 'mysql',
            'Usuário MySQL sem root' => config('database.connections.mysql.username') !== 'root',
            'Sessão no banco' => config('session.driver') === 'database',
            'Cookie seguro e HttpOnly' => config('session.secure') && config('session.http_only'),
            'Cadastro público desativado' => ! config('innovation.registration_enabled'),
            'Contato de privacidade definido' => filled(config('innovation.privacy_contact')),
            'E-mail SMTP configurado' => $mailer === 'smtp' && filled(config('mail.mailers.smtp.host')) && filled(config('mail.from.address')) && ! str_ends_with((string) config('mail.from.address'), '@example.com'),
            'Assets compilados, sem servidor Vite de desenvolvimento' => is_file(public_path('build/manifest.json')) && ! is_file(public_path('hot')),
            'Diretórios graváveis' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
        ];
        try {
            DB::connection()->getPdo();
            $checks['Conexão com banco'] = true;
            $migrator = app('migrator');
            $checks['Migrations atualizadas'] = $migrator->repositoryExists() && count(array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan())) === 0;
            $checks['Administrador ativo e verificado'] = User::whereNull('blocked_at')->whereNotNull('email_verified_at')->get()->contains(fn ($user) => $user->hasAdministrativeAccess());
            $checks['Contas demonstrativas ausentes'] = ! User::withTrashed()->whereIn('email', ['admin@corp.local', 'avaliador@corp.local', 'funcionario@corp.local'])->exists();
        } catch (\Throwable) {
            $checks['Banco e estrutura acessíveis'] = false;
        }
        foreach ($checks as $label => $ok) {
            $this->line(($ok ? '[OK] ' : '[PENDENTE] ').$label);
        }
        $this->line('Este comando não confirma DNS, certificado TLS, entrega de e-mail, backups, restauração ou acessibilidade. Valide esses itens no ambiente publicado.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
