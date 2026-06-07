<?php
require_once ('dbscore.lib');
if (!$configpresent) {
    http_response_code(403);
    exit('Forbidden');
}
$isSu = !empty($prauth[$ADM][42]);
$debugOn = (($pr[8] ?? '') === 'on');
if (!$isSu && !$debugOn) {
    http_response_code(403);
    exit('Forbidden');
}
phpinfo();
