@extends('theme::layouts.1col')

@section('title', 'Services Monitor')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-heartbeat me-2"></i>Services Monitor</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Service</th><th>Status</th><th>Details</th></tr></thead>
                <tbody>
                    @foreach ($serviceChecks as $name => $check)
                    <tr>
                        <td class="fw-bold">{{ $name }}</td>
                        <td>
                            @if ($check['status'] === 'ok' || $check['status'] === 'green')
                                <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                            @elseif ($check['status'] === 'warning' || $check['status'] === 'yellow')
                                <span class="badge bg-warning text-dark"><i class="fas fa-exclamation"></i> Warning</span>
                            @else
                                <span class="badge bg-danger"><i class="fas fa-times"></i> Error</span>
                            @endif
                        </td>
                        <td>{{ $check['message'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
