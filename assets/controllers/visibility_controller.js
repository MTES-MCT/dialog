import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["this", "input", "radio", "checkbox"];
    static outlets = ["output"];
    static values = { showIf: String };

    connect() {
        this.update();
    }

    update() {
        if (this.hasRadioTarget) {
            const radio = this.radioTargets.find(radio => radio.checked);
            const value = radio ? radio.value : undefined;
            this.outputOutletElement.hidden = value !== this.showIfValue;
        } else if (this.hasCheckboxTarget) {
            const value = this.checkboxTargets.filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
            this.outputOutletElement.hidden = !value.includes(this.showIfValue);
        } else if (this.hasInputTarget) {
            this.outputOutletElement.hidden = this.inputTarget.value !== this.showIfValue;
        } else {
            return;
        }

        this.dispatch(this.outputOutletElement.hidden ? 'hidden' : 'visible');
    }

    revealOutput() {
        this.outputOutletElement.hidden = false;
    }

    hideThis() {
        this.thisTarget.hidden = true;
    }
}
