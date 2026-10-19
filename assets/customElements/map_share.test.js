// @ts-check
// @vitest-environment jsdom
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import './map_share';

const ORG_A = '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22';
const ORG_B = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';
const START_DATE_PARAM = 'map_filter_form[startDate]';
const TODAY = '2026-09-30';

describe('d-map-share', () => {
    beforeEach(() => {
        // jsdom n'implémente pas toujours l'ouverture des <dialog>.
        HTMLDialogElement.prototype.showModal = function () {
            this.setAttribute('open', '');
        };
    });

    afterEach(() => {
        document.body.innerHTML = '';
        window.history.replaceState(null, '', '/');
    });

    const mount = () => {
        document.body.innerHTML = `
            <d-map-share
                carteUrl="${window.location.origin}/carte"
                startDateParam="${START_DATE_PARAM}"
                defaultStartDate="${TODAY}"
            >
                <button type="button" data-share-role="trigger">Partager</button>
                <dialog data-share-role="modal">
                    <input type="text" data-share-role="linkInput" value="">
                    <button type="button" data-share-role="copyLink"></button>
                    <select data-share-role="orgSelect">
                        <option value="${ORG_A}" selected>Organisation A</option>
                        <option value="${ORG_B}">Organisation B</option>
                    </select>
                    <textarea data-share-role="embedInput"></textarea>
                    <button type="button" data-share-role="copyEmbed"></button>
                </dialog>
            </d-map-share>
        `;
    };

    const openModal = () => {
        /** @type {HTMLButtonElement} */ (document.querySelector('[data-share-role="trigger"]')).click();
    };

    const getOrgSelect = () => /** @type {HTMLSelectElement} */ (document.querySelector('[data-share-role="orgSelect"]'));

    /**
     * @returns {URL} L'URL portée par l'attribut src du code d'intégration
     */
    const getEmbedUrl = () => {
        const code = /** @type {HTMLTextAreaElement} */ (document.querySelector('[data-share-role="embedInput"]')).value;
        // Le code est lu comme du texte, sans jamais être interprété comme du HTML.
        const match = code.match(/^<iframe src="([^"]+)" [^<>]*><\/iframe>$/);
        if (!match) {
            throw new Error(`code d'intégration inattendu : ${code}`);
        }
        return new URL(match[1]);
    };

    it('génère un code d’intégration centré sur l’organisation quand l’URL n’a pas de paramètres', () => {
        window.history.replaceState(null, '', '/carte');
        mount();
        openModal();

        const url = getEmbedUrl();
        expect(url.pathname).toBe('/carte');
        expect([...url.searchParams.entries()]).toEqual([
            ['organizationUuid', ORG_A],
            ['embed', '1'],
        ]);
    });

    it('reprend les filtres de l’URL courante dans le code d’intégration', () => {
        window.history.replaceState(null, '', '/carte');
        mount();

        // Les filtres arrivent dans l'URL après le chargement de la page (cf. d-map-form).
        const filters = new URLSearchParams();
        filters.append('map_filter_form[measureTypes][]', 'noEntry');
        filters.append('map_filter_form[measureTypes][]', 'speedLimitation');
        filters.append('map_filter_form[displayTemporaryRegulations]', 'yes');
        filters.append('map_filter_form[endDate]', '2026-12-31');
        window.history.replaceState(null, '', `/carte?${filters}#mapZoomAndPosition=12/48.9/2.4`);
        openModal();

        const url = getEmbedUrl();
        expect(url.searchParams.getAll('map_filter_form[measureTypes][]')).toEqual(['noEntry', 'speedLimitation']);
        expect(url.searchParams.get('map_filter_form[displayTemporaryRegulations]')).toBe('yes');
        expect(url.searchParams.get('map_filter_form[endDate]')).toBe('2026-12-31');
        expect(url.searchParams.get('organizationUuid')).toBe(ORG_A);
        expect(url.searchParams.get('embed')).toBe('1');
        // La position courante n'est pas reprise : la carte intégrée est centrée sur l'organisation.
        expect(url.hash).toBe('');
    });

    it('conserve les filtres quand on change d’organisation', () => {
        window.history.replaceState(null, '', '/carte?map_filter_form[displayHeavyGoodsVehicles]=yes');
        mount();
        openModal();

        const select = getOrgSelect();
        select.value = ORG_B;
        select.dispatchEvent(new Event('change'));

        const url = getEmbedUrl();
        expect(url.searchParams.get('map_filter_form[displayHeavyGoodsVehicles]')).toBe('yes');
        expect(url.searchParams.getAll('organizationUuid')).toEqual([ORG_B]);
    });

    it('ne fige pas la date de début par défaut dans le code d’intégration', () => {
        window.history.replaceState(
            null,
            '',
            `/carte?map_filter_form[displayTemporaryRegulations]=yes&${START_DATE_PARAM}=${TODAY}`,
        );
        mount();
        openModal();

        const url = getEmbedUrl();
        expect(url.searchParams.get('map_filter_form[displayTemporaryRegulations]')).toBe('yes');
        // Absente, la date de début vaudra « aujourd'hui » à chaque consultation de la carte intégrée.
        expect(url.searchParams.has(START_DATE_PARAM)).toBe(false);
    });

    it('conserve une date de début choisie par l’utilisateur', () => {
        window.history.replaceState(
            null,
            '',
            `/carte?map_filter_form[displayTemporaryRegulations]=yes&${START_DATE_PARAM}=2026-11-15`,
        );
        mount();
        openModal();

        expect(getEmbedUrl().searchParams.get(START_DATE_PARAM)).toBe('2026-11-15');
    });

    it('transmet une date de début vide quand l’utilisateur l’a effacée', () => {
        // Une date effacée est absente de l'URL de la carte (cf. d-map-form).
        window.history.replaceState(null, '', '/carte?map_filter_form[displayTemporaryRegulations]=yes');
        mount();
        openModal();

        expect(getEmbedUrl().searchParams.getAll(START_DATE_PARAM)).toEqual(['']);
    });

    it('remplace le centrage de l’URL courante par l’organisation sélectionnée', () => {
        window.history.replaceState(
            null,
            '',
            `/carte?organizationUuid=${ORG_B}&regulationOrderRecordUuid=e413a47e-5928-4353-a8b2-8b7dda27f9a5`,
        );
        mount();
        openModal();

        const url = getEmbedUrl();
        expect(url.searchParams.getAll('organizationUuid')).toEqual([ORG_A]);
        expect(url.searchParams.has('regulationOrderRecordUuid')).toBe(false);
    });

    it('remplace le centrage sur une commune de l’URL courante par l’organisation sélectionnée', () => {
        window.history.replaceState(null, '', '/carte?insee=93070&map_filter_form[displayTemporaryRegulations]=yes');
        mount();
        openModal();

        const url = getEmbedUrl();
        expect(url.searchParams.has('insee')).toBe(false);
        expect(url.searchParams.getAll('organizationUuid')).toEqual([ORG_A]);
        expect(url.searchParams.get('map_filter_form[displayTemporaryRegulations]')).toBe('yes');
    });
});
