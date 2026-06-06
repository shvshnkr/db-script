#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpCollective\Toml\Toml;

$file = $argv[1] ?? '_langdb/english.toml';
try {
    Toml::decodeFile($file);
    echo "OK $file\n";
} catch (Throwable $e) {
    echo "FAIL $file: " . $e->getMessage() . "\n";
    exit(1);
}
