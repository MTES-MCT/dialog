// @ts-check

/**
 * @typedef {Object} MeasureTypeStyle
 * @property {string} color
 * @property {number[]} dasharray
 * @property {number} lineWidth
 * @property {string} [backgroundColor] - Optional solid color drawn beneath the dashed
 *   line, to make dasharray gaps visible (e.g. red/white "hachures" for noEntry).
 * @property {'bevel' | 'round' | 'miter'} [lineJoin] - MapLibre `line-join` for the
 *   main (dashed) layer (defaults to 'round').
 * @property {'butt' | 'round' | 'square'} [lineCap] - MapLibre `line-cap` for the
 *   main (dashed) layer. Note: this applies to both the line endpoints AND each
 *   dash endpoint — see `backgroundLineCap` to round only the line endpoints.
 *   Defaults to 'round'.
 * @property {'bevel' | 'round' | 'miter'} [backgroundLineJoin] - MapLibre `line-join`
 *   for the background layer (defaults to 'round'). Only used when `backgroundColor`
 *   is set.
 * @property {'butt' | 'round' | 'square'} [backgroundLineCap] - MapLibre `line-cap`
 *   for the background layer (defaults to 'round'). Only used when `backgroundColor`
 *   is set. Set this to round the overall line endpoints while keeping crisp dash
 *   ends on the main layer (use `lineCap: 'butt'` on the main layer for that).
 * @property {string} [borderColor] - Optional border color drawn around the main
 *   line. Implemented as a wider underlay layer painted with this color.
 *   Requires `borderWidth`.
 * @property {number} [borderWidth] - Border thickness in pixels added on EACH
 *   side of the main line (so the underlay is `lineWidth + 2 * borderWidth`).
 *   Only applied when `borderColor` is set.
 */

/** @typedef {import('@maplibre/maplibre-gl-style-spec').ExpressionSpecification} ExpressionSpecification */

/** Default values used when a measure type style does not override them. */
export const DEFAULT_LINE_JOIN = 'round';
export const DEFAULT_LINE_CAP = 'round';

/**
 * Per-measure-type style definitions.
 *
 * NOTE: MapLibre forbids data-driven expressions on `line-cap` / `line-join`,
 * so any variation of these properties between measure types requires one
 * MapLibre layer per type (filtered on `measure_type`). See the consumers in
 * `assets/customElements/map.js`, which iterate this object in declaration
 * order — the last entry is therefore drawn on top.
 *
 * @type {Record<string, MeasureTypeStyle>}
 */
export const MEASURE_TYPE_STYLES = {
  noOvertaking: { color: '#DA70D6', dasharray: [1, 2, 4, 2], lineWidth: 4 }, // pink
  alternateRoad: { color: '#6A6AF4', dasharray: [2, 2], lineWidth: 4 }, // purple
  parkingProhibited: {
    color: '#FFFFFF',
    dasharray: [1, 0],
    lineWidth: 1,
    borderColor: '#FA7A35',
    borderWidth: 3,
  }, // black
  speedLimitation: {color: '#f6c43c', dasharray: [1, 0], lineWidth: 4}, // yellow with dark yellow border
  noEntry: {
    color: '#CE0500',
    dasharray: [1, 1],
    lineWidth: 4,
    backgroundColor: '#FFFFFF',
    lineCap: 'butt',
    backgroundLineCap: 'round',
  }, // red/white hachures, rounded overall ends
};

export const DEFAULT_MEASURE_STYLE = { color: '#000000', dasharray: [1, 0], lineWidth: 4 };

/**
 * @param {string} measureType
 * @returns {MeasureTypeStyle}
 */
export function getMeasureTypeStyle(measureType) {
    return MEASURE_TYPE_STYLES[measureType] ?? DEFAULT_MEASURE_STYLE;
}

/**
 * Build the constant `layout` block for the main (dashed) line layer of a
 * single measure type.
 * @param {MeasureTypeStyle} style
 * @returns {{ 'line-join': 'bevel' | 'round' | 'miter', 'line-cap': 'butt' | 'round' | 'square' }}
 */
export function buildMeasureLineLayout(style) {
    return {
        'line-join': style.lineJoin ?? DEFAULT_LINE_JOIN,
        'line-cap': style.lineCap ?? DEFAULT_LINE_CAP,
    };
}

/**
 * Build the constant `layout` block for the background (solid underlay) line
 * layer of a single measure type.
 * @param {MeasureTypeStyle} style
 * @returns {{ 'line-join': 'bevel' | 'round' | 'miter', 'line-cap': 'butt' | 'round' | 'square' }}
 */
export function buildMeasureBackgroundLineLayout(style) {
    return {
        'line-join': style.backgroundLineJoin ?? DEFAULT_LINE_JOIN,
        'line-cap': style.backgroundLineCap ?? DEFAULT_LINE_CAP,
    };
}

/**
 * Build a MapLibre 'step' expression for line-width based on zoom level.
 * line-width = baseWidth when zoom < firstStep, baseWidth*2 between firstStep and secondStep, baseWidth*4 for zoom > secondStep.
 * @param {number} baseWidth
 * @param {number} firstStep
 * @param {number} secondStep
 * @returns {ExpressionSpecification}
 */
export function buildLineWidthExpression(baseWidth = 4, firstStep = 15, secondStep = 18) {
    return /** @type {ExpressionSpecification} */ (['step', ['zoom'], baseWidth, firstStep, baseWidth * 2, secondStep, baseWidth * 4]);
}

/** @typedef {import('maplibre-gl').LineLayerSpecification} LineLayerSpecification */
/** @typedef {import('maplibre-gl').CircleLayerSpecification} CircleLayerSpecification */
/** @typedef {import('maplibre-gl').FilterSpecification} FilterSpecification */

/**
 * @typedef {Object} BuildMeasureLineLayersOptions
 * @property {string} sourceId - MapLibre source id (e.g. 'locations-source').
 * @property {string} [sourceLayer] - Vector tile source-layer name. Omit for
 *   GeoJSON sources.
 * @property {string} [layerIdPrefix='locations-layer'] - Prefix used to build
 *   layer ids (`<prefix>-<measureType>`, `<prefix>-<measureType>-background`,
 *   `<prefix>-<measureType>-border`).
 * @property {FilterSpecification | null} [filter] - Layer filter:
 *   - omitted (`undefined`): defaults to `['==', ['get', 'measure_type'], measureType]`
 *     (the convention for the vector-tile `locations-source`).
 *   - `null`: no filter at all (useful when the GeoJSON source already only
 *     contains the geometry to render).
 *   - any other value: used as-is.
 * @property {number} [lineWidthFirstStep=15] - Zoom level for the first width step.
 * @property {number} [lineWidthSecondStep=18] - Zoom level for the second width step.
 * @property {number} [opacity] - When set, applies this `line-opacity` to every sub-layer
 *   (border, background, main) — e.g. 0.5 to render draft regulations at 50% transparency.
 *   When omitted, no `line-opacity` is emitted (layers keep their default full opacity).
 */

/**
 * Build the full ordered list of MapLibre line layers for a single measure type.
 * Layers are returned bottom-to-top (border, then background, then main dashed
 * line) — the consumer just needs to call `map.addLayer(...)` on each in order.
 *
 * @param {string} measureType
 * @param {MeasureTypeStyle} style
 * @param {BuildMeasureLineLayersOptions} options
 * @returns {LineLayerSpecification[]}
 */
export function buildMeasureLineLayers(measureType, style, options) {
    const {
        sourceId,
        sourceLayer,
        layerIdPrefix = 'locations-layer',
        filter,
        lineWidthFirstStep = 15,
        lineWidthSecondStep = 18,
        opacity,
    } = options;

    const resolvedFilter = filter === null
        ? undefined
        : (filter ?? /** @type {FilterSpecification} */ (['==', ['get', 'measure_type'], measureType]));
    const lineWidthExpr = buildLineWidthExpression(style.lineWidth, lineWidthFirstStep, lineWidthSecondStep);
    const mainLayout = buildMeasureLineLayout(style);
    // Only emit `line-opacity` when explicitly requested, so the default layers keep their
    // exact previous paint (and existing tests/snapshots stay valid).
    const opacityPaint = opacity === undefined ? {} : { 'line-opacity': opacity };

    /**
     * @param {string} id
     * @param {Record<string, unknown>} layout
     * @param {Record<string, unknown>} paint
     * @returns {LineLayerSpecification}
     */
    const makeLayer = (id, layout, paint) => /** @type {LineLayerSpecification} */ ({
        id,
        type: 'line',
        source: sourceId,
        ...(sourceLayer ? { 'source-layer': sourceLayer } : {}),
        ...(resolvedFilter ? { filter: resolvedFilter } : {}),
        layout,
        paint,
    });

    /** @type {LineLayerSpecification[]} */
    const layers = [];

    if (style.borderColor && style.borderWidth) {
        layers.push(makeLayer(
            `${layerIdPrefix}-${measureType}-border`,
            mainLayout,
            {
                'line-color': style.borderColor,
                'line-width': buildLineWidthExpression(
                    style.lineWidth + 2 * style.borderWidth,
                    lineWidthFirstStep,
                    lineWidthSecondStep,
                ),
                ...opacityPaint,
            },
        ));
    }

    if (style.backgroundColor) {
        layers.push(makeLayer(
            `${layerIdPrefix}-${measureType}-background`,
            buildMeasureBackgroundLineLayout(style),
            {
                'line-color': style.backgroundColor,
                'line-width': lineWidthExpr,
                ...opacityPaint,
            },
        ));
    }

    layers.push(makeLayer(
        `${layerIdPrefix}-${measureType}`,
        mainLayout,
        {
            'line-color': style.color,
            'line-dasharray': style.dasharray,
            'line-width': lineWidthExpr,
            ...opacityPaint,
        },
    ));

    return layers;
}

/**
 * Build the `paint` block for a circle layer that visually matches a measure
 * type's line styling — used for endpoints / vertices of a drawn or previewed
 * geometry. Falls back to the border color when the main color is
 * intentionally light (e.g. `parkingProhibited` is white over an orange
 * border), so the point stays visible.
 *
 * @param {MeasureTypeStyle} style
 * @param {Object} [options]
 * @param {number | ExpressionSpecification} [options.radius=6]
 * @param {number} [options.strokeWidth=2]
 * @param {string} [options.strokeColor='#FFFFFF']
 * @returns {CircleLayerSpecification['paint']}
 */
export function buildMeasurePointPaint(style, options = {}) {
    const {
        radius = 6,
        strokeWidth = 2,
        strokeColor = '#FFFFFF',
    } = options;

    return {
        'circle-radius': radius,
        'circle-color': style.borderColor ?? style.color,
        'circle-stroke-color': strokeColor,
        'circle-stroke-width': strokeWidth,
    };
}
