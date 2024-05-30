// @ts-check


export default class extends HTMLElement {
    connectedCallback() {
        const height = this.dataset.height || '300px';
        /** @type {[number, number]} */
        const pos = JSON.parse(this.dataset.pos || '');
        const zoom = +(this.dataset.zoom || 13);


	const geojson = JSON.parse(this.dataset.geojson || '[]'); // example : "[{"type":"Feature","geometry":{"type":"LineString","coordinates":[[-1.728876775,48.37839112],[-1.72880412,48.378224342], … ]},"properties":{"is_permanent":true,"is_draft":false,"measure_type":"noEntry","location_uuid":"018f3ede-1930-7556-8b32-449ca7f026a8"}},{"type":"Feature","geometry":{"type":"MultiLineString","coordinates":[[[-1.6976246,48.21139647],[-1.697490153,48.211703784]],[[-1.697490153,48.211703784],[-1.697420744,48.211848064]], … ]},"properties":{"is_permanent":false,"is_draft":true,"measure_type":"speedLimitation","location_uuid":"018f7c41-846c-72fc-a2a6-477a3a171c0a"}}, … ]]"
	const locationsAsGeoJSONOutputId = this.dataset['locationsAsGeojsonOutputId'] || 'locations_as_geojson_output';
	const mapFilterTurboFrameId = this.dataset['mapFilterTurboFrameId'] || 'map_filter_turbo_frame';
	const locationPath = this.dataset['locationPath'];

        const container = document.createElement('div');
        container.style.height = height;
        this.appendChild(container);

        this.mapOnPromise = createMapLibreMap(container, pos, zoom, geojson, locationsAsGeoJSONOutputId, mapFilterTurboFrameId, locationPath);
	// use this to debug in the JS console of your browser :
	//my_map = await document.getElementsByTagName("dialog-map")[0].mapOnPromise
    }
}

async function createMapLibreMap(container, pos, zoom, geojson, locationsAsGeoJSONOutputId, mapFilterTurboFrameId, locationPath) {
    // Lazy load to only transfer MapLibre JS when loading the map : 
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
	hash: "mapZoomAndPosition",
    });

    // credit: https://maplibre.org/maplibre-gl-js/docs/examples/geojson-line/
    map.on('load', () => {
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
