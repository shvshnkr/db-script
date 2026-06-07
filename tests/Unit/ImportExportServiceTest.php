<?php
declare(strict_types=1);

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\DbdataRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\Service\ImportExportService;
use PHPUnit\Framework\TestCase;

final class ImportExportServiceTest extends TestCase
{
    private string $tmpdir;

    protected function setUp(): void
    {
        $this->tmpdir = sys_get_temp_dir() . '/dbs-ie-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);
        mkdir($this->tmpdir . '/_data', 0775, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpdir . '/_data/*') ?: []);
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        @rmdir($this->tmpdir . '/_data');
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    private function service(array $tables): ImportExportService
    {
        $toml = new TomlLoader();
        $toml->writeFile($this->tmpdir . '/_conf/dbdata.toml', ['tables' => $tables]);
        $config = new ConfigRepository($this->tmpdir . '/_conf', $toml);

        return new ImportExportService(
            new DbdataRepository($config),
            new \Dbscript\Database\ConnectionFactory($config),
            $this->tmpdir,
        );
    }

    public function testParseLegacyFdbWithPlevels(): void
    {
        $service = $this->service([]);
        $content = "ID¦TITLE\n0¦0\n1¦Hello\n2¦World";
        $parsed = $service->parseFdbContent($content);

        $this->assertSame(['ID', 'TITLE'], $parsed['headers']);
        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('Hello', $parsed['rows'][0]['TITLE']);
    }

    public function testParseSemicolonFormatSkipsPlevels(): void
    {
        $service = $this->service([]);
        $content = "id;title\n1;Alpha";
        $parsed = $service->parseFdbContent($content, true);

        $this->assertSame(['id', 'title'], $parsed['headers']);
        $this->assertSame('Alpha', $parsed['rows'][0]['title']);
    }

    public function testPreviewRejectsSameEngine(): void
    {
        $service = $this->service([
            ['id' => 1, 'visual_name' => 'A', 'engine' => 'mysql', 'mysql_table' => 'a'],
            ['id' => 2, 'visual_name' => 'B', 'engine' => 'mysql', 'mysql_table' => 'b'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $service->preview(1, 2);
    }

    public function testPreviewReturnsDirection(): void
    {
        $service = $this->service([
            ['id' => 1, 'visual_name' => 'Sql', 'engine' => 'mysql', 'mysql_table' => 'demo'],
            ['id' => 2, 'visual_name' => 'Csv', 'engine' => 'fdb', 'file_base' => 'demo.csv'],
        ]);

        $preview = $service->preview(1, 2);
        $this->assertSame('mysql_to_fdb', $preview['direction']);
        $this->assertSame('SQL → CSV', $preview['direction_label']);
    }

    public function testListTablesIncludesEngine(): void
    {
        $service = $this->service([
            ['id' => 1, 'visual_name' => 'Sql', 'engine' => 'mysql', 'mysql_table' => 'demo'],
            ['id' => 2, 'visual_name' => 'Csv', 'engine' => 'fdb', 'file_base' => 'demo.csv'],
        ]);

        $tables = $service->listTables();
        $this->assertSame('mysql', $tables[0]['engine']);
        $this->assertSame('fdb', $tables[1]['engine']);
    }
}
