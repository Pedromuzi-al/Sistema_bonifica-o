<?php

use App\Models\Notification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:prune-read-notifications', function () {
    $days = (int) config('innovation.retention_days');
    if ($days <= 0) {
        $this->info('Retenção automática desabilitada. Defina o prazo aprovado pela empresa.');

        return;
    }
    $count = Notification::whereNotNull('read_at')->where('read_at', '<', now()->subDays($days))->delete();
    $this->info($count.' notificações lidas removidas. Propostas, votos e auditoria foram preservados.');
})->purpose('Aplica o prazo configurado somente às notificações já lidas');
Schedule::command('app:prune-read-notifications')->daily()->withoutOverlapping();

Schedule::command('app:purge-private-documents --execute')->daily()->withoutOverlapping();
