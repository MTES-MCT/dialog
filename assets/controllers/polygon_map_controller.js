import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';

export default class extends Controller {
    static targets = ['container', 'loading'];
    static values = {
        geojson: Object,
        style: { type: String, default: 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/standard.json' },
        fillColor: { type: String, default: '#000' },
        fillOpacity: { type: Number, default: 0.2 },
        strokeColor: { type: String, default: '#000' },
        strokeWidth: { type: Number, default: 1.2 },
        padding: { type: Number, default: 50 },
        width: { type: Number, default: 600 },
        height: { type: Number, default: 400 }
    }

    connect() {
        this.setupContainerSize();
        this.initializeMap();
        this.setupResizeHandler();
    }

    setupContainerSize() {
        this.element.style.overflow = 'hidden';
        this.element.style.position = 'relative';

        this.containerTarget.style.width = '100%';
        this.containerTarget.style.overflow = 'hidden';

        const ratio = (this.heightValue / this.widthValue) * 100;

        this.containerTarget.style.height = '0';
        this.containerTarget.style.paddingBottom = `${ratio}%`;
        this.containerTarget.style.position = 'relative';
    }

    setupResizeHandler() {
        this.resizeObserver = new ResizeObserver(entries => {
            for (const entry of entries) {
                if (this.map) {
                    this.map.resize();
                    this.centerMapOnPolygon();
                    this.constrainMapCanvas();
                }
            }
        });

        this.resizeObserver.observe(this.element);

        const parentElement = this.element.parentElement;
        if (parentElement) {
            this.resizeObserver.observe(parentElement);
        }

        window.addEventListener('resize', this.handleWindowResize.bind(this));
    }

    handleWindowResize() {
        if (this.map) {
            setTimeout(() => {
                this.map.resize();

                if (this.hasGeojsonValue && this.map.getSource('polygon-source')) {
                    this.centerMapOnPolygon();
                }

                this.constrainMapCanvas();
            }, 100);
        }
    }

    constrainMapCanvas() {
        const mapContainer = this.element.querySelector('.maplibregl-canvas-container');
        if (mapContainer) {
            mapContainer.style.overflow = 'hidden';
        }

        const canvas = this.element.querySelector('.maplibregl-canvas');
        if (canvas) {
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.position = 'absolute';
            canvas.style.top = '0';
            canvas.style.left = '0';
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.style.display = 'none';
        }
    }

    initializeMap() {
        const mapOptions = {
            container: this.containerTarget,
            style: this.styleValue,
            interactive: false,
            attributionControl: false,
        };

        this.map = new maplibregl.Map(mapOptions);

        this.map.on('load', () => {
            if (this.hasGeojsonValue) {
                this.addPolygonToMap();
            }

            this.hideLoading();
            this.constrainMapCanvas();

            this.element.dispatchEvent(new CustomEvent('maplibre:ready', {
                detail: { map: this.map },
                bubbles: true
            }));
        });

        this.map.on('error', () => {
            this.hideLoading();
            this.containerTarget.innerHTML = `<h3>Erreur de chargement de la carte</h3>`;
        });
    }

    addPolygonToMap() {
        if (!this.geojsonValue || !this.geojsonValue.coordinates) {
            console.error('Invalid GeoJSON');
            return;
        }

        this.map.addSource('polygon-source', {
            type: 'geojson',
            data: this.geojsonValue
        });

        this.map.addLayer({
            id: 'polygon-fill',
            type: 'fill',
            source: 'polygon-source',
            paint: {
                'fill-color': this.fillColorValue,
                'fill-opacity': this.fillOpacityValue,
            }
        });

        this.map.addLayer({
            id: 'polygon-outline',
            type: 'line',
            source: 'polygon-source',
            paint: {
                'line-color': this.strokeColorValue,
                'line-width': this.strokeWidthValue,
            }
        });

        this.centerMapOnPolygon();
    }

    centerMapOnPolygon() {
        try {
            const bounds = new maplibregl.LngLatBounds();
            this.processCoordinates(this.geojsonValue.coordinates, bounds);

            this.map.fitBounds(bounds, {
                padding: this.paddingValue,
                maxZoom: 15,
                animate: false,
            });
        } catch (error) {
            console.error('Error centering map', error);
        }
    }

    processCoordinates(coordinates, bounds) {
        try {
            if (coordinates.length === 0) {
                return;
            }

            // Point [longitude, latitude]
            if (typeof coordinates[0] === 'number' && typeof coordinates[1] === 'number' && coordinates.length === 2) {
                bounds.extend([coordinates[0], coordinates[1]]);
                return;
            }

            // Parcourir récursivement les tableaux de coordonnées
            coordinates.forEach(coord => {
                if (Array.isArray(coord)) {
                    this.processCoordinates(coord, bounds);
                }
            });
        } catch (error) {
            console.error('Error processing coordinates', error);
        }
    }

    disconnect() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }

        window.removeEventListener('resize', this.handleWindowResize.bind(this));

        if (this.controlsObserver) {
            this.controlsObserver.disconnect();
        }

        if (this.map) {
            this.map.remove();
        }
    }
}
