@extends('theme::layout')

@section('title', 'Browse Form Templates')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Browse Templates</h4>
        </div>
        <a href="{{ route('forms.template.create') }}" class="atom-btn-white">
            <i class="bi bi-plus-circle me-1"></i>Create Template
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($formTypes as $value => $label)
                            <option value="{{ $value }}" {{ $type == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search templates..." value="{{ $search ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="atom-btn-white w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($templates->isEmpty())
                <div class="p-4 text-center text-muted">No templates found.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Fields</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td><strong>{{ $template->name }}</strong></td>
                                    <td>{{ $formTypes[$template->form_type] ?? $template->form_type }}</td>
                                    <td>{{ $template->field_count }}</td>
                                    <td>
                                        <a href="{{ route('forms.builder', $template->id) }}" class="atom-btn-white btn-sm me-1">Edit</a>
                                        <a href="{{ route('forms.preview', $template->id) }}" class="atom-btn-white btn-sm">Preview</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
