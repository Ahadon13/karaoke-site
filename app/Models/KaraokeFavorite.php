<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaraokeFavorite extends Model
{
    protected $fillable = [
        'video_id',
        'title',
        'thumbnail_url',
        'channel_title',
        'rating',
        'favorited_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'favorited_at' => 'datetime',
        ];
    }
}
