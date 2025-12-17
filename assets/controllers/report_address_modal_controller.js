import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    updateModalUrl(event) {
        // Empêcher l'ouverture immédiate du modal par d-modal-trigger
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const administratorFieldId = button.dataset.administratorFieldId;
        const roadNumberFieldId = button.dataset.roadNumberFieldId;
        const cityLabelFieldId = button.dataset.cityLabelFieldId;
        const roadNameFieldId = button.dataset.roadNameFieldId;
        const roadBanIdFieldId = button.dataset.roadBanIdFieldId;
        const baseUrl = button.dataset.baseUrl;
        const frameId = button.dataset.frameId;
        const modalId = button.getAttribute('aria-controls');

        if (!baseUrl || !frameId || !modalId) {
            return;
        }

        // Trouver le modal et le turbo-frame à l'intérieur
        const modal = document.querySelector(`#${modalId}`);
        if (!modal) {
            return;
        }

        const frame = modal.querySelector(`turbo-frame#${frameId}`);
        if (!frame) {
            return;
        }

        // Construire l'URL avec les paramètres
        const url = new URL(baseUrl, window.location.origin);

        // Cas 1 : Routes numérotées (administrator + roadNumber)
        if (administratorFieldId && roadNumberFieldId) {
            const administratorField = document.querySelector(`#${administratorFieldId}`);
            const roadNumberField = document.querySelector(`#${roadNumberFieldId}`);

            if (administratorField && roadNumberField) {
                const administratorValue = administratorField.value || '';
                const roadNumberValue = roadNumberField.value || '';

                if (administratorValue) {
                    url.searchParams.set('administrator', administratorValue);
                }
                if (roadNumberValue) {
                    url.searchParams.set('roadNumber', roadNumberValue);
                }
            }
        }

        // Cas 2 : Routes nommées (cityLabel + roadName + roadBanId)
        if (cityLabelFieldId && roadNameFieldId) {
            const cityLabelField = document.querySelector(`#${cityLabelFieldId}`);
            const roadNameField = document.querySelector(`#${roadNameFieldId}`);

            if (cityLabelField && roadNameField) {
                const cityLabelValue = cityLabelField.value || '';
                const roadNameValue = roadNameField.value || '';

                if (cityLabelValue) {
                    url.searchParams.set('cityLabel', cityLabelValue);
                }
                if (roadNameValue) {
                    url.searchParams.set('roadName', roadNameValue);
                }
            }

            // Ajouter le roadBanId s'il est disponible
            if (roadBanIdFieldId) {
                const roadBanIdField = document.querySelector(`#${roadBanIdFieldId}`);
                if (roadBanIdField) {
                    const roadBanIdValue = roadBanIdField.value || '';
                    if (roadBanIdValue) {
                        url.searchParams.set('roadBanId', roadBanIdValue);
                    }
                }
            }
        }

        url.searchParams.set('frameId', frameId);

        // Mettre à jour l'URL du turbo-frame et forcer le rechargement
        const currentSrc = frame.getAttribute('src');
        frame.setAttribute('src', url.toString());

        // Si l'URL a changé, forcer le rechargement du frame
        if (currentSrc !== url.toString()) {
            // Réinitialiser le contenu du frame pour forcer le rechargement
            frame.innerHTML = `
                <div class="fr-text--center fr-py-4w">
                    <div class="fr-mb-2w">
                        <i class="fr-icon-refresh-line fr-icon--lg" aria-hidden="true"></i>
                    </div>
                    <p>Chargement...</p>
                </div>
            `;
        }

        // S'assurer que le modal est dans le document
        if (!document.body.contains(modal)) {
            document.body.appendChild(modal);
        }

        // Ouvrir le modal après la mise à jour de l'URL
        // Utiliser requestAnimationFrame pour s'assurer que le DOM est à jour
        requestAnimationFrame(() => {
            modal.showModal();
        });
    }
}

