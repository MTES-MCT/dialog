// @ts-check

import { getAttributeOrError } from './util';
import { mapStyles } from 'carte-facile';
import { MEASURE_TYPE_STYLES, buildMeasureLineLayers } from '../maps/measure_type_styles';

/**
 * The `source-layer` name used inside the MVT tiles produced by the backend
 * (see LocationRepository::findRestrictionsAsMVT).
 */
const RESTRICTIONS_SOURCE_LAYER = 'restrictions';

class MapLibreMap {
    /** @type {string} */
    #locationPopupUrl;

    /** @type {Promise<maplibregl.Map>} */
    #prom;

    /** @type {maplibregl} */
    #maplibregl;

    /** @type {maplibregl.Map} */
    #map;

    /** @type {MapElement} */
    #mapElement;

    /** @type {HTMLElement} */
    #loader;

    /**
     * @param {MapElement} mapElement
     * @param {HTMLElement} root
     * @param {string} height
     * @param {string} minHeight
     * @param {[number, number]} center
     * @param {number} zoom
     * @param {string} locationPopupUrl
     * @param {[[number, number], [number, number]] | null} initialBbox
     */
    constructor(
        mapElement,
        root,
        height,
        minHeight,
        center,
        zoom,
        locationPopupUrl,
        initialBbox,
    ) {
        this.#locationPopupUrl = locationPopupUrl;
        this.#mapElement = mapElement;

        // Create a container for the map
        const mapContainer = document.createElement('div');
        mapContainer.style.height = height;
        mapContainer.style.minHeight = minHeight;
        mapContainer.style.position = 'relative'; // Anchor the absolutely-positioned loader overlay
        mapContainer.hidden = true; // Don't show an empty map
        root.appendChild(mapContainer);

        // Loader overlay shown while restriction tiles are being fetched.
        const loader = document.createElement('div');
        loader.className = 'd-map-loader';
        loader.hidden = true;
        loader.innerHTML = '<div class="d-map-loader__chip" role="status" aria-live="polite">'
            + '<span class="d-map-loader__spinner" aria-hidden="true"></span>'
            + '<span>Chargement…</span>'
            + '</div>';
        mapContainer.appendChild(loader);
        this.#loader = loader;

        /** @type {Partial<maplibregl.MapOptions>} */
        const mapOptions = {
            center,
            zoom,
            minZoom: 4.33, // Prevent zooming less than size of metropolitan France
            maxZoom: 18, // Default is 22, adjust so that maximum zoom makes house numbers visible
        };

        // Apply the initial bbox via Map options so that MapLibre treats it as the canonical
        // initial position (it overrides center/zoom). Setting it via fitBounds() after
        // construction can race with MapLibre's `hash` option and/or be overridden.
        // We only honor the bbox when the URL doesn't already carry a position hash.
        if (initialBbox && !window.location.hash.includes('mapZoomAndPosition=')) {
            mapOptions.bounds = initialBbox;
            mapOptions.fitBoundsOptions = { padding: 40, animate: false };
        }

        this.#prom = this.#init(mapOptions, mapContainer);
    }

    /**
     * @param {Partial<maplibregl.MapOptions>} mapOptions
     * @param {HTMLElement} mapContainer
     * @returns {Promise<maplibregl.Map>}
     */
    async #init(mapOptions, mapContainer) {
        // Lazy load to only transfer MapLibre JS when loading the map
        const exports = await import('maplibre-gl');
        const maplibregl = exports.default;
        this.#maplibregl = maplibregl;

        return new Promise((resolve) => {
            // NOTE: creation and configuration of the map should be done synchronously, without any 'await' in between.
            // See: https://github.com/MTES-MCT/dialog/issues/881

            // Create and configure the map
            const map = new maplibregl.Map({
                container: mapContainer,
                style: mapStyles.desaturated,
                hash: 'mapZoomAndPosition',
                ...mapOptions,
            });
            this.#map = map;

            map.on('load', () => {
                map.addControl(new maplibregl.NavigationControl(), 'bottom-right');

                map.addSource('locations-source', {
                    type: 'vector',
                    tiles: [this.#mapElement.getTilesUrl()],
                    minzoom: 0,
                    // Beyond this zoom MapLibre re-uses ("overzooms") the z=14 tile
                    // instead of fetching higher-zoom tiles. For line geometries this
                    // is visually fine and drastically reduces the number of requests
                    // at deep zoom levels.
                    maxzoom: 14,
                });

                this.#mapElement.onTilesUrlChange(() => {
                    const source = /** @type {maplibregl.VectorTileSource} */ (map.getSource('locations-source'));
                    if (source && typeof source.setTiles === 'function') {
                        source.setTiles([this.#mapElement.getTilesUrl()]);
                    }
                });

                // Show the loader while restriction tiles are being fetched (initial load,
                // pan/zoom, and filter changes — which trigger a tile refetch via setTiles).
                const updateLoader = () => {
                    this.#loader.hidden = map.isSourceLoaded('locations-source');
                };
                map.on('sourcedataloading', (event) => {
                    if (event.sourceId === 'locations-source') {
                        this.#loader.hidden = false;
                    }
                });
                map.on('sourcedata', (event) => {
                    if (event.sourceId === 'locations-source') {
                        updateLoader();
                    }
                });

                const lineWidthFirstStep = 15;
                const lineWidthSecondStep = 18;
                const measureLayerIds = [];
                for (const [measureType, style] of Object.entries(MEASURE_TYPE_STYLES)) {
                    const layers = buildMeasureLineLayers(measureType, style, {
                        sourceId: 'locations-source',
                        sourceLayer: RESTRICTIONS_SOURCE_LAYER,
                        lineWidthFirstStep,
                        lineWidthSecondStep,
                    });
                    for (const layer of layers) {
                        map.addLayer(layer);
                        measureLayerIds.push(layer.id);
                    }
                }

                // Invisible click-zone layer covering all measures, inserted below the
                // first measure layer so that visible styling is preserved while click
                // events still reach this layer's listeners.
                map.addLayer(
                    {
                        'id': 'locations-layer-click-zone',
                        'type': 'line',
                        'source': 'locations-source',
                        'source-layer': RESTRICTIONS_SOURCE_LAYER,
                        'layout': {
                            'line-join': 'round',
                            'line-cap': 'round',
                        },
                        'paint': {
                            'line-color': '#000000',
                            'line-width': ["step", ["zoom"], 12, lineWidthFirstStep, 16, lineWidthSecondStep, 20], // like the measures layers above : steps are zoom = 15 and zoom = 18
                            'line-opacity': 0, // fully transparent
                        },
                    },
                    measureLayerIds[0],
                );

                // popup when clicking on a feature of the locations layer
                map.on('click', 'locations-layer-click-zone', (event) => {
                    // When restrictions overlap, pick the topmost rendered measure
                    // feature so the popup matches the trace the user sees on top.
                    // event.features[0] comes from the invisible click-zone and does
                    // not follow the visual stacking order of the measure layers.
                    // Fall back to it only when the click lands in a dash gap.
                    const visibleFeatures = map.queryRenderedFeatures(event.point, { layers: measureLayerIds });
                    const feature = visibleFeatures[0] ?? event.features?.[0];
                    if (!feature) {
                        return;
                    }
                    this.#openLocationPopup(event.lngLat.toArray(), feature.properties.location_uuid);
                });

                // change the cursor when the mouse is over the locations layer
                map.on('mouseenter', 'locations-layer-click-zone', () => {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'locations-layer-click-zone', () => {
                    map.getCanvas().style.cursor = '';
                });

                // The map is ready to be revealed
                mapContainer.hidden = false;

                resolve(map);
            });
        })
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

        const locationPopUp = new this.#maplibregl.Popup({
            closeButton: false,
            className: 'd-map-popup',
            maxWidth: '320px',
        })
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

export class MapElement extends HTMLElement {
    connectedCallback() {
        const mapHeight = this.getAttribute('mapHeight') || '100%';
        const mapMinHeight = this.getAttribute('mapMinHeight') || '600px';
        const mapPos = JSON.parse(this.getAttribute('mapPos') || METROPOLITAN_FRANCE_CENTER);
        const mapZoom = +(this.getAttribute('mapZoom') || 13);
        const locationPopupUrl = getAttributeOrError(this, 'locationPopupUrl');

        const initialBboxAttr = this.getAttribute('initialBbox');
        /** @type {[[number, number], [number, number]] | null} */
        let initialBbox = null;
        if (initialBboxAttr) {
            try {
                const parsed = JSON.parse(initialBboxAttr);
                if (parsed && typeof parsed === 'object'
                    && Number.isFinite(parsed.minLon) && Number.isFinite(parsed.minLat)
                    && Number.isFinite(parsed.maxLon) && Number.isFinite(parsed.maxLat)) {
                    initialBbox = [
                        [parsed.minLon, parsed.minLat],
                        [parsed.maxLon, parsed.maxLat],
                    ];
                }
            } catch (e) {
                // Invalid JSON: fall back to the default position.
            }
        }

        const map = new MapLibreMap(
            this,
            this,
            mapHeight,
            mapMinHeight,
            mapPos,
            mapZoom,
            locationPopupUrl,
            initialBbox,
        );

        map.onReady((mapInstance) => {
            const elementsToHide = /** @type {NodeListOf<HTMLElement>} */ (this.querySelectorAll('[data-map-hidewhen=ready]'));
            elementsToHide.forEach(el => el.hidden = true);

            // useful to debug the map in the JS console of your browser : access it with "document.getElementsByTagName('d-map')[0].map"
            this.map = mapInstance;
        });
    }

    /**
     * Returns the tile URL template as an absolute URL. MapLibre's tile workers
     * require absolute URLs (the `Request` constructor in worker context cannot
     * resolve relative URLs against `document.baseURI`).
     * @returns {string}
     */
    getTilesUrl() {
        const url = getAttributeOrError(this, 'tilesUrl');
        return url.startsWith('http') ? url : window.location.origin + url;
    }

    /**
     * @param {() => void} callback
     */
    onTilesUrlChange(callback) {
        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === "attributes" && mutation.attributeName?.toLowerCase() === 'tilesurl') {
                    callback();
                }
            }
        });

        observer.observe(this, { attributes: true });
    }

    /**
     * Center map on given coordinates
     * @param {[number, number]} coordinates
     * @param {number} zoom
     */
    flyTo(coordinates, zoom) {
        this.map?.flyTo({
            center: coordinates,
            zoom,
            // Animation options
            duration: 2000, // ms
            essential: false, // Disable if browser has [prefers-reduced-motion]
        });
    }
}

customElements.define('d-map', MapElement);
