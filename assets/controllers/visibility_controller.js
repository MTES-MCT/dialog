import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["hideable", "this"];

    showTargets() {
        this.hideableTargets.forEach(target => {
            target.hidden = false;
        });
    }

    hideTargets() {
        this.hideableTargets.forEach(target => {
            target.hidden = true;
        });
    }

    hideCurrentTarget(event) {
        event.currentTarget.hidden = true;
    }

    showById(event) {
        const element = document.querySelector(`#${event.params.id}`);

        if (!element) {
            throw new Error(`element #${event.params.id} does not exist`);
        }

        element.hidden = false;
    }
}
