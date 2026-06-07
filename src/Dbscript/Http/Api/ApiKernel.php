<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Application;
use Dbscript\Auth\JwtAuthService;
use Dbscript\Config\DbdataRepository;
use Dbscript\Config\FilesCfgRepository;
use Dbscript\Config\UserRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\I18n\LangResolver;
use Dbscript\Security\DenywordsGuard;
use Dbscript\Service\EditorService;
use Dbscript\Service\FileManagerService;
use Dbscript\Service\ImportExportService;
use Dbscript\Service\InfoService;
use Dbscript\Service\MenuService;
use Dbscript\Service\ReaderService;
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
        } else {
            $claims = $this->auth->readFromRequest($request);
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
        $reader = new ReaderService(new DbdataRepository($config), new ConnectionFactory($config), $editor);
        $editorCtrl = new EditorApiController($editor);
        $readerCtrl = new ReaderApiController($reader);
        $filesCtrl = new FilesApiController(new FileManagerService(
            $config,
            new FilesCfgRepository($this->app->root() . '/_conf'),
            $this->app->root(),
        ));
        $sqlCtrl = new SqlApiController($editor);
        $menuCtrl = new MenuApiController(new MenuService($config));
        $infoCtrl = new InfoApiController(new InfoService($config));
        $i18nCtrl = new I18nApiController(new LangResolver($config, $this->app->root() . '/_langdb'));
        $themeCtrl = new ThemeApiController(new ThemeService($config));
        $converterCtrl = new ConverterApiController(new ImportExportService(
            new DbdataRepository($config),
            new ConnectionFactory($config),
            $this->app->root(),
        ));

        $router = new ApiRouter();

        $router->add('POST', '/api/v1/auth/login', fn (Request $req, ?array $claims, bool $secure) => $authCtrl->login($req, $secure), false);
        $router->add('POST', '/api/v1/auth/logout', fn (Request $req, ?array $claims, bool $secure) => $authCtrl->logout($req, $secure));
        $router->add('GET', '/api/v1/auth/me', fn (Request $req, ?array $claims, bool $secure) => $authCtrl->me($claims ?? []));

        $router->add('GET', '/api/v1/theme', fn () => $themeCtrl->show(), false);

        $router->add('GET', '/api/v1/menu', fn () => $menuCtrl->list());
        $router->add('GET', '/api/v1/info', fn () => $infoCtrl->list(), false);
        $router->add('GET', '/api/v1/info/{slug}', fn (Request $req, ?array $claims) => $infoCtrl->show(
            (string) $req->attributes->get('slug'),
            $req,
            $claims,
        ), false);
        $router->add('GET', '/api/v1/i18n', fn (Request $req) => $i18nCtrl->bundle($req), false);
        $router->add('GET', '/api/v1/i18n/languages', fn () => $i18nCtrl->languages(), false);

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
        $router->add('POST', '/api/v1/tables/{tableId}/import', fn (Request $req) => $editorCtrl->importCsv(
            (string) $req->attributes->get('tableId'),
            $req,
        ));

        $router->add('GET', '/api/v1/reader/tables/{tableId}/search', fn (Request $req) => $readerCtrl->search(
            (string) $req->attributes->get('tableId'),
            $req,
        ));
        $router->add('GET', '/api/v1/reader/tables/{tableId}/rows/{pk}', fn (Request $req) => $readerCtrl->viewRow(
            (string) $req->attributes->get('tableId'),
            (string) $req->attributes->get('pk'),
        ));
        $router->add('GET', '/api/v1/reader/tables/{tableId}/export.csv', fn (Request $req) => $readerCtrl->exportCsv(
            (string) $req->attributes->get('tableId'),
            $req,
        ));

        $router->add('GET', '/api/v1/files', fn (Request $req, ?array $claims) => $filesCtrl->list($req, $claims ?? []));
        $router->add('POST', '/api/v1/files', fn (Request $req, ?array $claims) => $filesCtrl->upload($req, $claims ?? []));
        $router->add('GET', '/api/v1/files/{hash}/download', fn (Request $req, ?array $claims) => $filesCtrl->download(
            (string) $req->attributes->get('hash'),
            $claims ?? [],
        ));
        $router->add('DELETE', '/api/v1/files/{hash}', fn (Request $req, ?array $claims) => $filesCtrl->delete(
            (string) $req->attributes->get('hash'),
            $req,
            $claims ?? [],
        ));

        $router->add('POST', '/api/v1/sql/execute', fn (Request $req, ?array $claims) => $sqlCtrl->execute($req, $claims ?? []));

        $router->add('GET', '/api/v1/converter/tables', fn () => $converterCtrl->listTables());
        $router->add('POST', '/api/v1/converter/preview', fn (Request $req) => $converterCtrl->preview($req));
        $router->add('POST', '/api/v1/converter/run', fn (Request $req) => $converterCtrl->convert($req));

        return $router;
    }
}
