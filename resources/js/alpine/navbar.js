import { DESKTOP_QUERY, navbarOffset, reducedMotion } from './motion';

const SCROLL_THRESHOLD = 40;
const MEGA_CLOSE_DELAY = 150;
const MOBILE_CLOSE_MS = 280;

/**
 * Navbar: scroll state, scrollspy, Services mega menu, mobile menu.
 */
export default ({ forceSolid = false } = {}) => ({
    scrolled: false,
    forceSolid,
    active: '',
    megaOpen: false,
    megaSource: null,
    mobileOpen: false,
    mobileClosing: false,

    init() {
        this.onScroll = () => {
            this.scrolled = window.scrollY > SCROLL_THRESHOLD;
        };
        this.onScroll();
        window.addEventListener('scroll', this.onScroll, { passive: true });

        this.desktop = window.matchMedia(DESKTOP_QUERY);
        this.onBreakpoint = (event) => {
            if (event.matches) {
                this.closeMobile(false, true);
            } else {
                this.closeMega();
            }
        };
        this.desktop.addEventListener('change', this.onBreakpoint);

        this.initScrollSpy();
    },

    destroy() {
        window.removeEventListener('scroll', this.onScroll);
        window.removeEventListener('resize', this.onResize);
        this.desktop.removeEventListener('change', this.onBreakpoint);
        this.spy?.disconnect();
        clearTimeout(this.closeTimer);
        document.documentElement.classList.remove('overflow-hidden');
    },

    /**
     * Active item = the section under a 1px line just below the navbar.
     * The observer only signals crossings; the active id is measured then.
     */
    initScrollSpy() {
        const ids = [...new Set([...this.$root.querySelectorAll('[data-spy]')].map((link) => link.dataset.spy))];
        const sections = ids.map((id) => document.getElementById(id)).filter(Boolean);

        const update = () => {
            const line = navbarOffset() + 1;
            const current = sections.find((section) => {
                const rect = section.getBoundingClientRect();
                return rect.top <= line && rect.bottom > line;
            });

            this.active = current?.id ?? '';
        };

        const observe = () => {
            this.spy?.disconnect();
            const line = navbarOffset() + 1;
            this.spy = new IntersectionObserver(update, {
                rootMargin: `-${line}px 0px -${Math.max(0, window.innerHeight - line - 1)}px 0px`,
            });
            sections.forEach((section) => this.spy.observe(section));
            update();
        };

        observe();
        this.onResize = observe;
        window.addEventListener('resize', this.onResize, { passive: true });
    },

    // Mega menu -------------------------------------------------------------

    megaEnter(event) {
        if (event.pointerType !== 'mouse') return;
        clearTimeout(this.closeTimer);
        if (!this.megaOpen) this.megaSource = 'hover';
        this.megaOpen = true;
    },

    megaLeave(event) {
        if (event.pointerType !== 'mouse') return;
        clearTimeout(this.closeTimer);
        this.closeTimer = setTimeout(() => this.closeMega(), MEGA_CLOSE_DELAY);
    },

    toggleMega() {
        // A click right after a hover-open keeps the menu open instead of closing it.
        if (this.megaOpen && this.megaSource === 'hover') {
            this.megaSource = 'click';
            return;
        }

        if (this.megaOpen) {
            this.closeMega();
        } else {
            this.openMega();
        }
    },

    openMega(focusFirst = false) {
        clearTimeout(this.closeTimer);
        this.megaOpen = true;
        this.megaSource = 'click';

        if (focusFirst) {
            this.$nextTick(() => this.$refs.mega?.querySelector('.mega-panel a')?.focus());
        }
    },

    closeMega() {
        clearTimeout(this.closeTimer);
        this.megaOpen = false;
        this.megaSource = null;
    },

    megaFocusOut(event) {
        if (!this.$refs.mega?.contains(event.relatedTarget)) this.closeMega();
    },

    closeAll() {
        if (this.megaOpen) {
            const focusWasInside = this.$refs.mega?.contains(document.activeElement);
            this.closeMega();
            if (focusWasInside) this.$refs.megaTrigger?.focus();
        }

        if (this.mobileOpen) this.closeMobile(true);
    },

    // Mobile menu -----------------------------------------------------------

    openMobile() {
        this.mobileClosing = false;
        this.mobileOpen = true;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.mobileClose?.focus());
    },

    closeMobile(restoreFocus = true, instant = false) {
        if (!this.mobileOpen) return;

        document.documentElement.classList.remove('overflow-hidden');

        const done = () => {
            this.mobileOpen = false;
            this.mobileClosing = false;
            if (restoreFocus) this.$refs.hamburger?.focus();
        };

        if (instant || reducedMotion()) {
            done();
            return;
        }

        this.mobileClosing = true;
        setTimeout(done, MOBILE_CLOSE_MS);
    },

    /** Keep keyboard focus inside the open mobile menu. */
    trapFocus(event) {
        if (event.key !== 'Tab') return;

        const focusable = [...this.$refs.mobileMenu.querySelectorAll('a[href], button, summary')].filter(
            (el) => el.offsetParent !== null,
        );
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
});
