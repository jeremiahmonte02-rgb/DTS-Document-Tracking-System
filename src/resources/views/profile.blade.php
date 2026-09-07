@extends('layouts.app')

@section('title', 'My Profile - Document Tracking System')

@section('pageTitle', 'My Profile')

@section('content')
    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center alert-dismissible fade show shadow-sm mb-4 pe-5" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; padding: 1rem 1.25rem; border-radius: 0.375rem; position: relative;">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>
            <strong>Success!</strong> {{ session('success') }}
        </div>
        <button type="button" class="btn-close position-absolute" data-bs-dismiss="alert" aria-label="Close" style="right: 0.5rem; top: 50%; transform: translateY(-50%); padding: 1.25rem; min-width: 44px; min-height: 44px; background: none; border: none; cursor: pointer; color: #0f5132; filter: invert(20%) sepia(50%) saturate(500%) hue-rotate(100deg);"></button>
    </div>
    @endif

    <div class="row g-4">
        <!-- Account details + avatar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Account Details</h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar-preview mb-3">
                        @if($user->avatar_path)
                            <img src="{{ $user->avatarUrl() }}" class="profile-avatar-img" alt="Profile picture">
                        @else
                            <i class="bi bi-person-circle profile-avatar-fallback"></i>
                        @endif
                    </div>

                    <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" class="text-start">
                        @csrf
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Change profile picture</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text">PNG, JPG or WEBP. Max 2&nbsp;MB, 2048&times;2048&nbsp;px.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload"></i> Upload Picture
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profile information -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Profile Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $user->name }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>

                        <dt class="col-sm-4">Department</dt>
                        <dd class="col-sm-8">{{ $user->department->name ?? 'No Department Assigned' }}</dd>

                        <dt class="col-sm-4">Role</dt>
                        <dd class="col-sm-8"><span class="badge bg-primary">{{ $user->role->name ?? 'Standard User' }}</span></dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- My Uploaded Documents -->
    <div class="card mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-folder2-open"></i> My Uploaded Documents
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="profile-documents-table-wrapper" data-fetch-url="{{ route('api.profile.documents') }}">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Document Number</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Current Department</th>
                            <th>Status</th>
                            <th>Uploaded Date</th>
                        </tr>
                    </thead>
                    <tbody id="profileDocumentsTable">
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading your documents...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <nav aria-label="Document pagination">
                <ul class="pagination pagination-sm mb-0"></ul>
            </nav>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/profile-documents.js') }}?v={{ filemtime(public_path('js/modules/profile-documents.js')) }}"></script>
@endsection
