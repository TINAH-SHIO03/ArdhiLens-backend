<?php
// ============================================================
// MODEL 2 — PlotEncumbrance.php (Bank Loans)
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlotEncumbrance extends Model
{
    use HasFactory;

    protected $fillable = [
        'plot_id',
        'bank_name',
        'amount',
        'registration_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'expiry_date'       => 'date',
        'amount'            => 'decimal:2',
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

