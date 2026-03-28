@extends('theme::layouts.2col')

@section('title', 'Workflow Dashboard')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">
        <i class="fas fa-project-diagram me-2" aria-hidden="true"></i>Workflow Dashboard
    </h1>

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

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h2 class="display-6 text-primary mb-1">{{ $stats['my_tasks'] }}</h2>
                    <p class="text-muted mb-0">My Tasks</p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <a href="{{ route('workflow.my-tasks') }}" class="text-decoration-none">View all</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h2 class="display-6 text-info mb-1">{{ $stats['pool_tasks'] }}</h2>
                    <p class="text-muted mb-0">Pool Tasks</p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <a href="{{ route('workflow.pool') }}" class="text-decoration-none">Browse pool</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h2 class="display-6 text-success mb-1">{{ $stats['completed_today'] }}</h2>
                    <p class="text-muted mb-0">Completed Today</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h2 class="display-6 {{ $stats['overdue_tasks'] > 0 ? 'text-danger' : 'text-muted' }} mb-1">{{ $stats['overdue_tasks'] }}</h2>
                    <p class="text-muted mb-0">Overdue</p>
                </div>
                @if($stats['overdue_tasks'] > 0)
                    <div class="card-footer bg-white border-top-0">
                        <a href="{{ route('workflow.overdue') }}" class="text-danger text-decoration-none">View overdue</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        {{-- My Tasks Preview --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-1" aria-hidden="true"></i> My Tasks</h5>
                    <a href="{{ route('workflow.my-tasks') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0">
                    @if(count($myTasks) === 0)
                        <p class="text-muted text-center py-4 mb-0">No tasks assigned to you.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Step</th>
                                        <th scope="col">Workflow</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myTasks as $task)
                                        <tr>
                                            <td>
                                                <a href="{{ route('workflow.task', $task->id) }}">{{ $task->step_name }}</a>
                                            </td>
                                            <td>{{ $task->workflow_name }}</td>
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
                                                @if($task->priority === 'high')
                                                    <span class="badge bg-danger">High</span>
                                                @elseif($task->priority === 'low')
                                                    <span class="badge bg-secondary">Low</span>
                                                @else
                                                    <span class="badge bg-primary">Normal</span>
                                                @endif
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

        {{-- Pool Tasks Preview --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-inbox me-1" aria-hidden="true"></i> Task Pool</h5>
                    <a href="{{ route('workflow.pool') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0">
                    @if(count($poolTasks) === 0)
                        <p class="text-muted text-center py-4 mb-0">No tasks available in the pool.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Step</th>
                                        <th scope="col">Workflow</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($poolTasks as $task)
                                        <tr>
                                            <td>
                                                <a href="{{ route('workflow.task', $task->id) }}">{{ $task->step_name }}</a>
                                            </td>
                                            <td>{{ $task->workflow_name }}</td>
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
                                                <form action="{{ route('workflow.task.claim', $task->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-hand-paper" aria-hidden="true"></i>
                                                        <span class="visually-hidden">Claim task {{ $task->step_name }}</span>
                                                    </button>
                                                </form>
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
    </div>

    {{-- Recent Activity --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-1" aria-hidden="true"></i> Recent Activity</h5>
        </div>
        <div class="card-body p-0">
            @if(count($recentHistory) === 0)
                <p class="text-muted text-center py-4 mb-0">No recent workflow activity.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Time</th>
                                <th scope="col">User</th>
                                <th scope="col">Action</th>
                                <th scope="col">Workflow</th>
                                <th scope="col">Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentHistory as $entry)
                                <tr>
                                    <td><small>{{ $entry->performed_at }}</small></td>
                                    <td>{{ $entry->performer_name ?? 'System' }}</td>
                                    <td><span class="badge bg-secondary">{{ $entry->action }}</span></td>
                                    <td>{{ $entry->workflow_name ?? '-' }}</td>
                                    <td>
                                        @if($entry->comment)
                                            <small class="text-muted">{{ Str::limit($entry->comment, 60) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
