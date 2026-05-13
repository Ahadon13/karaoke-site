<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaraokePlayHistory extends Model
{
    protected $fillable = [
        'video_id',
        'title',
        'thumbnail_url',
        'channel_title',
        'searched_keyword',
        'played_count',
        'last_played_at',
    ];

    protected function casts(): array
    {
        return [
            'played_count' => 'integer',
            'last_played_at' => 'datetime',
        ];
    }
}
