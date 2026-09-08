// @ts-check
// @vitest-environment jsdom
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Application } from '@hotwired/stimulus';
import MatomoEventController from './matomo_event_controller';

describe('matomo_event_controller', () => {
    /** @type {Application} */
    let application;

    beforeEach(async () => {
        window._paq = [];
        application = Application.start();
        application.register('matomo-event', MatomoEventController);
    });

    afterEach(async () => {
        document.body.innerHTML = '';
        await new Promise((resolve) => setTimeout(resolve, 0));
        application.stop();
        vi.restoreAllMocks();
    });

    const connect = async () => {
        await new Promise((resolve) => setTimeout(resolve, 0));
    };

    it('trace un événement avec catégorie, action et nom statique au clic', async () => {
        document.body.innerHTML = `
            <button
                data-controller="matomo-event"
                data-action="click->matomo-event#track"
                data-matomo-event-category-value="Arrêté"
                data-matomo-event-action-value="Publication"
                data-matomo-event-name-value="FOO-2024"
            >Publier</button>
        `;
        await connect();

        document.querySelector('button').click();

        expect(window._paq).toEqual([['trackEvent', 'Arrêté', 'Publication', 'FOO-2024']]);
    });

    it('omet le nom quand il est absent', async () => {
        document.body.innerHTML = `
            <button
                data-controller="matomo-event"
                data-action="click->matomo-event#track"
                data-matomo-event-category-value="Arrêté"
                data-matomo-event-action-value="Duplication"
            >Dupliquer</button>
        `;
        await connect();

        document.querySelector('button').click();

        expect(window._paq).toEqual([['trackEvent', 'Arrêté', 'Duplication', undefined]]);
    });

    it('trace le libellé de l’option sélectionnée d’un select via trackValue', async () => {
        document.body.innerHTML = `
            <div
                data-controller="matomo-event"
                data-action="change->matomo-event#trackValue"
                data-matomo-event-category-value="Arrêté"
                data-matomo-event-action-value="Sélection type de localisation"
            >
                <select>
                    <option value="">--</option>
                    <option value="wholeCity">Ville entière</option>
                    <option value="zone">Zone</option>
                </select>
            </div>
        `;
        await connect();

        const select = /** @type {HTMLSelectElement} */ (document.querySelector('select'));
        select.value = 'zone';
        select.dispatchEvent(new Event('change', { bubbles: true }));

        expect(window._paq).toEqual([
            ['trackEvent', 'Arrêté', 'Sélection type de localisation', 'Zone'],
        ]);
    });

    it('ne trace rien quand _paq est absent', async () => {
        delete window._paq;
        document.body.innerHTML = `
            <button
                data-controller="matomo-event"
                data-action="click->matomo-event#track"
                data-matomo-event-category-value="Arrêté"
                data-matomo-event-action-value="Publication"
            >Publier</button>
        `;
        await connect();

        expect(() => document.querySelector('button').click()).not.toThrow();
        expect(window._paq).toBeUndefined();
    });

    it('ne trace rien quand la catégorie ou l’action manque', async () => {
        document.body.innerHTML = `
            <button
                data-controller="matomo-event"
                data-action="click->matomo-event#track"
                data-matomo-event-category-value="Arrêté"
            >Publier</button>
        `;
        await connect();

        document.querySelector('button').click();

        expect(window._paq).toEqual([]);
    });
});
