/**
 * Plain JS implementation of `lodash.get`.
 * See: https://youmightnotneed.com/lodash/#get
 * @param {any} obj 
 * @param {string|Array<string>} path 
 * @param {any} defaultValue 
 * @returns {any}
 */
export const getByPath = (obj, path, defaultValue) => {
    if (!path) {
        return undefined;
    }

    const pathArray = Array.isArray(path) ? path : path.match(/([^[.\]])+/g);

    const result = pathArray.reduce(
        (prevObj, key) => prevObj && prevObj[key],
        obj,
    );

    return result === undefined ? defaultValue : result;
};
