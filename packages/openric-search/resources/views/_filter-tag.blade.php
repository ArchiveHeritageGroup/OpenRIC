{{-- Single filter tag --}}
@php $removeParams = request()->except([$filter['param'] ?? '', 'page']); @endphp
<a href="{{ route('search', $removeParams) }}" class="badge bg-secondary text-decoration-none" title="Remove filter">
  {{ $filter['label'] ?? '' }}
  <i class="fas fa-times ms-1" aria-hidden="true"></i>
</a>
