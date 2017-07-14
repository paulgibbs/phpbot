<?php

namespace DMBot;

use DMBot\IRC\Message;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * The core of the IRC Bot.
 * 
 * @author wammy21@gmail.com
 * @author djpaul@gmail.com
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
     * @var DMBot\Modules Module container. 
     */
    public $modules;

    /**
     * Database access
     * @var Illuminate\Database\Capsule\Manager 
     */
    public $db;

    /**
     *
     * @var bool Whether or not we are registered with the server.  
     */
    private $_registered = false;

    /**
     *
     * @var DMBot\IRC\Message Object containing the details of the current message received. 
     */
    private $_message; //TODO: might not be needed.

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

    /**
     *
     * @var int Last time we received a ping
     */
    private $_pingTime = 0;

    /**
     * 
     * @param string $dir working directory
     * @param string $file config file to load.
     */
    public function __construct($dir, $file) {
        $this->_messageQueueTime = microtime(true);
        $this->_config = new Config($dir, $file);
        $this->_socket = new Net\Socket(SOL_TCP);
        $this->modules = new Modules();

        if ($this->_config->enable_db == 1) {
            $this->db = new DB;
            
            $this->db->addConnection([
                'driver' => 'mysql',
                'host' => $this->_config->db_server,
                'database' => $this->_config->db_database,
                'username' => $this->_config->db_user,
                'password' => $this->_config->db_password
            ]);
            
            $this->db->setAsGlobal(); //So we can access via DB:: 
            $this->db->bootEloquent(); //So we can use eloquent models.
        }

        date_default_timezone_set($this->_config->default_timezone);
    }

    /**
     * Load/Setup the modules.
     */
    public function Modules() {
        $this->modules->loadModules(explode(',', $this->_config->modules));
    }

    /**
     * Output debug information.
     * @param int $lvl Debug level
     * @param string $str Message
     */
    public function Debug($lvl, $str) {
        if ($lvl <= (int) $this->_config->debug_level) {
            echo $str . "\n";
        }
    }

    /**
     * Connect to the server
     */
    public function Connect() {
        $this->_socket->connect($this->_config->server, $this->_config->server_port);
        if ($this->_socket->connected) {
            $this->Debug(8, REDBG . YELLOW . 'Connected.' . BLACKBG . NORMAL);
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

            $this->Debug(8, REDBG . YELLOW . 'Registering.' . BLACKBG . NORMAL);
            $this->_socket->Write("USER dm_bot 0 * :" . $this->_config->irc_name . " " . $this->version . "\n");
            sleep(1);
            $this->_socket->Write("NICK " . $this->_config->irc_nick . "\n");
            $this->_registered = true;
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
     * @param mixed $msg
     * @param string $chan
     */
    public function PrivMsg($msg, $chan) {
        //Add the message to the queue.
        if(is_array($msg)) {
            foreach($msg as $line) {
                $this->_messageQueue[] = ['chan' => $chan, 'msg' => $line];
            }
        } else if (is_string($msg) && $msg != '') {
            $this->_messageQueue[] = ['chan' => $chan, 'msg' => $msg];
        } else {
            trigger_error('PrivMsg Incorrect parameter type: $msg',E_USER_NOTICE);
        }
    }

    /**
     * Listen for incoming data to process
     */
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
                $this->modules->run('TIMER');
                $data_received = $this->_socket->read(1024, PHP_NORMAL_READ);
                if (($data_received == '-2') || ($data_received == '-1')) {
                    trigger_error("Could not read from {$this->_config->server}:{$this->_config->server_port} Reason: " . socket_strerror(socket_last_error($this->_socket->socket)), E_USER_ERROR);
                    $this->_socket->connected = false;
                }
                $data_received = str_replace("\r", "", $data_received);
                $data_received = str_replace("\n", "", $data_received);
                $this->Debug(10, RED . $data_received . NORMAL);
                if (strlen($data_received) > 0) {
                    $Message = new Message($data_received);
                    $this->_processMessage($Message);
                }
            } else {
                $this->modules->run('TIMER');
                if ((int) $this->_pingTime <= time() - (int) $this->_config->ping_intervals) {
                    $this->Debug(7, RED . "Haven't recieved a ping in {$this->_config->ping_intervals} secs. Pinging." . NORMAL);
                    $write = "PING :" . time() . "\n";
                    if ($this->_socket->write($write) === false) {
                        trigger_error("Could not write to {$this->_config->server}:{$this->_config->server_port} Reason: " . socket_strerror(socket_last_error($this->_socket->socket)), E_USER_ERROR);
                        $this->_socket->connected = false;
                    }
                }
            }


            $time = microtime(true);
            if ($time - $this->_messageQueueTime > 1) {
                if (($tmp = count($this->_messageQueue)) > 0) {
                    /* send the command */
                    $ar = current($this->_messageQueue);
                    $key = key($this->_messageQueue);
                    $chan = $ar['chan'];
                    $msg = $ar['msg'];
                    unset($this->_messageQueue[$key]);
                    reset($this->_messageQueue);
                    $msg = str_split($msg, 300);
                    for ($x = 1; $x < count($msg); $x++) {
                        //TODO: this should really be added to the queue at the NEXT array position. 
                        //      otherwise the remaining message gets placed at the end and received out of order (if there are other messages in the queue)
                        $this->Debug(1, "Message too long, queueing the rest");
                        $this->PrivMsg($msg[$x], $chan);
                    }
                    if ($this->_socket->connected) { //check if we are connected
                        $tmp = count($this->_messageQueue);
                        $this->Debug(1, "Sending Message ($tmp left on queue) " . strlen($msg[0]));
                        $this->_socket->write("PRIVMSG $chan :{$msg[0]}\n"); //write to the socket
                    }
                    $this->_messageQueueTime = $time;
                }
            }
        }
    }

    /**
     * Process the incoming message. 
     * @param Message $Message
     */
    private function _processMessage(Message $Message) {
        switch ($Message->type) {
            case 'ping':
                $this->_pingTime = time();
                $this->_socket->write("PONG :" . $Message->data . "\n");
                $this->Debug(7, GREENBG . "Ping? Pong!" . BLACKBG);
                break;
            case 'pong':
                $this->_pingTime = time();
                break;
            case 'privmsg':
                $this->Debug(8, WHITEBG . BLACK . "<{$Message->nick}/{$Message->channel}> Said: {$Message->data}" . NORMAL . BLACKBG);
                if ($Message->data == "Register first.") {
                    $this->Register();
                    $this->Join();
                }
                if(preg_match('/^!'.Config::get('irc_nick').' help$/i', $Message->data)) {
                    $this->_sendHelpMessage($Message);
                }
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'notice':
                $this->Debug(8, YELLOW . "Notice from {$Message->nick}: {$Message->data}" . NORMAL);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'names':
                $this->Debug(8, WHITEBG . BLACK . "People in {$Message->channel}: {$Message->data}" . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'jtopic':
                $this->Debug(8, WHITEBG . BLACK . "Topic For Channel {$Message->channel}: {$Message->data}" . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'jtopicauth':
                $this->Debug(8, WHITEBG . BLACK . "Set By: {$Message->nick} on " . date("F j, Y, g:i a", $Message->data) . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'mode':
                $this->Debug(8, BLUE . BOLD . "{$Message->nick}" . NORMAL . BLUE . " sets mode {$Message->channel} {$Message->modes} {$Message->users}" . NORMAL);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'join':
                $this->Debug(8, BLUE . BOLD . "{$Message->nick} Joined {$Message->channel}" . NORMAL);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'part':
                $this->Debug(8, WHITEBG . BLACK . "{$Message->nick} Left {$Message->channel}" . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'kick':
                $this->Debug(8, WHITEBG . BLACK . "{$Message->nick} Kicked {$Message->users} from {$Message->channel} for: {$Message->data}" . NORMAL . BLACKBG);
                if (strtolower($Message->users) == strtolower($this->_config->irc_name)) {
                    $this->Join($Message->channel); //rejoin if we were kicked. 
                }
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'nick':
                $this->Debug(8, WHITEBG . BLACK . "{$Message->nick} is now known as {$Message->data}" . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'quit':
                $this->Debug(8, WHITEBG . BLACK . "{$Message->nick} has quit: {$Message->data}" . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'ison':
                $this->Debug(8, WHITEBG . BLACK . "ISON result: {$Message->nick}" . NORMAL . BLACKBG);
                $this->modules->run(strtoupper($Message->type), $Message);
                break;
            case 'unregistered':
                $this->Register();
                $this->Join();
                break;
            case '372':
            case '375':
            case '376':
                $this->Debug(8, BLUEBG . WHITE . "{$Message->data}" . BLACKBG . NORMAL);
                break;
            case 'motd':
                $this->Debug(8, BLUEBG . WHITE . "{$Message->data}" . BLACKBG . NORMAL);
                break;
            default:
                $this->Debug(8, REDBG . YELLOW . $Message->rawData . BLACKBG . NORMAL);
                break;
        }
    }
    
    private function _sendHelpMessage(Message $Message) {
        $messages = [];
        $messages[] = 'DMBot v'.$this->version;
        $messages[] = 'Loaded Modules: ';
        
        foreach($this->modules->getModules() as $Module) {
            $messages[] = $Module->name.' v'.$Module->version.' - '.$Module->description;
            $messages[] = $Module->help;
        }
        
        $this->PrivMsg($messages, $Message->nick);
    }

    public function ErrorHandler($errno, $errstr, $errfile, $errline) {

        $ShowFullFilePath = 0; //Show (or not) the full file path on errors.

        if ($ShowFullFilePath == 0) {
            $file = explode(DIRECTORY_SEPARATOR, $errfile); //we dont really need the whole path, do we?
            $num = count($file);
        } elseif ($ShowFullFilePath == 1) {
            $file['0'] = $errfile;
            $num = count($file);
        }

        $errtype = 'Unkown';
        switch ($errno) { //Error types
            case 1:
                $errtype = "Error";
                break;
            case 2:
                $errtype = "Warning";
                break;
            case 4:
                $errtype = "Parse Error";
                break;
            case 8:
                $errtype = "Notice";
                break;
            case 16:
                $errtype = "Core Error";
                $exit = 1;
                break;
            case 32:
                $errtype = "Core Warning";
                break;
            case 64:
                $errtype = "Compile Error";
                $exit = 1;
                break;
            case 128:
                $errtype = "Compile Warning";
                break;
            case 256:
                $errtype = "User Error";
                break;
            case 512:
                $errtype = "User Warning";
                break;
            case 1024:
                $errtype = "User Notice";
                break;
            case 2047:
                $errtype = "Global Error";
                break;
            case 2048:
                $errtype = "Strict Error";
                break;
        }
        $msg = "Error Information ($errtype): Error NO: $errno On Line: $errline In File: " . $file[$num - 1] . " Error: $errstr";

        if ($this->_socket->connected && $this->_registered) {
            $this->PrivMsg($msg, $this->_config->owner);
        }
        $this->Debug(0, REDBG . YELLOW . $msg . BLACKBG . NORMAL);
    }

}
