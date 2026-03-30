@extends('theme::layouts.1col')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4"><i class="fas fa-cogs me-2"></i>Settings</h1>

    {{-- Standard scoped settings --}}
    @if ($scopeCards->isNotEmpty())
    <h5 class="mb-3">Standard Settings</h5>
    <div class="row mb-4">
        @foreach ($scopeCards as $card)
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas {{ $card->icon }} fa-2x text-primary mb-2"></i>
                    <h6 class="card-title">{{ $card->label }}</h6>
                    <p class="card-text small text-muted">{{ $card->description }}</p>
                    <span class="badge bg-secondary">{{ $card->count }} settings</span>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('settings.section', $card->key) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- OpenRiC grouped settings --}}
    @if ($openricGroups->isNotEmpty())
    <h5 class="mb-3">OpenRiC Settings</h5>
    <div class="row mb-4">
        @foreach ($openricGroups as $group)
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas {{ $groupIcons[$group->key] ?? 'fa-puzzle-piece' }} fa-2x text-primary mb-2"></i>
                    <h6 class="card-title">{{ $group->label }}</h6>
                    <span class="badge bg-secondary">{{ $group->count }} settings</span>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('settings.openric', $group->key) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Quick links to dedicated pages --}}
    <h5 class="mb-3">Quick Links</h5>
    <div class="row">
        @foreach ([
            ['route' => 'settings.system-info', 'icon' => 'fa-server', 'label' => 'System Information'],
            ['route' => 'settings.services', 'icon' => 'fa-heartbeat', 'label' => 'Services Monitor'],
            ['route' => 'settings.themes', 'icon' => 'fa-palette', 'label' => 'Theme Configuration'],
            ['route' => 'settings.error-log', 'icon' => 'fa-exclamation-triangle', 'label' => 'Error Log'],
            ['route' => 'settings.email', 'icon' => 'fa-envelope', 'label' => 'Email Settings'],
            ['route' => 'settings.security', 'icon' => 'fa-shield-alt', 'label' => 'Security'],
            ['route' => 'settings.plugins', 'icon' => 'fa-plug', 'label' => 'Plugins'],
        ] as $link)
        <div class="col-md-2 mb-3">
            <a href="{{ route($link['route']) }}" class="btn btn-outline-secondary w-100 py-3">
                <i class="fas {{ $link['icon'] }} d-block mb-1"></i>
                <small>{{ $link['label'] }}</small>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
