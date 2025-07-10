// @ts-check
import { getAttributeOrError, querySelectorOrError } from "./util";

customElements.define('d-modal-trigger', class extends HTMLElement {
    /** @type {HTMLDialogElement} */
    #modal;

    /** @type {HTMLButtonElement} */
    #triggerButton;

    /** @type {HTMLButtonElement|null} */
    #submitButton;

    /** @type {string} */
    #submitValue;

    connectedCallback() {
        this.#modal = querySelectorOrError(document, `#${getAttributeOrError(this, 'modal')}`);
        this.#triggerButton = querySelectorOrError(this, 'button');
        this.#submitValue = getAttributeOrError(this, 'submitValue');

        // Le bouton submit est optionnel (présent dans les modals de confirmation)
        try {
            this.#submitButton = querySelectorOrError(this.#modal, 'button[type=submit]');
        } catch (e) {
            this.#submitButton = null;
        }

        this.#init();
    }

    #init() {
        this.#triggerButton.addEventListener('click', (event) => {
            event.preventDefault();

            // S'assurer que la modal est dans le document
            if (!document.body.contains(this.#modal)) {
                document.body.appendChild(this.#modal);
            }

            if (this.#submitButton) {
                this.#submitButton.value = this.#submitValue;
            }
            this.#modal.showModal();
        });

        // Gestion du bouton de fermeture
        const closeButton = this.#modal.querySelector('button.fr-link--close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                this.#modal.close();
            });
        }

        this.#modal.addEventListener('close', () => {
            if (this.#modal.returnValue === this.#submitValue) {
                const event = new CustomEvent('modal-trigger:submit', { bubbles: true });
                this.dispatchEvent(event);
            }
        });
    }
});
