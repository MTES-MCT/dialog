import { Controller } from "@hotwired/stimulus";

/**
 * Provide actions to disable targeted elements.
 */
export default class extends Controller {
    static outlets = ['target'];

    disable() {
        this.targetOutletElements.forEach(el => {
            el.disabled = true;
        });
    }

    clear() {
        this.targetOutletElements.forEach(el => {
            el.disabled = false;
        });
    }
}
