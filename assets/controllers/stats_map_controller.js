import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

// Cache global pour éviter de recharger l'API plusieurs fois
let cachedOrganizationsData = null;

// Configuration des territoires d'outre-mer
const OVERSEAS_TERRITORIES = [
    { name: 'Guadeloupe', center: [-61.55, 16.25], zoom: 8.5 },
    { name: 'Martinique', center: [-61.02, 14.64], zoom: 9 },
    { name: 'Guyane', center: [-53.13, 3.93], zoom: 5 },
    { name: 'La Réunion', center: [55.536, -21.115], zoom: 8 },
    { name: 'Mayotte', center: [45.166244, -12.8275], zoom: 10 },
];

// Bounding box de la France métropolitaine
const FRANCE_METRO_BOUNDS = {
    west: -5.5,
    east: 9.5,
    south: 41.0,
    north: 51.5,
};

// Style de carte minimaliste
const MAP_STYLE = 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json';

// Couleur DSFR bleu France
const ORGANIZATION_COLOR = '#000091';

export default class extends Controller {
    static targets = ['mainMap', 'overseasMap'];
    static values = {
        apiUrl: String,
    };

    async connect() {
        if (!cachedOrganizationsData) {
            await this.loadData();
        }
        this.initializeMainMap();
        this.initializeOverseasMaps();
    }

    disconnect() {
        this.mainMap?.remove();
        this.overseasMaps?.forEach(map => map.remove());
    }

    async loadData() {
        const response = await fetch(this.apiUrlValue);
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        cachedOrganizationsData = await response.json();
    }

    initializeMainMap() {
        if (!this.hasMainMapTarget || !cachedOrganizationsData) return;

        this.mainMap = this.createMap(this.mainMapTarget, {
            center: [2.725, 47.16],
            zoom: 5,
            minZoom: 4.33,
        });

        this.mainMap.addControl(new maplibregl.NavigationControl(), 'top-left');

        this.mainMap.on('load', () => {
            this.addOrganizationsLayer(this.mainMap);
            this.fitMapToMetropolitanFrance();
        });
    }

    initializeOverseasMaps() {
        if (!this.hasOverseasMapTarget || !cachedOrganizationsData) return;

        this.overseasMaps = this.overseasMapTargets.map((container, index) => {
            const territory = OVERSEAS_TERRITORIES[index];
            if (!territory) return null;

            const map = this.createMap(container, {
                center: territory.center,
                zoom: territory.zoom,
                minZoom: territory.zoom - 2,
            });

            map.addControl(
                new maplibregl.NavigationControl({ showCompass: false }),
                'top-right'
            );

            map.on('load', () => this.addOrganizationsLayer(map));

            return map;
        }).filter(Boolean);
    }

    createMap(container, options) {
        return new maplibregl.Map({
            container,
            style: MAP_STYLE,
            center: options.center,
            zoom: options.zoom,
            minZoom: options.minZoom,
            maxZoom: 18,
            interactive: true,
            attributionControl: false,
        });
    }

    addOrganizationsLayer(map) {
        const container = map.getContainer();
        if (!container.dataset.statsMapId) {
            container.dataset.statsMapId = `stats-map-${Math.random().toString(36).slice(2)}`;
        }
        const prefix = container.dataset.statsMapId;

        const sourceId = `${prefix}-source`;
        const fillLayerId = `${prefix}-fill`;
        const outlineLayerId = `${prefix}-outline`;

        map.addSource(sourceId, {
            type: 'geojson',
            data: cachedOrganizationsData,
        });

        map.addLayer({
            id: fillLayerId,
            type: 'fill',
            source: sourceId,
            paint: {
                'fill-color': ORGANIZATION_COLOR,
                'fill-opacity': 0.2,
            },
        });

        map.addLayer({
            id: outlineLayerId,
            type: 'line',
            source: sourceId,
            paint: {
                'line-color': ORGANIZATION_COLOR,
                'line-width': 2,
            },
        });

        this.addHoverEffect(map, fillLayerId);
        this.addClusterNamePopup(map, fillLayerId);
    }

    addHoverEffect(map, fillLayerId) {
        map.on('mouseenter', fillLayerId, () => {
            map.getCanvas().style.cursor = 'pointer';
        });

        map.on('mouseleave', fillLayerId, () => {
            map.getCanvas().style.cursor = '';
        });
    }

    addClusterNamePopup(map, fillLayerId) {
        map.on('click', fillLayerId, (event) => {
            if (!event.features || event.features.length === 0) {
                return;
            }

            const properties = event.features[0].properties || {};
            const clusterName = properties.clusterName || '';

            if (!clusterName) {
                return;
            }

            const { lng, lat } = event.lngLat;
            const zoom = Math.max(map.getZoom(), 8).toFixed(2);
            const mapLink = `/carte#mapZoomAndPosition=${zoom}/${lat.toFixed(5)}/${lng.toFixed(5)}`;

            const names = clusterName
                .split(',')
                .map(name => name.trim())
                .filter(name => name.length > 0);

            const MAX_VISIBLE_NAMES = 10;
            const visibleNames = names.slice(0, MAX_VISIBLE_NAMES);
            const totalCount = names.length;
            const remainingCount = totalCount - visibleNames.length;

            const listItems = visibleNames
                .map(name => `<li>${name}</li>`)
                .join('');

            const moreInfo = remainingCount > 0
                ? `<li><em>… et ${remainingCount} autre${remainingCount > 1 ? 's' : ''}</em></li>`
                : '';

            const content = `
                <div class="fr-background-default--grey fr-p-1w fr-text--sm fr-radius-1w">
                    <p class="fr-text--bold fr-mb-1v">Zones couvertes</p>
                    <ul class="fr-list fr-mb-0">
                        ${listItems}
                        ${moreInfo}
                    </ul>
                    <p class="fr-mt-1v fr-mb-0">
                        <a class="fr-link fr-link--sm" href="${mapLink}" rel="noopener noreferrer">
                            Voir les restrictions
                        </a>
                    </p>
                </div>
            `;

            new maplibregl.Popup({
                closeButton: true,
                closeOnClick: true,
                className: 'd-map-popup',
                maxWidth: '320px',
            })
                .setLngLat(event.lngLat)
                .setHTML(content)
                .addTo(map);
        });
    }

    fitMapToMetropolitanFrance() {
        if (!cachedOrganizationsData?.features) return;

        const bounds = new maplibregl.LngLatBounds();

        const isInMetropolitanFrance = (lng, lat) => {
            return lng >= FRANCE_METRO_BOUNDS.west &&
                   lng <= FRANCE_METRO_BOUNDS.east &&
                   lat >= FRANCE_METRO_BOUNDS.south &&
                   lat <= FRANCE_METRO_BOUNDS.north;
        };

        const extendBoundsWithCoordinates = (coordinates) => {
            coordinates.forEach((coord) => {
                const [lng, lat] = coord;
                if (isInMetropolitanFrance(lng, lat)) {
                    bounds.extend(coord);
                }
            });
        };

        cachedOrganizationsData.features.forEach((feature) => {
            const { type, coordinates } = feature.geometry;

            if (type === 'Polygon') {
                extendBoundsWithCoordinates(coordinates[0]);
            } else if (type === 'MultiPolygon') {
                coordinates.forEach(polygon => extendBoundsWithCoordinates(polygon[0]));
            }
        });

        if (!bounds.isEmpty()) {
            this.mainMap.fitBounds(bounds, { padding: 50, maxZoom: 6 });
        }
    }
}

