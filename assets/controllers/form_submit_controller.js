import { Controller } from "@hotwired/stimulus";

/**
 * Submit a form via a Stimulus action.
 */
export default class extends Controller {
    submit() {
        this.element.requestSubmit();
    }
}
