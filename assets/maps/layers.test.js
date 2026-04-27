// @ts-check

import { describe, it, expect, vi } from 'vitest';
import { addHouseNumbersLayer, addMeasureLineLayer } from './layers';

/**
 * Minimal MapLibre map mock — only the methods exercised by the helpers.
 */
function createMapMock() {
    return {
        addSource: vi.fn(),
        addLayer: vi.fn(),
    };
}

describe('addHouseNumbersLayer', () => {
    it('adds a single symbol layer named "house-numbers" on the plan_ign source', () => {
        const map = createMapMock();

        addHouseNumbersLayer(/** @type {any} */ (map));

        expect(map.addLayer).toHaveBeenCalledTimes(1);
        const layer = map.addLayer.mock.calls[0][0];
        expect(layer.id).toBe('house-numbers');
        expect(layer.type).toBe('symbol');
        expect(layer.source).toBe('plan_ign');
        expect(layer['source-layer']).toBe('toponyme_parcellaire_adresse_ponc');
        expect(layer.minzoom).toBe(15);
        expect(layer.filter).toEqual(['==', 'txt_typo', 'ADRESSE']);
    });

    it('does not add a source (it relies on the existing plan_ign source)', () => {
        const map = createMapMock();

        addHouseNumbersLayer(/** @type {any} */ (map));

        expect(map.addSource).not.toHaveBeenCalled();
    });
});

describe('addMeasureLineLayer', () => {
    it('adds a GeoJSON source and a line layer with the given ids', () => {
        const map = createMapMock();
        const data = { type: 'FeatureCollection', features: [] };

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 'my-src',
            layerId: 'my-layer',
            measureType: 'noEntry',
            data,
        });

        expect(map.addSource).toHaveBeenCalledWith('my-src', { type: 'geojson', data });

        const layer = map.addLayer.mock.calls[0][0];
        expect(layer.id).toBe('my-layer');
        expect(layer.type).toBe('line');
        expect(layer.source).toBe('my-src');
    });

    it('falls back to an empty FeatureCollection when no data is given', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'noEntry',
        });

        const sourceConfig = map.addSource.mock.calls[0][1];
        expect(sourceConfig.data).toEqual({ type: 'FeatureCollection', features: [] });
    });

    it('applies the measure type style (noEntry → red, solid)', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'noEntry',
        });

        const layer = map.addLayer.mock.calls[0][0];
        expect(layer.paint['line-color']).toBe('#CE0500');
        expect(layer.paint['line-width']).toBe(3);
        expect(layer.paint['line-dasharray']).toEqual([1, 0]);
    });

    it('falls back to the default style for unknown measure types', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'unknownMeasure',
        });

        const layer = map.addLayer.mock.calls[0][0];
        expect(layer.paint['line-color']).toBe('#000000');
        expect(layer.paint['line-width']).toBe(3);
        expect(layer.paint['line-dasharray']).toEqual([1, 0]);
    });
});
