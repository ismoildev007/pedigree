<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'language_code',
        'phone_number',
        'is_admin',
        'password',
        'referral_code',
        'referred_by',
        'is_blocked',
    ];
    protected $casts = [
        'is_blocked' => 'boolean',
        'is_admin' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Kanal a'zoliklari
     */
    public function channelMemberships(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    /**
     * Taklif qilgan foydalanuvchilar
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Kim tomonidan taklif qilingan
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Foydalanuvchi konkursda ovoz berganmi?
     */
    public function hasVotedInContest($contestId): bool
    {
        return $this->votes()->where('contest_id', $contestId)->exists();
    }

    /**
     * Foydalanuvchi barcha kanallarga a'zomi?
     */
    public function hasJoinedAllChannels($contestId): bool
    {
        $contest = ContestSetting::find($contestId);
        if (!$contest) {
            return false;
        }

        $requiredChannelIds = $contest->channels()->pluck('channels.id');
        $joinedChannelIds = $this->channelMemberships()->pluck('channel_id');

        return $requiredChannelIds->diff($joinedChannelIds)->isEmpty();
    }

    /**
     * Foydalanuvchining jami ovozlari
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    /**
     * Foydalanuvchining jami referrallari
     */
    public function getTotalReferralsAttribute(): int
    {
        return $this->referrals()->count();
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'channel_members', 'user_id', 'channel_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
