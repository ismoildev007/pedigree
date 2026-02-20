<?php

namespace App\Services\Zoom;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZoomMeetingService
{
    protected $baseUrl;
    protected $accountId;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('zoom.base_url', 'https://api.zoom.us/v2');
        $this->accountId = config('zoom.account_id');
        $this->clientId = config('zoom.client_id');
        $this->clientSecret = config('zoom.client_secret');

        \Log::info("ZoomService Initialized. AccountID: " . substr($this->accountId, 0, 5) . "***");
    }

    protected function getAccessToken()
    {
        // Check cache first
        if (Cache::has('zoom_access_token')) {
            \Log::info("Zoom Token found in cache.");
            return Cache::get('zoom_access_token');
        }

        \Log::info("Zoom Token not cached. Requesting new token...");
        \Log::info("Zoom Auth URL: https://zoom.us/oauth/token");
        \Log::info("Zoom Auth Params: grant_type=account_credentials, account_id=" . $this->accountId);

        // Zoom ba'zida query paramga nisbatan body orqali yuborilgan ma'lumotni yaxshi ko'radi
        // Trying mixed approach: Query param for account_id (safest) AND body just in case, 
        // to be absolutely sure we cover all bases or stick to one working method.
        // Let's stick to the URL query param method which is documented for Server-to-Server.

        $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$this->accountId}";

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->post($url);

        \Log::info("Zoom Auth Response Status: " . $response->status());
        \Log::info("Zoom Auth Response Body: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'];

            // Cache token (subtract 60s for safety buffer)
            Cache::put('zoom_access_token', $token, now()->addSeconds($expiresIn - 60));
            \Log::info("Zoom Token retrieved and cached successfully.");
            return $token;
        }

        // Xatoni aniq ko'rish uchun logga chiqaramiz
        \Log::error("Zoom Auth Failed. Body: " . $response->body());
        throw new \Exception("Zoom Access Token Error: " . $response->status() . " - " . $response->body());
    }

    public function createMeeting($topic, $startTime, $durationMinutes, $candidateEmail)
    {
        \Log::info("Creating Zoom Meeting for: $candidateEmail");

        try {
            $token = $this->getAccessToken();
        } catch (\Exception $e) {
            \Log::error("Aborting creation due to token error: " . $e->getMessage());
            throw $e;
        }

        $url = "{$this->baseUrl}/users/me/meetings";
        \Log::info("Zoom Meeting Create URL: $url");

        $response = Http::withToken($token)
            ->post($url, [
                'topic' => $topic,
                'type' => 2, // Scheduled meeting
                'start_time' => $startTime, // "2024-01-01T10:00:00Z"
                'duration' => $durationMinutes,
                'timezone' => 'Asia/Tashkent', // Adjust as needed
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => false,
                    'mute_upon_entry' => true,
                    'auto_recording' => 'cloud', // Important for our use case!
                ],
            ]);

        \Log::info("Zoom Create Meeting Response: " . $response->status());

        if ($response->successful()) {
            \Log::info("Zoom Meeting Created Successfully.");
            return $response->json();
        }

        \Log::error("Zoom Create Meeting Failed: " . $response->body());
        throw new \Exception("Failed to create Zoom meeting: " . $response->body());
    }

    public function getRecording($meetingId)
    {
        \Log::info("Fetching Zoom Recording for ID: $meetingId");
        $token = $this->getAccessToken();

        // Fetch all recordings for this meeting ID
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/meetings/{$meetingId}/recordings");

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['recording_files'])) {
                // Find the MP4 video file
                foreach ($data['recording_files'] as $file) {
                    if (isset($file['file_type']) && $file['file_type'] === 'MP4') {
                        \Log::info("Found MP4 recording.");
                        return $file['download_url'] . "?access_token=" . $token;
                        // Note: download_url usually requires token appended or header
                    }
                }
            }
        } else {
            \Log::warning("Failed to fetch recording: " . $response->body());
        }

        return null;
    }

    public function getAudioRecording($meetingId)
    {
        \Log::info("Fetching Zoom Audio Recording for ID: $meetingId");
        $token = $this->getAccessToken();

        // Fetch all recordings for this meeting ID
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/meetings/{$meetingId}/recordings");

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['recording_files'])) {
                // First try to find M4A (Audio Only)
                foreach ($data['recording_files'] as $file) {
                    if (isset($file['file_type']) && $file['file_type'] === 'M4A') {
                        \Log::info("Found M4A audio recording.");
                        return $file['download_url'] . "?access_token=" . $token;
                    }
                }

                // Fallback to MP4 if no audio-only file found (Whisper can extract audio from video too, though bigger)
                foreach ($data['recording_files'] as $file) {
                    if (isset($file['file_type']) && $file['file_type'] === 'MP4') {
                        \Log::info("No M4A found, falling back to MP4.");
                        return $file['download_url'] . "?access_token=" . $token;
                    }
                }
            }
        } else {
            \Log::warning("Failed to fetch recording: " . $response->body());
        }

        return null;
    }
}
