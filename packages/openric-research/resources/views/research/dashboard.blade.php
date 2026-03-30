@extends('theme::layouts.1col')
@section('title', 'Research Dashboard')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Research Dashboard</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{!! session('error') !!}</div>@endif

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card text-bg-primary"><div class="card-body"><h5 class="card-title">{{ $stats['total_researchers'] ?? 0 }}</h5><p class="card-text mb-0">Active Researchers</p></div></div></div>
            <div class="col-md-3"><div class="card text-bg-success"><div class="card-body"><h5 class="card-title">{{ $stats['today_bookings'] ?? 0 }}</h5><p class="card-text mb-0">Today's Bookings</p></div></div></div>
            <div class="col-md-3"><div class="card text-bg-info"><div class="card-body"><h5 class="card-title">{{ $stats['week_bookings'] ?? 0 }}</h5><p class="card-text mb-0">This Week</p></div></div></div>
            <div class="col-md-3"><div class="card text-bg-warning"><div class="card-body"><h5 class="card-title">{{ $stats['pending_requests'] ?? 0 }}</h5><p class="card-text mb-0">Pending Requests</p></div></div></div>
        </div>

        <div class="row g-4">
            {{-- Researcher Status --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">My Research Status</h5></div>
                    <div class="card-body">
                        @if($researcher)
                            <p><strong>Name:</strong> {{ $researcher->first_name }} {{ $researcher->last_name }}</p>
                            <p><strong>Status:</strong> <span class="badge bg-{{ $researcher->status === 'approved' ? 'success' : ($researcher->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($researcher->status) }}</span></p>
                            @if($researcher->institution)<p><strong>Institution:</strong> {{ $researcher->institution }}</p>@endif
                        @else
                            <p>You are not registered as a researcher.</p>
                            <a href="{{ route('researcher.register') }}" class="btn btn-primary">Register Now</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upcoming Bookings --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Upcoming Bookings</h5></div>
                    <div class="card-body">
                        @if(!empty($enhancedData['upcoming_bookings']))
                            <ul class="list-group list-group-flush">
                                @foreach($enhancedData['upcoming_bookings'] as $booking)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ $booking->booking_date }} - {{ $booking->room_name ?? 'Room' }}</span>
                                        <span class="badge bg-info">{{ $booking->status }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No upcoming bookings.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pending Approvals (Admin) --}}
            @if($isAdmin ?? false)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Pending Approvals</h5></div>
                    <div class="card-body">
                        @if(!empty($pendingApprovals))
                            <ul class="list-group list-group-flush">
                                @foreach(array_slice($pendingApprovals, 0, 5) as $pending)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <a href="{{ route('research.viewResearcher', $pending->id) }}">{{ $pending->first_name }} {{ $pending->last_name }}</a>
                                        <small class="text-muted">{{ $pending->created_at }}</small>
                                    </li>
                                @endforeach
                            </ul>
                            @if(count($pendingApprovals) > 5)
                                <a href="{{ route('research.researchers', ['filter' => 'pending']) }}" class="btn btn-sm btn-outline-primary mt-2">View all</a>
                            @endif
                        @else
                            <p class="text-muted mb-0">No pending approvals.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Today's Schedule --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Today's Schedule</h5></div>
                    <div class="card-body">
                        @if(!empty($todaySchedule))
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Time</th><th>Researcher</th><th>Room</th></tr></thead>
                                <tbody>
                                @foreach($todaySchedule as $b)
                                    <tr>
                                        <td>{{ $b->start_time ?? '' }}</td>
                                        <td>{{ $b->first_name ?? '' }} {{ $b->last_name ?? '' }}</td>
                                        <td>{{ $b->room_name ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted mb-0">No bookings today.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Recent Activity</h5></div>
                    <div class="card-body">
                        @if(!empty($recentActivity))
                            <ul class="list-group list-group-flush">
                                @foreach(array_slice($recentActivity, 0, 5) as $act)
                                    <li class="list-group-item">
                                        <small class="text-muted">{{ $act->created_at }}</small>
                                        <br>{{ $act->activity_type ?? $act->action ?? '' }} - {{ $act->entity_title ?? $act->description ?? '' }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No recent activity.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Journal --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Recent Journal Entries</h5></div>
                    <div class="card-body">
                        @if(!empty($recentJournalEntries))
                            <ul class="list-group list-group-flush">
                                @foreach($recentJournalEntries as $je)
                                    <li class="list-group-item">
                                        <a href="{{ route('research.journalEntry', $je->id) }}">{{ $je->title ?? 'Untitled' }}</a>
                                        <small class="text-muted d-block">{{ $je->entry_date }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No journal entries yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
