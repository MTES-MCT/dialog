// @ts-check
// @vitest-environment jsdom
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { Application } from '@hotwired/stimulus';
import OneWayDirectionController from './one_way_direction_controller';

const DIRECTION_SELECT = `
    <select data-one-way-direction-target="direction">
        <option value="BOTH">Double sens</option>
        <option value="A_TO_B">A vers B</option>
        <option value="B_TO_A">B vers A</option>
    </select>
`;

/**
 * @param {string} type - valeur initiale du type de restriction
 * @param {string} locations - HTML des localisations
 */
const formHtml = (type, locations) => `
    <form data-controller="one-way-direction">
        <select id="type" data-one-way-direction-target="type" data-action="change->one-way-direction#refresh">
            <option value="noEntry" ${type === 'noEntry' ? 'selected' : ''}>Circulation interdite</option>
            <option value="oneWay" ${type === 'oneWay' ? 'selected' : ''}>Sens unique</option>
        </select>
        <div id="locations">${locations}</div>
    </form>
`;

describe('one_way_direction_controller', () => {
    /** @type {Application} */
    let application;

    beforeEach(() => {
        application = Application.start();
        application.register('one-way-direction', OneWayDirectionController);
    });

    afterEach(async () => {
        document.body.innerHTML = '';
        await nextTick();
        application.stop();
    });

    const nextTick = () => new Promise((resolve) => setTimeout(resolve, 0));

    /** @param {string} value */
    const selectType = (value) => {
        const type = /** @type {HTMLSelectElement} */ (document.getElementById('type'));
        type.value = value;
        type.dispatchEvent(new Event('change', { bubbles: true }));
    };

    /** @param {ParentNode} [root] */
    const directionSelect = (root = document) => /** @type {HTMLSelectElement} */ (root.querySelector('[data-one-way-direction-target="direction"]'));

    /** @param {HTMLSelectElement} select */
    const bothOption = (select) => /** @type {HTMLOptionElement} */ (select.querySelector('option[value="BOTH"]'));

    it('laisse « Double sens » disponible pour les autres types de restriction', async () => {
        document.body.innerHTML = formHtml('noEntry', DIRECTION_SELECT);
        await nextTick();

        const select = directionSelect();

        expect(select.value).toBe('BOTH');
        expect(bothOption(select).hidden).toBe(false);
        expect(bothOption(select).disabled).toBe(false);
    });

    it('retire « Double sens » et bascule sur « A vers B » quand le type devient sens unique', async () => {
        document.body.innerHTML = formHtml('noEntry', DIRECTION_SELECT);
        await nextTick();

        const select = directionSelect();
        let changes = 0;
        select.addEventListener('change', () => changes++);

        selectType('oneWay');

        expect(select.value).toBe('A_TO_B');
        expect(bothOption(select).hidden).toBe(true);
        expect(bothOption(select).disabled).toBe(true);
        // Les aperçus carte sont prévenus du changement de sens
        expect(changes).toBe(1);
    });

    it('conserve un sens déjà choisi', async () => {
        document.body.innerHTML = formHtml('noEntry', DIRECTION_SELECT);
        await nextTick();

        const select = directionSelect();
        select.value = 'B_TO_A';
        let changes = 0;
        select.addEventListener('change', () => changes++);

        selectType('oneWay');

        expect(select.value).toBe('B_TO_A');
        expect(changes).toBe(0);
    });

    it('rétablit « Double sens » quand on quitte le sens unique sans avoir choisi de sens', async () => {
        document.body.innerHTML = formHtml('oneWay', DIRECTION_SELECT);
        await nextTick();

        const select = directionSelect();
        expect(bothOption(select).disabled).toBe(true);
        expect(select.value).toBe('A_TO_B');

        selectType('noEntry');

        expect(bothOption(select).hidden).toBe(false);
        expect(bothOption(select).disabled).toBe(false);
        // « A vers B » avait été imposé : il ne doit pas restreindre la nouvelle mesure à un sens
        expect(select.value).toBe('BOTH');
    });

    it('garde, en quittant le sens unique, un sens choisi par l\u2019utilisateur', async () => {
        document.body.innerHTML = formHtml('noEntry', DIRECTION_SELECT);
        await nextTick();

        const select = directionSelect();

        // Sens imposé puis modifié par l'utilisateur
        selectType('oneWay');
        select.value = 'B_TO_A';
        selectType('noEntry');
        expect(select.value).toBe('B_TO_A');

        // Sens déjà enregistré (mesure existante) : jamais imposé, donc jamais remis à zéro
        select.value = 'A_TO_B';
        selectType('oneWay');
        selectType('noEntry');
        expect(select.value).toBe('A_TO_B');
    });

    it('applique la règle aux localisations ajoutées après coup', async () => {
        document.body.innerHTML = formHtml('oneWay', '');
        await nextTick();

        /** @type {HTMLElement} */ (document.getElementById('locations')).innerHTML = DIRECTION_SELECT;
        await nextTick();

        const select = directionSelect();

        expect(select.value).toBe('A_TO_B');
        expect(bothOption(select).disabled).toBe(true);
    });

    it('ne touche pas au sens des exceptions', async () => {
        document.body.innerHTML = formHtml('oneWay', `<ul><li class="app-whole-city-exception">${DIRECTION_SELECT}</li></ul>`);
        await nextTick();

        const select = directionSelect();

        expect(select.value).toBe('BOTH');
        expect(bothOption(select).disabled).toBe(false);
    });
});
