<?php

namespace DMBot\Modules;

use DMBot\Module;
use DMBot\Modules;
use DMBot\Config;
use DMBot\Tokeniser;
use DMBot\IRC\Message as IRCMessage;
use Illuminate\Database\Capsule\Manager as DB;
use DMBot\Modules\Logbot\Models\Author;
use DMBot\Modules\Logbot\Models\Message;
use DMBot\Modules\Logbot\Models\Channel;

/**
 * Message type: MESSAGE
 */
define('LB_TYPE_MSG', '1');

/**
 * Message type: URL
 */
define('LB_TYPE_URL', '2');

/**
 * Message type: IMAGE
 */
define('LB_TYPE_PIC', '3');

/**
 * Logbot module - logs messages.
 */
class Logbot extends Module {

    public $version = '2.0';
    public $name = 'Log Bot';
    public $description = 'Chat logging bot';
    public $help = '!logbot help';

    /**
     *
     * @var bool If the setup notification has been sent (if required)
     */
    private $_setupNoticeSent = false;

    /**
     * Returns true if the db is setup.
     * @return bool
     */
    protected function _isSetup() {
        if (Config::get('enable_db') != 1) {
            return false;
        }
        return DB::schema()->hasTable('LOGBOT_authors');
    }

    /**
     * Sets up the DB tables.
     */
    protected function _setupDB() {
        if (Config::get('enable_db') == 1) {
            DB::schema()->create('LOGBOT_authors', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->boolean('privacy', 0);
                $table->boolean('privacy_warning', 1);
                $table->string('quit_message');
                $table->timestamps();
            });

            DB::schema()->create('LOGBOT_channels', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->boolean('enable_logging', 0);
                $table->timestamps();
            });

            DB::schema()->create('LOGBOT_logs', function ($table) {
                $table->increments('id');
                $table->integer('channel_id');
                $table->integer('author_id');
                $table->integer('type');
                $table->text('message');
                $table->boolean('privacy', 0);
                $table->timestamps();
            });

            DB::connection()->statement('ALTER TABLE LOGBOT_logs ADD FULLTEXT full(message)');
        } else {
            trigger_error(Tokeniser::tokenise('logbot', 'SETUP_DB_NOT_CONFIGURED'), E_USER_NOTICE);
        }
    }

    /**
     * Remove the db tables
     */
    protected function _removeDB() {
        if (Config::get('enable_db') == 1) {
            DB::schema()->drop('LOGBOT_authors');
            DB::schema()->drop('LOGBOT_channels');
            DB::schema()->drop('LOGBOT_logs');
            $this->_setupNoticeSent = false;
        } else {
            trigger_error(Tokeniser::tokenise('logbot', 'SETUP_DB_NOT_CONFIGURED'), E_USER_NOTICE);
        }
    }

    /**
     * Sets ignore/listen setting for the specified Author.
     * @param Author $Author
     * @param bool $bIgnore
     */
    protected function _setPrivacy(Author $Author, $bIgnore) {
        $Author->privacy = $bIgnore;
        $Author->save();
    }

    /**
     * Returns listen/ignore setting for Author (in human-readable form)
     * @param Author $Author
     * @return string 'listen' or 'ignore' 
     */
    protected function _getPrivacy(Author $Author) {
        return ($Author->privacy == 1) ? 'ignore' : 'listen';
    }

    /**
     * Sets whether the user should be PM'd the privacy warning on join.
     * @param Author $Author
     * @param bool $bEnable
     */
    protected function _setPrivacyWarning(Author $Author, $bEnable) {
        $Author->privacy_warning = $bEnable;
        $Author->save();
    }

    protected function _sendPrivacyChangeReply($nick, $message) {
        global $bot;
        $messages = [
            $message,
            Tokeniser::tokenise('logbot', 'PRIVACY_SETTINGS_CHANGED_2', [])
        ];
        $bot->PrivMsg($messages, $nick);
    }

    /**
     * Handle PRIVMSG events
     * @global \DMBot\Bot $bot
     * @param \DMBot\IRC\Message $Message
     */
    public function PRIVMSG(IRCMessage $Message) {
        global $bot;

        $nick = $Message->nick;
        $channel = $Message->channel;
        $msg = $Message->data;

        if (preg_match('/^!logbot help$/', $msg)) {
            if ($channel == 'PM') {
                $channel = Config::get('irc_channels');
            }
            $this->_sendHelpMessage($nick, $channel);
        }

        if ($channel == 'PM') {
            if ($this->_isSetup()) {
                $Author = Author::firstOrCreate(['name' => $nick]);

                /* Handle ignore/listen requests */
                if (preg_match('/^!ignore$/', $msg)) {
                    $this->_setPrivacy($Author, true);
                    $this->_sendPrivacyChangeReply($nick, Tokeniser::tokenise('logbot', 'PRIVACY_SETTINGS_CHANGED_IGNORE', []));
                } else if (preg_match('/^!listen$/', $msg)) {
                    $this->_setPrivacy($Author, false);
                    $this->_sendPrivacyChangeReply($nick, Tokeniser::tokenise('logbot', 'PRIVACY_SETTINGS_CHANGED_LISTEN', []));
                }
            }
            if (preg_match('/^!logbot setup$/', $msg) && ($nick == Config::get('owner'))) {
                $bot->PrivMsg(
                        Tokeniser::tokenise('logbot', 'SETUP_CONFIRM', []), $nick
                );
            } else if (preg_match('/^!logbot setup confirm$/', $msg) && ($nick == Config::get('owner'))) {
                try {
                    $this->_setupDB();
                    $bot->PrivMsg(
                            Tokeniser::tokenise('logbot', 'SETUP_COMPLETE', []), $nick
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    $bot->PrivMsg(
                            Tokeniser::tokenise('logbot', 'SETUP_ERROR', ['error' => $e->getMessage()]), $nick
                    );
                }
            } else if (preg_match('/^!logbot uninstall$/', $msg) && ($nick == Config::get('owner'))) {
                $bot->PrivMsg(
                        Tokeniser::tokenise('logbot', 'UNINSTALL_CONFIRM', []), $nick
                );
            } else if (preg_match('/^!logbot uninstall confirm$/', $msg) && ($nick == Config::get('owner'))) {
                try {
                    $this->_removeDB();
                    $bot->PrivMsg(
                            Tokeniser::tokenise('logbot', 'UNINSTALL_COMPLETE', []), $nick
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    $bot->PrivMsg(
                            Tokeniser::tokenise('logbot', 'SETUP_ERROR', ['error' => $e->getMessage()]), $nick
                    );
                }
            }
        } else if ($this->_isSetup()) {
            $Author = Author::firstOrCreate(['name' => $nick]);
            $Channel = Channel::firstOrCreate(['name' => $channel]);

            if (preg_match('/^!logbot on$/', $msg) && ($nick == Config::get('owner'))) {
                $Channel->enable_logging = true;
                $Channel->save();
                $bot->PrivMsg( Tokeniser::tokenise('logbot', 'LOGBOT_ON'), $Channel->name);
            }
            if (preg_match('/^!logbot off$/', $msg) && ($nick == Config::get('owner'))) {
                $Channel->enable_logging = false;
                $Channel->save();
                $bot->PrivMsg( Tokeniser::tokenise('logbot', 'LOGBOT_OFF'), $Channel->name);
            }
            if ($Channel->enable_logging) {
                /* This may be annoying - leaving it to on join only. 
                  if ($Author->privacy_warning) {
                  $this->_sendPrivacyWarning($Author, $Message);
                  } */
                $this->_handleMessage($Author, $Channel, $Message);
            }
        }
    }

    /**
     * Handles processing incoming messages
     * @global \DMBot\Bot $bots
     * @param Author $Author
     * @param Channel $Channel
     * @param \DMBot\IRC\Message $Message
     */
    private function _handleMessage(Author $Author, Channel $Channel, IRCMessage $Message) {
        global $bot;
        $messageType = LB_TYPE_MSG;
        /* Check it is a URI - big regex from http://www.ietf.org/rfc/rfc3986.txt */
        if (strpos($Message->data, '://') && !strpos($Message->data, ' ')) {
            $matches = [];
            //TODO: ereg is deprecated
            $is_match = @ereg('^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?', $Message->data, $matches);

            /* $matches[2] = protocol
              $matches[4] = 'www.example.com'
              $matches[5] = everything after the domain (prefixed with "/") */

            /* only allow picture URLs from HTTP or FTP protocols */
            if ($is_match && preg_match('/^(http|ftp)/i', $matches[2])) {
                if (isset($matches[5]) && preg_match('/[aA-zZ0-9]+\.(jpg|gif|png|jpeg|bmp|pcx|tga|pcx)$/i', $matches[5])) {
                    $messageType = LB_TYPE_PIC;
                    /* TODO: ...process images ... */
                } else {
                    $messageType = LB_TYPE_URL;
                }
            }

            /* searches for more URI protocols in addition to the ones in the above preg_match() call */
            if ($is_match && (($messageType == LB_TYPE_URL) || preg_match('/^(irc)/i', $matches[2]))) {

                /* TODO: ...process URLS ... */
            }
        }

        //Save the message to the DB.
        try {
            //TODO: not sure if we should just not log the message if it is private? 
            Message::create([
                'author_id' => $Author->id,
                'channel_id' => $Channel->id,
                'message' => $Message->data,
                'privacy' => $Author->privacy,
                'type' => $messageType
            ]);
        } catch (\Doctrine\DBAL\Driver\PDOException $e) {
            $bot->PrivMsg(
                    Tokeniser::tokenise('logbot', 'SETUP_ERROR', ['error' => $e->getMessage()]), $nick
            );
        }
    }

    /**
     * Handle JOIN events.
     * @global \DMBot\Bot $bot
     * @param \DMBot\IRC\Message $Message
     * @return null
     */
    public function JOIN(IRCMessage $Message) {
        global $bot;

        $nick = $Message->nick;
        $channel = $Message->channel;

        if ($nick == Config::get('irc_nick')) {
            if (!$this->_isSetup() && !$this->_setupNoticeSent) {
                if (Config::get('enable_db') != 1) {
                    $bot->PrivMsg(Tokeniser::tokenise('logbot', 'SETUP_DB_NOT_CONFIGURED'), Config::get('owner'));
                } else {
                    $bot->PrivMsg(Tokeniser::tokenise('logbot', 'SETUP_NEEDED'), Config::get('owner'));
                }
                $this->_setupNoticeSent = true; //only send this out once. 
            }
            return; /* Don't process our own joins. */
        }

        if (!$this->_isSetup()) {
            return; //Silent until we are setup.
        }

        $Author = Author::firstOrCreate(['name' => $nick]);
        $Channel = Channel::firstOrCreate(['name' => $channel]);

        /* only show privacy warning once per not-seen-before nickname */
        if ($Channel->enable_logging && $Author->privacy_warning) {
            $this->_sendPrivacyWarning($Author, $Message);
        }
    }

    /**
     * Send the privacy warning.
     * @global \DMBot\Bot $bot
     * @param Author $Author
     * @param \DMBot\IRC\Message $Message
     */
    private function _sendPrivacyWarning(Author $Author, IRCMessage $Message) {
        global $bot;
        $messages = [];
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_1', ['nick' => $Author->name, 'channel' => $Message->channel, 'botname' => Config::get('irc_nick')]);
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_2', ['channel' => $Message->channel]);
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_3');
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_4');
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_5');
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_6');
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_7');
        $bot->PrivMsg($messages, $Author->name);

        $Author->privacy_warning = false;
        $Author->save();
    }

    /**
     * Sends the specified user the help message (available commands, etc)
     * @global \DMBot\Bot $bot
     * @param Author $Author
     * @param Channel $Channel
     */
    private function _sendHelpMessage($nick, $channel) {
        global $bot;
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_1', ['nick' => $nick, 'channel' => $channel, 'botname' => Config::get('irc_nick')]);
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_2', ['channel' => $channel]);
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_3');
        $messages[] = Tokeniser::tokenise('logbot', 'PRIVACY_WARNING_4');

        $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_1');
        $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_IGNORE');
        $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_LISTEN');

        if ($nick == Config::get('owner')) {
            $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_2');
            $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_INSTALL');
            $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_UNINSTALL');

            $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_3');
            $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_LOG_ON');
            $messages[] = Tokeniser::tokenise('logbot', 'HELP_MSG_LOG_OFF');
        }
        $bot->PrivMsg($messages, $nick);
    }

}

Modules::addModule('logbot', 'DMBot\Modules\Logbot');
