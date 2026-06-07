<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;
use Dbscript\Auth\AuthMiddleware;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Service\EditorService;
use Dbscript\Service\ReaderService;
use Dbscript\View\TwigFactory;

final class ReaderController
{
    public function __construct(
        private readonly ReaderService $reader,
        private readonly EditorService $editor,
    ) {
    }

    public function handle(): void
    {
        AuthMiddleware::requireLogin();
        $tbl = (string) ($_GET['tbl'] ?? '');

        if ($tbl === '') {
            $this->renderTablePicker();
            return;
        }

        if ((string) ($_GET['export'] ?? '') === 'csv') {
            $this->downloadCsv($tbl);
            return;
        }

        $pk = (string) ($_GET['pk'] ?? '');
        if ($pk !== '') {
            $this->renderView($tbl, $pk);
            return;
        }

        $this->renderSearch($tbl);
    }

    private function downloadCsv(string $tbl): void
    {
        $csv = $this->reader->export($tbl, [
            'q' => (string) ($_GET['q'] ?? ''),
        ]);

        $table = $this->editor->listTables();
        $name = 'table-' . $tbl;
        foreach ($table as $meta) {
            if ((string) ($meta['id'] ?? '') === $tbl) {
                $name = (string) ($meta['mysql_table'] ?? $name);
                break;
            }
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        echo $csv;
    }

    private function renderTablePicker(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('reader/tables.html.twig', [
            'tables' => $this->editor->listTables(),
        ]);
    }

    private function renderSearch(string $tbl): void
    {
        $result = $this->reader->search($tbl, [
            'page' => (int) ($_GET['page'] ?? 1),
            'limit' => (int) ($_GET['limit'] ?? 25),
            'q' => (string) ($_GET['q'] ?? ''),
        ]);
        $pkColumns = $this->primaryKeyColumns($tbl);

        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('reader/search.html.twig', [
            'result' => $result,
            'columns' => $result['rows'] !== [] ? array_keys($result['rows'][0]) : [],
            'pk_columns' => $pkColumns,
            'q' => (string) ($_GET['q'] ?? ''),
        ]);
    }

    private function renderView(string $tbl, string $pk): void
    {
        $loaded = $this->reader->viewRow($tbl, $pk);

        header('Content-Type: text/html; charset=UTF-8');
        $view = TwigFactory::create(Application::get());
        echo $view->render('reader/view.html.twig', [
            'loaded' => $loaded,
            'columns' => array_keys($loaded['row']),
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
        $editor = EditorController::editorService($config);
        $reader = new ReaderService(new DbdataRepository($config), new ConnectionFactory($config), $editor);

        return new self($reader, $editor);
    }
}
