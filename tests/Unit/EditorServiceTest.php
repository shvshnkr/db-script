<?php
declare(strict_types=1);

use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Service\EditorService;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\Security\DenywordsGuard;
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

    private function editor(): EditorService
    {
        $config = new ConfigRepository($this->tmpdir . '/_conf', new TomlLoader());

        return new EditorService(
            new DbdataRepository($config),
            new ConnectionFactory($config),
            new DenywordsGuard($config),
        );
    }

    public function testListTablesFromToml(): void
    {
        $tables = $this->editor()->listTables();
        $this->assertCount(1, $tables);
        $this->assertSame('Demo', $tables[0]['visual_name']);
    }

    public function testUnknownTableThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->editor()->getRow('999', '1');
    }

    public function testNonMysqlEngineThrows(): void
    {
        $toml = new TomlLoader();
        $toml->writeFile($this->tmpdir . '/_conf/dbdata.toml', [
            'tables' => [[
                'id' => 2,
                'visual_name' => 'Csv table',
                'engine' => 'csv',
            ]],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->editor()->listRows('2');
    }

    public function testDeleteRowsEmptyReturnsZero(): void
    {
        $this->assertSame(0, $this->editor()->deleteRows('1', []));
    }
}
