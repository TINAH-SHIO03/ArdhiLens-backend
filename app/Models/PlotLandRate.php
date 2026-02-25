<?php
// ============================================================
// MODEL 5 — PlotLandRate.php (Tax Payments)
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlotLandRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'plot_id',
        'amount_paid',
        'payment_date',
        'period_from',
        'period_to',
        'receipt_number',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'period_from'  => 'date',
        'period_to'    => 'date',
        'amount_paid'  => 'decimal:2',
    ];

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }
}