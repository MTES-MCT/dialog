export function querySelectorOrError(root, selector) {
    const el = root.querySelector(selector);

    if (!el) {
        throw new Error(`element ${getRepr(root)}: no element found using selector ${selector}`);
    }

    return el;
}

export function getAttributeOrError(el, name) {
    const value = el.getAttribute(name);

    if (value === null) {     
        throw new Error(`element ${getRepr(el)}: attribute "${name}" is required, but it is not defined or empty`);
    }

    return value;
}

/**
 * @param {Element} el 
 * @returns {string}
 */
function getRepr(el) {
    const tmp = document.createElement('div');
    tmp.appendChild(el);
    const repr = tmp.innerHTML;
    return repr.length < 80 ? repr : repr.substring(0, 80) + ' [...]';
}
