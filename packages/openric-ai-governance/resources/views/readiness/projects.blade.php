@extends('theme::layouts.1col')

@section('title', 'AI Projects')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-rocket me-2"></i>AI Projects</h1>
        <a href="{{ route('ai-governance.projects.create') }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>New Project
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Checklist</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr>
                        <td><strong>{{ $project->project_name }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $project->status === 'approved' ? 'success' : ($project->status === 'pending_approval' ? 'warning' : ($project->status === 'rejected' ? 'danger' : 'secondary')) }}">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $checks = 0;
                                if($project->corpus_completeness_documented) $checks++;
                                if($project->metadata_minimum_met) $checks++;
                                if($project->access_rules_structured) $checks++;
                                if($project->derivatives_prepared) $checks++;
                                if($project->evaluation_plan_approved) $checks++;
                                if($project->human_review_workflow_active) $checks++;
                            @endphp
                            {{ $checks }}/6
                        </td>
                        <td><small>{{ $project->created_at }}</small></td>
                        <td>
                            <a href="{{ route('ai-governance.projects.show', $project->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No projects defined.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $projects->links() }}
        </div>
    </div>
</div>
@endsection
