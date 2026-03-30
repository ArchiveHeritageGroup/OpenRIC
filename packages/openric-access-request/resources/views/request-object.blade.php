@extends('openric-theme::layouts.1col')

@section('title', 'Request Access to Object')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('homepage') }}">Home</a></li>
                    <li class="breadcrumb-item active">Request Object Access</li>
                </ol>
            </nav>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>Request Access to: {{ $slug }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('accessRequest.storeObjectRequest') }}" method="POST">
                        @csrf
                        <input type="hidden" name="object_id" value="{{ $slug }}">

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required>{{ old('description') }}</textarea>
                            <div class="form-text">Describe why you need access to this material.</div>
                        </div>

                        <div class="mb-3">
                            <label for="justification" class="form-label">Justification <span class="text-muted">(Optional)</span></label>
                            <textarea class="form-control" id="justification" name="justification" rows="3">{{ old('justification') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
