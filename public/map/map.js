const map = new maplibregl.Map({
    container: 'map', // container id
    style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json', // style URL
    center: [0, 0], // starting position [lng, lat]
    zoom: 1, // starting zoom
    maplibreLogo: false
});

let first_map_load = true;

map.on('load', () => {
    // sources : 
    map.addSource(
	'regulations-source',
	{
            type: 'vector',
            url: 'http://localhost:3000/general_map_view'
	}
    );
    map.addSource(
	'regulations-aggregated-source',
	{
            type: 'vector',
            url: 'http://localhost:3000/general_map_view_aggregated'
	}
    );
    // layers (i.e. styles) :
    map.addLayer(
	{
            'id': 'regulations-layer',
            'type': 'line',
            'source': 'regulations-source',
            'source-layer': 'general_map_view',
            'layout': {
		'line-join': 'round',
		'line-cap': 'round'
            },
            'paint': {
		'line-color': '#ff69b4',
		'line-width': 5
            }
	},
	"toponyme numéro de route - départementale" // insert this layer below the main label layers like road labels
    );
    // this layer 'regulations-aggregated-layer' is to display the scattered geometries of a regulation : when clicking on a single geometry, it simply shows the other geometries associated to the same regulation, if any
    map.addLayer(
	{
            'id': 'regulations-aggregated-layer',
            'type': 'line',
            'source': 'regulations-aggregated-source',
            'source-layer': 'general_map_view_aggregated',
            'layout': {
		'line-join': 'round',
		'line-cap': 'round',
		'visibility': 'none'  // hide this layer by default
            },
            'paint': {
		'line-color': '#61B3F0',
		'line-width': 10
            }
	},
	"regulations-layer"
    );
    // popup when clicking on a feature of the regulation layer
    map.on('click', 'regulations-layer', (e) => {
        new maplibregl.Popup()
            .setLngLat(e.lngLat)
            .setHTML((e.features[0].properties.road_name || "''") + " [" + (e.features[0].properties.road_number || "") + "]" + "<h3>" + e.features[0].properties.identifier + "</h3>" + e.features[0].properties.description + "<br />" + " • arrêté permanent = " + e.features[0].properties.is_permanent + "<br /> • arrêté à l'état de brouillon = " + e.features[0].properties.is_draft)
            .addTo(map);
    });    
    // change the cursor when the mouse is over the regulations layer
    // also, highlight the geom(s) (one or many) associated to one regulation when the mouse is over one of its geometry
    map.on('mouseenter', 'regulations-layer', (e) => {
        map.getCanvas().style.cursor = 'pointer';
	let current_regulation_id = e.features[0].properties.regulation_order_id;
	map.setFilter("regulations-aggregated-layer", ["==", "regulation_order_id", current_regulation_id]);
	map.setLayoutProperty('regulations-aggregated-layer', 'visibility', 'visible');
    });
    map.on('mouseleave', 'regulations-layer', (e) => {
        map.getCanvas().style.cursor = '';
	map.setFilter("regulations-aggregated-layer", null); // delete the filter
	map.setLayoutProperty('regulations-aggregated-layer', 'visibility', 'none');
    });
    // filtering :
    // demo : this filter will display all regulations (means : (is_draft) OR (NOT is_draft) -> always TRUE)
    //map.setFilter("regulations-layer", ["any", ["==", "is_draft", true], ["==", "is_draft", false]]);    
});

map.on('idle', () => {
    // automatically pan and zoom on the regulation layer
    // "map.getSource("regulations-source").bounds" is not defined inside "map.on('load', () => {", i.e. before the map is fully loaded
    // we must do that only one time
    if (first_map_load) {
	map.fitBounds(
	    map.getSource("regulations-source").bounds,
	    {
		padding: 100,
		animate: true,
		duration: 500  // duration in ms
	    }
	);
	first_map_load = false;
    };
});
