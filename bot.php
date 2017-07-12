<?php
/**
 * The DM PHP IRC Bot.
 * 
 * This Code was written with the assistance of
 * The Place of Dangerous Minds
 * http://WWW.Dangerous-Minds.NET who has supported me
 * in many of my projects. Please take the time to visit
 * them and support them as well.
 * 
 * @author wammy21@gmail.com
 * @author djpaul@gmail.com
 * 
 */

set_time_limit(0);
error_reporting(E_ALL);

include __DIR__.'/vendor/autoload.php';
include __DIR__.'/includes/colors.php';

use DMBot\Bot;

define('DMBOT_BASE',__DIR__);

$file = '.env';
if (isset($GLOBALS['argv']['1'])) {
    if (strtolower($GLOBALS['argv']['1']) == 'help') {
        echo "Usage:\n\t{$GLOBALS['argv']['0']} [configuration file]\n";
        exit;
    } else {
        $file = $GLOBALS['argv']['1'];
    }
}

global $bot;
$bot = null;

while(1) {
	echo "DMBot Starting up...\n";
	$start_time=time();
	$bot = new Bot(__DIR__,$file);
        
        set_error_handler([&$bot,'ErrorHandler']);
        
	$bot->Connect();

	$bot->Register();

	$bot->Join();
        
        $bot->Modules();

	$bot->Listen();
	
	echo "DMBot Ended.\n";
	$end_time=time();
	if($start_time+5 >= $end_time) {
		$diff = ($end_time - $start_time);
		echo "Dalying Restart, Loop ended in $diff secs.\n";
		sleep(30);
	} else {
		echo "Restarting.\n";
	}
}