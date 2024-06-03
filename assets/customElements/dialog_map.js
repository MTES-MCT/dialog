// @ts-check

customElements.define('dialog-map', class extends HTMLElement {
    connectedCallback() {
        const height = this.dataset.height || '300px';
        /** @type {[number, number]} */
        const pos = JSON.parse(this.dataset.pos || '');
        const zoom = +(this.dataset.zoom || 13);

        const sourceEl = document.getElementById(this.dataset['source']);
        const geojson = JSON.parse(sourceEl.textContent);

        const locationPath = this.dataset['locationPath'];

        const container = document.createElement('div');
        container.style.height = height;
        container.hidden = true;
        this.appendChild(container);

        const elementsToHide = /** @type {NodeListOf<HTMLElement>} */ (this.querySelectorAll('[data-hidewhen=ready]'));

        createMapLibreMap(
            container, pos, zoom, geojson, sourceEl, locationPath
        ).then((map) => {
            container.hidden = false;
            elementsToHide.forEach(el => el.hidden = true);

            this.map = map; // useful to debug the map in the JS console of your browser : access it with "document.getElementsByTagName('dialog-map')[0].map"
        });
    }
});

async function createMapLibreMap(container, pos, zoom, geojson, sourceEl, locationPath) {
    // Lazy load to only transfer MapLibre JS when loading the map :
    const maplibregl = (await import('maplibre-gl')).default;

    const styleLink = document.createElement('link');
    styleLink.rel = 'stylesheet';
    styleLink.href = 'https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css';
    document.head.appendChild(styleLink);

    const map = new maplibregl.Map({
        container: container,
        style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json',
        //style: osm_style,
        center: pos,
        zoom,
        hash: "mapZoomAndPosition",
    });

    return new Promise((resolve) => {
        // credit: https://maplibre.org/maplibre-gl-js/docs/examples/geojson-line/
        map.on('load', () => {
            resolve(map);

            map.addControl(new maplibregl.NavigationControl(), 'bottom-right');
            map.addSource('locations-source',  {
                type: 'geojson',
                data: geojson,
                tolerance: 0.0, // we want to display the data at very low zoom level -> tolerance must be very low
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
            map.on('click', 'locations-layer', (e) => {
                const locationProperties = e.features[0].properties;
                const locationTurboFrame = document.createElement('turbo-frame');
                locationTurboFrame.id = `location_turbo_frame_${locationProperties.location_uuid}`;
                locationTurboFrame.src = `${locationPath}/${locationProperties.location_uuid}`;
                const locationPopUp = new maplibregl.Popup()
                    .setLngLat(e.lngLat)
                    .setDOMContent(locationTurboFrame)
                    .addTo(map);
                const locationPopUpContainer = locationPopUp.getElement();
                if (locationPopUpContainer) {
                    locationPopUpContainer.setAttribute("hidden", "");
                }
                // display the popup when the turbo frame is loaded (otherwise MapLibre GL JS will display an empty popup for a few seconds)
                locationTurboFrame.addEventListener('turbo:frame-load', () => {
                    if (locationPopUpContainer) {
                        locationPopUpContainer.removeAttribute("hidden");
                    }
                    // force an update for dynamic positioning (as the popup is filled after its creation, thanks to an AJAX request)
                    // credits : https://stackoverflow.com/questions/60928595/dynamic-anchor-popup-open-outside-of-map-container
                    locationPopUp._update();
                });
            });
            // change the cursor when the mouse is over the locations layer
            map.on('mouseenter', 'locations-layer', () => {
                map.getCanvas().style.cursor = 'pointer';
            });
            map.on('mouseleave', 'locations-layer', () => {
                map.getCanvas().style.cursor = '';
            });
        });

        // Mutation API Observer
        function mutationCallback(mutationList) {
            for (const mutation of mutationList) {
                if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
                    const geoJson = mutation.addedNodes[0].textContent;
                    // credits to https://maplibre.org/maplibre-gl-js/docs/examples/live-update-feature/
                    map.getSource('locations-source').setData(JSON.parse(geoJson));
                }
            }
        };

        console.log('source', sourceEl);
        const observer = new MutationObserver(mutationCallback);
        observer.observe(sourceEl, { childList: true });
    });
}
