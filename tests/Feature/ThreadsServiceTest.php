<?php

namespace Tests\Feature;

use App\Models\MetaCredential;
use App\Services\ThreadsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThreadsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MetaCredential::create([
            'app_id' => 'test_meta_app_123',
            'app_secret' => 'test_meta_secret_456',
            'threads_app_id' => 'test_threads_app_789',
            'threads_app_secret' => 'test_threads_secret_abc',
            'graph_version' => 'v22.0',
            'token_status' => 'valid',
        ]);
    }

    public function test_get_authorization_url_generates_correct_params(): void
    {
        $service = new ThreadsService();
        $redirectUri = 'https://sosmedauto.dj1.my.id/threads/callback';
        $authUrl = $service->getAuthorizationUrl($redirectUri, 'custom_state_123');

        $this->assertStringStartsWith('https://threads.net/oauth/authorize?', $authUrl);
        $this->assertStringContainsString('client_id=test_threads_app_789', $authUrl);
        $this->assertStringContainsString(urlencode('threads_basic,threads_content_publish'), $authUrl);
        $this->assertStringContainsString('state=custom_state_123', $authUrl);
    }

    public function test_exchange_code_and_long_lived_token(): void
    {
        Http::fake([
            'https://graph.threads.net/oauth/access_token' => Http::response([
                'access_token' => 'short_token_xyz',
                'user_id' => '123456789',
            ], 200),
            'https://graph.threads.net/access_token*' => Http::response([
                'access_token' => 'long_token_60days',
                'token_type' => 'bearer',
                'expires_in' => 5184000,
            ], 200),
        ]);

        $service = new ThreadsService();
        $shortRes = $service->exchangeCodeForToken('auth_code_999', 'https://test.com/cb');

        $this->assertTrue($shortRes['success']);
        $this->assertEquals('short_token_xyz', $shortRes['access_token']);
        $this->assertEquals('123456789', $shortRes['user_id']);

        $longRes = $service->exchangeForLongLivedToken('short_token_xyz');
        $this->assertTrue($longRes['success']);
        $this->assertEquals('long_token_60days', $longRes['access_token']);
        $this->assertEquals(5184000, $longRes['expires_in']);
    }

    public function test_get_user_profile_and_publishing_limit(): void
    {
        Http::fake([
            'https://graph.threads.net/v1.0/me*' => Http::response([
                'id' => 'th_user_123',
                'username' => 'sevencols_official',
                'name' => 'Sevencols',
                'threads_profile_picture_url' => 'https://example.com/avatar.jpg',
            ], 200),
            'https://graph.threads.net/v1.0/th_user_123/threads_publishing_limit*' => Http::response([
                'data' => [
                    [
                        'quota_usage' => 12,
                        'config' => [
                            'quota_total' => 250,
                            'quota_duration' => 86400,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new ThreadsService();
        $profile = $service->getUserProfile('valid_token');
        $this->assertTrue($profile['success']);
        $this->assertEquals('sevencols_official', $profile['username']);
        $this->assertEquals('th_user_123', $profile['id']);

        $limit = $service->getPublishingLimit('th_user_123', 'valid_token');
        $this->assertTrue($limit['success']);
        $this->assertEquals(12, $limit['quota_usage']);
        $this->assertEquals(250, $limit['config']['quota_total']);
    }

    public function test_publish_single_image_post(): void
    {
        Http::fake([
            'https://graph.threads.net/v1.0/th_user_123/threads' => Http::response([
                'id' => 'container_img_001',
            ], 200),
            'https://graph.threads.net/v1.0/th_user_123/threads_publish' => Http::response([
                'id' => 'published_post_111',
            ], 200),
        ]);

        $service = new ThreadsService();
        $res = $service->publishThreadsPost(
            'th_user_123',
            'token_abc',
            ['https://example.com/photo.jpg'],
            'Halo dari otomasi Threads!'
        );

        $this->assertTrue($res['success']);
        $this->assertEquals('published_post_111', $res['id']);
        $this->assertEquals('container_img_001', $res['container_id']);
    }

    public function test_publish_carousel_post(): void
    {
        Http::fake([
            'https://graph.threads.net/v1.0/th_user_123/threads' => Http::sequence()
                ->push(['id' => 'child_cnt_1'], 200)
                ->push(['id' => 'child_cnt_2'], 200)
                ->push(['id' => 'parent_carousel_cnt'], 200),
            'https://graph.threads.net/v1.0/th_user_123/threads_publish' => Http::response([
                'id' => 'published_carousel_999',
            ], 200),
        ]);

        $service = new ThreadsService();
        $res = $service->publishThreadsPost(
            'th_user_123',
            'token_abc',
            ['https://example.com/img1.jpg', 'https://example.com/img2.jpg'],
            'Postingan Carousel Threads'
        );

        $this->assertTrue($res['success']);
        $this->assertEquals('published_carousel_999', $res['id']);
        $this->assertEquals('parent_carousel_cnt', $res['container_id']);
    }

    public function test_caption_is_truncated_to_500_chars_for_threads(): void
    {
        $capturedText = null;

        Http::fake([
            'https://graph.threads.net/v1.0/th_user_123/threads' => function ($request) use (&$capturedText) {
                $capturedText = $request['text'] ?? null;
                return Http::response(['id' => 'cnt_text_001'], 200);
            },
            'https://graph.threads.net/v1.0/th_user_123/threads_publish' => Http::response([
                'id' => 'published_text_001',
            ], 200),
        ]);

        $service = new ThreadsService();
        $longCaption = str_repeat('A', 650);
        $res = $service->publishThreadsPost('th_user_123', 'token_abc', [], $longCaption);

        $this->assertTrue($res['success']);
        $this->assertEquals(500, mb_strlen($capturedText));
    }
}
