<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
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
            ->assertSee('Trending songs are not available yet.')
            ->assertDontSee('YOUTUBE_API_KEY')
            ->assertDontSee('Dokploy')
            ->assertDontSee('Nixpacks');
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
                        ],
                    ],
                ],
            ]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Trending songs right now')
            ->assertSee('Trending Song Official Video')
            ->assertSee('selectedVideo: null', false)
            ->assertSee('overflow-y-hidden', false);

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return Str::startsWith($request->url(), 'https://www.googleapis.com/youtube/v3/videos')
                && $query['key'] === 'test-key'
                && $query['part'] === 'snippet,status'
                && $query['chart'] === 'mostPopular'
                && $query['videoCategoryId'] === '10'
                && $query['regionCode'] === 'PH';
        });
    }
}
