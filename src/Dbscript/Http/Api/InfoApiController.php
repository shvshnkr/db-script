<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\InfoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class InfoApiController
{
    public function __construct(
        private readonly InfoService $info,
    ) {
    }

    public function show(string $slug, Request $request, ?array $claims): Response
    {
        $normalized = ltrim(strtolower($slug), '.');
        $requiresAuth = in_array($normalized, ['info'], true);

        if ($requiresAuth && $claims === null) {
            return ApiResponse::unauthorized();
        }

        try {
            return ApiResponse::ok($this->info->page(
                $slug,
                $claims,
                $request->getClientIp(),
            ));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        } catch (\RuntimeException $e) {
            return ApiResponse::forbidden('notrights', $e->getMessage());
        }
    }

    public function list(): Response
    {
        return ApiResponse::ok(['pages' => $this->info->allowedSlugs()]);
    }
}
