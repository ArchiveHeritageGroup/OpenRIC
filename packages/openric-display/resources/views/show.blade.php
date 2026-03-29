@extends('theme::layouts.1col')

@section('title', $object->title ?? 'Record Detail')

@section('content')
<div class="container-fluid py-3">

    {{-- Breadcrumb --}}
    @if(!empty($breadcrumb))
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('display.browse') }}">Browse</a></li>
            @foreach($breadcrumb as $crumb)
                @if($crumb->id !== $id)
                <li class="breadcrumb-item">
                    <a href="{{ route('display.show', ['id' => $crumb->id]) }}">{{ $crumb->title ?? 'Untitled' }}</a>
                </li>
                @else
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb->title ?? 'Untitled' }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
    @endif

    <div class="row">
        {{-- Main content --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $object->title ?? 'Untitled' }}</h4>
                    <div>
                        @if(!empty($type))
                        <span class="badge bg-primary">{{ ucfirst($type) }}</span>
                        @endif
                        @if(!empty($object->level_name))
                        <span class="badge bg-info text-dark">{{ $object->level_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    {{-- Identity area --}}
                    <table class="table table-sm">
                        @if(!empty($object->identifier))
                        <tr><th style="width:200px">Identifier</th><td>{{ $object->identifier }}</td></tr>
                        @endif
                        @if(!empty($object->level_name))
                        <tr><th>Level of description</th><td>{{ $object->level_name }}</td></tr>
                        @endif
                        @if(!empty($object->extent_and_medium))
                        <tr><th>Extent and medium</th><td>{!! nl2br(e($object->extent_and_medium)) !!}</td></tr>
                        @endif
                    </table>

                    {{-- Scope and content --}}
                    @if(!empty($object->scope_and_content))
                    <h6 class="mt-3 fw-bold">Scope and content</h6>
                    <div class="border rounded p-3 bg-light">{!! nl2br(e($object->scope_and_content)) !!}</div>
                    @endif

                    {{-- Archival history --}}
                    @if(!empty($object->archival_history))
                    <h6 class="mt-3 fw-bold">Archival history</h6>
                    <div>{!! nl2br(e($object->archival_history)) !!}</div>
                    @endif

                    {{-- Acquisition --}}
                    @if(!empty($object->acquisition))
                    <h6 class="mt-3 fw-bold">Immediate source of acquisition</h6>
                    <div>{!! nl2br(e($object->acquisition)) !!}</div>
                    @endif

                    {{-- Arrangement --}}
                    @if(!empty($object->arrangement))
                    <h6 class="mt-3 fw-bold">Arrangement</h6>
                    <div>{!! nl2br(e($object->arrangement)) !!}</div>
                    @endif

                    {{-- Access conditions --}}
                    @if(!empty($object->access_conditions))
                    <h6 class="mt-3 fw-bold">Conditions governing access</h6>
                    <div>{!! nl2br(e($object->access_conditions)) !!}</div>
                    @endif

                    {{-- Reproduction conditions --}}
                    @if(!empty($object->reproduction_conditions))
                    <h6 class="mt-3 fw-bold">Conditions governing reproduction</h6>
                    <div>{!! nl2br(e($object->reproduction_conditions)) !!}</div>
                    @endif

                    {{-- Profile fields --}}
                    @if(!empty($fields))
                    <h6 class="mt-4 fw-bold">Profile fields ({{ $profile->name ?? 'Default' }})</h6>
                    <table class="table table-sm table-bordered">
                        <thead class="table-light"><tr><th>Field</th><th>Code</th><th>Group</th></tr></thead>
                        <tbody>
                        @foreach($fields as $field)
                        <tr>
                            <td>{{ $field->name ?? $field->code }}</td>
                            <td class="text-muted small">{{ $field->code }}</td>
                            <td><span class="badge bg-secondary">{{ $field->field_group ?? '' }}</span></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Digital objects --}}
            @if(!empty($digitalObjects))
            <div class="card mb-3">
                <div class="card-header fw-bold">Digital Objects</div>
                <div class="card-body p-2">
                    @foreach($digitalObjects as $dobj)
                    <div class="mb-2">
                        @if(!empty($dobj->path) && !empty($dobj->name))
                        <img src="{{ rtrim($dobj->path, '/') . '/' . $dobj->name }}" class="img-fluid rounded" alt="Digital object">
                        @else
                        <div class="bg-light rounded p-3 text-center text-muted">
                            <i class="bi bi-file-earmark" style="font-size:2rem"></i>
                            <div class="small">{{ $dobj->mime_type ?? 'File' }}</div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Events (creators, dates) --}}
            @if(!empty($events))
            <div class="card mb-3">
                <div class="card-header fw-bold">Events</div>
                <ul class="list-group list-group-flush">
                    @foreach($events as $event)
                    <li class="list-group-item">
                        <div class="fw-semibold">{{ $event->event_type ?? 'Event' }}</div>
                        @if(!empty($event->actor_name))
                        <div class="small">{{ $event->actor_name }}</div>
                        @endif
                        @if(!empty($event->start_date) || !empty($event->end_date))
                        <div class="small text-muted">
                            {{ $event->start_date ?? '?' }} &mdash; {{ $event->end_date ?? '?' }}
                        </div>
                        @endif
                        @if(!empty($event->event_description))
                        <div class="small text-muted">{{ $event->event_description }}</div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Subjects --}}
            @if(!empty($subjects))
            <div class="card mb-3">
                <div class="card-header fw-bold">Subjects</div>
                <div class="card-body p-2">
                    @foreach($subjects as $subj)
                    <a href="{{ route('display.browse', ['subject' => $subj->id]) }}" class="badge bg-secondary text-decoration-none me-1 mb-1">{{ $subj->name }}</a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Places --}}
            @if(!empty($places))
            <div class="card mb-3">
                <div class="card-header fw-bold">Places</div>
                <div class="card-body p-2">
                    @foreach($places as $place)
                    <a href="{{ route('display.browse', ['place' => $place->id]) }}" class="badge bg-secondary text-decoration-none me-1 mb-1">{{ $place->name }}</a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Children --}}
            @if(!empty($children))
            <div class="card mb-3">
                <div class="card-header fw-bold">Child Records ({{ count($children) }})</div>
                <ul class="list-group list-group-flush" style="max-height:400px; overflow-y:auto">
                    @foreach($children as $child)
                    <li class="list-group-item py-1">
                        <a href="{{ route('display.show', ['id' => $child->id]) }}" class="text-decoration-none">
                            {{ $child->title ?? $child->identifier ?? 'Untitled' }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Admin actions --}}
            @auth
            <div class="card mb-3">
                <div class="card-header fw-bold">Admin Actions</div>
                <div class="card-body p-2">
                    <form method="POST" action="{{ route('display.change.type') }}" class="mb-2">
                        @csrf
                        <input type="hidden" name="id" value="{{ $id }}">
                        <div class="input-group input-group-sm">
                            <select name="type" class="form-select form-select-sm">
                                @foreach(['archive','museum','gallery','library','dam','universal'] as $t)
                                <option value="{{ $t }}" {{ ($type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary btn-sm">Set Type</button>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="recursive" value="1" id="recursive">
                            <label class="form-check-label small" for="recursive">Apply recursively</label>
                        </div>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </div>
</div>
@endsection
