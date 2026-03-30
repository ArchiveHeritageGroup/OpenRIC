@if(count($items ?? []) > 0)
  @foreach($items as $result)
    @include('search::_search-result', ['result' => $result])
  @endforeach
@else
  <div class="p-3">No results matching your search.</div>
@endif
