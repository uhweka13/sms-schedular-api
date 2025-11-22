<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsModel extends Model
{
    protected $fillable = [
        'recipients',
        'message',
        'send_at',
        'status',
        'provider_response',
        'sender'
    ];

    protected $casts = [
        'recipients' => 'array',
        'send_at'    => 'datetime',
    ];
}
