<?php
declare(strict_types=1);

namespace Dbscript\Tests\Unit;

use Dbscript\Auth\JwtAuthService;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\Config\UserRepository;
use Dbscript\Install\InstallWriter;
use PHPUnit\Framework\TestCase;

final class JwtAuthServiceTest extends TestCase
{
    private string $tmpdir;

    protected function setUp(): void
    {
        $this->tmpdir = sys_get_temp_dir() . '/dbs-arch-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);

        $toml = new TomlLoader();
        $config = new ConfigRepository($this->tmpdir . '/_conf', $toml);
        $users = new UserRepository($config);
        (new InstallWriter($config, $users))->writeFreshInstall('admin', 'secret-pass', '127.0.0.1');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    public function testAuthenticateAndValidate(): void
    {
        $toml = new TomlLoader();
        $config = new ConfigRepository($this->tmpdir . '/_conf', $toml);
        $users = new UserRepository($config);
        $auth = new JwtAuthService($config, $users);

        $token = $auth->authenticate('admin', 'secret-pass');
        $this->assertNotNull($token);

        $claims = $auth->validateToken($token->toString());
        $this->assertSame('admin', $claims['login'] ?? null);
        $this->assertSame('admin', $claims['role'] ?? null);

        $this->assertNull($auth->authenticate('admin', 'wrong'));
    }
}
