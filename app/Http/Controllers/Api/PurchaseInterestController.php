<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plot;
use App\Models\PurchaseInterest;
use App\Models\User;
use App\Models\VerificationLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseInterestController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isBuyer()) {
            return $this->error('Buyer access only.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'plot_reference' => ['required', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:1000'],
            'verification_log_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $plot = Plot::query()
            ->whereRaw('LOWER(plot_reference) = ?', [strtolower($request->input('plot_reference'))])
            ->first();

        if (! $plot) {
            return $this->error('Plot not found.', [], 404);
        }

        $seller = null;
        if ($plot->owner_nida) {
            $seller = User::query()
                ->where('nin', $plot->owner_nida)
                ->where('role', 'seller')
                ->first();
        }

        $verificationLogId = $request->input('verification_log_id');
        if ($verificationLogId) {
            $ownsLog = VerificationLog::query()
                ->where('id', (int) $verificationLogId)
                ->where('user_id', $user->id)
                ->exists();
            if (! $ownsLog) {
                $verificationLogId = null;
            }
        }

        $interest = PurchaseInterest::query()->updateOrCreate(
            [
                'buyer_id' => $user->id,
                'plot_id' => $plot->id,
            ],
            [
                'seller_id' => $seller?->id,
                'verification_log_id' => $verificationLogId,
                'plot_reference' => $plot->plot_reference,
                'buyer_message' => $request->input('message'),
                'status' => PurchaseInterest::STATUS_PENDING,
                'seller_reply' => null,
                'responded_at' => null,
            ]
        );

        if ($seller) {
            $this->notificationService->notifySystem(
                $seller,
                'Buyer interested in your land',
                "{$user->name} wants to buy {$plot->plot_reference}. Open seller dashboard to respond.",
                [
                    'event' => 'purchase_interest',
                    'interest_id' => $interest->id,
                    'plot_reference' => $plot->plot_reference,
                    'buyer_name' => $user->name,
                    'role' => 'seller',
                ]
            );
        }

        return $this->success('Purchase interest sent.', [
            'interest' => $this->payload($interest->fresh(['buyer', 'seller', 'plot'])),
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isBuyer()) {
            return $this->error('Buyer access only.', [], 403);
        }

        $items = PurchaseInterest::query()
            ->with(['seller', 'plot'])
            ->where('buyer_id', $user->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (PurchaseInterest $interest) => $this->payload($interest));

        return $this->success('Buyer interests loaded.', [
            'interests' => $items,
        ]);
    }

    public function forSeller(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSeller()) {
            return $this->error('Seller access only.', [], 403);
        }

        $plotIds = $user->nin
            ? Plot::query()->where('owner_nida', $user->nin)->pluck('id')
            : collect();

        $items = PurchaseInterest::query()
            ->with(['buyer', 'plot'])
            ->where(function ($q) use ($user, $plotIds) {
                $q->where('seller_id', $user->id);
                if ($plotIds->isNotEmpty()) {
                    $q->orWhere(function ($owned) use ($plotIds) {
                        $owned->whereNull('seller_id')->whereIn('plot_id', $plotIds);
                    });
                }
            })
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get()
            ->map(function (PurchaseInterest $interest) use ($user, $plotIds) {
                if (
                    ! $interest->seller_id
                    && $plotIds->contains((int) $interest->plot_id)
                ) {
                    $interest->update(['seller_id' => $user->id]);
                }

                return $this->payload($interest->fresh(['buyer', 'seller', 'plot']));
            });

        return $this->success('Seller buyer requests loaded.', [
            'interests' => $items,
            'pending_interest_count' => $items->where('status', PurchaseInterest::STATUS_PENDING)->count(),
        ]);
    }

    public function respond(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSeller()) {
            return $this->error('Seller access only.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:accepted,declined,contacted'],
            'reply' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $interest = PurchaseInterest::query()->with(['buyer', 'plot'])->find($id);
        if (! $interest) {
            return $this->error('Interest not found.', [], 404);
        }

        $ownsInterest = (int) $interest->seller_id === (int) $user->id
            || ($user->nin && $interest->plot && $interest->plot->owner_nida === $user->nin);

        if (! $ownsInterest) {
            return $this->error('You cannot respond to this request.', [], 403);
        }

        $interest->update([
            'seller_id' => $user->id,
            'status' => $request->input('status'),
            'seller_reply' => $request->input('reply'),
            'responded_at' => now(),
        ]);

        try {
            if ($interest->buyer) {
                $this->notificationService->notifySystem(
                    $interest->buyer,
                    'Seller responded to your interest',
                    "Seller replied on {$interest->plot_reference}: {$interest->status}.",
                    [
                        'event' => 'purchase_interest_response',
                        'interest_id' => $interest->id,
                        'plot_reference' => $interest->plot_reference,
                        'status' => $interest->status,
                        'role' => 'buyer',
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to notify buyer on interest response', ['error' => $e->getMessage()]);
        }

        return $this->success('Response saved.', [
            'interest' => $this->payload($interest->fresh(['buyer', 'seller', 'plot'])),
        ]);
    }

    private function payload(PurchaseInterest $interest): array
    {
        $viewerRole = request()->user()?->isSeller() ? 'seller' : 'buyer';

        return [
            'id' => $interest->id,
            'status' => $interest->status,
            'status_label' => $this->statusLabel($interest->status, $viewerRole),
            'action_required' => $viewerRole === 'seller'
                && $interest->status === PurchaseInterest::STATUS_PENDING,
            'waiting_for_seller' => $viewerRole === 'buyer'
                && $interest->status === PurchaseInterest::STATUS_PENDING,
            'plot_reference' => $interest->plot_reference,
            'plot_id' => $interest->plot_id,
            'buyer_message' => $interest->buyer_message,
            'seller_reply' => $interest->seller_reply,
            'verification_log_id' => $interest->verification_log_id,
            'responded_at' => $interest->responded_at?->toIso8601String(),
            'created_at' => $interest->created_at?->toIso8601String(),
            'updated_at' => $interest->updated_at?->toIso8601String(),
            'buyer' => $interest->buyer ? [
                'id' => $interest->buyer->id,
                'name' => $interest->buyer->name,
                'email' => $interest->buyer->email,
                'phone_number' => $interest->buyer->phone_number,
            ] : null,
            'seller' => $interest->seller ? [
                'id' => $interest->seller->id,
                'name' => $interest->seller->name,
                'email' => $interest->seller->email,
                'phone_number' => $interest->seller->phone_number,
            ] : null,
            'location' => $interest->plot
                ? trim("{$interest->plot->ward}, {$interest->plot->district}, {$interest->plot->region}", ' ,')
                : null,
        ];
    }

    private function statusLabel(string $status, string $role): string
    {
        if ($role === 'seller') {
            return match ($status) {
                PurchaseInterest::STATUS_PENDING => 'Action required — respond to buyer',
                PurchaseInterest::STATUS_ACCEPTED => 'You accepted this buyer',
                PurchaseInterest::STATUS_DECLINED => 'You declined this buyer',
                PurchaseInterest::STATUS_CONTACTED => 'You contacted this buyer',
                default => ucfirst($status),
            };
        }

        return match ($status) {
            PurchaseInterest::STATUS_PENDING => 'Sent — waiting for seller to respond',
            PurchaseInterest::STATUS_ACCEPTED => 'Seller accepted your interest',
            PurchaseInterest::STATUS_DECLINED => 'Seller declined your interest',
            PurchaseInterest::STATUS_CONTACTED => 'Seller contacted you',
            default => ucfirst($status),
        };
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
