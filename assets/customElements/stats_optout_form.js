// @ts-check
import { querySelectorOrError } from './util';

customElements.define('d-stats-optout-form', class extends HTMLElement {
    connectedCallback() {
        // https://developer.matomo.org/guides/tracking-javascript-guide#optional-creating-a-custom-opt-out-form

        document.addEventListener('DOMContentLoaded', () => {
            /** @type {HTMLInputElement} */
            const checkbox = querySelectorOrError(this, 'input[type="checkbox"]');
            checkbox.checked = localStorage.getItem('matomo_checkbox') || 'true';
            this._setOptOut(checkbox);

            checkbox.addEventListener('click', () => this.#onCheckboxClick(checkbox));
        });
    }

    /**
     * @param {HTMLInputElement} checkbox 
     */
    #onCheckboxClick = (checkbox) => {
        localStorage.getItem('matomo_checkbox', checkbox.checked);
        if (checkbox.checked) {
            window['_paq'].push(['forgetUserOptOut']);
        } else {
            window['_paq'].push(['optUserOut']);
        }

        this._setOptOut(checkbox);
    };

    /**
     * @param {HTMLInputElement} checkbox 
     */
    _setOptOut(checkbox) {
        window['_paq'].push([function () {
            checkbox.checked = !this.isUserOptedOut();
        }]);
    }
})
