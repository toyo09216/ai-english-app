<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
class ApiService
{
    public function callWhisperApi($audioFilePath)
    {
        // $audioFilePathのデータは、audio/audio_20250312021509.wavのような文字列が格納されている
        // curlでリクエストする場合はこのようになる
        // curl https://api.openai.com/v1/audio/transcriptions \
        // -H "Authorization: Bearer $OPENAI_API_KEY" \
        // -H "Content-Type: multipart/form-data" \
        // -F file="@/path/to/file/audio.wav" \
        // -F model="whisper-1"

        $response = Http::attach(
            'file',
            file_get_contents(storage_path('app/public/' . $audioFilePath)),
            'audio.wav'
        )->withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            // 'Content-Type' => 'multipart/form-data',
        ])->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'en',
        ]);

        // dd($response->json());
        return $response->json();
    }


    /**
     * @param Collection<Message> $messages
     * @return array
     */
    public function callGptApi($messages)
    {
        $apiKey = env('OPENAI_API_KEY');
        $url = 'https://api.openai.com/v1/chat/completions';

        // メッセージ履歴をGPT APIの形式に変換
        $formattedMessages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful English teacher. Please help users learn English naturally through conversation.'
            ]
        ];

        foreach ($messages as $message) {
            $role = $message->sender === 1 ? 'user' : 'assistant';
            $formattedMessages[] = [
                'role' => $role,
                'content' => $message->message_en
            ];
        }

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $formattedMessages,
            'temperature' => 0.7,
            'max_tokens' => 500
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('GPT API request failed with status code: ' . $httpCode);
        }

        // dd($response);
        return json_decode($response, true);
    }

    /**
     * @param string $messages
     * @return string 保存された音声ファイルのパス
     */
    public function callTtsApi($messages)
    {
        $apiKey = env('OPENAI_API_KEY');
        $url = 'https://api.openai.com/v1/audio/speech';

        $data = [
            'model' => 'tts-1',
            'input' => $messages,
            // alloy, ash, coral, echo, fable, onyx, nova, sage, shimmer
            'voice' => 'nova',
            'response_format' => 'wav'
        ];

        // 現在日時を使用してファイル名を生成
        $fileName = 'audio_' . date('YmdHis') . '.wav';
        $filePath = 'ai_audio/' . $fileName;
        $fullPath = storage_path('app/public/' . $filePath);

        // ディレクトリが存在しない場合は作成
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('TTS API request failed with status code: ' . $httpCode);
        }

        // レスポンスの音声データをファイルに保存
        file_put_contents($fullPath, $response);

        // storage/app/public からの相対パスを返す
        return $filePath;
    }

    /**
     * @param string $text 翻訳したい英文
     * @return array
     */
    public function translateToJapanese($text)
    {
        $apiKey = env('OPENAI_API_KEY');
        $url = 'https://api.openai.com/v1/chat/completions';

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a translator. Translate the following English text to natural Japanese. Only respond with the translation, no explanations or additional text.'
            ],
            [
                'role' => 'user',
                'content' => $text
            ]
        ];

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('GPT API request failed with status code: ' . $httpCode);
        }

        return json_decode($response, true);
    }
}
