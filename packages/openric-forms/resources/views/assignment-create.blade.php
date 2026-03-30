@extends('theme::layout')

@section('title', 'Create Assignment')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('forms.index') }}">Forms</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('forms.assignments') }}">Assignments</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
            <h4 class="mb-0"><i class="bi bi-link me-2"></i>Create Assignment</h4>
        </div>
    </div>

    <form method="post" action="{{ route('forms.assignment.create') }}">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Assignment Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="template_id" class="form-label">Template <span class="text-danger">*</span></label>
                            <select class="form-select" id="template_id" name="template_id" required>
                                <option value="">Select template...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <input type="number" class="form-control" id="priority" name="priority" value="0" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Actions</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="atom-btn-white w-100 mb-2">
                            <i class="bi bi-check-lg me-1"></i>Create
                        </button>
                        <a href="{{ route('forms.assignments') }}" class="atom-btn-white w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
