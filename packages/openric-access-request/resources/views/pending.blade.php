@extends('openric-theme::layouts.1col')

@section('title', 'Pending Access Requests')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('homepage') }}">Home</a></li>
                    <li class="breadcrumb-item active">Pending Requests</li>
                </ol>
            </nav>

            @if(session('notice'))
                <div class="alert alert-info alert-dismissible fade show">
                    {{ session('notice') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-clock me-2"></i>Pending Access Requests</h4>
                <a href="{{ route('accessRequest.browse') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-list me-1"></i>All Requests
                </a>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if(empty($requests))
                        <div class="p-4 text-center text-muted">
                            <p>No pending access requests.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>User</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $req)
                                        <tr>
                                            <td>{{ $req['type']['value'] ?? $req['type'] ?? 'N/A' }}</td>
                                            <td>{{ $req['userName']['value'] ?? $req['userName'] ?? 'Unknown' }}</td>
                                            <td>{{ $req['createdAt']['value'] ?? $req['createdAt'] ?? '' }}</td>
                                            <td>
                                                <a href="{{ route('accessRequest.view', $req['request']['value'] ?? $req['request']) }}" class="btn btn-outline-primary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
