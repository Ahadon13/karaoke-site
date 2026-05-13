<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaraokeCuratedSong extends Model
{
    protected $fillable = [
        'video_id',
        'title',
        'thumbnail_url',
        'channel_title',
        'description',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
