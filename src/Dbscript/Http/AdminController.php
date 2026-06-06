<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;
use Dbscript\Auth\AuthMiddleware;
use Dbscript\Config\UserRepository;
use Dbscript\View\TwigFactory;

final class AdminController
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    public function handle(Application $app): void
    {
        $claims = AuthMiddleware::requireLogin();
        if (($claims['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo 'Admin role required.';
            exit;
        }

        $view = TwigFactory::create($app);
        header('Content-Type: text/html; charset=UTF-8');
        echo $view->render('admin/index.html.twig', [
            'user' => $claims['login'],
            'role' => $claims['role'],
            'users' => $this->users->all(),
            'config_files' => ['property', 'users', 'secrets', 'sitedata', 'dbdata', 'styles', 'langset', 'denywords', 'files'],
        ]);
    }

    public static function fromApplication(Application $app): self
    {
        return new self(new UserRepository($app->config()));
    }
}
