@extends('theme::layouts.1col')
@section('title', 'Publish Gate Rules')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Publish Gate Rules</h1>
    <div><a href="{{ route('workflow.admin') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Workflows</a> <a href="{{ route('workflow.gates.edit') }}" class="btn btn-success"><i class="fas fa-plus me-1"></i>Create Rule</a></div>
</div>
<p class="text-muted">Gate rules define the criteria an object must meet before it can be published.</p>
@if(count($rules) === 0)
    <div class="alert alert-info">No gate rules configured.</div>
@else
    <div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr><th>Order</th><th>Name</th><th>Type</th><th>Entity</th><th>Severity</th><th>Field</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>@foreach($rules as $rule)<tr><td>{{ $rule->sort_order ?? 0 }}</td><td>{{ $rule->name }}</td><td><span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($rule->rule_type ?? '')) }}</span></td><td>{{ str_replace('_', ' ', ucfirst($rule->entity_type ?? 'any')) }}</td><td>@if(($rule->severity ?? '') === 'blocker')<span class="badge bg-danger">Blocker</span>@else<span class="badge bg-warning text-dark">Warning</span>@endif</td><td>{{ $rule->field_name ?? '-' }}</td><td>@if($rule->is_active ?? false)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td><td><a href="{{ route('workflow.gates.edit', $rule->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a><form action="{{ route('workflow.gates.delete', $rule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf<button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>@endforeach</tbody></table></div></div></div>
@endif
@endsection
