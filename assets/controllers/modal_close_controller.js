import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    close() {
        // Chercher la modale parente en remontant depuis le turbo-frame
        let modal = null;

        // Méthode 1 : Chercher la frame et remonter jusqu'au dialog parent
        const frame = this.element.closest('turbo-frame');
        if (frame) {
            modal = frame.closest('dialog');
        }

        // Méthode 2 : Si on est dans window.top, chercher les modales ouvertes
        if (!modal) {
            const targetDocument = window.top && window.top !== window ? window.top.document : document;
            const openModals = targetDocument.querySelectorAll('dialog[open]');
            // Prendre la première modale ouverte trouvée
            if (openModals.length > 0) {
                modal = openModals[0];
            }
        }

        // Fermer la modale si elle a été trouvée
        if (modal && modal instanceof HTMLDialogElement) {
            modal.close();
        }
    }
}

