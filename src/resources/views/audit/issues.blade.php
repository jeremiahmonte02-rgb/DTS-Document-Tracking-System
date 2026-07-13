@extends('layouts.app')

@section('title', 'Reported Issues - Document Tracking System')



@section('pageTitle', 'Audit Portal')

@section('content')
            <div class="audit-header">
                <h1>Reported Issues</h1>
                <p>Review and manage issue reports filed against documents across all departments.</p>
            </div>

            @php
                $totalIssues = $issues->total();
                $openCount = $issues->where('status', 'open')->count();
                $inProgressCount = $issues->where('status', 'in_progress')->count();
                $resolvedCount = $issues->where('status', 'resolved')->count();
            @endphp

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon emerald">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Total Issues</p>
                                <p class="metrics-value">{{ $totalIssues }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon blue">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Open</p>
                                <p class="metrics-value">{{ $openCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon amber">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div>
                                <p class="metrics-label">In Progress</p>
                                <p class="metrics-value">{{ $inProgressCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon emerald">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Resolved</p>
                                <p class="metrics-value">{{ $resolvedCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header-section">
                    <h5>
                        Issue Reports
                        <span class="count-badge">{{ $totalIssues }}</span>
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:12%">Document ID</th>
                                <th style="width:14%">Issue Type</th>
                                <th style="width:14%">Department</th>
                                <th style="width:22%">Description</th>
                                <th style="width:12%">Reporter</th>
                                <th style="width:10%">Priority</th>
                                <th style="width:10%">Status</th>
                                <th style="width:6%">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issues as $issue)
                            <tr>
                                <td>
                                    <a href="{{ url('/document-details/' . ($issue->document->document_number ?? $issue->document_id)) }}"
                                       class="doc-number text-decoration-none">
                                        {{ $issue->document->document_number ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->issue_type ?? 'General' }}</span>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->assignedDepartment->name ?? 'Unassigned' }}</span>
                                </td>
                                <td>
                                    <span class="issue-description" title="{{ e($issue->description) }}">
                                        {{ Str::limit($issue->description, 60) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->reportedBy->name ?? 'Unknown' }}</span>
                                </td>
                                <td>
                                    <span class="audit-badge priority-{{ $issue->priority }}">
                                        {{ ucfirst($issue->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="audit-badge status-{{ $issue->status }}">
                                        {{ str_replace('_', ' ', ucfirst($issue->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->created_at->format('M d, Y') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </div>
                                        <h6>No Issues Reported</h6>
                                        <p>There are currently no reported issues in the system. Issue reports will appear here once users flag documents.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($issues->hasPages())
                <div class="pagination-wrapper d-flex justify-content-center">
                    {{ $issues->links() }}
                </div>
                @endif
            </div>
@endsection
