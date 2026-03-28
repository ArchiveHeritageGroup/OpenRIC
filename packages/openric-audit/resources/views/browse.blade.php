@extends('theme::layouts.2col')

@section('title', 'Audit Trail')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">Audit Trail</h1>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">User</th>
                    <th scope="col">Action</th>
                    <th scope="col">Entity</th>
                    <th scope="col">Title</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->created_at }}</td>
                        <td>{{ $item->username ?? 'System' }}</td>
                        <td><span class="badge bg-secondary">{{ $item->action }}</span></td>
                        <td>{{ $item->entity_type }}</td>
                        <td>
                            <a href="{{ route('audit.show', $item->id) }}">{{ $item->entity_title ?? $item->entity_id ?? '-' }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted text-center">No audit entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($total > $limit)
        <nav aria-label="Audit pagination">
            <ul class="pagination">
                @if($offset > 0)
                    <li class="page-item"><a class="page-link" href="?offset={{ max(0, $offset - $limit) }}&limit={{ $limit }}">Previous</a></li>
                @endif
                @if($offset + $limit < $total)
                    <li class="page-item"><a class="page-link" href="?offset={{ $offset + $limit }}&limit={{ $limit }}">Next</a></li>
                @endif
            </ul>
        </nav>
    @endif
@endsection
