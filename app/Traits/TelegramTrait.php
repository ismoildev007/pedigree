<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

trait TelegramTrait
{
    protected $telegram;

    public function __construct()
    {
        $token = env('TELEGRAM_BOT_TOKEN', '7638629069:AAHXEEL0410voDh_hcP7vO1fuIhLXM2A05U');
        if (!$token) {
            Log::error("TELEGRAM_BOT_TOKEN is not set in .env file.");
            throw new \Exception("Telegram bot token is missing.");
        }
        $this->telegram = new Api($token);
    }
}
