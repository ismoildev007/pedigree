<?php

namespace App\Services\Google;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GoogleCalendarService
{
    protected $client;
    protected $calendarService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAccessType('offline');

        $this->authenticate();
    }

    public function getClient()
    {
        return $this->client;
    }

    protected function authenticate()
    {
        $accessToken = Cache::get('google_access_token');
        $refreshToken = Cache::get('google_refresh_token');

        if ($accessToken) {
            $this->client->setAccessToken($accessToken);
        } elseif ($refreshToken) {
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            // Update cache
            $newToken = $this->client->getAccessToken();
            Cache::put('google_access_token', $newToken, now()->addSeconds($newToken['expires_in']));
        }

        if ($this->client->getAccessToken()) {
            $this->calendarService = new Calendar($this->client);
        }
    }

    public function createMeetEvent($candidateEmail, Carbon $startTime, Carbon $endTime, $summary = 'Interview')
    {
        if (!$this->calendarService) {
            throw new \Exception("Google Calendar Service not authenticated.");
        }

        $event = new Event([
            'summary' => $summary,
            'description' => 'Interview with candidate',
            'start' => [
                'dateTime' => $startTime->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $endTime->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
            'attendees' => [
                ['email' => $candidateEmail],
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ]);

        $calendarId = 'primary';
        $event = $this->calendarService->events->insert($calendarId, $event, ['conferenceDataVersion' => 1]);

        return [
            'id' => $event->getId(),
            'link' => $event->getHangoutLink(),
            'htmlLink' => $event->getHtmlLink(),
        ];
    }
}
