import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { DrawManager } from '../customElements/draw-manager';
import { OsrmRouter } from '../customElements/osrm-router';
import '../styles/components/draw-map.css';

export default class extends Controller {
    static targets = ['container', 'polygonBtn', 'roadLineBtn', 'validateBtn'];
    static values = {
        centerJson: String,
        zoom: Number,
        submitUrl: String,
    };

    #map = null;
    #draw = null;
    #currentMode = null;
    #router = null;

    connect() {
        this.initializeMap();
    }

    disconnect() {
        this.#map?.remove();
        this.#map = null;
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
}
