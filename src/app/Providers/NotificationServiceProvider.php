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
        View::composer(['partials.sidebar-nav', 'layouts.app'], function (ViewContract $view) {
            $user = auth()->user();

            $unreadNotificationsCount = 0;
            $recentNotifications = collect();

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
            }

            $view->with('unreadNotificationsCount', $unreadNotificationsCount);
            $view->with('recentNotifications', $recentNotifications);
        });
    }
}