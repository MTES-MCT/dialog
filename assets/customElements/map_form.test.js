// @ts-check
// @vitest-environment jsdom
import { describe, it, expect, afterEach } from 'vitest';
import './map_form';

const nextFrame = () => new Promise((resolve) => requestAnimationFrame(() => resolve(undefined)));

describe('d-map-form', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        window.history.replaceState(null, '', '/');
    });

    it('reprend le filtre par collectivité dans l’URL des tuiles sans le dupliquer dans l’URL de la page', async () => {
        window.history.replaceState(null, '', '/carte?departmentCode=93&embed=1');

        document.body.innerHTML = `
            <div id="map"></div>
            <d-map-form target="map" urlAttribute="tilesUrl">
                <form action="/carte/tiles/{z}/{x}/{y}.mvt" method="get">
                    <input type="hidden" name="departmentCode" value="93">
                    <input type="checkbox" name="map_filter_form[measureTypes][]" value="noEntry" checked>
                    <input type="checkbox" name="map_filter_form[displayDrafts]" value="yes">
                </form>
            </d-map-form>
        `;
        await nextFrame();

        const map = /** @type {HTMLElement} */ (document.getElementById('map'));
        const checkbox = /** @type {HTMLInputElement} */ (document.querySelector('[name="map_filter_form[displayDrafts]"]'));

        // Le champ caché, hors formulaire Symfony, est transmis tel quel aux tuiles.
        expect(map.getAttribute('tilesUrl')).toBe(
            `${window.location.origin}/carte/tiles/{z}/{x}/{y}.mvt?departmentCode=93&map_filter_form%5BmeasureTypes%5D%5B%5D=noEntry`,
        );
        // Les paramètres de premier niveau de l'URL de la page sont conservés, sans doublon.
        expect([...new URL(window.location.href).searchParams.entries()]).toEqual([
            ['departmentCode', '93'],
            ['embed', '1'],
            ['map_filter_form[measureTypes][]', 'noEntry'],
        ]);

        // Un changement de filtre conserve la collectivité.
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));

        expect(map.getAttribute('tilesUrl')).toBe(
            `${window.location.origin}/carte/tiles/{z}/{x}/{y}.mvt?departmentCode=93&map_filter_form%5BmeasureTypes%5D%5B%5D=noEntry&map_filter_form%5BdisplayDrafts%5D=yes`,
        );
        expect(new URL(window.location.href).searchParams.getAll('departmentCode')).toEqual(['93']);
    });
});
