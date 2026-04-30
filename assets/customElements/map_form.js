// @ts-check

import { getAttributeOrError, querySelectorOrError } from "./util";

customElements.define('d-map-form', class extends HTMLElement {
    /** @type {HTMLFormElement} */
    #form;

    /** @type {HTMLElement} */
    #map;

    /** @type {string} */
    #urlAttribute;

    connectedCallback() {
        requestAnimationFrame(() => {
            const form = /** @type {HTMLFormElement} */ querySelectorOrError(this, 'form');
            this.#form = form;

            const map = /** @type {HTMLElement} */ querySelectorOrError(document, `#${getAttributeOrError(this, 'target')}`);
            this.#map = map;

            this.#urlAttribute = /** @type {string} */ getAttributeOrError(this, 'urlAttribute');

            this.#init();
        });
    }

    #init() {
        this.#setUrlFromFormValues();

        for (const formControl of this.#form.elements) {
            formControl.addEventListener('change', () => {
                this.#setUrlFromFormValues();
            });
        }
    }

    #setUrlFromFormValues() {
        const formData = new FormData(this.#form);
        const searchParams = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            searchParams.append(key, value.toString());
        }

        // Use the raw `action` attribute rather than `form.action`: when the action
        // contains URL-template placeholders like `{z}/{x}/{y}` (e.g. for MapLibre
        // vector tile sources), the `action` property would URL-encode the curly
        // braces (`%7Bz%7D`), which prevents MapLibre from substituting them.
        // We then prefix with the document origin to produce an absolute URL,
        // as MapLibre tile workers cannot resolve relative URLs.
        const action = this.#form.getAttribute('action') || this.#form.action;
        const absoluteAction = action.startsWith('http') ? action : window.location.origin + action;
        const url = absoluteAction + '?' + searchParams.toString();

        this.#map.setAttribute(this.#urlAttribute, url);
    }
});
