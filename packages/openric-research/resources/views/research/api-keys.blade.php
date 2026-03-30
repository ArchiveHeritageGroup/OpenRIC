@extends('theme::layouts.1col')
@section('title', 'API Keys')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">API Keys</h2>
        @if(session('success'))<div class="alert alert-success">{!! session('success') !!}</div>@endif

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Generate New Key</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.apiKeys') }}" class="row g-3">
                    @csrf <input type="hidden" name="form_action" value="generate">
                    <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required placeholder="My API Key"></div>
                    <div class="col-md-4"><label class="form-label">Expires At</label><input type="date" name="expires_at" class="form-control"></div>
                    <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Generate</button></div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Prefix</th><th>Created</th><th>Expires</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($apiKeys as $key)
                    <tr>
                        <td>{{ $key->name }}</td>
                        <td><code>{{ $key->key_prefix }}...</code></td>
                        <td><small>{{ $key->created_at }}</small></td>
                        <td>{{ $key->expires_at ?? 'Never' }}</td>
                        <td>
                            @if($key->revoked_at)<span class="badge bg-danger">Revoked</span>
                            @elseif($key->expires_at && $key->expires_at < now())<span class="badge bg-warning">Expired</span>
                            @else<span class="badge bg-success">Active</span>@endif
                        </td>
                        <td>
                            @if(!$key->revoked_at)
                                <form method="POST" action="{{ route('research.apiKeys') }}" class="d-inline">@csrf <input type="hidden" name="form_action" value="revoke"><input type="hidden" name="key_id" value="{{ $key->id }}"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke key?')">Revoke</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center">No API keys.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
