// @ts-check

const MAX_VISIBLE_NAMES = 10;

/**
 * Construit le contenu du popup « Zones couvertes » de la carte des statistiques.
 *
 * Le nom de cluster vient de l'API publique (/api/stats) et agrège des noms
 * d'organisations saisis librement par les utilisateurs : le contenu est donc
 * construit avec des nœuds DOM et `textContent`, jamais par interpolation dans
 * une chaîne HTML (sinon XSS stockée via le nom d'une organisation).
 *
 * @param {string} clusterName noms d'organisations séparés par des virgules
 * @param {string} mapLink lien vers la carte des restrictions
 * @returns {HTMLElement}
 */
export function buildClusterPopupContent(clusterName, mapLink) {
    const names = clusterName
        .split(',')
        .map(name => name.trim())
        .filter(name => name.length > 0);

    const visibleNames = names.slice(0, MAX_VISIBLE_NAMES);
    const remainingCount = names.length - visibleNames.length;

    const root = document.createElement('div');
    root.className = 'fr-background-default--grey fr-p-1w fr-text--sm fr-radius-1w';

    const title = document.createElement('p');
    title.className = 'fr-text--bold fr-mb-1v';
    title.textContent = 'Zones couvertes';
    root.appendChild(title);

    const list = document.createElement('ul');
    list.className = 'fr-list fr-mb-0';

    for (const name of visibleNames) {
        const item = document.createElement('li');
        item.textContent = name;
        list.appendChild(item);
    }

    if (remainingCount > 0) {
        const item = document.createElement('li');
        const emphasis = document.createElement('em');
        emphasis.textContent = `… et ${remainingCount} autre${remainingCount > 1 ? 's' : ''}`;
        item.appendChild(emphasis);
        list.appendChild(item);
    }

    root.appendChild(list);

    const linkParagraph = document.createElement('p');
    linkParagraph.className = 'fr-mt-1v fr-mb-0';

    const link = document.createElement('a');
    link.className = 'fr-link fr-link--sm';
    link.href = mapLink;
    link.rel = 'noopener noreferrer';
    link.textContent = 'Voir les restrictions';

    linkParagraph.appendChild(link);
    root.appendChild(linkParagraph);

    return root;
}
