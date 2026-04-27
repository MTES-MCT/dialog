import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { addHouseNumbersLayer, addMeasureLineLayer } from '../maps/layers';
import { boundsFromGeoJSON, extractFirstGeometry } from '../maps/geojson';

export default class extends Controller {
    static targets = ['container', 'loader', 'message', 'overlay'];
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
        measureType: { type: String, default: '' },
        interactive: { type: Boolean, default: true },
    };

    #map = null;
    #abortController = null;
    #debounceTimer = null;
    #boundDebouncedLoad = null;
    #boundDismissOverlay = null;

    connect() {
        this.#boundDebouncedLoad = () => this.#debouncedLoad();
        this.#observeFieldChanges();
        this.#tryLoadGeometry();
    }

    disconnect() {
        this.#abortController?.abort();
        clearTimeout(this.#debounceTimer);
        this.#stopListeningForm();
        if (this.#boundDismissOverlay && this.hasOverlayTarget) {
            this.overlayTarget.removeEventListener('click', this.#boundDismissOverlay);
            this.#boundDismissOverlay = null;
        }
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
        const roadBanId = this.#getFieldValue('roadBanId');

        if (!roadBanId) {
            this.#hideMap();
            return;
        }

        const params = new URLSearchParams({ roadType: 'lane', roadBanId });

        this.#setParamIfPresent(params, ['roadName', 'cityCode']);
        this.#setParamIfActive(params, ['fromHouseNumber', 'fromRoadBanId', 'toHouseNumber', 'toRoadBanId', 'direction']);

        this.#fetchAndDisplay(params);
    }

    #loadForNumberedRoad() {
        const administrator = this.#getFieldValue('administrator');
        const roadNumber = this.#getFieldValue('roadNumber');

        if (!administrator || !roadNumber) {
            this.#hideMap();
            return;
        }

        const params = new URLSearchParams({
            roadType: this.roadTypeValue,
            administrator,
            roadNumber,
        });

        this.#setParamIfPresent(params, ['fromPointNumber', 'fromSide', 'fromAbscissa', 'toPointNumber', 'toSide', 'toAbscissa', 'direction']);

        this.#fetchAndDisplay(params);
    }

    #getFieldValue(name) {
        return document.getElementById(this[`${name}FieldValue`])?.value;
    }

    #setParamIfPresent(params, names) {
        for (const name of names) {
            const value = this.#getFieldValue(name);
            if (value) params.set(name, value);
        }
    }

    #setParamIfActive(params, names) {
        for (const name of names) {
            const field = document.getElementById(this[`${name}FieldValue`]);
            if (this.#isFieldActive(field)) params.set(name, field.value);
        }
    }

    #loadForRawGeoJSON() {
        const field = document.getElementById(this.geometryFieldValue);
        const raw = field?.value;

        if (!raw) {
            this.#hideMap();
            return;
        }

        try {
            const geojson = extractFirstGeometry(JSON.parse(raw));

            if (!geojson) {
                this.#hideMap();
                return;
            }

            this.#displayGeometry(geojson);
        } catch {
            this.#hideMap();
        }
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
            dragRotate: false,
            keyboard: false,
            touchPitch: false,
            boxZoom: false,
        });

        this.#map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

        if (!this.interactiveValue && this.hasOverlayTarget) {
            this.overlayTarget.hidden = false;
            this.#boundDismissOverlay = () => this.#dismissOverlay();
            this.overlayTarget.addEventListener('click', this.#boundDismissOverlay);
        }

        this.#map.on('load', () => {
            addHouseNumbersLayer(this.#map);
            this.#addSourceAndLayers(geojson);
            this.#fitBounds(geojson);
        });

        this.#map.on('error', () => {
            this.#hideMap();
        });
    }

    #dismissOverlay() {
        if (this.#boundDismissOverlay && this.hasOverlayTarget) {
            this.overlayTarget.removeEventListener('click', this.#boundDismissOverlay);
            this.overlayTarget.hidden = true;
            this.#boundDismissOverlay = null;
        }
    }

    #addSourceAndLayers(geojson) {
        addMeasureLineLayer(this.#map, {
            sourceId: 'location-preview',
            layerId: 'location-preview-line',
            measureType: this.measureTypeValue,
            data: geojson,
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

    #fitBounds(geojson) {
        const bounds = boundsFromGeoJSON(geojson);

        if (!bounds.isEmpty()) {
            this.#map.fitBounds(bounds, {
                padding: 40,
                maxZoom: 18,
                animate: false,
            });
        }
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
