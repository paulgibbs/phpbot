<?php
define('LB_TYPE_MSG', '1');
define('LB_TYPE_URL', '2');
define('LB_TYPE_PIC', '3');
define('LB_ALLOW_LOGGING', true);
include('logger.php');

/* Version 1.9 - last edited by DJPaul 17/02/06 14:05 GMT.
   logbot - logs what you type.  This class deals with privacy options - its parent, LOGBOT_Logger, is where the actual logging
   takes place.

   Note logbot will now log everything: privacy setting enforcement is to be implemented in the SEARCH ENGINE.
*/
class LOGBOT extends LOGBOT_Logger {
	function __construct() {
		global $Modules;
		$Modules->add_module('LOGBOT', 'Chat logging bot', 'Log Bot', '1.9', '');
		parent::__construct();
	}

  /* Sets ignore/listen setting for the specified nickname. */
  protected function _setPrivacy($nick, $bIgnore) {
	  $nick_id = $this->_getNickID($nick);
	  $sql = "UPDATE `LOGBOT_authors` SET `privacy`='" . (int) $bIgnore . "' WHERE `id`='" . $nick_id . "' LIMIT 1";
    parent::query($sql);
  }

  /* Returns listen/ignore setting for $nick (in human-readable form - 'listen' or 'ignore') */
  protected function _getPrivacy($nick) {
	  $nick_id = $this->_getNickID($nick);
		$sql = "SELECT `privacy` FROM `LOGBOT_authors` WHERE `id`='" . $nick_id . "' LIMIT 1";
		$result = $this->query($sql);

    return ($result[0]['privacy'] == 1) ? 'ignore' : 'listen';
  }

  /*
    Returns true if the user should be PM'd the privacy warning on join.
  */
  protected function _showPrivacyWarning($nick) {
	  $nick_id = $this->_getNickID($nick);
	  $sql = "SELECT `privacywarning` FROM `LOGBOT_authors` WHERE `id`='" . $nick_id . "' LIMIT 1";
    $result = parent::query($sql);

		return (bool) $result[0]['privacywarning'];
  }

  /* Sets whether the user should be PM'd the privacy warning on join. */
  protected function _setPrivacyWarning($nick, $bEnable) {
	  $nick_id = $this->_getNickID($nick);
	  $sql = "UPDATE `LOGBOT_authors` SET `privacywarning`='" . (int) $bEnable . "' WHERE `id`='" . $nick_id . "' LIMIT 1";
    parent::query($sql);
  }

  /* recieves PRIVMSG events */	
	public function PRIVMSG($event) {
		global $DMBot;
    $nick = $event['privmsg']['nick'];
    $channel = $event['privmsg']['channel'];
    $msg = $event['privmsg']['msg'];
    $changedPrivacy = false;


    if ($channel == 'PM') {
      /* Handle ignore/listen requests */
      if (strripos($msg, '!ignore') !== false) {
	      $this->_setPrivacy($nick, true);
	      $changedPrivacy = true;
	    } else if (strripos($msg, '!listen') !== false) {
	      $this->_setPrivacy($nick, false);
	      $changedPrivacy = true;
	    }

	    if ($changedPrivacy) {
		    /* Confirm new privacy settings to user */
        if ($this->_getPrivacy($nick) == 'ignore') {
          $output = tokeniser::tokenise('logbot', 'PRIVACY_SETTINGS_CHANGED_IGNORE', array());
        } else {
	        $output = tokeniser::tokenise('logbot', 'PRIVACY_SETTINGS_CHANGED_LISTEN', array());
        }
        $DMBot->PrivMsg($output, $nick);

        $output = tokeniser::tokenise('logbot', 'PRIVACY_SETTINGS_CHANGED_2', array());
        $DMBot->PrivMsg($output, $nick);
      }
    } else {
      parent::PRIVMSG($event);
    }
  }

  /* recieves JOIN events */
  public function JOIN() {
	  global $DMBot;
		$nick = $DMBot->ar_message['join']['nick'];
		$channel = $DMBot->ar_message['join']['channel'];
	
	  if ($nick == $DMBot->ar_botcfg['nick']) {
      return;
  	}

    /* only show privacy warning once per not-seen-before nickname */
    if ($this->_showPrivacyWarning($nick)) {
      $tokens = array('nick' => $nick, 'channel' => $channel, 'botname' => $DMBot->ar_botcfg['nick']);
      $output = tokeniser::tokenise('logbot', 'PRIVACY_WARNING_1', $tokens);
      $DMBot->PrivMsg($output, $nick);

      $tokens = array('channel' => $channel);
      $output = tokeniser::tokenise('logbot', 'PRIVACY_WARNING_2', $tokens);
      $DMBot->PrivMsg($output, $nick);

      $output = tokeniser::tokenise('logbot', 'PRIVACY_WARNING_3', array());
      $DMBot->PrivMsg($output, $nick);

      $output = tokeniser::tokenise('logbot', 'PRIVACY_WARNING_4', array());
      $DMBot->PrivMsg($output, $nick);

      $output = tokeniser::tokenise('logbot', 'PRIVACY_WARNING_5', array());
      $DMBot->PrivMsg($output, $nick);

      $output = tokeniser::tokenise('logbot', 'PRIVACY_WARNING_6', array());
      $DMBot->PrivMsg($output, $nick);

      $this->_setPrivacyWarning($nick, false);
    }
  }
}
?>