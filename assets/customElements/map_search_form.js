// @ts-check

import { getAttributeOrError, querySelectorOrError } from "./util";
import { MapElement } from './map';

// Hélas les types de lieux ne sont pas documentés par l'IGN.
// On peut les redécouvrir à partir des réponses de l'API.
// Voir la doc : https://geoservices.ign.fr/documentation/services/services-geoplateforme/autocompletion
const ZOOM_MAP = {
    housenumber: 18, // Adresse
    street: 17, // Rue
    default: 14, // Tout le reste
};

/**
 * @param {string} kind 
 * @returns {number}
 */
function getZoom(kind) {
    return ZOOM_MAP[kind] ?? ZOOM_MAP['default'];
}

customElements.define('d-map-search-form', class extends HTMLElement {
    connectedCallback() {
        requestAnimationFrame(() => {
            /** @type {MapElement} */
            const map = querySelectorOrError(document, `#${getAttributeOrError(this, 'target')}`);

            /** @type {HTMLInputElement} */
            const searchValueField = querySelectorOrError(document, '#search_value');

            searchValueField.addEventListener('change', () => {
                const { coordinates, kind } = JSON.parse(searchValueField.value);
                const zoom = getZoom(kind);
                map.flyTo(coordinates, zoom);
            });
        });
    }
});
