// @ts-check
// @vitest-environment jsdom
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Application } from '@hotwired/stimulus';
import UnsavedChangesController from './unsaved_changes_controller';

const MESSAGE = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr·e de vouloir quitter la page ?';

const TRACK_EVENT = ['trackEvent', 'formulaire', 'alerte-modifications-non-sauvegardees'];

describe('unsaved_changes_controller', () => {
    /** @type {Application} */
    let application;

    beforeEach(async () => {
        document.body.innerHTML = `
            <form
                data-controller="unsaved-changes"
                data-unsaved-changes-message-value="${MESSAGE}"
                data-unsaved-changes-tracking-name-value="test"
            >
                <input type="text" name="title" value="initial">
            </form>
        `;
        window._paq = [];
        application = Application.start();
        application.register('unsaved-changes', UnsavedChangesController);
        // La connexion des contrôleurs Stimulus est asynchrone.
        await new Promise((resolve) => setTimeout(resolve, 0));
    });

    afterEach(async () => {
        // Vider le DOM déclenche disconnect() sur les contrôleurs (asynchrone) :
        // sans cela, leurs écouteurs sur window et document fuiteraient entre les tests.
        document.body.innerHTML = '';
        await new Promise((resolve) => setTimeout(resolve, 0));
        application.stop();
        vi.restoreAllMocks();
    });

    const getForm = () => /** @type {HTMLFormElement} */ (document.querySelector('form'));

    const getInput = () => /** @type {HTMLInputElement} */ (document.querySelector('input'));

    const dispatchBeforeVisit = () => {
        const event = new CustomEvent('turbo:before-visit', { cancelable: true });
        document.dispatchEvent(event);
        return event;
    };

    const dispatchBeforeUnload = () => {
        const event = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(event);
        return event;
    };

    it('laisse naviguer sans alerte quand le formulaire n’est pas modifié', () => {
        const confirmMock = vi.spyOn(window, 'confirm');

        const event = dispatchBeforeVisit();

        expect(confirmMock).not.toHaveBeenCalled();
        expect(event.defaultPrevented).toBe(false);
        expect(window._paq).toEqual([]);
    });

    it('demande confirmation et trace l’événement Matomo lors d’une navigation Turbo', () => {
        const confirmMock = vi.spyOn(window, 'confirm').mockReturnValue(false);
        getInput().value = 'modifié';

        const event = dispatchBeforeVisit();

        expect(confirmMock).toHaveBeenCalledWith(MESSAGE);
        expect(event.defaultPrevented).toBe(true);
        expect(window._paq).toEqual([[...TRACK_EVENT, 'test:navigation-interne']]);
    });

    it('laisse naviguer si l’utilisateur confirme vouloir quitter la page', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        getInput().value = 'modifié';

        const event = dispatchBeforeVisit();

        expect(event.defaultPrevented).toBe(false);
        expect(window._paq).toEqual([[...TRACK_EVENT, 'test:navigation-interne']]);
    });

    it('bloque la fermeture de la page et trace l’événement Matomo', () => {
        getInput().value = 'modifié';

        const event = dispatchBeforeUnload();

        expect(event.defaultPrevented).toBe(true);
        expect(window._paq).toEqual([[...TRACK_EVENT, 'test:fermeture-page']]);
    });

    it('ne bloque pas la fermeture de la page sans modification', () => {
        const event = dispatchBeforeUnload();

        expect(event.defaultPrevented).toBe(false);
        expect(window._paq).toEqual([]);
    });

    it('désarme l’alerte pendant la soumission du formulaire', () => {
        getInput().value = 'modifié';
        getForm().dispatchEvent(new Event('submit'));

        const event = dispatchBeforeVisit();

        expect(event.defaultPrevented).toBe(false);
        expect(window._paq).toEqual([]);
    });

    it('réarme l’alerte quand la soumission échoue', () => {
        getInput().value = 'modifié';
        getForm().dispatchEvent(new Event('submit'));
        getForm().dispatchEvent(new CustomEvent('turbo:submit-end', { detail: { success: false } }));

        vi.spyOn(window, 'confirm').mockReturnValue(false);
        const event = dispatchBeforeVisit();

        expect(event.defaultPrevented).toBe(true);
    });

    it('ne déclenche qu’une seule alerte quand plusieurs formulaires sont modifiés', async () => {
        document.body.insertAdjacentHTML(
            'beforeend',
            `<form
                data-controller="unsaved-changes"
                data-unsaved-changes-message-value="${MESSAGE}"
                data-unsaved-changes-tracking-name-value="autre"
            >
                <input type="text" name="other" value="initial">
            </form>`,
        );
        await new Promise((resolve) => setTimeout(resolve, 0));
        const confirmMock = vi.spyOn(window, 'confirm').mockReturnValue(true);
        document.querySelectorAll('input').forEach((input) => {
            input.value = 'modifié';
        });

        dispatchBeforeVisit();

        expect(confirmMock).toHaveBeenCalledTimes(1);
        expect(window._paq).toHaveLength(1);
    });
});
