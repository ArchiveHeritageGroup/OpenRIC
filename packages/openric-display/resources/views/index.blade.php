@extends('theme::layouts.1col')

@section('title', 'Browse Records')

@section('content')
<div class="container-fluid py-3">

    {{-- Breadcrumb --}}
    @if(!empty($breadcrumb ?? []))
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('display.browse') }}">Browse</a></li>
            @foreach($breadcrumb as $crumb)
                @if(!$loop->last)
                <li class="breadcrumb-item">
                    <a href="{{ route('display.browse', ['parent' => $crumb->id]) }}">{{ $crumb->title ?? 'Untitled' }}</a>
                </li>
                @else
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb->title ?? 'Untitled' }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
    @endif

    {{-- Search / corrected query --}}
    @if(!empty($correctedQuery))
    <div class="alert alert-info mb-3">
        Showing results for <strong>{{ $correctedQuery }}</strong>.
        @if(!empty($originalQuery))
        Search instead for <a href="{{ route('display.browse', array_merge($filterParams ?? [], ['query' => $originalQuery])) }}">{{ $originalQuery }}</a>.
        @endif
    </div>
    @endif

    <div class="row">
        {{-- Sidebar: Facets --}}
        <div class="col-md-3" id="facet-sidebar">
            <div class="card mb-3">
                <div class="card-header fw-bold">Filters</div>
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('display.browse') }}" id="browse-filter-form">
                        {{-- Text search --}}
                        <div class="mb-3">
                            <label for="query" class="form-label fw-semibold">Search</label>
                            <input type="text" class="form-control form-control-sm" name="query" id="query" value="{{ $queryFilter ?? '' }}" placeholder="Search records...">
                        </div>

                        {{-- Top level only --}}
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="topLevel" value="1" id="topLevel" {{ ($topLevelOnly ?? '1') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="topLevel">Top-level only</label>
                        </div>

                        {{-- Has digital object --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="hasDigital" value="1" id="hasDigital" {{ !empty($hasDigital) ? 'checked' : '' }}>
                            <label class="form-check-label" for="hasDigital">Has digital object</label>
                        </div>

                        {{-- GLAM Type facet --}}
                        @if(!empty($types))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">GLAM Type</label>
                            @foreach($types as $t)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" value="{{ $t->object_type }}" id="type_{{ $t->object_type }}" {{ ($typeFilter ?? '') === $t->object_type ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_{{ $t->object_type }}">{{ ucfirst($t->object_type) }} <span class="badge bg-secondary">{{ $t->count }}</span></label>
                            </div>
                            @endforeach
                            @if($typeFilter)
                            <a href="{{ route('display.browse', array_merge($filterParams ?? [], ['type' => null])) }}" class="small text-muted">Clear</a>
                            @endif
                        </div>
                        @endif

                        {{-- Level facet --}}
                        @if(!empty($levels))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Level</label>
                            <select name="level" class="form-select form-select-sm">
                                <option value="">All levels</option>
                                @foreach($levels as $lv)
                                <option value="{{ $lv->id }}" {{ ($levelFilter ?? '') == $lv->id ? 'selected' : '' }}>{{ $lv->name }} ({{ $lv->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Repository facet --}}
                        @if(!empty($repositories))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Repository</label>
                            <select name="repo" class="form-select form-select-sm">
                                <option value="">All repositories</option>
                                @foreach($repositories as $rp)
                                <option value="{{ $rp->id }}" {{ ($repoFilter ?? '') == $rp->id ? 'selected' : '' }}>{{ $rp->name }} ({{ $rp->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Creator facet --}}
                        @if(!empty($creators))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Creator</label>
                            <select name="creator" class="form-select form-select-sm">
                                <option value="">All creators</option>
                                @foreach($creators as $cr)
                                <option value="{{ $cr->id }}" {{ ($creatorFilter ?? '') == $cr->id ? 'selected' : '' }}>{{ $cr->name }} ({{ $cr->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Subject facet --}}
                        @if(!empty($subjects))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject</label>
                            <select name="subject" class="form-select form-select-sm">
                                <option value="">All subjects</option>
                                @foreach($subjects as $sb)
                                <option value="{{ $sb->id }}" {{ ($subjectFilter ?? '') == $sb->id ? 'selected' : '' }}>{{ $sb->name }} ({{ $sb->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Place facet --}}
                        @if(!empty($places))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Place</label>
                            <select name="place" class="form-select form-select-sm">
                                <option value="">All places</option>
                                @foreach($places as $pl)
                                <option value="{{ $pl->id }}" {{ ($placeFilter ?? '') == $pl->id ? 'selected' : '' }}>{{ $pl->name }} ({{ $pl->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Genre facet --}}
                        @if(!empty($genres))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Genre</label>
                            <select name="genre" class="form-select form-select-sm">
                                <option value="">All genres</option>
                                @foreach($genres as $gn)
                                <option value="{{ $gn->id }}" {{ ($genreFilter ?? '') == $gn->id ? 'selected' : '' }}>{{ $gn->name }} ({{ $gn->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Media type facet --}}
                        @if(!empty($mediaTypes))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Media Type</label>
                            <select name="media" class="form-select form-select-sm">
                                <option value="">All media</option>
                                @foreach($mediaTypes as $mt)
                                <option value="{{ $mt->media_type }}" {{ ($mediaFilter ?? '') == $mt->media_type ? 'selected' : '' }}>{{ ucfirst($mt->media_type) }} ({{ $mt->count }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Preserve hidden fields --}}
                        <input type="hidden" name="sort" value="{{ $sort ?? 'date' }}">
                        <input type="hidden" name="dir" value="{{ $sortDir ?? 'desc' }}">
                        <input type="hidden" name="view" value="{{ $viewMode ?? 'card' }}">
                        <input type="hidden" name="limit" value="{{ $limit ?? 30 }}">
                        @if(!empty($parentId))
                        <input type="hidden" name="parent" value="{{ $parentId }}">
                        @endif

                        <button type="submit" class="btn btn-primary btn-sm w-100">Apply Filters</button>
                    </form>
                </div>
            </div>

            {{-- Export links --}}
            <div class="card mb-3">
                <div class="card-header fw-bold">Export</div>
                <div class="card-body p-2">
                    <a href="{{ route('display.export.csv', $filterParams ?? []) }}" class="btn btn-outline-secondary btn-sm w-100 mb-1">Export CSV</a>
                    <a href="{{ route('display.print', $filterParams ?? []) }}" class="btn btn-outline-secondary btn-sm w-100" target="_blank">Print View</a>
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div class="col-md-9">
            {{-- Header: result count, sort, view toggle --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <strong>{{ number_format($total ?? 0) }}</strong> result{{ ($total ?? 0) !== 1 ? 's' : '' }}
                    @if(!empty($parent))
                    in <em>{{ $parent->title ?? 'Untitled' }}</em>
                    @endif
                    @if(!empty($digitalObjectCount))
                    <span class="text-muted ms-2">({{ number_format($digitalObjectCount) }} with digital objects)</span>
                    @endif
                </div>
                <div class="d-flex gap-2 align-items-center">
                    {{-- Sort --}}
                    <select class="form-select form-select-sm" style="width:auto" onchange="location.href='{{ route('display.browse') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(location.search)), sort: this.value}).toString()">
                        <option value="title" {{ ($sort ?? '') === 'title' ? 'selected' : '' }}>Title</option>
                        <option value="date" {{ ($sort ?? '') === 'date' ? 'selected' : '' }}>Date added</option>
                        <option value="identifier" {{ ($sort ?? '') === 'identifier' ? 'selected' : '' }}>Identifier</option>
                        <option value="startdate" {{ ($sort ?? '') === 'startdate' ? 'selected' : '' }}>Start date</option>
                        <option value="enddate" {{ ($sort ?? '') === 'enddate' ? 'selected' : '' }}>End date</option>
                        @if(!empty($queryFilter))
                        <option value="relevance" {{ ($sort ?? '') === 'relevance' ? 'selected' : '' }}>Relevance</option>
                        @endif
                    </select>

                    {{-- Sort direction --}}
                    <a href="{{ route('display.browse', array_merge($filterParams ?? [], ['dir' => ($sortDir ?? 'desc') === 'desc' ? 'asc' : 'desc'])) }}"
                       class="btn btn-sm btn-outline-secondary" title="Toggle sort direction">
                        @if(($sortDir ?? 'desc') === 'desc')
                            <i class="bi bi-sort-down"></i> Desc
                        @else
                            <i class="bi bi-sort-up"></i> Asc
                        @endif
                    </a>

                    {{-- View mode --}}
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('display.browse', array_merge($filterParams ?? [], ['view' => 'list'])) }}"
                           class="btn btn-outline-secondary {{ ($viewMode ?? 'card') === 'list' ? 'active' : '' }}" title="List view">
                            <i class="bi bi-list"></i>
                        </a>
                        <a href="{{ route('display.browse', array_merge($filterParams ?? [], ['view' => 'card'])) }}"
                           class="btn btn-outline-secondary {{ ($viewMode ?? 'card') === 'card' ? 'active' : '' }}" title="Card view">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </a>
                    </div>

                    {{-- Items per page --}}
                    <select class="form-select form-select-sm" style="width:auto" onchange="location.href='{{ route('display.browse') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(location.search)), limit: this.value}).toString()">
                        @foreach([10, 20, 30, 50, 100] as $perPage)
                        <option value="{{ $perPage }}" {{ ($limit ?? 30) == $perPage ? 'selected' : '' }}>{{ $perPage }} per page</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Results: Card view --}}
            @if(($viewMode ?? 'card') === 'card')
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                @forelse($objects ?? [] as $obj)
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        @if(!empty($obj->thumbnail))
                        <a href="{{ route('display.show', ['id' => $obj->id]) }}">
                            <img src="{{ $obj->thumbnail }}" class="card-img-top" alt="{{ $obj->title ?? 'Untitled' }}" style="height:180px; object-fit:cover;">
                        </a>
                        @else
                        <a href="{{ route('display.show', ['id' => $obj->id]) }}" class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" style="height:180px;">
                            <i class="bi bi-file-earmark" style="font-size:3rem"></i>
                        </a>
                        @endif
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1">
                                <a href="{{ route('display.show', ['id' => $obj->id]) }}" class="text-decoration-none">
                                    {{ \Illuminate\Support\Str::limit($obj->title ?? 'Untitled', 80) }}
                                </a>
                            </h6>
                            <div class="small text-muted">
                                @if(!empty($obj->level_name))<span class="badge bg-info text-dark me-1">{{ $obj->level_name }}</span>@endif
                                @if(!empty($obj->object_type))<span class="badge bg-primary me-1">{{ ucfirst($obj->object_type) }}</span>@endif
                                @if(!empty($obj->identifier))<span>{{ $obj->identifier }}</span>@endif
                            </div>
                            @if(!empty($obj->scope_and_content))
                            <p class="card-text small mt-1 mb-0 text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($obj->scope_and_content), 120) }}</p>
                            @endif
                            @if(($obj->child_count ?? 0) > 0)
                            <div class="mt-1">
                                <a href="{{ route('display.browse', ['parent' => $obj->id, 'topLevel' => '0']) }}" class="small">{{ $obj->child_count }} child record{{ $obj->child_count > 1 ? 's' : '' }}</a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-secondary">No records found matching your criteria.</div>
                </div>
                @endforelse
            </div>

            {{-- Results: List view --}}
            @else
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px"></th>
                            <th>Title</th>
                            <th>Identifier</th>
                            <th>Level</th>
                            <th>Type</th>
                            <th>Children</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($objects ?? [] as $obj)
                        <tr>
                            <td>
                                @if(!empty($obj->thumbnail))
                                <img src="{{ $obj->thumbnail }}" alt="" style="width:40px;height:40px;object-fit:cover" class="rounded">
                                @elseif($obj->has_digital ?? false)
                                <span class="text-success"><i class="bi bi-image"></i></span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('display.show', ['id' => $obj->id]) }}">{{ $obj->title ?? 'Untitled' }}</a>
                            </td>
                            <td class="small text-muted">{{ $obj->identifier ?? '' }}</td>
                            <td><span class="badge bg-info text-dark">{{ $obj->level_name ?? '' }}</span></td>
                            <td><span class="badge bg-primary">{{ ucfirst($obj->object_type ?? 'unknown') }}</span></td>
                            <td>
                                @if(($obj->child_count ?? 0) > 0)
                                <a href="{{ route('display.browse', ['parent' => $obj->id, 'topLevel' => '0']) }}">{{ $obj->child_count }}</a>
                                @else
                                <span class="text-muted">0</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No records found matching your criteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Pagination --}}
            @if(($totalPages ?? 0) > 1)
            <nav class="mt-3">
                <ul class="pagination pagination-sm justify-content-center flex-wrap">
                    {{-- Previous --}}
                    <li class="page-item {{ ($page ?? 1) <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route('display.browse', array_merge($filterParams ?? [], ['page' => max(1, ($page ?? 1) - 1)])) }}">Previous</a>
                    </li>

                    @php
                        $startPage = max(1, ($page ?? 1) - 4);
                        $endPage = min($totalPages, $startPage + 9);
                        if ($endPage - $startPage < 9) $startPage = max(1, $endPage - 9);
                    @endphp

                    @if($startPage > 1)
                    <li class="page-item">
                        <a class="page-link" href="{{ route('display.browse', array_merge($filterParams ?? [], ['page' => 1])) }}">1</a>
                    </li>
                    @if($startPage > 2)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    @endif
                    @endif

                    @for($p = $startPage; $p <= $endPage; $p++)
                    <li class="page-item {{ $p === ($page ?? 1) ? 'active' : '' }}">
                        <a class="page-link" href="{{ route('display.browse', array_merge($filterParams ?? [], ['page' => $p])) }}">{{ $p }}</a>
                    </li>
                    @endfor

                    @if($endPage < $totalPages)
                    @if($endPage < $totalPages - 1)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    @endif
                    <li class="page-item">
                        <a class="page-link" href="{{ route('display.browse', array_merge($filterParams ?? [], ['page' => $totalPages])) }}">{{ $totalPages }}</a>
                    </li>
                    @endif

                    {{-- Next --}}
                    <li class="page-item {{ ($page ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route('display.browse', array_merge($filterParams ?? [], ['page' => min($totalPages, ($page ?? 1) + 1)])) }}">Next</a>
                    </li>
                </ul>
            </nav>
            @endif
        </div>
    </div>
</div>
@endsection
