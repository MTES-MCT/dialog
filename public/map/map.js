const map = new maplibregl.Map({
    container: 'map', // container id
    style: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json', // style URL
    center: [0, 0], // starting position [lng, lat]
    zoom: 1, // starting zoom
    maplibreLogo: false
});

map.on('load', () => {
    // sources : 
    map.addSource(
	'regulations-source',
	{
            type: 'vector',
            url: 'http://localhost:3000/location'
	}
    );
    // layers (i.e. styles) : 
    map.addLayer({
        'id': 'regulations-layer',
        'type': 'line',
        'source': 'regulations-source',
        'source-layer': 'location',
        'layout': {
            'line-join': 'round',
            'line-cap': 'round'
        },
        'paint': {
            'line-color': '#ff69b4',
            'line-width': 10
        }
    });
    // popup when clicking on a feature of the regulation layer
    map.on('click', 'regulations-layer', (e) => {
        new maplibregl.Popup()
            .setLngLat(e.lngLat)
            .setHTML((e.features[0].properties.road_name || "''") + " [" + (e.features[0].properties.road_number || "") + "]")
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

map.on('idle', () => {
    // automatically pan and zoom on the regulation layer
    // "map.getSource("regulations-source").bounds" is not defined inside "map.on('load', () => {", before the map is fully loaded
    map.fitBounds(
	map.getSource("regulations-source").bounds,
	{
	    padding: 100,
	    animate: true,
	    duration: 500  // duration in ms
	}
    );
});
