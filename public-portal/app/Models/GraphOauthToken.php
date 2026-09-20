<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraphOauthToken extends Model
{
    protected $primaryKey = 'connection_key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['access_token', 'refresh_token'];
    protected $casts = [
        'access_token' => 'encrypted', 'refresh_token' => 'encrypted',
        'expires_at' => 'immutable_datetime', 'needs_reconnect' => 'boolean',
    ];
}
