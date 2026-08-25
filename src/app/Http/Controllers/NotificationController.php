<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark a single notification as read for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $updated = Notification::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => $updated > 0,
            'message' => $updated > 0
                ? 'Notification marked as read.'
                : 'Notification not found for this user.',
        ], $updated > 0 ? 200 : 404);
    }
}