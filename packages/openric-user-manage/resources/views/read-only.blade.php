@extends('theme::layouts.1col')

@section('title', 'Read-Only Mode')
@section('body-class', 'view read-only')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="text-center mb-4">
                <i class="fas fa-lock fa-4x text-muted mb-3"></i>
                <h1>Read-Only Mode</h1>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>This archive is currently in read-only mode.</strong>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">What this means:</h5>
                    <ul class="mb-0">
                        <li>You can browse and search archival descriptions</li>
                        <li>You can view detailed information about items</li>
                        <li>You can add items to your clipboard</li>
                        <li>Administrative functions are temporarily disabled</li>
                        <li>Creating, editing, or deleting records is not available</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <a href="{{ route('home') }}" class="btn btn-primary">
                    <i class="fas fa-home"></i> Return to Home
                </a>
                <a href="{{ route('user.browse') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-search"></i> Browse Archives
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
