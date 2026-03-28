@extends('theme::layouts.1col')

@section('title', 'Edit Exhibition — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3"><i class="fas fa-edit me-2"></i>Edit Exhibition</h1>

    <form method="POST" action="{{ route('exhibition.update', $exhibition->id) }}">
        @csrf
        @method('PUT')
        @include('openric-exhibition::_form', ['exhibition' => $exhibition])

        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            <a href="{{ route('exhibition.show', $exhibition->id) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="button" class="btn btn-outline-danger float-end" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </div>
    </form>

    {{-- Delete confirmation modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Exhibition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong>{{ $exhibition->title }}</strong>? This will also remove all objects, storylines, sections, events, and checklists.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('exhibition.destroy', $exhibition->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
