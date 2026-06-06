<?php
declare(strict_types=1);

/**
 * Single boot entry for arch-modern Dbscript.
 * Legacy entry points migrate here incrementally; until then dbscore.lib may still load.
 */

use Dbscript\Application;

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('DBSCRIPT_ROOT')) {
    define('DBSCRIPT_ROOT', __DIR__);
}

if (!defined('DBSCRIPT_CONF')) {
    define('DBSCRIPT_CONF', DBSCRIPT_ROOT . '/_conf');
}

if (!defined('DBSCRIPT_LANG')) {
    define('DBSCRIPT_LANG', DBSCRIPT_ROOT . '/_langdb');
}

return Application::boot(DBSCRIPT_ROOT);
