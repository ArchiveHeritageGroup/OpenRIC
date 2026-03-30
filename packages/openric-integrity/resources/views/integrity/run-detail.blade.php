@extends('theme::layouts.1col')
@section('title', 'Integrity - Run Detail')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Run Detail</h1></div></div>
@if($run)
<div class="card mb-4"><div class="card-header"><h5 class="mb-0">Run Details</h5></div>
<div class="card-body"><div class="row"><div class="col-md-6"><dl>
    <dt>Run ID</dt><dd>{{ $run['run_id'] ?? '' }}</dd>
    <dt>Started</dt><dd>{{ $run['started_at'] ?? '' }}</dd>
    <dt>Completed</dt><dd>{{ $run['completed_at'] ?? '' }}</dd>
</dl></div><div class="col-md-6"><dl>
    <dt>Total Checks</dt><dd>{{ count($run['checks'] ?? []) }}</dd>
    <dt>Passed</dt><dd>{{ collect($run['checks'] ?? [])->where('passed', true)->count() }}</dd>
    <dt>Failed</dt><dd>{{ collect($run['checks'] ?? [])->where('passed', false)->count() }}</dd>
</dl></div></div></div></div>

@foreach($run['checks'] ?? [] as $key => $check)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-{{ ($check['passed'] ?? false) ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>{{ $check['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}</h6>
            <span class="badge bg-{{ ($check['count'] ?? 0) > 0 ? 'danger' : 'success' }}">{{ $check['count'] ?? 0 }}</span>
        </div>
        @if(!empty($check['details']))
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><tbody>
            @foreach(array_slice($check['details'], 0, 20) as $detail)
                <tr>@foreach($detail as $k => $v)<td><small><strong>{{ $k }}:</strong> {{ \Illuminate\Support\Str::limit((string)$v, 80) }}</small></td>@endforeach</tr>
            @endforeach
        </tbody></table></div></div>
        @endif
    </div>
@endforeach
@else
    <div class="alert alert-warning">Run not found.</div>
@endif
<div class="mt-3"><a href="{{ route('integrity.runs') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Runs</a></div>
@endsection
