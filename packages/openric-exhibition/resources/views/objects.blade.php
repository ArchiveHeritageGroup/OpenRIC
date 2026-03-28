@extends('theme::layouts.1col')

@section('title', 'Objects — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-box me-2"></i>Exhibition Objects</h1>
        <div>
            <a href="{{ route('exhibition.show', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <a href="{{ route('exhibition.objectListCsv', $exhibition->id) }}" class="btn btn-outline-success btn-sm"><i class="fas fa-file-csv me-1"></i> Export CSV</a>
        </div>
    </div>

    <p class="text-muted">{{ $exhibition->title }}</p>

    {{-- Add object form --}}
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add Object</div>
        <div class="card-body">
            <form method="POST" action="{{ route('exhibition.objects.add', $exhibition->id) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Entity IRI</label>
                    <input type="text" name="entity_iri" class="form-control form-control-sm" required placeholder="https://...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Identifier</label>
                    <input type="text" name="identifier" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Object list --}}
    @if (isset($exhibition->objects) && $exhibition->objects->isNotEmpty())
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>#</th><th>Title</th><th>Identifier</th><th>Entity IRI</th><th>Section</th><th>Status</th><th>Notes</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @foreach ($exhibition->objects as $i => $obj)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $obj->title ?? 'Untitled' }}</td>
                        <td>{{ $obj->identifier ?? '' }}</td>
                        <td><small class="text-muted">{{ Str::limit($obj->entity_iri ?? '', 50) }}</small></td>
                        <td>{{ $obj->section ?? '' }}</td>
                        <td>{{ $obj->status ?? '' }}</td>
                        <td>{{ Str::limit($obj->notes ?? '', 50) }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('exhibition.objects.remove', [$exhibition->id, $obj->id]) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove" onclick="return confirm('Remove this object?')"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
        <div class="alert alert-info">No objects in this exhibition yet.</div>
    @endif
</div>
@endsection
