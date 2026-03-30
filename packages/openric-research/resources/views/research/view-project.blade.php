@extends('theme::layouts.1col')
@section('title', 'Project: ' . ($project->title ?? ''))
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $project->title }}</h2>
            <span class="badge bg-{{ ($project->status ?? '') === 'active' ? 'success' : 'secondary' }} fs-6">{{ ucfirst($project->status ?? 'planning') }}</span>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Details</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ ucfirst($project->project_type ?? 'personal') }}</dd>
                            @if($project->description)<dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $project->description }}</dd>@endif
                            @if($project->institution)<dt class="col-sm-4">Institution</dt><dd class="col-sm-8">{{ $project->institution }}</dd>@endif
                            @if($project->supervisor)<dt class="col-sm-4">Supervisor</dt><dd class="col-sm-8">{{ $project->supervisor }}</dd>@endif
                            @if($project->funding_source)<dt class="col-sm-4">Funding</dt><dd class="col-sm-8">{{ $project->funding_source }}</dd>@endif
                            @if($project->start_date)<dt class="col-sm-4">Start Date</dt><dd class="col-sm-8">{{ $project->start_date }}</dd>@endif
                            @if($project->expected_end_date)<dt class="col-sm-4">Expected End</dt><dd class="col-sm-8">{{ $project->expected_end_date }}</dd>@endif
                        </dl>
                    </div>
                </div>

                {{-- Milestones --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Milestones ({{ count($milestones) }})</h5></div>
                    <ul class="list-group list-group-flush">
                        @forelse($milestones as $m)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $m->title }} @if($m->due_date)<small class="text-muted">(due {{ $m->due_date }})</small>@endif</span>
                                <span class="badge bg-{{ ($m->status ?? '') === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($m->status ?? 'pending') }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No milestones.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Resources --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Resources ({{ count($resources) }})</h5></div>
                    <ul class="list-group list-group-flush">
                        @forelse($resources as $r)
                            <li class="list-group-item">
                                <strong>{{ $r->title ?? $r->resource_type ?? 'Resource' }}</strong>
                                @if($r->description)<br><small class="text-muted">{{ $r->description }}</small>@endif
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No resources linked.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                {{-- Collaborators --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">Collaborators ({{ count($collaborators) }})</h6></div>
                    <ul class="list-group list-group-flush">
                        @foreach($collaborators as $c)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $c->first_name }} {{ $c->last_name }}</span>
                                <span class="badge bg-secondary">{{ $c->role ?? 'member' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Activity --}}
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Recent Activity</h6></div>
                    <ul class="list-group list-group-flush">
                        @forelse(array_slice($activities, 0, 10) as $a)
                            <li class="list-group-item"><small>{{ $a->activity_type ?? '' }} - {{ $a->entity_title ?? '' }}<br><span class="text-muted">{{ $a->created_at }}</span></small></li>
                        @empty
                            <li class="list-group-item text-muted">No activity.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-3"><a href="{{ route('research.projects') }}" class="btn btn-outline-secondary">Back to Projects</a></div>
    </div>
</div>
@endsection
