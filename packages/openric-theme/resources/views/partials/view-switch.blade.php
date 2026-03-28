<div class="btn-group btn-group-sm" role="group" aria-label="View mode">
    <a href="{{ request()->fullUrlWithQuery(['view' => 'ric']) }}"
       class="btn {{ (session('openric_view_mode', config('openric.default_view', 'ric')) === 'ric') ? 'btn-info' : 'btn-outline-info' }}"
       title="RiC-O native view">
        RiC
    </a>
    <a href="{{ request()->fullUrlWithQuery(['view' => 'traditional']) }}"
       class="btn {{ (session('openric_view_mode', config('openric.default_view', 'ric')) === 'traditional') ? 'btn-info' : 'btn-outline-info' }}"
       title="Traditional archival view (ISAD(G) / ISAAR-CPF)">
        Traditional
    </a>
</div>
