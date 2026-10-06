// @ts-check

import { getAttributeOrError, querySelectorOrError } from './util';

customElements.define('d-map-share', class extends HTMLElement {
    /** @type {string} */
    #carteUrl;

    /** @type {string} */
    #startDateParam;

    /** @type {string} */
    #defaultStartDate;

    /** @type {HTMLButtonElement} */
    #trigger;

    /** @type {HTMLDialogElement} */
    #modal;

    /** @type {HTMLInputElement} */
    #linkInput;

    /** @type {HTMLSelectElement|null} */
    #orgSelect;

    /** @type {HTMLTextAreaElement|null} */
    #embedInput;

    connectedCallback() {
        this.#carteUrl = getAttributeOrError(this, 'carteUrl');
        this.#startDateParam = getAttributeOrError(this, 'startDateParam');
        this.#defaultStartDate = getAttributeOrError(this, 'defaultStartDate');
        this.#trigger = querySelectorOrError(this, '[data-share-role="trigger"]');
        this.#modal = querySelectorOrError(this, '[data-share-role="modal"]');
        this.#linkInput = querySelectorOrError(this, '[data-share-role="linkInput"]');
        this.#orgSelect = this.querySelector('[data-share-role="orgSelect"]');
        this.#embedInput = this.querySelector('[data-share-role="embedInput"]');

        // Move the modal to <body> so it isn't clipped by overflow/positioning of the map container.
        if (this.#modal.parentElement !== document.body) {
            document.body.appendChild(this.#modal);
        }

        this.#trigger.addEventListener('click', (event) => {
            event.preventDefault();
            this.#openModal();
        });

        const tabButtons = /** @type {NodeListOf<HTMLButtonElement>} */ (this.#modal.querySelectorAll('[data-share-tab]'));
        tabButtons.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                this.#activateTab(btn.dataset.shareTab || 'link');
            });
        });

        const copyLinkBtn = querySelectorOrError(this.#modal, '[data-share-role="copyLink"]');
        copyLinkBtn.addEventListener('click', () => {
            this.#copy(this.#linkInput.value, 'copyLinkFeedback');
        });

        if (this.#orgSelect && this.#embedInput) {
            this.#orgSelect.addEventListener('change', () => this.#updateEmbed());
            const copyEmbedBtn = querySelectorOrError(this.#modal, '[data-share-role="copyEmbed"]');
            copyEmbedBtn.addEventListener('click', () => {
                if (this.#embedInput) {
                    this.#copy(this.#embedInput.value, 'copyEmbedFeedback');
                }
            });
            this.#updateEmbed();
        }
    }

    #openModal() {
        this.#linkInput.value = window.location.href;
        // Les filtres sont synchronisés dans l'URL au fil des changements (cf. d-map-form) :
        // on régénère le code d'intégration à chaque ouverture pour qu'il reflète l'URL courante.
        this.#updateEmbed();
        this.#modal.showModal();
        requestAnimationFrame(() => this.#updateTabsHeight());
    }

    /** @param {string} name */
    #activateTab(name) {
        const tabs = /** @type {NodeListOf<HTMLButtonElement>} */ (this.#modal.querySelectorAll('[data-share-tab]'));
        const panels = /** @type {NodeListOf<HTMLElement>} */ (this.#modal.querySelectorAll('[data-share-panel]'));
        tabs.forEach((tab) => {
            const isActive = tab.dataset.shareTab === name;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.setAttribute('tabindex', isActive ? '0' : '-1');
        });
        panels.forEach((panel) => {
            const isActive = panel.dataset.sharePanel === name;
            panel.classList.toggle('fr-tabs__panel--selected', isActive);
        });
        requestAnimationFrame(() => this.#updateTabsHeight());
    }

    #updateTabsHeight() {
        const tabsEl = /** @type {HTMLElement|null} */ (this.#modal.querySelector('.fr-tabs'));
        const selectedPanel = /** @type {HTMLElement|null} */ (this.#modal.querySelector('.fr-tabs__panel--selected'));
        const tabsList = /** @type {HTMLElement|null} */ (this.#modal.querySelector('.fr-tabs__list'));
        if (!tabsEl || !selectedPanel || !tabsList) return;
        tabsEl.style.setProperty('--tabs-height', `${tabsList.offsetHeight + selectedPanel.scrollHeight}px`);
    }

    #updateEmbed() {
        if (!this.#orgSelect || !this.#embedInput) {
            return;
        }
        const organizationUuid = this.#orgSelect.value;
        const absoluteCarteUrl = new URL(this.#carteUrl, window.location.origin);
        const params = absoluteCarteUrl.searchParams;
        // Reprend les paramètres de l'URL courante (filtres de la carte) dans l'URL de l'iframe.
        for (const [key, value] of new URLSearchParams(window.location.search)) {
            params.append(key, value);
        }
        this.#unfreezeDefaultStartDate(params);
        // La carte intégrée est centrée sur l'organisation sélectionnée : le zoom sur un arrêté
        // ou sur une commune, prioritaires côté serveur, ne doivent pas prendre le dessus.
        params.delete('regulationOrderRecordUuid');
        params.delete('insee');
        params.set('organizationUuid', organizationUuid);
        params.set('embed', '1');
        const src = absoluteCarteUrl.toString();
        this.#embedInput.value = `<iframe src="${src}" width="1280" height="600" frameborder="0" title="DiaLog"></iframe>`;
    }

    /**
     * La date de début par défaut (aujourd'hui) n'est pas figée dans le code d'intégration : en son
     * absence, la carte intégrée démarre au jour de la consultation. Une date vidée par l'utilisateur
     * est transmise vide pour ne pas être confondue avec ce cas.
     * @param {URLSearchParams} params
     */
    #unfreezeDefaultStartDate(params) {
        const filterPrefix = this.#startDateParam.slice(0, this.#startDateParam.indexOf('[') + 1);
        const hasFilters = [...params.keys()].some((key) => key.startsWith(filterPrefix));

        if (params.get(this.#startDateParam) === this.#defaultStartDate) {
            params.delete(this.#startDateParam);
        } else if (hasFilters && !params.has(this.#startDateParam)) {
            params.set(this.#startDateParam, '');
        }
    }

    /**
     * @param {string} text
     * @param {string} feedbackRole
     */
    async #copy(text, feedbackRole) {
        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            // fallback: select the text so the user can copy manually
        }
        const feedback = /** @type {HTMLElement|null} */ (this.#modal.querySelector(`[data-share-role="${feedbackRole}"]`));
        const labelRole = feedbackRole.replace('Feedback', 'Label');
        const label = /** @type {HTMLElement|null} */ (this.#modal.querySelector(`[data-share-role="${labelRole}"]`));
        if (feedback) {
            if (label) label.hidden = true;
            feedback.hidden = false;
            setTimeout(() => {
                feedback.hidden = true;
                if (label) label.hidden = false;
            }, 2000);
        }
    }
});
