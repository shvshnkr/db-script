<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\ReaderService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReaderApiController
{
    public function __construct(
        private readonly ReaderService $reader,
    ) {
    }

    public function search(string $tableId, Request $request): Response
    {
        try {
            $result = $this->reader->search($tableId, [
                'page' => $request->query->get('page', 1),
                'limit' => $request->query->get('limit', 50),
                'q' => $request->query->get('q', ''),
            ]);

            return ApiResponse::ok($result);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function viewRow(string $tableId, string $pk): Response
    {
        try {
            return ApiResponse::ok($this->reader->viewRow($tableId, $pk));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function exportCsv(string $tableId, Request $request): Response
    {
        try {
            $csv = $this->reader->export($tableId, [
                'q' => $request->query->get('q', ''),
                'limit' => $request->query->get('limit', 5000),
            ]);

            return new Response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="table-' . $tableId . '.csv"',
            ]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }
}
