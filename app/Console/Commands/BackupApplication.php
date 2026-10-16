<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class BackupApplication extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Cria cópia privada do MySQL e dos documentos; execute sem alterações concorrentes de arquivos';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Este backup exige MySQL.');

            return self::FAILURE;
        }
        if (app()->isProduction() && ! app()->isDownForMaintenance()) {
            $this->error('Ative a manutenção e suspenda gravações antes do backup de produção.');

            return self::FAILURE;
        }
        $directory = storage_path('app/backups/'.now()->format('Ymd-His').'-'.Str::random(8));
        if (! mkdir($directory, 0700, true)) {
            $this->error('Não foi possível criar o diretório de backup.');

            return self::FAILURE;
        }
        $gzip = null;
        try {
            $connection = DB::connection();
            $config = $connection->getConfig();
            $counts = [];
            foreach ($connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']) as $row) {
                $table = array_values((array) $row)[0];
                $counts[$table] = $connection->table($table)->count();
            }
            $gzip = gzopen($directory.'/database.sql.gz', 'wb9');
            if ($gzip === false) {
                throw new \RuntimeException('Falha ao abrir arquivo do backup.');
            }
            chmod($directory.'/database.sql.gz', 0600);
            $arguments = ['mysqldump', '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'], '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--hex-blob', $config['database']];
            if (! empty($config['unix_socket'])) {
                array_splice($arguments, 1, 0, ['--socket='.$config['unix_socket']]);
            }
            $dump = new Process($arguments, base_path(), ['MYSQL_PWD' => (string) $config['password']]);
            $dump->setTimeout(3600);
            $dump->run(function ($type, $buffer) use ($gzip) {
                if ($type === Process::OUT && gzwrite($gzip, $buffer) === false) {
                    throw new \RuntimeException('Falha ao gravar backup.');
                }
            });
            gzclose($gzip);
            $gzip = null;
            if (! $dump->isSuccessful()) {
                throw new \RuntimeException('mysqldump falhou; confira instalação, conexão e privilégios do usuário.');
            }
            $private = storage_path('app/private');
            if (! is_dir($private)) {
                mkdir($private, 0700, true);
            }
            $archive = new Process(['tar', '-czf', $directory.'/documents.tar.gz', '-C', storage_path('app'), 'private']);
            $archive->setTimeout(3600);
            $archive->mustRun();
            chmod($directory.'/documents.tar.gz', 0600);
            $manifest = ['created_at' => now()->toIso8601String(), 'database' => $config['database'], 'tables' => $counts, 'sha256' => ['database.sql.gz' => hash_file('sha256', $directory.'/database.sql.gz'), 'documents.tar.gz' => hash_file('sha256', $directory.'/documents.tar.gz')]];
            file_put_contents($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            chmod($directory.'/manifest.json', 0600);
            $this->info('Backup criado em '.$directory);
            $this->warn('Copie para armazenamento externo protegido e preserve APP_KEY separadamente. Valide a restauração antes de confiar nesta cópia.');

            return self::SUCCESS;
        } catch (\Throwable $error) {
            if (is_resource($gzip)) {
                gzclose($gzip);
            }
            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            } rmdir($directory);
            $this->error('Backup não concluído. Verifique espaço disponível, cliente mysqldump, tar e permissões. Nenhuma cópia parcial foi mantida.');

            return self::FAILURE;
        }
    }
}
