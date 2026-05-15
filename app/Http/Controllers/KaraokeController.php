<?php

namespace App\Http\Controllers;

use App\Models\KaraokeCuratedSong;
use App\Models\KaraokeFavorite;
use App\Models\KaraokePlayHistory;
use App\Models\KaraokeQueueItem;
use App\Models\KaraokeRoom;
use App\Models\KaraokeSong;
use App\Services\KaraokeSongCatalog;
use App\Services\KaraokeRoomManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KaraokeController extends Controller
{
    public function index(Request $request, KaraokeSongCatalog $songCatalog, KaraokeRoomManager $roomManager): View
    {
        $room = $roomManager->resolve($request->query('room'));
        $feature = $request->query('feature', 'all');
        $genre = $request->query('genre');

        if (filled($genre)) {
            try {
                $songCatalog->ensureGenreSongs((string) $genre, 12);
            } catch (\RuntimeException) {
                // The cached catalog still renders when YouTube is unavailable.
            }
        }

        $catalogSongs = $songCatalog->catalogQuery(
            query: $request->query('q'),
            feature: is_string($feature) ? $feature : 'all',
            genre: is_string($genre) ? $genre : null,
        )
            ->limit(24)
            ->get()
            ->map->toVideoArray()
            ->values()
            ->all();

        return view('karaoke.index', [
            'activeRoom' => $room,
            'queueItems' => $roomManager->activeQueueItems($room),
            'catalogVideos' => $catalogSongs,
            'featureFilters' => KaraokeSongCatalog::FEATURE_FILTERS,
            'genreFilters' => $songCatalog->topGenres(),
            'activeFeature' => is_string($feature) ? $feature : 'all',
            'activeGenre' => is_string($genre) ? $genre : null,
            'curatedVideos' => KaraokeCuratedSong::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(),
            'trendingVideos' => KaraokeSong::query()
                ->orderByDesc('played_count')
                ->orderByDesc('search_count')
                ->orderByDesc('cached_at')
                ->limit(8)
                ->get()
                ->map->toVideoArray()
                ->values()
                ->all(),
            'recentVideos' => KaraokeSong::query()
                ->orderByDesc('last_played_at')
                ->orderByDesc('cached_at')
                ->limit(8)
                ->get()
                ->map->toVideoArray()
                ->values()
                ->all(),
            'favoriteVideos' => KaraokeFavorite::query()
                ->orderByDesc('favorited_at')
                ->limit(12)
                ->get(),
        ]);
    }

    public function storePlayHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'video_id' => ['required', 'string', 'max:32'],
            'title' => ['required', 'string', 'max:255'],
            'thumbnail_url' => ['nullable', 'string', 'max:255'],
            'channel_title' => ['nullable', 'string', 'max:255'],
            'searched_keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $history = KaraokePlayHistory::firstOrNew([
            'video_id' => $validated['video_id'],
        ]);

        $history->fill([
            'title' => $validated['title'],
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'channel_title' => $validated['channel_title'] ?? null,
            'searched_keyword' => $validated['searched_keyword'] ?? null,
            'last_played_at' => now(),
        ]);

        $history->played_count = $history->exists ? $history->played_count + 1 : 1;
        $history->save();

        KaraokeSong::query()
            ->where('video_id', $validated['video_id'])
            ->update([
                'played_count' => DB::raw('played_count + 1'),
                'last_played_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'history' => $history->fresh(),
        ]);
    }

    public function storeFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'video_id' => ['required', 'string', 'max:32'],
            'title' => ['required', 'string', 'max:255'],
            'thumbnail_url' => ['nullable', 'string', 'max:255'],
            'channel_title' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $favorite = KaraokeFavorite::firstOrNew([
            'video_id' => $validated['video_id'],
        ]);

        $favorite->fill([
            'title' => $validated['title'],
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'channel_title' => $validated['channel_title'] ?? null,
            'rating' => $validated['rating'] ?? $favorite->rating,
            'favorited_at' => $favorite->favorited_at ?? now(),
        ]);

        $favorite->save();

        return response()->json([
            'success' => true,
            'favorite' => $favorite->fresh(),
        ]);
    }

    public function destroyFavorite(string $videoId): JsonResponse
    {
        KaraokeFavorite::query()
            ->where('video_id', $videoId)
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function storeQueueItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_code' => ['nullable', 'string', 'max:16', 'regex:/^[A-Za-z0-9_-]+$/'],
            'singer_name' => ['nullable', 'string', 'max:80'],
            'video_id' => ['required', 'string', 'max:32'],
            'title' => ['required', 'string', 'max:255'],
            'thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'channel_title' => ['nullable', 'string', 'max:255'],
            'searched_keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $room = app(KaraokeRoomManager::class)->resolve($validated['room_code'] ?? null);

        $queueItem = DB::transaction(function () use ($room, $validated): KaraokeQueueItem {
            $nextPosition = ((int) KaraokeQueueItem::query()
                ->where('karaoke_room_id', $room->id)
                ->max('position')) + 1;

            return KaraokeQueueItem::create([
                'karaoke_room_id' => $room->id,
                'singer_name' => trim($validated['singer_name'] ?? '') ?: 'Guest',
                'video_id' => $validated['video_id'],
                'title' => $validated['title'],
                'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                'channel_title' => $validated['channel_title'] ?? null,
                'searched_keyword' => $validated['searched_keyword'] ?? null,
                'position' => $nextPosition,
                'status' => KaraokeQueueItem::STATUS_QUEUED,
            ]);
        });

        return response()->json([
            'success' => true,
            'queue_item' => $queueItem->fresh(),
        ], 201);
    }

    public function updateQueueItem(Request $request, KaraokeQueueItem $queueItem): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in([
                KaraokeQueueItem::STATUS_QUEUED,
                KaraokeQueueItem::STATUS_PLAYING,
                KaraokeQueueItem::STATUS_DONE,
                KaraokeQueueItem::STATUS_SKIPPED,
            ])],
            'score' => ['nullable', 'integer', 'between:0,100'],
        ]);

        DB::transaction(function () use ($queueItem, $validated): void {
            $status = $validated['status'] ?? $queueItem->status;

            if ($status === KaraokeQueueItem::STATUS_PLAYING) {
                KaraokeQueueItem::query()
                    ->where('karaoke_room_id', $queueItem->karaoke_room_id)
                    ->where('id', '!=', $queueItem->id)
                    ->where('status', KaraokeQueueItem::STATUS_PLAYING)
                    ->update(['status' => KaraokeQueueItem::STATUS_QUEUED]);

                $queueItem->started_at ??= now();
            }

            if (in_array($status, [KaraokeQueueItem::STATUS_DONE, KaraokeQueueItem::STATUS_SKIPPED], true)) {
                $queueItem->started_at ??= now();
                $queueItem->finished_at = now();
            }

            $queueItem->status = $status;

            if (array_key_exists('score', $validated)) {
                $queueItem->score = $validated['score'];
            }

            $queueItem->save();
        });

        return response()->json([
            'success' => true,
            'queue_item' => $queueItem->fresh(),
        ]);
    }

    public function destroyQueueItem(KaraokeQueueItem $queueItem): JsonResponse
    {
        $queueItem->delete();

        return response()->json([
            'success' => true,
        ]);
    }

}
