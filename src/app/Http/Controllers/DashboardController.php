<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Core Metric Card Telemetry
        $totalDocuments = DB::table('documents')->count();
        $pendingDocuments = DB::table('documents')->where('status', 'pending_transfer')->count();
        $inTransitDocuments = DB::table('documents')->where('status', 'in_transit')->count();

        $receivedToday = DB::table('documents')
            ->where('status', 'received')
            ->whereDate('completed_at', Carbon::today())
            ->count();

        // 2. Chart Distributions Aggregations (Doughnut Status Distribution)
        $statusDistribution = DB::table('documents')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $chartStatuses = ['received', 'pending_transfer', 'in_transit', 'rejected'];
        $statusMetrics = [];
        foreach ($chartStatuses as $status) {
            $statusMetrics[$status] = $statusDistribution[$status] ?? 0;
        }

        // 3. Department Bar Distribution
        $departmentDistribution = DB::table('documents')
            ->join('departments', 'documents.current_department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.name')
            ->pluck('count', 'name')
            ->toArray();

        // 4. Recent Activity Stream
        $activityFeed = DB::table('document_events')
            ->join('documents', 'document_events.document_id', '=', 'documents.id')
            ->join('users', 'document_events.user_id', '=', 'users.id')
            ->select(
                'document_events.event_label',
                'document_events.note',
                'document_events.created_at',
                'documents.title',
                'documents.document_number',
                'users.name as user_name'
            )
            ->latest('document_events.created_at')
            ->take(10)
            ->get();

        // 5. Notifications count (placeholder for now)
        $unreadNotificationsCount = 3;

        return view('dashboard', compact(
            'totalDocuments', 'pendingDocuments', 'inTransitDocuments', 'receivedToday',
            'statusMetrics', 'departmentDistribution', 'activityFeed', 'unreadNotificationsCount'
        ));
    }
}
