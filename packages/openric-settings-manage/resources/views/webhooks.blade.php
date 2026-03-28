@extends('theme::layouts.1col')

@section('title', 'Webhooks Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-plug me-2"></i>Webhooks</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-0">Configure Webhooks settings here. This section will be populated once the corresponding service tables are created.</p>
        </div>
    </div>
</div>
@endsection
