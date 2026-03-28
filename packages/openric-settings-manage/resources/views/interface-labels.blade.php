@extends('theme::layouts.1col')

@section('title', 'Interface Labels Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-tags me-2"></i>Interface Labels</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.interface-labels') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-3">Customize the labels displayed throughout the application interface.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%">Label Key</th>
                                <th style="width: 35%">Default Value</th>
                                <th style="width: 35%">Custom Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($settings as $key => $label)
                            <tr>
                                <td><code>{{ $key }}</code></td>
                                <td class="text-muted">{{ $label['default'] ?? $key }}</td>
                                <td>
                                    <input type="text" name="labels[{{ $key }}]" class="form-control form-control-sm" value="{{ $label['custom'] ?? $label['default'] ?? '' }}">
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No interface labels configured.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                <button type="submit" name="reset_defaults" value="1" class="btn btn-outline-warning"><i class="fas fa-undo me-1"></i> Reset to Defaults</button>
            </div>
        </div>
    </form>
</div>
@endsection
