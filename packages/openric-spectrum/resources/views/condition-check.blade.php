@extends('theme::layouts.1col')

@section('title', __('Condition Check'))

@section('content')

@php
$resource = $resource ?? null;
$conditionCheck = $conditionCheck ?? null;
$conditionChecks = $conditionChecks ?? [];
$photos = $photos ?? [];
$photosByType = $photosByType ?? [];
$photoTypes = $photoTypes ?? [
    'overall' => 'Overall View',
    'detail'  => 'Detail',
    'damage'  => 'Damage/Deterioration',
    'before'  => 'Before Treatment',
    'after'   => 'After Treatment',
    'other'   => 'Other',
];
$stats = $stats ?? ['total_checks' => 0, 'critical' => 0, 'poor' => 0];

$conditionOptions = [
    'excellent' => ['label' => 'Excellent', 'color' => 'success', 'description' => 'No visible damage or deterioration'],
    'good'      => ['label' => 'Good', 'color' => 'info', 'description' => 'Minor signs of age, no active deterioration'],
    'fair'      => ['label' => 'Fair', 'color' => 'warning', 'description' => 'Some damage or deterioration present'],
    'poor'      => ['label' => 'Poor', 'color' => 'danger', 'description' => 'Significant damage requiring attention'],
    'critical'  => ['label' => 'Critical', 'color' => 'dark', 'description' => 'Severe damage, immediate intervention needed'],
];

$currentCondition = $conditionCheck['overall_condition'] ?? '';
@endphp

<h1 class="h3 mb-4">
    <i class="fas fa-heartbeat me-2"></i>{{ __('Condition Check') }}
    @if ($resource)
        <small class="text-muted">- {{ $resource->title ?? $resource->slug ?? '' }}</small>
    @endif
</h1>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('spectrum.dashboard') }}">{{ __('Spectrum Dashboard') }}</a></li>
        @if ($resource)
        <li class="breadcrumb-item"><a href="{{ route('spectrum.index') }}?slug={{ $resource->slug ?? '' }}">{{ $resource->title ?? $resource->slug ?? '' }}</a></li>
        @endif
        <li class="breadcrumb-item"><a href="{{ route('spectrum.condition-admin') }}">{{ __('Condition Management') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Condition Check') }}</li>
    </ol>
</nav>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <!-- Statistics Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>{{ __('Statistics') }}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('Total Checks') }}</span>
                    <span class="badge bg-primary">{{ $stats['total_checks'] }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('Critical') }}</span>
                    <span class="badge bg-danger">{{ $stats['critical'] }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>{{ __('Poor') }}</span>
                    <span class="badge bg-warning text-dark">{{ $stats['poor'] }}</span>
                </div>
            </div>
        </div>

        <!-- Previous Checks -->
        @if (!empty($conditionChecks))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>{{ __('Previous Checks') }}</h5>
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($conditionChecks as $cc)
                @php $ccArr = is_object($cc) ? (array)$cc : $cc; @endphp
                <li class="list-group-item d-flex justify-content-between align-items-center {{ ($conditionCheck && ($conditionCheck['id'] ?? null) == ($ccArr['id'] ?? null)) ? 'active' : '' }}">
                    <a href="{{ route('spectrum.condition-photos') }}?slug={{ $resource->slug ?? '' }}&condition_id={{ $ccArr['id'] ?? '' }}"
                       class="{{ ($conditionCheck && ($conditionCheck['id'] ?? null) == ($ccArr['id'] ?? null)) ? 'text-white' : '' }} text-decoration-none">
                        <small>{{ $ccArr['check_date'] ?? '' }}</small>
                        <br>
                        @php $cond = $ccArr['overall_condition'] ?? 'unknown'; @endphp
                        <span class="badge bg-{{ $conditionOptions[$cond]['color'] ?? 'secondary' }}">{{ ucfirst($cond) }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Quick Links -->
        <div class="card mb-4">
            <div class="card-body">
                <a href="{{ route('spectrum.condition-admin') }}" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-cog me-1"></i>{{ __('Condition Management') }}
                </a>
                <a href="{{ route('spectrum.condition-risk') }}" class="btn btn-outline-danger w-100 mb-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>{{ __('Risk Assessment') }}
                </a>
                @if ($resource)
                <a href="{{ route('spectrum.condition-photos') }}?slug={{ $resource->slug ?? '' }}" class="btn btn-outline-info w-100">
                    <i class="fas fa-camera me-1"></i>{{ __('Condition Photos') }}
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9">
        @if ($conditionCheck)
        <!-- Current Check Details -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>{{ __('Condition Check Details') }}</h5>
                <span class="badge bg-{{ $conditionOptions[$currentCondition]['color'] ?? 'secondary' }} fs-6">
                    {{ ucfirst($currentCondition ?: 'Not Assessed') }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <dt>{{ __('Reference') }}</dt>
                        <dd>{{ $conditionCheck['condition_check_reference'] ?? '-' }}</dd>
                    </div>
                    <div class="col-md-4">
                        <dt>{{ __('Check Date') }}</dt>
                        <dd>{{ $conditionCheck['check_date'] ?? '-' }}</dd>
                    </div>
                    <div class="col-md-4">
                        <dt>{{ __('Checked By') }}</dt>
                        <dd>{{ $conditionCheck['checked_by'] ?? $conditionCheck['assessor'] ?? '-' }}</dd>
                    </div>
                </div>

                @if (!empty($conditionCheck['condition_notes'] ?? $conditionCheck['notes'] ?? ''))
                <div class="mb-3">
                    <dt>{{ __('Notes') }}</dt>
                    <dd>{{ $conditionCheck['condition_notes'] ?? $conditionCheck['notes'] ?? '' }}</dd>
                </div>
                @endif

                @if (!empty($conditionCheck['next_check_date']))
                <div class="mb-3">
                    <dt>{{ __('Next Check Due') }}</dt>
                    <dd>
                        {{ $conditionCheck['next_check_date'] }}
                        @if (strtotime($conditionCheck['next_check_date']) < time())
                        <span class="badge bg-danger ms-2">{{ __('Overdue') }}</span>
                        @endif
                    </dd>
                </div>
                @endif
            </div>
        </div>

        <!-- Condition Assessment Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>{{ __('Record Condition Assessment') }}</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('spectrum.condition-admin') }}">
                    @csrf
                    <input type="hidden" name="condition_check_id" value="{{ $conditionCheck['id'] ?? '' }}">
                    @if ($resource)
                    <input type="hidden" name="slug" value="{{ $resource->slug ?? '' }}">
                    @endif

                    <!-- Overall Condition -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('Overall Condition') }}</label>
                        <div class="row g-2">
                            @foreach ($conditionOptions as $key => $opt)
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="overall_condition" id="cond_{{ $key }}" value="{{ $key }}"
                                        {{ $currentCondition === $key ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cond_{{ $key }}">
                                        <span class="badge bg-{{ $opt['color'] }}">{{ $opt['label'] }}</span>
                                        <br><small class="text-muted">{{ $opt['description'] }}</small>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Detailed Condition Fields -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Surface Condition') }}</label>
                            <select name="surface_condition" class="form-select">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="clean" {{ ($conditionCheck['surface_condition'] ?? '') === 'clean' ? 'selected' : '' }}>{{ __('Clean') }}</option>
                                <option value="dusty" {{ ($conditionCheck['surface_condition'] ?? '') === 'dusty' ? 'selected' : '' }}>{{ __('Dusty') }}</option>
                                <option value="stained" {{ ($conditionCheck['surface_condition'] ?? '') === 'stained' ? 'selected' : '' }}>{{ __('Stained') }}</option>
                                <option value="corroded" {{ ($conditionCheck['surface_condition'] ?? '') === 'corroded' ? 'selected' : '' }}>{{ __('Corroded') }}</option>
                                <option value="flaking" {{ ($conditionCheck['surface_condition'] ?? '') === 'flaking' ? 'selected' : '' }}>{{ __('Flaking') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Structural Integrity') }}</label>
                            <select name="structural_integrity" class="form-select">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="intact" {{ ($conditionCheck['structural_integrity'] ?? '') === 'intact' ? 'selected' : '' }}>{{ __('Intact') }}</option>
                                <option value="minor_damage" {{ ($conditionCheck['structural_integrity'] ?? '') === 'minor_damage' ? 'selected' : '' }}>{{ __('Minor Damage') }}</option>
                                <option value="cracked" {{ ($conditionCheck['structural_integrity'] ?? '') === 'cracked' ? 'selected' : '' }}>{{ __('Cracked') }}</option>
                                <option value="broken" {{ ($conditionCheck['structural_integrity'] ?? '') === 'broken' ? 'selected' : '' }}>{{ __('Broken') }}</option>
                                <option value="fragmented" {{ ($conditionCheck['structural_integrity'] ?? '') === 'fragmented' ? 'selected' : '' }}>{{ __('Fragmented') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Environmental Damage') }}</label>
                            <select name="environmental_damage" class="form-select">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="none" {{ ($conditionCheck['environmental_damage'] ?? '') === 'none' ? 'selected' : '' }}>{{ __('None') }}</option>
                                <option value="light" {{ ($conditionCheck['environmental_damage'] ?? '') === 'light' ? 'selected' : '' }}>{{ __('Light Damage') }}</option>
                                <option value="water" {{ ($conditionCheck['environmental_damage'] ?? '') === 'water' ? 'selected' : '' }}>{{ __('Water Damage') }}</option>
                                <option value="fire" {{ ($conditionCheck['environmental_damage'] ?? '') === 'fire' ? 'selected' : '' }}>{{ __('Fire Damage') }}</option>
                                <option value="pest" {{ ($conditionCheck['environmental_damage'] ?? '') === 'pest' ? 'selected' : '' }}>{{ __('Pest Damage') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Handling Requirements') }}</label>
                            <select name="handling_requirements" class="form-select">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="standard" {{ ($conditionCheck['handling_requirements'] ?? '') === 'standard' ? 'selected' : '' }}>{{ __('Standard') }}</option>
                                <option value="fragile" {{ ($conditionCheck['handling_requirements'] ?? '') === 'fragile' ? 'selected' : '' }}>{{ __('Fragile') }}</option>
                                <option value="specialist" {{ ($conditionCheck['handling_requirements'] ?? '') === 'specialist' ? 'selected' : '' }}>{{ __('Specialist Only') }}</option>
                                <option value="do_not_handle" {{ ($conditionCheck['handling_requirements'] ?? '') === 'do_not_handle' ? 'selected' : '' }}>{{ __('Do Not Handle') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Condition Notes -->
                    <div class="mb-3">
                        <label class="form-label">{{ __('Condition Notes') }}</label>
                        <textarea name="condition_notes" class="form-control" rows="4" placeholder="{{ __('Describe the condition in detail...') }}">{{ $conditionCheck['condition_notes'] ?? $conditionCheck['notes'] ?? '' }}</textarea>
                    </div>

                    <!-- Conservation Recommendations -->
                    <div class="mb-3">
                        <label class="form-label">{{ __('Conservation Recommendations') }}</label>
                        <textarea name="conservation_recommendations" class="form-control" rows="3" placeholder="{{ __('Recommended conservation actions...') }}">{{ $conditionCheck['conservation_recommendations'] ?? '' }}</textarea>
                    </div>

                    <!-- Next Check Date -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Next Check Date') }}</label>
                            <input type="date" name="next_check_date" class="form-control" value="{{ $conditionCheck['next_check_date'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Priority') }}</label>
                            <select name="priority" class="form-select">
                                <option value="low" {{ ($conditionCheck['priority'] ?? '') === 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                                <option value="medium" {{ ($conditionCheck['priority'] ?? 'medium') === 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                                <option value="high" {{ ($conditionCheck['priority'] ?? '') === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                <option value="urgent" {{ ($conditionCheck['priority'] ?? '') === 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>{{ __('Save Condition Check') }}
                        </button>
                        <a href="{{ route('spectrum.condition-admin') }}" class="btn btn-outline-secondary">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Photos Section -->
        @if (!empty($photos))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-camera me-2"></i>{{ __('Condition Photos') }} ({{ count($photos) }})</h5>
            </div>
            <div class="card-body">
                @foreach ($photoTypes as $typeKey => $typeLabel)
                    @if (!empty($photosByType[$typeKey]))
                    <h6 class="text-muted mt-3">{{ $typeLabel }}</h6>
                    <div class="row g-2 mb-3">
                        @foreach ($photosByType[$typeKey] as $photo)
                        <div class="col-md-3">
                            <div class="card h-100">
                                <img src="{{ $photo['file_path'] ?? '' }}" class="card-img-top" alt="{{ $photo['caption'] ?? $typeLabel }}" style="height: 150px; object-fit: cover;">
                                <div class="card-body p-2">
                                    <small class="text-muted">{{ $photo['caption'] ?? '' }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        @else
        <!-- No condition check found -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            {{ __('No condition check record found. A new one will be created when you perform a condition check on this object.') }}
        </div>
        @endif
    </div>
</div>

@endsection
