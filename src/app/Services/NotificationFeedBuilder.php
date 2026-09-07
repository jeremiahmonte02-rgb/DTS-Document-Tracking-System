<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
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
}
