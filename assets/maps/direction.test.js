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

/**
 * Carte factice à projection plane. L'échelle (`pixelsPerDegree`) et le niveau de zoom sont
 * réglés séparément, pour tester chaque règle de masquage indépendamment de l'autre.
 * À 1 000 000 px par degré, LINE mesure 2 000 px à l'écran.
 *
 * @param {number} [pixelsPerDegree]
 * @param {number} [zoom]
 */
function createMap(pixelsPerDegree = 1_000_000, zoom = 18) {
    /** @type {Array<() => void>} */
    const zoomListeners = [];
    let scale = pixelsPerDegree;
    let level = zoom;

    return {
        /** @param {string} event @param {() => void} listener */
        on(event, listener) {
            if (event === 'zoom') {
                zoomListeners.push(listener);
            }
        },
        /** @param {[number, number]} lngLat */
        project: ([lng, lat]) => ({ x: lng * scale, y: -lat * scale }),
        getZoom: () => level,
        /** @param {number} value @param {number} [zoom] */
        zoomTo(value, zoom = level) {
            scale = value;
            level = zoom;
            zoomListeners.forEach((listener) => listener());
        },
    };
}

/** @param {{ options: { element: HTMLElement } }} marker */
const isShown = (marker) => marker.options.element.style.display !== 'none';

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
        const markers = new DirectionMarkers(/** @type {any} */ (createMap()));

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
        const markers = new DirectionMarkers(/** @type {any} */ (createMap()));

        markers.update({ type: 'LineString', coordinates: LINE }, 'A_TO_B');
        markers.update({ type: 'LineString', coordinates: LINE }, 'BOTH');

        expect(markerInstances).toHaveLength(5);
        expect(markerInstances.slice(0, 3).every((marker) => marker.removed)).toBe(true);
        expect(markerInstances.slice(3).every((marker) => !marker.removed)).toBe(true);

        markers.update(null, 'A_TO_B');

        expect(markerInstances.every((marker) => marker.removed)).toBe(true);
    });

    it('shows one arrow per one-way location and no endpoint in read-only mode', () => {
        const markers = new DirectionMarkers(/** @type {any} */ (createMap()));
        const other = { type: 'LineString', coordinates: [[3, 49], [3, 49.001]] };

        markers.updateArrows([
            { geometry: { type: 'LineString', coordinates: LINE }, direction: 'A_TO_B' },
            { geometry: other, direction: 'B_TO_A' },
            { geometry: other, direction: 'BOTH' },
            { geometry: null, direction: 'A_TO_B' },
        ]);

        expect(markerInstances).toHaveLength(2);
        expect(markerInstances.every((marker) => marker.options.element.className.includes('app-direction-marker--arrow'))).toBe(true);
        expect(markerInstances[0].options.rotation).toBeCloseTo(90, 5);
        // Tracé vers le nord, sens B vers A : la flèche pointe vers le sud
        expect(markerInstances[1].options.rotation).toBeCloseTo(180, 5);

        markers.updateArrows([]);

        expect(markerInstances.every((marker) => marker.removed)).toBe(true);
    });

    it('hides the arrow, then A and B, as the line shrinks on screen, and shows them again when zooming in', () => {
        // LINE mesure 200 px : tout tient
        const map = createMap(100_000);
        const markers = new DirectionMarkers(/** @type {any} */ (map));

        markers.update({ type: 'LineString', coordinates: LINE }, 'A_TO_B');
        const [a, b, arrow] = markerInstances;

        expect([a, b, arrow].map(isShown)).toEqual([true, true, true]);

        // 50 px : la flèche, à 25 px de A et de B, toucherait les pastilles
        map.zoomTo(25_000);
        expect([a, b, arrow].map(isShown)).toEqual([true, true, false]);

        // 20 px : A et B se chevaucheraient
        map.zoomTo(10_000);
        expect([a, b, arrow].map(isShown)).toEqual([false, false, false]);

        map.zoomTo(100_000);
        expect([a, b, arrow].map(isShown)).toEqual([true, true, true]);
    });

    it('hides markers created while the map is already zoomed out', () => {
        const markers = new DirectionMarkers(/** @type {any} */ (createMap(10_000)));

        markers.update({ type: 'LineString', coordinates: LINE }, 'A_TO_B');

        expect(markerInstances.map(isShown)).toEqual([false, false, false]);
    });

    it('hides the arrow below the minimum zoom even on a long line, but keeps A and B', () => {
        // LINE mesure 2 000 px : la longueur n'est pas en cause
        const map = createMap(1_000_000, 12.9);
        const markers = new DirectionMarkers(/** @type {any} */ (map));

        markers.update({ type: 'LineString', coordinates: LINE }, 'A_TO_B');
        const [a, b, arrow] = markerInstances;

        // A et B restent : ils disent où commence et où finit le tracé, sans le recouvrir
        expect([a, b, arrow].map(isShown)).toEqual([true, true, false]);

        map.zoomTo(1_000_000, 13);
        expect([a, b, arrow].map(isShown)).toEqual([true, true, true]);
    });

    it('hides every read-only arrow below the minimum zoom', () => {
        const map = createMap(1_000_000, 12);
        const markers = new DirectionMarkers(/** @type {any} */ (map));
        const other = { type: 'LineString', coordinates: [[3, 49], [3, 49.001]] };

        markers.updateArrows([
            { geometry: { type: 'LineString', coordinates: LINE }, direction: 'A_TO_B' },
            { geometry: other, direction: 'B_TO_A' },
        ]);

        expect(markerInstances.map(isShown)).toEqual([false, false]);

        map.zoomTo(1_000_000, 16);
        expect(markerInstances.map(isShown)).toEqual([true, true]);
    });

    it('keeps the read-only arrow as long as the line sticks out on both sides', () => {
        // 50 px : sans pastilles A et B à éviter, la flèche tient
        const map = createMap(25_000);
        const markers = new DirectionMarkers(/** @type {any} */ (map));

        markers.updateArrows([{ geometry: { type: 'LineString', coordinates: LINE }, direction: 'A_TO_B' }]);

        expect(isShown(markerInstances[0])).toBe(true);

        // 20 px : la flèche recouvrirait tout le tracé
        map.zoomTo(10_000);
        expect(isShown(markerInstances[0])).toBe(false);
    });
});
