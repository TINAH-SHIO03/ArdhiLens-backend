<?php
// ============================================================
// MODEL 3 — PlotDispute.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlotDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'plot_id',
        'dispute_type',
        'description',
        'court_case_number',
        'filing_date',
        'resolved_date',
        'status',
    ];

    protected $casts = [
        'filing_date'   => 'date',
        'resolved_date' => 'date',
    ];

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'Ongoing');
    }
}