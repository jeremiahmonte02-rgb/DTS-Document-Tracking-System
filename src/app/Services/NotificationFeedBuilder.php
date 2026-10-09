<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationFeedBuilder
{
    public static function getUnreadCounts(User $user): array
    {
        $unreadNotificationsCount = DB::table('notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $unreadAnnouncementsCount = Announcement::query()
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();

        return [
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'unreadAnnouncementsCount' => $unreadAnnouncementsCount,
            'totalUnread' => $unreadNotificationsCount + $unreadAnnouncementsCount,
        ];
    }

    public static function buildUnifiedFeed(User $user, int $limit = 10): Collection
    {
        $recentNotifications = Notification::query()
            ->with('document:id,document_number,title')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $unreadAnnouncements = Announcement::query()
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return collect()
            ->merge($recentNotifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => 'notification',
                    'notification_type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'created_at' => $n->created_at,
                    'time_ago' => $n->created_at?->diffForHumans(),
                    'document' => $n->document,
                    'document_number' => $n->document?->document_number,
                ];
            }))
            ->merge($unreadAnnouncements->map(function ($a) {
                return [
                    'id' => $a->id,
                    'type' => 'announcement',
                    'title' => $a->title,
                    'message' => $a->message,
                    'created_at' => $a->created_at,
                    'time_ago' => $a->created_at?->diffForHumans(),
                    'document' => null,
                    'document_number' => null,
                ];
            }))
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Full history feed for the dedicated notifications page: every item
     * (read AND unread) with its read state attached, optionally filtered
     * by type, with real pagination. buildUnifiedFeed() above is untouched
     * and remains the dropdown/composer's unread-only, limit(10) source.
     */
    public static function buildPaginatedFeed(
        User $user,
        ?string $type = null,
        int $page = 1,
        int $perPage = 20
    ): LengthAwarePaginator {
        $items = collect();

        if ($type === null || $type === 'notification') {
            $notifications = Notification::query()
                ->with('document:id,document_number,title')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            $items = $items->merge($notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => 'notification',
                    'title' => $n->title,
                    'message' => $n->message,
                    'created_at' => $n->created_at,
                    'time_ago' => $n->created_at?->diffForHumans(),
                    'document' => $n->document,
                    'document_number' => $n->document?->document_number,
                    'is_read' => $n->read_at !== null,
                ];
            }));
        }

        if ($type === null || $type === 'announcement') {
            $readIds = array_map(
                'intval',
                DB::table('announcement_reads')->where('user_id', $user->id)->pluck('announcement_id')->all()
            );

            $announcements = Announcement::query()
                ->orderByDesc('created_at')
                ->get();

            $items = $items->merge($announcements->map(function ($a) use ($readIds) {
                return [
                    'id' => $a->id,
                    'type' => 'announcement',
                    'title' => $a->title,
                    'message' => $a->message,
                    'created_at' => $a->created_at,
                    'time_ago' => $a->created_at?->diffForHumans(),
                    'document' => null,
                    'document_number' => null,
                    'is_read' => in_array((int) $a->id, $readIds, true),
                ];
            }));
        }

        $sorted = $items->sortByDesc('created_at')->values();
        $page = max(1, $page);

        return new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page
        );
    }
}
