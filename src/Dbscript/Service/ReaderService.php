<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;

final class ReaderService
{
    public function __construct(
        private readonly DbdataRepository $dbdata,
        private readonly ConnectionFactory $connections,
        private readonly EditorService $editor,
    ) {
    }

    /** @return array<string, mixed> */
    public function search(string $tableId, array $query = []): array
    {
        $q = trim((string) ($query['q'] ?? ''));
        if ($q === '') {
            return $this->editor->listRows($tableId, $query);
        }

        $table = $this->requireTable($tableId);
        if (($table['engine'] ?? 'mysql') !== 'mysql') {
            throw new \InvalidArgumentException('Only mysql tables supported in arch-modern');
        }

        $mysqlTable = (string) ($table['mysql_table'] ?? '');
        if ($mysqlTable === '' || !preg_match('/^[A-Za-z0-9_]+$/', $mysqlTable)) {
            throw new \InvalidArgumentException('Invalid mysql_table name');
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(500, max(1, (int) ($query['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $conn = $this->connections->forTable($table);
        $columns = array_map(
            static fn ($column) => $column->getName(),
            $conn->createSchemaManager()->listTableColumns($mysqlTable)
        );

        $likes = [];
        $params = [];
        foreach ($columns as $column) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
                continue;
            }
            $likes[] = 'CAST(`' . $column . '` AS CHAR) LIKE ?';
            $params[] = '%' . $q . '%';
        }

        if ($likes === []) {
            return $this->editor->listRows($tableId, $query);
        }

        $where = '(' . implode(' OR ', $likes) . ')';
        $total = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM `' . $mysqlTable . '` WHERE ' . $where,
            $params
        );

        $rows = $conn->fetchAllAssociative(
            'SELECT * FROM `' . $mysqlTable . '` WHERE ' . $where
            . ' LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return [
            'table_id' => (int) ($table['id'] ?? 0),
            'table' => $mysqlTable,
            'visual_name' => (string) ($table['visual_name'] ?? $mysqlTable),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'q' => $q,
            'rows' => $rows,
        ];
    }

    /** @return array<string, mixed> */
    public function viewRow(string $tableId, string $pk): array
    {
        return $this->editor->getRow($tableId, $pk);
    }

    public function export(string $tableId, array $query = [], string $format = 'csv'): string
    {
        if ($format !== 'csv') {
            throw new \InvalidArgumentException('Unsupported export format: ' . $format);
        }

        $result = $this->search($tableId, array_merge($query, [
            'page' => 1,
            'limit' => min(5000, max(1, (int) ($query['limit'] ?? 5000))),
        ]));

        $rows = $result['rows'];
        if ($rows === []) {
            return '';
        }

        $columns = array_keys($rows[0]);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open temp stream for CSV export');
        }

        fputcsv($handle, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    /** @return array<string, mixed> */
    private function requireTable(string $tableId): array
    {
        $table = $this->dbdata->get((int) $tableId);
        if ($table === null) {
            throw new \InvalidArgumentException('Unknown table id: ' . $tableId);
        }

        return $table;
    }
}
