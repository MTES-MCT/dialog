import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox'];

    connect() {
        this.element.addEventListener('click', this.#onClick);
    }

    disconnect() {
        this.element.removeEventListener('click', this.#onClick);
    }

    #onClick = () => {
        this.checkboxTarget.checked = !this.checkboxTarget.checked;
        this.element.setAttribute('aria-checked', this.checkboxTarget.checked ? 'true' : 'false');
    };
}
