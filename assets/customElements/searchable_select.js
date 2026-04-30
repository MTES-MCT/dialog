// @ts-check
import Choices from 'choices.js';
// @ts-ignore - CSS side-effect import handled by Webpack
import 'choices.js/public/assets/styles/choices.css';
import { querySelectorOrError } from './util';

customElements.define('d-searchable-select', class extends HTMLElement {
    /** @type {Choices|null} */
    #choices = null;

    connectedCallback() {
        const select = querySelectorOrError(this, 'select');

        // Idempotent: avoid double-init (e.g. after a Turbo morph).
        if (select.dataset.searchableInit === '1') {
            return;
        }
        select.dataset.searchableInit = '1';

        const searchPlaceholder = this.getAttribute('search-placeholder') || '';
        const noResultsLabel = this.getAttribute('no-results-label') || '';
        const noChoicesLabel = this.getAttribute('no-choices-label') || '';

        this.#choices = new Choices(select, {
            searchEnabled: true,
            searchPlaceholderValue: searchPlaceholder,
            shouldSort: false, // Preserve server-side ordering.
            itemSelectText: '',
            allowHTML: false,
            noResultsText: noResultsLabel,
            noChoicesText: noChoicesLabel,
        });
    }

    disconnectedCallback() {
        if (this.#choices) {
            this.#choices.destroy();
            this.#choices = null;
        }
    }
});
