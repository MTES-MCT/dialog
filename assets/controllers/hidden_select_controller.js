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

        this.selectTarget.addEventListener('change', () => {
            const selectedOption = this.selectTarget.options[this.selectTarget.selectedIndex];
            this.hiddenTarget.value = selectedOption.dataset['value'];
        });
    }
}
