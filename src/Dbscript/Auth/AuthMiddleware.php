<?php
declare(strict_types=1);

namespace Dbscript\Auth;

use Dbscript\Application;
use Dbscript\Config\UserRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Validates dbs_jwt on protected pages; redirects to login-arch.php when missing.
 */
final class AuthMiddleware
{
    public static function requireLogin(?string $redirect = 'login-arch.php'): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $app = Application::get();
        $config = $app->config();
        $users = new UserRepository($config);
        $auth = new JwtAuthService($config, $users);
        $request = Request::createFromGlobals();
        $claims = $auth->readFromRequest($request);

        if ($claims === null) {
            header('Location: ' . $redirect);
            exit;
        }

        return $claims;
    }
}
