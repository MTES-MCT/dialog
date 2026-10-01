// @ts-check
// @vitest-environment jsdom
import { describe, it, expect, afterEach } from 'vitest';
import './map_share';

const SEINE_SAINT_DENIS_UUID = '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22';
const DIALOG_UUID = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';

/**
 * @param {string} options
 */
const render = (options) => {
    document.body.innerHTML = `
        <d-map-share carteUrl="https://dialog.test/carte">
            <button type="button" data-share-role="trigger">Partager</button>
            <dialog data-share-role="modal">
                <input data-share-role="linkInput" />
                <button type="button" data-share-role="copyLink">Copier</button>
                <select data-share-role="orgSelect">${options}</select>
                <textarea data-share-role="embedInput"></textarea>
                <button type="button" data-share-role="copyEmbed">Copier</button>
            </dialog>
        </d-map-share>
    `;

    return {
        select: /** @type {HTMLSelectElement} */ (document.querySelector('[data-share-role="orgSelect"]')),
        embedInput: /** @type {HTMLTextAreaElement} */ (document.querySelector('[data-share-role="embedInput"]')),
    };
};

/**
 * @param {string} embedCode
 */
const getIframeUrl = (embedCode) => {
    const container = document.createElement('div');
    container.innerHTML = embedCode;
    return new URL(/** @type {HTMLIFrameElement} */ (container.querySelector('iframe')).src);
};

describe('d-map-share', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('limite la carte intégrée à la collectivité de l’organisation sélectionnée', () => {
        const { embedInput } = render(`
            <option value="${SEINE_SAINT_DENIS_UUID}" data-boundary-parameter="departmentCode" data-boundary-code="93" selected>Seine-Saint-Denis</option>
            <option value="${DIALOG_UUID}">DiaLog</option>
        `);

        const url = getIframeUrl(embedInput.value);

        expect(url.origin + url.pathname).toBe('https://dialog.test/carte');
        expect([...url.searchParams.entries()]).toEqual([
            ['organizationUuid', SEINE_SAINT_DENIS_UUID],
            ['departmentCode', '93'],
            ['embed', '1'],
        ]);
    });

    it('n’ajoute pas de filtre par collectivité quand l’organisation n’a pas de code', () => {
        const { embedInput } = render(`
            <option value="${DIALOG_UUID}" selected>DiaLog</option>
        `);

        const url = getIframeUrl(embedInput.value);

        expect([...url.searchParams.entries()]).toEqual([
            ['organizationUuid', DIALOG_UUID],
            ['embed', '1'],
        ]);
    });

    it('met à jour le code d’intégration quand on change d’organisation', () => {
        const { select, embedInput } = render(`
            <option value="${DIALOG_UUID}" selected>DiaLog</option>
            <option value="${SEINE_SAINT_DENIS_UUID}" data-boundary-parameter="epciCode" data-boundary-code="200054781">Métropole du Grand Paris</option>
        `);

        select.value = SEINE_SAINT_DENIS_UUID;
        select.dispatchEvent(new Event('change'));

        const url = getIframeUrl(embedInput.value);

        expect(url.searchParams.get('organizationUuid')).toBe(SEINE_SAINT_DENIS_UUID);
        expect(url.searchParams.get('epciCode')).toBe('200054781');
        expect(url.searchParams.get('embed')).toBe('1');
    });
});
