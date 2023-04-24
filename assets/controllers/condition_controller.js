import { Controller } from "@hotwired/stimulus";
import { getByPath } from '../lib/object';

/**
 * Compute a boolean conditional and emit events based on the result.
 */
export default class extends Controller {
    static values = {
        equals: String,
        eventTargetPath: String,
    };

    connect() {
        if (!this.hasEqualsValue) {
            throw new Error('Please define an "equals" value');
        }
    }

    compute(event) {
        const value = getByPath(event, `${this.eventTargetPathValue}.dataset.conditionValue`);
        this.element.dispatchEvent(
            new CustomEvent(value === this.equalsValue ? 'condition.true' : 'condition.false')
        );
    }
}
