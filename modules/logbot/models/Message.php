<?php

namespace DMBot\Modules\Logbot\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Message Model
 */
class Message extends Model {

    protected $table = 'LOGBOT_logs';
    protected $fillable = ['channel_id', 'type', 'author_id', 'message','privacy'];

    public function author() {
        return $this->hasOne('\DMBot\Modules\Logbot\Models\Message');
    }
    
    public function channel() {
        return $this->hasOne('\DMBot\Modules\Logbot\Models\Channel');
    }

}
