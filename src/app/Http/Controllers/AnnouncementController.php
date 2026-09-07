<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    /**
     * Mark an announcement as read for the authenticated user. Read state is
     * stored per-user, so the same announcement can be unread for others.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        DB::table('announcement_reads')->updateOrInsert(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $request->user()->id,
            ],
            [
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read.',
        ], 200);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $unreadIds = Announcement::query()
            ->whereDoesntHave('reads', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->pluck('id');

        if ($unreadIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No unread announcements.',
            ], 200);
        }

        $now = now();
        $rows = $unreadIds->map(function ($announcementId) use ($userId, $now) {
            return [
                'announcement_id' => $announcementId,
                'user_id' => $userId,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('announcement_reads')->insertOrIgnore($rows);

        return response()->json([
            'success' => true,
            'message' => 'All announcements marked as read.',
        ], 200);
    }
}
