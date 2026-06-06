#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Seed demo table + dbdata.toml for arch-modern editor in Docker dev.
 * Usage: php scripts/arch-modern-seed-demo.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Config\TomlLoader;

$root = dirname(__DIR__);
$app = Application::boot($root);
$config = $app->config();

$host = getenv('DBSCRIPT_MYSQL_HOST') ?: 'db';
$user = getenv('DBSCRIPT_MYSQL_USER') ?: 'root';
$pass = getenv('DBSCRIPT_MYSQL_PASS') ?: 'dbscript_root';
$db = getenv('DBSCRIPT_MYSQL_DB') ?: 'dbscript_test';

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db),
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$pdo->exec('CREATE TABLE IF NOT EXISTS demo_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(128) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$pdo->exec("INSERT IGNORE INTO demo_items (id, title) VALUES (1, 'Hello arch-modern'), (2, 'Dbscript 2026')");

$site = $config->exists('sitedata') ? $config->load('sitedata') : [];
$site['mysql'] = ['host' => $host, 'login' => $user, 'password' => $pass, 'database' => $db];
$config->save('sitedata', $site);

$config->save('dbdata', [
    'tables' => [[
        'id' => 1,
        'file_base' => '',
        'visual_name' => 'Demo items',
        'mysql_table' => 'demo_items',
        'mysql_host' => $host,
        'mysql_database' => $db,
        'engine' => 'mysql',
        'write_acl' => 'SU',
    ]],
]);

echo "arch-modern: seeded demo_items in {$db}@{$host}\n";
