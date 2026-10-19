<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class LocalBackupService
{
    public function create(): string
    {
        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() === ':memory:') {
            throw new \RuntimeException('A cópia local requer um arquivo SQLite.');
        }
        $directory = storage_path('app/backups/'.now()->format('Ymd-His').'-'.Str::random(8));
        mkdir($directory, 0700, true);
        DB::statement('VACUUM INTO ?', [$directory.'/database.sqlite']);
        chmod($directory.'/database.sqlite', 0600);
        $snapshot = new \PDO('sqlite:'.$directory.'/database.sqlite');
        $tables = $snapshot->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = (int) $snapshot->query('SELECT COUNT(*) FROM "'.str_replace('"', '""', $table).'"')->fetchColumn();
        }
        $private = storage_path('app/private');
        if (! is_dir($private)) {
            mkdir($private, 0700, true);
        }
        $hashes = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($private, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isLink()) {
                throw new \RuntimeException('Links simbólicos não são permitidos na cópia dos documentos.');
            }
            if ($file->isFile()) {
                $hashes[substr($file->getPathname(), strlen($private) + 1)] = hash_file('sha256', $file->getPathname());
            }
        }
        (new Process(['tar', '-czf', $directory.'/documents.tar.gz', '-C', storage_path('app'), 'private']))->mustRun();
        chmod($directory.'/documents.tar.gz', 0600);
        $manifest = ['driver' => 'sqlite', 'created_at' => now()->toIso8601String(), 'tables' => $counts, 'documents' => $hashes, 'sha256' => ['database.sqlite' => hash_file('sha256', $directory.'/database.sqlite'), 'documents.tar.gz' => hash_file('sha256', $directory.'/documents.tar.gz')]];
        file_put_contents($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($directory.'/manifest.json', 0600);

        return $directory;
    }

    public function verify(string $directory, string $name): string
    {
        if (! preg_match('/\A[a-zA-Z0-9_]+_test\z/', $name)) {
            throw new \RuntimeException('Use um destino exclusivo com nome terminado em _test.');
        }
        $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['driver'] ?? null) !== 'sqlite') {
            throw new \RuntimeException('A cópia não é SQLite.');
        }
        foreach (['database.sqlite', 'documents.tar.gz'] as $file) {
            if (! hash_equals($manifest['sha256'][$file] ?? '', hash_file('sha256', $directory.'/'.$file))) {
                throw new \RuntimeException('Checksum inválido no backup.');
            }
        }
        $listing = new Process(['tar', '-tzf', $directory.'/documents.tar.gz']);
        $listing->mustRun();
        foreach (explode("\n", trim($listing->getOutput())) as $entry) {
            if (! str_starts_with($entry, 'private/') || str_contains($entry, '..') || str_contains($entry, '\\')) {
                throw new \RuntimeException('Caminho não permitido no backup.');
            }
        }
        $types = new Process(['tar', '-tvzf', $directory.'/documents.tar.gz']);
        $types->mustRun();
        foreach (explode("\n", trim($types->getOutput())) as $entry) {
            if (! in_array(substr($entry, 0, 1), ['-', 'd'], true)) {
                throw new \RuntimeException('A cópia contém um tipo de arquivo não permitido.');
            }
        }
        $target = storage_path('app/restore-check/'.$name);
        if (file_exists($target)) {
            throw new \RuntimeException('Destino já existe; use outro nome. Nenhum dado foi sobrescrito.');
        }
        mkdir($target, 0700, true);
        copy($directory.'/database.sqlite', $target.'/database.sqlite');
        chmod($target.'/database.sqlite', 0600);
        $restored = new \PDO('sqlite:'.$target.'/database.sqlite');
        if ($restored->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new \RuntimeException('Integridade SQLite inválida.');
        }
        foreach ($manifest['tables'] as $table => $count) {
            if ((int) $restored->query('SELECT COUNT(*) FROM "'.str_replace('"', '""', $table).'"')->fetchColumn() !== $count) {
                throw new \RuntimeException('Contagem divergente na restauração.');
            }
        }
        (new Process(['tar', '-xzf', $directory.'/documents.tar.gz', '--no-same-owner', '--no-same-permissions', '-C', $target]))->mustRun();
        foreach ($manifest['documents'] as $file => $hash) {
            if (str_contains($file, '..') || str_starts_with($file, '/') || ! hash_equals($hash, hash_file('sha256', $target.'/private/'.$file))) {
                throw new \RuntimeException('Documento restaurado diverge da cópia original.');
            }
        }

        return $target;
    }
}
