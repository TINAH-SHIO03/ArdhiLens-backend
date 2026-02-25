<?php
// ============================================================
// MODEL 6 — PlotOwnershipHistory.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlotOwnershipHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'plot_id',
        'from_nida',
        'to_nida',
        'transfer_date',
        'transfer_reason',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    public function previousOwner()
    {
        return $this->belongsTo(Nida::class, 'from_nida', 'nin');
    }

    public function newOwner()
    {
        return $this->belongsTo(Nida::class, 'to_nida', 'nin');
    }
}