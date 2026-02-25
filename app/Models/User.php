<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nin',
        'role',
        'phone_number',
        'is_active',
        'verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at'       => 'datetime',
        'is_active'         => 'boolean',
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

    // ─── Helper Methods ──────────────────────────────────────────

    /**
     * Check if user has completed NIDA verification
     */
    public function isNidaVerified(): bool
    {
        return !is_null($this->verified_at) && !is_null($this->nin);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
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
