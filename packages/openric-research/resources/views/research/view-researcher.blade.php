@extends('theme::layouts.1col')
@section('title', 'View Researcher')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">{{ $researcher->first_name }} {{ $researcher->last_name }}</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Researcher Details</h5>
                        <span class="badge bg-{{ $researcher->status === 'approved' ? 'success' : ($researcher->status === 'pending' ? 'warning' : 'secondary') }} fs-6">{{ ucfirst($researcher->status) }}</span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Title</dt><dd class="col-sm-8">{{ $researcher->title ?? '-' }}</dd>
                            <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $researcher->email }}</dd>
                            <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $researcher->phone ?? '-' }}</dd>
                            <dt class="col-sm-4">Affiliation</dt><dd class="col-sm-8">{{ ucfirst($researcher->affiliation_type ?? 'independent') }}</dd>
                            <dt class="col-sm-4">Institution</dt><dd class="col-sm-8">{{ $researcher->institution ?? '-' }}</dd>
                            <dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $researcher->department ?? '-' }}</dd>
                            <dt class="col-sm-4">Position</dt><dd class="col-sm-8">{{ $researcher->position ?? '-' }}</dd>
                            <dt class="col-sm-4">ORCID</dt><dd class="col-sm-8">{{ $researcher->orcid_id ?? '-' }}</dd>
                            <dt class="col-sm-4">ID Type</dt><dd class="col-sm-8">{{ $researcher->id_type ?? '-' }}</dd>
                            <dt class="col-sm-4">ID Number</dt><dd class="col-sm-8">{{ $researcher->id_number ?? '-' }}</dd>
                            <dt class="col-sm-4">Research Interests</dt><dd class="col-sm-8">{{ $researcher->research_interests ?? '-' }}</dd>
                            <dt class="col-sm-4">Current Project</dt><dd class="col-sm-8">{{ $researcher->current_project ?? '-' }}</dd>
                            <dt class="col-sm-4">Registered</dt><dd class="col-sm-8">{{ $researcher->created_at }}</dd>
                            @if($researcher->approved_at)<dt class="col-sm-4">Approved</dt><dd class="col-sm-8">{{ $researcher->approved_at }}</dd>@endif
                            @if($researcher->expires_at)<dt class="col-sm-4">Expires</dt><dd class="col-sm-8">{{ $researcher->expires_at }}</dd>@endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">Actions</h6></div>
                    <div class="card-body">
                        @if($researcher->status === 'pending')
                            <form method="POST" action="{{ route('research.approveResearcher', $researcher->id) }}" class="mb-2">@csrf<button class="btn btn-success w-100">Approve</button></form>
                            <form method="POST" action="{{ route('research.rejectResearcher', $researcher->id) }}">@csrf<input type="text" name="reason" class="form-control mb-2" placeholder="Rejection reason"><button class="btn btn-danger w-100">Reject</button></form>
                        @elseif($researcher->status === 'approved')
                            <form method="POST" action="{{ route('research.suspendResearcher', $researcher->id) }}">@csrf<button class="btn btn-warning w-100">Suspend</button></form>
                        @elseif($researcher->status === 'suspended')
                            <form method="POST" action="{{ route('research.approveResearcher', $researcher->id) }}">@csrf<button class="btn btn-success w-100">Reactivate</button></form>
                        @endif
                    </div>
                </div>

                @if(!empty($bookings))
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Recent Bookings</h6></div>
                    <ul class="list-group list-group-flush">
                        @foreach(array_slice($bookings, 0, 5) as $b)
                            <li class="list-group-item"><small>{{ $b->booking_date }} - {{ $b->room_name ?? '' }}</small> <span class="badge bg-secondary">{{ $b->status }}</span></li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
