<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Http\Services\ApiService;
class MessageController extends Controller
{
    public function store(Request $request)
    {
        try {
            //音声データを保存
            if ($request->hasFile('audio')) {
                $audio = $request->file('audio');
                //ファイル名を日時に指定して保存する
                $timestamp = now()->format('YmdHis');
                $path = $audio->storeAs('audio', "audio_{$timestamp}.wav", 'public');

                // データベースに音声データのパスを保存
                $message = Message::create([
                    'thread_id' => $request->thread_id,
                    'message_en' => 'dummy',
                    'message_ja' => '',
                    'sender' => 1,
                    'audio_file_path' => $path,
                ]);

                // 音声データをAPIに送信
                $apiService = new ApiService();
                $response = $apiService->callWhisperApi($path);
                $message_en = $response['text'];

                // メッセージを更新
                $message->update([
                    'message_en' => $message_en,
                ]);

                $messages = Message::where('thread_id', $request->thread_id)->get();
                // GPTにAPIリクエスト
                $gptResponse = $apiService->callGptApi($messages);
                $aiMessageText = $gptResponse['choices'][0]['message']['content'];
                // データベースに音声データのパスを保存
                $aiMessage = Message::create([
                    'thread_id' => $request->thread_id,
                    'message_en' => $aiMessageText,
                    'message_ja' => '',
                    'sender' => 2, // 2はAI
                    'audio_file_path' => '',
                ]);

                // TTSにAPIリクエスト
                $aiAudioFilePath = $apiService->callTtsApi($aiMessageText);
                // dd($aiAudioFilePath);
                // データベースに音声データのパスを保存
                $aiMessage->update([
                    'audio_file_path' => $aiAudioFilePath,
                ]);

                return response()->json([
                    'message' => '音声データが保存されました',
                    'status' => 200,
                ]);
            }

            return response()->json([
                'message' => '音声データが保存されませんでした',
                'status' => 400,
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());  // エラーログを記録
            return response()->json([
                'message' => 'エラーが発生しました',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * 英文を日本語に翻訳
     */
    public function translate(ApiService $apiService, $threadId, $messageId)
    {
        try {
            $message = Message::find($messageId);

            if (!$message) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Message not found'
                ], 404);
            }

            $response = $apiService->translateToJapanese($message->message_en);
            $translatedText = $response['choices'][0]['message']['content'];

            $message->update([
                'message_ja' => $translatedText
            ]);

            return response()->json([
                'status' => 200,
                'message_ja' => $translatedText
            ]);

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Translation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
