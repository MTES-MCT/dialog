import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Register Turbo event handlers
// See: https://turbo.hotwired.dev/reference/events

document.addEventListener('turbo:frame-missing', (event) => {
    _displayAnyDebugExceptionPage(event);
});

const _displayAnyDebugExceptionPage = (event) => {
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
