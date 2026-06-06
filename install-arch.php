<?php
declare(strict_types=1);

/**
 * arch-modern installer — writes _conf/*.toml only (no csv, no dbscore.lib).
 */

require __DIR__ . '/vendor/autoload.php';

use Dbscript\Application;
use Dbscript\Config\UserRepository;
use Dbscript\Install\InstallWriter;

header('Content-Type: text/html; charset=UTF-8');

$root = __DIR__;
$app = Application::boot($root);

if ($app->isInstalled() && is_file($root . '/_conf/property.toml')) {
    echo '<p>Dbscript arch-modern already installed. Remove install-arch.php.</p>';
    exit;
}

$step = (int) ($_POST['step'] ?? $_GET['step'] ?? 0);
$lang = (string) ($_POST['lang'] ?? 'english');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dbscript arch-modern install</title>
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body>
<h1>Dbscript 4 — arch-modern install</h1>
<?php

if ($step === 0) {
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        echo '<p>Fatal: PHP 8.2+ required.</p></body></html>';
        exit;
    }
    if (!extension_loaded('mysqli')) {
        echo '<p>Fatal: mysqli extension required.</p></body></html>';
        exit;
    }
    ?>
    <form method="post">
        <input type="hidden" name="step" value="1">
        <label>Language:
            <select name="lang">
                <option value="english">english</option>
                <option value="russian">русский</option>
            </select>
        </label>
        <button type="submit">Next</button>
    </form>
    <?php
} elseif ($step === 1) {
    ?>
    <form method="post">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="lang" value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
        <p>MySQL connection</p>
        <label>Host <input name="mysql_host" value="127.0.0.1"></label><br>
        <label>User <input name="mysql_user" value="root"></label><br>
        <label>Password <input type="password" name="mysql_pass" value=""></label><br>
        <button type="submit">Next</button>
    </form>
    <?php
} elseif ($step === 2) {
    $mysqlHost = (string) ($_POST['mysql_host'] ?? '127.0.0.1');
    $mysqlUser = (string) ($_POST['mysql_user'] ?? 'root');
    $mysqlPass = (string) ($_POST['mysql_pass'] ?? '');
    ?>
    <form method="post">
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="lang" value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="mysql_host" value="<?= htmlspecialchars($mysqlHost, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="mysql_user" value="<?= htmlspecialchars($mysqlUser, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="mysql_pass" value="<?= htmlspecialchars($mysqlPass, ENT_QUOTES, 'UTF-8') ?>">
        <p>Admin account</p>
        <label>Login <input name="admin_login" value="admin" required></label><br>
        <label>Password <input type="password" name="admin_pass" required minlength="8"></label><br>
        <button type="submit">Install</button>
    </form>
    <?php
} elseif ($step === 3) {
    $mysqlHost = (string) ($_POST['mysql_host'] ?? '127.0.0.1');
    $mysqlUser = (string) ($_POST['mysql_user'] ?? 'root');
    $mysqlPass = (string) ($_POST['mysql_pass'] ?? '');
    $adminLogin = (string) ($_POST['admin_login'] ?? 'admin');
    $adminPass = (string) ($_POST['admin_pass'] ?? '');

    if (strlen($adminPass) < 8) {
        echo '<p>Password must be at least 8 characters.</p></body></html>';
        exit;
    }

    if (!is_dir($root . '/_conf') && !mkdir($root . '/_conf', 0775, true)) {
        echo '<p>Cannot create _conf directory.</p></body></html>';
        exit;
    }

    $config = $app->config();
    $users = new UserRepository($config);
    (new InstallWriter($config, $users))->writeFreshInstall(
        $adminLogin,
        $adminPass,
        $mysqlHost,
        $mysqlUser,
        $mysqlPass,
        $lang,
    );

    // Persist mysql creds in sitedata (install writer defaults)
    $site = $config->load('sitedata');
    $site['mysql'] = ['login' => $mysqlUser, 'password' => $mysqlPass, 'host' => $mysqlHost];
    $config->save('sitedata', $site);

    echo '<p>Installation complete.</p>';
    echo '<p><a href="login-arch.php">Go to login</a></p>';
    echo '<p>Remove install-arch.php from the server.</p>';
} else {
    echo '<p>Unknown step.</p>';
}

?>
</body>
</html>
