<?php
declare(strict_types=1);

namespace Dbscript\Service;

final class ReaderService
{
    /** @return array<string, mixed> */
    public function search(string $tableId, array $query): array
    {
        throw new \BadMethodCallException('ReaderService::search not implemented yet');
    }

    /** @return array<string, mixed> */
    public function viewRow(string $tableId, string $pk): array
    {
        throw new \BadMethodCallException('ReaderService::viewRow not implemented yet');
    }

    public function export(string $tableId, array $query, string $format = 'csv'): string
    {
        throw new \BadMethodCallException('ReaderService::export not implemented yet');
    }
}
