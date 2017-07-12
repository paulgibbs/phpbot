<?php

use DMBot\Bot;

$file = '.env';
if (isset($GLOBALS['argv']['1'])) {
    if (strtolower($GLOBALS['argv']['1']) == 'help') {
        echo "Usage:\n\t{$GLOBALS['argv']['0']} [configuration file]\n";
        exit;
    } else {
        $file = $GLOBALS['argv']['1'];
    }
}

while(1) {
	echo "DMBot Starting up...\n";
	$start_time=time();
	$bot = new Bot(__DIR__,$file);

	$bot->Connect();

	$bot->Register();

	$bot->Join();

	//$Modules = new Modules();

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