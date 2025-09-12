<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitiesLog extends Model
{
    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'identifier',
        'status',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
