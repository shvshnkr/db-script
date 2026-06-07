<?php
declare(strict_types=1);

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\Security\DenywordsGuard;
use PHPUnit\Framework\TestCase;

final class DenywordsGuardTest extends TestCase
{
    private string $tmpdir;

    protected function setUp(): void
    {
        $this->tmpdir = sys_get_temp_dir() . '/dbs-dw-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);

        (new TomlLoader())->writeFile($this->tmpdir . '/_conf/denywords.toml', [
            'words' => [
                'drop',
                ['word' => 'truncate', 'min_level' => 4],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    private function guard(): DenywordsGuard
    {
        return new DenywordsGuard(new ConfigRepository($this->tmpdir . '/_conf', new TomlLoader()));
    }

    public function testBlocksDropForLowLevelUser(): void
    {
        $this->assertSame('drop', $this->guard()->findBlocked('SELECT 1; DROP TABLE x', 0));
    }

    public function testBlocksBuiltinMysqlCatalog(): void
    {
        $this->assertSame('information_schema', $this->guard()->findBlocked('SELECT * FROM information_schema.tables', 10));
    }

    public function testLevelGatedWordAllowedForHighLevelUser(): void
    {
        $this->assertNull($this->guard()->findBlocked('TRUNCATE demo_items', 5));
    }

    public function testLevelGatedWordBlockedForLowLevelUser(): void
    {
        $this->assertSame('truncate', $this->guard()->findBlocked('TRUNCATE demo_items', 3));
    }

    public function testSuperUserBypass(): void
    {
        $guard = $this->guard();
        $guard->assertAllowed('DROP TABLE demo_items', 0, true);
        $this->assertTrue(true);
    }
}
