import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { addHouseNumbersLayer, addMeasureLineLayer, addReferencePointsLayer } from '../maps/layers';
import { boundsFromGeoJSON, extractFirstGeometry, toFeatureCollection } from '../maps/geojson';

export default class extends Controller {
    static targets = ['container', 'loader', 'message'];
    static values = {
        url: String,
        referencePointsUrl: String,
        referencePointRoads: Array,
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
        geometryFields: Array,
        measureType: { type: String, default: '' },
    };

    #map = null;
    #abortController = null;
    #referencePointsAbortController = null;
    #referencePointsData = null;
    #referencePointsKey = null;
    #debounceTimer = null;
    #boundDebouncedLoad = null;

    connect() {
        this.#boundDebouncedLoad = () => this.#debouncedLoad();
        this.#observeFieldChanges();
        this.#tryLoadGeometry();
    }

    disconnect() {
        this.#abortController?.abort();
        this.#referencePointsAbortController?.abort();
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
        } else if (roadType === 'wholeCity') {
            this.#loadForWholeCity();
        }
    }

    #loadForWholeCity() {
        const cityCode = this.#getFieldValue('cityCode');

        if (!cityCode) {
            this.#hideMap();
            return;
        }

        const params = new URLSearchParams({ roadType: 'wholeCity', cityCode });

        // Les voies en exception (identifiant BAN) sont exclues dans l'aperçu. Les sections sont
        // approximées par l'exclusion de la voie entière ; la soustraction exacte (sections,
        // tracé libre) reste faite côté serveur à l'enregistrement.
        const excluded = this.#collectExceptionRoadBanIds();
        if (excluded.length) {
            params.set('excludedRoadBanIds', excluded.join(','));
        }

        this.#fetchAndDisplay(params);
    }

    #collectExceptionRoadBanIds() {
        const section = this.element.closest('[data-form-reveal-target="section"]')
            || this.element.closest('[data-controller~="reset"]');

        if (!section) return [];

        return Array.from(section.querySelectorAll('[data-whole-city-exception-road-ban-id]'))
            .map(el => el.value)
            .filter(Boolean);
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

        this.#loadReferencePoints(administrator, roadNumber);
        this.#fetchAndDisplay(params);
    }

    async #loadReferencePoints(administrator, roadNumber) {
        await this.#loadReferencePointsForRoads([{ administrator, roadNumber }]);
    }

    async #loadReferencePointsForRoads(roads) {
        if (!this.referencePointsUrlValue) {
            return;
        }

        const seen = new Set();
        const uniqueRoads = [];

        for (const road of roads || []) {
            if (!road?.administrator || !road?.roadNumber) continue;
            const roadKey = `${road.administrator}|${road.roadNumber}`;
            if (seen.has(roadKey)) continue;
            seen.add(roadKey);
            uniqueRoads.push(road);
        }

        const key = [...seen].sort().join(',');

        if (key === this.#referencePointsKey) {
            return;
        }

        this.#referencePointsKey = key;
        this.#referencePointsAbortController?.abort();

        if (uniqueRoads.length === 0) {
            this.#referencePointsData = null;
            this.#applyReferencePoints();
            return;
        }

        this.#referencePointsAbortController = new AbortController();
        const signal = this.#referencePointsAbortController.signal;

        try {
            const featureLists = await Promise.all(uniqueRoads.map(async ({ administrator, roadNumber }) => {
                const params = new URLSearchParams({ administrator, roadNumber });
                const response = await fetch(`${this.referencePointsUrlValue}?${params.toString()}`, { signal });

                if (!response.ok || response.status === 204) {
                    return [];
                }

                const geojson = await response.json();
                return geojson?.features ?? [];
            }));

            this.#referencePointsData = { type: 'FeatureCollection', features: featureLists.flat() };
            this.#applyReferencePoints();
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.#referencePointsData = null;
                this.#applyReferencePoints();
            }
        }
    }

    #applyReferencePoints() {
        if (!this.#map) {
            return;
        }

        if (!this.#map.isStyleLoaded()) {
            this.#map.once('idle', () => this.#applyReferencePoints());
            return;
        }

        const data = toFeatureCollection(this.#referencePointsData);
        const source = this.#map.getSource('reference-points');

        if (source) {
            source.setData(data);
        } else {
            addReferencePointsLayer(this.#map, {
                sourceId: 'reference-points',
                circleLayerId: 'reference-points-circle',
                labelLayerId: 'reference-points-label',
                data,
            });
        }
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
        const fieldIds = this.geometryFieldsValue?.length
            ? this.geometryFieldsValue
            : (this.geometryFieldValue ? [this.geometryFieldValue] : []);

        if (fieldIds.length === 0) {
            this.#hideMap();
            return;
        }

        const geometries = [];

        for (const id of fieldIds) {
            const raw = document.getElementById(id)?.value;

            if (!raw) continue;

            try {
                const geometry = extractFirstGeometry(JSON.parse(raw));
                if (geometry) geometries.push(geometry);
            } catch {
                // Ignore invalid entries; keep displaying the rest.
            }
        }

        if (geometries.length === 0) {
            this.#hideMap();
            return;
        }

        const geojson = geometries.length === 1
            ? geometries[0]
            : { type: 'GeometryCollection', geometries };

        if (this.referencePointRoadsValue?.length) {
            this.#loadReferencePointsForRoads(this.referencePointRoadsValue);
        }

        this.#displayGeometry(geojson);
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

        this.#map.on('load', () => {
            addHouseNumbersLayer(this.#map);
            this.#addSourceAndLayers(geojson);
            this.#applyReferencePoints();
            this.#fitBounds(geojson);
        });

        this.#map.on('error', () => {
            this.#hideMap();
        });
    }

    #addSourceAndLayers(geojson) {
        addMeasureLineLayer(this.#map, {
            sourceId: 'location-preview',
            layerId: 'location-preview-line',
            pointLayerId: 'location-preview-point',
            measureType: this.measureTypeValue,
            data: geojson,
        });
    }

    #updateMapData(geojson) {
        const source = this.#map.getSource('location-preview');

        if (source) {
            source.setData(toFeatureCollection(geojson));
        } else if (this.#map.loaded()) {
            this.#addSourceAndLayers(geojson);
        }

        this.#fitBounds(geojson);
    }

    #fitBounds(geojson) {
        const bounds = boundsFromGeoJSON(geojson);

        if (bounds.isEmpty()) {
            return;
        }

        const sw = bounds.getSouthWest();
        const ne = bounds.getNorthEast();

        if (sw.lng === ne.lng && sw.lat === ne.lat) {
            this.#map.jumpTo({ center: sw, zoom: 18 });
            return;
        }

        this.#map.fitBounds(bounds, {
            padding: 40,
            maxZoom: 18,
            animate: false,
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
