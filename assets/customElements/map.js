// @ts-check


export default class extends HTMLElement {
    connectedCallback() {
        const height = this.dataset.height || '300px';
        /** @type {[number, number]} */
        const pos = JSON.parse(this.dataset.pos || '');
        const zoom = +(this.dataset.zoom || 13);
        const geojson = JSON.parse(this.dataset.geojson || '[]');
	const bbox = JSON.parse(this.dataset.bbox || '');
	
	const locationsAsGeoJSONOutputId = this.dataset['locations-as-geojson-output-id'] || 'locations_as_geojson_output';
	const mapFilterTurboFrameId = this.dataset['map-filter-turbo-frame-id'] || 'map_filter_turbo_frame';
	const locationPath = this.dataset['location-path'];
	
        const container = document.createElement('div');
        container.style.height = height;
        this.appendChild(container);
	
        this.mapOnPromise = createMapLibreMap(container, pos, zoom, geojson, bbox, locationsAsGeoJSONOutputId, mapFilterTurboFrameId, locationPath);
	// use this to debug in the JS console of your browser :
	//my_map = await document.getElementsByTagName("dialog-map")[0].mapOnPromise
    }
}

async function createMapLibreMap(container, pos, zoom, geojson, bbox, locationsAsGeoJSONOutputId, mapFilterTurboFrameId, locationPath) {
    const maplibregl = (await import('maplibre-gl')).default;
    
    const styleLink = document.createElement('link');
    styleLink.rel = 'stylesheet';
    styleLink.href = 'https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css';
    document.head.appendChild(styleLink);
    
    // Define the map syle (OpenStreetMap raster tiles)
    /*
    const osm_style = {
	"version": 8,
	"sources": {
	    "osm": {
		"type": "raster",
		"tiles": ["https://a.tile.openstreetmap.org/{z}/{x}/{y}.png"],
		"tileSize": 256,
		"attribution": "&copy; OpenStreetMap Contributors",
		"maxzoom": 19
	    }
	}, 
	"layers": [
	    {
		"id": "osm",
		"type": "raster",
		"source": "osm" // This must match the source key above
	    }
	]
    };*/
    
    const map = new maplibregl.Map({
        container: container,
        style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json',
	//style: osm_style, 
        center: pos,
        zoom,
    });
    let firstMapLoad = true;
    
    // credit: https://maplibre.org/maplibre-gl-js/docs/examples/geojson-line/
    map.on('load', () => {
	// automatically pan and zoom on the bbox, queried from the database (there is no .bounds for GeoJSON MapLibre source)
	// we must do that only one time
	if (firstMapLoad) {
	    map.fitBounds(
		bbox,
		{
		    padding: 100,
		    animate: true,
		    duration: 500  // duration in ms
		}
	    );
	    firstMapLoad = false;
	};
        map.addControl(new maplibregl.NavigationControl(), 'bottom-right');
	const locationSourceAsGeoJSON = {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: geojson
            },
	    tolerance: 0.0, // we want to display the data at very low zoom level -> tolerance must be very low
        };
        map.addSource('locations-source', locationSourceAsGeoJSON);
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
                    'line-color': '#ff69b4',
                    'line-width': 10,
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
            const locationPopUp = new maplibregl.Popup({'className' : 'fr-hidden'})
		  .setLngLat(e.lngLat)
		  .setDOMContent(locationTurboFrame)
		  .addTo(map);
	    // display the popup when the turbo frame is loaded (otherwise MapLibre GL JS will display an empty popup for a few seconds)
	    locationTurboFrame.addEventListener('turbo:frame-load', () => {
		locationPopUp.removeClassName('fr-hidden');
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
	    if (mutation.type === "childList") {
		if (mutation.addedNodes && mutation.addedNodes.length >= 1) {
		    const ouputElement = mutation.target.querySelector("#" + locationsAsGeoJSONOutputId);
		    if (ouputElement) {
			const new_geojson = JSON.parse(ouputElement.innerText || []);
			// credits to https://maplibre.org/maplibre-gl-js/docs/examples/live-update-feature/
			map.getSource('locations-source').setData({
			    type: 'FeatureCollection',
			    features: new_geojson
			});
		    }
		}
	    }
	}
    };
    const targetNode = document.getElementById(mapFilterTurboFrameId); // observe our <turbo-frame>
    if (targetNode) {
	const config = { attributes: false, childList: true, subtree: true, characterData: false };
	const observer = new MutationObserver(mutationCallback);
	observer.observe(targetNode, config);
    }
    
    return map;
}
