import { Controller } from "@hotwired/stimulus";
import { getAtPath } from '../lib';

/**
 * Compute a boolean conditional and emit events based on the result.
 */
export default class extends Controller {
    static values = {
        equals: String,
        eventAttr: String,
    };

    connect() {
        if (!this.hasEqualsValue) {
            throw new Error('"equals" value is required');
        }
        if (!this.hasEventAttrValue) {
            throw new Error('"event-attr" value is required');
        }
    }

    computeFromEvent(event) {
        const value = getAtPath(event, this.eventAttrValue);
        this.element.dispatchEvent(new CustomEvent(value === this.equalsValue ? 'condition.true' : 'condition.false'));
    }
}
