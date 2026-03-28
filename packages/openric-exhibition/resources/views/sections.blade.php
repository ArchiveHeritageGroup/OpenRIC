@extends('theme::layouts.1col')

@section('title', 'Sections — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-th-large me-2"></i>Sections</h1>
        <a href="{{ route('exhibition.show', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <p class="text-muted">{{ $exhibition->title }}</p>

    {{-- Add section form --}}
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add Section</div>
        <div class="card-body">
            <form method="POST" action="{{ route('exhibition.sections.store', $exhibition->id) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Section title" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Description">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="location" class="form-control form-control-sm" placeholder="Location">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (isset($exhibition->sections) && $exhibition->sections->isNotEmpty())
    <div class="list-group">
        @foreach ($exhibition->sections as $sec)
        <div class="list-group-item d-flex justify-content-between align-items-start">
            <div>
                <strong>{{ $sec->title }}</strong>
                @if (!empty($sec->location))
                    <span class="text-muted ms-2"><i class="fas fa-map-marker-alt"></i> {{ $sec->location }}</span>
                @endif
                @if (!empty($sec->description))
                    <br><small class="text-muted">{{ $sec->description }}</small>
                @endif
            </div>
            <form method="POST" action="{{ route('exhibition.sections.destroy', [$exhibition->id, $sec->id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this section?')"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        @endforeach
    </div>
    @else
        <div class="alert alert-info">No sections yet.</div>
    @endif
</div>
@endsection
