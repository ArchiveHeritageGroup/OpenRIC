@extends('theme::layouts.2col')

@section('title', 'Workflow Administration')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-cogs me-2" aria-hidden="true"></i>Workflows
        </h1>
        <div>
            <a href="{{ route('workflow.admin.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Create Workflow
            </a>
            <a href="{{ route('workflow.dashboard') }}" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(count($workflows) === 0)
        <div class="text-center text-muted py-5">
            <i class="fas fa-project-diagram fa-3x mb-3 opacity-50" aria-hidden="true"></i>
            <h4>No workflows configured</h4>
            <p>Create your first workflow to get started.</p>
            <a href="{{ route('workflow.admin.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Create Workflow
            </a>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Scope</th>
                                <th scope="col">Trigger</th>
                                <th scope="col">Applies To</th>
                                <th scope="col">Steps</th>
                                <th scope="col">Active Tasks</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workflows as $workflow)
                                <tr>
                                    <td>{{ $workflow->id }}</td>
                                    <td>
                                        <a href="{{ route('workflow.admin.edit', $workflow->id) }}">{{ $workflow->name }}</a>
                                        @if($workflow->is_default)
                                            <span class="badge bg-primary ms-1">Default</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($workflow->scope_type) }}</span>
                                        @if($workflow->scope_id)
                                            <small class="text-muted">#{{ $workflow->scope_id }}</small>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($workflow->trigger_event) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $workflow->applies_to)) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $workflow->step_count }}</span>
                                    </td>
                                    <td>
                                        @if($workflow->active_task_count > 0)
                                            <span class="badge bg-warning text-dark">{{ $workflow->active_task_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($workflow->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('workflow.admin.edit', $workflow->id) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit workflow {{ $workflow->name }}">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('workflow.admin.delete', $workflow->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this workflow and all its steps and tasks?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete workflow {{ $workflow->name }}">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
