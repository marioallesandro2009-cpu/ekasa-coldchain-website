/**
 * Locations block: clicking a map marker or a card opens a side panel with the
 * location's photo, details and a small OpenStreetMap map. Leaflet (and its CSS)
 * is only downloaded the first time a panel opens, as a separate chunk.
 * Without JS the markers and cards are plain links (card anchor, Google Maps).
 */
export default ({ locations, labels }) => ({
    locations,
    labels,
    current: null,
    trigger: null,
    map: null,
    marker: null,

    open(id, event) {
        const location = this.locations.find((item) => item.id === id);
        if (!location) return;

        this.trigger = event?.currentTarget ?? document.activeElement;
        this.current = location;
        document.documentElement.classList.add('overflow-hidden');

        this.$nextTick(() => {
            this.$refs.close?.focus();
            this.showMap();
        });
    },

    close() {
        if (!this.current) return;

        this.current = null;
        document.documentElement.classList.remove('overflow-hidden');
        this.trigger?.focus?.();
    },

    async showMap() {
        const location = this.current;
        if (!location || location.lat === null || location.lng === null) return;

        const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
        if (this.current !== location) return;

        const point = [location.lat, location.lng];

        if (!this.map) {
            this.map = L.map(this.$refs.map, { scrollWheelZoom: false, zoomControl: true, attributionControl: true })
                .setView(point, 14);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);
            // A brand-coloured dot instead of Leaflet's default marker images.
            const brand = getComputedStyle(document.documentElement).getPropertyValue('--color-brand').trim() || '#0f6b46';
            this.marker = L.circleMarker(point, { radius: 9, color: '#fff', weight: 3, fillColor: brand, fillOpacity: 1 }).addTo(this.map);
        } else {
            this.map.setView(point, 14);
            this.marker.setLatLng(point);
        }

        // The panel was just shown: let Leaflet measure it.
        requestAnimationFrame(() => this.map?.invalidateSize());
    },

    destroy() {
        document.documentElement.classList.remove('overflow-hidden');
        this.map?.remove();
    },
});
