{{--
  Digital Object Metadata — _metadata.blade.php
  Adapted from Heratio ahg-display _metadata.blade.php
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
$isPdf = isset($resource) && stripos($resource->mime_type ?? '', 'pdf') !== false;
$canAccessMasterFileFinal = $canAccessMasterFile ?? false;
if ($isPdf && !auth()->check()) {
    $canAccessMasterFileFinal = false;
}
@endphp

<section>

  @if($relatedToIo ?? false)
    @can('edit', $resource)
      <a href="{{ route('display.show', ['id' => $resource->id]) }}"><h2>{{ __(':label metadata', ['label' => 'Digital object']) }}</h2></a>
    @else
      <h2>{{ __(':label metadata', ['label' => 'Digital object']) }}</h2>
    @endcan
  @endif

  @if(($showOriginalFileMetadata ?? false) || ($showPreservationCopyMetadata ?? false))
    <fieldset class="collapsible digital-object-metadata single">
      <legend>{{ __('Preservation Copies') }}</legend>

      @if($showOriginalFileMetadata ?? false)
        <div class="digital-object-metadata-header">
          <h3>{{ __('Original file') }} <i class="fa fa-archive{{ !($canAccessOriginalFile ?? false) ? ' inactive' : '' }}" aria-hidden="true"></i></h3>
        </div>
        <div class="digital-object-metadata-body">
          @if($showOriginalFileName ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filename') }}</h6><div class="field-value">{{ $resource->original_filename ?? '-' }}</div></div>
          @endif
          @if($showOriginalFormatName ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Format name') }}</h6><div class="field-value">{{ $resource->format_name ?? '-' }}</div></div>
          @endif
          @if($showOriginalFileSize ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filesize') }}</h6><div class="field-value">{{ number_format(intval($resource->original_file_size ?? 0)) }} bytes</div></div>
          @endif
        </div>
      @endif

      @if($showPreservationCopyMetadata ?? false)
        <div class="digital-object-metadata-header">
          <h3>{{ __('Preservation copy') }}</h3>
        </div>
        <div class="digital-object-metadata-body">
          @if($showPreservationCopyFileName ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filename') }}</h6><div class="field-value">{{ $resource->preservation_copy_filename ?? '-' }}</div></div>
          @endif
          @if($showPreservationCopyFileSize ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filesize') }}</h6><div class="field-value">{{ number_format(intval($resource->preservation_copy_file_size ?? 0)) }} bytes</div></div>
          @endif
        </div>
      @endif
    </fieldset>
  @endif

  @if(($showMasterFileMetadata ?? false) || ($showReferenceCopyMetadata ?? false) || ($showThumbnailCopyMetadata ?? false))
    <fieldset class="collapsible digital-object-metadata single">
      <legend>{{ __('Access Copies') }}</legend>

      @if($showMasterFileMetadata ?? false)
        <div class="digital-object-metadata-header">
          <h3>{{ __('Master file') }} <i class="fa fa-file{{ !$canAccessMasterFileFinal ? ' inactive' : '' }}" aria-hidden="true"></i></h3>
        </div>
        <div class="digital-object-metadata-body">
          @if($showMasterFileName ?? false)
            @if($canAccessMasterFileFinal)
              <div class="field-block"><h6 class="field-label text-muted">{{ __('Filename') }}</h6><div class="field-value"><a href="{{ $resource->path ?? '#' }}" target="_blank">{{ $resource->name ?? '-' }}</a></div></div>
            @else
              <div class="field-block"><h6 class="field-label text-muted">{{ __('Filename') }}</h6><div class="field-value">{{ $resource->name ?? '-' }}</div></div>
              @if($isPdf && !auth()->check())
                <div class="alert alert-info small mt-2">
                  <i class="fas fa-lock me-1"></i>{{ __('Please log in to download this PDF file.') }}
                </div>
              @endif
            @endif
          @endif
          @if($showMasterFileMediaType ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Media type') }}</h6><div class="field-value">{{ $resource->media_type ?? '-' }}</div></div>
          @endif
          @if($showMasterFileMimeType ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Mime-type') }}</h6><div class="field-value">{{ $resource->mime_type ?? '-' }}</div></div>
          @endif
          @if($showMasterFileSize ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filesize') }}</h6><div class="field-value">{{ number_format($resource->byte_size ?? 0) }} bytes</div></div>
          @endif
          @if($showMasterFileCreatedAt ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Uploaded') }}</h6><div class="field-value">{{ $resource->created_at ? \Carbon\Carbon::parse($resource->created_at)->format('F j, Y') : '-' }}</div></div>
          @endif
        </div>
      @endif

      @if($showReferenceCopyMetadata ?? false)
        <div class="digital-object-metadata-header">
          <h3>{{ __('Reference copy') }}</h3>
        </div>
        <div class="digital-object-metadata-body">
          @if($showReferenceCopyFileName ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filename') }}</h6><div class="field-value">{{ $referenceCopy->name ?? '-' }}</div></div>
          @endif
          @if($showReferenceCopyMimeType ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Mime-type') }}</h6><div class="field-value">{{ $referenceCopy->mime_type ?? '-' }}</div></div>
          @endif
        </div>
      @endif

      @if($showThumbnailCopyMetadata ?? false)
        <div class="digital-object-metadata-header">
          <h3>{{ __('Thumbnail copy') }}</h3>
        </div>
        <div class="digital-object-metadata-body">
          @if($showThumbnailCopyFileName ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Filename') }}</h6><div class="field-value">{{ $thumbnailCopy->name ?? '-' }}</div></div>
          @endif
          @if($showThumbnailCopyMimeType ?? false)
            <div class="field-block"><h6 class="field-label text-muted">{{ __('Mime-type') }}</h6><div class="field-value">{{ $thumbnailCopy->mime_type ?? '-' }}</div></div>
          @endif
        </div>
      @endif
    </fieldset>
  @endif

</section>
