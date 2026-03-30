@extends('theme::layout')

@section('title', 'Forms Management')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Forms Management</h4>
            <p class="text-muted mb-0">Create and manage dynamic form templates</p>
        </div>
        <div>
            <a href="{{ route('forms.browse') }}" class="atom-btn-white me-2">Browse Templates</a>
            <a href="{{ route('forms.templates') }}" class="atom-btn-white me-2">Templates</a>
            <a href="{{ route('forms.template.create') }}" class="atom-btn-white">Create Template</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="mb-0 text-primary">{{ $stats['templates'] }}</h2>
                    <p class="text-muted mb-0">Templates</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="mb-0 text-success">{{ $stats['fields'] }}</h2>
                    <p class="text-muted mb-0">Form Fields</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="mb-0 text-info">{{ $stats['assignments'] }}</h2>
                    <p class="text-muted mb-0">Assignments</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Recent Templates</h6>
        </div>
        <div class="card-body p-0">
            @if($templates->isEmpty())
                <div class="p-4 text-center text-muted">
                    No templates yet. <a href="{{ route('forms.template.create') }}">Create one</a>.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates->take(10) as $template)
                                <tr>
                                    <td><strong>{{ $template->name }}</strong></td>
                                    <td>{{ $template->form_type ?? 'Information Object' }}</td>
                                    <td>
                                        <a href="{{ route('forms.builder', $template->id) }}" class="atom-btn-white btn-sm">Edit</a>
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
