<?php

namespace DMBot\Modules;

use DMBot\Modules;
use DMBot\Config;
use DMBot\Tokeniser;

class Greeting {
    
    public $version = '1.0';
    public $name = 'Greeting Module';
    public $description = 'Replies to direct greetings.';

    function PRIVMSG($Message) {
        global $bot;
        
        $greetings = [
            'hello',
            'greetings',
            'hi'
        ];
        if (preg_match('/^(' . implode('|', $greetings) . ')/', $Message->data) && (strpos($Message->data, Config::get('irc_nick')) !== false)) {
            $bot->PrivMsg(
                    Tokeniser::tokenise('greeting', 'GREETING_MESSAGE_'.rand(1,5), ['nick' => $Message->nick]), $Message->channel
            );
        }
    }

}

Modules::addModule('greeting', 'DMBot\Modules\Greeting');
