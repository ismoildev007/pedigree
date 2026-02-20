<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $fillable = [
        'candidate_id',
        'google_meet_link',
        'google_event_id',
        'start_time',
        'end_time',
        'recording_url',
        'transcript',
        'ai_analysis',
        'status',
        'platform',
        'zoom_meeting_link',
        'zoom_meeting_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'ai_analysis' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
