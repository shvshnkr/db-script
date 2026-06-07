<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\EditorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class EditorApiController
{
    public function __construct(
        private readonly EditorService $editor,
    ) {
    }

    public function listTables(): Response
    {
        return ApiResponse::ok($this->editor->listTables());
    }

    public function columnMeta(string $tableId): Response
    {
        try {
            return ApiResponse::ok($this->editor->getColumnMeta($tableId));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function listRows(string $tableId, Request $request): Response
    {
        try {
            $query = [
                'page' => $request->query->get('page', 1),
                'limit' => $request->query->get('limit', 50),
            ];

            return ApiResponse::ok($this->editor->listRows($tableId, $query));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function getRow(string $tableId, string $pk): Response
    {
        try {
            return ApiResponse::ok($this->editor->getRow($tableId, $pk));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function createRow(string $tableId, Request $request): Response
    {
        $data = $this->jsonBody($request);
        if ($data === []) {
            return ApiResponse::fail(['code' => 'validation', 'message' => 'Row data is required.'], 422);
        }

        try {
            $pk = $this->editor->createRow($tableId, $data);

            return ApiResponse::ok(['pk' => $pk], [], 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'validation', 'message' => $e->getMessage()], 422);
        }
    }

    public function updateRow(string $tableId, string $pk, Request $request): Response
    {
        $data = $this->jsonBody($request);
        if ($data === []) {
            return ApiResponse::fail(['code' => 'validation', 'message' => 'Row data is required.'], 422);
        }

        try {
            $this->editor->updateRow($tableId, $pk, $data);

            return ApiResponse::ok(['pk' => $pk, 'updated' => true]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function deleteRows(string $tableId, Request $request): Response
    {
        $payload = $this->jsonBody($request);
        $pks = $payload['pks'] ?? [];
        if (!is_array($pks) || $pks === []) {
            return ApiResponse::fail(['code' => 'validation', 'message' => 'pks array is required.'], 422);
        }

        $pks = array_values(array_map(static fn ($pk): string => (string) $pk, $pks));

        try {
            $deleted = $this->editor->deleteRows($tableId, $pks);

            return ApiResponse::ok(['deleted' => $deleted]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    /** @return array<string, mixed> */
    private function jsonBody(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
