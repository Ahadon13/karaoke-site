<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaraokeQueueItem extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PLAYING = 'playing';

    public const STATUS_DONE = 'done';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'karaoke_room_id',
        'singer_name',
        'video_id',
        'title',
        'thumbnail_url',
        'channel_title',
        'searched_keyword',
        'position',
        'status',
        'score',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'score' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(KaraokeRoom::class, 'karaoke_room_id');
    }
}
