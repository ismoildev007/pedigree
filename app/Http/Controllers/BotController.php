<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
use App\Models\User;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ContestSetting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{
    protected $telegram;

    public function __construct()
    {
        $token = '7638629069:AAHXEEL0410voDh_hcP7vO1fuIhLXM2A05U';
        if (!$token) {
            Log::error("TELEGRAM_BOT_TOKEN is not set in .env file.");
            throw new \Exception("Telegram bot token is missing.");
        }
        $this->telegram = new Api($token);
    }

    public function handleWebhook(Request $request)
    {
        try {
            $update = $this->telegram->getWebhookUpdate();
            if (!$update) {
                Log::warning("No valid webhook update received.");
                return response()->json(['status' => 'ok']);
            }

            // Callback queryni qayta ishlash
            if ($update->getCallbackQuery()) {
                $this->handleCallbackQuery($update->getCallbackQuery());
                return response()->json(['status' => 'ok']);
            }

            $message = $update->getMessage();
            if (!$message) {
                Log::warning("No message found in webhook update.");
                return response()->json(['status' => 'ok']);
            }

            $chatId = $message->getChat()->getId();
            $text = $message->getText();
            $user = $message->getFrom();

            // Foydalanuvchini ro'yxatdan o'tkazish
            $dbUser = User::firstOrCreate(
                ['telegram_id' => $user->getId()],
                ['first_name' => $user->getFirstName()]
            );

            // Buyruqlarni qayta ishlash
            if ($text === '/start') {
                $activeContest = ContestSetting::where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->first();
                if (!$activeContest) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Hozirda faol konkurs mavjud emas.',
                    ]);
                    return response()->json(['status' => 'ok']);
                }

                $this->sendChannelList($chatId, $dbUser);
            } elseif ($text === 'Sovrin egalari') {
                $this->sendPrizeWinners($chatId);
            }

            return response()->json(['status' => 'ok']);
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

    protected function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $userId = $callbackQuery->getFrom()->getId();
        $data = $callbackQuery->getData();

        if ($data === 'check_membership') {
            $user = User::where('telegram_id', $userId)->first();

            // Faol konkursni olish
            $activeContest = ContestSetting::where('status', 'active')
                ->where('end_date', '>=', now())
                ->first();

            if (!$activeContest) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Hozirda faol konkurs mavjud emas.',
                ]);
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                ]);
                return;
            }

            // Faol konkursga bog'liq kanallarni olish
            $channels = $activeContest->channels;
            if ($channels->isEmpty()) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Hozirda aʼzo boʼlish kerak boʼlgan kanal mavjud emas.',
                ]);
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                ]);
                return;
            }

            $allChannelsJoined = true;

            foreach ($channels as $channel) {
                try {
                    $member = $this->telegram->getChatMember([
                        'chat_id' => $channel->telegram_id,
                        'user_id' => $userId,
                    ]);

                    if (in_array($member['status'], ['member', 'administrator', 'creator'])) {
                        // Kanal a'zoligini saqlash
                        ChannelMember::firstOrCreate([
                            'user_id' => $user->id,
                            'channel_id' => $channel->id,
                        ]);
                    } else {
                        $allChannelsJoined = false;
                    }
                } catch (\Exception $e) {
                    Log::error("Error checking membership for user {$userId} in channel {$channel->telegram_id}: {$e->getMessage()}");
                    $allChannelsJoined = false;
                }
            }

            if ($allChannelsJoined) {
                $this->sendStudentList($chatId, $user);
            } else {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Iltimos, barcha kanal(lar)ga a'zo bo'ling va qayta urinib ko'ring.",
                ]);
                $this->sendChannelList($chatId, $user);
            }

            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
            ]);
        } elseif (strpos($data, 'vote_student_') === 0) {
            $studentId = str_replace('vote_student_', '', $data);
            $user = User::where('telegram_id', $userId)->first();

            if ($user->voted_student_id) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Siz allaqachon bir studentga ovoz bergansiz. Faqat bitta ovoz berish mumkin.',
                ]);
            } else {
                $student = Student::find($studentId);
                if ($student) {
                    $student->increment('votes');
                    $user->voted_student_id = $studentId;
                    $user->save();

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Siz {$student->first_name} {$student->last_name} ga ovoz berdingiz! Ovozlar soni: {$student->votes}",
                    ]);
                } else {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Student topilmadi. Iltimos, qaytadan urinib ko‘ring.',
                    ]);
                }
            }

            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
            ]);
        } elseif ($data === 'refresh_list') {
            $this->sendStudentList($chatId, User::where('telegram_id', $userId)->first());
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
            ]);
        }
    }

    protected function sendChannelList($chatId, $user)
    {
        // Faol konkursni olish
        $activeContest = ContestSetting::where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();

        if (!$activeContest) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hozirda faol konkurs mavjud emas.',
            ]);
            return;
        }

        // Faol konkursga bog'liq kanallarni olish
        $channels = $activeContest->channels;
        if ($channels->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hozirda aʼzo boʼlish kerak boʼlgan kanal mavjud emas.',
            ]);
            return;
        }

        $keyboard = [];
        foreach ($channels as $channel) {
            $keyboard[] = [['text' => $channel->name, 'url' => $channel->invite_link]];
        }
        $keyboard[] = [['text' => "A'zo bo'ldim", 'callback_data' => 'check_membership']];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Konkursda ishtirok etish uchun quyidagi kanal(lar)ga aʼzo boʼling:",
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    }

    protected function sendStudentList($chatId, $user)
    {
        // Aktiv holatdagi contest_id'larni olish
        $activeContestIds = ContestSetting::where('status', 'active')->pluck('id');

        // Studentlar faqat aktiv contestga tegishli bo'lsin
        $students = Student::whereIn('contest_id', $activeContestIds)
            ->orderBy('votes', 'desc')
            ->get();

        Log::info("Fetched students count: " . $students->count());

        if ($students->isEmpty()) {
            Log::warning("No active contest students found in database.");
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hozirda faqat aktiv musobaqada studentlar mavjud emas.',
            ]);
            return;
        }

        $keyboard = [];
        foreach ($students as $student) {
            Log::info("Adding student to keyboard: {$student->id} - {$student->first_name} {$student->last_name}");
            $keyboard[] = [
                [
                    'text' => "{$student->first_name} {$student->last_name} ({$student->votes})",
                    'callback_data' => "vote_student_{$student->id}"
                ]
            ];
        }

        // Yangilash va Sovrin egalari tugmasini qo'shish
        $keyboard[] = [
            ['text' => '🔄 Yangilash', 'callback_data' => 'refresh_list'],
        ];

        Log::info("Keyboard prepared: " . json_encode($keyboard));

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Quyidagi studentlardan biriga ovoz bering:",
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    }


    protected function sendPrizeWinners($chatId)
    {
        // Faol konkursni olish
        $activeContest = ContestSetting::where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();

        if (!$activeContest) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hozirda faol konkurs mavjud emas.',
            ]);
            return;
        }

        // Sovrinlarni olish (position bo'yicha tartiblangan)
        $prizes = $activeContest->prizes()->orderBy('position')->get();

        if ($prizes->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hozirda sovrinlar mavjud emas.',
            ]);
            return;
        }

        // Studentlarni ovozlar bo'yicha tartiblab, eng yaxshi 3 tasini olish
        $students = Student::orderBy('votes', 'desc')->take(3)->get();

        if ($students->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hozirda sovrin egalari mavjud emas.',
            ]);
            return;
        }

        // Xabar tayyorlash
        $message = "🏆 Sovrin egalari:\n\n";
        foreach ($students as $index => $student) {
            $place = $index + 1;
            // Mos sovrinni olish (agar mavjud bo'lsa, aks holda "Sovrin yo'q" deb ko'rsatamiz)
            $prize = $prizes->get($index) ? $prizes->get($index)->name : 'Sovrin yo‘q';
            $message .= "$place-o‘rin: {$student->first_name} {$student->last_name} (Sovrin: $prize, Ovozlar: {$student->votes})\n";
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);
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
