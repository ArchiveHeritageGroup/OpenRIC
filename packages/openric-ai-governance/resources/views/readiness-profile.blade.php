@extends('theme::layouts.1col')
@section('title', $mode === 'list' ? 'Readiness Profiles' : ($mode === 'create' ? 'New Readiness Profile' : 'Edit Readiness Profile'))
@section('content')

@if($mode === 'list')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Readiness Profiles</h1>
    <div>
        <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm me-1">Dashboard</a>
        <a href="{{ route('ai-governance.readiness-profiles.create') }}" class="btn btn-primary btn-sm">New Profile</a>
    </div>
</div>
@include('theme::partials.alerts')

<form method="GET" action="{{ route('ai-governance.readiness-profiles') }}" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="completeness" class="form-select form-select-sm">
            <option value="">All completeness</option>
            @foreach($completenessOptions as $val => $label)
                <option value="{{ $val }}" @selected(($filters['completeness'] ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead><tr><th>Collection</th><th>Completeness</th><th>Items</th><th>Digitised</th><th>Described</th><th>Last Assessed</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td><span title="{{ $item->collection_iri }}">{{ \Illuminate\Support\Str::limit($item->collection_title, 50) }}</span></td>
                <td><span class="badge bg-{{ $item->digitisation_completeness === 'complete' ? 'success' : ($item->digitisation_completeness === 'partial' ? 'warning' : 'secondary') }}">{{ $item->digitisation_completeness }}</span></td>
                <td>{{ number_format($item->estimated_item_count ?? 0) }}</td>
                <td>{{ number_format($item->digitised_item_count ?? 0) }}</td>
                <td>{{ number_format($item->described_item_count ?? 0) }}</td>
                <td>{{ $item->last_assessed_at ? \Carbon\Carbon::parse($item->last_assessed_at)->format('Y-m-d') : '-' }}</td>
                <td>
                    <a href="{{ route('ai-governance.readiness-profiles.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="POST" action="{{ route('ai-governance.readiness-profiles.delete', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete this profile?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Del</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-muted text-center">No readiness profiles found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($total > $limit)
<nav><ul class="pagination pagination-sm">
    @for($p = 1; $p <= ceil($total / $limit); $p++)
        <li class="page-item @if($p === $page) active @endif"><a class="page-link" href="{{ route('ai-governance.readiness-profiles', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
    @endfor
</ul></nav>
@endif

@else
{{-- Create / Edit Form --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $mode === 'create' ? 'New' : 'Edit' }} Readiness Profile</h1>
    <a href="{{ route('ai-governance.readiness-profiles') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
</div>
@include('theme::partials.alerts')

<form method="POST" action="{{ $mode === 'create' ? route('ai-governance.readiness-profiles.store') : route('ai-governance.readiness-profiles.update', $editing->id) }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="card mb-3"><div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="collection_iri" class="form-label">Collection IRI <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="collection_iri" name="collection_iri" value="{{ old('collection_iri', $editing->collection_iri ?? '') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="collection_title" class="form-label">Collection Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="collection_title" name="collection_title" value="{{ old('collection_title', $editing->collection_title ?? '') }}" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="digitisation_completeness" class="form-label">Digitisation Completeness <span class="text-danger">*</span></label>
                <select class="form-select" id="digitisation_completeness" name="digitisation_completeness" required>
                    @foreach($completenessOptions as $val => $label)
                        <option value="{{ $val }}" @selected(old('digitisation_completeness', $editing->digitisation_completeness ?? 'unknown') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="corpus_status" class="form-label">Corpus Status</label>
                <select class="form-select" id="corpus_status" name="corpus_status">
                    <option value="unknown" @selected(old('corpus_status', $editing->corpus_status ?? 'unknown') === 'unknown')>Unknown</option>
                    <option value="complete" @selected(old('corpus_status', $editing->corpus_status ?? '') === 'complete')>Complete</option>
                    <option value="partial" @selected(old('corpus_status', $editing->corpus_status ?? '') === 'partial')>Partial</option>
                    <option value="sampled" @selected(old('corpus_status', $editing->corpus_status ?? '') === 'sampled')>Sampled</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="estimated_item_count" class="form-label">Estimated Items</label>
                <input type="number" class="form-control" id="estimated_item_count" name="estimated_item_count" value="{{ old('estimated_item_count', $editing->estimated_item_count ?? '') }}" min="0">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="digitised_item_count" class="form-label">Digitised Items</label>
                <input type="number" class="form-control" id="digitised_item_count" name="digitised_item_count" value="{{ old('digitised_item_count', $editing->digitised_item_count ?? '') }}" min="0">
            </div>
            <div class="col-md-4 mb-3">
                <label for="described_item_count" class="form-label">Described Items</label>
                <input type="number" class="form-control" id="described_item_count" name="described_item_count" value="{{ old('described_item_count', $editing->described_item_count ?? '') }}" min="0">
            </div>
            <div class="col-md-4 mb-3">
                <label for="languages_present" class="form-label">Languages (comma-separated)</label>
                <input type="text" class="form-control" id="languages_present" name="languages_present" value="{{ old('languages_present', isset($editing->languages_present) ? implode(', ', json_decode($editing->languages_present ?? '[]', true) ?: []) : '') }}" placeholder="en, af, zu">
            </div>
        </div>
        <div class="mb-3">
            <label for="metadata_standards" class="form-label">Metadata Standards (comma-separated)</label>
            <input type="text" class="form-control" id="metadata_standards" name="metadata_standards" value="{{ old('metadata_standards', isset($editing->metadata_standards) ? implode(', ', json_decode($editing->metadata_standards ?? '[]', true) ?: []) : '') }}" placeholder="ISAD(G), Dublin Core">
        </div>
        <div class="mb-3">
            <label for="known_gaps" class="form-label">Known Gaps</label>
            <textarea class="form-control" id="known_gaps" name="known_gaps" rows="2">{{ old('known_gaps', $editing->known_gaps ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="excluded_records" class="form-label">Excluded Records</label>
            <textarea class="form-control" id="excluded_records" name="excluded_records" rows="2">{{ old('excluded_records', $editing->excluded_records ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="legal_privacy_exclusions" class="form-label">Legal / Privacy Exclusions</label>
            <textarea class="form-control" id="legal_privacy_exclusions" name="legal_privacy_exclusions" rows="2">{{ old('legal_privacy_exclusions', $editing->legal_privacy_exclusions ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="representational_bias_notes" class="form-label">Representational Bias Notes</label>
            <textarea class="form-control" id="representational_bias_notes" name="representational_bias_notes" rows="2">{{ old('representational_bias_notes', $editing->representational_bias_notes ?? '') }}</textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="data_quality_notes" class="form-label">Data Quality Notes</label>
                <textarea class="form-control" id="data_quality_notes" name="data_quality_notes" rows="2">{{ old('data_quality_notes', $editing->data_quality_notes ?? '') }}</textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label for="format_notes" class="form-label">Format Notes</label>
                <textarea class="form-control" id="format_notes" name="format_notes" rows="2">{{ old('format_notes', $editing->format_notes ?? '') }}</textarea>
            </div>
        </div>
    </div></div>
    <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Create Profile' : 'Update Profile' }}</button>
    <a href="{{ route('ai-governance.readiness-profiles') }}" class="btn btn-secondary">Cancel</a>
</form>
@endif
@endsection
