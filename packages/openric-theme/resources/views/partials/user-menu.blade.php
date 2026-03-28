{{-- User menu — adapted from Heratio user-menu.blade.php (266 lines) --}}
{{-- Authentication controls: login form, user profile, tasks, security, logout --}}

@if($themeData['isAuthenticated'] ?? false)
    {{-- Authenticated user dropdown --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
           id="userMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i>
            <span class="d-none d-lg-inline">{{ $themeData['userName'] ?? 'User' }}</span>
            @if(($themeData['pendingTaskCount'] ?? 0) > 0)
                <span class="badge bg-danger rounded-pill ms-1">{{ $themeData['pendingTaskCount'] }}</span>
            @endif
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenuDropdown" style="min-width: 260px;">
            {{-- Profile section --}}
            <li class="dropdown-header text-muted">
                <small>
                    <i class="bi bi-person"></i> {{ $themeData['userName'] ?? '' }}
                    <br>{{ $themeData['userEmail'] ?? '' }}
                </small>
            </li>
            <li><hr class="dropdown-divider"></li>

            {{-- Profile --}}
            @if(Route::has('profile.edit'))
                <li>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="bi bi-person-gear me-2"></i> My Profile
                    </a>
                </li>
            @endif
            @if(Route::has('password.change'))
                <li>
                    <a class="dropdown-item" href="{{ route('password.change') }}">
                        <i class="bi bi-key me-2"></i> Change Password
                    </a>
                </li>
            @endif

            {{-- Tasks section --}}
            <li><hr class="dropdown-divider"></li>
            <li class="dropdown-header"><small>Tasks</small></li>
            @if(Route::has('workflow.my-tasks'))
                <li>
                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('workflow.my-tasks') }}">
                        <span><i class="bi bi-check2-square me-2"></i> My Tasks</span>
                        @if(($themeData['pendingTaskCount'] ?? 0) > 0)
                            <span class="badge bg-primary rounded-pill">{{ $themeData['pendingTaskCount'] }}</span>
                        @endif
                    </a>
                </li>
            @endif
            @if(Route::has('workflow.dashboard'))
                <li>
                    <a class="dropdown-item" href="{{ route('workflow.dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> Workflow Dashboard
                    </a>
                </li>
            @endif

            {{-- Security section --}}
            <li><hr class="dropdown-divider"></li>
            <li class="dropdown-header"><small>Security</small></li>
            @if(Route::has('security.my-requests'))
                <li>
                    <a class="dropdown-item" href="{{ route('security.my-requests') }}">
                        <i class="bi bi-shield-lock me-2"></i> My Access Requests
                    </a>
                </li>
            @endif

            {{-- Admin-only items --}}
            @if($themeData['isAdmin'] ?? false)
                @if(Route::has('admin.security.access-requests'))
                    <li>
                        <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('admin.security.access-requests') }}">
                            <span><i class="bi bi-shield-exclamation me-2"></i> Pending Requests</span>
                            @if(($themeData['pendingAccessRequestCount'] ?? 0) > 0)
                                <span class="badge bg-warning text-dark rounded-pill">{{ $themeData['pendingAccessRequestCount'] }}</span>
                            @endif
                        </a>
                    </li>
                @endif
            @endif

            {{-- Preferences --}}
            <li><hr class="dropdown-divider"></li>
            @if(Route::has('preferences.edit'))
                <li>
                    <a class="dropdown-item" href="{{ route('preferences.edit') }}">
                        <i class="bi bi-sliders me-2"></i> Preferences
                    </a>
                </li>
            @endif

            {{-- Logout --}}
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                    </button>
                </form>
            </li>
        </ul>
    </li>
@else
    {{-- Unauthenticated — login dropdown --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="loginMenuDropdown"
           role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow p-3" aria-labelledby="loginMenuDropdown" style="min-width: 300px;">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="login-email" class="form-label small">Email or Username</label>
                    <input type="text" name="email" id="login-email" class="form-control form-control-sm"
                           required autocomplete="username" placeholder="admin@openric.org">
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label small">Password</label>
                    <input type="password" name="password" id="login-password" class="form-control form-control-sm"
                           required autocomplete="current-password">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="login-remember">
                        <label class="form-check-label small" for="login-remember">Remember me</label>
                    </div>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>

            @if(Route::has('register'))
                <hr class="my-2">
                <div class="text-center">
                    <a href="{{ route('register') }}" class="small text-decoration-none">
                        Create an account
                    </a>
                </div>
            @endif
        </ul>
    </li>
@endif
