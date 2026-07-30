<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nin',
        'role',
        'phone_number',
        'avatar_path',
        'is_active',
        'verified_at',
        'email_verified_at',
        'kyc_status',
        'kyc_submitted_at',
        'kyc_notes',
        'face_match_score',
        'face_match_passed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at'       => 'datetime',
        'kyc_submitted_at'  => 'datetime',
        'is_active'         => 'boolean',
        'face_match_passed' => 'boolean',
        'face_match_score'  => 'decimal:2',
        'password'          => 'hashed',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Get the NIDA record linked to this user
     */
    public function nida()
    {
        return $this->belongsTo(Nida::class, 'nin', 'nin');
    }

    /**
     * Get all verification logs for this user
     */
    public function verificationLogs()
    {
        return $this->hasMany(VerificationLog::class);
    }

    /**
     * Get all notifications for this user
     */
    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * Get unread notifications count
     */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->unread()->count();
    }

    /**
     * Get all documents uploaded by this user
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get all certificates for this user
     */
    public function certificates()
    {
        return $this->hasMany(VerificationCertificate::class);
    }

    /**
     * Get device tokens for push notifications
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    // ─── Helper Methods ──────────────────────────────────────────

    /**
     * Check if user has completed NIDA verification
     */
    public function isNidaVerified(): bool
    {
        return !is_null($this->verified_at) && !is_null($this->nin);
    }

    /**
     * Web admin panel only — admins cannot use the mobile API console.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && (bool) $this->is_active;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function linkedPlots()
    {
        return $this->hasMany(Plot::class, 'owner_nida', 'nin');
    }

    /**
     * Check if user is buyer
     */
    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    /**
     * Check if user is seller
     */
    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }
}
