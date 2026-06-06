#!/usr/bin/env php
<?php
/** Fix deprecated $var{idx} → $var[idx] for PHP 8.2 */
$root = dirname(__DIR__);
$files = ['filemgr.php', 'nedit.php'];
$pat = '/\$([a-zA-Z_][a-zA-Z0-9_]*)\{([^}]+)\}/';
foreach ($files as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . $rel;
    if (!is_file($path)) {
        fwrite(STDERR, "skip missing: $rel\n");
        continue;
    }
    $c = file_get_contents($path);
    $n = preg_replace($pat, '$$1[$2]', $c);
    if ($n !== $c) {
        file_put_contents($path, $n);
        echo "fixed: $rel\n";
    } else {
        echo "unchanged: $rel\n";
    }
}
