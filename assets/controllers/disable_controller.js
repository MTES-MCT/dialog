import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static outlets = ["output"];

    enableOutput() {
        this.outputOutletElements.forEach(el => el.disabled = false);
    }

    disableOutput() {
        this.outputOutletElements.forEach(el => el.disabled = true);
    }
}
