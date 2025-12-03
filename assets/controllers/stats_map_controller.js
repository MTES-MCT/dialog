import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';

// Cache global pour éviter de recharger l'API plusieurs fois
let cachedOrganizationsData = null;

// Configuration des territoires d'outre-mer
const OVERSEAS_TERRITORIES = [
    { name: 'Guadeloupe', center: [-61.55, 16.25], zoom: 8.5 },
    { name: 'Martinique', center: [-61.02, 14.64], zoom: 9 },
    { name: 'Guyane', center: [-53.13, 3.93], zoom: 6 },
    { name: 'La Réunion', center: [55.536, -21.115], zoom: 9.5 },
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
        map.addSource('organizations-source', {
            type: 'geojson',
            data: cachedOrganizationsData,
        });

        map.addLayer({
            id: 'organizations-fill',
            type: 'fill',
            source: 'organizations-source',
            paint: {
                'fill-color': ORGANIZATION_COLOR,
                'fill-opacity': 0.2,
            },
        });

        map.addLayer({
            id: 'organizations-outline',
            type: 'line',
            source: 'organizations-source',
            paint: {
                'line-color': ORGANIZATION_COLOR,
                'line-width': 2,
            },
        });

        this.addHoverEffect(map);
    }

    addHoverEffect(map) {
        map.on('mouseenter', 'organizations-fill', () => {
            map.getCanvas().style.cursor = 'pointer';
        });

        map.on('mouseleave', 'organizations-fill', () => {
            map.getCanvas().style.cursor = '';
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

