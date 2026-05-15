<?php

namespace App\Http\Controllers;

use App\Models\KaraokeSong;
use App\Services\KaraokeRoomManager;
use App\Services\KaraokeSongCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SongCatalogController extends Controller
{
    public function index(Request $request, KaraokeSongCatalog $songCatalog, KaraokeRoomManager $roomManager): View
    {
        $query = $request->query('q');
        $feature = $request->query('feature', 'all');
        $genre = $request->query('genre', 'all');
        $sort = $request->query('sort', 'popular');
        $searchError = null;

        if (is_string($query) && trim($query) !== '') {
            try {
                $songCatalog->searchAndCache($query, 12);
            } catch (RuntimeException $exception) {
                $searchError = $exception->getMessage();
            }
        }

        if (is_string($genre) && $genre !== 'all') {
            try {
                $songCatalog->ensureGenreSongs($genre, 12);
            } catch (RuntimeException $exception) {
                $searchError ??= $exception->getMessage();
            }
        }

        $songsQuery = $songCatalog->catalogQuery(
            query: is_string($query) ? $query : null,
            feature: is_string($feature) ? $feature : 'all',
            genre: is_string($genre) ? $genre : null,
        );

        match ($sort) {
            'latest' => $songsQuery->reorder()->orderByDesc('cached_at'),
            'played' => $songsQuery->reorder()->orderByDesc('played_count')->orderByDesc('last_played_at'),
            default => null,
        };

        $room = $roomManager->resolve($request->query('room'));

        return view('songs.index', [
            'activeRoom' => $room,
            'queueItems' => $roomManager->activeQueueItems($room),
            'songs' => $songsQuery->paginate(18)->withQueryString(),
            'featureFilters' => KaraokeSongCatalog::FEATURE_FILTERS,
            'genreFilters' => KaraokeSongCatalog::GENRE_FILTERS,
            'topGenres' => $songCatalog->topGenres(),
            'activeFeature' => is_string($feature) ? $feature : 'all',
            'activeGenre' => is_string($genre) ? $genre : 'all',
            'sort' => is_string($sort) ? $sort : 'popular',
            'query' => is_string($query) ? $query : '',
            'searchError' => $searchError,
        ]);
    }

    public function show(string $videoId, Request $request, KaraokeSongCatalog $songCatalog, KaraokeRoomManager $roomManager): View
    {
        $song = $songCatalog->songByVideoId($videoId);

        if (! $song) {
            try {
                $song = $songCatalog->searchAndCache($videoId, 1)->first();
            } catch (RuntimeException) {
                $song = null;
            }
        }

        abort_unless($song instanceof KaraokeSong, 404);

        if (filled($song->artist_name)) {
            try {
                $songCatalog->ensureArtistSongs($song->artist_name, 8);
            } catch (RuntimeException) {
                // Cached related songs are enough for the detail page.
            }
        }

        $room = $roomManager->resolve($request->query('room'));
        $moreFromArtist = filled($song->artist_name)
            ? $songCatalog->catalogQuery()->where('artist_name', $song->artist_name)->where('video_id', '!=', $song->video_id)->limit(8)->get()
            : collect();

        return view('songs.show', [
            'activeRoom' => $room,
            'queueItems' => $roomManager->activeQueueItems($room),
            'song' => $song,
            'moreFromArtist' => $moreFromArtist,
        ]);
    }
}
