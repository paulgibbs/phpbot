<?php

namespace DMBot\Modules;

use DMBot\Modules;
use DMBot\Config;
use DMBot\Tokeniser;

class Greeting {

    function PRIVMSG($Message) {
        global $bot;
        $greetings = [
            'hello',
            'greetings',
            'hi'
        ];
        if (preg_match('/^(' . implode('|', $greetings) . ')/', $Message->data) && (strpos($Message->data, Config::get('irc_nick')) !== false)) {
            $bot->PrivMsg(
                    Tokeniser::tokenise('greeting', 'GREETING_MESSAGE', ['nick' => $Message->nick]), $Message->channel
            );
        }
    }

}

Modules::addModule('greeting', 'DMBot\Modules\Greeting');
