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
            }

            $view->with('unreadNotificationsCount', $unreadNotificationsCount);
            $view->with('recentNotifications', $recentNotifications);
            $view->with('unreadAnnouncementsCount', $unreadAnnouncementsCount);
            $view->with('unreadAnnouncements', $unreadAnnouncements);
            $view->with('activeAnnouncements', $activeAnnouncements);
            $view->with('unifiedFeed', $unifiedFeed);
        });
    }
}