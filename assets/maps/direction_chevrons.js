// @ts-check

// Chevrons de sens de circulation dessinés le long des tracés à sens unique.
// Ce module n'importe pas MapLibre : la carte publique le charge à la demande.

/** Nom de l'image de chevron enregistrée dans le style MapLibre. */
export const DIRECTION_CHEVRON_IMAGE_ID = 'app-direction-chevron';

/** Valeurs de `direction` pour lesquelles un sens est à représenter. */
export const ONE_WAY_DIRECTIONS = ['A_TO_B', 'B_TO_A'];

/**
 * Zoom à partir duquel les chevrons sont affichés, sur toutes les cartes. En dessous, le trait
 * (4 px, contre 8 px à partir de ce zoom) est trop fin pour qu'un chevron y soit lisible.
 * La flèche, elle, a son propre seuil (ARROW_MIN_ZOOM dans direction.js).
 */
export const DIRECTION_CHEVRONS_MIN_ZOOM = 15;

/** Côté de l'image en pixels CSS, et densité à laquelle elle est dessinée. */
const CHEVRON_SIZE = 12;
const CHEVRON_PIXEL_RATIO = 3;

/** Blanc comme sur la maquette, avec un fin contour sombre pour rester visible sur les tracés clairs ou hachurés. */
const CHEVRON_COLOR = '#FFFFFF';
const CHEVRON_OUTLINE_COLOR = 'rgba(22, 22, 22, 0.45)';

/**
 * @param {string | null | undefined} direction
 */
export function isOneWay(direction) {
    return typeof direction === 'string' && ONE_WAY_DIRECTIONS.includes(direction);
}

/**
 * Regroupe les géométries à sens unique en une FeatureCollection dont chaque entité
 * porte sa propriété `direction`. Les géométries restent orientées de A vers B
 * (convention de stockage) : c'est la couche qui retourne le chevron pour B vers A.
 *
 * @param {Array<{ geometry: *, direction: string | null | undefined }>} items
 * @returns {{ type: 'FeatureCollection', features: Array<*> }}
 */
export function buildOneWayFeatureCollection(items) {
    return {
        type: 'FeatureCollection',
        features: (items || [])
            .filter((item) => item?.geometry && isOneWay(item.direction))
            .map((item) => ({
                type: 'Feature',
                properties: { direction: item.direction },
                geometry: item.geometry,
            })),
    };
}

/**
 * Couche de symboles qui répète un chevron le long des lignes dont la propriété
 * `direction` vaut A_TO_B ou B_TO_A. Le chevron suit l'ordre des sommets (A vers B)
 * et est retourné de 180° pour B vers A.
 *
 * @param {Object} options
 * @param {string} options.id
 * @param {string} options.sourceId
 * @param {string} [options.sourceLayer] - couche des tuiles vectorielles (absente pour une source GeoJSON)
 * @param {number} [options.minzoom] - zoom à partir duquel les chevrons sont affichés (DIRECTION_CHEVRONS_MIN_ZOOM par défaut)
 * @param {number} [options.opacity] - opacité des chevrons (ex : 0.5 pour les brouillons)
 * @returns {import('maplibre-gl').SymbolLayerSpecification}
 */
export function buildDirectionChevronsLayer({ id, sourceId, sourceLayer, minzoom = DIRECTION_CHEVRONS_MIN_ZOOM, opacity }) {
    return /** @type {import('maplibre-gl').SymbolLayerSpecification} */ ({
        id,
        type: 'symbol',
        source: sourceId,
        ...(sourceLayer ? { 'source-layer': sourceLayer } : {}),
        minzoom,
        filter: ['match', ['get', 'direction'], ONE_WAY_DIRECTIONS, true, false],
        layout: {
            'symbol-placement': 'line',
            'symbol-spacing': 90,
            'icon-image': DIRECTION_CHEVRON_IMAGE_ID,
            // Le chevron tient dans l'épaisseur du trait, qui double aux zooms 15 et 18
            'icon-size': ['step', ['zoom'], 0.8, 15, 1, 18, 1.8],
            'icon-rotate': ['case', ['==', ['get', 'direction'], 'B_TO_A'], 180, 0],
            // Les chevrons ne masquent ni ne sont masqués par les libellés du fond de carte
            'icon-allow-overlap': true,
            'icon-ignore-placement': true,
        },
        ...(opacity !== undefined ? { paint: { 'icon-opacity': opacity } } : {}),
    });
}

/**
 * Trace un petit chevron pointant vers la droite (le sens de parcours d'une ligne
 * pour MapLibre) : trait blanc, à l'intérieur du tracé, sur un fin contour sombre.
 *
 * @param {CanvasRenderingContext2D} context
 * @param {number} size - côté de la zone de dessin, dans l'unité du contexte
 */
export function drawChevron(context, size) {
    const unit = size / CHEVRON_SIZE;

    context.lineCap = 'round';
    context.lineJoin = 'round';

    for (const [color, width] of /** @type {Array<[string, number]>} */ ([[CHEVRON_OUTLINE_COLOR, 3.2], [CHEVRON_COLOR, 1.6]])) {
        context.beginPath();
        context.moveTo(4.75 * unit, 3 * unit);
        context.lineTo(7.75 * unit, 6 * unit);
        context.lineTo(4.75 * unit, 9 * unit);
        context.strokeStyle = color;
        context.lineWidth = width * unit;
        context.stroke();
    }
}

/**
 * Enregistre l'image de chevron dans la carte (une seule fois par carte).
 * Renvoie `false` si le navigateur ne fournit pas de canvas 2D.
 *
 * @param {import('maplibre-gl').Map} map
 */
export function addDirectionChevronImage(map) {
    if (map.hasImage(DIRECTION_CHEVRON_IMAGE_ID)) {
        return true;
    }

    const side = CHEVRON_SIZE * CHEVRON_PIXEL_RATIO;
    const canvas = document.createElement('canvas');
    canvas.width = side;
    canvas.height = side;
    const context = canvas.getContext('2d');

    if (!context) {
        return false;
    }

    drawChevron(context, side);
    map.addImage(DIRECTION_CHEVRON_IMAGE_ID, context.getImageData(0, 0, side, side), { pixelRatio: CHEVRON_PIXEL_RATIO });

    return true;
}

/**
 * Ajoute à la carte la couche de chevrons d'une source existante (tuiles ou GeoJSON)
 * dont les entités portent la propriété `direction`.
 *
 * @param {import('maplibre-gl').Map} map
 * @param {Parameters<typeof buildDirectionChevronsLayer>[0]} options
 * @returns {string | null} identifiant de la couche ajoutée, ou null sans canvas 2D
 */
export function addDirectionChevronsLayer(map, options) {
    if (!addDirectionChevronImage(map)) {
        return null;
    }

    map.addLayer(buildDirectionChevronsLayer(options));

    return options.id;
}
