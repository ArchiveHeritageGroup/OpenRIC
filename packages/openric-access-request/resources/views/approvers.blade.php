@extends('openric-theme::layouts.1col')

@section('title', 'Manage Approvers')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('homepage') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('accessRequest.pending') }}">Pending</a></li>
                    <li class="breadcrumb-item active">Approvers</li>
                </ol>
            </nav>

            @if(session('notice'))
                <div class="alert alert-info alert-dismissible fade show">
                    {{ session('notice') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-users me-2"></i>Access Request Approvers</h4>
            </div>

            <div class="card mb-4">
                <div class="card-header">Add New Approver</div>
                <div class="card-body">
                    <form action="{{ route('accessRequest.addApprover') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-10">
                            <label for="user_iri" class="form-label">User IRI</label>
                            <input type="text" class="form-control" id="user_iri" name="user_iri" required placeholder="https://ric.theahg.co.za/user/123">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Add Approver</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if(empty($approvers))
                        <div class="p-4 text-center text-muted">
                            <p>No approvers configured.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvers as $approver)
                                        <tr>
                                            <td>{{ $approver['name']['value'] ?? $approver['name'] ?? 'Unknown' }}</td>
                                            <td>{{ $approver['email']['value'] ?? $approver['email'] ?? 'N/A' }}</td>
                                            <td>
                                                <form action="{{ route('accessRequest.removeApprover', $approver['user']['value'] ?? $approver['user']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this approver?')">Remove</button>
                                                </form>
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
