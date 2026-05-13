// @ts-check

import { describe, it, expect } from 'vitest';
import {
    MEASURE_TYPE_STYLES,
    DEFAULT_MEASURE_STYLE,
    DEFAULT_LINE_JOIN,
    DEFAULT_LINE_CAP,
    getMeasureTypeStyle,
    buildMeasureLineLayout,
    buildMeasureBackgroundLineLayout,
    buildLineWidthExpression,
    buildMeasureLineLayers,
} from './measure_type_styles';

describe('getMeasureTypeStyle', () => {
    it('returns the style associated with a known measure type', () => {
        expect(getMeasureTypeStyle('noEntry')).toBe(MEASURE_TYPE_STYLES.noEntry);
        expect(getMeasureTypeStyle('speedLimitation')).toBe(MEASURE_TYPE_STYLES.speedLimitation);
        expect(getMeasureTypeStyle('parkingProhibited')).toBe(MEASURE_TYPE_STYLES.parkingProhibited);
        expect(getMeasureTypeStyle('alternateRoad')).toBe(MEASURE_TYPE_STYLES.alternateRoad);
        expect(getMeasureTypeStyle('noOvertaking')).toBe(MEASURE_TYPE_STYLES.noOvertaking);
    });

    it('returns the default style for an unknown measure type', () => {
        expect(getMeasureTypeStyle('unknown')).toBe(DEFAULT_MEASURE_STYLE);
        expect(getMeasureTypeStyle('')).toBe(DEFAULT_MEASURE_STYLE);
    });
});

describe('buildMeasureLineLayout', () => {
    it('falls back to the default line-join and line-cap when not overridden', () => {
        expect(buildMeasureLineLayout(MEASURE_TYPE_STYLES.speedLimitation)).toEqual({
            'line-join': DEFAULT_LINE_JOIN,
            'line-cap': DEFAULT_LINE_CAP,
        });
    });

    it("uses the style's overrides when provided", () => {
        expect(buildMeasureLineLayout(MEASURE_TYPE_STYLES.noEntry)).toEqual({
            'line-join': DEFAULT_LINE_JOIN,
            'line-cap': 'butt',
        });
    });
});

describe('buildLineWidthExpression', () => {
    it('builds a step expression using default base width and zoom thresholds', () => {
        expect(buildLineWidthExpression()).toEqual(['step', ['zoom'], 4, 15, 8, 18, 16]);
    });

    it('scales widths based on the provided base width', () => {
        expect(buildLineWidthExpression(2)).toEqual(['step', ['zoom'], 2, 15, 4, 18, 8]);
    });

    it('uses the provided zoom thresholds', () => {
        expect(buildLineWidthExpression(3, 12, 20)).toEqual(['step', ['zoom'], 3, 12, 6, 20, 12]);
    });
});

describe('buildMeasureBackgroundLineLayout', () => {
    it('falls back to the defaults when not overridden', () => {
        expect(buildMeasureBackgroundLineLayout(MEASURE_TYPE_STYLES.speedLimitation)).toEqual({
            'line-join': DEFAULT_LINE_JOIN,
            'line-cap': DEFAULT_LINE_CAP,
        });
    });

    it("uses the style's background overrides when provided", () => {
        expect(buildMeasureBackgroundLineLayout(MEASURE_TYPE_STYLES.noEntry)).toEqual({
            'line-join': DEFAULT_LINE_JOIN,
            'line-cap': 'round',
        });
    });

    it('honors backgroundLineJoin override', () => {
        expect(buildMeasureBackgroundLineLayout({
            color: '#000', dasharray: [1, 0], lineWidth: 4, backgroundLineJoin: 'miter',
        })).toEqual({
            'line-join': 'miter',
            'line-cap': DEFAULT_LINE_CAP,
        });
    });
});

describe('buildMeasureLineLayers', () => {
    const baseOptions = {
        sourceId: 'src',
        sourceLayer: 'restrictions',
    };

    /**
     * Narrow `paint` to non-undefined for ergonomic assertions; the helper
     * always returns layers with a `paint` block.
     * @param {import('maplibre-gl').LineLayerSpecification} layer
     */
    const paintOf = (layer) => /** @type {NonNullable<typeof layer.paint>} */ (layer.paint);

    it('returns a single main layer for a plain style (no background, no border)', () => {
        const layers = buildMeasureLineLayers('speedLimitation', MEASURE_TYPE_STYLES.speedLimitation, baseOptions);
        expect(layers).toHaveLength(1);
        const [main] = layers;
        expect(main.id).toBe('locations-layer-speedLimitation');
        expect(main.type).toBe('line');
        expect(main.source).toBe('src');
        expect(main['source-layer']).toBe('restrictions');
        expect(main.filter).toEqual(['==', ['get', 'measure_type'], 'speedLimitation']);
        expect(main.layout).toEqual({ 'line-join': DEFAULT_LINE_JOIN, 'line-cap': DEFAULT_LINE_CAP });
        expect(paintOf(main)['line-color']).toBe('#f6c43c');
        expect(paintOf(main)['line-dasharray']).toEqual([1, 0]);
        expect(paintOf(main)['line-width']).toEqual(['step', ['zoom'], 4, 15, 8, 18, 16]);
    });

    it('prepends a background layer when backgroundColor is set', () => {
        const layers = buildMeasureLineLayers('noEntry', MEASURE_TYPE_STYLES.noEntry, baseOptions);
        expect(layers).toHaveLength(2);
        const [background, main] = layers;
        expect(background.id).toBe('locations-layer-noEntry-background');
        expect(background.layout).toEqual({ 'line-join': DEFAULT_LINE_JOIN, 'line-cap': 'round' });
        expect(paintOf(background)['line-color']).toBe('#FFFFFF');
        expect(paintOf(background)['line-dasharray']).toBeUndefined();
        expect(main.id).toBe('locations-layer-noEntry');
        expect(main.layout).toEqual({ 'line-join': DEFAULT_LINE_JOIN, 'line-cap': 'butt' });
    });

    it('prepends a border layer when borderColor and borderWidth are set', () => {
        const layers = buildMeasureLineLayers('parkingProhibited', MEASURE_TYPE_STYLES.parkingProhibited, baseOptions);
        expect(layers).toHaveLength(2);
        const [border, main] = layers;
        expect(border.id).toBe('locations-layer-parkingProhibited-border');
        expect(paintOf(border)['line-color']).toBe('#FA7A35');
        // lineWidth (1) + 2 * borderWidth (3) = 7
        expect(paintOf(border)['line-width']).toEqual(['step', ['zoom'], 7, 15, 14, 18, 28]);
        expect(main.id).toBe('locations-layer-parkingProhibited');
        // main keeps its own (smaller) width
        expect(paintOf(main)['line-width']).toEqual(['step', ['zoom'], 1, 15, 2, 18, 4]);
    });

    it('returns border, background, then main when both are set', () => {
        const style = {
            color: '#000',
            dasharray: [1, 1],
            lineWidth: 2,
            backgroundColor: '#fff',
            borderColor: '#f00',
            borderWidth: 1,
        };
        const layers = buildMeasureLineLayers('combo', style, baseOptions);
        expect(layers.map((l) => l.id)).toEqual([
            'locations-layer-combo-border',
            'locations-layer-combo-background',
            'locations-layer-combo',
        ]);
    });

    it('does not add a border layer when borderColor is set without borderWidth', () => {
        const style = {
            color: '#000', dasharray: [1, 0], lineWidth: 4, borderColor: '#f00',
        };
        const layers = buildMeasureLineLayers('partial', style, baseOptions);
        expect(layers).toHaveLength(1);
    });

    it('honors custom lineWidthFirstStep and lineWidthSecondStep options', () => {
        const layers = buildMeasureLineLayers('speedLimitation', MEASURE_TYPE_STYLES.speedLimitation, {
            ...baseOptions,
            lineWidthFirstStep: 10,
            lineWidthSecondStep: 14,
        });
        expect(paintOf(layers[0])['line-width']).toEqual(['step', ['zoom'], 4, 10, 8, 14, 16]);
    });
});
