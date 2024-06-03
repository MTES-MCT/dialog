// @ts-check
import { getAttributeOrError, querySelectorOrError } from "./util";

customElements.define('d-modal-trigger', class extends HTMLElement {
    /** @type {HTMLDialogElement} */
    #modal;

    /** @type {HTMLButtonElement} */
    #triggerButton;

    /** @type {HTMLButtonElement} */
    #submitButton;

    /** @type {string} */
    #submitValue;


    connectedCallback() {
        this.#modal = querySelectorOrError(document, `#${getAttributeOrError(this, 'modal')}`);
        this.#triggerButton =  (querySelectorOrError(this, 'button'));
        this.#submitButton = querySelectorOrError(this.#modal, 'button[type=submit]');
        this.#submitValue = getAttributeOrError(this, 'submitValue');
        this.#init();
    }

    #init() {
        this.#triggerButton.addEventListener('click', (event) => {
            event.preventDefault();
            this.#submitButton.value = this.#submitValue;
            this.#modal.showModal();
        });

        this.#modal.addEventListener('close', () => {
            if (this.#modal.returnValue === this.#submitValue) {
                const event = new CustomEvent('modal-trigger:submit', { bubbles: true });
                this.dispatchEvent(event);
            }
        });
    }
});
