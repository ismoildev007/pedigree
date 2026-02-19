<?php

namespace App\Services;

use App\Repositories\InterviewRepository;
use App\Services\Google\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterviewService
{
    protected $interviewRepository;
    protected $googleCalendarService;

    public function __construct(
        InterviewRepository $interviewRepository,
        GoogleCalendarService $googleCalendarService
    ) {
        $this->interviewRepository = $interviewRepository;
        $this->googleCalendarService = $googleCalendarService;
    }

    public function scheduleInterview(array $data)
    {
        return DB::transaction(function () use ($data) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);

            // 1. Create Google Calendar Event
            try {
                $event = $this->googleCalendarService->createMeetEvent(
                    $data['candidate_email'],
                    $startTime,
                    $endTime
                );

                $data['google_meet_link'] = $event['link'];
                $data['google_event_id'] = $event['id'];

            } catch (\Exception $e) {
                Log::error("Failed to create Google Meet event: " . $e->getMessage());
                // Depending on requirements, we might want to fail hard or soft.
                // For now, let's allow creating without a link if API fails, but log it.
                // Or re-throw if it's critical.
                // throw $e; 
            }

            // 2. Save to Database
            $interview = $this->interviewRepository->create($data);

            return $interview;
        });
    }
}
