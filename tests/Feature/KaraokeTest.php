<?php

namespace Tests\Feature;

use App\Models\KaraokeFavorite;
use App\Models\KaraokePlayHistory;
use App\Models\KaraokeQueueItem;
use App\Models\KaraokeRoom;
use App\Models\KaraokeSong;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class KaraokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_karaoke_page_loads(): void
    {
        $this->withoutVite();

        $this->get('/karaoke')
            ->assertOk()
            ->assertSee('MyKaraoke')
            ->assertSee('music-note-field')
            ->assertSeeInOrder(['Search your song', 'Top songs', 'Room setup', 'Session queue', 'Top genres'])
            ->assertSee('Enable Mic')
            ->assertSee('Search your song')
            ->assertSee('Clear search')
            ->assertSee('Related songs')
            ->assertSee('Play suggestion')
            ->assertSee('Queue suggestion')
            ->assertSee('Queue selected')
            ->assertSee('Welcome to the new era of karaoke')
            ->assertSee('Top genres')
            ->assertSee('Latest additions')
            ->assertSee('Playlists')
            ->assertSee('Artists')
            ->assertSee('z-[120]', false)
            ->assertSee('x-on:pointerdown.prevent.stop', false)
            ->assertSee('Karaoke preview')
            ->assertSee('karaoke-overlay-controls')
            ->assertSee('Mini karaoke player')
            ->assertSee('Cancel video')
            ->assertSee('Play next')
            ->assertSee('Open player')
            ->assertSee('Waiting')
            ->assertSee('overflow-y-auto', false)
            ->assertSee('Singer name')
            ->assertSee('Favorites')
            ->assertDontSee('Now playing')
            ->assertDontSee('Voice input monitor')
            ->assertDontSee('Party queue')
            ->assertDontSee('h-[38rem]', false)
            ->assertDontSee('Top scores')
            ->assertDontSee('Search results');
    }

    public function test_search_requires_a_query(): void
    {
        $this->getJson('/karaoke/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_default_karaoke_room_is_scoped_to_the_browser_session(): void
    {
        $this->withoutVite();

        $this->get('/karaoke')->assertOk();
        $roomCode = KaraokeRoom::first()->code;

        $this->assertStringStartsWith('ROOM', $roomCode);

        $this->get('/karaoke')->assertOk();

        $this->assertSame(1, KaraokeRoom::count());
        $this->assertSame($roomCode, KaraokeRoom::first()->code);
    }

    public function test_search_returns_friendly_message_when_youtube_is_not_configured(): void
    {
        config(['services.youtube.api_key' => null]);

        $this->getJson('/karaoke/search?q=duvet')
            ->assertServiceUnavailable()
            ->assertJson([
                'results' => [],
            ])
            ->assertJsonStructure(['message', 'results']);
    }

    public function test_search_uses_youtube_api_parameters_and_returns_clean_results(): void
    {
        config([
            'services.youtube.api_key' => 'test-key',
            'services.youtube.region_code' => 'PH',
            'services.youtube.default_query_suffix' => 'karaoke lyrics',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/search*' => Http::response([
                'items' => [
                    [
                        'id' => ['videoId' => 'abc123xyz09'],
                        'snippet' => [
                            'title' => 'Test Song Karaoke',
                            'channelTitle' => 'Karaoke Channel',
                            'publishedAt' => '2026-05-13T00:00:00Z',
                            'thumbnails' => [
                                'medium' => ['url' => 'https://i.ytimg.com/vi/abc123xyz09/mqdefault.jpg'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->getJson('/karaoke/search?q=test%20song')
            ->assertOk()
            ->assertJsonPath('results.0.video_id', 'abc123xyz09')
            ->assertJsonPath('results.0.title', 'Test Song Karaoke')
            ->assertJsonPath('results.0.channel_title', 'Karaoke Channel');

        $this->assertSame(1, KaraokeSong::count());
        $this->assertSame('abc123xyz09', KaraokeSong::first()->video_id);

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return Str::startsWith($request->url(), 'https://www.googleapis.com/youtube/v3/search')
                && $query['key'] === 'test-key'
                && $query['part'] === 'snippet'
                && $query['q'] === 'test song karaoke lyrics'
                && $query['type'] === 'video'
                && $query['videoEmbeddable'] === 'true'
                && $query['videoSyndicated'] === 'true'
                && $query['maxResults'] === '12'
                && $query['regionCode'] === 'PH';
        });
    }

    public function test_song_and_artist_catalog_pages_load_cached_songs(): void
    {
        $this->withoutVite();
        config(['services.youtube.api_key' => null]);

        $song = KaraokeSong::create([
            'video_id' => 'song123xyz',
            'title' => 'Multo Karaoke',
            'artist_name' => 'Cup of Joe',
            'channel_title' => 'Cup of Joe Karaoke',
            'thumbnail_url' => 'https://i.ytimg.com/vi/song123xyz/mqdefault.jpg',
            'features' => ['original', 'vocals'],
            'genres' => ['opm', 'pop'],
            'language' => 'tagalog',
            'search_count' => 3,
            'played_count' => 2,
            'cached_at' => now(),
        ]);

        $this->get('/songs')
            ->assertOk()
            ->assertSee('Filter by')
            ->assertSee('Multo Karaoke')
            ->assertSee('View song page')
            ->assertSee('View artist page');

        $this->get("/songs/{$song->video_id}")
            ->assertOk()
            ->assertSee('Multo Karaoke')
            ->assertSee('More from Cup of Joe')
            ->assertSee('Official YouTube embed')
            ->assertDontSee('Lyrics')
            ->assertDontSee('Huminga');

        $this->get('/artists/'.Str::slug($song->artist_name))
            ->assertOk()
            ->assertSee('Cup of Joe')
            ->assertSee('Search Cup of Joe songs')
            ->assertSee('Multo Karaoke');
    }

    public function test_play_history_is_created_and_incremented(): void
    {
        $payload = [
            'video_id' => 'abc123xyz09',
            'title' => 'Test Song Karaoke',
            'thumbnail_url' => 'https://i.ytimg.com/vi/abc123xyz09/mqdefault.jpg',
            'channel_title' => 'Karaoke Channel',
            'searched_keyword' => 'test song',
        ];

        $this->postJson('/karaoke/play-history', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('history.played_count', 1);

        $this->postJson('/karaoke/play-history', $payload)
            ->assertOk()
            ->assertJsonPath('history.played_count', 2);

        $this->assertSame(1, KaraokePlayHistory::count());
        $this->assertSame(2, KaraokePlayHistory::first()->played_count);
    }

    public function test_favorite_can_be_created_rated_and_removed(): void
    {
        $payload = [
            'video_id' => 'fav123xyz09',
            'title' => 'Favorite Song Karaoke',
            'thumbnail_url' => 'https://i.ytimg.com/vi/fav123xyz09/mqdefault.jpg',
            'channel_title' => 'Karaoke Channel',
            'rating' => 4,
        ];

        $this->postJson('/karaoke/favorites', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('favorite.rating', 4);

        $this->postJson('/karaoke/favorites', [
            ...$payload,
            'rating' => 5,
        ])
            ->assertOk()
            ->assertJsonPath('favorite.rating', 5);

        $this->assertSame(1, KaraokeFavorite::count());
        $this->assertSame(5, KaraokeFavorite::first()->rating);

        $this->deleteJson('/karaoke/favorites/fav123xyz09')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, KaraokeFavorite::count());
    }

    public function test_queue_item_can_be_created_scored_and_removed(): void
    {
        $payload = [
            'room_code' => 'mainroom',
            'singer_name' => 'Adon',
            'video_id' => 'queue123xyz',
            'title' => 'Queued Song Karaoke',
            'thumbnail_url' => 'https://i.ytimg.com/vi/queue123xyz/mqdefault.jpg',
            'channel_title' => 'Queue Channel',
            'searched_keyword' => 'queued song',
        ];

        $queueItemId = $this->postJson('/karaoke/queue', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('queue_item.singer_name', 'Adon')
            ->assertJsonPath('queue_item.status', KaraokeQueueItem::STATUS_QUEUED)
            ->json('queue_item.id');

        $this->assertSame('MAINROOM', KaraokeRoom::first()->code);

        $this->patchJson("/karaoke/queue/{$queueItemId}", [
            'status' => KaraokeQueueItem::STATUS_PLAYING,
        ])
            ->assertOk()
            ->assertJsonPath('queue_item.status', KaraokeQueueItem::STATUS_PLAYING);

        $this->patchJson("/karaoke/queue/{$queueItemId}", [
            'status' => KaraokeQueueItem::STATUS_DONE,
            'score' => 92,
        ])
            ->assertOk()
            ->assertJsonPath('queue_item.status', KaraokeQueueItem::STATUS_DONE)
            ->assertJsonPath('queue_item.score', 92);

        $this->assertSame(92, KaraokeQueueItem::first()->score);

        $this->deleteJson("/karaoke/queue/{$queueItemId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, KaraokeQueueItem::count());
    }
}
