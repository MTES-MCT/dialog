import { Controller } from "@hotwired/stimulus";

/**
 * jsdom does not provide any implementation for <form /> submission
 * by default, and would error if a form is submitted without an implementation.
 * Use this to provide a no-op (do nothing) implementation.
 */
export default class extends Controller {
    connect() {
        this.element.addEventListener('submit', this.#onSubmit);
    }

    disconnect() {
        this.element.removeEventListener('submit', this.#onSubmit);
    }

    #onSubmit = (event) => {
        event.preventDefault();
    }
}
