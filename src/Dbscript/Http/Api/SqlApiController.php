<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\EditorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SqlApiController
{
    public function __construct(
        private readonly EditorService $editor,
    ) {
    }

    /** @param array{login: string, role: string} $claims */
    public function execute(Request $request, array $claims): Response
    {
        $payload = $this->jsonBody($request);
        $query = trim((string) ($payload['query'] ?? ''));
        if ($query === '') {
            return ApiResponse::fail(['code' => 'validation', 'message' => 'SQL query is required.'], 422);
        }

        try {
            $result = $this->editor->executeSql($query, [
                'table_id' => (string) ($payload['table_id'] ?? ''),
                'user_level' => $this->userLevel($claims),
                'bypass_denywords' => ($claims['role'] ?? '') === 'admin',
            ]);

            return ApiResponse::ok($result);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'validation', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return ApiResponse::fail(['code' => 'sql_error', 'message' => $e->getMessage()], 400);
        }
    }

    /** @param array{login: string, role: string} $claims */
    private function userLevel(array $claims): int
    {
        return ($claims['role'] ?? '') === 'admin' ? 10 : 4;
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
