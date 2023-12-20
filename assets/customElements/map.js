// @ts-check

export default class extends HTMLElement {
    connectedCallback() {
        const height = this.dataset.height || '300px';
        /** @type {[number, number]} */
        const pos = JSON.parse(this.dataset.pos || '');
        const zoom = +(this.dataset.zoom || 13);
        const geometries = JSON.parse(this.dataset.geometries || '[]');

        const container = document.createElement('div');
        container.style.height = height;
        this.appendChild(container);

        createMapLibreMap(container, pos, zoom, geometries);
    }
}

async function createMapLibreMap(container, pos, zoom, geometries) {
    const maplibregl = (await import('maplibre-gl')).default;

    const styleLink = document.createElement('link');
    styleLink.rel = 'stylesheet';
    styleLink.href = 'https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css';
    document.head.appendChild(styleLink);

    const map = new maplibregl.Map({
        container: container,
        // https://guides.data.gouv.fr/reutiliser-des-donnees/utiliser-les-api-geographiques/utiliser-les-tuiles-vectorielles
        style: 'https://openmaptiles.geo.data.gouv.fr/styles/osm-bright/style.json',
        center: pos,
        zoom,
    });

    // Credit: https://maplibre.org/maplibre-gl-js/docs/examples/geojson-line/
    map.on('load', () => {
        map.addControl(new maplibregl.NavigationControl(), 'top-left');

        map.addSource('locations', {
            type: 'geojson',
            data: {
                type: 'GeometryCollection',
                geometries
            }
        });

        map.addLayer({
            id: 'locations',
            'type': 'line',
            'source': 'locations',
            'layout': {
                'line-join': 'round',
                'line-cap': 'round',
            },
            'paint': {
                'line-color': '#F00',
                'line-width': 4,
            }
        });
    });
}

async function createLeafletMap(container, pos, zoom, geometries) {
    const L = (await import('leaflet')).default;

    const styleLink = document.createElement('link');
    styleLink.rel = 'stylesheet';
    styleLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    styleLink.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
    styleLink.crossOrigin = '';
    document.head.appendChild(styleLink);

    const map = L.map(container).setView([pos[1], pos[0]], zoom);

    const attribution = '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>';
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution }).addTo(map);
    L.tileLayer('https://openmaptiles.geo.data.gouv.fr/data/cadastre/{z}/{x}/{y}.pbf', { maxZoom: 19, attribution }).addTo(map);

    const geoJson = /** @type {GeoJSON.GeometryCollection} */ ({
        type: 'GeometryCollection',
        geometries,
    });

    L.geoJson(geoJson, { style: _feature => ({ color: 'red' }) }).addTo(map);
}
