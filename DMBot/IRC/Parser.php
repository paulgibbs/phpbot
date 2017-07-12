<?php

namespace DMBot\IRC;

class Parser {

    static function ParseNotice($read) {

        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read); //dont need ehm
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1'], 3);
        $nick = explode("!", $that['0'], 2);
        $that['2'] = str_replace(" ", "", $that['2']);
        if (strtolower($that['2']) == strtolower($this->ar_botcfg['nick'])) { //check if we are getting a PM
            $that['2'] = "PM";
        }
        $this->Debug(8, YELLOW . "Notice from {$nick[0]}: {$thiss[2]}" . NORMAL);
        $this->ar_message['notice']['nick'] = $nick[0];
        $this->ar_message['notice']['msg'] = $thiss[2];
    }

    static function ParseMode($read) {
        //:ChanServ!IRC@DMIndustries.NET MODE #wammy +oq Wammy Wammy
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1'], 5);
        $nick = explode("!", $that['0'], 2);
        if (($that[1] == "MODE") && isset($that['4'])) {
            $this->Debug(8, BLUE . BOLD . "{$nick['0']}" . NORMAL . BLUE . " sets mode {$that['2']} {$that['3']} {$that['4']}" . NORMAL);
            $this->ar_message['mode']['nick'] = $nick[0];
            $this->ar_message['mode']['channel'] = $that[2];
            $this->ar_message['mode']['modes'] = $that[3];
            $this->ar_message['mode']['users'] = $that[4];
        }
    }

    static function ParseKick($read) {

        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);  //we dont need \r\n
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1']);
        $nick = explode("!", $that['0'], 2);

        $this->ar_message['kick']['nick'] = $nick[0];
        $this->ar_message['kick']['channel'] = $that[2];
        $this->ar_message['kick']['who'] = $that[3];
        $this->ar_message['kick']['reason'] = $thiss[2];
        if ($that['1'] == "KICK") { //check to make sure this is a KICK

            $this->Debug(8, WHITEBG . BLACK . "{$nick['0']} Kicked {$that['3']} from {$that['2']} for: {$thiss['2']}" . NORMAL . BLACKBG);
            if (strtolower($that['3']) == strtolower($this->ar_botcfg['nick'])) {
                $this->Join($that['2']); //heh
            }
        }
    }

    static function ParseJoin($read) {
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read); //dont need them
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1'], 3);
        $nick = explode("!", $that['0'], 2);
        $that['2'] = str_replace(" ", "", $that['2']);
        $this->Debug(8, BLUE . BOLD . "{$nick['0']} Joined {$thiss['2']}" . NORMAL);
        $this->ar_message['join']['nick'] = $nick[0];
        $this->ar_message['join']['channel'] = $thiss[2];
    }

    static function ParsejTopic($read) {
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1']);
        if ($that['1'] == 332) {
            $this->Debug(8, WHITEBG . BLACK . "Topic For Channel {$that['3']}: {$thiss['2']}" . NORMAL . BLACKBG);
            $this->ar_message['join']['topic'] = $thiss[2];
        }
    }

    /* ISON Support
     * Added By DJPaul@Dangerous-Minds.Net
     * Confirmed by Wammy 02/15/06
     * In regards to DMBot Bug #0000128
     * Triggered after we've done a ISON and we've got the result. Designed to only recieve 1 ISON per request.
     */

    static function ParseISON($read) {
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);
        $thiss = explode(":", $read, 3);

        // If $thiss[2] contains a name, that person's online.
        $this->Debug(8, WHITEBG . BLACK . "ISON result: {$thiss[2]}" . NORMAL . BLACKBG);
        $this->ar_message['ison']['nick'] = $thiss[2];
    }

    /**
     * @return void
     * @param string $read
     * @desc Parses TOPIC Messages on IRC
     */
    static function ParsejTopicAuthor($read) {
        //:IRC.Sys-Techs.Net 333 IRCBot2 #SysT Wammy 1078981989
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);
        $that = explode(" ", $read);
        if ($that['1'] == 333) {
            error_reporting(0);
            $this->Debug(8, WHITEBG . BLACK . "Set By: {$that['4']} on " . date("F j, Y, g:i a", $that['5']) . NORMAL . BLACKBG);
            $this->ar_message['join']['author'] = $that['4'];
            $this->ar_message['join']['date'] = $that['5'];
            error_reporting(E_ALL);
        }
    }

    static function ParseNames($read) {
        //:irc.dangerous-minds.net 353 SomeBot = #syst :SomeBot Wammy
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1'], 5);
        $nick = explode("!", $that['0'], 2);
        $that['2'] = str_replace(" ", "", $that['2']);
        $this->Debug(8, WHITEBG . BLACK . "People in {$that['4']}: {$thiss['2']}" . NORMAL . BLACKBG);
    }

    static function ParsePart($read) {
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);
        $thiss = explode(":", $read, 2);
        $that = explode(" ", $thiss['1'], 3);
        //echo "1: ".$that['0']."\n";
        //echo "2: ".$that['1']."\n";
        //echo "3: ".$that['2']."\n";
        $nick = explode("!", $that['0'], 2);
        $that['2'] = str_replace(" ", "", $that['2']);
        $this->Debug(8, WHITEBG . BLACK . "{$nick['0']} Left {$that['2']}" . NORMAL . BLACKBG);
        $this->ar_message['part']['nick'] = $nick[0];
        $this->ar_message['part']['channel'] = $that[2];
    }

    static function ParseNick($read) {

        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read); //dont need ehm
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1'], 3);
        $nick = explode("!", $that['0'], 2);


        $this->Debug(8, WHITEBG . BLACK . "{$nick['0']} is now known as {$thiss['2']}" . NORMAL . BLACKBG);
        $this->ar_message['nick']['was'] = $nick[0];
        $this->ar_message['nick']['is'] = $thiss[2];
    }

    static function ParsePrivMsg($read) {
        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read); //dont need ehm
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1'], 3);
        $nick = explode("!", $that['0'], 2);
        $that['2'] = str_replace(" ", "", $that['2']);
        if (strtolower($that['2']) == strtolower($this->ar_botcfg['nick'])) { //check if we are getting a PM
            $that['2'] = "PM";
        }

        if ($thiss['2'] == "Register first.") {
            $this->Register();
            $this->Join();
        }

        $this->Debug(8, WHITEBG . BLACK . "<{$nick['0']}/{$that['2']}> Said: {$thiss['2']}" . NORMAL . BLACKBG);
        $this->ar_message['privmsg']['nick'] = $nick[0];
        $this->ar_message['privmsg']['channel'] = $that[2];
        $this->ar_message['privmsg']['msg'] = $thiss[2];
    }

    static function ParseQuit($read) {

        $read = str_replace("\n", "", $read);
        $read = str_replace("\r", "", $read);  //we dont need \r\n
        $thiss = explode(":", $read, 3);
        $that = explode(" ", $thiss['1']);
        $nick = explode("!", $that['0'], 2);
        $this->Debug(8, WHITEBG . BLACK . "{$nick[0]} has quit: {$thiss[2]}" . NORMAL . BLACKBG);
        $this->ar_message['quit']['nick'] = $nick[0];
        $this->ar_message['quit']['channel'] = $thiss[2];
    }

}
