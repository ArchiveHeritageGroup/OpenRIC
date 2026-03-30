@extends('theme::layouts.1col')
@section('title', 'ODRL Policies')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">ODRL Digital Rights Policies</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Create Policy</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.odrlPolicies') }}">
                    @csrf <input type="hidden" name="form_action" value="create">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Target Type *</label><select name="target_type" class="form-select" required><option value="information_object">Information Object</option><option value="digital_object">Digital Object</option><option value="collection">Collection</option></select></div>
                        <div class="col-md-2"><label class="form-label">Target ID *</label><input type="number" name="target_id" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label">Policy Type *</label><select name="policy_type" class="form-select" required><option value="permission">Permission</option><option value="prohibition">Prohibition</option><option value="duty">Duty</option></select></div>
                        <div class="col-md-3"><label class="form-label">Action *</label><select name="action_type" class="form-select" required><option value="view">View</option><option value="download">Download</option><option value="reproduce">Reproduce</option><option value="distribute">Distribute</option><option value="modify">Modify</option></select></div>
                        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Create</button></div>
                        <div class="col-12"><label class="form-label">Constraints JSON (optional)</label><textarea name="constraints_json" class="form-control" rows="2" placeholder='{"researcher_ids": [1,2], "date_from": "2026-01-01"}'></textarea></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Target</th><th>Policy</th><th>Action</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @forelse($policies['items'] ?? [] as $p)
                    <tr>
                        <td>{{ $p->target_type }}:{{ $p->target_id }}</td>
                        <td><span class="badge bg-{{ $p->policy_type === 'permission' ? 'success' : ($p->policy_type === 'prohibition' ? 'danger' : 'info') }}">{{ ucfirst($p->policy_type) }}</span></td>
                        <td>{{ $p->action_type }}</td>
                        <td><small>{{ $p->created_at }}</small></td>
                        <td>
                            <form method="POST" action="{{ route('research.odrlPolicies') }}" class="d-inline">@csrf <input type="hidden" name="form_action" value="delete"><input type="hidden" name="policy_id" value="{{ $p->id }}"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No policies.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
