@extends('theme::layouts.1col')
@section('title', 'Researcher Renewal')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Researcher Registration Renewal</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card" style="max-width:600px;">
            <div class="card-body">
                <p>Your researcher registration status: <span class="badge bg-{{ $researcher->status === 'approved' ? 'success' : 'warning' }}">{{ ucfirst($researcher->status) }}</span></p>
                @if($researcher->expires_at)<p>Expires: <strong>{{ $researcher->expires_at }}</strong></p>@endif
                <form method="POST" action="{{ route('research.renewal') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Reason for renewal</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Briefly explain why you need to renew your researcher access..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Renewal Request</button>
                    <a href="{{ route('research.profile') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
