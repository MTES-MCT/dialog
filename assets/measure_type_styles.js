// @ts-check

/**
 * @typedef {Object} MeasureTypeStyle
 * @property {string} color
 * @property {number[]} dasharray
 * @property {number} lineWidth
 */

/** @typedef {import('@maplibre/maplibre-gl-style-spec').ExpressionSpecification} ExpressionSpecification */

/** @type {Record<string, MeasureTypeStyle>} */
export const MEASURE_TYPE_STYLES = {
    noEntry:            { color: '#CE0500', dasharray: [1, 0], lineWidth: 3 }, // red — priorité 1
    speedLimitation:    { color: '#f6c43c', dasharray: [1, 0], lineWidth: 3 }, // yellow — priorité 2
    parkingProhibited:  { color: '#000000', dasharray: [1, 0], lineWidth: 3 }, // black — priorité 3
    alternateRoad:      { color: '#6A6AF4', dasharray: [3, 2], lineWidth: 3 }, // purple — priorité 4
};

export const DEFAULT_MEASURE_STYLE = { color: '#000000', dasharray: [1, 0], lineWidth: 3 };

/**
 * @param {string} measureType
 * @returns {MeasureTypeStyle}
 */
export function getMeasureTypeStyle(measureType) {
    return MEASURE_TYPE_STYLES[measureType] ?? DEFAULT_MEASURE_STYLE;
}

/**
 * Build a MapLibre 'case' expression for line-color based on measure_type feature property.
 * @returns {ExpressionSpecification}
 */
export function buildLineColorExpression() {
    /** @type {(string | ExpressionSpecification)[]} */
    const expr = ['case'];
    for (const [type, style] of Object.entries(MEASURE_TYPE_STYLES)) {
        expr.push(['==', ['get', 'measure_type'], type], style.color);
    }
    expr.push(DEFAULT_MEASURE_STYLE.color);
    return /** @type {ExpressionSpecification} */ (expr);
}

/**
 * Build a MapLibre 'case' expression for line-dasharray based on measure_type feature property.
 * @returns {ExpressionSpecification}
 */
export function buildLineDasharrayExpression() {
    /** @type {(string | number[] | ExpressionSpecification)[]} */
    const expr = ['case'];
    for (const [type, style] of Object.entries(MEASURE_TYPE_STYLES)) {
        if (style.dasharray[0] !== 1 || style.dasharray[1] !== 0) {
            expr.push(['==', ['get', 'measure_type'], type], ['literal', style.dasharray]);
        }
    }
    expr.push(['literal', DEFAULT_MEASURE_STYLE.dasharray]);
    return /** @type {ExpressionSpecification} */ (expr);
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
