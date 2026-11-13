<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '../../Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { usePixarTheme } from '@/Composables/usePixarTheme';

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
const savedToastText = ref('Settings & Theme Saved to Your Account!');
const saveBtnRef = ref(null);

let toastTimerId = null;

const speedOptions = [
    { value: '0.85', label: '0.85x (Gentle)' },
    { value: '1', label: '1.0x (Standard)' },
    { value: '1.15', label: '1.15x (Brisk)' },
];

const companions = [
    {
        id: 'fox',
        name: 'Ember Fox',
        emoji: '🦊',
        bg: 'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
        border: '#EA580C',
        desc: 'Quick-witted & energetic guide',
    },
    {
        id: 'astro',
        name: 'Astro Cat',
        emoji: '🐱',
        bg: 'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
        border: '#2563EB',
        desc: 'Curious star voyager',
    },
    {
        id: 'knight',
        name: 'Sprout Bear',
        emoji: '🐻',
        bg: 'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
        border: '#059669',
        desc: 'Patient & protective guardian',
    },
    {
        id: 'owl',
        name: 'Mystic Owl',
        emoji: '🦉',
        bg: 'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
        border: '#7C3AED',
        desc: 'Wise decoder of ancient lore',
    },
];

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
        burstCount: 14,
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
    }
    playSparkle();
};

const saveAllPreferences = (event) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('emodule_narration_speed', selectedSpeed.value);
        localStorage.setItem('emodule_large_text', String(isLargeText.value));
        localStorage.setItem('emodule_companion', selectedCompanion.value);
    }

    const anchor = event?.currentTarget || saveBtnRef.value;
    persistThemeToAccount(activeWorld.value, {
        onSuccess: () => {
            playSuccess();
            if (anchor) {
                spawnPixarBurst(anchor, 16, activeWorld.value);
            }
            triggerToast(`${activeWorldMeta.value.name} & settings saved across every login!`);
        },
    });
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
            <!-- 1. HERO HEADER BANNER -->
            <div class="clay-settings-hero">
                <span class="hero-gloss-shine" aria-hidden="true"></span>

                <div class="hero-top-meta">
                    <span class="settings-pill">STUDENT STUDIO</span>
                    <span class="settings-status-tag" :class="{ 'is-syncing': isSavingWorld }">
                        <span class="status-sync-dot" aria-hidden="true"></span>
                        {{ isSavingWorld ? 'Saving to Account...' : `World: ${activeWorldMeta.name} • Account Synced` }}
                    </span>
                </div>

                <div class="hero-content-row">
                    <div>
                        <h1 id="settings-heading" class="hero-main-title">
                            Color Worlds, Audio & Studio Settings
                        </h1>
                        <p class="hero-desc">
                            Choose your favorite Pixar-style color world, audio vibe, reading pace, and companion. Your theme stays saved on every login!
                        </p>
                    </div>

                    <div class="hero-icon-pod" aria-hidden="true">
                        <svg viewBox="0 0 64 64" class="clay-settings-cog-svg">
                            <defs>
                                <radialGradient id="pixarCogSphere" cx="34%" cy="28%" r="72%">
                                    <stop offset="0%" stop-color="#FFF0A8" />
                                    <stop offset="52%" stop-color="#FFBE1A" />
                                    <stop offset="100%" stop-color="#C96D00" />
                                </radialGradient>
                            </defs>
                            <circle cx="32" cy="32" r="28" fill="url(#pixarCogSphere)" stroke="#FFFFFF" stroke-width="2.5" />
                            <ellipse cx="23" cy="20" rx="10" ry="5" transform="rotate(-24 23 20)" fill="#FFFFFF" opacity="0.65" />
                            <circle cx="32" cy="32" r="10" fill="#FFFFFF" opacity="0.92" />
                            <circle cx="32" cy="32" r="5.5" fill="#18143B" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- TOAST CONFIRMATION -->
            <transition name="toast-pop">
                <div v-if="showSavedToast" class="clay-saved-toast" role="status" aria-live="polite">
                    <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor" aria-hidden="true">
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
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r="1.5" fill="currentColor" />
                                <circle cx="17.5" cy="10.5" r="1.5" fill="currentColor" />
                                <circle cx="8.5" cy="7.5" r="1.5" fill="currentColor" />
                                <circle cx="6.5" cy="12.5" r="1.5" fill="currentColor" />
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="card-title">Pixar Color World Theme</h2>
                            <p class="card-subtitle">Applies across all student pages & saves automatically to your profile</p>
                        </div>
                    </div>

                    <span class="world-current-chip">
                        {{ activeWorldMeta.mode }}
                    </span>
                </div>

                <div class="worlds-grid" role="radiogroup" aria-label="Choose your Pixar color world">
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
                            <span class="mini-stage-sun"></span>
                            <svg class="mini-stage-hills" viewBox="0 0 200 64" preserveAspectRatio="none">
                                <path d="M0,38 C45,14 95,46 145,22 C170,12 186,24 200,18 L200,64 L0,64 Z" :fill="world.preview.hillBack" opacity="0.82" />
                                <path d="M0,48 C50,30 100,56 150,34 C174,24 190,38 200,34 L200,64 L0,64 Z" :fill="world.preview.hillFront" />
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
                        <span class="card-badge-orb orb-audio" aria-hidden="true">🎧</span>
                        <div>
                            <h2 class="card-title">Sound & Music Studio</h2>
                            <p class="card-subtitle">Control the cheerful background melodies and button sounds</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="clay-mute-toggle-btn"
                        :class="{ 'is-muted': soundState.isMuted }"
                        @click="toggleMute"
                    >
                        <span>{{ soundState.isMuted ? '🔇 Audio Muted' : '🔊 Audio Active' }}</span>
                    </button>
                </div>

                <div class="settings-grid">
                    <!-- Background Music (BGM) -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-emoji" aria-hidden="true">🎵</span>
                                <div>
                                    <span class="item-name">Background Music</span>
                                    <span class="item-hint">Gentle sunny marimba & kalimba loop</span>
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
                                <span class="item-emoji" aria-hidden="true">✨</span>
                                <div>
                                    <span class="item-name">Playful Sound Effects</span>
                                    <span class="item-hint">Button pops, sparkles, boings & victory fanfare</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="test-sfx-chip"
                                :disabled="soundState.isMuted"
                                @click="playSparkle"
                            >
                                <span>✨ Test Sound</span>
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

            <!-- 4. COMPANION AVATAR SELECTOR CARD -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-badge-orb orb-companion" aria-hidden="true">🎒</span>
                        <div>
                            <h2 class="card-title">Companion Avatar</h2>
                            <p class="card-subtitle">Choose your loyal reading mascot for expeditions</p>
                        </div>
                    </div>
                </div>

                <div class="companions-grid">
                    <button
                        v-for="c in companions"
                        :key="c.id"
                        type="button"
                        class="companion-option-card"
                        :class="{ 'is-selected': selectedCompanion === c.id }"
                        @click="selectCompanion(c.id)"
                    >
                        <div class="companion-avatar-disc" :style="{ background: c.bg, borderColor: c.border }">
                            <span>{{ c.emoji }}</span>
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
                        <span class="card-badge-orb orb-reading" aria-hidden="true">📖</span>
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
                        <span class="item-hint">Speed for "Read Page Aloud" narration audio</span>

                        <div class="speed-buttons-row">
                            <button
                                v-for="spd in speedOptions"
                                :key="spd.value"
                                type="button"
                                class="speed-pill-btn"
                                :class="{ 'is-selected': selectedSpeed === spd.value }"
                                @click="handleSpeedChange(spd.value)"
                            >
                                {{ spd.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Text Size Toggle -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-emoji" aria-hidden="true">🔤</span>
                                <div>
                                    <span class="item-name">Large Reading Font</span>
                                    <span class="item-hint">Larger, more comfortable text on story pages</span>
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
                </div>
            </div>

            <!-- 6. SAVE BUTTON BAR (ZERO-LAYOUT-SHIFT PIXAR TOY-VINYL CTA) -->
            <div class="settings-action-bar">
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
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   PIXAR TACTILE STUDIO SETTINGS — THEME-ADAPTIVE SURFACES & CONTROLS
   ========================================================================== */
.clay-settings-page {
    --spring-bounce: var(--px-ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));
    --font-display: var(--px-font-display, 'Fredoka', -apple-system, sans-serif);
    --font-ui: var(--px-font-body, 'Nunito', -apple-system, sans-serif);

    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding-bottom: 5.5rem;
}

/* 1. HERO BANNER */
.clay-settings-hero {
    position: relative;
    overflow: hidden;
    border-radius: 30px;
    background: var(--px-hero-banner-grad);
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 8px 0 var(--px-hero-banner-ledge),
        0 20px 38px rgba(var(--px-shadow-rgb), 0.24),
        inset 0 3px 6px rgba(255, 255, 255, 0.45);
    padding: 1.5rem 1.6rem 1.35rem;
    color: #FFFFFF;
}

.hero-gloss-shine {
    position: absolute;
    top: 5px;
    left: 20px;
    right: 20px;
    height: 15px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.52) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hero-top-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    flex-wrap: wrap;
    margin-bottom: 0.65rem;
}

.settings-pill {
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: var(--px-ink);
    background: var(--px-surface-elevated);
    padding: 0.24rem 0.7rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border);
    box-shadow: 0 3px 0 rgba(var(--px-shadow-rgb), 0.2);
}

.settings-status-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-family: var(--font-ui);
    font-size: 0.76rem;
    font-weight: 800;
    color: #FFFFFF;
    background: rgba(var(--px-shadow-rgb), 0.34);
    border: 1px solid rgba(255, 255, 255, 0.32);
    padding: 0.22rem 0.68rem;
    border-radius: 999px;
}

.status-sync-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #34D399;
    box-shadow: 0 0 8px #34D399;
}

.settings-status-tag.is-syncing .status-sync-dot {
    background: #FBBF24;
    box-shadow: 0 0 8px #FBBF24;
}

.hero-content-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.hero-main-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.42rem, 3.2vw, 1.98rem);
    font-weight: 900;
    color: #FFFFFF;
    text-shadow: 0 3px 0 rgba(var(--px-shadow-rgb), 0.28);
    line-height: 1.15;
}

.hero-desc {
    margin: 0.45rem 0 0;
    font-family: var(--font-ui);
    font-size: 0.9rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.92);
    max-width: 560px;
    line-height: 1.45;
}

.hero-icon-pod {
    width: 68px;
    height: 68px;
    flex-shrink: 0;
}

.clay-settings-cog-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 8px 14px rgba(var(--px-shadow-rgb), 0.3));
}

/* TOAST */
.clay-saved-toast {
    position: fixed;
    top: 1.4rem;
    left: 50%;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(180deg, var(--px-success-lit) 0%, var(--px-success) 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.94rem;
    font-weight: 800;
    padding: 0.65rem 1.35rem;
    border-radius: 9999px;
    border: 2px solid rgba(255, 255, 255, 0.88);
    box-shadow:
        0 4px 0 var(--px-success-pedestal),
        0 12px 26px var(--px-success-glow);
    z-index: 1000;
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

/* 2. CARDS */
.clay-card {
    position: relative;
    border-radius: 28px;
    background: linear-gradient(176deg, var(--px-surface-card) 0%, var(--px-surface-card-bottom) 100%);
    border: 2.5px solid var(--px-card-border);
    box-shadow: var(--px-card-shadow);
    padding: 1.3rem 1.45rem;
    transition: background 320ms var(--px-ease-settle), border-color 280ms ease;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding-bottom: 0.85rem;
    border-bottom: 2px dashed rgba(var(--px-shadow-rgb), 0.16);
    margin-bottom: 1.05rem;
}

.card-title-group {
    display: flex;
    align-items: center;
    gap: 0.78rem;
}

.card-badge-orb {
    width: 44px;
    height: 44px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    color: #FFFFFF;
    border: 2px solid rgba(255, 255, 255, 0.78);
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 3px rgba(255, 255, 255, 0.6);
}

.orb-palette { background: linear-gradient(145deg, #FF7866 0%, #DE2B1E 100%); }
.orb-audio { background: linear-gradient(145deg, #52ADFF 0%, #146CE8 100%); }
.orb-companion { background: linear-gradient(145deg, #3AE8D2 0%, #078578 100%); }
.orb-reading { background: linear-gradient(145deg, #FFBE1A 0%, #D97706 100%); color: #261800; }

.card-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.18rem;
    font-weight: 900;
    color: var(--px-ink);
}

.card-subtitle {
    margin: 0.15rem 0 0;
    font-family: var(--font-ui);
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.world-current-chip {
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--px-accent-ink);
    background: linear-gradient(180deg, var(--px-accent-lit) 0%, var(--px-accent) 100%);
    padding: 0.28rem 0.75rem;
    border-radius: 999px;
    box-shadow:
        0 3px 0 var(--px-accent-pedestal),
        inset 0 2px 2px rgba(255, 255, 255, 0.6);
}

/* ==========================================================================
   PIXAR COLOR WORLD SELECTOR GRID
   ========================================================================== */
.worlds-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.world-theme-card {
    position: relative;
    display: flex;
    flex-direction: column;
    text-align: left;
    padding: 0.65rem;
    border-radius: 22px;
    border: 2.5px solid var(--px-card-border);
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    cursor: pointer;
    color: var(--px-ink);
    transition: transform 220ms var(--spring-bounce), box-shadow 220ms ease, border-color 200ms ease;
}

.world-theme-card:hover {
    transform: translate3d(0, -4px, 0) scale(1.02);
    box-shadow:
        0 8px 18px -6px rgba(var(--px-shadow-rgb), 0.25),
        var(--px-well-shadow);
}

.world-theme-card:active {
    transform: translate3d(0, 2px, 0) scale(0.98);
}

.world-theme-card:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.world-theme-card.is-selected {
    border-color: var(--px-focus-stroke);
    background: var(--px-surface-elevated);
    box-shadow:
        0 0 0 3px var(--px-focus-ring),
        0 10px 22px -6px rgba(var(--px-shadow-rgb), 0.25);
}

.world-mini-stage {
    position: relative;
    width: 100%;
    height: 82px;
    border-radius: 16px;
    overflow: hidden;
    border: 1.5px solid rgba(255, 255, 255, 0.65);
    box-shadow: inset 0 2px 6px rgba(var(--px-shadow-rgb), 0.16);
    margin-bottom: 0.65rem;
}

.mini-stage-sun {
    position: absolute;
    top: 10px;
    left: 12px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #FFFFFF 0%, #FFE066 65%, #FFA929 100%);
    box-shadow: 0 0 10px rgba(255, 224, 102, 0.8);
}

.mini-stage-hills {
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    width: 100%;
    height: 46px;
}

.mini-stage-card {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 10px;
    border: 1.5px solid rgba(255, 255, 255, 0.78);
    box-shadow: 0 4px 10px rgba(var(--px-shadow-rgb), 0.28);
}

.mini-marble {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.88);
    box-shadow: inset 0 2px 2px rgba(255, 255, 255, 0.6);
}

.mini-mode-tag {
    position: absolute;
    top: 6px;
    right: 6px;
    padding: 0.1rem 0.42rem;
    border-radius: 999px;
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
    background: rgba(255, 255, 255, 0.9);
    color: #18143B;
}

.world-card-meta {
    display: flex;
    flex-direction: column;
    gap: 0.16rem;
    padding: 0 0.2rem 0.15rem;
}

.world-card-name {
    font-family: var(--font-display);
    font-size: 0.94rem;
    font-weight: 900;
    color: var(--px-ink);
}

.world-card-inspiration {
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-link-ink);
}

.world-card-desc {
    margin-top: 0.1rem;
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    line-height: 1.35;
}

.world-active-badge {
    top: -8px;
    right: 12px;
}

/* ==========================================================================
   MUTE / TEST BUTTONS & SLIDERS
   ========================================================================== */
.clay-mute-toggle-btn {
    padding: 0.42rem 0.92rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    border: 2px solid var(--px-card-border);
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    color: var(--px-secondary-ink);
    cursor: pointer;
    box-shadow: 0 3px 0 var(--px-secondary-pedestal);
    transition: transform 0.16s var(--spring-bounce), box-shadow 0.16s ease;
}

.clay-mute-toggle-btn:hover {
    transform: translateY(-2px);
}

.clay-mute-toggle-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 var(--px-secondary-pedestal);
}

.clay-mute-toggle-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.clay-mute-toggle-btn.is-muted {
    background: linear-gradient(180deg, var(--px-danger-lit) 0%, var(--px-danger) 100%);
    border-color: var(--px-card-border);
    color: #FFFFFF;
    box-shadow: 0 3px 0 var(--px-danger-pedestal);
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.setting-item-box {
    background: var(--px-surface-well);
    border: 1.5px solid var(--px-card-border);
    box-shadow: var(--px-well-shadow);
    border-radius: 20px;
    padding: 0.98rem 1.1rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.58rem;
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
    gap: 0.55rem;
}

.item-emoji {
    font-size: 1.3rem;
}

.item-name {
    display: block;
    font-family: var(--font-display);
    font-size: 0.94rem;
    font-weight: 800;
    color: var(--px-ink);
}

.item-hint {
    display: block;
    font-family: var(--font-ui);
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

/* Bouncy Pixar Clay Toggle Switch (No Pure #000 Shadows) */
.clay-toggle-switch {
    position: relative;
    width: 52px;
    height: 30px;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border);
    background: rgba(var(--px-shadow-rgb), 0.26);
    box-shadow: inset 0 3px 6px rgba(var(--px-shadow-rgb), 0.24), 0 2px 0 rgba(255, 255, 255, 0.35);
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
    outline-offset: 3px;
}

.clay-toggle-switch .knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, #EEF2FF 100%);
    box-shadow: 0 3px 6px rgba(var(--px-shadow-rgb), 0.28);
    transition: transform 0.26s var(--spring-bounce);
}

.clay-toggle-switch.is-on .knob {
    transform: translateX(22px);
}

.clay-toggle-switch:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.test-sfx-chip {
    padding: 0.32rem 0.72rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--px-accent-ink);
    background: linear-gradient(180deg, var(--px-accent-lit) 0%, var(--px-accent) 100%);
    border: 1.5px solid rgba(255, 255, 255, 0.75);
    box-shadow: 0 3px 0 var(--px-accent-pedestal);
    cursor: pointer;
    transition: transform 0.15s ease;
}

.test-sfx-chip:hover:not(:disabled) {
    transform: translateY(-2px);
}

.test-sfx-chip:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

/* Sliders */
.slider-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.slider-icon {
    font-size: 0.85rem;
    opacity: 0.75;
}

.clay-slider {
    flex: 1;
    -webkit-appearance: none;
    appearance: none;
    height: 9px;
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
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, var(--px-accent-lit) 28%, var(--px-accent) 100%);
    border: 2px solid #FFFFFF;
    box-shadow: 0 3px 6px rgba(var(--px-shadow-rgb), 0.3);
    cursor: pointer;
    transition: transform 0.15s var(--spring-bounce);
}

.clay-slider::-webkit-slider-thumb:hover {
    transform: scale(1.16);
}

.pct-badge {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-ink);
    min-width: 36px;
    text-align: right;
}

/* Companions Grid */
.companions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.85rem;
}

.companion-option-card {
    position: relative;
    background: var(--px-surface-well);
    border: 2px solid var(--px-card-border);
    box-shadow: var(--px-well-shadow);
    border-radius: 22px;
    padding: 1rem 0.8rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    transition: transform 0.2s var(--spring-bounce), border-color 0.18s ease, background 0.18s ease, box-shadow 0.2s ease;
}

.companion-option-card:hover {
    transform: translateY(-3px);
}

.companion-option-card:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.companion-option-card.is-selected {
    background: var(--px-surface-elevated);
    border-color: var(--px-success);
    box-shadow:
        0 4px 0 var(--px-success),
        0 10px 20px -4px var(--px-success-glow);
}

.companion-avatar-disc {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    border: 3px solid;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 5px 12px rgba(var(--px-shadow-rgb), 0.2);
    margin-bottom: 0.5rem;
}

.companion-option-name {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--px-ink);
}

.companion-option-desc {
    margin-top: 0.2rem;
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    line-height: 1.35;
}

.companion-active-badge {
    position: absolute;
    top: -8px;
    right: 10px;
    background: linear-gradient(180deg, var(--px-success-lit) 0%, var(--px-success) 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 900;
    padding: 0.12rem 0.5rem;
    border-radius: 9999px;
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 2px 0 var(--px-success-pedestal);
}

/* Speed Pills */
.speed-buttons-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.35rem;
}

.speed-pill-btn {
    flex: 1;
    padding: 0.42rem 0.5rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-elevated);
    color: var(--px-ink-muted);
    cursor: pointer;
    box-shadow: 0 3px 0 rgba(var(--px-shadow-rgb), 0.16);
    transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
}

.speed-pill-btn:hover {
    transform: translateY(-2px);
    color: var(--px-ink);
}

.speed-pill-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.speed-pill-btn.is-selected {
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    color: var(--px-secondary-ink);
    border-color: rgba(255, 255, 255, 0.75);
    box-shadow: 0 3px 0 var(--px-secondary-pedestal);
}

/* Save Bar: Zero-Layout-Shift Pixar Toy-Vinyl CTA */
.settings-action-bar {
    display: flex;
    justify-content: flex-end;
}

.pixar-save-all-btn {
    --_depth: 6px;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.84rem 1.85rem;
    margin-bottom: var(--_depth);
    border-radius: 9999px;
    border: 0;
    font-family: var(--font-display);
    font-size: 0.98rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: var(--px-explore-ink);
    background: linear-gradient(180deg, var(--px-explore-lit) 0%, var(--px-explore) 48%, var(--px-explore-belly) 100%);
    box-shadow:
        0 var(--_depth) 0 var(--px-explore-pedestal),
        0 calc(var(--_depth) + 10px) 24px -6px var(--px-explore-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.65),
        inset 0 -4px 8px rgba(var(--px-shadow-rgb), 0.16);
    cursor: pointer;
    transition: transform 220ms var(--spring-bounce), box-shadow 180ms ease;
}

.pixar-save-all-btn::before {
    content: "";
    position: absolute;
    inset: 3px 10px auto 10px;
    height: 44%;
    border-radius: inherit;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0.04) 100%);
    pointer-events: none;
}

.pixar-save-all-btn:hover:not(:disabled) {
    transform: translate3d(0, -2px, 0) scale(1.025);
    box-shadow:
        0 calc(var(--_depth) + 2px) 0 var(--px-explore-pedestal),
        0 calc(var(--_depth) + 16px) 28px -5px var(--px-explore-glow),
        inset 0 2px 3px rgba(255, 255, 255, 0.72);
}

.pixar-save-all-btn:active:not(:disabled) {
    transform: translate3d(0, calc(var(--_depth) - 2px), 0) scaleX(1.035) scaleY(0.95);
    box-shadow:
        0 2px 0 var(--px-explore-pedestal),
        0 4px 10px -2px var(--px-explore-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.5);
    transition-duration: 75ms;
}

.pixar-save-all-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

.pixar-save-all-btn:disabled {
    opacity: 0.72;
    cursor: wait;
}

/* Responsive */
@media (max-width: 960px) {
    .worlds-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .companions-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 520px) {
    .worlds-grid {
        grid-template-columns: 1fr;
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
