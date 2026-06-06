<?php
declare(strict_types=1);

/**
 * arch-modern login — JWT cookie dbs_jwt (no dbsa, no hashgen).
 */

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

if (!$app->config()->exists('property')) {
    header('Location: install-arch.php');
    exit;
}

$config = $app->config();
$users = new UserRepository($config);
$auth = new JwtAuthService($config, $users);
$request = Request::createFromGlobals();
$secure = $request->isSecure();

if ($auth->readFromRequest($request) !== null) {
        header('Location: w-arch.php');
    exit;
}

$error = '';
if ($request->isMethod('POST')) {
    $login = (string) $request->request->get('login', '');
    $password = (string) $request->request->get('password', '');
    $token = $auth->authenticate($login, $password);
    if ($token === null) {
        $error = 'Invalid login or password.';
    } else {
        $response = new Response('', 302, ['Location' => 'w-arch.php']);
        $auth->attachCookie($response, $token, $secure);
        $response->send();
        exit;
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dbscript login</title>
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body>
<h1>Dbscript — arch-modern login</h1>
<?php if ($error !== ''): ?>
<p style="color:crimson"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post">
    <label>Login <input name="login" required autocomplete="username"></label><br>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label><br>
    <button type="submit">Sign in</button>
</form>
</body>
</html>
