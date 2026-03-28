@extends('theme::layouts.1col')

@section('title', 'Exhibition Dashboard')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3"><i class="fas fa-tachometer-alt me-2"></i>Exhibition Dashboard</h1>

    {{-- Stats --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $stats['total'] }}</div>
                    <small class="text-muted">Total Exhibitions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <div class="h2 mb-0 text-success">{{ $stats['active'] }}</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <div class="h2 mb-0 text-warning">{{ $stats['planning'] }}</div>
                    <small class="text-muted">Planning</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-secondary">
                <div class="card-body">
                    <div class="h2 mb-0 text-secondary">{{ $stats['completed'] }}</div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Active exhibitions --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-star me-1"></i> Active Exhibitions</span>
            <a href="{{ route('exhibition.index', ['status' => 'active']) }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        @if ($activeExhibitions->isEmpty())
            <div class="card-body"><p class="text-muted mb-0">No active exhibitions.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Title</th><th>Venue</th><th>Dates</th><th>Curator</th></tr></thead>
                    <tbody>
                        @foreach ($activeExhibitions as $ex)
                        <tr>
                            <td><a href="{{ route('exhibition.show', $ex->id) }}">{{ $ex->title }}</a></td>
                            <td>{{ $ex->venue ?? '' }}</td>
                            <td>
                                @if ($ex->start_date)
                                    {{ \Carbon\Carbon::parse($ex->start_date)->format('d M Y') }}
                                    @if ($ex->end_date) &ndash; {{ \Carbon\Carbon::parse($ex->end_date)->format('d M Y') }} @endif
                                @endif
                            </td>
                            <td>{{ $ex->curator ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
