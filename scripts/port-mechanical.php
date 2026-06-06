#!/usr/bin/env php
<?php
/**
 * Mechanical PHP 8 port helpers for Dbscript4.
 * Run from repo root: php scripts/port-mechanical.php
 */
$root = dirname(__DIR__);
$extensions = ['php', 'lib'];
$files = [];
foreach ($extensions as $ext) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)) {
            continue;
        }
        if ($file->getExtension() === $ext || ($ext === 'lib' && $file->getFilename() === 'dbscore.lib')) {
            $files[] = $path;
        }
    }
}
$files = array_unique($files);

function port_file(string $path): bool {
    $original = file_get_contents($path);
    $content = $original;

    // import_request_variables("PGC","") / ("PG","")
    $content = preg_replace(
        '/@?\s*import_request_variables\s*\(\s*"([^"]+)"\s*,\s*"([^"]*)"\s*\)\s*;/',
        'extract(array_merge($_GET, $_POST, $_COOKIE), EXTR_SKIP);',
        $content
    );

    // each($_POST) while loops -> foreach
    $content = preg_replace(
        '/while\s*\(\s*list\s*\(\s*\$(\w+)\s*,\s*\$(\w+)\s*\)\s*=\s*each\s*\(\s*\$_(POST|GET|REQUEST)\s*\)\s*\)\s*:\s*\R\s*;/',
        'foreach ($_$3 as $$1 => $$2) :',
        $content
    );

    // split($sep, $str) -> explode($sep, $str)  (PHP split removed; not jQuery)
    $content = preg_replace(
        '/\bsplit\s*\(\s*([^,]+)\s*,\s*([^)]+)\)/',
        'explode($1, $2)',
        $content
    );

    // implode($array, $glue) -> implode($glue, $array) when first arg is $variable
    $content = preg_replace_callback(
        '/implode\s*\(\s*(\$[a-zA-Z_][\w\[\]\'"$>-]*)\s*,\s*([^)]+)\)/',
        function ($m) {
            return 'implode(' . $m[2] . ', ' . $m[1] . ')';
        },
        $content
    );

    // get_magic_quotes_gpc blocks in quote_smart style
    $content = preg_replace(
        '/if\s*\(\s*get_magic_quotes_gpc\s*\(\s*\)\s*\)\s*\{\s*\R\s*\$value\s*=\s*stripslashes\s*\(\s*\$value\s*\)\s*;\s*\R\s*\}/',
        '',
        $content
    );
    $content = preg_replace(
        '/if\s*\(\s*\$debug\s*\)\s*echo\s*"mqgpc="\.get_magic_quotes_gpc\(\)\s*;\s*\R\s*if\s*\(\s*\$debug\s*\)\s*echo\s*"mqrtm="\.get_magic_quotes_runtime\(\)\s*;\s*\R/',
        '',
        $content
    );
    $content = preg_replace('/ini_set\s*\(\s*[\'"]magic_quotes_gpc[\'"]\s*,\s*[\'"]0[\'"]\s*\)\s*;\s*\R/', '', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        return true;
    }
    return false;
}

$changed = 0;
foreach ($files as $file) {
    if (port_file($file)) {
        echo "Updated: " . str_replace($root . DIRECTORY_SEPARATOR, '', $file) . PHP_EOL;
        $changed++;
    }
}
echo "Done. $changed file(s) modified." . PHP_EOL;
