@extends('theme::layouts.2col')

@section('title', 'Workflow Task Pool')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="fas fa-inbox me-2" aria-hidden="true"></i>Task Pool
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

    <p class="text-muted">Tasks available for claiming. Claim a task to assign it to yourself.</p>

    @if(count($tasks) === 0)
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
            No tasks available in the pool.
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
                                <th scope="col">Priority</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Created</th>
                                <th scope="col">Action</th>
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
                                    <td><small>{{ $task->created_at }}</small></td>
                                    <td>
                                        <form action="{{ route('workflow.task.claim', $task->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-hand-paper me-1" aria-hidden="true"></i>Claim
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
