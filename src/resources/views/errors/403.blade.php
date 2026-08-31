@extends('layouts.app')

@section('title', 'Access Restricted - Document Tracking System')

@section('pageTitle', 'Access Restricted')

@section('content')
            <div class="row justify-content-center mt-5">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-shield-x text-danger" style="font-size: 4rem;"></i>
                            <h2 class="mt-4 fw-bold">Access Restricted</h2>
                            <p class="text-muted mb-4">
                                {{ $exception->getMessage() ?: 'This action is unauthorized for your role or department.' }}
                            </p>
                            <div class="alert alert-info d-flex align-items-center gap-2 text-start" role="alert">
                                <i class="bi bi-info-circle-fill fs-5"></i>
                                <div>
                                    <strong>Your Context:</strong><br>
                                    Role: <strong>{{ auth()->user()->role->name ?? 'N/A' }}</strong>
                                    &middot;
                                    Department: <strong>{{ auth()->user()->department->name ?? 'Not Assigned' }}</strong>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                                <a href="/dashboard" class="btn btn-primary">
                                    <i class="bi bi-speedometer2"></i> Return to Dashboard
                                </a>
                                <a href="/inbox" class="btn btn-outline-primary">
                                    <i class="bi bi-inbox"></i> View My Department Queue
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection
