import { Controller } from "@hotwired/stimulus";

/**
 * Submit a form when it receives a specified event.
 */
export default class extends Controller {
    static values = { eventName: String };

    connect() {
        this.element.addEventListener(this.eventNameValue, this.#submit);
    }

    disconnect() {
        this.element.removeEventListener(this.eventNameValue, this.#submit);
    }

    #submit = () => {
        this.element.requestSubmit();
    };
}
