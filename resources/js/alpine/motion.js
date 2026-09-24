/** Shared helpers for the Alpine components. */

export function reducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export const DESKTOP_QUERY = '(min-width: 1024px)';

/** Navbar height once scrolled: 68px desktop, 64px mobile. */
export function navbarOffset() {
    return window.matchMedia(DESKTOP_QUERY).matches ? 68 : 64;
}
