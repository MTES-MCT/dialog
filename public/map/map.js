const map = new maplibregl.Map({
    container: 'map', // container id
    style: 'https://demotiles.maplibre.org/style.json', // style URL
    center: [0, 0], // starting position [lng, lat]
    zoom: 1, // starting zoom
    maplibreLogo: true
});

map.on('load', () => {
    map.addSource('regulations-source', {
        type: 'vector',
        url:
        'http://localhost:3000/location'
    });
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
    map.on('click', 'regulations-layer', (e) => {
        new maplibregl.Popup()
            .setLngLat(e.lngLat)
            .setHTML(e.features[0].properties.road_name + " [" + e.features[0].properties.road_number + "]")
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

