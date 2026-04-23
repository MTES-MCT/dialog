// @ts-check

import maplibregl from 'maplibre-gl';

/**
 * Recursively walks a GeoJSON `coordinates` array and extends the given
 * MapLibre LngLatBounds with every [lng, lat] pair encountered.
 *
 * @param {*} coordinates
 * @param {import('maplibre-gl').LngLatBounds} bounds
 */
export function extendBoundsFromCoordinates(coordinates, bounds) {
    if (!Array.isArray(coordinates) || coordinates.length === 0) {
        return;
    }

    if (typeof coordinates[0] === 'number' && typeof coordinates[1] === 'number') {
        bounds.extend([coordinates[0], coordinates[1]]);
        return;
    }

    coordinates.forEach((c) => extendBoundsFromCoordinates(c, bounds));
}

/**
 * Builds a `LngLatBounds` covering the given GeoJSON geometry (including
 * `GeometryCollection`). The returned bounds may be empty when the geometry
 * carries no usable coordinates.
 *
 * @param {*} geojson
 * @returns {import('maplibre-gl').LngLatBounds}
 */
export function boundsFromGeoJSON(geojson) {
    const bounds = new maplibregl.LngLatBounds();

    if (!geojson) {
        return bounds;
    }

    if (geojson.type === 'GeometryCollection') {
        (geojson.geometries || []).forEach((g) => {
            const sub = boundsFromGeoJSON(g);
            if (!sub.isEmpty()) {
                bounds.extend(sub.getSouthWest());
                bounds.extend(sub.getNorthEast());
            }
        });
        return bounds;
    }

    extendBoundsFromCoordinates(geojson.coordinates, bounds);

    return bounds;
}

/**
 * Returns the first geometry contained in a GeoJSON object — accepts a bare
 * geometry, a Feature, or a FeatureCollection (taking its first feature).
 * Returns `null` when no usable geometry can be found.
 *
 * @param {*} parsed
 */
export function extractFirstGeometry(parsed) {
    if (!parsed || !parsed.type) {
        return null;
    }

    if (parsed.type === 'FeatureCollection') {
        return parsed.features?.[0]?.geometry || null;
    }

    if (parsed.type === 'Feature') {
        return parsed.geometry || null;
    }

    return parsed;
}

/**
 * Strict variant of {@link extractFirstGeometry}: returns the geometry only
 * when the input unambiguously describes a single geometry (a bare geometry,
 * a Feature, or a FeatureCollection holding exactly one Feature).
 *
 * Returns `null` for FeatureCollections containing zero or several features,
 * or for any other unsupported shape.
 *
 * @param {*} parsed
 */
export function extractSingleGeometry(parsed) {
    if (!parsed || typeof parsed !== 'object') {
        return null;
    }

    if (parsed.type === 'FeatureCollection') {
        if (!Array.isArray(parsed.features) || parsed.features.length !== 1) {
            return null;
        }

        return extractSingleGeometry(parsed.features[0]);
    }

    if (parsed.type === 'Feature') {
        return parsed.geometry || null;
    }

    if (typeof parsed.type === 'string' && 'coordinates' in parsed) {
        return parsed;
    }

    return null;
}
