<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { spawnPixarBurst, usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';

const {
    playPop,
    playSparkle,
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
        spawnPixarBurst(event.currentTarget, 20, activeWorld.value);
    }
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
        :full-width="true"
    >
        <div class="clay-classmate-profile-page">
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
                        <span>Back to Classmates</span>
                    </span>
                </button>
            </div>

            <!-- Profile Hero Showcase Card -->
            <article class="profile-hero-card" :class="{ 'is-me': classmate.is_current_user }">
                <div class="hero-rays" aria-hidden="true"></div>

                <!-- Avatar Halo & Mascot -->
                <div class="hero-avatar-halo">
                    <span
                        class="hero-avatar-bubble"
                        :style="{ background: classmate.avatarBg }"
                        aria-hidden="true"
                    >
                        <span class="hero-avatar-emoji">{{ getAvatarEmoji(classmate.avatar) }}</span>
                    </span>
                    <span class="hero-lvl-crown">LVL {{ classmate.level }}</span>
                </div>

                <!-- Identity Meta -->
                <div class="hero-identity">
                    <span v-if="classmate.is_current_user" class="hero-you-tag">
                        🌟 That's You!
                    </span>
                    <h1 class="hero-name">{{ classmate.name }}</h1>
                    <p class="hero-title">{{ classmate.level_title }}</p>
                    <span v-if="classmate.level_badge" class="hero-badge-pill">
                        {{ classmate.level_badge }}
                    </span>
                </div>

                <!-- Cheer Celebration Button -->
                <div class="hero-cheer-action">
                    <button
                        type="button"
                        class="clay-cheer-cta"
                        :aria-label="`Cheer for ${classmate.name}`"
                        @click="cheer($event)"
                    >
                        <span class="cta-face">
                            <span>🎉</span>
                            <span>Give High Five!</span>
                        </span>
                    </button>
                </div>
            </article>

            <!-- Stats Matrix Showcase -->
            <section class="profile-stats-section" aria-labelledby="stats-heading">
                <h2 id="stats-heading" class="section-title">
                    <span>🏆</span>
                    <span>Adventure Statistics</span>
                </h2>

                <div class="stats-pedestal-grid">
                    <div class="pedestal-card stat-stars">
                        <span class="pedestal-icon" aria-hidden="true">⭐</span>
                        <span class="pedestal-value">{{ Number(classmate.stars).toLocaleString() }}</span>
                        <span class="pedestal-label">Reading Stars</span>
                    </div>

                    <div class="pedestal-card stat-xp">
                        <span class="pedestal-icon" aria-hidden="true">⚡</span>
                        <span class="pedestal-value">{{ Number(classmate.xp).toLocaleString() }}</span>
                        <span class="pedestal-label">Total XP</span>
                    </div>

                    <div class="pedestal-card stat-quizzes">
                        <span class="pedestal-icon" aria-hidden="true">🏆</span>
                        <span class="pedestal-value">{{ classmate.quizzes_passed }}</span>
                        <span class="pedestal-label">Quizzes Passed</span>
                    </div>

                    <div class="pedestal-card stat-chapters">
                        <span class="pedestal-icon" aria-hidden="true">📖</span>
                        <span class="pedestal-value">{{ classmate.lectures_completed }}</span>
                        <span class="pedestal-label">Chapters Read</span>
                    </div>
                </div>
            </section>

            <!-- Shared Classrooms Section -->
            <section class="profile-rooms-section" aria-labelledby="shared-rooms-heading">
                <h2 id="shared-rooms-heading" class="section-title">
                    <span>🏫</span>
                    <span>Shared Classrooms ({{ classmate.shared_classrooms.length }})</span>
                </h2>

                <div class="shared-rooms-grid">
                    <div
                        v-for="room in classmate.shared_classrooms"
                        :key="room.id"
                        class="shared-room-card"
                        @click="goToClassroom(room.id)"
                    >
                        <div class="room-card-icon" aria-hidden="true">
                            {{ room.icon || '📚' }}
                        </div>
                        <div class="room-card-meta">
                            <h3 class="room-card-title">{{ room.name }}</h3>
                            <span v-if="room.grade_level" class="room-card-sub">{{ room.grade_level }}</span>
                        </div>
                        <span class="room-card-arrow" aria-hidden="true">→</span>
                    </div>
                </div>
            </section>
        </div>
    </StudentLayout>
</template>

<style scoped>
.clay-classmate-profile-page {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    padding: 0.75rem 0.85rem 5.5rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
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
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.84rem;
    border-radius: 12px;
    outline: none;
}

.back-btn-face {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.85rem;
    border-radius: 12px;
    background: var(--px-surface-card, #FFFFFF);
    border: 2px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink, #1E293B);
    box-shadow: 0 3px 0 var(--px-card-ledge, #CBD5E1);
    transition: transform 120ms ease, box-shadow 120ms ease;
}

.profile-back-btn:active .back-btn-face {
    transform: translateY(2px);
    box-shadow: 0 1px 0 var(--px-card-ledge, #CBD5E1);
}

/* Hero Showcase Card */
.profile-hero-card {
    position: relative;
    background: var(--px-surface-card, #FFFFFF);
    border: 3px solid var(--px-card-border, #E2E8F0);
    border-radius: 28px;
    padding: 2rem 1.5rem;
    text-align: center;
    box-shadow:
        0 6px 0 var(--px-card-ledge, #CBD5E1),
        0 16px 36px rgba(0, 0, 0, 0.08),
        inset 0 2px 2px rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    overflow: hidden;
}

.profile-hero-card.is-me {
    border-color: #8B5CF6;
    box-shadow:
        0 6px 0 #7C3AED,
        0 18px 40px rgba(124, 58, 237, 0.15);
}

.hero-avatar-halo {
    position: relative;
}

.hero-avatar-bubble {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 4px solid #FFFFFF;
    box-shadow:
        0 6px 0 rgba(0, 0, 0, 0.16),
        0 12px 28px rgba(124, 58, 237, 0.28),
        inset 0 3px 4px rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-avatar-emoji {
    font-size: 3.4rem;
    line-height: 1;
}

.hero-lvl-crown {
    position: absolute;
    bottom: -6px;
    right: -6px;
    background: #0F172A;
    color: #FACC15;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.82rem;
    padding: 0.15rem 0.6rem;
    border-radius: 9999px;
    border: 2.5px solid #FFFFFF;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.hero-identity {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.hero-you-tag {
    background: #F59E0B;
    color: #FFFFFF;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.72rem;
    padding: 0.12rem 0.55rem;
    border-radius: 9999px;
}

.hero-name {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.85rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
    line-height: 1.15;
}

.hero-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    margin: 0;
}

.hero-badge-pill {
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #CBD5E1);
    color: #7C3AED;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.76rem;
    padding: 0.15rem 0.65rem;
    border-radius: 9999px;
}

/* Cheer CTA */
.clay-cheer-cta {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1rem;
    border-radius: 16px;
    outline: none;
}

.clay-cheer-cta .cta-face {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.4rem;
    border-radius: 16px;
    background: linear-gradient(145deg, #8B5CF6 0%, #6D28D9 100%);
    border: 2px solid #5B21B6;
    color: #FFFFFF;
    box-shadow: 0 4px 0 #4C1D95;
    transition: transform 120ms ease, box-shadow 120ms ease;
}

.clay-cheer-cta:active .cta-face {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #4C1D95;
}

/* Section Title */
.section-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.15rem;
    color: var(--px-ink, #0F172A);
    margin: 0 0 0.65rem 0.25rem;
}

/* Stats Pedestal Grid */
.stats-pedestal-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

@media (min-width: 600px) {
    .stats-pedestal-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.pedestal-card {
    background: var(--px-surface-card, #FFFFFF);
    border: 2px solid var(--px-card-border, #E2E8F0);
    border-radius: 18px;
    padding: 1rem 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 4px 0 var(--px-card-ledge, #CBD5E1);
}

.pedestal-icon {
    font-size: 1.45rem;
    margin-bottom: 0.2rem;
}

.pedestal-value {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.35rem;
    color: var(--px-ink, #0F172A);
    line-height: 1.1;
}

.pedestal-label {
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-ink-muted, #94A3B8);
    text-transform: uppercase;
}

/* Shared Classrooms Grid */
.shared-rooms-grid {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.shared-room-card {
    background: var(--px-surface-card, #FFFFFF);
    border: 2px solid var(--px-card-border, #E2E8F0);
    border-radius: 18px;
    padding: 0.85rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    cursor: pointer;
    box-shadow: 0 3px 0 var(--px-card-ledge, #CBD5E1);
    transition: transform 140ms ease, box-shadow 140ms ease;
}

.shared-room-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 var(--px-card-ledge, #CBD5E1);
}

.shared-room-card:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 var(--px-card-ledge, #CBD5E1);
}

.room-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.room-card-meta {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.room-card-title {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.05rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-card-sub {
    font-size: 0.74rem;
    color: var(--px-ink-muted, #64748B);
}

.room-card-arrow {
    font-size: 1.15rem;
    color: #7C3AED;
    font-weight: 800;
}
</style>
