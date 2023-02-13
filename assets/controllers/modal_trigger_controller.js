import { Controller } from "@hotwired/stimulus";

/**
 * Manage the opening and closing of a modal.
 */
export default class extends Controller {
    static outlets = ['modal'];

    static values = {
        key: String,
    };

    connect() {
        // Enable default DSFR modal behavior.
        this.element.dataset.frOpened = 'false';

        this.modalOutletElement.addEventListener('close', this.#onModalClose);
    }

    modalOutletDisconnected(_controller, modal) {
        modal.removeEventListener('close', this.#onModalClose);
    }

    showModal() {
        this.modalOutlet.setValue(this.keyValue);
        // Default DSFR modal behavior takes care of the rest...
    }

    #onModalClose = () => {
        if (this.modalOutletElement.returnValue === this.keyValue) {
            const event = new CustomEvent('modal-trigger:submit', { bubbles: true });
            this.element.dispatchEvent(event);
        }
    };
}
