{{--
  Image flow / carousel component for digital objects — _imageflow.blade.php
  Adapted from Heratio ahg-display _imageflow.blade.php
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
$extIconMap = [
    'mp3' => 'fas fa-file-audio', 'wav' => 'fas fa-file-audio', 'ogg' => 'fas fa-file-audio',
    'flac' => 'fas fa-file-audio', 'aac' => 'fas fa-file-audio',
    'mp4' => 'fas fa-file-video', 'avi' => 'fas fa-file-video', 'mkv' => 'fas fa-file-video',
    'mov' => 'fas fa-file-video', 'wmv' => 'fas fa-file-video', 'webm' => 'fas fa-file-video',
    'pdf' => 'fas fa-file-pdf',
    'doc' => 'fas fa-file-word', 'docx' => 'fas fa-file-word',
    'xls' => 'fas fa-file-excel', 'xlsx' => 'fas fa-file-excel', 'csv' => 'fas fa-file-excel',
    'ppt' => 'fas fa-file-powerpoint', 'pptx' => 'fas fa-file-powerpoint',
    'zip' => 'fas fa-file-archive', 'rar' => 'fas fa-file-archive', 'tar' => 'fas fa-file-archive',
    'glb' => 'fas fa-cube', 'gltf' => 'fas fa-cube', 'obj' => 'fas fa-cube',
    'xml' => 'fas fa-file-code', 'json' => 'fas fa-file-code',
    'txt' => 'fas fa-file-alt',
];
$imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'svg'];

$thumbnailMeta = $thumbnailMeta ?? [];
if (empty($thumbnailMeta) && !empty($thumbnails)) {
    foreach ($thumbnails as $item) {
        $thumbnailMeta[] = ['slug' => $item->slug ?? null, 'title' => $item->title ?? ''];
    }
}

if (!function_exists('_getFileIconDisplay')) {
    function _getFileIconDisplay($path, $extIconMap, $imageExts) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, $imageExts)) return null;
        return $extIconMap[$ext] ?? 'fas fa-file';
    }
}
@endphp
<div class="accordion" id="digital-object-carousel"
  data-carousel-next-arrow-button-text="{{ __('Next') }}"
  data-carousel-prev-arrow-button-text="{{ __('Previous') }}"
  data-carousel-images-region-label="{{ __('Digital object images carousel') }}"
  data-carousel-title-region-label="{{ __('Digital object title link') }}">
  <div class="accordion-item border-0">
    <h2 class="accordion-header rounded-0 rounded-top border border-bottom-0" id="heading-carousel">
      <button class="accordion-button rounded-0 rounded-top text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-carousel" aria-expanded="true" aria-controls="collapse-carousel">
        <span>{{ __('Image carousel') }}</span>
      </button>
    </h2>
    <div id="collapse-carousel" class="accordion-collapse collapse show" aria-labelledby="heading-carousel">
      <div class="accordion-body bg-secondary px-5 pt-4 pb-3">
        <div id="slider-images" class="mb-0">
          @foreach($thumbnails as $idx => $item)
            @php
              $meta = $thumbnailMeta[$idx] ?? null;
              $slug = $meta ? $meta['slug'] : null;
              $title = $meta ? $meta['title'] : '';
              $href = $slug ? route('display.show', ['id' => $item->id ?? 0]) : '#';
              $filePath = $item->path ?? '';
              $iconClass = _getFileIconDisplay($filePath, $extIconMap, $imageExts);
            @endphp
            <a title="{{ e($title) }}" href="{{ $href }}">
              @if($iconClass)
              <span class="img-thumbnail mx-2 d-inline-flex align-items-center justify-content-center" style="width:120px;height:120px;background:#f8f9fa;">
                <i class="{{ $iconClass }} fa-3x text-secondary"></i>
              </span>
              @else
              <img src="{{ $filePath }}" class="img-thumbnail mx-2" alt="{{ strip_tags($title ?: 'Untitled') }}" style="max-height:120px;">
              @endif
            </a>
          @endforeach
        </div>

        <div id="slider-title">
          @foreach($thumbnails as $idx => $item)
            @php
              $meta = $thumbnailMeta[$idx] ?? null;
              $title = $meta ? $meta['title'] : '';
              $href = route('display.show', ['id' => $item->id ?? 0]);
            @endphp
            <a href="{{ $href }}" class="text-white text-center mt-2 mb-1">
              {{ strip_tags($title ?: __('Untitled')) }}
            </a>
          @endforeach
        </div>

        @if(isset($limit) && isset($total) && $limit < $total)
          <div class="text-white text-center mt-2 mb-1">
            {{ __('Results :from to :to of :total', ['from' => 1, 'to' => $limit, 'total' => $total]) }}
            <a class="btn btn-outline-light btn-sm ms-2" href="{{ route('display.browse', ['onlyMedia' => true]) }}">{{ __('Show all') }}</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
