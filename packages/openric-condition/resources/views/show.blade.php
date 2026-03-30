@extends('theme::layouts.1col')

@section('title', 'Condition Assessment #' . $check->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Condition Assessment #{{ $check->id }}</h1>
    <div>
        <a href="{{ route('condition.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <a href="{{ route('condition.export', $check->id) }}" class="btn btn-outline-primary"><i class="fas fa-download me-1"></i>Export</a>
    </div>
</div>

@include('theme::partials.alerts')

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Assessment Details</h5></div>
            <div class="card-body">
                <table class="table table-bordered table-sm mb-0">
                    <tr><th style="width:35%">Object IRI</th><td><code>{{ $check->object_iri }}</code></td></tr>
                    <tr><th>Condition</th><td><span class="badge bg-info">{{ $check->condition_code }}</span> {{ $check->condition_label }}</td></tr>
                    <tr><th>Conservation Priority</th><td>{{ $check->conservation_priority }}/5</td></tr>
                    <tr><th>Completeness</th><td>{{ $check->completeness_pct }}%</td></tr>
                    <tr><th>Assessed By</th><td>{{ $check->assessor_name ?? '-' }}</td></tr>
                    <tr><th>Assessed At</th><td>{{ $check->assessed_at }}</td></tr>
                    @if($check->next_assessment_date)<tr><th>Next Assessment</th><td>{{ $check->next_assessment_date }}</td></tr>@endif
                    @if($check->storage_requirements)<tr><th>Storage Requirements</th><td>{{ $check->storage_requirements }}</td></tr>@endif
                    @if($check->recommendations)<tr><th>Recommendations</th><td>{{ $check->recommendations }}</td></tr>@endif
                    @if($check->notes)<tr><th>Notes</th><td>{{ $check->notes }}</td></tr>@endif
                </table>
            </div>
        </div>

        {{-- Photos --}}
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Photos ({{ $check->annotation_stats['total_photos'] ?? 0 }})</h5>
            </div>
            <div class="card-body">
                @if($check->photos && $check->photos->count())
                    <div class="row g-3">
                        @foreach($check->photos as $photo)
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                        <p class="small mb-1">{{ $photo->caption ?? $photo->photo_type ?? 'Photo' }}</p>
                                        <span class="badge bg-secondary">{{ $photo->photo_type ?? '' }}</span>
                                        <div class="mt-2">
                                            <form action="{{ route('condition.delete-photo', $photo->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this photo?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No photos uploaded yet.</p>
                @endif

                <hr>
                <h6>Upload Photo</h6>
                <form action="{{ route('condition.upload-photo', $check->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Photo</label><input type="file" class="form-control" name="photo" accept="image/*" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Type</label><select name="photo_type" class="form-select"><option value="before">Before</option><option value="after">After</option><option value="damage">Damage</option><option value="overview">Overview</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Caption</label><input type="text" class="form-control" name="caption"></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Upload</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Annotation Stats --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Annotation Stats</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th>Total Photos</th><td>{{ $check->annotation_stats['total_photos'] ?? 0 }}</td></tr>
                    <tr><th>Annotated Photos</th><td>{{ $check->annotation_stats['annotated_photos'] ?? 0 }}</td></tr>
                    <tr><th>Total Annotations</th><td>{{ $check->annotation_stats['total_annotations'] ?? 0 }}</td></tr>
                </table>
            </div>
        </div>

        {{-- History --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Assessment History</h6></div>
            <div class="card-body p-0">
                @if(!empty($history))
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($history, 0, 10) as $h)
                            <a href="{{ route('conditions.show', $h['id']) }}" class="list-group-item list-group-item-action {{ $h['id'] == $check->id ? 'active' : '' }}">
                                <div class="d-flex justify-content-between">
                                    <small><span class="badge bg-info">{{ $h['condition_code'] }}</span></small>
                                    <small>{{ $h['assessed_at'] }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-3">No history.</div>
                @endif
            </div>
        </div>

        {{-- Templates --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Templates</h6></div>
            <div class="card-body">
                @foreach($templates as $t)
                    <div class="mb-2"><span class="badge bg-secondary">{{ $t['name'] }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
