<?php

namespace Tests\Feature;

use App\Models\KaraokeSong;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutVite();
        config(['services.youtube.api_key' => null]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('MyKaraoke')
            ->assertSee('music-note-field')
            ->assertSee('Sing your favorite karaoke songs on any device')
            ->assertSee('Give it a spin')
            ->assertSee('Trending songs are not available yet.')
            ->assertSee('Search, select, and play')
            ->assertSee('Open room')
            ->assertDontSee('YOUTUBE_API_KEY')
            ->assertDontSee('Dokploy')
            ->assertDontSee('Nixpacks')
            ->assertDontSee('Sign up')
            ->assertDontSee('Pricing')
            ->assertDontSee('Frequently Asked Questions');
    }

    public function test_homepage_loads_youtube_trending_music(): void
    {
        $this->withoutVite();
        Cache::flush();

        config([
            'services.youtube.api_key' => 'test-key',
            'services.youtube.region_code' => 'PH',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'blocked12345',
                        'snippet' => [
                            'title' => 'Blocked Trending Song',
                            'channelTitle' => 'Blocked Channel',
                            'publishedAt' => '2026-05-14T00:00:00Z',
                            'thumbnails' => [
                                'medium' => ['url' => 'https://i.ytimg.com/vi/blocked12345/mqdefault.jpg'],
                            ],
                        ],
                        'status' => [
                            'embeddable' => true,
                            'privacyStatus' => 'public',
                            'uploadStatus' => 'processed',
                        ],
                        'contentDetails' => [
                            'regionRestriction' => [
                                'blocked' => ['PH'],
                            ],
                        ],
                        'player' => [
                            'embedHtml' => '<iframe src="https://www.youtube.com/embed/blocked12345"></iframe>',
                        ],
                    ],
                    [
                        'id' => 'music123xyz',
                        'snippet' => [
                            'title' => 'Trending Song Official Video',
                            'channelTitle' => 'Music Channel',
                            'publishedAt' => '2026-05-14T00:00:00Z',
                            'thumbnails' => [
                                'medium' => ['url' => 'https://i.ytimg.com/vi/music123xyz/mqdefault.jpg'],
                            ],
                        ],
                        'status' => [
                            'embeddable' => true,
                            'privacyStatus' => 'public',
                            'uploadStatus' => 'processed',
                        ],
                        'contentDetails' => [
                            'regionRestriction' => [],
                        ],
                        'player' => [
                            'embedHtml' => '<iframe src="https://www.youtube.com/embed/music123xyz"></iframe>',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Sing your favorite karaoke songs on any device')
            ->assertSee('Search, select, and play')
            ->assertSee('Stage player')
            ->assertSee('Reopen player')
            ->assertSee('Trending Song Official Video')
            ->assertDontSee('Blocked Trending Song')
            ->assertDontSee('Sign up')
            ->assertDontSee('Pricing')
            ->assertDontSee('Frequently Asked Questions')
            ->assertSee('selectedVideo: null', false)
            ->assertSee('window.location.origin', false)
            ->assertSee('overflow-y-hidden', false);

        $this->assertSame(1, KaraokeSong::count());

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return Str::startsWith($request->url(), 'https://www.googleapis.com/youtube/v3/videos')
                && $query['key'] === 'test-key'
                && $query['part'] === 'snippet,status,contentDetails,player'
                && $query['chart'] === 'mostPopular'
                && $query['videoCategoryId'] === '10'
                && $query['regionCode'] === 'PH';
        });
    }
}
