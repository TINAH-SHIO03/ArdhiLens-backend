<?php

namespace App\Services\LandVerification;

use App\Models\Plot;
use Illuminate\Support\Carbon;

class RiskScoringService
{
    private const VERDICT_SAFE = 'SAFE';

    private const VERDICT_CAUTION = 'CAUTION';

    private const VERDICT_DO_NOT_BUY = 'DO_NOT_BUY';

    public function score(Plot $plot): array
    {
        $plot->loadMissing([
            'encumbrances',
            'disputes',
            'caveats',
            'landRates',
            'ownershipHistories',
        ]);

        $score = 0;
        $factors = [];
        $interactionPenalties = [];

        $activeEncumbranceCount = $plot->encumbrances->where('status', 'Active')->count();
        $defaultedEncumbranceCount = $plot->encumbrances->where('status', 'Defaulted')->count();
        $ongoingDisputeCount = $plot->disputes->where('status', 'Ongoing')->count();
        $activeCaveatCount = $plot->caveats->where('status', 'Active')->count();
        $ownershipChangeCount = $plot->ownershipHistories->count();

        $loanPoints = 0;
        if ($activeEncumbranceCount > 0) {
            $loanPoints += 15;
        }
        if ($defaultedEncumbranceCount > 0) {
            $loanPoints += 10;
        }
        $loanPoints = min(25, $loanPoints);
        if ($loanPoints > 0) {
            $this->addFactor(
                $factors,
                $score,
                'loan_risk',
                $loanPoints,
                (string) __('api.land_verification.risk_reasons.loan_risk', [
                    'active' => $activeEncumbranceCount,
                    'defaulted' => $defaultedEncumbranceCount,
                ])
            );
        }

        $disputePoints = match (true) {
            $ongoingDisputeCount >= 3 => 40,
            $ongoingDisputeCount === 2 => 32,
            $ongoingDisputeCount === 1 => 25,
            default => 0,
        };
        if ($disputePoints > 0) {
            $this->addFactor(
                $factors,
                $score,
                'ongoing_disputes',
                $disputePoints,
                (string) __('api.land_verification.risk_reasons.ongoing_disputes', [
                    'count' => $ongoingDisputeCount,
                ])
            );
        }

        $caveatPoints = match (true) {
            $activeCaveatCount >= 2 => 28,
            $activeCaveatCount === 1 => 18,
            default => 0,
        };
        if ($caveatPoints > 0) {
            $this->addFactor(
                $factors,
                $score,
                'active_caveats',
                $caveatPoints,
                (string) __('api.land_verification.risk_reasons.active_caveats', [
                    'count' => $activeCaveatCount,
                ])
            );
        }

        if ((bool) $plot->double_allocation_flag) {
            $this->addFactor(
                $factors,
                $score,
                'double_allocation',
                35,
                (string) __('api.land_verification.risk_reasons.double_allocation')
            );
        }

        $ownershipPoints = match (true) {
            $ownershipChangeCount >= 6 => 22,
            $ownershipChangeCount >= 4 => 15,
            $ownershipChangeCount >= 2 => 8,
            default => 0,
        };
        if ($ownershipPoints > 0) {
            $this->addFactor(
                $factors,
                $score,
                'ownership_changes',
                $ownershipPoints,
                (string) __('api.land_verification.risk_reasons.ownership_changes', [
                    'count' => $ownershipChangeCount,
                ])
            );
        }

        $landRateAssessment = $this->assessLandRates($plot);
        if ($landRateAssessment['points'] > 0) {
            $this->addFactor(
                $factors,
                $score,
                'land_rates',
                $landRateAssessment['points'],
                $landRateAssessment['detail']
            );
        }

        $statusPoints = match ($plot->status) {
            'Under Review' => 15,
            'Disputed' => 25,
            'Revoked' => 40,
            default => 0,
        };
        if ($statusPoints > 0) {
            $this->addFactor(
                $factors,
                $score,
                'plot_status',
                $statusPoints,
                (string) __('api.land_verification.risk_reasons.plot_status', [
                    'status' => $plot->status,
                ])
            );
        }

        $certificateAssessment = $this->assessCertificateRisk($plot);
        foreach ($certificateAssessment['factors'] as $factor) {
            $this->addFactor(
                $factors,
                $score,
                $factor['name'],
                $factor['points'],
                $factor['detail']
            );
        }

        if ((bool) $plot->double_allocation_flag && ($ongoingDisputeCount > 0 || $activeCaveatCount > 0)) {
            $this->addInteractionPenalty(
                $interactionPenalties,
                $score,
                'double_allocation_with_legal_restrictions',
                10,
                (string) __('api.land_verification.risk_reasons.double_allocation_with_legal_restrictions')
            );
        }

        if ($plot->status === 'Revoked' && $certificateAssessment['expired']) {
            $this->addInteractionPenalty(
                $interactionPenalties,
                $score,
                'revoked_with_expired_certificate',
                10,
                (string) __('api.land_verification.risk_reasons.revoked_with_expired_certificate')
            );
        }

        if (($activeEncumbranceCount > 0 || $defaultedEncumbranceCount > 0) && $landRateAssessment['is_over_24_months']) {
            $this->addInteractionPenalty(
                $interactionPenalties,
                $score,
                'debt_and_long_overdue_rates',
                5,
                (string) __('api.land_verification.risk_reasons.debt_and_long_overdue_rates')
            );
        }

        $uncertaintyPenalty = $this->calculateUncertaintyPenalty($plot);
        if ($uncertaintyPenalty > 0) {
            $score += $uncertaintyPenalty;
        }

        $score = max(0, min(100, $score));

        if ((bool) $plot->double_allocation_flag || $plot->status === 'Revoked') {
            $score = max(70, $score);
        }

        $verdict = $this->mapVerdict($score);
        $reasons = $this->buildReasons($factors, $interactionPenalties, $uncertaintyPenalty);

        return [
            'score' => $score,
            'verdict' => $verdict,
            'reasons' => $reasons,
            'factors' => $factors,
            'interaction_penalties' => $interactionPenalties,
            'uncertainty_penalty' => $uncertaintyPenalty,
        ];
    }

    private function assessLandRates(Plot $plot): array
    {
        $latestRate = $plot->landRates
            ->filter(fn ($rate) => $rate->period_to !== null)
            ->sortByDesc(fn ($rate) => $rate->period_to->getTimestamp())
            ->first();

        if (! $latestRate) {
            return [
                'points' => 20,
                'detail' => (string) __('api.land_verification.risk_reasons.no_land_rate_record'),
                'is_over_24_months' => true,
            ];
        }

        $now = Carbon::now();
        $daysFromCoverageEnd = $latestRate->period_to->diffInDays($now, false);

        if ($daysFromCoverageEnd <= 0) {
            return [
                'points' => 0,
                'detail' => (string) __('api.land_verification.risk_reasons.land_rates_current'),
                'is_over_24_months' => false,
            ];
        }

        if ($daysFromCoverageEnd <= 365) {
            return [
                'points' => 6,
                'detail' => (string) __('api.land_verification.risk_reasons.land_rates_overdue_12'),
                'is_over_24_months' => false,
            ];
        }

        if ($daysFromCoverageEnd <= 730) {
            return [
                'points' => 12,
                'detail' => (string) __('api.land_verification.risk_reasons.land_rates_overdue_24'),
                'is_over_24_months' => false,
            ];
        }

        return [
            'points' => 20,
            'detail' => (string) __('api.land_verification.risk_reasons.land_rates_overdue_24_plus'),
            'is_over_24_months' => true,
        ];
    }

    private function assessCertificateRisk(Plot $plot): array
    {
        $factors = [];
        $expired = false;

        if ($plot->certificate_type === 'Letter of Offer') {
            $factors[] = [
                'name' => 'certificate_type_offer_letter',
                'points' => 8,
                'detail' => (string) __('api.land_verification.risk_reasons.certificate_offer_letter'),
            ];
        }

        if ($plot->expiry_date !== null) {
            $now = Carbon::now();

            if ($plot->expiry_date->isPast()) {
                $factors[] = [
                    'name' => 'certificate_expired',
                    'points' => 20,
                    'detail' => (string) __('api.land_verification.risk_reasons.certificate_expired', [
                        'date' => $plot->expiry_date->toDateString(),
                    ]),
                ];
                $expired = true;
            } elseif ($plot->expiry_date->lessThanOrEqualTo($now->copy()->addDays(90))) {
                $factors[] = [
                    'name' => 'certificate_expiring_soon',
                    'points' => 6,
                    'detail' => (string) __('api.land_verification.risk_reasons.certificate_expiring_soon', [
                        'date' => $plot->expiry_date->toDateString(),
                    ]),
                ];
            }
        }

        return [
            'factors' => $factors,
            'expired' => $expired,
        ];
    }

    private function calculateUncertaintyPenalty(Plot $plot): int
    {
        $hasMissingCriticalData = $plot->status === null
            || $plot->certificate_type === null
            || $plot->issue_date === null
            || $plot->disputes->contains(fn ($dispute) => blank($dispute->status))
            || $plot->caveats->contains(fn ($caveat) => blank($caveat->status));

        if (! $hasMissingCriticalData) {
            return 0;
        }

        return (int) config('land_verification.risk.uncertainty_penalty', 8);
    }

    private function buildReasons(array $factors, array $interactionPenalties, int $uncertaintyPenalty): array
    {
        $reasons = [];

        foreach ($factors as $factor) {
            $reasons[] = $factor['detail'];
        }

        foreach ($interactionPenalties as $penalty) {
            $reasons[] = $penalty['detail'];
        }

        if ($uncertaintyPenalty > 0) {
            $reasons[] = (string) __('api.land_verification.risk_reasons.uncertainty_penalty');
        }

        if ($reasons === []) {
            $reasons[] = (string) __('api.land_verification.risk_reasons.no_major_red_flags');
        }

        return $reasons;
    }

    private function mapVerdict(int $score): string
    {
        $safeMax = (int) config('land_verification.risk.thresholds.safe_max', 29);
        $cautionMax = (int) config('land_verification.risk.thresholds.caution_max', 69);

        if ($score <= $safeMax) {
            return self::VERDICT_SAFE;
        }

        if ($score <= $cautionMax) {
            return self::VERDICT_CAUTION;
        }

        return self::VERDICT_DO_NOT_BUY;
    }

    private function addFactor(array &$factors, int &$score, string $name, int $points, string $detail): void
    {
        if ($points <= 0) {
            return;
        }

        $factors[] = [
            'name' => $name,
            'points' => $points,
            'detail' => $detail,
        ];
        $score += $points;
    }

    private function addInteractionPenalty(
        array &$interactionPenalties,
        int &$score,
        string $name,
        int $points,
        string $detail
    ): void {
        if ($points <= 0) {
            return;
        }

        $interactionPenalties[] = [
            'name' => $name,
            'points' => $points,
            'detail' => $detail,
        ];
        $score += $points;
    }
}

