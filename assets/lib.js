// @ts-check
// Turbo helpers

// See: https://turbo.hotwired.dev/reference/events
export function registerTurboEventHandlers() {
    document.addEventListener('turbo:frame-missing', (event) => {
        _displayAnyDebugExceptionPage(event);
    });
}

function _displayAnyDebugExceptionPage(event) {
    /** @type {Response} */
    const response = event.detail.response;

    if (response.status !== 500 || !response.headers.has('X-Debug-Exception')) {
        // Most likely not a Symfony debug error response.
        return;
    }

    event.preventDefault();

    response.text().then(html => {
        document.open();
        document.write(html);
        document.close();
        history.pushState({}, '', response.url);
    });
};

// End Turbo helpers

/**
 * @param {HTMLElement} element 
 * @param {(target: HTMLElement) => void} resetCallback
 * @returns {void}
 */
export function resetFormControl(element, resetCallback = (_target) => null) {
    // No unified DOM interface exists to reset a form control.
    // So we must implement it on a case-by-case basis in JavaScript.

    if (element instanceof HTMLInputElement) {
        element.value = '';
        resetCallback(element);

        return;
    }

    if (element instanceof HTMLSelectElement) {
        element.selectedIndex = 0;
        resetCallback(element);

        return;
    }

    if (element instanceof HTMLFieldSetElement) {
        for (const subElement of element.elements) {
            resetFormControl(subElement, resetCallback);
        }
        resetCallback(element);

        return;
    }

    if (element instanceof HTMLButtonElement) {
        if (element.dataset.resetBehavior === 'click') {
            element.click();
        }
        resetCallback(element);

        return;
    }

    throw new Error(`Reset not implemented for element ${element}`);
}

export function respondToVisibility(element, callback) {
    // Credit: https://stackoverflow.com/a/44670818
    const options = { root: document.documentElement };

    const observer = new IntersectionObserver((entries, _observer) => {
        entries.forEach(entry => {
            callback(entry.intersectionRatio > 0);
        });
    }, options);

    observer.observe(element);
}

export const debounce = (fn, delay = 10) => {
    let timeoutId = null;

    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(fn, delay);
    };
}
