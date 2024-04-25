import { Controller } from "@hotwired/stimulus"
import { resetFormControl } from "../lib";

export default class extends Controller {
    static targets = ['element'];

    reset({ params }) {
        this.elementTargets.forEach(el => {
            if (this._shouldReset(params, el)) {
                resetFormControl(el);
            }
        });
    }

    /**
     * @param {Object} params 
     * @param {HTMLElement} el 
     * @returns {Boolean}
     */
    _shouldReset(params, el) {
        if (!params.key) {
            return true;
        }

        if (!el.dataset.resetKeys) {
            return false;
        }

        /** @type {string[]} */
        const keys = (JSON.parse(el.dataset.resetKeys));

        return keys.includes(params.key);
    }
}
