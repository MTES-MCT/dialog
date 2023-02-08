/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';

// Configure htmx

// See: https://htmx.org/docs/#modifying_swapping_behavior_with_events
document.body.addEventListener('htmx:beforeSwap', (event) => {
    // By default, htmx does nothing when receiving a 204 No Content response.
    // See: https://htmx.org/docs/#requests
    // We'd like it to interpret those as 'swap with nothing'.
    if (event.detail.xhr.status === 204) {
        event.detail.shouldSwap = true;
    }
});
