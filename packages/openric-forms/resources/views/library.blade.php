@extends('theme::layout')

@section('title', 'Template Library')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Template Library</h4>
            <p class="text-muted mb-0">Pre-built form templates based on archival standards</p>
        </div>
        <a href="{{ route('forms.templates') }}" class="atom-btn-white">Back to Templates</a>
    </div>

    <div class="row">
        @foreach($library as $item)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">{{ $item['name'] }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">{{ $item['description'] }}</p>
                        <span class="badge bg-secondary">{{ $item['fields'] }} fields</span>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-outline-primary btn-sm w-100" disabled>Use Template</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
