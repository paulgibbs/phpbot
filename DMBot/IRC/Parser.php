<?php
namespace DMBot\IRC;
use DMBot\Config;

/**
 * IRC protocol parser
 * 
 * @author wammy21@gmail.com
 * @author djpaul@gmail.com
 */
class Parser {

    static function parseNotice(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);
        $data_parts = explode(" ", $message_parts['1'], 3);
        $nick = explode("!", $data_parts['0'], 2);
        $data_parts['2'] = str_replace(" ", "", $data_parts['2']);
        if (strtolower($data_parts['2']) == strtolower(Config::get('irc_nick'))) { //check if we are getting a PM
            $data_parts['2'] = "PM";
        }
        
        $Message->type = 'notice';
        $Message->nick = $nick[0];
        $Message->data = $message_parts[2];
    }

    static function parseMode(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);
        $message_data = explode(" ", $message_parts['1'], 5);
        $nick = explode("!", $message_data['0'], 2);
        if (($message_data[1] == "MODE") && isset($message_data['4'])) {
            $Message->type = 'mode';
            $Message->nick = $nick[0];
            $Message->channel = $message_data[2];
            $Message->modes = $message_data[3];
            $Message->users = $message_data[4];
        }
    }

    static function parseKick(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);
        $data_parts = explode(" ", $message_parts['1']);
        $nick = explode("!", $data_parts['0'], 2);

        if ($data_parts['1'] == "KICK") { //check to make sure this is a KICK
            $Message->type = 'kick';
            $Message->nick = $nick[0];
            $Message->channel = $data_parts[2];
            $Message->data = $message_parts[2];
            $Message->users = $data_parts[3];
        }
    }

    static function parseJoin(Message &$Message) {
        //:DMBot!~dm_bot@host.net JOIN #Channel
        $message_parts = explode(":", $Message->rawData, 3);
        $data_parts = explode(" ", $message_parts['1'], 3);
        $nick = explode("!", $data_parts['0'], 2);
        $data_parts['2'] = str_replace(" ", "", $data_parts['2']);
        
        $Message->type = 'join';
        $Message->nick = $nick[0];
        $Message->channel = $data_parts[2];
    }

    static function parsejTopic(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);
        $channel_parts = explode(" ", $message_parts['1']);
        if ($channel_parts['1'] == 332) {
            $Message->type = 'jtopic';
            $Message->channel = $channel_parts[3];
            $Message->data = $message_parts[2];
        }
    }

    static function parseISON(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);

        // If $message_parts[2] contains a name, that person's online.
        $Message->type = 'ison';
        $Message->nick = $message_parts[2];
    }

    static function parsejTopicAuthor(Message &$Message) {
        $message_parts = explode(" ", $Message->rawData);
        if ($message_parts['1'] == 333) {
            error_reporting(0);
            $Message->type = 'jtopicauth';
            $Message->nick = $message_parts['4'];
            $Message->data = $message_parts['5'];
            error_reporting(E_ALL);
        }
    }

    static function parseNames(Message &$Message) {
        $Message->type = 'names';
        
        $message_parts = explode(":", $Message->rawData, 3);
        $data_parts = explode(" ", $message_parts['1'], 5);
        $data_parts['2'] = str_replace(" ", "", $data_parts['2']);
        
        $Message->channel = $data_parts['4'];
        $Message->data = $message_parts['2'];
    }

    static function parsePart(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 2);
        $data_parts = explode(" ", $message_parts['1'], 3);
        $nick = explode("!", $data_parts['0'], 2);
        $data_parts['2'] = str_replace(" ", "", $data_parts['2']);
        
        $Message->type = 'part';
        $Message->nick = $nick[0];
        $Message->channel = $data_parts[2];
    }

    static function parseNick(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);
        $data_parts = explode(" ", $message_parts['1'], 3);
        $nick = explode("!", $data_parts['0'], 2);

        $Message->type = 'nick';
        $Message->nick = $nick[0];
        $Message->data = $message_parts[2];
    }

    static function parsePrivMsg(Message &$Message) {
        
        $Message->type = 'privmsg';
        
        $message_parts = explode(":", $Message->rawData, 3);
        $message_recipient = explode(" ", $message_parts['1'], 3);
        $message_source = explode("!", $message_recipient['0'], 2);
        $message_recipient['2'] = str_replace(" ", "", $message_recipient['2']);
        if (strtolower($message_recipient['2']) == strtolower(Config::get('irc_nick'))) { //check if we are getting a PM
            $message_recipient['2'] = "PM";
        }
        
        $Message->nick = $message_source[0];
        $Message->channel = $message_recipient[2];
        $Message->data = $message_parts[2];
    }

    static function parseQuit(Message &$Message) {
        $message_parts = explode(":", $Message->rawData, 3);
        $data_parts = explode(" ", $message_parts['1']);
        $nick = explode("!", $data_parts['0'], 2);
        
        $Message->type = 'quit';
        $Message->nick = $nick[0];
        $Message->data = $message_parts[2];
    }

}
