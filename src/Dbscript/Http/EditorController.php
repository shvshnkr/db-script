<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;
use Dbscript\Auth\AuthMiddleware;
use Dbscript\Auth\CsrfService;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Security\DenywordsGuard;
use Dbscript\Service\EditorService;
use Dbscript\View\TwigFactory;

final class EditorController
{
    public function __construct(
        private readonly EditorService $editor,
        private readonly CsrfService $csrf,
    ) {
    }

    public function handle(): void
    {
        $claims = AuthMiddleware::requireLogin();
        $action = (string) ($_GET['action'] ?? '');

        if ($action === 'sql') {
            $this->handleSql($claims);
            return;
        }

        $tbl = (string) ($_GET['tbl'] ?? '');

        if ($tbl === '') {
            $this->renderTablePicker();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($tbl);
            return;
        }

        $pk = (string) ($_GET['pk'] ?? '');

        if ($action === 'new') {
            $this->renderForm($tbl, null);
            return;
        }

        if ($pk !== '') {
            $this->renderForm($tbl, $pk);
            return;
        }

        $this->renderList($tbl);
    }

    /** @param array{login: string, role: string} $claims */
    private function handleSql(array $claims): void
    {
        $tbl = (string) ($_GET['tbl'] ?? $_POST['table_id'] ?? '');
        $error = '';
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrf->validate(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
                http_response_code(403);
                echo 'Invalid CSRF token.';
                exit;
            }

            $query = (string) ($_POST['query'] ?? '');
            try {
                $result = $this->editor->executeSql($query, [
                    'table_id' => $tbl,
                    'user_level' => $this->userLevel($claims),
                ]);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('editor/sql.html.twig', [
            'table_id' => $tbl,
            'query' => (string) ($_POST['query'] ?? 'SELECT * FROM demo_items LIMIT 5'),
            'result' => $result,
            'error' => $error,
            'csrf' => $this->csrf->token(),
        ]);
    }

    /** @param array{login: string, role: string} $claims */
    private function userLevel(array $claims): int
    {
        return ($claims['role'] ?? '') === 'admin' ? 10 : 4;
    }

    private function handlePost(string $tbl): void
    {
        if (!$this->csrf->validate(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            http_response_code(403);
            echo 'Invalid CSRF token.';
            exit;
        }

        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete') {
            $pk = (string) ($_POST['pk'] ?? '');
            if ($pk !== '') {
                $this->editor->deleteRows($tbl, [$pk]);
            }
            header('Location: w-arch.php?tbl=' . rawurlencode($tbl));
            exit;
        }

        $data = is_array($_POST['row'] ?? null) ? $_POST['row'] : [];
        $pk = (string) ($_POST['pk'] ?? '');

        if ($pk === '') {
            $newPk = $this->editor->createRow($tbl, $data);
            header('Location: w-arch.php?tbl=' . rawurlencode($tbl) . '&pk=' . rawurlencode($newPk));
            exit;
        }

        $this->editor->updateRow($tbl, $pk, $data);
        header('Location: w-arch.php?tbl=' . rawurlencode($tbl) . '&pk=' . rawurlencode($pk));
        exit;
    }

    private function renderTablePicker(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('editor/tables.html.twig', [
            'tables' => $this->editor->listTables(),
        ]);
    }

    private function renderList(string $tbl): void
    {
        $result = $this->editor->listRows($tbl, [
            'page' => (int) ($_GET['page'] ?? 1),
            'limit' => (int) ($_GET['limit'] ?? 25),
        ]);
        $pkColumns = $this->primaryKeyColumns($tbl);

        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('editor/list.html.twig', [
            'result' => $result,
            'columns' => $result['rows'] !== [] ? array_keys($result['rows'][0]) : [],
            'pk_columns' => $pkColumns,
            'csrf' => $this->csrf->token(),
        ]);
    }

    private function renderForm(string $tbl, ?string $pk): void
    {
        $columns = $this->editor->getColumnMeta($tbl);
        $row = [];
        if ($pk !== null) {
            $loaded = $this->editor->getRow($tbl, $pk);
            $row = $loaded['row'];
        }

        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('editor/form.html.twig', [
            'table_id' => $tbl,
            'pk' => $pk,
            'columns' => $columns,
            'row' => $row,
            'csrf' => $this->csrf->token(),
        ]);
    }

    /** @return list<string> */
    private function primaryKeyColumns(string $tbl): array
    {
        $columns = [];
        foreach ($this->editor->getColumnMeta($tbl) as $column) {
            if (!empty($column['primary'])) {
                $columns[] = (string) $column['name'];
            }
        }

        return $columns;
    }

    public static function fromApplication(Application $app): self
    {
        $config = $app->config();
        $editor = self::editorService($config);
        $property = $app->property();
        $csrfEnabled = (bool) ($property['security']['csrf_enabled'] ?? true);

        return new self($editor, new CsrfService($csrfEnabled));
    }

    public static function editorService(ConfigRepository $config): EditorService
    {
        return new EditorService(
            new DbdataRepository($config),
            new ConnectionFactory($config),
            new DenywordsGuard($config),
        );
    }
}
