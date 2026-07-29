<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInterest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CONTACTED = 'contacted';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'plot_id',
        'verification_log_id',
        'plot_reference',
        'buyer_message',
        'seller_reply',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    public function verificationLog()
    {
        return $this->belongsTo(VerificationLog::class);
    }
}
