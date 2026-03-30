@extends('theme::layouts.1col')
@section('title', 'Institutions')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Institutions</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Type</th><th>Country</th><th>Website</th></tr></thead>
                <tbody>
                @forelse($institutions as $inst)
                    <tr>
                        <td>{{ $inst->name }}</td>
                        <td>{{ $inst->institution_type ?? '-' }}</td>
                        <td>{{ $inst->country ?? '-' }}</td>
                        <td>@if($inst->website ?? null)<a href="{{ $inst->website }}" target="_blank">{{ $inst->website }}</a>@else - @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No institutions recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
