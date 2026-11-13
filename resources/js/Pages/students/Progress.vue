<script setup>
import { Head, router, useRemember } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import StudentLayout from '../../Components/StudentLayout.vue';
import { prefetchRoute, useIdlePrefetch } from '@/Composables/usePrefetch';

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
    module: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Whispering Woods: Fables & Folklore',
            slug: 'whispering-woods',
            description:
                'Journey across enchanted 3D floating islands, uncover hidden story scrolls, and outsmart the Chapter Guardian!',
            grade_level: 'Grade 4–6 • Reading Quest',
            theme: 'forest',
            icon: 'compass',
            xp_bonus: 300,
            boss_name: 'Elder Oak Guardian',
            status: 'published',
        }),
    },
    lectures: {
        type: Array,
        default: () => [],
    },
    quizzes: {
        type: Array,
        default: () => [],
    },
    completedQuizIds: {
        type: Array,
        default: () => [],
    },
    pendingQuizIds: {
        type: Array,
        default: () => [],
    },
    completedLectureIds: {
        type: Array,
        default: () => [],
    },
    initialStep: {
        type: Number,
        default: null,
    },
});

/* ==========================================================================
   BIOME ISLAND THEMES & STORYBOOK DIORAMA ARTWORK
   ========================================================================== */
const islandBiomes = [
    {
        key: 'emerald-woods',
        label: 'Emerald Woods',
        gradient: 'linear-gradient(180deg, #4ADE80 0%, #10B981 65%, #059669 100%)',
        ledge: '#065F46',
        rim: '#A7F3D0',
        pillBg: '#DCFCE7',
        pillText: '#065F46',
        dioramaImg: '/images/book_left_trees_mountains.png',
        dioramaCaption: 'Enchanted Pine Canopy',
    },
    {
        key: 'timber-crossing',
        label: 'Timber Crossing',
        gradient: 'linear-gradient(180deg, #FBBF24 0%, #F59E0B 65%, #D97706 100%)',
        ledge: '#92400E',
        rim: '#FDE68A',
        pillBg: '#FEF3C7',
        pillText: '#92400E',
        dioramaImg: '/images/boy_left_on_book_laptop.png',
        dioramaCaption: 'Canyon Bridge Outpost',
    },
    {
        key: 'crystal-cavern',
        label: 'Crystal Cavern',
        gradient: 'linear-gradient(180deg, #C084FC 0%, #9333EA 65%, #6B21A8 100%)',
        ledge: '#4C1D95',
        rim: '#E9D5FF',
        pillBg: '#F3E8FF',
        pillText: '#581C87',
        dioramaImg: '/images/girl_left_reading_book.png',
        dioramaCaption: 'Amethyst Story Grotto',
    },
    {
        key: 'coral-lagoon',
        label: 'Coral Lagoon',
        gradient: 'linear-gradient(180deg, #38BDF8 0%, #0284C7 65%, #0369A1 100%)',
        ledge: '#075985',
        rim: '#BAE6FD',
        pillBg: '#E0F2FE',
        pillText: '#075985',
        dioramaImg: '/images/book_right_mountains_river.png',
        dioramaCaption: 'Starlight Tide Pools',
    },
    {
        key: 'sunlit-cliff',
        label: 'Sunlit Cliffside',
        gradient: 'linear-gradient(180deg, #FB7185 0%, #E11D48 65%, #BE123C 100%)',
        ledge: '#881337',
        rim: '#FECDD3',
        pillBg: '#FFE4E6',
        pillText: '#9F1239',
        dioramaImg: '/images/book_with_message_and_laptop_girl.png',
        dioramaCaption: 'Sunset RidgeLookout',
    },
    {
        key: 'cloud-peak',
        label: 'Cloud Citadel',
        gradient: 'linear-gradient(180deg, #818CF8 0%, #4F46E5 65%, #3730A3 100%)',
        ledge: '#312E81',
        rim: '#C7D2FE',
        pillBg: '#E0E7FF',
        pillText: '#312E81',
        dioramaImg: '/images/girl_right_using_tablet.png',
        dioramaCaption: 'Sky Guardian Summit',
    },
];

const formatQuizTypeLabel = (type) => {
    const labels = {
        multiple_choice: 'Quiz Challenge',
        color_the_conflict: 'Color the Conflict',
        the_conflict_map: 'Conflict Map',
        matching_type: 'Matching Challenge',
        evaluation: 'Evaluation Arena',
        reflection: 'Story Reflection',
    };

    return labels[type] || 'Quiz Challenge';
};

/* ==========================================================================
   MILESTONE STREAM & STATE
   ========================================================================== */
const rawLectures = computed(() => props.lectures || []);
const rawQuizzes = computed(() => props.quizzes || []);

const milestones = computed(() => {
    const lectureItems = (rawLectures.value || []).map((l) => ({
        ...l,
        itemType: 'lecture',
    }));
    const quizItems = (rawQuizzes.value || []).map((q) => ({
        ...q,
        itemType: 'quiz',
    }));

    const combined = [...lectureItems, ...quizItems].sort((a, b) => {
        const orderA = Number(a.sort_order ?? 0);
        const orderB = Number(b.sort_order ?? 0);
        if (orderA !== orderB) {
            return orderA - orderB;
        }

        return Number(a.id ?? 0) - Number(b.id ?? 0);
    });

    return combined.map((item, idx) => {
        const biome = islandBiomes[idx % islandBiomes.length];
        const isQuiz = item.itemType === 'quiz';

        return {
            step: idx + 1,
            id: item.id,
            itemType: item.itemType,
            quizType: isQuiz ? item.type : null,
            slug: item.slug,
            title: item.title,
            subtitle: isQuiz ? `${formatQuizTypeLabel(item.type)} • ${biome.label}` : `Story Scroll • ${biome.label}`,
            summary:
                item.description ||
                (isQuiz
                    ? 'Test your reading comprehension to unlock the next floating island!'
                    : 'Read this story scroll carefully to collect golden stars and XP!'),
            body: item.body || item.reading_passage || '',
            readingPassage: item.reading_passage || item.context?.scenario || '',
            duration: isQuiz
                ? `${item.questions_count ?? item.questions?.length ?? 1} ${
                      (item.questions_count ?? item.questions?.length ?? 1) === 1 ? 'Question' : 'Questions'
                  }`
                : `${item.estimated_duration_minutes || 10} min read`,
            xp: Number(item.xp_reward || 120),
            passingPercentage: isQuiz ? Number(item.passing_percentage || 80) : null,
            contentsCount: Number(item.contents_count ?? item.contents?.length ?? 0),
            contents: item.contents || [],
            questionsCount: Number(item.questions_count ?? item.questions?.length ?? 0),
            questions: item.questions || [],
            biome,
        };
    });
});

const totalStepsCount = computed(() => milestones.value.length);

// Compute default initial step based on actual database completions
const computeDbUnlockedStep = () => {
    if (props.initialStep !== null && props.initialStep !== undefined) {
        return Number(props.initialStep);
    }
    const ms = milestones.value;
    if (ms.length === 0) {
        return 1;
    }
    // Find the first milestone that is NOT completed
    for (let i = 0; i < ms.length; i++) {
        const item = ms[i];
        const isCompleted = item.itemType === 'quiz'
            ? (props.completedQuizIds || []).includes(item.id)
            : (props.completedLectureIds || []).includes(item.id);
        if (!isCompleted) {
            return i + 1; // Step is 1-indexed
        }
    }
    // If all completed, currentStep is past the last step
    return ms.length + 1;
};

const currentStep = ref(computeDbUnlockedStep());
const activeFilter = useRemember('all', () => `student.progress.filter.${props.module?.id || 'default'}`); // 'all' | 'lecture' | 'quiz'
const shakingStep = ref(null);
const lockedTooltipStep = ref(null);
const isChestOpen = ref(false);
const chestShaking = ref(false);
const showLockedChestModal = ref(false);
const showQuestCelebrationToast = ref(false);
const lastCompletedStepTitle = ref('');
const lastCompletedStepXp = ref(0);

watch(
    () => [milestones.value.length, props.completedQuizIds, props.completedLectureIds],
    () => {
        currentStep.value = computeDbUnlockedStep();
    },
    { immediate: true, deep: true },
);

const isAllCompleted = computed(() => {
    return totalStepsCount.value > 0 && currentStep.value > totalStepsCount.value;
});

const completedCount = computed(() => {
    if (totalStepsCount.value === 0) {
        return 0;
    }

    return Math.min(totalStepsCount.value, Math.max(0, currentStep.value - 1));
});

const progressPercentage = computed(() => {
    if (totalStepsCount.value === 0) {
        return 0;
    }

    return Math.min(100, Math.round((completedCount.value / totalStepsCount.value) * 100));
});

const computedTotalXp = computed(() => {
    const itemsXp = milestones.value.reduce((acc, m) => acc + Number(m.xp || 0), 0);

    return itemsXp + Number(props.module?.xp_bonus || 250);
});

const earnedXpComputed = computed(() => {
    const itemsEarned = milestones.value
        .filter((m) => m.step < currentStep.value)
        .reduce((acc, m) => acc + Number(m.xp || 0), 0);

    const bonusEarned = isAllCompleted.value ? Number(props.module?.xp_bonus || 250) : 0;

    return itemsEarned + bonusEarned;
});

const totalPossibleStars = computed(() => totalStepsCount.value * 3);
const earnedStarsCount = computed(() => completedCount.value * 3);

const remainingMilestonesCount = computed(() => {
    return Math.max(0, totalStepsCount.value - completedCount.value);
});

const activeQuestNode = computed(() => {
    if (isAllCompleted.value) {
        return milestones.value[milestones.value.length - 1] || null;
    }

    return milestones.value.find((m) => m.step === currentStep.value) || milestones.value[0] || null;
});

const studentHudStats = computed(() => {
    const basePoints = 1350 + earnedXpComputed.value;
    const baseStars = 21 + earnedStarsCount.value;
    const totalXp = 540 + earnedXpComputed.value;

    return {
        level: Math.max(1, Math.floor(totalXp / 250) + 3),
        xp: totalXp % 1000,
        maxXp: 1000,
        points: basePoints,
        stars: baseStars,
        streak: 5,
    };
});

/* ==========================================================================
   MOBILE-FIRST S-CURVE SVG GEOMETRY (400px VIEWBOX FOR PERFECT PROPORTIONS)
   ========================================================================== */
const rowPitch = 285;
const topStartOffset = 185;

const nodePositions = computed(() => {
    return milestones.value.map((m, idx) => {
        const isLeft = idx % 2 === 0;

        return {
            ...m,
            isLeft,
            cx: isLeft ? 88 : 312,
            cy: topStartOffset + idx * rowPitch,
        };
    });
});

const svgHeight = computed(() => {
    if (milestones.value.length === 0) {
        return 580;
    }

    return topStartOffset + (milestones.value.length - 1) * rowPitch + 480;
});

const finaleY = computed(() => svgHeight.value - 145);

const segmentColors = ['#10B981', '#F59E0B', '#A855F7', '#0EA5E9', '#EC4899', '#6366F1'];

const segments = computed(() => {
    const pts = nodePositions.value;
    const segs = [];
    if (pts.length === 0) {
        return segs;
    }

    // Start Marker (200, 62) to First Island
    segs.push({
        id: 'seg-start',
        d: `M 200 62 C 200 120, ${pts[0].cx} 115, ${pts[0].cx} ${pts[0].cy}`,
        lit: currentStep.value >= 1,
        color: '#10B981',
    });

    // Island-to-Island S-Curve Bezier Segments
    for (let i = 0; i < pts.length - 1; i++) {
        const curr = pts[i];
        const next = pts[i + 1];
        const midY = (curr.cy + next.cy) / 2;
        segs.push({
            id: `seg-${i}`,
            d: `M ${curr.cx} ${curr.cy} C ${curr.cx} ${midY}, ${next.cx} ${midY}, ${next.cx} ${next.cy}`,
            lit: currentStep.value > i + 1,
            color: segmentColors[i % segmentColors.length],
        });
    }

    // Final Island to Grand Treasure Vault (200, finaleY)
    const last = pts[pts.length - 1];
    const endY = finaleY.value;
    const midEnd = (last.cy + endY) / 2;
    segs.push({
        id: 'seg-end',
        d: `M ${last.cx} ${last.cy} C ${last.cx} ${midEnd}, 200 ${midEnd}, 200 ${endY}`,
        lit: isAllCompleted.value,
        color: '#F59E0B',
    });

    return segs;
});

const pathTotalD = computed(() => {
    const pts = nodePositions.value;
    if (pts.length === 0) {
        return '';
    }

    let d = `M 200 62 C 200 120, ${pts[0].cx} 115, ${pts[0].cx} ${pts[0].cy}`;
    for (let i = 0; i < pts.length - 1; i++) {
        const curr = pts[i];
        const next = pts[i + 1];
        const midY = (curr.cy + next.cy) / 2;
        d += ` C ${curr.cx} ${midY}, ${next.cx} ${midY}, ${next.cx} ${next.cy}`;
    }

    const last = pts[pts.length - 1];
    const endY = finaleY.value;
    const midEnd = (last.cy + endY) / 2;
    d += ` C ${last.cx} ${midEnd}, 200 ${midEnd}, 200 ${endY}`;

    return d;
});

/* ==========================================================================
   INTERACTIVE HANDLERS (MOBILE SHEET, QUIZ GAMEPLAY, SCROLL, TREASURE CHEST)
   ========================================================================== */
const isNodeDimmedByFilter = (node) => {
    if (activeFilter.value === 'all') {
        return false;
    }

    return node.itemType !== activeFilter.value;
};

const handleNodeInteraction = (node) => {
    if (node.step > currentStep.value) {
        shakingStep.value = node.step;
        lockedTooltipStep.value = node.step;
        window.setTimeout(() => {
            shakingStep.value = null;
        }, 520);
        window.setTimeout(() => {
            if (lockedTooltipStep.value === node.step) {
                lockedTooltipStep.value = null;
            }
        }, 2400);

        return;
    }

    if (node.itemType === 'quiz') {
        openFullQuizArena(node);
        return;
    }

    openFullLectureReader(node);
};

const scrollToCurrentStep = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const targetStep = isAllCompleted.value ? 'finale-vault' : `quest-step-${currentStep.value}`;
    const el = document.getElementById(targetStep);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const handleChestClick = () => {
    if (!isAllCompleted.value) {
        chestShaking.value = true;
        window.setTimeout(() => {
            chestShaking.value = false;
        }, 520);
        showLockedChestModal.value = true;

        return;
    }

    isChestOpen.value = !isChestOpen.value;
};

const closeLockedChestModalAndScroll = () => {
    showLockedChestModal.value = false;
    nextTick(() => scrollToCurrentStep());
};

const getNodeUrl = (node) => {
    if (!node || !props.module?.slug || !node.slug) {
        return null;
    }

    return node.itemType === 'lecture'
        ? `/students/modules/${props.module.slug}/lectures/${node.slug}`
        : `/students/modules/${props.module.slug}/quizzes/${node.slug}`;
};

const prefetchNode = (node) => {
    if (!node || node.step > currentStep.value) {
        return;
    }

    const url = getNodeUrl(node);
    if (url) {
        prefetchRoute(url, ['student-modules', `module-${props.module?.slug}`], ['20s', '90s']);
    }
};

useIdlePrefetch(
    () => (activeQuestNode.value && !isAllCompleted.value ? getNodeUrl(activeQuestNode.value) : null),
    ['student-modules', props.module?.slug ? `module-${props.module.slug}` : ''],
    ['20s', '90s'],
);

const goBackToWorlds = () => {
    router.visit('/students/dashboard');
};

const openFullLectureReader = (node) => {
    if (!node || node.itemType !== 'lecture') {
        return;
    }

    if (props.module?.slug && node.slug) {
        router.visit(`/students/modules/${props.module.slug}/lectures/${node.slug}`);
        return;
    }

    router.visit('/students/lecture-reader');
};

const openFullQuizArena = (node) => {
    if (!node || node.itemType !== 'quiz') {
        return;
    }

    if (props.module?.slug && node.slug) {
        router.visit(`/students/modules/${props.module.slug}/quizzes/${node.slug}`);
        return;
    }

    router.visit('/students/quiz-arena');
};

// Confetti Burst Particles for Module Finale
const confettiPieces = Array.from({ length: 38 }, (_, i) => {
    const colors = ['#FDE047', '#34D399', '#38BDF8', '#FB7185', '#A78BFA', '#F97316'];

    return {
        id: i,
        left: `${(i * 97) % 100}%`,
        color: colors[i % colors.length],
        delay: `${(i % 9) * 0.22}s`,
        duration: `${2.8 + (i % 4) * 0.55}s`,
        size: `${8 + (i % 5) * 2}px`,
    };
});

onMounted(() => {
    if (isAllCompleted.value) {
        isChestOpen.value = true;
    }
});
</script>

<template>
    <Head :title="`${module?.title || 'Adventure Map'} • WEBREAD`" />

    <StudentLayout
        :user="user"
        :level="studentHudStats.level"
        :xp="studentHudStats.xp"
        :max-xp="studentHudStats.maxXp"
        :points="studentHudStats.points"
        :stars="studentHudStats.stars"
        :streak="studentHudStats.streak"
        active-nav="modules"
    >
        <section class="clay-expedition-page" aria-labelledby="expedition-module-title">
            <!-- =========================================================================
                 1. TIER 1: STICKY MOBILE-FIRST 3D CLAY EXPEDITION HUD BAR
                 ========================================================================= -->
            <div class="clay-sticky-expedition-hud">
                <div class="expedition-hud-top">
                    <button
                        type="button"
                        class="clay-back-worlds-btn"
                        aria-label="Back to Story Worlds"
                        @mouseenter="prefetchRoute('/students/dashboard', ['student-nav', 'student-modules'])"
                        @focus="prefetchRoute('/students/dashboard', ['student-nav', 'student-modules'])"
                        @click="goBackToWorlds"
                    >
                        <svg viewBox="0 0 20 20" class="back-arrow-svg" aria-hidden="true">
                            <path
                                d="M12.5 4.5L7 10l5.5 5.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>Worlds</span>
                    </button>

                    <div class="expedition-hud-title-pill">
                        <span class="hud-biome-dot" aria-hidden="true"></span>
                        <span class="hud-module-name-trunc">{{ module?.title || 'Whispering Woods' }}</span>
                    </div>

                    <div class="expedition-hud-counters">
                        <span class="clay-mini-xp-pill" title="Module XP Earned">
                            <svg viewBox="0 0 16 16" class="mini-bolt-svg" aria-hidden="true">
                                <path d="M9.5 1L3 9h4.5L6.5 15L13 7H8.5L9.5 1z" fill="currentColor" />
                            </svg>
                            {{ earnedXpComputed }}
                        </span>

                        <span class="clay-mini-star-pill" title="Module Stars Collected">
                            <svg viewBox="0 0 16 16" class="mini-star-svg" aria-hidden="true">
                                <path
                                    d="M8 1.3l2 4.1 4.5.7-3.2 3.1.8 4.5L8 11.6l-4.1 2.1.8-4.5L1.5 6.1 6 5.4 8 1.3z"
                                    fill="currentColor"
                                />
                            </svg>
                            {{ earnedStarsCount }}/{{ totalPossibleStars }}
                        </span>
                    </div>
                </div>

                <!-- Segmented 3D Liquid Progress Bar -->
                <div class="expedition-hud-progress-row">
                    <div
                        class="clay-expedition-track"
                        role="progressbar"
                        :aria-valuenow="progressPercentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`Module quest progress: ${completedCount} of ${totalStepsCount} completed`"
                    >
                        <div class="clay-expedition-fill" :style="{ width: `${progressPercentage}%` }">
                            <span class="expedition-fill-gloss" aria-hidden="true"></span>
                        </div>
                    </div>
                    <span class="expedition-pct-badge">
                        {{ completedCount }}/{{ totalStepsCount }} • {{ progressPercentage }}%
                    </span>
                </div>
            </div>

            <!-- =========================================================================
                 2. TIER 2: COMPACT 3D CLAY WORLD DIORAMA HERO BANNER
                 ========================================================================= -->
            <header class="clay-world-hero-card" :class="`hero-theme-${module?.theme || 'forest'}`">
                <span class="hero-top-specular" aria-hidden="true"></span>
                <div class="hero-orb hero-orb-right" aria-hidden="true"></div>
                <div class="hero-orb hero-orb-left" aria-hidden="true"></div>

                <div class="world-hero-top-row">
                    <span class="world-grade-pill">
                        <span class="live-pulse-dot" aria-hidden="true"></span>
                        {{ module?.grade_level || 'All Grades • Reading Quest' }}
                    </span>

                    <span class="world-boss-pill">
                        <svg viewBox="0 0 18 18" class="boss-crown-svg" aria-hidden="true">
                            <path d="M2 13.5L3.5 6l4 3 1.5-5 1.5 5 4-3L16 13.5H2z" fill="#FDE047" />
                        </svg>
                        Boss: {{ module?.boss_name || 'Chapter Guardian' }}
                    </span>
                </div>

                <div class="world-hero-main">
                    <div class="world-hero-copy">
                        <h1 id="expedition-module-title" class="world-hero-title">
                            {{ module?.title || 'Whispering Woods: Fables & Folklore' }}
                        </h1>
                        <p class="world-hero-desc">
                            {{
                                module?.description ||
                                'Tap each 3D floating island in order to read story scrolls, conquer quizzes, and unlock the Grand Treasure Vault!'
                            }}
                        </p>
                    </div>

                    <!-- 3D Floating Compass / Map Emblem -->
                    <div class="world-hero-crest" aria-hidden="true">
                        <svg viewBox="0 0 68 68" class="world-crest-svg">
                            <defs>
                                <linearGradient id="crestGoldRing" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#FEF08A" />
                                    <stop offset="50%" stop-color="#F59E0B" />
                                    <stop offset="100%" stop-color="#B45309" />
                                </linearGradient>
                                <linearGradient id="crestOceanCore" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#38BDF8" />
                                    <stop offset="100%" stop-color="#1D4ED8" />
                                </linearGradient>
                            </defs>
                            <circle cx="34" cy="34" r="29" fill="url(#crestGoldRing)" stroke="#FFFFFF" stroke-width="3" />
                            <circle cx="34" cy="34" r="22" fill="url(#crestOceanCore)" />
                            <polygon points="34,15 39,31 53,34 39,37 34,53 29,37 15,34 29,31" fill="#FEF08A" />
                            <polygon points="34,19 37,31 34,34 31,31" fill="#EF4444" />
                            <circle cx="34" cy="34" r="3.5" fill="#FFFFFF" />
                        </svg>
                    </div>
                </div>

                <!-- Filter Pills & Quick Jump CTA -->
                <div v-if="milestones.length > 0" class="world-hero-controls">
                    <div class="clay-quest-filter-group" role="tablist" aria-label="Filter quest milestones">
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeFilter === 'all'"
                            class="clay-quest-tab"
                            :class="{ 'is-active': activeFilter === 'all' }"
                            @click="activeFilter = 'all'"
                        >
                            All ({{ totalStepsCount }})
                        </button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeFilter === 'lecture'"
                            class="clay-quest-tab"
                            :class="{ 'is-active': activeFilter === 'lecture' }"
                            @click="activeFilter = 'lecture'"
                        >
                            Stories ({{ rawLectures.length }})
                        </button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeFilter === 'quiz'"
                            class="clay-quest-tab"
                            :class="{ 'is-active': activeFilter === 'quiz' }"
                            @click="activeFilter = 'quiz'"
                        >
                            Quizzes ({{ rawQuizzes.length }})
                        </button>
                    </div>

                    <button type="button" class="clay-jump-quest-btn" @click="scrollToCurrentStep">
                        <span>{{ isAllCompleted ? 'Vault Unlocked!' : `Go to Step ${currentStep}` }}</span>
                        <svg viewBox="0 0 18 18" class="jump-icon-svg" aria-hidden="true">
                            <path
                                d="M9 3v12M4.5 10.5L9 15l4.5-4.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- =========================================================================
                 3. TIER 3: GRAPHIC-RICH MOBILE-FIRST 3D S-CURVE EXPEDITION MAP
                 ========================================================================= -->
            <div class="clay-map-viewport">
                <!-- =====================================================================
                     ACTIVE EXPEDITION MAP (WHEN REALM HAS STORIES OR QUIZZES)
                     ===================================================================== -->
                <template v-if="milestones.length > 0">
                    <!-- Ambient Floating Clouds & Sparkles inside the Map Canvas -->
                <div class="map-sky-decor" aria-hidden="true">
                    <div class="map-cloud map-cloud-1"></div>
                    <div class="map-cloud map-cloud-2"></div>
                    <div class="map-cloud map-cloud-3"></div>
                    <div class="map-cloud map-cloud-4"></div>
                </div>

                <!-- DESKTOP 3D STORYBOOK POP-UP SIDE WINGS (>= 1024px) -->
                <div class="desktop-storybook-wings" aria-hidden="true">
                    <div class="wing-column wing-left">
                        <div class="wing-prop-card wing-float-slow">
                            <img src="/images/book_left_trees_mountains.png" alt="" width="965" height="777" loading="lazy" decoding="async" class="wing-art-img" />
                        </div>
                        <div class="wing-prop-card wing-float-mid">
                            <img src="/images/boy_left_on_book_laptop.png" alt="" width="838" height="905" loading="lazy" decoding="async" class="wing-art-img" />
                        </div>
                        <div class="wing-prop-card wing-float-fast">
                            <img src="/images/girl_left_reading_book.png" alt="" width="945" height="878" loading="lazy" decoding="async" class="wing-art-img" />
                        </div>
                    </div>

                    <div class="wing-column wing-right">
                        <div class="wing-prop-card wing-float-mid">
                            <img src="/images/book_right_mountains_river.png" alt="" width="996" height="640" loading="lazy" decoding="async" class="wing-art-img" />
                        </div>
                        <div class="wing-prop-card wing-float-slow">
                            <img src="/images/book_with_message_and_laptop_girl.png" alt="" width="1030" height="682" loading="lazy" decoding="async" class="wing-art-img" />
                        </div>
                        <div class="wing-prop-card wing-float-fast">
                            <img src="/images/girl_right_using_tablet.png" alt="" width="942" height="814" loading="lazy" decoding="async" class="wing-art-img" />
                        </div>
                    </div>
                </div>

                <!-- CONFETTI SKY BURST WHEN ALL QUESTS ARE COMPLETED -->
                <div v-if="isAllCompleted" class="confetti-celebration-stage" aria-hidden="true">
                    <span
                        v-for="piece in confettiPieces"
                        :key="piece.id"
                        class="confetti-bit"
                        :style="{
                            left: piece.left,
                            backgroundColor: piece.color,
                            animationDelay: piece.delay,
                            animationDuration: piece.duration,
                            width: piece.size,
                            height: piece.size,
                        }"
                    ></span>
                </div>

                <!-- MAIN S-CURVE TRAIL STAGE -->
                <div class="s-curve-trail-stage" :style="{ height: `${svgHeight}px` }">
                    <!-- SVG S-CURVE PATH LAYER (400px WIDE RESPONSIVE VIEWBOX) -->
                    <svg
                        class="trail-svg-canvas"
                        :viewBox="`0 0 400 ${svgHeight}`"
                        preserveAspectRatio="none"
                        aria-hidden="true"
                    >
                        <defs>
                            <filter id="clayTrailShadow" x="-15%" y="-5%" width="130%" height="115%">
                                <feDropShadow dx="0" dy="6" stdDeviation="4" flood-color="#0369A1" flood-opacity="0.22" />
                            </filter>
                            <filter id="glowEnergyFilter" x="-25%" y="-25%" width="150%" height="150%">
                                <feGaussianBlur stdDeviation="5" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>
                        </defs>

                        <!-- Outer 3D Clay Trail Rim -->
                        <path
                            :d="pathTotalD"
                            fill="none"
                            stroke="rgba(255, 255, 255, 0.85)"
                            stroke-width="34"
                            stroke-linecap="round"
                            filter="url(#clayTrailShadow)"
                        />

                        <!-- Recessed Clay Cobblestone Base Track -->
                        <path
                            :d="pathTotalD"
                            fill="none"
                            stroke="#CBD5E1"
                            stroke-width="24"
                            stroke-linecap="round"
                        />

                        <!-- Unlit Stepping Stone Dashes -->
                        <path
                            :d="pathTotalD"
                            fill="none"
                            stroke="#F8FAFC"
                            stroke-width="8"
                            stroke-linecap="round"
                            stroke-dasharray="2 22"
                        />

                        <!-- Illuminated Multi-Biome Energy Segments -->
                        <g v-for="seg in segments" :key="seg.id">
                            <path
                                v-if="seg.lit"
                                :d="seg.d"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="22"
                                stroke-linecap="round"
                                class="lit-energy-beam"
                            />
                            <path
                                v-if="seg.lit"
                                :d="seg.d"
                                fill="none"
                                stroke="#FEF08A"
                                stroke-width="7"
                                stroke-linecap="round"
                                stroke-dasharray="10 18"
                                class="lit-energy-sparkles"
                            />
                        </g>
                    </svg>

                    <!-- START EXPEDITION PILL POD (TOP CENTER) -->
                    <div class="start-expedition-pod">
                        <div class="clay-start-banner">
                            <span class="start-spark-dot" aria-hidden="true"></span>
                            <span>START EXPEDITION</span>
                        </div>
                        <div class="start-connector-stem" aria-hidden="true"></div>
                    </div>

                    <!-- MILESTONE ISLAND CLUSTERS -->
                    <div
                        v-for="node in nodePositions"
                        :id="`quest-step-${node.step}`"
                        :key="`${node.itemType}-${node.id}`"
                        v-memo="[node.step < currentStep, node.step === currentStep, isNodeDimmedByFilter(node), lockedTooltipStep === node.step, shakingStep === node.step]"
                        class="island-milestone-row"
                        :class="[
                            node.isLeft ? 'row-island-left' : 'row-island-right',
                            { 'is-dimmed-filter': isNodeDimmedByFilter(node) },
                        ]"
                        :style="{ top: `${node.cy - 54}px` }"
                        @mouseenter="prefetchNode(node)"
                    >
                        <!-- FLOATING 3D STORYBOOK DIORAMA PROP (OPPOSITE BEND FOR RICH GRAPHICS ON MOBILE & DESKTOP) -->
                        <div
                            class="curve-bend-diorama"
                            :class="node.isLeft ? 'diorama-on-right' : 'diorama-on-left'"
                            aria-hidden="true"
                        >
                            <img :src="node.biome.dioramaImg" alt="" width="68" height="68" class="diorama-art-img" loading="lazy" decoding="async" />
                        </div>

                        <!-- 1. 3D FLOATING BIOME ISLAND + CHUNKY TOY NODE BUTTON -->
                        <div class="island-anchor-pod">
                            <!-- 3-STAR MASTERY RIBBON (COMPLETED ISLANDS) -->
                            <div
                                v-if="node.step < currentStep"
                                class="island-stars-crown"
                                aria-label="3 of 3 stars earned"
                            >
                                <svg viewBox="0 0 68 24" class="stars-crown-svg" aria-hidden="true">
                                    <path
                                        d="M12 4l2.2 4.4 4.8.7-3.5 3.4.8 4.8L12 15l-4.3 2.3.8-4.8-3.5-3.4 4.8-.7L12 4z"
                                        fill="#FACC15"
                                        stroke="#92400E"
                                        stroke-width="1.2"
                                    />
                                    <path
                                        d="M34 1l2.6 5.2 5.7.8-4.1 4 1 5.7L34 14l-5.2 2.7 1-5.7-4.1-4 5.7-.8L34 1z"
                                        fill="#FDE047"
                                        stroke="#92400E"
                                        stroke-width="1.2"
                                    />
                                    <path
                                        d="M56 4l2.2 4.4 4.8.7-3.5 3.4.8 4.8L56 15l-4.3 2.3.8-4.8-3.5-3.4 4.8-.7L56 4z"
                                        fill="#FACC15"
                                        stroke="#92400E"
                                        stroke-width="1.2"
                                    />
                                </svg>
                            </div>

                            <!-- 3D TRAVELING COMPANION AVATAR BEACON (ACTIVE QUEST ISLAND) -->
                            <div v-if="node.step === currentStep" class="companion-here-beacon" aria-hidden="true">
                                <div class="companion-mascot-bubble">
                                    <!-- 3D Clay Ember Fox Explorer SVG -->
                                    <svg viewBox="0 0 44 44" class="companion-fox-svg">
                                        <circle cx="22" cy="22" r="20" fill="#FFF7ED" stroke="#EA580C" stroke-width="2.5" />
                                        <polygon points="10,16 14,5 20,13" fill="#EA580C" />
                                        <polygon points="34,16 30,5 24,13" fill="#EA580C" />
                                        <ellipse cx="22" cy="24" rx="12" ry="10.5" fill="#F97316" />
                                        <ellipse cx="17" cy="26" rx="6" ry="5.5" fill="#FFEDD5" />
                                        <ellipse cx="27" cy="26" rx="6" ry="5.5" fill="#FFEDD5" />
                                        <circle cx="17.5" cy="22" r="2.1" fill="#1E1B4B" />
                                        <circle cx="26.5" cy="22" r="2.1" fill="#1E1B4B" />
                                        <ellipse cx="22" cy="26.5" rx="2.4" ry="1.7" fill="#1E1B4B" />
                                    </svg>
                                </div>
                                <span class="beacon-quest-tag">QUEST HERE!</span>
                            </div>

                            <!-- LOCKED TOOLTIP BUBBLE WHEN TAPPED EARLY -->
                            <Transition name="pop-fade">
                                <div
                                    v-if="lockedTooltipStep === node.step"
                                    class="locked-island-tooltip"
                                    role="status"
                                >
                                    Finish Step {{ currentStep }} first!
                                </div>
                            </Transition>

                            <!-- PULSING AURA RING FOR ACTIVE QUEST -->
                            <div v-if="node.step === currentStep" class="island-active-aura" aria-hidden="true"></div>

                            <!-- CHUNKY 3D CLAY MILESTONE PUSH BUTTON -->
                            <button
                                type="button"
                                class="clay-island-node-btn"
                                :class="[
                                    node.step < currentStep
                                        ? 'node-is-completed'
                                        : node.step === currentStep
                                          ? 'node-is-active'
                                          : 'node-is-locked',
                                    { 'is-shaking': shakingStep === node.step },
                                ]"
                                :aria-label="`Step ${node.step}: ${node.title} (${
                                    node.step < currentStep
                                        ? 'Completed'
                                        : node.step === currentStep
                                          ? 'Current Quest'
                                          : 'Locked'
                                })`"
                                @click="handleNodeInteraction(node)"
                            >
                                <span class="node-specular-shine" aria-hidden="true"></span>

                                <!-- Completed Checkmark SVG -->
                                <svg
                                    v-if="node.step < currentStep"
                                    viewBox="0 0 28 28"
                                    class="node-state-svg"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M6.5 14.5l5 5 10-11"
                                        fill="none"
                                        stroke="#FFFFFF"
                                        stroke-width="4"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                                <!-- Active Step Number -->
                                <span v-else-if="node.step === currentStep" class="node-step-num">
                                    {{ node.step }}
                                </span>

                                <!-- Locked Padlock SVG -->
                                <svg v-else viewBox="0 0 28 28" class="node-state-svg" aria-hidden="true">
                                    <rect x="6" y="13" width="16" height="11" rx="3.5" fill="#64748B" />
                                    <path
                                        d="M9.5 13V9.5a4.5 4.5 0 0 1 9 0V13"
                                        fill="none"
                                        stroke="#64748B"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                    />
                                    <circle cx="14" cy="18.5" r="1.8" fill="#F8FAFC" />
                                </svg>
                            </button>

                            <!-- 3D FLOATING BIOME ISLAND BASE PLATTER -->
                            <div
                                class="clay-biome-island-platter"
                                :style="{
                                    background: node.biome.gradient,
                                    '--island-ledge': node.biome.ledge,
                                    '--island-rim': node.biome.rim,
                                }"
                                aria-hidden="true"
                            >
                                <span class="island-top-lawn"></span>
                                <!-- Custom 3D Vector Island Prop (Tree / Crystal / Coral) -->
                                <svg viewBox="0 0 32 32" class="island-prop-svg">
                                    <template v-if="node.itemType === 'quiz'">
                                        <!-- Lightning Crystal Spire for Quizzes -->
                                        <polygon points="16,2 25,14 19,14 23,28 9,15 15,15" fill="#FDE047" stroke="#92400E" stroke-width="1.5" />
                                    </template>
                                    <template v-else>
                                        <!-- Lush 3D Pine Tree for Story Lectures -->
                                        <rect x="13.5" y="21" width="5" height="8" rx="1.5" fill="#78350F" />
                                        <polygon points="16,3 27,15 5,15" fill="#4ADE80" />
                                        <polygon points="16,9 29,22 3,22" fill="#22C55E" />
                                    </template>
                                </svg>
                            </div>
                        </div>

                        <!-- 2. 3D CLAY SPEECH CALLOUT QUEST CARD (NEVER OVERFLOWS MOBILE) -->
                        <article
                            class="clay-quest-callout-card"
                            :class="[
                                node.isLeft ? 'callout-from-left' : 'callout-from-right',
                                {
                                    'callout-is-active': node.step === currentStep,
                                    'callout-is-completed': node.step < currentStep,
                                    'callout-is-locked': node.step > currentStep,
                                },
                            ]"
                            role="button"
                            tabindex="0"
                            @click="handleNodeInteraction(node)"
                            @keydown.enter="handleNodeInteraction(node)"
                            @keydown.space.prevent="handleNodeInteraction(node)"
                        >
                            <span class="callout-top-gloss" aria-hidden="true"></span>

                            <!-- Top Kicker Row: Quest Type Pill + XP Bounty -->
                            <div class="callout-header-row">
                                <span
                                    class="callout-biome-pill"
                                    :style="{
                                        backgroundColor: node.biome.pillBg,
                                        color: node.biome.pillText,
                                    }"
                                >
                                    <template v-if="node.itemType === 'quiz'">⚡ QUIZ {{ node.step }}</template>
                                    <template v-else>📖 STORY {{ node.step }}</template>
                                </span>

                                <span class="callout-xp-pill">+{{ node.xp }} XP</span>
                            </div>

                            <!-- Quest Title & Short Description -->
                            <h3 class="callout-quest-title">{{ node.title }}</h3>
                            <p class="callout-quest-summary">{{ node.summary }}</p>

                            <!-- Footer Meta + Action Button -->
                            <div class="callout-footer-row">
                                <div class="callout-meta-tags">
                                    <span class="meta-duration-chip">{{ node.duration }}</span>
                                    <span v-if="node.passingPercentage" class="meta-pass-chip">
                                        Pass {{ node.passingPercentage }}%
                                    </span>
                                </div>

                                <span
                                    class="callout-status-cta"
                                    :class="
                                        node.step < currentStep
                                            ? 'cta-replay'
                                            : node.step === currentStep
                                              ? 'cta-start'
                                              : 'cta-locked'
                                    "
                                >
                                    <template v-if="node.step < currentStep">Replay ↻</template>
                                    <template v-else-if="node.step === currentStep">START ▶</template>
                                    <template v-else>Locked</template>
                                </span>
                            </div>
                        </article>
                    </div>

                    <!-- =====================================================================
                         4. TIER 4: GRAND FINALE — WORLD GUARDIAN & 3D TREASURE VAULT ISLAND
                         ===================================================================== -->
                    <div id="finale-vault" class="finale-vault-pod">
                        <!-- Golden Ribbon Header -->
                        <div class="finale-ribbon-banner">
                            <span class="finale-ribbon-pill">
                                {{ isAllCompleted ? '★ WORLD MASTERED! ★' : 'GRAND TREASURE VAULT' }}
                            </span>
                            <p class="finale-ribbon-sub">
                                {{
                                    isAllCompleted
                                        ? `You conquered ${module?.boss_name || 'the Guardian'} & unlocked +${module?.xp_bonus || 300} Bonus XP!`
                                        : `Complete all ${totalStepsCount} island quests to unlock the Guardian Vault!`
                                }}
                            </p>
                        </div>

                        <!-- Interactive 3D Treasure Chest Stage -->
                        <div
                            class="clay-treasure-chest-stage"
                            :class="{
                                'chest-is-open': isChestOpen && isAllCompleted,
                                'chest-is-shaking': chestShaking,
                            }"
                            role="button"
                            tabindex="0"
                            :aria-label="
                                isAllCompleted
                                    ? 'Open Grand Treasure Chest'
                                    : `Locked Treasure Chest. ${remainingMilestonesCount} quests remaining.`
                            "
                            @click="handleChestClick"
                            @keydown.enter="handleChestClick"
                            @keydown.space.prevent="handleChestClick"
                        >
                            <!-- Spinning Sunburst Aura when Unlocked -->
                            <div
                                class="vault-golden-sunburst"
                                :class="{ 'is-spinning': isAllCompleted }"
                                aria-hidden="true"
                            ></div>

                            <!-- 3D Treasure Chest Box -->
                            <div class="clay-chest-assembly" aria-hidden="true">
                                <!-- Chest Lid -->
                                <div class="clay-chest-lid">
                                    <div class="chest-lid-handle"></div>
                                    <div class="chest-lid-shell">
                                        <span class="chest-gold-strap"></span>
                                        <span class="chest-gold-strap"></span>
                                    </div>
                                </div>

                                <!-- Overflowing Coins, Gems & Trophy Booty (Visible when Open) -->
                                <div
                                    class="clay-chest-loot"
                                    :class="{ 'loot-popped': isChestOpen && isAllCompleted }"
                                >
                                    <svg viewBox="0 0 120 64" class="loot-treasure-svg">
                                        <!-- Golden Coins & Ruby/Emerald Gems + Center Trophy -->
                                        <circle cx="28" cy="46" r="11" fill="#FACC15" stroke="#B45309" stroke-width="2" />
                                        <circle cx="92" cy="46" r="11" fill="#FACC15" stroke="#B45309" stroke-width="2" />
                                        <polygon points="44,32 54,44 44,54 34,44" fill="#38BDF8" stroke="#0284C7" stroke-width="1.8" />
                                        <polygon points="76,32 86,44 76,54 66,44" fill="#FB7185" stroke="#E11D48" stroke-width="1.8" />
                                        <!-- Golden Trophy Cup -->
                                        <path d="M48 12h24v16c0 6.6-5.4 12-12 12s-12-5.4-12-12V12z" fill="#FDE047" stroke="#B45309" stroke-width="2.2" />
                                        <rect x="55" y="40" width="10" height="8" fill="#F59E0B" />
                                        <rect x="49" y="48" width="22" height="5" rx="2.5" fill="#B45309" />
                                    </svg>
                                </div>

                                <!-- Chest Base Body -->
                                <div class="clay-chest-base">
                                    <span class="chest-gold-strap"></span>
                                    <div class="chest-center-lock">
                                        <svg v-if="isAllCompleted" viewBox="0 0 20 20" class="lock-emblem-svg">
                                            <path
                                                d="M10 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L10 14.3l-4.8 2.6.9-5.4-3.9-3.8 5.4-.8L10 2z"
                                                fill="#78350F"
                                            />
                                        </svg>
                                        <svg v-else viewBox="0 0 20 20" class="lock-emblem-svg">
                                            <rect x="4.5" y="9" width="11" height="8" rx="2.5" fill="#78350F" />
                                            <path
                                                d="M7 9V6.8a3 3 0 0 1 6 0V9"
                                                fill="none"
                                                stroke="#78350F"
                                                stroke-width="2.2"
                                            />
                                        </svg>
                                    </div>
                                    <span class="chest-gold-strap"></span>
                                </div>
                            </div>

                            <span class="clay-chest-cta-pill">
                                <template v-if="!isAllCompleted">
                                    🔒 Locked • {{ remainingMilestonesCount }}
                                    {{ remainingMilestonesCount === 1 ? 'Quest' : 'Quests' }} Left
                                </template>
                                <template v-else-if="isChestOpen">
                                    ✨ +{{ module?.xp_bonus || 300 }} Bonus XP Claimed!
                                </template>
                                <template v-else>Tap to Open Vault!</template>
                            </span>
                        </div>
                    </div>
                </div>
                </template>

                <!-- =====================================================================
                     EMPTY EXPEDITION STAGE (WHEN NO STORIES OR QUIZZES YET)
                     ===================================================================== -->
                <div v-else class="clay-empty-expedition-stage">
                    <div class="clay-empty-expedition-card">
                        <div class="empty-icon-wrapper" aria-hidden="true">
                            <div class="empty-icon-bubble">
                                <span class="empty-emoji">🏕️</span>
                            </div>
                            <div class="empty-sparkle-decor spark-1">✨</div>
                            <div class="empty-sparkle-decor spark-2">🌟</div>
                        </div>

                        <span class="empty-status-pill">EXPEDITION ON PAUSE</span>

                        <h2 class="empty-title">No Activity Yet</h2>

                        <p class="empty-desc">
                            There are no story scrolls or quiz challenges available for this realm yet. Come back later!
                        </p>

                        <div class="empty-actions">
                            <button
                                type="button"
                                class="clay-empty-back-btn"
                                @click="goBackToWorlds"
                            >
                                <svg viewBox="0 0 20 20" class="empty-btn-icon" aria-hidden="true">
                                    <path
                                        d="M12.5 4.5L7 10l5.5 5.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                <span>Back to Realms</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =========================================================================
                 FLOATING MOBILE THUMB-ZONE QUICK QUEST BAR
                 ========================================================================= -->
            <div v-if="activeQuestNode" class="mobile-thumb-quest-bar">
                <div class="thumb-quest-info" @click="scrollToCurrentStep">
                    <span class="thumb-step-badge">
                        {{ isAllCompleted ? 'DONE' : `STEP ${activeQuestNode.step}` }}
                    </span>
                    <div class="thumb-quest-text">
                        <span class="thumb-quest-kicker">
                            {{ isAllCompleted ? 'All Island Quests Complete!' : 'Current Active Quest' }}
                        </span>
                        <strong class="thumb-quest-name">{{ activeQuestNode.title }}</strong>
                    </div>
                </div>

                <button
                    type="button"
                    class="clay-thumb-play-btn"
                    @click="isAllCompleted ? handleChestClick() : handleNodeInteraction(activeQuestNode)"
                >
                    <span>{{ isAllCompleted ? 'VAULT' : 'PLAY ▶' }}</span>
                </button>
            </div>

            <!-- =========================================================================
                 QUEST COMPLETION CELEBRATION TOAST
                 ========================================================================= -->
            <Transition name="toast-pop">
                <div v-if="showQuestCelebrationToast" class="clay-quest-toast" role="status" aria-live="polite">
                    <div class="toast-star-badge" aria-hidden="true">★</div>
                    <div class="toast-copy">
                        <strong>Quest Complete! +{{ lastCompletedStepXp }} XP & 3 Stars!</strong>
                        <span>{{ lastCompletedStepTitle }} mastered! Next island unlocked.</span>
                    </div>
                </div>
            </Transition>
        </section>

        <!-- =========================================================================
             3D CLAY LOCKED TREASURE VAULT BOTTOM SHEET / DIALOG
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="clay-sheet-pop">
                <div
                    v-if="showLockedChestModal"
                    class="clay-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="locked-vault-heading"
                    @click.self="showLockedChestModal = false"
                >
                    <div class="clay-locked-vault-sheet">
                        <span class="sheet-grab-bar" aria-hidden="true"></span>

                        <div class="vault-emblem-pod" aria-hidden="true">
                            <svg viewBox="0 0 64 64" class="vault-lock-svg">
                                <rect x="10" y="24" width="44" height="32" rx="10" fill="#F59E0B" stroke="#78350F" stroke-width="3" />
                                <path d="M20 24V18a12 12 0 0 1 24 0v6" fill="none" stroke="#78350F" stroke-width="4.5" stroke-linecap="round" />
                                <circle cx="32" cy="38" r="4.5" fill="#78350F" />
                                <rect x="30" y="40" width="4" height="8" rx="2" fill="#78350F" />
                            </svg>
                        </div>

                        <span class="vault-status-pill">TREASURE VAULT LOCKED</span>
                        <h3 id="locked-vault-heading" class="vault-sheet-title">
                            Keep Going, Young Explorer!
                        </h3>
                        <p class="vault-sheet-desc">
                            Complete
                            <strong class="vault-highlight">
                                {{ remainingMilestonesCount }} more
                                {{ remainingMilestonesCount === 1 ? 'island quest' : 'island quests' }}
                            </strong>
                            along the trail to defeat {{ module?.boss_name || 'the Guardian' }} and unlock
                            <strong>+{{ module?.xp_bonus || 300 }} Bonus XP</strong>!
                        </p>

                        <div class="vault-progress-box">
                            <div class="vault-progress-labels">
                                <span>EXPEDITION PROGRESS</span>
                                <strong>{{ completedCount }} / {{ totalStepsCount }} Quests ({{ progressPercentage }}%)</strong>
                            </div>
                            <div class="vault-progress-track">
                                <div class="vault-progress-fill" :style="{ width: `${progressPercentage}%` }"></div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="clay-sheet-primary-cta"
                            @click="closeLockedChestModalAndScroll"
                        >
                            <span class="cta-top-shine" aria-hidden="true"></span>
                            <span>JUMP TO STEP {{ currentStep }} ▶</span>
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   MOBILE-FIRST SMOOTH 3D CARTOON CLAYMORPHISM EXPEDITION PROGRESS TRACKER
   ========================================================================== */
.clay-expedition-page {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-bottom: 4.5rem;
}

/* ==========================================================================
   1. STICKY MOBILE-FIRST 3D CLAY EXPEDITION HUD BAR
   ========================================================================== */
.clay-sticky-expedition-hud {
    position: sticky;
    top: 0.35rem;
    z-index: 45;
    background: linear-gradient(180deg, var(--px-surface-card, rgba(255, 255, 255, 0.96)) 0%, var(--px-surface-card-bottom, rgba(241, 245, 249, 0.96)) 100%);
    backdrop-filter: blur(12px);
    border-radius: 22px;
    padding: 0.55rem 0.7rem;
    border: 2.5px solid var(--px-card-border, #FFFFFF);
    box-shadow:
        0 6px 0 var(--px-hud-ledge, #CBD5E1),
        0 12px 24px rgba(var(--px-shadow-rgb, 30, 27, 75), 0.16),
        inset 0 2px 3px var(--px-rim-top, rgba(255, 255, 255, 0.95));
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.expedition-hud-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
}

.clay-back-worlds-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.38rem 0.68rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #6366F1 0%, #4338CA 100%);
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 3px 0 #312E81,
        0 6px 12px rgba(67, 56, 202, 0.28),
        inset 0 2px 2px rgba(255, 255, 255, 0.45);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.18s var(--spring-bounce), box-shadow 0.18s ease;
}

.clay-back-worlds-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #312E81;
}

.back-arrow-svg {
    width: 14px;
    height: 14px;
}

.expedition-hud-title-pill {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    flex: 1 1 auto;
    padding: 0.28rem 0.6rem;
    border-radius: 9999px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-card-border, #E2E8F0);
}

.hud-biome-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 6px #10B981;
    flex-shrink: 0;
}

.hud-module-name-trunc {
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--px-ink, #1E1B4B);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.expedition-hud-counters {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    flex-shrink: 0;
}

.clay-mini-xp-pill,
.clay-mini-star-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    border: 1.5px solid #FFFFFF;
}

.clay-mini-xp-pill {
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    box-shadow: 0 2.5px 0 #B45309;
}

.clay-mini-star-pill {
    background: linear-gradient(180deg, #E0F2FE 0%, #38BDF8 100%);
    color: #0C4A6E;
    box-shadow: 0 2.5px 0 #0284C7;
}

.mini-bolt-svg,
.mini-star-svg {
    width: 11px;
    height: 11px;
}

.expedition-hud-progress-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.clay-expedition-track {
    flex: 1 1 auto;
    height: 12px;
    border-radius: 9999px;
    background: #E2E8F0;
    border: 2px solid #CBD5E1;
    box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.15);
    overflow: hidden;
    position: relative;
}

.clay-expedition-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #10B981 0%, #34D399 50%, #FACC15 100%);
    transition: width 0.5s var(--spring-bounce);
    position: relative;
}

.expedition-fill-gloss {
    position: absolute;
    top: 1px;
    left: 6px;
    right: 6px;
    height: 4px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.6);
}

.expedition-pct-badge {
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    color: #334155;
    flex-shrink: 0;
}

/* ==========================================================================
   2. COMPACT 3D CLAY WORLD DIORAMA HERO BANNER
   ========================================================================== */
.clay-world-hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 26px;
    padding: 0.95rem 1rem;
    background: linear-gradient(135deg, #059669 0%, #0EA5E9 55%, #4F46E5 100%);
    border: 3px solid rgba(255, 255, 255, 0.92);
    box-shadow:
        0 7px 0 #1E3A8A,
        0 16px 28px rgba(14, 165, 233, 0.28),
        inset 0 3px 6px rgba(255, 255, 255, 0.45);
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.hero-theme-ocean {
    background: linear-gradient(135deg, #0284C7 0%, #06B6D4 55%, #3B82F6 100%);
}

.hero-theme-cosmic {
    background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 55%, #EC4899 100%);
}

.hero-theme-volcano {
    background: linear-gradient(135deg, #EA580C 0%, #F97316 55%, #E11D48 100%);
}

.hero-top-specular {
    position: absolute;
    top: 4px;
    left: 18px;
    right: 18px;
    height: 12px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.hero-orb-right {
    width: 120px;
    height: 120px;
    top: -35px;
    right: -25px;
    background: radial-gradient(circle, rgba(254, 240, 138, 0.35) 0%, transparent 70%);
}

.hero-orb-left {
    width: 100px;
    height: 100px;
    bottom: -35px;
    left: 8%;
    background: radial-gradient(circle, rgba(167, 243, 208, 0.3) 0%, transparent 70%);
}

.world-hero-top-row {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.world-grade-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.22);
    border: 1.5px solid rgba(255, 255, 255, 0.55);
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 700;
}

.live-pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #FDE047;
    box-shadow: 0 0 8px #FDE047;
}

.world-boss-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.35);
    border: 1.5px solid rgba(254, 240, 138, 0.65);
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 700;
    color: #FEF08A;
}

.boss-crown-svg {
    width: 12px;
    height: 12px;
}

.world-hero-main {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.world-hero-copy {
    flex: 1 1 auto;
    min-width: 0;
}

.world-hero-title {
    margin: 0 0 0.22rem;
    font-family: var(--font-display);
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1.15;
    text-shadow: 0 2px 4px rgba(15, 23, 42, 0.28);
}

.world-hero-desc {
    margin: 0;
    font-family: var(--font-body);
    font-size: 0.76rem;
    font-weight: 700;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.92);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.world-hero-crest {
    width: 54px;
    height: 54px;
    flex-shrink: 0;
    animation: crestFloat 3.6s ease-in-out infinite;
}

.world-crest-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 6px 10px rgba(15, 23, 42, 0.3));
}

@keyframes crestFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(4deg); }
}

.world-hero-controls {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding-top: 0.45rem;
    border-top: 1.5px solid rgba(255, 255, 255, 0.22);
}

.clay-quest-filter-group {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.clay-quest-tab {
    padding: 0.28rem 0.62rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.18);
    border: 1.5px solid rgba(255, 255, 255, 0.45);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s var(--spring-bounce);
}

.clay-quest-tab.is-active {
    background: #FFFFFF;
    color: #1E1B4B;
    border-color: #FFFFFF;
    box-shadow: 0 3px 0 rgba(15, 23, 42, 0.25);
    transform: translateY(-1px);
}

.clay-jump-quest-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.32rem 0.72rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FDE047 0%, #F59E0B 100%);
    color: #78350F;
    border: 2px solid #FFFFFF;
    box-shadow: 0 3px 0 #B45309;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.18s var(--spring-bounce);
}

.clay-jump-quest-btn:active {
    transform: translateY(2px);
}

.jump-icon-svg {
    width: 12px;
    height: 12px;
}

/* ==========================================================================
   3. GRAPHIC-RICH MOBILE-FIRST 3D S-CURVE EXPEDITION MAP VIEWPORT
   ========================================================================== */
.clay-map-viewport {
    position: relative;
    width: 100%;
    border-radius: 30px;
    background:
        radial-gradient(circle at 20% 15%, var(--px-sun-bloom, rgba(255, 255, 255, 0.65)) 0%, transparent 45%),
        radial-gradient(circle at 80% 45%, var(--px-secondary-bloom, rgba(186, 230, 253, 0.65)) 0%, transparent 48%),
        linear-gradient(180deg, var(--px-sky-top, #E0F2FE) 0%, var(--px-sky-mid, #BAE6FD) 45%, var(--px-sky-bottom, #D9F99D) 100%);
    border: 3px solid var(--px-card-border, #FFFFFF);
    box-shadow:
        0 10px 0 var(--px-hud-ledge, #94A3B8),
        0 20px 38px rgba(var(--px-shadow-rgb, 15, 23, 42), 0.18),
        inset 0 4px 10px var(--px-rim-top, rgba(255, 255, 255, 0.9));
    overflow: hidden;
    padding: 0.5rem 0 2rem;
}

/* ==========================================================================
   EMPTY EXPEDITION STAGE (NO ACTIVITIES YET)
   ========================================================================== */
.clay-empty-expedition-stage {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 480px;
    padding: 3rem 1.25rem;
    position: relative;
    z-index: 5;
}

.clay-empty-expedition-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    max-width: 460px;
    width: 100%;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border-radius: 28px;
    padding: 2.25rem 1.75rem;
    border: 3.5px solid #FFFFFF;
    box-shadow:
        0 10px 0 #CBD5E1,
        0 20px 32px rgba(15, 23, 42, 0.12),
        inset 0 3px 6px rgba(255, 255, 255, 0.95);
    position: relative;
    animation: clayEmptyFloat 4s ease-in-out infinite alternate;
}

@keyframes clayEmptyFloat {
    0% { transform: translateY(0); }
    100% { transform: translateY(-6px); }
}

.empty-icon-wrapper {
    position: relative;
    margin-bottom: 1.25rem;
}

.empty-icon-bubble {
    width: 92px;
    height: 92px;
    border-radius: 50%;
    background: linear-gradient(180deg, #FEF3C7 0%, #FDE68A 65%, #F59E0B 100%);
    border: 3.5px solid #FFFFFF;
    box-shadow:
        0 6px 0 #D97706,
        0 12px 20px rgba(217, 119, 6, 0.25),
        inset 0 4px 6px rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-emoji {
    font-size: 2.85rem;
    filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.15));
}

.empty-sparkle-decor {
    position: absolute;
    font-size: 1.35rem;
    animation: sparkleSpin 3s ease-in-out infinite alternate;
    pointer-events: none;
}

.spark-1 {
    top: -6px;
    right: -10px;
}

.spark-2 {
    bottom: -4px;
    left: -12px;
    animation-delay: 1.5s;
}

@keyframes sparkleSpin {
    0% { transform: scale(0.9) rotate(-10deg); opacity: 0.8; }
    100% { transform: scale(1.15) rotate(15deg); opacity: 1; }
}

.empty-status-pill {
    display: inline-block;
    padding: 0.35rem 0.95rem;
    background: #FEF3C7;
    color: #92400E;
    font-family: var(--font-display);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    border-radius: 9999px;
    border: 2px solid #FDE68A;
    margin-bottom: 0.75rem;
}

.empty-title {
    font-family: var(--font-display);
    font-size: 1.75rem;
    font-weight: 800;
    color: #1E293B;
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
}

.empty-desc {
    font-family: var(--font-body);
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.55;
    color: #64748B;
    margin: 0 0 1.5rem;
    max-width: 380px;
}

.empty-actions {
    display: flex;
    justify-content: center;
    width: 100%;
}

.clay-empty-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.65rem;
    background: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 5px 0 #0369A1,
        0 10px 18px rgba(2, 132, 199, 0.35),
        inset 0 2px 4px rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.clay-empty-back-btn:hover {
    transform: translateY(-2px);
    box-shadow:
        0 7px 0 #0369A1,
        0 14px 22px rgba(2, 132, 199, 0.4),
        inset 0 2px 4px rgba(255, 255, 255, 0.85);
}

.clay-empty-back-btn:active {
    transform: translateY(3px);
    box-shadow:
        0 2px 0 #0369A1,
        0 4px 8px rgba(2, 132, 199, 0.3),
        inset 0 1px 2px rgba(0, 0, 0, 0.2);
}

.empty-btn-icon {
    width: 18px;
    height: 18px;
}

/* Ambient Clouds inside Map */
.map-sky-decor {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.map-cloud {
    position: absolute;
    background: rgba(255, 255, 255, 0.72);
    border-radius: 9999px;
    box-shadow:
        0 6px 0 rgba(186, 230, 253, 0.6),
        inset 0 3px 4px #FFFFFF;
}

.map-cloud-1 { width: 95px; height: 32px; top: 8%; left: -12px; }
.map-cloud-2 { width: 110px; height: 36px; top: 32%; right: -18px; }
.map-cloud-3 { width: 88px; height: 30px; top: 62%; left: 6%; }
.map-cloud-4 { width: 105px; height: 34px; top: 84%; right: 5%; }

/* Desktop Flanking Wings (>= 1024px) */
.desktop-storybook-wings {
    display: none;
}

/* Confetti Sky Burst */
.confetti-celebration-stage {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 30;
    overflow: hidden;
}

.confetti-bit {
    position: absolute;
    top: -16px;
    border-radius: 3px;
    animation: confettiRain linear infinite;
}

@keyframes confettiRain {
    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
    100% { transform: translateY(900px) rotate(720deg); opacity: 0; }
}

/* S-Curve Stage Container */
.s-curve-trail-stage {
    position: relative;
    width: 100%;
    max-width: 560px;
    margin: 0 auto;
    z-index: 5;
}

.trail-svg-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.lit-energy-beam {
    filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.45));
}

.lit-energy-sparkles {
    animation: energyDashFlow 1.6s linear infinite;
}

@keyframes energyDashFlow {
    from { stroke-dashoffset: 28; }
    to { stroke-dashoffset: 0; }
}

/* Start Expedition Pod */
.start-expedition-pod {
    position: absolute;
    top: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 12;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.clay-start-banner {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.38rem 0.95rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5F9 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 4px 0 #94A3B8,
        0 8px 16px rgba(15, 23, 42, 0.14);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #0F172A;
}

.start-spark-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 8px #10B981;
}

.start-connector-stem {
    width: 4px;
    height: 16px;
    background: #10B981;
    border-radius: 9999px;
}

/* ==========================================================================
   MILESTONE ISLAND ROW (MOBILE-OPTIMIZED SO BUTTON & CARD NEVER CLIP)
   ========================================================================== */
.island-milestone-row {
    position: absolute;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    padding: 0 0.65rem;
    gap: 0.65rem;
    z-index: 10;
    pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.island-milestone-row.is-dimmed-filter {
    opacity: 0.32;
    filter: grayscale(0.6);
}

.row-island-left {
    flex-direction: row;
    justify-content: flex-start;
}

.row-island-right {
    flex-direction: row-reverse;
    justify-content: flex-start;
}

/* Floating 3D Storybook Vignette on the Opposite Bend (Visible on Mobile!) */
.curve-bend-diorama {
    position: absolute;
    top: -70px;
    width: 68px;
    height: 68px;
    pointer-events: none;
    z-index: 6;
    opacity: 0.94;
    animation: dioramaFloat 4.2s ease-in-out infinite alternate;
}

.diorama-on-right {
    right: 8px;
}

.diorama-on-left {
    left: 8px;
}

.diorama-art-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 6px 12px rgba(14, 116, 144, 0.25));
}

@keyframes dioramaFloat {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(-6px) scale(1.04); }
}

/* 1. 3D Island Anchor Pod */
.island-anchor-pod {
    position: relative;
    width: 96px;
    height: 102px;
    display: flex;
     flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    pointer-events: auto;
}

/* 3-Star Mastery Ribbon above Completed Islands */
.island-stars-crown {
    position: absolute;
    top: -20px;
    z-index: 18;
    pointer-events: none;
    filter: drop-shadow(0 3px 4px rgba(15, 23, 42, 0.22));
}

.stars-crown-svg {
    width: 58px;
    height: 21px;
}

/* Traveling 3D Companion Beacon on Active Quest */
.companion-here-beacon {
    position: absolute;
    top: -44px;
    z-index: 22;
    display: flex;
    flex-direction: column;
    align-items: center;
    pointer-events: none;
    animation: companionBounce 2s ease-in-out infinite;
}

.companion-mascot-bubble {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    box-shadow: 0 4px 10px rgba(234, 88, 12, 0.35);
}

.companion-fox-svg {
    width: 100%;
    height: 100%;
}

.beacon-quest-tag {
    margin-top: -3px;
    padding: 0.14rem 0.48rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #F97316 0%, #EA580C 100%);
    color: #FFFFFF;
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 3px 6px rgba(154, 52, 18, 0.35);
    font-family: var(--font-display);
    font-size: 0.54rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

@keyframes companionBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

/* Locked Island Tooltip */
.locked-island-tooltip {
    position: absolute;
    top: -32px;
    z-index: 25;
    background: #1E1B4B;
    color: #FEF08A;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    border: 1.5px solid #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 6px 12px rgba(15, 23, 42, 0.3);
}

/* Pulsing Ring on Active Island */
.island-active-aura {
    position: absolute;
    width: 74px;
    height: 74px;
    top: 8px;
    border-radius: 50%;
    border: 3px solid rgba(249, 115, 22, 0.65);
    animation: auraExpand 1.9s cubic-bezier(0.24, 0, 0.38, 1) infinite;
    pointer-events: none;
    z-index: 3;
}

@keyframes auraExpand {
    0% { transform: scale(0.88); opacity: 1; }
    100% { transform: scale(1.36); opacity: 0; }
}

/* Chunky 3D Pushable Toy Button */
.clay-island-node-btn {
    position: relative;
    z-index: 8;
    width: 62px;
    height: 62px;
    border-radius: 50%;
    border: 3.5px solid #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s ease;
}

.node-specular-shine {
    position: absolute;
    top: 4px;
    left: 11px;
    right: 11px;
    height: 15px;
    border-radius: 50%;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.node-is-completed {
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    box-shadow:
        0 6px 0 #065F46,
        0 10px 18px rgba(5, 150, 105, 0.38),
        inset 0 2px 3px rgba(255, 255, 255, 0.6);
}

.node-is-active {
    background: linear-gradient(180deg, #FB923C 0%, #EA580C 100%);
    box-shadow:
        0 6px 0 #9A3412,
        0 12px 22px rgba(234, 88, 12, 0.48),
        inset 0 2px 3px rgba(255, 255, 255, 0.65);
    animation: activeBtnPulse 2.4s ease-in-out infinite;
}

@keyframes activeBtnPulse {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.node-is-locked {
    background: linear-gradient(180deg, #E2E8F0 0%, #CBD5E1 100%);
    box-shadow:
        0 5px 0 #64748B,
        0 8px 14px rgba(71, 85, 105, 0.22);
}

.clay-island-node-btn:active {
    transform: translateY(4px);
}

.node-state-svg {
    width: 28px;
    height: 28px;
}

.node-step-num {
    font-family: var(--font-display);
    font-size: 1.55rem;
    font-weight: 800;
    color: #FFFFFF;
    text-shadow: 0 2px 0 #9A3412;
}

.is-shaking {
    animation: nodeLockShake 0.42s ease-in-out;
}

@keyframes nodeLockShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    50% { transform: translateX(6px); }
    75% { transform: translateX(-4px); }
}

/* 3D Floating Biome Island Platter */
.clay-biome-island-platter {
    position: absolute;
    bottom: 2px;
    width: 94px;
    height: 44px;
    border-radius: 50%;
    border: 2.5px solid var(--island-rim, #A7F3D0);
    box-shadow:
        0 7px 0 var(--island-ledge, #065F46),
        0 14px 20px rgba(15, 23, 42, 0.22),
        inset 0 3px 6px rgba(255, 255, 255, 0.65);
    z-index: 4;
    pointer-events: none;
}

.island-top-lawn {
    position: absolute;
    inset: 3px 8px 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.22);
}

.island-prop-svg {
    position: absolute;
    top: -14px;
    right: -4px;
    width: 26px;
    height: 26px;
    filter: drop-shadow(0 3px 4px rgba(15, 23, 42, 0.25));
}

/* ==========================================================================
   2. 3D CLAY SPEECH CALLOUT QUEST CARD (CRISP & FULL WIDTH ON MOBILE)
   ========================================================================== */
.clay-quest-callout-card {
    position: relative;
    flex: 1 1 auto;
    min-width: 0;
    max-width: 275px;
    border-radius: 22px;
    padding: 0.72rem 0.82rem;
    background: linear-gradient(165deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 6px 0 #94A3B8,
        0 12px 22px rgba(15, 23, 42, 0.14),
        inset 0 2px 4px #FFFFFF;
    pointer-events: auto;
    cursor: pointer;
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 0.32rem;
}

.clay-quest-callout-card.callout-is-active {
    background: linear-gradient(165deg, #FFFFFF 0%, #FFFBEB 65%, #FEF3C7 100%);
    border-color: #FDE047;
    box-shadow:
        0 7px 0 #D97706,
        0 14px 26px rgba(245, 158, 11, 0.28),
        inset 0 2px 4px #FFFFFF;
}

.clay-quest-callout-card.callout-is-completed {
    border-color: #A7F3D0;
    box-shadow:
        0 6px 0 #059669,
        0 12px 20px rgba(16, 185, 129, 0.18),
        inset 0 2px 4px #FFFFFF;
}

.clay-quest-callout-card.callout-is-locked {
    opacity: 0.88;
}

.clay-quest-callout-card:active {
    transform: translateY(2px);
}

.callout-top-gloss {
    position: absolute;
    top: 3px;
    left: 14px;
    right: 14px;
    height: 9px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.callout-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
}

.callout-biome-pill {
    padding: 0.16rem 0.48rem;
    border-radius: 8px;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.03em;
}

.callout-xp-pill {
    padding: 0.15rem 0.48rem;
    border-radius: 9999px;
    background: #FFFBEB;
    border: 1.5px solid #FDE68A;
    color: #B45309;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    flex-shrink: 0;
}

.callout-quest-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.88rem;
    font-weight: 800;
    line-height: 1.2;
    color: #1E1B4B;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.callout-quest-summary {
    margin: 0;
    font-family: var(--font-body);
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.32;
    color: #475569;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.callout-footer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    padding-top: 0.32rem;
    border-top: 1.5px dashed #E2E8F0;
}

.callout-meta-tags {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.meta-duration-chip,
.meta-pass-chip {
    padding: 0.14rem 0.42rem;
    border-radius: 6px;
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 700;
}

.meta-duration-chip {
    background: #F1F5F9;
    color: #475569;
}

.meta-pass-chip {
    background: #FEF3C7;
    color: #92400E;
}

.callout-status-cta {
    padding: 0.22rem 0.58rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 800;
    flex-shrink: 0;
}

.cta-start {
    background: linear-gradient(180deg, #FB923C 0%, #EA580C 100%);
    color: #FFFFFF;
    box-shadow: 0 2.5px 0 #9A3412;
}

.cta-replay {
    background: #DCFCE7;
    color: #065F46;
}

.cta-locked {
    background: #F1F5F9;
    color: #94A3B8;
}

/* ==========================================================================
   4. GRAND FINALE — WORLD GUARDIAN & 3D TREASURE VAULT ISLAND
   ========================================================================== */
.finale-vault-pod {
    position: absolute;
    bottom: 36px;
    left: 50%;
    transform: translateX(-50%);
    width: 92%;
    max-width: 380px;
    z-index: 16;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.finale-ribbon-banner {
    margin-bottom: 0.65rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.finale-ribbon-pill {
    padding: 0.35rem 1.1rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow:
        0 4px 0 #B45309,
        0 8px 16px rgba(180, 83, 9, 0.3);
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #78350F;
}

.finale-ribbon-sub {
    margin: 0;
    font-family: var(--font-body);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink, #0C4A6E);
    background: var(--px-surface-glass, rgba(255, 255, 255, 0.85));
    border: 1.5px solid var(--px-card-border, rgba(255, 255, 255, 0.9));
    padding: 0.25rem 0.78rem;
    border-radius: 9999px;
    max-width: 310px;
}

.clay-treasure-chest-stage {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    user-select: none;
    transition: transform 0.25s var(--spring-bounce);
}

.clay-treasure-chest-stage:active {
    transform: scale(0.96);
}

.chest-is-shaking {
    animation: nodeLockShake 0.45s ease-in-out;
}

.vault-golden-sunburst {
    position: absolute;
    top: -28px;
    width: 170px;
    height: 170px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(250, 204, 21, 0.55) 0%, rgba(250, 204, 21, 0) 70%);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.4s ease;
}

.vault-golden-sunburst.is-spinning {
    opacity: 1;
    animation: spinSunburst 12s linear infinite;
}

@keyframes spinSunburst {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.clay-chest-assembly {
    position: relative;
    width: 126px;
    height: 92px;
    z-index: 5;
    perspective: 500px;
}

.clay-chest-lid {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 44px;
    z-index: 9;
    transform-origin: bottom center;
    transition: transform 0.55s var(--spring-bounce);
}

.chest-is-open .clay-chest-lid {
    transform: rotateX(-72deg) translateY(-16px);
}

.chest-lid-handle {
    position: absolute;
    top: -7px;
    left: 50%;
    transform: translateX(-50%);
    width: 28px;
    height: 10px;
    border: 3px solid #FDE047;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}

.chest-lid-shell {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #B45309 0%, #78350F 100%);
    border-radius: 28px 28px 8px 8px;
    border: 3px solid #451A03;
    box-shadow: inset 0 4px 6px rgba(255, 255, 255, 0.35);
    display: flex;
    justify-content: space-around;
    align-items: center;
}

.chest-gold-strap {
    width: 12px;
    height: 100%;
    background: linear-gradient(180deg, #FDE047 0%, #D97706 100%);
}

.clay-chest-loot {
    position: absolute;
    top: 4px;
    left: 6px;
    right: 6px;
    height: 54px;
    z-index: 7;
    opacity: 0;
    transform: scale(0.6) translateY(10px);
    transition: all 0.5s var(--spring-bounce);
    pointer-events: none;
}

.clay-chest-loot.loot-popped {
    opacity: 1;
    transform: scale(1.08) translateY(-14px);
}

.loot-treasure-svg {
    width: 100%;
    height: 100%;
}

.clay-chest-base {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 54px;
    background: linear-gradient(180deg, #92400E 0%, #451A03 100%);
    border-radius: 8px 8px 18px 18px;
    border: 3px solid #290E03;
    box-shadow: 0 8px 0 #1E293B, 0 14px 22px rgba(15, 23, 42, 0.28);
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 6;
}

.chest-center-lock {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: radial-gradient(circle, #FEF08A 0%, #F59E0B 100%);
    border: 2.5px solid #FFFFFF;
    box-shadow: 0 3px 0 #92400E;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.lock-emblem-svg {
    width: 16px;
    height: 16px;
}

.clay-chest-cta-pill {
    margin-top: 0.7rem;
    padding: 0.38rem 0.95rem;
    border-radius: 9999px;
    background: #FFFFFF;
    border: 2px solid #FDE68A;
    box-shadow: 0 4px 0 #CBD5E1, 0 8px 14px rgba(15, 23, 42, 0.12);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: #92400E;
}

/* ==========================================================================
   FLOATING MOBILE THUMB-ZONE QUICK QUEST BAR
   ========================================================================== */
.mobile-thumb-quest-bar {
    position: fixed;
    left: 0.75rem;
    right: 0.75rem;
    bottom: 6.3rem;
    z-index: 48;
    max-width: 500px;
    margin: 0 auto;
    padding: 0.55rem 0.7rem;
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(30, 27, 75, 0.96) 0%, rgba(15, 23, 42, 0.96) 100%);
    border: 2.5px solid rgba(255, 255, 255, 0.9);
    box-shadow:
        0 6px 0 #0F172A,
        0 14px 28px rgba(15, 23, 42, 0.35);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    color: #FFFFFF;
}

.thumb-quest-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
    flex: 1 1 auto;
    cursor: pointer;
}

.thumb-step-badge {
    padding: 0.28rem 0.55rem;
    border-radius: 12px;
    background: linear-gradient(180deg, #FDE047 0%, #F59E0B 100%);
    color: #78350F;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    flex-shrink: 0;
}

.thumb-quest-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.thumb-quest-kicker {
    font-family: var(--font-body);
    font-size: 0.58rem;
    font-weight: 800;
    color: #93C5FD;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.thumb-quest-name {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 700;
    color: #FFFFFF;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.clay-thumb-play-btn {
    padding: 0.45rem 0.9rem;
    border-radius: 14px;
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
    box-shadow: 0 3.5px 0 #065F46;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
    flex-shrink: 0;
}

.clay-thumb-play-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #065F46;
}

/* ==========================================================================
   CELEBRATION TOAST
   ========================================================================== */
.clay-quest-toast {
    position: fixed;
    top: 4.8rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 95;
    width: 92%;
    max-width: 400px;
    padding: 0.75rem 1rem;
    border-radius: 22px;
    background: linear-gradient(135deg, #059669 0%, #10B981 100%);
    color: #FFFFFF;
    border: 2.5px solid #FFFFFF;
    box-shadow: 0 6px 0 #065F46, 0 16px 32px rgba(5, 150, 105, 0.38);
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.toast-star-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #FDE047;
    color: #78350F;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 900;
    flex-shrink: 0;
}

.toast-copy {
    display: flex;
    flex-direction: column;
    font-size: 0.75rem;
}

.toast-copy strong {
    font-family: var(--font-display);
    font-size: 0.84rem;
}

/* ==========================================================================
   LOCKED TREASURE VAULT MODAL
   ========================================================================== */
.clay-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 120;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.clay-locked-vault-sheet {
    position: relative;
    width: 100%;
    max-width: 540px;
    max-height: 88vh;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border-radius: 32px 32px 0 0;
    border-top: 3.5px solid #FFFFFF;
    padding: 0.85rem 1.15rem 1.35rem;
    box-shadow: 0 -12px 36px rgba(15, 23, 42, 0.32);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sheet-grab-bar {
    width: 46px;
    height: 5px;
    border-radius: 9999px;
    background: #CBD5E1;
    margin: 0 auto 0.65rem;
    flex-shrink: 0;
}

/* Locked Vault Sheet */
.clay-locked-vault-sheet {
    align-items: center;
    text-align: center;
}

.vault-emblem-pod {
    width: 68px;
    height: 68px;
    margin-bottom: 0.5rem;
}

.vault-lock-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 6px 12px rgba(217, 119, 6, 0.3));
}

.vault-status-pill {
    padding: 0.22rem 0.7rem;
    border-radius: 9999px;
    background: #FEF3C7;
    color: #B45309;
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
    margin-bottom: 0.4rem;
}

.vault-sheet-title {
    margin: 0 0 0.35rem;
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 800;
    color: #1E1B4B;
}

.vault-sheet-desc {
    margin: 0 0 0.9rem;
    font-family: var(--font-body);
    font-size: 0.84rem;
    font-weight: 700;
    color: #475569;
    line-height: 1.45;
}

.vault-highlight {
    color: #B45309;
    background: #FEF3C7;
    padding: 0.1rem 0.38rem;
    border-radius: 6px;
}

.vault-progress-box {
    width: 100%;
    background: #F1F5F9;
    border: 2px solid #CBD5E1;
    border-radius: 18px;
    padding: 0.75rem 0.9rem;
    margin-bottom: 1rem;
}

.vault-progress-labels {
    display: flex;
    justify-content: space-between;
    font-family: var(--font-display);
    font-size: 0.68rem;
    color: #475569;
    margin-bottom: 0.4rem;
}

.vault-progress-track {
    width: 100%;
    height: 12px;
    border-radius: 9999px;
    background: #CBD5E1;
    overflow: hidden;
}

.vault-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #F59E0B 0%, #10B981 100%);
    border-radius: 9999px;
}

/* ==========================================================================
   INTERACTIVE STORY GLOSSARY & FULL-PAGE READ-ALOUD NARRATION STYLES
   ========================================================================== */
.clay-open-full-reader-banner {
    width: 100%;
    margin-bottom: 0.75rem;
    padding: 0.68rem 0.95rem;
    border-radius: 16px;
    border: 2.5px solid #4F46E5;
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    color: #312E81;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 4px 0 #4F46E5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    transition: transform 0.15s ease;
}

.clay-open-full-reader-banner:hover {
    transform: translateY(-2px);
}

.full-reader-arrow {
    padding: 0.2rem 0.58rem;
    border-radius: 999px;
    background: #4F46E5;
    color: #FFFFFF;
    font-size: 0.68rem;
}

.story-block-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 0.35rem;
}

.story-interactive-hint-pill {
    padding: 0.16rem 0.55rem;
    border-radius: 9999px;
    background: #FEF3C7;
    border: 1.5px solid #F59E0B;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    color: #92400E;
}

.clay-page-narration-box {
    margin: 0.45rem 0 0.7rem;
    padding: 0.65rem 0.8rem;
    border-radius: 16px;
    background: linear-gradient(135deg, #EFF6FF 0%, #E0E7FF 100%);
    border: 2px solid #93C5FD;
    box-shadow:
        0 4px 0 #BFDBFE,
        0 8px 16px rgba(37, 99, 235, 0.12);
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.narration-box-head {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.narration-head-icon {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    background: linear-gradient(180deg, #3B82F6 0%, #1D4ED8 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    box-shadow: 0 3px 0 #1E3A8A;
    flex-shrink: 0;
}

.narration-head-copy {
    display: flex;
    flex-direction: column;
}

.narration-head-copy strong {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: #1E3A8A;
}

.narration-head-copy span {
    font-family: var(--font-body);
    font-size: 0.68rem;
    font-weight: 700;
    color: #3B82F6;
}

.narration-audio-element {
    width: 100%;
    height: 36px;
    border-radius: 999px;
}

.clay-student-vocab-meaning-card {
    position: sticky;
    top: 0;
    z-index: 15;
    margin-bottom: 0.75rem;
    padding: 0.75rem 0.9rem;
    border-radius: 18px;
    background: linear-gradient(180deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 2.5px solid #F59E0B;
    box-shadow:
        0 5px 0 #B45309,
        0 12px 24px rgba(180, 83, 9, 0.22);
}

.vocab-meaning-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
}

.vocab-meaning-kicker {
    display: block;
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #B45309;
}

.vocab-meaning-title-row {
    display: flex;
    align-items: baseline;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.vocab-meaning-word {
    font-family: var(--font-display);
    font-size: 1.02rem;
    font-weight: 900;
    color: #78350F;
}

.vocab-meaning-pron {
    font-family: var(--font-body);
    font-size: 0.74rem;
    font-weight: 800;
    color: #92400E;
}

.vocab-meaning-close-btn {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 1.5px solid #F59E0B;
    background: #FFFFFF;
    color: #92400E;
    font-size: 0.7rem;
    font-weight: 900;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.vocab-meaning-definition {
    margin: 0.3rem 0 0;
    font-family: var(--font-body);
    font-size: 0.84rem;
    font-weight: 800;
    color: #451A03;
    line-height: 1.45;
}

.story-block-paragraph :deep(.vocab-gloss-token) {
    background: linear-gradient(180deg, #FEF9C3 0%, #FDE047 100%);
    color: #78350F;
    font-weight: 900;
    padding: 0.06rem 0.38rem;
    border-radius: 8px;
    border-bottom: 3px solid #D97706;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.story-block-paragraph :deep(.vocab-gloss-token:hover),
.story-block-paragraph :deep(.vocab-gloss-token:focus) {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.3);
}

.clay-story-vocab-chips-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.38rem;
    margin-top: 0.65rem;
    padding-top: 0.55rem;
    border-top: 1.5px dashed #CBD5E1;
}

.vocab-chips-label {
    font-family: var(--font-display);
    font-size: 0.65rem;
    font-weight: 800;
    color: #64748B;
}

.clay-vocab-word-chip {
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    border: 1.5px solid #F59E0B;
    background: #FFFBEB;
    color: #92400E;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 2px 0 #D97706;
    transition: transform 0.15s ease;
}

.clay-vocab-word-chip:hover,
.clay-vocab-word-chip.is-active {
    background: #FDE047;
    color: #78350F;
    transform: translateY(-1px);
}

/* Transitions */
.clay-sheet-pop-enter-active,
.clay-sheet-pop-leave-active {
    transition: opacity 0.25s ease, transform 0.28s var(--spring-bounce);
}

.clay-sheet-pop-enter-from,
.clay-sheet-pop-leave-to {
    opacity: 0;
    transform: translateY(36px);
}

.toast-pop-enter-active,
.toast-pop-leave-active {
    transition: all 0.28s var(--spring-bounce);
}

.toast-pop-enter-from,
.toast-pop-leave-to {
    opacity: 0;
    transform: translate(-50%, -20px) scale(0.9);
}

/* ==========================================================================
   TABLET & DESKTOP ENHANCEMENTS (>= 768px & >= 1024px)
   ========================================================================== */
@media (min-width: 768px) {
    .s-curve-trail-stage {
        max-width: 660px;
    }

    .island-milestone-row {
        padding: 0 1.5rem;
        gap: 1.1rem;
    }

    .clay-quest-callout-card {
        max-width: 340px;
        padding: 0.95rem 1.1rem;
    }

    .curve-bend-diorama {
        top: -36px;
        width: 84px;
        height: 84px;
    }

    .diorama-on-right {
        right: 20px;
    }

    .diorama-on-left {
        left: 20px;
    }

    .callout-quest-title {
        font-size: 1rem;
    }

    .clay-modal-backdrop {
        align-items: center;
        padding: 1.5rem;
    }

    .clay-locked-vault-sheet {
        border-radius: 32px;
        border: 3.5px solid #FFFFFF;
    }
}

@media (min-width: 1024px) {
    .curve-bend-diorama {
        display: none;
    }

    .desktop-storybook-wings {
        display: block;
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 2;
    }

    .wing-column {
        position: absolute;
        top: 60px;
        bottom: 80px;
        width: 210px;
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        align-items: center;
    }

    .wing-left { left: 12px; }
    .wing-right { right: 12px; }

    .wing-prop-card {
        width: 100%;
    }

    .wing-art-img {
        width: 100%;
        height: auto;
        object-fit: contain;
        filter: drop-shadow(0 12px 20px rgba(14, 116, 144, 0.22));
    }

    .wing-float-slow { animation: dioramaFloat 5.5s ease-in-out infinite alternate; }
    .wing-float-mid { animation: dioramaFloat 4.5s ease-in-out infinite alternate-reverse; }
    .wing-float-fast { animation: dioramaFloat 3.8s ease-in-out infinite alternate; }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

