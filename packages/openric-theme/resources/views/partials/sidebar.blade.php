<nav aria-label="Sidebar navigation">
    <div class="list-group list-group-flush">
        <h6 class="list-group-item bg-light fw-bold text-uppercase small">Records</h6>
        <a href="{{ route('record-sets.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('record-sets.*') ? 'active' : '' }}">Record Sets</a>
        <a href="{{ route('records.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('records.*') ? 'active' : '' }}">Records</a>
        <a href="{{ route('record-parts.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('record-parts.*') ? 'active' : '' }}">Record Parts</a>

        <h6 class="list-group-item bg-light fw-bold text-uppercase small mt-2">Agents</h6>
        <a href="{{ route('persons.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('persons.*') ? 'active' : '' }}">Persons</a>
        <a href="{{ route('corporate-bodies.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('corporate-bodies.*') ? 'active' : '' }}">Corporate Bodies</a>
        <a href="{{ route('families.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('families.*') ? 'active' : '' }}">Families</a>

        <h6 class="list-group-item bg-light fw-bold text-uppercase small mt-2">Context</h6>
        <a href="{{ route('activities.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('activities.*') ? 'active' : '' }}">Activities</a>
        <a href="{{ route('places.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('places.*') ? 'active' : '' }}">Places</a>
        <a href="{{ route('mandates.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('mandates.*') ? 'active' : '' }}">Mandates</a>
        <a href="{{ route('functions.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('functions.*') ? 'active' : '' }}">Functions</a>

        <h6 class="list-group-item bg-light fw-bold text-uppercase small mt-2">Physical / Digital</h6>
        <a href="{{ route('instantiations.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('instantiations.*') ? 'active' : '' }}">Instantiations</a>

        @auth
            @if(Auth::user()->isAdmin())
                <h6 class="list-group-item bg-light fw-bold text-uppercase small mt-2">Admin</h6>
                <a href="{{ route('admin.users.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
                <a href="{{ route('admin.roles.index', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles</a>
                <a href="{{ route('audit.browse', [], false) }}" class="list-group-item list-group-item-action {{ request()->routeIs('audit.*') ? 'active' : '' }}">Audit Trail</a>
            @endif
        @endauth
    </div>
</nav>
