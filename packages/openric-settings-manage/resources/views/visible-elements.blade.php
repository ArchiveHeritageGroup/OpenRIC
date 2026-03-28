@extends('theme::layouts.1col')

@section('title', 'Visible Elements Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-eye me-2"></i>Visible Elements</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.visible-elements') }}">
        @csrf
        <p class="text-muted mb-3">Select which elements are visible on public and admin interfaces.</p>

        @foreach ($settings as $groupKey => $group)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">{{ $group['label'] ?? ucfirst(str_replace('_', ' ', $groupKey)) }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach ($group['elements'] ?? [] as $elementKey => $element)
                    <div class="col-md-4 col-lg-3 mb-2">
                        <div class="form-check">
                            <input type="hidden" name="elements[{{ $groupKey }}][{{ $elementKey }}]" value="0">
                            <input class="form-check-input" type="checkbox" name="elements[{{ $groupKey }}][{{ $elementKey }}]" id="el_{{ $groupKey }}_{{ $elementKey }}" value="1" {{ ($element['visible'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="el_{{ $groupKey }}_{{ $elementKey }}">{{ $element['label'] ?? $elementKey }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
    </form>
</div>
@endsection
