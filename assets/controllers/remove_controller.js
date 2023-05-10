import { Controller } from '@hotwired/stimulus';

/**
 * Remove an element and notify its parent.
 */
export default class extends Controller {
    static targets = ['this'];

    removeElement(_event) {
        const parent = this.thisTarget.parentElement;
        this.thisTarget.remove();
        parent.dispatchEvent(new CustomEvent('remove:child-removed'));
    }
}
