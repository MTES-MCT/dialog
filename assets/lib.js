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
