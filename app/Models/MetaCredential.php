<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaCredential extends Model
{
    protected $table = 'meta_credentials';

    protected $fillable = [
        'app_id',
        'app_secret',
        'threads_app_id',
        'threads_app_secret',
        'graph_version',
        'user_access_token',
        'system_user_token',
        'token_type',
        'token_expires_at',
        'token_status',
        'webhook_verify_token',
        'last_verified_at',
        'notes',
    ];

    protected $casts = [
        'app_secret' => 'encrypted',
        'threads_app_secret' => 'encrypted',
        'user_access_token' => 'encrypted',
        'system_user_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public static function getActive(): self
    {
        return static::firstOrCreate([], [
            'graph_version' => env('META_GRAPH_VERSION', 'v22.0'),
            'token_status' => 'unconfigured',
        ]);
    }

    public function getActiveToken(): ?string
    {
        if ($this->token_type === 'system_user' && !empty($this->system_user_token)) {
            return $this->system_user_token;
        }
        return $this->user_access_token;
    }

    public function getThreadsAppId(): ?string
    {
        return $this->threads_app_id ?: env('THREADS_APP_ID');
    }

    public function getThreadsAppSecret(): ?string
    {
        return $this->threads_app_secret ?: env('THREADS_APP_SECRET');
    }
}
