<?php

namespace App\Repositories;

use App\Models\Interview;
use App\Models\Candidate;

class InterviewRepository
{
    public function create(array $data)
    {
        // Ensure candidate exists or create new one
        $candidate = Candidate::firstOrCreate(
            ['email' => $data['candidate_email']],
            [
                'name' => $data['candidate_name'],
                'phone' => $data['candidate_phone'] ?? null,
            ]
        );

        return Interview::create([
            'candidate_id' => $candidate->id,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => 'scheduled',
            'platform' => $data['platform'] ?? 'google_meet',
            'google_meet_link' => $data['google_meet_link'] ?? null,
            'google_event_id' => $data['google_event_id'] ?? null,
            'zoom_meeting_link' => $data['zoom_meeting_link'] ?? null,
            'zoom_meeting_id' => $data['zoom_meeting_id'] ?? null,
        ]);
    }

    public function find($id)
    {
        return Interview::with('candidate')->findOrFail($id);
    }

    public function update($id, array $data)
    {
        $interview = $this->find($id);
        $interview->update($data);
        return $interview;
    }
}
