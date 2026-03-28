@extends('theme::layouts.2col')

@section('title', 'My Workflow Tasks')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-clipboard-list me-2" aria-hidden="true"></i>My Tasks
        </h1>
        <a href="{{ route('workflow.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Dashboard
        </a>
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

    {{-- Filter Tabs --}}
    <nav aria-label="Task status filter">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ !$currentStatus ? 'active' : '' }}"
                   href="{{ route('workflow.my-tasks') }}"
                   @if(!$currentStatus) aria-current="page" @endif>All Active</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $currentStatus === 'claimed' ? 'active' : '' }}"
                   href="{{ route('workflow.my-tasks', ['status' => 'claimed']) }}"
                   @if($currentStatus === 'claimed') aria-current="page" @endif>Claimed</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $currentStatus === 'in_progress' ? 'active' : '' }}"
                   href="{{ route('workflow.my-tasks', ['status' => 'in_progress']) }}"
                   @if($currentStatus === 'in_progress') aria-current="page" @endif>In Progress</a>
            </li>
        </ul>
    </nav>

    @if(count($tasks) === 0)
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fa-3x mb-3 opacity-50" aria-hidden="true"></i>
            <h4>No tasks assigned to you</h4>
            <p>Browse the <a href="{{ route('workflow.pool') }}">task pool</a> to claim available tasks.</p>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Step</th>
                                <th scope="col">Workflow</th>
                                <th scope="col">Entity</th>
                                <th scope="col">Status</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Decision</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr>
                                    <td>{{ $task->id }}</td>
                                    <td>
                                        <a href="{{ route('workflow.task', $task->id) }}">{{ $task->step_name }}</a>
                                    </td>
                                    <td>{{ $task->workflow_name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $task->object_type }}</span>
                                        @if($task->object_iri)
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="{{ $task->object_iri }}">{{ $task->object_iri }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->status === 'claimed')
                                            <span class="badge bg-info">Claimed</span>
                                        @elseif($task->status === 'in_progress')
                                            <span class="badge bg-primary">In Progress</span>
                                        @elseif($task->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->priority === 'high')
                                            <span class="badge bg-danger">High</span>
                                        @elseif($task->priority === 'low')
                                            <span class="badge bg-secondary">Low</span>
                                        @else
                                            <span class="badge bg-primary">Normal</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            @if($task->due_date < now()->toDateString())
                                                <span class="text-danger fw-bold">
                                                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $task->due_date }}
                                                </span>
                                            @else
                                                {{ $task->due_date }}
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->decision === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($task->decision === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-secondary">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('workflow.task', $task->id) }}" class="btn btn-sm btn-outline-primary" aria-label="View task {{ $task->id }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
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
