<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationAttemptLog extends Model
{
    use HasFactory;

    protected $table = 'verification_attempt_logs';

    protected $fillable = [
        'user_id',
        'session_token',
        'challenge_id',
        'attempt_type',
        'passed',
        'correct_count',
        'total_questions',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'passed' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('attempt_type', $type);
    }

    // ─── Helper Methods ──────────────────────────────────────────

    public static function logAttempt(
        ?int $userId,
        ?string $sessionToken,
        ?string $challengeId,
        string $attemptType,
        bool $passed,
        ?int $correctCount = null,
        ?int $totalQuestions = null
    ): self {
        return static::create([
            'user_id'        => $userId,
            'session_token'  => $sessionToken,
            'challenge_id'   => $challengeId,
            'attempt_type'   => $attemptType,
            'passed'         => $passed,
            'correct_count'  => $correctCount,
            'total_questions' => $totalQuestions,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
        ]);
    }
}
