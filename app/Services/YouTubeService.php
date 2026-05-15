<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class YouTubeService
{
    private const SEARCH_ENDPOINT = 'https://www.googleapis.com/youtube/v3/search';

    private const VIDEOS_ENDPOINT = 'https://www.googleapis.com/youtube/v3/videos';

    /**
     * @return array<int, array{
     *     video_id: string,
     *     title: string,
     *     channel_title: string,
     *     thumbnail_url: ?string,
     *     published_at: ?string
     * }>
     */
    public function search(string $query): array
    {
        $apiKey = config('services.youtube.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('YouTube search is not configured yet. Set YOUTUBE_API_KEY before searching.');
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 250)
                ->get(self::SEARCH_ENDPOINT, [
                    'key' => $apiKey,
                    'part' => 'snippet',
                    'q' => $this->queryWithSuffix($query),
                    'type' => 'video',
                    'videoEmbeddable' => 'true',
                    'videoSyndicated' => 'true',
                    'maxResults' => 12,
                    'regionCode' => config('services.youtube.region_code', 'PH'),
                ]);
        } catch (Throwable $exception) {
            Log::error('YouTube API request failed.', [
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('YouTube search is temporarily unavailable. Please try again in a moment.', previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('YouTube API returned an unsuccessful response.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('YouTube search is temporarily unavailable. Please try again in a moment.');
        }

        return collect($response->json('items', []))
            ->map(fn (array $item): array => [
                'video_id' => (string) data_get($item, 'id.videoId', ''),
                'title' => (string) data_get($item, 'snippet.title', ''),
                'channel_title' => (string) data_get($item, 'snippet.channelTitle', ''),
                'thumbnail_url' => data_get($item, 'snippet.thumbnails.medium.url')
                    ?? data_get($item, 'snippet.thumbnails.high.url')
                    ?? data_get($item, 'snippet.thumbnails.default.url'),
                'published_at' => data_get($item, 'snippet.publishedAt'),
            ])
            ->filter(fn (array $video): bool => filled($video['video_id']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{
     *     video_id: string,
     *     title: string,
     *     channel_title: string,
     *     thumbnail_url: ?string,
     *     published_at: ?string
     * }>
     */
    public function trendingMusic(int $maxResults = 12): array
    {
        $apiKey = config('services.youtube.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('YouTube trending music is not configured yet. Set YOUTUBE_API_KEY to load featured songs.');
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 250)
                ->get(self::VIDEOS_ENDPOINT, [
                    'key' => $apiKey,
                    'part' => 'snippet,status,contentDetails,player',
                    'chart' => 'mostPopular',
                    'videoCategoryId' => '10',
                    'maxResults' => min(50, max($maxResults, 24)),
                    'regionCode' => config('services.youtube.region_code', 'PH'),
                ]);
        } catch (Throwable $exception) {
            Log::error('YouTube trending music API request failed.', [
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Trending music is temporarily unavailable. Please try again in a moment.', previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('YouTube trending music API returned an unsuccessful response.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Trending music is temporarily unavailable. Please try again in a moment.');
        }

        return collect($response->json('items', []))
            ->filter(fn (array $item): bool => $this->isPlayableTrendingVideo($item))
            ->map(fn (array $item): array => [
                'video_id' => (string) data_get($item, 'id', ''),
                'title' => (string) data_get($item, 'snippet.title', ''),
                'channel_title' => (string) data_get($item, 'snippet.channelTitle', ''),
                'thumbnail_url' => data_get($item, 'snippet.thumbnails.medium.url')
                    ?? data_get($item, 'snippet.thumbnails.high.url')
                    ?? data_get($item, 'snippet.thumbnails.default.url'),
                'published_at' => data_get($item, 'snippet.publishedAt'),
            ])
            ->filter(fn (array $video): bool => filled($video['video_id']))
            ->take($maxResults)
            ->values()
            ->all();
    }

    private function isPlayableTrendingVideo(array $item): bool
    {
        if (blank(data_get($item, 'id'))) {
            return false;
        }

        if (data_get($item, 'status.embeddable') !== true) {
            return false;
        }

        if (data_get($item, 'status.privacyStatus') !== 'public') {
            return false;
        }

        if (data_get($item, 'status.uploadStatus') !== 'processed') {
            return false;
        }

        if (blank(data_get($item, 'player.embedHtml'))) {
            return false;
        }

        $region = strtoupper((string) config('services.youtube.region_code', 'PH'));
        $blockedRegions = collect(data_get($item, 'contentDetails.regionRestriction.blocked', []))
            ->map(fn (string $country): string => strtoupper($country));

        if ($blockedRegions->contains($region)) {
            return false;
        }

        $allowedRegions = collect(data_get($item, 'contentDetails.regionRestriction.allowed', []))
            ->map(fn (string $country): string => strtoupper($country));

        if ($allowedRegions->isNotEmpty() && ! $allowedRegions->contains($region)) {
            return false;
        }

        return true;
    }

    private function queryWithSuffix(string $query): string
    {
        $query = trim($query);
        $suffix = trim((string) config('services.youtube.default_query_suffix', 'karaoke lyrics'));

        if ($suffix === '' || Str::contains(Str::lower($query), Str::lower($suffix))) {
            return $query;
        }

        return trim($query.' '.$suffix);
    }
}
