<nav class="sidebar bg-light border-end p-3" style="min-width:220px;">
    <h6 class="text-uppercase text-muted small mb-3">Research Portal</h6>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'workspace' ? ' active fw-bold' : '' }}" href="{{ route('research.dashboard') }}">
                <i class="bi bi-house-door me-2"></i>Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'profile' ? ' active fw-bold' : '' }}" href="{{ route('research.profile') }}">
                <i class="bi bi-person me-2"></i>Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'collections' ? ' active fw-bold' : '' }}" href="{{ route('research.collections') }}">
                <i class="bi bi-collection me-2"></i>Collections
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'annotations' ? ' active fw-bold' : '' }}" href="{{ route('research.annotations') }}">
                <i class="bi bi-sticky me-2"></i>Notes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'journal' ? ' active fw-bold' : '' }}" href="{{ route('research.journal') }}">
                <i class="bi bi-journal-text me-2"></i>Journal
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'projects' ? ' active fw-bold' : '' }}" href="{{ route('research.projects') }}">
                <i class="bi bi-kanban me-2"></i>Projects
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'bibliographies' ? ' active fw-bold' : '' }}" href="{{ route('research.bibliographies') }}">
                <i class="bi bi-book me-2"></i>Bibliographies
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'reports' ? ' active fw-bold' : '' }}" href="{{ route('research.reports') }}">
                <i class="bi bi-file-earmark-text me-2"></i>Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'savedSearches' ? ' active fw-bold' : '' }}" href="{{ route('research.savedSearches') }}">
                <i class="bi bi-search me-2"></i>Saved Searches
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'reproductions' ? ' active fw-bold' : '' }}" href="{{ route('research.reproductions') }}">
                <i class="bi bi-copy me-2"></i>Reproductions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'notifications' ? ' active fw-bold' : '' }}" href="{{ route('research.notifications') }}">
                <i class="bi bi-bell me-2"></i>Notifications
                @if(($unreadNotifications ?? 0) > 0)
                    <span class="badge bg-danger ms-1">{{ $unreadNotifications }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'workspaces' ? ' active fw-bold' : '' }}" href="{{ route('research.workspaces') }}">
                <i class="bi bi-people me-2"></i>Team Workspaces
            </a>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-muted small">Bookings</span>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'book' ? ' active fw-bold' : '' }}" href="{{ route('research.book') }}">
                <i class="bi bi-calendar-plus me-2"></i>Book a Visit
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'bookings' ? ' active fw-bold' : '' }}" href="{{ route('research.bookings') }}">
                <i class="bi bi-calendar-check me-2"></i>All Bookings
            </a>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-muted small">Administration</span>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'researchers' ? ' active fw-bold' : '' }}" href="{{ route('research.researchers') }}">
                <i class="bi bi-people-fill me-2"></i>Researchers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'rooms' ? ' active fw-bold' : '' }}" href="{{ route('research.rooms') }}">
                <i class="bi bi-building me-2"></i>Reading Rooms
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'seats' ? ' active fw-bold' : '' }}" href="{{ route('research.seats') }}">
                <i class="bi bi-grid-3x3 me-2"></i>Seats
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'equipment' ? ' active fw-bold' : '' }}" href="{{ route('research.equipment') }}">
                <i class="bi bi-tools me-2"></i>Equipment
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'retrievalQueue' ? ' active fw-bold' : '' }}" href="{{ route('research.retrievalQueue') }}">
                <i class="bi bi-inbox me-2"></i>Retrieval Queue
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'walkIn' ? ' active fw-bold' : '' }}" href="{{ route('research.walkIn') }}">
                <i class="bi bi-door-open me-2"></i>Walk-In
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'adminTypes' ? ' active fw-bold' : '' }}" href="{{ route('research.adminTypes') }}">
                <i class="bi bi-tags me-2"></i>Researcher Types
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'adminStatistics' ? ' active fw-bold' : '' }}" href="{{ route('research.adminStatistics') }}">
                <i class="bi bi-graph-up me-2"></i>Statistics
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'institutions' ? ' active fw-bold' : '' }}" href="{{ route('research.institutions') }}">
                <i class="bi bi-bank me-2"></i>Institutions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'activities' ? ' active fw-bold' : '' }}" href="{{ route('research.activities') }}">
                <i class="bi bi-activity me-2"></i>Activity Log
            </a>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-muted small">Advanced</span>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'validationQueue' ? ' active fw-bold' : '' }}" href="{{ route('research.validationQueue') }}">
                <i class="bi bi-check2-square me-2"></i>Validation Queue
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'entityResolution' ? ' active fw-bold' : '' }}" href="{{ route('research.entityResolution') }}">
                <i class="bi bi-link-45deg me-2"></i>Entity Resolution
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'odrlPolicies' ? ' active fw-bold' : '' }}" href="{{ route('research.odrlPolicies') }}">
                <i class="bi bi-shield-lock me-2"></i>ODRL Policies
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ ($sidebarActive ?? '') === 'documentTemplates' ? ' active fw-bold' : '' }}" href="{{ route('research.documentTemplates') }}">
                <i class="bi bi-file-earmark-ruled me-2"></i>Doc Templates
            </a>
        </li>
    </ul>
</nav>
