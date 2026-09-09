<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishLog extends Model
{
    protected $fillable = [
        'schedule_id',
        'project_campaign_id',
        'connected_account_id',
        'platform',
        'content_type',
        'action_status',
        'media_id',
        'container_id',
        'response_payload',
        'error_message',
        'error_code',
        'error_subcode',
        'executed_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'executed_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function projectCampaign(): BelongsTo
    {
        return $this->belongsTo(ProjectCampaign::class);
    }

    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }
}
