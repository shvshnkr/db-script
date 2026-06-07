<?php
declare(strict_types=1);

use Dbscript\Application;
use Dbscript\Http\Api\ApiKernel;
use Dbscript\Install\InstallWriter;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\Config\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

final class EditorApiTest extends TestCase
{
    private string $tmpdir;

    protected function setUp(): void
    {
        Application::resetForTests();
        $this->tmpdir = sys_get_temp_dir() . '/dbs-spa-ed-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $toml = new TomlLoader();
        $config = new ConfigRepository($this->tmpdir . '/_conf', $toml);
        $users = new UserRepository($config);
        (new InstallWriter($config, $users))->writeFreshInstall('admin', 'secret-pass', '127.0.0.1');

        Application::boot($this->tmpdir);
    }

    protected function tearDown(): void
    {
        Application::resetForTests();
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    public function testTablesRequiresAuth(): void
    {
        $kernel = new ApiKernel(Application::get());
        $response = $kernel->handle(Request::create('/api/v1/tables', 'GET'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testThemeIsPublic(): void
    {
        $kernel = new ApiKernel(Application::get());
        $response = $kernel->handle(Request::create('/api/v1/theme', 'GET'));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('css_variables', $body['data'] ?? []);
    }
}
