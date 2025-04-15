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

        const toolbarOptions = {
            container: [
                ['bold', 'italic', 'underline'],
                [{ 'header': [1, 2, 3, false] }],
                [{ 'size': [] }],
                [{ 'align': [] }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }]
            ]
        };

        this.quill = new Quill(editorContainer, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            }
        });

        // Ajouter le bouton de variables seulement si des variables sont définies
        if (this.variablesValue.length > 0) {
            this.addVariablesButton();
        }

        // Synchroniser le contenu avec le textarea caché
        this.quill.on('text-change', () => {
            this.element.value = this.quill.root.innerHTML;
        });

        // Initialiser le contenu si présent
        if (this.element.value) {
            this.quill.root.innerHTML = this.element.value;
        }
    }

    addVariablesButton() {
        // Ajouter le bouton de variables
        const variablesButton = document.createElement('button');
        variablesButton.className = 'ql-variable-button';
        variablesButton.innerHTML = '<span class="fr-icon-settings-5-line" aria-hidden="true"></span>';
        variablesButton.title = 'Insérer une variable';
        variablesButton.style.cssText = `
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            cursor: pointer;
            margin: 0 2px;
        `;

        // Créer le menu déroulant des variables
        const variablesDropdown = document.createElement('div');
        variablesDropdown.className = 'ql-variables-dropdown';
        variablesDropdown.style.cssText = `
            display: none;
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            padding: 3px;
            z-index: 1000;
            min-width: 200px;
        `;

        // Variable pour stocker la position du curseur
        let savedCursorPosition = null;

        this.variablesValue.forEach(variable => {
            const option = document.createElement('div');
            option.className = 'ql-variable-option';
            option.style.cssText = `
                padding: 6px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
            `;
            option.textContent = variable.label;
            option.onclick = () => {
                const insertPosition = savedCursorPosition ?? (this.quill.getSelection()?.index ?? this.quill.getLength());
                this.quill.insertText(insertPosition, variable.value);
                this.quill.setSelection(insertPosition + variable.value.length, 0);
                variablesDropdown.style.display = 'none';
                savedCursorPosition = null;
            };
            variablesDropdown.appendChild(option);
        });

        const toolbar = this.quill.getModule('toolbar').container;
        toolbar.appendChild(variablesButton);
        toolbar.appendChild(variablesDropdown);

        const updateDropdownPosition = () => {
            if (variablesDropdown.style.display === 'block') {
                const rect = variablesButton.getBoundingClientRect();
                const toolbarRect = toolbar.getBoundingClientRect();

                if (rect && toolbarRect) {
                    variablesDropdown.style.top = `${rect.bottom - toolbarRect.top}px`;
                    variablesDropdown.style.left = `${rect.left - toolbarRect.left}px`;
                } else {
                    variablesDropdown.style.display = 'none';
                }
            }
        };

        // Gestionnaire d'événements pour le clic sur le bouton
        variablesButton.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            savedCursorPosition = this.quill.getSelection()?.index ?? this.quill.getLength();
            variablesDropdown.style.display = variablesDropdown.style.display === 'none' ? 'block' : 'none';
            if (variablesDropdown.style.display === 'block') {
                requestAnimationFrame(updateDropdownPosition);
            }
        };

        // Gestionnaire d'événements pour le clic en dehors
        document.addEventListener('click', (e) => {
            if (!variablesDropdown.contains(e.target) && e.target !== variablesButton) {
                variablesDropdown.style.display = 'none';
                savedCursorPosition = null;
            }
        });

        window.addEventListener('scroll', updateDropdownPosition, true);
        this.quill.root.addEventListener('scroll', updateDropdownPosition);

        this.element.addEventListener('disconnect', () => {
            window.removeEventListener('scroll', updateDropdownPosition, true);
            this.quill.root.removeEventListener('scroll', updateDropdownPosition);
        });
    }

    disconnect() {
        if (this.quill) {
            this.quill = null;
        }
    }
}
