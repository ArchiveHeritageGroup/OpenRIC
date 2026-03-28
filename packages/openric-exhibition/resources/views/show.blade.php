@extends('theme::layouts.1col')

@section('title', $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3"><i class="fas fa-palette me-2"></i>{{ $exhibition->title }}</h1>
            @if (!empty($exhibition->subtitle))
                <p class="text-muted mb-0">{{ $exhibition->subtitle }}</p>
            @endif
        </div>
        <div>
            <a href="{{ route('exhibition.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            @auth
            <a href="{{ route('exhibition.edit', $exhibition->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i> Edit</a>
            @endauth
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            {{-- Details --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-info-circle me-1"></i> Exhibition Details</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Type</div>
                        <div class="col-sm-9">{{ $exhibition->exhibition_type ?? '' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Status</div>
                        <div class="col-sm-9">{{ $exhibition->status ?? '' }}</div>
                    </div>
                    @if (!empty($exhibition->project_code))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Project Code</div>
                        <div class="col-sm-9">{{ $exhibition->project_code }}</div>
                    </div>
                    @endif
                    @if (!empty($exhibition->description))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Description</div>
                        <div class="col-sm-9">{!! nl2br(e($exhibition->description)) !!}</div>
                    </div>
                    @endif
                    @if (!empty($exhibition->theme))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Theme</div>
                        <div class="col-sm-9">{{ $exhibition->theme }}</div>
                    </div>
                    @endif
                    @if (!empty($exhibition->target_audience))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Target Audience</div>
                        <div class="col-sm-9">{{ $exhibition->target_audience }}</div>
                    </div>
                    @endif
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Dates</div>
                        <div class="col-sm-9">
                            @if ($exhibition->start_date)
                                {{ \Carbon\Carbon::parse($exhibition->start_date)->format('d M Y') }}
                                @if ($exhibition->end_date)
                                    &ndash; {{ \Carbon\Carbon::parse($exhibition->end_date)->format('d M Y') }}
                                @endif
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </div>
                    </div>
                    @if (!empty($exhibition->venue))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Venue</div>
                        <div class="col-sm-9">{{ $exhibition->venue }}</div>
                    </div>
                    @endif
                    @if (!empty($exhibition->curator))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Curator</div>
                        <div class="col-sm-9">{{ $exhibition->curator }}</div>
                    </div>
                    @endif
                    @if (!empty($exhibition->designer))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Designer</div>
                        <div class="col-sm-9">{{ $exhibition->designer }}</div>
                    </div>
                    @endif
                    @if (!empty($exhibition->budget))
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-bold">Budget</div>
                        <div class="col-sm-9">{{ $exhibition->budget_currency ?? '' }} {{ number_format((float) $exhibition->budget, 2) }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Objects --}}
            @if (isset($exhibition->objects) && $exhibition->objects->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-box me-1"></i> Objects ({{ $exhibition->objects->count() }})</span>
                    @auth
                    <a href="{{ route('exhibition.objects', $exhibition->id) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                    @endauth
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>#</th><th>Title</th><th>Identifier</th><th>Section</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($exhibition->objects as $i => $obj)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $obj->title ?? 'Untitled' }}</td>
                                <td>{{ $obj->identifier ?? '' }}</td>
                                <td>{{ $obj->section ?? '' }}</td>
                                <td>{{ $obj->status ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Storylines --}}
            @if (isset($exhibition->storylines) && $exhibition->storylines->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-stream me-1"></i> Storylines ({{ $exhibition->storylines->count() }})</div>
                <ul class="list-group list-group-flush">
                    @foreach ($exhibition->storylines as $sl)
                    <li class="list-group-item">
                        <strong>{{ $sl->title }}</strong>
                        @if (!empty($sl->description))
                            <br><small class="text-muted">{{ Str::limit($sl->description, 200) }}</small>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            {{-- Sections --}}
            @if (isset($exhibition->sections) && $exhibition->sections->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-th-large me-1"></i> Sections ({{ $exhibition->sections->count() }})</div>
                <ul class="list-group list-group-flush">
                    @foreach ($exhibition->sections as $sec)
                    <li class="list-group-item">
                        <strong>{{ $sec->title }}</strong>
                        @if (!empty($sec->location))
                            <br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> {{ $sec->location }}</small>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Events --}}
            @if (isset($exhibition->events) && $exhibition->events->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-calendar-alt me-1"></i> Events ({{ $exhibition->events->count() }})</div>
                <ul class="list-group list-group-flush">
                    @foreach ($exhibition->events as $evt)
                    <li class="list-group-item">
                        <strong>{{ $evt->title }}</strong>
                        @if ($evt->event_date)
                            <br><small class="text-muted">{{ \Carbon\Carbon::parse($evt->event_date)->format('d M Y') }}{{ $evt->event_time ? ' ' . $evt->event_time : '' }}</small>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Checklists --}}
            @if (isset($exhibition->checklists) && $exhibition->checklists->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-tasks me-1"></i> Checklists ({{ $exhibition->checklists->count() }})</div>
                <ul class="list-group list-group-flush">
                    @foreach ($exhibition->checklists as $cl)
                    <li class="list-group-item">
                        <i class="fas {{ $cl->is_completed ? 'fa-check-square text-success' : 'fa-square text-muted' }} me-1"></i>
                        {{ $cl->title }}
                        @if (!empty($cl->category))
                            <span class="badge bg-light text-dark ms-1">{{ $cl->category }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
