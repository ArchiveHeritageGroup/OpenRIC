@extends('theme::layouts.2col')

@section('title', $isaar['5.1.2']['value'] ?? 'Person')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            {{ $isaar['5.1.2']['value'] ?? 'Person' }}
            <span class="badge bg-success">Person</span>
        </h1>
        <div class="d-flex gap-2">
            @include('theme::partials.view-switch')
            <a href="{{ route('persons.edit', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        </div>
    </div>

    @include('theme::partials.alerts')

    <div class="card mb-3">
        <div class="card-header bg-light"><h5 class="card-title mb-0">5.1 Identity Area</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">5.1.1 Type of entity</dt>
                <dd class="col-sm-8">Person</dd>
                <dt class="col-sm-4">5.1.2 Authorized form of name</dt>
                <dd class="col-sm-8">{{ $isaar['5.1.2']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.1.6 Identifiers</dt>
                <dd class="col-sm-8">{{ $isaar['5.1.6']['value'] ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><h5 class="card-title mb-0">5.2 Description Area</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">5.2.1 Dates of existence</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.1']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.2 History</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.2']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.3 Places</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.3']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.4 Legal status</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.4']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.5 Functions, occupations, activities</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.5']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.6 Mandates/sources of authority</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.6']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.7 Internal structures/genealogy</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.7']['value'] ?? '-' }}</dd>
                <dt class="col-sm-4">5.2.8 General context</dt>
                <dd class="col-sm-8">{{ $isaar['5.2.8']['value'] ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><h5 class="card-title mb-0">5.3 Relationships Area</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">5.3.1 Related entities</dt>
                <dd class="col-sm-8">{{ $isaar['5.3.1']['value'] ?? '-' }}</dd>
            </dl>
        </div>
    </div>
@endsection
