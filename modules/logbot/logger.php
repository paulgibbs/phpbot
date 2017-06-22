<?php		
/* logger - logging module for logbot.  Please see logbot.php. */
class LOGBOT_Logger {
	private $_db;  /* Reference to DB class */
  private $_delicious;  /* Reference to del.icio.us class */
  private $_bUseDelicious=false;  /* do we use Delicious? */


	function __construct() {
		global $Modules;
		$this->_db = &$Modules->ar_Modules['DB']['obj'];
		if (!$this->_db) {
			die("LOGBOT_Logger:__construct - DB module required.");
		}

    $this->_delicious = &$Modules->ar_Modules['DELICIOUS']['obj'];
    if ($this->_delicious) {
	    $this->_bUseDelicious = true;
    }
	}
	
	/* gets nick's ID */
	protected function _getNickID($nick) {
		$nick = mysql_real_escape_string($nick);
		$sql = "SELECT `id` FROM `LOGBOT_authors` WHERE `name`='" . $nick . "' LIMIT 1";
		$result = $this->query($sql);
		
		if ($result === false) {
			/* no match, add to DB */
			$sql = "INSERT INTO `LOGBOT_authors` SET `name`='" . $nick . "'";
			$result = $this->query($sql);
			return $this->_db->get_mysql_insert_id();
		} else {
			return $result[0]['id'];
		}
	}

	/* gets channel's ID */
	protected function _getChannelID($channel) {
		$channel = mysql_real_escape_string($channel);
		$sql = "SELECT `id` FROM `LOGBOT_channels` WHERE `name`='" . $channel . "' LIMIT 1";
		$result = $this->query($sql);
		
		if ($result === false) {
			/* no match, add to DB */
			$sql = "INSERT INTO `LOGBOT_channels` SET `name`='" . $channel . "'";
			$result = $this->query($sql);
			return $this->_db->get_mysql_insert_id();
		} else {
			return $result[0]['id'];
		}
	}
	
	/* adds message to DB */
	protected function _addMessage($nick, $channel, $msg, $type) {
		$nick_id = $this->_getNickID($nick);
		$channel_id = $this->_getChannelID($channel);
		$msg = mysql_real_escape_string($msg);

    $sql = "INSERT INTO `LOGBOT_logs` SET `channel_id`='" . $channel_id
                             . "', `msgtype_id`='" . $type
                             . "', `nickname_id`='" . $nick_id
                             . "', `message`='" . $msg
                             . "', `timeanddate`=NOW()"
                             . "";
    $this->query($sql);
	}
	
	/* convenience function for querying the DB */
	public function query($sql) {
	  return $this->_db->query($sql);
	}

  /* recieves PRIVMSG events */
  /* disambiguation: LB_TYPE_PIC are URIs ending in recognised image file extensions (bmp, jpg etc), with a protocol of HTTP or FTP only.
                     LB_TYPE_URL are all other URIs, with protocols of HTTP, FTP or IRC.
                     LB_TYPE_MSG is for everything else.
  */
	public function PRIVMSG($event) {
    $nick = $event['privmsg']['nick'];
    $channel = $event['privmsg']['channel'];
    $msg = $event['privmsg']['msg'];
    $is_url = false;
    $done = false;

    /* Check it is a URI - big regex from http://www.ietf.org/rfc/rfc3986.txt */
    if (strpos($msg, '://') && !strpos($msg, ' ')) {
	    $is_match = ereg('^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?', $msg, $matches);

	    /* $matches[2] = protocol
	       $matches[4] = 'www.example.com'
         $matches[5] = everything after the domain (prefixed with "/") */
      
      /* only allow picture URLs from HTTP or FTP protocols */
      if ($is_match && preg_match('/^(http|ftp)/i', $matches[2])) {
		    if (isset($matches[5]) && preg_match('/[aA-zZ0-9]+\.(jpg|gif|png|jpeg|bmp|pcx|tga|pcx)$/i', $matches[5])) {
  		    /* picture */
   	      $this->_addMessage($nick, $channel, $msg, LB_TYPE_PIC);
          $done = true;
        } else {
	        $is_url = true;
        }
      }

      /* searches for more URI protocols in addition to the ones in the above preg_match() call */
      if ($is_match && !$done && ($is_url || preg_match('/^(irc)/i', $matches[2]))) {
 	  	  /* link */
  	    $this->_addMessage($nick, $channel, $msg, LB_TYPE_URL);
        $done = true;

        /* add to del.icio.us */
        if ($this->_bUseDelicious) {
          $regex = '/[^\.]+\.([^\.]+)\.[^\.]+$/i'; /* cuts off e.g. 'www', 'com' from 'www.example.com' */
          $this->_delicious->add(preg_replace($regex, "\\1", $matches[4]), $msg, array($nick, $channel));
        }
	    }
    }

    if (!$done) {
	    /* message */
	    $this->_addMessage($nick, $channel, $msg, LB_TYPE_MSG);
    }
	}
}
?>