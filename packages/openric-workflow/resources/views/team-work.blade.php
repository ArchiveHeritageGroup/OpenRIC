@extends('theme::layouts.1col')
@section('title', 'Team Work')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-users me-3"></i><div><h1 class="mb-0">Team Work</h1></div></div>
@if(isset($rows) && count($rows))
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0">
        <thead><tr><th>#</th><th>Member</th><th>Tasks</th><th>Completed</th><th>Overdue</th><th>Avg Duration</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr>@foreach((array)$row as $v)<td>{{ $v }}</td>@endforeach</tr>@endforeach</tbody>
    </table></div>
@else<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No records found.</div>@endif
@endsection
