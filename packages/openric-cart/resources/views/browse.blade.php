@extends('openric-theme::layouts.1col')

@section('title', 'Browse Digital Items')

@section('content')
<div class="container mt-4">
    <h2><i class="fas fa-store me-2"></i>Digital Marketplace</h2>
    <p class="text-muted">Browse available digital items and publications.</p>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                    <h5>Digital Publications</h5>
                    <p class="text-muted small">E-books, journals, and documents</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Browse</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-image fa-3x text-success mb-3"></i>
                    <h5>Images & Photos</h5>
                    <p class="text-muted small">High-resolution photographs and artwork</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Browse</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-video fa-3x text-danger mb-3"></i>
                    <h5>Video & Audio</h5>
                    <p class="text-muted small">Documentary films and recordings</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Browse</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-archive fa-3x text-warning mb-3"></i>
                    <h5>Archives</h5>
                    <p class="text-muted small">Historical document collections</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Browse</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
