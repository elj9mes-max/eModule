<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import StudentLayout from '../../Components/StudentLayout.vue';

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
});

const selectedBiome = ref('all');
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

const totalQuestsCount = computed(() => {
    return (props.modules || []).reduce(
        (sum, mod) => sum + Number(mod.lectures_count || 0) + Number(mod.quizzes_count || 0),
        0,
    );
});

const getModuleProgress = (mod, index) => {
    const totalItems = Number(mod.lectures_count || 0) + Number(mod.quizzes_count || 0);
    if (totalItems === 0) {
        return index === 0 ? 35 : 20;
    }

    return Math.min(100, Math.max(25, ((mod.id * 23 + totalItems * 14) % 75) + 20));
};

const getEarnedStars = (mod, index) => {
    const progress = getModuleProgress(mod, index);
    if (progress >= 80) {
        return 3;
    }
    if (progress >= 45) {
        return 2;
    }

    return 1;
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
        router.visit(`/students/modules/${targetMod.slug}`);

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
        active-nav="modules"
    >
        <section class="clay-adventure-dashboard" aria-labelledby="modules-heading">
            <!-- 1. LEVEL 1 HIERARCHY: 3D CARTOON CLAY HERO QUEST BANNER -->
            <div class="clay-hero-banner">
                <!-- Specular Top Bubble Gloss & Floating 3D Clouds -->
                <span class="hero-bubble-gloss" aria-hidden="true"></span>
                <div class="hero-ambient-orb orb-1" aria-hidden="true"></div>
                <div class="hero-ambient-orb orb-2" aria-hidden="true"></div>

                <div class="hero-top-row">
                    <span class="clay-quest-kicker">
                        <span class="kicker-spark-dot" aria-hidden="true"></span>
                        FEATURED ADVENTURE
                    </span>

                    <span class="clay-xp-burst-pill">
                        <svg viewBox="0 0 16 16" class="bolt-svg" aria-hidden="true">
                            <path d="M9.5 1L3 9h4.5L6.5 15L13 7H8.5L9.5 1z" fill="currentColor" />
                        </svg>
                        +{{ featuredModule?.xp_bonus || 250 }} XP BONUS
                    </span>
                </div>

                <div class="hero-main-row">
                    <div class="hero-copy-col">
                        <h1 id="modules-heading" class="hero-title">
                            {{ featuredModule ? featuredModule.title : 'Explore Reading Realms!' }}
                        </h1>
                        <p class="hero-subtitle">
                            {{
                                featuredModule?.description ||
                                'Jump into colorful 3D story worlds, complete reading quests, and collect golden stars!'
                            }}
                        </p>
                    </div>

                    <!-- 3D Cartoon Floating Treasure Shield Mascot Badge -->
                    <div class="hero-mascot-pod" aria-hidden="true">
                        <svg viewBox="0 0 72 72" class="hero-mascot-svg">
                            <defs>
                                <linearGradient id="shieldClayBody" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#FDE047" />
                                    <stop offset="50%" stop-color="#F59E0B" />
                                    <stop offset="100%" stop-color="#D97706" />
                                </linearGradient>
                                <linearGradient id="shieldInnerGem" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#38BDF8" />
                                    <stop offset="100%" stop-color="#2563EB" />
                                </linearGradient>
                            </defs>
                            <path
                                d="M36 6L60 16V34C60 50.5 49.5 62.2 36 67C22.5 62.2 12 50.5 12 34V16L36 6Z"
                                fill="url(#shieldClayBody)"
                                stroke="#FFFFFF"
                                stroke-width="3"
                            />
                            <path
                                d="M36 12L54 19.5V33.5C54 46.5 45.8 55.8 36 59.8C26.2 55.8 18 46.5 18 33.5V19.5L36 12Z"
                                fill="url(#shieldInnerGem)"
                            />
                            <path
                                d="M36 20L39.6 27.8L48 28.9L41.8 34.6L43.5 43L36 38.8L28.5 43L30.2 34.6L24 28.9L32.4 27.8L36 20Z"
                                fill="#FEF08A"
                            />
                            <ellipse cx="28" cy="19" rx="5" ry="2.2" transform="rotate(-25 28 19)" fill="#FFFFFF" fill-opacity="0.75" />
                        </svg>
                    </div>
                </div>

                <div class="hero-bottom-row">
                    <div class="hero-stat-pills">
                        <span class="hero-glass-pill">
                            <svg viewBox="0 0 18 18" class="pill-svg" aria-hidden="true">
                                <path d="M3 4.5A1.5 1.5 0 0 1 4.5 3h3.8a1.5 1.5 0 0 1 1.5 1.5v10.2a1.2 1.2 0 0 0-1.2-1.2H4.5A1.5 1.5 0 0 1 3 12V4.5z" fill="#FDE047" />
                                <path d="M15 4.5A1.5 1.5 0 0 0 13.5 3H9.7a1.5 1.5 0 0 0-1.5 1.5v10.2a1.2 1.2 0 0 1 1.2-1.2h4.1A1.5 1.5 0 0 0 15 12V4.5z" fill="#FEF08A" />
                            </svg>
                            {{ modules.length }} {{ modules.length === 1 ? 'World' : 'Worlds' }}
                        </span>

                        <span class="hero-glass-pill">
                            <svg viewBox="0 0 18 18" class="pill-svg" aria-hidden="true">
                                <circle cx="9" cy="9" r="7" fill="none" stroke="#6EE7B7" stroke-width="2.2" />
                                <circle cx="9" cy="9" r="3" fill="#6EE7B7" />
                            </svg>
                            {{ totalQuestsCount }} Quests
                        </span>
                    </div>

                    <button
                        v-if="featuredModule"
                        type="button"
                        class="clay-hero-cta-btn"
                        @click="openRealmPreview(featuredModule)"
                    >
                        <span class="cta-btn-shine" aria-hidden="true"></span>
                        <span>START QUEST</span>
                        <svg viewBox="0 0 20 20" class="cta-arrow-svg" aria-hidden="true">
                            <path d="M7 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- 2. LEVEL 2 HIERARCHY: WORLD MAP HEADER & INTERACTIVE 3D CLAY FILTER PILLS -->
            <div class="clay-controls-section">
                <div class="controls-header-row">
                    <div class="controls-title-group">
                        <span class="clay-map-badge" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="map-badge-svg">
                                <path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6z" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" />
                                <line x1="9" y1="3" x2="9" y2="18" stroke="currentColor" stroke-width="2.4" />
                                <line x1="15" y1="6" x2="15" y2="21" stroke="currentColor" stroke-width="2.4" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="controls-title">Story Worlds</h2>
                            <p class="controls-subtitle">Tap a 3D island card to begin your quest</p>
                        </div>
                    </div>

                    <span class="clay-world-counter">
                        {{ filteredModules.length }} {{ filteredModules.length === 1 ? 'REALM' : 'REALMS' }}
                    </span>
                </div>

                <!-- Scrollable 3D Candy Clay Biome Filter Pills -->
                <div class="clay-filter-strip" role="tablist" aria-label="Filter story worlds by biome">
                    <button
                        v-for="biome in biomeFilters"
                        :key="biome.id"
                        type="button"
                        role="tab"
                        :aria-selected="selectedBiome === biome.id"
                        class="clay-filter-pill"
                        :class="{ 'is-active': selectedBiome === biome.id }"
                        :style="
                            selectedBiome === biome.id
                                ? {
                                      background: biome.activeBg,
                                      '--pill-ledge': biome.activeLedge,
                                  }
                                : {}
                        "
                        @click="selectedBiome = biome.id"
                    >
                        <span
                            class="filter-biome-dot"
                            :style="{ backgroundColor: selectedBiome === biome.id ? '#FFFFFF' : biome.dot }"
                            aria-hidden="true"
                        ></span>
                        <span>{{ biome.label }}</span>
                    </button>
                </div>
            </div>

            <!-- 3. LEVEL 3 HIERARCHY: 2-COLUMN 3D CARTOON CLAY REALM CARDS GRID -->
            <div v-if="filteredModules.length > 0" class="clay-realms-grid">
                <article
                    v-for="(mod, idx) in filteredModules"
                    :key="mod.id"
                    class="clay-realm-card"
                    :class="[
                        `theme-${mod.theme || 'forest'}`,
                        { 'is-pressed': pressedCardId === mod.id },
                    ]"
                    role="button"
                    tabindex="0"
                    :aria-label="'Open learning module: ' + mod.title"
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
                            />
                            <!-- Vibrant 3D Cartoon Vector World Illustration Fallback -->
                            <div v-else class="diorama-fallback-scene" aria-hidden="true">
                                <svg viewBox="0 0 600 340" preserveAspectRatio="xMidYMid slice" class="fallback-world-svg">
                                    <defs>
                                        <linearGradient :id="`skyGrad-${mod.id}`" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#38BDF8" />
                                            <stop offset="55%" stop-color="#BAE6FD" />
                                            <stop offset="100%" stop-color="#FEF9C3" />
                                        </linearGradient>
                                        <linearGradient :id="`peakA-${mod.id}`" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#A78BFA" />
                                            <stop offset="100%" stop-color="#6366F1" />
                                        </linearGradient>
                                        <linearGradient :id="`peakB-${mod.id}`" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#F472B6" />
                                            <stop offset="100%" stop-color="#DB2777" />
                                        </linearGradient>
                                        <linearGradient :id="`meadowA-${mod.id}`" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#4ADE80" />
                                            <stop offset="100%" stop-color="#16A34A" />
                                        </linearGradient>
                                        <linearGradient :id="`meadowB-${mod.id}`" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#34D399" />
                                            <stop offset="100%" stop-color="#059669" />
                                        </linearGradient>
                                        <linearGradient :id="`pathGold-${mod.id}`" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#FDE047" />
                                            <stop offset="100%" stop-color="#F59E0B" />
                                        </linearGradient>
                                    </defs>

                                    <rect width="600" height="340" :fill="`url(#skyGrad-${mod.id})`" />

                                    <!-- 3D Cartoon Sun -->
                                    <circle cx="495" cy="70" r="38" fill="#FDE047" />
                                    <circle cx="495" cy="70" r="28" fill="#FEF9C3" />

                                    <!-- Puffy White Cartoon Clouds -->
                                    <path d="M65 110 C65 92 84 78 108 78 C126 78 142 90 148 102 C158 98 170 104 174 114 C182 116 188 124 188 132 C188 140 178 146 166 146 L70 146 C58 146 51 136 54 126 C54 118 59 113 65 110 Z" fill="#FFFFFF" />
                                    <path d="M340 82 C340 66 358 54 380 54 C398 54 414 64 420 76 C430 72 442 78 446 86 C454 88 460 94 460 102 C460 110 450 116 438 116 L345 116 C332 116 326 106 329 96 C329 90 334 85 340 82 Z" fill="#FFFFFF" />

                                    <!-- Candy Mountains with Snow Caps -->
                                    <path d="M80 255 L180 105 L280 255 Z" :fill="`url(#peakA-${mod.id})`" />
                                    <path d="M180 105 L155 148 C168 156 192 156 205 148 Z" fill="#FFFFFF" />

                                    <path d="M230 265 L350 85 L470 265 Z" :fill="`url(#peakB-${mod.id})`" />
                                    <path d="M350 85 L315 145 C335 156 365 156 385 145 Z" fill="#FFFFFF" />

                                    <!-- Rolling Toy Hills -->
                                    <path d="M-20 250 Q120 185 270 235 T620 215 L620 340 L-20 340 Z" :fill="`url(#meadowA-${mod.id})`" />
                                    <path d="M-20 278 Q180 225 350 268 T620 250 L620 340 L-20 340 Z" :fill="`url(#meadowB-${mod.id})`" />

                                    <!-- Golden Quest Trail -->
                                    <path d="M250 340 Q282 282 296 244 Q306 225 316 220 L338 223 Q328 230 322 250 Q314 288 354 340 Z" :fill="`url(#pathGold-${mod.id})`" />

                                    <!-- Bubble Cartoon Trees -->
                                    <rect x="82" y="212" width="10" height="34" rx="3" fill="#92400E" />
                                    <circle cx="87" cy="200" r="26" fill="#10B981" />
                                    <circle cx="79" cy="191" r="10" fill="#A7F3D0" fill-opacity="0.75" />

                                    <rect x="488" y="200" width="11" height="38" rx="3" fill="#92400E" />
                                    <circle cx="493.5" cy="188" r="28" fill="#F97316" />
                                    <circle cx="485" cy="178" r="11" fill="#FDE047" fill-opacity="0.75" />
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
                                <span class="progress-title">Quest Progress</span>
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
                            <span>PLAY</span>
                            <svg viewBox="0 0 18 18" class="play-arrow-svg">
                                <path d="M6.5 4.5L11.5 9L6.5 13.5" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </article>
            </div>

            <!-- 4. EMPTY REALMS STATE -->
            <div v-else class="clay-empty-card">
                <div class="clay-empty-badge" aria-hidden="true">
                    <svg viewBox="0 0 48 48" class="empty-svg">
                        <circle cx="24" cy="24" r="18" fill="none" stroke="#6366F1" stroke-width="3" stroke-dasharray="6 6" />
                        <path d="M24 14v12l8 4" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </div>
                <h2 class="empty-title">No Worlds Found Here Yet!</h2>
                <p class="empty-desc">
                    {{
                        selectedBiome === 'all'
                            ? 'Your teacher is crafting new reading worlds and story quests. Check back soon!'
                            : 'No worlds match this biome filter yet. Tap below to see all available worlds.'
                    }}
                </p>
                <button
                    v-if="selectedBiome !== 'all'"
                    type="button"
                    class="clay-reset-btn"
                    @click="selectedBiome = 'all'"
                >
                    Show All Worlds
                </button>
            </div>
        </section>

        <!-- 5. 3D CLAYMORPHISM MOBILE BOTTOM SHEET REALM BRIEFING -->
        <Teleport to="body">
            <Transition name="clay-sheet">
                <div
                    v-if="activeRealmPreview"
                    class="clay-sheet-backdrop"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="`sheet-title-${activeRealmPreview.id}`"
                    @click.self="closeRealmPreview"
                >
                    <div class="clay-bottom-sheet" :class="`theme-${activeRealmPreview.theme || 'forest'}`">
                        <div class="sheet-pill-handle" aria-hidden="true"></div>

                        <div class="sheet-header">
                            <span class="sheet-biome-tag">
                                {{ getBiomeLabel(activeRealmPreview.theme) }} • {{ activeRealmPreview.grade_level || 'All Grades' }}
                            </span>
                            <button
                                type="button"
                                class="clay-close-btn"
                                aria-label="Close realm preview"
                                @click="closeRealmPreview"
                            >
                                <svg viewBox="0 0 20 20" class="close-svg" aria-hidden="true">
                                    <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
                                </svg>
                            </button>
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
                            <span class="cta-btn-shine" aria-hidden="true"></span>
                            <span>LET'S GO!</span>
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
   1. LEVEL 1 HIERARCHY: 3D CLAY HERO QUEST BANNER
   ========================================================================== */
.clay-hero-banner {
    position: relative;
    overflow: hidden;
    border-radius: 30px;
    padding: 1.1rem 1.15rem;
    background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 52%, #EC4899 100%);
    border: 3px solid rgba(255, 255, 255, 0.92);
    box-shadow:
        0 8px 0 #3730A3,
        0 18px 34px rgba(79, 70, 229, 0.32),
        inset 0 4px 8px rgba(255, 255, 255, 0.45),
        inset 0 -6px 10px rgba(49, 46, 129, 0.4);
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.hero-bubble-gloss {
    position: absolute;
    top: 5px;
    left: 22px;
    right: 22px;
    height: 16px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hero-ambient-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.orb-1 {
    width: 140px;
    height: 140px;
    top: -40px;
    right: -30px;
    background: radial-gradient(circle, rgba(254, 240, 138, 0.35) 0%, transparent 70%);
}

.orb-2 {
    width: 120px;
    height: 120px;
    bottom: -45px;
    left: 10%;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.35) 0%, transparent 70%);
}

.hero-top-row {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.clay-quest-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.22rem 0.68rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.22);
    border: 1.5px solid rgba(255, 255, 255, 0.55);
    box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.5);
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #FFFFFF;
}

.kicker-spark-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #4ADE80;
    box-shadow: 0 0 8px #4ADE80;
}

.clay-xp-burst-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.22rem;
    padding: 0.24rem 0.68rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 3px 0 #B45309,
        inset 0 2px 2px rgba(255, 255, 255, 0.85);
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
}

.bolt-svg {
    width: 12px;
    height: 12px;
    color: #B45309;
}

.hero-main-row {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
}

.hero-copy-col {
    flex: 1 1 auto;
    min-width: 0;
}

.hero-title {
    margin: 0 0 0.28rem;
    font-family: var(--font-display);
    font-size: 1.32rem;
    font-weight: 800;
    line-height: 1.15;
    color: #FFFFFF;
    text-shadow: 0 2px 4px rgba(30, 27, 75, 0.35);
}

.hero-subtitle {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 700;
    line-height: 1.42;
    color: #EEF2FF;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.hero-mascot-pod {
    width: 68px;
    height: 68px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.18);
    border: 2px solid rgba(255, 255, 255, 0.55);
    box-shadow:
        0 4px 12px rgba(30, 27, 75, 0.22),
        inset 0 2px 4px rgba(255, 255, 255, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    animation: mascotFloat 4s ease-in-out infinite alternate;
}

@keyframes mascotFloat {
    0% { transform: translateY(0) rotate(-3deg); }
    100% { transform: translateY(-5px) rotate(4deg); }
}

.hero-mascot-svg {
    width: 52px;
    height: 52px;
    filter: drop-shadow(0 4px 6px rgba(30, 27, 75, 0.3));
}

.hero-bottom-row {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    flex-wrap: wrap;
    padding-top: 0.15rem;
}

.hero-stat-pills {
    display: flex;
    align-items: center;
    gap: 0.42rem;
}

.hero-glass-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.65rem;
    border-radius: 14px;
    background: rgba(30, 27, 75, 0.28);
    border: 1.5px solid rgba(255, 255, 255, 0.35);
    font-size: 0.7rem;
    font-weight: 800;
    color: #FFFFFF;
}

.pill-svg {
    width: 14px;
    height: 14px;
}

/* 3D Pushable Toy CTA Button */
.clay-hero-cta-btn {
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 44px;
    padding: 0.48rem 1.1rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #4ADE80 0%, #10B981 55%, #059669 100%);
    color: #FFFFFF;
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 5px 0 #065F46,
        0 8px 16px rgba(16, 185, 129, 0.4),
        inset 0 2px 3px rgba(255, 255, 255, 0.7);
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-shadow: 0 1px 2px rgba(6, 95, 70, 0.5);
    cursor: pointer;
    transition: transform 160ms var(--spring-bounce), box-shadow 160ms ease;
}

.clay-hero-cta-btn:hover {
    transform: translateY(-2px);
}

.clay-hero-cta-btn:active {
    transform: translateY(4px) scale(0.97);
    box-shadow:
        0 1px 0 #065F46,
        inset 0 2px 4px rgba(6, 95, 70, 0.35);
}

.cta-btn-shine {
    position: absolute;
    top: 3px;
    left: 14px;
    right: 14px;
    height: 35%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.cta-arrow-svg {
    width: 16px;
    height: 16px;
}

/* ==========================================================================
   2. LEVEL 2 HIERARCHY: WORLD MAP HEADER & 3D CANDY FILTER PILLS
   ========================================================================== */
.clay-controls-section {
    display: flex;
    flex-direction: column;
    gap: 0.68rem;
}

.controls-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0 0.1rem;
}

.controls-title-group {
    display: flex;
    align-items: center;
    gap: 0.58rem;
    min-width: 0;
}

.clay-map-badge {
    width: 42px;
    height: 42px;
    border-radius: 16px;
    background: linear-gradient(180deg, #60A5FA 0%, #2563EB 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 4px 0 #1D4ED8,
        0 6px 14px rgba(37, 99, 235, 0.24),
        inset 0 2px 3px rgba(255, 255, 255, 0.7);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.map-badge-svg {
    width: 20px;
    height: 20px;
}

.controls-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.16rem;
    font-weight: 800;
    color: #1E1B4B;
    line-height: 1.15;
}

.controls-subtitle {
    margin: 0.08rem 0 0;
    font-size: 0.73rem;
    font-weight: 700;
    color: #475569;
}

.clay-world-counter {
    padding: 0.28rem 0.72rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFFFFF 0%, #EDE9FE 100%);
    border: 2px solid #FFFFFF;
    box-shadow:
        0 3px 0 #C4B5FD,
        0 4px 10px rgba(124, 58, 237, 0.12);
    font-family: var(--font-display);
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #5B21B6;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Horizontal 3D Candy Clay Filter Strip */
.clay-filter-strip {
    display: flex;
    align-items: center;
    gap: 0.48rem;
    padding: 0.2rem 0.1rem 0.45rem;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
}

.clay-filter-strip::-webkit-scrollbar {
    display: none;
}

.clay-filter-pill {
    --pill-ledge: #CBD5E1;

    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 40px;
    padding: 0.35rem 0.88rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5F9 100%);
    border: 2px solid #FFFFFF;
    box-shadow:
        0 4px 0 var(--pill-ledge),
        0 6px 12px rgba(15, 23, 42, 0.08),
        inset 0 2px 2px rgba(255, 255, 255, 0.9);
    font-family: var(--font-display);
    font-size: 0.75rem;
    font-weight: 700;
    color: #334155;
    white-space: nowrap;
    cursor: pointer;
    transition: transform 160ms var(--spring-bounce), box-shadow 160ms ease, background 160ms ease, color 160ms ease;
    outline: none;
}

.clay-filter-pill:active {
    transform: translateY(3px) scale(0.96);
}

.clay-filter-pill.is-active {
    color: #FFFFFF;
    border-color: rgba(255, 255, 255, 0.95);
    box-shadow:
        0 4px 0 var(--pill-ledge),
        0 8px 16px rgba(79, 70, 229, 0.25),
        inset 0 2px 3px rgba(255, 255, 255, 0.55);
    transform: translateY(-1px);
}

.filter-biome-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ==========================================================================
   3. LEVEL 3 HIERARCHY: 2-COLUMN 3D CARTOON CLAY REALM CARDS GRID
   ========================================================================== */
.clay-realms-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.78rem;
    padding-bottom: 1.25rem;
}

.clay-realm-card {
    /* Default Biome: Forest Emerald */
    --card-bg-top: #FFFFFF;
    --card-bg-bottom: #ECFDF5;
    --card-border: #A7F3D0;
    --card-ledge: #10B981;
    --card-ambient: rgba(16, 185, 129, 0.18);
    --biome-accent: #059669;
    --biome-pill-bg: #D1FAE5;
    --biome-pill-text: #065F46;
    --btn-grad: linear-gradient(180deg, #34D399 0%, #059669 100%);
    --btn-ledge: #065F46;

    position: relative;
    overflow: hidden;
    border-radius: 24px;
    padding: 0.65rem;
    background: linear-gradient(180deg, var(--card-bg-top) 0%, var(--card-bg-bottom) 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 6px 0 var(--card-ledge),
        0 12px 22px var(--card-ambient),
        inset 0 3px 5px rgba(255, 255, 255, 0.95),
        inset 0 -3px 6px var(--card-border);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.52rem;
    min-width: 0;
    cursor: pointer;
    user-select: none;
    outline: none;
    transition: transform 220ms var(--spring-bounce), box-shadow 220ms ease;
}

.clay-realm-card:focus-visible {
    box-shadow: 0 0 0 3px #4F46E5, 0 6px 0 var(--card-ledge);
}

.clay-realm-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 9px 0 var(--card-ledge),
        0 16px 28px var(--card-ambient),
        inset 0 3px 5px rgba(255, 255, 255, 0.95),
        inset 0 -3px 6px var(--card-border);
}

.clay-realm-card:active,
.clay-realm-card.is-pressed {
    transform: translateY(4px) scale(0.97);
    box-shadow:
        0 2px 0 var(--card-ledge),
        0 4px 10px var(--card-ambient),
        inset 0 2px 4px rgba(15, 23, 42, 0.1);
}

/* Rich Color Biome Palettes */
.theme-forest {
    --card-bg-top: #FFFFFF;
    --card-bg-bottom: #ECFDF5;
    --card-border: #A7F3D0;
    --card-ledge: #10B981;
    --card-ambient: rgba(16, 185, 129, 0.2);
    --biome-accent: #059669;
    --biome-pill-bg: #D1FAE5;
    --biome-pill-text: #065F46;
    --btn-grad: linear-gradient(180deg, #34D399 0%, #059669 100%);
    --btn-ledge: #047857;
}

.theme-ocean {
    --card-bg-top: #FFFFFF;
    --card-bg-bottom: #F0F9FF;
    --card-border: #BAE6FD;
    --card-ledge: #0EA5E9;
    --card-ambient: rgba(14, 165, 233, 0.2);
    --biome-accent: #0284C7;
    --biome-pill-bg: #E0F2FE;
    --biome-pill-text: #075985;
    --btn-grad: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    --btn-ledge: #0369A1;
}

.theme-cosmic {
    --card-bg-top: #FFFFFF;
    --card-bg-bottom: #F5F3FF;
    --card-border: #DDD6FE;
    --card-ledge: #8B5CF6;
    --card-ambient: rgba(139, 92, 246, 0.22);
    --biome-accent: #7C3AED;
    --biome-pill-bg: #EDE9FE;
    --biome-pill-text: #5B21B6;
    --btn-grad: linear-gradient(180deg, #A78BFA 0%, #7C3AED 100%);
    --btn-ledge: #5B21B6;
}

.theme-volcano {
    --card-bg-top: #FFFFFF;
    --card-bg-bottom: #FFF7ED;
    --card-border: #FED7AA;
    --card-ledge: #F97316;
    --card-ambient: rgba(249, 115, 22, 0.2);
    --biome-accent: #EA580C;
    --biome-pill-bg: #FFEDD5;
    --biome-pill-text: #9A3412;
    --btn-grad: linear-gradient(180deg, #FB923C 0%, #EA580C 100%);
    --btn-ledge: #C2410C;
}

.card-top-gloss {
    position: absolute;
    top: 3px;
    left: 14px;
    right: 14px;
    height: 10px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

/* Card Top Row */
.realm-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.28rem;
    min-width: 0;
}

.clay-biome-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.24rem;
    padding: 0.16rem 0.46rem;
    border-radius: 9999px;
    background: var(--biome-pill-bg);
    color: var(--biome-pill-text);
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 2px 0 var(--card-border);
    font-size: 0.55rem;
    font-weight: 900;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.biome-dot-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--biome-accent);
    flex-shrink: 0;
}

.clay-xp-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.12rem;
    padding: 0.16rem 0.44rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FEF08A 0%, #FACC15 100%);
    color: #78350F;
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 2px 0 #D97706;
    font-family: var(--font-display);
    font-size: 0.56rem;
    font-weight: 800;
    white-space: nowrap;
    flex-shrink: 0;
}

.xp-mini-svg {
    width: 10px;
    height: 10px;
    color: #B45309;
}

/* 3D Cartoon Diorama Picture Window */
.clay-diorama-frame {
    padding: 4px;
    border-radius: 16px;
    background: var(--card-border);
    box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.12);
}

.diorama-viewport {
    position: relative;
    border-radius: 13px;
    overflow: hidden;
    aspect-ratio: 16 / 10;
    background: #BAE6FD;
    border: 2px solid #FFFFFF;
}

.diorama-img,
.diorama-fallback-scene,
.fallback-world-svg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.diorama-img {
    transition: transform 350ms var(--spring-bounce);
}

.clay-realm-card:hover .diorama-img {
    transform: scale(1.06);
}

.diorama-new-pill {
    position: absolute;
    top: 6px;
    left: 6px;
    padding: 0.12rem 0.42rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FB7185 0%, #E11D48 100%);
    color: #FFFFFF;
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 2px 0 #9F1239;
    font-family: var(--font-display);
    font-size: 0.5rem;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.diorama-stars-pod {
    position: absolute;
    bottom: 5px;
    left: 6px;
    display: inline-flex;
    align-items: center;
    gap: 0.12rem;
    padding: 0.14rem 0.38rem;
    border-radius: 9999px;
    background: rgba(30, 27, 75, 0.68);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.45);
}

.mini-star-svg {
    width: 11px;
    height: 11px;
}

.star-earned {
    color: #FACC15;
    filter: drop-shadow(0 1px 2px rgba(245, 158, 11, 0.5));
}

.star-empty {
    color: rgba(255, 255, 255, 0.35);
}

/* Card Body Copy & Progress Bar */
.realm-card-body {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    padding: 0 0.05rem;
    flex: 1 1 auto;
}

.realm-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 800;
    color: #1E1B4B;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.realm-description {
    margin: 0;
    font-size: 0.68rem;
    font-weight: 700;
    color: #475569;
    line-height: 1.34;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.realm-progress-block {
    display: flex;
    flex-direction: column;
    gap: 0.18rem;
    margin-top: auto;
    padding-top: 0.2rem;
}

.realm-progress-labels {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.58rem;
    font-weight: 800;
    color: #64748B;
}

.progress-value {
    font-family: var(--font-display);
    color: var(--biome-accent);
}

.clay-mini-track {
    width: 100%;
    height: 8px;
    border-radius: 9999px;
    background: #FFFFFF;
    border: 1.5px solid var(--card-border);
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.1);
    padding: 1px;
    overflow: hidden;
}

.clay-mini-fill {
    height: 100%;
    border-radius: 9999px;
    background: var(--btn-grad);
    position: relative;
    transition: width 350ms var(--spring-bounce);
}

.mini-fill-shine {
    position: absolute;
    top: 1px;
    left: 4px;
    right: 4px;
    height: 2px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.65);
}

/* Card Footer: Counters + 3D Pushable Play Button */
.realm-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.32rem;
    padding-top: 0.15rem;
}

.realm-counters {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
}

.clay-counter-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    padding: 0.18rem 0.42rem;
    border-radius: 10px;
    background: #FFFFFF;
    border: 1.5px solid var(--card-border);
    box-shadow: 0 2px 0 var(--card-border);
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 800;
    color: #334155;
    white-space: nowrap;
}

.counter-svg {
    width: 11px;
    height: 11px;
    color: var(--biome-accent);
    flex-shrink: 0;
}

.clay-play-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.14rem;
    min-height: 32px;
    padding: 0.24rem 0.62rem;
    border-radius: 9999px;
    background: var(--btn-grad);
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 3px 0 var(--btn-ledge),
        inset 0 1.5px 2px rgba(255, 255, 255, 0.65);
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    flex-shrink: 0;
    transition: transform 150ms var(--spring-bounce), box-shadow 150ms ease;
}

.clay-realm-card:hover .clay-play-btn {
    transform: scale(1.05);
}

.play-arrow-svg {
    width: 12px;
    height: 12px;
}

/* ==========================================================================
   4. EMPTY REALMS STATE
   ========================================================================== */
.clay-empty-card {
    background: linear-gradient(180deg, #FFFFFF 0%, #F5F3FF 100%);
    border-radius: 28px;
    padding: 2.25rem 1.35rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
    border: 3px solid #FFFFFF;
    box-shadow:
        0 7px 0 #C4B5FD,
        0 16px 30px rgba(124, 58, 237, 0.14);
}

.clay-empty-badge {
    width: 64px;
    height: 64px;
    border-radius: 22px;
    background: #EDE9FE;
    border: 2.5px solid #FFFFFF;
    box-shadow: 0 4px 0 #C4B5FD;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.25rem;
}

.empty-svg {
    width: 34px;
    height: 34px;
}

.empty-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.2rem;
    font-weight: 800;
    color: #1E1B4B;
}

.empty-desc {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    max-width: 340px;
    line-height: 1.45;
}

.clay-reset-btn {
    margin-top: 0.4rem;
    min-height: 42px;
    padding: 0.45rem 1.15rem;
    border-radius: 9999px;
    border: 2.5px solid #FFFFFF;
    background: linear-gradient(180deg, #6366F1 0%, #4338CA 100%);
    box-shadow: 0 4px 0 #312E81;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    color: #FFFFFF;
    cursor: pointer;
}

/* ==========================================================================
   5. 3D CLAYMORPHISM MOBILE BOTTOM SHEET REALM BRIEFING
   ========================================================================== */
.clay-sheet-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(30, 27, 75, 0.48);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0;
}

.clay-bottom-sheet {
    width: 100%;
    max-width: 520px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F5F3FF 100%);
    border-radius: 32px 32px 0 0;
    padding: 0.8rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom, 0px));
    border-top: 3px solid #FFFFFF;
    box-shadow: 0 -10px 34px rgba(30, 27, 75, 0.28);
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.sheet-pill-handle {
    width: 48px;
    height: 6px;
    border-radius: 9999px;
    background: #C7D2FE;
    margin: 0 auto 0.15rem;
}

.sheet-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.sheet-biome-tag {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    background: #EDE9FE;
    border: 2px solid #FFFFFF;
    box-shadow: 0 2px 0 #C4B5FD;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    color: #5B21B6;
}

.clay-close-btn {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    border: 2px solid #FFFFFF;
    background: linear-gradient(180deg, #FFE4E6 0%, #FECDD3 100%);
    box-shadow: 0 3px 0 #FDA4AF;
    color: #E11D48;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.close-svg {
    width: 16px;
    height: 16px;
}

.sheet-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.35rem;
    font-weight: 800;
    color: #1E1B4B;
}

.sheet-desc {
    margin: 0;
    font-size: 0.84rem;
    font-weight: 700;
    line-height: 1.48;
    color: #475569;
}

.sheet-loot-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem;
}

.sheet-loot-pod {
    padding: 0.65rem 0.5rem;
    border-radius: 18px;
    border: 2px solid #FFFFFF;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    text-align: center;
}

.pod-stories {
    background: linear-gradient(180deg, #EFF6FF 0%, #DBEAFE 100%);
    box-shadow: 0 3px 0 #93C5FD;
    color: #1E40AF;
}

.pod-quizzes {
    background: linear-gradient(180deg, #ECFDF5 0%, #D1FAE5 100%);
    box-shadow: 0 3px 0 #6EE7B7;
    color: #065F46;
}

.pod-reward {
    background: linear-gradient(180deg, #FEFCE8 0%, #FEF08A 100%);
    box-shadow: 0 3px 0 #FACC15;
    color: #92400E;
}

.loot-lbl {
    font-size: 0.55rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    opacity: 0.8;
}

.loot-val {
    font-family: var(--font-display);
    font-size: 0.84rem;
    font-weight: 800;
}

.sheet-guardian-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 0.85rem;
    border-radius: 16px;
    background: #FFFFFF;
    border: 2px solid #E0E7FF;
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
}

.guardian-name {
    font-family: var(--font-display);
    color: #4F46E5;
}

.clay-sheet-launch-btn {
    position: relative;
    overflow: hidden;
    width: 100%;
    min-height: 50px;
    border-radius: 9999px;
    border: 2.5px solid #FFFFFF;
    background: linear-gradient(180deg, #34D399 0%, #10B981 55%, #059669 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    cursor: pointer;
    box-shadow:
        0 6px 0 #047857,
        0 12px 22px rgba(16, 185, 129, 0.35),
        inset 0 2px 3px rgba(255, 255, 255, 0.7);
}

.clay-sheet-launch-btn:active {
    transform: translateY(4px);
    box-shadow: 0 2px 0 #047857;
}

.clay-sheet-enter-active,
.clay-sheet-leave-active {
    transition: opacity 200ms ease;
}

.clay-sheet-enter-from,
.clay-sheet-leave-to {
    opacity: 0;
}

/* ==========================================================================
   SMALL PHONE (< 390px) & TABLET/DESKTOP UPGRADES
   ========================================================================== */
@media (max-width: 390px) {
    .clay-realms-grid {
        gap: 0.58rem;
    }

    .clay-realm-card {
        padding: 0.55rem;
        border-radius: 20px;
    }

    .clay-counter-badge:nth-child(2) {
        display: none;
    }
}

@media (min-width: 720px) {
    .clay-hero-banner {
        padding: 1.45rem 1.75rem;
        border-radius: 34px;
    }

    .hero-title {
        font-size: 1.65rem;
    }

    .hero-subtitle {
        font-size: 0.92rem;
    }

    .clay-realms-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.35rem;
    }

    .clay-realm-card {
        border-radius: 28px;
        padding: 0.95rem;
        gap: 0.75rem;
    }

    .clay-biome-chip {
        padding: 0.22rem 0.62rem;
        font-size: 0.66rem;
    }

    .clay-xp-pill {
        padding: 0.22rem 0.58rem;
        font-size: 0.66rem;
    }

    .clay-diorama-frame {
        padding: 6px;
        border-radius: 20px;
    }

    .diorama-viewport {
        border-radius: 15px;
    }

    .realm-title {
        font-size: 1.14rem;
    }

    .realm-description {
        font-size: 0.78rem;
    }

    .clay-counter-badge {
        padding: 0.24rem 0.55rem;
        font-size: 0.68rem;
    }

    .clay-play-btn {
        min-height: 38px;
        padding: 0.32rem 0.88rem;
        font-size: 0.76rem;
    }

    .clay-sheet-backdrop {
        align-items: center;
        padding: 1rem;
    }

    .clay-bottom-sheet {
        border-radius: 32px;
        border: 3px solid #FFFFFF;
        box-shadow:
            0 8px 0 #C4B5FD,
            0 22px 44px rgba(30, 27, 75, 0.32);
        padding: 1.4rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .clay-realm-card,
    .diorama-img,
    .clay-mini-fill,
    .clay-hero-cta-btn,
    .clay-filter-pill,
    .hero-mascot-pod {
        animation: none !important;
        transition: none !important;
    }
}
</style>
