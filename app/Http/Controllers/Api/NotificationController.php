<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        public readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $result = $this->notificationService->getNotifications($user, $page, $perPage);

        return $this->success(__('api.notifications.list_fetched'), [
            'notifications' => $result['notifications']->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->toIso8601String(),
                ];
            }),
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        return $this->success(__('api.notifications.unread_count_fetched'), [
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        $updated = $this->notificationService->markAsRead($id, $user);

        if (! $updated) {
            return $this->error(__('api.notifications.not_found'), [], 404);
        }

        return $this->success(__('api.notifications.marked_as_read'), [
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        $count = $this->notificationService->markAllAsRead($user);

        return $this->success(__('api.notifications.all_marked_as_read'), [
            'marked_count' => $count,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        $deleted = $this->notificationService->deleteNotification($id, $user);

        if (! $deleted) {
            return $this->error(__('api.notifications.not_found'), [], 404);
        }

        return $this->success(__('api.notifications.deleted'));
    }

    public function registerDeviceToken(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.notifications.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        $validated = $validator->validated();

        $deviceToken = $user->deviceTokens()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'platform' => $validated['platform'],
                'last_used_at' => now(),
            ]
        );

        return $this->success(__('api.notifications.device_token_registered'), [
            'device_token_id' => $deviceToken->id,
        ]);
    }

    public function removeDeviceToken(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.notifications.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.notifications.unauthenticated'), [], 401);
        }

        $user->deviceTokens()->where('token', $request->input('token'))->delete();

        return $this->success(__('api.notifications.device_token_removed'));
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
