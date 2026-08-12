import { Controller } from '@hotwired/stimulus';

// Un même événement (beforeunload ou turbo:before-visit) est reçu par toutes les
// instances de ce contrôleur présentes sur la page (plusieurs formulaires ouverts) :
// ce registre garantit une seule alerte et un seul événement Matomo par déclenchement.
const handledEvents = new WeakSet();

/**
 * À poser sur un élément <form> : avertit l'utilisateur qui quitte la page
 * (fermeture d'onglet ou navigation Turbo) alors que le formulaire comporte
 * des modifications non sauvegardées, et trace chaque déclenchement de
 * l'alerte dans Matomo.
 */
export default class extends Controller {
    static values = {
        message: String,
        trackingName: String,
    };

    #initialState = null;
    #submitting = false;

    connect() {
        this.#initialState = this.#serialize();
        window.addEventListener('beforeunload', this.#onBeforeUnload);
        document.addEventListener('turbo:before-visit', this.#onBeforeVisit);
        this.element.addEventListener('submit', this.#onSubmit);
        this.element.addEventListener('turbo:submit-end', this.#onSubmitEnd);
    }

    disconnect() {
        window.removeEventListener('beforeunload', this.#onBeforeUnload);
        document.removeEventListener('turbo:before-visit', this.#onBeforeVisit);
        this.element.removeEventListener('submit', this.#onSubmit);
        this.element.removeEventListener('turbo:submit-end', this.#onSubmitEnd);
    }

    // Fermeture de l'onglet, rechargement ou navigation vers un autre site :
    // le navigateur affiche sa propre boîte de dialogue (le message personnalisé
    // n'est plus supporté par les navigateurs modernes).
    #onBeforeUnload = (event) => {
        if (handledEvents.has(event) || !this.#hasUnsavedChanges()) {
            return;
        }

        handledEvents.add(event);
        this.#track('fermeture-page');
        event.preventDefault();
        // Requis par certains navigateurs pour afficher la boîte de dialogue.
        event.returnValue = '';
    };

    // Navigation interne interceptée par Turbo Drive : beforeunload ne se
    // déclenche pas, on affiche donc une confirmation équivalente.
    #onBeforeVisit = (event) => {
        if (handledEvents.has(event) || !this.#hasUnsavedChanges()) {
            return;
        }

        handledEvents.add(event);
        this.#track('navigation-interne');

        if (!window.confirm(this.messageValue)) {
            event.preventDefault();
        }
    };

    // Une soumission en cours est une sortie légitime : on désarme l'alerte.
    #onSubmit = () => {
        this.#submitting = true;
    };

    // Soumission échouée sans re-rendu du formulaire (erreur réseau…) :
    // les modifications sont toujours non sauvegardées, on réarme l'alerte.
    #onSubmitEnd = (event) => {
        if (!event.detail.success) {
            this.#submitting = false;
        }
    };

    #hasUnsavedChanges() {
        return !this.#submitting && this.#serialize() !== this.#initialState;
    }

    #serialize() {
        const entries = [...new FormData(this.element).entries()]
            .map(([key, value]) => [key, value instanceof File ? `${value.name}:${value.size}` : value]);

        return JSON.stringify(entries);
    }

    #track(trigger) {
        window._paq = window._paq || [];
        window._paq.push(['trackEvent', 'formulaire', 'alerte-modifications-non-sauvegardees', `${this.trackingNameValue}:${trigger}`]);
    }
}
