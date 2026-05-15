<?php

namespace App\Http\Controllers;

use App\Services\KaraokeRoomManager;
use App\Services\KaraokeSongCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ArtistController extends Controller
{
    public function show(string $artistName, Request $request, KaraokeSongCatalog $songCatalog, KaraokeRoomManager $roomManager): View
    {
        $artistName = Str::of(urldecode($artistName))->replace('-', ' ')->squish()->toString();
        $searchError = null;
        $query = $request->query('q');
        $feature = $request->query('feature', 'all');

        try {
            $songCatalog->ensureArtistSongs($artistName, 12);
        } catch (RuntimeException $exception) {
            $searchError = $exception->getMessage();
        }

        $songsQuery = $songCatalog->catalogQuery(
            query: is_string($query) ? $query : null,
            feature: is_string($feature) ? $feature : 'all',
        )
            ->whereRaw('LOWER(artist_name) = ?', [Str::lower($artistName)]);

        $songs = $songsQuery->paginate(18)->withQueryString();
        $room = $roomManager->resolve($request->query('room'));
        $coverSong = $songs->getCollection()->first();
        $displayArtistName = $coverSong?->artist_name ?: Str::title($artistName);

        return view('artists.show', [
            'activeRoom' => $room,
            'queueItems' => $roomManager->activeQueueItems($room),
            'artistName' => $displayArtistName,
            'coverSong' => $coverSong,
            'songs' => $songs,
            'featureFilters' => KaraokeSongCatalog::FEATURE_FILTERS,
            'query' => is_string($query) ? $query : '',
            'activeFeature' => is_string($feature) ? $feature : 'all',
            'searchError' => $searchError,
        ]);
    }
}
