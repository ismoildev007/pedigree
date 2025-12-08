<?php

namespace App\Http\Controllers;

use App\Services\HandlerService;
use App\Traits\TelegramTrait;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{
    use TelegramTrait;
    protected HandlerService $handlerService;

    public function __construct(HandlerService $handlerService)
    {
        $this->handlerService = $handlerService;
    }

    public function handleWebhook()
    {
        try {
            $this->handlerService->handle();
        } catch (\Exception $e) {
            Log::error("Webhook error: {$e->getMessage()}");
            if (isset($chatId)) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Xatolik yuz berdi. Iltimos, qaytadan urinib koʼring.',
                ]);
            }
            return response()->json(['status' => 'error']);
        }
    }

    public function setBotCommands()
    {
        $commands = [
            [
                'command' => 'start',
                'description' => 'Botni ishga tushirish / Start the bot',
            ]
        ];

        Telegram::bot('mybot')->setMyCommands([
            'commands' => $commands,
        ]);

        Log::info("Bot commands set successfully!");
    }
}
