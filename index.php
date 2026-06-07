<?php
declare(strict_types=1);

$q = (string) ($_SERVER['QUERY_STRING'] ?? '');
$first = $q !== '' ? $q[0] : '';

$spaPath = '/app/';
if ($first === 'w') {
    $spaPath = '/app/editor';
} elseif ($first === 'r' || $first === 't') {
    $spaPath = '/app/reader';
} elseif ($first === 'f' || $first === 'c' || $first === 'i') {
    $spaPath = '/app/files';
} elseif ($first === 'a' || $first === 'u') {
    header('Location: /admin-arch.php' . ($q !== '' ? '?' . $q : ''));
    exit;
}

if ($q !== '' && str_contains($q, '=')) {
    parse_str($q, $params);
    if (isset($params['viewid']) || isset($params['vID'])) {
        $view = (string) ($params['viewid'] ?? $params['vID'] ?? '');
        $view = ltrim(strtolower($view), '.');
        if (in_array($view, ['ver', 'info', 'author', 'help'], true)) {
            header('Location: /app/info/' . rawurlencode($view));
            exit;
        }
    }
    if (isset($params['tbl']) && ($first === 'r' || $first === 't' || $first === 'w')) {
        $base = $first === 'w' ? '/app/editor/' : '/app/reader/';
        header('Location: ' . $base . rawurlencode((string) $params['tbl']));
        exit;
    }
}

header('Location: ' . $spaPath);
exit;
