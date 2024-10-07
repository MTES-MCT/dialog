// @ts-check

import { getAttributeOrError, querySelectorOrError } from "./util";
import { MapElement } from './map';

customElements.define('d-map-search-form', class extends HTMLElement {
    connectedCallback() {
        requestAnimationFrame(() => {
            /** @type {MapElement} */
            const map = querySelectorOrError(document, `#${getAttributeOrError(this, 'target')}`);

            /** @type {HTMLInputElement} */
            const searchValueField = querySelectorOrError(document, '#search_value');

            searchValueField.addEventListener('change', () => {
                const { coordinates, kind } = JSON.parse(searchValueField.value);

                // Zoom closer on streets
                const zoom = kind === 'street' ? 17 : 14;

                map.flyTo(coordinates, zoom);
            });
        });
    }
});
