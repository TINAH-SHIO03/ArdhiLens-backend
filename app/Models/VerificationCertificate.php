<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCertificate extends Model
{
    use HasFactory;

    protected $table = 'verification_certificates';

    protected $fillable = [
        'user_id',
        'verification_log_id',
        'certificate_number',
        'certificate_type',
        'certificate_data',
        'pdf_path',
        'signature',
        'public_key',
        'pdf_content_hash',
        'pdf_signature',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'certificate_data' => 'array',
        'issued_at'        => 'datetime',
        'expires_at'       => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verificationLog()
    {
        return $this->belongsTo(VerificationLog::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // ─── Helper Methods ──────────────────────────────────────────

    public function isValid(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }
        return $this->expires_at->isFuture();
    }

    public function getSignatureData(): string
    {
        return json_encode([
            'certificate_number' => $this->certificate_number,
            'user_id'            => $this->user_id,
            'verification_log_id' => $this->verification_log_id,
            'issued_at'          => $this->issued_at->toIso8601String(),
        ]);
    }
}
