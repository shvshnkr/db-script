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

final class SpaApiPhaseTest extends TestCase
{
    private string $tmpdir;
    private string $token = '';

    protected function setUp(): void
    {
        Application::resetForTests();
        $this->tmpdir = sys_get_temp_dir() . '/dbs-spa-ph-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir . '/_conf', 0775, true);

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $toml = new TomlLoader();
        $config = new ConfigRepository($this->tmpdir . '/_conf', $toml);
        $users = new UserRepository($config);
        (new InstallWriter($config, $users))->writeFreshInstall('admin', 'secret-pass', '127.0.0.1');

        Application::boot($this->tmpdir);

        $kernel = new ApiKernel(Application::get());
        $login = Request::create('/api/v1/auth/login', 'POST', [], [], [], [], json_encode([
            'login' => 'admin',
            'password' => 'secret-pass',
        ]));
        $login->headers->set('Content-Type', 'application/json');
        $response = $kernel->handle($login);
        $body = json_decode($response->getContent(), true);
        $this->token = (string) ($body['data']['token'] ?? '');
    }

    protected function tearDown(): void
    {
        Application::resetForTests();
        array_map('unlink', glob($this->tmpdir . '/_conf/*') ?: []);
        array_map('unlink', glob($this->tmpdir . '/_local/uploads/*') ?: []);
        @rmdir($this->tmpdir . '/_local/uploads');
        @rmdir($this->tmpdir . '/_local');
        @rmdir($this->tmpdir . '/_conf');
        @rmdir($this->tmpdir);
    }

    public function testMenuAndI18nEndpoints(): void
    {
        $kernel = new ApiKernel(Application::get());

        $menu = $this->authed($kernel, '/api/v1/menu', 'GET');
        $this->assertSame(200, $menu->getStatusCode());
        $menuBody = json_decode($menu->getContent(), true);
        $this->assertNotEmpty($menuBody['data']['items'] ?? []);

        $i18n = $kernel->handle(Request::create('/api/v1/i18n?lang=english', 'GET'));
        $this->assertSame(200, $i18n->getStatusCode());
        $i18nBody = json_decode($i18n->getContent(), true);
        $this->assertSame('english', $i18nBody['data']['language'] ?? null);
        $this->assertArrayHasKey('MNU_2', $i18nBody['data']['messages'] ?? []);
    }

    public function testFilesListRequiresAuth(): void
    {
        $kernel = new ApiKernel(Application::get());
        $response = $kernel->handle(Request::create('/api/v1/files', 'GET'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testFilesListEmptyForAdmin(): void
    {
        $kernel = new ApiKernel(Application::get());
        $response = $this->authed($kernel, '/api/v1/files', 'GET');
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame([], $body['data'] ?? null);
    }

    public function testInfoPagesArePublicOrProtected(): void
    {
        $kernel = new ApiKernel(Application::get());

        $ver = $kernel->handle(Request::create('/api/v1/info/ver', 'GET'));
        $this->assertSame(200, $ver->getStatusCode());
        $verBody = json_decode($ver->getContent(), true);
        $this->assertSame('ver', $verBody['data']['slug'] ?? null);

        $info = $kernel->handle(Request::create('/api/v1/info/info', 'GET'));
        $this->assertSame(401, $info->getStatusCode());
    }

    public function testSqlExecuteRejectsEmptyQuery(): void
    {
        $kernel = new ApiKernel(Application::get());
        $response = $this->authed($kernel, '/api/v1/sql/execute', 'POST', json_encode([
            'query' => '',
        ]));
        $this->assertSame(422, $response->getStatusCode());
    }

    private function authed(ApiKernel $kernel, string $path, string $method, ?string $body = null)
    {
        $request = Request::create($path, $method, [], [], [], [], $body ?? '');
        if ($body !== null) {
            $request->headers->set('Content-Type', 'application/json');
        }
        $request->headers->set('Authorization', 'Bearer ' . $this->token);
        $request->headers->set('X-Requested-With', 'DbscriptSPA');

        return $kernel->handle($request);
    }
}
