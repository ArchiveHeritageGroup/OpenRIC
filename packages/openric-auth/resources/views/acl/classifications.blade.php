@extends('theme::layouts.1col')
@section('title', 'Security Classifications')
@section('content')
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Admin</a></li><li class="breadcrumb-item"><a href="{{ route('acl.groups') }}">ACL</a></li><li class="breadcrumb-item active">Security Classifications</li></ol></nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-shield-lock me-2"></i>Security Classifications</h2>
    <a href="{{ route('acl.groups') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to ACL</a>
</div>
<div class="card"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="bi bi-layers me-2"></i>Classification Levels</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered table-striped table-hover mb-0"><thead><tr><th>Color</th><th>Code</th><th>Name</th><th class="text-center">Level</th><th class="text-center">2FA</th><th class="text-center">Watermark</th><th class="text-center">Download</th><th class="text-center">Print</th><th class="text-center">Copy</th></tr></thead><tbody>
@forelse($classifications as $cls)
<tr><td><span class="d-inline-block rounded-circle" style="width:20px;height:20px;background-color:{{ $cls->color ?? '#ccc' }};"></span></td><td><code>{{ $cls->code }}</code></td><td><strong>{{ $cls->name }}</strong></td><td class="text-center"><span class="badge bg-secondary">{{ $cls->level }}</span></td><td class="text-center">{!! $cls->requires_2fa ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' !!}</td><td class="text-center">{!! $cls->watermark_required ? '<i class="bi bi-check-circle text-warning"></i>' : '<i class="bi bi-x-circle text-muted"></i>' !!}</td><td class="text-center">{!! $cls->download_allowed ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td><td class="text-center">{!! $cls->print_allowed ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td><td class="text-center">{!! $cls->copy_allowed ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td></tr>
@empty
<tr><td colspan="9" class="text-center text-muted py-4">No classification levels defined.</td></tr>
@endforelse
</tbody></table></div></div></div>
@endsection
