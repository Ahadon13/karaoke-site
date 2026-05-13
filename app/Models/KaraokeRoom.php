<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KaraokeRoom extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(KaraokeQueueItem::class);
    }
}
