import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { mapStyles } from 'carte-facile';
import { addHouseNumbersLayer, addMeasureLineLayer } from '../maps/layers';
import { extractSingleGeometry } from '../maps/geojson';
import { getMeasureTypeStyle, buildMeasurePointPaint } from '../maps/measure_type_styles';
import '../styles/components/draw-line-map.css';

const LINE_SOURCE = 'draw-line-source';
const LINE_LAYER = 'draw-line-layer';
const FILL_LAYER = 'draw-fill-layer';
const POINTS_SOURCE = 'draw-points-source';
const POINTS_LAYER = 'draw-points-layer';
const EMPTY_FC = { type: 'FeatureCollection', features: [] };

const SEARCH_DEBOUNCE_MS = 250;
const SEARCH_MIN_LENGTH = 3;
const SEARCH_LIMIT = 5;
const SEARCH_ZOOM_BY_TYPE = {
  housenumber: 18,
  street: 17,
  locality: 16,
  municipality: 13,
};
const SEARCH_DEFAULT_ZOOM = 15;

export default class extends Controller {
  static targets = [
    'container',
    'geometryField',
    'drawBtn',
    'undoBtn',
    'clearBtn',
    'warning',
    'searchInput',
    'searchResults',
  ];
  static values = {
    centerJson: { type: String, default: '[2.725, 47.16]' },
    zoom: { type: Number, default: 15 },
    // Étendue de l'organisation '[minLon, minLat, maxLon, maxLat]' : quand elle est
    // fournie, la carte s'ouvre zoomée dessus au lieu du centre/zoom par défaut
    orgBboxJson: { type: String, default: '' },
    // Indique si la localisation a déjà été enregistrée (l'état "Modifier le tracé"
    // du bouton n'est proposé qu'après validation du formulaire)
    persisted: { type: Boolean, default: false },
    measureType: { type: String, default: '' },
    searchApiUrl: { type: String, default: '' },
    // 'LineString' (tracé libre) ou 'Polygon' (périmètre d'une zone, anneau fermé automatiquement)
    geometryType: { type: String, default: 'LineString' },
    start: {
      type: Object,
      default: {
        label: 'regulation.location.raw_geojson.draw_line_map.draw.start',
        icon: 'fr-icon-x-draw-line',
      },
    },
    finish: {
      type: Object,
      default: {
        label: 'regulation.location.raw_geojson.draw_line_map.draw.finish',
        icon: 'fr-icon-checkbox-fill',
      },
    },
    edit: {
      type: Object,
      default: {
        label: 'regulation.location.raw_geojson.draw_line_map.draw.edit',
        icon: 'fr-icon-edit-line',
      },
    },
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
      this.geometryFieldTarget.removeEventListener(
        'input',
        this.#boundFieldInput,
      );
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

    this.#searchDebounceTimer = setTimeout(
      () => this.#searchAddresses(query),
      SEARCH_DEBOUNCE_MS,
    );
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
      this.#selectAddress(this.#searchResults[index]);
    } else if (event.key === 'Escape') {
      this.#hideSearchResults();
    }
  }

  async #searchAddresses(query) {
    this.#searchAbortController?.abort();
    this.#searchAbortController = new AbortController();

    const url = new URL(this.searchApiUrlValue);
    url.searchParams.set('q', query);
    url.searchParams.set('limit', String(SEARCH_LIMIT));
    url.searchParams.set('autocomplete', '1');

    try {
      const response = await fetch(url.toString(), {
        signal: this.#searchAbortController.signal,
      });

      if (!response.ok) {
        this.#renderSearchResults([]);
        return;
      }

      const data = await response.json();
      const features =
        data && Array.isArray(data.features) ? data.features : [];
      this.#renderSearchResults(features);
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
      empty.textContent = 'Aucune adresse trouvée';
      this.searchResultsTarget.appendChild(empty);
      this.#showSearchResults();

      return;
    }

    results.forEach((feature, index) => {
      const props = feature.properties || {};
      const item = document.createElement('li');
      item.className = 'draw-line-map-search__result';
      item.setAttribute('role', 'option');
      item.dataset.index = String(index);

      const name = document.createElement('span');
      name.className = 'draw-line-map-search__result-name';
      name.textContent = props.label || props.name || '';

      const item_meta_parts = [];
      if (props.type === 'municipality') {
        if (props.context) {
          item_meta_parts.push(props.context);
        }
      } else if (props.city) {
        item_meta_parts.push(`${props.postcode || ''} ${props.city}`.trim());
      } else if (props.context) {
        item_meta_parts.push(props.context);
      }

      const meta = document.createElement('span');
      meta.className = 'draw-line-map-search__result-meta';
      meta.textContent =
        item_meta_parts.length > 0 ? ` — ${item_meta_parts.join(', ')}` : '';

      item.appendChild(name);
      item.appendChild(meta);

      item.addEventListener('mousedown', (e) => {
        // Prevent input blur before click triggers selection
        e.preventDefault();
      });
      item.addEventListener('click', () => this.#selectAddress(feature));
      item.addEventListener('mouseenter', () =>
        this.#setSearchActiveIndex(index),
      );

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
    const items = this.searchResultsTarget.querySelectorAll(
      '.draw-line-map-search__result',
    );
    items.forEach((el, i) => {
      el.classList.toggle('draw-line-map-search__result--active', i === index);
    });
  }

  #selectAddress(feature) {
    if (!feature || !this.#map) {
      this.#hideSearchResults();

      return;
    }

    const props = feature.properties || {};
    const geometry = feature.geometry || {};

    if (Array.isArray(feature.bbox) && feature.bbox.length === 4) {
      const [minLng, minLat, maxLng, maxLat] = feature.bbox;
      this.#map.fitBounds(
        [
          [minLng, minLat],
          [maxLng, maxLat],
        ],
        { padding: 40, maxZoom: 16, animate: true },
      );
    } else if (
      Array.isArray(geometry.coordinates) &&
      geometry.coordinates.length === 2
    ) {
      const zoom = SEARCH_ZOOM_BY_TYPE[props.type] || SEARCH_DEFAULT_ZOOM;
      this.#map.flyTo({ center: geometry.coordinates, zoom });
    }

    if (this.hasSearchInputTarget) {
      this.searchInputTarget.value =
        props.label || props.name || this.searchInputTarget.value;
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

  // Retourne l'étendue de l'organisation sous forme [[minLon, minLat], [maxLon, maxLat]],
  // ou null si elle est absente ou invalide (la carte retombe alors sur centre/zoom)
  #organizationBounds() {
    if (!this.orgBboxJsonValue) {
      return null;
    }

    let bbox;

    try {
      bbox = JSON.parse(this.orgBboxJsonValue);
    } catch {
      return null;
    }

    if (
      !Array.isArray(bbox) ||
      bbox.length !== 4 ||
      !bbox.every(Number.isFinite)
    ) {
      return null;
    }

    return [
      [bbox[0], bbox[1]],
      [bbox[2], bbox[3]],
    ];
  }

  #isSectionHidden() {
    return (
      !!this.#hiddenAncestor && this.#hiddenAncestor.hasAttribute('hidden')
    );
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
        this.#fitBoundsToCoordinates({ animate: false });
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
      const orgBounds = this.#organizationBounds();

      this.#map = new maplibregl.Map({
        container: this.containerTarget,
        style: mapStyles.desaturated,
        ...(orgBounds
          ? { bounds: orgBounds, fitBoundsOptions: { padding: 40 } }
          : {
              center: JSON.parse(this.centerJsonValue),
              zoom: this.zoomValue,
            }),
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
    const style = getMeasureTypeStyle(this.measureTypeValue);

    if (this.#isPolygon()) {
      // Remplissage translucide du périmètre, sous la ligne de contour
      this.#map.addLayer(
        {
          id: FILL_LAYER,
          type: 'fill',
          source: LINE_SOURCE,
          filter: ['==', '$type', 'Polygon'],
          paint: {
            'fill-color': style.color,
            'fill-opacity': 0.12,
          },
        },
        LINE_LAYER,
      );
    }
    this.#map.addLayer({
      id: POINTS_LAYER,
      type: 'circle',
      source: POINTS_SOURCE,
      paint: {
        ...buildMeasurePointPaint(style, { radius: 5 }),
        'circle-radius': [
          'case',
          ['boolean', ['feature-state', 'hover'], false],
          8,
          5,
        ],
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

    const hits = this.#map.queryRenderedFeatures(e.point, {
      layers: [POINTS_LAYER],
    });

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
    this.#updateDrawButton();
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
      features: [
        {
          type: 'Feature',
          geometry: this.#buildGeometry(),
          properties: {},
        },
      ],
    });
  }

  #isPolygon() {
    return this.geometryTypeValue === 'Polygon';
  }

  #buildGeometry() {
    if (this.#isPolygon() && this.#coordinates.length >= 3) {
      // Un anneau GeoJSON doit être fermé (dernier point = premier point)
      return {
        type: 'Polygon',
        coordinates: [[...this.#coordinates, this.#coordinates[0]]],
      };
    }

    return { type: 'LineString', coordinates: this.#coordinates };
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
      } else if (
        index === this.#coordinates.length - 1 &&
        this.#coordinates.length > 1
      ) {
        role = 'end';
      }

      return {
        type: 'Feature',
        id: index,
        geometry: { type: 'Point', coordinates: coord },
        properties: { index, role },
      };
    });

    this.#setHoverState(null);
    source.setData({ type: 'FeatureCollection', features });
  }

  #fitBoundsToCoordinates({ animate = true } = {}) {
    if (!this.#map || this.#coordinates.length === 0) {
      return;
    }

    if (this.#coordinates.length === 1) {
      if (animate) {
        this.#map.flyTo({ center: this.#coordinates[0], zoom: 15 });
        return;
      }

      this.#map.jumpTo({ center: this.#coordinates[0], zoom: 15 });
      return;
    }

    const bounds = this.#coordinates.reduce(
      (b, c) => b.extend(c),
      new maplibregl.LngLatBounds(this.#coordinates[0], this.#coordinates[0]),
    );
    this.#map.fitBounds(bounds, { padding: 40, maxZoom: 17, animate });
  }

  #loadFromField() {
    const raw = this.geometryFieldTarget.value.trim();

    if (raw === '') {
      this.#coordinates = [];
      this.#refreshMapFromCoordinates();

      return;
    }

    this.#prettifyField(raw);

    const coords = this.#extractCoordinates(raw);

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
    this.#fitBoundsToCoordinates({ animate: false });
  }

  #prettifyField(raw) {
    let parsed;

    try {
      parsed = JSON.parse(raw);
    } catch {
      return;
    }

    const pretty = JSON.stringify(parsed, null, 2);

    if (this.geometryFieldTarget.value === pretty) {
      return;
    }

    this.#suppressFieldInput = true;
    this.geometryFieldTarget.value = pretty;
    this.geometryFieldTarget.dispatchEvent(
      new Event('input', { bubbles: true }),
    );
    this.#suppressFieldInput = false;
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

    const coords = this.#extractCoordinates(raw);

    if (coords === null) {
      return;
    }

    this.#unsupportedGeometry = false;
    this.#hideWarning();
    this.#coordinates = coords;
    this.#refreshMapFromCoordinates();
    this.#fitBoundsToCoordinates({ animate: true });
  }

  #writeFieldFromCoordinates() {
    if (!this.hasGeometryFieldTarget) {
      return;
    }

    const minPoints = this.#isPolygon() ? 3 : 2;
    const value =
      this.#coordinates.length >= minPoints
        ? JSON.stringify(this.#buildGeometry(), null, 2)
        : '';

    if (this.geometryFieldTarget.value === value) {
      return;
    }

    this.#suppressFieldInput = true;
    this.geometryFieldTarget.value = value;
    this.geometryFieldTarget.dispatchEvent(
      new Event('input', { bubbles: true }),
    );
    this.#suppressFieldInput = false;
  }

  /**
   * Returns an array of [lng, lat] if the raw GeoJSON string represents a
   * single geometry matching the configured geometry type (directly, wrapped
   * in a Feature, or inside a FeatureCollection with exactly one feature).
   * In 'Polygon' mode, only a single-ring polygon (no holes) is supported and
   * the closing point is removed for point-by-point editing. Returns null
   * otherwise (invalid JSON or unsupported shape that we refuse to overwrite).
   */
  #extractCoordinates(raw) {
    let parsed;

    try {
      parsed = JSON.parse(raw);
    } catch {
      return null;
    }

    const geometry = extractSingleGeometry(parsed);

    if (!geometry) {
      return null;
    }

    if (this.#isPolygon()) {
      if (
        geometry.type !== 'Polygon' ||
        !Array.isArray(geometry.coordinates) ||
        geometry.coordinates.length !== 1
      ) {
        return null;
      }

      const ring = this.#sanitizeCoordinates(geometry.coordinates[0]);

      // Retire le point de fermeture de l'anneau (dernier = premier) pour l'édition
      if (ring.length >= 2) {
        const [firstLng, firstLat] = ring[0];
        const [lastLng, lastLat] = ring[ring.length - 1];

        if (firstLng === lastLng && firstLat === lastLat) {
          ring.pop();
        }
      }

      return ring;
    }

    if (
      geometry.type !== 'LineString' ||
      !Array.isArray(geometry.coordinates)
    ) {
      return null;
    }

    return this.#sanitizeCoordinates(geometry.coordinates);
  }

  #sanitizeCoordinates(coordinates) {
    return coordinates
      .filter(
        (c) =>
          Array.isArray(c) &&
          c.length >= 2 &&
          Number.isFinite(c[0]) &&
          Number.isFinite(c[1]),
      )
      .map((c) => [c[0], c[1]]);
  }

  #updateDrawButton() {
    if (!this.hasDrawBtnTarget) {
      return;
    }

    this.drawBtnTarget.classList.toggle('active', this.#isDrawing);
    this.drawBtnTarget.setAttribute(
      'aria-pressed',
      this.#isDrawing ? 'true' : 'false',
    );
    this.drawBtnTarget.classList.remove(
      this.startValue.icon,
      this.finishValue.icon,
      this.editValue.icon,
    );

    if (this.#isDrawing) {
      this.drawBtnTarget.classList.add(this.finishValue.icon);
      this.drawBtnTarget.textContent = this.finishValue.label;
      return;
    }

    // L'état "Modifier le tracé" n'apparaît que pour une localisation déjà
    // enregistrée : tant que le formulaire n'a pas été validé, le tracé reste
    // librement modifiable et le bouton garde son libellé initial
    if (this.#coordinates.length > 0 && this.persistedValue) {
      this.drawBtnTarget.classList.add(this.editValue.icon);
      this.drawBtnTarget.textContent = this.editValue.label;
      return;
    }

    this.drawBtnTarget.classList.add(this.startValue.icon);
    this.drawBtnTarget.textContent = this.startValue.label;
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

    [
      this.hasDrawBtnTarget ? this.drawBtnTarget : null,
      this.hasUndoBtnTarget ? this.undoBtnTarget : null,
    ]
      .filter(Boolean)
      .forEach((btn) => {
        btn.disabled = true;
      });
  }

  #enableDrawingControls() {
    [
      this.hasDrawBtnTarget ? this.drawBtnTarget : null,
      this.hasUndoBtnTarget ? this.undoBtnTarget : null,
    ]
      .filter(Boolean)
      .forEach((btn) => {
        btn.disabled = false;
      });
  }
}
