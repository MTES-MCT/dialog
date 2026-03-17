export class DrawManager {
    constructor(map) {
        this.map = map;
        this.features = [];
        this.isDrawing = false;
        this.currentMode = null;
        this.currentCoordinates = [];
        this.sourceId = 'draw-source';
        this.layerId = 'draw-layer';
        this.previewSourceId = 'draw-preview-source';
        this.previewLayerId = 'draw-preview-layer';
        this.previewFillLayerId = 'draw-preview-fill-layer';
        this.waypoints = [];
        this.routedSegments = [];
        this.setupSource();
        this.setupPreviewSource();
    }

    setupSource() {
        if (!this.map.getSource(this.sourceId)) {
            this.map.addSource(this.sourceId, {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: this.features },
            });
        }

        if (!this.map.getLayer(this.layerId)) {
            this.map.addLayer({
                id: this.layerId,
                type: 'line',
                source: this.sourceId,
                filter: ['==', ['geometry-type'], 'LineString'],
                paint: {
                    'line-color': '#000091',
                    'line-width': 4,
                },
            });

            this.map.addLayer({
                id: 'draw-fill',
                type: 'fill',
                source: this.sourceId,
                filter: ['==', ['geometry-type'], 'Polygon'],
                paint: {
                    'fill-color': '#000091',
                    'fill-opacity': 0.2,
                },
            });

            this.map.addLayer({
                id: 'draw-outline',
                type: 'line',
                source: this.sourceId,
                filter: ['==', ['geometry-type'], 'Polygon'],
                paint: {
                    'line-color': '#000091',
                    'line-width': 4,
                },
            });
        }
    }

    setupPreviewSource() {
        if (!this.map.getSource(this.previewSourceId)) {
            this.map.addSource(this.previewSourceId, {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] },
            });
        }

        if (!this.map.getLayer(this.previewFillLayerId)) {
            this.map.addLayer({
                id: this.previewFillLayerId,
                type: 'fill',
                source: this.previewSourceId,
                filter: ['==', ['geometry-type'], 'Polygon'],
                paint: {
                    'fill-color': '#4a90e2',
                    'fill-opacity': 0.15,
                },
            });
        }

        if (!this.map.getLayer(this.previewLayerId)) {
            this.map.addLayer({
                id: this.previewLayerId,
                type: 'line',
                source: this.previewSourceId,
                paint: {
                    'line-color': '#4a90e2',
                    'line-width': 4,
                },
            });
        }

        if (!this.map.getLayer('draw-preview-points')) {
            this.map.addLayer({
                id: 'draw-preview-points',
                type: 'circle',
                source: this.previewSourceId,
                filter: ['==', ['geometry-type'], 'Point'],
                paint: {
                    'circle-radius': 4,
                    'circle-color': '#4a90e2',
                    'circle-stroke-color': 'white',
                    'circle-stroke-width': 2,
                },
            });
        }
    }

    updatePreview() {
        if (!this.isDrawing || this.currentCoordinates.length < 1) {
            this.clearPreview();
            return;
        }

        let features = [];

        if (this.currentMode === 'polygon' && this.currentCoordinates.length > 2) {
            features.push({
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [this.currentCoordinates.concat([this.currentCoordinates[0]])],
                },
                properties: {},
            });
            this.currentCoordinates.forEach((coord, index) => {
                features.push({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: coord,
                    },
                    properties: { index },
                });
            });
        } else if (this.currentMode === 'polygon' && this.currentCoordinates.length > 0) {
            this.currentCoordinates.forEach((coord, index) => {
                features.push({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: coord,
                    },
                    properties: { index },
                });
            });
        }

        const previewSource = this.map.getSource(this.previewSourceId);
        if (previewSource && features.length > 0) {
            previewSource.setData({
                type: 'FeatureCollection',
                features: features,
            });
        }
    }

    clearPreview() {
        const previewSource = this.map.getSource(this.previewSourceId);
        if (previewSource) {
            previewSource.setData({
                type: 'FeatureCollection',
                features: [],
            });
        }
    }

    setMode(mode) {
        this.currentMode = mode;
        this.isDrawing = mode !== null;
        this.currentCoordinates = [];
        this.waypoints = [];
        this.routedSegments = [];
    }

    addCoordinate(lngLat) {
        if (!this.isDrawing) return;
        this.currentCoordinates.push([lngLat.lng, lngLat.lat]);
    }

    addWaypoint(lngLat) {
        this.waypoints.push([lngLat.lng, lngLat.lat]);
    }

    addRoutedSegment(coordinates) {
        this.routedSegments.push(coordinates);
    }

    getFullRoutedLine() {
        if (this.routedSegments.length === 0) return [];
        const coords = [...this.routedSegments[0]];
        for (let i = 1; i < this.routedSegments.length; i++) {
            coords.push(...this.routedSegments[i].slice(1));
        }
        return coords;
    }

    updateRoutedPreview() {
        const features = [];
        const fullLine = this.getFullRoutedLine();

        if (fullLine.length > 1) {
            features.push({
                type: 'Feature',
                geometry: { type: 'LineString', coordinates: fullLine },
                properties: {},
            });
        }

        this.waypoints.forEach((coord, index) => {
            features.push({
                type: 'Feature',
                geometry: { type: 'Point', coordinates: coord },
                properties: { index },
            });
        });

        const previewSource = this.map.getSource(this.previewSourceId);
        if (previewSource) {
            previewSource.setData({ type: 'FeatureCollection', features });
        }
    }

    finishRoutedLine() {
        const coordinates = this.getFullRoutedLine();
        if (coordinates.length < 2) {
            this.waypoints = [];
            this.routedSegments = [];
            return;
        }

        this.features.push({
            type: 'Feature',
            geometry: { type: 'LineString', coordinates },
            properties: { routed: true },
        });
        this.updateLayer();
        this.waypoints = [];
        this.routedSegments = [];
    }

    finishDrawing() {
        if (!this.isDrawing || this.currentCoordinates.length < 2) {
            this.currentCoordinates = [];
            this.isDrawing = false;
            return;
        }

        let feature = null;

        if (this.currentMode === 'polygon' && this.currentCoordinates.length >= 3) {
            feature = {
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [this.currentCoordinates.concat([this.currentCoordinates[0]])],
                },
                properties: {},
            };
        }

        if (feature) {
            this.features.push(feature);
            this.updateLayer();
        }

        this.currentCoordinates = [];
        this.isDrawing = false;
    }

    deleteAll() {
        this.features = [];
        this.currentCoordinates = [];
        this.waypoints = [];
        this.routedSegments = [];
        this.isDrawing = false;
        this.currentMode = null;
        this.updateLayer();
        this.clearPreview();
    }

    updateLayer() {
        const source = this.map.getSource(this.sourceId);
        if (source) {
            source.setData({
                type: 'FeatureCollection',
                features: this.features,
            });
        }
    }

    getAll() {
        return {
            type: 'FeatureCollection',
            features: this.features,
        };
    }

    setData(data) {
        if (data && data.features) {
            this.features = data.features;
            this.updateLayer();
        }
    }

    getFeatureCount() {
        return this.features.length;
    }
}
