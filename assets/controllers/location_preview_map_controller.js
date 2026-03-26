import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';

export default class extends Controller {
    static targets = ['container', 'nearbyStreetsList'];
    static values = {
        url: String,
        roadType: String,
        nearbyStreetsUrl: String,
        roadBanIdField: String,
        roadNameField: String,
        cityCodeField: String,
        fromHouseNumberField: String,
        fromRoadBanIdField: String,
        toHouseNumberField: String,
        toRoadBanIdField: String,
        directionField: String,
        administratorField: String,
        roadNumberField: String,
        geometryField: String,
        strokeColor: { type: String, default: '#000091' },
        strokeWidth: { type: Number, default: 3 },
        fillColor: { type: String, default: '#000091' },
        fillOpacity: { type: Number, default: 0.15 },
    };

    #map = null;
    #abortController = null;
    #debounceTimer = null;
    #boundDebouncedLoad = null;

    connect() {
        this.#boundDebouncedLoad = () => this.#debouncedLoad();
        this.#observeFieldChanges();
        this.#tryLoadGeometry();
    }

    disconnect() {
        this.#abortController?.abort();
        clearTimeout(this.#debounceTimer);
        this.#stopListeningForm();
        this.#map?.remove();
        this.#map = null;
    }

    #observeFieldChanges() {
        // Listen to events bubbling up from any form field within the parent location card
        const locationCard = this.element.closest('[data-controller~="form-reveal"]')
            || this.element.closest('[data-controller~="reset"]')
            || this.element.parentElement;

        if (locationCard) {
            locationCard.addEventListener('input', this.#boundDebouncedLoad);
            locationCard.addEventListener('change', this.#boundDebouncedLoad);
            locationCard.addEventListener('autocomplete.change', this.#boundDebouncedLoad);
            this._locationCard = locationCard;
        }
    }

    #stopListeningForm() {
        if (this._locationCard && this.#boundDebouncedLoad) {
            this._locationCard.removeEventListener('input', this.#boundDebouncedLoad);
            this._locationCard.removeEventListener('change', this.#boundDebouncedLoad);
            this._locationCard.removeEventListener('autocomplete.change', this.#boundDebouncedLoad);
            this._locationCard = null;
        }
    }

    #debouncedLoad() {
        clearTimeout(this.#debounceTimer);
        this.#debounceTimer = setTimeout(() => this.#tryLoadGeometry(), 500);
    }

    #tryLoadGeometry() {
        const roadType = this.roadTypeValue;

        if (roadType === 'lane') {
            this.#loadForNamedStreet();
        } else if (roadType === 'departmentalRoad' || roadType === 'nationalRoad') {
            this.#loadForNumberedRoad();
        } else if (roadType === 'rawGeoJSON') {
            this.#loadForRawGeoJSON();
        }
    }

    #loadForNamedStreet() {
        const field = document.getElementById(this.roadBanIdFieldValue);
        const roadBanId = field?.value;

        if (!roadBanId) {
            this.#hideMap();
            return;
        }

        const params = new URLSearchParams({ roadType: 'lane', roadBanId });

        const roadNameField = document.getElementById(this.roadNameFieldValue);
        const cityCodeField = document.getElementById(this.cityCodeFieldValue);
        const fromHouseNumberField = document.getElementById(this.fromHouseNumberFieldValue);
        const fromRoadBanIdField = document.getElementById(this.fromRoadBanIdFieldValue);
        const toHouseNumberField = document.getElementById(this.toHouseNumberFieldValue);
        const toRoadBanIdField = document.getElementById(this.toRoadBanIdFieldValue);
        const directionField = document.getElementById(this.directionFieldValue);

        if (roadNameField?.value) params.set('roadName', roadNameField.value);
        if (cityCodeField?.value) params.set('cityCode', cityCodeField.value);
        if (fromHouseNumberField?.value) params.set('fromHouseNumber', fromHouseNumberField.value);
        if (fromRoadBanIdField?.value) params.set('fromRoadBanId', fromRoadBanIdField.value);
        if (toHouseNumberField?.value) params.set('toHouseNumber', toHouseNumberField.value);
        if (toRoadBanIdField?.value) params.set('toRoadBanId', toRoadBanIdField.value);
        if (directionField?.value) params.set('direction', directionField.value);

        this.#fetchAndDisplay(params);
    }

    #loadForNumberedRoad() {
        const administratorField = document.getElementById(this.administratorFieldValue);
        const roadNumberField = document.getElementById(this.roadNumberFieldValue);
        const administrator = administratorField?.value;
        const roadNumber = roadNumberField?.value;

        if (!administrator || !roadNumber) {
            this.#hideMap();
            return;
        }

        const params = new URLSearchParams({
            roadType: this.roadTypeValue,
            administrator,
            roadNumber,
        });
        this.#fetchAndDisplay(params);
    }

    #loadForRawGeoJSON() {
        const field = document.getElementById(this.geometryFieldValue);
        const raw = field?.value;

        if (!raw) {
            this.#hideMap();
            return;
        }

        try {
            const geojson = this.#toGeometry(JSON.parse(raw));

            if (!geojson) {
                this.#hideMap();
                return;
            }

            this.#displayGeometry(geojson);
            this.#fetchNearbyStreets(geojson);
        } catch {
            this.#hideMap();
        }
    }

    #toGeometry(parsed) {
        if (!parsed || !parsed.type) {
            return null;
        }

        if (parsed.type === 'FeatureCollection') {
            return parsed.features?.[0]?.geometry || null;
        }

        if (parsed.type === 'Feature') {
            return parsed.geometry || null;
        }

        return parsed;
    }

    async #fetchAndDisplay(params) {
        this.#abortController?.abort();
        this.#abortController = new AbortController();

        const url = `${this.urlValue}?${params.toString()}`;

        try {
            const response = await fetch(url, { signal: this.#abortController.signal });

            if (response.status === 204 || !response.ok) {
                this.#hideMap();
                return;
            }

            const geojson = await response.json();
            this.#displayGeometry(geojson);
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.#hideMap();
            }
        }
    }

    #displayGeometry(geojson) {
        if (!geojson || !geojson.type) {
            this.#hideMap();
            return;
        }

        const hasData = geojson.type === 'GeometryCollection'
            ? geojson.geometries?.length > 0
            : geojson.coordinates?.length > 0;

        if (!hasData) {
            this.#hideMap();
            return;
        }

        const wasHidden = this.containerTarget.hidden;
        this.containerTarget.hidden = false;

        if (this.#map) {
            this.#updateMapData(geojson);
        } else if (wasHidden) {
            // Wait for browser reflow so the container has actual dimensions
            requestAnimationFrame(() => this.#initializeMap(geojson));
        } else {
            this.#initializeMap(geojson);
        }
    }

    #initializeMap(geojson) {
        this.#map = new maplibregl.Map({
            container: this.containerTarget,
            style: mapStyles.desaturated,
            interactive: false,
            attributionControl: false,
        });

        this.#map.on('load', () => {
            this.#addSourceAndLayers(geojson);
            this.#fitBounds(geojson);
        });

        this.#map.on('error', () => {
            this.#hideMap();
        });
    }

    #addSourceAndLayers(geojson) {
        this.#map.addSource('location-preview', {
            type: 'geojson',
            data: geojson,
        });

        const isPolygon = this.#containsPolygon(geojson);

        if (isPolygon) {
            this.#map.addLayer({
                id: 'location-preview-fill',
                type: 'fill',
                source: 'location-preview',
                paint: {
                    'fill-color': this.fillColorValue,
                    'fill-opacity': this.fillOpacityValue,
                },
            });
        }

        this.#map.addLayer({
            id: 'location-preview-line',
            type: 'line',
            source: 'location-preview',
            paint: {
                'line-color': this.strokeColorValue,
                'line-width': this.strokeWidthValue,
            },
        });
    }

    #updateMapData(geojson) {
        const source = this.#map.getSource('location-preview');

        if (source) {
            source.setData(geojson);
        } else if (this.#map.loaded()) {
            this.#addSourceAndLayers(geojson);
        }

        this.#fitBounds(geojson);
    }

    #containsPolygon(geojson) {
        if (geojson.type === 'Polygon' || geojson.type === 'MultiPolygon') {
            return true;
        }

        if (geojson.type === 'GeometryCollection') {
            return geojson.geometries.some(g => g.type === 'Polygon' || g.type === 'MultiPolygon');
        }

        return false;
    }

    #fitBounds(geojson) {
        const bounds = new maplibregl.LngLatBounds();
        this.#extractBounds(geojson, bounds);

        if (!bounds.isEmpty()) {
            this.#map.fitBounds(bounds, {
                padding: 40,
                maxZoom: 15,
                animate: false,
            });
        }
    }

    #extractBounds(geojson, bounds) {
        if (geojson.type === 'GeometryCollection') {
            geojson.geometries.forEach(g => this.#extractBounds(g, bounds));

            return;
        }

        this.#processCoordinates(geojson.coordinates, bounds);
    }

    #processCoordinates(coordinates, bounds) {
        if (!Array.isArray(coordinates) || coordinates.length === 0) {
            return;
        }

        if (typeof coordinates[0] === 'number' && typeof coordinates[1] === 'number') {
            bounds.extend([coordinates[0], coordinates[1]]);
            return;
        }

        coordinates.forEach(coord => {
            if (Array.isArray(coord)) {
                this.#processCoordinates(coord, bounds);
            }
        });
    }

    #hideMap() {
        this.containerTarget.hidden = true;
    }

    async #fetchNearbyStreets(geojson) {
        if (!this.hasNearbyStreetsUrlValue || !geojson) {
            return;
        }

        this.#abortController?.abort();
        this.#abortController = new AbortController();

        try {
            const response = await fetch(this.nearbyStreetsUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ geometry: geojson, radius: 100 }),
                signal: this.#abortController.signal,
            });

            if (!response.ok) {
                return;
            }

            const streets = await response.json();
            this.#displayNearbyStreets(streets);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erreur lors de la récupération des rues proches:', error);
            }
        }
    }

    #displayNearbyStreets(streets) {
        this.#displayNearbyStreetsOnMap(streets);

        if (!this.hasNearbyStreetsListTarget) {
            return;
        }

        this.nearbyStreetsListTarget.innerHTML = '';

        if (streets.length === 0) {
            this.nearbyStreetsListTarget.innerHTML = '<li>Aucune rue trouvée à proximité</li>';

            return;
        }

        streets.forEach(street => {
            const li = document.createElement('li');
            li.textContent = `${street.roadName} (${street.distance} m)`;
            this.nearbyStreetsListTarget.appendChild(li);
        });
    }

    #displayNearbyStreetsOnMap(streets) {
        if (!this.#map) {
            return;
        }

        const features = streets
            .filter(s => s.geometry)
            .map(street => ({
                type: 'Feature',
                geometry: street.geometry,
                properties: {
                    roadName: street.roadName,
                    distance: street.distance,
                },
            }));

        const data = { type: 'FeatureCollection', features };

        if (this.#map.getSource('nearby-streets-source')) {
            this.#map.getSource('nearby-streets-source').setData(data);
        } else {
            this.#map.addSource('nearby-streets-source', { type: 'geojson', data });
            this.#map.addLayer({
                id: 'nearby-streets-layer',
                type: 'line',
                source: 'nearby-streets-source',
                paint: {
                    'line-color': '#e63946',
                    'line-width': 3,
                    'line-opacity': 0.7,
                    'line-dasharray': [2, 2],
                },
            });
        }
    }
}
