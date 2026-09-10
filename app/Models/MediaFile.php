<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFile extends Model
{
    protected $fillable = [
        'original_name',
        'file_path',
        'file_hash',
        'mime_type',
        'file_size',
        'media_type',
    ];

    public function projectCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCampaign::class, 'campaign_media')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) return '';

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $mediaDisk = config('filesystems.media_disk', env('MEDIA_DISK', 'local'));
        if ($mediaDisk === 'r2') {
            $cleanPath = ltrim(str_replace('/storage/', '', $this->file_path), '/');
            $r2Url = config('filesystems.disks.r2.url');
            if (!empty($r2Url)) {
                return rtrim($r2Url, '/') . '/' . $cleanPath;
            }
            return \Illuminate\Support\Facades\Storage::disk('r2')->url($cleanPath);
        }

        $path = parse_url($this->file_path, PHP_URL_PATH) ?? $this->file_path;
        return asset($path);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->media_type === 'video' || str_starts_with($this->mime_type ?? '', 'video/');
    }
}
