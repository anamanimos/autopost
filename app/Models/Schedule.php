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

        if (str_starts_with($this->media_path, 'http://') || str_starts_with($this->media_path, 'https://')) {
            return $this->media_path;
        }

        $mediaDisk = config('filesystems.media_disk', env('MEDIA_DISK', 'local'));
        if ($mediaDisk === 'r2') {
            $cleanPath = ltrim(str_replace('/storage/', '', $this->media_path), '/');
            $r2Url = config('filesystems.disks.r2.url');
            if (!empty($r2Url)) {
                return rtrim($r2Url, '/') . '/' . $cleanPath;
            }
            return \Illuminate\Support\Facades\Storage::disk('r2')->url($cleanPath);
        }

        $path = parse_url($this->media_path, PHP_URL_PATH) ?? $this->media_path;
        return asset($path);
    }

    public function getMediaUrlsAttribute(): array
    {
        $paths = $this->media_paths;
        if (empty($paths)) {
            $paths = !empty($this->media_path) ? [$this->media_path] : [];
        }

        $mediaDisk = config('filesystems.media_disk', env('MEDIA_DISK', 'local'));
        $r2Url = config('filesystems.disks.r2.url');

        return array_map(function ($p) use ($mediaDisk, $r2Url) {
            if (empty($p)) return '';
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                return $p;
            }
            if ($mediaDisk === 'r2') {
                $cleanPath = ltrim(str_replace('/storage/', '', $p), '/');
                if (!empty($r2Url)) {
                    return rtrim($r2Url, '/') . '/' . $cleanPath;
                }
                return \Illuminate\Support\Facades\Storage::disk('r2')->url($cleanPath);
            }
            $path = parse_url($p, PHP_URL_PATH) ?? $p;
            return asset($path);
        }, $paths);
    }
}
