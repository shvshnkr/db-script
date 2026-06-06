#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Dev helper: write fresh arch-modern TOML configs into _conf/.
 * Usage: php scripts/arch-modern-install-dev.php [admin_password]
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Config\UserRepository;
use Dbscript\Install\InstallWriter;

$pass = $argv[1] ?? 'admin';
$app = Application::boot(dirname(__DIR__));
$config = $app->config();
$users = new UserRepository($config);

(new InstallWriter($config, $users))->writeFreshInstall('admin', $pass);

echo "arch-modern: wrote _conf/*.toml (admin / {$pass})\n";
