@extends('theme::layouts.1col')
@section('title', 'Report: ' . ($report->title ?? ''))
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $report->title }}</h2>
            <span class="badge bg-secondary fs-6">{{ ucfirst($report->status ?? 'draft') }}</span>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        {{-- Status Update --}}
        <form method="POST" action="{{ route('research.viewReport', $report->id) }}" class="mb-3 d-inline-flex gap-2">
            @csrf <input type="hidden" name="form_action" value="update_status">
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                @foreach(['draft','in_progress','review','published','archived'] as $s)<option value="{{ $s }}" {{ ($report->status ?? '') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
            </select>
            <button class="btn btn-sm btn-outline-primary">Update Status</button>
        </form>

        {{-- Add Section --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Add Section</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.viewReport', $report->id) }}" class="row g-3">
                    @csrf <input type="hidden" name="form_action" value="add_section">
                    <div class="col-md-4"><select name="section_type" class="form-select"><option value="text">Text</option><option value="heading">Heading</option><option value="title_page">Title Page</option><option value="toc">Table of Contents</option><option value="bibliography">Bibliography</option><option value="collection_list">Collection List</option></select></div>
                    <div class="col-md-5"><input type="text" name="title" class="form-control" placeholder="Section title"></div>
                    <div class="col-md-3"><button class="btn btn-success w-100">Add Section</button></div>
                </form>
            </div>
        </div>

        {{-- Sections --}}
        @foreach($report->sections ?? [] as $section)
            <div class="card mb-2">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="mb-0">{{ $section->title ?: ucfirst($section->section_type ?? 'text') }}</h6>
                    <small class="text-muted">#{{ $section->sort_order ?? 0 }}</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('research.viewReport', $report->id) }}">
                        @csrf <input type="hidden" name="form_action" value="update_section"><input type="hidden" name="section_id" value="{{ $section->id }}">
                        <div class="mb-2"><input type="text" name="title" class="form-control" value="{{ $section->title }}"></div>
                        <div class="mb-2"><textarea name="content" class="form-control" rows="4">{!! $section->content ?? '' !!}</textarea></div>
                        <button class="btn btn-sm btn-primary">Save Section</button>
                    </form>
                    <form method="POST" action="{{ route('research.viewReport', $report->id) }}" class="d-inline mt-2">
                        @csrf <input type="hidden" name="form_action" value="delete_section"><input type="hidden" name="section_id" value="{{ $section->id }}">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete section?')">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach

        <div class="mt-3">
            <a href="{{ route('research.reports') }}" class="btn btn-outline-secondary">Back</a>
            <form method="POST" action="{{ route('research.viewReport', $report->id) }}" class="d-inline">
                @csrf <input type="hidden" name="form_action" value="delete_report">
                <button class="btn btn-outline-danger" onclick="return confirm('Delete entire report?')">Delete Report</button>
            </form>
        </div>
    </div>
</div>
@endsection
