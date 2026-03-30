{{-- Actor ACL Form — adapted from Heratio _acl-actor.blade.php --}}
<h1>Edit authority record permissions of {{ $resource->name ?? $resource->title ?? '' }}</h1>
@include('openric-auth::acl._acl-modal', ['entityType' => 'actor', 'label' => 'Authority record', 'basicActions' => $basicActions])
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form id="editForm" method="POST">@csrf
<div class="accordion mb-3"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#all-collapse">Permissions for all authority records</button></h2><div id="all-collapse" class="accordion-collapse collapse"><div class="accordion-body">@include('openric-auth::acl._acl-table', ['object' => $rootActor ?? (object)['id' => 0, 'slug' => 'root', 'is_root' => true], 'permissions' => $actors[$rootActor->id ?? 0] ?? [], 'actions' => $basicActions, 'module' => 'actor', 'moduleLabel' => 'Authority record'])</div></div></div></div>
<ul class="actions mb-3 nav gap-2"><li><a href="{{ route('acl.groups') }}" class="btn btn-outline-secondary">Cancel</a></li><li><input class="btn btn-success" type="submit" value="Save"></li></ul>
</form>
