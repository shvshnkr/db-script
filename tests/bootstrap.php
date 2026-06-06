<?php
declare(strict_types=1);

chdir(dirname(__DIR__));

$_SERVER['SERVER_SOFTWARE'] = 'Apache';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
$_SERVER['HTTP_REFERER'] = '';
$_SERVER['PHP_AUTH_USER'] = '';

$coreloadskip = true;
$installermode = true;
$dbdataskip = true;
$nomnu = 1;

require_once dirname(__DIR__) . '/dbscore.lib';
