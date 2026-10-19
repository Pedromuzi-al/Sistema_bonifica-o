<?php

namespace App\Console\Commands;

use App\Services\LocalBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class CheckBackupRestore extends Command
{
    protected $signature = 'app:check-backup-restore {directory} {--database= : Banco vazio exclusivo com nome terminado em _test}';

    protected $description = 'Restaura uma cópia somente em banco de teste vazio e confere tabelas e documentos';

    public function handle(): int
    {
        if (config('database.default') === 'sqlite' && ! app()->isProduction()) {
            try {
                $path = app(LocalBackupService::class)->verify($this->argument('directory'), (string) $this->option('database'));
                $this->info('SQLite e documentos restaurados e conferidos em '.$path);

                return self::SUCCESS;
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Restauração local não concluída. Confira checksums e use um destino novo terminado em _test. A aplicação não foi alterada.');

                return self::FAILURE;
            }
        }
        $database = $this->option('database');
        $config = DB::connection()->getConfig();
        if (config('database.default') !== 'mysql' || ! is_string($database) || ! preg_match('/\A[a-zA-Z0-9_]+_test\z/', $database) || $database === $config['database']) {
            $this->error('Informe um banco MySQL exclusivo terminado em _test, diferente do banco da aplicação.');

            return self::FAILURE;
        }
        $directory = realpath($this->argument('directory'));
        if (! $directory || ! is_file($directory.'/manifest.json')) {
            $this->error('Manifesto de backup ausente.');

            return self::FAILURE;
        }
        $input = null;
        try {
            $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            foreach (['database.sql.gz', 'documents.tar.gz'] as $file) {
                if (! is_file($directory.'/'.$file) || ! hash_equals($manifest['sha256'][$file] ?? '', hash_file('sha256', $directory.'/'.$file))) {
                    throw new \RuntimeException('Checksum inválido.');
                }
            }
            if (($manifest['database'] ?? null) === $database) {
                throw new \RuntimeException('Destino igual à origem do backup.');
            }
            config(['database.connections.restore_check' => array_merge($config, ['database' => $database, 'url' => null])]);
            DB::purge('restore_check');
            $target = DB::connection('restore_check');
            if ($target->select('SHOW TABLES')) {
                $this->error('O banco de destino deve estar vazio. Nenhum dado foi alterado.');

                return self::FAILURE;
            }
            $input = gzopen($directory.'/database.sql.gz', 'rb');
            $arguments = ['mysql', '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'], $database];
            if (! empty($config['unix_socket'])) {
                array_splice($arguments, 1, 0, ['--socket='.$config['unix_socket']]);
            }
            $restore = new Process($arguments, base_path(), ['MYSQL_PWD' => (string) $config['password']]);
            $restore->setTimeout(3600);
            $restore->setInput($input);
            $restore->mustRun();
            fclose($input);
            $input = null;
            foreach ($manifest['tables'] as $table => $count) {
                if ($target->table($table)->count() !== $count) {
                    throw new \RuntimeException('Contagem de registros divergente.');
                }
            }
            $archive = new Process(['tar', '-tzf', $directory.'/documents.tar.gz']);
            $archive->mustRun();
            foreach (explode("\n", trim($archive->getOutput())) as $entry) {
                if (! str_starts_with($entry, 'private/') || str_contains($entry, '..') || str_starts_with($entry, '/')) {
                    throw new \RuntimeException('Caminho inválido no arquivo de documentos.');
                }
            }
            $this->info('Restauração MySQL conferida: todas as contagens coincidem. Arquivo de documentos íntegro. Banco de teste preservado para inspeção.');

            return self::SUCCESS;
        } catch (\Throwable) {
            if (is_resource($input)) {
                fclose($input);
            }
            $this->error('Falha na conferência. Verifique integridade, clientes mysql/tar, permissões e destino. O banco de teste pode conter importação parcial; a aplicação não foi alterada.');

            return self::FAILURE;
        } finally {
            DB::disconnect('restore_check');
        }
    }
}
