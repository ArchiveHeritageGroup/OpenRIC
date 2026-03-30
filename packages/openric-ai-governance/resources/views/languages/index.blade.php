@extends('theme::layouts.1col')
@section('title', 'Language AI Settings')
@section('content')
<div class="container-fluid py-4">
    <h1><i class="fas fa-language me-2"></i>Language AI Settings</h1>
    <a href="{{ route('ai-governance.languages.create') }}" class="btn btn-primary mb-3">Add Language</a>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Code</th><th>Language</th><th>AI</th><th>Translation</th><th>Embedding</th><th>Warning</th><th></th></tr></thead>
                <tbody>
                    @forelse($languages as $lang)
                    <tr>
                        <td><code>{{ $lang->language_code }}</code></td>
                        <td>{{ $lang->language_name }}</td>
                        <td>{!! $lang->ai_allowed ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td>{!! $lang->translation_allowed ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td>{!! $lang->embedding_enabled ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td><small>{{ Str::limit($lang->access_warning, 30) }}</small></td>
                        <td><a href="{{ route('ai-governance.languages.edit', $lang->language_code) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No language settings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
