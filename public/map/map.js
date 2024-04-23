const map = new maplibregl.Map({
    container: 'map', // container id
    style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json', // style URL
    center: [0, 0], // starting position [lng, lat]
    zoom: 1, // starting zoom
    maplibreLogo: false
});

let first_map_load = true;
const draft_filter = ["==", "is_draft", false];
const permanent_only_filter = ["==", "is_permanent", true];
const temporary_only_filter = ["==", "is_permanent", false];
const filters_as_a_dict = {};

function apply_filters() {
    const filters_as_a_list = ["all", ...Object.values(filters_as_a_dict)];
    map.setFilter("regulations-layer", filters_as_a_list);
    //console.log("filters_as_a_list : ", filters_as_a_list); // for debugging purpose
};

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
            .setHTML((e.features[0].properties.road_name || "''") + " [" + (e.features[0].properties.road_number || "") + "]" + "<h3>" + e.features[0].properties.identifier + "</h3>" + "<h4>" + e.features[0].properties.organization_name + "</h4>" + e.features[0].properties.description + "<br />" + " • arrêté permanent = " + e.features[0].properties.is_permanent + "<br /> • arrêté à l'état de brouillon = " + e.features[0].properties.is_draft)
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
    apply_filters();   
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

// UI filtering
// draft regulations filter
// credits : https://maplibre.org/maplibre-gl-js/docs/examples/filter-within-layer/
document.getElementById('display-drafts').addEventListener('change', (e) => {
    if (! e.target.checked) {  // do not display draft regulations
	filters_as_a_dict.draft_filter = draft_filter;
    } else {
	delete filters_as_a_dict.draft_filter
    };
    apply_filters();
});
// permanent and/or temporary regulations filter
// credits : https://maplibre.org/maplibre-gl-js/docs/examples/filter-within-layer/
document.getElementById('regulations-permanent-and-or-temporary').addEventListener('change', (e) => {
    switch (e.target.value) {
    case "permanent-only":
	filters_as_a_dict.permanent_and_or_temporary_filter = permanent_only_filter;
	break;
    case "temporary-only":
	filters_as_a_dict.permanent_and_or_temporary_filter = temporary_only_filter;
	break;
    default:
	delete filters_as_a_dict.permanent_and_or_temporary_filter;
    };
    apply_filters();
});
// organization names filter
// credits : https://maplibre.org/maplibre-gl-js/docs/examples/filter-markers-by-input/
// fitering reference : https://maplibre.org/maplibre-style-spec/expressions/
document.getElementById('organization-filter').addEventListener('input', (e) => {
    if (e.target.value.length) {
	const beginning_of_an_organization_name = e.target.value.trim().toLowerCase();
	filters_as_a_dict.organization_filter = [">", ["index-of", beginning_of_an_organization_name, ["downcase", ["get", "organization_name"]]], -1];
    } else {
	delete filters_as_a_dict.organization_filter;
    };
    apply_filters();
});
