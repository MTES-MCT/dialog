import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { addHouseNumbersLayer, addMeasureLineLayer } from '../maps/layers';
import { extendBoundsFromCoordinates, extractSingleGeometry } from '../maps/geojson';
import '../styles/components/draw-line-map.css';

const LINE_SOURCE = 'draw-line-source';
const LINE_LAYER = 'draw-line-layer';
const POINTS_SOURCE = 'draw-points-source';
const POINTS_LAYER = 'draw-points-layer';
const EMPTY_FC = { type: 'FeatureCollection', features: [] };

const SEARCH_API_URL = 'https://geo.api.gouv.fr/communes';
const SEARCH_DEBOUNCE_MS = 250;
const SEARCH_MIN_LENGTH = 2;
const SEARCH_LIMIT = 5;

export default class extends Controller {
    static targets = ['container', 'geometryField', 'drawBtn', 'undoBtn', 'clearBtn', 'warning', 'searchInput', 'searchResults'];
    static values = {
        centerJson: { type: String, default: '[2.725, 47.16]' },
        zoom: { type: Number, default: 15 },
        measureType: { type: String, default: '' },
    };

    #map = null;
    #coordinates = [];
    #isDrawing = false;
    #draggingIndex = null;
    #hoveredIndex = null;
    #hiddenObserver = null;
    #hiddenAncestor = null;
    #initialized = false;
    #suppressFieldInput = false;
    #unsupportedGeometry = false;
    #boundFieldInput = null;
    #boundKeydown = null;
    #searchAbortController = null;
    #searchDebounceTimer = null;
    #searchResults = [];
    #searchActiveIndex = -1;
    #searchBlurTimer = null;

    connect() {
        if (!this.hasGeometryFieldTarget) {
            return;
        }

        this.#boundFieldInput = () => this.#handleFieldInput();
        this.geometryFieldTarget.addEventListener('input', this.#boundFieldInput);

        this.#boundKeydown = (e) => this.#handleKeydown(e);
        document.addEventListener('keydown', this.#boundKeydown);

        this.#hiddenAncestor = this.element.closest('[hidden]');

        if (this.#isSectionHidden()) {
            this.#observeReveal();
        } else {
            this.#initializeMap();
        }
    }

    disconnect() {
        this.#hiddenObserver?.disconnect();
        this.#hiddenObserver = null;

        if (this.#boundFieldInput && this.hasGeometryFieldTarget) {
            this.geometryFieldTarget.removeEventListener('input', this.#boundFieldInput);
        }

        if (this.#boundKeydown) {
            document.removeEventListener('keydown', this.#boundKeydown);
        }

        this.#searchAbortController?.abort();
        clearTimeout(this.#searchDebounceTimer);
        clearTimeout(this.#searchBlurTimer);

        this.#map?.remove();
        this.#map = null;
    }

    onSearchInput() {
        clearTimeout(this.#searchDebounceTimer);
        const query = this.searchInputTarget.value.trim();

        if (query.length < SEARCH_MIN_LENGTH) {
            this.#searchAbortController?.abort();
            this.#renderSearchResults([]);
            this.#hideSearchResults();

            return;
        }

        this.#searchDebounceTimer = setTimeout(() => this.#searchCommunes(query), SEARCH_DEBOUNCE_MS);
    }

    onSearchFocus() {
        clearTimeout(this.#searchBlurTimer);

        if (this.#searchResults.length > 0) {
            this.#showSearchResults();
        }
    }

    onSearchBlur() {
        // Delay so a click on a result is processed before hiding
        clearTimeout(this.#searchBlurTimer);
        this.#searchBlurTimer = setTimeout(() => this.#hideSearchResults(), 150);
    }

    onSearchKeydown(event) {
        if (this.#searchResults.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.#moveSearchActive(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.#moveSearchActive(-1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const index = this.#searchActiveIndex >= 0 ? this.#searchActiveIndex : 0;
            this.#selectCommune(this.#searchResults[index]);
        } else if (event.key === 'Escape') {
            this.#hideSearchResults();
        }
    }

    async #searchCommunes(query) {
        this.#searchAbortController?.abort();
        this.#searchAbortController = new AbortController();

        const url = new URL(SEARCH_API_URL);
        url.searchParams.set('nom', query);
        url.searchParams.set('fields', 'nom,code,codeDepartement,centre,contour');
        url.searchParams.set('boost', 'population');
        url.searchParams.set('limit', String(SEARCH_LIMIT));

        try {
            const response = await fetch(url.toString(), { signal: this.#searchAbortController.signal });

            if (!response.ok) {
                this.#renderSearchResults([]);
                return;
            }

            const data = await response.json();
            this.#renderSearchResults(Array.isArray(data) ? data : []);
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.#renderSearchResults([]);
            }
        }
    }

    #renderSearchResults(results) {
        if (!this.hasSearchResultsTarget) {
            return;
        }

        this.#searchResults = results;
        this.#searchActiveIndex = -1;
        this.searchResultsTarget.innerHTML = '';

        if (results.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'draw-line-map-search__empty';
            empty.textContent = 'Aucune commune trouvée';
            this.searchResultsTarget.appendChild(empty);
            this.#showSearchResults();

            return;
        }

        results.forEach((commune, index) => {
            const item = document.createElement('li');
            item.className = 'draw-line-map-search__result';
            item.setAttribute('role', 'option');
            item.dataset.index = String(index);

            const name = document.createElement('span');
            name.className = 'draw-line-map-search__result-name';
            name.textContent = commune.nom;

            const meta = document.createElement('span');
            meta.className = 'draw-line-map-search__result-meta';
            meta.textContent = ` (${commune.codeDepartement || commune.code})`;

            item.appendChild(name);
            item.appendChild(meta);

            item.addEventListener('mousedown', (e) => {
                // Prevent input blur before click triggers selection
                e.preventDefault();
            });
            item.addEventListener('click', () => this.#selectCommune(commune));
            item.addEventListener('mouseenter', () => this.#setSearchActiveIndex(index));

            this.searchResultsTarget.appendChild(item);
        });

        this.#showSearchResults();
    }

    #moveSearchActive(delta) {
        const count = this.#searchResults.length;

        if (count === 0) {
            return;
        }

        const next = (this.#searchActiveIndex + delta + count) % count;
        this.#setSearchActiveIndex(next);
    }

    #setSearchActiveIndex(index) {
        this.#searchActiveIndex = index;
        const items = this.searchResultsTarget.querySelectorAll('.draw-line-map-search__result');
        items.forEach((el, i) => {
            el.classList.toggle('draw-line-map-search__result--active', i === index);
        });
    }

    #selectCommune(commune) {
        if (!commune || !this.#map) {
            this.#hideSearchResults();

            return;
        }

        if (commune.contour && Array.isArray(commune.contour.coordinates)) {
            const bounds = new maplibregl.LngLatBounds();
            extendBoundsFromCoordinates(commune.contour.coordinates, bounds);

            if (!bounds.isEmpty()) {
                this.#map.fitBounds(bounds, { padding: 40, maxZoom: 14, animate: true });
            }
        } else if (commune.centre && Array.isArray(commune.centre.coordinates)) {
            this.#map.flyTo({ center: commune.centre.coordinates, zoom: 13 });
        }

        if (this.hasSearchInputTarget) {
            this.searchInputTarget.value = commune.nom;
        }

        this.#hideSearchResults();
    }

    #showSearchResults() {
        if (this.hasSearchResultsTarget) {
            this.searchResultsTarget.hidden = false;
        }
    }

    #hideSearchResults() {
        if (this.hasSearchResultsTarget) {
            this.searchResultsTarget.hidden = true;
        }
    }

    toggleDraw() {
        if (this.#unsupportedGeometry) {
            return;
        }

        this.#isDrawing = !this.#isDrawing;
        this.#updateDrawButton();
        this.#setCursor(this.#isDrawing ? 'crosshair' : '');
    }

    undo() {
        if (this.#unsupportedGeometry || this.#coordinates.length === 0) {
            return;
        }

        this.#coordinates.pop();
        this.#refreshMapFromCoordinates();
        this.#writeFieldFromCoordinates();
    }

    clear() {
        if (this.#unsupportedGeometry) {
            this.#unsupportedGeometry = false;
            this.#hideWarning();
            this.#enableDrawingControls();
        }

        this.#coordinates = [];
        this.#refreshMapFromCoordinates();
        this.#writeFieldFromCoordinates();
    }

    #isSectionHidden() {
        return !!this.#hiddenAncestor && this.#hiddenAncestor.hasAttribute('hidden');
    }

    #observeReveal() {
        if (!this.#hiddenAncestor) {
            return;
        }

        this.#hiddenObserver = new MutationObserver(() => {
            if (this.#isSectionHidden()) {
                return;
            }

            if (!this.#initialized) {
                this.#initializeMap();
            } else {
                this.#map?.resize();
                this.#fitBoundsToCoordinates();
            }
        });

        this.#hiddenObserver.observe(this.#hiddenAncestor, {
            attributes: true,
            attributeFilter: ['hidden'],
        });
    }

    #initializeMap() {
        if (!this.hasContainerTarget || this.#initialized) {
            return;
        }

        try {
            this.#map = new maplibregl.Map({
                container: this.containerTarget,
                style: mapStyles.desaturated,
                center: JSON.parse(this.centerJsonValue),
                zoom: this.zoomValue,
                minZoom: 4,
                maxZoom: 19,
                attributionControl: false,
            });

            this.#map.on('load', () => {
                this.#map.addControl(new maplibregl.NavigationControl(), 'bottom-left');
                addHouseNumbersLayer(this.#map);
                this.#setupLineLayer();
                this.#map.on('click', (e) => this.#handleMapClick(e));
                this.#loadFromField();
            });

            this.#map.on('error', (e) => console.error('MapLibre error:', e));
            this.#initialized = true;

            if (this.#hiddenAncestor && !this.#hiddenObserver) {
                this.#observeReveal();
            }
        } catch (error) {
            console.error('Failed to initialize draw-line map:', error);
        }
    }

    #setupLineLayer() {
        addMeasureLineLayer(this.#map, {
            sourceId: LINE_SOURCE,
            layerId: LINE_LAYER,
            measureType: this.measureTypeValue,
        });

        this.#map.addSource(POINTS_SOURCE, { type: 'geojson', data: EMPTY_FC });
        this.#map.addLayer({
            id: POINTS_LAYER,
            type: 'circle',
            source: POINTS_SOURCE,
            paint: {
                'circle-radius': [
                    'case',
                    ['boolean', ['feature-state', 'hover'], false], 8,
                    5,
                ],
                'circle-color': [
                    'match',
                    ['get', 'role'],
                    'start', '#18753c',
                    'end', '#ce0500',
                    '#000091',
                ],
                'circle-stroke-color': '#ffffff',
                'circle-stroke-width': 2,
            },
        });

        this.#bindCanvasInteractions();
    }

    #bindCanvasInteractions() {
        const canvas = this.#map.getCanvasContainer();

        this.#map.on('mouseenter', POINTS_LAYER, (e) => {
            this.#map.getCanvas().style.cursor = 'pointer';
            const feature = e.features?.[0];

            if (feature && this.#hoveredIndex !== feature.properties.index) {
                this.#setHoverState(feature.properties.index);
            }
        });

        this.#map.on('mouseleave', POINTS_LAYER, () => {
            this.#setCursor(this.#isDrawing ? 'crosshair' : '');
            this.#setHoverState(null);
        });

        this.#map.on('mousedown', POINTS_LAYER, (e) => {
            if (this.#unsupportedGeometry) {
                return;
            }

            const feature = e.features?.[0];

            if (!feature) {
                return;
            }

            e.preventDefault();
            this.#draggingIndex = feature.properties.index;
            canvas.style.cursor = 'grabbing';

            const onMove = (ev) => this.#handleVertexDrag(ev);
            const onUp = () => {
                this.#map.off('mousemove', onMove);
                this.#map.off('mouseup', onUp);
                this.#draggingIndex = null;
                canvas.style.cursor = '';
                this.#writeFieldFromCoordinates();
            };

            this.#map.on('mousemove', onMove);
            this.#map.once('mouseup', onUp);
        });

        this.#map.on('contextmenu', POINTS_LAYER, (e) => {
            if (this.#unsupportedGeometry) {
                return;
            }

            const feature = e.features?.[0];

            if (!feature) {
                return;
            }

            e.preventDefault();
            const index = feature.properties.index;
            this.#coordinates.splice(index, 1);
            this.#refreshMapFromCoordinates();
            this.#writeFieldFromCoordinates();
        });
    }

    #handleVertexDrag(e) {
        if (this.#draggingIndex === null) {
            return;
        }

        this.#coordinates[this.#draggingIndex] = [e.lngLat.lng, e.lngLat.lat];
        this.#renderLine();
        this.#renderPoints();
    }

    #setHoverState(index) {
        if (this.#hoveredIndex !== null) {
            this.#map.setFeatureState(
                { source: POINTS_SOURCE, id: this.#hoveredIndex },
                { hover: false },
            );
        }

        this.#hoveredIndex = index;

        if (index !== null) {
            this.#map.setFeatureState(
                { source: POINTS_SOURCE, id: index },
                { hover: true },
            );
        }
    }

    #handleMapClick(e) {
        if (!this.#isDrawing || this.#unsupportedGeometry) {
            return;
        }

        const hits = this.#map.queryRenderedFeatures(e.point, { layers: [POINTS_LAYER] });

        if (hits.length > 0) {
            return;
        }

        this.#coordinates.push([e.lngLat.lng, e.lngLat.lat]);
        this.#refreshMapFromCoordinates();
        this.#writeFieldFromCoordinates();
    }

    #handleKeydown(e) {
        if (e.key === 'Escape' && this.#isDrawing) {
            this.#isDrawing = false;
            this.#updateDrawButton();
            this.#setCursor('');
        }
    }

    #refreshMapFromCoordinates() {
        if (!this.#map) {
            return;
        }

        this.#renderLine();
        this.#renderPoints();
    }

    #renderLine() {
        const source = this.#map.getSource(LINE_SOURCE);

        if (!source) {
            return;
        }

        if (this.#coordinates.length < 2) {
            source.setData(EMPTY_FC);

            return;
        }

        source.setData({
            type: 'FeatureCollection',
            features: [{
                type: 'Feature',
                geometry: { type: 'LineString', coordinates: this.#coordinates },
                properties: {},
            }],
        });
    }

    #renderPoints() {
        const source = this.#map.getSource(POINTS_SOURCE);

        if (!source) {
            return;
        }

        const features = this.#coordinates.map((coord, index) => {
            let role = 'middle';

            if (index === 0) {
                role = 'start';
            } else if (index === this.#coordinates.length - 1 && this.#coordinates.length > 1) {
                role = 'end';
            }

            return {
                type: 'Feature',
                id: index,
                geometry: { type: 'Point', coordinates: coord },
                properties: { index, role },
            };
        });

        source.setData({ type: 'FeatureCollection', features });
        this.#hoveredIndex = null;
    }

    #fitBoundsToCoordinates() {
        if (!this.#map || this.#coordinates.length === 0) {
            return;
        }

        if (this.#coordinates.length === 1) {
            this.#map.flyTo({ center: this.#coordinates[0], zoom: 15 });

            return;
        }

        const bounds = this.#coordinates.reduce(
            (b, c) => b.extend(c),
            new maplibregl.LngLatBounds(this.#coordinates[0], this.#coordinates[0]),
        );
        this.#map.fitBounds(bounds, { padding: 40, maxZoom: 17 });
    }

    #loadFromField() {
        const raw = this.geometryFieldTarget.value.trim();

        if (raw === '') {
            this.#coordinates = [];
            this.#refreshMapFromCoordinates();

            return;
        }

        const coords = this.#extractLineStringCoordinates(raw);

        if (coords === null) {
            this.#unsupportedGeometry = true;
            this.#showWarning();
            this.#disableDrawingControls();

            return;
        }

        this.#unsupportedGeometry = false;
        this.#hideWarning();
        this.#coordinates = coords;
        this.#refreshMapFromCoordinates();
        this.#fitBoundsToCoordinates();
    }

    #handleFieldInput() {
        if (this.#suppressFieldInput || !this.#initialized) {
            return;
        }

        const raw = this.geometryFieldTarget.value.trim();

        if (raw === '') {
            this.#unsupportedGeometry = false;
            this.#hideWarning();
            this.#coordinates = [];
            this.#refreshMapFromCoordinates();

            return;
        }

        const coords = this.#extractLineStringCoordinates(raw);

        if (coords === null) {
            return;
        }

        this.#unsupportedGeometry = false;
        this.#hideWarning();
        this.#coordinates = coords;
        this.#refreshMapFromCoordinates();
    }

    #writeFieldFromCoordinates() {
        if (!this.hasGeometryFieldTarget) {
            return;
        }

        const value = this.#coordinates.length >= 2
            ? JSON.stringify({ type: 'LineString', coordinates: this.#coordinates })
            : '';

        if (this.geometryFieldTarget.value === value) {
            return;
        }

        this.#suppressFieldInput = true;
        this.geometryFieldTarget.value = value;
        this.geometryFieldTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.#suppressFieldInput = false;
    }

    /**
     * Returns an array of [lng, lat] if the raw GeoJSON string represents a
     * single LineString (directly, wrapped in a Feature, or inside a
     * FeatureCollection with exactly one LineString). Returns null otherwise
     * (invalid JSON or unsupported shape that we refuse to overwrite).
     */
    #extractLineStringCoordinates(raw) {
        let parsed;

        try {
            parsed = JSON.parse(raw);
        } catch {
            return null;
        }

        const geometry = extractSingleGeometry(parsed);

        if (!geometry || geometry.type !== 'LineString' || !Array.isArray(geometry.coordinates)) {
            return null;
        }

        const coords = geometry.coordinates.filter(
            (c) => Array.isArray(c) && c.length >= 2 && Number.isFinite(c[0]) && Number.isFinite(c[1]),
        ).map((c) => [c[0], c[1]]);

        return coords;
    }

    #updateDrawButton() {
        if (this.hasDrawBtnTarget) {
            this.drawBtnTarget.classList.toggle('active', this.#isDrawing);
            this.drawBtnTarget.setAttribute('aria-pressed', this.#isDrawing ? 'true' : 'false');
        }
    }

    #setCursor(cursor) {
        if (this.#map) {
            this.#map.getCanvas().style.cursor = cursor;
        }
    }

    #showWarning() {
        if (this.hasWarningTarget) {
            this.warningTarget.hidden = false;
        }
    }

    #hideWarning() {
        if (this.hasWarningTarget) {
            this.warningTarget.hidden = true;
        }
    }

    #disableDrawingControls() {
        if (this.#isDrawing) {
            this.#isDrawing = false;
            this.#updateDrawButton();
            this.#setCursor('');
        }

        [this.hasDrawBtnTarget ? this.drawBtnTarget : null,
            this.hasUndoBtnTarget ? this.undoBtnTarget : null]
            .filter(Boolean)
            .forEach((btn) => { btn.disabled = true; });
    }

    #enableDrawingControls() {
        [this.hasDrawBtnTarget ? this.drawBtnTarget : null,
            this.hasUndoBtnTarget ? this.undoBtnTarget : null]
            .filter(Boolean)
            .forEach((btn) => { btn.disabled = false; });
    }
}
