<?php
declare(strict_types=1);

use Dbscript\Application;
use Dbscript\Http\Api\ApiKernel;
use Symfony\Component\HttpFoundation\Request;
use Dbscript\Install\InstallWriter;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\Config\UserRepository;
use PHPUnit\Framework\TestCase;

final class AuthApiTest extends TestCase
{
    private string $tmpdir;

    protected function setUp(): void
    {
        Application::resetForTests();
        $this->tmpdir = sys_get_temp_dir() . '/dbs-spa-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);

        $toml = new TomlLoader();
        $config = new ConfigRepository($this->tmpdir . '/_conf', $toml);
        $users = new UserRepository($config);
        (new InstallWriter($config, $users))->writeFreshInstall('admin', 'secret-pass', '127.0.0.1');

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        Application::boot($this->tmpdir);
    }

    protected function tearDown(): void
    {
        Application::resetForTests();
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    public function testMeReturns401WithoutAuth(): void
    {
        $kernel = new ApiKernel(Application::get());
        $request = Request::create('/api/v1/auth/me', 'GET');
        $response = $kernel->handle($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testLoginAndMeWithBearer(): void
    {
        $kernel = new ApiKernel(Application::get());

        $login = Request::create('/api/v1/auth/login', 'POST', [], [], [], [], json_encode([
            'login' => 'admin',
            'password' => 'secret-pass',
        ]));
        $login->headers->set('Content-Type', 'application/json');
        $loginResponse = $kernel->handle($login);
        $this->assertSame(200, $loginResponse->getStatusCode());

        $body = json_decode($loginResponse->getContent(), true);
        $token = $body['data']['token'] ?? '';
        $this->assertNotSame('', $token);

        $me = Request::create('/api/v1/auth/me', 'GET');
        $me->headers->set('Authorization', 'Bearer ' . $token);
        $meResponse = $kernel->handle($me);
        $this->assertSame(200, $meResponse->getStatusCode());

        $meBody = json_decode($meResponse->getContent(), true);
        $this->assertSame('admin', $meBody['data']['login'] ?? null);
    }
}
