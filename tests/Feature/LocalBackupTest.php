<?php

namespace Tests\Feature;

use App\Services\LocalBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalBackupTest extends TestCase
{
    private function isolated(callable $check): void
    {
        $directory = sys_get_temp_dir().'/bonifica-backup-'.bin2hex(random_bytes(6));
        mkdir($directory.'/storage/app/private/evidence', 0700, true);
        $pdo = new \PDO('sqlite:'.$directory.'/source.sqlite');
        $pdo->exec('CREATE TABLE samples (id INTEGER PRIMARY KEY, label TEXT)');
        $pdo->exec("INSERT INTO samples (label) VALUES ('Registro restaurado')");
        file_put_contents($directory.'/storage/app/private/evidence/test.txt', 'Documento restaurado');
        $storage = storage_path();
        $connection = DB::getDefaultConnection();
        config(['database.connections.local_backup_test' => ['driver' => 'sqlite', 'database' => $directory.'/source.sqlite', 'prefix' => '']]);
        DB::setDefaultConnection('local_backup_test');
        $this->app->useStoragePath($directory.'/storage');
        try {
            $check(app(LocalBackupService::class));
        } finally {
            DB::purge('local_backup_test');
            DB::setDefaultConnection($connection);
            $this->app->useStoragePath($storage);
            File::deleteDirectory($directory);
        }
    }

    public function test_snapshot_restores_sqlite_and_actual_document_contents_without_overwriting_source(): void
    {
        $this->isolated(function (LocalBackupService $service) {
            $backup = $service->create();
            DB::table('samples')->insert(['label' => 'Registro posterior ao backup']);
            $target = $service->verify($backup, 'restoration_test');
            $restored = new \PDO('sqlite:'.$target.'/database.sqlite');
            $this->assertSame(1, (int) $restored->query('SELECT COUNT(*) FROM samples')->fetchColumn());
            $this->assertSame(2, DB::table('samples')->count());
            $this->assertSame('Documento restaurado', file_get_contents($target.'/private/evidence/test.txt'));
            $this->expectException(\RuntimeException::class);
            $service->verify($backup, 'restoration_test');
        });
    }

    public function test_corrupted_backup_is_rejected_before_creating_restore_target(): void
    {
        $this->isolated(function (LocalBackupService $service) {
            $backup = $service->create();
            file_put_contents($backup.'/database.sqlite', 'invalidado', FILE_APPEND);
            try {
                $service->verify($backup, 'corrupted_test');
                $this->fail('A cópia corrompida deve ser recusada.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Checksum inválido', $exception->getMessage());
                $this->assertDirectoryDoesNotExist(storage_path('app/restore-check/corrupted_test'));
            }
        });
    }
}
