import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['label'];
    static values = { text: String };

    async copy() {
        const textToCopy = this.textValue ?? '';
        if (!textToCopy) return;

        try {
            await navigator.clipboard.writeText(textToCopy);
            this.#flash(true);
        } catch (e) {
            this.#flash(false);
        }
    }

    #flash(success) {
        const originalClassName = this.element.className;
        // Toggle color only
        this.element.classList.remove('btn-outline-secondary');
        this.element.classList.add(success ? 'btn-success' : 'btn-danger');
        setTimeout(() => {
            this.element.className = originalClassName;
        }, 1000);
    }
}


