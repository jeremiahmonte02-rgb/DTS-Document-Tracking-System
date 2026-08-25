<?php

namespace App\Providers;

use App\Models\DepartmentDocumentSla;
use App\Models\DocumentRoute;
use App\Models\DocumentType;
use App\Models\Notification;
use Illuminate\Support\Carbon;
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

                $unreadNotificationsCount += $this->dynamicOverdueCount((int) $user->department_id);

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

    /**
     * Count live workbench steps past their SLA deadline for the given
     * department. Mirrors the operational overdue detection used on the
     * dashboard; not persisted, so it stays accurate without writes.
     *
     * @param int|null $departmentId
     * @return int
     */
    private function dynamicOverdueCount(?int $departmentId): int
    {
        if (!$departmentId) {
            return 0;
        }

        $activeRoutes = DocumentRoute::join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->select('document_routes.*', 'documents.document_type_id')
            ->where('document_routes.status', 'current')
            ->whereNotIn('documents.status', ['completed', 'cancelled', 'rejected'])
            ->where('document_routes.department_id', $departmentId)
            ->get();

        if ($activeRoutes->isEmpty()) {
            return 0;
        }

        $departmentSlas = DepartmentDocumentSla::get()
            ->groupBy('department_id')
            ->map(fn ($items) => $items->keyBy('document_type_id'));

        $documentTypes = DocumentType::get()->keyBy('id');
        $now = Carbon::now('Asia/Manila');

        $overdueCount = 0;

        foreach ($activeRoutes as $route) {
            $allowedMinutes = 1440;

            if (isset($departmentSlas[$route->department_id][$route->document_type_id])) {
                $allowedMinutes = $departmentSlas[$route->department_id][$route->document_type_id]->processing_time_minutes;
            } elseif (isset($documentTypes[$route->document_type_id]) && !is_null($documentTypes[$route->document_type_id]->default_processing_time)) {
                $allowedMinutes = $documentTypes[$route->document_type_id]->default_processing_time;
            }

            $deadline = Carbon::parse($route->created_at, 'Asia/Manila')->addMinutes($allowedMinutes);

            if ($now->greaterThan($deadline)) {
                $overdueCount++;
            }
        }

        return $overdueCount;
    }
}