/**
 * CSRF header for manual fetch() calls to Laravel.
 *
 * Prefers the XSRF-TOKEN cookie: Laravel refreshes it on every response, so it stays valid after
 * login (which rotates the token) and other Inertia visits. The <meta name="csrf-token"> tag is only
 * rendered on the first full page load and goes stale, which causes "CSRF token mismatch" errors.
 *
 * @returns {Record<string, string>}
 */
export const csrfHeaders = () => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    if (match) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) };
    }

    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    return metaToken ? { 'X-CSRF-TOKEN': metaToken } : {};
};
