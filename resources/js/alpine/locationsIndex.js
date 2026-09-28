import { reducedMotion } from './motion';

// Only ever used when no active location has coordinates yet — documented
// here, not a real place the site claims to be at.
const DEFAULT_CENTER = [-6.2, 106.8];
const DEFAULT_ZOOM = 10;
const SINGLE_ZOOM = 15;
const MAX_FIT_ZOOM = 15;
const HIGHLIGHT_MS = 1200;

/**
 * Home page #locations section: one Leaflet map auto-fit to every active
 * location, kept in sync with a filterable card list (components/sections/
 * locations.blade.php + location-card.blade.php share this same x-data
 * scope).
 *
 * The map's own IntersectionObserver (rootMargin 400px) is separate from —
 * and stricter than — the site-wide x-ignore/data-alpine-defer mechanism
 * (app.js) this section also carries: that one has an idle-timeout fallback
 * that wakes every deferred section a couple of seconds after load *however
 * far it still is from the viewport*, which is fine for cheap reactivity
 * (filter tabs, card entrance animation) but would defeat the point for
 * Leaflet specifically — a ~150 KB dynamic import plus live tile fetches.
 * Alpine itself may init this component early (via that shared mechanism);
 * this observer is what actually gates the heavy work until the map is
 * genuinely about to be scrolled into view, same idea as the lokasi_gudang
 * block's own mini map (opened on demand), just viewport-triggered instead
 * of click-triggered.
 */
export default ({ locations, labels }) => ({
    locations,
    labels,
    activeFilter: 'all',
    highlighted: null,
    markers: {},
    highlightTimer: null,
    mapObserver: null,
    L: null,
    map: null,

    init() {
        this.mapObserver = new IntersectionObserver((entries) => {
            if (!entries[0].isIntersecting) return;

            this.mapObserver.disconnect();
            this.loadMap();
        }, { rootMargin: '400px 0px' });

        this.mapObserver.observe(this.$refs.map);
    },

    async loadMap() {
        const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
        this.L = L;

        this.map = L.map(this.$refs.map, { scrollWheelZoom: false, zoomControl: true, attributionControl: true });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(this.map);

        this.buildMarkers();
        this.fitToVisible();
        requestAnimationFrame(() => this.map?.invalidateSize());
    },

    destroy() {
        clearTimeout(this.highlightTimer);
        this.mapObserver?.disconnect();
        this.map?.remove();
    },

    isVisible(id) {
        const location = this.locations.find((item) => item.id === id);
        return !!location && (this.activeFilter === 'all' || location.kind === this.activeFilter);
    },

    visibleLocations() {
        return this.locations.filter((location) => this.isVisible(location.id));
    },

    setFilter(kind) {
        if (this.activeFilter === kind) return;

        this.activeFilter = kind;
        this.buildMarkers();
        this.fitToVisible();
    },

    buildMarkers() {
        // The map may not exist yet if a filter tab is clicked before the map's own
        // observer has fired (loadMap() will call this itself once it does).
        if (!this.map) return;

        Object.values(this.markers).forEach((marker) => marker.remove());
        this.markers = {};

        this.visibleLocations()
            .filter((location) => location.lat !== null && location.lng !== null)
            .forEach((location) => {
                const icon = this.L.divIcon({
                    className: '',
                    html: `<span class="loc-marker${location.kind === 'hub' ? ' loc-marker--hub' : ''}">`
                        + `<span class="loc-marker-pulse loc-pulse${location.kind === 'hub' ? ' hub-radar' : ''}"></span>`
                        + '<span class="loc-marker-dot"></span></span>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });

                const marker = this.L.marker([location.lat, location.lng], {
                    icon,
                    // `alt` only becomes a real <img alt>, which a divIcon (a <div>, not
                    // an <img>) never has; `title` sets a title attribute on the icon
                    // element itself regardless of icon type, giving the marker (Leaflet
                    // makes it role="button" + keyboard-focusable below) an accessible
                    // name instead of a nameless button.
                    alt: location.name,
                    title: location.name,
                    keyboard: true,
                }).addTo(this.map);

                marker.bindPopup(this.popupContent(location));
                marker.on('click', () => this.highlightCard(location.id));
                this.markers[location.id] = marker;
            });
    },

    popupContent(location) {
        const wrap = document.createElement('div');
        wrap.className = 'min-w-40 text-sm';

        const name = document.createElement('p');
        name.className = 'font-bold text-ink';
        name.textContent = location.name;
        wrap.append(name);

        const city = document.createElement('p');
        city.className = 'text-ink-muted';
        city.textContent = location.city;
        wrap.append(city);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mt-2 inline-flex items-center gap-1 font-semibold text-brand';
        button.textContent = this.labels.viewDetails;
        button.addEventListener('click', () => this.highlightCard(location.id));
        wrap.append(button);

        return wrap;
    },

    fitToVisible() {
        if (!this.map) return;

        const points = this.visibleLocations().filter((location) => location.lat !== null && location.lng !== null);

        if (points.length === 0) {
            this.map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        } else if (points.length === 1) {
            this.map.setView([points[0].lat, points[0].lng], SINGLE_ZOOM);
        } else {
            const bounds = this.L.latLngBounds(points.map((location) => [location.lat, location.lng]));
            this.map.fitBounds(bounds, { padding: [48, 48], maxZoom: MAX_FIT_ZOOM });
        }
    },

    focusMap(id) {
        const location = this.locations.find((item) => item.id === id);
        if (!location || location.lat === null || location.lng === null || !this.map) return;

        const zoom = Math.max(this.map.getZoom(), SINGLE_ZOOM);

        if (reducedMotion()) {
            this.map.setView([location.lat, location.lng], zoom);
        } else {
            this.map.flyTo([location.lat, location.lng], zoom, { duration: 0.6 });
        }

        this.markers[id]?.openPopup();
    },

    highlightCard(id) {
        document.getElementById(`location-${id}`)?.scrollIntoView({
            behavior: reducedMotion() ? 'auto' : 'smooth',
            block: 'center',
        });
        clearTimeout(this.highlightTimer);
        this.highlighted = id;
        this.highlightTimer = setTimeout(() => {
            this.highlighted = null;
        }, HIGHLIGHT_MS);
    },
});
