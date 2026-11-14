/**
 * Lite performance mode for slower devices (mostly low-end Android phones).
 *
 * The initial `data-perf` attribute on <html> is set by an inline script in app.blade.php
 * before first paint, so CSS can drop expensive effects (backdrop blur, looping animations)
 * without a flash. This module reads and changes that state at runtime.
 *
 * Stored choice: 'lite' | 'full'. No stored value means "auto" (device detection).
 */
export const PERFORMANCE_MODE_STORAGE_KEY = 'emodule_performance_mode';

const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** True when Lite mode is active on this device. */
export const isLitePerformanceMode = () =>
    typeof document !== 'undefined' && document.documentElement.dataset.perf === 'lite';

/** True when decorative JS effects (particle bursts, tilt, count-up animations) should be skipped. */
export const shouldReduceEffects = () => prefersReducedMotion() || isLitePerformanceMode();

/** Applies and remembers an explicit choice: 'lite' or 'full'. */
export const setPerformanceMode = (mode) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.perf = mode === 'lite' ? 'lite' : 'full';

    try {
        window.localStorage?.setItem(PERFORMANCE_MODE_STORAGE_KEY, mode === 'lite' ? 'lite' : 'full');
    } catch {
        // Ignore storage errors in restricted contexts
    }
};
