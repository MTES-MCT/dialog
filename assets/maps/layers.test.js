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
        const data = /** @type {import('geojson').FeatureCollection} */ ({ type: 'FeatureCollection', features: [] });

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 'my-src',
            layerId: 'my-layer',
            measureType: 'unknownMeasure',
            data,
        });

        expect(map.addSource).toHaveBeenCalledWith('my-src', { type: 'geojson', data });

        // Default style has no border/background → a single line layer.
        expect(map.addLayer).toHaveBeenCalledTimes(1);
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
            measureType: 'unknownMeasure',
        });

        const sourceConfig = map.addSource.mock.calls[0][1];
        expect(sourceConfig.data).toEqual({ type: 'FeatureCollection', features: [] });
    });

    it('wraps a bare geometry into a FeatureCollection on the source', () => {
        const map = createMapMock();
        const point = /** @type {import('geojson').Point} */ ({ type: 'Point', coordinates: [2.35, 48.85] });

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'unknownMeasure',
            data: point,
        });

        expect(map.addSource.mock.calls[0][1].data).toEqual({
            type: 'FeatureCollection',
            features: [{ type: 'Feature', properties: {}, geometry: point }],
        });
    });

    it('does not filter the line layer when no pointLayerId is given', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'unknownMeasure',
        });

        expect(map.addLayer).toHaveBeenCalledTimes(1);
        expect(map.addLayer.mock.calls[0][0].filter).toBeUndefined();
    });

    it('adds a point circle layer and filters the line layers when pointLayerId is given', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            pointLayerId: 'p',
            measureType: 'unknownMeasure',
        });

        expect(map.addLayer).toHaveBeenCalledTimes(2);

        const lineLayer = map.addLayer.mock.calls[0][0];
        expect(lineLayer.id).toBe('l');
        expect(lineLayer.filter).toEqual(['in', '$type', 'LineString', 'Polygon']);

        const pointLayer = map.addLayer.mock.calls[1][0];
        expect(pointLayer.id).toBe('p');
        expect(pointLayer.type).toBe('circle');
        expect(pointLayer.source).toBe('s');
        expect(pointLayer.filter).toEqual(['==', '$type', 'Point']);
        expect(pointLayer.paint['circle-color']).toBe('#000000');
    });

    it('applies the measure type style (noEntry → red dashed line over a white background line)', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'noEntry',
        });

        // noEntry has a backgroundColor → 2 layers: background underlay + main dashed line.
        expect(map.addLayer).toHaveBeenCalledTimes(2);

        const background = map.addLayer.mock.calls[0][0];
        expect(background.id).toBe('l-background');
        expect(background.paint['line-color']).toBe('#FFFFFF');

        const main = map.addLayer.mock.calls[1][0];
        expect(main.id).toBe('l');
        expect(main.paint['line-color']).toBe('#CE0500');
        expect(main.paint['line-dasharray']).toEqual([1, 1]);
        // Width is now a zoom-based step expression coming from
        // buildLineWidthExpression — assert its shape rather than a scalar.
        expect(main.paint['line-width']).toEqual(['step', ['zoom'], 4, 15, 8, 18, 16]);
    });

    it('applies the border underlay (parkingProhibited → white line with orange border)', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'parkingProhibited',
        });

        // parkingProhibited has a borderColor → 2 layers: border underlay + main white line.
        expect(map.addLayer).toHaveBeenCalledTimes(2);

        const border = map.addLayer.mock.calls[0][0];
        expect(border.id).toBe('l-border');
        expect(border.paint['line-color']).toBe('#FA7A35');

        const main = map.addLayer.mock.calls[1][0];
        expect(main.id).toBe('l');
        expect(main.paint['line-color']).toBe('#FFFFFF');
    });

    it('uses the border color as the point color when available (parkingProhibited)', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            pointLayerId: 'p',
            measureType: 'parkingProhibited',
        });

        const pointLayer = map.addLayer.mock.calls.at(-1)[0];
        expect(pointLayer.id).toBe('p');
        expect(pointLayer.paint['circle-color']).toBe('#FA7A35');
    });

    it('falls back to the default style for unknown measure types', () => {
        const map = createMapMock();

        addMeasureLineLayer(/** @type {any} */ (map), {
            sourceId: 's',
            layerId: 'l',
            measureType: 'unknownMeasure',
        });

        expect(map.addLayer).toHaveBeenCalledTimes(1);
        const layer = map.addLayer.mock.calls[0][0];
        expect(layer.paint['line-color']).toBe('#000000');
        expect(layer.paint['line-width']).toEqual(['step', ['zoom'], 4, 15, 8, 18, 16]);
        expect(layer.paint['line-dasharray']).toEqual([1, 0]);
    });
});
