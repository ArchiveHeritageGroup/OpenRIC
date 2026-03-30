@extends('theme::layout')

@section('title', 'Form Assignments')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-link me-2"></i>Form Assignments</h4>
            <p class="text-muted mb-0">Assign templates to repositories and levels</p>
        </div>
        <a href="{{ route('forms.assignment.create') }}" class="atom-btn-white">
            <i class="bi bi-plus-circle me-1"></i>Create Assignment
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($assignments->isEmpty())
                <div class="p-4 text-center text-muted">
                    No assignments yet. <a href="{{ route('forms.assignment.create') }}">Create one</a>.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Template</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignments as $assignment)
                                <tr>
                                    <td><strong>{{ $assignment->template_name ?? 'Unknown' }}</strong></td>
                                    <td>{{ $assignment->form_type ?? '' }}</td>
                                    <td>{{ $assignment->priority ?? 0 }}</td>
                                    <td>
                                        <a href="{{ route('forms.builder', $assignment->template_id) }}" class="atom-btn-white btn-sm">Edit</a>
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
