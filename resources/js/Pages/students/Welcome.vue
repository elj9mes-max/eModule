<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SoundController from '@/Components/SoundController.vue';
import { useSound } from '@/Services/soundManager';
import { shouldReduceEffects } from '@/Composables/usePerformanceMode';
import { usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';

const {
    soundManager,
    playPop,
    playSparkle,
    playWhoosh,
    playSuccess,
    playBonus,
} = useSound();

const {
    PIXAR_WORLDS,
    activeWorld,
    setWorldTheme,
    spawnPixarBurst,
} = usePixarTheme();
useWorldOnDocument(activeWorld);

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student',
            email: '',
            role: 'student',
            ui_theme: 'toybox',
        }),
    },
});

const prefersReducedMotion = shouldReduceEffects;

const isMounted = ref(false);
const isLaunching = ref(false);
const pointerParallax = ref({ x: 0, y: 0 });
const cardTiltStyle = ref({});
const heroCardRef = ref(null);
const exploreBtnRef = ref(null);

let animationFrameId = null;
let launchTimeoutId = null;
let canFinePointer = false;

const displayName = computed(() => (props.user?.name || 'Student').trim());

const nameLetters = computed(() =>
    Array.from(displayName.value).map((character) => (character === ' ' ? '\u00A0' : character)),
);

const timeGreeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return { kind: 'morning', text: 'Good morning' };
    }

    if (hour < 18) {
        return { kind: 'afternoon', text: 'Good afternoon' };
    }

    return { kind: 'evening', text: 'Good evening' };
});

const selectWorld = (worldId, event) => {
    if (activeWorld.value === worldId) {
        return;
    }

    playPop(1.18);
    setWorldTheme(worldId, {
        persist: true,
        anchorEl: event?.currentTarget || null,
        burstCount: 10,
    });
};

const triggerToyPop = (event, pitch = 1.15) => {
    playPop(pitch);
    if (event?.currentTarget) {
        spawnPixarBurst(event.currentTarget, 8, activeWorld.value);
    }
};

const triggerSparkleLoot = (event) => {
    playSparkle();
    if (event?.currentTarget) {
        spawnPixarBurst(event.currentTarget, 10, activeWorld.value);
    }
};

const triggerBonusReward = (event) => {
    playBonus();
    if (event?.currentTarget) {
        spawnPixarBurst(event.currentTarget, 14, activeWorld.value);
    }
};

const handleStagePointerMove = (event) => {
    if (!canFinePointer || prefersReducedMotion() || document.hidden || animationFrameId) {
        return;
    }

    const clientX = event.clientX;
    const clientY = event.clientY;
    animationFrameId = window.requestAnimationFrame(() => {
        const nx = (clientX / window.innerWidth - 0.5) * 2;
        const ny = (clientY / window.innerHeight - 0.5) * 2;
        pointerParallax.value = {
            x: Math.round(nx * 10),
            y: Math.round(ny * 8),
        };
        animationFrameId = null;
    });
};

const handleCardPointerMove = (event) => {
    if (!canFinePointer || prefersReducedMotion() || !heroCardRef.value) {
        return;
    }

    const rect = heroCardRef.value.getBoundingClientRect();
    const px = (event.clientX - rect.left) / rect.width - 0.5;
    const py = (event.clientY - rect.top) / rect.height - 0.5;
    const rx = (-py * 4.5).toFixed(2);
    const ry = (px * 4.5).toFixed(2);

    cardTiltStyle.value = {
        transform: `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg) translate3d(0, -3px, 0)`,
    };
};

const handleCardPointerLeave = () => {
    cardTiltStyle.value = {};
};

const handleVisibilityChange = () => {
    if (document.hidden) {
        if (animationFrameId) {
            window.cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        if (soundManager?.audioCtx?.state === 'running') {
            soundManager.audioCtx.suspend().catch(() => {});
        }
    }
};

const exploreDashboard = () => {
    if (isLaunching.value) {
        return;
    }

    isLaunching.value = true;
    playWhoosh();
    playSuccess();
    spawnPixarBurst(exploreBtnRef.value || heroCardRef.value, 20, activeWorld.value);

    launchTimeoutId = window.setTimeout(() => {
        router.visit('/students/classes', {
            onFinish: () => {
                isLaunching.value = false;
            },
        });
    }, 440);
};

onMounted(() => {
    canFinePointer =
        typeof window !== 'undefined' &&
        window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    if (typeof window !== 'undefined' && !prefersReducedMotion()) {
        animationFrameId = window.requestAnimationFrame(() => {
            isMounted.value = true;
            animationFrameId = null;
        });
    } else {
        isMounted.value = true;
    }

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    }

    if (animationFrameId && typeof window !== 'undefined') {
        window.cancelAnimationFrame(animationFrameId);
    }

    if (launchTimeoutId && typeof window !== 'undefined') {
        window.clearTimeout(launchTimeoutId);
    }
});
</script>

<template>
    <Head :title="`Welcome, ${displayName} • WEBREAD`" />

    <div
        class="welcome-page pixar-stage"
        :class="{ 'is-mounted': isMounted, 'is-launching': isLaunching }"
        :data-pixar-theme="activeWorld"
        @pointermove="handleStagePointerMove"
    >
        <!-- Plane 2b: Volumetric Drifting Clouds & Twinkling Sparks -->
        <div class="pixar-cloud-field" aria-hidden="true">
            <svg class="pixar-cloud pixar-cloud--1" viewBox="0 0 260 110" fill="none">
                <defs>
                    <linearGradient id="welcomeCloudGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.96" />
                        <stop offset="68%" stop-color="#FFFDF9" stop-opacity="0.88" />
                        <stop offset="100%" stop-color="#D9EEFF" stop-opacity="0.72" />
                    </linearGradient>
                </defs>
                <path
                    d="M44 92h170c22 0 38-15 38-34 0-17-12-31-29-34-6-15-21-24-39-24-15 0-28 7-36 18-7-4-16-7-25-7-22 0-40 16-43 37-19 2-32 16-32 32 0 7 3 12 10 12h86Z"
                    fill="url(#welcomeCloudGrad)"
                />
            </svg>
            <svg class="pixar-cloud pixar-cloud--2" viewBox="0 0 220 96" fill="none">
                <path
                    d="M36 80h148c19 0 32-13 32-29 0-14-10-26-25-29-6-13-18-21-33-21-13 0-24 6-31 16-6-4-13-6-21-6-19 0-34 14-36 32-16 2-28 13-28 27 0 6 3 10 9 10h85Z"
                    fill="url(#welcomeCloudGrad)"
                />
            </svg>
            <svg class="pixar-cloud pixar-cloud--3" viewBox="0 0 190 84" fill="none">
                <path
                    d="M32 70h126c16 0 27-11 27-25 0-12-9-22-21-25-5-11-15-18-28-18-11 0-20 5-26 14-5-3-11-5-18-5-16 0-29 12-31 27-14 2-24 11-24 23 0 5 3 9 8 9h77Z"
                    fill="url(#welcomeCloudGrad)"
                />
            </svg>
        </div>

        <div class="pixar-sparkle-field" aria-hidden="true">
            <span class="pixar-sparkle pixar-sparkle--1"></span>
            <span class="pixar-sparkle pixar-sparkle--2"></span>
            <span class="pixar-sparkle pixar-sparkle--3"></span>
            <span class="pixar-sparkle pixar-sparkle--4"></span>
            <span class="pixar-sparkle pixar-sparkle--5"></span>
            <span class="pixar-sparkle pixar-sparkle--6"></span>
        </div>

        <!-- Plane 3: Drifting Volumetric Memory Orbs -->
        <div
            class="pixar-orb-field"
            aria-hidden="true"
            :style="{ transform: `translate3d(${pointerParallax.x * 0.25}px, ${pointerParallax.y * 0.2}px, 0)` }"
        >
            <span class="pixar-orb pixar-orb--1"></span>
            <span class="pixar-orb pixar-orb--2"></span>
            <span class="pixar-orb pixar-orb--3"></span>
            <span class="pixar-orb pixar-orb--4"></span>
            <span class="pixar-orb pixar-orb--5"></span>
            <span class="pixar-orb pixar-orb--6"></span>
        </div>

        <!-- Plane 4: Tri-Ridge Multi-Stop Gradient Storybook Horizon Hills -->
        <div class="pixar-hills" aria-hidden="true">
            <svg viewBox="0 0 1440 240" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="pxWelcomeHillFar" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--px-hill-far-1)" />
                        <stop offset="100%" stop-color="var(--px-hill-far-2)" />
                    </linearGradient>
                    <linearGradient id="pxWelcomeHillMid" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--px-hill-mid-1)" />
                        <stop offset="100%" stop-color="var(--px-hill-mid-2)" />
                    </linearGradient>
                    <linearGradient id="pxWelcomeHillNear" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--px-hill-near-1)" />
                        <stop offset="100%" stop-color="var(--px-hill-near-2)" />
                    </linearGradient>
                </defs>
                <path
                    d="M0,126 C210,36 450,148 730,78 C1010,12 1240,118 1440,68 L1440,240 L0,240 Z"
                    fill="url(#pxWelcomeHillFar)"
                />
                <path
                    d="M0,152 C260,82 510,192 840,120 C1100,64 1280,152 1440,118 L1440,240 L0,240 Z"
                    fill="url(#pxWelcomeHillMid)"
                />
                <path
                    d="M0,184 C310,126 570,218 900,154 C1150,106 1310,176 1440,148 L1440,240 L0,240 Z"
                    fill="url(#pxWelcomeHillNear)"
                />
                <path
                    d="M0,184 C310,126 570,218 900,154 C1150,106 1310,176 1440,148"
                    fill="none"
                    stroke="var(--px-hill-rim)"
                    stroke-width="3"
                />
            </svg>
        </div>

        <!-- Luminous Adventure Trail Curves -->
        <div class="adventure-trail-layer" aria-hidden="true">
            <svg class="trail-svg" viewBox="0 0 1440 900" fill="none" preserveAspectRatio="none">
                <path
                    class="trail-path"
                    d="M 90 820 C 245 680, 345 700, 520 540 C 600 460, 640 480, 720 480"
                />
                <path
                    class="trail-path trail-path--right"
                    d="M 1350 820 C 1195 680, 1095 700, 920 540 C 840 460, 800 480, 720 480"
                />
            </svg>
        </div>

        <!-- Top-Right Pixar Utility Bar: World Swatches + Playful Sound Controller -->
        <div class="top-utility-bar">
            <div class="pixar-world-picker" role="group" aria-label="Color world theme">
                <span class="world-picker-label">Worlds</span>
                <button
                    v-for="world in PIXAR_WORLDS"
                    :key="world.id"
                    type="button"
                    class="world-swatch-btn"
                    :class="{ 'is-active': activeWorld === world.id }"
                    :style="{ background: world.swatch }"
                    :aria-pressed="activeWorld === world.id"
                    :aria-label="`Switch to ${world.name} theme`"
                    :title="world.name"
                    @click="(e) => selectWorld(world.id, e)"
                ></button>
            </div>

            <SoundController variant="compact" />
        </div>

        <!-- 3D Pop-Up World Flanking Diorama Wings (Left & Right) -->
        <aside
            class="scene-wing wing-left"
            :style="{ transform: `translate3d(${pointerParallax.x * -0.45}px, ${pointerParallax.y * -0.35}px, 0)` }"
        >
            <img
                src="/images/book_left_trees_mountains.png"
                alt=""
                width="965"
                height="777"
                decoding="async"
                class="asset-pop asset-book-left"
            />

            <div class="boy-pod-wrap">
                <img
                    src="/images/boy_left_on_book_laptop.png"
                    alt=""
                    width="838"
                    height="905"
                    decoding="async"
                    class="asset-pop asset-boy-laptop"
                />
                <button
                    type="button"
                    class="floating-speech-chip chip-left"
                    aria-label="Chapter 1 story cheer"
                    @click="(e) => triggerToyPop(e, 1.25)"
                >
                    <svg class="chip-svg-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="10" r="6" fill="var(--px-accent)" />
                        <path d="M9.5 17.5h5M10.5 20h3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                    </svg>
                    <span>Ready for Chapter 1!</span>
                </button>
            </div>

            <div class="diorama-floor-anchor floor-left">
                <span class="diorama-contact-shadow"></span>
                <img
                    src="/images/girl_left_reading_book.png"
                    alt=""
                    width="945"
                    height="878"
                    decoding="async"
                    class="asset-pop asset-girl-reading"
                />
            </div>
        </aside>

        <aside
            class="scene-wing wing-right"
            :style="{ transform: `translate3d(${pointerParallax.x * 0.45}px, ${pointerParallax.y * -0.35}px, 0)` }"
        >
            <img
                src="/images/book_right_mountains_river.png"
                alt=""
                width="996"
                height="640"
                decoding="async"
                class="asset-pop asset-book-right"
            />

            <div class="message-girl-pod-wrap">
                <img
                    src="/images/book_with_message_and_laptop_girl.png"
                    alt=""
                    width="1030"
                    height="682"
                    decoding="async"
                    class="asset-pop asset-book-laptop-girl"
                />
                <button
                    type="button"
                    class="floating-speech-chip chip-right"
                    aria-label="Reading quest cheer"
                    @click="(e) => triggerToyPop(e, 1.28)"
                >
                    <svg class="chip-svg-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6.5A3.5 3.5 0 0 1 7.5 3h9A3.5 3.5 0 0 1 20 6.5v6A3.5 3.5 0 0 1 16.5 16H10l-4.5 3.5V16A3.5 3.5 0 0 1 4 12.5v-6Z" fill="var(--px-secondary-lit)" />
                    </svg>
                    <span>Reading Quest Awaits!</span>
                </button>
            </div>

            <div class="diorama-floor-anchor floor-right">
                <span class="diorama-contact-shadow"></span>
                <img
                    src="/images/girl_right_using_tablet.png"
                    alt=""
                    width="942"
                    height="814"
                    decoding="async"
                    class="asset-pop asset-girl-tablet"
                />
            </div>
        </aside>

        <!-- Sculpted Volumetric Stars Around Stage -->
        <div class="ambient-decorations" aria-hidden="true">
            <div class="float-star star-1">
                <svg width="28" height="28" viewBox="0 0 36 36">
                    <defs>
                        <radialGradient id="pxWelcomeStar" cx="35%" cy="28%" r="70%">
                            <stop offset="0%" stop-color="#FFF7BA" />
                            <stop offset="55%" stop-color="#FFBE1A" />
                            <stop offset="100%" stop-color="#D98300" />
                        </radialGradient>
                    </defs>
                    <path d="M18 2.5L21.9 12.6L32.8 14L24.5 21.1L26.8 31.8L18 26.2L9.2 31.8L11.5 21.1L3.2 14L14.1 12.6L18 2.5Z" fill="url(#pxWelcomeStar)" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="float-star star-2">
                <svg width="24" height="24" viewBox="0 0 36 36">
                    <path d="M18 2.5L21.9 12.6L32.8 14L24.5 21.1L26.8 31.8L18 26.2L9.2 31.8L11.5 21.1L3.2 14L14.1 12.6L18 2.5Z" fill="url(#pxWelcomeStar)" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="float-star star-3">
                <svg width="26" height="26" viewBox="0 0 36 36">
                    <path d="M18 2.5L21.9 12.6L32.8 14L24.5 21.1L26.8 31.8L18 26.2L9.2 31.8L11.5 21.1L3.2 14L14.1 12.6L18 2.5Z" fill="url(#pxWelcomeStar)" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <!-- MAIN HERO STAGE -->
        <main class="hero-stage">
            <section
                ref="heroCardRef"
                class="pixar-hero-card"
                :style="cardTiltStyle"
                aria-labelledby="welcome-heading"
                @pointermove="handleCardPointerMove"
                @pointerleave="handleCardPointerLeave"
            >
                <!-- Overlapping Sculpted 3D Lumina Explorer Crest & Mini Props -->
                <div class="pixar-hero-crest-row" aria-hidden="true">
                    <span class="mini-orb-badge orb-globe">
                        <svg viewBox="0 0 40 40" width="32" height="32">
                            <defs>
                                <radialGradient id="pxGlobeSphere" cx="32%" cy="28%" r="72%">
                                    <stop offset="0%" stop-color="#9BE7FF" />
                                    <stop offset="52%" stop-color="#1D8CE8" />
                                    <stop offset="100%" stop-color="#0B478A" />
                                </radialGradient>
                            </defs>
                            <circle cx="20" cy="20" r="16" fill="url(#pxGlobeSphere)" />
                            <path d="M12 15c3-2 7-1 8 3 1 3-3 6-6 5-3-1-4-6-2-8Zm12 6c2-2 6-1 7 2 1 3-1 6-4 7-3 1-5-6-3-9Z" fill="#4ADE80" />
                            <ellipse cx="14" cy="11" rx="5" ry="2.6" transform="rotate(-24 14 11)" fill="#FFFFFF" opacity="0.6" />
                        </svg>
                    </span>

                    <div class="pixar-hero-crest">
                        <svg viewBox="0 0 120 120" width="76" height="76" class="mascot-svg">
                            <defs>
                                <radialGradient id="pxWelcomeMascotOrb" cx="34%" cy="28%" r="72%">
                                    <stop offset="0%" stop-color="#FFF0A8" />
                                    <stop offset="46%" stop-color="#FFBE1A" />
                                    <stop offset="86%" stop-color="#E38B00" />
                                    <stop offset="100%" stop-color="#A85900" />
                                </radialGradient>
                                <linearGradient id="pxWelcomeMascotRim" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.88" />
                                    <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0" />
                                </linearGradient>
                                <filter id="pxWelcomeMascotDrop" x="-25%" y="-25%" width="150%" height="150%">
                                    <feDropShadow dx="0" dy="8" stdDeviation="6" flood-color="#1E1A3B" flood-opacity="0.24" />
                                </filter>
                            </defs>
                            <circle cx="60" cy="60" r="46" fill="url(#pxWelcomeMascotOrb)" filter="url(#pxWelcomeMascotDrop)" />
                            <ellipse cx="45" cy="34" rx="22" ry="12" transform="rotate(-24 45 34)" fill="url(#pxWelcomeMascotRim)" />
                            <circle cx="35" cy="29" r="4.8" fill="#FFFFFF" opacity="0.94" />
                            <g class="mascot-eyes-blink">
                                <ellipse cx="47" cy="59" rx="5.5" ry="7.6" fill="#1E1A3B" />
                                <ellipse cx="73" cy="59" rx="5.5" ry="7.6" fill="#1E1A3B" />
                                <circle cx="45" cy="55.8" r="2.2" fill="#FFFFFF" />
                                <circle cx="71" cy="55.8" r="2.2" fill="#FFFFFF" />
                                <circle cx="49" cy="62.2" r="1.1" fill="#FFFFFF" opacity="0.8" />
                                <circle cx="75" cy="62.2" r="1.1" fill="#FFFFFF" opacity="0.8" />
                            </g>
                            <ellipse cx="36" cy="70" rx="6.2" ry="3.5" fill="#FF6B5B" opacity="0.46" />
                            <ellipse cx="84" cy="70" rx="6.2" ry="3.5" fill="#FF6B5B" opacity="0.46" />
                            <path d="M50 72 Q60 82 70 72" fill="none" stroke="#1E1A3B" stroke-width="3.8" stroke-linecap="round" />
                        </svg>
                    </div>

                    <span class="mini-orb-badge orb-book">
                        <svg viewBox="0 0 40 40" width="32" height="32">
                            <defs>
                                <linearGradient id="pxStoryMapGrad" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#FFE680" />
                                    <stop offset="55%" stop-color="#F59E0B" />
                                    <stop offset="100%" stop-color="#B45309" />
                                </linearGradient>
                            </defs>
                            <rect x="6" y="8" width="28" height="24" rx="6" fill="url(#pxStoryMapGrad)" stroke="#FFFFFF" stroke-width="1.8" />
                            <path d="M12 24c4-6 9 2 14-8" fill="none" stroke="#FFFDF9" stroke-width="2.4" stroke-linecap="round" stroke-dasharray="3 3" />
                            <circle cx="26" cy="15" r="2.6" fill="#DE2B1E" stroke="#FFFFFF" stroke-width="1.2" />
                        </svg>
                    </span>
                </div>

                <!-- Sculpted Ruby Bookmark Ribbon -->
                <div class="hero-bookmark-ribbon" aria-hidden="true">
                    <div class="ribbon-tail"></div>
                    <svg viewBox="0 0 24 24" width="17" height="17" class="ribbon-star">
                        <path d="M12 2l2.9 6.2 6.8.9-5 4.7 1.3 6.7L12 17.2l-6 3.3 1.3-6.7-5-4.7 6.8-.9L12 2Z" fill="#FFE661" stroke="#FFFFFF" stroke-width="1.3" stroke-linejoin="round" />
                    </svg>
                </div>

                <!-- Sculpted Gilded Corner Rivets -->
                <div class="corner-rivet rivet-tl" aria-hidden="true"></div>
                <div class="corner-rivet rivet-tr" aria-hidden="true"></div>
                <div class="corner-rivet rivet-bl" aria-hidden="true"></div>
                <div class="corner-rivet rivet-br" aria-hidden="true"></div>

                <!-- Role Pill Badge -->
                <div class="role-chip">
                    <span class="chip-dot" aria-hidden="true"></span>
                    <svg class="role-chip-svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <polygon points="15.5 8.5 13.4 13.4 8.5 15.5 10.6 10.6 15.5 8.5" fill="currentColor" />
                    </svg>
                    <span>STUDENT ADVENTURER</span>
                </div>

                <!-- Time-Aware Greeting with Volumetric SVG Icon -->
                <p class="time-greeting">
                    <span class="time-icon" aria-hidden="true">
                        <svg v-if="timeGreeting.kind === 'morning'" viewBox="0 0 24 24" width="20" height="20">
                            <circle cx="12" cy="12" r="5.5" fill="#FFB814" stroke="#FFFDF9" stroke-width="1.5" />
                            <path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8" stroke="#FF9900" stroke-width="2.2" stroke-linecap="round" />
                        </svg>
                        <svg v-else-if="timeGreeting.kind === 'afternoon'" viewBox="0 0 24 24" width="20" height="20">
                            <circle cx="15" cy="9" r="4.5" fill="#FFB814" />
                            <path d="M6.5 19h11a3.5 3.5 0 0 0 .4-6.98 5 5 0 0 0-9.6-1.32A3.8 3.8 0 0 0 6.5 19Z" fill="#7DD3FC" stroke="#FFFFFF" stroke-width="1.5" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" width="20" height="20">
                            <path d="M18.5 14.5A7 7 0 0 1 9.5 5.5a7 7 0 1 0 9 9Z" fill="#FFD166" stroke="#F59E0B" stroke-width="1.6" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span>{{ timeGreeting.text }}!</span>
                </p>

                <!-- Hero Personalized Greeting -->
                <h1 id="welcome-heading" class="greeting">
                    <span class="greeting-hi">Hi,</span>
                    <span class="greeting-name" :aria-label="displayName">
                        <span
                            v-for="(letter, index) in nameLetters"
                            :key="`letter-${index}`"
                            class="name-letter"
                            :style="{ '--i': index }"
                            aria-hidden="true"
                        >{{ letter }}</span>
                    </span><span class="greeting-bang">!</span>
                </h1>

                <p class="subtitle">
                    Your reading adventure awaits! Discover new stories, earn XP, and unlock treasures along the way.
                </p>

                <!-- Neat Recessed Quest Summary Pills Inside Hero Card -->
                <div class="quest-mini-grid">
                    <button
                        type="button"
                        class="quest-mini-pill"
                        @click="triggerSparkleLoot"
                    >
                        <span class="quest-mini-orb quest-orb-gold" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
                            </svg>
                        </span>
                        <span class="quest-mini-copy">
                            <span class="quest-mini-kicker">DAILY QUEST</span>
                            <span class="quest-mini-title">Module 1 Storyline</span>
                        </span>
                        <span class="quest-tag-xp">+150 XP</span>
                    </button>

                    <button
                        type="button"
                        class="quest-mini-pill"
                        @click="triggerSparkleLoot"
                    >
                        <span class="quest-mini-orb quest-orb-mint" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="6 3 18 3 22 9 12 21 2 9 6 3" />
                            </svg>
                        </span>
                        <span class="quest-mini-copy">
                            <span class="quest-mini-kicker">READING VAULT</span>
                            <span class="quest-mini-title">Explorer Streak</span>
                        </span>
                        <span class="quest-tag-unlocked">READY</span>
                    </button>
                </div>

                <!-- Interactive Welcome Bonus Vinyl Pill -->
                <div class="perk-row">
                    <button
                        type="button"
                        class="welcome-perk-btn"
                        @click="triggerBonusReward"
                    >
                        <svg class="perk-star-svg" viewBox="0 0 24 24" width="19" height="19" aria-hidden="true">
                            <path d="M12 2l2.4 5.2 5.6.8-4.1 3.9 1 5.6L12 14.8l-4.9 2.7 1-5.6L4 8l5.6-.8L12 2Z" fill="#FFBE1A" stroke="#9A5B00" stroke-width="1.3" stroke-linejoin="round" />
                        </svg>
                        <span class="perk-text">
                            <strong>+500 XP Welcome Bonus</strong> available when you explore today!
                        </span>
                        <span class="perk-badge">FREE REWARD</span>
                    </button>
                </div>

                <!-- Hero Zero-Layout-Shift Sculpted Toy-Vinyl CTA -->
                <div class="explore-wrap">
                    <span class="explore-halo" aria-hidden="true"></span>
                    <button
                        ref="exploreBtnRef"
                        type="button"
                        class="pixar-btn pixar-btn--explore"
                        :aria-busy="isLaunching"
                        :disabled="isLaunching"
                        aria-label="Explore your student dashboard"
                        @click="exploreDashboard"
                    >
                        <span class="pixar-btn__label btn-explore-content">
                            <svg class="rocket-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09Z" />
                                <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2Z" fill="currentColor" fill-opacity="0.22" />
                                <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0" />
                                <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5" />
                            </svg>
                            <span>{{ isLaunching ? 'LAUNCHING…' : 'EXPLORE' }}</span>
                            <span class="btn-arrow-orb" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14" />
                                    <path d="m13 6 6 6-6 6" />
                                </svg>
                            </span>
                        </span>
                    </button>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* ==========================================================================
   PIXAR UI DESIGN SYSTEM — LIVING STORYBOOK WELCOME STAGE (4 WORLDS)
   ========================================================================== */
.welcome-page,
.welcome-page[data-pixar-theme="toybox"] {
    --px-sky-top: #42BAFF;
    --px-sky-mid: #7EE0FF;
    --px-sky-bottom: #FFF0A8;
    --px-sun-bloom: rgba(255, 214, 66, 0.72);
    --px-secondary-bloom: rgba(255, 110, 150, 0.34);

    --px-hill-far-1: #8B96FF;
    --px-hill-far-2: #5D6DF5;
    --px-hill-mid-1: #29E0BF;
    --px-hill-mid-2: #0DB596;
    --px-hill-near-1: #4BE364;
    --px-hill-near-2: #11A349;
    --px-hill-rim: rgba(212, 255, 173, 0.88);
    --px-cloud-opacity: 0.92;
    --px-sparkle-color: #FFF7B2;
    --px-wing-glow: rgba(255, 232, 130, 0.5);
    --px-card-aura: rgba(255, 200, 56, 0.44);
    --px-trail-color: rgba(255, 232, 128, 0.88);

    --px-card-bg-top: rgba(255, 253, 249, 0.96);
    --px-card-bg-bottom: rgba(244, 237, 255, 0.93);
    --px-surface-well: #EAE4FC;
    --px-surface-elevated: #FFFFFF;
    --px-surface-glass: rgba(255, 253, 249, 0.88);

    --px-ink: #18143B;
    --px-ink-muted: #4C4670;
    --px-ink-subtle: #625C88;

    --px-primary-lit: #FF7866;
    --px-primary: #DE2B1E;
    --px-primary-belly: #B51B10;
    --px-primary-pedestal: #821008;
    --px-primary-ink: #FFFFFF;
    --px-primary-glow: rgba(230, 65, 51, 0.42);

    --px-secondary-lit: #52ADFF;
    --px-secondary: #146CE8;
    --px-secondary-belly: #0E50B5;
    --px-secondary-pedestal: #08347D;
    --px-secondary-ink: #FFFFFF;
    --px-secondary-glow: rgba(20, 108, 232, 0.4);

    --px-accent-lit: #FFE661;
    --px-accent: #FFBF0F;
    --px-accent-belly: #E39900;
    --px-accent-pedestal: #AB6D00;
    --px-accent-ink: #261800;
    --px-accent-glow: rgba(255, 191, 15, 0.42);

    /* Hero Explore Emerald Toy-Vinyl CTA (WCAG AA Checked >= 4.5:1) */
    --px-explore-lit: #2EE8B5;
    --px-explore: #078562;
    --px-explore-belly: #05664B;
    --px-explore-pedestal: #034532;
    --px-explore-ink: #FFFFFF;
    --px-explore-glow: rgba(7, 133, 98, 0.42);

    --px-hero-name-grad: linear-gradient(92deg, #146CE8 0%, #7C3AED 50%, #DE2B1E 100%);
    --px-role-bg: linear-gradient(145deg, #EEF6FF 0%, #D9EBFF 100%);
    --px-role-ink: #0E4DA4;
    --px-role-border: rgba(20, 108, 232, 0.35);

    --px-perk-bg: linear-gradient(135deg, #FFFDF0 0%, #FFEFA8 100%);
    --px-perk-border: #F59E0B;
    --px-perk-pedestal: #D97706;
    --px-perk-ink: #4A2800;
    --px-perk-badge-bg: #261800;
    --px-perk-badge-ink: #FFE661;

    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(255, 226, 128, 0.58) 58%, rgba(255, 180, 56, 0.28) 100%);

    --px-orb-1: #FF627A;
    --px-orb-2: #3DB2FF;
    --px-orb-3: #FFD028;
    --px-orb-4: #3CE6A6;
    --px-orb-5: #A77BFF;
    --px-orb-6: #FF9447;

    --px-shadow-rgb: 28, 22, 62;
    --px-rim-top: rgba(255, 255, 255, 0.95);
    --px-card-border: rgba(255, 255, 255, 0.9);
    --px-well-shadow: inset 0 4px 8px rgba(var(--px-shadow-rgb), 0.12),
        inset 0 1px 2px rgba(var(--px-shadow-rgb), 0.08),
        0 2px 0 rgba(255, 255, 255, 0.88);
    --px-card-shadow:
        0 28px 54px -14px rgba(var(--px-shadow-rgb), 0.24),
        0 10px 22px -6px rgba(var(--px-shadow-rgb), 0.14),
        0 3px 6px rgba(var(--px-shadow-rgb), 0.09),
        inset 0 2px 2px var(--px-rim-top),
        inset 0 -10px 24px -8px rgba(var(--px-shadow-rgb), 0.07);
    --px-focus-ring: rgba(20, 108, 232, 0.34);
    --px-focus-stroke: #146CE8;

    --px-font-display: 'Fredoka', ui-rounded, 'SF Pro Rounded', 'Nunito', system-ui, sans-serif;
    --px-font-body: 'Nunito', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
    --px-ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
    --px-ease-settle: cubic-bezier(0.22, 1, 0.36, 1);
    --px-btn-depth: 6px;
    --px-noise-svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.55'/%3E%3C/svg%3E");

    position: relative;
    isolation: isolate;
    min-height: 100vh;
    width: 100%;
    overflow-x: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4.5rem 1.25rem 2.75rem;
    box-sizing: border-box;
    font-family: var(--px-font-body);
    color: var(--px-ink);
    background: linear-gradient(
        166deg,
        var(--px-sky-top) 0%,
        var(--px-sky-mid) 50%,
        var(--px-sky-bottom) 100%
    );
    transition: filter 0.45s ease, opacity 0.45s ease, transform 0.45s ease;
}

.welcome-page[data-pixar-theme="luca-coast"] {
    --px-sky-top: #FF955C;
    --px-sky-mid: #FFD675;
    --px-sky-bottom: #38E4DF;
    --px-sun-bloom: rgba(255, 118, 92, 0.62);
    --px-secondary-bloom: rgba(46, 229, 219, 0.42);

    --px-hill-far-1: #B482FF;
    --px-hill-far-2: #7D52E6;
    --px-hill-mid-1: #2CE6D2;
    --px-hill-mid-2: #0AB09E;
    --px-hill-near-1: #12C7AE;
    --px-hill-near-2: #067D6E;
    --px-hill-rim: rgba(180, 255, 245, 0.85);
    --px-cloud-opacity: 0.9;
    --px-sparkle-color: #FFF3B0;
    --px-wing-glow: rgba(255, 184, 108, 0.48);
    --px-card-aura: rgba(20, 212, 192, 0.38);
    --px-trail-color: rgba(255, 243, 176, 0.88);

    --px-card-bg-top: rgba(255, 253, 247, 0.96);
    --px-card-bg-bottom: rgba(255, 240, 220, 0.93);
    --px-surface-well: #F7EBDA;
    --px-surface-glass: rgba(255, 253, 247, 0.88);

    --px-ink: #182940;
    --px-ink-muted: #435773;
    --px-ink-subtle: #5B6F8C;

    --px-secondary-lit: #FF9357;
    --px-secondary: #D44711;
    --px-secondary-belly: #A83307;
    --px-secondary-pedestal: #782002;
    --px-secondary-glow: rgba(212, 71, 17, 0.38);

    --px-explore-lit: #3AE8D2;
    --px-explore: #078578;
    --px-explore-belly: #05665C;
    --px-explore-pedestal: #03453E;
    --px-explore-glow: rgba(7, 133, 120, 0.42);

    --px-hero-name-grad: linear-gradient(92deg, #D44711 0%, #078578 52%, #1D4ED8 100%);
    --px-role-bg: linear-gradient(145deg, #ECFEFF 0%, #CCFBF1 100%);
    --px-role-ink: #0F5E56;
    --px-role-border: rgba(7, 133, 120, 0.35);

    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(155, 245, 234, 0.58) 58%, rgba(20, 184, 166, 0.28) 100%);

    --px-orb-1: #2CE6D2;
    --px-orb-2: #FF884D;
    --px-orb-3: #FFE054;
    --px-orb-4: #4CD4FF;
    --px-orb-5: #FF6B8B;
    --px-orb-6: #A5F36B;

    --px-shadow-rgb: 26, 40, 66;
    --px-focus-ring: rgba(7, 133, 120, 0.32);
    --px-focus-stroke: #078578;
}

.welcome-page[data-pixar-theme="memory-orb"] {
    color-scheme: dark;
    --px-sky-top: #1E124A;
    --px-sky-mid: #3A1F80;
    --px-sky-bottom: #672BA8;
    --px-sun-bloom: rgba(255, 92, 178, 0.44);
    --px-secondary-bloom: rgba(56, 208, 255, 0.36);

    --px-hill-far-1: #7B4DFF;
    --px-hill-far-2: #4B28A8;
    --px-hill-mid-1: #4F46E5;
    --px-hill-mid-2: #2D268A;
    --px-hill-near-1: #2C2266;
    --px-hill-near-2: #16103A;
    --px-hill-rim: rgba(105, 215, 255, 0.65);
    --px-cloud-opacity: 0.22;
    --px-sparkle-color: #8BE9FF;
    --px-wing-glow: rgba(105, 215, 255, 0.34);
    --px-card-aura: rgba(167, 123, 255, 0.42);
    --px-trail-color: rgba(108, 224, 255, 0.68);

    --px-card-bg-top: rgba(44, 34, 92, 0.94);
    --px-card-bg-bottom: rgba(28, 21, 64, 0.94);
    --px-surface-well: #161033;
    --px-surface-elevated: #362B6E;
    --px-surface-glass: rgba(39, 31, 82, 0.84);

    --px-ink: #FFF9F2;
    --px-ink-muted: #D4CEFA;
    --px-ink-subtle: #B3AAE6;

    --px-secondary-lit: #6CE0FF;
    --px-secondary: #0E84C9;
    --px-secondary-belly: #0A5F94;
    --px-secondary-pedestal: #063F66;
    --px-secondary-glow: rgba(14, 132, 201, 0.46);

    --px-explore-lit: #FFE76B;
    --px-explore: #FFC421;
    --px-explore-belly: #E09B00;
    --px-explore-pedestal: #A36B00;
    --px-explore-ink: #241800;
    --px-explore-glow: rgba(255, 196, 33, 0.48);

    --px-hero-name-grad: linear-gradient(92deg, #6CE0FF 0%, #FF82BA 52%, #FFC421 100%);
    --px-role-bg: linear-gradient(145deg, #273D6E 0%, #1C2E57 100%);
    --px-role-ink: #9EE8FF;
    --px-role-border: rgba(108, 224, 255, 0.42);

    --px-perk-bg: linear-gradient(135deg, #3D285E 0%, #2C1B47 100%);
    --px-perk-border: #FFC421;
    --px-perk-pedestal: #A36B00;
    --px-perk-ink: #FFE999;
    --px-perk-badge-bg: #FFC421;
    --px-perk-badge-ink: #241800;

    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.88) 0%, rgba(167, 123, 255, 0.56) 58%, rgba(255, 196, 33, 0.34) 100%);

    --px-orb-1: #FFD447;
    --px-orb-2: #48C9FF;
    --px-orb-3: #FF6B9E;
    --px-orb-4: #A77BFF;
    --px-orb-5: #45E69F;
    --px-orb-6: #FF9E42;

    --px-shadow-rgb: 9, 6, 24;
    --px-rim-top: rgba(255, 255, 255, 0.28);
    --px-card-border: rgba(167, 139, 250, 0.3);
    --px-well-shadow: inset 0 4px 10px rgba(4, 2, 16, 0.58),
        inset 0 1px 2px rgba(4, 2, 16, 0.44),
        0 1px 0 rgba(255, 255, 255, 0.16);
    --px-card-shadow:
        0 28px 56px -12px rgba(4, 2, 18, 0.62),
        0 10px 24px -4px rgba(4, 2, 18, 0.42),
        inset 0 1.5px 1px var(--px-rim-top),
        inset 0 -12px 24px -8px rgba(126, 94, 255, 0.22);
    --px-focus-ring: rgba(108, 224, 255, 0.4);
    --px-focus-stroke: #6CE0FF;
}

.welcome-page[data-pixar-theme="marigold"] {
    color-scheme: dark;
    --px-sky-top: #29103D;
    --px-sky-mid: #571963;
    --px-sky-bottom: #8C2260;
    --px-sun-bloom: rgba(255, 174, 48, 0.48);
    --px-secondary-bloom: rgba(55, 222, 198, 0.28);

    --px-hill-far-1: #C43B96;
    --px-hill-far-2: #7D1F68;
    --px-hill-mid-1: #D93D6B;
    --px-hill-mid-2: #8C1B4A;
    --px-hill-near-1: #4A1754;
    --px-hill-near-2: #240B2E;
    --px-hill-rim: rgba(255, 198, 92, 0.68);
    --px-cloud-opacity: 0.2;
    --px-sparkle-color: #FFD269;
    --px-wing-glow: rgba(255, 174, 48, 0.36);
    --px-card-aura: rgba(245, 134, 22, 0.42);
    --px-trail-color: rgba(255, 200, 87, 0.72);

    --px-card-bg-top: rgba(54, 27, 72, 0.94);
    --px-card-bg-bottom: rgba(36, 17, 50, 0.94);
    --px-surface-well: #1E0D2B;
    --px-surface-elevated: #45235C;
    --px-surface-glass: rgba(54, 27, 72, 0.86);

    --px-ink: #FFF8F0;
    --px-ink-muted: #EBCEF0;
    --px-ink-subtle: #C9A3CF;

    --px-secondary-lit: #FF70A8;
    --px-secondary: #D4246C;
    --px-secondary-belly: #A81651;
    --px-secondary-pedestal: #780A37;
    --px-secondary-glow: rgba(212, 36, 108, 0.44);

    --px-explore-lit: #87FFEE;
    --px-explore: #37DEC6;
    --px-explore-belly: #1AB39D;
    --px-explore-pedestal: #0D8070;
    --px-explore-ink: #042923;
    --px-explore-glow: rgba(55, 222, 198, 0.45);

    --px-hero-name-grad: linear-gradient(92deg, #87FFEE 0%, #FFC054 52%, #FF70A8 100%);
    --px-role-bg: linear-gradient(145deg, #4E225B 0%, #371645 100%);
    --px-role-ink: #FFD27D;
    --px-role-border: rgba(255, 192, 84, 0.42);

    --px-perk-bg: linear-gradient(135deg, #4F2046 0%, #381534 100%);
    --px-perk-border: #FF9E2C;
    --px-perk-pedestal: #9E4A00;
    --px-perk-ink: #FFE4A8;
    --px-perk-badge-bg: #FFC054;
    --px-perk-badge-ink: #261100;

    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.88) 0%, rgba(255, 184, 77, 0.54) 58%, rgba(212, 36, 108, 0.32) 100%);

    --px-orb-1: #FFAC38;
    --px-orb-2: #FF639B;
    --px-orb-3: #49E6CE;
    --px-orb-4: #FFD66E;
    --px-orb-5: #C47DFF;
    --px-orb-6: #FF785A;

    --px-shadow-rgb: 18, 6, 26;
    --px-rim-top: rgba(255, 214, 153, 0.28);
    --px-card-border: rgba(255, 186, 112, 0.28);
    --px-well-shadow: inset 0 4px 10px rgba(10, 2, 18, 0.58),
        inset 0 1px 2px rgba(10, 2, 18, 0.44),
        0 1px 0 rgba(255, 214, 153, 0.16);
    --px-card-shadow:
        0 28px 56px -12px rgba(10, 2, 18, 0.62),
        0 10px 24px -4px rgba(10, 2, 18, 0.42),
        inset 0 1.5px 1px var(--px-rim-top),
        inset 0 -12px 24px -8px rgba(245, 134, 22, 0.2);
    --px-focus-ring: rgba(255, 192, 84, 0.4);
    --px-focus-stroke: #FFC054;
}

.welcome-page.is-launching {
    filter: blur(6px) saturate(1.18);
    opacity: 0;
    transform: scale(1.03);
}

/* Plane 2: Dual Studio Sun & Bounce Light Blooms */
.pixar-stage::before {
    content: "";
    position: fixed;
    inset: -15% -10% auto -10%;
    height: 85vh;
    background:
        radial-gradient(ellipse 60% 52% at 20% 14%, var(--px-sun-bloom) 0%, transparent 68%),
        radial-gradient(ellipse 52% 48% at 82% 24%, var(--px-secondary-bloom) 0%, transparent 70%);
    pointer-events: none;
    z-index: -4;
}

/* Plane 5: Anti-Banding Tactile Cinema Grain */
.pixar-stage::after {
    content: "";
    position: fixed;
    inset: 0;
    background-image: var(--px-noise-svg);
    background-repeat: repeat;
    opacity: 0.032;
    pointer-events: none;
    z-index: 90;
}

/* Plane 2b: Clouds & Firefly Sparkles */
.pixar-cloud-field,
.pixar-sparkle-field {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: -3;
}

.pixar-cloud {
    position: absolute;
    height: auto;
    opacity: var(--px-cloud-opacity);
    filter: drop-shadow(0 12px 24px rgba(var(--px-shadow-rgb), 0.12));
    will-change: transform;
    animation: px-cloud-drift var(--_cdur, 28s) ease-in-out infinite alternate;
}

.pixar-cloud--1 { width: clamp(160px, 18vw, 250px); top: 8%; left: 9%; --_cdur: 26s; }
.pixar-cloud--2 { width: clamp(140px, 15vw, 210px); top: 14%; right: 11%; --_cdur: 32s; animation-delay: -9s; }
.pixar-cloud--3 { width: clamp(110px, 12vw, 175px); top: 34%; left: 26%; --_cdur: 24s; animation-delay: -5s; }

@keyframes px-cloud-drift {
    0% { transform: translate3d(-14px, 0, 0); }
    100% { transform: translate3d(20px, -8px, 0); }
}

.pixar-sparkle {
    position: absolute;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: radial-gradient(circle, #FFFFFF 0%, var(--px-sparkle-color) 55%, transparent 100%);
    box-shadow: 0 0 14px 3px var(--px-sparkle-color);
    will-change: transform, opacity;
    animation: px-twinkle var(--_sdur, 4.4s) ease-in-out infinite alternate;
}

.pixar-sparkle--1 { top: 16%; left: 22%; --_sdur: 3.8s; }
.pixar-sparkle--2 { top: 26%; right: 24%; --_sdur: 4.9s; animation-delay: -1.4s; }
.pixar-sparkle--3 { top: 48%; left: 18%; --_sdur: 4.2s; animation-delay: -2.2s; }
.pixar-sparkle--4 { top: 54%; right: 20%; --_sdur: 5.1s; animation-delay: -0.7s; }
.pixar-sparkle--5 { top: 18%; left: 42%; width: 7px; height: 7px; --_sdur: 3.4s; animation-delay: -1.9s; }
.pixar-sparkle--6 { bottom: 26%; right: 36%; width: 8px; height: 8px; --_sdur: 4.5s; animation-delay: -2.8s; }

@keyframes px-twinkle {
    0% { transform: translate3d(0, 6px, 0) scale(0.65); opacity: 0.35; }
    100% { transform: translate3d(4px, -10px, 0) scale(1.25); opacity: 0.95; }
}

/* Plane 3: Drifting Volumetric Memory Orbs */
.pixar-orb-field {
    position: fixed;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: -2;
    transition: transform 260ms var(--px-ease-settle);
}

.pixar-orb {
    position: absolute;
    border-radius: 50%;
    background:
        radial-gradient(circle at 28% 24%, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.56) 15%, transparent 38%),
        radial-gradient(circle at 58% 60%, var(--_orb-color, var(--px-orb-1)) 0%, var(--_orb-color, var(--px-orb-1)) 55%, rgba(255, 255, 255, 0.12) 100%);
    box-shadow:
        inset 0 -10px 22px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 4px 10px rgba(255, 255, 255, 0.72),
        0 18px 40px -8px rgba(var(--px-shadow-rgb), 0.24);
    opacity: 0.88;
    will-change: transform;
    animation: px-orb-float var(--_orb-dur, 18s) ease-in-out infinite alternate;
    animation-delay: var(--_orb-delay, 0s);
}

.pixar-orb--1 { width: 132px; height: 132px; top: 8%; left: 5%; --_orb-color: var(--px-orb-1); --_orb-dur: 16s; }
.pixar-orb--2 { width: 88px; height: 88px; top: 19%; right: 10%; --_orb-color: var(--px-orb-2); --_orb-dur: 13s; --_orb-delay: -4s; }
.pixar-orb--3 { width: 162px; height: 162px; bottom: 14%; left: 12%; --_orb-color: var(--px-orb-3); --_orb-dur: 21s; --_orb-delay: -7s; opacity: 0.82; }
.pixar-orb--4 { width: 104px; height: 104px; bottom: 20%; right: 14%; --_orb-color: var(--px-orb-4); --_orb-dur: 15s; --_orb-delay: -2s; }
.pixar-orb--5 { width: 54px; height: 54px; top: 38%; left: 31%; --_orb-color: var(--px-orb-5); --_orb-dur: 14s; --_orb-delay: -6s; opacity: 0.78; }
.pixar-orb--6 { width: 62px; height: 62px; top: 44%; right: 29%; --_orb-color: var(--px-orb-6); --_orb-dur: 17s; --_orb-delay: -9s; opacity: 0.8; }

@keyframes px-orb-float {
    0% { transform: translate3d(0, 0, 0) scale(1); }
    50% { transform: translate3d(18px, -24px, 0) scale(1.05); }
    100% { transform: translate3d(-14px, 14px, 0) scale(0.96); }
}

/* Plane 4: Tri-Ridge Multi-Stop Horizon Hills */
.pixar-hills {
    position: fixed;
    left: 0;
    right: 0;
    bottom: -2px;
    height: clamp(120px, 21vh, 235px);
    pointer-events: none;
    z-index: -1;
}

.pixar-hills svg {
    width: 100%;
    height: 100%;
    display: block;
}

.adventure-trail-layer {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: -1;
}

.trail-svg {
    width: 100%;
    height: 100%;
    overflow: visible;
}

.trail-path {
    stroke: var(--px-trail-color);
    stroke-width: 4.5;
    stroke-linecap: round;
    stroke-dasharray: 10 15;
    animation: dashFlow 22s linear infinite;
    filter: drop-shadow(0 4px 10px rgba(var(--px-shadow-rgb), 0.16));
}

.trail-path--right {
    animation-direction: reverse;
}

@keyframes dashFlow {
    from { stroke-dashoffset: 400; }
    to { stroke-dashoffset: 0; }
}

/* ==========================================================================
   TOP-RIGHT UTILITY BAR (WORLD SWATCHES + SOUND CONTROLLER)
   ========================================================================== */
.top-utility-bar {
    position: fixed;
    top: 0.9rem;
    right: 1.15rem;
    z-index: 40;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.pixar-world-picker {
    display: inline-flex;
    align-items: center;
    gap: 0.42rem;
    padding: 0.36rem 0.72rem;
    border-radius: 999px;
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-glass);
    backdrop-filter: blur(14px) saturate(145%);
    -webkit-backdrop-filter: blur(14px) saturate(145%);
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb), 0.16),
        0 10px 20px -6px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 2px var(--px-rim-top);
}

.world-picker-label {
    font-family: var(--px-font-display);
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: var(--px-ink);
    padding-right: 0.15rem;
}

.world-swatch-btn {
    position: relative;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid var(--px-card-border);
    padding: 0;
    cursor: pointer;
    box-shadow:
        inset 0 2px 3px rgba(255, 255, 255, 0.82),
        0 3px 6px rgba(var(--px-shadow-rgb), 0.24);
    transition: transform 220ms var(--px-ease-spring), box-shadow 200ms ease;
}

.world-swatch-btn:hover {
    transform: translate3d(0, -2px, 0) scale(1.16);
}

.world-swatch-btn:active {
    transform: scale(0.92);
}

.world-swatch-btn.is-active {
    transform: scale(1.18);
    box-shadow:
        0 0 0 2.5px var(--px-ink),
        0 4px 10px rgba(var(--px-shadow-rgb), 0.28),
        inset 0 2px 3px rgba(255, 255, 255, 0.85);
}

.world-swatch-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

/* ==========================================================================
   FLANKING 3D DIORAMA WINGS & SEMANTIC SPEECH CHIPS
   ========================================================================== */
.scene-wing {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 30vw;
    max-width: 440px;
    z-index: 2;
    pointer-events: none;
    transition: transform 240ms var(--px-ease-settle), opacity 0.3s ease;
}

.scene-wing::before {
    content: "";
    position: absolute;
    inset: 14% 4% 8% 4%;
    border-radius: 50%;
    background: radial-gradient(circle at 50% 52%, var(--px-wing-glow) 0%, transparent 70%);
    pointer-events: none;
}

.wing-left { left: 0; }
.wing-right { right: 0; }

.asset-pop {
    height: auto;
    display: block;
    user-select: none;
    pointer-events: none;
    filter: drop-shadow(0 16px 24px rgba(var(--px-shadow-rgb), 0.2));
}

.asset-book-left {
    position: absolute;
    top: 6%;
    left: 4%;
    width: 72%;
    max-width: 295px;
    animation: floatBookLeft 7s ease-in-out infinite;
}

.boy-pod-wrap {
    position: absolute;
    top: 38%;
    right: 6%;
    width: 50%;
    max-width: 205px;
    animation: floatBoy 6.2s ease-in-out infinite 0.4s;
}

.asset-boy-laptop {
    width: 100%;
}

.diorama-floor-anchor {
    position: absolute;
    bottom: 0;
    width: 74%;
    max-width: 305px;
    pointer-events: none;
}

.floor-left { left: 4%; }
.floor-right { right: 4%; }

.diorama-contact-shadow {
    position: absolute;
    left: 12%;
    right: 12%;
    bottom: 10px;
    height: 22px;
    border-radius: 50%;
    background: radial-gradient(ellipse at center, rgba(var(--px-shadow-rgb), 0.26) 0%, transparent 72%);
}

.asset-girl-reading {
    position: relative;
    width: 100%;
    animation: floatGirlLeft 6.6s ease-in-out infinite 0.8s;
}

.asset-book-right {
    position: absolute;
    top: 6%;
    right: 5%;
    width: 72%;
    max-width: 295px;
    animation: floatBookRight 7.2s ease-in-out infinite 0.2s;
}

.message-girl-pod-wrap {
    position: absolute;
    top: 37%;
    left: 4%;
    width: 66%;
    max-width: 265px;
    animation: floatBookLaptop 6.5s ease-in-out infinite 0.7s;
}

.asset-book-laptop-girl {
    width: 100%;
}

.asset-girl-tablet {
    position: relative;
    width: 100%;
    animation: floatGirlRight 6s ease-in-out infinite 1.2s;
}

.floating-speech-chip {
    position: relative;
    margin: -10px auto 0;
    display: flex;
    width: fit-content;
    align-items: center;
    gap: 0.38rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-glass);
    backdrop-filter: blur(12px) saturate(140%);
    -webkit-backdrop-filter: blur(12px) saturate(140%);
    color: var(--px-ink);
    font-family: var(--px-font-display);
    font-size: 0.76rem;
    font-weight: 700;
    white-space: nowrap;
    pointer-events: auto;
    cursor: pointer;
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb), 0.16),
        0 10px 20px -6px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 2px 2px var(--px-rim-top);
    transition: transform 200ms var(--px-ease-spring), box-shadow 180ms ease;
}

.floating-speech-chip:hover {
    transform: translate3d(0, -3px, 0) scale(1.06);
}

.floating-speech-chip:active {
    transform: translate3d(0, 2px, 0) scale(0.96);
}

.floating-speech-chip:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.chip-svg-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    color: var(--px-ink);
}

@keyframes floatBookLeft {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -12px, 0) rotate(-1.5deg); }
}

@keyframes floatBoy {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -10px, 0) rotate(1.2deg); }
}

@keyframes floatGirlLeft {
    0%, 100% { transform: translate3d(0, 0, 0); }
    50% { transform: translate3d(0, -8px, 0); }
}

@keyframes floatBookRight {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -12px, 0) rotate(1.5deg); }
}

@keyframes floatBookLaptop {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -10px, 0) rotate(-1.2deg); }
}

@keyframes floatGirlRight {
    0%, 100% { transform: translate3d(0, 0, 0); }
    50% { transform: translate3d(0, -8px, 0); }
}

/* Volumetric Ambient Stars */
.ambient-decorations {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
}

.float-star {
    position: absolute;
    filter: drop-shadow(0 6px 10px rgba(var(--px-shadow-rgb), 0.22));
    animation: pulseStar 3.2s ease-in-out infinite alternate;
}

.star-1 { top: 8%; left: 32%; animation-delay: 0.2s; }
.star-2 { bottom: 13%; left: 30%; animation-delay: 1.2s; }
.star-3 { bottom: 14%; right: 30%; animation-delay: 0.8s; }

@keyframes pulseStar {
    0% { transform: scale(0.88) rotate(0deg); opacity: 0.78; }
    100% { transform: scale(1.14) rotate(14deg); opacity: 1; }
}

/* ==========================================================================
   CENTERPIECE PIXAR HERO WELCOME CARD
   ========================================================================== */
.hero-stage {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 680px;
    display: flex;
    justify-content: center;
    margin: 0 auto;
}

.pixar-hero-card {
    position: relative;
    width: 100%;
    text-align: center;
    padding: 3.35rem 2.35rem 2.25rem;
    border-radius: 38px;
    box-sizing: border-box;
    transform-style: preserve-3d;
    background: linear-gradient(172deg, var(--px-card-bg-top) 0%, var(--px-card-bg-bottom) 100%);
    backdrop-filter: blur(18px) saturate(145%);
    -webkit-backdrop-filter: blur(18px) saturate(145%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        var(--px-card-shadow),
        0 0 48px -10px var(--px-card-aura);
    opacity: 0;
    transform: translate3d(0, 28px, 0) scale(0.95);
    transition:
        opacity 0.55s ease 140ms,
        transform 0.75s var(--px-ease-spring) 140ms,
        background 360ms var(--px-ease-settle),
        box-shadow 280ms var(--px-ease-settle);
}

.is-mounted .pixar-hero-card {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
}

.pixar-hero-card::before {
    content: "";
    position: absolute;
    inset: 8px 28px auto 28px;
    height: 38px;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--px-rim-top) 0%, rgba(255, 255, 255, 0) 100%);
    opacity: 0.72;
    pointer-events: none;
}

/* Overlapping Crest Row: Globe + Lumina Explorer + Map Book */
.pixar-hero-crest-row {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translate(-50%, -52%) translateZ(26px);
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    pointer-events: none;
}

.mini-orb-badge {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: var(--px-surface-elevated);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 10px 20px -6px rgba(var(--px-shadow-rgb), 0.25),
        inset 0 2px 3px var(--px-rim-top);
    animation: miniOrbBob 4.2s ease-in-out infinite alternate;
}

.orb-book {
    animation-delay: -2.1s;
}

@keyframes miniOrbBob {
    0% { transform: translate3d(0, 2px, 0) rotate(-4deg); }
    100% { transform: translate3d(0, -6px, 0) rotate(4deg); }
}

.pixar-hero-crest {
    width: 86px;
    height: 86px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: var(--px-crest-bg);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 16px 32px -4px var(--px-accent-glow),
        0 8px 16px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 3px rgba(255, 255, 255, 0.92);
}

.mascot-svg {
    display: block;
}

.mascot-eyes-blink {
    transform-box: fill-box;
    transform-origin: center;
    animation: px-mascot-blink 4.6s ease-in-out infinite;
}

@keyframes px-mascot-blink {
    0%, 92%, 100% { transform: scaleY(1); }
    96% { transform: scaleY(0.12); }
}

/* Sculpted Glossy Bookmark Ribbon */
.hero-bookmark-ribbon {
    position: absolute;
    top: -6px;
    right: 42px;
    width: 34px;
    height: 54px;
    background: linear-gradient(180deg, var(--px-primary-lit) 0%, var(--px-primary) 65%, var(--px-primary-belly) 100%);
    border: 2px solid rgba(255, 255, 255, 0.65);
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow:
        0 8px 16px -4px var(--px-primary-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 12;
    transform-origin: top center;
    animation: bookmarkSway 4.2s ease-in-out infinite alternate;
}

@keyframes bookmarkSway {
    0% { transform: rotate(-1.5deg); }
    100% { transform: rotate(2.5deg); }
}

.ribbon-tail {
    position: absolute;
    bottom: -8px;
    left: 0;
    right: 0;
    height: 8px;
    background: var(--px-primary-belly);
    clip-path: polygon(0% 0%, 100% 0%, 50% 100%);
}

.ribbon-star {
    filter: drop-shadow(0 2px 3px rgba(var(--px-shadow-rgb), 0.25));
}

.corner-rivet {
    position: absolute;
    width: 13px;
    height: 13px;
    background: radial-gradient(circle at 35% 35%, #FFF4A8 0%, #FFB814 62%, #B86B00 100%);
    border: 1.5px solid var(--px-card-border);
    border-radius: 50%;
    box-shadow:
        0 2px 4px rgba(var(--px-shadow-rgb), 0.24),
        inset 1px 1px 2px rgba(255, 255, 255, 0.85);
}

.rivet-tl { top: 18px; left: 18px; }
.rivet-tr { top: 18px; right: 18px; }
.rivet-bl { bottom: 18px; left: 18px; }
.rivet-br { bottom: 18px; right: 18px; }

/* Role Chip */
.role-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.35rem;
    padding: 0.36rem 1rem;
    font-family: var(--px-font-display);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: var(--px-role-ink);
    background: var(--px-role-bg);
    border: 1.5px solid var(--px-role-border);
    border-radius: 999px;
    box-shadow:
        0 3px 0 rgba(var(--px-shadow-rgb), 0.1),
        inset 0 2px 2px rgba(255, 255, 255, 0.55);
}

.chip-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.75);
}

.time-greeting {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    width: 100%;
    margin: 0.9rem 0 0.25rem;
    font-family: var(--px-font-body);
    font-size: 1rem;
    font-weight: 800;
    color: var(--px-ink-muted);
}

.time-icon {
    display: inline-flex;
    animation: miniOrbBob 3.2s ease-in-out infinite alternate;
}

/* Headline */
.greeting {
    margin: 0 0 0.75rem;
    font-family: var(--px-font-display);
    font-size: clamp(2.35rem, 5.6vw, 4.15rem);
    font-weight: 900;
    line-height: 1.08;
    letter-spacing: -0.015em;
    color: var(--px-ink);
}

.greeting-hi {
    margin-right: 0.22em;
}

.greeting-name {
    display: inline;
}

.name-letter {
    display: inline-block;
    background: var(--px-hero-name-grad);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 3px 0 rgba(var(--px-shadow-rgb), 0.14));
    opacity: 0;
    transform: translate3d(0, 18px, 0) scale(0.75) rotate(-6deg);
    transition:
        opacity 0.32s ease calc(240ms + var(--i) * 40ms),
        transform 0.52s var(--px-ease-spring) calc(240ms + var(--i) * 40ms);
}

.is-mounted .name-letter {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1) rotate(0deg);
}

.greeting-bang {
    display: inline-block;
    color: var(--px-primary-lit);
    animation: bangWiggle 2.8s ease-in-out 1.4s infinite;
}

@keyframes bangWiggle {
    0%, 72%, 100% { transform: rotate(0deg); }
    82% { transform: rotate(12deg) scale(1.1); }
    92% { transform: rotate(-8deg); }
}

.subtitle {
    margin: 0 auto 1.35rem;
    max-width: 490px;
    font-family: var(--px-font-body);
    font-size: 1.02rem;
    font-weight: 700;
    line-height: 1.5;
    color: var(--px-ink-muted);
}

/* ==========================================================================
   INSIDE-CARD RECESSED MINI QUEST SUMMARY PILLS
   ========================================================================== */
.quest-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.7rem;
    margin-bottom: 1rem;
}

.quest-mini-pill {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.62rem 0.8rem;
    border-radius: 20px;
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    color: var(--px-ink);
    text-align: left;
    cursor: pointer;
    transition: transform 200ms var(--px-ease-spring), box-shadow 200ms ease;
}

.quest-mini-pill:hover {
    transform: translate3d(0, -2px, 0) scale(1.02);
}

.quest-mini-pill:active {
    transform: translate3d(0, 2px, 0) scale(0.98);
}

.quest-mini-pill:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.quest-mini-orb {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    color: #FFFFFF;
    box-shadow:
        0 3px 0 rgba(var(--px-shadow-rgb), 0.2),
        inset 0 2px 2px rgba(255, 255, 255, 0.55);
}

.quest-orb-gold {
    background: linear-gradient(180deg, #FF9E42 0%, #E0680B 100%);
}

.quest-orb-mint {
    background: linear-gradient(180deg, #3AE8D2 0%, #078578 100%);
}

.quest-mini-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.quest-mini-kicker {
    font-family: var(--px-font-display);
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: var(--px-ink-subtle);
}

.quest-mini-title {
    font-size: 0.84rem;
    font-weight: 800;
    color: var(--px-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.quest-tag-xp {
    font-family: var(--px-font-display);
    font-size: 0.7rem;
    font-weight: 800;
    background: linear-gradient(180deg, var(--px-accent-lit) 0%, var(--px-accent) 100%);
    color: var(--px-accent-ink);
    padding: 0.22rem 0.55rem;
    border-radius: 999px;
    box-shadow: 0 2px 0 var(--px-accent-pedestal);
    flex-shrink: 0;
}

.quest-tag-unlocked {
    font-family: var(--px-font-display);
    font-size: 0.68rem;
    font-weight: 800;
    background: linear-gradient(180deg, #3DE094 0%, #0D7E4E 100%);
    color: #FFFFFF;
    padding: 0.22rem 0.55rem;
    border-radius: 999px;
    box-shadow: 0 2px 0 #075434;
    flex-shrink: 0;
}

/* Interactive Welcome Perk Pill */
.perk-row {
    display: flex;
    justify-content: center;
    margin-bottom: 1.45rem;
}

.welcome-perk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.55rem;
    background: var(--px-perk-bg);
    border: 2px solid var(--px-perk-border);
    border-radius: 999px;
    padding: 0.52rem 1.05rem;
    color: var(--px-perk-ink);
    font-family: var(--px-font-body);
    cursor: pointer;
    box-shadow:
        0 4px 0 var(--px-perk-pedestal),
        0 10px 20px -6px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 2px rgba(255, 255, 255, 0.55);
    transition: transform 200ms var(--px-ease-spring), box-shadow 180ms ease;
}

.welcome-perk-btn:hover {
    transform: translate3d(0, -2px, 0) scale(1.02);
}

.welcome-perk-btn:active {
    transform: translate3d(0, 2px, 0) scale(0.98);
}

.welcome-perk-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.perk-star-svg {
    flex-shrink: 0;
    animation: pulseStar 2.2s ease-in-out infinite alternate;
}

.perk-text {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--px-perk-ink);
}

.perk-text strong {
    font-weight: 900;
}

.perk-badge {
    font-family: var(--px-font-display);
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    background: var(--px-perk-badge-bg);
    color: var(--px-perk-badge-ink);
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

/* ==========================================================================
   ZERO-LAYOUT-SHIFT SCULPTED TOY-VINYL EXPLORE CTA
   ========================================================================== */
.explore-wrap {
    position: relative;
    display: inline-flex;
    justify-content: center;
}

.explore-halo {
    position: absolute;
    inset: -7px -7px 0 -7px;
    border-radius: 999px;
    border: 2.5px solid var(--px-explore-lit);
    opacity: 0.55;
    pointer-events: none;
    animation: haloPulse 2.4s ease-out infinite;
}

@keyframes haloPulse {
    0% { opacity: 0.65; transform: scale(0.98); }
    100% { opacity: 0; transform: scale(1.1); }
}

.pixar-btn {
    --_lit: var(--px-explore-lit);
    --_base: var(--px-explore);
    --_belly: var(--px-explore-belly);
    --_pedestal: var(--px-explore-pedestal);
    --_ink: var(--px-explore-ink);
    --_glow: var(--px-explore-glow);
    --_depth: 7px;

    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 268px;
    min-height: 58px;
    padding: 0.92rem 2.15rem;
    margin-bottom: var(--_depth);
    border: 0;
    border-radius: 999px;
    font-family: var(--px-font-display);
    font-size: 1.24rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: var(--_ink);
    cursor: pointer;
    user-select: none;
    overflow: hidden;
    background: linear-gradient(180deg, var(--_lit) 0%, var(--_base) 46%, var(--_belly) 100%);
    box-shadow:
        0 var(--_depth) 0 var(--_pedestal),
        0 calc(var(--_depth) + 12px) 26px -6px var(--_glow),
        0 4px 8px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 2px rgba(255, 255, 255, 0.65),
        inset 0 -5px 10px rgba(var(--px-shadow-rgb), 0.16);
    transform: translate3d(0, 0, 0) scale(1);
    transition:
        transform 240ms var(--px-ease-spring),
        box-shadow 200ms var(--px-ease-settle),
        filter 180ms ease;
}

.pixar-btn::before {
    content: "";
    position: absolute;
    inset: 3px 10px auto 10px;
    height: 44%;
    border-radius: inherit;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.48) 0%, rgba(255, 255, 255, 0.04) 100%);
    pointer-events: none;
}

.pixar-btn::after {
    content: "";
    position: absolute;
    top: -20%;
    left: -45%;
    width: 36%;
    height: 140%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.36) 50%, transparent 100%);
    transform: skewX(-24deg) translateX(-120%);
    transition: transform 520ms var(--px-ease-settle);
    pointer-events: none;
}

.pixar-btn:hover:not(:disabled) {
    transform: translate3d(0, -3px, 0) scale(1.03);
    box-shadow:
        0 calc(var(--_depth) + 2px) 0 var(--_pedestal),
        0 calc(var(--_depth) + 18px) 32px -5px var(--_glow),
        0 6px 12px rgba(var(--px-shadow-rgb), 0.24),
        inset 0 2px 3px rgba(255, 255, 255, 0.74),
        inset 0 -5px 10px rgba(var(--px-shadow-rgb), 0.14);
}

.pixar-btn:hover:not(:disabled)::after {
    transform: skewX(-24deg) translateX(460%);
}

.pixar-btn:active:not(:disabled) {
    transform: translate3d(0, calc(var(--_depth) - 2px), 0) scaleX(1.035) scaleY(0.95);
    box-shadow:
        0 2px 0 var(--_pedestal),
        0 4px 10px -2px var(--_glow),
        0 1px 3px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 2px rgba(255, 255, 255, 0.48),
        inset 0 -3px 6px rgba(var(--px-shadow-rgb), 0.2);
    transition-duration: 75ms;
}

.pixar-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

.pixar-btn:disabled {
    cursor: wait;
    transform: translate3d(0, -2px, 0) scale(1.04);
}

.btn-explore-content {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
}

.rocket-svg {
    transition: transform 280ms var(--px-ease-spring);
}

.pixar-btn:hover:not(:disabled) .rocket-svg {
    transform: translate3d(3px, -3px, 0) rotate(-6deg);
}

.btn-arrow-orb {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.24);
    border: 1.5px solid rgba(255, 255, 255, 0.55);
    box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6);
    transition: transform 260ms var(--px-ease-spring);
}

.pixar-btn:hover:not(:disabled) .btn-arrow-orb {
    transform: translateX(5px);
}

/* ==========================================================================
   RESPONSIVE DESIGN & REDUCED MOTION
   ========================================================================== */
@media (max-width: 1180px) {
    .scene-wing {
        width: 24vw;
        opacity: 0.8;
    }
}

@media (max-width: 940px) {
    .scene-wing {
        display: none;
    }

    .float-star {
        opacity: 0.55;
    }
}

@media (max-width: 600px) {
    .welcome-page {
        padding: 4.8rem 1rem 2.25rem;
    }

    .top-utility-bar {
        right: 0.75rem;
        left: 0.75rem;
        justify-content: space-between;
    }

    .world-picker-label {
        display: none;
    }

    .pixar-hero-card {
        padding: 3rem 1.25rem 1.85rem;
        border-radius: 30px;
    }

    .quest-mini-grid {
        grid-template-columns: 1fr;
    }

    .welcome-perk-btn {
        border-radius: 20px;
        padding: 0.6rem 0.9rem;
    }

    .explore-wrap,
    .pixar-btn--explore {
        width: 100%;
    }

    .pixar-btn--explore {
        min-width: 0;
        font-size: 1.12rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        transition-delay: 0ms !important;
    }

    .pixar-orb,
    .pixar-cloud,
    .pixar-sparkle,
    .mascot-eyes-blink {
        animation: none !important;
    }

    .scene-wing,
    .pixar-orb-field {
        transform: none !important;
    }
}
</style>
