<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Http\EditorController;

$app = Application::boot(__DIR__);
EditorController::fromApplication($app)->handle();
