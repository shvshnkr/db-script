<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Application;
use Dbscript\Auth\JwtAuthService;
use Dbscript\Config\DbdataRepository;
use Dbscript\Config\UserRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Security\DenywordsGuard;
use Dbscript\Service\EditorService;
use Dbscript\View\ThemeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiKernel
{
    private ApiRouter $router;

    private JwtAuthService $auth;

    public function __construct(
        private readonly Application $app,
    ) {
        $config = $app->config();
        $this->auth = new JwtAuthService($config, new UserRepository($config));
        $this->router = $this->buildRouter();
    }

    public function handle(Request $request): Response
    {
        if (!$this->app->isInstalled()) {
            return ApiResponse::fail([
                'code' => 'not_installed',
                'message' => 'Dbscript is not installed. Run install.php first.',
            ], 503);
        }

        $match = $this->router->match($request);
        if ($match === null) {
            return ApiResponse::fail([
                'code' => 'not_found',
                'message' => 'API route not found.',
            ], 404);
        }

        $claims = null;
        if ($match['auth']) {
            $claims = $this->auth->readFromRequest($request);
            if ($claims === null) {
                return ApiResponse::unauthorized();
            }
        }

        foreach ($match['params'] as $key => $value) {
            $request->attributes->set($key, $value);
        }

        $secure = $request->isSecure();

        return ($match['handler'])($request, $claims, $secure);
    }

    public static function fromGlobals(): Response
    {
        $app = Application::get();
        $kernel = new self($app);
        $request = Request::createFromGlobals();

        return $kernel->handle($request);
    }

    private function buildRouter(): ApiRouter
    {
        $config = $this->app->config();
        $authCtrl = new AuthApiController($this->auth, new UserRepository($config));
        $editor = new EditorService(
            new DbdataRepository($config),
            new ConnectionFactory($config),
            new DenywordsGuard($config),
        );
        $editorCtrl = new EditorApiController($editor);
        $themeCtrl = new ThemeApiController(new ThemeService($config));

        $router = new ApiRouter();

        $router->add('POST', '/api/v1/auth/login', fn (Request $req, ?array $claims, bool $secure) => $authCtrl->login($req, $secure), false);
        $router->add('POST', '/api/v1/auth/logout', fn (Request $req, ?array $claims, bool $secure) => $authCtrl->logout($req, $secure));
        $router->add('GET', '/api/v1/auth/me', fn (Request $req, ?array $claims, bool $secure) => $authCtrl->me($claims ?? []));

        $router->add('GET', '/api/v1/theme', fn () => $themeCtrl->show(), false);

        $router->add('GET', '/api/v1/tables', fn () => $editorCtrl->listTables());
        $router->add('GET', '/api/v1/tables/{tableId}/meta', fn (Request $req) => $editorCtrl->columnMeta((string) $req->attributes->get('tableId')));
        $router->add('GET', '/api/v1/tables/{tableId}/rows', fn (Request $req) => $editorCtrl->listRows((string) $req->attributes->get('tableId'), $req));
        $router->add('GET', '/api/v1/tables/{tableId}/rows/{pk}', fn (Request $req) => $editorCtrl->getRow(
            (string) $req->attributes->get('tableId'),
            (string) $req->attributes->get('pk'),
        ));
        $router->add('POST', '/api/v1/tables/{tableId}/rows', fn (Request $req) => $editorCtrl->createRow(
            (string) $req->attributes->get('tableId'),
            $req,
        ));
        $router->add('PUT', '/api/v1/tables/{tableId}/rows/{pk}', fn (Request $req) => $editorCtrl->updateRow(
            (string) $req->attributes->get('tableId'),
            (string) $req->attributes->get('pk'),
            $req,
        ));
        $router->add('DELETE', '/api/v1/tables/{tableId}/rows', fn (Request $req) => $editorCtrl->deleteRows(
            (string) $req->attributes->get('tableId'),
            $req,
        ));

        return $router;
    }
}
