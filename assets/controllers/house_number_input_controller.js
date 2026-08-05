import { Controller } from '@hotwired/stimulus';

// Valide un champ libre de numéro de voie contre la liste des numéros connus
// (chargés dynamiquement dans le <datalist> associé via turbo-stream).
// La validation n'est active que lorsque des options sont disponibles.
export default class extends Controller {
    static values = {
        invalidMessage: String,
    };

    connect() {
        this.validate = this.validate.bind(this);
        this.element.addEventListener('input', this.validate);
        this.element.addEventListener('change', this.validate);

        if (this.element.list) {
            this.observer = new MutationObserver(this.validate);
            this.observer.observe(this.element.list, { childList: true });
        }
    }

    disconnect() {
        this.element.removeEventListener('input', this.validate);
        this.element.removeEventListener('change', this.validate);

        if (this.observer) {
            this.observer.disconnect();
        }

        this.#clearError();
    }

    validate() {
        const datalist = this.element.list;
        const value = this.element.value.trim();

        // Pas de validation si le champ est vide ou si aucune option n'est chargée.
        if (!datalist || value === '' || datalist.options.length === 0) {
            this.#clearError();
            return;
        }

        const knownValues = Array.from(datalist.options).map((option) => option.value.toLowerCase());
        const isValid = knownValues.includes(value.toLowerCase());

        if (isValid) {
            this.#clearError();
        } else {
            this.#showError();
        }
    }

    #showError() {
        this.element.setCustomValidity(this.invalidMessageValue);

        const group = this.element.closest('.fr-input-group');

        let message = document.getElementById(this.#messageId());
        if (!message) {
            message = document.createElement('p');
            message.id = this.#messageId();
            message.className = 'fr-message fr-message--error';
            message.setAttribute('aria-live', 'polite');
            (group ?? this.element.parentNode).appendChild(message);
        }
        message.textContent = this.invalidMessageValue;

        this.element.setAttribute('aria-describedby', this.#messageId());
    }

    #clearError() {
        this.element.setCustomValidity('');

        const message = document.getElementById(this.#messageId());
        if (message) {
            message.remove();
        }

        if (this.element.getAttribute('aria-describedby') === this.#messageId()) {
            this.element.removeAttribute('aria-describedby');
        }
    }

    #messageId() {
        return `${this.element.id}-error`;
    }
}
