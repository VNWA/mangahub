<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $perPage = min((int) $request->query('per_page', 20), 50);
        $unreadOnly = $request->query('unread_only', false);

        $query = $user->notifications()->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $notifications->getCollection()->map(function ($notification) {
                $data = $notification->data;
                $readAt = $notification->read_at?->toISOString();

                // Extract type from notification class name or data
                $type = $data['type'] ?? str_replace('App\\Notifications\\', '', $notification->type);
                $type = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $type));
                $type = str_replace('_notification', '', $type);

                // For new_chapter, ensure we have manga_slug and chapter_slug
                if ($type === 'new_chapter') {
                    if (isset($data['manga_slug']) && isset($data['chapter_slug'])) {
                        // Already has slugs
                    } elseif (isset($data['manga']) && isset($data['chapter'])) {
                        // Extract from nested structure
                        $data['manga_slug'] = $data['manga']['slug'] ?? null;
                        $data['chapter_slug'] = $data['chapter']['slug'] ?? null;
                    }
                }

                return [
                    'id' => $notification->id,
                    'type' => $type,
                    'title' => $data['title'] ?? 'Thông báo',
                    'message' => $data['message'] ?? '',
                    'data' => $data,
                    'read_at' => $readAt,
                    'created_at' => $notification->created_at->toISOString(),
                ];
            })->values(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json([
                'ok' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'ok' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'ok' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }
}
