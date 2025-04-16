import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        height: { type: Number, default: 300 },
        variables: { type: Array, default: [] }
    }

    async connect() {
        // Créer un conteneur pour l'éditeur
        const editorContainer = document.createElement('div');
        editorContainer.style.height = `${this.heightValue}px`;
        this.element.parentNode.insertBefore(editorContainer, this.element);
        this.element.style.display = 'none';

        // Charger Quill de manière asynchrone
        const { default: Quill } = await import('quill');

        const containerOptions = [
            ['bold', 'italic', 'underline'],
            [{ 'header': [1, 2, 3, false] }],
            [{ 'size': [] }],
            [{ 'align': [] }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'color': [] }, { 'background': [] }]
        ];

        const handlersOptions = {};

        if (this.variablesValue.length > 0) {
             // Credits: https://jsfiddle.net/q7jferw3/ via https://github.com/slab/quill/issues/1817#issuecomment-344079500
            containerOptions.unshift([{ 'variables': this.variablesValue.map(item => item.value) }]);

            handlersOptions['variables'] = function(value) {
                if (value) {
                    const cursorPosition = this.quill.getSelection().index;
                    this.quill.insertText(cursorPosition, value);
                    this.quill.setSelection(cursorPosition + value.length);
                }
            };
        }

        this.quill = new Quill(editorContainer, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: containerOptions,
                    handlers: handlersOptions,
                }
            }
        });

        if (this.variablesValue.length > 0) {
            this.addVariablesPickerHTML();
        }
    }

    addVariablesPickerHTML() {
        // Credits: https://jsfiddle.net/q7jferw3/ via https://github.com/slab/quill/issues/1817#issuecomment-344079500
        const parent = this.element.parentElement;

        // On doit définir manuellement le texte de chaque option affichée dans la dropdown, sinon leur texte est vide
        const variablesPickerItems = parent.querySelectorAll('.ql-variables .ql-picker-item');
        variablesPickerItems.forEach(item => {
            item.textContent = this.variablesValue.find((v) => v.value === item.dataset.value).label;
        });

        // On affiche l'icône dans le label
        const variablesIcon = document.createElement('span');
        variablesIcon.className = 'fr-icon-settings-5-line';
        variablesIcon.style.marginRight = '1rem';
        variablesIcon.setAttribute('aria-hidden', 'true');
        const pickerLabel = parent.querySelector('.ql-variables .ql-picker-label');
        pickerLabel.innerHTML = variablesIcon.outerHTML + pickerLabel.innerHTML;
    }

    disconnect() {
        if (this.quill) {
            this.quill = null;
        }
    }
}
