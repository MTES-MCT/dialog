import { Controller } from "@hotwired/stimulus"

/**
 * Toggles the visibility of the map filters sidebar so it can be collapsed,
 * which is especially useful when the map is embedded in an iframe.
 */
export default class extends Controller {
    static targets = ['collapseButton', 'expandButton', 'map'];
    static classes = ['collapsed'];

    connect() {
        this._syncButtons();
        this._alignHandles();

        // Keep the handles aligned with the search bar when the layout reflows
        // (window resize, responsive breakpoints, font loading...).
        this._resizeObserver = new ResizeObserver(() => this._alignHandles());
        this._resizeObserver.observe(this.element);

        // Once the collapse animation ends, make sure the MapLibre canvas matches
        // its final container size.
        if (this.hasMapTarget) {
            this._onTransitionEnd = () => this.mapTarget.map?.resize();
            this.mapTarget.addEventListener('transitionend', this._onTransitionEnd);
        }
    }

    disconnect() {
        this._resizeObserver?.disconnect();

        if (this.hasMapTarget && this._onTransitionEnd) {
            this.mapTarget.removeEventListener('transitionend', this._onTransitionEnd);
        }
    }

    toggle() {
        this.element.classList.toggle(this.collapsedClass);
        this._syncButtons();
        this._alignHandles();
        this._resizeMap();
    }

    _syncButtons() {
        const collapsed = this.element.classList.contains(this.collapsedClass);

        if (this.hasCollapseButtonTarget) {
            this.collapseButtonTarget.setAttribute('aria-expanded', String(!collapsed));
        }

        if (this.hasExpandButtonTarget) {
            this.expandButtonTarget.hidden = !collapsed;
        }
    }

    /**
     * Aligns both the collapse and expand handles with the vertical center of the
     * search bar so they always sit at the same level. The measured offset is
     * memorized: while collapsed the search bar is hidden, so we reuse the last
     * known value instead of recomputing from a hidden (zero-sized) element.
     */
    _alignHandles() {
        const collapsed = this.element.classList.contains(this.collapsedClass);
        const searchBar = this.element.querySelector('.fr-search-bar');

        if (!collapsed && searchBar) {
            const barRect = searchBar.getBoundingClientRect();

            if (barRect.height > 0) {
                const centerY = barRect.top + (barRect.height / 2);
                this._expandTop = centerY - this.element.getBoundingClientRect().top;

                const sidebar = this.hasCollapseButtonTarget
                    ? this.collapseButtonTarget.closest('.d-map-sidebar')
                    : null;
                if (sidebar) {
                    this._collapseTop = centerY - sidebar.getBoundingClientRect().top;
                }
            }
        }

        if (this.hasCollapseButtonTarget && this._collapseTop !== undefined) {
            this.collapseButtonTarget.style.top = `${this._collapseTop}px`;
            this.collapseButtonTarget.style.transform = 'translate(100%, -50%)';
        }

        if (this.hasExpandButtonTarget && this._expandTop !== undefined) {
            this.expandButtonTarget.style.top = `${this._expandTop}px`;
            this.expandButtonTarget.style.transform = 'translateY(-50%)';
        }
    }

    _resizeMap() {
        if (!this.hasMapTarget) {
            return;
        }

        // The MapLibre instance is exposed on the <d-map> element once ready.
        // Resizing keeps the canvas in sync with its new container width.
        this.mapTarget.map?.resize();
    }
}
