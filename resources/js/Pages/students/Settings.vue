<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '../../Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student Explorer',
            email: 'student@example.com',
            role: 'student',
        }),
    },
});

const {
    state: soundState,
    playPop,
    playSparkle,
    playClick,
    playSuccess,
    toggleBgm,
    toggleMute,
    setBgmVolume,
    setSfxVolume,
} = useSound();

// Local preference states with persistence
const defaultSpeed = typeof window !== 'undefined' ? localStorage.getItem('emodule_narration_speed') || '1' : '1';
const defaultLargeText = typeof window !== 'undefined' ? localStorage.getItem('emodule_large_text') === 'true' : false;
const defaultCompanion = typeof window !== 'undefined' ? localStorage.getItem('emodule_companion') || 'fox' : 'fox';

const selectedSpeed = ref(defaultSpeed);
const isLargeText = ref(defaultLargeText);
const selectedCompanion = ref(defaultCompanion);
const showSavedToast = ref(false);

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

const saveAllPreferences = () => {
    playSuccess();
    showSavedToast.value = true;
    setTimeout(() => {
        showSavedToast.value = false;
    }, 2800);
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
                <div class="hero-gear-sparkle s-1" aria-hidden="true">⚙️</div>
                <div class="hero-gear-sparkle s-2" aria-hidden="true">🎵</div>

                <div class="hero-top-meta">
                    <span class="settings-pill">STUDENT PREFERENCES</span>
                    <span class="settings-status-tag">Synced to Device</span>
                </div>

                <div class="hero-content-row">
                    <div>
                        <h1 id="settings-heading" class="hero-main-title">
                            Adventure Studio & Audio Settings
                        </h1>
                        <p class="hero-desc">
                            Customize your background music, playful sound effects, narration speed, and reading companion!
                        </p>
                    </div>

                    <div class="hero-icon-pod" aria-hidden="true">
                        <svg viewBox="0 0 64 64" class="clay-settings-cog-svg">
                            <defs>
                                <linearGradient id="clayCogGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#C4B5FD" />
                                    <stop offset="50%" stop-color="#8B5CF6" />
                                    <stop offset="100%" stop-color="#5B21B6" />
                                </linearGradient>
                            </defs>
                            <circle cx="32" cy="32" r="28" fill="url(#clayCogGrad)" stroke="#FFFFFF" stroke-width="2.5" />
                            <circle cx="32" cy="32" r="10" fill="#FFFFFF" opacity="0.9" />
                            <circle cx="32" cy="32" r="6" fill="#6D28D9" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- TOAST CONFIRMATION -->
            <transition name="toast-pop">
                <div v-if="showSavedToast" class="clay-saved-toast" role="alert">
                    <span>✨ Settings Saved Successfully!</span>
                </div>
            </transition>

            <!-- 2. AUDIO & VIBE STUDIO CARD -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-emoji">🎧</span>
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
                                <span class="item-emoji">🎵</span>
                                <div>
                                    <span class="item-name">Background Music</span>
                                    <span class="item-hint">Gentle sunny marimba & kalimba loop</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="clay-toggle-switch"
                                :class="{ 'is-on': soundState.isBgmPlaying && !soundState.isMuted }"
                                :disabled="soundState.isMuted"
                                @click="toggleBgm"
                            >
                                <span class="knob"></span>
                            </button>
                        </div>

                        <div class="slider-row">
                            <span class="slider-icon">🔈</span>
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
                            <span class="slider-icon">🔊</span>
                            <span class="pct-badge">{{ Math.round((soundState.bgmVolume / 0.8) * 100) }}%</span>
                        </div>
                    </div>

                    <!-- Sound Effects (SFX) -->
                    <div class="setting-item-box">
                        <div class="setting-item-top">
                            <div class="item-info">
                                <span class="item-emoji">✨</span>
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
                            <span class="slider-icon">🔈</span>
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
                            <span class="slider-icon">🔊</span>
                            <span class="pct-badge">{{ Math.round(soundState.sfxVolume * 100) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. COMPANION AVATAR SELECTOR CARD -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-emoji">🎒</span>
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

            <!-- 4. READING & NARRATION PREFERENCES -->
            <div class="clay-card">
                <div class="card-header">
                    <div class="card-title-group">
                        <span class="card-emoji">📖</span>
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
                                <span class="item-emoji">🔤</span>
                                <div>
                                    <span class="item-name">Large Reading Font</span>
                                    <span class="item-hint">Larger, more comfortable text on story pages</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="clay-toggle-switch"
                                :class="{ 'is-on': isLargeText }"
                                @click="toggleLargeText"
                            >
                                <span class="knob"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. SAVE BUTTON BAR -->
            <div class="settings-action-bar">
                <button
                    type="button"
                    class="clay-save-all-btn"
                    @click="saveAllPreferences"
                >
                    <span class="save-btn-shine" aria-hidden="true"></span>
                    <span>✓ Save All Preferences</span>
                </button>
            </div>
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   CLAYMORPHIC SETTINGS TOKENS & STYLES
   ========================================================================== */
.clay-settings-page {
    --ink-primary: #1E1B4B;
    --ink-secondary: #475569;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-display: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;

    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding-bottom: 5.5rem;
}

/* 1. HERO BANNER */
.clay-settings-hero {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    background: linear-gradient(145deg, #F5F3FF 0%, #EDE9FE 60%, #DDD6FE 100%);
    border: 3px solid #FFFFFF;
    box-shadow:
        0 8px 0 #7C3AED,
        0 18px 36px rgba(124, 58, 237, 0.18),
        inset 0 3px 6px rgba(255, 255, 255, 0.95);
    padding: 1.5rem 1.6rem 1.3rem;
}

.hero-gloss-shine {
    position: absolute;
    top: 4px;
    left: 20px;
    right: 20px;
    height: 14px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hero-gear-sparkle {
    position: absolute;
    font-size: 1.4rem;
    opacity: 0.55;
    animation: gearBob 4s infinite alternate ease-in-out;
    pointer-events: none;
}

.s-1 { top: 12%; right: 16%; animation-delay: 0s; }
.s-2 { bottom: 16%; left: 35%; animation-delay: -2s; font-size: 1.15rem; }

@keyframes gearBob {
    0% { transform: translateY(0) rotate(-10deg); }
    100% { transform: translateY(-8px) rotate(10deg); }
}

.hero-top-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.6rem;
}

.settings-pill {
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: #5B21B6;
    background: rgba(255, 255, 255, 0.85);
    padding: 0.22rem 0.65rem;
    border-radius: 9999px;
    border: 1.5px solid #C4B5FD;
}

.settings-status-tag {
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 700;
    color: #6D28D9;
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
    font-size: clamp(1.4rem, 3.2vw, 1.95rem);
    font-weight: 900;
    color: #4C1D95;
    line-height: 1.15;
}

.hero-desc {
    margin: 0.45rem 0 0;
    font-family: var(--font-ui);
    font-size: 0.9rem;
    font-weight: 600;
    color: #5B21B6;
    max-width: 520px;
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
    filter: drop-shadow(0 6px 14px rgba(109, 40, 217, 0.35));
}

/* TOAST */
.clay-saved-toast {
    position: fixed;
    top: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(145deg, #10B981 0%, #059669 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    padding: 0.65rem 1.35rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
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
    border-radius: 24px;
    background: #FFFFFF;
    border: 3px solid #FFFFFF;
    box-shadow:
        0 8px 0 #C7D2FE,
        0 18px 36px rgba(49, 46, 129, 0.12),
        inset 0 3px 5px rgba(255, 255, 255, 0.95);
    padding: 1.25rem 1.4rem;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.85rem;
    border-bottom: 2px dashed #E2E8F0;
    margin-bottom: 1rem;
}

.card-title-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-emoji {
    font-size: 1.6rem;
}

.card-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 900;
    color: var(--ink-primary);
}

.card-subtitle {
    margin: 0.15rem 0 0;
    font-family: var(--font-ui);
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ink-secondary);
}

.clay-mute-toggle-btn {
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    border: 2px solid #C7D2FE;
    background: #EEF2FF;
    color: #4338CA;
    cursor: pointer;
    box-shadow: 0 3px 0 #A5B4FC;
    transition: transform 0.16s var(--spring-bounce), background 0.16s ease;
}

.clay-mute-toggle-btn:hover {
    transform: translateY(-2px);
    background: #E0E7FF;
}

.clay-mute-toggle-btn.is-muted {
    background: #FEE2E2;
    border-color: #FCA5A5;
    color: #991B1B;
    box-shadow: 0 3px 0 #EF4444;
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.setting-item-box {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 0.95rem 1.05rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.55rem;
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
    gap: 0.5rem;
}

.item-emoji {
    font-size: 1.3rem;
}

.item-name {
    display: block;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--ink-primary);
}

.item-hint {
    display: block;
    font-family: var(--font-ui);
    font-size: 0.74rem;
    font-weight: 600;
    color: var(--ink-secondary);
}

/* Bouncy Clay Toggle Switch */
.clay-toggle-switch {
    position: relative;
    width: 48px;
    height: 28px;
    border-radius: 9999px;
    border: 2px solid #2B3354;
    background: #CBD5E1;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 2px 0 #1D2440;
    cursor: pointer;
    transition: background 0.25s ease;
    padding: 0;
    outline: none;
    flex-shrink: 0;
}

.clay-toggle-switch.is-on {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.clay-toggle-switch .knob {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 1.5px solid #2B3354;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.25s var(--spring-bounce);
}

.clay-toggle-switch.is-on .knob {
    transform: translateX(20px);
}

.clay-toggle-switch:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.test-sfx-chip {
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    color: #1E40AF;
    background: #DBEAFE;
    border: 1.5px solid #3B82F6;
    box-shadow: 0 2px 0 #1D4ED8;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.test-sfx-chip:hover:not(:disabled) {
    transform: translateY(-1px);
    background: #BFDBFE;
}

/* Sliders */
.slider-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.slider-icon {
    font-size: 0.85rem;
    opacity: 0.7;
}

.clay-slider {
    flex: 1;
    -webkit-appearance: none;
    height: 8px;
    border-radius: 9999px;
    background: #E2E8F0;
    outline: none;
    border: 1px solid #CBD5E1;
}

.clay-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(145deg, #FFC000 0%, #F59E0B 100%);
    border: 2px solid #2B3354;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.15s var(--spring-bounce);
}

.clay-slider::-webkit-slider-thumb:hover {
    transform: scale(1.15);
}

.pct-badge {
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--ink-primary);
    min-width: 35px;
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
    background: #F8FAFC;
    border: 2.5px solid #E2E8F0;
    border-radius: 20px;
    padding: 1rem 0.8rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    transition: transform 0.18s var(--spring-bounce), border-color 0.18s ease, background 0.18s ease;
}

.companion-option-card:hover {
    transform: translateY(-3px);
    background: #F1F5F9;
}

.companion-option-card.is-selected {
    background: linear-gradient(145deg, #F0FDF4 0%, #DCFCE7 100%);
    border-color: #10B981;
    box-shadow: 0 4px 0 #059669, 0 8px 18px rgba(16, 185, 129, 0.2);
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
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
    margin-bottom: 0.5rem;
}

.companion-option-name {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--ink-primary);
}

.companion-option-desc {
    margin-top: 0.2rem;
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--ink-secondary);
    line-height: 1.35;
}

.companion-active-badge {
    position: absolute;
    top: -8px;
    right: 10px;
    background: #10B981;
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 900;
    padding: 0.1rem 0.45rem;
    border-radius: 9999px;
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 2px 0 #059669;
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
    padding: 0.4rem 0.5rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    border: 2px solid #E2E8F0;
    background: #FFFFFF;
    color: var(--ink-secondary);
    cursor: pointer;
    box-shadow: 0 2px 0 #CBD5E1;
    transition: transform 0.15s ease, background 0.15s ease;
}

.speed-pill-btn:hover {
    transform: translateY(-1px);
    background: #F1F5F9;
}

.speed-pill-btn.is-selected {
    background: linear-gradient(145deg, #6366F1 0%, #4F46E5 100%);
    color: #FFFFFF;
    border-color: #818CF8;
    box-shadow: 0 3px 0 #3730A3;
}

/* Save Bar */
.settings-action-bar {
    display: flex;
    justify-content: flex-end;
}

.clay-save-all-btn {
    position: relative;
    overflow: hidden;
    padding: 0.75rem 1.6rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 900;
    color: #FFFFFF;
    background: linear-gradient(145deg, #10B981 0%, #059669 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 6px 0 #047857,
        0 12px 24px rgba(16, 185, 129, 0.3),
        inset 0 2px 4px rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: transform 0.18s var(--spring-bounce);
}

.clay-save-all-btn:hover {
    transform: translateY(-2px);
}

.clay-save-all-btn:active {
    transform: translateY(2px);
    box-shadow: 0 2px 0 #047857;
}

.save-btn-shine {
    position: absolute;
    top: 2px;
    left: 14px;
    right: 14px;
    height: 10px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

/* Responsive */
@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .companions-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

