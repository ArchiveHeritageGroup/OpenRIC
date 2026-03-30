@extends('theme::layouts.1col')
@section('title', 'Custom Fields')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-th-list me-2"></i>Custom Fields</h2>
    <div>
        <a href="{{ route('custom-fields.export') }}" class="btn btn-outline-secondary me-2"><i class="fas fa-download me-1"></i>Export</a>
        <a href="{{ route('custom-fields.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Field</a>
    </div>
</div>

@include('theme::partials.alerts')

<div class="card">
    <div class="card-body p-0">
        @if(isset($fields) && count($fields) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">Order</th>
                            <th>Label</th>
                            <th>Name</th>
                            <th>Entity Type</th>
                            <th>Field Type</th>
                            <th>Required</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fields as $def)
                            <tr>
                                <td>{{ $def->sort_order ?? 0 }}</td>
                                <td><strong>{{ $def->label ?? $def->name }}</strong></td>
                                <td><code>{{ $def->name ?? '' }}</code></td>
                                <td>{{ $entityTypes[$def->entity_type] ?? $def->entity_type ?? '' }}</td>
                                <td>{{ $fieldTypes[$def->field_type] ?? $def->field_type ?? '' }}</td>
                                <td>@if($def->is_required ?? false)<span class="badge bg-warning text-dark">Yes</span>@else<span class="text-muted">No</span>@endif</td>
                                <td>@if($def->is_active ?? true)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                                <td>
                                    <a href="{{ route('custom-fields.edit', $def->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('custom-fields.destroy', $def->id) }}" class="d-inline" onsubmit="return confirm('Delete this field?')">@csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4 text-center text-muted">
                <p>No custom fields defined.</p>
                <a href="{{ route('custom-fields.create') }}" class="btn btn-primary">Create your first custom field</a>
            </div>
        @endif
    </div>
</div>

{{-- Import Form --}}
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Import Custom Fields</h6></div>
    <div class="card-body">
        <form action="{{ route('custom-fields.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3"><label class="form-label">CSV File</label><input type="file" class="form-control" name="file" accept=".csv,.txt" required></div>
                <div class="col-md-4 mb-3 d-flex align-items-end"><button type="submit" class="btn btn-outline-primary"><i class="fas fa-upload me-1"></i>Import</button></div>
            </div>
        </form>
    </div>
</div>
@endsection
