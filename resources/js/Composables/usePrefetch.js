import { onBeforeUnmount, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const prefetchedUrls = new Set();

/**
 * Prefetch an Inertia GET route into the client-side cache with SWR timing and cache tags.
 *
 * @param {string | null | undefined} url
 * @param {string | string[]} cacheTags
 * @param {[string, string] | string | number} cacheFor
 */
export function prefetchRoute(url, cacheTags = [], cacheFor = ['15s', '60s']) {
    if (!url || typeof window === 'undefined') {
        return;
    }

    const normalizedTags = Array.isArray(cacheTags) ? cacheTags.filter(Boolean) : [cacheTags].filter(Boolean);

    try {
        router.prefetch(
            url,
            {},
            {
                cacheFor,
                cacheTags: normalizedTags,
            },
        );
        prefetchedUrls.add(url);
    } catch {
        // Ignore duplicate or cancelled prefetch attempts
    }
}

/**
 * Invalidate client-side Inertia prefetched responses by cache tag(s).
 *
 * @param {string | string[]} tags
 */
export function flushCacheTags(tags) {
    const normalized = Array.isArray(tags) ? tags.filter(Boolean) : [tags].filter(Boolean);
    if (normalized.length === 0) {
        return;
    }

    router.flushByCacheTags(normalized);
}

/**
 * Flush all Inertia client-side prefetched entries (e.g., on logout).
 */
export function flushAllClientCache() {
    prefetchedUrls.clear();
    router.flushAll();
}

/**
 * Schedule an idle-time route prefetch after mount, cancelling cleanly on unmount.
 *
 * @param {() => string | null | undefined} urlGetter
 * @param {string | string[]} cacheTags
 * @param {[string, string] | string | number} cacheFor
 */
export function useIdlePrefetch(urlGetter, cacheTags = [], cacheFor = ['20s', '90s']) {
    let idleHandle = null;
    let timeoutHandle = null;

    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const run = () => {
            const url = typeof urlGetter === 'function' ? urlGetter() : urlGetter;
            if (url) {
                prefetchRoute(url, cacheTags, cacheFor);
            }
        };

        if (typeof window.requestIdleCallback === 'function') {
            idleHandle = window.requestIdleCallback(run, { timeout: 1200 });
        } else {
            timeoutHandle = window.setTimeout(run, 250);
        }
    });

    onBeforeUnmount(() => {
        if (idleHandle !== null && typeof window.cancelIdleCallback === 'function') {
            window.cancelIdleCallback(idleHandle);
        }
        if (timeoutHandle !== null) {
            window.clearTimeout(timeoutHandle);
        }
    });
}

