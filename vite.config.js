import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import os from 'node:os';

const DEV_SERVER_PORT = 5173;
const LARAVEL_SERVER_PORT = 8000;

/** This PC's address on the home network (Wi-Fi/Ethernet), skipping loopback and self-assigned 169.254.x.x ones. */
const detectLanAddress = () =>
    Object.values(os.networkInterfaces())
        .flat()
        .find((net) => net && net.family === 'IPv4' && !net.internal && !net.address.startsWith('169.254.'))?.address ?? null;

/** Prints the address to open on a phone once the dev server is up. */
const announceLanUrls = (lanAddress, lanHostName) => ({
    name: 'emodule-announce-lan-urls',
    configureServer(server) {
        server.httpServer?.once('listening', () => {
            setTimeout(() => {
                server.config.logger.info(
                    `\n  📱 Open on your phone (same Wi-Fi): http://${lanAddress}:${LARAVEL_SERVER_PORT}` +
                        `\n     or, if your phone supports .local names: http://${lanHostName}:${LARAVEL_SERVER_PORT}\n`,
                );
            }, 200);
        });
    },
});

export default defineConfig(({ mode }) => {
    /**
     * `npm run dev:lan` (mode "lan"): serve on the home network so a phone on the same Wi-Fi can open the app.
     * Assets load from this PC's real network address, which every phone can reach; the PC name
     * (<pc-name>.local) also works for opening the site on phones that support .local names.
     */
    const isLanMode = mode === 'lan';
    const lanHostName = `${os.hostname().toLowerCase()}.local`;
    const lanAddress = isLanMode ? (detectLanAddress() ?? lanHostName) : null;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                        optimizedFallbacks: false,
                    }),
                    bunny('Fredoka', {
                        weights: [400, 500, 600, 700],
                        optimizedFallbacks: false,
                    }),
                    bunny('Nunito', {
                        weights: [600, 700, 800, 900],
                        optimizedFallbacks: false,
                    }),
                ],
            }),
            tailwindcss(),
            ...(isLanMode ? [announceLanUrls(lanAddress, lanHostName)] : []),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        server: {
            ...(isLanMode
                ? {
                      host: '0.0.0.0',
                      port: DEV_SERVER_PORT,
                      strictPort: true,
                      origin: `http://${lanAddress}:${DEV_SERVER_PORT}`,
                      hmr: { host: lanAddress },
                  }
                : {}),
            // Let pages opened from this PC or another device on the home network (by PC name, e.g.
            // http://my-pc.local:8000, or by IP) load dev assets; Vite otherwise only allows localhost.
            cors: {
                origin: /^https?:\/\/(localhost|127\.0\.0\.1|\[::1\]|[\w-]+\.local|192\.168\.\d{1,3}\.\d{1,3}|10\.\d{1,3}\.\d{1,3}\.\d{1,3})(:\d+)?$/,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
