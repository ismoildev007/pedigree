<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Interview;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\SpeechToTextService;
use App\Services\AiAnalysisService;
use App\Services\OpenAI\OpenAiTranscriptionService;
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
    protected $openAiTranscription;

    public function __construct(
        GoogleDriveService $driveService,
        SpeechToTextService $speechService,
        AiAnalysisService $aiService,
        OpenAiTranscriptionService $openAiTranscription
    ) {
        parent::__construct();
        $this->driveService = $driveService;
        $this->speechService = $speechService;
        $this->aiService = $aiService;
        $this->openAiTranscription = $openAiTranscription;
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

                    if ($interview->platform === 'zoom') {
                        $this->info("Fetching Zoom recording for meeting ID: {$interview->zoom_meeting_id}");
                        $zoomService = new \App\Services\Zoom\ZoomMeetingService();
                        // Try to get audio first for transcription
                        $audioUrl = $zoomService->getAudioRecording($interview->zoom_meeting_id);

                        if ($audioUrl) {
                            $this->info("Found Zoom audio recording!");
                            $interview->recording_url = $audioUrl;
                            // We save this URL. Note: this might be a temporary download link with token.
                            // ideally we should download and store to S3/GCS. For now, we use it for transcription immediately.
                            $interview->save();
                        } else {
                            // Try video if audio not found, but getAudioRecording handles fallback to MP4.
                            $this->warn("No Zoom recording found yet.");
                            continue;
                        }
                    } else {
                        // Google Meet Logic (Existing)
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
                }

                // 2. Transcribe
                if (!$interview->transcript && $interview->recording_url) {
                    $this->info("Transcribing...");

                    if ($interview->platform === 'zoom') {
                        $this->info("Downloading Zoom audio for transcription...");

                        // Create temp directory if not exists
                        if (!file_exists(storage_path('app/temp'))) {
                            mkdir(storage_path('app/temp'), 0755, true);
                        }

                        $tempPath = storage_path('app/temp/zoom_audio_' . $interview->id . '.m4a');

                        // Download file
                        // Note: capturing context for stream
                        file_put_contents($tempPath, fopen($interview->recording_url, 'r'));

                        $this->info("Audio downloaded to $tempPath. Sending to OpenAI Whisper...");

                        $transcript = $this->openAiTranscription->transcribeAudio($tempPath);

                        // Cleanup
                        @unlink($tempPath);

                        if ($transcript) {
                            $this->info("Transcription successful!");
                            $interview->transcript = $transcript;
                            $interview->save();
                        } else {
                            $this->error("Transcription failed.");
                        }

                    } else {
                        // Google Meet Transcription (Mock for now or use GCS)
                        // Mock transcript
                        $transcript = "Interviewer: Tell me about yourself. Candidate: I am a Laravel developer with 5 years of experience...";
                        $interview->transcript = $transcript;
                        $interview->save();
                    }
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
