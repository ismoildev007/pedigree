<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = $this->getClient();

        if ($request->has('error')) {
            return response()->json(['error' => $request->input('error'), 'details' => 'Access denied or cancelled by user.'], 400);
        }

        if ($request->has('code')) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

                // Check if token fetch failed (it returns array with 'error' key sometimes)
                if (isset($token['error'])) {
                    return response()->json(['error' => $token['error'], 'details' => $token['error_description'] ?? 'Unknown error'], 400);
                }

                // In a real app, associate this with the user or store securely.
                // For this demo, we'll use Cache for a "system-wide" token.
                Cache::put('google_access_token', $token, now()->addSeconds($token['expires_in']));
                if (isset($token['refresh_token'])) {
                    Cache::forever('google_refresh_token', $token['refresh_token']);
                }

                return response()->json(['message' => 'Google Authentication successful!', 'token_info' => $token]);

            } catch (\Exception $e) {
                return response()->json(['error' => 'Token fetch failed', 'message' => $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'Authorization code not received'], 400);
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName(config('google.application_name'));
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));
        $client->setScopes(config('google.scopes'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent'); // Force refresh token

        return $client;
    }
}
