<?php

return [
    'application_name' => env('GOOGLE_APPLICATION_NAME', 'Google Meeting Integration'),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'scopes' => [
        \Google\Service\Calendar::CALENDAR,
        \Google\Service\Drive::DRIVE_READONLY,
        // Add other scopes as needed
    ],
    'access_type' => 'offline',
    'approval_prompt' => 'force',
];
