<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'project_campaign_id',
        'item_code',
        'media_file_id',
        'media_path',
        'media_paths',
        'target_date',
        'target_time',
        'status',
        'notes',
        'executed_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'media_paths' => 'array',
        'executed_at' => 'datetime',
    ];

    public function projectCampaign(): BelongsTo
    {
        return $this->belongsTo(ProjectCampaign::class, 'project_campaign_id');
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(PublishLog::class);
    }

    public function getMediaUrlAttribute(): string
    {
        if (empty($this->media_path)) return '';
        $path = parse_url($this->media_path, PHP_URL_PATH) ?? $this->media_path;
        return asset($path);
    }

    public function getMediaUrlsAttribute(): array
    {
        $paths = $this->media_paths;
        if (empty($paths)) {
            $paths = !empty($this->media_path) ? [$this->media_path] : [];
        }
        return array_map(function ($p) {
            $path = parse_url($p, PHP_URL_PATH) ?? $p;
            return asset($path);
        }, $paths);
    }
}
