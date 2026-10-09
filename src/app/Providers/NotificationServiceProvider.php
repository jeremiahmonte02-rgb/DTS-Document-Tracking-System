<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Notification;
use App\Services\NotificationFeedBuilder;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['partials.sidebar-nav', 'layouts.app', 'partials.announcement-banner'], function (ViewContract $view) {
            $user = auth()->user();

            $unreadNotificationsCount = 0;
            $recentNotifications = collect();
            $unreadAnnouncementsCount = 0;
            $unreadAnnouncements = collect();
            $activeAnnouncements = collect();
            $activeNearOverdueWarnings = collect();
            $activeBannerItems = collect();
            $unifiedFeed = collect();

            if ($user) {
                $counts = NotificationFeedBuilder::getUnreadCounts($user);
                $unreadNotificationsCount = $counts['unreadNotificationsCount'];
                $unreadAnnouncementsCount = $counts['unreadAnnouncementsCount'];

                $recentNotifications = Notification::query()
                    ->with('document:id,document_number,title')
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                $unreadAnnouncements = \App\Models\Announcement::query()
                    ->whereDoesntHave('reads', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                $unifiedFeed = NotificationFeedBuilder::buildUnifiedFeed($user, 10);

                $activeAnnouncements = $unreadAnnouncements->take(3);

                // Department-scoped near-overdue warnings for the banner. These
                // are per-user notification rows fanned out only to members of
                // the holding department (see Notification::broadcastToDepartment),
                // so filtering by the viewer's own user id guarantees a user
                // never sees another department's warnings. Merged newest-first
                // with system-wide announcements under the existing cap of 3.
                $activeNearOverdueWarnings = Notification::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'near_overdue')
                    ->whereNull('read_at')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                // NOTE: toBase() first — Eloquent's map() only demotes to a base
                // Collection when the result is non-empty, so without this an
                // empty announcements list would leave an Eloquent-typed
                // receiver whose merge() calls getKey() on these plain
                // wrapper arrays and crashes. Base merge is type-agnostic.
                $activeBannerItems = $unreadAnnouncements->toBase()
                    ->map(fn ($a) => ['kind' => 'announcement', 'item' => $a])
                    ->merge(
                        $activeNearOverdueWarnings->toBase()->map(fn ($n) => ['kind' => 'near_overdue', 'item' => $n])
                    )
                    ->sortByDesc(fn ($entry) => $entry['item']->created_at)
                    ->take(3)
                    ->values();
            }

            $view->with('unreadNotificationsCount', $unreadNotificationsCount);
            $view->with('recentNotifications', $recentNotifications);
            $view->with('unreadAnnouncementsCount', $unreadAnnouncementsCount);
            $view->with('unreadAnnouncements', $unreadAnnouncements);
            $view->with('activeAnnouncements', $activeAnnouncements);
            $view->with('activeNearOverdueWarnings', $activeNearOverdueWarnings);
            $view->with('activeBannerItems', $activeBannerItems);
            $view->with('unifiedFeed', $unifiedFeed);
        });
    }
}