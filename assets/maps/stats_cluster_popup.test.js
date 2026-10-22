// @ts-check
// @vitest-environment jsdom
import { describe, it, expect } from 'vitest';
import { buildClusterPopupContent } from './stats_cluster_popup';

const itemTexts = (el) => [...el.querySelectorAll('li')].map(li => li.textContent);

describe('buildClusterPopupContent', () => {
    it('liste les noms des organisations et le lien vers la carte', () => {
        const el = buildClusterPopupContent(
            'Ville de Paris, Ville de Lyon',
            '/carte#mapZoomAndPosition=8.00/48.85000/2.35000',
        );

        expect(el.querySelector('p')?.textContent).toBe('Zones couvertes');
        expect(itemTexts(el)).toEqual(['Ville de Paris', 'Ville de Lyon']);

        const link = el.querySelector('a');
        expect(link?.getAttribute('href')).toBe('/carte#mapZoomAndPosition=8.00/48.85000/2.35000');
        expect(link?.getAttribute('rel')).toBe('noopener noreferrer');
        expect(link?.textContent).toBe('Voir les restrictions');
    });

    it('ignore les noms vides', () => {
        const el = buildClusterPopupContent(' Ville de Paris ,, , Ville de Lyon', '/carte');

        expect(itemTexts(el)).toEqual(['Ville de Paris', 'Ville de Lyon']);
    });

    it('tronque la liste au-delà de 10 noms', () => {
        const eleven = Array.from({ length: 11 }, (_, i) => `Org ${i + 1}`).join(', ');
        expect(itemTexts(buildClusterPopupContent(eleven, '/carte')).at(-1)).toBe('… et 1 autre');

        const twelve = Array.from({ length: 12 }, (_, i) => `Org ${i + 1}`).join(', ');
        const items = itemTexts(buildClusterPopupContent(twelve, '/carte'));
        expect(items).toHaveLength(11);
        expect(items.at(-1)).toBe('… et 2 autres');
    });

    it('affiche un nom contenant du HTML comme du texte brut (XSS stockée)', () => {
        const payload = '<img src="x" onerror="alert(1)">';
        const el = buildClusterPopupContent(`${payload}, Ville de Lyon`, '/carte');

        expect(el.querySelector('img')).toBeNull();
        expect(itemTexts(el)).toEqual([payload, 'Ville de Lyon']);
    });
});
