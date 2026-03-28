@extends('theme::layouts.2col')

@section('title', $isadg['3.1.2']['value'] ?? 'Record Part')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            {{ $isadg['3.1.2']['value'] ?? 'Record Part' }}
            <span class="badge bg-warning text-dark">Part</span>
        </h1>
        <div class="d-flex gap-2">
            @include('theme::partials.view-switch')
            <a href="{{ route('record-parts.edit', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        </div>
    </div>

    @include('theme::partials.alerts')

    <div class="card mb-3">
        <div class="card-header bg-light"><h5 class="card-title mb-0">Identity Statement Area</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">3.1.1 Reference code</dt>
                <dd class="col-sm-8">{{ $isadg['3.1.1']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">3.1.2 Title</dt>
                <dd class="col-sm-8">{{ $isadg['3.1.2']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">3.1.5 Extent</dt>
                <dd class="col-sm-8">{{ $isadg['3.1.5']['value'] ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><h5 class="card-title mb-0">Content</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">3.3.1 Scope and content</dt>
                <dd class="col-sm-8">{{ $isadg['3.3.1']['value'] ?? '-' }}</dd>
            </dl>
        </div>
    </div>
@endsection
