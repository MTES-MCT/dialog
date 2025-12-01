import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = {
        url: String,
    };

    connect() {
        if (this.urlValue) {
            // Trouver et fermer uniquement la modale ReportAddress
            // Chercher le frame parent ou la modale qui contient la frame avec un ID "report-address-form-frame"
            let modal = null;

            // Méthode 1 : Chercher la frame et remonter jusqu'au dialog parent
            const frame = this.element.closest('turbo-frame');
            if (frame) {
                modal = frame.closest('dialog');
            }

            // Méthode 2 : Si on est dans window.top, chercher les modales avec un ID contenant "report-address-form-modal"
            if (!modal) {
                const targetDocument = window.top && window.top !== window ? window.top.document : document;
                const reportAddressModals = targetDocument.querySelectorAll('dialog[id*="report-address-form-modal"]');
                reportAddressModals.forEach(m => {
                    if (m instanceof HTMLDialogElement && m.hasAttribute('open')) {
                        modal = m;
                    }
                });
            }

            // Fermer la modale si elle a été trouvée
            if (modal && modal instanceof HTMLDialogElement) {
                modal.close();
            }

            // Utiliser requestAnimationFrame pour s'assurer que le DOM est prêt
            requestAnimationFrame(() => {
                if (window.top && window.top !== window) {
                    window.top.location.href = this.urlValue;
                } else {
                    window.location.href = this.urlValue;
                }
            });
        }
    }
}

