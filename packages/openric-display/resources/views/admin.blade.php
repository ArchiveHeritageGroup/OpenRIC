@extends('theme::layouts.1col')

@section('title', 'Display Management')

@section('content')
<div class="container-fluid py-3">
    <h2 class="mb-4">Display Management</h2>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Navigation tabs --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.index') ? 'active' : '' }}" href="{{ route('display.index') }}">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.profiles') ? 'active' : '' }}" href="{{ route('display.profiles') }}">Profiles</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.levels') ? 'active' : '' }}" href="{{ route('display.levels') }}">Levels</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.fields') ? 'active' : '' }}" href="{{ route('display.fields') }}">Fields</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.bulk.set.type') ? 'active' : '' }}" href="{{ route('display.bulk.set.type') }}">Bulk Set Type</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.browse.settings') ? 'active' : '' }}" href="{{ route('display.browse.settings') }}">Browse Settings</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('display.reindex') ? 'active' : '' }}" href="{{ route('display.reindex') }}">Reindex</a></li>
    </ul>

    {{-- Dashboard stats --}}
    @if(isset($stats))
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title">{{ number_format($stats['total_objects'] ?? 0) }}</h3>
                    <p class="card-text text-muted">Total Objects</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title">{{ number_format($stats['configured_objects'] ?? 0) }}</h3>
                    <p class="card-text text-muted">Configured Objects</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    @php $unconfigured = ($stats['total_objects'] ?? 0) - ($stats['configured_objects'] ?? 0); @endphp
                    <h3 class="card-title {{ $unconfigured > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($unconfigured) }}</h3>
                    <p class="card-text text-muted">Unconfigured Objects</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Type breakdown --}}
    @if(!empty($stats['by_type']))
    <div class="card mb-4">
        <div class="card-header fw-bold">Objects by GLAM Type</div>
        <div class="card-body">
            <div class="row">
                @foreach($stats['by_type'] as $typeRow)
                <div class="col-md-2 col-sm-4 mb-2 text-center">
                    <h5>{{ number_format($typeRow->count) }}</h5>
                    <span class="badge bg-primary">{{ ucfirst($typeRow->object_type) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Profiles section --}}
    @if(isset($profiles) && !isset($stats))
    <div class="card mb-4">
        <div class="card-header fw-bold">Display Profiles</div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Domain</th><th>Default</th><th>Sort</th><th>Description</th></tr>
                </thead>
                <tbody>
                @forelse($profiles as $p)
                <tr>
                    <td>{{ $p->name ?? '(unnamed)' }}</td>
                    <td><span class="badge bg-primary">{{ ucfirst($p->domain ?? '') }}</span></td>
                    <td>{{ ($p->is_default ?? false) ? 'Yes' : 'No' }}</td>
                    <td>{{ $p->sort_order ?? '' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($p->description ?? '', 80) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No profiles configured.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Levels section --}}
    @if(isset($levels) && isset($domains))
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Levels of Description</span>
            <div>
                <a href="{{ route('display.levels') }}" class="btn btn-sm {{ empty($currentDomain) ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                @foreach($domains as $d)
                <a href="{{ route('display.levels', ['domain' => $d]) }}" class="btn btn-sm {{ ($currentDomain ?? '') === $d ? 'btn-primary' : 'btn-outline-primary' }}">{{ ucfirst($d) }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Domain</th><th>Sort</th><th>Description</th></tr>
                </thead>
                <tbody>
                @forelse($levels as $lv)
                <tr>
                    <td>{{ $lv->name ?? '(unnamed)' }}</td>
                    <td><span class="badge bg-info text-dark">{{ $lv->domain ?? '' }}</span></td>
                    <td>{{ $lv->sort_order ?? '' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($lv->description ?? '', 80) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No levels configured.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Fields section --}}
    @if(isset($fields) && isset($fieldGroups))
    <div class="card mb-4">
        <div class="card-header fw-bold">Display Fields</div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Code</th><th>Group</th><th>Sort</th><th>Help Text</th></tr>
                </thead>
                <tbody>
                @forelse($fields as $f)
                <tr>
                    <td>{{ $f->name ?? '(unnamed)' }}</td>
                    <td class="text-muted small">{{ $f->code ?? '' }}</td>
                    <td><span class="badge bg-secondary">{{ $f->field_group ?? '' }}</span></td>
                    <td>{{ $f->sort_order ?? '' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($f->help_text ?? '', 60) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No fields configured.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Bulk Set Type section --}}
    @if(isset($collections) && isset($collectionTypes))
    <div class="card mb-4">
        <div class="card-header fw-bold">Bulk Set GLAM Type</div>
        <div class="card-body">
            <form method="POST" action="{{ route('display.bulk.set.type') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Collection</label>
                        <select name="parent_id" class="form-select" required>
                            <option value="">Select collection...</option>
                            @foreach($collections as $c)
                            <option value="{{ $c->id }}">{{ $c->title ?? $c->identifier ?? 'ID ' . $c->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">GLAM Type</label>
                        <select name="type" class="form-select" required>
                            @foreach(['archive','museum','gallery','library','dam','universal'] as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" onclick="return confirm('This will update the selected collection and ALL its children. Continue?')">
                            Apply to All Children
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Browse settings section --}}
    @if(isset($settings) && !isset($stats) && !isset($profiles) && !isset($fields))
    <div class="card mb-4">
        <div class="card-header fw-bold">Browse Settings</div>
        <div class="card-body">
            <form method="POST" action="{{ route('display.browse.settings') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="use_glam_browse" value="1" id="use_glam_browse" {{ ($settings['use_glam_browse'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="use_glam_browse">Use GLAM browse as default</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default sort field</label>
                            <select name="default_sort_field" class="form-select">
                                @foreach(['updated_at' => 'Date updated', 'title' => 'Title', 'identifier' => 'Identifier'] as $val => $lbl)
                                <option value="{{ $val }}" {{ ($settings['default_sort_field'] ?? 'updated_at') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default sort direction</label>
                            <select name="default_sort_direction" class="form-select">
                                <option value="asc" {{ ($settings['default_sort_direction'] ?? 'desc') === 'asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ ($settings['default_sort_direction'] ?? 'desc') === 'desc' ? 'selected' : '' }}>Descending</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Default view</label>
                            <select name="default_view" class="form-select">
                                <option value="list" {{ ($settings['default_view'] ?? 'list') === 'list' ? 'selected' : '' }}>List</option>
                                <option value="card" {{ ($settings['default_view'] ?? 'list') === 'card' ? 'selected' : '' }}>Card</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Items per page</label>
                            <input type="number" name="items_per_page" class="form-control" value="{{ $settings['items_per_page'] ?? 30 }}" min="10" max="100">
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_facets" value="1" id="show_facets" {{ ($settings['show_facets'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_facets">Show facets sidebar</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember_filters" value="1" id="remember_filters" {{ ($settings['remember_filters'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember_filters">Remember last-used filters</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Reindex section --}}
    @if(request()->routeIs('display.reindex'))
    <div class="card mb-4">
        <div class="card-header fw-bold">Reindex Display Cache</div>
        <div class="card-body">
            <p>Re-detect and save GLAM types for all objects in the database. This will force-refresh every object's type detection.</p>
            @if(isset($stats))
            <p><strong>{{ number_format($stats['total_objects'] ?? 0) }}</strong> objects to reindex, <strong>{{ number_format($stats['configured_objects'] ?? 0) }}</strong> currently configured.</p>
            @endif
            <form method="POST" action="{{ route('display.reindex') }}">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('This will re-detect types for ALL objects. This may take a while. Continue?')">
                    Start Reindex
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Quick links --}}
    <div class="card">
        <div class="card-header fw-bold">Quick Links</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <a href="{{ route('display.browse') }}" class="btn btn-outline-primary w-100">Browse Records</a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="{{ route('display.search') }}" class="btn btn-outline-primary w-100">Search</a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="{{ route('display.treeview') }}" class="btn btn-outline-primary w-100">Tree View</a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="{{ route('display.export.csv') }}" class="btn btn-outline-secondary w-100">Export All CSV</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
