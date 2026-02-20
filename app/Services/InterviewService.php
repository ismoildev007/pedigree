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
            $platform = $data['platform'] ?? 'google_meet';

            try {
                if ($platform === 'zoom') {
                    $zoomService = new \App\Services\Zoom\ZoomMeetingService();
                    $duration = $startTime->diffInMinutes($endTime);
                    $meeting = $zoomService->createMeeting("Interview with candidate", $startTime->toIso8601String(), $duration, $data['candidate_email']);

                    $data['zoom_meeting_link'] = $meeting['join_url'];
                    $data['zoom_meeting_id'] = $meeting['id'];

                } else {
                    // Default to Google Meet
                    $event = $this->googleCalendarService->createMeetEvent(
                        $data['candidate_email'],
                        $startTime,
                        $endTime
                    );

                    $data['google_meet_link'] = $event['link'];
                    $data['google_event_id'] = $event['id'];
                }

            } catch (\Exception $e) {
                Log::error("Failed to create meeting on platform {$platform}: " . $e->getMessage());
                // Allow creation even if meeting fails? Maybe better to throw.
                // For now, consistent with previous logic: log error but continue (link will be null)
            }

            // 2. Save to Database
            $interview = $this->interviewRepository->create($data);

            return $interview;
        });
    }
}
