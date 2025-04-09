<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Http\Services\ApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザーとして認証を追加
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        // テスト用の基本的なメッセージを作成
        Message::create([
            'thread_id' => 1,
            'message_en' => 'Hello, how are you?',
            'message_ja' => '',
            'sender' => 1,
            'audio_file_path' => ''
        ]);
    }

    public function test_translate_success()
    {
        // ApiServiceのモックを作成
        $mockApiService = Mockery::mock(ApiService::class);
        $mockApiService->shouldReceive('translateToJapanese')
            ->once()
            ->andReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'こんにちは、お元気ですか？'
                        ]
                    ]
                ]
            ]);

        $this->app->instance(ApiService::class, $mockApiService);

        // 翻訳APIを呼び出し
        $response = $this->postJson('/thread/1/message/1/translate');

        // レスポンスを検証
        $response->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                    'message_ja' => 'こんにちは、お元気ですか？'
                ]);
    }

    public function test_translate_message_not_found()
    {
        // 存在しないメッセージIDでリクエスト
        $response = $this->postJson('/thread/1/message/999/translate');

        $response->assertStatus(404)
                ->assertJson([
                    'status' => 404,
                    'message' => 'Message not found'
                ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
