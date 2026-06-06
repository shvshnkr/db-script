<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;
use Dbscript\Auth\AuthMiddleware;
use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Service\EditorService;
use Dbscript\View\TwigRenderer;

final class EditorController
{
    public function __construct(
        private readonly EditorService $editor,
        private readonly TwigRenderer $view,
    ) {
    }

    public function handle(): void
    {
        $claims = AuthMiddleware::requireLogin();
        $tbl = (string) ($_GET['tbl'] ?? '');

        if ($tbl === '') {
            $this->renderTablePicker($claims);
            return;
        }

        $result = $this->editor->listRows($tbl, [
            'page' => (int) ($_GET['page'] ?? 1),
            'limit' => (int) ($_GET['limit'] ?? 25),
        ]);

        header('Content-Type: text/html; charset=UTF-8');
        echo $this->view->render('editor/list.html.twig', [
            'user' => $claims['login'],
            'result' => $result,
            'columns' => $result['rows'] !== [] ? array_keys($result['rows'][0]) : [],
        ]);
    }

    /** @param array{login: string, role: string} $claims */
    private function renderTablePicker(array $claims): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo $this->view->render('editor/tables.html.twig', [
            'user' => $claims['login'],
            'tables' => $this->editor->listTables(),
        ]);
    }

    public static function fromApplication(Application $app): self
    {
        $config = $app->config();
        $dbdata = new DbdataRepository($config);
        $connections = new ConnectionFactory($config);
        $editor = new EditorService($dbdata, $connections);
        $view = new TwigRenderer($app->root() . '/templates');

        return new self($editor, $view);
    }
}
