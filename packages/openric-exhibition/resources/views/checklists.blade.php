@extends('theme::layouts.1col')

@section('title', 'Checklists — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-tasks me-2"></i>Checklists</h1>
        <a href="{{ route('exhibition.show', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <p class="text-muted">{{ $exhibition->title }}</p>

    {{-- Add checklist item form --}}
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add Checklist Item</div>
        <div class="card-body">
            <form method="POST" action="{{ route('exhibition.checklists.store', $exhibition->id) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Item title" required>
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-select form-select-sm">
                            <option value="general">General</option>
                            <option value="logistics">Logistics</option>
                            <option value="conservation">Conservation</option>
                            <option value="installation">Installation</option>
                            <option value="marketing">Marketing</option>
                            <option value="security">Security</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="due_date" class="form-control form-control-sm" placeholder="Due date">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (isset($exhibition->checklists) && $exhibition->checklists->isNotEmpty())
    @php
        $grouped = $exhibition->checklists->groupBy('category');
    @endphp
    @foreach ($grouped as $category => $items)
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-folder me-1"></i> {{ ucfirst($category) }}</div>
        <ul class="list-group list-group-flush">
            @foreach ($items as $cl)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <form method="POST" action="{{ route('exhibition.checklists.toggle', [$exhibition->id, $cl->id]) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm p-0 me-2" title="Toggle">
                            <i class="fas {{ $cl->is_completed ? 'fa-check-square text-success' : 'fa-square text-muted' }}"></i>
                        </button>
                    </form>
                    <span class="{{ $cl->is_completed ? 'text-decoration-line-through text-muted' : '' }}">{{ $cl->title }}</span>
                    @if ($cl->due_date)
                        <small class="text-muted ms-2"><i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($cl->due_date)->format('d M Y') }}</small>
                    @endif
                    @if (!empty($cl->notes))
                        <small class="text-muted ms-2">{{ $cl->notes }}</small>
                    @endif
                </div>
                <form method="POST" action="{{ route('exhibition.checklists.destroy', [$exhibition->id, $cl->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                </form>
            </li>
            @endforeach
        </ul>
    </div>
    @endforeach
    @else
        <div class="alert alert-info">No checklist items yet.</div>
    @endif
</div>
@endsection
