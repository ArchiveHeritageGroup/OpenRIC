{{-- Footer — adapted from Heratio footer.blade.php (93 lines) --}}
{{-- Displays repository info, standards badges, links, copyright, and version --}}

@php
    $footerText = $themeData['footerText'] ?? '© OpenRiC — Records in Contexts';
    $repositoryName = $themeData['repositoryName'] ?? '';
    $appVersion = $themeData['appVersion'] ?? '0.1.0';
    $appName = $themeData['appName'] ?? 'OpenRiC';

    // Standards supported
    $standards = ['RiC-O', 'ISAD(G)', 'ISAAR(CPF)', 'EAD3', 'EAC-CPF', 'Dublin Core', 'OAI-PMH', 'IIIF', 'JSON-LD'];
@endphp

<footer class="mt-auto py-4 text-light" role="contentinfo">
    <div class="container-xl">
        <div class="row">
            {{-- Left: System name, repository, standards --}}
            <div class="col-lg-4 mb-3 mb-lg-0">
                <h5 class="text-white mb-2">
                    <i class="bi bi-archive me-1"></i> <span class="text-info">Open</span>RiC
                </h5>
                @if($repositoryName)
                    <p class="mb-2 text-light opacity-75 small">{{ $repositoryName }}</p>
                @endif
                <div class="d-flex flex-wrap gap-1 mb-2">
                    @foreach($standards as $standard)
                        <span class="badge bg-secondary bg-opacity-50 fw-normal" style="font-size: 0.7rem;">{{ $standard }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Center: Quick links --}}
            <div class="col-lg-4 mb-3 mb-lg-0">
                <h6 class="text-white mb-2">Quick Links</h6>
                <ul class="list-unstyled small mb-0">
                    @if(Route::has('search.index'))
                        <li><a href="{{ route('search.index') }}" class="text-light text-decoration-none opacity-75">
                            <i class="bi bi-search me-1"></i> Search Records
                        </a></li>
                    @endif
                    @if(Route::has('records.index'))
                        <li><a href="{{ route('records.index') }}" class="text-light text-decoration-none opacity-75">
                            <i class="bi bi-file-earmark-text me-1"></i> Browse Records
                        </a></li>
                    @endif
                    @if(Route::has('oai.index'))
                        <li><a href="{{ route('oai.index') }}" class="text-light text-decoration-none opacity-75">
                            <i class="bi bi-cloud-download me-1"></i> OAI-PMH Endpoint
                        </a></li>
                    @endif
                    @if(Route::has('graph.explore'))
                        <li><a href="{{ route('graph.explore') }}" class="text-light text-decoration-none opacity-75">
                            <i class="bi bi-share me-1"></i> Graph Explorer
                        </a></li>
                    @endif
                </ul>
            </div>

            {{-- Right: Copyright, version, branding --}}
            <div class="col-lg-4 text-lg-end">
                <p class="mb-1 small opacity-75">{!! $footerText !!}</p>
                <p class="mb-1 small opacity-50">
                    Powered by <strong>{{ $appName }}</strong> v{{ $appVersion }}
                </p>
                <p class="mb-0 small opacity-50">
                    RiC-O native archival description platform
                    <br>
                    <a href="https://theahg.co.za" class="text-info text-decoration-none opacity-75" target="_blank" rel="noopener">
                        The Archive and Heritage Group
                    </a>
                    &middot;
                    <a href="https://www.ica.org/standards/RiC/RiC-O_v0-2.html" class="text-light opacity-75" target="_blank" rel="noopener">
                        RiC-O Standard
                    </a>
                </p>
            </div>
        </div>

        {{-- Bottom bar --}}
        <hr class="my-3 opacity-25">
        <div class="row">
            <div class="col-md-6 small opacity-50">
                &copy; {{ date('Y') }} {{ $repositoryName ?: 'The Archive and Heritage Group' }}. Licensed under AGPL-3.0.
            </div>
            <div class="col-md-6 text-md-end small opacity-50">
                Built with Laravel, Apache Jena Fuseki, Elasticsearch &amp; RiC-O
            </div>
        </div>
    </div>
</footer>
