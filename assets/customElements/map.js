// @ts-check


export default class extends HTMLElement {
    connectedCallback() {
        const height = this.dataset.height || '300px';
        /** @type {[number, number]} */
        const pos = JSON.parse(this.dataset.pos || '');
        const zoom = +(this.dataset.zoom || 13);
        const geojson = JSON.parse(this.dataset.geojson || '[]');
	const bbox = JSON.parse(this.dataset.bbox || '');
	
	const regulations_as_geojson_output_id = this.dataset['regulations-as-geojson-output-id'] || 'regulations_as_geojson_output';
	const regulations_as_geojson_turbo_frame_id = this.dataset['regulations-as-geojson-turbo-frame-id'] || 'regulations_as_geojson_turbo_frame';
	
        const container = document.createElement('div');
        container.style.height = height;
        this.appendChild(container);
	
        this.map_on_promise = createMapLibreMap(container, pos, zoom, geojson, bbox, regulations_as_geojson_output_id, regulations_as_geojson_turbo_frame_id);
	// use this to debug in the JS console of your browser :
	//my_map = await document.getElementsByTagName("dialog-map")[0].map_on_promise
    }
}

async function createMapLibreMap(container, pos, zoom, geojson, bbox, regulations_as_geojson_output_id, regulations_as_geojson_turbo_frame_id) {
    const maplibregl = (await import('maplibre-gl')).default;
    
    const styleLink = document.createElement('link');
    styleLink.rel = 'stylesheet';
    styleLink.href = 'https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css';
    document.head.appendChild(styleLink);
    
    // Define the map syle (OpenStreetMap raster tiles)
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
    };
    
    const map = new maplibregl.Map({
        container: container,
        style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json',
	//style: osm_style, 
        center: pos,
        zoom,
    });
    let first_map_load = true;
    
    // credit: https://maplibre.org/maplibre-gl-js/docs/examples/geojson-line/
    map.on('load', () => {
	// automatically pan and zoom on the bbox, queried from the database (there is no .bounds for GeoJSON MapLibre source)
	// we must do that only one time
	if (first_map_load) {
	    map.fitBounds(
		bbox,
		{
		    padding: 100,
		    animate: true,
		    duration: 500  // duration in ms
		}
	    );
	    first_map_load = false;
	};
        map.addControl(new maplibregl.NavigationControl(), 'top-left');
	const regulation_source_as_json = {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: geojson
            }
        };
        map.addSource('regulations-source', regulation_source_as_json);
        map.addLayer(
	    {
		'id': 'regulations-layer',
		'type': 'line',
		'source': 'regulations-source',
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
	// popup when clicking on a feature of the regulation layer
	map.on('click', 'regulations-layer', (e) => {
            new maplibregl.Popup()
		.setLngLat(e.lngLat)
		.setHTML((e.features[0].properties.road_name || "''") + " [" + (e.features[0].properties.road_number || "") + "]" + "<h3>" + e.features[0].properties.identifier + "</h3>" + "<h4>" + e.features[0].properties.organization_name + "</h4>" + e.features[0].properties.description + "<br />" + " • arrêté permanent = " + e.features[0].properties.is_permanent + "<br /> • arrêté à l'état de brouillon = " + e.features[0].properties.is_draft)
		.addTo(map);
	});
	// change the cursor when the mouse is over the regulations layer
	map.on('mouseenter', 'regulations-layer', () => {
            map.getCanvas().style.cursor = 'pointer';
	});
	map.on('mouseleave', 'regulations-layer', () => {
            map.getCanvas().style.cursor = '';
	});
    });
    
    // Mutation API Observer
    function mutationCallback(mutationList) {
	for (const mutation of mutationList) {
	    if (mutation.type === "childList") {
		if (mutation.addedNodes && mutation.addedNodes.length >= 1) {
		    const ouput_element = mutation.target.querySelector("#" + regulations_as_geojson_output_id);
		    if (ouput_element) {
			const new_geojson = JSON.parse(ouput_element.innerText || []);
			// credits to https://maplibre.org/maplibre-gl-js/docs/examples/live-update-feature/
			map.getSource('regulations-source').setData({
			    type: 'FeatureCollection',
			    features: new_geojson
			});
		    }
		}
	    }
	}
    };
    const targetNode = document.getElementById(regulations_as_geojson_turbo_frame_id); // observe our <turbo-frame>
    if (targetNode) {
	const config = { attributes: false, childList: true, subtree: true, characterData: false };
	const observer = new MutationObserver(mutationCallback);
	observer.observe(targetNode, config);
    }
    
    return map;
}
