@extends('theme::layouts.1col')

@section('title', 'Object List — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-list me-2"></i>Object List</h1>
        <div>
            <a href="{{ route('exhibition.objects', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <a href="{{ route('exhibition.objectListCsv', $exhibition->id) }}" class="btn btn-outline-success btn-sm"><i class="fas fa-file-csv me-1"></i> Export CSV</a>
        </div>
    </div>

    <p class="text-muted">{{ $exhibition->title }}</p>

    @if (isset($exhibition->objects) && $exhibition->objects->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>Title</th><th>Identifier</th><th>Section</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
                @foreach ($exhibition->objects as $i => $obj)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $obj->title ?? 'Untitled' }}</td>
                    <td>{{ $obj->identifier ?? '' }}</td>
                    <td>{{ $obj->section ?? '' }}</td>
                    <td>{{ $obj->status ?? '' }}</td>
                    <td>{{ $obj->notes ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="alert alert-info">No records found.</div>
    @endif
</div>
@endsection
