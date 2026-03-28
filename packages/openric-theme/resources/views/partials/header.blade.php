<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
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
                            <li><a class="dropdown-item" href="{{ route('record-sets.index', [], false) }}">Record Sets</a></li>
                            <li><a class="dropdown-item" href="{{ route('records.index', [], false) }}">Records</a></li>
                            <li><a class="dropdown-item" href="{{ route('record-parts.index', [], false) }}">Record Parts</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Agents</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('persons.index', [], false) }}">Persons</a></li>
                            <li><a class="dropdown-item" href="{{ route('corporate-bodies.index', [], false) }}">Corporate Bodies</a></li>
                            <li><a class="dropdown-item" href="{{ route('families.index', [], false) }}">Families</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Context</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('activities.index', [], false) }}">Activities</a></li>
                            <li><a class="dropdown-item" href="{{ route('places.index', [], false) }}">Places</a></li>
                            <li><a class="dropdown-item" href="{{ route('mandates.index', [], false) }}">Mandates</a></li>
                            <li><a class="dropdown-item" href="{{ route('functions.index', [], false) }}">Functions</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('instantiations.index', [], false) }}">Instantiations</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    @include('theme::partials.view-switch')
                    <form class="d-flex" role="search" action="{{ route('home') }}" method="GET">
                        <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search..." aria-label="Search">
                    </form>
                    @auth
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->display_name ?? Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if(Auth::user()->isAdmin())
                                    <li><a class="dropdown-item" href="{{ route('admin.users.index', [], false) }}">Admin</a></li>
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
