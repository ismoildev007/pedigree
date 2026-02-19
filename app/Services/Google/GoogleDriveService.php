<?php

namespace App\Services\Google;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GoogleDriveService
{
    protected $client;
    protected $driveService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAccessType('offline');
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
        // Refresh token logic here if needed
        $this->driveService = new Drive($this->client);
    }

    public function findRecordingByMeetingCode($meetingCode, $startTime)
    {
        if (!$this->driveService) {
            $this->authenticate();
        }

        if (!$this->driveService) {
            throw new \Exception("Google Drive Service not authenticated.");
        }

        // Search for files containing the meeting code in the name
        // and created after the interview start time.
        // MimeType for Google Meet recordings is usually video/mp4

        $time = Carbon::parse($startTime)->subMinutes(5)->toRfc3339String();

        $optParams = [
            'q' => "name contains '{$meetingCode}' and mimeType = 'video/mp4' and createdTime > '{$time}' and trashed = false",
            'fields' => 'files(id, name, webViewLink, webContentLink, createdTime, size)',
            'orderBy' => 'createdTime desc'
        ];

        $results = $this->driveService->files->listFiles($optParams);

        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0];
        }

        return null;
    }

    protected function authenticate()
    {
        $accessToken = Cache::get('google_access_token');
        $refreshToken = Cache::get('google_refresh_token');

        if ($accessToken) {
            $this->client->setAccessToken($accessToken);
        } elseif ($refreshToken) {
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken = $this->client->getAccessToken();
            Cache::put('google_access_token', $newToken, now()->addSeconds($newToken['expires_in']));
        }

        if ($this->client->getAccessToken()) {
            $this->driveService = new Drive($this->client);
        }
    }

    public function getFileDetails($fileId)
    {
        if (!$this->driveService) {
            throw new \Exception("Google Drive Service not authenticated.");
        }
        return $this->driveService->files->get($fileId, ['fields' => 'id, name, webViewLink, webContentLink, mimeType']);
    }
}
