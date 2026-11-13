<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
    classmates: {
        type: Array,
        default: () => [],
    },
    classrooms: {
        type: Array,
        default: () => [],
    },
    active_classroom: {
        type: Object,
        default: null,
    },
});

const { playPop, playSparkle, playBonus, playClick } = useSound();

const activeFilter = ref('all_time');

const filterOptions = [
    { id: 'all_time', label: 'All-Time Champions', icon: '👑' },
    { id: 'weekly', label: 'This Week', icon: '⚡' },
    { id: 'streaks', label: 'Reading Streaks', icon: '🔥' },
];

// Dynamic gamified leaderboards data sourced from database
const leaderboardData = computed(() => {
    if (Array.isArray(props.classmates) && props.classmates.length > 0) {
        return props.classmates.map((c, idx) => ({
            id: c.id,
            rank: c.rank || idx + 1,
            name: c.name,
            avatar: c.avatar || 'fox',
            avatarBg: c.avatarBg || 'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
            level: Number(c.level) || 1,
            xp: Number(c.xp) || 0,
            points: Number(c.points || c.xp) || 0,
            stars: Number(c.stars) || 0,
            streak: Number(c.streak) || 1,
            title: c.title || `Explorer Tier ${c.level || 1}`,
            badge: c.badge || (idx === 0 ? '🥇 Champion' : idx === 1 ? '🥈 Vanguard' : idx === 2 ? '🥉 Explorer' : '🌟 Pioneer'),
            isCurrentUser: Boolean(c.is_current_user),
        }));
    }

    const g = props.user?.gamification || {};
    return [
        {
            id: props.user?.id || 1,
            rank: 1,
            name: props.user?.name || 'You',
            avatar: 'knight',
            avatarBg: 'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
            level: Number(g.level) || 1,
            xp: Number(g.total_xp) || 0,
            points: Number(g.total_xp) || 0,
            stars: Number(g.total_stars) || 0,
            streak: 1,
            title: g.level_title || 'Novice Reader',
            badge: '🥇 Champion',
            isCurrentUser: true,
        },
    ];
});

const topThree = computed(() => {
    return leaderboardData.value.slice(0, 3);
});

const currentUserRank = computed(() => {
    return leaderboardData.value.find((item) => item.isCurrentUser) || leaderboardData.value[2];
});

const pointsToNextRank = computed(() => {
    const current = currentUserRank.value;
    if (current.rank === 1) return 0;
    const ahead = leaderboardData.value.find((item) => item.rank === current.rank - 1);
    if (!ahead) return 0;
    return Math.max(0, ahead.xp - current.xp + 10);
});

const setFilter = (id) => {
    activeFilter.value = id;
    playPop(1.15);
};

const switchClassroom = (classroomId) => {
    playClick();
    router.get('/students/ranking', { class_id: classroomId }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`Explorer Rankings • WEBREAD`" />

    <StudentLayout
        :user="user"
        :level="currentUserRank.level"
        :xp="currentUserRank.xp % 1000"
        :max-xp="1000"
        :points="currentUserRank.points"
        :stars="currentUserRank.stars"
        :streak="currentUserRank.streak"
        active-nav="ranking"
    >
        <section class="clay-ranking-page" aria-labelledby="ranking-heading">

            <!-- CLASSROOM CONTEXT BANNER & ROOM SELECTOR -->
            <div v-if="active_classroom || (classrooms && classrooms.length > 0)" class="clay-room-banner">
                <div class="room-banner-main">
                    <div class="room-icon-pod">
                        <span>{{ active_classroom?.icon || '🏫' }}</span>
                    </div>
                    <div class="room-meta">
                        <div class="room-kicker">CLASSROOM LEADERBOARD</div>
                        <h2 class="room-heading">{{ active_classroom?.name || 'Classroom Room' }}</h2>
                        <div class="room-badges">
                            <span v-if="active_classroom?.grade_level" class="room-badge grade-badge">
                                {{ active_classroom.grade_level }}
                            </span>
                            <span v-if="active_classroom?.teacher?.name" class="room-badge teacher-badge">
                                👨‍🏫 {{ active_classroom.teacher.name }}
                            </span>
                            <span class="room-badge count-badge">
                                👥 {{ leaderboardData.length }} {{ leaderboardData.length === 1 ? 'Classmate' : 'Classmates' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Multi-classroom switch pills if enrolled in > 1 room -->
                <div v-if="classrooms && classrooms.length > 1" class="room-switcher-row">
                    <span class="switcher-label">Your Rooms:</span>
                    <button
                        v-for="c in classrooms"
                        :key="c.id"
                        type="button"
                        class="room-switcher-pill"
                        :class="{ 'is-active': c.id === active_classroom?.id }"
                        @click="switchClassroom(c.id)"
                    >
                        <span>{{ c.icon || '🏫' }}</span>
                        <span>{{ c.name }}</span>
                    </button>
                </div>
            </div>

            <!-- Fallback notice if student has 0 classrooms -->
            <div v-else class="clay-room-empty-banner">
                <div class="empty-icon">🎒</div>
                <div class="empty-text">
                    <strong>Global Explorer Stage:</strong> You haven't joined a classroom yet. Ask your teacher for your 6-digit class code to join your room on your Dashboard!
                </div>
            </div>

            <!-- 2. PODIUM STAGE (TOP 3 EXPLORERS) -->
            <div class="clay-podium-section" aria-label="Top 3 Ranked Explorers">
                <div class="podium-grid">
                    <!-- #2 Silver Podium (Left) -->
                    <div v-if="topThree[1]" class="podium-col rank-second">
                        <div class="podium-avatar-wrap">
                            <span class="podium-medal-crown">🥈</span>
                            <div class="podium-avatar-disc" :style="{ background: topThree[1].avatarBg }">
                                <span class="podium-avatar-emoji">🐱</span>
                            </div>
                            <span class="podium-lvl-tag">LVL {{ topThree[1].level }}</span>
                        </div>
                        <h3 class="podium-player-name">{{ topThree[1].name }}</h3>
                        <span class="podium-score-pill">{{ Number(topThree[1].xp).toLocaleString() }} XP</span>
                        <div class="podium-block block-silver">
                            <span class="podium-step-num">2</span>
                        </div>
                    </div>

                    <!-- #1 Gold Podium (Center) -->
                    <div v-if="topThree[0]" class="podium-col rank-first">
                        <div class="podium-avatar-wrap">
                            <span class="podium-medal-crown gold-crown">👑</span>
                            <div class="podium-avatar-disc gold-glow" :style="{ background: topThree[0].avatarBg }">
                                <span class="podium-avatar-emoji">🦊</span>
                            </div>
                            <span class="podium-lvl-tag gold-lvl">LVL {{ topThree[0].level }}</span>
                        </div>
                        <h3 class="podium-player-name">{{ topThree[0].name }}</h3>
                        <span class="podium-score-pill gold-pill">{{ Number(topThree[0].xp).toLocaleString() }} XP</span>
                        <div class="podium-block block-gold">
                            <span class="podium-step-num">1</span>
                        </div>
                    </div>

                    <!-- #3 Bronze Podium (Right) -->
                    <div v-if="topThree[2]" class="podium-col rank-third">
                        <div class="podium-avatar-wrap">
                            <span class="podium-medal-crown">🥉</span>
                            <div class="podium-avatar-disc" :style="{ background: topThree[2].avatarBg }">
                                <span class="podium-avatar-emoji">🐻</span>
                            </div>
                            <span class="podium-lvl-tag">LVL {{ topThree[2].level }}</span>
                        </div>
                        <h3 class="podium-player-name">
                            {{ topThree[2].name }}
                            <span v-if="topThree[2].isCurrentUser" class="you-badge">(You)</span>
                        </h3>
                        <span class="podium-score-pill">{{ Number(topThree[2].xp).toLocaleString() }} XP</span>
                        <div class="podium-block block-bronze">
                            <span class="podium-step-num">3</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. CURRENT USER POSITION SPOTLIGHT BANNER -->
            <div class="clay-user-spotlight-card" @mouseenter="playSparkle" @click="playBonus">
                <span class="spotlight-gloss" aria-hidden="true"></span>
                <div class="spotlight-left">
                    <span class="spotlight-rank-badge">#{{ currentUserRank.rank }}</span>
                    <div class="spotlight-text">
                        <span class="spotlight-kicker">YOUR CURRENT STANDING</span>
                        <h2 class="spotlight-title">{{ currentUserRank.name }} (You)</h2>
                    </div>
                </div>

                <div class="spotlight-right">
                    <div class="spotlight-stat-box">
                        <span class="stat-label">EXPEDITION XP</span>
                        <span class="stat-value">{{ Number(currentUserRank.xp).toLocaleString() }} XP</span>
                    </div>

                    <div v-if="pointsToNextRank > 0" class="spotlight-gap-pill">
                        <span>🔥 Only <strong>+{{ pointsToNextRank }} XP</strong> to Rank #{{ currentUserRank.rank - 1 }}!</span>
                    </div>
                    <div v-else class="spotlight-gap-pill gold">
                        <span>🏆 You are leading the realm! Keep reading!</span>
                    </div>
                </div>
            </div>

            <!-- 4. FULL EXPLORER LEADERBOARD LIST -->
            <div class="clay-leaderboard-container">
                <div class="leaderboard-table-header">
                    <span class="th-rank">RANK</span>
                    <span class="th-player">EXPLORER</span>
                    <span class="th-streak">STREAK</span>
                    <span class="th-stars">STARS</span>
                    <span class="th-xp">TOTAL XP</span>
                </div>

                <div class="leaderboard-rows-list">
                    <article
                        v-for="player in leaderboardData"
                        :key="player.id"
                        class="clay-leaderboard-row"
                        :class="{ 'is-current-user': player.isCurrentUser }"
                        @mouseenter="playPop(1.2)"
                    >
                        <!-- Rank Number & Ribbon -->
                        <div class="td-rank">
                            <span
                                class="rank-badge-disc"
                                :class="{
                                    'badge-gold': player.rank === 1,
                                    'badge-silver': player.rank === 2,
                                    'badge-bronze': player.rank === 3,
                                }"
                            >
                                {{ player.rank }}
                            </span>
                        </div>

                        <!-- Player Identity -->
                        <div class="td-player">
                            <div class="player-avatar-circle" :style="{ background: player.avatarBg }">
                                <span v-if="player.avatar === 'fox'">🦊</span>
                                <span v-else-if="player.avatar === 'astro'">🐱</span>
                                <span v-else-if="player.avatar === 'knight'">🐻</span>
                                <span v-else>🦉</span>
                            </div>
                            <div class="player-name-col">
                                <span class="player-name-text">
                                    {{ player.name }}
                                    <span v-if="player.isCurrentUser" class="you-indicator">YOU</span>
                                </span>
                                <span class="player-title-text">{{ player.title }} • LVL {{ player.level }}</span>
                            </div>
                        </div>

                        <!-- Streak Flame -->
                        <div class="td-streak">
                            <span class="streak-flame-pill">
                                🔥 {{ player.streak }}d
                            </span>
                        </div>

                        <!-- Stars -->
                        <div class="td-stars">
                            <span class="stars-stat-pill">
                                ⭐ {{ player.stars }}
                            </span>
                        </div>

                        <!-- XP Points Total -->
                        <div class="td-xp">
                            <span class="xp-val-text">{{ Number(player.xp).toLocaleString() }}</span>
                            <span class="xp-lbl-text">XP</span>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   CLAYMORPHIC LEADERBOARD TOKENS & STYLES
   ========================================================================== */
.clay-ranking-page {
    --ink-primary: #1E1B4B;
    --ink-secondary: #475569;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-display: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;

    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding-bottom: 5rem;
}

/* 1. TOP HERO BANNER */
.clay-ranking-hero {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    background: linear-gradient(145deg, #FFFBEB 0%, #FEF3C7 60%, #FDE68A 100%);
    border: 3px solid #FFFFFF;
    box-shadow:
        0 8px 0 #D97706,
        0 18px 36px rgba(217, 119, 6, 0.2),
        inset 0 3px 6px rgba(255, 255, 255, 0.95);
    padding: 1.5rem 1.6rem 1.2rem;
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

.hero-confetti-sparkle {
    position: absolute;
    font-size: 1.4rem;
    pointer-events: none;
    opacity: 0.55;
    animation: sparkleFloat 4s infinite alternate ease-in-out;
}

.c-1 { top: 12%; right: 18%; animation-delay: 0s; }
.c-2 { bottom: 18%; left: 32%; animation-delay: -1.5s; font-size: 1.1rem; }
.c-3 { top: 40%; right: 5%; animation-delay: -2.8s; font-size: 1.6rem; }

@keyframes sparkleFloat {
    0% { transform: translateY(0) rotate(-6deg); }
    100% { transform: translateY(-10px) rotate(8deg); }
}

.hero-top-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}

.ranking-season-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: #92400E;
    background: rgba(255, 255, 255, 0.85);
    padding: 0.22rem 0.65rem;
    border-radius: 9999px;
    border: 1.5px solid #FCD34D;
    box-shadow: 0 2px 0 rgba(146, 64, 14, 0.15);
}

.season-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #F59E0B;
}

.ranking-reset-tag {
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 700;
    color: #B45309;
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
    color: #78350F;
    line-height: 1.15;
}

.hero-description {
    margin: 0.45rem 0 0;
    font-family: var(--font-ui);
    font-size: 0.9rem;
    font-weight: 600;
    color: #92400E;
    max-width: 520px;
    line-height: 1.45;
}

.hero-trophy-pod {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.clay-trophy-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 8px 16px rgba(180, 83, 9, 0.35));
    animation: trophyBob 4s infinite alternate ease-in-out;
}

@keyframes trophyBob {
    0% { transform: translateY(0) rotate(-2deg); }
    100% { transform: translateY(-6px) rotate(2deg); }
}

/* Tabs */
.ranking-filter-tabs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.15rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
}

.clay-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.38rem 0.85rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    background: rgba(255, 255, 255, 0.7);
    color: #78350F;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #D97706;
    transition: transform 0.16s var(--spring-bounce), background 0.16s ease;
    white-space: nowrap;
}

.clay-filter-pill:hover {
    transform: translateY(-2px);
    background: #FFFFFF;
}

.clay-filter-pill.is-active {
    background: linear-gradient(180deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    border-color: #FEF3C7;
    box-shadow: 0 4px 0 #92400E, 0 6px 12px rgba(180, 83, 9, 0.3);
}

/* 2. PODIUM STAGE */
.clay-podium-section {
    padding: 1.25rem 0.5rem 0.5rem;
}

.podium-grid {
    display: grid;
    grid-template-columns: 1fr 1.15fr 1fr;
    gap: 0.75rem;
    align-items: flex-end;
    max-width: 650px;
    margin: 0 auto;
}

.podium-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.podium-avatar-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 0.45rem;
}

.podium-medal-crown {
    position: absolute;
    top: -18px;
    font-size: 1.55rem;
    z-index: 2;
}

.gold-crown {
    font-size: 1.95rem;
    top: -24px;
    animation: crownFloat 2.5s infinite alternate ease-in-out;
}

@keyframes crownFloat {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(-4px) scale(1.08); }
}

.podium-avatar-disc {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3.5px solid #FFFFFF;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.14);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.85rem;
}

.gold-glow {
    width: 74px;
    height: 74px;
    font-size: 2.25rem;
    border-color: #FEF08A;
    box-shadow: 0 0 0 3px #F59E0B, 0 10px 22px rgba(245, 158, 11, 0.35);
}

.podium-lvl-tag {
    position: absolute;
    bottom: -6px;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    color: #1E293B;
    background: #FFFFFF;
    border: 1.5px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.08rem 0.42rem;
    box-shadow: 0 2px 0 #94A3B8;
}

.gold-lvl {
    background: #FEF3C7;
    border-color: #F59E0B;
    color: #92400E;
    box-shadow: 0 2px 0 #D97706;
}

.podium-player-name {
    margin: 0.2rem 0;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--px-stage-ink, var(--ink-primary));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
}

.you-badge {
    color: #059669;
    font-size: 0.76rem;
}

.podium-score-pill {
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 900;
    color: #4338CA;
    background: #EEF2FF;
    border-radius: 9999px;
    padding: 0.15rem 0.55rem;
    margin-bottom: 0.5rem;
    border: 1.5px solid #C7D2FE;
}

.gold-pill {
    background: #FEF3C7;
    color: #B45309;
    border-color: #FCD34D;
}

.podium-block {
    width: 100%;
    border-radius: 20px 20px 6px 6px;
    border: 3px solid #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
}

.podium-step-num {
    font-family: var(--font-display);
    font-size: 2.2rem;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.9);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.block-first { height: 130px; }
.block-second { height: 95px; }
.block-third { height: 75px; }

.block-gold {
    height: 130px;
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    box-shadow: 0 6px 0 #92400E, 0 12px 24px rgba(180, 83, 9, 0.25);
}

.block-silver {
    height: 95px;
    background: linear-gradient(180deg, #E2E8F0 0%, #94A3B8 100%);
    box-shadow: 0 5px 0 #475569, 0 10px 20px rgba(71, 85, 105, 0.2);
}

.block-bronze {
    height: 75px;
    background: linear-gradient(180deg, #FDBA74 0%, #C2410C 100%);
    box-shadow: 0 5px 0 #7C2D12, 0 10px 20px rgba(194, 65, 12, 0.2);
}

/* 3. CURRENT USER SPOTLIGHT BANNER */
.clay-user-spotlight-card {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    background: linear-gradient(145deg, #ECFDF5 0%, #D1FAE5 100%);
    border: 3px solid #FFFFFF;
    box-shadow:
        0 6px 0 #34D399,
        0 14px 28px rgba(16, 185, 129, 0.16),
        inset 0 3px 5px rgba(255, 255, 255, 0.9);
    padding: 1rem 1.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    cursor: pointer;
    transition: transform 0.18s var(--spring-bounce);
}

.clay-user-spotlight-card:hover {
    transform: translateY(-2px);
}

.spotlight-gloss {
    position: absolute;
    top: 4px;
    left: 20px;
    right: 20px;
    height: 10px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.spotlight-left {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.spotlight-rank-badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(145deg, #10B981 0%, #047857 100%);
    border: 2px solid #FFFFFF;
    box-shadow: 0 3px 0 #064E3B;
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spotlight-kicker {
    display: block;
    font-family: var(--font-display);
    font-size: 0.65rem;
    font-weight: 900;
    color: #065F46;
    letter-spacing: 0.04em;
}

.spotlight-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.12rem;
    font-weight: 900;
    color: #064E3B;
}

.spotlight-right {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.spotlight-stat-box {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.stat-label {
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    color: #047857;
}

.stat-value {
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 900;
    color: #064E3B;
}

.spotlight-gap-pill {
    font-family: var(--font-ui);
    font-size: 0.78rem;
    font-weight: 700;
    color: #9A3412;
    background: #FFEDD5;
    border: 1.5px solid #FDBA74;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    white-space: nowrap;
}

.spotlight-gap-pill.gold {
    background: #FEF3C7;
    border-color: #FCD34D;
    color: #92400E;
}

/* 4. FULL LEADERBOARD TABLE */
.clay-leaderboard-container {
    border-radius: 24px;
    background: linear-gradient(176deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-card-bottom, #FFFFFF) 100%);
    border: 2.5px solid var(--px-card-border, #FFFFFF);
    box-shadow:
        0 8px 0 var(--px-hud-ledge, #C7D2FE),
        0 18px 36px rgba(var(--px-shadow-rgb, 49, 46, 129), 0.16),
        inset 0 2px 4px var(--px-rim-top, rgba(255, 255, 255, 0.95));
    padding: 0.85rem 1rem;
}

.leaderboard-table-header {
    display: grid;
    grid-template-columns: 50px 1fr 90px 90px 100px;
    gap: 0.5rem;
    padding: 0.45rem 0.85rem;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-ink-subtle, #64748B);
    letter-spacing: 0.05em;
    border-bottom: 2px dashed rgba(var(--px-shadow-rgb, 148, 163, 184), 0.18);
}

.leaderboard-rows-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    margin-top: 0.55rem;
}

.clay-leaderboard-row {
    display: grid;
    grid-template-columns: 50px 1fr 90px 90px 100px;
    gap: 0.5rem;
    align-items: center;
    padding: 0.65rem 0.85rem;
    border-radius: 18px;
    background: var(--px-surface-well, #F8FAFC);
    border: 2px solid var(--px-card-border, #F1F5F9);
    transition: transform 0.16s var(--spring-bounce), background 0.16s ease, border-color 0.16s ease;
}

.clay-leaderboard-row:hover {
    transform: translateY(-2px);
    background: var(--px-surface-elevated, #F1F5F9);
}

.clay-leaderboard-row.is-current-user {
    background: linear-gradient(145deg, #F0FDF4 0%, #DCFCE7 100%);
    border-color: #86EFAC;
    box-shadow: 0 4px 0 #4ADE80, 0 6px 14px rgba(34, 197, 94, 0.18);
}

.clay-leaderboard-row.is-current-user .player-name-text {
    color: #064E3B;
}

.clay-leaderboard-row.is-current-user .player-title-text {
    color: #047857;
}

.td-rank {
    display: flex;
    align-items: center;
}

.rank-badge-disc {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #E2E8F0;
    color: #475569;
    font-family: var(--font-display);
    font-size: 0.88rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-gold {
    background: linear-gradient(145deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    box-shadow: 0 2px 0 #D97706;
}

.badge-silver {
    background: linear-gradient(145deg, #F1F5F9 0%, #94A3B8 100%);
    color: #1E293B;
    box-shadow: 0 2px 0 #64748B;
}

.badge-bronze {
    background: linear-gradient(145deg, #FFEDD5 0%, #EA580C 100%);
    color: #FFFFFF;
    box-shadow: 0 2px 0 #C2410C;
}

.td-player {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.player-avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 2px solid #FFFFFF;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.player-name-col {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.player-name-text {
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--px-ink, var(--ink-primary));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.you-indicator {
    display: inline-block;
    background: #10B981;
    color: #FFFFFF;
    font-size: 0.58rem;
    font-weight: 900;
    padding: 0.05rem 0.35rem;
    border-radius: 9999px;
    margin-left: 0.3rem;
}

.player-title-text {
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--px-ink-muted, #64748B);
}

.td-streak {
    display: flex;
    align-items: center;
}

.streak-flame-pill {
    font-family: var(--font-display);
    font-size: 0.75rem;
    font-weight: 800;
    color: #C2410C;
    background: #FFF7ED;
    border: 1.5px solid #FDBA74;
    border-radius: 9999px;
    padding: 0.15rem 0.5rem;
    box-shadow: 0 2px 0 #EA580C;
}

.td-stars {
    display: flex;
    align-items: center;
}

.stars-stat-pill {
    font-family: var(--font-display);
    font-size: 0.75rem;
    font-weight: 800;
    color: #B45309;
    background: #FEF3C7;
    border: 1.5px solid #FCD34D;
    border-radius: 9999px;
    padding: 0.15rem 0.5rem;
    box-shadow: 0 2px 0 #F59E0B;
}

.td-xp {
    display: flex;
    align-items: baseline;
    gap: 0.2rem;
    text-align: right;
    justify-content: flex-end;
}

.xp-val-text {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 900;
    color: #1E1B4B;
}

.xp-lbl-text {
    font-family: var(--font-display);
    font-size: 0.65rem;
    font-weight: 800;
    color: #6366F1;
}

/* CLASSROOM CONTEXT BANNER */
.clay-room-banner {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2px solid #E2E8F0;
    border-radius: 20px;
    padding: 1rem 1.25rem;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05), 0 2px 0 #CBD5E1;
    margin-bottom: 0.75rem;
}

.room-banner-main {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.room-icon-pod {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: linear-gradient(145deg, #EEF2FF 0%, #C7D2FE 100%);
    border: 2px solid #818CF8;
    box-shadow: 0 4px 0 #6366F1, inset 0 2px 2px rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.room-meta {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.room-kicker {
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #4F46E5;
    text-transform: uppercase;
}

.room-heading {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 900;
    color: #0F172A;
    line-height: 1.2;
}

.room-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.2rem;
}

.room-badge {
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    border: 1.5px solid transparent;
}

.grade-badge {
    background: #FEF3C7;
    color: #92400E;
    border-color: #FCD34D;
}

.teacher-badge {
    background: #E0E7FF;
    color: #3730A3;
    border-color: #A5B4FC;
}

.count-badge {
    background: #F1F5F9;
    color: #475569;
    border-color: #CBD5E1;
}

.room-switcher-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding-top: 0.6rem;
    border-top: 1px dashed #E2E8F0;
}

.switcher-label {
    font-family: var(--font-ui);
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748B;
}

.room-switcher-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.75rem;
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: #334155;
    background: #F8FAFC;
    border: 1.5px solid #CBD5E1;
    border-radius: 9999px;
    cursor: pointer;
    box-shadow: 0 2px 0 #94A3B8;
    transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.room-switcher-pill:hover {
    transform: translateY(-1px);
    background: #FFFFFF;
    border-color: #818CF8;
}

.room-switcher-pill.is-active {
    background: #4F46E5;
    color: #FFFFFF;
    border-color: #3730A3;
    box-shadow: 0 2px 0 #1E1B4B;
}

.clay-room-empty-banner {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: #FFFBEB;
    border: 2px dashed #FCD34D;
    border-radius: 16px;
    padding: 0.85rem 1.15rem;
    margin-bottom: 0.75rem;
    font-family: var(--font-ui);
    font-size: 0.82rem;
    color: #92400E;
}

.empty-icon {
    font-size: 1.6rem;
    flex-shrink: 0;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .leaderboard-table-header {
        grid-template-columns: 40px 1fr 85px;
    }
    .th-streak, .th-stars {
        display: none;
    }
    .clay-leaderboard-row {
        grid-template-columns: 40px 1fr 85px;
    }
    .td-streak, .td-stars {
        display: none;
    }
    .hero-content-row {
        flex-direction: column-reverse;
        align-items: flex-start;
    }
    .hero-trophy-pod {
        width: 60px;
        height: 60px;
    }
    .clay-user-spotlight-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .spotlight-right {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

