import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { DrawManager } from '../customElements/draw-manager';
import { OsrmRouter } from '../customElements/osrm-router';
import '../styles/components/draw-map.css';

export default class extends Controller {
    static targets = ['container', 'polygonBtn', 'roadLineBtn', 'validateBtn', 'geometryField'];
    static values = {
        centerJson: String,
        zoom: Number,
        submitUrl: String,
    };

    #map = null;
    #draw = null;
    #currentMode = null;
    #router = null;
    #observer = null;

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

    #isContainerHidden() {
        return this.containerTarget.offsetParent === null;
    }

    #observeVisibility() {
        this.#observer = new MutationObserver(() => {
            if (!this.#isContainerHidden()) {
                this.#observer.disconnect();
                this.#observer = null;
                this.initializeMap();
            }
        });

        // Observe ancestor hidden attribute changes
        let parent = this.element.closest('[hidden]') || this.element.parentElement;
        if (parent) {
            this.#observer.observe(parent, { attributes: true, attributeFilter: ['hidden'] });
        }
    }

    get centerCoords() {
        return JSON.parse(this.centerJsonValue || '[2.725, 47.16]');
    }

    initializeMap() {
        if (!this.hasContainerTarget) {
            return;
        }

        try {
            const centerCoords = this.centerCoords;
            const zoomLevel = this.zoomValue || 5;

            this.#map = new maplibregl.Map({
                container: this.containerTarget,
                style: mapStyles.desaturated,
                center: centerCoords,
                zoom: zoomLevel,
                minZoom: 4.33,
                maxZoom: 18,
                hash: 'mapZoomAndPosition',
            });

            this.#map.on('load', () => {
                this.addNavigationControl();
                this.initializeDrawControl();
                this.setupDrawing();
                this.loadGeometryFromField();
            });

            this.#map.on('error', (e) => {
                console.error('Erreur MapLibre:', e);
            });
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la carte:', error);
        }
    }

    addNavigationControl() {
        this.#map.addControl(new maplibregl.NavigationControl(), 'top-left');
    }

    initializeDrawControl() {
        this.#draw = new DrawManager(this.#map);
        this.#router = new OsrmRouter();
    }

    updateButtonClasses() {
        this.polygonBtnTarget?.classList.toggle('active', this.#currentMode === 'polygon');
        this.roadLineBtnTarget?.classList.toggle('active', this.#currentMode === 'road-line');
    }

    togglePolygon() {
        this.#currentMode = this.#currentMode === 'polygon' ? null : 'polygon';
        this.updateButtonClasses();
    }

    toggleRoadLine() {
        this.#currentMode = this.#currentMode === 'road-line' ? null : 'road-line';
        this.updateButtonClasses();
    }

    setupDrawing() {
        let isDrawingPolygon = false;
        let isDrawingRoadLine = false;
        this._isRoutingInProgress = false;
        this._routingPromise = Promise.resolve();

        this.#map.on('mousedown', (e) => {
            if (!this.#currentMode) {
                return;
            }

            if (this.#currentMode === 'polygon') {
                if (!isDrawingPolygon) {
                    this.#draw.setMode('polygon');
                    isDrawingPolygon = true;
                    this.#map.dragPan.disable();
                }
                this.#draw.addCoordinate(e.lngLat);
                this.#draw.updatePreview();
                this.#map.getCanvas().style.cursor = 'crosshair';
            } else if (this.#currentMode === 'road-line') {
                if (this._isRoutingInProgress) return;

                if (!isDrawingRoadLine) {
                    this.#draw.setMode('road-line');
                    isDrawingRoadLine = true;
                }

                const newPoint = [e.lngLat.lng, e.lngLat.lat];
                const previousWaypoints = this.#draw.waypoints;

                if (previousWaypoints.length === 0) {
                    this.#draw.addWaypoint(e.lngLat);
                    this.#draw.updateRoutedPreview();
                    this.#map.getCanvas().style.cursor = 'crosshair';
                } else {
                    const lastWaypoint = previousWaypoints[previousWaypoints.length - 1];
                    this._isRoutingInProgress = true;
                    this.#map.getCanvas().style.cursor = 'wait';

                    this._routingPromise = this.#router.getSegment(lastWaypoint, newPoint)
                        .then((segmentCoords) => {
                            this.#draw.addWaypoint(e.lngLat);
                            this.#draw.addRoutedSegment(segmentCoords);
                            this.#draw.updateRoutedPreview();
                        })
                        .catch((err) => {
                            console.error('Routing error:', err);
                            this.#draw.addWaypoint(e.lngLat);
                            this.#draw.addRoutedSegment([lastWaypoint, newPoint]);
                            this.#draw.updateRoutedPreview();
                        })
                        .finally(() => {
                            this._isRoutingInProgress = false;
                            this.#map.getCanvas().style.cursor = 'crosshair';
                        });
                }
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (isDrawingPolygon) {
                    isDrawingPolygon = false;
                    this.clearPolygonPreview();
                }
                if (isDrawingRoadLine) {
                    isDrawingRoadLine = false;
                    this.#draw.waypoints = [];
                    this.#draw.routedSegments = [];
                    this.#draw.clearPreview();
                    this.#map.getCanvas().style.cursor = '';
                }
            }
        });
    }

    getDrawnData() {
        return this.#draw?.getAll() || { type: 'FeatureCollection', features: [] };
    }

    setDrawnData(data) {
        if (this.#draw && data && data.features) {
            this.#draw.setData(data);
        }
    }

    clearPolygonPreview() {
        this.#map.dragPan.enable();
        this.#draw.currentCoordinates = [];
        this.#draw.clearPreview();
        this.#map.getCanvas().style.cursor = '';
    }

    clearAll() {
        if (this.#draw) {
            this.#draw.deleteAll();
            this.clearPolygonPreview();
            this.#currentMode = null;
            this.updateButtonClasses();
        }
    }

    async submitGeoJSON() {
        await this._routingPromise;

        this.#draw.finishDrawing();
        this.#draw.finishRoutedLine();
        this.#draw.clearPreview();
        this.#map.dragPan.enable();
        this.#map.getCanvas().style.cursor = '';

        this.#currentMode = null;
        this.updateButtonClasses();

        const geoJsonData = this.getDrawnData();

        if (!geoJsonData.features || geoJsonData.features.length === 0) {
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

            this.clearAll();
        } catch (error) {
            console.error('Erreur lors de l\'envoi du GeoJSON:', error);
            alert(`Erreur: ${error.message}`);
            this.dispatch('submitError', { detail: { error: error.message } });
        } finally {
            this.hasValidateBtnTarget && (this.validateBtnTarget.disabled = false);
        }
    }

    async validateToField() {
        await this._routingPromise;

        this.#draw.finishDrawing();
        this.#draw.finishRoutedLine();
        this.#draw.clearPreview();
        this.#map.dragPan.enable();
        this.#map.getCanvas().style.cursor = '';

        this.#currentMode = null;
        this.updateButtonClasses();

        const geoJsonData = this.getDrawnData();

        if (!geoJsonData.features || geoJsonData.features.length === 0) {
            return;
        }

        let geometry;

        if (geoJsonData.features.length === 1) {
            geometry = geoJsonData.features[0].geometry;
        } else {
            geometry = {
                type: 'GeometryCollection',
                geometries: geoJsonData.features.map(f => f.geometry),
            };
        }

        if (this.hasGeometryFieldTarget) {
            this.geometryFieldTarget.value = JSON.stringify(geometry);
            this.dispatch('geometryChanged', { detail: { geometry } });
        }
    }

    loadGeometryFromField() {
        if (!this.hasGeometryFieldTarget || !this.geometryFieldTarget.value) {
            return;
        }

        try {
            const geometry = JSON.parse(this.geometryFieldTarget.value);
            const feature = { type: 'Feature', geometry, properties: {} };
            const featureCollection = { type: 'FeatureCollection', features: [feature] };
            this.setDrawnData(featureCollection);
            this.#fitBoundsToGeometry(geometry);
        } catch {
            // Invalid JSON, ignore
        }
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
                return geometry.coordinates;
            case 'Polygon':
                return geometry.coordinates.flat();
            case 'MultiPoint':
                return geometry.coordinates;
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
}
