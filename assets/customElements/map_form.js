// @ts-check

import { getAttributeOrError, querySelectorOrError } from "./util";

customElements.define('d-map-form', class extends HTMLElement {
    /** @type {HTMLFormElement} */
    #form;

    /** @type {HTMLElement} */
    #map;

    /** @type {string} */
    #urlAttribute;

    /**
     * @type {string | null | undefined}
     */
    #paramPrefix;

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

        this.#syncBrowserUrl(formData);
    }

    /**
     * @param {FormData} formData
     */
    #syncBrowserUrl(formData) {
        const paramPrefix = this.#getParamPrefix();
        if (paramPrefix === null) {
            return;
        }

        const url = new URL(window.location.href);

        for (const key of [...url.searchParams.keys()]) {
            if (key.startsWith(paramPrefix)) {
                url.searchParams.delete(key);
            }
        }

        for (const [key, value] of formData.entries()) {
            const stringValue = value.toString();
            if (!key.startsWith(paramPrefix) || stringValue === '') {
                continue;
            }
            url.searchParams.append(key, stringValue);
        }

        window.history.replaceState(null, '', url.toString());
    }

    /**
     * @returns {string | null}
     */
    #getParamPrefix() {
        if (this.#paramPrefix !== undefined) {
            return this.#paramPrefix;
        }

        for (const formControl of this.#form.elements) {
            const name = /** @type {HTMLInputElement} */ (formControl).name;
            const bracketIndex = name ? name.indexOf('[') : -1;
            if (bracketIndex > 0) {
                this.#paramPrefix = name.slice(0, bracketIndex);
                return this.#paramPrefix;
            }
        }

        this.#paramPrefix = null;
        return null;
    }
});
