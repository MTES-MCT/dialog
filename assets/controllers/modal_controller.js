import { Controller } from "@hotwired/stimulus";

/**
 * Manage a modal with a return value.
 */
export default class extends Controller {
    static targets = ['value'];

    connect() {
        this.element.addEventListener('dsfr.conceal', this.#clearValue);
        this.element.addEventListener('close', this.#onClose);
    }

    disconnect() {
        this.element.removeEventListener('dsfr.conceal', this.#clearValue);
        this.element.removeEventListener('close', this.#onClose);
    }

    setValue(value) {
        this.valueTarget.value = value;
    }

    #clearValue = () => {
        this.valueTarget.value = "";
    };

    #onClose = () => {
        // DSFR only conceals the modal automatically on <button aria-controls="<id>" />.
        // For dialog return values (<button value="..." />), nothing happens by default,
        // and we need to conceal the modal manually.
        dsfr(this.element).modal.conceal();
    };
}
