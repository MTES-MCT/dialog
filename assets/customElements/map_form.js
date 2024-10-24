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
    
        const url = this.#form.action + '?' + searchParams.toString();

        this.#map.setAttribute(this.#urlAttribute, url);
    }
});
