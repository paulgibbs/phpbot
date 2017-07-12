<?php
/********************************************************
* bot.php By Wammy (wammy21@gmail.com)
*
* The core of the DM Bot
* 
* This Code was written with the assistance of
* The Place of Dangerous Minds
* http://WWW.Dangerous-Minds.NET who has supported me
* in many of my projects. Please take the time to visit
* them and support them as well.
*
* Known Issues:
* 
*
*
*
* Created on 00/00/05 By Wammy
* Last Edited on 
*
********************************************************/

require 'includes/classes/irc.class.php';
require 'includes/classes/tokeniser.class.php';
require 'includes/classes/config.class.php';
require("includes/functions/errorhandler.php");
set_time_limit(0);
error_reporting(E_ALL);
$old_error_handler = set_error_handler("ErrorHandler");
echo"\n\n";
if(!isset($GLOBALS['argv']['1'])) {
	echo "Usage:\n\t{$GLOBALS['argv']['0']} [configuration file]\n";
	exit;
}
while(1) {
	echo "DMBot Starting up...\n";
	$time=time();
	$DMBot = new DMBot($GLOBALS['argv']['1']); // Start the IRC Class

	$DMBot->Connect();

	$DMBot->Register();

	$DMBot->Join();

	$Modules = new Modules();

	$DMBot->Listen();
	
	echo "DMBot Ended.\n";
	$time2=time();
	if($time+5 >= $time2) {
		$time3 = ($time2 - $time);
		echo "Dalying Restart, Loop ended in $time3 secs.\n";
		sleep(30);
	} else {
		echo "Restarting.\n";
	}
}
?>
