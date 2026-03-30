@extends('theme::layouts.1col')
@section('title', 'My Work')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-user-check me-3"></i><div><h1 class="mb-0">My Work</h1></div></div>
@if(isset($rows) && count($rows))
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0">
        <thead><tr><th>#</th><th>Task</th><th>Workflow</th><th>Step</th><th>Due Date</th><th>Priority</th><th>Actions</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr>@foreach((array)$row as $v)<td>{{ $v }}</td>@endforeach</tr>@endforeach</tbody>
    </table></div>
@else<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No records found.</div>@endif
@endsection
