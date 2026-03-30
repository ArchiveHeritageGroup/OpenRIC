@extends('theme::layouts.1col')
@section('title', 'Research Statistics')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Research Statistics</h2>

        <form method="GET" action="{{ route('research.adminStatistics') }}" class="row g-3 mb-4">
            <div class="col-auto"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}"></div>
            <div class="col-auto"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $dateTo }}"></div>
            <div class="col-auto d-flex align-items-end"><button class="btn btn-primary">Filter</button></div>
        </form>

        <div class="row g-3">
            @foreach($stats as $label => $value)
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3>{{ $value }}</h3>
                            <p class="mb-0 text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
