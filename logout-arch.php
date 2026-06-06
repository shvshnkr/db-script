<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Auth\JwtAuthService;
use Dbscript\Config\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$app = Application::boot(__DIR__);
$config = $app->config();
$auth = new JwtAuthService($config, new UserRepository($config));
$request = Request::createFromGlobals();

unset($_SESSION['dbs_current_user'], $_SESSION['dbs_csrf']);

$response = new Response('', 302, ['Location' => 'login-arch.php']);
$auth->clearCookie($response, $request->isSecure());
$response->send();
