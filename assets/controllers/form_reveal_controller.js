import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["button", "section", "formControl"];
    static outlets = ["section", "form-control"];

    static values = {
        isPermanentButton: {
            type: Boolean,
            default: true,
        },
    };

    open() {
        this.sectionTargets.forEach(el => el.hidden = false);
        this.sectionOutletElements.forEach(el => el.hidden = false);

        this.formControlTargets.forEach(el => el.disabled = false);
        this.formControlOutletElements.forEach(el => el.disabled = false);

        if (!this.isPermanentButtonValue) {
            this.buttonTarget.hidden = true;
        }
    }

    close() {
        this.sectionTargets.forEach(el => el.hidden = true);
        this.sectionOutletElements.forEach(el => el.hidden = true);

        this.formControlTargets.forEach(el => el.disabled = true);
        this.formControlOutletElements.forEach(el => el.disabled = true);

        if (!this.isPermanentButtonValue) {
            this.buttonTarget.hidden = false;
        }
    }

    openByValue(event) {
        this.sectionTargets.forEach((element) => {
            element.hidden = element.dataset.value !== event.target.value;
        });

        this.formControlTargets.forEach((element) => {
            element.disabled = element.dataset.value !== event.target.value;
        });
    }
}
