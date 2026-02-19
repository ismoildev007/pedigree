<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Interview;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\SpeechToTextService;
use App\Services\AiAnalysisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessInterviewRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'interviews:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch recordings, transcribe, and analyze interviews';

    protected $driveService;
    protected $speechService;
    protected $aiService;

    public function __construct(
        GoogleDriveService $driveService,
        SpeechToTextService $speechService,
        AiAnalysisService $aiService
    ) {
        parent::__construct();
        $this->driveService = $driveService;
        $this->speechService = $speechService;
        $this->aiService = $aiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting interview processing...');

        // Find interviews that are 'scheduled' (or 'completed' but missing analysis)
        // and end_time has passed.
        $interviews = Interview::where('end_time', '<', now())
            ->whereNull('ai_analysis')
            ->get();

        foreach ($interviews as $interview) {
            $this->info("Processing Interview ID: {$interview->id}");

            try {
                // 1. Find Recording
                if (!$interview->recording_url) {
                    $this->info("Searching for recording...");

                    // Extract meeting code from link (e.g., https://meet.google.com/abc-defg-hij -> abc-defg-hij)
                    $code = Str::afterLast($interview->google_meet_link, '/');

                    // Allow for possible query params cleanup
                    if (str_contains($code, '?')) {
                        $code = Str::before($code, '?');
                    }

                    $this->info("Looking for recording for meeting code: $code");

                    $file = $this->driveService->findRecordingByMeetingCode($code, $interview->start_time);

                    if ($file) {
                        $this->info("Found recording: " . $file->name);
                        $interview->recording_url = $file->webViewLink;
                        $interview->save();
                    } else {
                        $this->warn("No recording found yet for meeting $code.");
                        continue;
                    }
                }

                // 2. Transcribe
                if (!$interview->transcript) {
                    $this->info("Transcribing...");
                    // In real app, we'd download the file from Drive, upload to GCS, then transcribe.
                    // $gcsUri = $this->uploadToGcs($interview->recording_url);
                    // $transcript = $this->speechService->transcribeAudio($gcsUri);

                    // Mock transcript
                    $transcript = "Interviewer: Tell me about yourself. Candidate: I am a Laravel developer with 5 years of experience...";
                    $interview->transcript = $transcript;
                    $interview->save();
                }

                // 3. AI Analysis
                if ($interview->transcript && !$interview->ai_analysis) {
                    $this->info("Running AI Analysis...");
                    $analysis = $this->aiService->analyzeInterview($interview->transcript);
                    $interview->ai_analysis = $analysis;
                    $interview->status = 'completed';
                    $interview->save();
                }

            } catch (\Exception $e) {
                Log::error("Failed to process interview {$interview->id}: " . $e->getMessage());
                $this->error("Error: " . $e->getMessage());
            }
        }

        $this->info('Processing complete.');
    }
}
