<?php
/*  ����� �� ��������
$stroka="<div id=23> � ����� ������, �� </nobr> ������ ������</br>";
$a1=strpos ($stroka,"</nobr>")+7;  $a2=strpos($stroka,"</br>") ;
$res=substr ($stroka,$a1,$a2);
echo $res;
exit;
*/ 

//SITE UNKNOWN  PROGRAMM CREATED BY DJ--ALEX
 	global $vergetfile;	
$vergetfile="Search v4.2.3 (c) dj--alex";
	require_once ('dbscore.lib');
	if ($auth==cmsg("AUTHEN")) {
	 dbs_require_basic_auth();
	}
$enterpoint=$vergetfile;
If ($prauth[$ADM][4]==true) {$adm=1;} else { $gmlimitcfg=1;} ;// { $adm=$ADM;
if ($ADM<1) $adm=0;

?><CENTER> <p align="center">	<?php if (!$pr[10]) { echo "<img src=_style/$sd[0] align=middle></p>"; }?>
<h<?php print $sd[3]; ?>><?php print "$sd[1]"; ?>
<?php // MASTER MODE
$deftbl=$pr[16];

if ($pr[37]) {// analog in writefile
?><br><form action="getfile.php" method="post">
<?php hidekey("vID",$vID); hidekey("vID2",$vID2); hidekey("colfind",$colfind); hidekey("intf","master-mode"); hidekey("mode",$mode); hidekey("printlimit",$printlimit); hidekey("field",$field); hidekey("live",$live); ?>
    <script src="ajax.js"></script>
    <?php 	//section select group

	//	hidekey ("groupdb",$groupdb);print_r ($list);	//	print_r ($a);
        $grouplist=groupdbfielddetect ($prdbdata,17);// set group as field
        $groupdbthisname="groupdb";
	groupdbprint ($grouplist,"Group",$prdbdata,$tbl,$groupdb);
        
        $grouplist2=groupdbfielddetect ($prdbdata,6);// set IP as field
        $groupdbthisname="ipfilter";// in future - add this variable to f
        groupdbprint ($grouplist2,"IP",$prdbdata,$tbl,$ipfilter);// IP CFG OPT FUTURE groupdbfielddetect
//        ..print_r ($grouplist);        print_r ($grouplist2);
	submitkey ("write","SELECT");
	if ($prauth[$ADM][2]) submitkey ("live","LIVEMOD");echo "*";
	if ($live) echo "in future release!";
	echo"</form><br>";
}

 if ($pr[24]) { 
	 if (($adm==1)OR($deftbl==false)) { ?>
<form action="r.php" method="post">
	<?php hidekey("vID",$vID); hidekey("vID2",$vID2); hidekey("colfind",$colfind); hidekey("intf","master-mode"); hidekey("mode",$mode); hidekey("printlimit",$printlimit); hidekey("field",$field); hidekey("groupdb",$groupdb); hidekey("ipfilter",$ipfilter); hidekey("page",0); hidekey("live",$live); ?>
		 <?php 
        //if (($groupdb!=="Unsorted")or ($ipfilter!=="Unsorted")) ����� ������ ��� �������?
 printlink ($prauth,$prdbdata,$ADM,$tbl,0,"tbl",cmsg("SELLINK"),$groupdb,$ipfilter,6);
//if  printlink ($prauth,$prdbdata,$ADM,$tbl,$grouplist,"tbl",cmsg("SELLINK"),$ipfilter);
submitkey ($write,"A_USRGO");
  } ;
 if (($adm==0)AND($deftbl==true)) { ?> 
	<form action="r.php" method="post">
		<?php hidekey("tbl",$deftbl); ?>
	<?php submitkey ("write","FMG_ENTER");
	 };
echo "</form>";
}; // MASTER MODE ENDS
	if (!$pr[24]) require ('readfilemenu.php');

 if ($adm==1) {
	if ($pr[11]) { echo "<br>".cmsg("GF_USRDIS_A_WARN"); }
    } else if ($pr[11]) { echo "<br>".cmsg("GF_USRDIS_U_WARN"); }
	fclose ($desc);
	showshortlog ();
          echo cmsg ("HERE").":".print_massive ($onlineusers);          echo "<Br>";         print "$sd[2]";

if (strpos ($USERDAT,"MSIE")==true) { window (); echo cmsg ("MSIE_DETECT");closewindow () ; }

?>

