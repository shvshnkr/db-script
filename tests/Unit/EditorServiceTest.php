<?php
declare(strict_types=1);

use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Service\EditorService;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use PHPUnit\Framework\TestCase;

final class EditorServiceTest extends TestCase
{
    private string $tmpdir;

    protected function setUp(): void
    {
        $this->tmpdir = sys_get_temp_dir() . '/dbs-ed-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);

        $toml = new TomlLoader();
        $toml->writeFile($this->tmpdir . '/_conf/dbdata.toml', [
            'tables' => [[
                'id' => 1,
                'visual_name' => 'Demo',
                'mysql_table' => 'demo_items',
                'mysql_host' => '127.0.0.1',
                'mysql_database' => 'demo',
                'engine' => 'mysql',
            ]],
        ]);
        $toml->writeFile($this->tmpdir . '/_conf/sitedata.toml', [
            'mysql' => [
                'host' => '127.0.0.1',
                'login' => 'root',
                'password' => '',
                'database' => 'demo',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    public function testListTablesFromToml(): void
    {
        $config = new ConfigRepository($this->tmpdir . '/_conf', new TomlLoader());
        $editor = new EditorService(new DbdataRepository($config), new ConnectionFactory($config));
        $tables = $editor->listTables();
        $this->assertCount(1, $tables);
        $this->assertSame('Demo', $tables[0]['visual_name']);
    }
}
