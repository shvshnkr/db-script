<?php
/**
 * Idempotent: ensure LIMITED/LIMITED user exists with editor+admin disabled.
 * CLI: php scripts/smoke-setup-limited-user.php
 */
declare(strict_types=1);

$coreloadskip = true;
$dbdataskip = true;
$nomnu = 1;

chdir(dirname(__DIR__));
require_once 'dbscore.lib';

$username = 'LIMITED';
$password = 'LIMITED';

$filbas = '_conf/gmdata.cfg';
if (!is_file($filbas)) {
    fwrite(STDERR, "FAIL: $filbas not found (run smoke-install first)\n");
    exit(1);
}

$gmdata = csvopen($filbas, 'r', '0');
$data = readfullcsv($gmdata, 'new');
fclose($gmdata);

if (!is_array($prauth ?? null)) {
    fwrite(STDERR, "FAIL: gmdata parse error\n");
    exit(1);
}

for ($i = 1; $i < ($prauthcnt ?? count($prauth)); $i++) {
    if (strtolower((string)($prauth[$i][0] ?? '')) === strtolower($username)) {
        echo "OK: LIMITED user already exists at index $i\n";
        exit(0);
    }
}

$template = $prauth[1] ?? $prauth[0];
if (!is_array($template)) {
    fwrite(STDERR, "FAIL: no template user in gmdata\n");
    exit(1);
}

$newIdx = (int)($prauthcnt ?? count($prauth));
$newUser = $template;
$newUser[0] = $username;
$newUser[1] = hashgen($password);
$newUser[2] = 0;
$newUser[3] = 0;
$newUser[4] = 0;
$newUser[10] = 1;
$newUser[15] = $username;
$newUser[21] = 'Limited';
$newUser[42] = 0;

$prauth[$newIdx] = $newUser;
$prauthcnt = $newIdx + 1;

$tempdescr = csvopen('_conf/gmdata.cfg', 'w', 1);
if (!$tempdescr) {
    fwrite(STDERR, "FAIL: cannot write gmdata.cfg\n");
    exit(1);
}
writefullcsv($tempdescr, $gmheader, $gmplevel, $prauth);
fclose($tempdescr);

echo "OK: LIMITED user added at index $newIdx\n";
