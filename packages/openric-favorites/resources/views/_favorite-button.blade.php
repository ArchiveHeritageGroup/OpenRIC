{{-- Partial: Favorite toggle button for use on entity detail pages --}}
@props(['entityIri' => '', 'entityType' => '', 'title' => '', 'isFavorite' => false])
<button type="button"
        class="btn btn-sm {{ $isFavorite ? 'btn-warning' : 'btn-outline-secondary' }} favorite-toggle"
        data-entity-iri="{{ $entityIri }}"
        data-entity-type="{{ $entityType }}"
        data-title="{{ $title }}"
        data-url="{{ route('favorites.toggle') }}"
        title="{{ $isFavorite ? 'Remove from favorites' : 'Add to favorites' }}">
    <i class="fas fa-star"></i>
</button>
<script>
(function() {
    document.querySelectorAll('.favorite-toggle').forEach(function(btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function() {
            var data = {
                entity_iri: btn.dataset.entityIri,
                entity_type: btn.dataset.entityType,
                title: btn.dataset.title
            };
            fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.favorited) {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-warning');
                    btn.title = 'Remove from favorites';
                } else {
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-outline-secondary');
                    btn.title = 'Add to favorites';
                }
            });
        });
    });
})();
</script>
