@extends('theme::layouts.1col')
@section('title', 'AI Output #' . $output->id)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">AI Output #{{ $output->id }}</h1>
    <a href="{{ route('ai-governance.provenance-log') }}" class="btn btn-outline-secondary btn-sm">Back to Log</a>
</div>
@include('theme::partials.alerts')

<div class="row">
    {{-- Left column: output details --}}
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Output Details</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Entity IRI</dt>
                    <dd class="col-sm-8"><code>{{ $output->entity_iri }}</code></dd>
                    <dt class="col-sm-4">Output Type</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">{{ $outputTypes[$output->output_type] ?? $output->output_type }}</span></dd>
                    <dt class="col-sm-4">Model</dt>
                    <dd class="col-sm-8"><code>{{ $output->model_name }}</code>@if($output->model_version) <span class="text-muted small">({{ $output->model_version }})</span>@endif</dd>
                    <dt class="col-sm-4">Pipeline</dt>
                    <dd class="col-sm-8">{{ $output->pipeline_name ?? '-' }}</dd>
                    <dt class="col-sm-4">Confidence</dt>
                    <dd class="col-sm-8">
                        @if($output->confidence_score !== null)
                            <span class="badge bg-{{ $output->confidence_score >= 0.8 ? 'success' : ($output->confidence_score >= 0.5 ? 'warning' : 'danger') }}">{{ number_format($output->confidence_score, 4) }}</span>
                        @else -
                        @endif
                    </dd>
                    <dt class="col-sm-4">Processing Time</dt>
                    <dd class="col-sm-8">{{ $output->processing_time_ms !== null ? number_format($output->processing_time_ms) . ' ms' : '-' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        @php $statusBg = match($output->status) { 'approved' => 'success', 'rejected' => 'danger', 'pending_review' => 'warning', 'auto_applied' => 'info', default => 'secondary' }; @endphp
                        <span class="badge bg-{{ $statusBg }}">{{ $statusOptions[$output->status] ?? $output->status }}</span>
                    </dd>
                    <dt class="col-sm-4">Edit Distance</dt>
                    <dd class="col-sm-8">{{ $output->edit_distance ?? '-' }}</dd>
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ \Carbon\Carbon::parse($output->created_at)->format('Y-m-d H:i:s') }}</dd>
                    @if($output->reviewed_at)
                    <dt class="col-sm-4">Reviewed</dt>
                    <dd class="col-sm-8">{{ \Carbon\Carbon::parse($output->reviewed_at)->format('Y-m-d H:i:s') }} (user #{{ $output->reviewed_by }})</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if(!empty($output->risk_flags))
        <div class="alert alert-danger">
            <strong>Risk Flags:</strong>
            @foreach($output->risk_flags as $flag)
                <span class="badge bg-danger me-1">{{ $flag }}</span>
            @endforeach
        </div>
        @endif

        @if($output->prompt_template)
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Prompt Template</h6></div>
            <div class="card-body"><pre class="mb-0 small" style="white-space: pre-wrap;">{{ $output->prompt_template }}</pre></div>
        </div>
        @endif

        @if($output->input_text)
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Input Text</h6></div>
            <div class="card-body"><pre class="mb-0 small" style="white-space: pre-wrap;">{{ $output->input_text }}</pre></div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Raw AI Output</h6></div>
            <div class="card-body"><pre class="mb-0 small" style="white-space: pre-wrap;">{{ $output->raw_output ?? '(empty)' }}</pre></div>
        </div>

        @if($output->approved_output)
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Approved Output</h6></div>
            <div class="card-body"><pre class="mb-0 small" style="white-space: pre-wrap;">{{ $output->approved_output }}</pre></div>
        </div>
        @endif

        @if($output->review_notes)
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Review Notes</h6></div>
            <div class="card-body"><p class="mb-0">{{ $output->review_notes }}</p></div>
        </div>
        @endif

        @if(!empty($output->retrieved_records))
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Retrieved Records ({{ count($output->retrieved_records) }})</h6></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($output->retrieved_records as $rec)
                        <li><code class="small">{{ $rec }}</code></li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- Right column: review + rate --}}
    <div class="col-md-4">
        {{-- Review form --}}
        @if(in_array($output->status, ['pending_review', 'auto_applied']))
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark"><h5 class="mb-0">Review This Output</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('ai-governance.provenance-review', $output->id) }}">@csrf
                    <div class="mb-3">
                        <label for="review_status" class="form-label">Decision</label>
                        <select class="form-select" id="review_status" name="status" required>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                            <option value="superseded">Superseded</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="approved_output" class="form-label">Edited Output (optional)</label>
                        <textarea class="form-control" id="approved_output" name="approved_output" rows="5" placeholder="Leave blank to approve raw output as-is">{{ $output->raw_output }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="review_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="review_notes" name="review_notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Review</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Rating form --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Rate This Output</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('ai-governance.provenance-rate', $output->id) }}">@csrf
                    <div class="mb-3">
                        <label class="form-label">Satisfaction (1-5)</label>
                        <div class="btn-group w-100" role="group">
                            @for($r = 1; $r <= 5; $r++)
                                <input type="radio" class="btn-check" name="rating" value="{{ $r }}" id="rating_{{ $r }}" @if($r === 3) checked @endif>
                                <label class="btn btn-outline-{{ $r <= 2 ? 'danger' : ($r <= 3 ? 'warning' : 'success') }}" for="rating_{{ $r }}">{{ $r }}</label>
                            @endfor
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="rate_comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="rate_comment" name="comment" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">Submit Rating</button>
                </form>
            </div>
        </div>

        {{-- Existing ratings --}}
        @if(!empty($output->ratings))
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Ratings ({{ count($output->ratings) }})</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($output->ratings as $rating)
                <li class="list-group-item">
                    <span class="badge bg-{{ $rating->rating >= 4 ? 'success' : ($rating->rating >= 3 ? 'warning' : 'danger') }}">{{ $rating->rating }}/5</span>
                    <span class="small ms-1">{{ $rating->user_name ?? 'User #' . $rating->user_id }}</span>
                    @if($rating->comment)<br><span class="small text-muted">{{ $rating->comment }}</span>@endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Provenance chain --}}
        @if(!empty($chain) && count($chain) > 1)
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Provenance Chain ({{ count($chain) }} outputs)</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($chain as $c)
                <li class="list-group-item {{ $c->id === $output->id ? 'active' : '' }}">
                    <a href="{{ route('ai-governance.provenance-detail', $c->id) }}" class="{{ $c->id === $output->id ? 'text-white' : '' }}">
                        #{{ $c->id }} &mdash; {{ $c->output_type }} ({{ $c->status }})
                    </a>
                    <br><span class="small">{{ \Carbon\Carbon::parse($c->created_at)->format('Y-m-d H:i') }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
