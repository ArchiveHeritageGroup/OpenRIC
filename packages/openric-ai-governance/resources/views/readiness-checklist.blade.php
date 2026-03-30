@extends('theme::layouts.1col')
@section('title', $mode === 'list' ? 'Readiness Checklists' : ($mode === 'create' ? 'New Checklist' : 'Edit Checklist'))
@section('content')

@if($mode === 'list')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Readiness Checklists</h1>
    <div>
        <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm me-1">Dashboard</a>
        <a href="{{ route('ai-governance.readiness-checklist.create') }}" class="btn btn-primary btn-sm">New Checklist</a>
    </div>
</div>
@include('theme::partials.alerts')

<form method="GET" action="{{ route('ai-governance.readiness-checklist') }}" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach($statusOptions as $val => $label)
                <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Search project name or use case..."></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead><tr><th>Project</th><th>Use Case</th><th>Status</th><th>Progress</th><th>Approved</th><th>Updated</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($items as $item)
            @php
                $checks = [
                    $item->use_case_defined ?? false,
                    $item->corpus_completeness_documented ?? false,
                    $item->metadata_minimum_met ?? false,
                    $item->access_rules_structured ?? false,
                    $item->derivatives_prepared ?? false,
                    $item->evaluation_plan_approved ?? false,
                    $item->human_review_workflow_active ?? false,
                ];
                $done = count(array_filter($checks));
                $pct = round($done / 7 * 100);
            @endphp
            <tr>
                <td>{{ $item->project_name }}</td>
                <td class="small">{{ \Illuminate\Support\Str::limit($item->use_case, 40) }}</td>
                <td>
                    @php $stBg = match($item->checklist_status) { 'ready' => 'success', 'in_progress' => 'warning', 'blocked' => 'danger', default => 'secondary' }; @endphp
                    <span class="badge bg-{{ $stBg }}">{{ $statusOptions[$item->checklist_status] ?? $item->checklist_status }}</span>
                </td>
                <td>
                    <div class="progress" style="height: 18px; min-width: 80px;">
                        <div class="progress-bar bg-{{ $pct === 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}" style="width: {{ $pct }}%">{{ $done }}/7</div>
                    </div>
                </td>
                <td>
                    @if($item->approved_by)
                        <span class="badge bg-success">Yes</span> <span class="small">{{ \Carbon\Carbon::parse($item->approved_at)->format('Y-m-d') }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="small">{{ \Carbon\Carbon::parse($item->updated_at)->format('Y-m-d') }}</td>
                <td><a href="{{ route('ai-governance.readiness-checklist.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-muted text-center">No checklists found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($total > $limit)
<nav><ul class="pagination pagination-sm">
    @for($p = 1; $p <= ceil($total / $limit); $p++)
        <li class="page-item @if($p === $page) active @endif"><a class="page-link" href="{{ route('ai-governance.readiness-checklist', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
    @endfor
</ul></nav>
@endif

@else
{{-- Create / Edit Form --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $mode === 'create' ? 'New' : 'Edit' }} Readiness Checklist</h1>
    <div>
        <a href="{{ route('ai-governance.readiness-checklist') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
        @if($mode === 'edit')
            <form method="POST" action="{{ route('ai-governance.readiness-checklist.auto-check', $editing->id) }}" class="d-inline">@csrf
                <button type="submit" class="btn btn-outline-info btn-sm">Run Auto-Checks</button>
            </form>
            @if(!($editing->approved_by ?? null))
            <form method="POST" action="{{ route('ai-governance.readiness-checklist.approve', $editing->id) }}" class="d-inline" onsubmit="return confirm('Approve this checklist?')">@csrf
                <button type="submit" class="btn btn-success btn-sm">Approve</button>
            </form>
            @endif
        @endif
    </div>
</div>
@include('theme::partials.alerts')

@if($mode === 'edit' && ($editing->approved_by ?? null))
<div class="alert alert-success">
    <strong>Approved</strong> by user #{{ $editing->approved_by }} on {{ \Carbon\Carbon::parse($editing->approved_at)->format('Y-m-d H:i') }}
</div>
@endif

<form method="POST" action="{{ $mode === 'create' ? route('ai-governance.readiness-checklist.store') : route('ai-governance.readiness-checklist.update', $editing->id) }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">Project Details</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="project_name" class="form-label">Project Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="project_name" name="project_name" value="{{ old('project_name', $editing->project_name ?? '') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="use_case" class="form-label">Use Case <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="use_case" name="use_case" value="{{ old('use_case', $editing->use_case ?? '') }}" required placeholder="e.g. Automated description generation">
                </div>
            </div>
            <div class="mb-3">
                <label for="project_description" class="form-label">Project Description</label>
                <textarea class="form-control" id="project_description" name="project_description" rows="2">{{ old('project_description', $editing->project_description ?? '') }}</textarea>
            </div>
            <div class="mb-3">
                <label for="readiness_profile_id" class="form-label">Linked Readiness Profile ID</label>
                <input type="number" class="form-control" id="readiness_profile_id" name="readiness_profile_id" value="{{ old('readiness_profile_id', $editing->readiness_profile_id ?? '') }}" min="1">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">Checklist Items</h5></div>
        <div class="card-body">
            @php
                $checkItems = [
                    ['field' => 'use_case_defined', 'label' => '1. Use case clearly defined', 'notes' => 'use_case_notes'],
                    ['field' => 'corpus_completeness_documented', 'label' => '2. Corpus completeness documented', 'notes' => null],
                    ['field' => 'metadata_minimum_met', 'label' => '3. Metadata minimum standard met', 'notes' => 'metadata_notes'],
                    ['field' => 'access_rules_structured', 'label' => '4. Access rules structured for AI', 'notes' => 'access_rules_notes'],
                    ['field' => 'derivatives_prepared', 'label' => '5. AI derivatives prepared', 'notes' => 'derivatives_notes'],
                    ['field' => 'evaluation_plan_approved', 'label' => '6. Evaluation plan approved', 'notes' => null],
                    ['field' => 'human_review_workflow_active', 'label' => '7. Human review workflow active', 'notes' => 'workflow_notes'],
                ];
            @endphp

            @foreach($checkItems as $ci)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex align-items-center">
                    <div class="form-check form-switch me-3">
                        <input class="form-check-input" type="checkbox" name="{{ $ci['field'] }}" value="1" id="{{ $ci['field'] }}" @checked(old($ci['field'], $editing->{$ci['field']} ?? false))>
                        <label class="form-check-label fw-bold" for="{{ $ci['field'] }}">{{ $ci['label'] }}</label>
                    </div>
                    @if(isset($autoChecks[$ci['field']]))
                        @if($autoChecks[$ci['field']])
                            <span class="badge bg-success ms-auto" title="Auto-check passed">AUTO: PASS</span>
                        @else
                            <span class="badge bg-danger ms-auto" title="Auto-check failed">AUTO: FAIL</span>
                        @endif
                    @endif
                </div>
                @if($ci['notes'])
                <div class="mt-2">
                    <textarea class="form-control form-control-sm" name="{{ $ci['notes'] }}" rows="1" placeholder="Notes...">{{ old($ci['notes'], $editing->{$ci['notes']} ?? '') }}</textarea>
                </div>
                @endif
                @if($ci['field'] === 'evaluation_plan_approved')
                <div class="mt-2">
                    <textarea class="form-control form-control-sm" name="evaluation_plan" rows="2" placeholder="Evaluation plan details...">{{ old('evaluation_plan', $editing->evaluation_plan ?? '') }}</textarea>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Create Checklist' : 'Update Checklist' }}</button>
    <a href="{{ route('ai-governance.readiness-checklist') }}" class="btn btn-secondary">Cancel</a>
</form>
@endif
@endsection
