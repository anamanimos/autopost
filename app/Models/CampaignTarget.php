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
        return in_array($this->platform_target, ['both', 'instagram_only']);
    }

    public function targetsFacebook(): bool
    {
        return in_array($this->platform_target, ['both', 'facebook_only']);
    }
}
