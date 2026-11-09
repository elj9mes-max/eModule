<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useSound } from '@/Services/soundManager';

const props = defineProps({
    /**
     * Display variant:
     * - 'floating': Floating clay widget (ideal for Welcome.vue)
     * - 'compact': Inline HUD capsule (ideal for StudentLayout.vue header)
     */
    variant: {
        type: String,
        default: 'floating',
    },
});

const {
    state,
    playPop,
    playSparkle,
    playClick,
    playBonus,
    toggleBgm,
    toggleMute,
    setBgmVolume,
    setSfxVolume,
} = useSound();

const isOpen = ref(false);
const panelRef = ref(null);
const triggerRef = ref(null);

const togglePanel = () => {
    isOpen.value = !isOpen.value;
    playPop(isOpen.value ? 1.2 : 0.9);
};

const handleTestSound = () => {
    playSparkle();
};

const handleBgmToggle = () => {
    toggleBgm();
};

const handleMuteToggle = () => {
    toggleMute();
};

const handleBgmSlider = (e) => {
    const val = parseFloat(e.target.value);
    setBgmVolume(val);
    if (!state.isBgmPlaying && val > 0) {
        toggleBgm();
    }
};

const handleSfxSlider = (e) => {
    const val = parseFloat(e.target.value);
    setSfxVolume(val);
    playPop(1.0);
};

// Close panel when clicking outside
const handleClickOutside = (e) => {
    if (!isOpen.value) return;
    if (
        panelRef.value &&
        !panelRef.value.contains(e.target) &&
        triggerRef.value &&
        !triggerRef.value.contains(e.target)
    ) {
        isOpen.value = false;
        playPop(0.85);
    }
};

// Close on Escape key
const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
        triggerRef.value?.focus();
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('click', handleClickOutside);
        window.addEventListener('keydown', handleKeyDown);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('click', handleClickOutside);
        window.removeEventListener('keydown', handleKeyDown);
    }
});
</script>

<template>
    <div
        class="sound-controller-root"
        :class="[`variant-${variant}`, { 'is-open': isOpen }]"
    >
        <!-- TRIGGER BUTTON -->
        <button
            ref="triggerRef"
            type="button"
            class="sound-trigger-btn"
            :class="{
                'is-muted': state.isMuted,
                'is-bgm-active': state.isBgmPlaying && !state.isMuted,
            }"
            :aria-expanded="isOpen"
            :aria-label="state.isMuted ? 'Audio muted. Tap to open sound settings.' : 'Audio active. Tap to open sound settings.'"
            :title="state.isMuted ? 'Audio Muted' : state.isBgmPlaying ? 'Music & SFX Playing' : 'SFX Ready (Music Paused)'"
            @click.stop="togglePanel"
        >
            <!-- Animated Equalizer Bars when BGM is playing -->
            <div
                v-if="state.isBgmPlaying && !state.isMuted"
                class="eq-bars"
                aria-hidden="true"
            >
                <span class="eq-bar bar-1"></span>
                <span class="eq-bar bar-2"></span>
                <span class="eq-bar bar-3"></span>
            </div>

            <!-- Speaker / Note Icon -->
            <div class="trigger-icon-wrap" aria-hidden="true">
                <span v-if="state.isMuted" class="icon-emoji">🔇</span>
                <span v-else-if="state.isBgmPlaying" class="icon-emoji music-bounce">🎵</span>
                <span v-else class="icon-emoji">🔊</span>
            </div>

            <span v-if="variant === 'floating'" class="trigger-label">
                {{ state.isMuted ? 'Muted' : state.isBgmPlaying ? 'Music ON' : 'Sound' }}
            </span>

            <span class="trigger-chevron" :class="{ 'is-flipped': isOpen }" aria-hidden="true">▾</span>
        </button>

        <!-- SOUND SETTINGS FLYOUT PANEL -->
        <transition name="sound-panel-pop">
            <div
                v-if="isOpen"
                ref="panelRef"
                class="sound-settings-panel"
                role="dialog"
                aria-label="Sound and Music Settings"
            >
                <!-- Specular Highlight -->
                <span class="panel-gloss" aria-hidden="true"></span>

                <!-- Header -->
                <div class="panel-header">
                    <div class="panel-title-group">
                        <span class="panel-title-icon">🎧</span>
                        <h3 class="panel-title">Audio & Vibe</h3>
                    </div>

                    <!-- Quick Master Mute -->
                    <button
                        type="button"
                        class="mute-chip-btn"
                        :class="{ 'is-active': state.isMuted }"
                        :aria-pressed="state.isMuted"
                        @click="handleMuteToggle"
                    >
                        <span>{{ state.isMuted ? '🔇 Unmute' : '🔊 Mute' }}</span>
                    </button>
                </div>

                <!-- BGM / Music Card -->
                <div class="setting-card">
                    <div class="setting-card-header">
                        <div class="setting-info">
                            <span class="setting-emoji">🎵</span>
                            <div>
                                <span class="setting-name">Background Music</span>
                                <span class="setting-desc">Playful sunny melody</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="toggle-switch-btn"
                            :class="{ 'is-on': state.isBgmPlaying && !state.isMuted }"
                            :disabled="state.isMuted"
                            :aria-label="`Toggle background music. Currently ${state.isBgmPlaying ? 'ON' : 'OFF'}`"
                            @click="handleBgmToggle"
                        >
                            <span class="toggle-knob"></span>
                        </button>
                    </div>

                    <!-- BGM Volume Slider -->
                    <div class="slider-row">
                        <span class="slider-icon">🔈</span>
                        <input
                            type="range"
                            min="0"
                            max="0.8"
                            step="0.02"
                            :value="state.bgmVolume"
                            :disabled="state.isMuted || !state.isBgmPlaying"
                            class="sound-range-slider"
                            aria-label="Background music volume"
                            @input="handleBgmSlider"
                        />
                        <span class="slider-icon">🔊</span>
                        <span class="volume-badge">{{ Math.round((state.bgmVolume / 0.8) * 100) }}%</span>
                    </div>
                </div>

                <!-- SFX / Sound Effects Card -->
                <div class="setting-card">
                    <div class="setting-card-header">
                        <div class="setting-info">
                            <span class="setting-emoji">✨</span>
                            <div>
                                <span class="setting-name">Sound Effects</span>
                                <span class="setting-desc">Pops, chimes & boings</span>
                            </div>
                        </div>

                        <!-- Test Sound Button -->
                        <button
                            type="button"
                            class="test-sfx-btn"
                            :disabled="state.isMuted"
                            title="Play sample sound"
                            @click="handleTestSound"
                        >
                            <span>✨ Test</span>
                        </button>
                    </div>

                    <!-- SFX Volume Slider -->
                    <div class="slider-row">
                        <span class="slider-icon">🔈</span>
                        <input
                            type="range"
                            min="0"
                            max="1"
                            step="0.05"
                            :value="state.sfxVolume"
                            :disabled="state.isMuted"
                            class="sound-range-slider"
                            aria-label="Sound effects volume"
                            @input="handleSfxSlider"
                        />
                        <span class="slider-icon">🔊</span>
                        <span class="volume-badge">{{ Math.round(state.sfxVolume * 100) }}%</span>
                    </div>
                </div>

                <!-- Footer Tip -->
                <div class="panel-footer">
                    <span class="footer-tip">
                        🌟 Tip: Hover badges and click <strong>EXPLORE</strong> for fun sound effects!
                    </span>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* ==========================================================================
   CLAYMORPHIC GAME TOKENS & CONTROLLER STYLES
   ========================================================================== */
.sound-controller-root {
    --clay-navy: #1D2440;
    --clay-blue: #2395EB;
    --clay-blue-dark: #1467A6;
    --clay-gold: #FFC000;
    --clay-coral: #FF6B4A;
    --clay-green: #06D6A0;
    --clay-purple: #845EC2;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-game: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-ui: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;

    position: relative;
    display: inline-block;
    font-family: var(--font-game);
    user-select: none;
    z-index: 100;
}

/* Floating variant (top-right fixed on Welcome.vue) */
.sound-controller-root.variant-floating {
    position: fixed;
    top: 1.25rem;
    right: 1.25rem;
    z-index: 999;
}

/* Compact variant (for HUD headers) */
.sound-controller-root.variant-compact {
    position: relative;
}

/* ==========================================================================
   TRIGGER BUTTON
   ========================================================================== */
.sound-trigger-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.85rem;
    border-radius: 999px;
    background: linear-gradient(145deg, #FFFFFF 0%, #F5EFE0 100%);
    border: 2.5px solid #2B3354;
    box-shadow:
        0 4px 0 #1D2440,
        0 6px 14px rgba(29, 36, 64, 0.16),
        inset 0 2px 4px rgba(255, 255, 255, 0.9);
    color: var(--clay-navy);
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.18s var(--spring-bounce), box-shadow 0.18s ease, background 0.2s ease;
    outline: none;
}

.sound-trigger-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow:
        0 6px 0 #1D2440,
        0 10px 18px rgba(29, 36, 64, 0.2),
        inset 0 2px 4px rgba(255, 255, 255, 0.95);
}

.sound-trigger-btn:active {
    transform: translateY(2px) scale(0.98);
    box-shadow:
        0 2px 0 #1D2440,
        0 3px 6px rgba(29, 36, 64, 0.15),
        inset 0 1px 2px rgba(29, 36, 64, 0.1);
}

.sound-trigger-btn:focus-visible {
    box-shadow:
        0 0 0 3px #FFFFFF,
        0 0 0 5px var(--clay-blue),
        0 4px 0 #1D2440;
}

.sound-trigger-btn.is-bgm-active {
    background: linear-gradient(145deg, #FFFBEB 0%, #FEF3C7 100%);
    border-color: #D97706;
    box-shadow:
        0 4px 0 #92400E,
        0 6px 14px rgba(217, 119, 6, 0.25),
        inset 0 2px 4px rgba(255, 255, 255, 0.9);
}

.sound-trigger-btn.is-muted {
    background: linear-gradient(145deg, #F3F4F6 0%, #E5E7EB 100%);
    border-color: #6B7280;
    color: #4B5563;
    box-shadow:
        0 4px 0 #374151,
        0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Equalizer Bars Animation */
.eq-bars {
    display: inline-flex;
    align-items: flex-end;
    gap: 2px;
    height: 14px;
    width: 12px;
    margin-right: 2px;
}

.eq-bar {
    width: 2.5px;
    border-radius: 999px;
    background: #F59E0B;
    animation: eqBounce 0.9s infinite ease-in-out;
}

.eq-bar.bar-1 {
    height: 40%;
    animation-delay: 0s;
}

.eq-bar.bar-2 {
    height: 90%;
    animation-delay: 0.2s;
    background: #EC4899;
}

.eq-bar.bar-3 {
    height: 60%;
    animation-delay: 0.4s;
    background: #2563EB;
}

@keyframes eqBounce {
    0%, 100% { height: 30%; }
    50% { height: 100%; }
}

.trigger-icon-wrap {
    font-size: 1.05rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.music-bounce {
    display: inline-block;
    animation: noteWobble 1.8s infinite ease-in-out;
}

@keyframes noteWobble {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(-8deg) scale(1.1); }
    75% { transform: rotate(8deg) scale(1.05); }
}

.trigger-label {
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.02em;
}

.trigger-chevron {
    font-size: 0.8rem;
    transition: transform 0.2s var(--spring-bounce);
    opacity: 0.7;
}

.trigger-chevron.is-flipped {
    transform: rotate(180deg);
}

/* ==========================================================================
   SETTINGS FLYOUT PANEL (CLAY TACTILE POPUP)
   ========================================================================== */
.sound-settings-panel {
    position: absolute;
    top: calc(100% + 0.65rem);
    right: 0;
    width: 310px;
    padding: 1.15rem;
    border-radius: 1.25rem;
    background: linear-gradient(150deg, #FFFFFF 0%, #FAF6EE 100%);
    border: 3px solid #2B3354;
    box-shadow:
        0 10px 0 #1D2440,
        0 18px 30px rgba(29, 36, 64, 0.24),
        inset 0 3px 6px rgba(255, 255, 255, 0.95);
    z-index: 1000;
    box-sizing: border-box;
}

.panel-gloss {
    position: absolute;
    top: 6px;
    left: 12px;
    right: 12px;
    height: 14px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.85) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 0.8rem;
    border-bottom: 2px dashed #E2DAC8;
    margin-bottom: 0.85rem;
}

.panel-title-group {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.panel-title-icon {
    font-size: 1.2rem;
}

.panel-title {
    margin: 0;
    font-size: 1.02rem;
    font-weight: 800;
    color: var(--clay-navy);
}

.mute-chip-btn {
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 2px solid #2B3354;
    background: #FFF;
    color: var(--clay-navy);
    cursor: pointer;
    box-shadow: 0 2px 0 #1D2440;
    transition: transform 0.15s ease, background 0.15s ease;
}

.mute-chip-btn:hover {
    transform: translateY(-1px);
    background: #FEE2E2;
}

.mute-chip-btn.is-active {
    background: #EF4444;
    color: #FFF;
    border-color: #991B1B;
    box-shadow: 0 2px 0 #7F1D1D;
}

/* Setting Cards */
.setting-card {
    background: rgba(255, 255, 255, 0.85);
    border: 2px solid #E8DFCC;
    border-radius: 0.85rem;
    padding: 0.75rem;
    margin-bottom: 0.7rem;
    box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.9), 0 2px 6px rgba(29, 36, 64, 0.04);
}

.setting-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.6rem;
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.setting-emoji {
    font-size: 1.25rem;
}

.setting-name {
    display: block;
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--clay-navy);
    line-height: 1.2;
}

.setting-desc {
    display: block;
    font-size: 0.72rem;
    font-family: var(--font-ui);
    color: #64748B;
    font-weight: 600;
}

/* Bouncy Toggle Switch */
.toggle-switch-btn {
    position: relative;
    width: 44px;
    height: 25px;
    border-radius: 999px;
    border: 2px solid #2B3354;
    background: #CBD5E1;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 2px 0 #1D2440;
    cursor: pointer;
    transition: background 0.25s ease;
    padding: 0;
    outline: none;
}

.toggle-switch-btn.is-on {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.toggle-knob {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 1.5px solid #2B3354;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.25s var(--spring-bounce);
}

.toggle-switch-btn.is-on .toggle-knob {
    transform: translateX(19px);
}

.toggle-switch-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* Test SFX Button */
.test-sfx-btn {
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 800;
    color: #1E40AF;
    background: #DBEAFE;
    border: 1.5px solid #3B82F6;
    box-shadow: 0 2px 0 #1D4ED8;
    cursor: pointer;
    transition: transform 0.15s ease, background 0.15s ease;
}

.test-sfx-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    background: #BFDBFE;
}

.test-sfx-btn:active:not(:disabled) {
    transform: translateY(1px);
    box-shadow: none;
}

.test-sfx-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* Sliders */
.slider-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-family: var(--font-ui);
}

.slider-icon {
    font-size: 0.85rem;
    opacity: 0.65;
}

.sound-range-slider {
    flex: 1;
    -webkit-appearance: none;
    height: 8px;
    border-radius: 999px;
    background: #E2E8F0;
    outline: none;
    border: 1px solid #CBD5E1;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.sound-range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: linear-gradient(145deg, #FFC000 0%, #F59E0B 100%);
    border: 2px solid #2B3354;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.15s var(--spring-bounce);
}

.sound-range-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

.sound-range-slider:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.volume-badge {
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--clay-navy);
    min-width: 32px;
    text-align: right;
}

/* Footer Tip */
.panel-footer {
    padding-top: 0.45rem;
    text-align: center;
}

.footer-tip {
    font-size: 0.72rem;
    color: #64748B;
    font-family: var(--font-ui);
    font-weight: 600;
}

/* Pop Animation */
.sound-panel-pop-enter-active {
    transition: opacity 0.25s ease, transform 0.25s var(--spring-bounce);
}

.sound-panel-pop-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.sound-panel-pop-enter-from {
    opacity: 0;
    transform: translateY(-8px) scale(0.94);
}

.sound-panel-pop-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.96);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .sound-settings-panel {
        right: -0.5rem;
        width: calc(100vw - 2rem);
        max-width: 310px;
    }
}
</style>

