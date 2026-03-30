{{-- Show Actions — adapted from Heratio _show-actions.blade.php --}}
@auth
<ul class="actions mb-3 nav gap-2">
    <li><a href="{{ route('acl.edit-group', ['id' => $group->id]) }}" class="btn btn-outline-primary">Edit</a></li>
    <li><a href="{{ route('acl.groups') }}" class="btn btn-outline-secondary">Return to group list</a></li>
</ul>
@endauth
