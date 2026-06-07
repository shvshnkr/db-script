<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\DbdataRepository;
use Dbscript\Database\ConnectionFactory;
use Doctrine\DBAL\Connection;

final class ImportExportService
{
    private const LEGACY_SEPARATOR = '¦';

    public function __construct(
        private readonly DbdataRepository $dbdata,
        private readonly ConnectionFactory $connections,
        private readonly string $projectRoot,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listTables(): array
    {
        return array_map(function (array $table): array {
            return [
                'id' => (int) ($table['id'] ?? 0),
                'visual_name' => (string) ($table['visual_name'] ?? ''),
                'engine' => $this->normalizeEngine((string) ($table['engine'] ?? 'mysql')),
                'file_base' => (string) ($table['file_base'] ?? ''),
                'mysql_table' => (string) ($table['mysql_table'] ?? ''),
            ];
        }, $this->dbdata->tables());
    }

    /** @return array<string, mixed> */
    public function preview(int $sourceId, int $destId): array
    {
        $source = $this->requireTable($sourceId);
        $dest = $this->requireTable($destId);
        $sourceEngine = $this->normalizeEngine((string) ($source['engine'] ?? 'mysql'));
        $destEngine = $this->normalizeEngine((string) ($dest['engine'] ?? 'mysql'));

        if ($sourceEngine === $destEngine) {
            throw new \InvalidArgumentException('Source and destination must use different storage engines');
        }

        $direction = $this->direction($sourceEngine, $destEngine);

        return [
            'source' => $this->tableSummary($source, $sourceEngine),
            'destination' => $this->tableSummary($dest, $destEngine),
            'direction' => $direction,
            'direction_label' => $direction === 'fdb_to_mysql' ? 'CSV → SQL' : 'SQL → CSV',
        ];
    }

    /**
     * @param array{rewrite?: bool, use_semicolon?: bool, unique_id?: bool, verbose?: bool} $options
     * @return array<string, mixed>
     */
    public function convert(int $sourceId, int $destId, array $options = []): array
    {
        $preview = $this->preview($sourceId, $destId);
        $rewrite = (bool) ($options['rewrite'] ?? false);
        $useSemicolon = (bool) ($options['use_semicolon'] ?? false);
        $verbose = (bool) ($options['verbose'] ?? false);

        $log = [];
        if ($preview['direction'] === 'fdb_to_mysql') {
            $result = $this->fdbToMysql(
                $this->requireTable($sourceId),
                $this->requireTable($destId),
                $rewrite,
                $useSemicolon,
                (bool) ($options['unique_id'] ?? false),
                $verbose,
                $log,
            );
        } else {
            $result = $this->mysqlToFdb(
                $this->requireTable($sourceId),
                $this->requireTable($destId),
                $rewrite,
                $useSemicolon,
                $verbose,
                $log,
            );
        }

        return array_merge($preview, $result, ['log' => $log]);
    }

    /** @param list<string> $log */
    private function fdbToMysql(
        array $source,
        array $dest,
        bool $rewrite,
        bool $useSemicolon,
        bool $uniqueId,
        bool $verbose,
        array &$log,
    ): array {
        $fileBase = (string) ($source['file_base'] ?? '');
        if ($fileBase === '') {
            throw new \InvalidArgumentException('Source FDB table has no file_base configured');
        }

        $path = $this->dataPath($fileBase);
        if (!is_readable($path)) {
            throw new \InvalidArgumentException('Source FDB file not found: ' . $fileBase);
        }

        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            throw new \InvalidArgumentException('Source FDB file is empty');
        }

        $parsed = $this->parseFdbContent($content, $useSemicolon);
        $mysqlTable = $this->requireMysqlTable($dest);
        $conn = $this->connections->forTable($dest);

        if ($rewrite) {
            $conn->executeStatement('TRUNCATE TABLE `' . $mysqlTable . '`');
            if ($verbose) {
                $log[] = 'Truncated destination table ' . $mysqlTable;
            }
        }

        $allowed = $this->columnNames($conn, $mysqlTable);
        $imported = 0;
        $skipped = 0;

        foreach ($parsed['rows'] as $rowIndex => $row) {
            $filtered = [];
            foreach ($row as $column => $value) {
                $name = $this->resolveColumnName($this->fieldFix((string) $column), $allowed);
                if ($name === null) {
                    continue;
                }
                if (!$uniqueId && strtolower($name) === 'id') {
                    continue;
                }
                $filtered[$name] = $value;
            }

            if ($filtered === []) {
                $skipped++;
                continue;
            }

            try {
                $columns = array_keys($filtered);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $conn->executeStatement(
                    sprintf(
                        'INSERT INTO `%s` (`%s`) VALUES (%s)',
                        $mysqlTable,
                        implode('`, `', $columns),
                        $placeholders,
                    ),
                    array_values($filtered),
                );
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if ($verbose) {
                    $log[] = 'Row ' . ($rowIndex + 1) . ' skipped: ' . $e->getMessage();
                }
            }
        }

        if ($verbose) {
            $log[] = sprintf('Imported %d rows, skipped %d', $imported, $skipped);
        }

        return [
            'affected' => $imported,
            'imported' => $imported,
            'skipped' => $skipped,
            'destination_file' => null,
        ];
    }

    /** @param list<string> $log */
    private function mysqlToFdb(
        array $source,
        array $dest,
        bool $rewrite,
        bool $useSemicolon,
        bool $verbose,
        array &$log,
    ): array {
        $fileBase = (string) ($dest['file_base'] ?? '');
        if ($fileBase === '') {
            throw new \InvalidArgumentException('Destination FDB table has no file_base configured');
        }

        $mysqlTable = $this->requireMysqlTable($source);
        $conn = $this->connections->forTable($source);
        $rows = $conn->fetchAllAssociative('SELECT * FROM `' . $mysqlTable . '`');
        $path = $this->dataPath($fileBase);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create _data directory');
        }

        if ($rewrite || !is_file($path) || filesize($path) === 0) {
            $payload = $this->buildFdbContent($rows, $useSemicolon);
            if (file_put_contents($path, $payload) === false) {
                throw new \RuntimeException('Cannot write destination FDB file');
            }
        } else {
            $payload = $this->buildFdbDataLines($rows, $useSemicolon);
            if ($payload !== '' && file_put_contents($path, "\n" . $payload, FILE_APPEND) === false) {
                throw new \RuntimeException('Cannot append destination FDB file');
            }
        }

        if ($verbose) {
            $log[] = sprintf('Wrote %d rows to %s (%s)', count($rows), $fileBase, $rewrite ? 'rewrite' : 'append');
        }

        return [
            'affected' => count($rows),
            'imported' => count($rows),
            'skipped' => 0,
            'destination_file' => $fileBase,
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function buildFdbContent(array $rows, bool $useSemicolon): string
    {
        if ($rows === []) {
            return '';
        }

        $separator = $useSemicolon ? ';' : self::LEGACY_SEPARATOR;
        $columns = array_keys($rows[0]);
        $header = implode($separator, array_map(
            fn (string $column): string => $this->makeCsvHappy(strtoupper($column), $separator),
            $columns,
        ));

        $lines = [$header];
        if (!$useSemicolon) {
            $lines[] = implode($separator, array_fill(0, count($columns), '0'));
        }

        foreach ($rows as $row) {
            $parts = [];
            foreach ($columns as $column) {
                $parts[] = $this->makeCsvHappy((string) ($row[$column] ?? ''), $separator);
            }
            $lines[] = implode($separator, $parts);
        }

        return implode("\n", $lines);
    }

    /** @param list<array<string, mixed>> $rows */
    private function buildFdbDataLines(array $rows, bool $useSemicolon): string
    {
        if ($rows === []) {
            return '';
        }

        $separator = $useSemicolon ? ';' : self::LEGACY_SEPARATOR;
        $columns = array_keys($rows[0]);
        $lines = [];
        foreach ($rows as $row) {
            $parts = [];
            foreach ($columns as $column) {
                $parts[] = $this->makeCsvHappy((string) ($row[$column] ?? ''), $separator);
            }
            $lines[] = implode($separator, $parts);
        }

        return implode("\n", $lines);
    }

    /** @return array{headers: list<string>, rows: list<array<string, string>>} */
    public function parseFdbContent(string $content, bool $useSemicolon = false): array
    {
        $separator = $useSemicolon ? ';' : self::LEGACY_SEPARATOR;
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\r|\n/', rtrim($content)) ?: [];
        if ($lines === [] || trim($lines[0]) === '') {
            throw new \InvalidArgumentException('FDB file header is required');
        }

        $headers = array_map(
            fn (string $field): string => $this->fieldFix($field),
            $this->splitFdbLine($lines[0], $separator),
        );
        $headers = array_values(array_filter($headers, static fn (string $h): bool => $h !== ''));
        if ($headers === []) {
            throw new \InvalidArgumentException('FDB file header is required');
        }

        $startRow = 1;
        if (!$useSemicolon && isset($lines[1]) && $this->isPlevelsLine($lines[1], $separator, count($headers))) {
            $startRow = 2;
        }

        $rows = [];
        for ($i = $startRow, $count = count($lines); $i < $count; $i++) {
            if (trim($lines[$i]) === '') {
                continue;
            }

            $values = $this->splitFdbLine($lines[$i], $separator);
            if (count($values) !== count($headers)) {
                throw new \InvalidArgumentException('Column count mismatch on line ' . ($i + 1));
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $this->unquoteFdbValue($values[$index] ?? '');
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function normalizeEngine(string $engine): string
    {
        $engine = strtolower(trim($engine));

        return $engine === 'mysql' ? 'mysql' : 'fdb';
    }

    private function direction(string $sourceEngine, string $destEngine): string
    {
        if ($sourceEngine === 'fdb' && $destEngine === 'mysql') {
            return 'fdb_to_mysql';
        }
        if ($sourceEngine === 'mysql' && $destEngine === 'fdb') {
            return 'mysql_to_fdb';
        }

        throw new \InvalidArgumentException('Unsupported conversion direction');
    }

    /** @return array<string, mixed> */
    private function tableSummary(array $table, string $engine): array
    {
        return [
            'id' => (int) ($table['id'] ?? 0),
            'visual_name' => (string) ($table['visual_name'] ?? ''),
            'engine' => $engine,
            'file_base' => (string) ($table['file_base'] ?? ''),
            'mysql_table' => (string) ($table['mysql_table'] ?? ''),
        ];
    }

    /** @return array<string, true> */
    private function columnNames(Connection $conn, string $mysqlTable): array
    {
        $allowed = [];
        foreach ($conn->createSchemaManager()->listTableColumns($mysqlTable) as $column) {
            $allowed[$column->getName()] = true;
        }

        return $allowed;
    }

    private function requireMysqlTable(array $table): string
    {
        if ($this->normalizeEngine((string) ($table['engine'] ?? 'mysql')) !== 'mysql') {
            throw new \InvalidArgumentException('Expected mysql table metadata');
        }

        $mysqlTable = (string) ($table['mysql_table'] ?? '');
        if ($mysqlTable === '' || !preg_match('/^[A-Za-z0-9_]+$/', $mysqlTable)) {
            throw new \InvalidArgumentException('Invalid mysql_table name');
        }

        return $mysqlTable;
    }

    /** @return array<string, mixed> */
    private function requireTable(int $tableId): array
    {
        $table = $this->dbdata->get($tableId);
        if ($table === null) {
            throw new \InvalidArgumentException('Unknown table id: ' . $tableId);
        }

        return $table;
    }

    private function dataPath(string $fileBase): string
    {
        $fileBase = str_replace(['/', '\\', "\0"], '', $fileBase);

        return $this->projectRoot . '/_data/' . $fileBase;
    }

    /** @return list<string> */
    private function splitFdbLine(string $line, string $separator): array
    {
        return explode($separator, rtrim($line, "\r\n"));
    }

    private function isPlevelsLine(string $line, string $separator, int $expectedColumns): bool
    {
        $parts = $this->splitFdbLine($line, $separator);
        if (count($parts) !== $expectedColumns) {
            return false;
        }

        foreach ($parts as $part) {
            if (!preg_match('/^\d+$/', trim($part))) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, true> $allowed */
    private function resolveColumnName(string $header, array $allowed): ?string
    {
        if ($header === '') {
            return null;
        }

        if (isset($allowed[$header])) {
            return $header;
        }

        $lower = strtolower($header);
        foreach (array_keys($allowed) as $column) {
            if (strtolower($column) === $lower) {
                return $column;
            }
        }

        return null;
    }

    private function fieldFix(string $line): string
    {
        $line = preg_replace('/\s+/', '', $line) ?? '';
        $field = '';
        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            if (preg_match('/^[_a-z0-9-]+$/i', $char)) {
                $field .= $char;
            }
        }

        return $field;
    }

    private function makeCsvHappy(string $value, string $separator): string
    {
        $value = str_replace(["\n", "\r"], '', $value);
        if (str_contains($value, $separator)) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    private function unquoteFdbValue(string $value): string
    {
        $value = trim($value);
        if ($value !== ''
            && $value[0] === $value[strlen($value) - 1]
            && ($value[0] === "'" || $value[0] === '"')
        ) {
            return substr($value, 1, -1);
        }

        if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            return str_replace('""', '"', substr($value, 1, -1));
        }

        return $value;
    }
}
