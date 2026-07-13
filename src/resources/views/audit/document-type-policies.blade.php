@extends('layouts.app')

@section('title', 'Routing Policies - Document Tracking System')



@section('pageTitle', 'Routing Policies')

@section('content')
            <div class="policy-header">
                <h1>Document Type Routing Policies</h1>
                <p>Configure whether each document type follows a fixed immutable path or a mutable template path across departments.</p>
            </div>

            @if (session('success'))
            <div class="alert alert-success alert-flash d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
            </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="table-container">
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>Policy Status</th>
                                </tr>
                            </thead>
                            <tbody id="policyTableBody">
                                @forelse($documentTypes as $type)
                                <tr data-type-id="{{ $type->id }}"
                                    data-type-name="{{ $type->name }}"
                                    data-is-immutable="{{ $type->routingPolicy?->is_immutable ?? 'null' }}"
                                    data-predefined-route="{{ json_encode($type->routingPolicy?->predefined_route ?? []) }}"
                                    onclick="selectPolicyType(this)">
                                    <td class="fw-semibold">{{ $type->name }}</td>
                                    <td>
                                        @if ($type->routingPolicy)
                                            @if ($type->routingPolicy->is_immutable)
                                            <span class="badge badge-policy badge-immutable">Immutable</span>
                                            @else
                                            <span class="badge badge-policy badge-mutable">Mutable</span>
                                            @endif
                                        @else
                                        <span class="badge badge-policy badge-default">Default</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2">
                                        <div class="empty-state">
                                            <i class="bi bi-folder2-open"></i>
                                            <p>No document types defined.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="editor-card" id="policyEditor">
                        <div class="empty-state" id="editorPlaceholder">
                            <i class="bi bi-arrow-left-circle"></i>
                            <p>Select a document type from the list to configure its routing policy.</p>
                        </div>

                        <div id="editorContent" style="display: none;">
                            <form method="POST" action="{{ route('audit.policies.store') }}" id="policyForm">
                                @csrf
                                <input type="hidden" name="document_type_id" id="policyDocumentTypeId">
                                <input type="hidden" name="is_immutable" id="policyIsImmutable" value="0">
                                <input type="hidden" name="predefined_route" id="policyPredefinedRoute">

                                <h5 id="editorTitle">Configure Policy</h5>
                                <p class="editor-subtitle" id="editorSubtitle">Set the routing behavior for this document type.</p>

                                <div class="mb-4">
                                    <label class="form-label">Routing Mode</label>
                                    <div class="policy-toggle">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="immutableToggle" onchange="toggleImmutable(this)">
                                        </div>
                                        <span class="toggle-label mutable" id="modeLabel">Mutable — departments can be reordered during upload</span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Default Route Path</label>
                                    <p class="placeholder-text mb-2">Select departments and build the ordered routing sequence.</p>

                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <select id="deptPool" class="form-select mb-2" size="8" multiple>
                                                @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" data-name="{{ $dept->name }}">{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-accent btn-sm flex-grow-1" onclick="addSelectedDepts()">
                                                    <i class="bi bi-plus-lg"></i> Add
                                                </button>
                                                <button type="button" class="btn btn-accent-outline btn-sm" onclick="clearRoute()">
                                                    <i class="bi bi-x-lg"></i> Clear
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-7">
                                            <div class="route-list-container" id="routeListContainer">
                                                <div class="placeholder-text d-flex align-items-center justify-content-center h-100" id="routePlaceholder">
                                                    Selected departments appear here in order
                                                </div>
                                                <ul class="list-unstyled mb-0" id="routeList"></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 pt-2 border-top" style="border-color: var(--whisper) !important;">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="bi bi-check-lg"></i> Save Policy
                                    </button>
                                    <button type="button" class="btn btn-accent-outline" onclick="resetEditor()">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

    @include('partials.access-denied-modal')
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/audit-policies.js') }}"></script>
@endsection
