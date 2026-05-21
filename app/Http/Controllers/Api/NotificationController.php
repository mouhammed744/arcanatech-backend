<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationController
 *
 * ENDPOINTS :
 * GET    /api/me/notifications              → Liste des notifications de l'utilisateur connecté
 * POST   /api/me/notifications/{id}/read   → Marquer une notification comme lue
 * POST   /api/me/notifications/read-all    → Marquer toutes comme lues
 * DELETE /api/me/notifications/{id}        → Supprimer une notification
 *
 * POST   /api/me/fcm-token                 → Enregistrer un token FCM (mobile)
 * DELETE /api/me/fcm-token                 → Supprimer le token FCM (déconnexion)
 */
class NotificationController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /me/notifications
    // ──────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = $user->notifications();

        // Filtre : non lues seulement
        if ($request->boolean('unread')) {
            $query = $user->unreadNotifications();
        }

        $notifications = $query
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data'         => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'total'        => $notifications->total(),
            'per_page'     => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /me/notifications/{id}/read
    // ──────────────────────────────────────────────────────────
    public function markAsRead(string $notificationId): JsonResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($notificationId);

        $notification->markAsRead();

        return response()->json([
            'message'      => 'Notification marquée comme lue.',
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /me/notifications/read-all
    // ──────────────────────────────────────────────────────────
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message'      => 'Toutes les notifications ont été marquées comme lues.',
            'unread_count' => 0,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /me/notifications/{id}
    // ──────────────────────────────────────────────────────────
    public function destroy(string $notificationId): JsonResponse
    {
        auth()->user()
            ->notifications()
            ->findOrFail($notificationId)
            ->delete();

        return response()->json(['message' => 'Notification supprimée.']);
    }

    // ──────────────────────────────────────────────────────────
    // POST /me/fcm-token  { token, platform }
    // ──────────────────────────────────────────────────────────
    public function storeFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'    => 'required|string|max:512',
            'platform' => 'nullable|string|in:android,ios',
        ]);

        // Upsert : un appareil = un token (unique)
        FcmToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'utilisateur_id' => auth()->id(),
                'platform'       => $validated['platform'] ?? 'android',
            ]
        );

        return response()->json(['message' => 'Token FCM enregistré.']);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /me/fcm-token  { token }
    // ──────────────────────────────────────────────────────────
    public function deleteFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        FcmToken::where('utilisateur_id', auth()->id())
                ->where('token', $validated['token'])
                ->delete();

        return response()->json(['message' => 'Token FCM supprimé.']);
    }
}
