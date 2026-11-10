// @ts-check
// @vitest-environment jsdom

import { describe, it, expect, vi, beforeEach } from 'vitest';

const markerInstances = [];

vi.mock('maplibre-gl', () => {
    class Marker {
        constructor(options) {
            this.options = options;
            this.lngLat = null;
            this.removed = false;
            markerInstances.push(this);
        }

        setLngLat(lngLat) {
            this.lngLat = lngLat;

            return this;
        }

        addTo() {
            return this;
        }

        remove() {
            this.removed = true;
        }
    }

    return { default: { Marker } };
});

vi.mock('../styles/components/direction-markers.css', () => ({}));

const {
    bearing,
    buildDirectionDecorations,
    DirectionMarkers,
    extractLines,
    lineEndpoints,
    lineMidpoint,
} = await import('./direction');

/** @type {[number, number][]} */
const LINE = [[2.0, 48.0], [2.001, 48.0], [2.002, 48.0]];

describe('extractLines', () => {
    it('returns the coordinates of a LineString', () => {
        expect(extractLines({ type: 'LineString', coordinates: LINE })).toEqual([LINE]);
    });

    it('returns every line of a MultiLineString', () => {
        const other = [[3, 49], [3.001, 49]];

        expect(extractLines({ type: 'MultiLineString', coordinates: [LINE, other] })).toEqual([LINE, other]);
    });

    it('unwraps Feature, FeatureCollection and GeometryCollection', () => {
        const feature = { type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates: LINE } };

        expect(extractLines(feature)).toEqual([LINE]);
        expect(extractLines({ type: 'FeatureCollection', features: [feature] })).toEqual([LINE]);
        expect(extractLines({
            type: 'GeometryCollection',
            geometries: [{ type: 'Point', coordinates: [0, 0] }, { type: 'LineString', coordinates: LINE }],
        })).toEqual([LINE]);
    });

    it('ignores non-linear geometries, degenerate lines and garbage', () => {
        expect(extractLines({ type: 'Point', coordinates: [2, 48] })).toEqual([]);
        expect(extractLines({ type: 'Polygon', coordinates: [[[0, 0], [1, 0], [1, 1], [0, 0]]] })).toEqual([]);
        expect(extractLines({ type: 'LineString', coordinates: [[2, 48]] })).toEqual([]);
        expect(extractLines(null)).toEqual([]);
        expect(extractLines('nope')).toEqual([]);
    });
});

describe('lineEndpoints', () => {
    it('takes A at the start of the first line and B at the end of the last line', () => {
        const other = [[3, 49], [3.001, 49]];

        expect(lineEndpoints({ type: 'MultiLineString', coordinates: [LINE, other] })).toEqual({
            a: [2.0, 48.0],
            b: [3.001, 49],
        });
    });

    it('returns null without a usable line', () => {
        expect(lineEndpoints({ type: 'Point', coordinates: [2, 48] })).toBeNull();
    });
});

describe('bearing', () => {
    it('is 0 towards the north, 90 towards the east, 180 towards the south, 270 towards the west', () => {
        expect(bearing([2, 48], [2, 48.001])).toBeCloseTo(0, 5);
        expect(bearing([2, 48], [2.001, 48])).toBeCloseTo(90, 5);
        expect(bearing([2, 48], [2, 47.999])).toBeCloseTo(180, 5);
        expect(bearing([2, 48], [1.999, 48])).toBeCloseTo(270, 5);
    });
});

describe('lineMidpoint', () => {
    it('finds the point at half the length and the bearing of its segment', () => {
        // Deux segments de longueur égale : le milieu est le sommet central, sur le 1er segment
        const midpoint = lineMidpoint({ type: 'LineString', coordinates: LINE });

        expect(midpoint).not.toBeNull();
        expect(midpoint?.coordinates[0]).toBeCloseTo(2.001, 9);
        expect(midpoint?.coordinates[1]).toBeCloseTo(48.0, 9);
        expect(midpoint?.bearing).toBeCloseTo(90, 5);
    });

    it('interpolates inside a longer segment and spans several lines', () => {
        // Première ligne vers l'est (longueur 1), seconde vers le nord (longueur 3) : le milieu est à 1 sur la seconde
        const geometry = {
            type: 'MultiLineString',
            coordinates: [
                [[0, 0], [0.001, 0]],
                [[0.001, 0], [0.001, 0.003]],
            ],
        };

        const midpoint = lineMidpoint(geometry);

        expect(midpoint?.coordinates[0]).toBeCloseTo(0.001, 9);
        expect(midpoint?.coordinates[1]).toBeCloseTo(0.001, 9);
        expect(midpoint?.bearing).toBeCloseTo(0, 5);
    });

    it('returns null when every point is the same', () => {
        expect(lineMidpoint({ type: 'LineString', coordinates: [[2, 48], [2, 48]] })).toBeNull();
    });
});

describe('buildDirectionDecorations', () => {
    const geometry = { type: 'LineString', coordinates: LINE };

    it('places A and B at the endpoints and no arrow for both directions', () => {
        const decorations = buildDirectionDecorations(geometry, 'BOTH');

        expect(decorations).toEqual({ a: [2.0, 48.0], b: [2.002, 48.0], arrow: null });
    });

    it('points the arrow along the line from A to B', () => {
        const decorations = buildDirectionDecorations(geometry, 'A_TO_B');

        expect(decorations?.arrow?.rotation).toBeCloseTo(90, 5);
        expect(decorations?.arrow?.coordinates[0]).toBeCloseTo(2.001, 9);
    });

    it('reverses the arrow from B to A', () => {
        const decorations = buildDirectionDecorations(geometry, 'B_TO_A');

        expect(decorations?.arrow?.rotation).toBeCloseTo(270, 5);
    });

    it('returns null for a non-linear geometry', () => {
        expect(buildDirectionDecorations({ type: 'Polygon', coordinates: [[[0, 0], [1, 0], [1, 1], [0, 0]]] }, 'A_TO_B')).toBeNull();
    });
});

describe('DirectionMarkers', () => {
    beforeEach(() => {
        markerInstances.length = 0;
    });

    it('adds A, B and a rotated arrow marker, then removes them all on clear', () => {
        const markers = new DirectionMarkers(/** @type {any} */ ({}));

        markers.update({ type: 'LineString', coordinates: LINE }, 'B_TO_A');

        expect(markerInstances).toHaveLength(3);
        expect(markerInstances[0].options.element.textContent).toBe('A');
        expect(markerInstances[0].lngLat).toEqual([2.0, 48.0]);
        expect(markerInstances[1].options.element.textContent).toBe('B');
        expect(markerInstances[1].lngLat).toEqual([2.002, 48.0]);
        expect(markerInstances[2].options.rotation).toBeCloseTo(270, 5);
        expect(markerInstances[2].options.rotationAlignment).toBe('map');
        expect(markerInstances[2].options.element.className).toContain('app-direction-marker--arrow');

        markers.clear();

        expect(markerInstances.every((marker) => marker.removed)).toBe(true);
    });

    it('replaces the previous markers on each update and shows none without a direction', () => {
        const markers = new DirectionMarkers(/** @type {any} */ ({}));

        markers.update({ type: 'LineString', coordinates: LINE }, 'A_TO_B');
        markers.update({ type: 'LineString', coordinates: LINE }, 'BOTH');

        expect(markerInstances).toHaveLength(5);
        expect(markerInstances.slice(0, 3).every((marker) => marker.removed)).toBe(true);
        expect(markerInstances.slice(3).every((marker) => !marker.removed)).toBe(true);

        markers.update(null, 'A_TO_B');

        expect(markerInstances.every((marker) => marker.removed)).toBe(true);
    });
});
