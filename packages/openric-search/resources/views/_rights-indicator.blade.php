{{-- Rights Indicator for Browse/Search Results --}}
@php
if (!isset($doc) || !isset($doc['id'])) { return; }
$objectId = $doc['id'];
$hasRights = \Illuminate\Support\Facades\DB::table('rights_basis')->where('entity_id', $objectId)->exists();
@endphp

@if($hasRights)
<span class="rights-indicators ms-2">
  <i class="fas fa-copyright text-info" title="Has rights statement"></i>
</span>
@endif
