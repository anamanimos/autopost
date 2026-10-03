<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTarget extends Model
{
    protected $fillable = [
        'project_campaign_id',
        'connected_account_id',
        'platform_target',
    ];

    public function projectCampaign(): BelongsTo
    {
        return $this->belongsTo(ProjectCampaign::class);
    }

    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    public function targetsInstagram(): bool
    {
        return in_array($this->platform_target, ['all', 'both', 'instagram_only', 'ig_threads'])
            || str_contains($this->platform_target, 'instagram')
            || str_contains($this->platform_target, 'ig');
    }

    public function targetsFacebook(): bool
    {
        return in_array($this->platform_target, ['all', 'both', 'facebook_only', 'fb_threads'])
            || str_contains($this->platform_target, 'facebook')
            || str_contains($this->platform_target, 'fb');
    }

    public function targetsThreads(): bool
    {
        return in_array($this->platform_target, ['all', 'threads_only', 'ig_threads', 'fb_threads'])
            || str_contains($this->platform_target, 'threads');
    }

    public function targetsTikTok(): bool
    {
        if ($this->platform_target === 'all') {
            return (bool) ($this->connectedAccount && $this->connectedAccount->hasTikTok());
        }

        return in_array($this->platform_target, ['tiktok_only'])
            || str_contains($this->platform_target, 'tiktok');
    }
}
