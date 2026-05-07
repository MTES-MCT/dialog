// @ts-check

import { describe, it, expect } from 'vitest';
import {
    MEASURE_TYPE_STYLES,
    DEFAULT_MEASURE_STYLE,
    getMeasureTypeStyle,
    buildLineColorExpression,
    buildLineDasharrayExpression,
    buildLineWidthExpression,
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

describe('buildLineColorExpression', () => {
    it('builds a case expression with one branch per measure type and a default', () => {
        const expr = buildLineColorExpression();

        expect(expr[0]).toBe('case');

        const entries = Object.entries(MEASURE_TYPE_STYLES);
        // 'case' + (condition + value) per type + default value
        expect(expr).toHaveLength(1 + entries.length * 2 + 1);

        entries.forEach(([type, style], index) => {
            const condition = expr[1 + index * 2];
            const value = expr[2 + index * 2];
            expect(condition).toEqual(['==', ['get', 'measure_type'], type]);
            expect(value).toBe(style.color);
        });

        expect(expr[expr.length - 1]).toBe(DEFAULT_MEASURE_STYLE.color);
    });
});

describe('buildLineDasharrayExpression', () => {
    it('only includes branches for non-solid dasharrays and falls back to the default', () => {
        const expr = buildLineDasharrayExpression();

        expect(expr[0]).toBe('case');

        const dashedEntries = Object.entries(MEASURE_TYPE_STYLES).filter(
            ([, style]) => style.dasharray[0] !== 1 || style.dasharray[1] !== 0,
        );

        expect(expr).toHaveLength(1 + dashedEntries.length * 2 + 1);

        dashedEntries.forEach(([type, style], index) => {
            const condition = expr[1 + index * 2];
            const value = expr[2 + index * 2];
            expect(condition).toEqual(['==', ['get', 'measure_type'], type]);
            expect(value).toEqual(['literal', style.dasharray]);
        });

        expect(expr[expr.length - 1]).toEqual(['literal', DEFAULT_MEASURE_STYLE.dasharray]);
    });

    it('omits measure types whose dasharray is solid ([1, 0])', () => {
        const expr = buildLineDasharrayExpression();
        const serialized = JSON.stringify(expr);

        expect(serialized).not.toContain('noEntry');
        expect(serialized).not.toContain('speedLimitation');
        expect(serialized).not.toContain('parkingProhibited');
        expect(serialized).toContain('alternateRoad');
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
