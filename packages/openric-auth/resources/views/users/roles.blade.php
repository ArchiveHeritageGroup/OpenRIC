@extends('theme::layouts.2col')
@section('title', 'Roles')
@section('content')
<h1 class="h3 mb-4"><i class="bi bi-person-badge me-2"></i>Roles</h1>
@include('theme::partials.alerts')
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Name</th><th>Label</th><th>Level</th><th>Users</th><th>Description</th></tr></thead>
        <tbody>
            @foreach($roles as $role)
                <tr>
                    <td><code>{{ $role->name }}</code></td>
                    <td>{{ $role->label }}</td>
                    <td><span class="badge bg-primary">{{ $role->level }}</span></td>
                    <td>{{ $role->user_count ?? 0 }}</td>
                    <td>{{ $role->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
