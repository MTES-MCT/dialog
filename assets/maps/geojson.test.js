// @ts-check

import { describe, it, expect } from 'vitest';
import {
    extendBoundsFromCoordinates,
    boundsFromGeoJSON,
    extractFirstGeometry,
    extractSingleGeometry,
} from './geojson';
import maplibregl from 'maplibre-gl';

describe('extendBoundsFromCoordinates', () => {
    it('extends bounds with a single point', () => {
        const bounds = new maplibregl.LngLatBounds();
        extendBoundsFromCoordinates([2.35, 48.85], bounds);

        expect(bounds.isEmpty()).toBe(false);
        expect(bounds.getSouthWest().toArray()).toEqual([2.35, 48.85]);
        expect(bounds.getNorthEast().toArray()).toEqual([2.35, 48.85]);
    });

    it('extends bounds with a LineString-like coordinates array', () => {
        const bounds = new maplibregl.LngLatBounds();
        extendBoundsFromCoordinates([[2.0, 48.0], [3.0, 49.0]], bounds);

        expect(bounds.getSouthWest().toArray()).toEqual([2.0, 48.0]);
        expect(bounds.getNorthEast().toArray()).toEqual([3.0, 49.0]);
    });

    it('handles deeply nested coordinates (MultiPolygon)', () => {
        const bounds = new maplibregl.LngLatBounds();
        const multiPolygon = [
            [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]],
            [[[10, 10], [11, 10], [11, 11], [10, 11], [10, 10]]],
        ];

        extendBoundsFromCoordinates(multiPolygon, bounds);

        expect(bounds.getSouthWest().toArray()).toEqual([0, 0]);
        expect(bounds.getNorthEast().toArray()).toEqual([11, 11]);
    });

    it('does nothing for empty or invalid input', () => {
        const bounds = new maplibregl.LngLatBounds();
        extendBoundsFromCoordinates([], bounds);
        extendBoundsFromCoordinates(null, bounds);
        extendBoundsFromCoordinates(undefined, bounds);

        expect(bounds.isEmpty()).toBe(true);
    });

    it('accepts 3D coordinates [lng, lat, alt]', () => {
        const bounds = new maplibregl.LngLatBounds();
        extendBoundsFromCoordinates([2.35, 48.85, 100], bounds);

        expect(bounds.getSouthWest().toArray()).toEqual([2.35, 48.85]);
    });
});

describe('boundsFromGeoJSON', () => {
    it('returns empty bounds for null/undefined', () => {
        expect(boundsFromGeoJSON(null).isEmpty()).toBe(true);
        expect(boundsFromGeoJSON(undefined).isEmpty()).toBe(true);
    });

    it('builds bounds from a Point geometry', () => {
        const bounds = boundsFromGeoJSON({ type: 'Point', coordinates: [2.35, 48.85] });

        expect(bounds.isEmpty()).toBe(false);
        expect(bounds.getCenter().toArray()).toEqual([2.35, 48.85]);
    });

    it('builds bounds from a LineString geometry', () => {
        const bounds = boundsFromGeoJSON({
            type: 'LineString',
            coordinates: [[0, 0], [10, 10]],
        });

        expect(bounds.getSouthWest().toArray()).toEqual([0, 0]);
        expect(bounds.getNorthEast().toArray()).toEqual([10, 10]);
    });

    it('builds bounds from a GeometryCollection', () => {
        const bounds = boundsFromGeoJSON({
            type: 'GeometryCollection',
            geometries: [
                { type: 'Point', coordinates: [0, 0] },
                { type: 'LineString', coordinates: [[5, 5], [10, 10]] },
            ],
        });

        expect(bounds.getSouthWest().toArray()).toEqual([0, 0]);
        expect(bounds.getNorthEast().toArray()).toEqual([10, 10]);
    });

    it('handles a GeometryCollection with no geometries', () => {
        expect(boundsFromGeoJSON({ type: 'GeometryCollection' }).isEmpty()).toBe(true);
        expect(boundsFromGeoJSON({ type: 'GeometryCollection', geometries: [] }).isEmpty()).toBe(true);
    });
});

describe('extractFirstGeometry', () => {
    it('returns null for falsy or untyped values', () => {
        expect(extractFirstGeometry(null)).toBeNull();
        expect(extractFirstGeometry({})).toBeNull();
    });

    it('returns the geometry of a bare geometry object', () => {
        const geom = { type: 'Point', coordinates: [1, 2] };

        expect(extractFirstGeometry(geom)).toBe(geom);
    });

    it('unwraps a Feature', () => {
        const geom = { type: 'Point', coordinates: [1, 2] };

        expect(extractFirstGeometry({ type: 'Feature', geometry: geom })).toBe(geom);
    });

    it('returns null when a Feature has no geometry', () => {
        expect(extractFirstGeometry({ type: 'Feature' })).toBeNull();
        expect(extractFirstGeometry({ type: 'Feature', geometry: null })).toBeNull();
    });

    it('returns the first feature geometry of a FeatureCollection', () => {
        const first = { type: 'Point', coordinates: [1, 2] };
        const second = { type: 'Point', coordinates: [3, 4] };

        const result = extractFirstGeometry({
            type: 'FeatureCollection',
            features: [
                { type: 'Feature', geometry: first },
                { type: 'Feature', geometry: second },
            ],
        });

        expect(result).toBe(first);
    });

    it('returns null for an empty FeatureCollection', () => {
        expect(extractFirstGeometry({ type: 'FeatureCollection', features: [] })).toBeNull();
        expect(extractFirstGeometry({ type: 'FeatureCollection' })).toBeNull();
    });
});

describe('extractSingleGeometry', () => {
    it('returns null for non-objects', () => {
        expect(extractSingleGeometry(null)).toBeNull();
        expect(extractSingleGeometry('foo')).toBeNull();
        expect(extractSingleGeometry(42)).toBeNull();
    });

    it('returns the geometry when given a bare geometry', () => {
        const geom = { type: 'LineString', coordinates: [[0, 0], [1, 1]] };

        expect(extractSingleGeometry(geom)).toBe(geom);
    });

    it('returns the geometry when given a Feature', () => {
        const geom = { type: 'Point', coordinates: [1, 2] };

        expect(extractSingleGeometry({ type: 'Feature', geometry: geom })).toBe(geom);
    });

    it('returns the geometry of a FeatureCollection with exactly one Feature', () => {
        const geom = { type: 'Point', coordinates: [1, 2] };

        const result = extractSingleGeometry({
            type: 'FeatureCollection',
            features: [{ type: 'Feature', geometry: geom }],
        });

        expect(result).toBe(geom);
    });

    it('returns null for a FeatureCollection with multiple features (strict)', () => {
        const result = extractSingleGeometry({
            type: 'FeatureCollection',
            features: [
                { type: 'Feature', geometry: { type: 'Point', coordinates: [0, 0] } },
                { type: 'Feature', geometry: { type: 'Point', coordinates: [1, 1] } },
            ],
        });

        expect(result).toBeNull();
    });

    it('returns null for an empty FeatureCollection', () => {
        expect(extractSingleGeometry({ type: 'FeatureCollection', features: [] })).toBeNull();
    });

    it('returns null for an object lacking a "coordinates" key', () => {
        expect(extractSingleGeometry({ type: 'Point' })).toBeNull();
    });
});
