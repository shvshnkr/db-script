<?php
require_once ('dbscore.lib');
//$script="login.php";
 if ($pr[36]=="on")  { 
 	$script="disable";
dbs_require_basic_auth();
 };
 
if ($pr[36]!=="on") if (!isset ($_SERVER['PHP_AUTH_USER']))  msgexiterror ("anonymous",0,"disable");
 
if (($_SERVER['PHP_AUTH_USER']=="UNKNOWN")OR($_SERVER['PHP_AUTH_USER']=="anonymous")OR($_SERVER['PHP_AUTH_USER']==false)) msgexiterror ("notuser",0,$script);

 
?>
