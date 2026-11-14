<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '../../Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { usePixarTheme } from '@/Composables/usePixarTheme';
import { flushAllClientCache } from '@/Composables/usePrefetch';
import { isLitePerformanceMode, setPerformanceMode } from '@/Composables/usePerformanceMode';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student Explorer',
            email: 'student@example.com',
            role: 'student',
            ui_theme: 'toybox',
        }),
    },
});

const {
    state: soundState,
    playPop,
    playSparkle,
    playSuccess,
    playClick,
    toggleBgm,
    toggleMute,
    setBgmVolume,
    setSfxVolume,
} = useSound();

const {
    PIXAR_WORLDS,
    activeWorld,
    activeWorldMeta,
    isSavingWorld,
    setWorldTheme,
    persistThemeToAccount,
    spawnPixarBurst,
} = usePixarTheme();

// Local reading & companion states with persistence
const defaultSpeed = typeof window !== 'undefined' ? localStorage.getItem('emodule_narration_speed') || '1' : '1';
const defaultLargeText = typeof window !== 'undefined' ? localStorage.getItem('emodule_large_text') === 'true' : false;
const defaultCompanion = typeof window !== 'undefined' ? localStorage.getItem('emodule_companion') || 'fox' : 'fox';

const selectedSpeed = ref(defaultSpeed);
const isLargeText = ref(defaultLargeText);
const selectedCompanion = ref(defaultCompanion);
const showSavedToast = ref(false);
const isSmoothMode = ref(isLitePerformanceMode());

const toggleSmoothMode = () => {
    isSmoothMode.value = !isSmoothMode.value;
    setPerformanceMode(isSmoothMode.value ? 'lite' : 'full');
    playPop(1.15);
};
const savedToastText = ref('Settings & Theme Saved to Your Account!');
const saveBtnRef = ref(null);
const isLoggingOut = ref(false);

let toastTimerId = null;

const speedOptions = [
    { value: '0.85', rate: '0.85x', tag: 'Gentle', label: '0.85x (Gentle)' },
    { value: '1', rate: '1.0x', tag: 'Standard', label: '1.0x (Standard)' },
    { value: '1.15', rate: '1.15x', tag: 'Brisk', label: '1.15x (Brisk)' },
];

const companions = [
    {
        id: 'fox',
        name: 'Ember Fox',
        bg: 'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
        border: '#EA580C',
        desc: 'Quick-witted & energetic guide',
    },
    {
        id: 'astro',
        name: 'Astro Cat',
        bg: 'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
        border: '#2563EB',
        desc: 'Curious star voyager',
    },
    {
        id: 'knight',
        name: 'Sprout Bear',
        bg: 'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
        border: '#059669',
        desc: 'Patient & protective guardian',
    },
    {
        id: 'owl',
        name: 'Mystic Owl',
        bg: 'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
        border: '#7C3AED',
        desc: 'Wise decoder of ancient lore',
    },
];

const syncCompanionFromHud = (event) => {
    const nextId = event?.detail?.id || window.localStorage?.getItem('emodule_companion');
    if (nextId && nextId !== selectedCompanion.value) {
        selectedCompanion.value = nextId;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('emodule:companion-changed', syncCompanionFromHud);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('emodule:companion-changed', syncCompanionFromHud);
    }
    if (toastTimerId) {
        clearTimeout(toastTimerId);
    }
});

const triggerToast = (message) => {
    savedToastText.value = message;
    showSavedToast.value = true;
    if (toastTimerId) {
        clearTimeout(toastTimerId);
    }
    toastTimerId = setTimeout(() => {
        showSavedToast.value = false;
    }, 2800);
};

const handleSelectWorld = (world, event) => {
    if (activeWorld.value === world.id) {
        playPop(1.2);
        return;
    }

    playSparkle();
    setWorldTheme(world.id, {
        persist: true,
        anchorEl: event?.currentTarget || null,
        burstCount: 12,
        onSaved: () => {
            triggerToast(`${world.name} theme saved to your account!`);
        },
    });
};

const handleBgmSlider = (e) => {
    const val = parseFloat(e.target.value);
    setBgmVolume(val);
    if (!soundState.isBgmPlaying && val > 0) {
        toggleBgm();
    }
};

const handleSfxSlider = (e) => {
    const val = parseFloat(e.target.value);
    setSfxVolume(val);
    playPop(1.1);
};

const handleSpeedChange = (speed) => {
    selectedSpeed.value = speed;
    if (typeof window !== 'undefined') {
        localStorage.setItem('emodule_narration_speed', speed);
    }
    playPop(1.2);
};

const toggleLargeText = () => {
    isLargeText.value = !isLargeText.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('emodule_large_text', String(isLargeText.value));
    }
    playPop(1.15);
};

const selectCompanion = (id) => {
    selectedCompanion.value = id;
    if (typeof window !== 'undefined') {
        localStorage.setItem('emodule_companion', id);
        window.dispatchEvent(new CustomEvent('emodule:companion-changed', { detail: { id } }));
    }
    playSparkle();
};

const saveAllPreferences = (event) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('emodule_narration_speed', selectedSpeed.value);
        localStorage.setItem('emodule_large_text', String(isLargeText.value));
        localStorage.setItem('emodule_companion', selectedCompanion.value);
        window.dispatchEvent(
            new CustomEvent('emodule:companion-changed', { detail: { id: selectedCompanion.value } }),
        );
    }

    const anchor = event?.currentTarget || saveBtnRef.value;
    persistThemeToAccount(activeWorld.value, {
        onSuccess: () => {
            playSuccess();
            if (anchor) {
                spawnPixarBurst(anchor, 14, activeWorld.value);
            }
            triggerToast(`${activeWorldMeta.value.name} & settings saved across every login!`);
        },
    });
};

const handleLogout = () => {
    if (isLoggingOut.value) {
        return;
    }
    isLoggingOut.value = true;
    playClick();
    flushAllClientCache();
    router.post(
        '/logout',
        {},
        {
            onFinish: () => {
                isLoggingOut.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Student Settings • WEBREAD" />

    <StudentLayout
        :user="user"
        :level="6"
        :xp="450"
        :max-xp="1000"
        :points="3620"
        :stars="32"
        :streak="6"
        active-nav="settings"
    >
        <section class="clay-settings-page" aria-labelledby="settings-heading">
            <!-- 1. COMPACT PIXAR 3D HERO HEADER BANNER -->
            <div class="clay-settings-hero">
                <span class="hero-gloss-shine" aria-hidden="true"></span>

                <div class="hero-top-meta">
                    <span class="settings-pill">STUDENT STUDIO</span>
                    <span class="settings-status-tag" :class="{ 'is-syncing': isSavingWorld }">
                        <span class="status-sync-dot" aria-hidden="true"></span>
                        {{ isSavingWorld ? 'Saving to Account...' : `World: ${activeWorldMeta.name}` }}
                    </span>
                </div>

                <div class="hero-content-row">
                    <div class="hero-text-col">
                        <h1 id="settings-heading" class="hero-main-title">
                            Color Worlds, Audio & Studio Settings
                        </h1>
                        <p class="hero-desc">
                            Pick your animated color world, audio vibe, reading pace, and companion. Saved on every login!
                        </p>
                    </div>

                    <div class="hero-icon-pod" aria-hidden="true">
                        <svg viewBox="0 0 64 64" class="clay-settings-cog-svg">
                            <defs>
                                <radialGradient id="pixarCogSphere" cx="32%" cy="26%" r="74%">
                                    <stop offset="0%" stop-color="#FFF5C0" />
                                    <stop offset="48%" stop-color="#FFBE1A" />
                                    <stop offset="100%" stop-color="#C96D00" />
                                </radialGradient>
                                <radialGradient id="pixarCogCore" cx="35%" cy="30%" r="70%">
                                    <stop offset="0%" stop-color="#52ADFF" />
                                    <stop offset="100%" stop-color="#18143B" />
                                </radialGradient>
                            </defs>
                            <!-- Volumetric 3D Gear Teeth -->
                            <g fill="url(#pixarCogSphere)" stroke="#FFFFFF" stroke-width="1.8">
                                <rect x="27" y="2" width="10" height="12" rx="4" />
                                <rect x="27" y="50" width="10" height="12" rx="4" />
                                <rect x="2" y="27" width="12" height="10" rx="4" />
                                <rect x="50" y="27" width="12" height="10" rx="4" />
                                <rect x="27" y="2" width="10" height="12" rx="4" transform="rotate(45 32 32)" />
                                <rect x="27" y="50" width="10" height="12" rx="4" transform="rotate(45 32 32)" />
                                <rect x="2" y="27" width="12" height="10" rx="4" transform="rotate(45 32 32)" />
                                <rect x="50" y="27" width="12" height="10" rx="4" transform="rotate(45 32 32)" />
                            </g>
                            <circle cx="32" cy="32" r="22" fill="url(#pixarCogSphere)" stroke="#FFFFFF" stroke-width="2.2" />
                            <ellipse cx="24" cy="21" rx="8.5" ry="4.2" transform="rotate(-24 24 21)" fill="#FFFFFF" opacity="0.7" />
                            <circle cx="32" cy="32" r="10.5" fill="#FFFFFF" opacity="0.94" />
                            <circle cx="32" cy="32" r="6.2" fill="url(#pixarCogCore)" />
                            <circle cx="30" cy="30" r="1.8" fill="#FFFFFF" opacity="0.9" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- TOAST CONFIRMATION -->
            <transition name="toast-pop">
                <div v-if="showSavedToast" class="clay-saved-toast" role="status" aria-live="polite">
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ savedToastText }}</span>
                </div>
            </transition>

            <!-- 2. PIXAR COLOR WORLD THEME PICKER -->
            <div class="clay-card world-studio-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-badge-orb orb-palette" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r="1.5" fill="currentColor" />
                                <circle cx="17.5" cy="10.5" r="1.5" fill="currentColor" />
                                <circle cx="8.5" cy="7.5" r="1.5" fill="currentColor" />
                                <circle cx="6.5" cy="12.5" r="1.5" fill="currentColor" />
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="card-title">Color World Theme</h2>
                            <p class="card-subtitle">Applies across all student pages & saves automatically</p>
                        </div>
                    </div>

                    <span class="world-current-chip">
                        {{ activeWorldMeta.mode }}
                    </span>
                </div>

                <div class="worlds-grid" role="radiogroup" aria-label="Choose your color world">
                    <button
                        v-for="world in PIXAR_WORLDS"
                        :key="world.id"
                        type="button"
                        role="radio"
                        class="world-theme-card"
                        :class="{ 'is-selected': activeWorld === world.id, 'is-dark-world': world.isDark }"
                        :aria-checked="activeWorld === world.id"
                        @click="(e) => handleSelectWorld(world, e)"
                    >
                        <!-- Miniature Pixar 5-Plane Diorama Preview -->
                        <div class="world-mini-stage" :style="{ background: world.preview.sky }" aria-hidden="true">
                            <span class="mini-stage-gloss"></span>
                            <span class="mini-stage-sun"></span>
                            <span v-if="!world.isDark" class="mini-stage-cloud"></span>
                            <template v-else>
                                <span class="mini-stage-twinkle mini-stage-twinkle--1"></span>
                                <span class="mini-stage-twinkle mini-stage-twinkle--2"></span>
                            </template>
                            <svg class="mini-stage-hills" viewBox="0 0 200 64" preserveAspectRatio="none">
                                <path d="M0,38 C45,14 95,46 145,22 C170,12 186,24 200,18 L200,64 L0,64 Z" :fill="world.preview.hillBack" opacity="0.84" />
                                <path d="M0,48 C50,30 100,56 150,34 C174,24 190,38 200,34 L200,64 L0,64 Z" :fill="world.preview.hillFront" />
                            </svg>

                            <!-- Volumetric 3D clay sprout mascot -->
                            <svg class="mini-stage-mascot" viewBox="0 0 40 46">
                                <defs>
                                    <radialGradient :id="`pxSproutBody-${world.id}`" cx="34%" cy="28%" r="78%">
                                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.92" />
                                        <stop offset="32%" :stop-color="world.preview.primary" />
                                        <stop offset="100%" :stop-color="world.preview.primary" />
                                    </radialGradient>
                                </defs>
                                <ellipse cx="20" cy="43.5" rx="12" ry="2.4" fill="#140A2E" opacity="0.28" />
                                <path d="M20 9 Q18.5 3.5 23 2" :stroke="world.preview.hillFront" stroke-width="2" stroke-linecap="round" fill="none" />
                                <circle cx="24" cy="2.6" r="2.4" :fill="world.preview.accent" />
                                <path
                                    d="M5 31 C5 16 11 8.5 20 8.5 C29 8.5 35 16 35 31 C35 39 28.5 42 20 42 C11.5 42 5 39 5 31 Z"
                                    :fill="`url(#pxSproutBody-${world.id})`"
                                />
                                <path d="M8 34 C11 39 29 39 32 34 C31 39.5 26 41.5 20 41.5 C14 41.5 9 39.5 8 34 Z" fill="#140A2E" opacity="0.14" />
                                <ellipse cx="14.6" cy="24" rx="3.7" ry="4.4" fill="#FFFFFF" />
                                <ellipse cx="25.4" cy="24" rx="3.7" ry="4.4" fill="#FFFFFF" />
                                <circle cx="15.3" cy="25" r="2.2" fill="#18143B" />
                                <circle cx="26.1" cy="25" r="2.2" fill="#18143B" />
                                <circle cx="16.1" cy="24" r="0.85" fill="#FFFFFF" />
                                <circle cx="26.9" cy="24" r="0.85" fill="#FFFFFF" />
                                <ellipse cx="10.8" cy="30.5" rx="2.3" ry="1.3" fill="#FF7A9A" opacity="0.6" />
                                <ellipse cx="29.2" cy="30.5" rx="2.3" ry="1.3" fill="#FF7A9A" opacity="0.6" />
                                <path d="M17 31.5 Q20 34.5 23 31.5" stroke="#18143B" stroke-width="1.7" stroke-linecap="round" fill="none" />
                            </svg>

                            <div class="mini-stage-card" :style="{ background: world.preview.card }">
                                <span class="mini-marble" :style="{ background: world.preview.primary }"></span>
                                <span class="mini-marble" :style="{ background: world.preview.secondary }"></span>
                                <span class="mini-marble" :style="{ background: world.preview.accent }"></span>
                            </div>
                            <span class="mini-mode-tag">{{ world.mode }}</span>
                        </div>

                        <div class="world-card-meta">
                            <span class="world-card-name">{{ world.name }}</span>
                            <span class="world-card-inspiration">{{ world.inspiration }}</span>
                            <span class="world-card-desc">{{ world.tagline }}</span>
                        </div>

                        <span v-if="activeWorld === world.id" class="companion-active-badge world-active-badge">
                            ACTIVE
                        </span>
                    </button>
                </div>
            </div>

            <!-- 3. AUDIO & VIBE STUDIO CARD -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-badge-orb orb-audio" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6" />
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" fill="currentColor" fill-opacity="0.25" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="card-title">Sound & Music Studio</h2>
                            <p class="card-subtitle">Control background melodies and button foley</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="clay-mute-toggle-btn"
                        :class="{ 'is-muted': soundState.isMuted }"
                        @click="toggleMute"
                    >
                        <span>{{ soundState.isMuted ? '🔇 Muted' : '🔊 Audio On' }}</span>
                    </button>
                </div>

                <div class="settings-grid">
                    <!-- Background Music (BGM) -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-icon-pill pill-bgm" aria-hidden="true">🎵</span>
                                <div>
                                    <span class="item-name">Background Music</span>
                                    <span class="item-hint">Sunny marimba & kalimba loop</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                role="switch"
                                class="clay-toggle-switch"
                                :class="{ 'is-on': soundState.isBgmPlaying && !soundState.isMuted }"
                                :aria-checked="soundState.isBgmPlaying && !soundState.isMuted"
                                :disabled="soundState.isMuted"
                                aria-label="Toggle background music"
                                @click="toggleBgm"
                            >
                                <span class="knob"></span>
                            </button>
                        </div>

                        <div class="slider-row">
                            <span class="slider-icon" aria-hidden="true">🔈</span>
                            <input
                                type="range"
                                min="0"
                                max="0.8"
                                step="0.02"
                                :value="soundState.bgmVolume"
                                :disabled="soundState.isMuted || !soundState.isBgmPlaying"
                                class="clay-slider"
                                aria-label="Background music volume"
                                @input="handleBgmSlider"
                            />
                            <span class="slider-icon" aria-hidden="true">🔊</span>
                            <span class="pct-badge">{{ Math.round((soundState.bgmVolume / 0.8) * 100) }}%</span>
                        </div>
                    </div>

                    <!-- Sound Effects (SFX) -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-icon-pill pill-sfx" aria-hidden="true">✨</span>
                                <div>
                                    <span class="item-name">Sound Effects</span>
                                    <span class="item-hint">Button pops, sparkles & fanfare</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="test-sfx-chip"
                                :disabled="soundState.isMuted"
                                @click="playSparkle"
                            >
                                <span>✨ Test</span>
                            </button>
                        </div>

                        <div class="slider-row">
                            <span class="slider-icon" aria-hidden="true">🔈</span>
                            <input
                                type="range"
                                min="0"
                                max="1"
                                step="0.05"
                                :value="soundState.sfxVolume"
                                :disabled="soundState.isMuted"
                                class="clay-slider"
                                aria-label="Sound effects volume"
                                @input="handleSfxSlider"
                            />
                            <span class="slider-icon" aria-hidden="true">🔊</span>
                            <span class="pct-badge">{{ Math.round(soundState.sfxVolume * 100) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. COMPANION AVATAR SELECTOR CARD (3D PIXAR VECTOR CHARACTERS) -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-badge-orb orb-companion" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.8l-6.2 4.5 2.4-7.4L2 9.4h7.6z" fill="currentColor" fill-opacity="0.25" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="card-title">Companion Avatar</h2>
                            <p class="card-subtitle">Choose your loyal 3D reading mascot</p>
                        </div>
                    </div>
                </div>

                <div class="companions-grid" role="radiogroup" aria-label="Choose your companion avatar">
                    <button
                        v-for="c in companions"
                        :key="c.id"
                        type="button"
                        role="radio"
                        class="companion-option-card"
                        :class="{ 'is-selected': selectedCompanion === c.id }"
                        :aria-checked="selectedCompanion === c.id"
                        @click="selectCompanion(c.id)"
                    >
                        <div class="companion-avatar-disc" :style="{ background: c.bg, borderColor: c.border }">
                            <!-- 3D Cartoon Vector Companions -->
                            <svg v-if="c.id === 'fox'" viewBox="0 0 48 48" class="companion-character-svg" aria-hidden="true">
                                <path d="M10 10 L18 22 L8 26 Z" fill="#EA580C" />
                                <path d="M38 10 L30 22 L40 26 Z" fill="#EA580C" />
                                <path d="M12 13 L17 21 L11 23 Z" fill="#FDE68A" />
                                <path d="M36 13 L31 21 L37 23 Z" fill="#FDE68A" />
                                <ellipse cx="24" cy="27" rx="14" ry="12" fill="#F97316" />
                                <path d="M10 28 Q17 39 24 39 Q31 39 38 28 Q32 25 24 30 Q16 25 10 28 Z" fill="#FFFBEB" />
                                <circle cx="18.5" cy="25.5" r="2.4" fill="#1E293B" />
                                <circle cx="29.5" cy="25.5" r="2.4" fill="#1E293B" />
                                <circle cx="19.3" cy="24.7" r="0.9" fill="#FFFFFF" />
                                <circle cx="30.3" cy="24.7" r="0.9" fill="#FFFFFF" />
                                <ellipse cx="24" cy="30" rx="2.8" ry="2" fill="#1E293B" />
                            </svg>

                            <svg v-else-if="c.id === 'astro'" viewBox="0 0 48 48" class="companion-character-svg" aria-hidden="true">
                                <circle cx="24" cy="26" r="14" fill="#FFFFFF" stroke="#60A5FA" stroke-width="2.5" />
                                <rect x="13" y="19" width="22" height="13" rx="6.5" fill="#1E3A8A" />
                                <path d="M16 22 Q20 20 25 22" stroke="#93C5FD" stroke-width="2" stroke-linecap="round" fill="none" />
                                <circle cx="19" cy="26" r="2.1" fill="#38BDF8" />
                                <circle cx="29" cy="26" r="2.1" fill="#38BDF8" />
                                <circle cx="24" cy="9" r="3" fill="#F59E0B" />
                                <line x1="24" y1="12" x2="24" y2="14" stroke="#60A5FA" stroke-width="2.5" />
                            </svg>

                            <svg v-else-if="c.id === 'knight'" viewBox="0 0 48 48" class="companion-character-svg" aria-hidden="true">
                                <circle cx="13" cy="16" r="5" fill="#059669" />
                                <circle cx="35" cy="16" r="5" fill="#059669" />
                                <circle cx="13" cy="16" r="2.5" fill="#A7F3D0" />
                                <circle cx="35" cy="16" r="2.5" fill="#A7F3D0" />
                                <circle cx="24" cy="27" r="13" fill="#10B981" />
                                <ellipse cx="24" cy="30" rx="6" ry="4.5" fill="#D1FAE5" />
                                <circle cx="18.5" cy="24.5" r="2.3" fill="#064E3B" />
                                <circle cx="29.5" cy="24.5" r="2.3" fill="#064E3B" />
                                <ellipse cx="24" cy="28.8" rx="2.5" ry="1.8" fill="#064E3B" />
                            </svg>

                            <svg v-else viewBox="0 0 48 48" class="companion-character-svg" aria-hidden="true">
                                <ellipse cx="24" cy="27" rx="13" ry="12" fill="#8B5CF6" />
                                <circle cx="18.5" cy="25" r="5" fill="#FEF3C7" />
                                <circle cx="29.5" cy="25" r="5" fill="#FEF3C7" />
                                <circle cx="18.5" cy="25" r="2.4" fill="#1E1B4B" />
                                <circle cx="29.5" cy="25" r="2.4" fill="#1E1B4B" />
                                <polygon points="24,27 21.5,31 26.5,31" fill="#F59E0B" />
                                <path d="M13 16 Q18 19 21 20" stroke="#DDD6FE" stroke-width="2.5" stroke-linecap="round" fill="none" />
                                <path d="M35 16 Q30 19 27 20" stroke="#DDD6FE" stroke-width="2.5" stroke-linecap="round" fill="none" />
                            </svg>
                        </div>
                        <span class="companion-option-name">{{ c.name }}</span>
                        <span class="companion-option-desc">{{ c.desc }}</span>
                        <span v-if="selectedCompanion === c.id" class="companion-active-badge">ACTIVE</span>
                    </button>
                </div>
            </div>

            <!-- 5. READING & NARRATION PREFERENCES -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-badge-orb orb-reading" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" fill="currentColor" fill-opacity="0.2" />
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" fill="currentColor" fill-opacity="0.2" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="card-title">Story & Reading Experience</h2>
                            <p class="card-subtitle">Configure narration pace and reading comfort</p>
                        </div>
                    </div>
                </div>

                <div class="settings-grid">
                    <!-- Narration Speed -->
                    <div class="setting-item-box">
                        <span class="item-name">Narration Speed</span>
                        <span class="item-hint">Speed for "Read Page Aloud" voice audio</span>

                        <div class="speed-buttons-row" role="radiogroup" aria-label="Narration speed">
                            <button
                                v-for="spd in speedOptions"
                                :key="spd.value"
                                type="button"
                                role="radio"
                                class="speed-pill-btn"
                                :class="{ 'is-selected': selectedSpeed === spd.value }"
                                :aria-checked="selectedSpeed === spd.value"
                                @click="handleSpeedChange(spd.value)"
                            >
                                <span class="speed-rate">{{ spd.rate }}</span>
                                <span class="speed-tag">{{ spd.tag }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Text Size Toggle -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-icon-pill pill-font" aria-hidden="true">🔤</span>
                                <div>
                                    <span class="item-name">Large Reading Font</span>
                                    <span class="item-hint">Larger, comfortable text on story pages</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                role="switch"
                                class="clay-toggle-switch"
                                :class="{ 'is-on': isLargeText }"
                                :aria-checked="isLargeText"
                                aria-label="Toggle large reading font"
                                @click="toggleLargeText"
                            >
                                <span class="knob"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Smooth Mode (Lite performance) Toggle -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-icon-pill pill-font" aria-hidden="true">⚡</span>
                                <div>
                                    <span class="item-name">Smooth Mode</span>
                                    <span class="item-hint">Fewer moving effects so slower phones and tablets run faster</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                role="switch"
                                class="clay-toggle-switch"
                                :class="{ 'is-on': isSmoothMode }"
                                :aria-checked="isSmoothMode"
                                aria-label="Toggle smooth mode"
                                @click="toggleSmoothMode"
                            >
                                <span class="knob"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. BOTTOM ACCOUNT & ACTIONS BAR (SAVE PREFERENCES + LOGOUT AT VERY BOTTOM) -->
            <div class="clay-card settings-bottom-footer">
                <div class="footer-save-row">
                    <div class="footer-save-info">
                        <span class="footer-save-title">Sync Preferences</span>
                        <span class="footer-save-sub">Keep your world, audio & reading choices across devices</span>
                    </div>

                    <button
                        ref="saveBtnRef"
                        type="button"
                        class="pixar-save-all-btn"
                        :disabled="isSavingWorld"
                        :aria-busy="isSavingWorld"
                        @click="saveAllPreferences"
                    >
                        <span class="pixar-btn__label">
                            {{ isSavingWorld ? 'Saving Preferences...' : '✓ Save All Preferences' }}
                        </span>
                    </button>
                </div>

                <div class="footer-divider" aria-hidden="true"></div>

                <!-- LOGOUT SECTION RIGHT AT THE BOTTOM PART OF SETTINGS -->
                <div class="footer-logout-row">
                    <div class="footer-account-meta">
                        <span class="account-avatar-dot" aria-hidden="true">👤</span>
                        <div class="account-text-col">
                            <span class="account-name">{{ user?.name || 'Student Explorer' }}</span>
                            <span class="account-email">{{ user?.email || 'Student Portal Session' }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="pixar-logout-btn"
                        :disabled="isLoggingOut"
                        :aria-busy="isLoggingOut"
                        aria-label="Log out of student portal"
                        @click="handleLogout"
                    >
                        <svg viewBox="0 0 24 24" class="logout-btn-svg" aria-hidden="true">
                            <path d="M10 4H6.5A2.5 2.5 0 0 0 4 6.5v11A2.5 2.5 0 0 0 6.5 20H10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                            <path d="M16 8l4 4-4 4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <line x1="10" y1="12" x2="19.5" y2="12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                        </svg>
                        <span>{{ isLoggingOut ? 'Logging Out...' : 'Log Out' }}</span>
                    </button>
                </div>
            </div>
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   PIXAR TACTILE STUDIO SETTINGS — MOBILE-FIRST SCALED-DOWN RESPONSIVE UI
   ========================================================================== */
.clay-settings-page {
    --spring-bounce: var(--px-ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));
    --font-display: var(--px-font-display, 'Fredoka', -apple-system, sans-serif);
    --font-ui: var(--px-font-body, 'Nunito', -apple-system, sans-serif);

    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    padding-bottom: 1.5rem;
    max-width: 880px;
    margin: 0 auto;
}

/* 1. HERO BANNER (MOBILE-FIRST SCALED-DOWN & COMPACT) */
.clay-settings-hero {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    background: var(--px-hero-banner-grad);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 5px 0 var(--px-hero-banner-ledge),
        0 12px 24px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 5px rgba(255, 255, 255, 0.45);
    padding: clamp(0.82rem, 2.2vw, 1.12rem) clamp(0.9rem, 2.6vw, 1.32rem);
    color: #FFFFFF;
}

.hero-gloss-shine {
    position: absolute;
    top: 4px;
    left: 16px;
    right: 16px;
    height: 10px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.5) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hero-top-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-bottom: 0.4rem;
}

.settings-pill {
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: var(--px-ink);
    background: var(--px-surface-elevated);
    padding: 0.16rem 0.54rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border);
    box-shadow: 0 2px 0 rgba(var(--px-shadow-rgb), 0.2);
}

.settings-status-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.32rem;
    font-family: var(--font-ui);
    font-size: 0.66rem;
    font-weight: 800;
    color: #FFFFFF;
    background: rgba(var(--px-shadow-rgb), 0.34);
    border: 1px solid rgba(255, 255, 255, 0.32);
    padding: 0.15rem 0.52rem;
    border-radius: 999px;
}

.status-sync-dot {
    width: 6.5px;
    height: 6.5px;
    border-radius: 50%;
    background: #34D399;
    box-shadow: 0 0 6px #34D399;
}

.settings-status-tag.is-syncing .status-sync-dot {
    background: #FBBF24;
    box-shadow: 0 0 6px var(--px-tint-warn-line);
}

.hero-content-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
}

.hero-text-col {
    min-width: 0;
    flex: 1;
}

.hero-main-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.04rem, 2.5vw, 1.4rem);
    font-weight: 900;
    color: #FFFFFF;
    text-shadow: 0 2px 0 rgba(var(--px-shadow-rgb), 0.28);
    line-height: 1.16;
}

.hero-desc {
    margin: 0.26rem 0 0;
    font-family: var(--font-ui);
    font-size: clamp(0.72rem, 1.6vw, 0.84rem);
    font-weight: 700;
    color: rgba(255, 255, 255, 0.92);
    max-width: 520px;
    line-height: 1.36;
}

.hero-icon-pod {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.clay-settings-cog-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 5px 9px rgba(var(--px-shadow-rgb), 0.3));
}

/* TOAST */
.clay-saved-toast {
    position: fixed;
    top: max(0.9rem, env(safe-area-inset-top, 0.9rem));
    left: 50%;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(180deg, var(--px-success-lit) 0%, var(--px-success) 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    padding: 0.48rem 1rem;
    border-radius: 9999px;
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 4px 0 var(--px-success-pedestal),
        0 10px 22px var(--px-success-glow);
    z-index: 1000;
    max-width: calc(100vw - 1.5rem);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.toast-pop-enter-active {
    transition: opacity 0.25s ease, transform 0.25s var(--spring-bounce);
}
.toast-pop-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-pop-enter-from {
    opacity: 0;
    transform: translate(-50%, -15px);
}
.toast-pop-leave-to {
    opacity: 0;
    transform: translate(-50%, -10px);
}

/* 2. CARDS (MOBILE-FIRST COMPACT PROPORTIONS) */
.clay-card {
    position: relative;
    border-radius: 18px;
    background: linear-gradient(176deg, var(--px-surface-card) 0%, var(--px-surface-card-bottom) 100%);
    border: 2px solid var(--px-card-border);
    box-shadow: var(--px-card-shadow);
    padding: clamp(0.8rem, 2.2vw, 1.08rem) clamp(0.84rem, 2.4vw, 1.18rem);
    transition: background 320ms var(--px-ease-settle), border-color 280ms ease;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.55rem;
    flex-wrap: wrap;
    padding-bottom: 0.58rem;
    border-bottom: 1.5px dashed rgba(var(--px-shadow-rgb), 0.15);
    margin-bottom: 0.72rem;
}

.card-title-group {
    display: flex;
    align-items: center;
    gap: 0.56rem;
    min-width: 0;
}

.card-badge-orb {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    color: #FFFFFF;
    border: 1.5px solid var(--px-card-border);
    box-shadow:
        0 3px 0 rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 3px rgba(255, 255, 255, 0.6);
}

.orb-palette { background: linear-gradient(145deg, #FF7866 0%, #DE2B1E 100%); }
.orb-audio { background: linear-gradient(145deg, #52ADFF 0%, #146CE8 100%); }
.orb-companion { background: linear-gradient(145deg, #3AE8D2 0%, #078578 100%); }
.orb-reading { background: linear-gradient(145deg, #FFBE1A 0%, #D97706 100%); color: #261800; }

.card-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(0.94rem, 2vw, 1.06rem);
    font-weight: 900;
    color: var(--px-ink);
    line-height: 1.2;
}

.card-subtitle {
    margin: 0.08rem 0 0;
    font-family: var(--font-ui);
    font-size: clamp(0.66rem, 1.5vw, 0.74rem);
    font-weight: 700;
    color: var(--px-ink-muted);
    line-height: 1.3;
}

.world-current-chip {
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--px-accent-ink);
    background: linear-gradient(180deg, var(--px-accent-lit) 0%, var(--px-accent) 100%);
    padding: 0.2rem 0.58rem;
    border-radius: 999px;
    box-shadow:
        0 2px 0 var(--px-accent-pedestal),
        inset 0 2px 2px rgba(255, 255, 255, 0.6);
}

/* ==========================================================================
   PIXAR COLOR WORLD SELECTOR GRID (MOBILE-FIRST 2-COL, 4-COL DESKTOP)
   ========================================================================== */
.worlds-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(130px, 100%), 1fr));
    gap: 0.52rem;
}

.world-theme-card {
    position: relative;
    display: flex;
    flex-direction: column;
    text-align: left;
    padding: 0.44rem;
    border-radius: 15px;
    border: 2px solid var(--px-card-border);
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    cursor: pointer;
    color: var(--px-ink);
    min-width: 0;
    transition: transform 220ms var(--spring-bounce), box-shadow 220ms ease, border-color 200ms ease;
}

@media (hover: hover) {
    .world-theme-card:hover {
        transform: translate3d(0, -3px, 0) scale(1.015);
        box-shadow:
            0 8px 18px -6px rgba(var(--px-shadow-rgb), 0.25),
            var(--px-well-shadow);
    }
}

.world-theme-card:active {
    transform: translate3d(0, 2px, 0) scale(0.98);
}

.world-theme-card:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.world-theme-card.is-selected {
    transform: translate3d(0, -2px, 0);
    border-color: var(--px-focus-stroke);
    background: var(--px-surface-elevated);
    box-shadow:
        0 0 0 2.5px var(--px-focus-ring),
        0 8px 18px -6px rgba(var(--px-shadow-rgb), 0.24);
}

.world-mini-stage {
    position: relative;
    width: 100%;
    height: 56px;
    border-radius: 11px;
    overflow: hidden;
    border: 1.5px solid rgba(255, 255, 255, 0.65);
    box-shadow: inset 0 2px 5px rgba(var(--px-shadow-rgb), 0.16);
    margin-bottom: 0.38rem;
}

.mini-stage-gloss {
    position: absolute;
    top: 2px;
    left: 8px;
    right: 8px;
    height: 6px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
    z-index: 2;
}

.mini-stage-sun {
    position: absolute;
    top: 7px;
    left: 9px;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #FFFFFF 0%, #FFE066 65%, #FFA929 100%);
    box-shadow: 0 0 8px rgba(255, 224, 102, 0.8);
}

.world-theme-card.is-dark-world .mini-stage-sun {
    background: radial-gradient(circle at 35% 35%, #FFFFFF 0%, #EDE7FF 55%, #B9ACF2 100%);
    box-shadow: 0 0 10px rgba(214, 204, 255, 0.85);
}

.mini-stage-cloud {
    position: absolute;
    top: 10px;
    left: 36%;
    width: 22px;
    height: 7.5px;
    border-radius: 999px;
    background: #FFFFFF;
    opacity: 0.92;
    box-shadow: inset 0 -1.5px 0 rgba(160, 196, 255, 0.45);
    animation: mini-cloud-drift 7s ease-in-out infinite alternate;
    animation-play-state: paused;
}

.mini-stage-cloud::before {
    content: '';
    position: absolute;
    left: 5px;
    bottom: 3.5px;
    width: 10px;
    height: 8.5px;
    border-radius: 50%;
    background: inherit;
}

.mini-stage-twinkle {
    position: absolute;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: radial-gradient(circle, #FFFFFF 0%, #FFE9A8 60%, transparent 100%);
    box-shadow: 0 0 5px rgba(255, 233, 168, 0.9);
    animation: mini-twinkle 2.4s ease-in-out infinite;
    animation-play-state: paused;
}

.mini-stage-twinkle--1 { top: 9px; left: 42%; }
.mini-stage-twinkle--2 { top: 18px; left: 62%; animation-delay: -1.2s; }

.mini-stage-mascot {
    position: absolute;
    left: 8%;
    bottom: 3px;
    width: 20px;
    height: 23px;
    transform-origin: 50% 100%;
    filter: drop-shadow(0 2px 2px rgba(20, 10, 46, 0.25));
}

.world-theme-card.is-selected .mini-stage-mascot {
    animation: mini-mascot-pop 0.6s var(--spring-bounce) both, mini-mascot-bob 3.2s ease-in-out 0.6s infinite;
}

.world-theme-card.is-selected .mini-stage-cloud,
.world-theme-card.is-selected .mini-stage-twinkle {
    animation-play-state: running;
}

@media (hover: hover) {
    .world-theme-card:hover .mini-stage-mascot {
        animation: mini-mascot-bob 1.6s ease-in-out infinite;
    }

    .world-theme-card:hover .mini-stage-cloud,
    .world-theme-card:hover .mini-stage-twinkle {
        animation-play-state: running;
    }
}

@keyframes mini-mascot-pop {
    0% { transform: scale(0.6, 0.6); }
    45% { transform: scale(1.15, 0.85); }
    70% { transform: scale(0.94, 1.08); }
    100% { transform: scale(1, 1); }
}

@keyframes mini-mascot-bob {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-3px) rotate(-4deg); }
}

@keyframes mini-cloud-drift {
    from { transform: translateX(-5px); }
    to { transform: translateX(8px); }
}

@keyframes mini-twinkle {
    0%, 100% { opacity: 0.35; transform: scale(0.7); }
    50% { opacity: 1; transform: scale(1.15); }
}

.mini-stage-hills {
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    width: 100%;
    height: 34px;
}

.mini-stage-card {
    position: absolute;
    bottom: 6px;
    left: 60%;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 3.5px;
    padding: 3.5px 5.5px;
    border-radius: 7px;
    border: 1.5px solid rgba(255, 255, 255, 0.78);
    box-shadow: 0 3px 8px rgba(var(--px-shadow-rgb), 0.26);
}

.mini-marble {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.88);
    box-shadow: inset 0 1.5px 1.5px rgba(255, 255, 255, 0.6);
}

.mini-mode-tag {
    position: absolute;
    top: 4px;
    right: 4px;
    padding: 0.06rem 0.32rem;
    border-radius: 999px;
    font-family: var(--font-display);
    font-size: 0.5rem;
    font-weight: 800;
    background: rgba(255, 255, 255, 0.92);
    color: #18143B;
}

.world-card-meta {
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
    padding: 0 0.12rem 0.08rem;
    min-width: 0;
}

.world-card-name {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 900;
    color: var(--px-ink);
    line-height: 1.2;
}

.world-card-inspiration {
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 700;
    color: var(--px-link-ink);
    line-height: 1.2;
}

.world-card-desc {
    margin-top: 0.04rem;
    font-family: var(--font-ui);
    font-size: 0.62rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    line-height: 1.28;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.world-active-badge {
    top: -7px;
    right: 8px;
}

/* ==========================================================================
   MUTE / TEST BUTTONS & SLIDERS
   ========================================================================== */
.clay-mute-toggle-btn {
    padding: 0.36rem 0.76rem;
    min-height: 36px;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    border: 1.5px solid var(--px-card-border);
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    color: var(--px-secondary-ink);
    cursor: pointer;
    box-shadow: 0 3px 0 var(--px-secondary-pedestal);
    transition: transform 0.16s var(--spring-bounce), box-shadow 0.16s ease;
}

@media (hover: hover) {
    .clay-mute-toggle-btn:hover {
        transform: translateY(-2px);
    }
}

.clay-mute-toggle-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 var(--px-secondary-pedestal);
}

.clay-mute-toggle-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.clay-mute-toggle-btn.is-muted {
    background: linear-gradient(180deg, var(--px-danger-lit) 0%, var(--px-danger) 100%);
    border-color: var(--px-card-border);
    color: #FFFFFF;
    box-shadow: 0 3px 0 var(--px-danger-pedestal);
}

/* Settings Grid (Mobile-First 1-Col -> 2-Col Tablet/Desktop) */
.settings-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.65rem;
}

.setting-item-box {
    background: var(--px-surface-well);
    border: 1.5px solid var(--px-card-border);
    box-shadow: var(--px-well-shadow);
    border-radius: 15px;
    padding: 0.72rem 0.84rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.45rem;
    min-width: 0;
}

.setting-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.item-info {
    display: flex;
    align-items: center;
    gap: 0.48rem;
    min-width: 0;
}

.item-icon-pill {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    background: var(--px-surface-elevated);
    border: 1.5px solid var(--px-card-border);
    box-shadow: 0 2px 0 rgba(var(--px-shadow-rgb), 0.14);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.92rem;
    flex-shrink: 0;
}

.item-name {
    display: block;
    font-family: var(--font-display);
    font-size: 0.84rem;
    font-weight: 800;
    color: var(--px-ink);
    line-height: 1.2;
}

.item-hint {
    display: block;
    font-family: var(--font-ui);
    font-size: 0.66rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    line-height: 1.25;
}

/* Bouncy Pixar Clay Toggle Switch */
.clay-toggle-switch {
    position: relative;
    width: 46px;
    height: 26px;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border);
    background: rgba(var(--px-shadow-rgb), 0.26);
    box-shadow: inset 0 2px 5px rgba(var(--px-shadow-rgb), 0.24), 0 2px 0 rgba(255, 255, 255, 0.35);
    cursor: pointer;
    transition: background 0.25s ease;
    padding: 0;
    flex-shrink: 0;
}

.clay-toggle-switch.is-on {
    background: linear-gradient(135deg, var(--px-success-lit) 0%, var(--px-success) 100%);
}

.clay-toggle-switch:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.clay-toggle-switch .knob {
    position: absolute;
    top: 2.5px;
    left: 2.5px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, #EEF2FF 100%);
    box-shadow: 0 2px 5px rgba(var(--px-shadow-rgb), 0.28);
    transition: transform 0.26s var(--spring-bounce);
}

.clay-toggle-switch.is-on .knob {
    transform: translateX(20px);
}

.clay-toggle-switch:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.test-sfx-chip {
    padding: 0.26rem 0.6rem;
    min-height: 32px;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-accent-ink);
    background: linear-gradient(180deg, var(--px-accent-lit) 0%, var(--px-accent) 100%);
    border: 1.5px solid var(--px-card-border);
    box-shadow: 0 2px 0 var(--px-accent-pedestal);
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.15s ease;
}

@media (hover: hover) {
    .test-sfx-chip:hover:not(:disabled) {
        transform: translateY(-2px);
    }
}

.test-sfx-chip:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

/* Sliders */
.slider-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 28px;
}

.slider-icon {
    font-size: 0.78rem;
    opacity: 0.75;
}

.clay-slider {
    flex: 1;
    min-width: 0;
    -webkit-appearance: none;
    appearance: none;
    height: 8px;
    border-radius: 9999px;
    background: rgba(var(--px-shadow-rgb), 0.16);
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.2);
}

.clay-slider:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

.clay-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, var(--px-accent-lit) 28%, var(--px-accent) 100%);
    border: 2px solid var(--px-card-border);
    box-shadow: 0 2px 5px rgba(var(--px-shadow-rgb), 0.3);
    cursor: pointer;
    transition: transform 0.15s var(--spring-bounce);
}

@media (hover: hover) {
    .clay-slider::-webkit-slider-thumb:hover {
        transform: scale(1.14);
    }
}

.pct-badge {
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink);
    min-width: 34px;
    text-align: right;
}

/* ==========================================================================
   COMPANIONS GRID (3D PIXAR VECTOR AVATARS, MOBILE-FIRST 2-COL -> 4-COL)
   ========================================================================== */
.companions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(130px, 100%), 1fr));
    gap: 0.52rem;
}

.companion-option-card {
    position: relative;
    background: var(--px-surface-well);
    border: 2px solid var(--px-card-border);
    box-shadow: var(--px-well-shadow);
    border-radius: 15px;
    padding: 0.62rem 0.48rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    min-width: 0;
    transition: transform 0.2s var(--spring-bounce), border-color 0.18s ease, background 0.18s ease, box-shadow 0.2s ease;
}

@media (hover: hover) {
    .companion-option-card:hover {
        transform: translateY(-2px);
    }
}

.companion-option-card:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.companion-option-card.is-selected {
    background: var(--px-surface-elevated);
    border-color: var(--px-success);
    box-shadow:
        0 3px 0 var(--px-success),
        0 8px 16px -4px var(--px-success-glow);
}

.companion-avatar-disc {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 2.5px solid;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 4px 10px rgba(var(--px-shadow-rgb), 0.18),
        inset 0 2px 4px rgba(255, 255, 255, 0.8);
    margin-bottom: 0.34rem;
    overflow: hidden;
}

.companion-character-svg {
    width: 32px;
    height: 32px;
    display: block;
    filter: drop-shadow(0 2px 2px rgba(15, 23, 42, 0.16));
}

.companion-option-name {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-ink);
    line-height: 1.2;
}

.companion-option-desc {
    margin-top: 0.1rem;
    font-family: var(--font-ui);
    font-size: 0.62rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    line-height: 1.28;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.companion-active-badge {
    position: absolute;
    top: -7px;
    right: 8px;
    background: linear-gradient(180deg, var(--px-success-lit) 0%, var(--px-success) 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.54rem;
    font-weight: 900;
    padding: 0.08rem 0.42rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border);
    box-shadow: 0 2px 0 var(--px-success-pedestal);
}

/* ==========================================================================
   SPEED PILLS (FLEX COMPACT ROW — ZERO OVERFLOW AT 320px)
   ========================================================================== */
.speed-buttons-row {
    display: flex;
    align-items: stretch;
    gap: 0.38rem;
    margin-top: 0.25rem;
}

.speed-pill-btn {
    flex: 1 1 0%;
    min-width: 0;
    padding: 0.36rem 0.32rem;
    min-height: 42px;
    border-radius: 14px;
    font-family: var(--font-display);
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-elevated);
    color: var(--px-ink-muted);
    cursor: pointer;
    box-shadow: 0 2px 0 rgba(var(--px-shadow-rgb), 0.15);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.04rem;
    transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
}

@media (hover: hover) {
    .speed-pill-btn:hover {
        transform: translateY(-2px);
        color: var(--px-ink);
    }
}

.speed-pill-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.speed-pill-btn.is-selected {
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    color: var(--px-secondary-ink);
    border-color: var(--px-card-border);
    box-shadow: 0 3px 0 var(--px-secondary-pedestal);
}

.speed-rate {
    font-size: 0.76rem;
    font-weight: 900;
    line-height: 1.1;
}

.speed-tag {
    font-family: var(--font-ui);
    font-size: 0.6rem;
    font-weight: 800;
    opacity: 0.9;
    line-height: 1.1;
}

/* ==========================================================================
   6. BOTTOM FOOTER: SAVE PREFERENCES + LOGOUT BUTTON AT THE BOTTOM
   ========================================================================== */
.settings-bottom-footer {
    display: flex;
    flex-direction: column;
    gap: 0.78rem;
}

.footer-save-row,
.footer-logout-row {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.58rem;
}

.footer-save-info {
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
    min-width: 0;
}

.footer-save-title {
    font-family: var(--font-display);
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--px-ink);
}

.footer-save-sub {
    font-family: var(--font-ui);
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.footer-divider {
    height: 1.5px;
    background: repeating-linear-gradient(
        90deg,
        rgba(var(--px-shadow-rgb), 0.16) 0,
        rgba(var(--px-shadow-rgb), 0.16) 6px,
        transparent 6px,
        transparent 12px
    );
}

.footer-account-meta {
    display: flex;
    align-items: center;
    gap: 0.52rem;
    min-width: 0;
}

.account-avatar-dot {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    background: var(--px-surface-well);
    border: 1.5px solid var(--px-card-border);
    box-shadow: var(--px-well-shadow);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.92rem;
    flex-shrink: 0;
}

.account-text-col {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.account-name {
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--px-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.account-email {
    font-family: var(--font-ui);
    font-size: 0.66rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Zero-Layout-Shift Pixar Toy-Vinyl Save Button */
.pixar-save-all-btn {
    --_depth: 5px;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 44px;
    padding: 0.62rem 1.3rem;
    margin-bottom: var(--_depth);
    border-radius: 9999px;
    border: 0;
    font-family: var(--font-display);
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: var(--px-explore-ink);
    background: linear-gradient(180deg, var(--px-explore-lit) 0%, var(--px-explore) 48%, var(--px-explore-belly) 100%);
    box-shadow:
        0 var(--_depth) 0 var(--px-explore-pedestal),
        0 calc(var(--_depth) + 8px) 18px -6px var(--px-explore-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.65),
        inset 0 -3px 6px rgba(var(--px-shadow-rgb), 0.16);
    cursor: pointer;
    transition: transform 220ms var(--spring-bounce), box-shadow 180ms ease;
}

/* Zero-Layout-Shift Pixar Toy-Vinyl Logout Button */
.pixar-logout-btn {
    --_depth: 5px;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.42rem;
    width: 100%;
    min-height: 44px;
    padding: 0.6rem 1.25rem;
    margin-bottom: var(--_depth);
    border-radius: 9999px;
    border: 0;
    font-family: var(--font-display);
    font-size: 0.84rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: #FFFFFF;
    background: linear-gradient(180deg, var(--px-danger-lit) 0%, var(--px-danger) 55%, var(--px-danger-pedestal) 100%);
    box-shadow:
        0 var(--_depth) 0 var(--px-danger-pedestal),
        0 calc(var(--_depth) + 8px) 18px -6px var(--px-danger-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.55),
        inset 0 -3px 6px rgba(var(--px-shadow-rgb), 0.18);
    cursor: pointer;
    transition: transform 220ms var(--spring-bounce), box-shadow 180ms ease;
}

.pixar-save-all-btn::before,
.pixar-logout-btn::before {
    content: "";
    position: absolute;
    inset: 3px 10px auto 10px;
    height: 42%;
    border-radius: inherit;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0.04) 100%);
    pointer-events: none;
}

.logout-btn-svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

@media (hover: hover) {
    .pixar-save-all-btn:hover:not(:disabled) {
        transform: translate3d(0, -2px, 0) scale(1.02);
        box-shadow:
            0 calc(var(--_depth) + 2px) 0 var(--px-explore-pedestal),
            0 calc(var(--_depth) + 12px) 22px -5px var(--px-explore-glow),
            inset 0 2px 3px rgba(255, 255, 255, 0.72);
    }

    .pixar-logout-btn:hover:not(:disabled) {
        transform: translate3d(0, -2px, 0) scale(1.02);
        box-shadow:
            0 calc(var(--_depth) + 2px) 0 var(--px-danger-pedestal),
            0 calc(var(--_depth) + 12px) 22px -5px var(--px-danger-glow),
            inset 0 2px 3px rgba(255, 255, 255, 0.65);
    }
}

.pixar-save-all-btn:active:not(:disabled) {
    transform: translate3d(0, calc(var(--_depth) - 2px), 0) scaleX(1.035) scaleY(0.95);
    box-shadow:
        0 2px 0 var(--px-explore-pedestal),
        0 4px 10px -2px var(--px-explore-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.5);
    transition-duration: 75ms;
}

.pixar-logout-btn:active:not(:disabled) {
    transform: translate3d(0, calc(var(--_depth) - 2px), 0) scaleX(1.035) scaleY(0.95);
    box-shadow:
        0 2px 0 var(--px-danger-pedestal),
        0 4px 10px -2px var(--px-danger-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.45);
    transition-duration: 75ms;
}

.pixar-save-all-btn:focus-visible,
.pixar-logout-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.pixar-save-all-btn:disabled,
.pixar-logout-btn:disabled {
    opacity: 0.72;
    cursor: wait;
}

/* ==========================================================================
   MOBILE-FIRST PROGRESSIVE BREAKPOINTS (560px -> 720px -> 860px)
   ========================================================================== */
@media (min-width: 560px) {
    .clay-settings-page {
        gap: 0.85rem;
    }

    .clay-settings-hero,
    .clay-card {
        border-radius: 22px;
    }

    .hero-icon-pod {
        width: 48px;
        height: 48px;
    }

    .world-mini-stage {
        height: 64px;
        border-radius: 13px;
        margin-bottom: 0.46rem;
    }

    .world-theme-card {
        padding: 0.52rem;
        border-radius: 18px;
    }

    .world-card-name {
        font-size: 0.84rem;
    }

    .world-card-inspiration {
        font-size: 0.62rem;
    }

    .world-card-desc {
        font-size: 0.66rem;
    }

    .companion-option-card {
        padding: 0.72rem 0.55rem;
        border-radius: 18px;
    }

    .companion-avatar-disc {
        width: 46px;
        height: 46px;
    }

    .companion-character-svg {
        width: 36px;
        height: 36px;
    }

    .companion-option-name {
        font-size: 0.84rem;
    }

    .companion-option-desc {
        font-size: 0.66rem;
    }

    .footer-save-row,
    .footer-logout-row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
    }

    .pixar-save-all-btn,
    .pixar-logout-btn {
        width: auto;
    }
}

@media (min-width: 720px) {
    .settings-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .companions-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.65rem;
    }
}

@media (min-width: 860px) {
    .worlds-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.68rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
