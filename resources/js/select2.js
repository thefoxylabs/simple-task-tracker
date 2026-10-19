import $ from 'jquery';
import select2 from 'select2';

select2(window, $);
window.$ = window.jQuery = $;

/**
 * Turn every <select> into a searchable Select2 dropdown.
 *
 * Add `data-native` to a <select> to keep the browser's default control.
 * Livewire re-renders strip the attributes Select2 adds, so this runs again
 * after every update and re-creates any dropdown that was reset.
 */
function enhance(root = document) {
    root.querySelectorAll('select:not([data-native]):not([multiple])').forEach((el) => {
        if (! el.isConnected) {
            return;
        }

        const $el = $(el);
        const intact = el.classList.contains('select2-hidden-accessible')
            && el.nextElementSibling?.classList.contains('select2-container');

        if (intact) {
            // Keep the visible label in sync with values Livewire set.
            $el.trigger('change.select2');
            return;
        }

        // Livewire's re-render removes the ids Select2 uses to find its
        // instance, so clean up the old one by hand instead of destroy().
        if (el.nextElementSibling?.classList.contains('select2-container')) {
            el.nextElementSibling.remove();
        }
        $el.removeData('select2').off('.select2');

        const autoWidth = el.classList.contains('w-auto');

        $el.select2({
            width: autoWidth ? 'element' : '100%',
            minimumResultsForSearch: 0,
            dropdownAutoWidth: autoWidth,
            dropdownParent: $(el.closest('[role="dialog"]') ?? document.body),
        });

        if (autoWidth) {
            $el.next('.select2-container').addClass('select2-auto');
        }

        // Livewire listens for native "change" events; Select2 only fires jQuery ones.
        $el.off('.livewire').on('select2:select.livewire select2:clear.livewire', () => {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
}

const refresh = (root) => setTimeout(() => enhance(root));

function registerHooks() {
    // Leave Select2's own markup alone while Livewire updates the page: only
    // the <option>s are refreshed, so open dropdowns aren't torn down.
    window.Livewire.hook('morph.updating', ({ el, childrenOnly }) => {
        if (el.tagName === 'SELECT' && el.classList.contains('select2-hidden-accessible')) {
            childrenOnly();
        }
    });

    window.Livewire.hook('morph.removing', ({ el, skip }) => {
        if (! el.classList?.contains('select2-container')) {
            return;
        }

        const select = el.previousElementSibling;
        const isDropdown = el.classList.contains('select2-container--open') && ! select?.matches('select');

        if (isDropdown || select?.classList.contains('select2-hidden-accessible')) {
            skip();
        }
    });

    window.Livewire.hook('morphed', ({ el }) => refresh(el));
}

if (window.Livewire) {
    registerHooks();
} else {
    document.addEventListener('livewire:init', registerHooks);
}

document.addEventListener('livewire:navigated', () => refresh(document));
document.addEventListener('DOMContentLoaded', () => refresh(document));
refresh(document);

// Focus the search box as soon as a dropdown opens.
$(document).on('select2:open', () => {
    document.querySelector('.select2-container--open .select2-search__field')?.focus();
});
