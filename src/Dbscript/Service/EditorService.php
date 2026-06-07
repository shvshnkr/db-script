<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Dbscript\Security\DenywordsGuard;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

final class EditorService
{
    public function __construct(
        private readonly DbdataRepository $dbdata,
        private readonly ConnectionFactory $connections,
        private readonly DenywordsGuard $denywords,
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
        [$table, $mysqlTable, $conn] = $this->mysqlContext($tableId);

        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(500, max(1, (int) ($query['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

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
        [$table, $mysqlTable, $conn, $pkColumns] = $this->mysqlContext($tableId, true);
        $pkParts = $this->parsePk($pk, $pkColumns);
        $row = $this->fetchRowByPk($conn, $mysqlTable, $pkColumns, $pkParts);
        if ($row === null) {
            throw new \InvalidArgumentException('Row not found: ' . $pk);
        }

        return [
            'table_id' => (int) ($table['id'] ?? 0),
            'table' => $mysqlTable,
            'visual_name' => (string) ($table['visual_name'] ?? $mysqlTable),
            'pk' => $pk,
            'pk_columns' => $pkColumns,
            'row' => $row,
        ];
    }

    public function createRow(string $tableId, array $data): string
    {
        [$table, $mysqlTable, $conn, $pkColumns] = $this->mysqlContext($tableId, true);
        $filtered = $this->filterRowData($conn, $mysqlTable, $data, $pkColumns, true);
        if ($filtered === []) {
            throw new \InvalidArgumentException('No valid columns in row data');
        }

        $columns = array_keys($filtered);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $mysqlTable,
            implode('`, `', $columns),
            $placeholders
        );
        $conn->executeStatement($sql, array_values($filtered));

        $insertId = $conn->lastInsertId();
        if ($insertId !== '0' && $insertId !== '') {
            return (string) $insertId;
        }

        $pkValues = [];
        foreach ($pkColumns as $column) {
            if (!array_key_exists($column, $filtered)) {
                throw new \RuntimeException('Primary key value missing after insert');
            }
            $pkValues[] = (string) $filtered[$column];
        }

        return $this->encodePk($pkValues);
    }

    public function updateRow(string $tableId, string $pk, array $data): void
    {
        [$table, $mysqlTable, $conn, $pkColumns] = $this->mysqlContext($tableId, true);
        $pkParts = $this->parsePk($pk, $pkColumns);
        if ($this->fetchRowByPk($conn, $mysqlTable, $pkColumns, $pkParts) === null) {
            throw new \InvalidArgumentException('Row not found: ' . $pk);
        }

        $filtered = $this->filterRowData($conn, $mysqlTable, $data, $pkColumns, false);
        if ($filtered === []) {
            return;
        }

        $sets = [];
        $params = [];
        foreach ($filtered as $column => $value) {
            $sets[] = '`' . $column . '` = ?';
            $params[] = $value;
        }

        [$whereSql, $whereParams] = $this->pkWhereClause($pkColumns, $pkParts);
        $conn->executeStatement(
            'UPDATE `' . $mysqlTable . '` SET ' . implode(', ', $sets) . ' WHERE ' . $whereSql,
            array_merge($params, $whereParams)
        );
    }

    /** @param list<string> $pks */
    public function deleteRows(string $tableId, array $pks): int
    {
        if ($pks === []) {
            return 0;
        }

        [, $mysqlTable, $conn, $pkColumns] = $this->mysqlContext($tableId, true);
        $deleted = 0;

        foreach ($pks as $pk) {
            $pkParts = $this->parsePk($pk, $pkColumns);
            [$whereSql, $whereParams] = $this->pkWhereClause($pkColumns, $pkParts);
            $deleted += $conn->executeStatement(
                'DELETE FROM `' . $mysqlTable . '` WHERE ' . $whereSql,
                $whereParams
            );
        }

        return $deleted;
    }

    /** @return array<string, mixed> */
    public function executeSql(string $query, array $context = []): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new \InvalidArgumentException('Empty SQL query');
        }

        $userLevel = (int) ($context['user_level'] ?? 0);
        $superUser = (bool) ($context['bypass_denywords'] ?? false);
        $this->denywords->assertAllowed($query, $userLevel, $superUser);
        $this->assertSingleStatement($query);

        $conn = $this->connectionForContext($context);
        $verb = strtolower(strtok(ltrim($query), " \t\n\r") ?: '');

        if (in_array($verb, ['select', 'show', 'describe', 'desc', 'explain'], true)) {
            $rows = $conn->fetchAllAssociative($query);

            return [
                'kind' => 'select',
                'affected' => count($rows),
                'columns' => $rows !== [] ? array_keys($rows[0]) : [],
                'rows' => $rows,
                'query' => $query,
            ];
        }

        $affected = $conn->executeStatement($query);

        return [
            'kind' => 'write',
            'affected' => $affected,
            'columns' => [],
            'rows' => [],
            'query' => $query,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getColumnMeta(string $tableId): array
    {
        $table = $this->requireTable($tableId);
        $mysqlTable = (string) ($table['mysql_table'] ?? '');
        $conn = $this->connections->forTable($table);
        $schema = $conn->createSchemaManager();
        $columns = $schema->listTableColumns($mysqlTable);
        $pkColumns = $this->primaryKeyColumns($conn, $mysqlTable);
        $out = [];
        foreach ($columns as $column) {
            $name = $column->getName();
            $out[] = [
                'name' => $name,
                'type' => Type::lookupName($column->getType()),
                'nullable' => !$column->getNotnull(),
                'primary' => in_array($name, $pkColumns, true),
            ];
        }

        return $out;
    }

    /** @return array{0: array<string, mixed>, 1: string, 2: Connection, 3?: list<string>} */
    private function mysqlContext(string $tableId, bool $requirePk = false): array
    {
        $table = $this->requireTable($tableId);
        if (($table['engine'] ?? 'mysql') !== 'mysql') {
            throw new \InvalidArgumentException('Only mysql tables supported in arch-modern');
        }

        $mysqlTable = (string) ($table['mysql_table'] ?? '');
        if ($mysqlTable === '' || !self::isValidIdentifier($mysqlTable)) {
            throw new \InvalidArgumentException('Invalid mysql_table name');
        }

        $conn = $this->connections->forTable($table);
        if (!$requirePk) {
            return [$table, $mysqlTable, $conn];
        }

        $pkColumns = $this->primaryKeyColumns($conn, $mysqlTable);
        if ($pkColumns === []) {
            throw new \RuntimeException('Table has no primary key: ' . $mysqlTable);
        }

        return [$table, $mysqlTable, $conn, $pkColumns];
    }

    /** @return list<string> */
    private function primaryKeyColumns(Connection $conn, string $mysqlTable): array
    {
        foreach ($conn->createSchemaManager()->listTableIndexes($mysqlTable) as $index) {
            if ($index->isPrimary()) {
                return $index->getColumns();
            }
        }

        return [];
    }

    /** @param list<string> $pkColumns @return list<string> */
    private function parsePk(string $pk, array $pkColumns): array
    {
        if ($pk === '') {
            throw new \InvalidArgumentException('Primary key is required');
        }

        if (count($pkColumns) === 1) {
            return [$pk];
        }

        $parts = explode('|', $pk);
        if (count($parts) !== count($pkColumns)) {
            throw new \InvalidArgumentException('Invalid composite primary key');
        }

        return $parts;
    }

    /** @param list<string> $pkValues */
    private function encodePk(array $pkValues): string
    {
        return count($pkValues) === 1 ? (string) $pkValues[0] : implode('|', $pkValues);
    }

    /**
     * @param list<string> $pkColumns
     * @param list<string> $pkParts
     * @return array{0: string, 1: list<mixed>}
     */
    private function pkWhereClause(array $pkColumns, array $pkParts): array
    {
        $parts = [];
        $params = [];
        foreach ($pkColumns as $i => $column) {
            $parts[] = '`' . $column . '` = ?';
            $params[] = $pkParts[$i];
        }

        return [implode(' AND ', $parts), $params];
    }

    /**
     * @param list<string> $pkColumns
     * @param list<string> $pkParts
     * @return array<string, mixed>|null
     */
    private function fetchRowByPk(Connection $conn, string $mysqlTable, array $pkColumns, array $pkParts): ?array
    {
        [$whereSql, $params] = $this->pkWhereClause($pkColumns, $pkParts);
        $row = $conn->fetchAssociative(
            'SELECT * FROM `' . $mysqlTable . '` WHERE ' . $whereSql,
            $params
        );

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $pkColumns
     * @return array<string, mixed>
     */
    private function filterRowData(
        Connection $conn,
        string $mysqlTable,
        array $data,
        array $pkColumns,
        bool $forInsert,
    ): array {
        $allowed = [];
        foreach ($conn->createSchemaManager()->listTableColumns($mysqlTable) as $column) {
            $allowed[$column->getName()] = true;
        }

        $filtered = [];
        foreach ($data as $key => $value) {
            if (!is_string($key) || !isset($allowed[$key]) || !self::isValidIdentifier($key)) {
                continue;
            }
            if (!$forInsert && in_array($key, $pkColumns, true)) {
                continue;
            }
            $filtered[$key] = $value;
        }

        return $filtered;
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

    private static function isValidIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    /** @return array<string, mixed> */
    private function connectionForContext(array $context): Connection
    {
        $tableId = (string) ($context['table_id'] ?? '');
        if ($tableId !== '') {
            $table = $this->requireTable($tableId);
            if (($table['engine'] ?? 'mysql') !== 'mysql') {
                throw new \InvalidArgumentException('Only mysql tables supported in arch-modern');
            }

            return $this->connections->forTable($table);
        }

        return $this->connections->fromSitedata();
    }

    private function assertSingleStatement(string $query): void
    {
        if (!str_contains($query, ';')) {
            return;
        }

        $parts = array_values(array_filter(array_map('trim', explode(';', $query)), static fn (string $p): bool => $p !== ''));
        if (count($parts) > 1) {
            throw new \InvalidArgumentException('Multiple SQL statements not allowed');
        }
    }
}
