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
}
