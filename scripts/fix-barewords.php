#!/usr/bin/env php
<?php
/**
 * Quote bareword message keys in cmsg/lprint/rmsg/submitkey for PHP 8.
 * Usage: php scripts/fix-barewords.php [file ...]
 */
$root = dirname(__DIR__);
$files = array_slice($argv, 1);
if (!$files) {
    $files = ['admin.php', 'w.php'];
}
$funcs = ['cmsg', 'lprint', 'rmsg'];

foreach ($files as $rel) {
    $path = str_starts_with($rel, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:#', $rel)
        ? $rel
        : $root . DIRECTORY_SEPARATOR . $rel;
    if (!is_file($path)) {
        fwrite(STDERR, "skip missing: $rel\n");
        continue;
    }
    $content = file_get_contents($path);
    $original = $content;

    foreach ($funcs as $fn) {
        $content = preg_replace(
            '/\b' . preg_quote($fn, '/') . '\s*\(\s*([A-Z][A-Z0-9_]*)\s*\)/',
            $fn . '("$1")',
            $content
        );
    }

    // submitkey("name", BAREWORD)
    $content = preg_replace(
        '/\bsubmitkey\s*\(\s*"([^"]+)"\s*,\s*([A-Z][A-Z0-9_]*)\s*\)/',
        'submitkey("$1", "$2")',
        $content
    );
    // submitkey(name, BAREWORD) — rare first arg without quotes
    $content = preg_replace(
        '/\bsubmitkey\s*\(\s*([A-Z][A-Z0-9_]*)\s*,\s*([A-Z][A-Z0-9_]*)\s*\)/',
        'submitkey("$1", "$2")',
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "fixed: $rel\n";
    } else {
        echo "unchanged: $rel\n";
    }
}
