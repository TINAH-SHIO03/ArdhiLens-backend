<?php

// ============================================================
// MODEL 4 — PlotCaveat.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlotCaveat extends Model
{
    use HasFactory;

    protected $fillable = [
        'plot_id',
        'caveat_by',
        'reason',
        'registration_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'expiry_date'       => 'date',
    ];

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}

