<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class MysqlTestConfigurationTest extends TestCase
{
    #[TestWith(['ideias_corp', 'ideias_corp', false, false])]
    #[TestWith(['ideias_corp_test', 'ideias_corp_test', false, false])]
    #[TestWith(['ideias_corp_test', 'ideias_corp', true, false])]
    #[TestWith(['ideias_corp_test', 'ideias_corp', false, true])]
    public function test_mysql_profile_rejects_unsafe_database_configuration(string $testDatabase, string $applicationDatabase, bool $cached, bool $expectedSuccess): void
    {
        $root = tempnam(sys_get_temp_dir(), 'mysql-profile-');
        unlink($root);
        mkdir($root);
        mkdir($root.'/tests');
        mkdir($root.'/bootstrap');
        mkdir($root.'/bootstrap/cache');
        $project = dirname(__DIR__, 2);
        symlink($project.'/vendor', $root.'/vendor');
        copy($project.'/tests/mysql-bootstrap.php', $root.'/tests/mysql-bootstrap.php');
        file_put_contents($root.'/.env', 'DB_DATABASE='.$applicationDatabase);
        if ($cached) {
            file_put_contents($root.'/bootstrap/cache/config.php', '<?php return [];');
        }
        try {
            $process = new Process([PHP_BINARY, '-r', '$_ENV["DB_DATABASE"]=$argv[2]; require $argv[1]; echo "safe";', $root.'/tests/mysql-bootstrap.php', $testDatabase]);
            $process->run();
            $this->assertSame($expectedSuccess, $process->isSuccessful());
            if ($expectedSuccess) {
                $this->assertSame('safe', $process->getOutput());
            } else {
                $this->assertStringContainsString('RuntimeException', $process->getErrorOutput());
            }
        } finally {
            foreach ([$root.'/tests/mysql-bootstrap.php', $root.'/.env', $root.'/vendor', $root.'/bootstrap/cache/config.php'] as $path) {
                if (file_exists($path) || is_link($path)) {
                    unlink($path);
                }
            }
            rmdir($root.'/bootstrap/cache');
            rmdir($root.'/bootstrap');
            rmdir($root.'/tests');
            rmdir($root);
        }
    }
}
