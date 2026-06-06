<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Http\AdminController;

$app = Application::boot(__DIR__);
AdminController::fromApplication($app)->handle($app);
