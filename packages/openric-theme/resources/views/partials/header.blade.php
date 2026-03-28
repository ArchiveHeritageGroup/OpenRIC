<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ asset('OpenRiC.png') }}" alt="OpenRiC" height="32" class="me-2">
                <span class="text-info">Open</span>RiC
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Records</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/record-sets') }}">Record Sets</a></li>
                            <li><a class="dropdown-item" href="{{ url('/records') }}">Records</a></li>
                            <li><a class="dropdown-item" href="{{ url('/record-parts') }}">Record Parts</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Agents</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/persons') }}">Persons</a></li>
                            <li><a class="dropdown-item" href="{{ url('/corporate-bodies') }}">Corporate Bodies</a></li>
                            <li><a class="dropdown-item" href="{{ url('/families') }}">Families</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Context</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/activities') }}">Activities</a></li>
                            <li><a class="dropdown-item" href="{{ url('/places') }}">Places</a></li>
                            <li><a class="dropdown-item" href="{{ url('/mandates') }}">Mandates</a></li>
                            <li><a class="dropdown-item" href="{{ url('/functions') }}">Functions</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Discover</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/search') }}">Search</a></li>
                            <li><a class="dropdown-item" href="{{ url('/browse') }}">Browse</a></li>
                            <li><a class="dropdown-item" href="{{ url('/hierarchy') }}">Hierarchy</a></li>
                            <li><a class="dropdown-item" href="{{ url('/sparql') }}">SPARQL</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('/graph/overview') }}">Graph Overview</a></li>
                            <li><a class="dropdown-item" href="{{ url('/graph/agent-network') }}">Agent Network</a></li>
                            <li><a class="dropdown-item" href="{{ url('/graph/timeline') }}">Timeline</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/instantiations') }}">Instantiations</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    @include('theme::partials.view-switch')
                    <form class="d-flex" role="search" action="{{ url('/search') }}" method="GET">
                        <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search..." aria-label="Search">
                    </form>
                    @auth
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->display_name ?? Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if(Auth::user()->isAdmin())
                                    <li><a class="dropdown-item" href="{{ url('/admin/users') }}">Users</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/roles') }}">Roles</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/audit') }}">Audit Trail</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/mappings') }}">Mappings</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/workflow') }}">Workflows</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>
