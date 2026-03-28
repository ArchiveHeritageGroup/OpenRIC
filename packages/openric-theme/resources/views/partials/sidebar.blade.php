<nav aria-label="Sidebar navigation" class="sidebar-nav">
    <div class="accordion accordion-flush" id="sidebarAccordion">
        {{-- Records --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="sidebar-records-heading">
                <button class="accordion-button {{ request()->is('record-sets*', 'records*', 'record-parts*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-records" aria-expanded="{{ request()->is('record-sets*', 'records*', 'record-parts*') ? 'true' : 'false' }}" aria-controls="sidebar-records">
                    <i class="bi bi-archive me-2"></i> Records
                </button>
            </h2>
            <div id="sidebar-records" class="accordion-collapse collapse {{ request()->is('record-sets*', 'records*', 'record-parts*') ? 'show' : '' }}" aria-labelledby="sidebar-records-heading" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body p-0">
                    <a href="{{ url('/record-sets') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('record-sets*') ? 'active' : '' }}"><i class="bi bi-folder2 me-2"></i>Record Sets</a>
                    <a href="{{ url('/records') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('records*') ? 'active' : '' }}"><i class="bi bi-file-earmark me-2"></i>Records</a>
                    <a href="{{ url('/record-parts') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('record-parts*') ? 'active' : '' }}"><i class="bi bi-file-earmark-break me-2"></i>Record Parts</a>
                </div>
            </div>
        </div>

        {{-- Agents --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="sidebar-agents-heading">
                <button class="accordion-button {{ request()->is('persons*', 'corporate-bodies*', 'families*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-agents" aria-expanded="{{ request()->is('persons*', 'corporate-bodies*', 'families*') ? 'true' : 'false' }}" aria-controls="sidebar-agents">
                    <i class="bi bi-people me-2"></i> Agents
                </button>
            </h2>
            <div id="sidebar-agents" class="accordion-collapse collapse {{ request()->is('persons*', 'corporate-bodies*', 'families*') ? 'show' : '' }}" aria-labelledby="sidebar-agents-heading" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body p-0">
                    <a href="{{ url('/persons') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('persons*') ? 'active' : '' }}"><i class="bi bi-person me-2"></i>Persons</a>
                    <a href="{{ url('/corporate-bodies') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('corporate-bodies*') ? 'active' : '' }}"><i class="bi bi-building me-2"></i>Corporate Bodies</a>
                    <a href="{{ url('/families') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('families*') ? 'active' : '' }}"><i class="bi bi-house-heart me-2"></i>Families</a>
                </div>
            </div>
        </div>

        {{-- Context --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="sidebar-context-heading">
                <button class="accordion-button {{ request()->is('activities*', 'places*', 'mandates*', 'functions*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-context" aria-expanded="{{ request()->is('activities*', 'places*', 'mandates*', 'functions*') ? 'true' : 'false' }}" aria-controls="sidebar-context">
                    <i class="bi bi-diagram-3 me-2"></i> Context
                </button>
            </h2>
            <div id="sidebar-context" class="accordion-collapse collapse {{ request()->is('activities*', 'places*', 'mandates*', 'functions*') ? 'show' : '' }}" aria-labelledby="sidebar-context-heading" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body p-0">
                    <a href="{{ url('/activities') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('activities*') ? 'active' : '' }}"><i class="bi bi-lightning me-2"></i>Activities</a>
                    <a href="{{ url('/places') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('places*') ? 'active' : '' }}"><i class="bi bi-geo-alt me-2"></i>Places</a>
                    <a href="{{ url('/mandates') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('mandates*') ? 'active' : '' }}"><i class="bi bi-bank me-2"></i>Mandates</a>
                    <a href="{{ url('/functions') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('functions*') ? 'active' : '' }}"><i class="bi bi-gear me-2"></i>Functions</a>
                </div>
            </div>
        </div>

        {{-- Physical / Digital --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="sidebar-physical-heading">
                <button class="accordion-button {{ request()->is('instantiations*', 'condition*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-physical" aria-expanded="{{ request()->is('instantiations*', 'condition*') ? 'true' : 'false' }}" aria-controls="sidebar-physical">
                    <i class="bi bi-hdd me-2"></i> Physical / Digital
                </button>
            </h2>
            <div id="sidebar-physical" class="accordion-collapse collapse {{ request()->is('instantiations*', 'condition*') ? 'show' : '' }}" aria-labelledby="sidebar-physical-heading" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body p-0">
                    <a href="{{ url('/instantiations') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('instantiations*') ? 'active' : '' }}"><i class="bi bi-file-binary me-2"></i>Instantiations</a>
                    <a href="{{ url('/condition-assessments') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('condition*') ? 'active' : '' }}"><i class="bi bi-clipboard2-pulse me-2"></i>Condition Assessments</a>
                </div>
            </div>
        </div>

        {{-- Discover --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="sidebar-discover-heading">
                <button class="accordion-button {{ request()->is('search*', 'browse*', 'hierarchy*', 'graph*', 'sparql*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-discover" aria-expanded="{{ request()->is('search*', 'browse*', 'hierarchy*', 'graph*', 'sparql*') ? 'true' : 'false' }}" aria-controls="sidebar-discover">
                    <i class="bi bi-search me-2"></i> Discover
                </button>
            </h2>
            <div id="sidebar-discover" class="accordion-collapse collapse {{ request()->is('search*', 'browse*', 'hierarchy*', 'graph*', 'sparql*') ? 'show' : '' }}" aria-labelledby="sidebar-discover-heading" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body p-0">
                    <a href="{{ url('/search') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('search*') ? 'active' : '' }}"><i class="bi bi-search me-2"></i>Search</a>
                    <a href="{{ url('/browse') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('browse*') ? 'active' : '' }}"><i class="bi bi-list-ul me-2"></i>Browse</a>
                    <a href="{{ url('/hierarchy') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('hierarchy*') ? 'active' : '' }}"><i class="bi bi-diagram-2 me-2"></i>Hierarchy</a>
                    <a href="{{ url('/graph/overview') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('graph*') ? 'active' : '' }}"><i class="bi bi-share me-2"></i>Graph</a>
                    <a href="{{ url('/sparql') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('sparql*') ? 'active' : '' }}"><i class="bi bi-terminal me-2"></i>SPARQL</a>
                </div>
            </div>
        </div>

        {{-- Export --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="sidebar-export-heading">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-export" aria-expanded="false" aria-controls="sidebar-export">
                    <i class="bi bi-download me-2"></i> Export
                </button>
            </h2>
            <div id="sidebar-export" class="accordion-collapse collapse" aria-labelledby="sidebar-export-heading" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body p-0">
                    <a href="{{ url('/export/formats') }}" class="list-group-item list-group-item-action border-0 ps-4"><i class="bi bi-filetype-json me-2"></i>Export Formats</a>
                    <a href="{{ url('/oai') }}" class="list-group-item list-group-item-action border-0 ps-4"><i class="bi bi-cloud-download me-2"></i>OAI-PMH</a>
                </div>
            </div>
        </div>

        @auth
            {{-- Workflow --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="sidebar-workflow-heading">
                    <button class="accordion-button {{ request()->is('workflow*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-workflow" aria-expanded="{{ request()->is('workflow*') ? 'true' : 'false' }}" aria-controls="sidebar-workflow">
                        <i class="bi bi-kanban me-2"></i> Workflow
                    </button>
                </h2>
                <div id="sidebar-workflow" class="accordion-collapse collapse {{ request()->is('workflow*') ? 'show' : '' }}" aria-labelledby="sidebar-workflow-heading" data-bs-parent="#sidebarAccordion">
                    <div class="accordion-body p-0">
                        <a href="{{ url('/workflow') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('workflow') ? 'active' : '' }}"><i class="bi bi-speedometer me-2"></i>Dashboard</a>
                        <a href="{{ url('/workflow/my-tasks') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('workflow/my-tasks*') ? 'active' : '' }}"><i class="bi bi-check2-square me-2"></i>My Tasks</a>
                        <a href="{{ url('/workflow/pool') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('workflow/pool*') ? 'active' : '' }}"><i class="bi bi-collection me-2"></i>Pool</a>
                    </div>
                </div>
            </div>

            @if(Auth::user()->isAdmin())
                {{-- Admin --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="sidebar-admin-heading">
                        <button class="accordion-button {{ request()->is('admin*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-admin" aria-expanded="{{ request()->is('admin*') ? 'true' : 'false' }}" aria-controls="sidebar-admin">
                            <i class="bi bi-shield-lock me-2"></i> Admin
                        </button>
                    </h2>
                    <div id="sidebar-admin" class="accordion-collapse collapse {{ request()->is('admin*') ? 'show' : '' }}" aria-labelledby="sidebar-admin-heading" data-bs-parent="#sidebarAccordion">
                        <div class="accordion-body p-0">
                            <a href="{{ url('/admin/users') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('admin/users*') ? 'active' : '' }}"><i class="bi bi-people me-2"></i>Users</a>
                            <a href="{{ url('/admin/roles') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('admin/roles*') ? 'active' : '' }}"><i class="bi bi-person-badge me-2"></i>Roles</a>
                            <a href="{{ url('/admin/audit') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('admin/audit*') ? 'active' : '' }}"><i class="bi bi-journal-text me-2"></i>Audit Trail</a>
                            <a href="{{ url('/admin/mappings') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('admin/mappings*') ? 'active' : '' }}"><i class="bi bi-table me-2"></i>Mappings</a>
                            <a href="{{ url('/workflow/admin') }}" class="list-group-item list-group-item-action border-0 ps-4 {{ request()->is('workflow/admin*') ? 'active' : '' }}"><i class="bi bi-sliders me-2"></i>Workflow Admin</a>
                        </div>
                    </div>
                </div>
            @endif
        @endauth
    </div>
</nav>
