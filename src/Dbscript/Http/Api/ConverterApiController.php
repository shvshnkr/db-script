<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\ImportExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConverterApiController
{
    public function __construct(
        private readonly ImportExportService $converter,
    ) {
    }

    public function listTables(): Response
    {
        return ApiResponse::ok($this->converter->listTables());
    }

    public function preview(Request $request): Response
    {
        $payload = $this->jsonBody($request);
        $sourceId = (int) ($payload['source_id'] ?? 0);
        $destId = (int) ($payload['destination_id'] ?? 0);
        if ($sourceId <= 0 || $destId <= 0) {
            return ApiResponse::fail([
                'code' => 'validation',
                'message' => 'source_id and destination_id are required.',
            ], 422);
        }

        try {
            return ApiResponse::ok($this->converter->preview($sourceId, $destId));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'validation', 'message' => $e->getMessage()], 422);
        }
    }

    public function convert(Request $request): Response
    {
        $payload = $this->jsonBody($request);
        $sourceId = (int) ($payload['source_id'] ?? 0);
        $destId = (int) ($payload['destination_id'] ?? 0);
        if ($sourceId <= 0 || $destId <= 0) {
            return ApiResponse::fail([
                'code' => 'validation',
                'message' => 'source_id and destination_id are required.',
            ], 422);
        }

        $options = [
            'rewrite' => (bool) ($payload['rewrite'] ?? false),
            'use_semicolon' => (bool) ($payload['use_semicolon'] ?? false),
            'unique_id' => (bool) ($payload['unique_id'] ?? false),
            'verbose' => (bool) ($payload['verbose'] ?? true),
        ];

        try {
            return ApiResponse::ok($this->converter->convert($sourceId, $destId, $options));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'validation', 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return ApiResponse::fail(['code' => 'converter_error', 'message' => $e->getMessage()], 500);
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
