#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Convert _langdb/*.cfg (CP1251) → UTF-8 *.json for arch-modern MessageCatalog.
 * Usage: php scripts/convert-lang-cfg-to-toml.php [english|russian|all]
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Dbscript\I18n\MessageCatalog;

$langDir = dirname(__DIR__) . '/_langdb';
$target = $argv[1] ?? 'all';

$languages = $target === 'all'
    ? array_map(static fn ($f) => basename($f, '.cfg'), glob($langDir . '/*.cfg') ?: [])
    : [$target];

foreach ($languages as $lang) {
    $cfg = $langDir . '/' . $lang . '.cfg';
    if (!is_file($cfg)) {
        fwrite(STDERR, "skip: no cfg for {$lang}\n");
        continue;
    }

    $messages = MessageCatalog::parseLegacyCfg($cfg);
    $outPath = $langDir . '/' . $lang . '.json';
    $payload = [
        'meta' => ['name' => $lang, 'charset' => 'utf-8', 'source' => $lang . '.cfg'],
        'messages' => $messages,
    ];
    file_put_contents(
        $outPath,
        json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE) . "\n"
    );
    echo "converted {$lang}: " . count($messages) . " keys → {$outPath}\n";
}
