@extends('theme::layouts.1col')

@section('title', 'Condition Assessment Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Condition Assessment Templates</h1>
    <a href="{{ route('condition.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
    @foreach($templates as $template)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">{{ $template['name'] }}</h6></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Fields:</p>
                    <ul class="list-unstyled mb-2">
                        @foreach($template['fields'] as $field)
                            <li><span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($field)) }}</span></li>
                        @endforeach
                    </ul>
                    <p class="small text-muted mb-1">Condition Options:</p>
                    @foreach($template['condition_options'] as $opt)
                        <span class="badge bg-info me-1">{{ ucfirst($opt) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
