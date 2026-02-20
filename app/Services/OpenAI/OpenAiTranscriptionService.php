<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiTranscriptionService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', env('OPENAI_API_KEY'));
    }

    public function transcribeAudio($filePath)
    {
        Log::info("Starting OpenAI Transcription for file: $filePath");

        if (!file_exists($filePath)) {
            Log::error("File not found for transcription: $filePath");
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(120) // Transcriptions can take time
                ->attach(
                    'file',
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'response_format' => 'text', // Request simple text response
                    // 'language' => 'en', // Auto-detect is usually clear, or set if known
                ]);

            if ($response->successful()) {
                Log::info("Transcription successful.");
                return $response->body(); // Since we requested 'text', body is the string
            }

            Log::error("OpenAI Transcription Failed: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("Transcription Exception: " . $e->getMessage());
            return null;
        }
    }
}
