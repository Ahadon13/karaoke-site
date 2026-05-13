<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RuntimeException;

class HomeController extends Controller
{
    public function __invoke(YouTubeService $youTubeService): View
    {
        $featuredSongs = [];
        $featuredError = null;

        try {
            $featuredSongs = Cache::remember(
                'youtube.trending_music.'.config('services.youtube.region_code', 'PH'),
                now()->addMinutes(30),
                fn (): array => $youTubeService->trendingMusic()
            );
        } catch (RuntimeException) {
            $featuredError = 'Trending songs are warming up. You can still jump straight into the karaoke room.';
        }

        return view('welcome', [
            'featuredSongs' => $featuredSongs,
            'featuredError' => $featuredError,
        ]);
    }
}
