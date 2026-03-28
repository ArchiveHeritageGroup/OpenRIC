@extends('theme::layouts.2col')

@section('title', 'Overdue Tasks')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="fas fa-exclamation-triangle text-danger me-2" aria-hidden="true"></i>Overdue Tasks
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

    @if(count($tasks) === 0)
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-1" aria-hidden="true"></i>
            No overdue tasks found.
        </div>
    @else
        <div class="alert alert-warning" role="alert">
            <strong>{{ count($tasks) }}</strong> task(s) are past their due date.
        </div>

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
                                <th scope="col">Assigned To</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Days Overdue</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                @php
                                    $daysOverdue = (int) now()->diffInDays(\Carbon\Carbon::parse($task->due_date));
                                @endphp
                                <tr>
                                    <td>{{ $task->id }}</td>
                                    <td>
                                        <a href="{{ route('workflow.task', $task->id) }}">{{ $task->step_name }}</a>
                                    </td>
                                    <td>{{ $task->workflow_name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $task->object_type }}</span>
                                        @if($task->object_iri)
                                            <small class="text-muted d-block text-truncate" style="max-width: 180px;" title="{{ $task->object_iri }}">{{ $task->object_iri }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $task->assigned_name ?? $task->assigned_username ?? 'Unassigned' }}</td>
                                    <td class="text-danger fw-bold">{{ $task->due_date }}</td>
                                    <td>
                                        @if($daysOverdue > 7)
                                            <span class="badge bg-danger">{{ $daysOverdue }} days</span>
                                        @elseif($daysOverdue > 3)
                                            <span class="badge bg-warning text-dark">{{ $daysOverdue }} days</span>
                                        @else
                                            <span class="badge bg-info">{{ $daysOverdue }} days</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->status === 'claimed')
                                            <span class="badge bg-info">Claimed</span>
                                        @elseif($task->status === 'in_progress')
                                            <span class="badge bg-primary">In Progress</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
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
