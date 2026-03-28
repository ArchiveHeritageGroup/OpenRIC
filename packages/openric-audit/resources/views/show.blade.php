@extends('theme::layouts.2col')

@section('title', 'Audit Entry')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">Audit Entry #{{ $entry['id'] }}</h1>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Date</dt>
                <dd class="col-sm-9">{{ $entry['created_at'] }}</dd>

                <dt class="col-sm-3">User</dt>
                <dd class="col-sm-9">{{ $entry['username'] ?? 'System' }} ({{ $entry['user_email'] ?? '-' }})</dd>

                <dt class="col-sm-3">Action</dt>
                <dd class="col-sm-9"><span class="badge bg-secondary">{{ $entry['action'] }}</span></dd>

                <dt class="col-sm-3">Entity Type</dt>
                <dd class="col-sm-9">{{ $entry['entity_type'] ?? '-' }}</dd>

                <dt class="col-sm-3">Entity ID</dt>
                <dd class="col-sm-9"><code>{{ $entry['entity_id'] ?? '-' }}</code></dd>

                <dt class="col-sm-3">Entity Title</dt>
                <dd class="col-sm-9">{{ $entry['entity_title'] ?? '-' }}</dd>

                <dt class="col-sm-3">IP Address</dt>
                <dd class="col-sm-9">{{ $entry['ip_address'] ?? '-' }}</dd>

                @if($entry['old_values'])
                    <dt class="col-sm-3">Old Values</dt>
                    <dd class="col-sm-9"><pre class="bg-light p-2 rounded"><code>{{ json_encode(json_decode($entry['old_values']), JSON_PRETTY_PRINT) }}</code></pre></dd>
                @endif

                @if($entry['new_values'])
                    <dt class="col-sm-3">New Values</dt>
                    <dd class="col-sm-9"><pre class="bg-light p-2 rounded"><code>{{ json_encode(json_decode($entry['new_values']), JSON_PRETTY_PRINT) }}</code></pre></dd>
                @endif
            </dl>
        </div>
    </div>

    <a href="{{ route('audit.browse') }}" class="btn btn-secondary mt-3">Back to Audit Trail</a>
@endsection
