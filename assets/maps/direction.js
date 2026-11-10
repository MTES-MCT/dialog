// @ts-check

import maplibregl from 'maplibre-gl';
import '../styles/components/direction-markers.css';

export const DIRECTION_BOTH = 'BOTH';
export const DIRECTION_A_TO_B = 'A_TO_B';
export const DIRECTION_B_TO_A = 'B_TO_A';

/**
 * Extrait, dans l'ordre, les lignes (tableaux de [lng, lat]) d'une géométrie GeoJSON :
 * LineString, MultiLineString, GeometryCollection (récursif), Feature ou FeatureCollection.
 * Les autres types (Point, Polygon…) sont ignorés : ils ne portent pas de sens.
 *
 * @param {*} geojson
 * @returns {Array<Array<[number, number]>>}
 */
export function extractLines(geojson) {
    if (!geojson || typeof geojson !== 'object') {
        return [];
    }

    if (geojson.type === 'FeatureCollection') {
        return (geojson.features || []).flatMap((f) => extractLines(f));
    }

    if (geojson.type === 'Feature') {
        return extractLines(geojson.geometry);
    }

    if (geojson.type === 'GeometryCollection') {
        return (geojson.geometries || []).flatMap((g) => extractLines(g));
    }

    if (geojson.type === 'LineString') {
        return isUsableLine(geojson.coordinates) ? [geojson.coordinates] : [];
    }

    if (geojson.type === 'MultiLineString') {
        return (geojson.coordinates || []).filter(isUsableLine);
    }

    return [];
}

/**
 * @param {*} coordinates
 */
function isUsableLine(coordinates) {
    return Array.isArray(coordinates)
        && coordinates.length >= 2
        && coordinates.every((c) => Array.isArray(c) && typeof c[0] === 'number' && typeof c[1] === 'number');
}

/**
 * Extrémités A et B d'une géométrie linéaire orientée de A vers B (convention de
 * stockage de DiaLog) : A est le premier point de la première ligne, B le dernier
 * point de la dernière ligne. Renvoie `null` sans ligne exploitable.
 *
 * @param {*} geojson
 * @returns {{ a: [number, number], b: [number, number] } | null}
 */
export function lineEndpoints(geojson) {
    const lines = extractLines(geojson);

    if (lines.length === 0) {
        return null;
    }

    const first = lines[0];
    const last = lines[lines.length - 1];

    return { a: first[0], b: last[last.length - 1] };
}

/**
 * Cap (en degrés, 0 = nord, sens horaire) pour aller de `from` vers `to`.
 * Approximation équirectangulaire, suffisante à l'échelle d'une rue.
 *
 * @param {[number, number]} from
 * @param {[number, number]} to
 */
export function bearing(from, to) {
    const toRad = Math.PI / 180;
    const midLat = ((from[1] + to[1]) / 2) * toRad;
    const dx = (to[0] - from[0]) * Math.cos(midLat);
    const dy = to[1] - from[1];
    const degrees = Math.atan2(dx, dy) / toRad;

    return (degrees + 360) % 360;
}

/**
 * Point situé à mi-longueur du tracé (toutes lignes confondues) et cap du segment
 * qui le porte, dans le sens de parcours A vers B. Renvoie `null` sans ligne.
 *
 * @param {*} geojson
 * @returns {{ coordinates: [number, number], bearing: number } | null}
 */
export function lineMidpoint(geojson) {
    const lines = extractLines(geojson);

    if (lines.length === 0) {
        return null;
    }

    /** @type {Array<{ from: [number, number], to: [number, number], length: number }>} */
    const segments = [];
    let total = 0;

    for (const line of lines) {
        for (let i = 1; i < line.length; i++) {
            const length = planarDistance(line[i - 1], line[i]);
            segments.push({ from: line[i - 1], to: line[i], length });
            total += length;
        }
    }

    if (total === 0) {
        // Tous les points sont confondus : pas de sens à représenter
        return null;
    }

    let remaining = total / 2;

    for (const segment of segments) {
        if (remaining <= segment.length) {
            const ratio = segment.length === 0 ? 0 : remaining / segment.length;

            return {
                coordinates: [
                    segment.from[0] + (segment.to[0] - segment.from[0]) * ratio,
                    segment.from[1] + (segment.to[1] - segment.from[1]) * ratio,
                ],
                bearing: bearing(segment.from, segment.to),
            };
        }

        remaining -= segment.length;
    }

    const lastSegment = segments[segments.length - 1];

    return { coordinates: lastSegment.to, bearing: bearing(lastSegment.from, lastSegment.to) };
}

/**
 * Distance plane (en degrés corrigés de la latitude) : seul le rapport entre
 * segments compte pour trouver le milieu du tracé.
 *
 * @param {[number, number]} from
 * @param {[number, number]} to
 */
function planarDistance(from, to) {
    const midLat = ((from[1] + to[1]) / 2) * Math.PI / 180;
    const dx = (to[0] - from[0]) * Math.cos(midLat);
    const dy = to[1] - from[1];

    return Math.sqrt(dx * dx + dy * dy);
}

/**
 * Décorations de sens à afficher pour une géométrie orientée de A vers B :
 * les marqueurs A et B aux extrémités, et une flèche au milieu du tracé
 * orientée dans le sens concerné (aucune flèche en double sens).
 *
 * @param {*} geojson
 * @param {string | null | undefined} direction - BOTH, A_TO_B ou B_TO_A
 * @returns {{
 *   a: [number, number],
 *   b: [number, number],
 *   arrow: { coordinates: [number, number], rotation: number } | null,
 * } | null}
 */
export function buildDirectionDecorations(geojson, direction) {
    const endpoints = lineEndpoints(geojson);

    if (!endpoints) {
        return null;
    }

    let arrow = null;

    if (direction === DIRECTION_A_TO_B || direction === DIRECTION_B_TO_A) {
        const midpoint = lineMidpoint(geojson);

        if (midpoint) {
            const rotation = direction === DIRECTION_B_TO_A
                ? (midpoint.bearing + 180) % 360
                : midpoint.bearing;

            arrow = { coordinates: midpoint.coordinates, rotation };
        }
    }

    return { a: endpoints.a, b: endpoints.b, arrow };
}

/**
 * @param {string} className
 * @param {string} [text]
 */
function createMarkerElement(className, text) {
    const element = document.createElement('div');
    element.className = className;
    element.setAttribute('aria-hidden', 'true');

    if (text !== undefined) {
        element.textContent = text;
    }

    return element;
}

/**
 * Pointe de flèche (vers le nord avant rotation) à base échancrée : un simple
 * triangle presque équilatéral rendrait la rotation illisible (symétrie à 120°).
 */
function createArrowIcon() {
    const svgNamespace = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svgNamespace, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('class', 'app-direction-marker__arrow-icon');
    svg.setAttribute('aria-hidden', 'true');

    const path = document.createElementNS(svgNamespace, 'path');
    path.setAttribute('d', 'M12 2 L21 21 L12 16 L3 21 Z');
    svg.appendChild(path);

    return svg;
}

/**
 * Gère les marqueurs HTML de sens (A, B et flèche) d'une carte MapLibre. Les
 * marqueurs sont recréés à chaque mise à jour et retirés quand la géométrie
 * n'est plus linéaire ou que le sens ne doit plus être affiché.
 */
export class DirectionMarkers {
    /** @type {import('maplibre-gl').Map} */
    #map;
    /** @type {Array<import('maplibre-gl').Marker>} */
    #markers = [];

    /**
     * @param {import('maplibre-gl').Map} map
     */
    constructor(map) {
        this.#map = map;
    }

    /**
     * @param {*} geojson - géométrie orientée de A vers B (ou null pour tout retirer)
     * @param {string | null | undefined} direction
     */
    update(geojson, direction) {
        this.clear();

        const decorations = geojson ? buildDirectionDecorations(geojson, direction) : null;

        if (!decorations) {
            return;
        }

        this.#add(createMarkerElement('app-direction-marker app-direction-marker--endpoint', 'A'), decorations.a);
        this.#add(createMarkerElement('app-direction-marker app-direction-marker--endpoint', 'B'), decorations.b);

        if (decorations.arrow) {
            const element = createMarkerElement('app-direction-marker app-direction-marker--arrow');
            element.appendChild(createArrowIcon());
            this.#add(element, decorations.arrow.coordinates, {
                rotation: decorations.arrow.rotation,
                rotationAlignment: 'map',
                pitchAlignment: 'map',
            });
        }
    }

    clear() {
        for (const marker of this.#markers) {
            marker.remove();
        }

        this.#markers = [];
    }

    /**
     * @param {HTMLElement} element
     * @param {[number, number]} coordinates
     * @param {import('maplibre-gl').MarkerOptions} [options]
     */
    #add(element, coordinates, options = {}) {
        const marker = new maplibregl.Marker({ element, anchor: 'center', ...options })
            .setLngLat(coordinates)
            .addTo(this.#map);

        this.#markers.push(marker);
    }
}
