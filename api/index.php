<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Dbscript\Http\Api\ApiKernel;

$response = ApiKernel::fromGlobals();
$response->send();
