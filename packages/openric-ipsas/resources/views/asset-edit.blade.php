@extends('theme::layouts.1col')

@section('title', 'Edit Asset')

@section('content')
<h1>Edit Heritage Asset</h1>

<form method="POST">
    @csrf
    
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Asset Details</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ $asset->title ?? '' }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ ($asset->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($asset->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="disposed" {{ ($asset->status ?? '') == 'disposed' ? 'selected' : '' }}>Disposed</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ $asset->description ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="{{ $asset->location ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Condition Rating</label>
                    <select name="condition_rating" class="form-select">
                        <option value="">Select Rating</option>
                        <option value="excellent" {{ ($asset->condition_rating ?? '') == 'excellent' ? 'selected' : '' }}>Excellent</option>
                        <option value="good" {{ ($asset->condition_rating ?? '') == 'good' ? 'selected' : '' }}>Good</option>
                        <option value="fair" {{ ($asset->condition_rating ?? '') == 'fair' ? 'selected' : '' }}>Fair</option>
                        <option value="poor" {{ ($asset->condition_rating ?? '') == 'poor' ? 'selected' : '' }}>Poor</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ route('ipsas.asset.view', $asset->id) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>
@endsection
