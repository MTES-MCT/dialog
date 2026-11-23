// @ts-check

import { describe, it, expect, vi, afterEach } from 'vitest';
import {
    DIRECTION_CHEVRON_IMAGE_ID,
    addDirectionChevronImage,
    addDirectionChevronsLayer,
    buildDirectionChevronsLayer,
    buildOneWayFeatureCollection,
    drawChevron,
    isOneWay,
} from './direction_chevrons';

const LINE = { type: 'LineString', coordinates: [[2.0, 48.0], [2.001, 48.0]] };

/**
 * Contexte 2D minimal : enregistre les appels utiles aux assertions.
 */
function createContextMock() {
    return {
        beginPath: vi.fn(),
        moveTo: vi.fn(),
        lineTo: vi.fn(),
        stroke: vi.fn(),
        getImageData: vi.fn(() => ({ width: 32, height: 32, data: new Uint8ClampedArray(32 * 32 * 4) })),
        strokeStyle: '',
        lineWidth: 0,
        lineCap: '',
        lineJoin: '',
    };
}

/**
 * @param {ReturnType<typeof createContextMock> | null} context
 */
function stubDocument(context) {
    const canvas = { width: 0, height: 0, getContext: vi.fn(() => context) };
    vi.stubGlobal('document', { createElement: vi.fn(() => canvas) });

    return canvas;
}

function createMapMock({ hasImage = false } = {}) {
    return {
        hasImage: vi.fn(() => hasImage),
        addImage: vi.fn(),
        addLayer: vi.fn(),
    };
}

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('isOneWay', () => {
    it('is true for A_TO_B and B_TO_A only', () => {
        expect(isOneWay('A_TO_B')).toBe(true);
        expect(isOneWay('B_TO_A')).toBe(true);
        expect(isOneWay('BOTH')).toBe(false);
        expect(isOneWay('')).toBe(false);
        expect(isOneWay(null)).toBe(false);
        expect(isOneWay(undefined)).toBe(false);
    });
});

describe('buildOneWayFeatureCollection', () => {
    it('keeps one-way geometries untouched and carries their direction', () => {
        const other = { type: 'MultiLineString', coordinates: [[[3, 49], [3.001, 49]]] };

        expect(buildOneWayFeatureCollection([
            { geometry: LINE, direction: 'A_TO_B' },
            { geometry: other, direction: 'B_TO_A' },
        ])).toEqual({
            type: 'FeatureCollection',
            features: [
                { type: 'Feature', properties: { direction: 'A_TO_B' }, geometry: LINE },
                { type: 'Feature', properties: { direction: 'B_TO_A' }, geometry: other },
            ],
        });
    });

    it('drops two-way locations, missing geometries and missing directions', () => {
        expect(buildOneWayFeatureCollection([
            { geometry: LINE, direction: 'BOTH' },
            { geometry: null, direction: 'A_TO_B' },
            { geometry: LINE, direction: undefined },
        ]).features).toEqual([]);
        expect(buildOneWayFeatureCollection(/** @type {any} */ (null)).features).toEqual([]);
    });
});

describe('buildDirectionChevronsLayer', () => {
    it('repeats the chevron along one-way lines and flips it for B_TO_A', () => {
        const layer = buildDirectionChevronsLayer({ id: 'chevrons', sourceId: 'source' });

        expect(layer.id).toBe('chevrons');
        expect(layer.type).toBe('symbol');
        expect(layer.source).toBe('source');
        expect(layer.filter).toEqual(['match', ['get', 'direction'], ['A_TO_B', 'B_TO_A'], true, false]);
        expect(layer.layout?.['symbol-placement']).toBe('line');
        expect(layer.layout?.['icon-image']).toBe(DIRECTION_CHEVRON_IMAGE_ID);
        expect(layer.layout?.['icon-rotate']).toEqual(['case', ['==', ['get', 'direction'], 'B_TO_A'], 180, 0]);
        expect(layer.layout?.['icon-allow-overlap']).toBe(true);
        expect(layer).not.toHaveProperty('source-layer');
        expect(layer).not.toHaveProperty('minzoom');
        expect(layer).not.toHaveProperty('paint');
    });

    it('supports vector tile sources, a minimum zoom and an opacity', () => {
        const layer = buildDirectionChevronsLayer({
            id: 'chevrons',
            sourceId: 'source',
            sourceLayer: 'restrictions',
            minzoom: 13,
            opacity: 0.5,
        });

        expect(layer['source-layer']).toBe('restrictions');
        expect(layer.minzoom).toBe(13);
        expect(layer.paint).toEqual({ 'icon-opacity': 0.5 });
    });
});

describe('drawChevron', () => {
    it('strokes a white outline then a blue chevron pointing to the right', () => {
        const context = createContextMock();
        /** @type {string[]} */
        const strokes = [];
        context.stroke.mockImplementation(() => strokes.push(context.strokeStyle));

        drawChevron(/** @type {any} */ (context), 16);

        expect(strokes).toEqual(['#FFFFFF', '#000091']);
        // La pointe (sommet du milieu) est le point le plus à droite, à mi-hauteur
        const [tipX, tipY] = context.lineTo.mock.calls[0];
        const [startX] = context.moveTo.mock.calls[0];
        expect(tipX).toBeGreaterThan(startX);
        expect(tipY).toBe(8);
    });

    it('scales with the drawing size', () => {
        const context = createContextMock();

        drawChevron(/** @type {any} */ (context), 32);

        expect(context.lineTo.mock.calls[0]).toEqual([21, 16]);
    });
});

describe('addDirectionChevronImage', () => {
    it('registers the chevron image once, drawn at twice the CSS size', () => {
        const context = createContextMock();
        const canvas = stubDocument(context);
        const map = createMapMock();

        expect(addDirectionChevronImage(/** @type {any} */ (map))).toBe(true);

        expect(canvas.width).toBe(32);
        expect(canvas.height).toBe(32);
        expect(map.addImage).toHaveBeenCalledTimes(1);
        expect(map.addImage.mock.calls[0][0]).toBe(DIRECTION_CHEVRON_IMAGE_ID);
        expect(map.addImage.mock.calls[0][2]).toEqual({ pixelRatio: 2 });
    });

    it('does nothing when the image is already registered', () => {
        stubDocument(createContextMock());
        const map = createMapMock({ hasImage: true });

        expect(addDirectionChevronImage(/** @type {any} */ (map))).toBe(true);
        expect(map.addImage).not.toHaveBeenCalled();
    });

    it('returns false without a 2D canvas context', () => {
        stubDocument(null);
        const map = createMapMock();

        expect(addDirectionChevronImage(/** @type {any} */ (map))).toBe(false);
        expect(map.addImage).not.toHaveBeenCalled();
    });
});

describe('addDirectionChevronsLayer', () => {
    it('adds the image then the layer and returns the layer id', () => {
        stubDocument(createContextMock());
        const map = createMapMock();

        expect(addDirectionChevronsLayer(/** @type {any} */ (map), { id: 'chevrons', sourceId: 'source' })).toBe('chevrons');
        expect(map.addImage).toHaveBeenCalledTimes(1);
        expect(map.addLayer).toHaveBeenCalledTimes(1);
        expect(map.addLayer.mock.calls[0][0].id).toBe('chevrons');
    });

    it('adds no layer when the image cannot be drawn', () => {
        stubDocument(null);
        const map = createMapMock();

        expect(addDirectionChevronsLayer(/** @type {any} */ (map), { id: 'chevrons', sourceId: 'source' })).toBeNull();
        expect(map.addLayer).not.toHaveBeenCalled();
    });
});
