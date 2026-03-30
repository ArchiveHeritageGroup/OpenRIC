@php
$headerBg = $headerBackgroundColor ?? '#1a5276';
@endphp
<header>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: {{ $headerBg }};" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                @if($toggleLogo)
                    @if(!empty($logoPath))
                        <img src="{{ asset($logoPath) }}" alt="{{ $siteTitle ?? 'OpenRiC' }}" height="32" class="me-2">
                    @else
                        <img src="{{ asset('OpenRiC.png') }}" alt="{{ $siteTitle ?? 'OpenRiC' }}" height="32" class="me-2">
                    @endif
                @endif
                <span class="text-white">Open</span>RiC
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    {{-- Browse Dropdown (Heratio-style) --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-compass me-1"></i>Browse
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Browse</h6></li>
                            <li><a class="dropdown-item" href="{{ url('/browse/records') }}">
                                <i class="fas fa-folder-open me-2"></i>Archival descriptions
                            </a></li>
                            <li><a class="dropdown-item" href="{{ url('/persons') }}">
                                <i class="fas fa-user me-2"></i>Authority records
                            </a></li>
                            <li><a class="dropdown-item" href="{{ url('/corporate-bodies') }}">
                                <i class="fas fa-building me-2"></i>Archival institutions
                            </a></li>
                            <li><a class="dropdown-item" href="{{ url('/activities') }}">
                                <i class="fas fa-tasks me-2"></i>Activities
                            </a></li>
                            <li><a class="dropdown-item" href="{{ url('/places') }}">
                                <i class="fas fa-map-marker-alt me-2"></i>Places
                            </a></li>
                            <li><a class="dropdown-item" href="{{ url('/instantiations') }}">
                                <i class="fas fa-file-image me-2"></i>Digital objects
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/ric/explorer') }}">
                                <i class="fas fa-project-diagram me-2"></i>Graph Explorer
                            </a></li>
                        </ul>
                    </li>
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
                            <li><a class="dropdown-item" href="{{ url('/explorer') }}"><i class="fas fa-project-diagram me-2"></i>Graph Explorer</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/ric/explorer') }}">Full Graph (admin)</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/instantiations') }}">Instantiations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/explorer') }}"><i class="bi bi-share me-1"></i>Graph</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    @include('theme::partials.view-switch')
                    {{-- Language Selector (Heratio _change-language-menu pattern) --}}
                    @if(($toggleLanguageMenu ?? true) && count($enabledLanguages ?? []) > 1)
                    @php $currentLocale = app()->getLocale(); @endphp
                    <li class="nav-item dropdown d-flex flex-column">
                        <a class="nav-link dropdown-toggle d-flex align-items-center p-0"
                           href="#" id="language-menu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-2x fa-fw fa-globe-europe px-0 px-lg-2 py-2"
                               data-bs-toggle="tooltip" data-bs-placement="bottom"
                               data-bs-custom-class="d-none d-lg-block"
                               title="{{ __('Language') }}" aria-hidden="true"></i>
                            <span class="d-lg-none mx-1" aria-hidden="true">{{ __('Language') }}</span>
                            <span class="visually-hidden">{{ __('Language') }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="language-menu" style="max-height: 400px; overflow-y: auto;">
                            <li><h6 class="dropdown-header">{{ __('Language') }}</h6></li>
                            @foreach($enabledLanguages as $code => $meta)
                            <li>
                                <a class="dropdown-item{{ $currentLocale === $code ? ' active' : '' }}"
                                   href="{{ route('language.switch', $code) }}">
                                    @if($currentLocale === $code)<i class="fas fa-check me-1"></i>@endif
                                    {{ $meta['name'] ?? $code }}
                                    <small class="text-muted ms-1">({{ $code }})</small>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endif
                    <form class="d-flex" role="search" action="{{ url('/search') }}" method="GET">
                        <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search..." aria-label="Search">
                    </form>
                    @auth
                        @if(Auth::user()->isAdmin())
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog"></i> Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-sliders-h me-2"></i> Settings</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/users') }}"><i class="fas fa-users me-2"></i> Users</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/roles') }}"><i class="fas fa-user-shield me-2"></i> Roles</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/audit') }}"><i class="fas fa-history me-2"></i> Audit Trail</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/mappings') }}"><i class="fas fa-exchange-alt me-2"></i> Mappings</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/workflow') }}"><i class="fas fa-tasks me-2"></i> Workflows</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/settings/system-info') }}"><i class="fas fa-server me-2"></i> System Info</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/admin/settings/services') }}"><i class="fas fa-heartbeat me-2"></i> Services</a></li>
                                </ul>
                            </div>
                        @endif
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->display_name ?? Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('user.profile') }}"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
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
