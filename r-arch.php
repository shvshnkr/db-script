<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Http\ReaderController;

$app = Application::boot(__DIR__);
ReaderController::fromApplication($app)->handle();
