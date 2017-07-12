<?php

/**
 * 
 */

namespace DMBot;

/**
 * Class that contains the bot functions.
 */
class Bot {

    /**
     *
     * @var string Class Version 
     */
    public $version = '5.0.0b';

    /**
     * Container for all network socket functions.
     * 
     * @var DMBot\Net\Socket Socket Container 
     */
    private $_socket;

    /**
     *
     * @var bool Whether or not we are registered with the server.  
     */
    private $_registered = false;

    /**
     *
     * @var DMBot\IRC\Message Object containing the details of the current message received. 
     */
    private $_message;

    /**
     *
     * @var array Array of queued messages to send to the server 
     */
    private $_messageQueue = [];

    /**
     * I think this was to keep track of how often we are sending messages to the server. 
     * @var float Used to track message send rate
     */
    private $_messageQueueTime = 0.0;

    /**
     *
     * @var DMBot\Config Config Container 
     */
    private $_config;
    private $_pingTime = 0;

    public function __construct($dir, $file) {
        $this->_messageQueueTime = microtime(true);
        $this->_config = new Config($dir, $file);
        $this->_socket = new Net\Socket(SOL_TCP);

        date_default_timezone_set($this->_config->default_timezone);
    }

    public function Debug($lvl, $str) {
        if ($lvl <= $this->config->debug_level) {
            echo $str . "\n";
        }
    }

    public function Connect() {
        $this->_socket->connect($this->_config->server, $this->_config->server_port);
        if ($this->_socket->connected) {
            
        } else {
            trigger_error("Could not connect to {$this->_config->server}:{$this->_config->server_port} Reason: " . socket_strerror(socket_last_error($this->_socket->socket)), E_USER_ERROR);
        }
    }

    /**
     * Register with the server
     * @return boolean if registration was succesfull 
     */
    public function Register() {
        $this->_registered = false;
        if ($this->_socket->connected) {
            $this->_socket->Write("USER dm_bot 0 * :" . $this->_config->irc_name . " " . $this->version . "\n");
            sleep(1);
            $this->_socket->Write("NICK " . $this->_config->irc_nick . "\n");
        } else {
            trigger_error("Could not register with {$this->_config->irc_nick}", E_USER_ERROR);
        }
        return $this->_registered;
    }

    /**
     * Join a channel, leave $channel blank to join the configured channels.
     * @param string $channel Channel to join
     */
    public function Join($channel = '') {
        if ($this->_config->enable_nickserv == 1) {
            $this->PrivMsg("identify {$this->_config->nickserv_password}", "nickserv");
        }
        if (!empty($channel)) {
            $this->_socket->write("JOIN " . $channel . "\n");
        } else {
            $channels = explode(",", $this->_config->irc_channels);

            foreach ($channels as $channel) {
                $this->_socket->write("JOIN " . $channel . "\n");
            }
        }
    }

    /**
     * Send a message
     * @param string $msg
     * @param string $chan
     */
    public function PrivMsg($msg, $chan) {
        //Add the message to the queue.
        if ($msg != '') {
            $this->_messageQueue[] = ['chan' => $chan, 'msg' => $msg];
        }
    }

    public function Listen() {
        $this->_pingTime = time();

        //check if we are still connected
        while ($this->_socket->connected) {

            //check if there is data queued
            $set = [$this->_socket->socket];
            $set_w = [];
            $set_e = [];

            if (socket_select($set, $set_w, $set_e, 1, 0) > 0) {
                //Data available in the socket.
                //$Modules->Run('TIMER');
                $data_received = $this->_socket->read(1024, PHP_NORMAL_READ);
                if (($data_received == "-2") || ($data_received == "-1")) {
                    trigger_error("Could not read from {$this->_config->server}:{$this->_config->server_port} Reason: " . socket_strerror(socket_last_error($this->_socket->socket)), E_USER_ERROR);
                    $this->_socket->connected = false;
                }
                $data_received = str_replace("\r", "", $data_received);
                $data_received = str_replace("\n", "", $data_received);
                if (strlen($data_received) > 0) {

                    $Message = new DMBot\IRC\Message($data_received);

                    switch ($Message->type) {
                        case 'ping':
                            $this->_pingTime = time();
                            $this->_socket->write("PONG :" . $Message->data . "\n");
                            $this->Debug(7, GREENBG . "Ping? Pong!" . BLACKBG);
                            break;
                        case 'privmsg':
                        case 'notice':
                        case 'names':
                        case 'jtopic':
                        case 'jtopicauth':
                        case 'mode':
                        case 'join':
                        case 'part':
                        case 'kick':
                        case 'nick':
                        case 'quit':
                        case 'pong':
                        case 'ison':
                            //$Modules->Run(strtoupper($Message->type));
                            break;
                        case 'unregistered':
                            $this->Register();
                            $this->Join();
                            break;
                    }
                }
            } else {
                $Modules->Run('TIMER');
                if ((int) $this->ar_botcfg['pingtime'] <= time() - (int) $this->ar_botcfg['pinginterval']) {
                    $this->Debug(7, RED . "Havnt recieved a ping in {$this->ar_botcfg['pinginterval']} secs. Pinging." . NORMAL);
                    $write = "PING :" . time() . "\n";
                    if ($this->cl_Socket->Write($write) === false) {
                        trigger_error("Could not write to {$this->ar_botcfg['host']}:{$this->ar_botcfg['port']} Reason: " . socket_strerror(socket_last_error($this->cl_Socket->Socket)), E_USER_ERROR);
                        $this->cl_Socket->connected = false;
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
                if (($tmp = count($this->msgQueue)) > 0) {
                    /* send the command */
                    $ar = current($this->msgQueue);
                    $key = key($this->msgQueue);
                    $chan = $ar['chan'];
                    $msg = $ar['msg'];
                    unset($this->msgQueue[$key]);
                    reset($this->msgQueue);
                    $msg = str_split($msg, 300);
                    for ($x = 1; $x < count($msg); $x++) {
                        $this->Debug(1, "Message too long, queueing the rest");
                        $this->PrivMsg($msg[$x], $chan);
                    }
                    if ($this->cl_Socket->connected) { //check if we are connected
                        $tmp = count($this->msgQueue);
                        $this->Debug(1, "Sending Message ($tmp left on queue) " . strlen($msg[0]));
                        $this->cl_Socket->Write("PRIVMSG $chan :{$msg[0]}\n"); //write to the socket
                    }
                    $this->msgQueueTime = $time;
                }
            }
        }
    }

}
