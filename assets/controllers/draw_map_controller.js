import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { DrawManager } from '../customElements/draw-manager';
import { OsrmRouter } from '../customElements/osrm-router';
import '../styles/components/draw-map.css';

const EMPTY_FEATURE_COLLECTION = { type: 'FeatureCollection', features: [] };
const NEARBY_STREETS_SOURCE = 'nearby-streets-source';
const NEARBY_STREETS_LAYER = 'nearby-streets-layer';

export default class extends Controller {
    static targets = ['container', 'polygonBtn', 'lineBtn', 'roadLineBtn', 'validateBtn', 'geometryField', 'nearbyStreetsList'];
    static values = {
        centerJson: String,
        zoom: Number,
        submitUrl: String,
        nearbyStreetsUrl: String,
    };

    #map = null;
    #draw = null;
    #currentMode = null;
    #router = null;
    #observer = null;
    #isDrawing = false;
    #isRoutingInProgress = false;
    #routingPromise = Promise.resolve();

    connect() {
        if (this.#isContainerHidden()) {
            this.#observeVisibility();
        } else {
            this.initializeMap();
        }
    }

    disconnect() {
        this.#observer?.disconnect();
        this.#observer = null;
        this.#map?.remove();
        this.#map = null;
    }

    // --- Map initialization ---

    initializeMap() {
        if (!this.hasContainerTarget) {
            return;
        }

        try {
            this.#map = new maplibregl.Map({
                container: this.containerTarget,
                style: mapStyles.desaturated,
                center: this.#centerCoords,
                zoom: this.zoomValue || 5,
                minZoom: 4.33,
                maxZoom: 18,
                hash: 'mapZoomAndPosition',
            });

            this.#map.on('load', () => {
                this.#map.addControl(new maplibregl.NavigationControl(), 'top-left');
                this.#draw = new DrawManager(this.#map);
                this.#router = new OsrmRouter();
                this.#setupNearbyStreetsLayer();
                this.#bindDrawingEvents();
                this.loadGeometryFromField();
            });

            this.#map.on('error', (e) => {
                console.error('Erreur MapLibre:', e);
            });
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la carte:', error);
        }
    }

    // --- Mode toggling ---

    togglePolygon() {
        this.#toggleMode('polygon');
    }

    toggleLine() {
        this.#toggleMode('line');
    }

    toggleRoadLine() {
        this.#toggleMode('road-line');
    }

    // --- Drawing actions ---

    clearAll() {
        if (!this.#draw) {
            return;
        }

        this.#draw.deleteAll();
        this.#resetDrawingState();
        this.clearNearbyStreets();
    }

    async submitGeoJSON() {
        const geoJsonData = await this.#finalizeDrawing();

        if (!geoJsonData) {
            alert('Veuillez tracer un polygone ou une ligne avant de valider.');

            return;
        }

        try {
            this.hasValidateBtnTarget && (this.validateBtnTarget.disabled = true);

            const response = await fetch(this.submitUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(geoJsonData),
            });

            if (!response.ok) {
                throw new Error(`Erreur serveur: ${response.statusText}`);
            }

            const result = await response.json();

            alert(JSON.stringify(result));
            this.dispatch('submitSuccess', { detail: result });

            await this.#fetchNearbyStreets(geoJsonData);
        } catch (error) {
            console.error('Erreur lors de l\'envoi du GeoJSON:', error);
            alert(`Erreur: ${error.message}`);
            this.dispatch('submitError', { detail: { error: error.message } });
        } finally {
            this.hasValidateBtnTarget && (this.validateBtnTarget.disabled = false);
        }
    }

    async validateToField() {
        const geoJsonData = await this.#finalizeDrawing();

        if (!geoJsonData) {
            return;
        }

        if (this.hasGeometryFieldTarget) {
            const geometry = this.#buildGeometry(geoJsonData);
            this.geometryFieldTarget.value = JSON.stringify(geometry);
            this.dispatch('geometryChanged', { detail: { geometry } });
        }

        await this.#fetchNearbyStreets(geoJsonData);
    }

    loadGeometryFromField() {
        if (!this.hasGeometryFieldTarget || !this.geometryFieldTarget.value) {
            return;
        }

        try {
            const geometry = JSON.parse(this.geometryFieldTarget.value);
            this.#draw.setData({
                type: 'FeatureCollection',
                features: [{ type: 'Feature', geometry, properties: {} }],
            });
            this.#fitBoundsToGeometry(geometry);
        } catch {
            // Invalid JSON, ignore
        }
    }

    clearNearbyStreets() {
        this.#map.getSource(NEARBY_STREETS_SOURCE)
            ?.setData(EMPTY_FEATURE_COLLECTION);

        if (this.hasNearbyStreetsListTarget) {
            this.nearbyStreetsListTarget.innerHTML = '';
        }
    }

    // --- Private: visibility ---

    #isContainerHidden() {
        return this.containerTarget.offsetParent === null;
    }

    #observeVisibility() {
        this.#observer = new MutationObserver(() => {
            if (this.#isContainerHidden()) {
                return;
            }

            this.#observer.disconnect();
            this.#observer = null;
            this.initializeMap();
        });

        const parent = this.element.closest('[hidden]') || this.element.parentElement;

        if (parent) {
            this.#observer.observe(parent, { attributes: true, attributeFilter: ['hidden'] });
        }
    }

    // --- Private: mode management ---

    #toggleMode(mode) {
        this.#currentMode = this.#currentMode === mode ? null : mode;
        this.#updateButtonClasses();
    }

    #updateButtonClasses() {
        this.polygonBtnTarget?.classList.toggle('active', this.#currentMode === 'polygon');
        this.lineBtnTarget?.classList.toggle('active', this.#currentMode === 'line');
        this.roadLineBtnTarget?.classList.toggle('active', this.#currentMode === 'road-line');
    }

    // --- Private: drawing event handlers ---

    #bindDrawingEvents() {
        this.#map.on('mousedown', (e) => this.#handleMapClick(e));
        document.addEventListener('keydown', (e) => this.#handleKeydown(e));
    }

    #handleMapClick(e) {
        if (!this.#currentMode) {
            return;
        }

        const handlers = {
            'polygon': () => this.#handleSimpleDrawClick(e, 'polygon'),
            'line': () => this.#handleSimpleDrawClick(e, 'line'),
            'road-line': () => this.#handleRoadLineClick(e),
        };

        handlers[this.#currentMode]?.();
    }

    #handleSimpleDrawClick(e, mode) {
        if (!this.#isDrawing) {
            this.#draw.setMode(mode);
            this.#isDrawing = true;
            this.#map.dragPan.disable();
        }

        this.#draw.addCoordinate(e.lngLat);
        this.#draw.updatePreview();
        this.#setCursor('crosshair');
    }

    #handleRoadLineClick(e) {
        if (this.#isRoutingInProgress) {
            return;
        }

        if (!this.#isDrawing) {
            this.#draw.setMode('road-line');
            this.#isDrawing = true;
        }

        const newPoint = [e.lngLat.lng, e.lngLat.lat];
        const previousWaypoints = this.#draw.waypoints;

        if (previousWaypoints.length === 0) {
            this.#draw.addWaypoint(e.lngLat);
            this.#draw.updateRoutedPreview();
            this.#setCursor('crosshair');

            return;
        }

        const lastWaypoint = previousWaypoints[previousWaypoints.length - 1];
        this.#isRoutingInProgress = true;
        this.#setCursor('wait');

        this.#routingPromise = this.#router.getSegment(lastWaypoint, newPoint)
            .then((segmentCoords) => {
                this.#addRoutedWaypoint(e.lngLat, segmentCoords);
            })
            .catch((err) => {
                console.error('Routing error:', err);
                this.#addRoutedWaypoint(e.lngLat, [lastWaypoint, newPoint]);
            })
            .finally(() => {
                this.#isRoutingInProgress = false;
                this.#setCursor('crosshair');
            });
    }

    #addRoutedWaypoint(lngLat, segmentCoords) {
        this.#draw.addWaypoint(lngLat);
        this.#draw.addRoutedSegment(segmentCoords);
        this.#draw.updateRoutedPreview();
    }

    #handleKeydown(e) {
        if (e.key !== 'Escape' || !this.#isDrawing) {
            return;
        }

        if (this.#currentMode === 'road-line') {
            this.#draw.waypoints = [];
            this.#draw.routedSegments = [];
        }

        this.#isDrawing = false;
        this.#draw.currentCoordinates = [];
        this.#draw.clearPreview();
        this.#map.dragPan.enable();
        this.#setCursor('');
    }

    // --- Private: finalize & geometry ---

    async #finalizeDrawing() {
        await this.#routingPromise;

        this.#draw.finishDrawing();
        this.#draw.finishRoutedLine();
        this.#draw.clearPreview();
        this.#map.dragPan.enable();
        this.#setCursor('');

        this.#currentMode = null;
        this.#isDrawing = false;
        this.#updateButtonClasses();

        const geoJsonData = this.#draw?.getAll() || EMPTY_FEATURE_COLLECTION;

        if (!geoJsonData.features?.length) {
            return null;
        }

        return geoJsonData;
    }

    #buildGeometry(geoJsonData) {
        if (geoJsonData.features.length === 1) {
            return geoJsonData.features[0].geometry;
        }

        return {
            type: 'GeometryCollection',
            geometries: geoJsonData.features.map(f => f.geometry),
        };
    }

    #fitBoundsToGeometry(geometry) {
        const coords = this.#extractCoordinates(geometry);

        if (coords.length === 0) {
            return;
        }

        const bounds = coords.reduce(
            (b, coord) => b.extend(coord),
            new maplibregl.LngLatBounds(coords[0], coords[0]),
        );

        this.#map.fitBounds(bounds, { padding: 40, maxZoom: 16 });
    }

    #extractCoordinates(geometry) {
        switch (geometry.type) {
            case 'Point':
                return [geometry.coordinates];
            case 'LineString':
            case 'MultiPoint':
                return geometry.coordinates;
            case 'Polygon':
            case 'MultiLineString':
                return geometry.coordinates.flat();
            case 'MultiPolygon':
                return geometry.coordinates.flat(2);
            case 'GeometryCollection':
                return geometry.geometries.flatMap(g => this.#extractCoordinates(g));
            default:
                return [];
        }
    }

    // --- Private: nearby streets ---

    #setupNearbyStreetsLayer() {
        if (!this.#map.getSource(NEARBY_STREETS_SOURCE)) {
            this.#map.addSource(NEARBY_STREETS_SOURCE, {
                type: 'geojson',
                data: EMPTY_FEATURE_COLLECTION,
            });
        }

        if (!this.#map.getLayer(NEARBY_STREETS_LAYER)) {
            this.#map.addLayer({
                id: NEARBY_STREETS_LAYER,
                type: 'line',
                source: NEARBY_STREETS_SOURCE,
                paint: {
                    'line-color': '#e63946',
                    'line-width': 3,
                    'line-opacity': 0.7,
                    'line-dasharray': [2, 2],
                },
            });
        }
    }

    async #fetchNearbyStreets(geoJsonData) {
        if (!this.hasNearbyStreetsUrlValue) {
            return;
        }

        const geometry = this.#buildGeometry(geoJsonData);

        try {
            const response = await fetch(this.nearbyStreetsUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ geometry, radius: 100 }),
            });

            if (!response.ok) {
                throw new Error(`Erreur serveur: ${response.statusText}`);
            }

            const streets = await response.json();

            this.#displayNearbyStreetsOnMap(streets);
            this.#displayNearbyStreetsList(streets);
            this.dispatch('nearbyStreetsFound', { detail: { streets } });
        } catch (error) {
            console.error('Erreur lors de la récupération des rues proches:', error);
        }
    }

    #displayNearbyStreetsOnMap(streets) {
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

        this.#map.getSource(NEARBY_STREETS_SOURCE)
            ?.setData({ type: 'FeatureCollection', features });
    }

    #displayNearbyStreetsList(streets) {
        if (!this.hasNearbyStreetsListTarget) {
            return;
        }

        this.nearbyStreetsListTarget.innerHTML = '';

        if (streets.length === 0) {
            this.nearbyStreetsListTarget.innerHTML = '<li>Aucune rue trouvée à proximité</li>';

            return;
        }

        for (const street of streets) {
            const li = document.createElement('li');
            li.textContent = `${street.roadName} (${street.distance} m)`;
            this.nearbyStreetsListTarget.appendChild(li);
        }
    }

    // --- Private: helpers ---

    get #centerCoords() {
        return JSON.parse(this.centerJsonValue || '[2.725, 47.16]');
    }

    #setCursor(cursor) {
        this.#map.getCanvas().style.cursor = cursor;
    }

    #resetDrawingState() {
        this.#draw.currentCoordinates = [];
        this.#draw.clearPreview();
        this.#map.dragPan.enable();
        this.#setCursor('');
        this.#isDrawing = false;
        this.#currentMode = null;
        this.#updateButtonClasses();
    }
}
