<?php
declare(strict_types=1);

namespace Dbscript\Service;

/**
 * API-ready editor contract — see ARCHITECTURE.md § EditorService.
 * Implementation fills in during arch-modern phase 3.
 */
final class EditorService
{
    /** @return list<array<string, mixed>> */
    public function listTables(): array
    {
        throw new \BadMethodCallException('EditorService::listTables not implemented yet');
    }

    /** @return array<string, mixed> */
    public function listRows(string $tableId, array $query = []): array
    {
        throw new \BadMethodCallException('EditorService::listRows not implemented yet');
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
        throw new \BadMethodCallException('EditorService::getColumnMeta not implemented yet');
    }
}
