@extends('theme::layouts.1col')
@section('title', 'Request Access')
@section('content')
<div class="row justify-content-center"><div class="col-md-8">
<h1><i class="bi bi-hand-index"></i> Request Access</h1>
<div class="card mb-4"><div class="card-header"><h5 class="mb-0">Requested Resource</h5></div><div class="card-body"><p><strong>Title:</strong> {{ e($object->title ?? 'Untitled') }}</p>@if($classification ?? null)<p><strong>Classification:</strong> <span class="badge" style="background-color:{{ $classification->color ?? '#666' }}">{{ e($classification->name ?? '') }}</span></p>@endif</div></div>
@if($userClearance ?? null)<div class="alert alert-info"><strong>Your Current Clearance:</strong> <span class="badge" style="background-color:{{ $userClearance->color ?? '#666' }}">{{ e($userClearance->name ?? '') }}</span></div>@else<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> You do not currently have a security clearance.</div>@endif
<div class="card"><div class="card-header"><h5 class="mb-0">Access Request Details</h5></div><div class="card-body"><form action="{{ route('acl.submit-access-request') }}" method="post">@csrf<input type="hidden" name="object_iri" value="{{ $object->iri ?? '' }}">
<div class="mb-3"><label class="form-label">Type of Access *</label><select name="request_type" class="form-select" required><option value="view">View Only</option><option value="download">Download</option><option value="print">Print</option></select></div>
<div class="mb-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="normal">Normal</option><option value="urgent">Urgent</option><option value="immediate">Immediate</option></select></div>
<div class="mb-3"><label class="form-label">Justification *</label><textarea name="justification" class="form-control" rows="5" required minlength="20" placeholder="Detailed justification..."></textarea></div>
<div class="d-grid gap-2"><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-send"></i> Submit Request</button><a href="{{ route('security.my-requests') }}" class="btn btn-outline-secondary">View My Requests</a></div>
</form></div></div>
</div></div>
@endsection
