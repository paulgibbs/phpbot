<?php
namespace DMBot\IRC;

/**
 * IRC Message Container
 * 
 * @author wammy21@gmail.com
 */
class Message {

    public $rawData;
    public $type;
    public $data;
    public $nick;
    public $channel;
    public $modes;
    public $users;

    /**
     * 
     * @param string $data Raw irc data.
     */
    public function __construct($data) {
        $this->rawData = $data;
        $this->_parse();
    }

    /**
     * Parse the incoming message data.
     */
    private function _parse() {
        if (substr($this->rawData, 0, 4) == "PING") {
            $this->type = 'ping';
            $ping_data = explode(":", $this->rawData);
            $this->data = $ping_data['1'];
        }

        if (strpos($this->rawData, 'PRIVMSG')) {
            Parser::parsePrivMsg($this);
        } else {
            if (strpos($this->rawData, ' 451 JOIN ')) {
                $this->type = 'unregistered';
            } else if (strpos($this->rawData, ' 353 ')) {
                Parser::parseNames($this);
            } else if (strpos($this->rawData, ' 366 ')) {
                ///
            } else if (strpos($this->rawData, ' 462 ')) {
                ///
            } else if (strpos($this->rawData, ' 332 ')) {
                Parser::parsejTopic($this);
            } else if (strpos($this->rawData, ' 333 ')) {
                Parser::parsejTopicAuthor($this);
            } else if (strpos($this->rawData, ' 375 ')) {
                $this->type = '375';
                $parts = explode(":", $this->rawData, 3);
                $this->data = $parts['2'];
            } else if (strpos($this->rawData, ' 372 ')) {
                $this->type = '372';
                $parts = explode(":", $this->rawData, 3);
                $this->data = $parts['2'];
            } else if (strpos($this->rawData, ' 376 ')) {
                $this->type = '376';
                $parts = explode(":", $this->rawData, 3);
                $this->data = $parts['2'];
            } else if (strpos($this->rawData, ' 001 ') || strpos($this->rawData, ' 002 ') || strpos($this->rawData, ' 003 ') || strpos($this->rawData, ' 255 ') || strpos($this->rawData, ' 251 ') || strpos($this->rawData, ' 265 ') || strpos($this->rawData, ' 266 ')) {
                $this->type = 'motd';
                $parts = explode(":", $this->rawData, 3);
                $this->data = $parts['2'];
            } else if (strpos($this->rawData, ' MODE ')) {
                Parser::parseMode($this);
            } else if (strpos($this->rawData, ' JOIN ')) {
                Parser::parseJoin($this);
            } else if (strpos($this->rawData, ' PART ')) {
                Parser::parsePart($this);
            } else if (strpos($this->rawData, ' KICK ')) {
                Parser::parseKick($this);
            } else if (strpos($this->rawData, ' NICK ')) {
                Parser::parseNick($this);
            } else if (strpos($this->rawData, ' QUIT ')) {
                Parser::parseQuit($this);
            } else if (strpos($this->rawData, ' PONG ')) {
                $this->type = 'pong';
                $this->data = time();
            } else if (strpos($this->rawData, ' 303 ')) {
                Parser::parseISON($this);
            }
        }
    }

}
