{{-- Search box — adapted from Heratio search-box.blade.php (78 lines) --}}
{{-- Global search form with type selector and autocomplete --}}

<form action="{{ route('search.index') }}" method="GET" class="d-flex align-items-center" role="search" aria-label="Global search">
    {{-- Search scope selector --}}
    <div class="input-group">
        <button class="btn btn-outline-light dropdown-toggle" type="button" id="searchScopeDropdown"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Search scope">
            <i class="bi bi-funnel"></i>
        </button>
        <ul class="dropdown-menu" aria-labelledby="searchScopeDropdown">
            <li>
                <button type="button" class="dropdown-item search-scope-option active" data-scope="all">
                    <i class="bi bi-globe"></i> All entities
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button type="button" class="dropdown-item search-scope-option" data-scope="record">
                    <i class="bi bi-file-earmark-text"></i> Records
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item search-scope-option" data-scope="agent">
                    <i class="bi bi-people"></i> Agents
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item search-scope-option" data-scope="place">
                    <i class="bi bi-geo-alt"></i> Places
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item search-scope-option" data-scope="activity">
                    <i class="bi bi-activity"></i> Activities
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item search-scope-option" data-scope="function">
                    <i class="bi bi-gear"></i> Functions
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button type="button" class="dropdown-item search-scope-option" data-scope="semantic">
                    <i class="bi bi-stars"></i> Semantic search
                </button>
            </li>
        </ul>

        {{-- Hidden scope input --}}
        <input type="hidden" name="scope" id="searchScope" value="all">

        {{-- Search input --}}
        <input type="search"
               name="q"
               class="form-control"
               placeholder="Search records, agents, places..."
               aria-label="Search query"
               value="{{ request('q', '') }}"
               autocomplete="off"
               data-autocomplete-url="{{ route('search.index') }}">

        {{-- Search button --}}
        <button type="submit" class="btn btn-light" aria-label="Search">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>

{{-- Advanced search link --}}
@if(Route::has('search.advanced'))
    <a href="{{ route('search.advanced') }}" class="text-light small text-decoration-none ms-2 d-none d-lg-inline">
        Advanced
    </a>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.search-scope-option').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.search-scope-option').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('searchScope').value = this.dataset.scope;
            document.getElementById('searchScopeDropdown').innerHTML = this.innerHTML;
        });
    });
});
</script>
