import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ['dialog'];

    static values = {
        submit: String,
    }

    connect() {
        this.dialogTarget.addEventListener('close', this.#handleClose);
    }

    #handleClose = () => {
        dsfr(this.dialogTarget).modal.conceal();

        if (this.dialogTarget.returnValue === this.submitValue) {
            this.element.dispatchEvent(new CustomEvent(`dialog-submit:${this.submitValue}`));
        }
    };

    disconnect() {
        this.dialogTarget.removeEventListener('close', this.#handleClose);
    }
}
