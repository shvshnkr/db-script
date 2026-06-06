<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;
use Dbscript\Auth\AuthMiddleware;
use Dbscript\Auth\JwtAuthService;
use Dbscript\Config\UserRepository;
use Dbscript\View\TwigFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class LoginController
{
    public function __construct(
        private readonly JwtAuthService $auth,
    ) {
    }

    public function handle(Application $app): void
    {
        $request = Request::createFromGlobals();
        $secure = $request->isSecure();
        $view = TwigFactory::create($app);

        if ($this->auth->readFromRequest($request) !== null) {
            header('Location: w-arch.php');
            exit;
        }

        $error = '';
        if ($request->isMethod('POST')) {
            $login = (string) $request->request->get('login', '');
            $password = (string) $request->request->get('password', '');
            $token = $this->auth->authenticate($login, $password);
            if ($token === null) {
                $error = 'Invalid login or password.';
            } else {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
                $_SESSION['dbs_current_user'] = $login;
                $response = new Response('', 302, ['Location' => 'w-arch.php']);
                $this->auth->attachCookie($response, $token, $secure);
                $response->send();
                exit;
            }
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $view->render('auth/login.html.twig', ['error' => $error]);
    }

    public static function fromApplication(Application $app): self
    {
        $config = $app->config();

        return new self(new JwtAuthService($config, new UserRepository($config)));
    }
}
