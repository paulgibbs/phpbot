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
            }
        }
    }

}
