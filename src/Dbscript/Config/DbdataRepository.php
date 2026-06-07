<?php
declare(strict_types=1);

namespace Dbscript\Config;

final class DbdataRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function tables(): array
    {
        if (!$this->config->exists('dbdata')) {
            return [];
        }

        $data = $this->config->load('dbdata');
        $tables = $data['tables'] ?? [];

        return is_array($tables) ? array_values($tables) : [];
    }

    public function get(int $tableId): ?array
    {
        foreach ($this->tables() as $table) {
            if ((int) ($table['id'] ?? -1) === $tableId) {
                return $table;
            }
        }

        return null;
    }

    /** Row shape compatible with legacy dbdata consumers. */
    public function toLegacyRow(array $table): array
    {
        return [
            0 => (string) ($table['file_base'] ?? ''),
            1 => (string) ($table['visual_name'] ?? ''),
            2 => (string) ($table['images'] ?? ''),
            3 => (string) ($table['scr_type'] ?? ''),
            4 => (string) ($table['mode_category'] ?? ''),
            5 => (string) ($table['mysql_table'] ?? ''),
            6 => (string) ($table['mysql_host'] ?? '127.0.0.1'),
            7 => (string) ($table['category_type'] ?? ''),
            8 => (string) ($table['image_column'] ?? ''),
            9 => (string) ($table['mysql_database'] ?? ''),
            10 => (string) ($table['mode_name'] ?? ''),
            11 => (string) ($table['mode_code'] ?? ''),
            12 => (string) ($table['engine'] ?? 'mysql'),
            13 => (string) ($table['write_acl'] ?? ''),
            14 => (string) ($table['required_acl'] ?? ''),
            15 => (string) ($table['require_virtual_id'] ?? ''),
            16 => (string) ($table['column_filter'] ?? ''),
        ];
    }

    /** @return array{0: list<string>, 1: list<string>, 2: list<array<int, mixed>>, 3: int} */
    public function toLegacyBundle(): array
    {
        $header = [
            'file_base', 'visual_name', 'images', 'scr_type', 'mode_category',
            'mysql_table', 'mysql_host', 'category_type', 'image_column', 'mysql_database',
            'mode_name', 'mode_code', 'engine', 'write_acl', 'required_acl',
            'require_virtual_id', 'column_filter',
        ];
        $plevel = array_fill(0, count($header), 'd');
        $rows = [];
        foreach ($this->tables() as $table) {
            $rows[] = $this->toLegacyRow($table);
        }

        return [$header, $plevel, $rows, count($rows)];
    }
}
