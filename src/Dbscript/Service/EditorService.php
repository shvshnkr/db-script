<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;

final class EditorService
{
    public function __construct(
        private readonly DbdataRepository $dbdata,
        private readonly ConnectionFactory $connections,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listTables(): array
    {
        return $this->dbdata->tables();
    }

    /** @return array<string, mixed> */
    public function listRows(string $tableId, array $query = []): array
    {
        $table = $this->requireTable($tableId);
        if (($table['engine'] ?? 'mysql') !== 'mysql') {
            throw new \InvalidArgumentException('Only mysql tables supported in arch-modern phase 3');
        }

        $mysqlTable = (string) ($table['mysql_table'] ?? '');
        if ($mysqlTable === '' || !preg_match('/^[A-Za-z0-9_]+$/', $mysqlTable)) {
            throw new \InvalidArgumentException('Invalid mysql_table name');
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(500, max(1, (int) ($query['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $conn = $this->connections->forTable($table);
        $total = (int) $conn->fetchOne('SELECT COUNT(*) FROM `' . $mysqlTable . '`');

        $rows = $conn->fetchAllAssociative(
            'SELECT * FROM `' . $mysqlTable . '` LIMIT ' . $limit . ' OFFSET ' . $offset
        );

        return [
            'table_id' => (int) ($table['id'] ?? 0),
            'table' => $mysqlTable,
            'visual_name' => (string) ($table['visual_name'] ?? $mysqlTable),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'rows' => $rows,
        ];
    }

    /** @return array<string, mixed> */
    public function getRow(string $tableId, string $pk): array
    {
        throw new \BadMethodCallException('EditorService::getRow not implemented yet');
    }

    public function createRow(string $tableId, array $data): string
    {
        throw new \BadMethodCallException('EditorService::createRow not implemented yet');
    }

    public function updateRow(string $tableId, string $pk, array $data): void
    {
        throw new \BadMethodCallException('EditorService::updateRow not implemented yet');
    }

    /** @param list<string> $pks */
    public function deleteRows(string $tableId, array $pks): int
    {
        throw new \BadMethodCallException('EditorService::deleteRows not implemented yet');
    }

    /** @return array<string, mixed> */
    public function executeSql(string $query, array $context = []): array
    {
        throw new \BadMethodCallException('EditorService::executeSql not implemented yet');
    }

    /** @return list<array<string, mixed>> */
    public function getColumnMeta(string $tableId): array
    {
        $table = $this->requireTable($tableId);
        $mysqlTable = (string) ($table['mysql_table'] ?? '');
        $conn = $this->connections->forTable($table);
        $schema = $conn->createSchemaManager();
        $columns = $schema->listTableColumns($mysqlTable);
        $out = [];
        foreach ($columns as $column) {
            $out[] = [
                'name' => $column->getName(),
                'type' => $column->getType()->getName(),
                'nullable' => !$column->getNotnull(),
            ];
        }

        return $out;
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
