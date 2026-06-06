<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Http\LoginController;

$app = Application::boot(__DIR__);

if (!$app->config()->exists('property')) {
    header('Location: install-arch.php');
    exit;
}

LoginController::fromApplication($app)->handle($app);
