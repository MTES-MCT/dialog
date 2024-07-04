// @ts-check

import { getAttributeOrError, querySelectorOrError } from "./util";

customElements.define('d-map-form', class extends HTMLElement {
    /** @type {HTMLFormElement} */
    #form;

    /** @type {HTMLElement} */
    #map;

    connectedCallback() {
        requestAnimationFrame(() => {
            const form = /** @type {HTMLFormElement} */ querySelectorOrError(this, 'form');
            this.#form = form;
            
            const map = /** @type {HTMLElement} */ querySelectorOrError(document, `#${getAttributeOrError(this, 'target')}`);
            this.#map = map;

            this.#init();
        });
    }

    #init() {
        for (const formControl of this.#form.elements) {
            formControl.addEventListener('change', () => {
                this.#onChange();
            });
        }
    }

    #onChange() {
        const url = this.#makeUrl();
        this.#map.setAttribute('dataUrl', url);
    }

    #makeUrl() {
        const formData = new FormData(this.#form);
        const searchParams = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            searchParams.append(key, value.toString());
        }

        return this.#form.action + '?' + searchParams.toString();
    }
});
