import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { shouldReduceEffects } from '@/Composables/usePerformanceMode';

const STORAGE_KEY = 'webread-pixar-world';

export const PIXAR_WORLDS = [
    {
        id: 'toybox',
        name: 'Sunny Toybox',
        tagline: 'Radiant azure sky, sunlit meadow & toy-vinyl coral',
        inspiration: 'Sunlit playroom brights',
        mode: 'Daylight',
        isDark: false,
        swatch: 'linear-gradient(135deg, #42BAFF 0%, #FFBF0F 52%, #DE2B1E 100%)',
        preview: {
            sky: 'linear-gradient(166deg, #42BAFF 0%, #7EE0FF 55%, #FFF0A8 100%)',
            hillBack: '#6E7CF8',
            hillFront: '#11A349',
            card: '#FFFDF9',
            primary: '#DE2B1E',
            secondary: '#146CE8',
            accent: '#FFBF0F',
        },
        burst: ['#FF5E4D', '#146CE8', '#FFD028', '#15B886', '#8B6BFF'],
    },
    {
        id: 'luca-coast',
        name: 'Riviera Coast',
        tagline: 'Tangerine sunset sky, Riviera aqua & Vespa emerald',
        inspiration: 'Seaside village at golden hour',
        mode: 'Sunset',
        isDark: false,
        swatch: 'linear-gradient(135deg, #FF955C 0%, #FFE066 50%, #078578 100%)',
        preview: {
            sky: 'linear-gradient(166deg, #FF955C 0%, #FFD675 54%, #38E4DF 100%)',
            hillBack: '#8E61EE',
            hillFront: '#078578',
            card: '#FFFDF7',
            primary: '#078578',
            secondary: '#D44711',
            accent: '#FFB814',
        },
        burst: ['#2CE6D2', '#FF884D', '#FFE054', '#3DB2FF', '#FF6B8B'],
    },
    {
        id: 'memory-orb',
        name: 'Memory Orb Studio',
        tagline: 'Glowing twilight indigo, luminous gold & console cyan',
        inspiration: 'Glowing control-room twilight',
        mode: 'Night Glow',
        isDark: true,
        swatch: 'linear-gradient(135deg, #7C3AED 0%, #FF5FA2 52%, #38D6FF 100%)',
        preview: {
            sky: 'linear-gradient(166deg, #1E124A 0%, #3A1F80 54%, #672BA8 100%)',
            hillBack: '#4F46E5',
            hillFront: '#1D1745',
            card: '#2C225C',
            primary: '#FFC421',
            secondary: '#0E84C9',
            accent: '#D42874',
        },
        burst: ['#FFC421', '#38D6FF', '#FF5FA2', '#A875FF', '#3DE89D'],
    },
    {
        id: 'marigold',
        name: 'Fiesta Marigold',
        tagline: 'Candlelight plum dusk, marigold flame & spirit mint',
        inspiration: 'Candlelit marigold festival',
        mode: 'Fiesta',
        isDark: true,
        swatch: 'linear-gradient(135deg, #FF9924 0%, #D4246C 52%, #37DEC6 100%)',
        preview: {
            sky: 'linear-gradient(166deg, #29103D 0%, #571963 54%, #8C2260 100%)',
            hillBack: '#B8326D',
            hillFront: '#2E103D',
            card: '#361B48',
            primary: '#F58414',
            secondary: '#D4246C',
            accent: '#37DEC6',
        },
        burst: ['#FFA426', '#FF4D94', '#37DEC6', '#FFD94A', '#B56BFF'],
    },
];

const VALID_WORLD_IDS = new Set(PIXAR_WORLDS.map((world) => world.id));

export const resolveValidWorldId = (value) =>
    typeof value === 'string' && VALID_WORLD_IDS.has(value) ? value : 'toybox';

const prefersReducedMotion = shouldReduceEffects;

const readStoredWorld = () => {
    if (typeof window === 'undefined') {
        return 'toybox';
    }

    try {
        return resolveValidWorldId(window.localStorage?.getItem(STORAGE_KEY));
    } catch {
        return 'toybox';
    }
};

const writeStoredWorld = (worldId) => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage?.setItem(STORAGE_KEY, worldId);
    } catch {
        // Ignore storage errors in restricted contexts
    }
};

const optimisticWorld = ref(null);
const optimisticUserId = ref(null);
const isSavingWorld = ref(false);

export const spawnPixarBurst = (anchorEl, count = 14, worldId = 'toybox') => {
    if (prefersReducedMotion() || !anchorEl || typeof document === 'undefined') {
        return;
    }

    const rect = anchorEl.getBoundingClientRect();
    const originX = rect.left + rect.width / 2;
    const originY = rect.top + rect.height / 2;
    const preset = PIXAR_WORLDS.find((w) => w.id === worldId) || PIXAR_WORLDS[0];
    const palette = preset.burst;

    for (let i = 0; i < count; i += 1) {
        const dot = document.createElement('span');
        const size = 8 + (i % 4) * 3;
        const color = palette[i % palette.length];
        dot.setAttribute('aria-hidden', 'true');
        Object.assign(dot.style, {
            position: 'fixed',
            width: `${size}px`,
            height: `${size}px`,
            left: `${originX - size / 2}px`,
            top: `${originY - size / 2}px`,
            borderRadius: '50%',
            pointerEvents: 'none',
            zIndex: '200',
            background: `radial-gradient(circle at 32% 28%, #FFFFFF 0%, ${color} 68%)`,
            boxShadow: 'inset 0 2px 2px rgba(255, 255, 255, 0.85), 0 4px 10px rgba(28, 22, 62, 0.22)',
        });
        document.body.appendChild(dot);

        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.35;
        const dist = 54 + Math.random() * 68;
        const dx = Math.cos(angle) * dist;
        const dy = Math.sin(angle) * dist - 18;

        const anim = dot.animate(
            [
                { transform: 'translate3d(0, 0, 0) scale(0.4)', opacity: 1 },
                { transform: `translate3d(${dx * 0.72}px, ${dy * 0.72}px, 0) scale(1.18)`, opacity: 1, offset: 0.45 },
                { transform: `translate3d(${dx}px, ${dy + 28}px, 0) scale(0.2)`, opacity: 0 },
            ],
            {
                duration: 680 + Math.random() * 180,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                fill: 'forwards',
            },
        );
        anim.onfinish = () => dot.remove();
    }
};

/**
 * Mirrors the active world onto <html> while the calling shell is mounted, so
 * content teleported to <body> (modals, toasts) inherits the same world tokens.
 */
export const useWorldOnDocument = (worldRef) => {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    watch(
        worldRef,
        (worldId) => {
            const meta = PIXAR_WORLDS.find((w) => w.id === worldId) || PIXAR_WORLDS[0];
            root.dataset.pixarTheme = meta.id;
            root.style.colorScheme = meta.isDark ? 'dark' : 'light';
        },
        { immediate: true },
    );

    onBeforeUnmount(() => {
        delete root.dataset.pixarTheme;
        root.style.colorScheme = '';
    });
};

export function usePixarTheme() {
    const page = usePage();

    const authUser = computed(() => page.props.auth?.user || page.props.user || null);

    watch(
        () => [authUser.value?.id, authUser.value?.ui_theme],
        ([userId, serverTheme]) => {
            if (!userId) {
                optimisticWorld.value = null;
                optimisticUserId.value = null;
                return;
            }

            if (optimisticUserId.value !== null && optimisticUserId.value !== userId) {
                optimisticWorld.value = null;
            }

            const resolvedServer = serverTheme ? resolveValidWorldId(serverTheme) : null;
            if (resolvedServer && optimisticWorld.value === resolvedServer) {
                optimisticWorld.value = null;
            }

            if (resolvedServer && !isSavingWorld.value && !optimisticWorld.value) {
                writeStoredWorld(resolvedServer);
            }
        },
        { immediate: true },
    );

    const activeWorld = computed(() => {
        const user = authUser.value;
        if (user?.id) {
            if (optimisticWorld.value && optimisticUserId.value === user.id) {
                return optimisticWorld.value;
            }
            if (user.ui_theme) {
                return resolveValidWorldId(user.ui_theme);
            }
        }

        if (optimisticWorld.value) {
            return optimisticWorld.value;
        }

        return readStoredWorld();
    });

    const activeWorldMeta = computed(
        () => PIXAR_WORLDS.find((w) => w.id === activeWorld.value) || PIXAR_WORLDS[0],
    );

    const persistThemeToAccount = (worldId, { onSuccess, onError, onFinish } = {}) => {
        const nextWorld = resolveValidWorldId(worldId);
        const user = authUser.value;

        if (!user?.id || user.role !== 'student') {
            if (typeof onSuccess === 'function') {
                onSuccess();
            }
            if (typeof onFinish === 'function') {
                onFinish();
            }
            return;
        }

        isSavingWorld.value = true;
        router.patch(
            '/students/settings/theme',
            { ui_theme: nextWorld },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['auth', 'user'],
                onSuccess: () => {
                    optimisticWorld.value = null;
                    if (typeof onSuccess === 'function') {
                        onSuccess();
                    }
                },
                onError: (errors) => {
                    optimisticWorld.value = null;
                    if (typeof onError === 'function') {
                        onError(errors);
                    }
                },
                onFinish: () => {
                    isSavingWorld.value = false;
                    if (typeof onFinish === 'function') {
                        onFinish();
                    }
                },
            },
        );
    };

    const setWorldTheme = (
        worldId,
        { persist = true, anchorEl = null, burstCount = 12, onSaved = null } = {},
    ) => {
        const nextWorld = resolveValidWorldId(worldId);
        const userId = authUser.value?.id ?? null;

        const apply = () => {
            optimisticWorld.value = nextWorld;
            optimisticUserId.value = userId;
            writeStoredWorld(nextWorld);
        };

        if (
            typeof document !== 'undefined' &&
            typeof document.startViewTransition === 'function' &&
            !prefersReducedMotion()
        ) {
            document.startViewTransition(apply);
        } else {
            apply();
        }

        if (anchorEl && burstCount > 0) {
            spawnPixarBurst(anchorEl, burstCount, nextWorld);
        }

        if (persist) {
            persistThemeToAccount(nextWorld, { onSuccess: onSaved });
        }
    };

    return {
        PIXAR_WORLDS,
        activeWorld,
        activeWorldMeta,
        isSavingWorld,
        setWorldTheme,
        persistThemeToAccount,
        spawnPixarBurst,
    };
}

