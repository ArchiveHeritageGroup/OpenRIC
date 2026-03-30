@extends('theme::layouts.1col')
@section('title', 'Walk-In Visitors')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Walk-In Visitors</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="GET" action="{{ route('research.walkIn') }}" class="mb-3">
            <div class="row g-3"><div class="col-md-4">
                <select name="room_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Room --</option>
                    @foreach($rooms as $r)<option value="{{ $r->id }}" {{ $roomId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach
                </select>
            </div></div>
        </form>

        @if($currentRoom)
            <h5 class="mb-3">{{ $currentRoom->name }} - Current Visitors</h5>

            @if(!empty($currentWalkIns))
                <div class="table-responsive mb-4">
                    <table class="table table-striped">
                        <thead><tr><th>Name</th><th>Organization</th><th>Checked In</th><th></th></tr></thead>
                        <tbody>
                        @foreach($currentWalkIns as $v)
                            <tr>
                                <td>{{ $v->first_name }} {{ $v->last_name }}</td>
                                <td>{{ $v->organization ?? '-' }}</td>
                                <td>{{ $v->checked_in_at }}</td>
                                <td>
                                    <form method="POST" action="{{ route('research.walkIn', ['room_id' => $roomId]) }}">@csrf <input type="hidden" name="form_action" value="checkout"><input type="hidden" name="visitor_id" value="{{ $v->id }}"><button class="btn btn-sm btn-outline-warning">Check Out</button></form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-4">No current visitors.</p>
            @endif

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Register Walk-In Visitor</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('research.walkIn', ['room_id' => $roomId]) }}">
                        @csrf <input type="hidden" name="form_action" value="register">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Organization</label><input type="text" name="organization" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">ID Type</label><select name="id_type" class="form-select"><option value="">--</option><option value="passport">Passport</option><option value="national_id">National ID</option><option value="drivers_license">Driver's License</option></select></div>
                            <div class="col-md-4"><label class="form-label">ID Number</label><input type="text" name="id_number" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Purpose</label><input type="text" name="purpose" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Research Topic</label><input type="text" name="research_topic" class="form-control"></div>
                            <div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="rules_acknowledged" class="form-check-input" value="1"><label class="form-check-label">Rules Acknowledged</label></div></div>
                            <div class="col-12"><button type="submit" class="btn btn-primary">Register Visitor</button></div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
