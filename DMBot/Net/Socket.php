<?php
namespace DMBot\Net;

/**
 * PHP Sockets wrapper.
 * Handles the low level socket functions
 * 
 * @author wammy21@gmail.com
 */
class Socket {

    /**
     *
     * @var mixed Socket resource handle
     */
    public $socket;
    
    /**
     *
     * @var string EOL character/string 
     */
    private $_end;
    
    /**
     *
     * @var string Message Queue 
     */
    private $_queue;
    
    /**
     *
     * @var int Socket Protocol  
     */
    private $_protocol;
    
    /**
     *
     * @var bool Wether or not the socket is connected. 
     */
    public $connected = false;

    /**
     * Construct the Sockets Class
     *
     * @param int $proto The protocol type.
     */
    function __construct($proto = SOL_TCP) {
        if ($proto == SOL_TCP) {
            $type = SOCK_STREAM;
        } elseif ($proto == SOL_UDP) {
            $type = SOCK_DGRAM;
        } else {
            trigger_error('\'' . $proto . '\' is not a valid protocol type.' . "\n");
        }

        $this->socket = socket_create(AF_INET, $type, $proto);
        $this->_protocol = $proto;

        $this->_end = "\n";
        $this->_queue = '';
    }

    /**
     * Connect to a given host.
     *
     * @param        string $host Domain or IP To Connect to.
     * @param        int $port The port to connect to.
     * @return       int results from socket_connect()
     */
    public function connect($host, $port) {
        $res = @socket_connect($this->socket, $host, $port) or trigger_error("Socket Error: " . socket_strerror(socket_last_error($this->socket)));
        if (false !== $res) {
            $this->connected = true;
        } else {
            $this->connected = false;
        }
        return $res;
    }

    /**
     * Write to connected Socket.
     *
     * @param        string $string Data to write.
     * @return       int results from socket_write()
     */
    public function write($string = "\x0") {
        $res = @socket_write($this->socket, $string, strlen($string)) or trigger_error("Socket Error: " . socket_strerror(socket_last_error($this->socket)));
        return $res;
    }

    /**
     * Read from the connected socket.
     * @param int $length
     * @param int $type
     * @return string data received
     */
    function read($length, $type = PHP_NORMAL_READ) {
        $receivedSize = 1;
        /*
         * This was due to an issue at some point with the way socket_read was 
         * handled on windows - this may no longer be required. 
         */
        if (($tmp = strpos(strtoupper(PHP_OS), "WIND")) !== false) {
            $receivedSize.=@socket_recv($this->socket, $txt_read, $length, 0);
        } else {
            $txt_read = @socket_read($this->socket, $length, $type);
        }
        
        if ($txt_read === false || $receivedSize < 0) {
            return '-2';
        } elseif (strlen($txt_read) == 0 || $receivedSize == 0) {
            return '-1';
        }
        
        return $txt_read;
    }

    /**
     * Check if there is data available.
     *
     * @return       bool Data Queued
     */
    public function dataQueued() {
        $arr = array($this->socket);
        if (socket_select($arr, $t = null, $te = null, 0) > 0)
            return true;
        else
            return false;
    }

    /**
     * Close Socket.
     *
     */
    public function close() { // Close the socket.
        @socket_shutdown($this->socket, 2);
        @socket_close($this->socket);
        $this->connected = false;
    }

}
