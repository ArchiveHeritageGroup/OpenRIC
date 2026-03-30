@extends('openric-theme::layouts.1col')

@section('title', 'View Access Request')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('homepage') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('accessRequest.pending') }}">Pending</a></li>
                    <li class="breadcrumb-item active">View Request</li>
                </ol>
            </nav>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Access Request</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">IRI</dt>
                        <dd class="col-sm-9"><code>{{ $accessRequest['iri'] ?? 'N/A' }}</code></dd>

                        <dt class="col-sm-3">Type</dt>
                        <dd class="col-sm-9">{{ $accessRequest['type'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            @php
                                $status = $accessRequest['status'] ?? '';
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
                        </dd>

                        <dt class="col-sm-3">Created</dt>
                        <dd class="col-sm-9">{{ $accessRequest['createdAt'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $accessRequest['description'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Reason/Justification</dt>
                        <dd class="col-sm-9">{{ $accessRequest['reason'] ?? 'N/A' }}</dd>
                    </dl>

                    @if(($accessRequest['status'] ?? '') === 'pending@en' || $accessRequest['status'] === 'pending')
                        <hr>
                        <div class="d-flex gap-2">
                            <form action="{{ route('accessRequest.approve', $accessRequest['iri']) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check me-1"></i>Approve
                                </button>
                            </form>
                            <form action="{{ route('accessRequest.deny', $accessRequest['iri']) }}" method="POST" class="d-inline">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" name="reason" class="form-control" placeholder="Reason for denial">
                                    <button type="submit" class="btn btn-danger">Deny</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
