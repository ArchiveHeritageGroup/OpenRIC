@extends('theme::layouts.1col')
@section('title', 'Integrity Check Results')
@section('content')
<h1 class="h3 mb-4">Integrity Check Results</h1>
@if($results)
    <div class="small text-muted mb-3">Run ID: {{ $results['run_id'] ?? '' }} | {{ $results['started_at'] ?? '' }} - {{ $results['completed_at'] ?? '' }}</div>
    @foreach($results['checks'] ?? [] as $key => $check)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-{{ ($check['passed'] ?? false) ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>{{ $check['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}</h6>
                <span class="badge bg-{{ ($check['count'] ?? 0) > 0 ? 'danger' : 'success' }}">{{ $check['count'] ?? 0 }}</span>
            </div>
            @if(!empty($check['details']))
                <div class="card-body p-0">
                    <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                        @foreach(array_slice($check['details'], 0, 20) as $detail)
                            <tr>@foreach($detail as $k => $v)<td><small><strong>{{ $k }}:</strong> {{ $v }}</small></td>@endforeach</tr>
                        @endforeach
                    </tbody></table></div>
                </div>
            @endif
        </div>
    @endforeach
@else
    <div class="alert alert-info">No results found. Run an integrity check first.</div>
@endif
<a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
@endsection
