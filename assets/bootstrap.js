import * as _Turbo from '@hotwired/turbo';
import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// See: https://github.com/hotwired/turbo/issues/294#issuecomment-877842232
document.addEventListener('turbo:before-fetch-request', (event) => {
    event.detail.fetchOptions.headers['X-CSP-Nonce'] = document.querySelector('meta[name="csp-nonce"]').content;
});
document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('script[nonce]').forEach((element) => {
        element.setAttribute('nonce', element.nonce);
    });
});
