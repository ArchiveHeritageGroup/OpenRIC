@extends('theme::layouts.2col')
@section('title', 'Condition Assessments')
@section('sidebar') @include('theme::partials.sidebar') @endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Condition Assessments</h1>
    <a href="{{ route('condition.create') }}" class="btn btn-primary">New Assessment</a>
</div>
@include('theme::partials.alerts')
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Object</th><th>Condition</th><th>Priority</th><th>Assessor</th><th>Date</th></tr></thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td><code class="small">{{ \Illuminate\Support\Str::limit($item['object_iri'], 50) }}</code></td>
                    <td><span class="badge bg-info">{{ $item['condition_label'] }}</span></td>
                    <td>{{ $item['conservation_priority'] }}</td>
                    <td>{{ $item['assessor_name'] ?? '-' }}</td>
                    <td>{{ $item['assessed_at'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center">No assessments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
