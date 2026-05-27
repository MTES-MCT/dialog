// @ts-check

import {
    buildMeasureLineLayers,
    buildMeasurePointPaint,
    getMeasureTypeStyle,
} from './measure_type_styles';
import { toFeatureCollection } from './geojson';

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
        maxzoom: 24,
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
            'text-ignore-placement': false,
        },
        paint: {
            'text-color': '#695744',
            'text-halo-width': 1,
            'text-halo-color': '#FFFFFF',
        },
    });
}

/**
 * Adds a GeoJSON source and the line layer(s) styled according to a measure
 * type. All styling (color, dasharray, border, background, line-cap/join,
 * zoom-based width) comes from `MEASURE_TYPE_STYLES` in
 * `assets/maps/measure_type_styles.js` so the preview stays in sync with the
 * main map.
 *
 * Several MapLibre layers may be added (border + background + main line) when
 * the measure type style requires it. The `layerId` is used for the main line
 * layer; additional layers use `<layerId>-background` / `<layerId>-border`.
 *
 * @param {import('maplibre-gl').Map} map
 * @param {Object} options
 * @param {string} options.sourceId
 * @param {string} options.layerId
 * @param {string} options.measureType
 * @param {import('geojson').GeoJSON} [options.data] - Initial GeoJSON data (defaults to empty FeatureCollection).
 * @param {string} [options.pointLayerId] - When provided, a circle layer is added
 *   for `Point` features and the line layer is restricted to `LineString`/`Polygon`.
 */
export function addMeasureLineLayer(map, { sourceId, layerId, measureType, data, pointLayerId }) {
    const style = getMeasureTypeStyle(measureType);

    map.addSource(sourceId, {
        type: 'geojson',
        data: data !== undefined ? toFeatureCollection(data) : { type: 'FeatureCollection', features: [] },
    });

    const lineLayers = buildMeasureLineLayers(measureType, style, {
        sourceId,
        layerIdPrefix: layerId,
        // The source is a GeoJSON whose features all belong to the same
        // measure (no `measure_type` property to filter on). When we also
        // render points, restrict the line layers to non-Point geometries.
        filter: pointLayerId
            ? /** @type {import('maplibre-gl').FilterSpecification} */ (['in', '$type', 'LineString', 'Polygon'])
            : null,
    });

    // The helper names layers `${layerIdPrefix}-${measureType}[-border|-background]`.
    // For GeoJSON consumers we use the caller-supplied `layerId` as the public
    // id of the main line layer (and `${layerId}-border` / `${layerId}-background`
    // for the optional underlays), so the existing contract is preserved.
    const generatedPrefix = `${layerId}-${measureType}`;
    for (const layer of lineLayers) {
        layer.id = layerId + layer.id.slice(generatedPrefix.length);
        map.addLayer(layer);
    }

    if (pointLayerId) {
        map.addLayer({
            id: pointLayerId,
            type: 'circle',
            source: sourceId,
            filter: ['==', '$type', 'Point'],
            paint: buildMeasurePointPaint(style),
        });
    }
}
