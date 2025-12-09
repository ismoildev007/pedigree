<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = [
        'user_id',
        'student_id',
        'contest_id',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Foydalanuvchi bilan bog'lanish
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Student bilan bog'lanish
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Konkurs bilan bog'lanish
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(ContestSetting::class, 'contest_id');
    }

    /**
     * Unique constraint: Bir user bir konkursda faqat bir marta ovoz berishi mumkin
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($vote) {
            // IP addressni saqlash
            $vote->ip_address = request()->ip();
        });
    }
}
