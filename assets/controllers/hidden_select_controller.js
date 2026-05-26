import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['hidden', 'select'];

    connect() {
        if (!this.hasHiddenTarget) {
            throw new Error('hidden target is missing');
        }

        if (!this.hasSelectTarget) {
            throw new Error('select target is missing');
        }

        this.syncHiddenFromSelected = this.syncHiddenFromSelected.bind(this);

        this.selectTarget.addEventListener('change', this.syncHiddenFromSelected);

        // Lorsque les options sont rechargées dynamiquement (turbo-stream),
        // le navigateur sélectionne visuellement la première option mais
        // n'émet pas d'événement `change`. On synchronise donc le champ caché
        // dès qu'on détecte une mutation des options.
        this.observer = new MutationObserver(() => this.syncHiddenFromSelected());
        this.observer.observe(this.selectTarget, { childList: true });
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    syncHiddenFromSelected() {
        const selectedOption = this.selectTarget.options[this.selectTarget.selectedIndex];
        const newValue = selectedOption ? (selectedOption.dataset['value'] ?? '') : '';

        if (this.hiddenTarget.value === newValue) {
            return;
        }

        this.hiddenTarget.value = newValue;
        this.hiddenTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
