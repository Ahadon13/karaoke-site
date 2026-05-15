<?php

namespace App\Http\Controllers;

use App\Services\KaraokeRoomManager;
use App\Services\KaraokeSongCatalog;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RuntimeException;

class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        YouTubeService $youTubeService,
        KaraokeSongCatalog $songCatalog,
        KaraokeRoomManager $roomManager,
    ): View
    {
        $room = $roomManager->resolve($request->query('room'));
        $featuredSongs = [];
        $featuredError = null;

        try {
            $featuredSongs = Cache::remember(
                'youtube.trending_music.embeddable.v2.'.config('services.youtube.region_code', 'PH'),
                now()->addMinutes(30),
                fn (): array => $youTubeService->trendingMusic()
            );

            $featuredSongs = $songCatalog
                ->cacheResults($featuredSongs, 'trending music')
                ->map->toVideoArray()
                ->values()
                ->all();
        } catch (RuntimeException) {
            $featuredError = 'Trending songs are warming up. You can still jump straight into the karaoke room.';
        }

        return view('welcome', [
            'activeRoom' => $room,
            'queueItems' => $roomManager->activeQueueItems($room),
            'featuredSongs' => $featuredSongs,
            'featuredError' => $featuredError,
        ]);
    }
}
