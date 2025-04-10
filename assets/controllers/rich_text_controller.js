import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

export default class extends Controller {
    static values = {
        height: { type: Number, default: 300 }
    }

    connect() {
        // Créer un conteneur pour l'éditeur
        const editorContainer = document.createElement('div');
        editorContainer.style.height = `${this.heightValue}px`;
        this.element.parentNode.insertBefore(editorContainer, this.element);
        this.element.style.display = 'none';

        const toolbarOptions = {
            container: [
                ['bold', 'italic', 'underline'],
                [{ 'header': [1, 2, 3, false] }],
                [{ 'size': [] }],
                [{ 'align': [] }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }],
            ]
        };

        this.quill = new Quill(editorContainer, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            }
        });

        // Synchroniser le contenu avec le textarea caché
        this.quill.on('text-change', () => {
            this.element.value = this.quill.root.innerHTML;
        });

        // Initialiser le contenu si présent
        if (this.element.value) {
            this.quill.root.innerHTML = this.element.value;
        }
    }

    disconnect() {
        if (this.quill) {
            this.quill = null;
        }
    }
}
