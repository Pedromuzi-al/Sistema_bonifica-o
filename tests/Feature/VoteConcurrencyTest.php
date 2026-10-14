<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\Configuration\Registry;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class VoteConcurrencyTest extends TestCase
{
    public function test_two_processes_cannot_register_the_same_vote(): void
    {
        $driver = config('database.default');
        $isMysql = $driver === 'mysql';
        $temporaryFile = tempnam(sys_get_temp_dir(), 'innovation-concurrency-');
        $database = $isMysql ? config('database.connections.mysql.database') : $temporaryFile;
        $barrier = $temporaryFile.'-start';
        $processes = [];
        try {
            if ($isMysql) {
                $this->assertStringEndsWith('_test', $database);
                $this->assertSame(realpath(base_path('tests/mysql-bootstrap.php')), realpath(Registry::get()->bootstrap()));
                Artisan::call('migrate:fresh', ['--database' => 'mysql', '--force' => true]);
            } else {
                $driver = 'sqlite';
                config(['database.default' => $driver, 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null, 'database.connections.sqlite.transaction_mode' => 'IMMEDIATE']);
                DB::purge($driver);
                Artisan::call('migrate', ['--database' => $driver, '--force' => true]);
            }
            $this->seed(DatabaseSeeder::class);
            $author = User::where('email', 'funcionario@corp.local')->firstOrFail();
            $voter = User::factory()->create();
            $voter->assignRole('employee');
            $idea = Idea::create(['author_id' => $author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Teste concorrente', 'problem_description' => 'Problema', 'solution_description' => 'Solução', 'expected_results' => 'Estimativa', 'resources_needed' => 'Recursos', 'status' => 'voting', 'is_public' => true, 'published_at' => now()]);
            $cycle = VotingCycle::create(['name' => 'Concorrência', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour(), 'status' => 'open']);
            $criterion = $cycle->criteria()->create(['name' => 'Impacto', 'description' => 'Impacto medido', 'weight' => 100]);
            $cycle->ideas()->attach($idea->id, ['snapshot' => '{}']);
            $cycle->update(['frozen_configuration' => ['criteria' => [$criterion->toArray()], 'allow_abstention' => false]]);
            $worker = <<<'WORKER'
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$driver = $argv[9];
config(['database.default' => $driver, 'database.connections.'.$driver.'.database' => $argv[2], 'database.connections.'.$driver.'.url' => null, 'database.connections.sqlite.transaction_mode' => 'IMMEDIATE', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge($driver);
file_put_contents($argv[3].'-ready-'.$argv[4], 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($argv[3])) {
    if (microtime(true) > $deadline) { throw new RuntimeException('Barreira indisponível.'); }
    usleep(10000);
}
try {
    $app->make(App\Services\VotingService::class)->cast(App\Models\User::findOrFail($argv[5]), App\Models\Idea::findOrFail($argv[6]), ['voting_cycle_id' => (int) $argv[7], 'scores' => [(int) $argv[8] => 5]]);
    echo 'accepted';
} catch (Illuminate\Validation\ValidationException $exception) {
    if (! array_key_exists('vote', $exception->errors())) { throw $exception; }
    echo 'duplicate';
}
WORKER;
            for ($index = 0; $index < 2; $index++) {
                $process = new Process([PHP_BINARY, '-r', $worker, base_path(), $database, $barrier, (string) $index, (string) $voter->id, (string) $idea->id, (string) $cycle->id, (string) $criterion->id, $driver]);
                if ($isMysql) {
                    $connection = config('database.connections.mysql');
                    $process->setEnv(['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database, 'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'], 'DB_SOCKET' => $connection['unix_socket'], 'DB_URL' => '']);
                }
                $process->setTimeout(20)->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (! file_exists($barrier.'-ready-0') || ! file_exists($barrier.'-ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Os processos não chegaram à barreira de concorrência.');
                }
                usleep(10000);
            }
            file_put_contents($barrier, 'start');
            $outputs = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $outputs[] = $process->getOutput();
            }
            sort($outputs);
            $this->assertSame(['accepted', 'duplicate'], $outputs);
            $this->assertSame(1, $idea->votes()->count());
            $this->assertSame(1, DB::table('vote_scores')->count());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::purge($driver);
            foreach ([$temporaryFile, $temporaryFile.'-journal', $temporaryFile.'-wal', $temporaryFile.'-shm', $barrier, $barrier.'-ready-0', $barrier.'-ready-1'] as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }
}
