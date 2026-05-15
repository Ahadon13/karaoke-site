<?php

namespace App\Services;

use App\Models\KaraokeSong;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class KaraokeSongCatalog
{
    public const FEATURE_FILTERS = [
        'all' => 'All',
        KaraokeSong::FEATURE_ORIGINAL => 'Original',
        KaraokeSong::FEATURE_VOCALS => 'Vocals',
        KaraokeSong::FEATURE_MELODY => 'Melody',
        KaraokeSong::FEATURE_DUET => 'Duet',
        KaraokeSong::FEATURE_ENGLISH => 'English',
    ];

    public const GENRE_FILTERS = [
        'pop' => 'Pop',
        'opm' => 'OPM',
        'rock' => 'Rock',
        'country' => 'Country',
        'ballad' => 'Ballad',
        'rnb' => 'RnB',
        'folk' => 'Folk',
        'soul' => 'Soul',
        'alternative' => 'Alternative',
        'indie' => 'Indie',
        'blues' => 'Blues',
        'electronic' => 'Electronic',
    ];

    public function __construct(private readonly YouTubeService $youTubeService)
    {
    }

    /**
     * @return Collection<int, KaraokeSong>
     */
    public function searchAndCache(string $query, int $limit = 12): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $videos = $this->youTubeService->search($query);

        return $this->cacheResults($videos, $query)->take($limit)->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return Collection<int, KaraokeSong>
     */
    public function cacheResults(array $videos, ?string $query = null): Collection
    {
        return collect($videos)
            ->filter(fn (array $video): bool => filled($video['video_id'] ?? null))
            ->map(fn (array $video): KaraokeSong => $this->cacheVideo($video, $query))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $video
     */
    public function cacheVideo(array $video, ?string $query = null): KaraokeSong
    {
        $metadata = $this->inferMetadata($video, $query);
        $song = KaraokeSong::firstOrNew(['video_id' => (string) $video['video_id']]);

        $song->fill([
            'title' => (string) ($video['title'] ?? $song->title ?? ''),
            'artist_name' => $metadata['artist_name'],
            'channel_title' => (string) ($video['channel_title'] ?? $song->channel_title ?? ''),
            'thumbnail_url' => $video['thumbnail_url'] ?? $song->thumbnail_url,
            'published_at' => $video['published_at'] ?? $song->published_at,
            'source_query' => $query ?: $song->source_query,
            'features' => $metadata['features'],
            'genres' => $metadata['genres'],
            'language' => $metadata['language'],
            'cached_at' => now(),
        ]);

        $song->search_count = $song->exists ? $song->search_count + 1 : 1;
        $song->save();

        return $song->fresh();
    }

    /**
     * @return Collection<int, KaraokeSong>
     */
    public function ensureGenreSongs(string $genre, int $limit = 12): Collection
    {
        $genre = Str::slug($genre);

        if (! array_key_exists($genre, self::GENRE_FILTERS)) {
            return collect();
        }

        $existing = $this->baseQuery()
            ->genre($genre)
            ->limit($limit)
            ->get();

        if ($existing->count() >= min(6, $limit)) {
            return $existing;
        }

        return $this->searchAndCache(self::GENRE_FILTERS[$genre].' karaoke', $limit);
    }

    /**
     * @return Collection<int, KaraokeSong>
     */
    public function ensureArtistSongs(string $artistName, int $limit = 12): Collection
    {
        $artistName = trim($artistName);

        if ($artistName === '') {
            return collect();
        }

        $existing = $this->baseQuery()
            ->where('artist_name', $artistName)
            ->limit($limit)
            ->get();

        if ($existing->count() >= min(3, $limit)) {
            return $existing;
        }

        return $this->searchAndCache($artistName.' karaoke', $limit);
    }

    public function songByVideoId(string $videoId): ?KaraokeSong
    {
        return KaraokeSong::query()->where('video_id', $videoId)->first();
    }

    public function catalogQuery(?string $query = null, ?string $feature = null, ?string $genre = null): Builder
    {
        $builder = $this->baseQuery()
            ->feature($feature)
            ->genre($genre);

        if (filled($query)) {
            $terms = trim((string) $query);
            $builder->where(function (Builder $subQuery) use ($terms): void {
                $subQuery
                    ->where('title', 'like', "%{$terms}%")
                    ->orWhere('artist_name', 'like', "%{$terms}%")
                    ->orWhere('channel_title', 'like', "%{$terms}%")
                    ->orWhere('source_query', 'like', "%{$terms}%");
            });
        }

        return $builder;
    }

    public function topGenres(int $limit = 12): array
    {
        $counts = KaraokeSong::query()
            ->get(['genres'])
            ->flatMap(fn (KaraokeSong $song): array => $song->genres ?: [])
            ->countBy()
            ->sortDesc();

        return collect(self::GENRE_FILTERS)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    public function inferMetadata(array $video, ?string $query = null): array
    {
        $title = (string) ($video['title'] ?? '');
        $channelTitle = (string) ($video['channel_title'] ?? '');
        $haystack = Str::lower($title.' '.$channelTitle.' '.$query);
        $features = [];

        if (Str::contains($haystack, ['original key', 'original karaoke', 'official karaoke'])) {
            $features[] = KaraokeSong::FEATURE_ORIGINAL;
        }

        if (Str::contains($haystack, ['with vocals', 'guide vocal', 'vocal guide', 'male vocal', 'female vocal'])) {
            $features[] = KaraokeSong::FEATURE_VOCALS;
        }

        if (Str::contains($haystack, ['melody guide', 'with melody', 'melody karaoke'])) {
            $features[] = KaraokeSong::FEATURE_MELODY;
        }

        if (Str::contains($haystack, ['duet', 'male female', 'male/female'])) {
            $features[] = KaraokeSong::FEATURE_DUET;
        }

        if (Str::contains($haystack, ['english', 'eng sub', 'english version'])) {
            $features[] = KaraokeSong::FEATURE_ENGLISH;
        }

        if ($features === []) {
            $features = [KaraokeSong::FEATURE_ORIGINAL, KaraokeSong::FEATURE_VOCALS];
        }

        $genres = collect(self::GENRE_FILTERS)
            ->keys()
            ->filter(fn (string $genre): bool => Str::contains($haystack, [$genre, str_replace('rnb', 'r&b', $genre)]))
            ->values()
            ->all();

        if (Str::contains($haystack, ['opm', 'tagalog', 'filipino'])) {
            $genres[] = 'opm';
        }

        if ($genres === []) {
            $genres = ['pop'];
        }

        return [
            'artist_name' => $this->inferArtistName($title, $channelTitle),
            'features' => array_values(array_unique($features)),
            'genres' => array_values(array_unique($genres)),
            'language' => Str::contains($haystack, ['tagalog', 'opm', 'filipino']) ? 'tagalog' : 'english',
        ];
    }

    private function baseQuery(): Builder
    {
        return KaraokeSong::query()
            ->orderByDesc('played_count')
            ->orderByDesc('search_count')
            ->orderByDesc('cached_at');
    }

    private function inferArtistName(string $title, ?string $channelTitle = null): ?string
    {
        $cleanTitle = trim(preg_replace('/\s*\[[^\]]*]|\s*\([^)]*\)/', '', html_entity_decode($title)) ?: $title);
        $cleanTitle = preg_replace('/\b(karaoke|lyrics?|hd|official|version|with|without|instrumental)\b/i', '', $cleanTitle) ?: $cleanTitle;
        $parts = preg_split('/\s+[–-]\s+/', $cleanTitle);

        if (is_array($parts) && count($parts) >= 2) {
            $candidate = trim((string) end($parts));

            if ($candidate !== '') {
                return Str::of($candidate)->squish()->limit(80, '')->toString();
            }
        }

        $channel = trim((string) $channelTitle);

        if ($channel !== '') {
            return Str::of($channel)
                ->replaceMatches('/\s*-\s*Topic$/i', '')
                ->replaceMatches('/\s*Karaoke\s*$/i', '')
                ->squish()
                ->limit(80, '')
                ->toString();
        }

        return null;
    }
}
