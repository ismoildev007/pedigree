<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StoreInterviewRequest;
use App\Http\Resources\InterviewResource;
use App\Services\InterviewService;
use Illuminate\Http\JsonResponse;

class InterviewController extends Controller
{
    protected $interviewService;

    public function __construct(InterviewService $interviewService)
    {
        $this->interviewService = $interviewService;
    }

    public function store(StoreInterviewRequest $request): JsonResponse
    {
        $interview = $this->interviewService->scheduleInterview($request->validated());

        return response()->json([
            'message' => 'Interview scheduled successfully',
            'data' => new InterviewResource($interview),
        ], 201);
    }
}
