// @ts-check

import { getMeasureTypeStyle } from '../measure_type_styles';

/**
 * Adds the IGN house numbers symbol layer to a MapLibre map.
 * The map must already use a style that exposes the `plan_ign` source.
 *
 * @param {import('maplibre-gl').Map} map
 */
export function addHouseNumbersLayer(map) {
    map.addLayer({
        id: 'house-numbers',
        type: 'symbol',
        source: 'plan_ign',
        'source-layer': 'toponyme_parcellaire_adresse_ponc',
        minzoom: 15,
        filter: ['==', 'txt_typo', 'ADRESSE'],
        layout: {
            'symbol-placement': 'point',
            'text-field': ['concat', ['get', 'numero'], ['get', 'indice_de_repetition']],
            'text-size': [
                'interpolate', ['linear'], ['zoom'],
                15, 9,
                17, 11,
                18, 13,
            ],
            'text-anchor': 'center',
            'text-font': ['Noto Sans Regular'],
            'text-allow-overlap': false,
        },
        paint: {
            'text-color': '#695744',
            'text-halo-width': 1,
            'text-halo-color': '#FFFFFF',
        },
    });
}

/**
 * Adds a GeoJSON source and a line layer styled according to a measure type.
 *
 * @param {import('maplibre-gl').Map} map
 * @param {Object} options
 * @param {string} options.sourceId
 * @param {string} options.layerId
 * @param {string} options.measureType
 * @param {import('geojson').GeoJSON} [options.data] - Initial GeoJSON data (defaults to empty FeatureCollection).
 */
export function addMeasureLineLayer(map, { sourceId, layerId, measureType, data }) {
    const style = getMeasureTypeStyle(measureType);

    map.addSource(sourceId, {
        type: 'geojson',
        data: data ?? { type: 'FeatureCollection', features: [] },
    });

    map.addLayer({
        id: layerId,
        type: 'line',
        source: sourceId,
        paint: {
            'line-color': style.color,
            'line-width': style.lineWidth,
            'line-dasharray': style.dasharray,
        },
    });
}
