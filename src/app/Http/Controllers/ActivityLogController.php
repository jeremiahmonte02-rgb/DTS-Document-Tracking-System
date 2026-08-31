<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdminOrAuditor = $user->isAdmin() || $user->isAuditor();

        $query = ActivityLog::with(['user', 'department'])
            ->orderBy('created_at', 'desc');

        if (!$isAdminOrAuditor) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('category')) {
            $category = $request->category;
            $ranges = [
                'auth'        => [10, 19],
                'documents'   => [20, 29],
                'users'       => [30, 39],
                'departments' => [40, 49],
                'policies'    => [50, 59],
            ];
            if (isset($ranges[$category])) {
                $query->whereBetween('event_code', $ranges[$category]);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $logs = $query->paginate(20);

        return view('activity-log', compact('logs', 'isAdminOrAuditor'));
    }
}
