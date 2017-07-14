<?php

namespace DMBot\Modules\Logbot\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Message Model
 */
class Channel extends Model {

    protected $table = 'LOGBOT_channels';
    protected $fillable = ['name','enable_logging'];

    
    public function messages() {
        return $this->hasMany('\DMBot\Modules\Logbot\Models\Message');
    }

}
