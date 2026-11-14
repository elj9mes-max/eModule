/**
 * One-shot hand-off from a lecture/quiz back to the adventure map: the "Back to map" buttons mark it,
 * and the map consumes it on mount to scroll to the next island instead of opening at the top.
 * Uses sessionStorage (not a URL query) so prefetched map visits keep matching their cache entries.
 */
const STORAGE_KEY = 'emodule_map_focus_next_island';

export const markReturnToMapFocus = () => {
    try {
        window.sessionStorage?.setItem(STORAGE_KEY, '1');
    } catch {
        // Ignore storage errors in restricted contexts
    }
};

/** Returns true once if a lecture/quiz asked the map to focus the next island, then clears the flag. */
export const consumeReturnToMapFocus = () => {
    try {
        const shouldFocus = window.sessionStorage?.getItem(STORAGE_KEY) === '1';
        window.sessionStorage?.removeItem(STORAGE_KEY);

        return shouldFocus;
    } catch {
        return false;
    }
};
