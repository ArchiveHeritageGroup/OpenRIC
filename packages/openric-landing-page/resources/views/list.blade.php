{{-- Landing Page List - Admin View -- adapted from Heratio ahg-landing-page::list --}}
@extends('theme::layouts.1col')

@section('title', 'Landing Pages')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Landing Pages</h1>
      <p class="text-muted mb-0">Manage your site's landing pages and page builder</p>
    </div>
    <a href="{{ route('landing-page.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Create New Page
    </a>
  </div>

  @if (session('notice'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle"></i> {{ session('notice') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if ($pages->isEmpty())
    <div class="text-center py-5">
      <i class="bi bi-file-earmark-plus display-1 text-muted"></i>
      <h3 class="mt-3 text-muted">No Landing Pages Yet</h3>
      <p class="text-muted">Create your first landing page to get started</p>
      <a href="{{ route('landing-page.create') }}" class="btn btn-primary btn-lg mt-2">
        <i class="bi bi-plus-lg"></i> Create Landing Page
      </a>
    </div>
  @else
    @if (isset($stats))
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card border-0 bg-primary bg-opacity-10">
            <div class="card-body text-center">
              <div class="h3 mb-0 text-primary">{{ $stats['total'] ?? 0 }}</div>
              <small class="text-muted">Total Pages</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 bg-success bg-opacity-10">
            <div class="card-body text-center">
              <div class="h3 mb-0 text-success">{{ $stats['active'] ?? 0 }}</div>
              <small class="text-muted">Active Pages</small>
            </div>
          </div>
        </div>
      </div>
    @endif

    <div class="row g-4">
      @foreach ($pages as $page)
        <div class="col-md-6 col-lg-4">
          <div class="card page-list-card h-100 {{ !$page->is_active ? 'border-warning' : '' }}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0">
                  {{ e($page->name) }}
                </h5>
                <div>
                  @if ($page->is_default ?? false)
                    <span class="badge bg-primary">Default</span>
                  @endif
                  @if (!$page->is_active)
                    <span class="badge bg-warning text-dark">Inactive</span>
                  @endif
                </div>
              </div>

              <p class="card-text text-muted small mb-3">
                @if ($page->description ?? null)
                  {{ e(\Illuminate\Support\Str::limit($page->description, 100)) }}
                @else
                  <em>No description</em>
                @endif
              </p>

              <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                <span>
                  <i class="bi bi-grid-3x3-gap"></i>
                  {{ $page->block_count ?? 0 }} blocks
                </span>
                <span>
                  <code>/landing/{{ e($page->slug) }}</code>
                </span>
              </div>

              <div class="d-flex gap-2">
                <a href="{{ route('landing-page.edit', $page->id) }}"
                   class="btn btn-primary btn-sm flex-grow-1">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="{{ route('landing-page.show', $page->slug) }}"
                   class="btn btn-outline-secondary btn-sm" target="_blank" title="Preview">
                  <i class="bi bi-eye"></i> Preview
                </a>
                @if ($page->is_active)
                  <form method="POST" action="{{ route('landing-page.post') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="{{ $page->id }}">
                    <button type="submit" class="btn btn-outline-warning btn-sm" title="Deactivate">
                      <i class="bi bi-pause-circle"></i>
                    </button>
                  </form>
                @else
                  <form method="POST" action="{{ route('landing-page.post') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="{{ $page->id }}">
                    <button type="submit" class="btn btn-outline-success btn-sm" title="Activate">
                      <i class="bi bi-play-circle"></i>
                    </button>
                  </form>
                @endif
              </div>
            </div>
            <div class="card-footer bg-transparent text-muted small">
              Updated {{ $page->updated_at ? \Carbon\Carbon::parse($page->updated_at)->diffForHumans() : 'N/A' }}
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
