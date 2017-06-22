<?php
/********************************************************
* irc.class.php By Wammy (wammy21@gmail.com)
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
require 'sockets.class.php';
require 'colors.class.php';
require 'modules.class.php';

Class DMBot {
	private $int_version = '4.0.6';
	private $str_cfgfile = '';
	public $ar_botcfg = array();
	public $cl_Socket;
	public $int_registered=0;
	public $ar_message=array();
	public $DebugLvl=0;
	public $msgQueue=array(array('chan'=>'','msg'=>''));
	public $msgQueueTime=0.0;
	function __construct($str_cfgfile) {
		global $DMBot,$Modules;
		$DMBot = $this;
		$this->msgQueueTime=microtime(true);
		$this->cl_Socket = new syst_Socket(SOL_TCP);
		
		Config::LoadConfigValues($str_cfgfile);

        $this->ar_botcfg['owner'] = Config::GetValue('dmbot_owner');
        $this->ar_botcfg['host'] = Config::GetValue('dmbot_server');
        $this->ar_botcfg['port'] = Config::GetValue('dmbot_server_port');
        $this->ar_botcfg['nick'] = Config::GetValue('dmbot_irc_nick');
        $this->ar_botcfg['name'] = Config::GetValue('dmbot_irc_name');
        $this->ar_botcfg['channels'] = Config::GetValue('dmbot_irc_channels',1);
        $this->ar_botcfg['log'] = Config::GetValue('dmbot_enable_logging');
        $this->ar_botcfg['nickserv'] = Config::GetValue('dmbot_enable_nickserv');
        $this->ar_botcfg['nickpass'] = Config::GetValue('dmbot_nickserv_password');
        $this->ar_botcfg['modulesdir'] = Config::GetValue('dmbot_modules_dir');
        $this->ar_botcfg['pinginterval'] = Config::GetValue('dmbot_ping_intervals');
        $this->DebugLvl = Config::GetValue('dmbot_debug_level');

		/* Added By Wammy@dangerous-minds.net
		* Approved by Wammy@dangerous-minds.net on 02/16/06
		* In regards to DMBot Bug #0000154
		*/
		date_default_timezone_set(Config::GetValue('dmbot_default_timezone'));

	}


	/**
	* @return void
	* @param int $lvl, str $str
	* @desc Output Information if Debug Level is appropriate.
	* In regards to DMBot Bug #0000150
	*/
	public function Debug($lvl,$str) {
		global $DMBot;
		if($lvl <= $DMBot->DebugLvl) {
			echo $str."\n";
		}
	}

	public function Connect() {
		global $DMBot;
		$DMBot->cl_Socket->Connect($DMBot->ar_botcfg['host'],$DMBot->ar_botcfg['port']);
		if($DMBot->cl_Socket->connected) {

		} else {
			trigger_error("Could not connect to {$DMBot->ar_botcfg['host']}:{$DMBot->ar_botcfg['port']} Reason: ".socket_strerror(socket_last_error($DMBot->cl_Socket->Socket)),E_USER_ERROR);
		}
	}

	public function Register()
	{
		global $DMBot;
		if($DMBot->cl_Socket->connected)
		{
			$DMBot->cl_Socket->Write("USER dm_bot 0 * :".$DMBot->ar_botcfg['name']." ".$DMBot->int_version."\n");
			sleep(1);
			$DMBot->cl_Socket->Write("NICK ".$DMBot->ar_botcfg['nick']."\n");
			$DMBot->int_registered=1;
			return true;
		}
		else
		{
			trigger_error("Could not register with {$DMBot->ar_botcfg['nick']}",E_USER_ERROR);
			return false;
		}
	}

	public function Join($channel='')
	{
		global $DMBot;
		if($DMBot->ar_botcfg['nickserv'] ==1) {
			$this->PrivMsg("identify {$DMBot->ar_botcfg['nickpass']}","nickserv");
		}
		if (!empty($channel)) {

			$DMBot->cl_Socket->Write("JOIN ".$channel."\n");

		} else {

			$i = 0;
			$x = count($DMBot->ar_botcfg['channels']);

			while ($i < $x)
			{
				$DMBot->cl_Socket->Write("JOIN ".$DMBot->ar_botcfg['channels'][$i]."\n");
				$i++;
			}
		}
	}
	public function PrivMsg($msg,$chan)
	{
		global $DMBot;
		/*
		* Changed By Wammy@Dangerous-Minds.Net
		* Confirmed by Wammy 02/16/06
		* In regards to DMBot Bug #0000122
		*/
		if($msg != '') {
			$DMBot->msgQueue[] =array('chan'=>$chan,'msg'=>$msg);
		}
	}

	private function ParseNotice($read)
	{

		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read); //dont need ehm
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1'],3);
		$nick = explode("!",$that['0'],2);
		$that['2'] = str_replace(" ","",$that['2']);
		if(strtolower($that['2']) == strtolower($this->ar_botcfg['nick'])) //check if we are getting a PM
		{
			$that['2'] = "PM";
		}
		$this->Debug(8,YELLOW."Notice from {$nick[0]}: {$thiss[2]}".NORMAL);
		$this->ar_message['notice']['nick'] = $nick[0];
		$this->ar_message['notice']['msg'] = $thiss[2];

	}

	private function ParseMode($read)
	{
		//:ChanServ!IRC@DMIndustries.NET MODE #wammy +oq Wammy Wammy
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1'],5);
		$nick = explode("!",$that['0'],2);
		if(($that[1] == "MODE") && isset($that['4'])) {
			$this->Debug(8,BLUE.BOLD."{$nick['0']}".NORMAL.BLUE." sets mode {$that['2']} {$that['3']} {$that['4']}".NORMAL);
			$this->ar_message['mode']['nick'] = $nick[0];
			$this->ar_message['mode']['channel'] = $that[2];
			$this->ar_message['mode']['modes'] = $that[3];
			$this->ar_message['mode']['users'] = $that[4];
		}

	}

	private function ParseKick($read)
	{

		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);  //we dont need \r\n
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1']);
		$nick = explode("!",$that['0'],2);

		$this->ar_message['kick']['nick'] = $nick[0];
		$this->ar_message['kick']['channel'] = $that[2];
		$this->ar_message['kick']['who'] = $that[3];
		$this->ar_message['kick']['reason'] = $thiss[2];
		if($that['1'] == "KICK") //check to make sure this is a KICK
		{

			$this->Debug(8,WHITEBG.BLACK."{$nick['0']} Kicked {$that['3']} from {$that['2']} for: {$thiss['2']}".NORMAL.BLACKBG);
			if(strtolower($that['3']) == strtolower($this->ar_botcfg['nick'])) {
				$this->Join($that['2']); //heh
			}
		}
	}

	private function ParseJoin($read)
	{
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read); //dont need them
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1'],3);
		$nick = explode("!",$that['0'],2);
		$that['2'] = str_replace(" ","",$that['2']);
		$this->Debug(8,BLUE.BOLD."{$nick['0']} Joined {$thiss['2']}".NORMAL);
		$this->ar_message['join']['nick'] = $nick[0];
		$this->ar_message['join']['channel'] = $thiss[2];
	}

	private function ParsejTopic($read)
	{
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1']);
		if($that['1'] == 332)
		{
			$this->Debug(8,WHITEBG.BLACK."Topic For Channel {$that['3']}: {$thiss['2']}".NORMAL.BLACKBG);
			$this->ar_message['join']['topic'] = $thiss[2];
		}
	}

	/* ISON Support
	* Added By DJPaul@Dangerous-Minds.Net
	* Confirmed by Wammy 02/15/06
	* In regards to DMBot Bug #0000128
	* Triggered after we've done a ISON and we've got the result. Designed to only recieve 1 ISON per request.
	*/
	private function ParseISON($read) {
		$read = str_replace("\n", "", $read);
		$read = str_replace("\r", "", $read);
		$thiss = explode(":", $read, 3);

		// If $thiss[2] contains a name, that person's online.
		$this->Debug(8,WHITEBG.BLACK."ISON result: {$thiss[2]}".NORMAL.BLACKBG);
		$this->ar_message['ison']['nick'] = $thiss[2];
	}

	/**
	* @return void
	* @param string $read
	* @desc Parses TOPIC Messages on IRC
	*/
	private function ParsejTopicAuthor($read)
	{
		//:IRC.Sys-Techs.Net 333 IRCBot2 #SysT Wammy 1078981989
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);
		$that = explode(" ",$read);
		if($that['1'] == 333)
		{
			error_reporting(0);
			$this->Debug(8,WHITEBG.BLACK."Set By: {$that['4']} on ".date("F j, Y, g:i a",$that['5']).NORMAL.BLACKBG);
			$this->ar_message['join']['author'] = $that['4'];
			$this->ar_message['join']['date'] = $that['5'];
			error_reporting(E_ALL);
		}
	}

	private function ParseNames($read)
	{
		//:irc.dangerous-minds.net 353 SomeBot = #syst :SomeBot Wammy
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1'],5);
		$nick = explode("!",$that['0'],2);
		$that['2'] = str_replace(" ","",$that['2']);
		$this->Debug(8,WHITEBG.BLACK."People in {$that['4']}: {$thiss['2']}".NORMAL.BLACKBG);

	}

	private function ParsePart($read)
	{
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);
		$thiss = explode(":",$read,2);
		$that = explode(" ",$thiss['1'],3);
		//echo "1: ".$that['0']."\n";
		//echo "2: ".$that['1']."\n";
		//echo "3: ".$that['2']."\n";
		$nick = explode("!",$that['0'],2);
		$that['2'] = str_replace(" ","",$that['2']);
		$this->Debug(8,WHITEBG.BLACK."{$nick['0']} Left {$that['2']}".NORMAL.BLACKBG);
		$this->ar_message['part']['nick'] = $nick[0];
		$this->ar_message['part']['channel'] = $that[2];


	}
	private function ParseNick($read)
	{

		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read); //dont need ehm
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1'],3);
		$nick = explode("!",$that['0'],2);


		$this->Debug(8,WHITEBG.BLACK."{$nick['0']} is now known as {$thiss['2']}".NORMAL.BLACKBG);
		$this->ar_message['nick']['was'] = $nick[0];
		$this->ar_message['nick']['is'] = $thiss[2];

	}

	private function ParsePrivMsg($read)
	{
		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read); //dont need ehm
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1'],3);
		$nick = explode("!",$that['0'],2);
		$that['2'] = str_replace(" ","",$that['2']);
		if(strtolower($that['2']) == strtolower($this->ar_botcfg['nick'])) //check if we are getting a PM
		{
			$that['2'] = "PM";
		}

		if($thiss['2'] == "Register first.")
		{
			$this->Register(); $this->Join();
		}

		$this->Debug(8,WHITEBG.BLACK."<{$nick['0']}/{$that['2']}> Said: {$thiss['2']}".NORMAL.BLACKBG);
		$this->ar_message['privmsg']['nick'] = $nick[0];
		$this->ar_message['privmsg']['channel'] = $that[2];
		$this->ar_message['privmsg']['msg'] = $thiss[2];

	}

	private function ParseQuit($read)
	{

		$read = str_replace("\n","",$read);
		$read = str_replace("\r","",$read);  //we dont need \r\n
		$thiss = explode(":",$read,3);
		$that = explode(" ",$thiss['1']);
		$nick = explode("!",$that['0'],2);
		$this->Debug(8,WHITEBG.BLACK."{$nick[0]} has quit: {$thiss[2]}".NORMAL.BLACKBG);
		$this->ar_message['quit']['nick'] = $nick[0];
		$this->ar_message['quit']['channel'] = $thiss[2];
	}

	public function Listen()
	{
		global $DMBot,$Modules;
		$DMBot->ar_botcfg['pingtime'] = time();

		//check if we are still connected
		while ($DMBot->cl_Socket->connected)
		{

			//check if there is data queued
			$set = array($DMBot->cl_Socket->Socket);
			$set_w = array();
			$set_e = array();

			if (socket_select($set, $set_w, $set_e, 1, 0) > 0)
			{
				$Modules->Run('TIMER');
				$read = $DMBot->cl_Socket->Read(1024,PHP_NORMAL_READ);
				if(($read == "-2") || ($read == "-1")) {
					trigger_error("Could not read from {$DMBot->ar_botcfg['host']}:{$DMBot->ar_botcfg['port']} Reason: ".socket_strerror(socket_last_error($DMBot->cl_Socket->Socket)),E_USER_ERROR);
					$DMBot->cl_Socket->connected = false;
				}
				$read = str_replace("\r","",$read);
				$read = str_replace("\n","",$read);
				$this->ar_message['RAW']=$read;
				if(strlen($read) > 0) {
					if (substr($read,0,4) == "PING"){
						$DMBot->ar_botcfg['pingtime'] = time();
						$thist = explode(":",$read);
						$write = "PONG :".$thist['1']."\n";
						$DMBot->cl_Socket->Write($write);
						$this->Debug(7,GREENBG."Ping? Pong!".BLACKBG);
					}elseif (strpos($read, ' NOTICE ') && !strpos($read, 'PRIVMSG')) {
						$DMBot->ParseNotice($read);
						$Modules->Run('NOTICE');
					}else if (strpos($read, ' 451 JOIN ')){
						$DMBot->Register();
						$DMBot->Join();
					}else if (strpos($read, ' 353 ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParseNames($read);
						$Modules->Run('NAMES');
					}else if (strpos($read, ' 366 ') && !strpos($read, 'PRIVMSG')){
					}else if (strpos($read, ' 462 ') && !strpos($read, 'PRIVMSG')){
					}else if (strpos($read, ' 332 ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParsejTopic($read);
						$Modules->Run('JTOPIC');
					}else if (strpos($read, ' 333 ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParsejTopicAuthor($read);
						$Modules->Run('JTOPICAUTH');
					}else if (strpos($read, ' 375 ') && !strpos($read, 'PRIVMSG')){
						$thiss = explode(":",$read,3);
						$this->Debug(8,BLUEBG.WHITE."{$thiss['2']}".BLACKBG.NORMAL);
					}else if (strpos($read, ' 372 ') && !strpos($read, 'PRIVMSG')){
						$thiss = explode(":",$read,3);
						$this->Debug(8,BLUEBG.WHITE."{$thiss['2']}".BLACKBG.NORMAL);
					}else if (strpos($read, ' 376 ') && !strpos($read, 'PRIVMSG')){
						$thiss = explode(":",$read,3);
						$this->Debug(8,BLUEBG.WHITE."{$thiss['2']}".BLACKBG.NORMAL);
					}else if (strpos($read, ' 001 ') || strpos($read, ' 002 ') || strpos($read, ' 003 ') || strpos($read, ' 255 ') || strpos($read, ' 251 ') || strpos($read, ' 265 ') || strpos($read, ' 266 ') && !strpos($read, 'PRIVMSG')){
						$thiss = explode(":",$read,3);
						$this->Debug(8,BLUEBG.WHITE."{$thiss['2']}".BLACKBG.NORMAL);
					}else if (strpos($read, ' MODE ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParseMode($read);
						$Modules->Run('MODE');
					}else if (strpos($read, ' JOIN ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParseJoin($read);
						$Modules->Run('JOIN');
					}else if (strpos($read, 'PRIVMSG')){
						$DMBot->ParsePrivMsg($read);
						$Modules->Run('PRIVMSG');
					}else if (strpos($read, ' PART ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParsePart($read);
						$Modules->Run('PART');
					}else if (strpos($read, ' KICK ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParseKick($read);
						$Modules->Run('KICK');
					}else if (strpos($read, ' NICK ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParseNick($read);
						$Modules->Run('NICK');
					}else if (strpos($read, ' QUIT ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ParseQuit($read);
						$Modules->Run('QUIT');
					}else if (strpos($read, ' PONG ') && !strpos($read, 'PRIVMSG')){
						$DMBot->ar_botcfg['pingtime'] = time();
					}else if (strpos($read, ' 303 ') && !strpos($read, 'PRIVMSG')){
						/* ISON Support
						* Added By DJPaul@Dangerous-Minds.Net
						* Confirmed by Wammy 02/15/06
						* In regards to DMBot Bug #0000128
						*/
						$DMBot->ParseISON($read);
						$Modules->Run('ISON');
					} else {
						$this->Debug(8,REDBG.YELLOW."$read".BLACKBG.NORMAL);
					}
				}
			} else {
				$Modules->Run('TIMER');
				if((int)$DMBot->ar_botcfg['pingtime'] <= time()-(int)$DMBot->ar_botcfg['pinginterval']) {
					$this->Debug(7,RED."Havnt recieved a ping in {$DMBot->ar_botcfg['pinginterval']} secs. Pinging.".NORMAL);
					$write = "PING :".time()."\n";
					if($DMBot->cl_Socket->Write($write)===false) {
						trigger_error("Could not write to {$DMBot->ar_botcfg['host']}:{$DMBot->ar_botcfg['port']} Reason: ".socket_strerror(socket_last_error($DMBot->cl_Socket->Socket)),E_USER_ERROR);
						$DMBot->cl_Socket->connected = false;
					}
				}

			}

			/*
			* Added By Wammy@Dangerous-Minds.Net
			* Confirmed by Wammy 02/16/06
			* In regards to DMBot Bug #0000122
			*/
			$time = microtime(true);
			if ($time - $this->msgQueueTime > 1) {
				if (($tmp=count($this->msgQueue)) > 0) {
					/* send the command */
					$ar = current($this->msgQueue);
					$key = key($this->msgQueue);
					$chan = $ar['chan'];
					$msg = $ar['msg'];
					unset($this->msgQueue[$key]);
					reset($this->msgQueue);
					$msg = str_split($msg,300);
					for($x=1;$x<count($msg);$x++) {
						$this->Debug(1,"Message too long, queueing the rest");
						$this->PrivMsg($msg[$x],$chan);
					}
					if($DMBot->cl_Socket->connected) //check if we are connected
					{
						$tmp = count($this->msgQueue);
						$this->Debug(1,"Sending Message ($tmp left on queue) ".strlen($msg[0]));
						$DMBot->cl_Socket->Write("PRIVMSG $chan :{$msg[0]}\n"); //write to the socket
					}
					$this->msgQueueTime = $time;
				}
			}

		}

	}


}


/* Added By DJPaul@dangerous-minds.net
* Approved by Wammy@dangerous-minds.net on 02/16/06
* In regards to DMBot Bug #0000154
*/
if (!function_exists('date_default_timezone_set')) {
	function date_default_timezone_set($timezone) {
		ini_set('date.timezone', $timezone);
	}
}

$Modules = null;
$DMBot = null;