<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KaraokeSong extends Model
{
    public const FEATURE_ORIGINAL = 'original';

    public const FEATURE_VOCALS = 'vocals';

    public const FEATURE_MELODY = 'melody';

    public const FEATURE_DUET = 'duet';

    public const FEATURE_ENGLISH = 'english';

    protected $fillable = [
        'video_id',
        'title',
        'artist_name',
        'channel_title',
        'thumbnail_url',
        'published_at',
        'source_query',
        'features',
        'genres',
        'language',
        'search_count',
        'played_count',
        'cached_at',
        'last_played_at',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'genres' => 'array',
            'published_at' => 'datetime',
            'cached_at' => 'datetime',
            'last_played_at' => 'datetime',
            'search_count' => 'integer',
            'played_count' => 'integer',
        ];
    }

    public function scopeFeature(Builder $query, ?string $feature): Builder
    {
        if (blank($feature) || $feature === 'all') {
            return $query;
        }

        return $query->whereJsonContains('features', $feature);
    }

    public function scopeGenre(Builder $query, ?string $genre): Builder
    {
        if (blank($genre) || $genre === 'all') {
            return $query;
        }

        return $query->whereJsonContains('genres', $genre);
    }

    public function toVideoArray(): array
    {
        return [
            'video_id' => $this->video_id,
            'title' => $this->title,
            'artist_name' => $this->artist_name,
            'channel_title' => $this->channel_title,
            'thumbnail_url' => $this->thumbnail_url,
            'published_at' => optional($this->published_at)->toISOString(),
            'features' => $this->features ?: [],
            'genres' => $this->genres ?: [],
            'language' => $this->language,
            'played_count' => $this->played_count,
        ];
    }
}
