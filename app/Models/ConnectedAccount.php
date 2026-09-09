<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectedAccount extends Model
{
    protected $fillable = [
        'page_id',
        'page_name',
        'page_category',
        'page_access_token',
        'ig_user_id',
        'ig_username',
        'ig_name',
        'ig_profile_picture_url',
        'is_active',
        'ig_publishing_quota_usage',
        'ig_publishing_quota_total',
        'last_synced_at',
        'last_verified_at',
    ];

    protected $casts = [
        'page_access_token' => 'encrypted',
        'is_active' => 'boolean',
        'ig_publishing_quota_usage' => 'integer',
        'ig_publishing_quota_total' => 'integer',
        'last_synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function campaignTargets(): HasMany
    {
        return $this->hasMany(CampaignTarget::class);
    }

    public function projectCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCampaign::class, 'campaign_targets')
            ->withPivot('platform_target')
            ->withTimestamps();
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(PublishLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->ig_username) {
            return "{$this->page_name} (@{$this->ig_username})";
        }
        return $this->page_name;
    }
}
