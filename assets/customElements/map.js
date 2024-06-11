// @ts-check

import { getAttributeOrError, querySelectorOrError } from './util';

/**
 * A source for map data that reads GeoJSON data in the textContent of an HTML element.
 */
class MapDataSource {
    /** @type {HTMLElement} */
    #element;

    /**
     * @param {HTMLElement} element
     */
    constructor(element) {
        this.#element = element;
    }

    /** @returns {any} */
    getValue() {
        return JSON.parse(this.#element.textContent || '{}');
    }

    /**
     * @param {(value: any) => void} callback
     */
    onChange(callback) {
        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
                    const parts = [];

                    mutation.addedNodes.forEach(node => {
                        parts.push(node.textContent);
                    });

                    const text = parts.join('');

                    if (text) {
                        callback(JSON.parse(text));
                    }
                }
            }
        });

        observer.observe(this.#element, { childList: true });
    }
}

class MapLibreMap {
    /** @type {string} */
    #locationPopupUrl;

    /** @type {Promise<maplibregl.Map>} */
    #prom;

    /** @type {any} */
    #maplibregl;

    /** @type {maplibregl.Map} */
    #map;

    /**
     * @param {HTMLElement} root
     * @param {string} height
     * @param {[number, number]} center
     * @param {number} zoom
     * @param {string} locationPopupUrl
     * @param {MapDataSource} dataSource
     */
    constructor(
        root,
        height,
        center,
        zoom,
        locationPopupUrl,
        dataSource,
    ) {
        this.#locationPopupUrl = locationPopupUrl;

        this.#prom = new Promise((resolve) => {
            // Lazy load to only transfer MapLibre JS when loading the map
            import('maplibre-gl').then(exports => {
                const maplibregl = exports.default;
                this.#maplibregl = maplibregl;

                // Create a container for the map
                const mapContainer = document.createElement('div');
                mapContainer.style.height = height;
                mapContainer.hidden = true; // Don't show an empty map
                root.appendChild(mapContainer);

                // Create and configure the map
                const map = new maplibregl.Map({
                    container: mapContainer,
                    style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json',
                    center,
                    zoom,
                    hash: "mapZoomAndPosition",
                });

                this.#map = map;

                map.on('load', () => {
                    map.addControl(new maplibregl.NavigationControl(), 'bottom-right');

                    map.addSource('locations-source', {
                        type: 'geojson',
                        data: dataSource.getValue(),
                        tolerance: 0.0, // we want to display the data at very low zoom level -> tolerance must be very low
                    });

                    dataSource.onChange(data => {
                        // credits to https://maplibre.org/maplibre-gl-js/docs/examples/live-update-feature/
                        map.getSource('locations-source').setData(data);
                    });

                    map.addLayer(
                        {
                            'id': 'locations-layer',
                            'type': 'line',
                            'source': 'locations-source',
                            'layout': {
                                'line-join': 'round',
                                'line-cap': 'round',
                            },
                            'paint': {
                                'line-color': ['case', // https://maplibre.org/maplibre-style-spec/expressions/#case : ['case', boolean, returned value, default value]
                                    ['==', ['get', 'measure_type'], 'noEntry'], '#ff5655', // red
                                    ['==', ['get', 'measure_type'], 'speedLimitation'], '#ff742e', // orange
                                    '#000000'], // black ; blue -> 0063cb
                                'line-width': 4,
                            },
                        },
                        "toponyme numéro de route - départementale" // insert this layer below the main label layers like road labels
                    );

                    // popup when clicking on a feature of the locations layer
                    map.on('click', 'locations-layer', (event) => {
                        if (!event.features) {
                            return;
                        }
                        const { properties } = event.features[0];
                        this.#openLocationPopup(event.lngLat.toArray(), properties.location_uuid);
                    });

                    // change the cursor when the mouse is over the locations layer
                    map.on('mouseenter', 'locations-layer', () => {
                        map.getCanvas().style.cursor = 'pointer';
                    });
                    map.on('mouseleave', 'locations-layer', () => {
                        map.getCanvas().style.cursor = '';
                    });

                    // The map is ready to be revealed
                    mapContainer.hidden = false;

                    resolve(map);
                });
            })
        });
    }

    /**
     * @param {(instance: maplibregl.Map) => void} callback
     */
    onReady(callback) {
        this.#prom.then(() => {
            callback(this.#map);
        });
    }

    /**
     *
     * @param {[number, number]} pos
     * @param {string} uuid
     */
    #openLocationPopup(pos, uuid) {
        const locationTurboFrame = document.createElement('turbo-frame');
        locationTurboFrame.id = `location_turbo_frame_${uuid}`;
        locationTurboFrame.setAttribute('src', `${this.#locationPopupUrl}/${uuid}`);

        const locationPopUp = new this.#maplibregl.Popup({closeButton: false})
            .setLngLat(pos)
            .setDOMContent(locationTurboFrame)
            .addTo(this.#map);

        const locationPopUpContainer = locationPopUp.getElement();
        locationPopUpContainer.setAttribute("hidden", "");

        // display the popup when the turbo frame is loaded (otherwise MapLibre GL JS will display an empty popup for a few seconds)
        locationTurboFrame.addEventListener('turbo:frame-load', () => {
            locationPopUpContainer.removeAttribute("hidden");
            // force an update for dynamic positioning (as the popup is filled after its creation, thanks to an AJAX request)
            // credits : https://stackoverflow.com/questions/60928595/dynamic-anchor-popup-open-outside-of-map-container
            locationPopUp._update();

	    // custom close button of the popup
	    const customCloseButton = document.getElementById(`close_location_popup_${uuid}`);
	    if (customCloseButton) {
		customCloseButton.addEventListener('click', () => {
		    locationPopUp.remove();
		});
	    }

        });
    }
}

const METROPOLITAN_FRANCE_CENTER = '[2.725, 47.16]';

customElements.define('d-map', class extends HTMLElement {
    connectedCallback() {
        const mapHeight = this.getAttribute('mapHeight') || '300px';
        const mapPos = JSON.parse(this.getAttribute('mapPos') || METROPOLITAN_FRANCE_CENTER);
        const mapZoom = +(this.getAttribute('mapZoom') || 13);
        const locationPopupUrl = getAttributeOrError(this, 'locationPopupUrl');
        const dataSource = new MapDataSource(querySelectorOrError(document, `#${getAttributeOrError(this, 'dataSource')}`));

        const map = new MapLibreMap(
            this,
            mapHeight,
            mapPos,
            mapZoom,
            locationPopupUrl,
            dataSource,
        );

        map.onReady((mapInstance) => {
            const elementsToHide = /** @type {NodeListOf<HTMLElement>} */ (this.querySelectorAll('[data-map-hidewhen=ready]'));
            elementsToHide.forEach(el => el.hidden = true);

            // useful to debug the map in the JS console of your browser : access it with "document.getElementsByTagName('d-map')[0].map"
            this.map = mapInstance;
        });
    }
});
