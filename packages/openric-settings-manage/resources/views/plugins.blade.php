@extends('theme::layouts.1col')

@section('title', 'Plugins')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-plug me-2"></i>Plugins</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> {{ __('settings.back_to_settings') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
    $plugins = [
        // Core plugins
        ['name' => 'OpenRiC Core', 'version' => '1.0.0', 'description' => 'Core RiC-O functionality', 'enabled' => true, 'category' => 'Core'],
        ['name' => 'Qdrant AI Embeddings', 'version' => '1.0.0', 'description' => 'Vector database for AI embeddings and semantic search', 'enabled' => true, 'category' => 'AI'],
        ['name' => 'Ollama AI Integration', 'version' => '1.0.0', 'description' => 'Local LLM integration for AI features', 'enabled' => true, 'category' => 'AI'],
        ['name' => 'Anthropic Claude Integration', 'version' => '1.0.0', 'description' => 'Cloud LLM integration via Anthropic API', 'enabled' => false, 'category' => 'AI'],
        
        // Management plugins
        ['name' => 'Record Management', 'version' => '1.0.0', 'description' => 'Record and record set management', 'enabled' => true, 'category' => 'Management'],
        ['name' => 'Agent Management', 'version' => '1.0.0', 'description' => 'Agent management (persons, families, corporate bodies)', 'enabled' => true, 'category' => 'Management'],
        ['name' => 'Place Management', 'version' => '1.0.0', 'description' => 'Place and location management', 'enabled' => true, 'category' => 'Management'],
        ['name' => 'Accession Management', 'version' => '1.0.0', 'description' => 'Accession and intake workflow', 'enabled' => true, 'category' => 'Management'],
        ['name' => '3D Model Viewer', 'version' => '1.0.0', 'description' => '3D model viewing and management', 'enabled' => false, 'category' => 'Management'],
        
        // Media plugins
        ['name' => 'Digital Object Management', 'version' => '1.0.0', 'description' => 'Digital objects, derivatives, and media management', 'enabled' => true, 'category' => 'Media'],
        ['name' => 'IIIF Image Viewer', 'version' => '1.0.0', 'description' => 'IIIF-compatible image viewer (OpenSeadragon, Mirador, Leaflet)', 'enabled' => true, 'category' => 'Media'],
        ['name' => 'Media Player', 'version' => '1.0.0', 'description' => 'Audio and video playback support', 'enabled' => true, 'category' => 'Media'],
        ['name' => 'ImageMagick Processing', 'version' => '1.0.0', 'description' => 'Image processing and format conversion', 'enabled' => true, 'category' => 'Media'],
        ['name' => 'FFmpeg Processing', 'version' => '1.0.0', 'description' => 'Video and audio transcoding', 'enabled' => true, 'category' => 'Media'],
        ['name' => 'Tesseract OCR', 'version' => '1.0.0', 'description' => 'Optical character recognition', 'enabled' => false, 'category' => 'Media'],
        
        // Integration plugins
        ['name' => 'OAI-PMH Repository', 'version' => '1.0.0', 'description' => 'OAI-PMH protocol for metadata harvesting', 'enabled' => false, 'category' => 'Integration'],
        ['name' => 'Fedora Commons Integration', 'version' => '1.0.0', 'description' => 'Fedora repository integration', 'enabled' => false, 'category' => 'Integration'],
        ['name' => 'AtoM/EAD Import', 'version' => '1.0.0', 'description' => 'Import from AtoM and EAD files', 'enabled' => true, 'category' => 'Integration'],
        ['name' => 'ISAD(G) Export', 'version' => '1.0.0', 'description' => 'Export to ISAD(G) finding aids', 'enabled' => true, 'category' => 'Integration'],
        ['name' => 'Dublin Core Export', 'version' => '1.0.0', 'description' => 'Dublin Core metadata export', 'enabled' => true, 'category' => 'Integration'],
        
        // Preservation plugins
        ['name' => 'Preservation Planning', 'version' => '1.0.0', 'description' => 'Preservation workflow and planning', 'enabled' => true, 'category' => 'Preservation'],
        ['name' => 'Checksum Verification', 'version' => '1.0.0', 'description' => 'File integrity checking (SHA-256, MD5)', 'enabled' => true, 'category' => 'Preservation'],
        ['name' => 'Virus Scanning', 'version' => '1.0.0', 'description' => 'ClamAV virus scanning', 'enabled' => true, 'category' => 'Preservation'],
        ['name' => 'Format Identification', 'version' => '1.0.0', 'description' => 'PRONOM-based format identification', 'enabled' => true, 'category' => 'Preservation'],
        ['name' => 'SIP/AIP/DIP Generation', 'version' => '1.0.0', 'description' => 'Submission, Archival, and DIP generation', 'enabled' => true, 'category' => 'Preservation'],
        
        // System plugins
        ['name' => 'Settings Management', 'version' => '1.0.0', 'description' => 'System configuration and settings', 'enabled' => true, 'category' => 'System'],
        ['name' => 'User Management', 'version' => '1.0.0', 'description' => 'User accounts and permissions', 'enabled' => true, 'category' => 'System'],
        ['name' => 'Role-Based Access Control', 'version' => '1.0.0', 'description' => 'RBAC and permissions management', 'enabled' => true, 'category' => 'System'],
        ['name' => 'LDAP Authentication', 'version' => '1.0.0', 'description' => 'LDAP/Active Directory integration', 'enabled' => false, 'category' => 'System'],
        ['name' => 'Theme Configuration', 'version' => '1.0.0', 'description' => 'Custom themes and branding', 'enabled' => true, 'category' => 'System'],
        
        // Voice & Accessibility
        ['name' => 'Text-to-Speech', 'version' => '1.0.0', 'description' => 'Voice reading and audio output', 'enabled' => true, 'category' => 'Accessibility'],
        ['name' => 'Speech-to-Text', 'version' => '1.0.0', 'description' => 'Voice commands and dictation', 'enabled' => false, 'category' => 'Accessibility'],
        ['name' => 'Screen Reader Support', 'version' => '1.0.0', 'description' => 'ARIA labels and accessibility features', 'enabled' => true, 'category' => 'Accessibility'],
        ['name' => 'Multi-Language Support', 'version' => '1.0.0', 'description' => 'i18n and l10n with language files', 'enabled' => true, 'category' => 'Accessibility'],
        
        // Analysis plugins
        ['name' => 'Named Entity Recognition', 'version' => '1.0.0', 'description' => 'AI-powered NER for archival entities', 'enabled' => false, 'category' => 'Analysis'],
        ['name' => 'Condition Assessment AI', 'version' => '1.0.0', 'description' => 'AI-based material condition analysis', 'enabled' => false, 'category' => 'Analysis'],
        ['name' => 'Face Detection', 'version' => '1.0.0', 'description' => 'Detect faces in photographs', 'enabled' => false, 'category' => 'Analysis'],
        ['name' => 'Semantic Search', 'version' => '1.0.0', 'description' => 'Vector-based semantic search', 'enabled' => true, 'category' => 'Analysis'],
        
        // Reporting
        ['name' => 'Usage Statistics', 'version' => '1.0.0', 'description' => 'Repository activity and usage reports', 'enabled' => true, 'category' => 'Reporting'],
        ['name' => 'Accession Reports', 'version' => '1.0.0', 'description' => 'Accession statistics and reports', 'enabled' => true, 'category' => 'Reporting'],
        ['name' => 'Collection Analysis', 'version' => '1.0.0', 'description' => 'Collection depth and coverage analysis', 'enabled' => true, 'category' => 'Reporting'],
    ];

    // Group by category
    $grouped = [];
    foreach ($plugins as $plugin) {
        $cat = $plugin['category'];
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }
        $grouped[$cat][] = $plugin;
    }
    @endphp

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Installed Plugins ({{ count($plugins) }})</h5>
        </div>
        <div class="card-body p-0">
            @if(empty($plugins))
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle me-1"></i> No plugins installed.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30px;"></th>
                                <th>Plugin</th>
                                <th>Description</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grouped as $category => $items)
                            <tr class="table-primary">
                                <td colspan="5"><strong>{{ $category }}</strong></td>
                            </tr>
                            @foreach($items as $plugin)
                            <tr>
                                <td></td>
                                <td>
                                    <strong>{{ $plugin['name'] }}</strong>
                                    <br><span class="badge bg-secondary">{{ $plugin['version'] }}</span>
                                </td>
                                <td class="text-muted">{{ $plugin['description'] }}</td>
                                <td>
                                    @if($plugin['enabled'])
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Enabled</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="fas fa-times me-1"></i> Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($plugin['enabled'])
                                        <button class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-toggle-off"></i> Disable
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-toggle-on"></i> Enable
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="fas fa-download me-2"></i>Available Plugins</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Additional plugins can be installed via Composer. Browse available packages:</p>
            <a href="https://packagist.org" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt me-1"></i> Browse Packagist
            </a>
        </div>
    </div>
</div>
@endsection