<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\FileManagerService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class FilesApiController
{
    public function __construct(
        private readonly FileManagerService $files,
    ) {
    }

    /** @param array{login: string, role: string} $claims */
    public function list(Request $request, array $claims): Response
    {
        $search = trim((string) $request->query->get('q', ''));

        return ApiResponse::ok($this->files->listFiles($claims, $search !== '' ? $search : null));
    }

    /** @param array{login: string, role: string} $claims */
    public function upload(Request $request, array $claims): Response
    {
        $upload = $request->files->get('file');
        if ($upload === null || !$upload->isValid()) {
            return ApiResponse::fail(['code' => 'validation', 'message' => 'File upload is required.'], 422);
        }

        try {
            $entry = $this->files->upload(
                (string) $upload->getClientOriginalName(),
                (string) $upload->getPathname(),
                $claims,
            );

            return ApiResponse::ok($entry, [], 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'validation', 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return ApiResponse::fail(['code' => 'upload_failed', 'message' => $e->getMessage()], 500);
        }
    }

    /** @param array{login: string, role: string} $claims */
    public function download(string $hash, array $claims): Response
    {
        try {
            $resolved = $this->files->resolveDownload($hash, $claims);
            $response = new BinaryFileResponse($resolved['path']);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                (string) $resolved['filename'],
            );
            $response->headers->set('Content-Type', (string) $resolved['mime']);

            return $response;
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'not_found', 'message' => $e->getMessage()], 404);
        } catch (\RuntimeException $e) {
            return ApiResponse::forbidden('notrights', $e->getMessage());
        }
    }

    /** @param array{login: string, role: string} $claims */
    public function delete(string $hash, Request $request, array $claims): Response
    {
        $payload = $this->jsonBody($request);
        $confirmHash = isset($payload['confirm_hash']) ? (string) $payload['confirm_hash'] : null;

        try {
            $deleted = $this->files->delete($hash, $confirmHash, $claims);

            return ApiResponse::ok(['deleted' => $deleted]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::fail(['code' => 'validation', 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return ApiResponse::forbidden('notrights', $e->getMessage());
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
