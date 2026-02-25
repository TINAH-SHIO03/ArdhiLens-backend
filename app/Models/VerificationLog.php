<?php
// ============================================================
// MODEL — VerificationLog.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plot_id',
        'geolocation_passed',
        'nida_passed',
        'certificate_passed',
        'submitted_latitude',
        'submitted_longitude',
        'ai_verdict',
        'risk_score',
        'ai_reasons',
        'ai_recommendation',
        'ai_payload',
        'status',
    ];

    protected $casts = [
        'geolocation_passed'   => 'boolean',
        'nida_passed'          => 'boolean',
        'certificate_passed'   => 'boolean',
        'submitted_latitude'   => 'decimal:8',
        'submitted_longitude'  => 'decimal:8',
        'risk_score'           => 'integer',
        'ai_payload'           => 'array', // json auto encode/decode
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeByVerdict($query, string $verdict)
    {
        return $query->where('ai_verdict', $verdict);
    }

    // ─── Helper Methods ──────────────────────────────────────────

    /**
     * Check if all 3 verification steps passed
     */
    public function allStepsPassed(): bool
    {
        return $this->geolocation_passed 
            && $this->nida_passed 
            && $this->certificate_passed;
    }

    /**
     * Get verdict in human readable form with color indicator
     */
    public function getVerdictLabelAttribute(): array
    {
        return match($this->ai_verdict) {
            'SAFE'        => ['label' => 'Safe to Buy',      'color' => 'green'],
            'CAUTION'     => ['label' => 'Proceed with Caution', 'color' => 'orange'],
            'DO_NOT_BUY'  => ['label' => 'Do Not Buy',       'color' => 'red'],
            default       => ['label' => 'Incomplete',        'color' => 'gray'],
        };
    }
}