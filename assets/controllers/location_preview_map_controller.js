import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';

export default class extends Controller {
    static targets = ['container', 'loader', 'message'];
    static values = {
        url: String,
        roadType: String,
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
        fromPointNumberField: String,
        fromSideField: String,
        fromAbscissaField: String,
        toPointNumberField: String,
        toSideField: String,
        toAbscissaField: String,
        isEntireStreetField: String,
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
        this._watchedElements = [];

        // Listen directly on each known field for input/change events
        // (programmatic events on hidden inputs don't bubble)
        const fieldNames = [
            'roadBanIdField', 'roadNameField', 'cityCodeField',
            'fromHouseNumberField', 'fromRoadBanIdField',
            'toHouseNumberField', 'toRoadBanIdField',
            'administratorField', 'roadNumberField',
            'fromPointNumberField', 'fromSideField', 'fromAbscissaField',
            'toPointNumberField', 'toSideField', 'toAbscissaField',
            'directionField', 'geometryField', 'isEntireStreetField',
        ];

        for (const name of fieldNames) {
            const id = this[`${name}Value`];
            if (!id) continue;
            const el = document.getElementById(id);
            if (!el) continue;
            el.addEventListener('input', this.#boundDebouncedLoad);
            el.addEventListener('change', this.#boundDebouncedLoad);
            this._watchedElements.push(el);
        }

        // Also listen on a parent for autocomplete.change and native bubbling events
        const locationCard = this.element.closest('[data-controller~="reset"]')
            || this.element.closest('[data-controller~="form-reveal"]')
            || this.element.parentElement;

        if (locationCard) {
            locationCard.addEventListener('autocomplete.change', this.#boundDebouncedLoad);
            this._locationCard = locationCard;
        }
    }

    #stopListeningForm() {
        if (this._watchedElements) {
            for (const el of this._watchedElements) {
                el.removeEventListener('input', this.#boundDebouncedLoad);
                el.removeEventListener('change', this.#boundDebouncedLoad);
            }
            this._watchedElements = [];
        }

        if (this._locationCard && this.#boundDebouncedLoad) {
            this._locationCard.removeEventListener('autocomplete.change', this.#boundDebouncedLoad);
            this._locationCard = null;
        }
    }

    #debouncedLoad() {
        clearTimeout(this.#debounceTimer);
        this.#debounceTimer = setTimeout(() => this.#tryLoadGeometry(), 500);
    }

    #tryLoadGeometry() {
        const section = this.element.closest('[data-form-reveal-target="section"]');
        if (section?.hidden) {
            this.#hideMap();
            return;
        }

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
        if (this.#isFieldActive(fromHouseNumberField)) params.set('fromHouseNumber', fromHouseNumberField.value);
        if (this.#isFieldActive(fromRoadBanIdField)) params.set('fromRoadBanId', fromRoadBanIdField.value);
        if (this.#isFieldActive(toHouseNumberField)) params.set('toHouseNumber', toHouseNumberField.value);
        if (this.#isFieldActive(toRoadBanIdField)) params.set('toRoadBanId', toRoadBanIdField.value);
        if (this.#isFieldActive(directionField)) params.set('direction', directionField.value);

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

        const fromPointNumberField = document.getElementById(this.fromPointNumberFieldValue);
        const fromSideField = document.getElementById(this.fromSideFieldValue);
        const fromAbscissaField = document.getElementById(this.fromAbscissaFieldValue);
        const toPointNumberField = document.getElementById(this.toPointNumberFieldValue);
        const toSideField = document.getElementById(this.toSideFieldValue);
        const toAbscissaField = document.getElementById(this.toAbscissaFieldValue);
        const directionField = document.getElementById(this.directionFieldValue);

        if (fromPointNumberField?.value) params.set('fromPointNumber', fromPointNumberField.value);
        if (fromSideField?.value) params.set('fromSide', fromSideField.value);
        if (fromAbscissaField?.value) params.set('fromAbscissa', fromAbscissaField.value);
        if (toPointNumberField?.value) params.set('toPointNumber', toPointNumberField.value);
        if (toSideField?.value) params.set('toSide', toSideField.value);
        if (toAbscissaField?.value) params.set('toAbscissa', toAbscissaField.value);
        if (directionField?.value) params.set('direction', directionField.value);

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

        this.#hideMessage();
        this.#showLoader();

        try {
            const response = await fetch(url, { signal: this.#abortController.signal });

            if (response.status === 204) {
                this.#hideLoader();
                this.#showMessage('Tracé non trouvé');
                return;
            }

            if (response.status === 404) {
                this.#hideLoader();
                this.#showMessage('Adresse non trouvée');
                return;
            }

            if (!response.ok) {
                this.#hideLoader();
                this.#showMessage('Erreur lors du chargement du tracé');
                return;
            }

            const geojson = await response.json();
            this.#hideLoader();
            this.#displayGeometry(geojson);
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.#hideLoader();
                this.#showMessage('Erreur lors du chargement du tracé');
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
            this.#showMessage('Tracé non trouvé');
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
            interactive: true,
            attributionControl: false,
            dragPan: true,
            dragRotate: false,
            keyboard: false,
            touchPitch: false,
            boxZoom: false,
        });

        this.#map.on('load', () => {
            this.#addHouseNumbersLayer();
            this.#addSourceAndLayers(geojson);
            this.#fitBounds(geojson);
        });

        this.#map.on('error', () => {
            this.#hideMap();
        });
    }

    #addHouseNumbersLayer() {
        this.#map.addLayer({
            id: 'house-numbers',
            type: 'symbol',
            source: 'plan_ign',
            'source-layer': 'toponyme_parcellaire_adresse_ponc',
            minzoom: 15,
            filter: ['==', 'txt_typo', 'ADRESSE'],
            layout: {
                'symbol-placement': 'point',
                'text-field': ['concat', ['get', 'numero'], ['get', 'indice_de_repetition']],
                'text-size': {
                    stops: [[15, 9], [17, 11], [18, 13]],
                },
                'text-anchor': 'center',
                'text-font': ['Noto Sans Regular'],
                'text-allow-overlap': false,
            },
            paint: {
                'text-color': '#695744',
                'text-halo-width': 1,
                'text-halo-color': '#FFFFFF',
            },
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
                maxZoom: 18,
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

    #showLoader() {
        if (this.hasLoaderTarget) {
            this.containerTarget.hidden = false;
            this.loaderTarget.hidden = false;
        }
    }

    #isFieldActive(field) {
        return field?.value && !field.closest('[hidden]');
    }

    #hideLoader() {
        if (this.hasLoaderTarget) {
            this.loaderTarget.hidden = true;
        }
    }

    #showMessage(text) {
        if (this.hasMessageTarget) {
            this.containerTarget.hidden = false;
            this.messageTarget.textContent = text;
            this.messageTarget.hidden = false;
        } else {
            this.#hideMap();
        }
    }

    #hideMessage() {
        if (this.hasMessageTarget) {
            this.messageTarget.hidden = true;
        }
    }

    #hideMap() {
        this.#hideMessage();
        this.containerTarget.hidden = true;
    }
}
