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
        if (this.hasSectionTarget) {
            this.sectionTarget.hidden = false;
        } else if (this.hasSectionOutlet) {
            this.sectionOutletElement.hidden = false;
        }

        if (this.hasFormControlTarget) {
            this.formControlTarget.disabled = false;
        } else if (this.hasFormControlOutlet) {
            this.formControlOutletElement.disabled = false;
        }

        if (!this.isPermanentButtonValue) {
            this.buttonTarget.hidden = true;
        }
    }

    close() {
        if (this.hasSectionTarget) {
            this.sectionTarget.hidden = true;
        } else if (this.hasSectionOutlet) {
            this.sectionOutletElement.hidden = true;
        }

        if (this.hasFormControlTarget) {
            this.formControlTarget.disabled = true;
        } else if (this.hasFormControlOutlet) {
            this.formControlOutletElement.disabled = true;
        }

        if (!this.isPermanentButtonValue) {
            this.buttonTarget.hidden = false;
        }
    }

    openByValue(event) {
        this.sectionTargets.forEach((element) => {
            if (element.dataset.value === event.target.value) {
                element.hidden = false;
            } else {
                element.hidden = true;
            }
        });
    }
}
