<?php
/********************************************************
* sockets.class.php By Wammy (wammy21@gmail.com)
*
* The core of the DM Bot
* 
* This Code was written with the assistance of
* The Place of Dangerous Minds
* http://WWW.Dangerous-Minds.NET who has supported me
* in many of my projects. Please take the time to visit
* them and support them as well.
*
* Known Issues:
* 
*
*
*
* Created on 00/00/05 By Wammy
* Last Edited on 
*
********************************************************/

Class syst_Socket
{
	public $Socket;
	private $end;
	private $Queue;
	private $proto;
	public $connected;

	/**
     * Construct the Sockets Class
     *
     * @param        int $proto The protocol type.
     */
	function __construct($proto = SOL_TCP)
	{
		if($proto == SOL_TCP) {
			$type = SOCK_STREAM;
		} elseif($proto == SOL_UDP) {
			$type = SOCK_DGRAM;
		} else {
			trigger_error('\'' . $proto . '\' is not a valid protocol type.' . "\n");
		}

		$this->Socket = socket_create(AF_INET, $type, $proto);
		$this->proto = $proto;

		$this->end = "\n";
		$this->Queue = '';
	}

	/**
     * Connect to a given host.
     *
     * @param        string $host Domain or IP To Connect to.
     * @param        int $port The port to connect to.
     * @return       int results from socket_connect()
     */
	public function Connect($host, $port)
	{
		$res = @socket_connect($this->Socket, $host, $port) or trigger_error("Socket Error: ".socket_strerror(socket_last_error($this->Socket)));
		if(false !== $res) {
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
	public function Write($string = "\x0")
	{
		$res = @socket_write($this->Socket, $string,strlen($string)) or trigger_error("Socket Error: ".socket_strerror(socket_last_error($this->Socket)));
		return $res;
	}

	function Read($int_length,$int_type=PHP_NORMAL_READ)
	{
		$int_res = 1;
		if(($tmp=strpos(strtoupper(PHP_OS), "WIND")) !== false) {
			$int_res.=@socket_recv($this->Socket,$txt_read,$int_length,0);


		} else {
			$txt_read = @socket_read($this->Socket,$int_length,$int_type);
		}
		if($txt_read === false || $int_res < 0) {
			return -2;
		} elseif (strlen($txt_read) == 0 || $int_res == 0) {
			return -1;
		} else
		return $txt_read;


	}

	/**
     * Check if there is data available.
     *
     * @return       bool Data Queued
     */
	public function Data_Queued()
	{
		$arr = array($this->Socket);
		if(socket_select($arr, $t = null, $te = null, 0) > 0)
		return true;
		else
		return false;
	}

	/**
     * Close Socket.
     *
     */
	public function Close() // Close the socket.
	{
		@socket_shutdown($this->Socket,2);
		@socket_close($this->Socket);
		$this->connected = false;
	}

}

?>
