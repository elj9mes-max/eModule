<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { spawnPixarBurst, usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';

const {
    playBonus,
    playClick,
} = useSound();

const { activeWorld } = usePixarTheme();
useWorldOnDocument(activeWorld);

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    classmate: {
        type: Object,
        required: true,
    },
});

const AVATAR_EMOJIS = {
    fox: '🦊',
    astro: '🐱',
    knight: '🐻',
    owl: '🦉',
    bear: '🐼',
    dragon: '🐲',
};

const hasCheered = ref(false);
const cheerToast = ref('');
let toastTimeout = null;

onBeforeUnmount(() => {
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
});

const getAvatarEmoji = (avatarKey) => {
    return AVATAR_EMOJIS[avatarKey] || '🦊';
};

const goBack = () => {
    playClick();
    router.visit('/students/classmates');
};

const cheer = (event) => {
    playBonus();
    if (event?.currentTarget) {
        spawnPixarBurst(event.currentTarget, 18, activeWorld.value);
    }
    hasCheered.value = true;
    cheerToast.value = props.classmate.is_current_user
        ? '🎉 High five to yourself, reading hero!'
        : `🎉 You cheered for ${props.classmate.name.split(' ')[0]}!`;

    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
    toastTimeout = setTimeout(() => {
        cheerToast.value = '';
    }, 2600);
};

const goToClassroom = (roomId) => {
    playClick();
    router.visit(`/students/classes/${roomId}`);
};
</script>

<template>
    <Head :title="`${classmate.name} • Classmate Profile`" />

    <StudentLayout
        :user="user"
        active-nav="classmates"
    >
        <section class="clay-classmate-profile-page" aria-labelledby="profile-hero-name">
            <!-- Navigation Back Bar -->
            <div class="profile-nav-bar">
                <button
                    type="button"
                    class="profile-back-btn"
                    aria-label="Back to Classmates"
                    @click="goBack"
                >
                    <span class="back-btn-face">
                        <span aria-hidden="true">←</span>
                        <span>Classmates</span>
                    </span>
                </button>
            </div>

            <!-- Floating Cheer Toast -->
            <transition name="toast-pop">
                <div v-if="cheerToast" class="cheer-toast-pill" role="status" aria-live="polite">
                    {{ cheerToast }}
                </div>
            </transition>

            <!-- Compact 3D Pixar Profile Hero Banner -->
            <article class="profile-hero-card" :class="{ 'is-me': classmate.is_current_user }">
                <span class="hero-top-gloss" aria-hidden="true"></span>

                <div class="hero-main-row">
                    <!-- 3D Glossy Avatar Halo -->
                    <div class="hero-avatar-halo">
                        <span
                            class="hero-avatar-bubble"
                            :style="{ background: classmate.avatarBg }"
                            aria-hidden="true"
                        >
                            <span class="avatar-specular"></span>
                            <span class="hero-avatar-emoji">{{ getAvatarEmoji(classmate.avatar) }}</span>
                        </span>
                        <span class="hero-lvl-crown">LV {{ classmate.level }}</span>
                    </div>

                    <!-- Identity Meta -->
                    <div class="hero-identity">
                        <div class="hero-badges-inline">
                            <span v-if="classmate.is_current_user" class="hero-you-tag">
                                🌟 YOU
                            </span>
                            <span v-if="classmate.level_badge" class="hero-badge-pill">
                                {{ classmate.level_badge }}
                            </span>
                        </div>
                        <h1 id="profile-hero-name" class="hero-name">{{ classmate.name }}</h1>
                        <p class="hero-title">{{ classmate.level_title }}</p>
                    </div>

                    <!-- Cheer Celebration Button -->
                    <div class="hero-cheer-action">
                        <button
                            type="button"
                            class="clay-cheer-cta"
                            :class="{ 'has-cheered': hasCheered }"
                            :aria-label="`Cheer for ${classmate.name}`"
                            @click="cheer($event)"
                        >
                            <span class="cta-face">
                                <span aria-hidden="true">{{ hasCheered ? '💖' : '🎉' }}</span>
                                <span>{{ hasCheered ? 'Cheered!' : 'High Five' }}</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Integrated Compact Stats Strip -->
                <div class="stats-pedestal-strip" role="list" aria-label="Adventure Statistics">
                    <div class="pedestal-tile stat-stars" role="listitem">
                        <span class="pedestal-icon" aria-hidden="true">⭐</span>
                        <div class="pedestal-copy">
                            <span class="pedestal-value">{{ Number(classmate.stars).toLocaleString() }}</span>
                            <span class="pedestal-label">Stars</span>
                        </div>
                    </div>

                    <div class="pedestal-tile stat-xp" role="listitem">
                        <span class="pedestal-icon" aria-hidden="true">⚡</span>
                        <div class="pedestal-copy">
                            <span class="pedestal-value">{{ Number(classmate.xp).toLocaleString() }}</span>
                            <span class="pedestal-label">Total XP</span>
                        </div>
                    </div>

                    <div class="pedestal-tile stat-quizzes" role="listitem">
                        <span class="pedestal-icon" aria-hidden="true">🏆</span>
                        <div class="pedestal-copy">
                            <span class="pedestal-value">{{ classmate.quizzes_passed }}</span>
                            <span class="pedestal-label">Quizzes</span>
                        </div>
                    </div>

                    <div class="pedestal-tile stat-chapters" role="listitem">
                        <span class="pedestal-icon" aria-hidden="true">📖</span>
                        <div class="pedestal-copy">
                            <span class="pedestal-value">{{ classmate.lectures_completed }}</span>
                            <span class="pedestal-label">Chapters</span>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Compact List of Shared Classrooms -->
            <section class="profile-rooms-section" aria-labelledby="shared-rooms-heading">
                <h2 id="shared-rooms-heading" class="section-title">
                    <span aria-hidden="true">🏫</span>
                    <span>Shared Classrooms</span>
                    <span class="section-count-pill">{{ classmate.shared_classrooms.length }}</span>
                </h2>

                <ul class="shared-rooms-list">
                    <li
                        v-for="room in classmate.shared_classrooms"
                        :key="room.id"
                        class="shared-room-item"
                    >
                        <button
                            type="button"
                            class="shared-room-btn"
                            :aria-label="`Open classroom ${room.name}`"
                            @click="goToClassroom(room.id)"
                        >
                            <span class="shared-room-face">
                                <span class="room-card-icon" aria-hidden="true">
                                    {{ room.icon || '📚' }}
                                </span>
                                <span class="room-card-meta">
                                    <span class="room-card-title">{{ room.name }}</span>
                                    <span v-if="room.grade_level" class="room-card-sub">{{ room.grade_level }}</span>
                                </span>
                                <span class="room-card-enter" aria-hidden="true">
                                    <span>Enter</span>
                                    <span>›</span>
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
            </section>
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   COMPACT 3D PIXAR CLASSMATE PROFILE
   ========================================================================== */
.clay-classmate-profile-page {
    --font-display: var(--px-font-display, 'Fredoka', ui-rounded, 'Nunito', system-ui, sans-serif);
    --font-body: var(--px-font-body, 'Nunito', ui-rounded, system-ui, sans-serif);
    --spring-bounce: var(--px-ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));

    width: 100%;
    max-width: 720px;
    margin: 0 auto;
    padding: 0.25rem 0 2rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    font-family: var(--font-body);
    color: var(--px-ink, #1E1B4B);
}

/* Navigation Back Bar */
.profile-nav-bar {
    display: flex;
    align-items: center;
}

.profile-back-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    margin-bottom: 3px;
    font-family: var(--font-display);
    font-size: 0.84rem;
    font-weight: 800;
    border-radius: 999px;
    outline: none;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.profile-back-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.back-btn-face {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 44px;
    padding: 0.4rem 0.95rem;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-soft, #F1F5F9) 100%);
    border: 2px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink, #1E293B);
    box-shadow:
        0 3px 0 var(--px-ledge, #CBD5E1),
        inset 0 1.5px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    transition: transform 140ms var(--spring-bounce), box-shadow 140ms ease;
}

.profile-back-btn:active .back-btn-face {
    transform: translate3d(0, 2.5px, 0) scaleX(1.035) scaleY(0.95);
    box-shadow: 0 0.5px 0 var(--px-ledge, #CBD5E1);
}

/* Floating Cheer Toast */
.cheer-toast-pill {
    position: fixed;
    top: calc(4.8rem + env(safe-area-inset-top, 0px));
    left: 50%;
    transform: translate3d(-50%, 0, 0);
    background: linear-gradient(180deg, #A78BFA 0%, #7C3AED 55%, #5B21B6 100%);
    color: #FFFFFF;
    border: 2px solid #4C1D95;
    padding: 0.4rem 1rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    white-space: nowrap;
    box-shadow:
        0 4px 0 #3B0764,
        0 10px 24px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.32),
        inset 0 1.5px 1px rgba(255, 255, 255, 0.6);
    z-index: 120;
    pointer-events: none;
}

.toast-pop-enter-active,
.toast-pop-leave-active {
    transition: opacity 220ms ease, transform 240ms var(--spring-bounce);
}

.toast-pop-enter-from,
.toast-pop-leave-to {
    opacity: 0;
    transform: translate3d(-50%, -12px, 0) scale(0.9);
}

/* Compact Hero Showcase Card */
.profile-hero-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFDF9) 0%, var(--px-surface-soft, #F4F0FF) 100%);
    border: 2.5px solid var(--px-card-border, #E2E8F0);
    border-radius: 24px;
    padding: 0.95rem 1rem;
    box-shadow:
        0 5px 0 var(--px-ledge, #CBD5E1),
        0 14px 28px -10px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.18),
        inset 0 2px 0 var(--px-rim-top, rgba(255, 255, 255, 0.95));
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.profile-hero-card.is-me {
    border-color: #8B5CF6;
    box-shadow:
        0 5px 0 #6D28D9,
        0 14px 30px -10px rgba(124, 58, 237, 0.22),
        inset 0 2px 0 rgba(255, 255, 255, 0.95);
}

.hero-top-gloss {
    position: absolute;
    top: 3px;
    left: 20px;
    right: 20px;
    height: 8px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hero-main-row {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.hero-avatar-halo {
    position: relative;
    flex-shrink: 0;
}

.hero-avatar-bubble {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 3px solid var(--px-surface-card, #FFFFFF);
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb, 28, 22, 62), 0.2),
        0 8px 18px rgba(124, 58, 237, 0.22),
        inset 0 2px 4px rgba(255, 255, 255, 0.85);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.avatar-specular {
    position: absolute;
    top: 4px;
    left: 10px;
    width: 18px;
    height: 9px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.72);
    transform: rotate(-22deg);
    pointer-events: none;
}

.hero-avatar-emoji {
    font-size: 2.15rem;
    line-height: 1;
}

.hero-lvl-crown {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: linear-gradient(180deg, #312E81 0%, #0F172A 100%);
    color: #FACC15;
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 800;
    padding: 0.1rem 0.42rem;
    border-radius: 9999px;
    border: 2px solid var(--px-surface-card, #FFFFFF);
    box-shadow: 0 2px 4px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.25);
}

.hero-identity {
    display: flex;
    flex-direction: column;
    gap: 0.12rem;
    flex: 1 1 auto;
    min-width: 0;
}

.hero-badges-inline {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.hero-you-tag {
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    color: #271300;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 900;
    padding: 0.08rem 0.42rem;
    border-radius: 9999px;
    border: 1px solid #B45309;
}

.hero-badge-pill {
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #CBD5E1);
    color: #6D28D9;
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 800;
    padding: 0.08rem 0.45rem;
    border-radius: 9999px;
}

.hero-name {
    font-family: var(--font-display);
    font-size: clamp(1.15rem, 1rem + 0.8vw, 1.45rem);
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    margin: 0;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hero-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    margin: 0;
}

/* Cheer CTA */
.hero-cheer-action {
    flex-shrink: 0;
}

.clay-cheer-cta {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    margin-bottom: 3px;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    border-radius: 14px;
    outline: none;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.clay-cheer-cta:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.clay-cheer-cta .cta-face {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 44px;
    padding: 0.45rem 0.85rem;
    border-radius: 14px;
    background: linear-gradient(180deg, #A78BFA 0%, #7C3AED 52%, #5B21B6 100%);
    border: 2px solid #4C1D95;
    color: #FFFFFF;
    box-shadow:
        0 3.5px 0 #3B0764,
        inset 0 1.5px 1px rgba(255, 255, 255, 0.55);
    transition: transform 140ms var(--spring-bounce), box-shadow 140ms ease;
    white-space: nowrap;
}

.clay-cheer-cta.has-cheered .cta-face {
    background: linear-gradient(180deg, #F472B6 0%, #DB2777 55%, #9D174D 100%);
    border-color: #831843;
    box-shadow:
        0 3.5px 0 #500724,
        inset 0 1.5px 1px rgba(255, 255, 255, 0.55);
}

.clay-cheer-cta:active .cta-face {
    transform: translate3d(0, 3px, 0) scaleX(1.035) scaleY(0.95);
    box-shadow: 0 0.5px 0 #3B0764;
}

/* Integrated Stats Pedestal Strip */
.stats-pedestal-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
}

@media (min-width: 520px) {
    .stats-pedestal-strip {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.pedestal-tile {
    background: var(--px-surface-well, #F8FAFC);
    border: 1.5px solid var(--px-card-border, #E2E8F0);
    box-shadow: inset 0 2px 3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.08);
    border-radius: 14px;
    padding: 0.5rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.pedestal-icon {
    font-size: 1.15rem;
    line-height: 1;
    flex-shrink: 0;
}

.pedestal-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.pedestal-value {
    font-family: var(--font-display);
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    line-height: 1.1;
}

.pedestal-label {
    font-size: 0.58rem;
    font-weight: 800;
    color: var(--px-ink-muted, #94A3B8);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* Section Title */
.section-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    margin: 0 0 0.45rem 0.15rem;
}

.section-count-pill {
    padding: 0.05rem 0.42rem;
    border-radius: 999px;
    background: #EDE9FE;
    border: 1px solid #C4B5FD;
    color: #4C1D95;
    font-size: 0.68rem;
}

/* Compact List of Shared Classrooms */
.shared-rooms-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.shared-room-btn {
    border: none;
    background: transparent;
    padding: 0;
    margin-bottom: 3px;
    width: 100%;
    text-align: left;
    cursor: pointer;
    border-radius: 16px;
    outline: none;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.shared-room-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.shared-room-face {
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-soft, #F8FAFC) 100%);
    border: 2px solid var(--px-card-border, #E2E8F0);
    border-radius: 16px;
    padding: 0.55rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    box-shadow:
        0 3px 0 var(--px-ledge, #CBD5E1),
        inset 0 1.5px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    transition: transform 150ms var(--spring-bounce), box-shadow 150ms ease;
}

@media (hover: hover) and (pointer: fine) {
    .shared-room-btn:hover .shared-room-face {
        transform: translate3d(0, -2px, 0);
        box-shadow:
            0 5px 0 var(--px-ledge, #CBD5E1),
            0 10px 18px -8px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.2);
    }
}

.shared-room-btn:active .shared-room-face {
    transform: translate3d(0, 2px, 0) scaleX(1.01) scaleY(0.97);
    box-shadow: 0 1px 0 var(--px-ledge, #CBD5E1);
}

.room-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    display: grid;
    place-items: center;
    font-size: 1.18rem;
    flex-shrink: 0;
}

.room-card-meta {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.room-card-title {
    font-family: var(--font-display);
    font-size: 0.94rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-card-sub {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
}

.room-card-enter {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    background: #EDE9FE;
    border: 1px solid #C4B5FD;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: #4C1D95;
    flex-shrink: 0;
}

@media (max-width: 480px) {
    .profile-hero-card {
        padding: 0.75rem;
        border-radius: 20px;
    }

    .hero-main-row {
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .hero-avatar-bubble {
        width: 54px;
        height: 54px;
    }

    .hero-avatar-emoji {
        font-size: 1.8rem;
    }

    .hero-cheer-action {
        width: 100%;
    }

    .clay-cheer-cta,
    .clay-cheer-cta .cta-face {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .back-btn-face,
    .clay-cheer-cta .cta-face,
    .shared-room-face,
    .toast-pop-enter-active,
    .toast-pop-leave-active {
        animation: none !important;
        transition: none !important;
    }
}
</style>
