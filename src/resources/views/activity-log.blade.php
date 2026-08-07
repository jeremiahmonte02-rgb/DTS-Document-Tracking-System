@extends('layouts.app')

@section('title', 'Activity Log - Document Tracking System')

@section('pageTitle', 'Activity Log')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search-input"
                               placeholder="Search activities..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="category-filter">
                        <option value="">All Categories</option>
                        <option value="auth" {{ request('category') === 'auth' ? 'selected' : '' }}>Auth</option>
                        <option value="documents" {{ request('category') === 'documents' ? 'selected' : '' }}>Documents</option>
                        <option value="users" {{ request('category') === 'users' ? 'selected' : '' }}>Users</option>
                        <option value="departments" {{ request('category') === 'departments' ? 'selected' : '' }}>Departments</option>
                        <option value="policies" {{ request('category') === 'policies' ? 'selected' : '' }}>Policies</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" id="filter-apply">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('activity-log') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-clock-history"></i> Activity Timeline
            </h5>
            <span class="badge bg-primary">{{ $logs->total() }} total</span>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">No activity logs found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                    <td>
                                        {{ $log->user->name ?? 'System' }}
                                        @if($log->department)
                                            <br><small class="text-muted">{{ $log->department->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $log->event_code->badgeClass() }}">
                                            <i class="bi {{ $log->event_code->icon() }}"></i>
                                            {{ $log->event_code->label() }}
                                        </span>
                                    </td>
                                    <td>{{ $log->description }}</td>
                                    <td><span class="text-muted">{{ $log->event_code->category() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top w-100">
                    <div class="activity-pagination w-100">
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('styles')
<style>
    .activity-pagination nav {
        width: 100% !important;
    }
</style>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('filter-apply').addEventListener('click', function () {
                const search = document.getElementById('search-input').value;
                const category = document.getElementById('category-filter').value;
                const params = new URLSearchParams();
                if (search) params.set('search', search);
                if (category) params.set('category', category);
                window.location.href = '{{ route("activity-log") }}?' + params.toString();
            });

            document.getElementById('search-input').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    document.getElementById('filter-apply').click();
                }
            });
        });
    </script>
@endsection
