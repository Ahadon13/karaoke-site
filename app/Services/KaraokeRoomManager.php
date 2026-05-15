<?php

namespace App\Services;

use App\Models\KaraokeQueueItem;
use App\Models\KaraokeRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KaraokeRoomManager
{
    private const DEFAULT_ROOM_CODE = 'MAINROOM';

    public function resolve(?string $roomCode = null): KaraokeRoom
    {
        if ($roomCode === null || trim($roomCode) === '') {
            $roomCode = session()->remember('mykaraoke_room_code', function (): string {
                return 'ROOM'.Str::upper(Str::random(8));
            });
        }

        $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $roomCode ?: self::DEFAULT_ROOM_CODE) ?: self::DEFAULT_ROOM_CODE);
        $code = substr($code, 0, 16) ?: self::DEFAULT_ROOM_CODE;

        $room = KaraokeRoom::firstOrCreate(
            ['code' => $code],
            [
                'name' => $code === self::DEFAULT_ROOM_CODE ? 'Main Room' : null,
                'last_active_at' => now(),
            ],
        );

        $room->forceFill(['last_active_at' => now()])->save();

        return $room;
    }

    /**
     * @return Collection<int, KaraokeQueueItem>
     */
    public function activeQueueItems(KaraokeRoom $room): Collection
    {
        return KaraokeQueueItem::query()
            ->where('karaoke_room_id', $room->id)
            ->whereIn('status', [
                KaraokeQueueItem::STATUS_QUEUED,
                KaraokeQueueItem::STATUS_PLAYING,
            ])
            ->orderBy('position')
            ->limit(24)
            ->get();
    }
}
