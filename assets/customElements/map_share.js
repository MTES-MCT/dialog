// @ts-check

import { getAttributeOrError, querySelectorOrError } from './util';

customElements.define('d-map-share', class extends HTMLElement {
    /** @type {string} */
    #carteUrl;

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
        this.#modal.showModal();
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
    }

    #updateEmbed() {
        if (!this.#orgSelect || !this.#embedInput) {
            return;
        }
        const organizationUuid = this.#orgSelect.value;
        const absoluteCarteUrl = new URL(this.#carteUrl, window.location.origin);
        absoluteCarteUrl.searchParams.set('organizationUuid', organizationUuid);
        const src = absoluteCarteUrl.toString();
        this.#embedInput.value = `<iframe src="${src}" width="800" height="600" frameborder="0" title="DiaLog"></iframe>`;
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
        if (feedback) {
            feedback.hidden = false;
            setTimeout(() => { feedback.hidden = true; }, 2000);
        }
    }
});
