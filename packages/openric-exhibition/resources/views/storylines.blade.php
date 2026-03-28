@extends('theme::layouts.1col')

@section('title', 'Storylines — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-stream me-2"></i>Storylines</h1>
        <a href="{{ route('exhibition.show', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <p class="text-muted">{{ $exhibition->title }}</p>

    {{-- Add storyline form --}}
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add Storyline</div>
        <div class="card-body">
            <form method="POST" action="{{ route('exhibition.storylines.store', $exhibition->id) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Storyline title" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Description (optional)">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (isset($exhibition->storylines) && $exhibition->storylines->isNotEmpty())
    <div class="list-group">
        @foreach ($exhibition->storylines as $sl)
        <div class="list-group-item d-flex justify-content-between align-items-start">
            <div>
                <a href="{{ route('exhibition.storyline', [$exhibition->id, $sl->id]) }}" class="fw-bold text-decoration-none">{{ $sl->title }}</a>
                @if (!empty($sl->description))
                    <br><small class="text-muted">{{ $sl->description }}</small>
                @endif
            </div>
            <form method="POST" action="{{ route('exhibition.storylines.destroy', [$exhibition->id, $sl->id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this storyline?')"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        @endforeach
    </div>
    @else
        <div class="alert alert-info">No storylines yet.</div>
    @endif
</div>
@endsection
