<script setup>
import { Head, router, useRemember } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import StudentLayout from '../../Components/StudentLayout.vue';
import { prefetchRoute, useIdlePrefetch } from '@/Composables/usePrefetch';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student',
            email: 'student@example.com',
            role: 'student',
        }),
    },
    modules: {
        type: Array,
        default: () => [],
    },
    /** The classroom being explored, or null for the "Code Adventures" collection. */
    classroom: {
        type: Object,
        default: null,
    },
    /** One-time "you just joined" message after entering a code. */
    joined_message: {
        type: String,
        default: null,
    },
});

const isCodeAdventures = computed(() => props.classroom === null);
const joinedBanner = ref(props.joined_message);

const goToClassrooms = () => {
    router.visit('/students/classes');
};

/** Leaderboard filtered to just this classroom's students. */
const classroomRankingUrl = computed(() => (props.classroom ? `/students/ranking?class_id=${props.classroom.id}` : '/students/ranking'));

const goToClassroomRanking = () => {
    router.visit(classroomRankingUrl.value);
};

const selectedBiome = useRemember('all', 'student.dashboard.biome');
const activeRealmPreview = ref(null);
const pressedCardId = ref(null);

const biomeFilters = [
    { id: 'all', label: 'All Worlds', dot: '#4F46E5', activeBg: 'linear-gradient(180deg, #6366F1 0%, #4338CA 100%)', activeLedge: '#312E81' },
    { id: 'forest', label: 'Forest', dot: '#10B981', activeBg: 'linear-gradient(180deg, #34D399 0%, #059669 100%)', activeLedge: '#065F46' },
    { id: 'ocean', label: 'Lagoon', dot: '#0EA5E9', activeBg: 'linear-gradient(180deg, #38BDF8 0%, #0284C7 100%)', activeLedge: '#075985' },
    { id: 'cosmic', label: 'Cosmic', dot: '#8B5CF6', activeBg: 'linear-gradient(180deg, #A78BFA 0%, #7C3AED 100%)', activeLedge: '#5B21B6' },
    { id: 'volcano', label: 'Volcano', dot: '#F97316', activeBg: 'linear-gradient(180deg, #FB923C 0%, #EA580C 100%)', activeLedge: '#9A3412' },
];

const playerStats = computed(() => {
    const moduleCount = props.modules?.length || 0;
    const baseXp = 680 + moduleCount * 45;

    return {
        level: Math.max(1, Math.floor(baseXp / 200) + 2),
        xp: baseXp % 1000,
        maxXp: 1000,
        points: 1250 + moduleCount * 150,
        stars: 24 + moduleCount * 3,
        streak: Math.max(3, moduleCount + 2),
    };
});

const filteredModules = computed(() => {
    if (selectedBiome.value === 'all') {
        return props.modules;
    }

    return props.modules.filter((mod) => (mod.theme || 'forest') === selectedBiome.value);
});

const featuredModule = computed(() => {
    if (!props.modules || props.modules.length === 0) {
        return null;
    }

    return props.modules[0];
});

/** Module map URL that remembers which list it was opened from, so its back button returns here. */
const moduleMapUrl = (mod) => `/students/modules/${mod.slug}?from=${props.classroom ? props.classroom.id : 'code'}`;

useIdlePrefetch(
    () => (featuredModule.value?.slug ? moduleMapUrl(featuredModule.value) : null),
    ['student-modules', featuredModule.value?.slug ? `module-${featuredModule.value.slug}` : ''],
    ['20s', '90s'],
);

const prefetchModule = (mod) => {
    if (mod?.slug) {
        prefetchRoute(moduleMapUrl(mod), ['student-modules', `module-${mod.slug}`], ['20s', '90s']);
    }
};

const totalQuestsCount = computed(() => {
    return (props.modules || []).reduce(
        (sum, mod) => sum + Number(mod.lectures_count || 0) + Number(mod.quizzes_count || 0),
        0,
    );
});

/** The student's real quest progress from the server (same rule as the expedition map). */
const getModuleProgress = (mod) => Math.min(100, Math.max(0, Number(mod.progress?.percent ?? 0)));

/** Card stars follow real progress: 3 only once every quest is done. */
const getEarnedStars = (mod) => {
    const progress = getModuleProgress(mod);
    if (progress >= 100) {
        return 3;
    }
    if (progress >= 50) {
        return 2;
    }

    return progress > 0 ? 1 : 0;
};

const getBiomeLabel = (theme) => {
    const labels = {
        forest: 'Emerald Woods',
        ocean: 'Coral Lagoon',
        cosmic: 'Starlight Sky',
        volcano: 'Sunset Peaks',
    };

    return labels[theme] || 'Emerald Woods';
};

const getBiomeEmoji = (theme) => {
    const emojis = {
        forest: '🌳',
        ocean: '🐠',
        cosmic: '🪐',
        volcano: '🌋',
    };

    return emojis[theme] || '🌳';
};

const sheetCloseRef = ref(null);

/** Move focus into the briefing sheet when it opens so Esc and screen readers land inside it. */
watch(activeRealmPreview, async (mod) => {
    if (mod) {
        await nextTick();
        sheetCloseRef.value?.focus();
    }
});

const openRealmPreview = (mod) => {
    pressedCardId.value = mod.id;
    window.setTimeout(() => {
        pressedCardId.value = null;
        activeRealmPreview.value = mod;
    }, 110);
};

const closeRealmPreview = () => {
    activeRealmPreview.value = null;
};

const launchModuleQuest = (mod) => {
    const targetMod = mod || activeRealmPreview.value;
    activeRealmPreview.value = null;
    if (targetMod?.slug) {
        router.visit(moduleMapUrl(targetMod));

        return;
    }

    router.visit('/students/progress');
};

</script>

<template>
    <Head title="Adventure Modules • WEBREAD" />

    <StudentLayout
        :user="user"
        :level="playerStats.level"
        :xp="playerStats.xp"
        :max-xp="playerStats.maxXp"
        :points="playerStats.points"
        :stars="playerStats.stars"
        :streak="playerStats.streak"
        active-nav="classes"
    >
        <section class="clay-adventure-dashboard" aria-labelledby="modules-heading">
            <!-- 0. CLASSROOM HEADER: which room these modules belong to + way back to all rooms -->
            <header class="clay-classroom-header" :class="isCodeAdventures ? 'is-code' : `room-theme-${classroom.theme}`">
                <!-- Lit room diorama: key light top-left, rolling clay hills, floating orbs -->
                <span class="header-scene" aria-hidden="true">
                    <span class="header-cloud header-cloud--1"></span>
                    <span class="header-cloud header-cloud--2"></span>
                    <span class="header-orb header-orb--1"></span>
                    <span class="header-orb header-orb--2"></span>
                    <span class="header-orb header-orb--3"></span>
                    <svg class="header-hills" viewBox="0 0 400 80" preserveAspectRatio="none">
                        <path class="hill-back" d="M0,44 C60,16 130,52 200,28 C262,8 330,38 400,22 L400,80 L0,80 Z" />
                        <path class="hill-front" d="M0,60 C80,34 160,72 245,46 C300,30 350,52 400,44 L400,80 L0,80 Z" />
                    </svg>
                </span>

                <button
                    type="button"
                    class="classroom-back-btn"
                    aria-label="Back to all classrooms"
                    @mouseenter="prefetchRoute('/students/classes', ['student-nav', 'student-classes'])"
                    @focus="prefetchRoute('/students/classes', ['student-nav', 'student-classes'])"
                    @click="goToClassrooms"
                >
                    <span class="back-btn-face">
                        <span aria-hidden="true">←</span>
                        <span>All Classrooms</span>
                    </span>
                </button>

                <!-- Gold 3D trophy key: opens this classroom's own leaderboard -->
                <a
                    v-if="!isCodeAdventures"
                    :href="classroomRankingUrl"
                    class="classroom-ranking-btn"
                    :aria-label="`View the ${classroom.name} class ranking`"
                    @mouseenter="prefetchRoute(classroomRankingUrl, ['student-nav', 'student-ranking'])"
                    @focus="prefetchRoute(classroomRankingUrl, ['student-nav', 'student-ranking'])"
                    @click.prevent="goToClassroomRanking"
                >
                    <span class="ranking-btn-face">
                        <svg viewBox="0 0 28 28" class="ranking-trophy-svg" aria-hidden="true">
                            <path d="M8 5h12v7a6 6 0 0 1-12 0V5z" fill="currentColor" />
                            <path d="M8 8H5.5a1.5 1.5 0 0 0-1.5 1.5v.5a4 4 0 0 0 4 4M20 8h2.5A1.5 1.5 0 0 1 24 9.5v.5a4 4 0 0 1-4 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                            <path d="M14 18v3.5M9.5 23.5h9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                            <ellipse cx="11.5" cy="8" rx="1.6" ry="2.4" fill="#FFFFFF" opacity="0.65" />
                        </svg>
                        <span>Class Ranking</span>
                    </span>
                </a>

                <div class="classroom-header-main">
                    <span class="classroom-header-icon" aria-hidden="true">
                        <span class="header-icon-glyph">{{ isCodeAdventures ? '🔑' : classroom.icon || '🏫' }}</span>
                    </span>
                    <div class="classroom-header-copy">
                        <span class="classroom-header-kicker">{{ isCodeAdventures ? 'Joined with module codes' : 'Classroom' }}</span>
                        <h1 id="modules-heading" class="classroom-header-title">{{ isCodeAdventures ? 'Code Adventures' : classroom.name }}</h1>
                    </div>
                </div>

                <transition name="alert-pop">
                    <div v-if="joinedBanner" class="classroom-joined-banner" role="status">
                        <span aria-hidden="true">🎉</span>
                        <span class="classroom-joined-text">{{ joinedBanner }}</span>
                        <button type="button" class="classroom-joined-dismiss" aria-label="Dismiss message" @click="joinedBanner = null">
                            <span aria-hidden="true">✕</span>
                        </button>
                    </div>
                </transition>
            </header>

            <!-- Shared SVG Defs for fallback diorama cards (avoids duplicating gradient defs per card) -->
            <svg width="0" height="0" style="position: absolute; pointer-events: none;" aria-hidden="true">
                <defs>
                    <linearGradient id="studentSkyGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#38BDF8" />
                        <stop offset="55%" stop-color="#BAE6FD" />
                        <stop offset="100%" stop-color="#FEF9C3" />
                    </linearGradient>
                    <linearGradient id="studentPeakA" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#A78BFA" />
                        <stop offset="100%" stop-color="#6366F1" />
                    </linearGradient>
                    <linearGradient id="studentPeakB" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#F472B6" />
                        <stop offset="100%" stop-color="#DB2777" />
                    </linearGradient>
                    <linearGradient id="studentMeadowA" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#4ADE80" />
                        <stop offset="100%" stop-color="#16A34A" />
                    </linearGradient>
                    <linearGradient id="studentMeadowB" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#34D399" />
                        <stop offset="100%" stop-color="#059669" />
                    </linearGradient>
                    <linearGradient id="studentPathGold" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#FDE047" />
                        <stop offset="100%" stop-color="#F59E0B" />
                    </linearGradient>
                    <symbol id="studentWorldScene" viewBox="0 0 600 340" preserveAspectRatio="xMidYMid slice">
                        <rect width="600" height="340" fill="url(#studentSkyGrad)" />

                        <!-- 3D Cartoon Sun -->
                        <circle cx="495" cy="70" r="38" fill="#FDE047" />
                        <circle cx="495" cy="70" r="28" fill="#FEF9C3" />

                        <!-- Puffy White Cartoon Clouds -->
                        <path d="M65 110 C65 92 84 78 108 78 C126 78 142 90 148 102 C158 98 170 104 174 114 C182 116 188 124 188 132 C188 140 178 146 166 146 L70 146 C58 146 51 136 54 126 C54 118 59 113 65 110 Z" fill="#FFFFFF" />
                        <path d="M340 82 C340 66 358 54 380 54 C398 54 414 64 420 76 C430 72 442 78 446 86 C454 88 460 94 460 102 C460 110 450 116 438 116 L345 116 C332 116 326 106 329 96 C329 90 334 85 340 82 Z" fill="#FFFFFF" />

                        <!-- Candy Mountains with Snow Caps -->
                        <path d="M80 255 L180 105 L280 255 Z" fill="url(#studentPeakA)" />
                        <path d="M180 105 L155 148 C168 156 192 156 205 148 Z" fill="#FFFFFF" />

                        <path d="M230 265 L350 85 L470 265 Z" fill="url(#studentPeakB)" />
                        <path d="M350 85 L315 145 C335 156 365 156 385 145 Z" fill="#FFFFFF" />

                        <!-- Rolling Toy Hills -->
                        <path d="M-20 250 Q120 185 270 235 T620 215 L620 340 L-20 340 Z" fill="url(#studentMeadowA)" />
                        <path d="M-20 278 Q180 225 350 268 T620 250 L620 340 L-20 340 Z" fill="url(#studentMeadowB)" />

                        <!-- Golden Quest Trail -->
                        <path d="M250 340 Q282 282 296 244 Q306 225 316 220 L338 223 Q328 230 322 250 Q314 288 354 340 Z" fill="url(#studentPathGold)" />

                        <!-- Bubble Cartoon Trees -->
                        <rect x="82" y="212" width="10" height="34" rx="3" fill="#92400E" />
                        <circle cx="87" cy="200" r="26" fill="#10B981" />
                        <circle cx="79" cy="191" r="10" fill="#A7F3D0" fill-opacity="0.75" />

                        <rect x="488" y="200" width="11" height="38" rx="3" fill="#92400E" />
                        <circle cx="493.5" cy="188" r="28" fill="#F97316" />
                        <circle cx="485" cy="178" r="11" fill="#FDE047" fill-opacity="0.75" />
                    </symbol>
                </defs>
            </svg>

            <!-- 3. LEVEL 3 HIERARCHY: 2-COLUMN 3D CARTOON CLAY REALM CARDS GRID -->
            <div v-if="filteredModules.length > 0" class="clay-realms-grid">
                <article
                    v-for="(mod, idx) in filteredModules"
                    :key="mod.id"
                    v-memo="[mod.id, mod.title, mod.theme, mod.status, mod.is_new, mod.lectures_count, mod.quizzes_count, mod.cover_image_url, mod.progress?.percent, pressedCardId === mod.id]"
                    class="clay-realm-card"
                    :class="[
                        `theme-${mod.theme || 'forest'}`,
                        { 'is-pressed': pressedCardId === mod.id },
                    ]"
                    :style="{ '--i': Math.min(idx, 8) }"
                    role="button"
                    tabindex="0"
                    :aria-label="'Open learning module: ' + mod.title"
                    @mouseenter="prefetchModule(mod)"
                    @focus="prefetchModule(mod)"
                    @click="openRealmPreview(mod)"
                    @keydown.enter="openRealmPreview(mod)"
                    @keydown.space.prevent="openRealmPreview(mod)"
                >
                    <!-- Specular Top Clay Highlight -->
                    <span class="card-top-gloss" aria-hidden="true"></span>

                    <!-- Top Row: Biome Pill + XP Candy Tag -->
                    <div class="realm-card-top">
                        <span class="clay-biome-chip">
                            <span class="biome-dot-indicator" aria-hidden="true"></span>
                            <span>{{ getBiomeLabel(mod.theme) }}</span>
                        </span>

                        <span class="clay-xp-pill">
                            <svg viewBox="0 0 14 14" class="xp-mini-svg" aria-hidden="true">
                                <path d="M8 1L2.5 8h4L5.5 13L11.5 6h-4L8 1z" fill="currentColor" />
                            </svg>
                            +{{ mod.xp_bonus || 250 }}
                        </span>
                    </div>

                    <!-- 3D Cartoon Diorama Picture Window with Star Rating Ribbon -->
                    <div class="clay-diorama-frame">
                        <div class="diorama-viewport">
                            <img
                                v-if="mod.cover_image_url"
                                :src="mod.cover_image_url"
                                :alt="mod.title"
                                class="diorama-img"
                                loading="lazy"
                                decoding="async"
                            />
                            <!-- Vibrant 3D Cartoon Vector World Illustration Fallback -->
                            <div v-else class="diorama-fallback-scene" aria-hidden="true">
                                <svg viewBox="0 0 600 340" preserveAspectRatio="xMidYMid slice" class="fallback-world-svg">
                                    <use href="#studentWorldScene" />
                                </svg>
                            </div>

                            <!-- New Tag Badge -->
                            <span v-if="mod.is_new" class="diorama-new-pill">NEW!</span>

                            <!-- 3-Star Cartoon Progress Ribbon inside Viewport -->
                            <div class="diorama-stars-pod" :aria-label="`${getEarnedStars(mod, idx)} of 3 stars earned`">
                                <svg
                                    v-for="starIdx in 3"
                                    :key="starIdx"
                                    viewBox="0 0 20 20"
                                    class="mini-star-svg"
                                    :class="starIdx <= getEarnedStars(mod, idx) ? 'star-earned' : 'star-empty'"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 1.5L12.6 6.8L18.5 7.6L14.2 11.8L15.2 17.6L10 14.8L4.8 17.6L5.8 11.8L1.5 7.6L7.4 6.8L10 1.5Z"
                                        fill="currentColor"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body: Bold Cartoon Title, Description & Bubbly Progress Bar -->
                    <div class="realm-card-body">
                        <h3 class="realm-title" :title="mod.title">{{ mod.title }}</h3>
                        <p class="realm-description">
                            {{
                                mod.description ||
                                'Explore interactive story chapters, vocabulary milestones, and comprehension challenges.'
                            }}
                        </p>

                        <!-- Bubbly Candy Progress Bar -->
                        <div class="realm-progress-block">
                            <div class="realm-progress-labels">
                                <span class="progress-title">{{ getModuleProgress(mod) >= 100 ? 'Quest Complete! 🏆' : 'Quest Progress' }}</span>
                                <span class="progress-value">{{ getModuleProgress(mod, idx) }}%</span>
                            </div>
                            <div class="clay-mini-track">
                                <div
                                    class="clay-mini-fill"
                                    :style="{ width: `${getModuleProgress(mod, idx)}%` }"
                                >
                                    <span class="mini-fill-shine" aria-hidden="true"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer: Story/Quiz Pills + 3D Pushable Play Button -->
                    <div class="realm-card-footer">
                        <div class="realm-counters">
                            <span class="clay-counter-badge" title="Story Chapters">
                                <svg viewBox="0 0 16 16" class="counter-svg" aria-hidden="true">
                                    <path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h3.2A1.3 1.3 0 0 1 8 3.3v9.4a1 1 0 0 0-1-1H3.5A1.5 1.5 0 0 1 2 10.2V3.5z" fill="currentColor" />
                                    <path d="M14 3.5A1.5 1.5 0 0 0 12.5 2H9.3A1.3 1.3 0 0 0 8 3.3v9.4a1 1 0 0 1 1-1h3.5a1.5 1.5 0 0 0 1.5-1.5V3.5z" fill="currentColor" opacity="0.65" />
                                </svg>
                                {{ mod.lectures_count || 0 }}
                            </span>

                            <span class="clay-counter-badge" title="Quizzes">
                                <svg viewBox="0 0 16 16" class="counter-svg" aria-hidden="true">
                                    <circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.8" />
                                    <circle cx="8" cy="8" r="2.5" fill="currentColor" />
                                </svg>
                                {{ mod.quizzes_count || 0 }}
                            </span>
                        </div>

                        <span class="clay-play-btn" aria-hidden="true">
                            <span class="play-btn-face">
                                <span>PLAY</span>
                                <svg viewBox="0 0 18 18" class="play-arrow-svg">
                                    <path d="M6.5 4.5L11.5 9L6.5 13.5" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </span>
                    </div>
                </article>
            </div>

            <!-- 4. EMPTY REALMS STATE -->
            <div v-else class="clay-empty-card">
                <div class="clay-empty-badge" aria-hidden="true">
                    <span class="empty-badge-emoji">{{ modules.length === 0 ? '🗺️' : '🔍' }}</span>
                </div>
                <h2 class="empty-title">
                    {{
                        modules.length > 0
                            ? 'No Worlds Found Here Yet!'
                            : (classroom ? `No Adventures in ${classroom.name} Yet!` : 'No Code Adventures Yet!')
                    }}
                </h2>
                <p class="empty-desc">
                    {{
                        modules.length === 0
                            ? (classroom
                                ? 'Your teacher has not added reading worlds to this classroom yet. Check back soon!'
                                : 'Enter a module Quest Code on the Classrooms page to unlock a reading adventure!')
                            : (selectedBiome === 'all'
                                ? 'Your teacher is crafting new reading worlds and story quests. Check back soon!'
                                : 'No worlds match this biome filter yet. Tap below to see all available worlds.')
                    }}
                </p>
                <button
                    v-if="selectedBiome !== 'all'"
                    type="button"
                    class="clay-reset-btn"
                    @click="selectedBiome = 'all'"
                >
                    <span class="reset-btn-face">Show All Worlds</span>
                </button>
            </div>
        </section>

        <!-- 5. 3D CLAYMORPHISM MOBILE BOTTOM SHEET REALM BRIEFING -->
        <Teleport to="body">
            <Transition name="clay-sheet" :duration="{ enter: 480, leave: 220 }">
                <div
                    v-if="activeRealmPreview"
                    class="clay-sheet-backdrop"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="`sheet-title-${activeRealmPreview.id}`"
                    @click.self="closeRealmPreview"
                    @keydown.esc="closeRealmPreview"
                >
                    <div class="clay-bottom-sheet" :class="`theme-${activeRealmPreview.theme || 'forest'}`">
                        <div class="sheet-pill-handle" aria-hidden="true"></div>

                        <div class="sheet-header">
                            <span class="sheet-biome-tag">
                                {{ getBiomeLabel(activeRealmPreview.theme) }} • {{ activeRealmPreview.grade_level || 'All Grades' }}
                            </span>
                            <button
                                ref="sheetCloseRef"
                                type="button"
                                class="clay-close-btn"
                                aria-label="Close realm preview"
                                @click="closeRealmPreview"
                            >
                                <span class="close-btn-face">
                                    <svg viewBox="0 0 20 20" class="close-svg" aria-hidden="true">
                                        <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
                                    </svg>
                                </span>
                            </button>
                        </div>

                        <!-- World art window with a clay biome medallion breaking its frame -->
                        <div class="sheet-art" aria-hidden="true">
                            <div class="sheet-art-viewport">
                                <img
                                    v-if="activeRealmPreview.cover_image_url"
                                    :src="activeRealmPreview.cover_image_url"
                                    alt=""
                                    class="sheet-art-img"
                                    decoding="async"
                                />
                                <svg v-else viewBox="0 0 600 340" preserveAspectRatio="xMidYMid slice" class="sheet-art-img">
                                    <use href="#studentWorldScene" />
                                </svg>
                            </div>
                            <span class="sheet-medallion">
                                <span class="sheet-medallion-glyph">{{ getBiomeEmoji(activeRealmPreview.theme) }}</span>
                            </span>
                        </div>

                        <h3 :id="`sheet-title-${activeRealmPreview.id}`" class="sheet-title">
                            {{ activeRealmPreview.title }}
                        </h3>

                        <p class="sheet-desc">
                            {{
                                activeRealmPreview.description ||
                                'Complete the story chapters and interactive comprehension quests inside this world to claim your XP treasure and guardian stars!'
                            }}
                        </p>

                        <!-- 3-Pod Clay Loot Summary -->
                        <div class="sheet-loot-grid">
                            <div class="sheet-loot-pod pod-stories">
                                <span class="loot-lbl">STORIES</span>
                                <span class="loot-val">{{ activeRealmPreview.lectures_count || 0 }} Chapters</span>
                            </div>
                            <div class="sheet-loot-pod pod-quizzes">
                                <span class="loot-lbl">QUIZZES</span>
                                <span class="loot-val">{{ activeRealmPreview.quizzes_count || 0 }} Quests</span>
                            </div>
                            <div class="sheet-loot-pod pod-reward">
                                <span class="loot-lbl">REWARD</span>
                                <span class="loot-val">+{{ activeRealmPreview.xp_bonus || 250 }} XP</span>
                            </div>
                        </div>

                        <div class="sheet-guardian-banner">
                            <span class="guardian-label">World Guardian:</span>
                            <strong class="guardian-name">{{ activeRealmPreview.boss_name || 'Chapter Guardian' }}</strong>
                        </div>

                        <button
                            type="button"
                            class="clay-sheet-launch-btn"
                            @click="launchModuleQuest(activeRealmPreview)"
                        >
                            <span class="launch-btn-face">LET'S GO!</span>
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   SMOOTH 3D CARTOON CLAYMORPHISM DASHBOARD (RICHER COLOR & CLEAR HIERARCHY)
   ========================================================================== */
.clay-adventure-dashboard {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    padding-top: 0.15rem;
}

/* ==========================================================================
   0. CLASSROOM HEADER — LIT CLAY DIORAMA (mobile first)
   One key light top-left; every shadow is tinted with the room hue.
   ========================================================================== */
.clay-classroom-header {
    --room-accent: #4F46E5;
    --room-ledge: #312E81;
    --room-soft: var(--px-tint-info-bg);

    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    padding: 8px 8px 1rem;
    border-radius: 30px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--room-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 color-mix(in srgb, var(--room-accent) 40%, var(--px-ledge)),
        0 18px 30px -14px color-mix(in srgb, var(--room-accent) 45%, rgba(var(--px-shadow-rgb), 0.5)),
        inset 0 2px 0 var(--px-rim-top);
}

.clay-classroom-header.room-theme-forest { --room-accent: #059669; --room-ledge: #065F46; --room-soft: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card)); }
.clay-classroom-header.room-theme-ocean { --room-accent: #0284C7; --room-ledge: #075985; --room-soft: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)); }
.clay-classroom-header.room-theme-cosmic { --room-accent: #7C3AED; --room-ledge: #5B21B6; --room-soft: color-mix(in srgb, var(--px-tint-magic-bg) 55%, var(--px-surface-card)); }
.clay-classroom-header.room-theme-volcano { --room-accent: #EA580C; --room-ledge: #9A3412; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)); }
.clay-classroom-header.is-code { --room-accent: #D97706; --room-ledge: #92400E; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)); }

.header-scene {
    position: relative;
    display: block;
    height: 104px;
    border-radius: 23px;
    overflow: hidden;
    background:
        radial-gradient(circle at 12% 6%, rgba(255, 255, 255, calc(0.8 * var(--px-gloss, 1))) 0%, rgba(255, 255, 255, 0) 36%),
        linear-gradient(172deg,
            color-mix(in srgb, var(--room-accent) 22%, var(--px-surface-card)) 0%,
            color-mix(in srgb, var(--room-accent) 58%, var(--px-surface-card)) 100%);
    box-shadow:
        inset 0 3px 8px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 -2px 0 rgba(255, 255, 255, 0.35);
}

.header-hills {
    position: absolute;
    left: 0;
    bottom: -1px;
    width: 100%;
    height: 56px;
}

.hill-back {
    fill: color-mix(in srgb, var(--room-accent) 70%, #FFFFFF);
    opacity: 0.9;
}

.hill-front {
    fill: var(--room-accent);
    stroke: color-mix(in srgb, var(--room-accent) 45%, #FFFFFF);
    stroke-width: 2;
}

.header-cloud {
    position: absolute;
    height: 14px;
    border-radius: 999px;
    background: #FFFFFF;
    opacity: calc(0.35 + 0.55 * var(--px-gloss, 1));
    box-shadow: inset 0 -3px 0 color-mix(in srgb, var(--room-accent) 22%, #FFFFFF);
    animation: header-drift 9s ease-in-out infinite alternate;
}

.header-cloud::before {
    content: '';
    position: absolute;
    left: 22%;
    bottom: 6px;
    width: 46%;
    height: 17px;
    border-radius: 50%;
    background: inherit;
}

.header-cloud--1 { top: 20px; left: 52%; width: 52px; }
.header-cloud--2 { top: 38px; left: 80%; width: 34px; animation-delay: -4.5s; }

.header-orb {
    position: absolute;
    border-radius: 50%;
    box-shadow: 0 4px 6px -2px rgba(var(--px-shadow-rgb), 0.35);
    animation: header-bob 4.2s ease-in-out infinite;
}

.header-orb--1 {
    top: 14px;
    right: 9%;
    width: 18px;
    height: 18px;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #FFE066 34%, #F59E0B 78%, #B45309 100%);
}

.header-orb--2 {
    top: 50px;
    left: 40%;
    width: 11px;
    height: 11px;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #FF9EB5 36%, #E11D48 100%);
    animation-delay: -1.4s;
}

.header-orb--3 {
    top: 24px;
    left: 33%;
    width: 9px;
    height: 9px;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #7DE7F7 36%, #0891B2 100%);
    animation-delay: -2.8s;
}

/* 3D back key: the button is the ledge, the face rides above it */
.classroom-back-btn {
    position: absolute;
    top: 18px;
    left: 18px;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: var(--px-ledge);
    cursor: pointer;
    filter: drop-shadow(0 4px 5px rgba(var(--px-shadow-rgb), 0.25));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.back-btn-face {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 40px;
    padding: 0 0.95rem;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    box-shadow: inset 0 2px 0 var(--px-rim-top);
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--px-ink);
    transform: translateY(-4px);
    transition: transform 0.25s var(--spring-bounce);
}

.classroom-back-btn:active .back-btn-face {
    transform: translateY(-1px) scale(0.98, 0.94);
    transition-duration: 0.06s;
}

.classroom-back-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

/* Gold 3D trophy key mirroring the back key on the scene's top-right */
.classroom-ranking-btn {
    position: absolute;
    top: 18px;
    right: 18px;
    z-index: 2;
    border-radius: 999px;
    background: #B45309;
    text-decoration: none;
    filter: drop-shadow(0 4px 6px rgba(180, 83, 9, 0.35));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.ranking-btn-face {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 40px;
    padding: 0 0.95rem 0 0.7rem;
    border-radius: inherit;
    background: linear-gradient(180deg, #FFE98A 0%, #FCC93A 55%, #F59E0B 100%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.7), inset 0 -2px 0 rgba(0, 0, 0, 0.1);
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 700;
    color: #5A2D00;
    white-space: nowrap;
    transform: translateY(-4px);
    transition: transform 0.25s var(--spring-bounce);
}

.ranking-trophy-svg {
    width: 20px;
    height: 20px;
    color: #B45309;
    filter: drop-shadow(0 1px 0 rgba(255, 255, 255, 0.6));
}

.classroom-ranking-btn:active .ranking-btn-face {
    transform: translateY(-1px) scale(0.98, 0.94);
    transition-duration: 0.06s;
}

.classroom-ranking-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

@media (hover: hover) {
    .classroom-ranking-btn:hover .ranking-btn-face {
        transform: translateY(-6px);
    }

    .classroom-ranking-btn:hover .ranking-trophy-svg {
        animation: trophy-wiggle 0.6s var(--spring-bounce);
    }
}

@keyframes trophy-wiggle {
    0%, 100% { transform: rotate(0deg) scale(1); }
    40% { transform: rotate(-14deg) scale(1.15); }
    70% { transform: rotate(8deg) scale(0.96); }
}

/* Tight phones: both keys share the scene, so the ranking key drops its label */
@media (max-width: 370px) {
    .ranking-btn-face {
        padding: 0 0.7rem;
    }

    .ranking-btn-face > span {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
    }
}

.classroom-header-main {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    min-width: 0;
    padding: 0 0.55rem;
}

/* Clay medallion that breaks out of the scene's bottom edge */
.classroom-header-icon {
    position: relative;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 72px;
    height: 72px;
    margin-top: -42px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%,
        color-mix(in srgb, var(--room-accent) 30%, #FFFFFF) 0%,
        var(--room-accent) 58%,
        var(--room-ledge) 100%);
    border: 4px solid var(--px-surface-card);
    box-shadow:
        0 5px 0 var(--room-ledge),
        0 14px 18px -8px color-mix(in srgb, var(--room-accent) 60%, rgba(var(--px-shadow-rgb), 0.6));
    animation: header-medallion-pop 0.7s var(--spring-bounce) 0.1s backwards;
}

.classroom-header-icon::before {
    content: '';
    position: absolute;
    top: 8px;
    left: 13px;
    width: 22px;
    height: 11px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: rotate(-28deg);
}

.header-icon-glyph {
    position: relative;
    font-size: 2rem;
    line-height: 1;
    filter: drop-shadow(0 2px 0 rgba(0, 0, 0, 0.18));
}

.classroom-header-copy {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
    padding-top: 0.35rem;
}

.classroom-header-kicker {
    font-family: var(--font-display);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--room-accent) 55%, var(--px-ink));
}

.classroom-header-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.3rem, 1rem + 2.4vw, 1.9rem);
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.01em;
    color: var(--px-ink);
    overflow-wrap: anywhere;
}

.classroom-header-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.2rem;
}

.classroom-meta-chip {
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    background: color-mix(in srgb, var(--room-accent) 12%, var(--px-surface-card));
    box-shadow: inset 0 2px 3px rgba(var(--px-shadow-rgb), 0.1);
    font-size: 0.76rem;
    font-weight: 800;
    color: color-mix(in srgb, var(--room-accent) 40%, var(--px-ink));
}

.classroom-joined-banner {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin: 0 0.55rem;
    padding: 0.35rem 0.35rem 0.35rem 0.85rem;
    border-radius: 18px;
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border: 2px solid var(--px-tint-success-line);
    box-shadow: 0 3px 0 var(--px-tint-success-line);
    color: var(--px-tint-success-ink);
    font-size: 0.85rem;
    font-weight: 700;
}

.classroom-joined-text {
    flex: 1;
}

.classroom-joined-dismiss {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border: 0;
    border-radius: 14px;
    background: transparent;
    color: inherit;
    cursor: pointer;
    font-size: 0.9rem;
}

.classroom-joined-dismiss:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 1px;
}

.alert-pop-enter-active {
    transition: transform 0.4s var(--spring-bounce), opacity 0.2s ease;
}

.alert-pop-leave-active {
    transition: transform 0.18s ease, opacity 0.18s ease;
}

.alert-pop-enter-from,
.alert-pop-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}

/* ==========================================================================
   1. MODULE REALM CARDS
   Phone: compact row card (square art beside copy). ≥600px: tall cards.
   ========================================================================== */
.clay-realms-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.95rem;
    padding-bottom: 1.25rem;
}

.theme-forest { --biome-accent: #059669; --biome-lit: #34D399; --biome-ledge: #065F46; }
.theme-ocean { --biome-accent: #0284C7; --biome-lit: #38BDF8; --biome-ledge: #075985; }
.theme-cosmic { --biome-accent: #7C3AED; --biome-lit: #A78BFA; --biome-ledge: #5B21B6; }
.theme-volcano { --biome-accent: #EA580C; --biome-lit: #FB923C; --biome-ledge: #9A3412; }

/* Everything else derives from the three biome colours + the active world */
.clay-realm-card,
.clay-bottom-sheet {
    --card-border: color-mix(in srgb, var(--biome-accent) 30%, var(--px-surface-card));
    --card-soft: color-mix(in srgb, var(--biome-accent) 11%, var(--px-surface-card));
    --biome-ink: color-mix(in srgb, var(--biome-accent) 48%, var(--px-ink));
    --btn-grad: linear-gradient(180deg, var(--biome-lit) 0%, var(--biome-accent) 62%);
}

.clay-realm-card {
    position: relative;
    display: grid;
    grid-template-columns: 6.9rem minmax(0, 1fr);
    grid-template-areas:
        'art top'
        'art body'
        'foot foot';
    column-gap: 0.8rem;
    row-gap: 0.5rem;
    min-width: 0;
    padding: 0.7rem;
    border-radius: 26px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--card-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 color-mix(in srgb, var(--biome-accent) 50%, var(--px-ledge)),
        0 16px 26px -12px color-mix(in srgb, var(--biome-accent) 50%, transparent),
        inset 0 2px 0 var(--px-rim-top),
        inset 0 -4px 0 rgba(var(--px-shadow-rgb), 0.05);
    cursor: pointer;
    user-select: none;
    outline: none;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    content-visibility: auto;
    contain-intrinsic-size: auto 200px;
    transition: transform 0.3s var(--spring-bounce);
    animation: realm-pop 0.55s var(--spring-bounce) backwards;
    animation-delay: calc(var(--i, 0) * 60ms);
}

.clay-realm-card:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

.clay-realm-card:active,
.clay-realm-card.is-pressed {
    transform: translateY(3px) scale(0.985, 0.97);
    transition-duration: 0.08s;
}

.card-top-gloss {
    position: absolute;
    top: 3px;
    left: 18px;
    right: 18px;
    height: 10px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, calc(0.9 * var(--px-gloss, 1))) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

/* Top row: biome chip + XP candy */
.realm-card-top {
    grid-area: top;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    min-width: 0;
}

.clay-biome-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    min-width: 0;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    background: var(--card-soft);
    box-shadow: inset 0 2px 3px rgba(var(--px-shadow-rgb), 0.1);
    color: var(--biome-ink);
    font-family: var(--font-display);
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.biome-dot-indicator {
    width: 8px;
    height: 8px;
    flex-shrink: 0;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 28%, #FFFFFF 0%, var(--biome-lit) 40%, var(--biome-accent) 100%);
}

.clay-xp-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    flex-shrink: 0;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFE066 0%, #FBBF24 100%);
    box-shadow: 0 2px 0 #B45309, inset 0 1.5px 0 rgba(255, 255, 255, 0.7);
    color: #5A2D00;
    font-family: var(--font-display);
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.xp-mini-svg {
    width: 11px;
    height: 11px;
    color: #92400E;
}

/* Diorama art window */
.clay-diorama-frame {
    grid-area: art;
    align-self: start;
    padding: 4px;
    border-radius: 20px;
    background: var(--card-border);
    box-shadow:
        inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.14),
        0 2px 0 var(--px-rim-top);
}

.diorama-viewport {
    position: relative;
    aspect-ratio: 1 / 1;
    border-radius: 16px;
    overflow: hidden;
    background: color-mix(in srgb, var(--biome-accent) 25%, var(--px-surface-card));
}

.diorama-img,
.diorama-fallback-scene,
.fallback-world-svg {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.diorama-img,
.fallback-world-svg {
    transition: transform 0.4s var(--spring-bounce);
}

.diorama-new-pill {
    position: absolute;
    top: 6px;
    left: 6px;
    padding: 0.14rem 0.45rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FB7185 0%, #E11D48 100%);
    box-shadow: 0 2px 0 #9F1239, inset 0 1.5px 0 rgba(255, 255, 255, 0.55);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    animation: new-pill-bounce 2.6s ease-in-out infinite;
}

/* Solid pod — no backdrop blur on a scrolling list */
.diorama-stars-pod {
    position: absolute;
    bottom: 6px;
    left: 50%;
    display: inline-flex;
    align-items: center;
    gap: 0.1rem;
    padding: 0.16rem 0.4rem;
    border-radius: 9999px;
    background: rgba(24, 20, 59, 0.74);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
    transform: translateX(-50%);
}

.mini-star-svg {
    width: 13px;
    height: 13px;
}

.star-earned {
    color: #FACC15;
    filter: drop-shadow(0 1px 1px rgba(180, 83, 9, 0.6));
}

.star-empty {
    color: rgba(255, 255, 255, 0.32);
}

/* Body copy + progress */
.realm-card-body {
    grid-area: body;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.realm-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.04rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--px-ink);
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.realm-description {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.38;
    color: var(--px-ink-muted);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.realm-progress-block {
    display: flex;
    flex-direction: column;
    gap: 0.22rem;
    margin-top: auto;
    padding-top: 0.25rem;
}

.realm-progress-labels {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--px-ink-muted);
}

.progress-value {
    font-family: var(--font-display);
    font-weight: 700;
    color: var(--biome-ink);
}

/* Pressed-in well with a glossy candy fill */
.clay-mini-track {
    width: 100%;
    height: 12px;
    padding: 2px;
    border-radius: 9999px;
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    overflow: hidden;
}

.clay-mini-fill {
    position: relative;
    height: 100%;
    border-radius: 9999px;
    background: var(--btn-grad);
    box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.12);
}

.mini-fill-shine {
    position: absolute;
    top: 1px;
    left: 4px;
    right: 4px;
    height: 3px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.55);
}

/* Footer: counters + 3D PLAY key */
.realm-card-footer {
    grid-area: foot;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.realm-counters {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
}

.clay-counter-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.28rem 0.6rem;
    border-radius: 12px;
    background: var(--px-surface-card);
    box-shadow: 0 2px 0 var(--card-border), inset 0 1.5px 0 var(--px-rim-top);
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--px-ink);
    white-space: nowrap;
}

.counter-svg {
    width: 13px;
    height: 13px;
    flex-shrink: 0;
    color: var(--biome-accent);
}

.clay-play-btn {
    flex-shrink: 0;
    border-radius: 9999px;
    background: var(--biome-ledge);
    filter: drop-shadow(0 5px 6px color-mix(in srgb, var(--biome-accent) 40%, transparent));
}

.play-btn-face {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    min-height: 44px;
    padding: 0 1.1rem;
    border-radius: inherit;
    background: var(--btn-grad);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -3px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 0.86rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #FFFFFF;
    text-shadow: 0 1px 0 var(--biome-ledge);
    transform: translateY(-5px);
    transition: transform 0.25s var(--spring-bounce);
}

.clay-realm-card:active .play-btn-face,
.clay-realm-card.is-pressed .play-btn-face {
    transform: translateY(-1px) scale(0.97, 0.93);
    transition-duration: 0.06s;
}

.play-arrow-svg {
    width: 14px;
    height: 14px;
}

@media (hover: hover) {
    .clay-realm-card:hover {
        transform: translateY(-6px) rotate(-0.4deg);
    }

    .clay-realm-card:hover .diorama-img,
    .clay-realm-card:hover .fallback-world-svg {
        transform: scale(1.07);
    }

    .clay-realm-card:hover .play-btn-face {
        transform: translateY(-7px);
    }
}

/* ==========================================================================
   2. EMPTY REALMS STATE
   ========================================================================== */
.clay-empty-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
    padding: 2rem 1.25rem;
    border-radius: 30px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, color-mix(in srgb, var(--px-tint-magic-bg) 55%, var(--px-surface-card)) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 var(--px-tint-magic-line),
        0 18px 30px -14px rgba(var(--px-shadow-rgb), 0.4),
        inset 0 2px 0 var(--px-rim-top);
    text-align: center;
}

.clay-empty-badge {
    position: relative;
    display: grid;
    place-items: center;
    width: 76px;
    height: 76px;
    margin-bottom: 0.25rem;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, var(--px-orb-5) 45%, color-mix(in srgb, var(--px-orb-5) 60%, #18143B) 100%);
    border: 4px solid var(--px-surface-card);
    box-shadow: 0 5px 0 color-mix(in srgb, var(--px-orb-5) 50%, #18143B), 0 14px 18px -8px rgba(var(--px-shadow-rgb), 0.45);
    animation: header-bob 3.6s ease-in-out infinite;
}

.empty-badge-emoji {
    font-size: 2.1rem;
    line-height: 1;
    filter: drop-shadow(0 2px 0 rgba(0, 0, 0, 0.18));
}

.empty-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--px-ink);
}

.empty-desc {
    margin: 0;
    max-width: 22rem;
    font-size: 0.86rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--px-ink-muted);
}

.clay-reset-btn {
    margin-top: 0.4rem;
    padding: 0;
    border: 0;
    border-radius: 9999px;
    background: var(--px-secondary-pedestal);
    cursor: pointer;
    filter: drop-shadow(0 5px 6px rgba(var(--px-shadow-rgb), 0.28));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.reset-btn-face {
    display: block;
    min-height: 46px;
    padding: 0.75rem 1.35rem;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 60%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -3px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--px-secondary-ink);
    transform: translateY(-5px);
    transition: transform 0.25s var(--spring-bounce);
}

.clay-reset-btn:active .reset-btn-face {
    transform: translateY(-1px) scale(0.98, 0.95);
    transition-duration: 0.06s;
}

.clay-reset-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

/* ==========================================================================
   3. REALM BRIEFING — BOTTOM SHEET ON PHONES, CENTRED CARD ON DESKTOP
   ========================================================================== */
.clay-sheet-backdrop {
    /* Teleported to <body>, so it re-declares the page's type & motion tokens */
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    background: rgba(var(--px-shadow-rgb), 0.55);
    backdrop-filter: blur(6px);
}

.clay-bottom-sheet {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    width: 100%;
    max-width: 520px;
    max-height: min(92dvh, 780px);
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 0.7rem 1.1rem calc(1.2rem + env(safe-area-inset-bottom, 0px));
    border-radius: 32px 32px 0 0;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--card-soft) 100%);
    border-top: 3px solid var(--px-card-border);
    box-shadow:
        0 -12px 36px rgba(var(--px-shadow-rgb), 0.3),
        inset 0 2px 0 var(--px-rim-top);
    color: var(--px-ink);
    font-family: var(--font-body);
}

.sheet-pill-handle {
    flex-shrink: 0;
    width: 52px;
    height: 6px;
    margin: 0 auto 0.1rem;
    border-radius: 9999px;
    background: var(--px-line-strong);
}

.sheet-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.sheet-biome-tag {
    min-width: 0;
    padding: 0.3rem 0.8rem;
    border-radius: 9999px;
    background: var(--card-soft);
    box-shadow: inset 0 2px 3px rgba(var(--px-shadow-rgb), 0.1);
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--biome-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 3D close key */
.clay-close-btn {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    padding: 0;
    border: 0;
    border-radius: 16px;
    background: var(--px-danger-pedestal);
    cursor: pointer;
    filter: drop-shadow(0 4px 4px rgba(var(--px-shadow-rgb), 0.25));
    -webkit-tap-highlight-color: transparent;
}

.close-btn-face {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-danger-lit) 0%, var(--px-danger) 62%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -2px 0 rgba(0, 0, 0, 0.12);
    color: #FFFFFF;
    transform: translateY(-4px);
    transition: transform 0.25s var(--spring-bounce);
}

.clay-close-btn:active .close-btn-face {
    transform: translateY(-1px) scale(0.96, 0.92);
    transition-duration: 0.06s;
}

.clay-close-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.close-svg {
    width: 16px;
    height: 16px;
}

/* World art window + medallion breaking its frame */
.sheet-art {
    position: relative;
    flex-shrink: 0;
    margin-bottom: 0.4rem;
    padding: 5px;
    border-radius: 24px;
    background: var(--card-border);
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.14);
}

.sheet-art-viewport {
    aspect-ratio: 16 / 7;
    border-radius: 19px;
    overflow: hidden;
    background: color-mix(in srgb, var(--biome-accent) 25%, var(--px-surface-card));
}

.sheet-art-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sheet-medallion {
    position: absolute;
    right: 18px;
    bottom: -24px;
    display: grid;
    place-items: center;
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%,
        color-mix(in srgb, var(--biome-accent) 30%, #FFFFFF) 0%,
        var(--biome-accent) 58%,
        var(--biome-ledge) 100%);
    border: 4px solid var(--px-surface-card);
    box-shadow:
        0 5px 0 var(--biome-ledge),
        0 12px 16px -6px color-mix(in srgb, var(--biome-accent) 60%, rgba(var(--px-shadow-rgb), 0.6));
}

.sheet-medallion::before {
    content: '';
    position: absolute;
    top: 7px;
    left: 11px;
    width: 18px;
    height: 9px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: rotate(-28deg);
}

.sheet-medallion-glyph {
    position: relative;
    font-size: 1.7rem;
    line-height: 1;
}

.sheet-title {
    margin: 0;
    padding-right: 4.4rem;
    font-family: var(--font-display);
    font-size: clamp(1.3rem, 1.1rem + 1.4vw, 1.6rem);
    font-weight: 700;
    line-height: 1.15;
    color: var(--px-ink);
    overflow-wrap: anywhere;
}

.sheet-desc {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--px-ink-muted);
}

.sheet-loot-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem;
}

.sheet-loot-pod {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    min-width: 0;
    padding: 0.65rem 0.4rem;
    border-radius: 18px;
    border: 2px solid var(--px-card-border);
    text-align: center;
}

.pod-stories {
    background: linear-gradient(180deg, color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-info-bg) 100%);
    box-shadow: 0 4px 0 var(--px-tint-info-line), inset 0 2px 0 var(--px-rim-top);
    color: var(--px-tint-info-ink);
}

.pod-quizzes {
    background: linear-gradient(180deg, color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-success-bg) 100%);
    box-shadow: 0 4px 0 var(--px-tint-success-line), inset 0 2px 0 var(--px-rim-top);
    color: var(--px-tint-success-ink);
}

.pod-reward {
    background: linear-gradient(180deg, color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-warn-bg) 100%);
    box-shadow: 0 4px 0 var(--px-tint-warn-line), inset 0 2px 0 var(--px-rim-top);
    color: var(--px-tint-warn-ink);
}

.loot-lbl {
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    opacity: 0.85;
}

.loot-val {
    font-family: var(--font-display);
    font-size: 0.86rem;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.sheet-guardian-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.65rem 0.9rem;
    border-radius: 18px;
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.guardian-name {
    font-family: var(--font-display);
    color: var(--biome-ink);
    text-align: right;
}

/* 3D launch key in the module's own biome colour */
.clay-sheet-launch-btn {
    flex-shrink: 0;
    width: 100%;
    margin-top: 0.15rem;
    padding: 0;
    border: 0;
    border-radius: 9999px;
    background: var(--biome-ledge);
    cursor: pointer;
    filter: drop-shadow(0 8px 10px color-mix(in srgb, var(--biome-accent) 40%, transparent));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.launch-btn-face {
    display: block;
    min-height: 54px;
    padding: 0.95rem 1.5rem;
    border-radius: inherit;
    background: var(--btn-grad);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.5), inset 0 -4px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #FFFFFF;
    text-shadow: 0 1px 0 var(--biome-ledge);
    transform: translateY(-6px);
    transition: transform 0.25s var(--spring-bounce);
}

@media (hover: hover) {
    .clay-sheet-launch-btn:hover .launch-btn-face {
        transform: translateY(-8px);
    }

    .classroom-back-btn:hover .back-btn-face,
    .clay-close-btn:hover .close-btn-face {
        transform: translateY(-6px);
    }

    .clay-reset-btn:hover .reset-btn-face {
        transform: translateY(-7px);
    }
}

.clay-sheet-launch-btn:active .launch-btn-face {
    transform: translateY(-2px) scale(0.98, 0.95);
    transition-duration: 0.06s;
}

.clay-sheet-launch-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

/* Sheet motion: backdrop fades, sheet springs up from the thumb zone */
.clay-sheet-enter-active {
    transition: opacity 0.22s ease;
}

.clay-sheet-leave-active {
    transition: opacity 0.2s ease;
}

.clay-sheet-enter-from,
.clay-sheet-leave-to {
    opacity: 0;
}

.clay-sheet-enter-active .clay-bottom-sheet {
    transition: transform 0.48s var(--spring-bounce);
}

.clay-sheet-leave-active .clay-bottom-sheet {
    transition: transform 0.2s ease-in;
}

.clay-sheet-enter-from .clay-bottom-sheet,
.clay-sheet-leave-to .clay-bottom-sheet {
    transform: translateY(105%);
}

/* ==========================================================================
   4. MOTION
   ========================================================================== */
@keyframes realm-pop {
    0% { transform: scale(0.7); opacity: 0; }
    60% { transform: scale(1.03, 0.97); opacity: 1; }
    80% { transform: scale(0.99, 1.02); }
    100% { transform: none; }
}

@keyframes header-medallion-pop {
    0% { transform: scale(0.4); }
    55% { transform: scale(1.14, 0.88); }
    78% { transform: scale(0.95, 1.06); }
    100% { transform: scale(1); }
}

@keyframes header-bob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

@keyframes header-drift {
    from { transform: translateX(-10px); }
    to { transform: translateX(12px); }
}

@keyframes new-pill-bounce {
    0%, 82%, 100% { transform: translateY(0) scale(1); }
    88% { transform: translateY(-3px) scale(1.08, 0.94); }
    94% { transform: translateY(0) scale(0.96, 1.04); }
}

/* ==========================================================================
   5. RESPONSIVE: tiny phones, then tablet & desktop
   ========================================================================== */
@media (max-width: 370px) {
    .clay-realm-card {
        grid-template-columns: 5.9rem minmax(0, 1fr);
        column-gap: 0.65rem;
        padding: 0.6rem;
    }

    .clay-xp-pill {
        display: none;
    }

    .classroom-header-icon {
        width: 62px;
        height: 62px;
    }

    .header-icon-glyph {
        font-size: 1.7rem;
    }

    .loot-val {
        font-size: 0.78rem;
    }
}

@media (min-width: 600px) {
    .clay-realms-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.2rem;
    }

    .clay-realm-card {
        grid-template-columns: minmax(0, 1fr);
        grid-template-areas:
            'top'
            'art'
            'body'
            'foot';
        row-gap: 0.7rem;
        padding: 0.9rem;
        border-radius: 30px;
        contain-intrinsic-size: auto 400px;
    }

    .clay-diorama-frame {
        align-self: stretch;
        padding: 5px;
        border-radius: 22px;
    }

    .diorama-viewport {
        aspect-ratio: 16 / 10;
        border-radius: 17px;
    }

    .diorama-stars-pod {
        left: 8px;
        bottom: 8px;
        transform: none;
    }

    .realm-title {
        font-size: 1.15rem;
    }

    .header-scene {
        height: 128px;
    }

    .header-hills {
        height: 70px;
    }

    .clay-classroom-header {
        padding: 10px 10px 1.15rem;
    }

    .classroom-header-main {
        padding: 0 0.9rem;
    }

    .classroom-header-icon {
        width: 86px;
        height: 86px;
        margin-top: -50px;
    }

    .header-icon-glyph {
        font-size: 2.4rem;
    }
}

@media (min-width: 720px) {
    .clay-sheet-backdrop {
        align-items: center;
        padding: 1.25rem;
    }

    .clay-bottom-sheet {
        padding: 1.2rem 1.35rem 1.4rem;
        border-radius: 34px;
        border: 3px solid var(--px-card-border);
        box-shadow:
            0 8px 0 color-mix(in srgb, var(--biome-accent) 45%, var(--px-ledge)),
            0 26px 48px rgba(var(--px-shadow-rgb), 0.35),
            inset 0 2px 0 var(--px-rim-top);
    }

    .sheet-pill-handle {
        display: none;
    }

    .clay-sheet-enter-from .clay-bottom-sheet,
    .clay-sheet-leave-to .clay-bottom-sheet {
        transform: translateY(24px) scale(0.9);
    }
}

@media (min-width: 1100px) {
    .clay-realms-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.35rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .clay-realm-card,
    .clay-realm-card *,
    .clay-classroom-header *,
    .clay-empty-badge,
    .clay-bottom-sheet,
    .clay-bottom-sheet * {
        animation: none !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
