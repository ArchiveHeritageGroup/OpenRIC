@extends('theme::layouts.2col')

@section('title', 'Task: ' . $task->step_name)

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-tasks me-2" aria-hidden="true"></i>Task #{{ $task->id }}: {{ $task->step_name }}
        </h1>
        <a href="{{ route('workflow.my-tasks') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>My Tasks
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

    <div class="row">
        {{-- Task Details --}}
        <div class="col-lg-8 mb-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Task Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Workflow</dt>
                        <dd class="col-sm-8">{{ $task->workflow_name }}</dd>

                        <dt class="col-sm-4">Step</dt>
                        <dd class="col-sm-8">{{ $task->step_name }}</dd>

                        <dt class="col-sm-4">Step Type</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $task->step_type)) }}</span>
                        </dd>

                        <dt class="col-sm-4">Action Required</dt>
                        <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $task->action_required)) }}</dd>

                        <dt class="col-sm-4">Entity Type</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">{{ $task->object_type }}</span>
                        </dd>

                        <dt class="col-sm-4">Entity IRI</dt>
                        <dd class="col-sm-8">
                            @if($task->object_iri)
                                <code>{{ $task->object_iri }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            @if($task->status === 'claimed')
                                <span class="badge bg-info">Claimed</span>
                            @elseif($task->status === 'in_progress')
                                <span class="badge bg-primary">In Progress</span>
                            @elseif($task->status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Priority</dt>
                        <dd class="col-sm-8">
                            @if($task->priority === 'high')
                                <span class="badge bg-danger">High</span>
                            @elseif($task->priority === 'low')
                                <span class="badge bg-secondary">Low</span>
                            @else
                                <span class="badge bg-primary">Normal</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Decision</dt>
                        <dd class="col-sm-8">
                            @if($task->decision === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($task->decision === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Assigned To</dt>
                        <dd class="col-sm-8">{{ $task->assigned_name ?? 'Unassigned' }}</dd>

                        <dt class="col-sm-4">Submitted By</dt>
                        <dd class="col-sm-8">{{ $task->submitted_name ?? '-' }}</dd>

                        <dt class="col-sm-4">Due Date</dt>
                        <dd class="col-sm-8">
                            @if($task->due_date)
                                @if($task->due_date < now()->toDateString())
                                    <span class="text-danger fw-bold">
                                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $task->due_date }} (overdue)
                                    </span>
                                @else
                                    {{ $task->due_date }}
                                @endif
                            @else
                                <span class="text-muted">No due date</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8">{{ $task->created_at }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Instructions --}}
            @if($task->instructions)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-1" aria-hidden="true"></i> Instructions</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $task->instructions }}</p>
                    </div>
                </div>
            @endif

            {{-- History Timeline --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-1" aria-hidden="true"></i> History</h5>
                </div>
                <div class="card-body">
                    @if(empty($task->history))
                        <p class="text-muted mb-0">No history recorded.</p>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($task->history as $entry)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-secondary me-2">{{ $entry->action }}</span>
                                            <strong>{{ $entry->performer_name ?? 'System' }}</strong>
                                            @if($entry->from_status && $entry->to_status)
                                                <span class="text-muted">
                                                    changed status from
                                                    <span class="badge bg-light text-dark border">{{ $entry->from_status }}</span>
                                                    to
                                                    <span class="badge bg-light text-dark border">{{ $entry->to_status }}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $entry->performed_at }}</small>
                                    </div>
                                    @if($entry->comment)
                                        <p class="mt-2 mb-0 text-muted small">
                                            <i class="fas fa-comment me-1" aria-hidden="true"></i>{{ $entry->comment }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action Panel --}}
        <div class="col-lg-4 mb-4">
            @if($task->status !== 'completed')
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        @if($task->assigned_to === null)
                            {{-- Unclaimed: offer claim --}}
                            <form action="{{ route('workflow.task.claim', $task->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 mb-2">
                                    <i class="fas fa-hand-paper me-1" aria-hidden="true"></i>Claim This Task
                                </button>
                            </form>
                        @elseif((int) $task->assigned_to === auth()->id())
                            {{-- Owned by current user --}}
                            @if($task->action_required === 'approve_reject')
                                {{-- Approve --}}
                                <form action="{{ route('workflow.task.approve', $task->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    <div class="mb-2">
                                        <label for="approve_comment" class="form-label">Comment (optional)</label>
                                        <textarea class="form-control" id="approve_comment" name="comment" rows="2" maxlength="5000"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check me-1" aria-hidden="true"></i>Approve
                                    </button>
                                </form>

                                {{-- Reject --}}
                                <form action="{{ route('workflow.task.reject', $task->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    <div class="mb-2">
                                        <label for="reject_comment" class="form-label">Reason for rejection <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="reject_comment" name="comment" rows="2" required maxlength="5000"></textarea>
                                        @error('comment')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-times me-1" aria-hidden="true"></i>Reject
                                    </button>
                                </form>
                            @elseif($task->action_required === 'acknowledge')
                                <form action="{{ route('workflow.task.approve', $task->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="comment" value="Acknowledged">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check me-1" aria-hidden="true"></i>Acknowledge
                                    </button>
                                </form>
                            @else
                                {{-- Review only --}}
                                <form action="{{ route('workflow.task.approve', $task->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    <div class="mb-2">
                                        <label for="review_comment" class="form-label">Review notes (optional)</label>
                                        <textarea class="form-control" id="review_comment" name="comment" rows="2" maxlength="5000"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check me-1" aria-hidden="true"></i>Mark Reviewed
                                    </button>
                                </form>
                            @endif

                            <hr>

                            {{-- Release --}}
                            <form action="{{ route('workflow.task.release', $task->id) }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label for="release_comment" class="form-label">Release comment (optional)</label>
                                    <textarea class="form-control" id="release_comment" name="comment" rows="2" maxlength="5000"></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-undo me-1" aria-hidden="true"></i>Release to Pool
                                </button>
                            </form>
                        @else
                            <p class="text-muted mb-0">This task is assigned to another user.</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Completed</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            Decision:
                            @if($task->decision === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($task->decision === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-secondary">{{ $task->decision }}</span>
                            @endif
                        </p>
                        @if($task->decision_comment)
                            <p class="text-muted small mt-2 mb-1">{{ $task->decision_comment }}</p>
                        @endif
                        @if($task->decision_at)
                            <p class="text-muted small mb-0">Decided: {{ $task->decision_at }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Publish Readiness Link --}}
            @if($task->object_iri)
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('workflow.publish-readiness', ['iri' => $task->object_iri]) }}" class="btn btn-outline-primary w-100">
                            <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i>Check Publish Readiness
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
