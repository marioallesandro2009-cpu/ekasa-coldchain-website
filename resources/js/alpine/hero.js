import { reducedMotion } from './motion';

/**
 * Hero entrance: the last item (indicators, data-delay 0.4s) finishes its 0.6s
 * transition 1s after start. Photos 2..n are only requested after that, so they do
 * not compete with the first paint (LCP) for bandwidth.
 */
const ENTRANCE_MS = 1000;

/**
 * Hero slideshow. One index drives the photo and the rotating label.
 * The progress bar CSS animation is the timer, so it pauses together with the hero.
 */
export default (config) => ({
    index: 0,
    previous: null,
    pending: null,
    hover: false,
    hidden: document.hidden,
    ready: false,

    get paused() {
        return this.hover || this.hidden;
    },

    init() {
        this.reduced = reducedMotion();
        this.count = config.slides.length;

        // Reduced motion: no zoom, no crossfade, 2 seconds longer per slide.
        const seconds = config.duration + (this.reduced ? 2 : 0);
        this.$el.style.setProperty('--slide-duration', `${seconds}s`);
        this.$el.style.setProperty('--kb-duration', `${seconds + 1}s`);

        this.photos = [this.$refs.photos.querySelector('[data-index="0"]')];
        this.loaded = config.slides.map((_, i) => i === 0);

        this.onVisibility = () => {
            this.hidden = document.hidden;
        };
        document.addEventListener('visibilitychange', this.onVisibility);

        this.applyPhotoState(false);
        this.ready = true;
        this.restartProgress();

        // Ken Burns starts once the page has loaded; the other photos once the page has
        // loaded AND the entrance animation is over (whichever comes last).
        const entranceDone = new Promise((resolve) => {
            this.entranceTimer = setTimeout(resolve, this.reduced ? 0 : ENTRANCE_MS);
        });
        this.onLoad = () => {
            if (!this.reduced) this.photos[this.index]?.classList.add('is-zooming');
            entranceDone.then(() => this.loadRemainingPhotos());
        };

        if (document.readyState === 'complete') {
            this.onLoad();
        } else {
            window.addEventListener('load', this.onLoad, { once: true });
        }
    },

    destroy() {
        clearTimeout(this.entranceTimer);
        document.removeEventListener('visibilitychange', this.onVisibility);
        window.removeEventListener('load', this.onLoad);
    },

    /**
     * Photo 2 is requested alone first (it is needed first, with the full bandwidth);
     * photos 3..n follow once it is ready. The <img> elements are all created now
     * so the stacking order stays 0..n.
     */
    loadRemainingPhotos() {
        const images = config.slides.slice(1).map((slide, offset) => {
            const i = offset + 1;
            const img = document.createElement('img');

            img.className = 'hero-photo absolute inset-0 size-full object-cover';
            img.dataset.index = String(i);
            img.sizes = '100vw';
            img.width = slide.width;
            img.height = slide.height;
            img.alt = slide.alt;
            img.decoding = 'async';
            img.setAttribute('aria-hidden', 'true');

            this.photos[i - 1].after(img);
            this.photos[i] = img;

            return { i, img, slide };
        });

        const [next, ...rest] = images;
        if (!next) return;

        this.fetchPhoto(next, 'high').then(() => rest.forEach((photo) => this.fetchPhoto(photo, 'low')));
    },

    /**
     * Requests one photo and marks it ready once it is downloaded and decoded,
     * so the crossfade never starts on a half-decoded image.
     */
    fetchPhoto({ i, img, slide }, priority) {
        return new Promise((resolve) => {
            const ready = () => {
                this.loaded[i] = true;
                if (this.pending === i) this.goTo(i);
                resolve();
            };

            img.addEventListener('load', () => img.decode().then(ready, ready), { once: true });
            // A failed photo is never shown (loaded stays false), but does not block the others.
            img.addEventListener('error', () => resolve(), { once: true });
            img.fetchPriority = priority;
            img.srcset = slide.srcset;
            img.src = slide.src;
        });
    },

    goTo(target) {
        if (target === this.index) {
            this.pending = null;
            return;
        }

        // Never switch to a photo that has not finished loading: wait for it.
        if (!this.loaded[target]) {
            this.pending = target;
            return;
        }

        this.pending = null;
        this.previous = this.index;
        this.index = target;
        this.applyPhotoState(true);
        this.restartProgress();
    },

    onProgressEnd(i) {
        if (i === this.index) this.goTo((this.index + 1) % this.count);
    },

    applyPhotoState(changed) {
        this.photos.forEach((img, i) => {
            if (!img) return;

            const active = i === this.index;
            const previous = i === this.previous;

            img.classList.toggle('is-active', active);
            img.classList.toggle('is-previous', previous);
            img.classList.toggle('is-fading', active && changed && !this.reduced);
            img.setAttribute('aria-hidden', active ? 'false' : 'true');

            if (this.reduced) return;

            if (active && changed) {
                // Restart the zoom for the incoming photo; the outgoing one keeps zooming while it fades.
                img.classList.remove('is-zooming');
                void img.offsetWidth;
                img.classList.add('is-zooming');
            } else if (!active && !previous) {
                img.classList.remove('is-zooming');
            }
        });
    },

    restartProgress() {
        this.$root.querySelectorAll('.hero-progress').forEach((bar) => bar.classList.remove('is-running'));

        const bar = this.$root.querySelector(`[data-progress="${this.index}"]`);

        if (bar) {
            void bar.offsetWidth;
            bar.classList.add('is-running');
        }
    },
});
