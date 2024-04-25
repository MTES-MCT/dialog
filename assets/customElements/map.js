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
        style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json',
        center: pos,
        zoom,
    });
    
    // credit: https://maplibre.org/maplibre-gl-js/docs/examples/geojson-line/
    map.on('load', () => {
        map.addControl(new maplibregl.NavigationControl(), 'top-left');
	
        map.addSource('regulations-source', {
            type: 'geojson',
            data: {
                type: 'GeometryCollection',
                geometries
            }
        });
	
        map.addLayer({
            id: 'regulations-layer',
            'type': 'line',
            'source': 'regulations-source',
            'layout': {
                'line-join': 'round',
                'line-cap': 'round',
            },
            'paint': {
                'line-color': '#F00',
                'line-width': 4,
            },
	    "toponyme numéro de route - départementale" // insert this layer below the main label layers like road labels
        });
    });
}
