import { reducedMotion } from './motion';

const THRESHOLD = 0.1;

/**
 * Section titles (port of React Bits SplitText, words only, no GSAP): once the
 * fonts are loaded the server-rendered text is split into word spans; when 10%
 * of the element is visible the words fade in from 40px lower, 45ms apart
 * (CSS: .split-word). Runs once. Inline markup (<br>, <span>...) is kept: only
 * text nodes are split. With reduced motion the DOM is left as it is.
 */
export default () => ({
    observer: null,

    init() {
        const el = this.$el;

        if (reducedMotion()) {
            el.dataset.split = '';
            return;
        }

        // Split with the final font, so word widths do not change afterwards.
        document.fonts.ready.then(() => {
            if (!el.isConnected || el.dataset.split !== undefined) return;

            split(el);
            el.dataset.split = '';

            this.observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;

                el.classList.add('is-in');
                this.observer.disconnect();
            }, { threshold: THRESHOLD });

            this.observer.observe(el);
        });
    },

    destroy() {
        this.observer?.disconnect();
    },
});

/** Wrap every word of the element's text nodes in <span class="split-word">. */
function split(el) {
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    const nodes = [];
    let index = 0;

    while (walker.nextNode()) nodes.push(walker.currentNode);

    for (const node of nodes) {
        const fragment = document.createDocumentFragment();

        for (const part of node.textContent.split(/(\s+)/)) {
            if (part === '') continue;

            if (/^\s+$/.test(part)) {
                fragment.append(part);
                continue;
            }

            const span = document.createElement('span');
            span.className = 'split-word';
            span.style.setProperty('--i', String(index++));
            span.textContent = part;
            fragment.append(span);
        }

        node.replaceWith(fragment);
    }
}
