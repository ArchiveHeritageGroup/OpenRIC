@extends('theme::layout')

@section('title', 'Templates')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-files me-2"></i>Form Templates</h4>
        </div>
        <a href="{{ route('forms.template.create') }}" class="atom-btn-white">
            <i class="bi bi-plus-circle me-1"></i>Create Template
        </a>
    </div>

    <div class="card">
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
                            @foreach($templates as $template)
                                <tr>
                                    <td><strong>{{ $template->name }}</strong></td>
                                    <td>{{ $template->form_type ?? 'Information Object' }}</td>
                                    <td>
                                        <a href="{{ route('forms.builder', $template->id) }}" class="atom-btn-white btn-sm me-1">Edit</a>
                                        <a href="{{ route('forms.preview', $template->id) }}" class="atom-btn-white btn-sm">Preview</a>
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
