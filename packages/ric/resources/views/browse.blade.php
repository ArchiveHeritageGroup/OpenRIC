@extends('theme::layouts.master')

@section('title', __('Browse') . ' — ' . __('OpenRiC'))

@section('layout-content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2 mb-3">
                <i class="fas fa-compass me-2"></i>{{ __('Browse') }}
            </h1>
            <p class="text-muted">{{ __('Explore archival records, authority records, and more.') }}</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Archival Descriptions --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                            <i class="fas fa-folder-open text-primary fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">{{ __('Archival Descriptions') }}</h5>
                            <small class="text-muted">{{ __('Records & Record Sets') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Browse archival descriptions organized hierarchically, including files, items, and series.') }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-primary rounded-pill">
                            {{ number_format($counts['recordsets']) }} {{ __('records') }}
                        </span>
                        <a href="{{ url('/browse/records') }}" class="btn btn-sm btn-outline-primary">
                            {{ __('Browse') }} <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Authority Records --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                            <i class="fas fa-users text-success fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">{{ __('Authority Records') }}</h5>
                            <small class="text-muted">{{ __('Persons & Corporate Bodies') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Explore authority records including persons, corporate bodies, and families.') }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-success rounded-pill">
                            {{ number_format($counts['persons'] + $counts['corporate_bodies'] + $counts['families']) }} {{ __('records') }}
                        </span>
                        <a href="{{ url('/persons') }}" class="btn btn-sm btn-outline-success">
                            {{ __('Browse') }} <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Places --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3 me-3">
                            <i class="fas fa-map-marker-alt text-info fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">{{ __('Places') }}</h5>
                            <small class="text-muted">{{ __('Geographic Locations') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Browse geographic places associated with archival records and events.') }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-info rounded-pill">
                            {{ number_format($counts['places']) }} {{ __('places') }}
                        </span>
                        <a href="{{ url('/places') }}" class="btn btn-sm btn-outline-info">
                            {{ __('Browse') }} <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activities --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3 me-3">
                            <i class="fas fa-tasks text-warning fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">{{ __('Activities') }}</h5>
                            <small class="text-muted">{{ __('Functions & Mandates') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Browse activities, functions, and mandates that relate to records.') }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-warning rounded-pill">
                            {{ number_format($counts['activities']) }} {{ __('activities') }}
                        </span>
                        <a href="{{ url('/activities') }}" class="btn btn-sm btn-outline-warning">
                            {{ __('Browse') }} <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Digital Objects --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 rounded-3 p-3 me-3">
                            <i class="fas fa-file-image text-danger fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">{{ __('Digital Objects') }}</h5>
                            <small class="text-muted">{{ __('Images, Documents & Media') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Browse digitized materials including photographs, documents, and media files.') }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-danger rounded-pill">
                            {{ number_format($counts['instantiations']) }} {{ __('objects') }}
                        </span>
                        <a href="{{ url('/instantiations') }}" class="btn btn-sm btn-outline-danger">
                            {{ __('Browse') }} <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Graph Explorer --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-secondary bg-opacity-10 rounded-3 p-3 me-3">
                            <i class="fas fa-project-diagram text-secondary fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">{{ __('Graph Explorer') }}</h5>
                            <small class="text-muted">{{ __('Visualize RiC Relations') }}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted small">
                        {{ __('Explore the RiC-O ontology graph showing relationships between entities.') }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge bg-secondary rounded-pill">
                            <i class="fas fa-share-nodes me-1"></i> {{ __('Graph View') }}
                        </span>
                        <a href="{{ url('/admin/ric/explorer') }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('Explore') }} <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
