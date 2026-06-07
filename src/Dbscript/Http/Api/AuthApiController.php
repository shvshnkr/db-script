<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Auth\JwtAuthService;
use Dbscript\Config\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthApiController
{
    public function __construct(
        private readonly JwtAuthService $auth,
        private readonly UserRepository $users,
    ) {
    }

    public function login(Request $request, bool $secure): Response
    {
        $payload = $this->jsonBody($request);
        $login = trim((string) ($payload['login'] ?? $request->request->get('login', '')));
        $password = (string) ($payload['password'] ?? $request->request->get('password', ''));

        if ($login === '' || $password === '') {
            return ApiResponse::fail([
                'code' => 'validation',
                'message' => 'Login and password are required.',
            ], 422);
        }

        $token = $this->auth->authenticate($login, $password);
        if ($token === null) {
            return ApiResponse::fail([
                'code' => 'invalid_credentials',
                'message' => 'Invalid login or password.',
            ], 401);
        }

        $user = $this->users->find($login);
        $response = ApiResponse::ok([
            'token' => $token->toString(),
            'user' => [
                'login' => $login,
                'role' => (string) ($user['role'] ?? 'editor'),
            ],
        ]);

        return $this->auth->attachCookie($response, $token, $secure);
    }

    public function logout(Request $request, bool $secure): Response
    {
        $response = ApiResponse::ok(['logged_out' => true]);

        return $this->auth->clearCookie($response, $secure);
    }

    /** @param array{login: string, role: string} $claims */
    public function me(array $claims): Response
    {
        $user = $this->users->find($claims['login']);

        return ApiResponse::ok([
            'login' => $claims['login'],
            'role' => $claims['role'],
            'active' => $user !== null && ($user['active'] ?? true),
        ]);
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
