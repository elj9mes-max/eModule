<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { spawnPixarBurst } from '@/Composables/usePixarTheme';
import { shouldReduceEffects } from '@/Composables/usePerformanceMode';

/**
 * Shared Pixar-style reward modal shown after finishing a lecture or submitting a quiz.
 * It only ever displays what the server says was gained, never an estimate.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    /** 'lecture' | 'quiz' */
    kind: { type: String, default: 'lecture' },
    /** 'saving' | 'earned' | 'repeat' | 'pending' | 'review' | 'error' */
    state: { type: String, default: 'earned' },
    itemTitle: { type: String, default: '' },
    playerName: { type: String, default: '' },
    passed: { type: Boolean, default: true },
    /** Stars on this attempt (0–3). Null hides the star row (lectures award no stars). */
    stars: { type: Number, default: null },
    starsGained: { type: Number, default: 0 },
    xpAmount: { type: Number, default: 0 },
    level: { type: Number, default: null },
    totalXp: { type: Number, default: null },
    leveledUp: { type: Boolean, default: false },
    /** @type {Array<{label: string, value: string}>} */
    metrics: { type: Array, default: () => [] },
    message: { type: String, default: '' },
    primaryLabel: { type: String, default: 'Continue ▶' },
    secondaryLabel: { type: String, default: '' },
});

const emit = defineEmits(['close', 'primary', 'secondary']);

const primaryRef = ref(null);
const cardRef = ref(null);
const displayedXp = ref(0);
let countFrame = 0;

const showStars = computed(() => props.kind === 'quiz' && props.stars !== null && !['pending', 'error', 'saving'].includes(props.state));
const showMedal = computed(() => !['pending', 'error'].includes(props.state));
const isCelebrating = computed(() => props.state === 'earned');

const heroArt = computed(() => {
    if (props.state === 'pending') {
        return 'plane';
    }

    return props.kind === 'quiz' ? 'trophy' : 'book';
});

const kicker = computed(() => {
    const isLecture = props.kind === 'lecture';

    return {
        saving: isLecture ? 'STORY COMPLETED!' : 'QUEST SUBMITTED!',
        earned: isLecture ? 'STORY COMPLETED!' : (props.passed ? 'QUEST MASTERED!' : 'QUEST ATTEMPTED'),
        repeat: isLecture ? 'STORY REVIEWED' : 'BEST SCORE KEPT',
        pending: 'SENT TO YOUR TEACHER',
        review: 'YOUR QUEST RESULTS',
        error: 'PROGRESS NOT SAVED',
    }[props.state] || 'NICE WORK!';
});

const heading = computed(() => {
    const name = props.playerName ? `, ${props.playerName.split(' ')[0]}` : '';

    switch (props.state) {
        case 'saving':
            return 'Counting your treasure…';
        case 'earned':
            if (props.kind === 'lecture') {
                return `Awesome Reading${name}!`;
            }

            return props.passed ? `Brilliant Work${name}!` : `Nice Try${name}!`;
        case 'repeat':
            return props.kind === 'lecture' ? 'Great Review!' : 'Good Practice Run!';
        case 'pending':
            return 'Answers Submitted!';
        case 'error':
            return 'Oops! The treasure slipped away';
        default:
            return props.itemTitle || 'Quest Results';
    }
});

const description = computed(() => {
    if (props.message) {
        return props.message;
    }

    switch (props.state) {
        case 'saving':
            return 'Saving your progress to your adventure log…';
        case 'earned':
            return props.kind === 'lecture'
                ? 'You finished every page of this story. Your XP has been added to your adventure total!'
                : 'Your rewards have been added to your adventure total!';
        case 'repeat':
            return props.kind === 'lecture'
                ? 'You already earned the XP for this story, so there is no new XP this time — but every re-read makes you sharper!'
                : 'Your best result is already saved, so this try didn’t add new XP or stars.';
        case 'pending':
            return 'Your teacher will read your answers and award your score, stars and XP soon.';
        case 'error':
            return 'We couldn’t save your progress. Check your connection and try again.';
        default:
            return '';
    }
});

const xpMedalLabel = computed(() => {
    if (props.state === 'review') {
        return 'XP EARNED';
    }

    return props.state === 'repeat' ? 'NEW XP' : 'XP GAINED';
});

const countUpXp = (target) => {
    cancelAnimationFrame(countFrame);

    if (shouldReduceEffects() || target <= 0) {
        displayedXp.value = target;

        return;
    }

    const duration = 1100;
    const startedAt = performance.now();
    displayedXp.value = 0;

    const tick = (now) => {
        const t = Math.min(1, (now - startedAt) / duration);
        displayedXp.value = Math.round(target * (1 - (1 - t) ** 3));
        if (t < 1) {
            countFrame = requestAnimationFrame(tick);
        }
    };

    countFrame = requestAnimationFrame(tick);
};

/** Re-run the reward reveal whenever the modal opens or the server result lands. */
watch(
    () => [props.open, props.state, props.xpAmount],
    async ([isOpen, state], previous) => {
        if (!isOpen) {
            cancelAnimationFrame(countFrame);

            return;
        }

        if (state === 'saving') {
            displayedXp.value = 0;
        } else if (state === 'earned') {
            countUpXp(props.xpAmount);
        } else {
            displayedXp.value = props.xpAmount;
        }

        await nextTick();

        const justOpened = !previous || !previous[0];
        const stateChanged = previous && previous[1] !== state;

        if (state === 'earned' && (justOpened || stateChanged)) {
            spawnPixarBurst(cardRef.value, 20, document.documentElement.dataset.pixarTheme || 'toybox');
        }

        if (justOpened || stateChanged) {
            primaryRef.value?.focus();
        }
    },
    { immediate: true },
);
</script>

<template>
    <Teleport to="body">
        <Transition name="reward-modal" :duration="{ enter: 560, leave: 200 }">
            <div
                v-if="open"
                class="reward-backdrop"
                role="dialog"
                aria-modal="true"
                aria-labelledby="reward-modal-title"
                aria-describedby="reward-modal-desc"
                @click.self="emit('close')"
                @keydown.esc="emit('close')"
            >
                <div ref="cardRef" class="reward-card" :class="[`is-${state}`, `kind-${kind}`, { 'is-failed': kind === 'quiz' && !passed }]">
                    <!-- 3D close key -->
                    <button type="button" class="reward-close" aria-label="Close" @click="emit('close')">
                        <span class="reward-close-face" aria-hidden="true">✕</span>
                    </button>

                    <!-- Hero stage: god-rays, floating loot and the clay hero object -->
                    <div class="reward-stage" aria-hidden="true">
                        <span class="stage-rays"></span>
                        <span class="stage-orb stage-orb--1"></span>
                        <span class="stage-orb stage-orb--2"></span>
                        <span class="stage-orb stage-orb--3"></span>

                        <!-- Storybook -->
                        <svg v-if="heroArt === 'book'" viewBox="0 0 160 112" class="stage-hero">
                            <defs>
                                <linearGradient id="pxRewardBookCover" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#A78BFA" />
                                    <stop offset="100%" stop-color="#5B21B6" />
                                </linearGradient>
                                <radialGradient id="pxRewardPageGlow" cx="50%" cy="20%" r="80%">
                                    <stop offset="0%" stop-color="#FFFBE6" />
                                    <stop offset="100%" stop-color="#FDE7B0" />
                                </radialGradient>
                                <radialGradient id="pxRewardBookLight" cx="50%" cy="100%" r="70%">
                                    <stop offset="0%" stop-color="#FFF7C2" />
                                    <stop offset="100%" stop-color="#FFF7C2" stop-opacity="0" />
                                </radialGradient>
                            </defs>
                            <ellipse cx="80" cy="106" rx="56" ry="5.5" fill="#2A1238" opacity="0.22" />
                            <path d="M40 58 L80 2 L120 58 Z" fill="url(#pxRewardBookLight)" class="book-light" />
                            <path d="M14 46 C40 36 64 38 80 50 C96 38 120 36 146 46 V98 C120 90 96 92 80 102 C64 92 40 90 14 98 Z" fill="url(#pxRewardBookCover)" stroke="#3B0F7A" stroke-width="3" stroke-linejoin="round" />
                            <path d="M20 44 C44 34 66 36 80 48 V96 C66 86 44 84 20 92 Z" fill="url(#pxRewardPageGlow)" stroke="#C99A4A" stroke-width="2" />
                            <path d="M140 44 C116 34 94 36 80 48 V96 C94 86 116 84 140 92 Z" fill="url(#pxRewardPageGlow)" stroke="#C99A4A" stroke-width="2" />
                            <path d="M30 54 C44 49 58 50 70 56 M30 64 C44 59 58 60 70 66 M30 74 C44 69 56 70 66 75" stroke="#D9B26B" stroke-width="2.4" stroke-linecap="round" fill="none" />
                            <path d="M90 56 C102 50 116 49 130 54 M90 66 C102 60 116 59 130 64 M94 75 C104 70 116 69 130 74" stroke="#D9B26B" stroke-width="2.4" stroke-linecap="round" fill="none" />
                            <path d="M80 48 V102" stroke="#B88A3A" stroke-width="2" />
                            <ellipse cx="34" cy="48" rx="8" ry="3" fill="#FFFFFF" opacity="0.6" />
                        </svg>

                        <!-- Trophy -->
                        <svg v-else-if="heroArt === 'trophy'" viewBox="0 0 160 112" class="stage-hero">
                            <defs>
                                <linearGradient id="pxRewardTrophy" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#FFF4B0" />
                                    <stop offset="45%" stop-color="#FCC93A" />
                                    <stop offset="100%" stop-color="#B86E05" />
                                </linearGradient>
                                <linearGradient id="pxRewardPlinth" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#8B5CF6" />
                                    <stop offset="100%" stop-color="#4C1D95" />
                                </linearGradient>
                            </defs>
                            <ellipse cx="80" cy="106" rx="46" ry="5" fill="#2A1238" opacity="0.22" />
                            <path d="M52 22 H34 C30 22 28 26 30 32 C34 46 44 52 56 52" fill="none" stroke="#C47A06" stroke-width="7" stroke-linecap="round" />
                            <path d="M108 22 H126 C130 22 132 26 130 32 C126 46 116 52 104 52" fill="none" stroke="#C47A06" stroke-width="7" stroke-linecap="round" />
                            <path d="M50 10 H110 V40 C110 58 97 70 80 70 C63 70 50 58 50 40 Z" fill="url(#pxRewardTrophy)" stroke="#8A4F05" stroke-width="3" />
                            <ellipse cx="64" cy="26" rx="5" ry="10" fill="#FFFFFF" opacity="0.6" />
                            <path d="M80 26 l4.3 8.7 9.6 1.4-7 6.8 1.7 9.5-8.6-4.5-8.6 4.5 1.7-9.5-7-6.8 9.6-1.4z" fill="#FFF7D1" stroke="#B86E05" stroke-width="1.5" />
                            <rect x="72" y="70" width="16" height="12" fill="#E3A008" stroke="#8A4F05" stroke-width="2" />
                            <rect x="52" y="82" width="56" height="20" rx="6" fill="url(#pxRewardPlinth)" stroke="#2E1065" stroke-width="3" />
                            <rect x="58" y="86" width="20" height="4" rx="2" fill="#FFFFFF" opacity="0.35" />
                        </svg>

                        <!-- Paper plane (sent to teacher) -->
                        <svg v-else viewBox="0 0 160 112" class="stage-hero">
                            <defs>
                                <linearGradient id="pxRewardPlane" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#FFFFFF" />
                                    <stop offset="100%" stop-color="#C7D7FE" />
                                </linearGradient>
                            </defs>
                            <ellipse cx="80" cy="106" rx="40" ry="4.5" fill="#2A1238" opacity="0.18" />
                            <path d="M18 78 C40 70 52 84 70 76" stroke="#FFFFFF" stroke-width="3" stroke-dasharray="2 8" stroke-linecap="round" fill="none" opacity="0.85" />
                            <path d="M66 62 L142 18 L112 92 L96 70 Z" fill="url(#pxRewardPlane)" stroke="#3730A3" stroke-width="3" stroke-linejoin="round" />
                            <path d="M96 70 L142 18 L88 80 Z" fill="#A5B4FC" stroke="#3730A3" stroke-width="3" stroke-linejoin="round" />
                            <path d="M88 80 L96 70 L100 88 Z" fill="#818CF8" stroke="#3730A3" stroke-width="2.5" stroke-linejoin="round" />
                            <path d="M78 58 L124 30" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" opacity="0.8" />
                        </svg>

                        <span v-if="leveledUp && state === 'earned'" class="stage-levelup">LEVEL UP!</span>
                    </div>

                    <span class="reward-kicker">{{ kicker }}</span>

                    <h2 id="reward-modal-title" class="reward-heading">{{ heading }}</h2>

                    <p v-if="itemTitle && state !== 'review'" class="reward-item-title">{{ itemTitle }}</p>

                    <!-- Clay star sockets: earned stars pop in one by one -->
                    <div v-if="showStars" class="reward-stars" :aria-label="`${stars} of 3 stars`">
                        <svg
                            v-for="starIdx in 3"
                            :key="starIdx"
                            viewBox="0 0 40 40"
                            class="reward-star"
                            :class="{ 'is-earned': starIdx <= stars }"
                            :style="{ '--s': starIdx }"
                            aria-hidden="true"
                        >
                            <path
                                d="M20 3.5l5.2 10.6 11.6 1.7-8.4 8.2 2 11.6L20 30.1 9.6 35.6l2-11.6-8.4-8.2 11.6-1.7z"
                                class="star-shape"
                            />
                            <ellipse cx="15" cy="13" rx="3.2" ry="1.8" fill="#FFFFFF" class="star-glint" transform="rotate(-30 15 13)" />
                        </svg>
                    </div>

                    <!-- Gold XP medal (+ new stars chip) -->
                    <div v-if="showMedal" class="reward-medal-row">
                        <div
                            class="reward-xp-medal"
                            :class="{ 'is-muted': state === 'repeat', 'is-saving': state === 'saving' }"
                            :aria-label="state === 'saving' ? 'Calculating XP' : `${xpAmount} ${xpMedalLabel.toLowerCase()}`"
                        >
                            <span class="medal-gloss" aria-hidden="true"></span>
                            <span class="medal-label" aria-hidden="true">{{ xpMedalLabel }}</span>
                            <span class="medal-value" aria-hidden="true">
                                <template v-if="state === 'saving'">…</template>
                                <template v-else>+{{ displayedXp }}<small>XP</small></template>
                            </span>
                        </div>

                        <span v-if="starsGained > 0 && state === 'earned'" class="reward-star-chip">
                            +{{ starsGained }} ⭐ new
                        </span>
                    </div>

                    <!-- Metrics (score, points…) -->
                    <div v-if="metrics.length" class="reward-metrics">
                        <div v-for="metric in metrics" :key="metric.label" class="reward-metric">
                            <span class="metric-label">{{ metric.label }}</span>
                            <strong class="metric-value">{{ metric.value }}</strong>
                        </div>
                    </div>

                    <!-- Level progress -->
                    <div v-if="level !== null && ['earned', 'repeat'].includes(state)" class="reward-level">
                        <span class="level-badge">LV {{ level }}</span>
                        <span class="level-copy">
                            <template v-if="leveledUp">You reached <strong>Level {{ level }}</strong>!</template>
                            <template v-else>Adventure total</template>
                        </span>
                        <strong v-if="totalXp !== null" class="level-total">{{ totalXp.toLocaleString() }} XP</strong>
                    </div>

                    <p id="reward-modal-desc" class="reward-desc">{{ description }}</p>

                    <slot />

                    <div class="reward-actions">
                        <button
                            ref="primaryRef"
                            type="button"
                            class="reward-primary"
                            :disabled="state === 'saving'"
                            @click="emit('primary')"
                        >
                            <span class="reward-primary-face">{{ primaryLabel }}</span>
                        </button>
                        <button v-if="secondaryLabel" type="button" class="reward-secondary" @click="emit('secondary')">
                            {{ secondaryLabel }}
                        </button>
                    </div>

                    <span class="sr-only" aria-live="polite">
                        {{ state === 'saving' ? '' : `${kicker}. ${xpAmount > 0 ? `${xpAmount} XP gained.` : ''} ${leveledUp ? `Level up to level ${level}.` : ''}` }}
                    </span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.reward-backdrop {
    --font-display: 'Fredoka', ui-rounded, 'Nunito', system-ui, sans-serif;
    --font-body: 'Nunito', ui-rounded, system-ui, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    position: fixed;
    inset: 0;
    z-index: 130;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(var(--px-shadow-rgb, 28, 22, 62), 0.58);
    backdrop-filter: blur(8px);
}

.reward-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.55rem;
    width: 100%;
    max-width: 410px;
    max-height: calc(100dvh - 2rem);
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 0 1.25rem 1.35rem;
    border-radius: 34px;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFDF9) 0%, var(--px-surface-soft, #F7F4FF) 100%);
    border: 3px solid var(--px-card-border, #FFFFFF);
    box-shadow:
        0 8px 0 var(--px-ledge, #D2CAEE),
        0 30px 60px -10px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.5),
        inset 0 2px 0 var(--px-rim-top, #FFFFFF);
    text-align: center;
    color: var(--px-ink, #18143B);
    font-family: var(--font-body);
}

/* 3D close key */
.reward-close {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 6;
    width: 42px;
    height: 42px;
    padding: 0;
    border: 0;
    border-radius: 14px;
    background: rgba(24, 20, 59, 0.45);
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.reward-close-face {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: inset 0 2px 0 #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: #2B245C;
    transform: translateY(-3px);
    transition: transform 0.25s var(--spring-bounce);
}

.reward-close:active .reward-close-face {
    transform: translateY(0) scale(0.95, 0.9);
    transition-duration: 0.06s;
}

.reward-close:focus-visible {
    outline: 3px solid #FFFFFF;
    outline-offset: 2px;
}

/* Hero stage */
.reward-stage {
    position: relative;
    flex-shrink: 0;
    display: grid;
    place-items: end center;
    width: calc(100% + 2.5rem);
    height: 172px;
    margin: 0 -1.25rem 0.25rem;
    padding-bottom: 8px;
    overflow: hidden;
    border-radius: 31px 31px 50% 50% / 31px 31px 24px 24px;
    background:
        radial-gradient(circle at 50% 74%, rgba(255, 240, 170, 0.95) 0%, rgba(255, 240, 170, 0) 55%),
        linear-gradient(180deg, #7C3AED 0%, #6366F1 50%, #F59E0B 140%);
    box-shadow: inset 0 -4px 0 rgba(0, 0, 0, 0.08);
}

.kind-lecture .reward-stage {
    background:
        radial-gradient(circle at 50% 74%, rgba(255, 244, 190, 0.95) 0%, rgba(255, 244, 190, 0) 55%),
        linear-gradient(180deg, #0EA5E9 0%, #6366F1 70%, #A855F7 140%);
}

.is-failed .reward-stage,
.is-repeat .reward-stage {
    background:
        radial-gradient(circle at 50% 74%, rgba(220, 240, 255, 0.85) 0%, rgba(220, 240, 255, 0) 55%),
        linear-gradient(180deg, #38BDF8 0%, #6366F1 120%);
}

.is-pending .reward-stage {
    background:
        radial-gradient(circle at 50% 74%, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0) 55%),
        linear-gradient(180deg, #60A5FA 0%, #818CF8 70%, #C084FC 140%);
}

.is-error .reward-stage {
    background:
        radial-gradient(circle at 50% 74%, rgba(255, 220, 230, 0.7) 0%, rgba(255, 220, 230, 0) 55%),
        linear-gradient(180deg, #7C6FB0 0%, #B79AD9 120%);
}

.stage-rays {
    position: absolute;
    top: 66%;
    left: 50%;
    width: 440px;
    height: 440px;
    margin: -220px 0 0 -220px;
    background: repeating-conic-gradient(rgba(255, 255, 255, 0.3) 0deg 9deg, rgba(255, 255, 255, 0) 9deg 24deg);
    -webkit-mask: radial-gradient(circle, #000 18%, transparent 62%);
    mask: radial-gradient(circle, #000 18%, transparent 62%);
    animation: rewardSpin 18s linear infinite;
}

.is-error .stage-rays,
.is-saving .stage-rays {
    display: none;
}

.stage-orb {
    position: absolute;
    border-radius: 50%;
    box-shadow: 0 3px 0 rgba(0, 0, 0, 0.15);
    animation: rewardFloat 3.2s ease-in-out infinite;
}

.stage-orb--1 {
    top: 34px;
    left: 15%;
    width: 22px;
    height: 22px;
    background: radial-gradient(circle at 34% 30%, #FFFBEA 0%, #FDD34A 40%, #C47A06 100%);
}

.stage-orb--2 {
    top: 54px;
    right: 14%;
    width: 16px;
    height: 16px;
    background: radial-gradient(circle at 34% 30%, #FFFFFF 0%, #7DD3FC 40%, #0369A1 100%);
    animation-delay: -1.1s;
}

.stage-orb--3 {
    top: 18px;
    right: 30%;
    width: 12px;
    height: 12px;
    background: radial-gradient(circle at 34% 30%, #FFFFFF 0%, #FDA4AF 40%, #BE123C 100%);
    animation-delay: -2.2s;
}

.is-error .stage-orb {
    display: none;
}

.stage-hero {
    position: relative;
    z-index: 2;
    width: 156px;
    height: 110px;
    overflow: visible;
    transform-origin: 50% 100%;
    animation: rewardHeroPop 0.7s var(--spring-bounce) 0.05s backwards;
}

.is-saving .stage-hero {
    animation: rewardBob 1.2s ease-in-out infinite;
}

.is-error .stage-hero {
    animation: rewardShake 0.5s ease-in-out 0.2s;
}

.is-pending .stage-hero {
    animation: rewardPlane 2.8s ease-in-out infinite;
}

.book-light {
    opacity: 0.9;
}

.stage-levelup {
    position: absolute;
    top: 20px;
    left: 16px;
    z-index: 4;
    padding: 0.3rem 0.75rem;
    border: 3px solid #FFFFFF;
    border-radius: 12px;
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    box-shadow: 0 3px 0 #065F46;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #FFFFFF;
    transform: rotate(-10deg);
    animation: rewardStamp 0.55s var(--spring-bounce) 0.5s backwards;
}

/* Copy */
.reward-kicker {
    padding: 0.28rem 0.85rem;
    border-radius: 9999px;
    background: var(--px-tint-warn-bg, #FEF3C7);
    box-shadow: inset 0 2px 3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.1);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: var(--px-tint-warn-ink, #92400E);
}

.is-repeat .reward-kicker,
.is-review .reward-kicker,
.is-failed .reward-kicker {
    background: var(--px-tint-info-bg, #DBEAFE);
    color: var(--px-tint-info-ink, #1E40AF);
}

.is-pending .reward-kicker {
    background: var(--px-tint-magic-bg, #EDE9FE);
    color: var(--px-tint-magic-ink, #5B21B6);
}

.is-error .reward-kicker {
    background: var(--px-tint-danger-bg, #FEE2E2);
    color: var(--px-tint-danger-ink, #B91C1C);
}

.reward-heading {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.4rem, 1.15rem + 1.4vw, 1.75rem);
    font-weight: 700;
    line-height: 1.12;
    letter-spacing: -0.01em;
    color: var(--px-ink, #18143B);
    overflow-wrap: anywhere;
}

.reward-item-title {
    margin: -0.2rem 0 0;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--px-ink-muted, #4C4670);
    overflow-wrap: anywhere;
}

/* Stars */
.reward-stars {
    display: flex;
    align-items: flex-end;
    gap: 0.35rem;
    margin-top: 0.1rem;
}

.reward-star {
    width: 46px;
    height: 46px;
    overflow: visible;
}

.reward-star:nth-child(2) {
    width: 56px;
    height: 56px;
    margin-bottom: 6px;
}

.star-shape {
    fill: var(--px-surface-well, #EAE4FC);
    stroke: var(--px-line-strong, #C3BBE6);
    stroke-width: 2.5;
    stroke-linejoin: round;
}

.star-glint {
    opacity: 0;
}

.reward-star.is-earned {
    filter: drop-shadow(0 3px 0 #B86E05) drop-shadow(0 6px 8px rgba(252, 201, 58, 0.45));
    animation: rewardStarPop 0.55s var(--spring-bounce) backwards;
    animation-delay: calc(0.35s + var(--s) * 0.18s);
}

.reward-star.is-earned .star-shape {
    fill: #FCC93A;
    stroke: #B86E05;
}

.reward-star.is-earned .star-glint {
    opacity: 0.85;
}

/* XP medal */
.reward-medal-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.15rem;
}

.reward-xp-medal {
    position: relative;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    min-width: 9.5rem;
    padding: 0.4rem 1.3rem 0.55rem;
    overflow: hidden;
    border-radius: 26px;
    background: linear-gradient(180deg, #FFE98A 0%, #FCC93A 55%, #E09A0B 100%);
    box-shadow:
        0 6px 0 #9A5A06,
        0 14px 22px -8px rgba(224, 154, 11, 0.6),
        inset 0 2px 0 rgba(255, 255, 255, 0.75),
        inset 0 -3px 0 rgba(0, 0, 0, 0.1);
    font-family: var(--font-display);
    font-weight: 700;
    color: #4A2300;
    animation: rewardMedalPop 0.6s var(--spring-bounce) 0.2s backwards;
}

.reward-xp-medal.is-muted {
    background: linear-gradient(180deg, #E0F2FE 0%, #BAE6FD 55%, #7DD3FC 100%);
    box-shadow:
        0 6px 0 #0369A1,
        inset 0 2px 0 rgba(255, 255, 255, 0.75),
        inset 0 -3px 0 rgba(0, 0, 0, 0.08);
    color: #0C4A6E;
}

.reward-xp-medal.is-saving .medal-value {
    animation: rewardPulse 1s ease-in-out infinite;
}

.medal-gloss {
    position: absolute;
    top: 3px;
    left: 12%;
    right: 12%;
    height: 38%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.medal-label {
    position: relative;
    font-size: 0.66rem;
    letter-spacing: 0.1em;
    opacity: 0.8;
}

.medal-value {
    position: relative;
    font-size: 2.4rem;
    line-height: 1.05;
    font-variant-numeric: tabular-nums;
}

.medal-value small {
    margin-left: 0.15rem;
    font-size: 1rem;
}

.reward-star-chip {
    padding: 0.4rem 0.8rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFF7D1 0%, #FDE68A 100%);
    box-shadow: 0 3px 0 #D97706, inset 0 2px 0 rgba(255, 255, 255, 0.8);
    font-family: var(--font-display);
    font-size: 0.85rem;
    font-weight: 700;
    color: #78350F;
    animation: rewardMedalPop 0.5s var(--spring-bounce) 0.9s backwards;
}

/* Metrics */
.reward-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(6.5rem, 1fr));
    gap: 0.5rem;
    width: 100%;
    margin-top: 0.3rem;
}

.reward-metric {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    padding: 0.55rem 0.5rem;
    border-radius: 18px;
    background: var(--px-surface-well, #EAE4FC);
    box-shadow: var(--px-well-shadow, inset 0 3px 6px rgba(28, 22, 62, 0.12));
}

.metric-label {
    font-size: 0.64rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: var(--px-ink-subtle, #625C88);
}

.metric-value {
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--px-ink, #18143B);
}

/* Level */
.reward-level {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    width: 100%;
    padding: 0.45rem 0.75rem 0.45rem 0.45rem;
    border-radius: 9999px;
    background: var(--px-surface-card, #FFFFFF);
    box-shadow: 0 3px 0 var(--px-ledge, #D2CAEE), inset 0 2px 0 var(--px-rim-top, #FFFFFF);
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--px-ink-muted, #4C4670);
}

.level-badge {
    flex-shrink: 0;
    padding: 0.3rem 0.6rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, var(--px-secondary-lit, #52ADFF) 0%, var(--px-secondary, #146CE8) 100%);
    box-shadow: 0 2px 0 var(--px-secondary-pedestal, #08347D), inset 0 1.5px 0 rgba(255, 255, 255, 0.5);
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--px-secondary-ink, #FFFFFF);
}

.level-copy {
    flex: 1;
    min-width: 0;
    text-align: left;
}

.level-copy strong,
.level-total {
    font-family: var(--font-display);
    color: var(--px-ink, #18143B);
}

.reward-desc {
    margin: 0.15rem 0 0;
    max-width: 34ch;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--px-ink-muted, #4C4670);
}

/* Actions */
.reward-actions {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    width: 100%;
    margin-top: 0.45rem;
}

.reward-primary {
    width: 100%;
    padding: 0;
    border: 0;
    border-radius: 9999px;
    background: var(--px-secondary-pedestal, #08347D);
    cursor: pointer;
    filter: drop-shadow(0 6px 8px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.3));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.reward-primary:disabled {
    cursor: wait;
    opacity: 0.7;
}

.reward-primary-face {
    display: block;
    min-height: 52px;
    padding: 0.9rem 1.4rem;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-secondary-lit, #52ADFF) 0%, var(--px-secondary, #146CE8) 60%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -3px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 700;
    color: var(--px-secondary-ink, #FFFFFF);
    transform: translateY(-6px);
    transition: transform 0.25s var(--spring-bounce);
}

.reward-primary:not(:disabled):active .reward-primary-face {
    transform: translateY(-2px) scale(0.98, 0.95);
    transition-duration: 0.06s;
}

.reward-primary:focus-visible,
.reward-secondary:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #146CE8);
    outline-offset: 4px;
}

.reward-secondary {
    min-height: 46px;
    border: 0;
    border-radius: 9999px;
    background: transparent;
    cursor: pointer;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--px-ink-muted, #4C4670);
}

@media (hover: hover) {
    .reward-primary:not(:disabled):hover .reward-primary-face {
        transform: translateY(-8px);
    }

    .reward-close:hover .reward-close-face {
        transform: translateY(-5px);
    }

    .reward-secondary:hover {
        color: var(--px-ink, #18143B);
    }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Motion */
.reward-modal-enter-active,
.reward-modal-leave-active {
    transition: opacity 0.2s ease;
}

.reward-modal-enter-from,
.reward-modal-leave-to {
    opacity: 0;
}

.reward-modal-enter-active .reward-card {
    transition: transform 0.56s var(--spring-bounce);
}

.reward-modal-leave-active .reward-card {
    transition: transform 0.2s ease-in;
}

.reward-modal-enter-from .reward-card {
    transform: scale(0.6) translateY(40px);
}

.reward-modal-leave-to .reward-card {
    transform: scale(0.92) translateY(16px);
}

@keyframes rewardSpin {
    to { transform: rotate(360deg); }
}

@keyframes rewardFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@keyframes rewardBob {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-6px) scale(1.03, 0.97); }
}

@keyframes rewardPlane {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(6px, -8px) rotate(-4deg); }
}

@keyframes rewardShake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-8px) rotate(-3deg); }
    40% { transform: translateX(7px) rotate(3deg); }
    60% { transform: translateX(-5px); }
    80% { transform: translateX(3px); }
}

@keyframes rewardHeroPop {
    0% { transform: scale(0.3) translateY(30px); }
    55% { transform: scale(1.12, 0.9); }
    75% { transform: scale(0.95, 1.06); }
    100% { transform: none; }
}

@keyframes rewardMedalPop {
    0% { transform: scale(0.4); opacity: 0; }
    60% { transform: scale(1.12, 0.92); opacity: 1; }
    80% { transform: scale(0.96, 1.05); }
    100% { transform: none; }
}

@keyframes rewardStarPop {
    0% { transform: scale(0) rotate(-40deg); opacity: 0; }
    60% { transform: scale(1.3, 1.1) rotate(8deg); opacity: 1; }
    80% { transform: scale(0.92, 1.06) rotate(-3deg); }
    100% { transform: none; }
}

@keyframes rewardStamp {
    0% { transform: rotate(-10deg) scale(2.2); opacity: 0; }
    100% { transform: rotate(-10deg) scale(1); opacity: 1; }
}

@keyframes rewardPulse {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .reward-backdrop *,
    .reward-backdrop *::before,
    .reward-backdrop *::after {
        animation: none !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
