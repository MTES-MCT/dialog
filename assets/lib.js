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

// Matomo helpers

export function beginMatomoTracking() {
    console.debug('[Matomo] Started');

    var _paq = window._paq = window._paq || [];

    // Track initial page view
    _paq.push(['setTrackerUrl', 'https://stats.beta.gouv.fr/matomo.php']);
    _paq.push(['setSiteId', '38']);
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);

    document.addEventListener('turbo:load', (event) => {
        const url = new URL(event.detail.url);
        // Track new page views
        // See: https://developer.matomo.org/guides/spa-tracking
        _paq.push(['setCustomUrl', url.pathname + url.search]);
        _paq.push(['setDocumentTitle', document.title]);
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
    });
}

// End Matomo helpers

export function resetFormControl(/** @type {HTMLElement} */ element) {
    // No unified DOM interface exists to reset a form control.
    // So we must implement it on a case-by-case basis in JavaScript.

    if (element instanceof HTMLInputElement) {
        element.value = '';
        return;
    }
    
    if (element instanceof HTMLSelectElement) {
        element.selectedIndex = 0;
        return;
    }
    
    if (element instanceof HTMLFieldSetElement) {
        for (const subElement of element.elements) {
            resetFormControl(subElement);
        }
        return;
    }

    throw new Error(`Reset not implemented for element ${element}`);
}
