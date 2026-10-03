<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokCredential extends Model
{
    protected $table = 'tiktok_credentials';

    protected $fillable = [
        'client_key',
        'client_secret',
        'status',
        'notes',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
    ];

    public static function getActive(): self
    {
        return static::firstOrCreate([], [
            'status' => 'unconfigured',
        ]);
    }

    public function getClientKey(): ?string
    {
        return $this->client_key ?: env('TIKTOK_CLIENT_KEY');
    }

    public function getClientSecret(): ?string
    {
        return $this->client_secret ?: env('TIKTOK_CLIENT_SECRET');
    }

    public function hasSecret(): bool
    {
        return !empty($this->client_secret) || !empty(env('TIKTOK_CLIENT_SECRET'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->getClientKey()) && !empty($this->getClientSecret());
    }
}
