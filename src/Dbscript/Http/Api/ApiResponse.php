<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    /** @param mixed $data */
    public static function ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => null,
        ], $status, [], false);
    }

    /** @param list<array{code: string, message: string}>|array{code: string, message: string} $errors */
    public static function fail(array $errors, int $status = 400): JsonResponse
    {
        if (isset($errors['code'])) {
            $errors = [$errors];
        }

        return new JsonResponse([
            'data' => null,
            'meta' => (object) [],
            'errors' => $errors,
        ], $status, [], false);
    }

    public static function unauthorized(string $code = 'unauthorized', string $message = 'Authentication required'): JsonResponse
    {
        return self::fail(['code' => $code, 'message' => $message], 401);
    }

    public static function forbidden(string $code = 'notrights', string $message = 'Permission denied'): JsonResponse
    {
        return self::fail(['code' => $code, 'message' => $message], 403);
    }
}
