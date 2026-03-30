@extends('theme::layouts.1col')
@section('title', 'Document Templates')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Document Templates</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Create Template</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.documentTemplates') }}">
                    @csrf <input type="hidden" name="form_action" value="create">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Document Type *</label><select name="document_type" class="form-select" required><option value="letter">Letter</option><option value="certificate">Certificate</option><option value="receipt">Receipt</option><option value="report">Report</option><option value="agreement">Agreement</option></select></div>
                        <div class="col-md-5"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Fields JSON</label><textarea name="fields_json" class="form-control" rows="2" placeholder='[{"name":"field1","type":"text","label":"Field 1"}]'></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary">Create Template</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Type</th><th>Description</th><th>Created</th></tr></thead>
                <tbody>
                @forelse($templates as $t)
                    <tr>
                        <td><strong>{{ $t->name }}</strong></td>
                        <td>{{ ucfirst($t->document_type ?? '-') }}</td>
                        <td>{{ $t->description ?? '-' }}</td>
                        <td><small>{{ $t->created_at }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No templates.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
