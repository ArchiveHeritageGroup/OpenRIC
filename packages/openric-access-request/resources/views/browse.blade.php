@extends('openric-theme::layouts.1col')

@section('title', 'Access Requests')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('homepage') }}">Home</a></li>
                    <li class="breadcrumb-item active">Access Requests</li>
                </ol>
            </nav>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('notice'))
                <div class="alert alert-info alert-dismissible fade show">
                    {{ session('notice') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-lock me-2"></i>Access Requests</h4>
                <div>
                    <a href="{{ route('accessRequest.pending') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-clock me-1"></i>Pending
                    </a>
                    <a href="{{ route('accessRequest.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>New Request
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if(empty($requests))
                        <div class="p-4 text-center text-muted">
                            <p>No access requests found.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>User</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $req)
                                        <tr>
                                            <td>{{ $req['type']['value'] ?? $req['type'] ?? 'N/A' }}</td>
                                            <td>{{ $req['userName']['value'] ?? $req['userName'] ?? 'Unknown' }}</td>
                                            <td>{{ $req['request']['value'] ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $status = $req['status']['value'] ?? $req['status'] ?? '';
                                                @endphp
                                                @if($status === 'approved' || $status === 'approved@en')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($status === 'denied' || $status === 'denied@en')
                                                    <span class="badge bg-danger">Denied</span>
                                                @elseif($status === 'pending' || $status === 'pending@en')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($status === 'cancelled' || $status === 'cancelled@en')
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                                                @endif
                                            </td>
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
