{{--
  Static page admin list -- adapted from Heratio ahg-static-page browse.blade.php (55 lines)
  and list.blade.php (41 lines). Combined into a single admin index view.
--}}
@extends('theme::layouts.2col')
@section('title', __('Static pages'))
@section('body-class', 'admin staticpage')

@section('sidebar')
  <div class="sidebar-widget mb-3">
    <h4>{{ __('Static pages') }}</h4>
    <p class="small text-muted">
      {{ __('Static pages are custom content pages that appear on your site. You can create pages such as About, Contact, Privacy, or any other informational page.') }}
    </p>
    <p class="small text-muted">
      {{ __('Pages with the slugs') }}
      <strong>home</strong>, <strong>about</strong>, <strong>contact</strong>,
      <strong>privacy</strong>, <strong>terms</strong>
      {{ __('are protected and cannot be deleted or renamed.') }}
    </p>
  </div>
@endsection

@section('content')
  <h1>{{ __('List pages') }}</h1>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="table-responsive mb-3">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th>{{ __('Title') }}</th>
          <th>{{ __('Slug') }}</th>
          <th>{{ __('Published') }}</th>
          <th>{{ __('Sort order') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pages as $page)
          <tr>
            <td>
              <a href="{{ route('staticpage.edit', $page->slug) }}">{{ $page->title ?: __('Untitled') }}</a>
              @if(in_array($page->slug, $protectedSlugs))
                <i class="fas fa-lock ms-1 text-muted" title="{{ __('Protected page') }}"></i>
              @endif
            </td>
            <td>{{ $page->slug }}</td>
            <td>
              @if($page->is_published)
                <span class="badge bg-success">{{ __('Yes') }}</span>
              @else
                <span class="badge bg-secondary">{{ __('No') }}</span>
              @endif
            </td>
            <td>{{ $page->sort_order }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-muted text-center">{{ __('No pages found.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @auth
    <section class="actions mb-3">
      <a class="btn atom-btn-outline-light" href="{{ route('staticpage.create') }}" title="{{ __('Add new') }}">
        {{ __('Add new') }}
      </a>
    </section>
  @endauth
@endsection
