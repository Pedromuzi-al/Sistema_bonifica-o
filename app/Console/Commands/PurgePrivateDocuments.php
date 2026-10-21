<?php

namespace App\Console\Commands;

use App\Services\PrivateDocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgePrivateDocuments extends Command
{
    protected $signature = 'app:purge-private-documents {--execute : Executa a exclusão dos documentos revisados com prazo vencido}';

    protected $description = 'Simula ou executa a exclusão de documentos com retenção individual aprovada, sem preservação obrigatória';

    public function handle(PrivateDocumentService $documents): int
    {
        $count = 0;
        $failed = false;
        $records = DB::table('idea_attachments')->whereNull('deleted_at')->where('legal_hold', false)->where(function ($query) {
            $query->where('review_status', 'deletion_pending')->orWhere(fn ($query) => $query->whereNotNull('reviewed_at')->where('retention_until', '<=', now()));
        });
        foreach ($records->lazyById(100) as $record) {
            if (! $this->option('execute')) {
                $count++;

                continue;
            }
            try {
                $count += (int) $documents->purge($record->id);
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Falha no documento '.$record->id.'. A exclusão continua pendente para nova tentativa.');
                $failed = true;
            }
        }
        $this->info($count.($this->option('execute') ? ' documentos excluídos.' : ' documentos elegíveis. Simulação: nenhum arquivo excluído.'));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
