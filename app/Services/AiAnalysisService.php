<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    protected $apiKey;

    public function __construct()
    {
        // Assuming OpenAI for now, but could be Gemini
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function analyzeInterview(string $transcript)
    {
        $prompt = "Analyze the following interview transcript. Identify key strengths, weaknesses, and provide an overall score (1-10). Transcript: " . substr($transcript, 0, 10000); // Truncate if too long

        try {
            // Mocking the call structure for OpenAI
            /*
            $response = Http::withToken($this->apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an HR assistant expert in candidate evaluation.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            return $response->json()['choices'][0]['message']['content'];
            */

            // Return mock analysis
            return [
                'summary' => 'The candidate demonstrated strong technical skills but lacked clear communication.',
                'strengths' => ['PHP', 'Laravel', 'System Design'],
                'weaknesses' => ['Communication', 'Detailed explanations'],
                'score' => 7,
            ];

        } catch (\Exception $e) {
            Log::error("AI Analysis failed: " . $e->getMessage());
            return null;
        }
    }
}
