import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ['scroll'];

    constructor(...args) {
        super(...args);

        // Throttle to scroll the first target and ignore the ones further down for a short delay.
        // We do this to show the topmost target in the viewport.
        this._scheduleScroll = throttle((/** @type {HTMLElement} */ element) => {
            console.log(element);
            element.scrollIntoView(true);
        }, 500);
    }

    scrollTargetConnected(element) {
        this._scheduleScroll(element);
    }
}

function throttle(fn, duration) {
    let isThrottled = false;

    return (...args) => {
        if (!isThrottled) {
            fn(...args);

            isThrottled = true;

            setTimeout(() => {
                isThrottled = false;
            }, duration);
        }
    }
}
