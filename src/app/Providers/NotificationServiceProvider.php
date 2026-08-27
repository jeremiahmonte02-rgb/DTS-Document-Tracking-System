<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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

            if ($user) {
                $unreadNotificationsCount = DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();

                $recentNotifications = Notification::query()
                    ->with('document:id,document_number,title')
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get();

                $unreadAnnouncements = \App\Models\Announcement::query()
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();

                if ($unreadAnnouncements->isNotEmpty()) {
                    $readIds = DB::table('announcement_reads')
                        ->where('user_id', $user->id)
                        ->whereIn('announcement_id', $unreadAnnouncements->pluck('id')->all())
                        ->pluck('announcement_id')
                        ->all();

                    $unreadAnnouncements->each(function ($announcement) use ($readIds) {
                        $announcement->is_read = in_array($announcement->id, $readIds, true);
                    });

                    $unreadAnnouncements = $unreadAnnouncements->where('is_read', false)->values();
                }

                $unreadAnnouncementsCount = $unreadAnnouncements->count();
                $activeAnnouncements = $unreadAnnouncements->take(3);
            }

            $view->with('unreadNotificationsCount', $unreadNotificationsCount);
            $view->with('recentNotifications', $recentNotifications);
            $view->with('unreadAnnouncementsCount', $unreadAnnouncementsCount);
            $view->with('unreadAnnouncements', $unreadAnnouncements);
            $view->with('activeAnnouncements', $activeAnnouncements);
        });
    }
}