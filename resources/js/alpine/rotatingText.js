import { reducedMotion } from './motion';

const EXIT_MS = 300;

/**
 * Rotating service label, driven by the parent hero `index` (no timer of its own).
 * Characters enter from below with a 20ms stagger and leave upwards.
 */
export default (labels) => ({
    // Declared here so they stay on this component (not the outer hero scope).
    current: null,
    target: null,
    leaving: false,
    timer: null,

    init() {
        this.current = this.$el.querySelector('.rt-set');
        this.leaving = false;

        if (!reducedMotion()) {
            const first = this.build(labels[0], false);
            this.current.replaceWith(first);
            this.current = first;
        }

        this.$watch('index', (i) => this.show(labels[i]));
    },

    destroy() {
        clearTimeout(this.timer);
    },

    show(label) {
        if (reducedMotion()) {
            this.current.textContent = label;
            return;
        }

        this.target = label;

        if (this.leaving) return;

        this.leaving = true;
        this.current.classList.remove('rt-in');
        this.current.classList.add('rt-out');

        this.timer = setTimeout(() => {
            const next = this.build(this.target, true);
            this.current.replaceWith(next);
            this.current = next;
            this.leaving = false;
        }, EXIT_MS);
    },

    build(label, animate) {
        const set = document.createElement('span');
        set.className = animate ? 'rt-set rt-in' : 'rt-set';
        let i = 0;

        label.split(' ').forEach((word, w) => {
            if (w > 0) set.append(' ');

            const wordEl = document.createElement('span');
            wordEl.className = 'rt-word';

            for (const char of word) {
                const charEl = document.createElement('span');
                charEl.className = 'rt-char';
                charEl.style.setProperty('--i', String(i++));
                charEl.textContent = char;
                wordEl.append(charEl);
            }

            set.append(wordEl);
        });

        return set;
    },
});
