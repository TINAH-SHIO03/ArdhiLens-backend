<?php
// ============================================================
// MODEL 1 — Plot.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plot_reference',
        'owner_nida',
        'region',
        'district',
        'ward',
        'village_mtaa',
        'street',
        'gps_latitude',
        'gps_longitude',
        'size_hectares',
        'land_use',
        'tenure_type',
        'certificate_type',
        'issue_date',
        'expiry_date',
        'zoning_compliant',
        'development_conditions_met',
        'double_allocation_flag',
        'status',
    ];

    protected $casts = [
        'issue_date'                  => 'date',
        'expiry_date'                 => 'date',
        'zoning_compliant'            => 'boolean',
        'development_conditions_met'  => 'boolean',
        'double_allocation_flag'      => 'boolean',
        'gps_latitude'                => 'decimal:8',
        'gps_longitude'               => 'decimal:8',
        'size_hectares'               => 'decimal:4',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function owner()
    {
        return $this->belongsTo(Nida::class, 'owner_nida', 'nin');
    }

    public function encumbrances()
    {
        return $this->hasMany(PlotEncumbrance::class);
    }

    public function disputes()
    {
        return $this->hasMany(PlotDispute::class);
    }

    public function caveats()
    {
        return $this->hasMany(PlotCaveat::class);
    }

    public function landRates()
    {
        return $this->hasMany(PlotLandRate::class);
    }

    public function ownershipHistories()
    {
        return $this->hasMany(PlotOwnershipHistory::class);
    }

    public function verificationLogs()
    {
        return $this->hasMany(VerificationLog::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeByReference($query, string $reference)
    {
        return $query->where('plot_reference', $reference);
    }

    // ─── Helper Methods ──────────────────────────────────────────

    /**
     * Check if plot has any active bank loans
     */
    public function hasActiveLoan(): bool
    {
        return $this->encumbrances()->where('status', 'Active')->exists();
    }

    /**
     * Check if plot has any ongoing disputes
     */
    public function hasOngoingDispute(): bool
    {
        return $this->disputes()->where('status', 'Ongoing')->exists();
    }

    /**
     * Check if plot has any active caveats
     */
    public function hasActiveCaveat(): bool
    {
        return $this->caveats()->where('status', 'Active')->exists();
    }

    /**
     * Count how many times ownership has changed
     */
    public function ownershipChangeCount(): int
    {
        return $this->ownershipHistories()->count();
    }

    /**
     * Check if land rates are up to date
     */
    public function hasRecentLandRatePayment(): bool
    {
        return $this->landRates()
                    ->where('period_to', '>=', now()->subYear())
                    ->exists();
    }

    /**
     * Collect all data needed for AI recommendation
     */
    public function getAiPayload(): array
    {
        return [
            'plot_reference'          => $this->plot_reference,
            'region'                  => $this->region,
            'district'                => $this->district,
            'land_use'                => $this->land_use,
            'tenure_type'             => $this->tenure_type,
            'certificate_type'        => $this->certificate_type,
            'size_hectares'           => $this->size_hectares,
            'status'                  => $this->status,
            'zoning_compliant'        => $this->zoning_compliant,
            'double_allocation_flag'  => $this->double_allocation_flag,
            'has_active_loan'         => $this->hasActiveLoan(),
            'has_ongoing_dispute'     => $this->hasOngoingDispute(),
            'has_active_caveat'       => $this->hasActiveCaveat(),
            'ownership_changes'       => $this->ownershipChangeCount(),
            'land_rates_up_to_date'   => $this->hasRecentLandRatePayment(),
            'encumbrances'            => $this->encumbrances()->get(['bank_name', 'amount', 'status']),
            'disputes'                => $this->disputes()->get(['dispute_type', 'description', 'status']),
            'caveats'                 => $this->caveats()->get(['caveat_by', 'reason', 'status']),
            'ownership_history'       => $this->ownershipHistories()->get(['from_nida', 'to_nida', 'transfer_date', 'transfer_reason']),
        ];
    }
}


