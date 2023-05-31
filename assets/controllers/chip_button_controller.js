import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'button'];

    connect() {
        this.buttonTarget.addEventListener('click', this.#onClick);
    }

    disconnect() {
        this.buttonTarget.removeEventListener('click', this.#onClick);
    }

    #onClick = () => {
        this.checkboxTarget.checked = !this.checkboxTarget.checked;
        this.checkboxTarget.dispatchEvent(new Event('change'));
    };
}
