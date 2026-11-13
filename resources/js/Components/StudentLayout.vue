<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useSound } from '@/Services/soundManager';
import { flushAllClientCache, prefetchRoute } from '@/Composables/usePrefetch';
import { usePixarTheme } from '@/Composables/usePixarTheme';

const { playPop, playBoing, playSparkle, playClick, playBonus } = useSound();
const { activeWorld } = usePixarTheme();

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
    level: {
        type: Number,
        default: 5,
    },
    xp: {
        type: Number,
        default: 680,
    },
    maxXp: {
        type: Number,
        default: 1000,
    },
    points: {
        type: Number,
        default: 1450,
    },
    stars: {
        type: Number,
        default: 28,
    },
    streak: {
        type: Number,
        default: 3,
    },
    activeNav: {
        type: String,
        default: 'modules',
    },
});

const avatarProfiles = [
    {
        id: 'fox',
        label: 'Ember Fox',
        bg1: '#FFF7ED',
        bg2: '#FDBA74',
        border: '#EA580C',
        shadow: '#C2410C',
        chipBg: '#FFEDD5',
        chipText: '#9A3412',
    },
    {
        id: 'astro',
        label: 'Astro Cat',
        bg1: '#EFF6FF',
        bg2: '#93C5FD',
        border: '#2563EB',
        shadow: '#1D4ED8',
        chipBg: '#DBEAFE',
        chipText: '#1E40AF',
    },
    {
        id: 'knight',
        label: 'Sprout Bear',
        bg1: '#ECFDF5',
        bg2: '#6EE7B7',
        border: '#059669',
        shadow: '#047857',
        chipBg: '#D1FAE5',
        chipText: '#065F46',
    },
    {
        id: 'owl',
        label: 'Mystic Owl',
        bg1: '#F5F3FF',
        bg2: '#C4B5FD',
        border: '#7C3AED',
        shadow: '#5B21B6',
        chipBg: '#EDE9FE',
        chipText: '#5B21B6',
    },
];

const selectedAvatarIndex = ref(0);
const isAvatarBouncing = ref(false);
const currentAvatar = computed(() => avatarProfiles[selectedAvatarIndex.value]);

const cycleAvatar = () => {
    selectedAvatarIndex.value = (selectedAvatarIndex.value + 1) % avatarProfiles.length;
    isAvatarBouncing.value = true;
    playBoing();
    window.setTimeout(() => {
        isAvatarBouncing.value = false;
    }, 320);
};

const effectiveLevel = computed(() => props.user?.gamification?.level ?? props.level ?? 1);
const effectiveXp = computed(() => props.user?.gamification?.xp_in_level ?? props.xp ?? 0);
const effectiveMaxXp = computed(() => props.user?.gamification?.xp_for_next_level ?? props.maxXp ?? 200);
const effectiveStars = computed(() => props.user?.gamification?.total_stars ?? props.stars ?? 0);
const effectivePoints = computed(() => props.user?.gamification?.total_xp ?? props.points ?? 0);
const effectiveTitle = computed(() => props.user?.gamification?.level_title ?? `Level ${effectiveLevel.value} Explorer`);

const xpPercentage = computed(() => {
    if (!effectiveMaxXp.value || effectiveMaxXp.value <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((effectiveXp.value / effectiveMaxXp.value) * 100)));
});

const ringCircumference = 2 * Math.PI * 28;
const ringDashOffset = computed(() => {
    return ringCircumference - (xpPercentage.value / 100) * ringCircumference;
});

const formattedPoints = computed(() => Number(effectivePoints.value || 0).toLocaleString());
const formattedStars = computed(() => Number(effectiveStars.value || 0).toLocaleString());
const formattedXp = computed(() => Number(effectiveXp.value || 0).toLocaleString());
const formattedMaxXp = computed(() => Number(effectiveMaxXp.value || 200).toLocaleString());

const goToModules = () => {
    playClick();
    router.visit('/students/dashboard');
};

const goToRanking = () => {
    playClick();
    router.visit('/students/ranking');
};

const goToSettings = () => {
    playClick();
    router.visit('/students/settings');
};

const goToWelcome = () => {
    playClick();
    router.visit('/students/welcome');
};

const handleLogout = () => {
    playClick();
    flushAllClientCache();
    router.post('/logout');
};
</script>

<template>
    <div class="clay-student-shell" :data-pixar-theme="activeWorld">
        <!-- 5-Plane Pixar Atmospheric World Backdrop -->
        <div class="clay-sky-backdrop" aria-hidden="true">
            <div class="clay-sun-bloom"></div>
            <div class="clay-bounce-bloom"></div>

            <!-- Drifting Volumetric Clouds -->
            <svg class="px-shell-cloud px-shell-cloud--1" viewBox="0 0 260 110" fill="none">
                <defs>
                    <linearGradient id="pxShellCloud" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.92" />
                        <stop offset="100%" stop-color="#D9EEFF" stop-opacity="0.65" />
                    </linearGradient>
                </defs>
                <path
                    d="M44 92h170c22 0 38-15 38-34 0-17-12-31-29-34-6-15-21-24-39-24-15 0-28 7-36 18-7-4-16-7-25-7-22 0-40 16-43 37-19 2-32 16-32 32 0 7 3 12 10 12h86Z"
                    fill="url(#pxShellCloud)"
                />
            </svg>
            <svg class="px-shell-cloud px-shell-cloud--2" viewBox="0 0 220 96" fill="none">
                <path
                    d="M36 80h148c19 0 32-13 32-29 0-14-10-26-25-29-6-13-18-21-33-21-13 0-24 6-31 16-6-4-13-6-21-6-19 0-34 14-36 32-16 2-28 13-28 27 0 6 3 10 9 10h85Z"
                    fill="url(#pxShellCloud)"
                />
            </svg>

            <!-- Drifting Volumetric Memory Orbs & Sparkles -->
            <span class="px-shell-orb px-shell-orb--1"></span>
            <span class="px-shell-orb px-shell-orb--2"></span>
            <span class="px-shell-orb px-shell-orb--3"></span>
            <span class="px-shell-sparkle px-shell-sparkle--1"></span>
            <span class="px-shell-sparkle px-shell-sparkle--2"></span>

            <!-- Tri-Ridge Multi-Stop Horizon Hills -->
            <div class="px-shell-hills">
                <svg viewBox="0 0 1440 200" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="pxShellHillFar" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--px-hill-far-1)" />
                            <stop offset="100%" stop-color="var(--px-hill-far-2)" />
                        </linearGradient>
                        <linearGradient id="pxShellHillMid" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--px-hill-mid-1)" />
                            <stop offset="100%" stop-color="var(--px-hill-mid-2)" />
                        </linearGradient>
                        <linearGradient id="pxShellHillNear" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--px-hill-near-1)" />
                            <stop offset="100%" stop-color="var(--px-hill-near-2)" />
                        </linearGradient>
                    </defs>
                    <path d="M0,108 C220,30 460,126 740,66 C1020,10 1240,102 1440,56 L1440,200 L0,200 Z" fill="url(#pxShellHillFar)" />
                    <path d="M0,132 C260,72 520,164 850,102 C1110,54 1280,130 1440,98 L1440,200 L0,200 Z" fill="url(#pxShellHillMid)" />
                    <path d="M0,156 C310,110 580,186 910,130 C1150,90 1310,148 1440,124 L1440,200 L0,200 Z" fill="url(#pxShellHillNear)" />
                </svg>
            </div>
        </div>

        <!-- STICKY 3D CLAYMORPHISM GAME HUD -->
        <header class="clay-hud-header">
            <div class="clay-hud-console">
                <!-- Specular Top Clay Gloss -->
                <span class="clay-console-gloss" aria-hidden="true"></span>

                <!-- TOP ROW: 3D Clay Avatar + Player Identity + Gem & Star Loot Pills -->
                <div class="hud-primary-row">
                    <!-- Left: Interactive 3D Clay Avatar Pod -->
                    <div class="hud-player-block">
                        <button
                            type="button"
                            class="clay-avatar-pod"
                            :class="{ 'is-popping': isAvatarBouncing }"
                            :title="`Switch Companion (${currentAvatar.label})`"
                            :aria-label="`Player avatar: ${currentAvatar.label}. Tap to switch companion.`"
                            @click="cycleAvatar"
                        >
                            <!-- SVG Circular XP Ring -->
                            <svg class="avatar-xp-ring-svg" viewBox="0 0 64 64" aria-hidden="true">
                                <defs>
                                    <linearGradient id="clayXpRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#34D399" />
                                        <stop offset="50%" stop-color="#38BDF8" />
                                        <stop offset="100%" stop-color="#6366F1" />
                                    </linearGradient>
                                </defs>
                                <circle
                                    cx="32"
                                    cy="32"
                                    r="28"
                                    fill="none"
                                    stroke="#E0E7FF"
                                    stroke-width="4.5"
                                />
                                <circle
                                    cx="32"
                                    cy="32"
                                    r="28"
                                    fill="none"
                                    stroke="url(#clayXpRingGrad)"
                                    stroke-width="4.5"
                                    stroke-linecap="round"
                                    :stroke-dasharray="ringCircumference"
                                    :stroke-dashoffset="ringDashOffset"
                                    class="xp-ring-progress"
                                />
                            </svg>

                            <!-- Smooth 3D Clay Portrait Medallion -->
                            <span
                                class="clay-portrait-medallion"
                                :style="{
                                    background: `linear-gradient(145deg, ${currentAvatar.bg1} 0%, ${currentAvatar.bg2} 100%)`,
                                    borderColor: currentAvatar.border,
                                }"
                            >
                                <!-- Custom 3D Cartoon Vector Companions -->
                                <svg v-if="currentAvatar.id === 'fox'" viewBox="0 0 48 48" class="avatar-character-svg" aria-hidden="true">
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

                                <svg v-else-if="currentAvatar.id === 'astro'" viewBox="0 0 48 48" class="avatar-character-svg" aria-hidden="true">
                                    <circle cx="24" cy="26" r="14" fill="#FFFFFF" stroke="#60A5FA" stroke-width="2.5" />
                                    <rect x="13" y="19" width="22" height="13" rx="6.5" fill="#1E3A8A" />
                                    <path d="M16 22 Q20 20 25 22" stroke="#93C5FD" stroke-width="2" stroke-linecap="round" fill="none" />
                                    <circle cx="19" cy="26" r="2.1" fill="#38BDF8" />
                                    <circle cx="29" cy="26" r="2.1" fill="#38BDF8" />
                                    <circle cx="24" cy="9" r="3" fill="#F59E0B" />
                                    <line x1="24" y1="12" x2="24" y2="14" stroke="#60A5FA" stroke-width="2.5" />
                                </svg>

                                <svg v-else-if="currentAvatar.id === 'knight'" viewBox="0 0 48 48" class="avatar-character-svg" aria-hidden="true">
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

                                <svg v-else viewBox="0 0 48 48" class="avatar-character-svg" aria-hidden="true">
                                    <ellipse cx="24" cy="27" rx="13" ry="12" fill="#8B5CF6" />
                                    <circle cx="18.5" cy="25" r="5" fill="#FEF3C7" />
                                    <circle cx="29.5" cy="25" r="5" fill="#FEF3C7" />
                                    <circle cx="18.5" cy="25" r="2.4" fill="#1E1B4B" />
                                    <circle cx="29.5" cy="25" r="2.4" fill="#1E1B4B" />
                                    <polygon points="24,27 21.5,31 26.5,31" fill="#F59E0B" />
                                    <path d="M13 16 Q18 19 21 20" stroke="#DDD6FE" stroke-width="2.5" stroke-linecap="round" fill="none" />
                                    <path d="M35 16 Q30 19 27 20" stroke="#DDD6FE" stroke-width="2.5" stroke-linecap="round" fill="none" />
                                </svg>
                            </span>

                            <!-- 3D Puffy Clay Level Pill -->
                            <span class="clay-lvl-pill">LVL {{ effectiveLevel }}</span>
                        </button>

                        <!-- Player Identity & Companion Badge -->
                        <div class="player-meta">
                            <div class="player-badges-line">
                                <span
                                    class="clay-companion-tag"
                                    :style="{
                                        backgroundColor: currentAvatar.chipBg,
                                        color: currentAvatar.chipText,
                                    }"
                                >
                                    <span class="companion-dot" :style="{ backgroundColor: currentAvatar.border }"></span>
                                    {{ currentAvatar.label }}
                                </span>

                                <span class="clay-streak-pill" title="Daily Reading Streak">
                                    <svg viewBox="0 0 16 16" class="streak-flame-svg" aria-hidden="true">
                                        <path d="M8.5 1.5C8.5 1.5 13 5 13 9.2C13 12 10.8 14.5 8 14.5C5.2 14.5 3 12 3 9.2C3 7.2 4.3 5.3 5.5 4.2C5.5 4.2 5.2 6.3 6.8 6.8C6.2 4.6 8.5 1.5 8.5 1.5Z" fill="#F97316" />
                                        <path d="M8 8.2C8 8.2 10.2 10 10.2 11.8C10.2 13.1 9.2 14.2 8 14.2C6.8 14.2 5.8 13.1 5.8 11.8C5.8 10.5 8 8.2 8 8.2Z" fill="#FDE047" />
                                    </svg>
                                    {{ streak }}d
                                </span>
                            </div>

                            <h2 class="player-display-name" :title="user?.name || 'Student'">
                                {{ user?.name || 'Student' }}
                            </h2>
                        </div>
                    </div>

                    <!-- Right: 3D Candy Clay Currency Capsules (Points & Stars) + Audio Controller -->
                    <div class="hud-currency-group" role="group" aria-label="Student Points, Stars and Sound Settings">
                        <!-- Emerald Gem Points Capsule -->
                        <div
                            class="clay-currency-pill clay-points-pill"
                            title="Total Points Earned"
                            @mouseenter="playBonus"
                            @click="playBonus"
                        >
                            <span class="currency-icon-badge points-badge" aria-hidden="true">
                                <svg viewBox="0 0 28 28" class="currency-svg">
                                    <defs>
                                        <linearGradient id="clayGemGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#6EE7B7" />
                                            <stop offset="50%" stop-color="#10B981" />
                                            <stop offset="100%" stop-color="#047857" />
                                        </linearGradient>
                                    </defs>
                                    <polygon points="14,2 24,9 20,25 8,25 4,9" fill="url(#clayGemGrad)" />
                                    <polygon points="14,4 21,9.5 14,14 7,9.5" fill="#ECFDF5" fill-opacity="0.75" />
                                </svg>
                            </span>
                            <div class="currency-text-col">
                                <span class="currency-val points-color">{{ formattedPoints }}</span>
                                <span class="currency-lbl">GEMS</span>
                            </div>
                        </div>

                        <!-- Golden Star Power Capsule -->
                        <div
                            class="clay-currency-pill clay-stars-pill"
                            title="Total Stars Earned"
                            @mouseenter="playSparkle"
                            @click="playSparkle"
                        >
                            <span class="currency-icon-badge stars-badge" aria-hidden="true">
                                <svg viewBox="0 0 28 28" class="currency-svg">
                                    <defs>
                                        <linearGradient id="clayStarGold" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#FEF08A" />
                                            <stop offset="50%" stop-color="#F59E0B" />
                                            <stop offset="100%" stop-color="#B45309" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        d="M14 2.5 C15 2.5 17 8.2 18 9.2 C19 10.2 24.8 10.8 25.3 11.9 C25.8 13 21.3 16.9 20.8 18.2 C20.3 19.5 21.8 25.1 20.8 25.7 C19.8 26.3 15.2 22.9 14 22.9 C12.8 22.9 8.2 26.3 7.2 25.7 C6.2 25.1 7.7 19.5 7.2 18.2 C6.7 16.9 2.2 13 2.7 11.9 C3.2 10.8 9 10.2 10 9.2 C11 8.2 13 2.5 14 2.5 Z"
                                        fill="url(#clayStarGold)"
                                    />
                                    <ellipse cx="11.5" cy="9.5" rx="2" ry="1.1" transform="rotate(-25 11.5 9.5)" fill="#FFFFFF" fill-opacity="0.85" />
                                </svg>
                            </span>
                            <div class="currency-text-col">
                                <span class="currency-val stars-color">{{ formattedStars }}</span>
                                <span class="currency-lbl">STARS</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTTOM ROW: Bubbly 3D Cartoon XP Progress Bar -->
                <div class="hud-xp-bar-container">
                    <div class="xp-bar-header">
                        <span class="xp-level-label">
                            <svg viewBox="0 0 16 16" class="xp-bolt-svg" aria-hidden="true">
                                <path d="M9.5 1L3 9h4.5L6.5 15L13 7H8.5L9.5 1z" fill="#F59E0B" />
                            </svg>
                            {{ effectiveTitle }} • Level {{ effectiveLevel }}
                        </span>
                        <span class="xp-numbers-readout">
                            <strong>{{ formattedXp }}</strong> / {{ formattedMaxXp }} XP ({{ xpPercentage }}%)
                        </span>
                    </div>

                    <div
                        class="clay-xp-track"
                        role="progressbar"
                        :aria-valuenow="effectiveXp"
                        :aria-valuemin="0"
                        :aria-valuemax="effectiveMaxXp"
                        :aria-label="`Experience progress: ${effectiveXp} of ${effectiveMaxXp} XP`"
                    >
                        <div class="clay-xp-fill" :style="{ width: `${xpPercentage}%` }">
                            <span class="xp-bubble-shine" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN 3D CLAY GAME STAGE -->
        <main class="clay-game-stage">
            <slot />
        </main>

        <!-- FLOATING 3D CLAYMORPHISM BOTTOM NAVIGATION DOCK -->
        <nav class="clay-bottom-dock" aria-label="Student Navigation">
            <button
                type="button"
                class="clay-dock-btn"
                :class="{ 'is-active': activeNav === 'modules' }"
                :aria-current="activeNav === 'modules' ? 'page' : undefined"
                @mouseenter="playPop(1.1); prefetchRoute('/students/dashboard', ['student-nav', 'student-modules'])"
                @focus="prefetchRoute('/students/dashboard', ['student-nav', 'student-modules'])"
                @click="goToModules"
            >
                <span class="clay-dock-pill pill-realms" aria-hidden="true">
                    <svg viewBox="0 0 28 28" class="dock-svg-icon">
                        <path d="M4 7.5C4 6.12 5.12 5 6.5 5H13v17H6.5C5.12 22 4 20.88 4 19.5v-12z" fill="currentColor" opacity="0.95" />
                        <path d="M24 7.5C24 6.12 22.88 5 21.5 5H15v17h6.5c1.38 0 2.5-1.12 2.5-2.5v-12z" fill="currentColor" opacity="0.7" />
                        <rect x="13" y="5" width="2" height="17" rx="1" fill="currentColor" />
                    </svg>
                </span>
                <span class="clay-dock-label">Modules</span>
            </button>

            <button
                type="button"
                class="clay-dock-btn"
                :class="{ 'is-active': activeNav === 'ranking' }"
                :aria-current="activeNav === 'ranking' ? 'page' : undefined"
                aria-label="View Leaderboard and Explorer Rankings"
                @mouseenter="playPop(1.1); prefetchRoute('/students/ranking', ['student-nav', 'student-ranking'])"
                @focus="prefetchRoute('/students/ranking', ['student-nav', 'student-ranking'])"
                @click="goToRanking"
            >
                <span class="clay-dock-pill pill-ranking" aria-hidden="true">
                    <svg viewBox="0 0 28 28" class="dock-svg-icon">
                        <path d="M7 6h14v7a7 7 0 0 1-14 0V6z" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" />
                        <path d="M7 9H4a2 2 0 0 0-2 2v1a4 4 0 0 0 4 4h1M21 9h3a2 2 0 0 1 2 2v1a4 4 0 0 1-4 4h-1" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                        <path d="M14 20v4M9 24h10" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                        <circle cx="14" cy="11" r="2.2" fill="currentColor" />
                    </svg>
                </span>
                <span class="clay-dock-label">Ranking</span>
            </button>

            <button
                type="button"
                class="clay-dock-btn"
                :class="{ 'is-active': activeNav === 'settings' }"
                :aria-current="activeNav === 'settings' ? 'page' : undefined"
                aria-label="View Student Audio and Account Settings"
                @mouseenter="playPop(1.1); prefetchRoute('/students/settings', ['student-nav', 'student-settings'])"
                @focus="prefetchRoute('/students/settings', ['student-nav', 'student-settings'])"
                @click="goToSettings"
            >
                <span class="clay-dock-pill pill-settings" aria-hidden="true">
                    <svg viewBox="0 0 28 28" class="dock-svg-icon">
                        <circle cx="14" cy="14" r="4.5" fill="none" stroke="currentColor" stroke-width="2.4" />
                        <path
                            d="M14 3.5v2.2M14 22.3v2.2M3.5 14h2.2M22.3 14h2.2M6.6 6.6l1.6 1.6M19.8 19.8l1.6 1.6M6.6 21.4l1.6-1.6M19.8 8.2l1.6-1.6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>
                <span class="clay-dock-label">Settings</span>
            </button>

            <span class="clay-dock-divider" aria-hidden="true"></span>

            <button
                type="button"
                class="clay-dock-btn dock-exit-btn"
                aria-label="Log out of student portal"
                @mouseenter="playPop(1.1)"
                @click="handleLogout"
            >
                <span class="clay-dock-pill pill-exit" aria-hidden="true">
                    <svg viewBox="0 0 28 28" class="dock-svg-icon">
                        <path d="M11 6H7.5A2.5 2.5 0 0 0 5 8.5v11A2.5 2.5 0 0 0 7.5 22H11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                        <path d="M18 10l4 4-4 4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        <line x1="11" y1="14" x2="21.5" y2="14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                    </svg>
                </span>
                <span class="clay-dock-label">Exit</span>
            </button>
        </nav>
    </div>
</template>

<style scoped>
/* ==========================================================================
   SMOOTH 3D CARTOON CLAYMORPHISM DESIGN SYSTEM (NO NEUMORPHISM)
   ========================================================================== */
.clay-student-shell {
    --font-display: var(--px-font-display, 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif);
    --font-body: var(--px-font-body, 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif);
    --spring-bounce: var(--px-ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));

    --ink-primary: var(--px-ink, #1E1B4B);
    --ink-secondary: var(--px-secondary, #4338CA);
    --ink-muted: var(--px-ink-muted, #64748B);

    min-height: 100vh;
    min-height: 100dvh;
    background: linear-gradient(166deg, var(--px-sky-top) 0%, var(--px-sky-mid) 50%, var(--px-sky-bottom) 100%);
    font-family: var(--font-body);
    color: var(--px-ink);
    position: relative;
    isolation: isolate;
    padding-bottom: calc(6.75rem + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
    transition: background 380ms var(--px-ease-settle), color 220ms ease;
}

.clay-student-shell::after {
    content: "";
    position: fixed;
    inset: 0;
    background-image: var(--px-noise-svg);
    background-repeat: repeat;
    opacity: 0.03;
    pointer-events: none;
    z-index: 90;
}

/* ==========================================================================
   5-PLANE PIXAR SKY, VOLUMETRIC ORBS & HILLS BACKDROP
   ========================================================================== */
.clay-sky-backdrop {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}

.clay-sun-bloom {
    position: absolute;
    inset: -15% auto auto -10%;
    width: 65vw;
    height: 60vh;
    background: radial-gradient(ellipse 60% 55% at 30% 25%, var(--px-sun-bloom) 0%, transparent 72%);
}

.clay-bounce-bloom {
    position: absolute;
    inset: auto -10% 10% auto;
    width: 55vw;
    height: 55vh;
    background: radial-gradient(ellipse 58% 54% at 70% 60%, var(--px-secondary-bloom) 0%, transparent 72%);
}

.px-shell-cloud {
    position: absolute;
    height: auto;
    opacity: var(--px-cloud-opacity);
    filter: drop-shadow(0 10px 20px rgba(var(--px-shadow-rgb), 0.12));
    will-change: transform;
    animation: shellCloudDrift var(--_cdur, 30s) ease-in-out infinite alternate;
}

.px-shell-cloud--1 { width: clamp(140px, 16vw, 230px); top: 10%; left: 3%; --_cdur: 28s; }
.px-shell-cloud--2 { width: clamp(130px, 15vw, 210px); top: 18%; right: 4%; --_cdur: 34s; animation-delay: -10s; }

@keyframes shellCloudDrift {
    0% { transform: translate3d(-12px, 0, 0); }
    100% { transform: translate3d(18px, -8px, 0); }
}

.px-shell-orb {
    position: absolute;
    border-radius: 50%;
    background:
        radial-gradient(circle at 28% 24%, rgba(255, 255, 255, 0.94) 0%, rgba(255, 255, 255, 0.5) 16%, transparent 40%),
        radial-gradient(circle at 58% 60%, var(--_orb, var(--px-orb-1)) 0%, var(--_orb, var(--px-orb-1)) 58%, rgba(255, 255, 255, 0.1) 100%);
    box-shadow:
        inset 0 -8px 18px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 3px 8px rgba(255, 255, 255, 0.65),
        0 14px 32px -6px rgba(var(--px-shadow-rgb), 0.2);
    opacity: 0.72;
    will-change: transform;
    animation: shellOrbFloat var(--_dur, 18s) ease-in-out infinite alternate;
}

.px-shell-orb--1 { width: 112px; height: 112px; top: 16%; left: 4%; --_orb: var(--px-orb-1); --_dur: 17s; }
.px-shell-orb--2 { width: 84px; height: 84px; top: 32%; right: 5%; --_orb: var(--px-orb-2); --_dur: 14s; animation-delay: -4s; }
.px-shell-orb--3 { width: 96px; height: 96px; bottom: 18%; left: 7%; --_orb: var(--px-orb-3); --_dur: 20s; animation-delay: -7s; }

@keyframes shellOrbFloat {
    0% { transform: translate3d(0, 0, 0) scale(1); }
    100% { transform: translate3d(14px, -18px, 0) scale(1.05); }
}

.px-shell-sparkle {
    position: absolute;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: radial-gradient(circle, #FFFFFF 0%, var(--px-sparkle-color) 58%, transparent 100%);
    box-shadow: 0 0 12px 3px var(--px-sparkle-color);
    animation: shellTwinkle 4s ease-in-out infinite alternate;
}

.px-shell-sparkle--1 { top: 24%; right: 12%; }
.px-shell-sparkle--2 { bottom: 26%; left: 16%; animation-delay: -2s; }

@keyframes shellTwinkle {
    0% { transform: translate3d(0, 4px, 0) scale(0.7); opacity: 0.4; }
    100% { transform: translate3d(3px, -8px, 0) scale(1.2); opacity: 0.92; }
}

.px-shell-hills {
    position: fixed;
    left: 0;
    right: 0;
    bottom: -2px;
    height: clamp(90px, 16vh, 180px);
    pointer-events: none;
}

.px-shell-hills svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* ==========================================================================
   STICKY 3D CLAYMORPHISM GAME HUD CONSOLE
   ========================================================================== */
.clay-hud-header {
    position: sticky;
    top: 0;
    z-index: 40;
    max-width: 1040px;
    margin: 0 auto;
}

.clay-hud-console {
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-card-bottom) 100%);
    padding: 0.72rem 0.85rem;
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 6px 0 var(--px-hud-ledge),
        0 16px 32px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 3px var(--px-rim-top);
    display: flex;
    flex-direction: column;
    gap: 0.58rem;
}

.clay-console-gloss {
    position: absolute;
    top: 4px;
    left: 20px;
    right: 20px;
    height: 12px;
    border-radius: 9999px;
    background: linear-gradient(180deg, var(--px-rim-top) 0%, rgba(255, 255, 255, 0) 100%);
    opacity: 0.65;
    pointer-events: none;
}

.hud-primary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.hud-player-block {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
    flex: 1 1 auto;
}

/* 3D Clay Avatar Pod */
.clay-avatar-pod {
    position: relative;
    width: 58px;
    height: 58px;
    padding: 0;
    border-radius: 50%;
    background: var(--px-surface-elevated);
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 4px 0 var(--px-hud-ledge),
        0 8px 16px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 2px 4px var(--px-rim-top);
    cursor: pointer;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 200ms var(--spring-bounce), box-shadow 200ms ease;
    outline: none;
}

.clay-avatar-pod:focus-visible {
    box-shadow: 0 0 0 3px #4F46E5, 0 4px 0 #C7D2FE;
}

.clay-avatar-pod:active {
    transform: translateY(3px) scale(0.95);
    box-shadow: 0 1px 0 #C7D2FE;
}

.clay-avatar-pod.is-popping {
    animation: clayPop 320ms var(--spring-bounce);
}

@keyframes clayPop {
    0% { transform: scale(1); }
    45% { transform: scale(1.14) rotate(6deg); }
    100% { transform: scale(1) rotate(0); }
}

.avatar-xp-ring-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
    pointer-events: none;
}

.xp-ring-progress {
    transition: stroke-dashoffset 450ms var(--spring-bounce);
}

.clay-portrait-medallion {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2.5px solid #EA580C;
    box-shadow:
        inset 0 3px 5px rgba(255, 255, 255, 0.8),
        inset 0 -3px 5px rgba(15, 23, 42, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    z-index: 1;
}

.avatar-character-svg {
    width: 36px;
    height: 36px;
    display: block;
    filter: drop-shadow(0 2px 2px rgba(15, 23, 42, 0.15));
}

.clay-lvl-pill {
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(180deg, #6366F1 0%, #4338CA 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.56rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    padding: 0.08rem 0.44rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 2px 0 #312E81,
        0 4px 8px rgba(67, 56, 202, 0.3);
    white-space: nowrap;
    z-index: 2;
}

/* Player Identity */
.player-meta {
    display: flex;
    flex-direction: column;
    gap: 0.16rem;
    min-width: 0;
}

.player-badges-line {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.clay-companion-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.12rem 0.48rem;
    border-radius: 9999px;
    font-size: 0.56rem;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    box-shadow:
        0 2px 0 rgba(99, 102, 241, 0.18),
        inset 0 1px 2px rgba(255, 255, 255, 0.8);
}

.companion-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.clay-streak-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.16rem;
    padding: 0.12rem 0.44rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFF7ED 0%, #FFEDD5 100%);
    border: 1.5px solid #FDBA74;
    box-shadow: 0 2px 0 #F97316;
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
    color: #C2410C;
}

.streak-flame-svg {
    width: 11px;
    height: 11px;
    display: block;
}

.player-display-name {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.02rem;
    font-weight: 800;
    color: var(--ink-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.18;
}

/* ==========================================================================
   3D CANDY CLAY CURRENCY CAPSULES (GEMS & STARS)
   ========================================================================== */
.hud-currency-group {
    display: flex;
    align-items: center;
    gap: 0.42rem;
    flex-shrink: 0;
}

.clay-currency-pill {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.55rem 0.25rem 0.28rem;
    border-radius: 18px;
    border: 2px solid #FFFFFF;
    cursor: pointer;
    transition: transform 0.18s var(--spring-bounce), box-shadow 0.18s ease;
}

.clay-currency-pill:hover {
    transform: translateY(-2px) scale(1.04);
}

.clay-points-pill {
    background: linear-gradient(180deg, #ECFDF5 0%, #D1FAE5 100%);
    box-shadow:
        0 4px 0 #6EE7B7,
        0 6px 12px rgba(16, 185, 129, 0.15),
        inset 0 2px 3px rgba(255, 255, 255, 0.9);
}

.clay-stars-pill {
    background: linear-gradient(180deg, #FEFCE8 0%, #FEF08A 100%);
    box-shadow:
        0 4px 0 #FACC15,
        0 6px 12px rgba(245, 158, 11, 0.16),
        inset 0 2px 3px rgba(255, 255, 255, 0.9);
}

.currency-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 11px;
    background: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow:
        0 2px 4px rgba(15, 23, 42, 0.08),
        inset 0 1px 2px rgba(255, 255, 255, 0.9);
}

.currency-svg {
    width: 18px;
    height: 18px;
    display: block;
}

.currency-text-col {
    display: flex;
    flex-direction: column;
    line-height: 1.04;
}

.currency-val {
    font-family: var(--font-display);
    font-size: 0.84rem;
    font-weight: 800;
}

.points-color {
    color: #065F46;
}

.stars-color {
    color: #92400E;
}

.currency-lbl {
    font-size: 0.48rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    color: #475569;
}

/* ==========================================================================
   3D CARTOON BUBBLY XP PROGRESS BAR
   ========================================================================== */
.hud-xp-bar-container {
    display: flex;
    flex-direction: column;
    gap: 0.24rem;
}

.xp-bar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.66rem;
    font-weight: 800;
    color: var(--px-ink-muted);
    padding: 0 0.15rem;
}

.xp-level-label {
    display: inline-flex;
    align-items: center;
    gap: 0.22rem;
    font-family: var(--font-display);
    font-weight: 700;
    color: var(--px-ink);
}

.xp-bolt-svg {
    width: 13px;
    height: 13px;
}

.xp-numbers-readout strong {
    color: var(--px-link-ink);
    font-weight: 900;
}

.clay-xp-track {
    width: 100%;
    height: 13px;
    border-radius: 9999px;
    background: var(--px-xp-track-bg);
    border: 2px solid var(--px-card-border);
    box-shadow:
        inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.2),
        0 2px 0 rgba(var(--px-shadow-rgb), 0.14);
    padding: 1.5px;
    overflow: hidden;
    box-sizing: border-box;
}

.clay-xp-fill {
    height: 100%;
    border-radius: 9999px;
    background: var(--px-xp-fill-grad);
    box-shadow: inset 0 -2px 3px rgba(var(--px-shadow-rgb), 0.22);
    position: relative;
    transition: width 450ms var(--spring-bounce);
}

.xp-bubble-shine {
    position: absolute;
    top: 1px;
    left: 6px;
    right: 6px;
    height: 3px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.72);
}

/* ==========================================================================
   MAIN 3D CLAY GAME STAGE
   ========================================================================== */
.clay-game-stage {
    position: relative;
    z-index: 10;
    max-width: 1040px;
    margin: 0.4rem auto 1.5rem;
    padding: 0 0.8rem;
}

/* ==========================================================================
   FLOATING 3D CLAYMORPHISM BOTTOM NAVIGATION DOCK
   ========================================================================== */
.clay-bottom-dock {
    position: fixed;
    bottom: max(0.75rem, env(safe-area-inset-bottom, 0.75rem));
    left: 50%;
    transform: translateX(-50%);
    z-index: 50;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-card-bottom) 100%);
    backdrop-filter: blur(14px) saturate(145%);
    -webkit-backdrop-filter: blur(14px) saturate(145%);
    border-radius: 30px;
    padding: 0.4rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 7px 0 var(--px-hud-ledge),
        0 18px 34px rgba(var(--px-shadow-rgb), 0.28),
        inset 0 2px 4px var(--px-rim-top);
    max-width: calc(100vw - 1.25rem);
}

.clay-dock-btn {
    min-width: 62px;
    min-height: 52px;
    padding: 0.2rem 0.48rem;
    border-radius: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.18rem;
    color: var(--px-ink-muted);
    transition: transform 180ms var(--spring-bounce), color 150ms ease;
    outline: none;
}

.clay-dock-btn:focus-visible {
    box-shadow: 0 0 0 3px var(--px-focus-stroke);
}

.clay-dock-btn:active {
    transform: translateY(3px) scale(0.92);
}

.clay-dock-pill {
    width: 40px;
    height: 34px;
    border-radius: 14px;
    background: var(--px-surface-well);
    border: 1.5px solid var(--px-card-border);
    box-shadow:
        0 3px 0 rgba(var(--px-shadow-rgb), 0.18),
        inset 0 2px 3px var(--px-rim-top);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 180ms var(--spring-bounce), background 180ms ease, box-shadow 180ms ease, color 180ms ease;
}

.pill-realms {
    color: var(--px-link-ink);
}

.pill-ranking {
    color: #F59E0B;
}

.pill-settings {
    color: #A78BFA;
}

.clay-dock-btn:hover .pill-ranking {
    color: #FBBF24;
}

.clay-dock-btn:hover .pill-settings {
    color: var(--px-secondary-lit);
}

.pill-exit {
    color: #FB7185;
}

.dock-svg-icon {
    width: 19px;
    height: 19px;
    display: block;
}

.clay-dock-btn.is-active {
    color: var(--px-ink);
}

.clay-dock-btn.is-active .clay-dock-pill {
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    color: var(--px-secondary-ink);
    border-color: var(--px-card-border);
    box-shadow:
        0 4px 0 var(--px-secondary-pedestal),
        0 6px 14px var(--px-secondary-glow),
        inset 0 2px 4px rgba(255, 255, 255, 0.55);
    transform: translateY(-2px);
}

.clay-dock-label {
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.clay-dock-divider {
    width: 2px;
    height: 32px;
    border-radius: 9999px;
    background: var(--px-card-border);
}

.dock-exit-btn:hover .pill-exit {
    background: linear-gradient(180deg, #FFE4E6 0%, #FECDD3 100%);
    box-shadow: 0 3px 0 #FDA4AF;
}

/* ==========================================================================
   SMALL PHONE (< 380px) & TABLET/DESKTOP ADAPTIVE BREAKPOINTS
   ========================================================================== */
@media (max-width: 380px) {
    .clay-avatar-pod {
        width: 52px;
        height: 52px;
    }

    .clay-portrait-medallion {
        width: 38px;
        height: 38px;
    }

    .player-display-name {
        font-size: 0.9rem;
        max-width: 88px;
    }

    .clay-streak-pill {
        display: none;
    }

    .clay-currency-pill {
        padding: 0.22rem 0.42rem 0.22rem 0.22rem;
        gap: 0.26rem;
    }

    .currency-icon-badge {
        width: 24px;
        height: 24px;
    }

    .currency-val {
        font-size: 0.78rem;
    }

    .clay-dock-btn {
        min-width: 54px;
        padding: 0.18rem 0.35rem;
    }
}

@media (min-width: 768px) {
    .clay-hud-header {
        /* padding: 1rem 1.5rem 0.6rem; */
    }

    .clay-hud-console {
        padding: 0.85rem 1.25rem;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
    }

    .hud-primary-row {
        flex: 1 1 auto;
    }

    .hud-xp-bar-container {
        width: 310px;
        flex-shrink: 0;
    }

    .clay-game-stage {
        padding: 0 1.5rem;
        margin-top: 0.6rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .clay-avatar-pod,
    .xp-ring-progress,
    .clay-xp-fill,
    .clay-dock-btn,
    .clay-dock-pill,
    .px-shell-cloud,
    .px-shell-orb,
    .px-shell-sparkle {
        animation: none !important;
        transition: none !important;
    }
}
</style>
