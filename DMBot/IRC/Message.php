<?php

namespace DMBot\IRC;

class Message {

    public $rawData;
    public $type;
    public $data;

    public function __construct($data) {
        $this->rawData = $data;
        $this->_parse();
    }

    private function _parse() {
        if (substr($this->rawData, 0, 4) == "PING") {
            $this->type = 'ping';
            $ping_data = explode(":", $this->rawData);
            $this->data = $ping_data['1'];

            $this->Debug(7, GREENBG . "Ping? Pong!" . BLACKBG);
        }

        if (strpos($this->rawData, 'PRIVMSG')) {
            $this->ParsePrivMsg($this->rawData);
        } else {
            if (strpos($this->rawData, ' 451 JOIN ')) {
                $this->type = 'unregistered';
            } else if (strpos($this->rawData, ' 353 ')) {
                $this->ParseNames($this->rawData);
            } else if (strpos($this->rawData, ' 366 ')) {
                ///
            } else if (strpos($this->rawData, ' 462 ')) {
                ///
            } else if (strpos($this->rawData, ' 332 ')) {
                $this->ParsejTopic($this->rawData);
            } else if (strpos($this->rawData, ' 333 ')) {
                $this->ParsejTopicAuthor($this->rawData);
            } else if (strpos($this->rawData, ' 375 ')) {
                $thiss = explode(":", $this->rawData, 3);
                $this->Debug(8, BLUEBG . WHITE . "{$thiss['2']}" . BLACKBG . NORMAL);
            } else if (strpos($this->rawData, ' 372 ')) {
                $thiss = explode(":", $this->rawData, 3);
                $this->Debug(8, BLUEBG . WHITE . "{$thiss['2']}" . BLACKBG . NORMAL);
            } else if (strpos($this->rawData, ' 376 ')) {
                $thiss = explode(":", $this->rawData, 3);
                $this->Debug(8, BLUEBG . WHITE . "{$thiss['2']}" . BLACKBG . NORMAL);
            } else if (strpos($this->rawData, ' 001 ') || strpos($this->rawData, ' 002 ') || strpos($this->rawData, ' 003 ') || strpos($this->rawData, ' 255 ') || strpos($this->rawData, ' 251 ') || strpos($this->rawData, ' 265 ') || strpos($this->rawData, ' 266 ')) {
                $thiss = explode(":", $this->rawData, 3);
                $this->Debug(8, BLUEBG . WHITE . "{$thiss['2']}" . BLACKBG . NORMAL);
            } else if (strpos($this->rawData, ' MODE ')) {
                $this->ParseMode($this->rawData);
            } else if (strpos($this->rawData, ' JOIN ')) {
                $this->ParseJoin($this->rawData);
            } else if (strpos($this->rawData, ' PART ')) {
                $this->ParsePart($this->rawData);
            } else if (strpos($this->rawData, ' KICK ')) {
                $this->ParseKick($this->rawData);
            } else if (strpos($this->rawData, ' NICK ')) {
                $this->ParseNick($this->rawData);
            } else if (strpos($this->rawData, ' QUIT ')) {
                $this->ParseQuit($this->rawData);
            } else if (strpos($this->rawData, ' PONG ')) {
                $this->ar_botcfg['pingtime'] = time();
            } else if (strpos($this->rawData, ' 303 ')) {
                $this->ParseISON($this->rawData);
            } else {
                $this->Debug(8, REDBG . YELLOW . "$this->rawData" . BLACKBG . NORMAL);
            }
        }
    }

}
