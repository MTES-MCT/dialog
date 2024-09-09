customElements.define('d-auto-form', class extends HTMLElement {
    connectedCallback() {
        const form = this.querySelector('form');

        if (!form) {
            return;
        }

        for (let formControl of form.elements) {
            formControl.addEventListener('change', () => {
                form.requestSubmit();
            });
        }
    }
})
