@extends('theme::layouts.1col')
@section('title', 'Registration Complete')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="text-center py-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
            <h2 class="mt-3">Registration Submitted</h2>
            <p class="lead text-muted">Your researcher registration has been submitted for review. You will be notified once your application has been processed.</p>
            <a href="{{ route('research.dashboard') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
        </div>
    </div>
</div>
@endsection
