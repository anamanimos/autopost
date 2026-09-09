<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCampaign extends Model
{
    protected $fillable = [
        'name',
        'content_type',
        'caption',
        'target_time',
        'images_per_post',
        'repeat_type',
        'start_date',
        'end_date',
        'exclude_days',
        'is_continuous',
        'status',
    ];

    protected $casts = [
        'exclude_days' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_continuous' => 'boolean',
    ];

    public function targets(): HasMany
    {
        return $this->hasMany(CampaignTarget::class);
    }

    public function connectedAccounts(): BelongsToMany
    {
        return $this->belongsToMany(ConnectedAccount::class, 'campaign_targets')
            ->withPivot('platform_target')
            ->withTimestamps();
    }

    public function mediaFiles(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'campaign_media')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(PublishLog::class);
    }

    /**
     * Cek apakah ada target akun yang nonaktif (Soft-Deactivation)
     */
    public function hasInactiveAccount(): bool
    {
        return $this->connectedAccounts->contains(fn($acc) => !$acc->is_active);
    }
}
