{{-- Landing Page Editor / Page Builder -- adapted from Heratio ahg-landing-page::edit --}}
@extends('theme::layouts.1col')

@section('title', 'Edit Landing Page')

@section('content')
<div class="landing-page-builder">
  {{-- Header Toolbar --}}
  <div class="builder-toolbar bg-dark text-white py-2 px-3 d-flex align-items-center justify-content-between sticky-top">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('landing-page.list') }}" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <h5 class="mb-0">{{ e($page->name) }}</h5>
      @if ($page->is_default ?? false)
        <span class="badge bg-primary">Default</span>
      @endif
      @if (!($page->is_active ?? true))
        <span class="badge bg-warning text-dark">Inactive</span>
      @endif
    </div>

    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-outline-light btn-sm" id="btn-preview"
              data-url="{{ route('landing-page.show', $page->slug) }}">
        <i class="bi bi-eye"></i> Preview
      </button>
      <button type="button" class="btn btn-outline-light btn-sm" id="btn-settings"
              data-bs-toggle="offcanvas" data-bs-target="#pageSettingsPanel">
        <i class="bi bi-gear"></i> Settings
      </button>
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button"
                data-bs-toggle="dropdown">
          <i class="bi bi-clock-history"></i> Versions
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          @if (count($versions) > 0)
            @foreach ($versions as $version)
              <li>
                <a class="dropdown-item version-restore" href="#" data-version-id="{{ $version->id }}">
                  <small class="text-muted">v{{ $version->version_number }}</small>
                  {{ $version->status }}
                  <br>
                  <small>{{ \Carbon\Carbon::parse($version->created_at)->format('M j, Y g:i A') }}</small>
                </a>
              </li>
            @endforeach
          @else
            <li><span class="dropdown-item text-muted">No versions yet</span></li>
          @endif
        </ul>
      </div>
      <button type="button" class="btn btn-info btn-sm" id="btn-save-draft">
        <i class="bi bi-save"></i> Save Draft
      </button>
      <button type="button" class="btn btn-success btn-sm" id="btn-publish">
        <i class="bi bi-check-circle"></i> Publish
      </button>
    </div>
  </div>

  <div class="builder-main d-flex">
    {{-- Block Palette (Left Sidebar) --}}
    <div class="builder-palette bg-light border-end" style="width: 280px; min-height: calc(100vh - 56px);">
      <div class="p-3">
        <h6 class="text-uppercase text-muted small mb-3">+ Add Block</h6>

        <div class="block-types" id="block-palette">
          @php
          $categories = [
              'Layout' => ['header_section', 'footer_section', 'row_1_col', 'row_2_col', 'row_3_col', 'divider', 'spacer'],
              'Content' => ['hero_banner', 'text_content', 'image_carousel'],
              'Data' => ['search_box', 'browse_panels', 'recent_items', 'featured_items', 'statistics', 'holdings_list'],
              'Navigation' => ['quick_links', 'repository_spotlight', 'map_block', 'copyright_bar', 'glam_browser'],
          ];
          @endphp

          @foreach ($categories as $catName => $catBlocks)
            <div class="block-category mb-2">
              <button class="btn btn-sm btn-outline-secondary w-100 text-start collapsed"
                      type="button" data-bs-toggle="collapse"
                      data-bs-target="#cat-{{ strtolower($catName) }}">
                {{ $catName }} <i class="bi bi-chevron-down float-end"></i>
              </button>
              <div class="collapse {{ $catName === 'Layout' ? 'show' : '' }}"
                   id="cat-{{ strtolower($catName) }}">
                @foreach ($blockTypes as $type)
                  @if (in_array($type->machine_name, $catBlocks))
                    <div class="block-type-item card mt-1 d-flex flex-row align-items-center"
                         draggable="true"
                         data-type-id="{{ $type->id }}"
                         data-machine-name="{{ $type->machine_name }}">
                      <div class="drag-handle bg-secondary bg-opacity-25 px-2 py-2 rounded-start"
                           style="cursor: grab;" title="Drag to canvas">
                        <i class="bi bi-grip-vertical"></i>
                      </div>
                      <div class="card-body py-2 px-2 flex-grow-1" style="cursor: pointer;" title="Click to add">
                        <div class="small fw-medium">
                          @if (!empty($type->icon))
                            <i class="bi {{ $type->icon }} me-1"></i>
                          @endif
                          {{ $type->label }}
                        </div>
                      </div>
                    </div>
                  @endif
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Canvas (Center) --}}
    <div class="builder-canvas flex-grow-1 bg-white" style="min-height: calc(100vh - 56px); overflow-y: auto;">
      <div class="canvas-header bg-light border-bottom p-2 d-flex align-items-center justify-content-between">
        <span class="small text-muted">
          <i class="bi bi-grid-3x3"></i> Canvas
          <span id="block-count">({{ count($blocks) }} blocks)</span>
        </span>
        <div>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-collapse-all" title="Collapse all blocks">
            <i class="bi bi-dash"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-expand-all" title="Expand all blocks">
            <i class="bi bi-plus"></i>
          </button>
        </div>
      </div>

      <div class="canvas-body p-4">
        <div id="blocks-container" class="blocks-drop-zone" data-page-id="{{ $page->id }}">
          @if (count($blocks) === 0)
            <div class="empty-canvas text-center py-5" id="empty-message">
              <i class="bi bi-inbox display-1 text-muted"></i>
              <p class="text-muted mt-3">Drag blocks here or click a block type to start building your page</p>
            </div>
          @else
            @foreach ($blocks as $block)
              @include('openric-landing-page::_block-card', ['block' => $block])
            @endforeach
          @endif
        </div>
      </div>
    </div>

    {{-- Block Config Panel (Right Sidebar) --}}
    <div class="builder-config bg-light border-start" id="config-panel" style="width: 350px; display: none;">
      <div class="config-header border-bottom p-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
          <i class="bi bi-sliders"></i> <span id="config-title">Block Settings</span>
        </h6>
        <button type="button" class="btn-close" id="close-config"></button>
      </div>
      <div class="config-body p-3" id="config-form-container">
        {{-- Dynamic form loaded here via JavaScript --}}
      </div>
    </div>
  </div>

  {{-- Page Settings Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="pageSettingsPanel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title"><i class="bi bi-gear"></i> Page Settings</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="page-settings-form">
        @csrf
        <input type="hidden" name="id" value="{{ $page->id }}">

        <div class="mb-3">
          <label class="form-label">Page Name</label>
          <input type="text" name="name" class="form-control"
                 value="{{ e($page->name) }}" required>
        </div>

        <div class="mb-3">
          <label class="form-label">URL Slug</label>
          <div class="input-group">
            <span class="input-group-text">/landing/</span>
            <input type="text" name="slug" class="form-control"
                   value="{{ e($page->slug) }}" required>
          </div>
          <div class="form-text">URL: {{ config('app.url') }}/landing/<span id="slug-preview">{{ $page->slug }}</span></div>
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3">{{ e($page->description ?? '') }}</textarea>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" name="is_default" class="form-check-input"
                   id="settings_is_default" {{ ($page->is_default ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="settings_is_default">Set as default home page</label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" name="is_active" class="form-check-input"
                   id="settings_is_active" {{ ($page->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="settings_is_active">Active (visible to public)</label>
          </div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Save Settings
          </button>
        </div>
      </form>

      <hr class="my-4">

      <div class="text-danger">
        <h6>Danger Zone</h6>
        @if (!($page->is_default ?? false))
          <button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete-page">
            <i class="bi bi-trash"></i> Delete This Page
          </button>
        @else
          <p class="small text-muted">Default page cannot be deleted</p>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Block Card Template (for JavaScript-based additions) --}}
<template id="block-card-template">
  <div class="block-card card mb-3" data-block-id="">
    <div class="card-header d-flex align-items-center py-2 cursor-grab block-handle">
      <i class="bi bi-grip-vertical text-muted me-2"></i>
      <i class="bi block-icon me-2"></i>
      <span class="block-label flex-grow-1"></span>
      <div class="block-actions">
        <button type="button" class="btn btn-sm btn-link text-muted btn-visibility" title="Toggle visibility">
          <i class="bi bi-eye"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-primary btn-edit" title="Edit block settings">
          <i class="bi bi-pencil"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-secondary btn-duplicate" title="Duplicate block">
          <i class="bi bi-copy"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-danger btn-delete" title="Delete block">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </div>
    <div class="card-body block-preview p-3">
      {{-- Block preview content --}}
    </div>
  </div>
</template>

<script>
window.LandingPageBuilder = {
    pageId: {{ $page->id }},
    blocks: @json($blocks->toArray()),
    blockTypes: @json($blockTypes->toArray()),
    csrfToken: '{{ csrf_token() }}',
    urls: {
        addBlock: '{{ route('landing-page.block.add') }}',
        updateBlock: '{{ route('landing-page.block.update', ['blockId' => '__BLOCK_ID__']) }}',
        deleteBlock: '{{ route('landing-page.block.delete', ['blockId' => '__BLOCK_ID__']) }}',
        duplicateBlock: '{{ route('landing-page.block.duplicate', ['blockId' => '__BLOCK_ID__']) }}',
        reorderBlocks: '{{ route('landing-page.blocks.reorder') }}',
        toggleVisibility: '{{ route('landing-page.block.toggleVisibility', ['blockId' => '__BLOCK_ID__']) }}',
        updateSettings: '{{ route('landing-page.updateSettings', $page->id) }}',
        deletePage: '{{ route('landing-page.delete', $page->id) }}',
        saveVersion: '{{ route('landing-page.saveVersion', $page->id) }}',
        listPage: '{{ route('landing-page.list') }}'
    }
};

// Page builder JavaScript
(function() {
    const B = window.LandingPageBuilder;

    function apiCall(url, data) {
        const formData = new FormData();
        formData.append('_token', B.csrfToken);
        Object.keys(data || {}).forEach(k => formData.append(k, data[k]));
        return fetch(url, { method: 'POST', body: formData }).then(r => r.json());
    }

    // Block palette click-to-add
    document.querySelectorAll('.block-type-item .card-body').forEach(el => {
        el.addEventListener('click', function() {
            const item = this.closest('.block-type-item');
            apiCall(B.urls.addBlock, {
                page_id: B.pageId,
                block_type_id: item.dataset.typeId,
                config: '{}'
            }).then(result => {
                if (result.success) window.location.reload();
            });
        });
    });

    // Block actions (visibility, edit, duplicate, delete)
    document.querySelectorAll('.btn-visibility').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.block-card');
            const blockId = card.dataset.blockId;
            const url = B.urls.toggleVisibility.replace('__BLOCK_ID__', blockId);
            apiCall(url, {}).then(() => window.location.reload());
        });
    });

    document.querySelectorAll('.btn-duplicate').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.block-card');
            const blockId = card.dataset.blockId;
            const url = B.urls.duplicateBlock.replace('__BLOCK_ID__', blockId);
            apiCall(url, {}).then(() => window.location.reload());
        });
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this block?')) return;
            const card = this.closest('.block-card');
            const blockId = card.dataset.blockId;
            const url = B.urls.deleteBlock.replace('__BLOCK_ID__', blockId);
            apiCall(url, {}).then(() => window.location.reload());
        });
    });

    document.querySelectorAll('.btn-delete-nested').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this nested block?')) return;
            const blockId = this.dataset.blockId;
            const url = B.urls.deleteBlock.replace('__BLOCK_ID__', blockId);
            apiCall(url, {}).then(() => window.location.reload());
        });
    });

    // Page settings form
    const settingsForm = document.getElementById('page-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {};
            new FormData(this).forEach((v, k) => { if (k !== '_token') data[k] = v; });
            apiCall(B.urls.updateSettings, data).then(result => {
                if (result.success) {
                    alert('Settings saved.');
                    window.location.reload();
                }
            });
        });
    }

    // Delete page
    const deleteBtn = document.getElementById('btn-delete-page');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this entire page? This cannot be undone.')) return;
            apiCall(B.urls.deletePage, {}).then(result => {
                if (result.success) window.location.href = B.urls.listPage;
            });
        });
    }

    // Preview
    const previewBtn = document.getElementById('btn-preview');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            window.open(this.dataset.url, '_blank');
        });
    }

    // Save draft / Publish
    const draftBtn = document.getElementById('btn-save-draft');
    if (draftBtn) {
        draftBtn.addEventListener('click', function() {
            apiCall(B.urls.saveVersion, { status: 'draft' }).then(result => {
                if (result.success) alert('Draft saved (version #' + (result.version_id || '') + ').');
            });
        });
    }

    const publishBtn = document.getElementById('btn-publish');
    if (publishBtn) {
        publishBtn.addEventListener('click', function() {
            apiCall(B.urls.saveVersion, { status: 'published' }).then(result => {
                if (result.success) alert('Page published.');
            });
        });
    }

    // Collapse / Expand all
    document.getElementById('btn-collapse-all')?.addEventListener('click', () => {
        document.querySelectorAll('.block-card .block-preview').forEach(el => el.style.display = 'none');
    });
    document.getElementById('btn-expand-all')?.addEventListener('click', () => {
        document.querySelectorAll('.block-card .block-preview').forEach(el => el.style.display = '');
    });
})();
</script>
@endsection
