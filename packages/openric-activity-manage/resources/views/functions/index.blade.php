@extends('theme::layouts.2col')
@section('title', 'Functions')
@section('sidebar') @include('theme::partials.sidebar') @endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Functions</h1>
    <a href="{{ route('functions.create') }}" class="btn btn-primary">Create Function</a>
</div>
@include('theme::partials.alerts')
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead><tr><th>Title</th><th>Identifier</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td><a href="{{ route('functions.show', ['iri' => urlencode($item['iri']['value'] ?? '')]) }}">{{ $item['title']['value'] ?? 'Untitled' }}</a></td>
                    <td>{{ $item['identifier']['value'] ?? '-' }}</td>
                    <td><a href="{{ route('functions.edit', ['iri' => urlencode($item['iri']['value'] ?? '')]) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted text-center">No functions found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection