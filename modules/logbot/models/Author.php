<?php

namespace DMBot\Modules\Logbot\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Message Author Model
 */
class Author extends Model {

    protected $table = 'LOGBOT_authors';
    protected $fillable = ['name', 'privacy', 'privacy_warning'];
    protected $attributes = [
        'privacy' => 0,
        'privacy_warning' => 1
    ];

    public function messages() {
        return $this->hasMany('\DMBot\Modules\Logbot\Models\Message');
    }

}
