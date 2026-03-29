@extends('theme::layouts.1col')

@section('title', __('Spectrum Procedure') . ': ' . ($procedureDef['label'] ?? ucwords(str_replace('_', ' ', $procedureType ?? ''))))

@section('content')

@php
$procedureType = $procedureType ?? '';
$procedureDef = $procedureDef ?? [];
$resource = $resource ?? null;
$currentState = $currentState ?? null;
$workflowConfig = $workflowConfig ?? null;
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$users = $users ?? collect();
$statusColors = $statusColors ?? [];

$configData = $workflowConfig;
$steps = $configData['steps'] ?? [];
$states = $configData['states'] ?? [];
$transitions = $configData['transitions'] ?? [];

// Determine the current state name
$currentStateName = $currentState ?? ($configData['initial_state'] ?? 'pending');

// Available transitions from current state
$availableTransitions = [];
foreach ($transitions as $transKey => $transDef) {
    if (isset($transDef['from']) && in_array($currentStateName, $transDef['from'])) {
        $availableTransitions[$transKey] = $transDef;
    }
}
@endphp

<h1>
    <i class="fas {{ $procedureDef['icon'] ?? 'fa-cog' }} me-2"></i>
    {{ $procedureDef['label'] ?? ucwords(str_replace('_', ' ', $procedureType)) }}
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
        <li class="breadcrumb-item active">{{ $procedureDef['label'] ?? '' }}</li>
    </ol>
</nav>

<div class="row">
    <!-- Sidebar: procedure info -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ __('Procedure Info') }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">{{ $procedureDef['description'] ?? '' }}</p>
                <dl class="mb-0">
                    <dt>{{ __('Category') }}</dt>
                    <dd>{{ ucwords($procedureDef['category'] ?? 'general') }}</dd>
                    <dt>{{ __('Current State') }}</dt>
                    <dd>
                        <span class="badge bg-primary fs-6">{{ ucwords(str_replace('_', ' ', $currentStateName)) }}</span>
                    </dd>
                </dl>
            </div>
        </div>

        @if ($resource)
        <a href="{{ route('spectrum.index') }}?slug={{ $resource->slug ?? '' }}" class="btn btn-outline-secondary w-100 mb-3">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Spectrum Data') }}
        </a>
        @else
        <a href="{{ route('spectrum.general') }}" class="btn btn-outline-secondary w-100 mb-3">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to General Procedures') }}
        </a>
        @endif
    </div>

    <!-- Main content -->
    <div class="col-md-9">
        @if ($workflowConfig)

        <!-- Steps Progress -->
        @if (!empty($steps))
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ __('Procedure Steps') }}</h5></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @php
                    $stateIndex = array_search($currentStateName, $states);
                    @endphp
                    @foreach ($steps as $index => $step)
                        @php
                        $stepStatus = 'pending';
                        if ($index < $stateIndex) $stepStatus = 'completed';
                        elseif ($index == $stateIndex) $stepStatus = 'current';

                        $badgeClass = match($stepStatus) {
                            'completed' => 'bg-success',
                            'current' => 'bg-warning',
                            default => 'bg-secondary'
                        };
                        @endphp
                    <div class="text-center">
                        <span class="badge {{ $badgeClass }} d-block mb-1" style="min-width: 30px;">
                            {{ $step['order'] }}
                        </span>
                        <small class="d-block" style="max-width: 80px; font-size: 0.7rem;">
                            {{ $step['name'] }}
                        </small>
                    </div>
                    @if ($index < count($steps) - 1)
                    <div class="d-flex align-items-center" style="margin-top: -15px;">
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Available Actions -->
        @if ($canEdit && !empty($availableTransitions))
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ __('Available Actions') }}</h5></div>
            <div class="card-body">
                <form method="post" action="{{ $resource ? route('spectrum.workflow') : route('spectrum.general-workflow') }}" class="row g-3">
                    @csrf
                    @if ($resource)
                    <input type="hidden" name="slug" value="{{ $resource->slug ?? '' }}">
                    @endif
                    <input type="hidden" name="procedure_type" value="{{ $procedureType }}">
                    <input type="hidden" name="from_state" value="{{ $currentStateName }}">

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Action') }}</label>
                        <select name="transition_key" class="form-select" required>
                            <option value="">{{ __('Select action...') }}</option>
                            @foreach ($availableTransitions as $transKey => $transDef)
                            @php $isRestart = ($transKey === 'restart'); @endphp
                            <option value="{{ $transKey }}" data-to-state="{{ $transDef['to'] }}">
                                @if ($isRestart)
                                    &#x21bb; {{ __('Restart') }} &rarr; {{ ucwords(str_replace('_', ' ', $transDef['to'])) }}
                                @else
                                    {{ ucwords(str_replace('_', ' ', $transKey)) }} &rarr; {{ ucwords(str_replace('_', ' ', $transDef['to'])) }}
                                @endif
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Assign to') }}</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->username ?? $user->authorized_form_of_name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <input type="text" name="note" class="form-control" placeholder="{{ __('Optional') }}">
                    </div>

                    <div class="col-12">
                        @php
                        $hasRestart = isset($availableTransitions['restart']);
                        $hasOnlyRestart = $hasRestart && count($availableTransitions) === 1;
                        @endphp
                        <button type="submit" class="btn {{ $hasOnlyRestart ? 'btn-warning' : 'btn-primary' }}">
                            @if ($hasOnlyRestart)
                            <i class="fas fa-redo me-1"></i> {{ __('Restart Procedure') }}
                            @else
                            <i class="fas fa-play me-1"></i> {{ __('Execute Action') }}
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @elseif (!$canEdit)
        <div class="alert alert-info">
            {{ __('You do not have permission to modify this workflow.') }}
        </div>
        @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            {{ __('This procedure has been completed.') }}
        </div>
        @endif

        <!-- History -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('Activity History') }}</h6>
            </div>
            <div class="card-body">
                @if (empty($history))
                <div class="alert alert-info mb-0">
                    {{ __('No activity recorded yet. Use the actions above to start the workflow.') }}
                </div>
                @else
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('From') }}</th>
                            <th>{{ __('To') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $event)
                        <tr>
                            <td><small>{{ is_object($event) ? $event->created_at : ($event['created_at'] ?? '') }}</small></td>
                            <td>
                                @php $transKey = is_object($event) ? $event->transition_key : ($event['transition_key'] ?? ''); @endphp
                                @if ($transKey === 'restart')
                                <span class="text-warning"><i class="fas fa-redo me-1"></i>{{ __('Restart') }}</span>
                                @else
                                {{ ucwords(str_replace('_', ' ', $transKey)) }}
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', is_object($event) ? $event->from_state : ($event['from_state'] ?? ''))) }}</span></td>
                            <td><span class="badge bg-primary">{{ ucwords(str_replace('_', ' ', is_object($event) ? $event->to_state : ($event['to_state'] ?? ''))) }}</span></td>
                            <td><small>{{ is_object($event) ? ($event->user_name ?? '') : ($event['user_name'] ?? '') }}</small></td>
                            <td><small>{{ is_object($event) ? ($event->note ?? '') : ($event['note'] ?? '') }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ __('No workflow configuration found for this procedure type.') }}
            <br><small>{{ __('An administrator needs to configure workflow steps for: ') }}{{ $procedureType }}</small>
        </div>
        @endif
    </div>
</div>

@endsection
