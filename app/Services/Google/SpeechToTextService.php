<?php

namespace App\Services\Google;

use Google\Client;
use Google\Service\Speech;
use Google\Service\Speech\RecognitionAudio;
use Google\Service\Speech\RecognitionConfig;
use Illuminate\Support\Facades\Log;

class SpeechToTextService
{
    protected $client;
    protected $speechService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setScopes([Speech::CLOUD_PLATFORM]);
        // Ideally use service account credentials for server-side operations
        // $this->client->setAuthConfig(storage_path('app/google-credentials.json'));
    }

    public function transcribeAudio($gcsUri)
    {
        $this->speechService = new Speech($this->client);

        $audio = new RecognitionAudio();
        $audio->setUri($gcsUri);

        $config = new RecognitionConfig();
        $config->setEncoding('LINEAR16'); // Adjust based on audio format
        $config->setSampleRateHertz(16000); // Adjust based on audio format
        $config->setLanguageCode('en-US');

        try {
            // For long audio (interviews), use Async method
            $operation = $this->speechService->speech->longrunningrecognize($config, $audio);

            // In a real scenario, you'd store the operation name and poll for status later.
            // For simplicity here, we might just return the operation name.
            return $operation->getName();

        } catch (\Exception $e) {
            Log::error("Speech-to-Text failed: " . $e->getMessage());
            return null;
        }
    }
}
