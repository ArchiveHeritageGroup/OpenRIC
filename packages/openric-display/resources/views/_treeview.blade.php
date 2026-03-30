{{--
  Treeview component — _treeview.blade.php
  Adapted from Heratio ahg-display _treeview.blade.php
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
$treeviewType = $treeviewType ?? 'sidebar';
@endphp
<ul class="nav nav-tabs border-0" id="treeview-menu" role="tablist">

  @if($treeviewType === 'sidebar')
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="treeview-tab" data-bs-toggle="tab" data-bs-target="#treeview"
              type="button" role="tab" aria-controls="treeview" aria-selected="true">
        {{ __('Holdings') }}
      </button>
    </li>
  @endif

  <li class="nav-item" role="presentation">
    <button class="nav-link{{ $treeviewType !== 'sidebar' ? ' active' : '' }}"
            id="treeview-search-tab" data-bs-toggle="tab" data-bs-target="#treeview-search"
            type="button" role="tab" aria-controls="treeview-search"
            aria-selected="{{ $treeviewType !== 'sidebar' ? 'true' : 'false' }}">
        {{ __('Quick search') }}
    </button>
  </li>

</ul>

<div class="tab-content mb-3" id="treeview-content">

  @if($treeviewType === 'sidebar')
    <div class="tab-pane fade show active" id="treeview" role="tabpanel" aria-labelledby="treeview-tab"
         data-current-id="{{ $resource->id ?? '' }}" data-sortable="{{ empty($sortable) ? 'false' : 'true' }}">
      <ul class="list-group rounded-0">
        @foreach($ancestors ?? [] as $ancestor)
          <li class="list-group-item list-group-item-action py-1">
            <a href="{{ route('display.show', ['id' => $ancestor->id]) }}" class="text-decoration-none small">
              {{ $ancestor->title ?? $ancestor->identifier ?? '' }}
            </a>
          </li>
        @endforeach

        @if(isset($resource))
          <li class="list-group-item list-group-item-action py-1 active">
            <strong class="small">{{ $resource->title ?? $resource->identifier ?? '' }}</strong>
          </li>
        @endif

        @if(isset($children))
          @foreach($children as $child)
            <li class="list-group-item list-group-item-action py-1 ps-4">
              <a href="{{ route('display.show', ['id' => $child->id]) }}" class="text-decoration-none small">
                {{ $child->title ?? $child->identifier ?? '' }}
              </a>
            </li>
          @endforeach
        @endif
      </ul>
    </div>
  @else
    <div id="fullwidth-treeview-active" hidden>
      <input type="button" id="fullwidth-treeview-more-button" class="btn btn-sm btn-outline-secondary" data-label="{{ __('%1% more') }}" value="" />
      <input type="button" id="fullwidth-treeview-reset-button" class="btn btn-sm btn-outline-secondary" value="{{ __('Reset') }}" />
    </div>
  @endif

  <div class="tab-pane fade{{ $treeviewType !== 'sidebar' ? ' show active' : '' }}" id="treeview-search" role="tabpanel" aria-labelledby="treeview-search-tab">
    <form method="get" role="search" class="p-2 bg-white border" action="{{ route('display.browse') }}" data-not-found="{{ __('No results found.') }}">
      <div class="input-group">
        <input type="text" name="query" class="form-control" aria-label="{{ __('Search hierarchy') }}" placeholder="{{ __('Search hierarchy') }}" required>
        <button class="btn btn-outline-secondary" type="submit">
          <i aria-hidden="true" class="fas fa-search"></i>
          <span class="visually-hidden">{{ __('Search') }}</span>
        </button>
      </div>
    </form>
  </div>

</div>
