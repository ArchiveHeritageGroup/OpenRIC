@extends('theme::layouts.1col')

@section('title', 'Create Exhibition')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3"><i class="fas fa-plus me-2"></i>Create Exhibition</h1>

    <form method="POST" action="{{ route('exhibition.store') }}">
        @csrf
        @include('openric-exhibition::_form', ['exhibition' => null])

        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create</button>
            <a href="{{ route('exhibition.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
