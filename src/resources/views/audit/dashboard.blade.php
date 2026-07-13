@extends('layouts.app')

@section('title', 'Audit Dashboard - Document Tracking System')



@section('pageTitle', 'Audit Portal')

@section('content')
            <div class="audit-header">
                <h1>Audit Dashboard</h1>
                <p>Cross-departmental visibility into the entire document lifecycle. All metrics are read-only and computed in real time.</p>
            </div>

            <div class="hero-card">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2>Welcome to UCDTS</h2>
                        <p class="hero-sub">
                            The University Document Tracking System provides complete visibility into document routing,
                            receipt confirmation, and archival across all departments. Use the directory below to search,
                            filter, and inspect any document in the system.
                        </p>
                        <a href="{{ route('audit.documents') }}" class="btn btn-accent">
                            <i class="bi bi-file-earmark-text"></i> Browse Documents
                        </a>
                    </div>
                    <div class="col-lg-4 text-center text-lg-end">
                        <div class="hero-visual d-inline-flex">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('audit.documents') }}" class="quick-card">
                        <div class="quick-icon emerald">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h6>Documents Directory</h6>
                        <p>Search and inspect all registered documents across the system.</p>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('audit.documents') }}?status=pending_transfer" class="quick-card">
                        <div class="quick-icon amber">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h6>Pending Transfers</h6>
                        <p>View documents awaiting transfer between departments.</p>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('audit.documents') }}?status=in_transit" class="quick-card">
                        <div class="quick-icon blue">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <h6>In Transit</h6>
                        <p>Monitor documents currently in transit between locations.</p>
                    </a>
                </div>
            </div>
@endsection
