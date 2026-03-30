@extends('theme::layout')

@section('title', 'Create Template')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('forms.index') }}">Forms</a></li>
                    <li class="breadcrumb-item active">Create Template</li>
                </ol>
            </nav>
            <h4 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Create Form Template</h4>
        </div>
    </div>

    <form method="post" action="{{ route('forms.template.create') }}">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Template Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="form_type" class="form-label">Form Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="form_type" name="form_type" required>
                                <option value="information_object">Information Object</option>
                                <option value="actor">Authority Record</option>
                                <option value="repository">Repository</option>
                                <option value="accession">Accession</option>
                                <option value="rights">Rights</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Actions</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="atom-btn-white w-100 mb-2">
                            <i class="bi bi-check-lg me-1"></i>Create Template
                        </button>
                        <a href="{{ route('forms.index') }}" class="atom-btn-white w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
