@extends('theme::layouts.1col')

@section('title', 'System Information')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-server me-2"></i>System Information</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Server Environment</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td class="fw-bold w-50">PHP Version</td><td>{{ $info['php_version'] }}</td></tr>
                            <tr><td class="fw-bold">Laravel Version</td><td>{{ $info['laravel_version'] }}</td></tr>
                            <tr><td class="fw-bold">Server Software</td><td>{{ $info['server_software'] }}</td></tr>
                            <tr><td class="fw-bold">Operating System</td><td>{{ $info['os'] }}</td></tr>
                            <tr><td class="fw-bold">Memory Limit</td><td>{{ $info['memory_limit'] }}</td></tr>
                            <tr><td class="fw-bold">Max Execution Time</td><td>{{ $info['max_execution_time'] }}s</td></tr>
                            <tr><td class="fw-bold">Upload Max Filesize</td><td>{{ $info['upload_max_filesize'] }}</td></tr>
                            <tr><td class="fw-bold">Post Max Size</td><td>{{ $info['post_max_size'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Database &amp; Disk</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td class="fw-bold w-50">Database Size</td><td>{{ $info['database_size'] }}</td></tr>
                            <tr><td class="fw-bold">Table Count</td><td>{{ $info['table_count'] }}</td></tr>
                            <tr><td class="fw-bold">Disk Free</td><td>{{ $info['disk_free_gb'] }} GB</td></tr>
                            <tr><td class="fw-bold">Disk Total</td><td>{{ $info['disk_total_gb'] }} GB</td></tr>
                            <tr><td class="fw-bold">Disk Used</td><td>{{ $info['disk_used_pct'] }}%</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">PHP Extensions ({{ count($info['extensions']) }})</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($info['extensions'] as $ext)
                            <span class="badge bg-light text-dark">{{ $ext }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
