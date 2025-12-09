<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ContestSetting;
use App\Models\Vote;
use App\Models\Referral;
use App\Traits\TelegramTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HandlerService
{
    use TelegramTrait;

    public function handle()
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
                [
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'username' => $user->getUsername(),
                    'language_code' => $user->getLanguageCode() ?? 'uz',
                ]
            );

            // Buyruqlarni qayta ishlash
            if ($text === '/start') {
                // Referral kodni tekshirish
                $parts = explode(' ', $text);
                if (isset($parts[1])) {
                    $this->handleReferral($dbUser, $parts[1]);
                }

                $this->sendMainMenu($chatId, $dbUser);
            } elseif ($text === '/help') {
                $this->sendHelp($chatId);
            } elseif ($text === '/stats') {
                $this->sendUserStats($chatId, $dbUser);
            } elseif ($text === '/mylink') {
                $this->sendReferralLink($chatId, $dbUser);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error("Webhook error: {$e->getMessage()}");
            if (isset($chatId)) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => '❌ Xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.',
                ]);
            }
            return response()->json(['status' => 'error']);
        }
    }

    protected function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $messageId = $callbackQuery->getMessage()->getMessageId();
        $userId = $callbackQuery->getFrom()->getId();
        $data = $callbackQuery->getData();

        $user = User::where('telegram_id', $userId)->first();

        // Main menu callbacks
        if ($data === 'main_menu') {
            $this->sendMainMenu($chatId, $user);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        if ($data === 'active_contests') {
            $this->sendActiveContests($chatId, $user);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        if ($data === 'my_votes') {
            $this->sendMyVotes($chatId, $user);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        if ($data === 'top_winners') {
            $this->sendAllContestWinners($chatId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        if ($data === 'my_referrals') {
            $this->sendReferralStats($chatId, $user);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        // Contest selection
        if (strpos($data, 'select_contest_') === 0) {
            $contestId = str_replace('select_contest_', '', $data);
            $this->sendContestChannels($chatId, $user, $contestId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        // Channel membership check
        if (strpos($data, 'check_membership_') === 0) {
            $contestId = str_replace('check_membership_', '', $data);
            $this->checkMembershipAndShowStudents($chatId, $user, $contestId, $callbackQuery->getId());
            return;
        }

        // Vote for student
        if (strpos($data, 'vote_') === 0) {
            $parts = explode('_', $data);
            if (count($parts) === 4 && $parts[0] === 'vote' && $parts[1] === 'student') {
                $studentId = $parts[2];
                $contestId = $parts[3];
                $this->handleVote($chatId, $user, $studentId, $contestId, $callbackQuery->getId());
                return;
            }
        }

        // Refresh student list
        if (strpos($data, 'refresh_contest_') === 0) {
            $contestId = str_replace('refresh_contest_', '', $data);
            $this->sendStudentList($chatId, $user, $contestId);
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => '✅ Ro\'yxat yangilandi!'
            ]);
            return;
        }

        // Show contest winners
        if (strpos($data, 'winners_') === 0) {
            $contestId = str_replace('winners_', '', $data);
            $this->sendPrizeWinners($chatId, $contestId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
            return;
        }

        $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
    }

    protected function sendMainMenu($chatId, $user)
    {
        $activeContestsCount = ContestSetting::where('status', 'active')
            ->where('end_date', '>=', now())
            ->count();

        $userVotesCount = Vote::where('user_id', $user->id)->count();
        $referralsCount = Referral::where('referrer_id', $user->id)->count();

        $text = "👋 Assalomu alaykum, {$user->first_name}!\n\n";
        $text .= "📊 Sizning statistikangiz:\n";
        $text .= "✅ Berilgan ovozlar: {$userVotesCount}\n";
        $text .= "👥 Taklif qilinganlar: {$referralsCount}\n";
        $text .= "🎯 Faol konkurslar: {$activeContestsCount}\n\n";
        $text .= "Quyidagi tugmalardan birini tanlang:";

        $keyboard = [
            [['text' => '🎯 Faol konkurslar', 'callback_data' => 'active_contests']],
            [['text' => '📊 Mening ovozlarim', 'callback_data' => 'my_votes']],
            [
                ['text' => '🏆 Top g\'oliblar', 'callback_data' => 'top_winners'],
                ['text' => '👥 Do\'stlarim', 'callback_data' => 'my_referrals']
            ],
            [['text' => '📱 Referral havolam', 'callback_data' => 'my_referrals']],
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendActiveContests($chatId, $user)
    {
        $activeContests = ContestSetting::where('status', 'active')
            ->where('end_date', '>=', now())
            ->get();

        if ($activeContests->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Hozirda faol konkurs mavjud emas.',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]]
                ])
            ]);
            return;
        }

        $text = "🎯 <b>Faol konkurslar ro'yxati:</b>\n\n";
        $keyboard = [];

        foreach ($activeContests as $contest) {
            $studentsCount = Student::where('contest_id', $contest->id)->count();
            $totalVotes = Student::where('contest_id', $contest->id)->sum('votes');
            $userVoted = Vote::where('user_id', $user->id)
                ->where('contest_id', $contest->id)
                ->exists();

            $endDate = Carbon::parse($contest->end_date)->format('d.m.Y H:i');
            $daysLeft = Carbon::parse($contest->end_date)->diffInDays(now());

            $text .= "📌 <b>{$contest->name}</b>\n";
            $text .= "👥 Ishtirokchilar: {$studentsCount}\n";
            $text .= "🗳 Jami ovozlar: {$totalVotes}\n";
            $text .= "⏰ Tugashi: {$endDate} ({$daysLeft} kun qoldi)\n";
            $text .= $userVoted ? "✅ Siz ovoz bergansiz\n" : "⚠️ Hali ovoz bermadingiz\n";
            $text .= "\n";

            $keyboard[] = [[
                'text' => ($userVoted ? '✅ ' : '🎯 ') . $contest->name,
                'callback_data' => 'select_contest_' . $contest->id
            ]];
        }

        $keyboard[] = [['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendContestChannels($chatId, $user, $contestId)
    {
        $contest = ContestSetting::find($contestId);
        if (!$contest || $contest->status !== 'active') {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Konkurs topilmadi yoki faol emas.',
            ]);
            return;
        }

        $channels = $contest->channels;
        if ($channels->isEmpty()) {
            $this->sendStudentList($chatId, $user, $contestId);
            return;
        }

        $text = "🎯 <b>{$contest->name}</b>\n\n";
        $text .= "Konkursda ishtirok etish uchun quyidagi kanal(lar)ga a'zo bo'ling:\n\n";

        $keyboard = [];
        foreach ($channels as $index => $channel) {
            $keyboard[] = [['text' => ($index + 1) . '. ' . $channel->name, 'url' => $channel->invite_link]];
        }

        $keyboard[] = [['text' => "✅ A'zo bo'ldim, tekshiring", 'callback_data' => 'check_membership_' . $contestId]];
        $keyboard[] = [['text' => '🔙 Orqaga', 'callback_data' => 'active_contests']];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function checkMembershipAndShowStudents($chatId, $user, $contestId, $callbackQueryId)
    {
        $contest = ContestSetting::find($contestId);
        if (!$contest) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ Konkurs topilmadi!',
                'show_alert' => true
            ]);
            return;
        }

        $channels = $contest->channels;
        if ($channels->isEmpty()) {
            $this->sendStudentList($chatId, $user, $contestId);
            $this->telegram->answerCallbackQuery(['callback_query_id' => $callbackQueryId]);
            return;
        }

        $allChannelsJoined = true;
        $notJoinedChannels = [];

        foreach ($channels as $channel) {
            try {
                $member = $this->telegram->getChatMember([
                    'chat_id' => $channel->telegram_id,
                    'user_id' => $user->telegram_id,
                ]);

                if (in_array($member['status'], ['member', 'administrator', 'creator'])) {
                    ChannelMember::firstOrCreate([
                        'user_id' => $user->id,
                        'channel_id' => $channel->id,
                    ]);
                } else {
                    $allChannelsJoined = false;
                    $notJoinedChannels[] = $channel->name;
                }
            } catch (\Exception $e) {
                Log::error("Error checking membership: {$e->getMessage()}");
                $allChannelsJoined = false;
                $notJoinedChannels[] = $channel->name;
            }
        }

        if ($allChannelsJoined) {
            $this->sendStudentList($chatId, $user, $contestId);
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '✅ Barcha kanallarga a\'zo ekansiz!',
            ]);
        } else {
            $channelsList = implode(', ', $notJoinedChannels);
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => "❌ Quyidagi kanallarga a'zo bo'lmadingiz: {$channelsList}",
                'show_alert' => true
            ]);
        }
    }

    protected function sendStudentList($chatId, $user, $contestId)
    {
        $contest = ContestSetting::find($contestId);
        if (!$contest) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Konkurs topilmadi.',
            ]);
            return;
        }

        $students = Student::where('contest_id', $contestId)
            ->orderBy('votes', 'desc')
            ->get();

        if ($students->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Bu konkursda hali ishtirokchilar yo\'q.',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[['text' => '🔙 Orqaga', 'callback_data' => 'active_contests']]]
                ])
            ]);
            return;
        }

        $userVote = Vote::where('user_id', $user->id)
            ->where('contest_id', $contestId)
            ->first();

        $text = "🎯 <b>{$contest->name}</b>\n\n";
        $text .= "👥 Ishtirokchilar ro'yxati:\n";
        $text .= $userVote ? "✅ Siz allaqachon ovoz bergansiz!\n\n" : "⚠️ Ovoz berish uchun tugmani bosing:\n\n";

        $keyboard = [];
        foreach ($students as $index => $student) {
            $position = $index + 1;
            $medal = $position === 1 ? '🥇' : ($position === 2 ? '🥈' : ($position === 3 ? '🥉' : '👤'));
            $voted = $userVote && $userVote->student_id == $student->id ? '✅' : '';

            $keyboard[] = [[
                'text' => "{$medal} {$position}. {$student->first_name} {$student->last_name} | 🗳 {$student->votes} {$voted}",
                'callback_data' => "vote_student_{$student->id}_{$contestId}"
            ]];
        }

        $keyboard[] = [
            ['text' => '🔄 Yangilash', 'callback_data' => 'refresh_contest_' . $contestId],
            ['text' => '🏆 G\'oliblar', 'callback_data' => 'winners_' . $contestId]
        ];
        $keyboard[] = [['text' => '🔙 Orqaga', 'callback_data' => 'active_contests']];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function handleVote($chatId, $user, $studentId, $contestId, $callbackQueryId)
    {
        $contest = ContestSetting::find($contestId);
        $student = Student::find($studentId);

        if (!$contest || !$student) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ Xatolik yuz berdi!',
                'show_alert' => true
            ]);
            return;
        }

        // Konkurs tugaganligini tekshirish
        if (Carbon::parse($contest->end_date)->isPast()) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '⏰ Bu konkurs allaqachon tugagan!',
                'show_alert' => true
            ]);
            return;
        }

        // Foydalanuvchi bu konkursda ovoz berganligini tekshirish
        $existingVote = Vote::where('user_id', $user->id)
            ->where('contest_id', $contestId)
            ->first();

        if ($existingVote) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ Siz bu konkursda allaqachon ovoz bergansiz!',
                'show_alert' => true
            ]);
            return;
        }

        // Anti-fraud: IP va vaqt bo'yicha tekshirish
        $recentVotes = Vote::where('user_id', $user->id)
            ->where('created_at', '>', now()->subMinutes(1))
            ->count();

        if ($recentVotes >= 3) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '⚠️ Juda tez ovoz beryapsiz. Biroz kuting!',
                'show_alert' => true
            ]);
            return;
        }

        // Ovoz berish
        try {
            \DB::beginTransaction();

            Vote::create([
                'user_id' => $user->id,
                'student_id' => $studentId,
                'contest_id' => $contestId,
            ]);

            $student->increment('votes');

            \DB::commit();

            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => "✅ Siz {$student->first_name} {$student->last_name}ga ovoz berdingiz!",
                'show_alert' => true
            ]);

            // Ro'yxatni yangilash
            $this->sendStudentList($chatId, $user, $contestId);

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error("Vote error: {$e->getMessage()}");
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ Xatolik yuz berdi. Qaytadan urinib ko\'ring!',
                'show_alert' => true
            ]);
        }
    }

    protected function sendPrizeWinners($chatId, $contestId)
    {
        $contest = ContestSetting::find($contestId);
        if (!$contest) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Konkurs topilmadi.',
            ]);
            return;
        }

        $prizes = $contest->prizes()->orderBy('position')->get();
        $students = Student::where('contest_id', $contestId)
            ->orderBy('votes', 'desc')
            ->take($prizes->count())
            ->get();

        if ($students->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Hozirda g\'oliblar yo\'q.',
            ]);
            return;
        }

        $text = "🏆 <b>{$contest->name}</b>\n\n";
        $text .= "G'oliblar ro'yxati:\n\n";

        foreach ($students as $index => $student) {
            $position = $index + 1;
            $medal = $position === 1 ? '🥇' : ($position === 2 ? '🥈' : '🥉');
            $prize = $prizes->get($index) ? $prizes->get($index)->name : 'Sovrin belgilanmagan';

            $text .= "{$medal} <b>{$position}-o'rin:</b>\n";
            $text .= "   👤 {$student->first_name} {$student->last_name}\n";
            $text .= "   🎁 Sovrin: {$prize}\n";
            $text .= "   🗳 Ovozlar: {$student->votes}\n\n";
        }

        $keyboard = [
            [['text' => '🔄 Yangilash', 'callback_data' => 'winners_' . $contestId]],
            [['text' => '🔙 Orqaga', 'callback_data' => 'select_contest_' . $contestId]]
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendMyVotes($chatId, $user)
    {
        $votes = Vote::where('user_id', $user->id)
            ->with(['student', 'contest'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($votes->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Siz hali hech kimga ovoz bermadingiz.',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]]
                ])
            ]);
            return;
        }

        $text = "📊 <b>Sizning ovozlaringiz:</b>\n\n";

        foreach ($votes as $vote) {
            $date = Carbon::parse($vote->created_at)->format('d.m.Y H:i');
            $text .= "🎯 <b>{$vote->contest->name}</b>\n";
            $text .= "   👤 {$vote->student->first_name} {$vote->student->last_name}\n";
            $text .= "   📅 {$date}\n";
            $text .= "   🗳 Hozirgi ovozlar: {$vote->student->votes}\n\n";
        }

        $keyboard = [[['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendAllContestWinners($chatId)
    {
        $contests = ContestSetting::where('status', 'active')->get();

        if ($contests->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Faol konkurslar yo\'q.',
            ]);
            return;
        }

        $text = "🏆 <b>Barcha konkurslar bo'yicha g'oliblar:</b>\n\n";

        foreach ($contests as $contest) {
            $topStudents = Student::where('contest_id', $contest->id)
                ->orderBy('votes', 'desc')
                ->take(3)
                ->get();

            if ($topStudents->isNotEmpty()) {
                $text .= "🎯 <b>{$contest->name}</b>\n";
                foreach ($topStudents as $index => $student) {
                    $medal = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : '🥉');
                    $text .= "   {$medal} {$student->first_name} {$student->last_name} - {$student->votes} ovoz\n";
                }
                $text .= "\n";
            }
        }

        $keyboard = [[['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function handleReferral($user, $referralCode)
    {
        if ($user->referred_by) {
            return; // Allaqachon referral orqali kelgan
        }

        $referrer = User::where('referral_code', $referralCode)->first();
        if ($referrer && $referrer->id !== $user->id) {
            $user->referred_by = $referrer->id;
            $user->save();

            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
            ]);

            // Referrerga xabar yuborish
            $this->telegram->sendMessage([
                'chat_id' => $referrer->telegram_id,
                'text' => "🎉 Yangi foydalanuvchi sizning havolangiz orqali qo'shildi!\n👤 {$user->first_name}",
            ]);
        }
    }

    protected function sendReferralLink($chatId, $user)
    {
        if (!$user->referral_code) {
            $user->referral_code = \Str::random(10);
            $user->save();
        }

        $botUsername = config('telegram.bots.mybot.username');
        $link = "https://t.me/{$botUsername}?start={$user->referral_code}";

        $referralsCount = Referral::where('referrer_id', $user->id)->count();

        $text = "📱 <b>Sizning referral havolangiz:</b>\n\n";
        $text .= "<code>{$link}</code>\n\n";
        $text .= "👥 Siz taklif qilganlar: {$referralsCount} ta\n\n";
        $text .= "Do'stlaringizni taklif qiling va bonuslar yutib oling! 🎁";

        $keyboard = [
            [['text' => '📤 Havolani ulashish', 'url' => "https://t.me/share/url?url={$link}&text=Konkursda ishtirok eting!"]],
            [['text' => '👥 Do\'stlarim', 'callback_data' => 'my_referrals']],
            [['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendReferralStats($chatId, $user)
    {
        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referred')
            ->orderBy('created_at', 'desc')
            ->get();

        $text = "👥 <b>Sizning do'stlaringiz:</b>\n\n";

        if ($referrals->isEmpty()) {
            $text .= "Hozircha hech kim yo'q. Do'stlaringizni taklif qiling! 🎉";
        } else {
            foreach ($referrals as $index => $referral) {
                $date = Carbon::parse($referral->created_at)->format('d.m.Y');
                $votes = Vote::where('user_id', $referral->referred_id)->count();
                $text .= ($index + 1) . ". {$referral->referred->first_name}\n";
                $text .= "   📅 Qo'shildi: {$date}\n";
                $text .= "   🗳 Ovozlar: {$votes}\n\n";
            }
        }

        $keyboard = [
            [['text' => '📱 Referral havolam', 'callback_data' => 'my_referrals']],
            [['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendUserStats($chatId, $user)
    {
        $votesCount = Vote::where('user_id', $user->id)->count();
        $referralsCount = Referral::where('referrer_id', $user->id)->count();
        $joinedDate = Carbon::parse($user->created_at)->format('d.m.Y');

        $text = "📊 <b>Sizning statistikangiz:</b>\n\n";
        $text .= "👤 Ism: {$user->first_name}\n";
        $text .= "📅 Qo'shilgan sana: {$joinedDate}\n";
        $text .= "🗳 Berilgan ovozlar: {$votesCount}\n";
        $text .= "👥 Taklif qilinganlar: {$referralsCount}\n";

        $keyboard = [[['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    protected function sendHelp($chatId)
    {
        $text = "❓ <b>Yordam bo'limi</b>\n\n";
        $text .= "📌 <b>Asosiy buyruqlar:</b>\n";
        $text .= "/start - Botni ishga tushirish\n";
        $text .= "/help - Yordam olish\n";
        $text .= "/stats - Statistikani ko'rish\n";
        $text .= "/mylink - Referral havolani olish\n\n";
        $text .= "📌 <b>Qanday ishlaydi?</b>\n";
        $text .= "1️⃣ Faol konkursni tanlang\n";
        $text .= "2️⃣ Kanal(lar)ga a'zo bo'ling\n";
        $text .= "3️⃣ Yoqtirgan ishtirokchingizga ovoz bering\n";
        $text .= "4️⃣ Do'stlaringizni taklif qiling va bonus oling!\n\n";
        $text .= "💡 Savol bo'lsa, @support ga murojaat qiling.";

        $keyboard = [[['text' => '🔙 Orqaga', 'callback_data' => 'main_menu']]];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }
}
