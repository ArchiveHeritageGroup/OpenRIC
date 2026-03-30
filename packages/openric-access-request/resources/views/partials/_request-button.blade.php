{{-- Request Access Button Partial --}}
@auth
    <a href="{{ route('accessRequest.requestObject', $slug ?? 'unknown') }}" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-key me-1"></i>Request Access
    </a>
@else
    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-sign-in-alt me-1"></i>Login to Request Access
    </a>
@endauth
