{{-- Accessibility helpers — adapted from Heratio accessibility-helpers.blade.php (128 lines) --}}
{{-- WCAG 2.1 AA compliance: ARIA live region, keyboard navigation, focus management --}}

{{-- ARIA live region for dynamic announcements --}}
<div id="openricLiveRegion" class="visually-hidden" aria-live="polite" aria-atomic="true" role="status"></div>

<script>
/**
 * Announce a message to screen readers via the ARIA live region.
 * @param {string} message
 * @param {string} priority - 'polite' (default) or 'assertive'
 */
function openricAnnounce(message, priority) {
    var region = document.getElementById('openricLiveRegion');
    if (!region) return;
    region.setAttribute('aria-live', priority || 'polite');
    region.textContent = '';
    // Small delay ensures screen readers pick up the change
    setTimeout(function() { region.textContent = message; }, 100);
}

/**
 * Move focus to an element and announce it.
 * @param {string} selector
 * @param {string} message
 */
function openricFocusTo(selector, message) {
    var el = document.querySelector(selector);
    if (!el) return;
    el.setAttribute('tabindex', '-1');
    el.focus();
    if (message) openricAnnounce(message);
}

document.addEventListener('DOMContentLoaded', function() {
    // Escape key closes modals — Bootstrap 5 handles this, but ensure it also works for custom modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var openModal = document.querySelector('.modal.show');
            if (openModal) {
                var bsModal = bootstrap.Modal.getInstance(openModal);
                if (bsModal) bsModal.hide();
            }
        }
    });

    // Auto-scope table headers for screen readers
    document.querySelectorAll('table thead th').forEach(function(th) {
        if (!th.hasAttribute('scope')) th.setAttribute('scope', 'col');
    });
    document.querySelectorAll('table tbody th').forEach(function(th) {
        if (!th.hasAttribute('scope')) th.setAttribute('scope', 'row');
    });

    // Auto aria-required on required form fields
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(el) {
        el.setAttribute('aria-required', 'true');
    });

    // Auto aria-label on batch/bulk checkboxes (no visible label)
    document.querySelectorAll('input[type="checkbox"].batch-select, input[type="checkbox"].select-all').forEach(function(el) {
        if (!el.hasAttribute('aria-label')) {
            el.setAttribute('aria-label', el.classList.contains('select-all') ? 'Select all items' : 'Select this item');
        }
    });

    // Watch for validation errors and set aria-invalid
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                var el = mutation.target;
                if (el.classList.contains('is-invalid')) {
                    el.setAttribute('aria-invalid', 'true');
                    var feedback = el.parentElement ? el.parentElement.querySelector('.invalid-feedback') : null;
                    if (feedback && feedback.id) {
                        el.setAttribute('aria-describedby', feedback.id);
                    }
                } else {
                    el.removeAttribute('aria-invalid');
                }
            }
        });
    });

    document.querySelectorAll('input, select, textarea').forEach(function(el) {
        observer.observe(el, { attributes: true, attributeFilter: ['class'] });
    });

    // Heading hierarchy check (admin/edit pages only, console warning)
    @if($themeData['isAdmin'] ?? false)
        var headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
        var lastLevel = 0;
        headings.forEach(function(h) {
            var level = parseInt(h.tagName.charAt(1));
            if (level > lastLevel + 1 && lastLevel > 0) {
                console.warn('[A11y] Heading hierarchy skipped from h' + lastLevel + ' to h' + level + ':', h.textContent.trim().substring(0, 50));
            }
            lastLevel = level;
        });
    @endif
});
</script>
