import { reducedMotion } from './motion';

const SWIPE = 50;

/**
 * Fleet showcase: one truck at a time (tabs pattern: thumbnails are tabs, each
 * truck a tabpanel), previous/next buttons, arrow keys on the tabs, swipe on
 * touch screens and an aria-live announcement. The track moves 600 ms without
 * bounce (CSS); with reduced motion it switches at once. Without JS every truck
 * is listed one below the other.
 */
export default ({ count, announce }) => ({
    index: 0,
    count,
    message: '',
    startX: null,

    init() {
        this.$el.classList.add('is-ready');
    },

    go(index, focusTab = false) {
        this.index = (index + this.count) % this.count;
        this.message = announce[this.index];

        if (focusTab) this.$nextTick(() => this.$refs.tabs.querySelectorAll('[role="tab"]')[this.index]?.focus());
    },

    prev() {
        this.go(this.index - 1);
    },

    next() {
        this.go(this.index + 1);
    },

    onTabKey(event) {
        const keys = { ArrowRight: this.index + 1, ArrowLeft: this.index - 1, Home: 0, End: this.count - 1 };
        if (!(event.key in keys)) return;

        event.preventDefault();
        this.go(keys[event.key], true);
    },

    trackStyle() {
        return `transform: translateX(-${this.index * 100}%);${reducedMotion() ? 'transition:none;' : ''}`;
    },

    pointerDown(event) {
        if (event.pointerType === 'mouse') return;
        this.startX = event.clientX;
    },

    pointerUp(event) {
        if (this.startX === null) return;

        const delta = event.clientX - this.startX;
        this.startX = null;

        if (Math.abs(delta) > SWIPE) delta < 0 ? this.next() : this.prev();
    },
});
