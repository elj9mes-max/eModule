<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '../../Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { spawnPixarBurst } from '@/Composables/usePixarTheme';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student Explorer',
            role: 'student',
        }),
    },
    /** Only the top of the leaderboard (the server never sends lower ranks). */
    classmates: {
        type: Array,
        default: () => [],
    },
    classmatesCount: {
        type: Number,
        default: 0,
    },
    /** The student's own standing; `rank` is null when they are outside the top list. */
    me: {
        type: Object,
        default: null,
    },
    active_classroom: {
        type: Object,
        default: null,
    },
});

const { playPop, playBonus, playClick } = useSound();

const AVATAR_EMOJI = {
    fox: '🦊',
    astro: '🐱',
    knight: '🐻',
    owl: '🦉',
    bear: '🐼',
    dragon: '🐲',
};

const leaders = computed(() =>
    (props.classmates || []).map((c, idx) => ({
        id: c.id,
        rank: c.rank || idx + 1,
        name: c.name,
        emoji: AVATAR_EMOJI[c.avatar] || '🦊',
        avatarBg: c.avatarBg || 'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
        level: Number(c.level) || 1,
        xp: Number(c.xp) || 0,
        stars: Number(c.stars) || 0,
        isCurrentUser: Boolean(c.is_current_user),
    })),
);

/** Podium order left → right: 2nd, 1st, 3rd. */
const podium = computed(() => [leaders.value[1], leaders.value[0], leaders.value[2]].filter(Boolean));
const runnersUp = computed(() => leaders.value.slice(3));

const myStats = computed(() => {
    const g = props.user?.gamification || {};

    return {
        rank: props.me?.rank ?? null,
        inTop: Boolean(props.me?.in_top),
        xp: Number(props.me?.xp ?? g.total_xp ?? 0),
        stars: Number(props.me?.stars ?? g.total_stars ?? 0),
        level: Number(props.me?.level ?? g.level ?? 1),
        xpToTop: Number(props.me?.xp_to_top ?? 0),
    };
});

const podiumClass = (player) => ({ 1: 'is-gold', 2: 'is-silver', 3: 'is-bronze' })[player.rank] || '';

const firstName = (name) => String(name || '').split(' ')[0];

/** Tap a podium avatar: confetti + squash, a tiny celebration of a classmate. */
const cheerFor = (event) => {
    playBonus();
    spawnPixarBurst(event.currentTarget, 14, document.documentElement.dataset.pixarTheme || 'toybox');
};

/** Back to the classroom whose leaderboard is showing (Ranking is no longer a nav tab). */
const backToClassroomUrl = computed(() =>
    props.active_classroom?.id ? `/students/classes/${props.active_classroom.id}` : '/students/classes',
);

const goBackToClassroom = () => {
    playClick();
    router.visit(backToClassroomUrl.value);
};
</script>

<template>
    <Head :title="`Top Explorers • WEBREAD`" />

    <StudentLayout
        :user="user"
        :level="myStats.level"
        :xp="myStats.xp % 1000"
        :max-xp="1000"
        :points="myStats.xp"
        :stars="myStats.stars"
        active-nav="classes"
    >
        <section class="clay-ranking-page" aria-labelledby="ranking-heading">
            <a
                :href="backToClassroomUrl"
                class="ranking-back-btn"
                :aria-label="`Back to ${active_classroom?.name || 'All Classrooms'}`"
                @click.prevent="goBackToClassroom"
            >
                <span class="ranking-back-face">
                    <span aria-hidden="true">←</span>
                    <span class="ranking-back-label">{{ active_classroom?.name || 'All Classrooms' }}</span>
                </span>
            </a>

            <!-- 1. HERO: lit trophy scene -->
            <header class="ranking-hero">
                <span class="hero-rays" aria-hidden="true"></span>
                <span class="hero-confetti hero-confetti--1" aria-hidden="true"></span>
                <span class="hero-confetti hero-confetti--2" aria-hidden="true"></span>
                <span class="hero-confetti hero-confetti--3" aria-hidden="true"></span>
                <span class="hero-confetti hero-confetti--4" aria-hidden="true"></span>

                <svg viewBox="0 0 120 120" class="hero-trophy" aria-hidden="true">
                    <defs>
                        <linearGradient id="pxRankTrophy" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#FFF4B0" />
                            <stop offset="45%" stop-color="#FCC93A" />
                            <stop offset="100%" stop-color="#B86E05" />
                        </linearGradient>
                        <linearGradient id="pxRankPlinth" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#8B5CF6" />
                            <stop offset="100%" stop-color="#4C1D95" />
                        </linearGradient>
                    </defs>
                    <ellipse cx="60" cy="112" rx="34" ry="5" fill="#1E0F3A" opacity="0.3" />
                    <path d="M38 24 H24 C20 24 18 28 20 34 C24 46 32 52 42 52" fill="none" stroke="#C47A06" stroke-width="6" stroke-linecap="round" />
                    <path d="M82 24 H96 C100 24 102 28 100 34 C96 46 88 52 78 52" fill="none" stroke="#C47A06" stroke-width="6" stroke-linecap="round" />
                    <path d="M36 12 H84 V40 C84 56 73 66 60 66 C47 66 36 56 36 40 Z" fill="url(#pxRankTrophy)" stroke="#8A4F05" stroke-width="3" />
                    <ellipse cx="47" cy="26" rx="4.5" ry="9" fill="#FFFFFF" opacity="0.6" />
                    <path d="M60 26 l4 8 8.8 1.3-6.4 6.2 1.5 8.8L60 46.1l-7.9 4.2 1.5-8.8-6.4-6.2 8.8-1.3z" fill="#FFF7D1" stroke="#B86E05" stroke-width="1.5" />
                    <rect x="53" y="66" width="14" height="12" fill="#E3A008" stroke="#8A4F05" stroke-width="2" />
                    <rect x="36" y="78" width="48" height="22" rx="7" fill="url(#pxRankPlinth)" stroke="#2E1065" stroke-width="3" />
                    <rect x="42" y="83" width="16" height="4" rx="2" fill="#FFFFFF" opacity="0.35" />
                </svg>

                <div class="hero-copy">
                    <span class="hero-kicker">{{ active_classroom ? 'CLASS LEADERBOARD' : 'EXPLORER LEADERBOARD' }}</span>
                    <h1 id="ranking-heading" class="hero-title">Top {{ Math.max(leaders.length, 1) }} Explorers</h1>
                    <p class="hero-room">
                        <span aria-hidden="true">{{ active_classroom?.icon || '🌍' }}</span>
                        {{ active_classroom?.name || 'All Explorers' }}
                    </p>
                    <div class="hero-chips">
                        <span v-if="active_classroom?.grade_level" class="hero-chip">🎓 {{ active_classroom.grade_level }}</span>
                        <span v-if="active_classroom?.teacher_name" class="hero-chip">👩‍🏫 {{ active_classroom.teacher_name }}</span>
                        <span class="hero-chip">👥 {{ classmatesCount }} {{ classmatesCount === 1 ? 'explorer' : 'explorers' }}</span>
                    </div>
                </div>
            </header>

            <p v-if="!active_classroom" class="ranking-note">
                🎒 Join a classroom with your teacher's code to see your own class's Top 5!
            </p>

            <!-- 2. PODIUM (top 3) -->
            <ol v-if="podium.length" class="ranking-podium" aria-label="Top 3 explorers">
                <li
                    v-for="player in podium"
                    :key="player.id"
                    class="podium-col"
                    :class="[podiumClass(player), { 'is-me': player.isCurrentUser }]"
                    :style="{ '--rise': player.rank === 1 ? 0 : player.rank === 2 ? 1 : 2 }"
                >
                    <button
                        type="button"
                        class="podium-avatar"
                        :aria-label="`Cheer for ${player.name}, rank ${player.rank}`"
                        @click="cheerFor"
                    >
                        <svg v-if="player.rank === 1" viewBox="0 0 48 30" class="podium-crown" aria-hidden="true">
                            <path d="M4 26 L8 6 L18 16 L24 2 L30 16 L40 6 L44 26 Z" fill="#FCC93A" stroke="#9A5A06" stroke-width="2.5" stroke-linejoin="round" />
                            <circle cx="24" cy="18" r="3" fill="#F43F5E" />
                            <path d="M10 22 H38" stroke="#FFF4B0" stroke-width="2" stroke-linecap="round" opacity="0.8" />
                        </svg>
                        <span class="avatar-sphere" :style="{ background: player.avatarBg }">
                            <span class="avatar-emoji" aria-hidden="true">{{ player.emoji }}</span>
                        </span>
                        <span class="podium-level">LV {{ player.level }}</span>
                    </button>

                    <span class="podium-name">
                        {{ firstName(player.name) }}
                        <span v-if="player.isCurrentUser" class="you-tag">YOU</span>
                    </span>
                    <span class="podium-xp">⚡ {{ player.xp.toLocaleString() }}</span>

                    <span class="podium-block" aria-hidden="true">
                        <span class="podium-block-shine"></span>
                        <span class="podium-number">{{ player.rank }}</span>
                    </span>
                </li>
            </ol>

            <!-- 3. RUNNERS-UP (#4 – #5) -->
            <ol v-if="runnersUp.length" class="ranking-runners" :start="4" aria-label="Explorers ranked 4 and 5">
                <li
                    v-for="(player, idx) in runnersUp"
                    :key="player.id"
                    class="runner-card"
                    :class="{ 'is-me': player.isCurrentUser }"
                    :style="{ '--i': idx }"
                    @mouseenter="playPop(1.2)"
                >
                    <span class="runner-rank">{{ player.rank }}</span>
                    <span class="runner-avatar" :style="{ background: player.avatarBg }" aria-hidden="true">{{ player.emoji }}</span>
                    <span class="runner-identity">
                        <span class="runner-name">
                            {{ player.name }}
                            <span v-if="player.isCurrentUser" class="you-tag">YOU</span>
                        </span>
                        <span class="runner-meta">LV {{ player.level }} • ⭐ {{ player.stars }}</span>
                    </span>
                    <span class="runner-xp">
                        <strong>{{ player.xp.toLocaleString() }}</strong>
                        <small>XP</small>
                    </span>
                </li>
            </ol>

            <p v-if="!leaders.length" class="ranking-note">
                🌱 No explorers have earned XP here yet. Finish a story or quiz to be the first on the board!
            </p>

            <!-- 4. YOUR ADVENTURE: encouraging, never shows a low rank -->
            <section class="my-standing" :class="{ 'is-top': myStats.inTop }" aria-labelledby="my-standing-title">
                <span class="standing-gloss" aria-hidden="true"></span>

                <span class="standing-medal" aria-hidden="true">
                    <svg viewBox="0 0 40 40" class="standing-star">
                        <path d="M20 3.5l5.2 10.6 11.6 1.7-8.4 8.2 2 11.6L20 30.1 9.6 35.6l2-11.6-8.4-8.2 11.6-1.7z" />
                        <ellipse cx="15" cy="13" rx="3.2" ry="1.8" fill="#FFFFFF" opacity="0.85" transform="rotate(-30 15 13)" />
                    </svg>
                </span>

                <div class="standing-copy">
                    <span class="standing-kicker">YOUR ADVENTURE</span>
                    <h2 id="my-standing-title" class="standing-title">
                        <template v-if="myStats.inTop && myStats.rank === 1">You're #1! Champion reader! 👑</template>
                        <template v-else-if="myStats.inTop">You're #{{ myStats.rank }} in the Top 5! 🎉</template>
                        <template v-else>Keep climbing, {{ firstName(user?.name) || 'Explorer' }}! 🚀</template>
                    </h2>
                    <p class="standing-desc">
                        <template v-if="myStats.inTop && myStats.rank === 1">Everyone's chasing you. Keep reading to stay on top!</template>
                        <template v-else-if="myStats.inTop">Every story and quiz pushes you higher up the podium.</template>
                        <template v-else-if="myStats.xpToTop > 0">
                            Earn <strong>+{{ myStats.xpToTop.toLocaleString() }} XP</strong> to join the Top 5. Every story and quiz counts!
                        </template>
                        <template v-else>Finish stories and quizzes to earn XP and join the board!</template>
                    </p>
                </div>

                <div class="standing-stats">
                    <span class="standing-stat">
                        <small>LEVEL</small>
                        <strong>{{ myStats.level }}</strong>
                    </span>
                    <span class="standing-stat">
                        <small>XP</small>
                        <strong>{{ myStats.xp.toLocaleString() }}</strong>
                    </span>
                    <span class="standing-stat">
                        <small>STARS</small>
                        <strong>⭐ {{ myStats.stars }}</strong>
                    </span>
                </div>
            </section>
        </section>
    </StudentLayout>
</template>

<style scoped>
.clay-ranking-page {
    --font-display: 'Fredoka', ui-rounded, 'Nunito', system-ui, sans-serif;
    --font-body: 'Nunito', ui-rounded, system-ui, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
    max-width: 640px;
    margin: 0 auto;
    padding-bottom: 1.5rem;
    font-family: var(--font-body);
    color: var(--px-ink);
}

/* ---------- Back key ---------- */
.ranking-back-btn {
    align-self: flex-start;
    display: inline-flex;
    max-width: 100%;
    border-radius: 999px;
    background: var(--px-ledge);
    text-decoration: none;
    filter: drop-shadow(0 4px 5px rgba(var(--px-shadow-rgb), 0.22));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.ranking-back-face {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-width: 0;
    min-height: 42px;
    padding: 0 1rem;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    box-shadow: inset 0 2px 0 var(--px-rim-top);
    font-family: var(--font-display);
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--px-ink);
    transform: translateY(-4px);
    transition: transform 0.25s var(--spring-bounce);
}

.ranking-back-label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ranking-back-btn:active .ranking-back-face {
    transform: translateY(-1px) scale(0.98, 0.94);
    transition-duration: 0.06s;
}

.ranking-back-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

/* ---------- Hero ---------- */
.ranking-hero {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    min-height: 150px;
    padding: 1rem 1.1rem;
    overflow: hidden;
    border-radius: 30px;
    background:
        radial-gradient(circle at 18% 70%, rgba(255, 236, 150, 0.85) 0%, rgba(255, 236, 150, 0) 45%),
        var(--px-hero-banner-grad, linear-gradient(135deg, #6366F1 0%, #8B5CF6 55%, #EC4899 100%));
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 var(--px-hero-banner-ledge, #312E81),
        0 18px 30px -12px rgba(var(--px-shadow-rgb), 0.5),
        inset 0 2px 0 rgba(255, 255, 255, 0.45);
    color: #FFFFFF;
    animation: rankPop 0.6s var(--spring-bounce) backwards;
}

.hero-rays {
    position: absolute;
    top: 50%;
    left: 70px;
    width: 360px;
    height: 360px;
    margin: -180px 0 0 -180px;
    background: repeating-conic-gradient(rgba(255, 255, 255, 0.2) 0deg 9deg, rgba(255, 255, 255, 0) 9deg 24deg);
    -webkit-mask: radial-gradient(circle, #000 15%, transparent 60%);
    mask: radial-gradient(circle, #000 15%, transparent 60%);
    pointer-events: none;
    animation: rankSpin 20s linear infinite;
}

.hero-confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    border-radius: 3px;
    pointer-events: none;
    animation: rankFloat 3.4s ease-in-out infinite;
}

.hero-confetti--1 { top: 14px; right: 18%; background: #FDE047; transform: rotate(20deg); }
.hero-confetti--2 { top: 62%; right: 6%; background: #34D399; border-radius: 50%; animation-delay: -1s; }
.hero-confetti--3 { top: 18%; right: 40%; width: 7px; height: 7px; background: #F472B6; animation-delay: -2s; }
.hero-confetti--4 { bottom: 12px; right: 30%; width: 8px; height: 8px; background: #7DD3FC; border-radius: 50%; animation-delay: -0.5s; }

.hero-trophy {
    position: relative;
    z-index: 1;
    flex-shrink: 0;
    width: 96px;
    height: 96px;
    filter: drop-shadow(0 6px 10px rgba(30, 15, 58, 0.35));
    animation: rankBob 3.6s ease-in-out infinite;
}

.hero-copy {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.hero-kicker {
    align-self: flex-start;
    padding: 0.18rem 0.6rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.hero-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.6rem, 1.2rem + 2.4vw, 2.2rem);
    font-weight: 700;
    line-height: 1.05;
    letter-spacing: -0.01em;
    text-shadow: 0 3px 0 rgba(30, 15, 58, 0.35);
}

.hero-room {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-top: 0.15rem;
}

.hero-chip {
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 2px 0 rgba(30, 15, 58, 0.25);
    font-size: 0.72rem;
    font-weight: 800;
    color: #2B245C;
    white-space: nowrap;
}

.ranking-note {
    margin: 0;
    padding: 0.75rem 1rem;
    border-radius: 20px;
    background: var(--px-surface-card);
    box-shadow: 0 3px 0 var(--px-ledge), inset 0 2px 0 var(--px-rim-top);
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    text-align: center;
}

/* ---------- Podium ---------- */
.ranking-podium {
    display: grid;
    grid-template-columns: 1fr 1.15fr 1fr;
    align-items: end;
    gap: 0.5rem;
    margin: 0.4rem 0 0;
    padding: 1.2rem 0.6rem 0;
    list-style: none;
    border-radius: 30px;
    background:
        radial-gradient(ellipse at 50% 100%, color-mix(in srgb, var(--px-accent) 30%, transparent) 0%, transparent 65%),
        linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 var(--px-ledge),
        0 16px 26px -12px rgba(var(--px-shadow-rgb), 0.4),
        inset 0 2px 0 var(--px-rim-top);
    overflow: hidden;
}

.podium-col {
    --medal: #CBD5F5;
    --medal-dark: #7C86B8;
    --block-top: #E4E8FB;
    --block-mid: #B9C1EC;
    --block-edge: #8590C8;
    --block-height: 78px;

    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    min-width: 0;
    text-align: center;
    animation: rankPop 0.6s var(--spring-bounce) backwards;
    animation-delay: calc(0.15s + var(--rise) * 0.12s);
}

.podium-col.is-gold {
    --medal: #FCC93A;
    --medal-dark: #B86E05;
    --block-top: #FFF1A6;
    --block-mid: #FCC93A;
    --block-edge: #B86E05;
    --block-height: 112px;
}

.podium-col.is-bronze {
    --medal: #F0A36B;
    --medal-dark: #9A4A12;
    --block-top: #FFD9B8;
    --block-mid: #E8915A;
    --block-edge: #9A4A12;
    --block-height: 60px;
}

.podium-avatar {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    transition: transform 0.3s var(--spring-bounce);
}

.podium-avatar:active {
    transform: scale(1.08, 0.9);
    transition-duration: 0.08s;
}

.podium-avatar:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
    border-radius: 50%;
}

.podium-crown {
    position: absolute;
    top: -22px;
    z-index: 2;
    width: 40px;
    height: 26px;
    filter: drop-shadow(0 3px 2px rgba(154, 90, 6, 0.4));
    animation: crownBob 2.4s ease-in-out infinite;
}

.avatar-sphere {
    position: relative;
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 4px solid var(--medal);
    box-shadow:
        0 4px 0 var(--medal-dark),
        0 10px 14px -6px rgba(var(--px-shadow-rgb), 0.5),
        inset 0 -6px 10px rgba(0, 0, 0, 0.12),
        inset 0 4px 6px rgba(255, 255, 255, 0.6);
}

.is-gold .avatar-sphere {
    width: 78px;
    height: 78px;
}

.avatar-emoji {
    font-size: 2rem;
    line-height: 1;
    filter: drop-shadow(0 2px 0 rgba(0, 0, 0, 0.15));
}

.is-gold .avatar-emoji {
    font-size: 2.4rem;
}

.podium-level {
    margin-top: -10px;
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    box-shadow: 0 2px 0 var(--px-secondary-pedestal);
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 700;
    color: var(--px-secondary-ink);
}

.podium-name {
    display: inline-flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 0.2rem;
    max-width: 100%;
    margin-top: 0.15rem;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.15;
    color: var(--px-ink);
    overflow-wrap: anywhere;
}

.podium-xp {
    padding: 0.18rem 0.55rem;
    border-radius: 999px;
    background: linear-gradient(180deg, #FFE98A 0%, #FBBF24 100%);
    box-shadow: 0 2px 0 #B45309, inset 0 1.5px 0 rgba(255, 255, 255, 0.7);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 700;
    color: #5A2D00;
    white-space: nowrap;
}

/* Clay podium steps: lit top face, front face, dark edge */
.podium-block {
    position: relative;
    display: grid;
    place-items: center;
    width: 100%;
    height: var(--block-height);
    margin-top: 0.4rem;
    overflow: hidden;
    border-radius: 18px 18px 0 0;
    background: linear-gradient(180deg, var(--block-top) 0%, var(--block-mid) 30%, var(--block-mid) 70%, var(--block-edge) 100%);
    box-shadow:
        inset 0 3px 0 rgba(255, 255, 255, 0.7),
        inset 0 -6px 0 rgba(0, 0, 0, 0.12);
    transform-origin: 50% 100%;
    animation: blockRise 0.7s var(--spring-bounce) backwards;
    animation-delay: calc(0.1s + var(--rise) * 0.12s);
}

.podium-block-shine {
    position: absolute;
    top: 0;
    left: -60%;
    width: 40%;
    height: 100%;
    background: linear-gradient(100deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.55) 50%, rgba(255, 255, 255, 0) 100%);
    transform: skewX(-12deg);
}

.is-gold .podium-block-shine {
    animation: blockShine 3.2s ease-in-out 1s infinite;
}

.podium-number {
    position: relative;
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 700;
    color: #FFFFFF;
    text-shadow: 0 3px 0 var(--block-edge);
}

.is-gold .podium-number {
    font-size: 2.6rem;
}

.you-tag {
    padding: 0.08rem 0.4rem;
    border-radius: 999px;
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    box-shadow: 0 2px 0 #065F46;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    color: #FFFFFF;
}

.podium-col.is-me .avatar-sphere {
    outline: 3px dashed #34D399;
    outline-offset: 4px;
}

/* ---------- Runners-up ---------- */
.ranking-runners {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.runner-card {
    display: grid;
    grid-template-columns: auto auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0.85rem 0.65rem 0.65rem;
    border-radius: 24px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 5px 0 var(--px-ledge),
        0 12px 20px -12px rgba(var(--px-shadow-rgb), 0.4),
        inset 0 2px 0 var(--px-rim-top);
    transition: transform 0.3s var(--spring-bounce);
    animation: rankPop 0.55s var(--spring-bounce) backwards;
    animation-delay: calc(0.55s + var(--i) * 0.08s);
}

.runner-card.is-me {
    background: linear-gradient(180deg, var(--px-surface-card) 0%, color-mix(in srgb, var(--px-tint-success-bg) 60%, var(--px-surface-card)) 100%);
    box-shadow:
        0 5px 0 #059669,
        0 12px 20px -10px rgba(16, 185, 129, 0.45),
        inset 0 2px 0 var(--px-rim-top);
}

@media (hover: hover) {
    .runner-card:hover {
        transform: translateY(-4px) rotate(-0.4deg);
    }
}

.runner-rank {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, var(--px-secondary-lit) 30%, var(--px-secondary) 100%);
    box-shadow: 0 3px 0 var(--px-secondary-pedestal);
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--px-secondary-ink);
}

.runner-avatar {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 3px solid var(--px-surface-card);
    box-shadow: 0 3px 0 var(--px-ledge), inset 0 -4px 6px rgba(0, 0, 0, 0.1);
    font-size: 1.5rem;
}

.runner-identity {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
}

.runner-name {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    min-width: 0;
    font-family: var(--font-display);
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--px-ink);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.runner-meta {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.runner-xp {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    line-height: 1.05;
    font-family: var(--font-display);
}

.runner-xp strong {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--px-ink);
}

.runner-xp small {
    font-size: 0.66rem;
    font-weight: 700;
    color: var(--px-ink-subtle);
}

/* ---------- Your adventure ---------- */
.my-standing {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.6rem 0.8rem;
    align-items: center;
    padding: 1rem 1rem 1.05rem;
    border-radius: 28px;
    background: linear-gradient(170deg, var(--px-surface-card) 0%, color-mix(in srgb, var(--px-tint-info-bg) 60%, var(--px-surface-card)) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 color-mix(in srgb, var(--px-secondary) 40%, var(--px-ledge)),
        0 16px 26px -12px rgba(var(--px-shadow-rgb), 0.4),
        inset 0 2px 0 var(--px-rim-top);
    animation: rankPop 0.6s var(--spring-bounce) 0.7s backwards;
}

.my-standing.is-top {
    background: linear-gradient(170deg, var(--px-surface-card) 0%, color-mix(in srgb, var(--px-tint-warn-bg) 70%, var(--px-surface-card)) 100%);
    box-shadow:
        0 6px 0 #D97706,
        0 16px 26px -10px rgba(245, 158, 11, 0.5),
        inset 0 2px 0 var(--px-rim-top);
}

.standing-gloss {
    position: absolute;
    top: 3px;
    left: 18px;
    right: 18px;
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, calc(0.9 * var(--px-gloss, 1))) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.standing-medal {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, var(--px-secondary-lit) 30%, var(--px-secondary) 70%, var(--px-secondary-pedestal) 100%);
    border: 4px solid var(--px-surface-card);
    box-shadow: 0 4px 0 var(--px-secondary-pedestal), 0 10px 14px -6px rgba(var(--px-shadow-rgb), 0.45);
    animation: rankBob 3s ease-in-out infinite;
}

.is-top .standing-medal {
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #FFE98A 30%, #FCC93A 70%, #B86E05 100%);
    box-shadow: 0 4px 0 #9A5A06, 0 10px 14px -6px rgba(245, 158, 11, 0.55);
}

.standing-star {
    width: 30px;
    height: 30px;
    fill: #FFFFFF;
    filter: drop-shadow(0 2px 0 rgba(0, 0, 0, 0.18));
}

.is-top .standing-star {
    fill: #FFF7D1;
}

.standing-copy {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.standing-kicker {
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: var(--px-ink-subtle);
}

.standing-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--px-ink);
}

.standing-desc {
    margin: 0;
    font-size: 0.84rem;
    font-weight: 600;
    line-height: 1.45;
    color: var(--px-ink-muted);
}

.standing-desc strong {
    color: var(--px-tint-warn-ink);
}

.standing-stats {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.45rem;
}

.standing-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.05rem;
    padding: 0.5rem 0.3rem;
    border-radius: 18px;
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
}

.standing-stat small {
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: var(--px-ink-subtle);
}

.standing-stat strong {
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--px-ink);
    white-space: nowrap;
}

/* ---------- Motion ---------- */
@keyframes rankPop {
    0% { transform: scale(0.7); opacity: 0; }
    60% { transform: scale(1.04, 0.96); opacity: 1; }
    80% { transform: scale(0.99, 1.02); }
    100% { transform: none; }
}

@keyframes blockRise {
    0% { transform: scaleY(0); }
    65% { transform: scaleY(1.08); }
    100% { transform: scaleY(1); }
}

@keyframes blockShine {
    0%, 60% { transform: translateX(0) skewX(-12deg); }
    100% { transform: translateX(420%) skewX(-12deg); }
}

@keyframes crownBob {
    0%, 100% { transform: translateY(0) rotate(-4deg); }
    50% { transform: translateY(-4px) rotate(4deg); }
}

@keyframes rankBob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

@keyframes rankFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(40deg); }
}

@keyframes rankSpin {
    to { transform: rotate(360deg); }
}

/* ---------- Responsive ---------- */
@media (max-width: 400px) {
    .ranking-hero {
        gap: 0.6rem;
        padding: 0.9rem;
    }

    .hero-trophy {
        width: 74px;
        height: 74px;
    }

    .ranking-podium {
        gap: 0.35rem;
        padding: 1.1rem 0.4rem 0;
    }

    .avatar-sphere {
        width: 54px;
        height: 54px;
    }

    .is-gold .avatar-sphere {
        width: 66px;
        height: 66px;
    }

    .avatar-emoji {
        font-size: 1.7rem;
    }

    .is-gold .avatar-emoji {
        font-size: 2rem;
    }

    .podium-name {
        font-size: 0.82rem;
    }

    .podium-xp {
        font-size: 0.66rem;
        padding: 0.15rem 0.4rem;
    }

    .runner-card {
        gap: 0.5rem;
        padding: 0.55rem 0.7rem 0.55rem 0.55rem;
    }

    .runner-rank {
        width: 32px;
        height: 32px;
        font-size: 0.95rem;
    }

    .runner-avatar {
        width: 38px;
        height: 38px;
        font-size: 1.25rem;
    }

    .standing-medal {
        width: 48px;
        height: 48px;
    }
}

@media (min-width: 640px) {
    .ranking-hero {
        min-height: 180px;
        padding: 1.3rem 1.6rem;
        gap: 1.4rem;
    }

    .hero-trophy {
        width: 124px;
        height: 124px;
    }

    .ranking-podium {
        gap: 0.9rem;
        padding: 1.6rem 1.4rem 0;
    }

    .podium-col.is-gold {
        --block-height: 132px;
    }

    .podium-col {
        --block-height: 96px;
    }

    .podium-col.is-bronze {
        --block-height: 74px;
    }

    .my-standing {
        grid-template-columns: auto minmax(0, 1fr) auto;
        padding: 1.2rem 1.4rem;
    }

    .standing-stats {
        grid-column: auto;
        grid-template-columns: repeat(3, 5.2rem);
    }
}

@media (prefers-reduced-motion: reduce) {
    .clay-ranking-page *,
    .clay-ranking-page *::before,
    .clay-ranking-page *::after,
    .clay-ranking-page {
        animation: none !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
