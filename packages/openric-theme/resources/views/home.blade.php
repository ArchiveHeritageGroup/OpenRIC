@extends('theme::layouts.1col')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h3 mb-4">OpenRiC Dashboard</h1>

    @include('theme::partials.alerts')

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Triplestore</h5>
                    <p class="display-6 mb-0" id="triple-count">{{ $tripleCount ?? '...' }}</p>
                    <p class="text-muted small">triples</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Fuseki</h5>
                    <p class="mb-0">
                        @if($fusekiOnline ?? false)
                            <span class="badge bg-success">Online</span>
                        @else
                            <span class="badge bg-danger">Offline</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">View Mode</h5>
                    <p class="mb-0">
                        <span class="badge bg-info">{{ ucfirst(session('openric_view_mode', config('openric.default_view', 'ric'))) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action">Create Record Set</a>
                    <a href="#" class="list-group-item list-group-item-action">Create Agent</a>
                    <a href="#" class="list-group-item list-group-item-action">Search</a>
                    <a href="{{ url('/graph/overview') }}" class="list-group-item list-group-item-action"><i class="bi bi-share me-2"></i>Graph Explorer</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">About OpenRiC</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">OpenRiC is the first open-source platform to implement RiC-O as a native storage and CRUD layer, with traditional archival standards rendered as views from the same graph.</p>
                    <p class="card-text small text-muted">RiC-O 1.1 | Laravel 12 | Apache Jena Fuseki</p>
                </div>
            </div>
        </div>
    </div>
@endsection
