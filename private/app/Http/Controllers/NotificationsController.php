<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? class_basename($notification->type),
                    'title' => $data['title'] ?? 'Notificacion',
                    'message' => $data['message'] ?? '',
                    'cliente_id' => $data['cliente_id'] ?? null,
                    'cliente_slug' => $data['cliente_slug'] ?? null,
                    'actor_id' => $data['actor_id'] ?? null,
                    'actor_name' => $data['actor_name'] ?? null,
                    'action_url_web' => $data['action_url_web'] ?? null,
                    'action_url_app' => $data['action_url_app'] ?? null,
                    'read_at' => optional($notification->read_at)?->toIso8601String(),
                    'created_at' => optional($notification->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notificacion marcada como leida.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Notificaciones marcadas como leidas.']);
    }
}
