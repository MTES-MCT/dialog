import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["switchable"];

    enableTargets() {
        this.switchableTargets.forEach(target => {
            target.disabled = false;
        })
    }

    disableTargets() {
        this.switchableTargets.forEach(target => {
            target.disabled = true;
        })
    }

    disableById(event) {
        const element = document.querySelector(`#${event.params.id}`);

        if (!element) {
            throw new Error(`element #${event.params.id} does not exist`);
        }

        element.disabled = true;
    }
}
