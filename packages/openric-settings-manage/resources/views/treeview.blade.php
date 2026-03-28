@extends('theme::layouts.1col')

@section('title', 'Treeview Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-sitemap me-2"></i>Treeview Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.treeview') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[treeview_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[treeview_enabled]" id="treeview_enabled" value="1" {{ ($settings['treeview_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="treeview_enabled">Enable Treeview Navigation</label>
                </div>
                <div class="mb-3">
                    <label for="treeview_max_depth" class="form-label">Maximum Depth</label>
                    <input type="number" name="settings[treeview_max_depth]" id="treeview_max_depth" class="form-control" value="{{ $settings['treeview_max_depth'] ?? 10 }}" min="1" max="50">
                </div>
                <div class="mb-3">
                    <label for="treeview_items_per_level" class="form-label">Items Per Level</label>
                    <input type="number" name="settings[treeview_items_per_level]" id="treeview_items_per_level" class="form-control" value="{{ $settings['treeview_items_per_level'] ?? 100 }}" min="10" max="1000">
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[treeview_show_counts]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[treeview_show_counts]" id="treeview_show_counts" value="1" {{ ($settings['treeview_show_counts'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="treeview_show_counts">Show Item Counts</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[treeview_expand_first]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[treeview_expand_first]" id="treeview_expand_first" value="1" {{ ($settings['treeview_expand_first'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="treeview_expand_first">Auto-expand First Level</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[treeview_lazy_load]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[treeview_lazy_load]" id="treeview_lazy_load" value="1" {{ ($settings['treeview_lazy_load'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="treeview_lazy_load">Lazy Load Child Nodes</label>
                </div>
                <div class="mb-3">
                    <label for="treeview_sort_order" class="form-label">Sort Order</label>
                    <select name="settings[treeview_sort_order]" id="treeview_sort_order" class="form-select">
                        <option value="alpha" {{ ($settings['treeview_sort_order'] ?? 'alpha') == 'alpha' ? 'selected' : '' }}>Alphabetical</option>
                        <option value="date" {{ ($settings['treeview_sort_order'] ?? '') == 'date' ? 'selected' : '' }}>By Date</option>
                        <option value="manual" {{ ($settings['treeview_sort_order'] ?? '') == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="identifier" {{ ($settings['treeview_sort_order'] ?? '') == 'identifier' ? 'selected' : '' }}>By Identifier</option>
                    </select>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[treeview_show_icons]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[treeview_show_icons]" id="treeview_show_icons" value="1" {{ ($settings['treeview_show_icons'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="treeview_show_icons">Show Type Icons</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
