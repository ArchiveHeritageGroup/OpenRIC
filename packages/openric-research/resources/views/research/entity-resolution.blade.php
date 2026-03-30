@extends('theme::layouts.1col')
@section('title', 'Entity Resolution')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Entity Resolution</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        {{-- Propose Match --}}
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Propose New Match</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.entityResolution') }}">
                    @csrf <input type="hidden" name="form_action" value="propose">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Entity A Type *</label><select name="entity_a_type" class="form-select" required><option value="information_object">Information Object</option><option value="actor">Actor</option><option value="repository">Repository</option></select></div>
                        <div class="col-md-2"><label class="form-label">Entity A ID *</label><input type="number" name="entity_a_id" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Entity B Type *</label><select name="entity_b_type" class="form-select" required><option value="information_object">Information Object</option><option value="actor">Actor</option><option value="repository">Repository</option></select></div>
                        <div class="col-md-2"><label class="form-label">Entity B ID *</label><input type="number" name="entity_b_id" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label">Relationship</label><select name="relationship_type" class="form-select"><option value="sameAs">Same As</option><option value="relatedTo">Related To</option><option value="partOf">Part Of</option><option value="memberOf">Member Of</option></select></div>
                        <div class="col-md-2"><label class="form-label">Confidence</label><input type="number" name="confidence" class="form-control" step="0.01" min="0" max="1"></div>
                        <div class="col-md-6"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control"></div>
                        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Propose</button></div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Proposals --}}
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Entity A</th><th>Entity B</th><th>Relationship</th><th>Confidence</th><th>Status</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @forelse($proposals['items'] ?? [] as $p)
                    <tr>
                        <td>{{ $p->entity_a_label ?? $p->entity_a_type.':'.$p->entity_a_id }}</td>
                        <td>{{ $p->entity_b_label ?? $p->entity_b_type.':'.$p->entity_b_id }}</td>
                        <td>{{ $p->relationship_type ?? '-' }}</td>
                        <td>{{ $p->confidence !== null ? number_format($p->confidence * 100, 1) . '%' : '-' }}</td>
                        <td><span class="badge bg-{{ $p->status === 'proposed' ? 'warning' : ($p->status === 'accepted' ? 'success' : 'secondary') }}">{{ ucfirst($p->status) }}</span></td>
                        <td><small>{{ $p->created_at }}</small></td>
                        <td>
                            @if($p->status === 'proposed')
                                <button class="btn btn-sm btn-outline-success" onclick="fetch('{{ route('research.resolveEntityResolution', $p->id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body:JSON.stringify({status:'accepted'})}).then(()=>location.reload())">Accept</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="fetch('{{ route('research.resolveEntityResolution', $p->id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body:JSON.stringify({status:'rejected'})}).then(()=>location.reload())">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted text-center">No proposals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
