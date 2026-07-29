<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plot;
use App\Models\PurchaseInterest;
use App\Models\User;
use App\Models\VerificationLog;
use App\Services\AuditLogService;
use App\Services\Identity\FaceMatchService;
use App\Services\Identity\NidaProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SellerController extends Controller
{
    public function __construct(
        private readonly NidaProviderInterface $nidaProvider,
        private readonly FaceMatchService $faceMatchService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Lightweight first paint for seller home.
     * Heavy sections (buyer interests, recent checks, documents) load via separate APIs.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSeller()) {
            return $this->error('Seller access only.', [], 403);
        }

        $plots = $user->nin
            ? Plot::query()
                ->where('owner_nida', $user->nin)
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get()
            : collect();

        $plotIds = $plots->pluck('id');

        $plotLinkStatus = 'no_nin';
        $plotLinkMessage = 'Enter the NIN registered as owner of your land to link plots.';
        if ($user->nin) {
            if ($plots->isEmpty()) {
                $plotLinkStatus = 'no_plots';
                $plotLinkMessage = 'No plots linked yet. Ask admin to set owner_nida to your NIN.';
            } else {
                $plotLinkStatus = 'linked';
                $plotLinkMessage = $plots->count().' plot(s) linked to your NIN.';
            }
        }

        $pendingInterestCount = PurchaseInterest::query()
            ->where('status', PurchaseInterest::STATUS_PENDING)
            ->where(function ($q) use ($user, $plotIds) {
                $q->where('seller_id', $user->id);
                if ($plotIds->isNotEmpty()) {
                    $q->orWhere(function ($owned) use ($plotIds) {
                        $owned->whereNull('seller_id')->whereIn('plot_id', $plotIds);
                    });
                }
            })
            ->count();

        return $this->success('Seller summary loaded.', [
            'kyc_status' => $user->kyc_status ?? 'none',
            'nin' => $user->nin,
            'plot_link_status' => $plotLinkStatus,
            'plot_link_message' => $plotLinkMessage,
            'linked_plot_count' => $plots->count(),
            'plots' => $plots->map(fn (Plot $plot) => [
                'id' => $plot->id,
                'plot_reference' => $plot->plot_reference,
                'region' => $plot->region,
                'district' => $plot->district,
                'ward' => $plot->ward,
                'status' => $plot->status,
                'size_hectares' => $plot->size_hectares,
                'has_boundary' => ! empty($plot->boundary_geojson),
            ]),
            'pending_interest_count' => $pendingInterestCount,
            'alerts_unread' => $user->unreadNotificationsCount(),
        ]);
    }

    public function recentVerifications(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSeller()) {
            return $this->error('Seller access only.', [], 403);
        }

        if (! $user->nin) {
            return $this->success('Recent buyer verifications loaded.', [
                'recent_buyer_verifications' => [],
            ]);
        }

        $plotIds = Plot::query()
            ->where('owner_nida', $user->nin)
            ->pluck('id');

        $recentVerifications = $plotIds->isEmpty()
            ? collect()
            : VerificationLog::query()
                ->with(['user', 'plot'])
                ->whereIn('plot_id', $plotIds)
                ->where('status', 'Completed')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get()
                ->map(fn (VerificationLog $log) => [
                    'id' => $log->id,
                    'plot_reference' => $log->plot?->plot_reference,
                    'verdict' => $log->ai_verdict,
                    'risk_score' => $log->risk_score,
                    'buyer_name' => $log->user?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]);

        return $this->success('Recent buyer verifications loaded.', [
            'recent_buyer_verifications' => $recentVerifications,
        ]);
    }

    public function submitKyc(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSeller()) {
            return $this->error('Seller access only.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'nin' => ['required', 'string', 'size:20'],
            'selfie_base64' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $nin = trim((string) $request->input('nin'));
        $identity = $this->nidaProvider->lookup($nin);
        if (! $identity) {
            return $this->error('NIDA record not found for this NIN.', [], 404);
        }

        $ninTaken = User::query()
            ->where('role', 'seller')
            ->where('nin', $nin)
            ->where('id', '!=', $user->id)
            ->exists();
        if ($ninTaken) {
            return $this->error(
                'This NIN is already linked to another seller account. Use your own NIN.',
                [],
                409
            );
        }

        $face = $this->faceMatchService->compare(
            $request->input('selfie_base64'),
            $identity['passport_image_base64'] ?? null,
        );

        $status = 'pending_review';
        if (($face['passed'] ?? false) && ($identity['status'] ?? '') === 'Active') {
            $status = 'verified';
        } elseif (empty($request->input('selfie_base64'))) {
            $status = 'pending_review';
        } elseif (! ($face['passed'] ?? false)) {
            $status = 'needs_manual_review';
        }

        $user->update([
            'nin' => $nin,
            'kyc_status' => $status,
            'kyc_submitted_at' => now(),
            'kyc_notes' => $face['notes'] ?? null,
            'face_match_score' => $face['score'] ?? null,
            'face_match_passed' => $face['passed'] ?? null,
            'verified_at' => $status === 'verified' ? now() : $user->verified_at,
        ]);

        $this->auditLogService->log('seller.kyc_submit', $user, 'user', $user->id, [
            'kyc_status' => $status,
            'nida_source' => $identity['source'] ?? 'local',
            'face' => $face,
        ], $request);

        $this->linkPendingInterestsToSeller($user);

        $linkedPlots = Plot::query()->where('owner_nida', $nin)->count();

        return $this->success('Seller KYC submitted.', [
            'user' => [
                'id' => $user->id,
                'nin' => $user->nin,
                'kyc_status' => $user->kyc_status,
                'face_match_score' => $user->face_match_score,
                'face_match_passed' => $user->face_match_passed,
                'identity_name' => $identity['full_name'] ?? null,
                'identity_source' => $identity['source'] ?? 'local',
            ],
            'linked_plot_count' => $linkedPlots,
            'plot_link_message' => $linkedPlots > 0
                ? "{$linkedPlots} plot(s) now linked to your NIN."
                : 'KYC saved. No plots found for this NIN yet — contact admin.',
        ]);
    }

    private function linkPendingInterestsToSeller(User $user): void
    {
        if (! $user->nin) {
            return;
        }

        $plotIds = Plot::query()->where('owner_nida', $user->nin)->pluck('id');
        if ($plotIds->isEmpty()) {
            return;
        }

        // Only claim unassigned interests — never steal another seller's requests.
        PurchaseInterest::query()
            ->whereIn('plot_id', $plotIds)
            ->whereNull('seller_id')
            ->update(['seller_id' => $user->id]);
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => (object) [],
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    private function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => (object) [],
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
