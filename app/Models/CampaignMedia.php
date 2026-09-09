<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMedia extends Model
{
    protected $table = 'campaign_media';

    protected $fillable = [
        'project_campaign_id',
        'media_file_id',
        'sort_order',
    ];

    public function projectCampaign(): BelongsTo
    {
        return $this->belongsTo(ProjectCampaign::class);
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }
}
