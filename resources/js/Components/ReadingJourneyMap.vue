<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    initialStep: {
        type: Number,
        default: 1,
    },
    module: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Conflict and Community Values',
            slug: 'conflict-and-community-values',
            icon: '📘',
            theme: 'forest',
            grade_level: 'Grade 3 • Reading Legends',
            xp_bonus: 250,
        }),
    },
    lectures: {
        type: Array,
        default: () => [],
    },
    moduleTitle: {
        type: String,
        default: null,
    },
    moduleGrade: {
        type: String,
        default: null,
    },
    totalXp: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['step-selected', 'step-completed', 'journey-completed']);

const displayTitle = computed(() => props.moduleTitle || props.module?.title || 'Conflict and Community Values');
const displayGrade = computed(() => props.moduleGrade || props.module?.grade_level || 'Grade 3 • Reading Legends');

// Theme palette pool for cyclical milestone styling
const themePool = [
    { theme: 'lush-green', name: 'Lush Green Island & Volcano Foothills', icon: '🌴', badge: 'Explorer I' },
    { theme: 'wooden-bridge', name: 'Wooden Arched Bridge Over Azure Water', icon: '🌉', badge: 'Pathfinder' },
    { theme: 'explorer-cave', name: 'Island Cove & Explorer Cave', icon: '🧭', badge: 'Wayfarer' },
    { theme: 'sandy-lagoon', name: 'Golden Sandy Shore & Tropical Coral Reef', icon: '📖', badge: 'Story Seeker' },
    { theme: 'cliffside-trail', name: 'Emerald Cliffside Trail & Rope Bridges', icon: '🗺️', badge: 'Cartographer' },
    { theme: 'highland-peak', name: 'Highland Pine Peak & Sky Observatory', icon: '🏔️', badge: 'Summit Scholar' },
];

// Dynamically map lectures from the database
const steps = computed(() => {
    if (!props.lectures || props.lectures.length === 0) {
        return [];
    }

    return props.lectures.map((lec, idx) => {
        const stepNum = idx + 1;
        const t = themePool[idx % themePool.length];
        const isLeft = stepNum % 2 === 1;
        const rawBody = lec.body || '';
        const strippedBody = rawBody.replace(/<[^>]*>/g, '').trim();

        return {
            id: stepNum,
            dbId: lec.id,
            slug: lec.slug,
            title: lec.title,
            subtitle: lec.description ? (lec.description.length > 45 ? lec.description.slice(0, 45) + '...' : lec.description) : `Reading Quest ${stepNum}`,
            theme: t.theme,
            themeName: t.name,
            icon: t.icon,
            badge: t.badge,
            xp: Number(lec.xp_reward) || 100,
            summary: lec.description || (strippedBody ? strippedBody.slice(0, 140) + '...' : 'Explore reading concepts, multimedia story blocks, and questions.'),
            body: lec.body || null,
            contentsCount: Number(lec.contents_count) || lec.contents?.length || 0,
            contents: lec.contents || [],
            is_published: lec.is_published ?? true,
            position: isLeft ? 'left' : 'right',
            cx: isLeft ? 170 : 510,
            cy: 220 + idx * 260,
        };
    });
});

const totalLecturesCount = computed(() => steps.value.length);

// Dynamic trail stage height based on how many lectures exist
const trailStageHeight = computed(() => {
    const list = steps.value;
    if (!list || list.length === 0) return 1200;
    const last = list[list.length - 1];
    return last.cy + 640;
});

// Dynamic S-Curve Path connecting Start (340,90) -> Step 1 -> ... -> Finale Chest
const pathTotalD = computed(() => {
    const list = steps.value;
    if (!list || list.length === 0) return '';
    const start = 'M 340,90 C 340,140 170,160 170,220';
    if (list.length === 1) {
        return `${start} C 170,350 340,400 340,640`;
    }
    let d = start;
    for (let i = 0; i < list.length - 1; i++) {
        const curr = list[i];
        const next = list[i + 1];
        d += ` C ${curr.cx},${curr.cy + 130} ${next.cx},${next.cy - 130} ${next.cx},${next.cy}`;
    }
    const last = list[list.length - 1];
    const chestY = last.cy + 420;
    d += ` C ${last.cx},${last.cy + 200} 340,${last.cy + 240} 340,${chestY}`;
    return d;
});

// Dynamic Illuminated Path Segments for granular progress lighting
const segments = computed(() => {
    const list = steps.value;
    const segs = [];
    if (!list || list.length === 0) return segs;

    // Segment 0: Start to Node 1
    segs.push({
        id: 0,
        d: 'M 340,90 C 340,140 170,160 170,220',
        isIlluminated: currentStep.value >= 1,
        color: '#06D6A0',
        isChestBeam: false,
    });

    for (let i = 0; i < list.length - 1; i++) {
        const curr = list[i];
        const next = list[i + 1];
        const stepNum = i + 1;
        segs.push({
            id: stepNum,
            d: `M ${curr.cx},${curr.cy} C ${curr.cx},${curr.cy + 130} ${next.cx},${next.cy - 130} ${next.cx},${next.cy}`,
            isIlluminated: currentStep.value > stepNum,
            color: stepNum % 3 === 1 ? '#06D6A0' : (stepNum % 3 === 2 ? '#2395EB' : '#FF6B4A'),
            isChestBeam: false,
        });
    }

    const last = list[list.length - 1];
    const chestY = last.cy + 420;
    segs.push({
        id: list.length,
        d: `M ${last.cx},${last.cy} C ${last.cx},${last.cy + 200} 340,${last.cy + 240} 340,${chestY}`,
        isIlluminated: currentStep.value > list.length,
        color: '#FFC000',
        isChestBeam: true,
    });

    return segs;
});

// Current player step: 1 through totalLecturesCount + 1 (unlocks treasure)
const currentStep = ref(props.initialStep);
const selectedStep = ref(null);
const showModal = ref(false);
const showConfetti = ref(false);
const chestOpened = ref(false);
const isCelebrationVisible = ref(false);

// Total adventure XP
const computedTotalXp = computed(() => {
    if (props.totalXp) return props.totalXp;
    const sumLectures = steps.value.reduce((acc, s) => acc + (s.xp || 100), 0);
    return (props.module?.xp_bonus || 0) + sumLectures;
});

// Dynamic progress percentage based on actual lecture count
const progressPercentage = computed(() => {
    const total = totalLecturesCount.value;
    if (total === 0) return 0;
    if (currentStep.value > total) return 100;
    const completedCount = currentStep.value - 1;
    return Math.round((completedCount / total) * 100);
});

const isStepCompleted = (stepId) => stepId < currentStep.value;
const isStepActive = (stepId) => stepId === currentStep.value;
const isStepLocked = (stepId) => stepId > currentStep.value;

const earnedXpComputed = computed(() => {
    return steps.value
        .filter((s) => isStepCompleted(s.id))
        .reduce((sum, s) => sum + s.xp, 0);
});

// Selection & Navigation
const handleSelectStep = (step) => {
    if (isStepLocked(step.id)) {
        triggerLockShake(step.id);
        return;
    }
    selectedStep.value = step;
    showModal.value = true;
    emit('step-selected', step);
};

const triggerLockShake = (stepId) => {
    const el = document.getElementById(`node-button-${stepId}`);
    if (el) {
        el.classList.add('shake-locked');
        setTimeout(() => el.classList.remove('shake-locked'), 500);
    }
};

const advanceToNextStep = () => {
    if (currentStep.value <= totalLecturesCount.value) {
        const justFinished = steps.value.find((s) => s.id === currentStep.value);
        if (justFinished) {
            emit('step-completed', justFinished);
        }
        currentStep.value += 1;
        if (currentStep.value <= totalLecturesCount.value) {
            selectedStep.value = steps.value[currentStep.value - 1];
        } else {
            triggerFinaleCelebration();
        }
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value -= 1;
        selectedStep.value = steps.value[currentStep.value - 1];
        chestOpened.value = false;
        showConfetti.value = false;
    }
};

const resetJourney = () => {
    currentStep.value = 1;
    selectedStep.value = steps.value[0] || null;
    chestOpened.value = false;
    showConfetti.value = false;
    isCelebrationVisible.value = false;
};

const triggerFinaleCelebration = () => {
    chestOpened.value = true;
    showConfetti.value = true;
    isCelebrationVisible.value = true;
    emit('journey-completed');
    setTimeout(() => {
        showConfetti.value = false;
    }, 7000);
};

const toggleChest = () => {
    if (totalLecturesCount.value === 0) return;
    if (currentStep.value > totalLecturesCount.value) {
        chestOpened.value = !chestOpened.value;
        if (chestOpened.value) {
            showConfetti.value = true;
            setTimeout(() => {
                showConfetti.value = false;
            }, 5000);
        }
    } else {
        const remaining = (totalLecturesCount.value + 1) - currentStep.value;
        alert(`🔒 The ancient treasure chest remains locked! Complete ${remaining} more reading milestone${remaining > 1 ? 's' : ''} to unlock the island treasure vault!`);
    }
};

const goToCreateLecture = () => {
    if (props.module?.slug) {
        router.visit(`/teachers/modules/${props.module.slug}/lectures/create`);
    }
};

const goToManageContent = (step) => {
    if (props.module?.slug && step?.slug) {
        router.visit(`/teachers/modules/${props.module.slug}/lectures/${step.slug}/contents/create`);
    }
};

onMounted(() => {
    if (steps.value.length > 0) {
        selectedStep.value = steps.value[0];
    }
    if (totalLecturesCount.value > 0 && currentStep.value > totalLecturesCount.value) {
        chestOpened.value = true;
    }
});
</script>

<template>
    <div class="reading-journey-wrapper">
        <!-- Embedded Google Display Fonts -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Neumorphic Ambient Backdrop Background -->
        <div class="ocean-backdrop">
            <!-- Animated Ambient Wave Ripples -->
            <div class="wave wave-1"></div>
            <div class="wave wave-2"></div>
            <div class="wave wave-3"></div>

            <!-- Soft Neumorphic Glow Discs -->
            <div class="soft-glow glow-top"></div>
            <div class="soft-glow glow-bottom"></div>
        </div>

        <!-- 3D Pop-Up Storybook & Character Side Flanking Scenery Wings -->
        <aside class="flank-wing flank-left" aria-hidden="true">
            <div class="flank-asset-wrap asset-book-left">
                <img
                    src="/images/book_left_trees_mountains.png"
                    alt="3D Pop-Up Book with Nature, Trees, and Mountains"
                    class="flank-img"
                />
            </div>
            <div class="flank-asset-wrap asset-boy-laptop">
                <img
                    src="/images/boy_left_on_book_laptop.png"
                    alt="3D Cartoon Boy Student on Book with Laptop"
                    class="flank-img"
                />
            </div>
            <div class="flank-asset-wrap asset-girl-reading">
                <img
                    src="/images/girl_left_reading_book.png"
                    alt="3D Cartoon Girl Student Reading Purple Book"
                    class="flank-img"
                />
            </div>
        </aside>

        <aside class="flank-wing flank-right" aria-hidden="true">
            <div class="flank-asset-wrap asset-book-right">
                <img
                    src="/images/book_right_mountains_river.png"
                    alt="3D Pop-Up Island Storybook with Mountains and River"
                    class="flank-img"
                />
            </div>
            <div class="flank-asset-wrap asset-book-laptop-girl">
                <img
                    src="/images/book_with_message_and_laptop_girl.png"
                    alt="3D Open Book with Speech Bubble and Girl on Laptop"
                    class="flank-img"
                />
            </div>
            <div class="flank-asset-wrap asset-girl-tablet">
                <img
                    src="/images/girl_right_using_tablet.png"
                    alt="3D Cartoon Girl Studying on Tablet"
                    class="flank-img"
                />
            </div>
        </aside>

        <!-- Confetti Burst Container -->
        <div v-if="showConfetti" class="confetti-sky" aria-hidden="true">
            <span
                v-for="i in 42"
                :key="i"
                class="confetti-piece"
                :style="{
                    '--delay': `${(i % 12) * 0.12}s`,
                    '--x': `${(i * 2.38) % 100}%`,
                    '--rot': `${(i * 47) % 360}deg`,
                    '--color': ['#FFB703', '#FF6B4A', '#06D6A0', '#2395EB', '#845EC2', '#EC4899', '#FACC15'][i % 7],
                    '--size': `${8 + (i % 6) * 3}px`
                }"
            ></span>
        </div>

        <!-- Sticky Floating Game HUD & Controls Top Bar -->
        <header class="hud-top-bar">
            <div class="hud-capsule">
                <div class="hud-left">
                    <span class="hud-badge-icon">🗺️</span>
                    <div class="hud-meta">
                        <span class="hud-kicker">ADVENTURE MAP</span>
                        <h2 class="hud-module-name">{{ displayTitle }}</h2>
                    </div>
                </div>

                <div class="hud-center-controls">
                    <!-- + Create Lecture Button -->
                    <!-- <button
                        v-if="module?.slug"
                        type="button"
                        class="btn-hud-create-lecture"
                        @click="goToCreateLecture"
                        title="Add a new lecture milestone to this module"
                    >
                        <span>✨ + CREATE LECTURE</span>
                    </button> -->
                </div>

                <div class="hud-right">
                    <!-- XP Counter Pill -->
                    <div class="stat-pill xp-pill" title="Earned Adventure Experience">
                        <span class="pill-spark">⚡</span>
                        <span class="pill-number">{{ earnedXpComputed }} / {{ computedTotalXp }} XP</span>
                    </div>

                    <!-- Progress Percentage Pill -->
                    <div class="stat-pill progress-pill">
                        <span class="pill-spark">🎯</span>
                        <span class="pill-number">{{ progressPercentage }}%</span>
                    </div>
                </div>
            </div>

            <!-- Top Interactive Micro-Control Toolbar -->
            <div v-if="totalLecturesCount > 0" class="hud-action-row">
                <button
                    type="button"
                    class="btn-hud-control btn-hud-prev"
                    :disabled="currentStep <= 1"
                    @click="previousStep"
                    title="Go back to previous step"
                >
                    <span class="btn-arrow">❮</span>
                    <span>Previous</span>
                </button>

                <div class="hud-stepper-track">
                    <div
                        class="hud-stepper-fill"
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>

                <button
                    v-if="currentStep <= totalLecturesCount"
                    type="button"
                    class="btn-hud-control btn-hud-next"
                    @click="advanceToNextStep"
                    title="Mark active step complete and advance"
                >
                    <span>Complete Step</span>
                    <span class="btn-arrow">➔</span>
                </button>

                <button
                    v-else
                    type="button"
                    class="btn-hud-control btn-hud-reset"
                    @click="resetJourney"
                    title="Start journey over"
                >
                    <span>🔄 Reset Map</span>
                </button>
            </div>
            <div v-else class="hud-action-row empty-action-row">
                <span class="empty-hud-text">🏝️ No lectures added yet — click "+ CREATE LECTURE" to begin!</span>
            </div>
        </header>

        <!-- MAIN VERTICAL ADVENTURE CONTAINER (Max Width 680px) -->
        <main class="journey-main-container">

            <!-- 1. HEADER BANNER SECTION -->
            <section class="adventure-hero-banner">
                <!-- Floating Decorative 3D Badges around Header -->
                <div class="floating-badge badge-cloud-left" aria-hidden="true">
                    <svg viewBox="0 0 64 40" class="svg-3d-cloud">
                        <path d="M16 32h32a12 12 0 0 0 0-24 16 16 0 0 0-31-4A10 10 0 0 0 16 32z" fill="#FFFFFF" filter="url(#cloudShadow)" />
                        <ellipse cx="28" cy="18" rx="8" ry="4" fill="rgba(255,255,255,0.8)" />
                    </svg>
                </div>

                <div class="floating-badge badge-sparkle-top" aria-hidden="true">
                    <div class="badge-gem">⭐</div>
                </div>

                <div class="floating-badge badge-lightbulb-right" aria-hidden="true">
                    <div class="badge-coin">💡</div>
                </div>

                <div class="floating-badge badge-pencil-left" aria-hidden="true">
                    <div class="badge-item">✏️</div>
                </div>

                <div class="floating-badge badge-gear-right" aria-hidden="true">
                    <div class="badge-gear-spin">⚙️</div>
                </div>

                <div class="floating-badge badge-bubble-top" aria-hidden="true">
                    <div class="badge-speech">💬</div>
                </div>

                <!-- Hero Content -->
                <div class="hero-text-block">
                    <div class="banner-pill">
                        <span class="pulsing-dot"></span>
                        <span v-if="totalLecturesCount > 0">{{ displayGrade.toUpperCase() }} • {{ totalLecturesCount }} LECTURE{{ totalLecturesCount === 1 ? '' : 'S' }}</span>
                        <span v-else>{{ displayGrade.toUpperCase() }} • UNCHARTED REALM</span>
                    </div>

                    <h1 class="hero-chunky-title">
                        Let's start your reading journey!
                    </h1>

                    <p class="hero-description">
                        Weave through mythical archipelagos, explore each reading milestone, and unlock the legendary Golden Pirate Chest!
                    </p>
                </div>
            </section>

            <!-- EMPTY STATE: WHEN NO LECTURES IN DATABASE -->
            <div v-if="totalLecturesCount === 0" class="empty-journey-container">
                <div class="empty-island-card">
                    <div class="empty-island-badge">🏝️</div>
                    <span class="empty-kicker">UNCHARTED READING REALM</span>
                    <h2 class="empty-title">No Lectures Added Yet</h2>
                    <p class="empty-desc">
                        This module has no reading lectures yet. Click the button below to summon your first lecture milestone and start charting the adventure map!
                    </p>
                    <button
                        type="button"
                        class="btn-3d-create-first-lecture"
                        @click="goToCreateLecture"
                    >
                        <span>✨ + CREATE FIRST LECTURE</span>
                    </button>
                </div>
            </div>

            <!-- 2. S-CURVE INTERACTIVE ADVENTURE MAP (ONLY WHEN LECTURES EXIST) -->
            <div
                v-else
                class="trail-stage"
                :style="{ height: `${trailStageHeight}px` }"
            >

                <!-- Continuous Vertical Cubic-Bezier SVG Trail Overlay -->
                <svg
                    class="svg-trail-canvas"
                    :viewBox="`0 0 680 ${trailStageHeight}`"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    preserveAspectRatio="xMidYMid meet"
                >
                    <defs>
                        <!-- Drop Shadow Filter for Trail -->
                        <filter id="pathShadow" x="-10%" y="-10%" width="120%" height="120%">
                            <feDropShadow dx="0" dy="8" stdDeviation="6" flood-color="#0284c7" flood-opacity="0.18" />
                        </filter>

                        <!-- Glowing Active Trail Filter -->
                        <filter id="glowLight" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="5" result="blur" />
                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                        </filter>
                    </defs>

                    <!-- Base Trail Underbed (Sandy Embankment) -->
                    <path
                        :d="pathTotalD"
                        stroke="#bae6fd"
                        stroke-width="26"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        filter="url(#pathShadow)"
                    />

                    <!-- Trail Road Surface (Warm Sand Stepping Base) -->
                    <path
                        :d="pathTotalD"
                        stroke="#fef3c7"
                        stroke-width="18"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    <!-- Trail Stepping Stones (Dashed Trail Line) -->
                    <path
                        :d="pathTotalD"
                        stroke="#f59e0b"
                        stroke-width="6"
                        stroke-linecap="round"
                        stroke-dasharray="8 18"
                        class="stepping-dash-trail"
                    />

                    <!-- Dynamic Illuminated Path Segments -->
                    <path
                        v-for="seg in segments"
                        :key="seg.id"
                        :d="seg.d"
                        :stroke="seg.isIlluminated ? seg.color : 'transparent'"
                        :stroke-width="seg.isChestBeam ? 14 : 12"
                        stroke-linecap="round"
                        class="illuminated-path"
                        :class="{ 'chest-beam': seg.isChestBeam }"
                        :filter="seg.isChestBeam && seg.isIlluminated ? 'url(#glowLight)' : null"
                    />
                </svg>

                <!-- START MARKER BADGE (Anchored above Node 1 at x=340, y=55) -->
                <div class="start-marker-pod">
                    <div class="neumorphic-start-pill">
                        <span class="start-flag">🚩</span>
                        <span class="start-text">START ADVENTURE</span>
                        <span class="start-dot"></span>
                    </div>
                    <div class="start-anchor-line"></div>
                </div>

                <!-- DYNAMIC INTERACTIVE MILESTONE NODES (Alternating Left & Right) -->
                <div class="nodes-container">
                    <div
                        v-for="step in steps"
                        :key="step.id"
                        class="step-cluster"
                        :class="[
                            `cluster-${step.position}`,
                            `theme-${step.theme}`,
                            {
                                'is-completed': isStepCompleted(step.id),
                                'is-active': isStepActive(step.id),
                                'is-locked': isStepLocked(step.id),
                                'is-selected': selectedStep?.id === step.id
                            }
                        ]"
                        :style="{ top: `${step.cy - 75}px` }"
                    >
                        <!-- Left or Right Sub-cluster Anchor: Terrain Pad + 3D Button -->
                        <div class="node-anchor-wrap">
                            <!-- Miniature 3D Island / Terrain Pad -->
                            <div class="terrain-island" :class="`terrain-${step.theme}`">
                                <div class="terrain-surface"></div>
                                <div class="terrain-shadow"></div>
                                <div class="terrain-accent" aria-hidden="true">
                                    <span v-if="step.theme === 'lush-green'">🌴</span>
                                    <span v-else-if="step.theme === 'wooden-bridge'">🪵</span>
                                    <span v-else-if="step.theme === 'explorer-cave'">🪨</span>
                                    <span v-else-if="step.theme === 'sandy-lagoon'">🐚</span>
                                    <span v-else-if="step.theme === 'cliffside-trail'">🌿</span>
                                    <span v-else-if="step.theme === 'highland-peak'">🌲</span>
                                </div>
                            </div>

                            <!-- Chunky 3D Cartoon Milestone Button (1-N) -->
                            <button
                                :id="`node-button-${step.id}`"
                                type="button"
                                class="chunky-3d-button"
                                :class="{
                                    'btn-completed': isStepCompleted(step.id),
                                    'btn-active': isStepActive(step.id),
                                    'btn-locked': isStepLocked(step.id)
                                }"
                                :aria-label="`Step ${step.id}: ${step.title}`"
                                @click="handleSelectStep(step)"
                            >
                                <span class="btn-gloss"></span>

                                <div class="btn-core">
                                    <span v-if="isStepCompleted(step.id)" class="badge-icon-check">✓</span>
                                    <span v-else-if="isStepActive(step.id)" class="badge-num-active">{{ step.id }}</span>
                                    <span v-else class="badge-icon-lock">🔒</span>
                                </div>

                                <span v-if="isStepActive(step.id)" class="active-pulse-aura"></span>
                            </button>

                            <!-- Current Step Pointer Avatar Beacon -->
                            <div v-if="isStepActive(step.id)" class="active-avatar-beacon">
                                <span class="beacon-arrow">▼</span>
                                <span class="beacon-chip">YOU ARE HERE</span>
                            </div>
                        </div>

                        <!-- Directional Neumorphic Speech Bubble Callout Card -->
                        <div
                            class="speech-bubble-card"
                            :class="[
                                `pointer-${step.position === 'left' ? 'left' : 'right'}`,
                                { 'elevated': isStepActive(step.id) }
                            ]"
                            @click="handleSelectStep(step)"
                        >
                            <div class="speech-pointer-triangle"></div>

                            <div class="bubble-header">
                                <span class="step-kicker-pill" :class="`pill-${step.theme}`">
                                    {{ step.icon }} LECTURE {{ step.id }}
                                </span>
                                <span class="xp-badge">+{{ step.xp }} XP</span>
                            </div>

                            <h3 class="bubble-title">{{ step.title }}</h3>

                            <p class="bubble-summary">{{ step.summary }}</p>

                            <div class="bubble-footer">
                                <div class="time-meta">
                                    <span v-if="step.contentsCount > 0" class="contents-count-tag">
                                        📁 {{ step.contentsCount }} {{ step.contentsCount === 1 ? 'Block' : 'Blocks' }}
                                    </span>
                                    <span v-else class="contents-count-tag empty-contents-tag">
                                        📖 Milestone
                                    </span>
                                </div>

                                <div class="action-tag">
                                    <span v-if="isStepCompleted(step.id)" class="tag-status tag-completed">
                                        Completed ✨
                                    </span>
                                    <span v-else-if="isStepActive(step.id)" class="tag-status tag-active">
                                        Active Quest 🔥
                                    </span>
                                    <span v-else class="tag-status tag-locked">
                                        Locked
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FINALE NODE (Bottom Destination: Treasure Island) -->
                <section class="finale-destination-pod">
                    <div class="celebration-ribbon-wrap">
                        <div class="curved-ribbon">
                            <span class="ribbon-tail left-tail"></span>
                            <div class="ribbon-content">
                                <span class="ribbon-star">⭐</span>
                                <span class="ribbon-text">CONGRATULATIONS!</span>
                                <span class="ribbon-star">⭐</span>
                            </div>
                            <span class="ribbon-tail right-tail"></span>
                        </div>
                        <p class="ribbon-sub">Final Destination • The Island Treasure Vault</p>
                    </div>

                    <!-- 3D Cartoon Interactive Treasure Chest -->
                    <div
                        class="treasure-chest-stage"
                        :class="{ 'is-unlocked': currentStep > totalLecturesCount, 'is-open': chestOpened }"
                        @click="toggleChest"
                    >
                        <div class="golden-sunburst" :class="{ 'spin-active': chestOpened }"></div>

                        <div class="chest-box">
                            <div class="chest-lid">
                                <div class="lid-wood">
                                    <div class="lid-iron-band band-1"></div>
                                    <div class="lid-iron-band band-2"></div>
                                    <div class="lid-iron-band band-3"></div>
                                    <div class="lid-handle"></div>
                                </div>
                            </div>

                            <div class="chest-booty" :class="{ 'visible': chestOpened }">
                                <div class="booty-sparkles">✨</div>
                                <div class="coins-pile">
                                    <span class="coin coin-1">🟡</span>
                                    <span class="coin coin-2">🪙</span>
                                    <span class="coin coin-3">🟡</span>
                                    <span class="gem gem-ruby">💎</span>
                                    <span class="gem gem-emerald">💚</span>
                                    <span class="gem gem-sapphire">🔷</span>
                                    <span class="coin coin-4">🪙</span>
                                </div>
                            </div>

                            <div class="chest-body">
                                <div class="body-iron-band band-1"></div>
                                <div class="body-iron-band band-2"></div>
                                <div class="body-iron-band band-3"></div>

                                <div class="chest-lock" :class="{ 'unlocked': chestOpened }">
                                    <span v-if="!chestOpened" class="lock-icon">🔒</span>
                                    <span v-else class="lock-icon key-icon">🗝️</span>
                                </div>
                            </div>
                        </div>

                        <div class="chest-action-pill">
                            <span v-if="currentStep <= totalLecturesCount">🔒 Complete all {{ totalLecturesCount }} Lectures to Unlock</span>
                            <span v-else-if="!chestOpened">✨ Click to Open Treasure! ✨</span>
                            <span v-else>🎉 Quest Complete! You're a Master Reader! 🎉</span>
                        </div>
                    </div>
                </section>

            </div>
        </main>

        <!-- STEP DETAIL MODAL / DRAWER -->
        <transition name="modal-bounce">
            <div
                v-if="showModal && selectedStep"
                class="modal-overlay"
                @click.self="showModal = false"
            >
                <div class="modal-card">
                    <button
                        type="button"
                        class="modal-close-btn"
                        @click="showModal = false"
                        aria-label="Close dialog"
                    >
                        ✕
                    </button>

                    <div class="modal-header">
                        <span class="modal-step-badge">{{ selectedStep.icon }} Lecture {{ selectedStep.id }}</span>
                        <span class="modal-theme-tag">{{ selectedStep.themeName }}</span>
                    </div>

                    <h2 class="modal-title">{{ selectedStep.title }}</h2>
                    <h4 class="modal-subtitle">{{ selectedStep.subtitle }}</h4>

                    <div class="modal-meta-row">
                        <div class="meta-item">
                            <span class="meta-icon">⚡</span>
                            <div>
                                <span class="meta-label">REWARD</span>
                                <span class="meta-value">+{{ selectedStep.xp }} XP</span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon">📁</span>
                            <div>
                                <span class="meta-label">CONTENTS</span>
                                <span class="meta-value">{{ selectedStep.contentsCount }} Blocks</span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon">🎯</span>
                            <div>
                                <span class="meta-label">STATUS</span>
                                <span class="meta-value">{{ isStepCompleted(selectedStep.id) ? 'Completed' : (isStepActive(selectedStep.id) ? 'Active' : 'Locked') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body-section">
                        <h4>Lecture Overview</h4>
                        <p>{{ selectedStep.summary }}</p>
                    </div>

                    <!-- Manage Multimedia Content Action Button -->
                    <div v-if="module?.slug && selectedStep?.slug" class="modal-manage-row">
                        <button
                            type="button"
                            class="btn-manage-contents"
                            @click="goToManageContent(selectedStep)"
                        >
                            <span>🎨 Manage Lecture Content Blocks ({{ selectedStep.contentsCount }}) ➔</span>
                        </button>
                    </div>

                    <div class="modal-actions-footer">
                        <button
                            type="button"
                            class="modal-secondary-btn"
                            @click="showModal = false"
                        >
                            Close
                        </button>

                        <button
                            v-if="isStepActive(selectedStep.id)"
                            type="button"
                            class="modal-primary-btn"
                            @click="() => { showModal = false; advanceToNextStep(); }"
                        >
                            <span>Mark as Complete ✓</span>
                        </button>

                        <button
                            v-else-if="isStepCompleted(selectedStep.id)"
                            type="button"
                            class="modal-review-btn"
                            @click="showModal = false"
                        >
                            <span>Replay Milestone ↺</span>
                        </button>

                        <button
                            v-else
                            type="button"
                            class="modal-locked-btn"
                            disabled
                        >
                            <span>🔒 Complete Lecture {{ selectedStep.id - 1 }} First</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- CELEBRATORY BOTTOM DRAWER WHEN CHEST UNLOCKED -->
        <transition name="drawer-slide">
            <div v-if="isCelebrationVisible && currentStep > totalLecturesCount" class="celebration-toast">
                <div class="toast-content">
                    <span class="toast-trophy">🏆</span>
                    <div>
                        <h4 class="toast-title">Reading Expedition Completed!</h4>
                        <p class="toast-p">You have unlocked all {{ totalLecturesCount }} island lectures and earned {{ computedTotalXp }} XP!</p>
                    </div>
                </div>
                <div class="toast-actions">
                    <button type="button" class="btn-toast-claim" @click="isCelebrationVisible = false">
                        Claim Star Bounty ⭐
                    </button>
                </div>
            </div>
        </transition>

    </div>
</template>

<style scoped>
/* ==========================================================================
   CSS CUSTOM PROPERTIES: 3D CARTOON + NEUMORPHIC SOFT-UI TOKENS
   ========================================================================== */
.reading-journey-wrapper {
    --font-heading: 'Fredoka', 'Baloo 2', system-ui, sans-serif;
    --font-body: 'Nunito', system-ui, sans-serif;

    /* Palette */
    --ocean-sky: #e0f2fe;
    --ocean-mid: #bae6fd;
    --ocean-deep: #7dd3fc;
    --brand-navy: #1d2440;
    --brand-blue: #2395eb;
    --brand-coral: #ff6b4a;
    --brand-green: #06d6a0;
    --brand-gold: #ffc000;
    --brand-purple: #845ec2;

    /* Neumorphic Tactile Soft Shadows */
    --shadow-light: rgba(255, 255, 255, 0.95);
    --shadow-dark: rgba(14, 116, 144, 0.16);
    --neu-card-shadow: 10px 10px 24px var(--shadow-dark), -10px -10px 24px var(--shadow-light);

    /* Chunky 3D Button Shadows */
    --btn-coral-lip: 0 6px 0 #c2410c, 0 10px 16px rgba(234, 88, 12, 0.45);
    --btn-green-lip: 0 6px 0 #15803d, 0 10px 16px rgba(34, 197, 94, 0.45);
    --btn-locked-lip: 0 4px 0 #94a3b8, 0 6px 12px rgba(100, 116, 139, 0.2);

    /* Animation Curve */
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    position: relative;
    width: 100%;
    min-height: 100vh;
    font-family: var(--font-body);
    color: var(--brand-navy);
    overflow-x: hidden;
    padding-bottom: 5rem;
    box-sizing: border-box;
}

/* Ocean Backdrop */
.ocean-backdrop {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    background: linear-gradient(180deg, #e0f2fe 0%, #bae6fd 45%, #7dd3fc 100%);
    overflow: hidden;
}

.wave {
    position: absolute;
    width: 200%;
    height: 120px;
    background: radial-gradient(ellipse at 50% 50%, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
    opacity: 0.4;
    border-radius: 40%;
}

.wave-1 {
    top: 15%;
    left: -50%;
    animation: driftWave 18s ease-in-out infinite alternate;
}

.wave-2 {
    top: 55%;
    right: -50%;
    animation: driftWave 22s ease-in-out infinite alternate-reverse;
}

.wave-3 {
    bottom: 5%;
    left: -30%;
    animation: driftWave 26s ease-in-out infinite alternate;
}

@keyframes driftWave {
    0% { transform: translate(0, 0) rotate(0deg); }
    100% { transform: translate(50px, -20px) rotate(3deg); }
}

.soft-glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
}

.glow-top {
    top: -5%;
    left: 20%;
    width: 400px;
    height: 400px;
    background: rgba(254, 240, 138, 0.35);
}

.glow-bottom {
    bottom: 5%;
    right: 15%;
    width: 500px;
    height: 500px;
    background: rgba(251, 113, 133, 0.22);
}

/* 3D Flanking Scenery Wings */
.flank-wing {
    position: absolute;
    top: 0;
    bottom: 0;
    width: calc((100vw - 720px) / 2);
    max-width: 380px;
    min-width: 150px;
    z-index: 2;
    pointer-events: none;
    overflow: visible;
}

.flank-left { left: 2vw; }
.flank-right { right: 2vw; }

.flank-asset-wrap {
    position: absolute;
    width: 90%;
    max-width: 320px;
    filter: drop-shadow(0 14px 28px rgba(14, 116, 144, 0.18));
}

.flank-img {
    width: 100%;
    height: auto;
    display: block;
    user-select: none;
}

.asset-book-left {
    top: 180px;
    left: 0;
    animation: floatBookLeft 7.5s ease-in-out infinite alternate;
}

.asset-boy-laptop {
    top: 860px;
    right: 5%;
    width: 75%;
    max-width: 240px;
    animation: floatBoy 6.8s ease-in-out infinite alternate 0.5s;
}

.asset-girl-reading {
    top: 1560px;
    left: 0;
    width: 95%;
    max-width: 320px;
    animation: floatGirlLeft 7.2s ease-in-out infinite alternate 1s;
}

.asset-book-right {
    top: 380px;
    right: 0;
    animation: floatBookRight 7.8s ease-in-out infinite alternate 0.3s;
}

.asset-book-laptop-girl {
    top: 1080px;
    right: 0;
    width: 92%;
    max-width: 310px;
    animation: floatBookLaptop 7s ease-in-out infinite alternate 0.8s;
}

.asset-girl-tablet {
    top: 1780px;
    right: 0;
    width: 88%;
    max-width: 290px;
    animation: floatGirlRight 6.5s ease-in-out infinite alternate 1.2s;
}

@keyframes floatBookLeft {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-16px) rotate(-1.5deg); }
}

@keyframes floatBoy {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-14px) rotate(1.2deg); }
}

@keyframes floatGirlLeft {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-12px) rotate(-1deg); }
}

@keyframes floatBookRight {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-16px) rotate(1.5deg); }
}

@keyframes floatBookLaptop {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-14px) rotate(-1deg); }
}

@keyframes floatGirlRight {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-12px) rotate(1deg); }
}

/* Confetti */
.confetti-sky {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 99;
    overflow: hidden;
}

.confetti-piece {
    position: absolute;
    top: -20px;
    left: var(--x);
    width: var(--size);
    height: calc(var(--size) * 1.4);
    background-color: var(--color);
    border-radius: 3px;
    opacity: 0.95;
    animation: confettiFall 4.5s ease-out var(--delay) infinite;
    transform: rotate(var(--rot));
}

@keyframes confettiFall {
    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
    100% { transform: translateY(105vh) rotate(720deg); opacity: 0; }
}

/* Sticky HUD Top Bar */
.hud-top-bar {
    position: sticky;
    top: 1rem;
    z-index: 40;
    max-width: 680px;
    margin: 0 auto 1.5rem auto;
    padding: 0 1rem;
}

.hud-capsule {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    border-radius: 26px;
    padding: 0.85rem 1.25rem;
    box-shadow: var(--neu-card-shadow);
    border: 2px solid rgba(255, 255, 255, 0.95);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
}

.hud-left {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.hud-badge-icon {
    font-size: 1.5rem;
    background: #e0f2fe;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
}

.hud-kicker {
    display: block;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #0284c7;
    font-family: var(--font-heading);
}

.hud-module-name {
    margin: 0;
    font-family: var(--font-heading);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--brand-navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 170px;
}

.btn-hud-create-lecture {
    background: linear-gradient(135deg, #2395eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: none;
    border-radius: 9999px;
    padding: 0.45rem 0.9rem;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.76rem;
    letter-spacing: 0.04em;
    box-shadow: 0 3px 0 #1e40af, 0 4px 8px rgba(37, 99, 235, 0.35);
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-hud-create-lecture:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #1e40af, 0 6px 12px rgba(37, 99, 235, 0.45);
}

.btn-hud-create-lecture:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #1e40af;
}

.hud-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.35rem 0.65rem;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.8rem;
}

.xp-pill {
    background: #fef3c7;
    color: #b45309;
    border: 1.5px solid #fde68a;
}

.progress-pill {
    background: #e0f2fe;
    color: #0369a1;
    border: 1.5px solid #bae6fd;
}

/* Micro Action Row */
.hud-action-row {
    margin-top: 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.78);
    backdrop-filter: blur(10px);
    border-radius: 9999px;
    padding: 0.4rem 0.85rem;
    box-shadow: 0 4px 14px rgba(14, 116, 144, 0.1);
}

.hud-stepper-track {
    flex: 1;
    height: 10px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
}

.hud-stepper-fill {
    height: 100%;
    background: linear-gradient(90deg, #06d6a0, #2395eb, #ff6b4a);
    border-radius: 9999px;
    transition: width 0.5s var(--spring-bounce);
}

.btn-hud-control {
    border: none;
    cursor: pointer;
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 800;
    border-radius: 9999px;
    padding: 0.38rem 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.2s ease;
}

.btn-hud-prev {
    background: #f1f5f9;
    color: #475569;
}

.btn-hud-prev:hover:not(:disabled) {
    background: #e2e8f0;
    transform: translateX(-2px);
}

.btn-hud-prev:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.btn-hud-next {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #ffffff;
    box-shadow: 0 3px 0 #9a3412, 0 4px 8px rgba(234, 88, 12, 0.3);
}

.btn-hud-next:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 0 #9a3412, 0 6px 12px rgba(234, 88, 12, 0.4);
}

.btn-hud-next:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #9a3412;
}

.btn-hud-reset {
    background: #fef08a;
    color: #854d0e;
    box-shadow: 0 2px 0 #ca8a04;
}

/* Main Vertical Container */
.journey-main-container {
    position: relative;
    z-index: 10;
    max-width: 680px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Banner */
.adventure-hero-banner {
    position: relative;
    text-align: center;
    padding: 2.2rem 1.5rem 1.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.84);
    backdrop-filter: blur(14px);
    border-radius: 32px;
    box-shadow: var(--neu-card-shadow);
    border: 2.5px solid rgba(255, 255, 255, 0.95);
    margin-bottom: 2rem;
}

.banner-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.9rem;
    background: #e0f2fe;
    color: #0284c7;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    margin-bottom: 0.75rem;
}

.pulsing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #0284c7;
}

.hero-chunky-title {
    font-family: var(--font-heading);
    font-size: 2.25rem;
    font-weight: 900;
    line-height: 1.18;
    color: #0369a1;
    margin: 0 0 0.75rem 0;
    text-shadow: 0 1px 0 #bae6fd, 0 2px 0 #7dd3fc, 0 3px 0 #38bdf8, 0 5px 12px rgba(2, 132, 199, 0.25);
}

.hero-description {
    font-size: 0.95rem;
    color: #475569;
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.5;
    font-weight: 600;
}

/* Floating Badges */
.floating-badge {
    position: absolute;
    pointer-events: none;
    z-index: 5;
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.1));
}

.badge-cloud-left { top: -24px; left: -20px; width: 64px; }
.badge-sparkle-top { top: -18px; right: 25%; font-size: 1.8rem; }
.badge-lightbulb-right { top: -12px; right: -10px; font-size: 1.75rem; }
.badge-pencil-left { bottom: -14px; left: -8px; font-size: 1.7rem; }
.badge-gear-right { bottom: -16px; right: -6px; font-size: 1.7rem; }
.badge-bubble-top { top: 40%; left: -24px; font-size: 1.6rem; }

/* S-Curve Trail Stage */
.trail-stage {
    position: relative;
    width: 100%;
    margin: 0 auto;
}

.svg-trail-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.stepping-dash-trail {
    animation: trailDashFlow 30s linear infinite;
}

@keyframes trailDashFlow {
    from { stroke-dashoffset: 1000; }
    to { stroke-dashoffset: 0; }
}

.illuminated-path {
    transition: stroke 0.6s ease;
}

.chest-beam {
    animation: chestGlowPulse 2.5s ease-in-out infinite alternate;
}

@keyframes chestGlowPulse {
    0% { stroke-width: 12; stroke-opacity: 0.8; }
    100% { stroke-width: 16; stroke-opacity: 1; }
}

/* Start Marker */
.start-marker-pod {
    position: absolute;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 15;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.neumorphic-start-pill {
    background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
    box-shadow: 6px 6px 14px var(--shadow-dark), -6px -6px 14px var(--shadow-light);
    border: 2px solid #ffffff;
    border-radius: 9999px;
    padding: 0.45rem 1.15rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.82rem;
    color: #0f172a;
}

.start-dot {
    width: 8px;
    height: 8px;
    background: #06d6a0;
    border-radius: 50%;
}

.start-anchor-line {
    width: 3px;
    height: 22px;
    background: #06d6a0;
    border-radius: 9999px;
}

/* Node Clusters */
.nodes-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.step-cluster {
    position: absolute;
    width: 100%;
    display: flex;
    align-items: center;
    z-index: 10;
}

.cluster-left {
    flex-direction: row;
    padding-left: calc(25% - 44px);
    gap: 1.25rem;
}

.cluster-right {
    flex-direction: row-reverse;
    padding-right: calc(25% - 44px);
    gap: 1.25rem;
}

.node-anchor-wrap {
    position: relative;
    width: 88px;
    height: 88px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.terrain-island {
    position: absolute;
    width: 110px;
    height: 60px;
    bottom: -6px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 1;
}

.terrain-surface {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    box-shadow: inset 0 3px 6px rgba(255, 255, 255, 0.7);
}

.terrain-shadow {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    transform: translateY(6px);
    background: rgba(14, 116, 144, 0.16);
    filter: blur(4px);
    z-index: -1;
}

.terrain-accent {
    position: absolute;
    top: -12px;
    right: -4px;
    font-size: 1.25rem;
}

.terrain-lush-green .terrain-surface { background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%); border: 2px solid #86efac; }
.terrain-wooden-bridge .terrain-surface { background: linear-gradient(180deg, #f59e0b 0%, #b45309 100%); border: 2px solid #fde68a; }
.terrain-explorer-cave .terrain-surface { background: linear-gradient(180deg, #94a3b8 0%, #475569 100%); border: 2px solid #cbd5e1; }
.terrain-sandy-lagoon .terrain-surface { background: linear-gradient(180deg, #fde047 0%, #ca8a04 100%); border: 2px solid #fef08a; }
.terrain-cliffside-trail .terrain-surface { background: linear-gradient(180deg, #10b981 0%, #047857 100%); border: 2px solid #6ee7b7; }
.terrain-highland-peak .terrain-surface { background: linear-gradient(180deg, #0284c7 0%, #0369a1 100%); border: 2px solid #7dd3fc; }

/* 3D Toy Button */
.chunky-3d-button {
    position: relative;
    z-index: 5;
    width: 68px;
    height: 68px;
    border-radius: 50%;
    border: 3px solid #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s ease;
    user-select: none;
    outline: none;
    padding: 0;
}

.btn-gloss {
    position: absolute;
    top: 4px;
    left: 12px;
    right: 12px;
    height: 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.75) 0%, rgba(255, 255, 255, 0) 100%);
    border-radius: 50%;
    pointer-events: none;
}

.btn-core {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-heading);
    font-weight: 900;
}

.btn-completed {
    background: linear-gradient(145deg, #34d399 0%, #059669 100%);
    box-shadow: var(--btn-green-lip);
}

.btn-completed:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 0 #15803d, 0 12px 20px rgba(34, 197, 94, 0.5);
}

.btn-completed:active {
    transform: translateY(4px);
    box-shadow: 0 2px 0 #15803d;
}

.badge-icon-check {
    font-size: 1.85rem;
    color: #ffffff;
    text-shadow: 0 2px 0 #047857;
}

.btn-active {
    background: linear-gradient(145deg, #fb7185 0%, #ea580c 100%);
    box-shadow: var(--btn-coral-lip);
    animation: activeBounce 2.4s ease-in-out infinite;
}

.btn-active:hover {
    transform: translateY(-3px) scale(1.04);
    box-shadow: 0 9px 0 #c2410c, 0 14px 22px rgba(234, 88, 12, 0.55);
}

.btn-active:active {
    transform: translateY(4px);
    box-shadow: 0 2px 0 #c2410c;
}

.badge-num-active {
    font-size: 1.85rem;
    color: #ffffff;
    text-shadow: 0 2px 0 #9a3412;
}

@keyframes activeBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-7px); }
}

.active-pulse-aura {
    position: absolute;
    inset: -10px;
    border-radius: 50%;
    border: 3px solid rgba(249, 115, 22, 0.6);
    animation: auraPulse 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
    pointer-events: none;
}

@keyframes auraPulse {
    0% { transform: scale(0.9); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0; }
}

.btn-locked {
    background: #cbd5e1;
    box-shadow: var(--btn-locked-lip);
    border-color: #f1f5f9;
    cursor: not-allowed;
    filter: grayscale(0.5);
}

.badge-icon-lock {
    font-size: 1.35rem;
    color: #64748b;
}

.shake-locked {
    animation: lockShake 0.4s ease-in-out;
}

@keyframes lockShake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}

.active-avatar-beacon {
    position: absolute;
    top: -34px;
    display: flex;
    flex-direction: column;
    align-items: center;
    pointer-events: none;
    animation: beaconBob 2s ease-in-out infinite;
    z-index: 20;
}

.beacon-chip {
    background: #ea580c;
    color: #ffffff;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.62rem;
    letter-spacing: 0.05em;
    padding: 0.2rem 0.5rem;
    border-radius: 9999px;
    box-shadow: 0 3px 8px rgba(234, 88, 12, 0.35);
}

.beacon-arrow {
    font-size: 0.7rem;
    color: #ea580c;
    margin-top: -3px;
}

@keyframes beaconBob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

/* Speech Bubble Card */
.speech-bubble-card {
    position: relative;
    max-width: 310px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    border-radius: 22px;
    padding: 1.15rem 1.25rem;
    box-shadow: var(--neu-card-shadow);
    border: 2px solid rgba(255, 255, 255, 0.95);
    cursor: pointer;
    transition: all 0.25s var(--spring-bounce);
    user-select: none;
}

.speech-bubble-card:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 12px 12px 28px rgba(14, 116, 144, 0.2), -12px -12px 28px var(--shadow-light);
}

.speech-bubble-card.elevated {
    border-color: #fed7aa;
    box-shadow: 12px 12px 30px rgba(249, 115, 22, 0.22), -10px -10px 24px var(--shadow-light);
}

.speech-pointer-triangle {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    pointer-events: none;
}

.pointer-left .speech-pointer-triangle {
    left: -12px;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right: 12px solid rgba(255, 255, 255, 0.94);
}

.pointer-right .speech-pointer-triangle {
    right: -12px;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-left: 12px solid rgba(255, 255, 255, 0.94);
}

.bubble-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.45rem;
}

.step-kicker-pill {
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    text-transform: uppercase;
}

.pill-lush-green { background: #dcfce7; color: #15803d; }
.pill-wooden-bridge { background: #fef3c7; color: #b45309; }
.pill-explorer-cave { background: #f1f5f9; color: #475569; }
.pill-sandy-lagoon { background: #fef9c3; color: #a16207; }
.pill-cliffside-trail { background: #d1fae5; color: #047857; }
.pill-highland-peak { background: #e0f2fe; color: #0369a1; }

.xp-badge {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.74rem;
    color: #f59e0b;
    background: #fffbeb;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    border: 1px solid #fef3c7;
}

.bubble-title {
    font-family: var(--font-heading);
    font-size: 1.02rem;
    font-weight: 800;
    color: var(--brand-navy);
    margin: 0 0 0.4rem 0;
    line-height: 1.3;
}

.bubble-summary {
    font-size: 0.82rem;
    color: #475569;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.bubble-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.5rem;
    border-top: 1px solid #f1f5f9;
}

.time-meta {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
}

.contents-count-tag {
    background: #f1f5f9;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    font-size: 0.7rem;
    color: #0369a1;
}

.tag-status {
    font-family: var(--font-heading);
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
}

.tag-completed { background: #dcfce7; color: #15803d; }
.tag-active { background: #ffedd5; color: #c2410c; }
.tag-locked { background: #f1f5f9; color: #94a3b8; }

/* Finale Node */
.finale-destination-pod {
    position: absolute;
    bottom: 35px;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 440px;
    z-index: 15;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.celebration-ribbon-wrap {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.curved-ribbon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1.8rem;
    background: linear-gradient(180deg, #fef08a 0%, #f59e0b 50%, #d97706 100%);
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(217, 119, 6, 0.4);
    border: 2px solid #fffbeb;
}

.ribbon-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ribbon-text {
    font-family: var(--font-heading);
    font-size: 1.15rem;
    font-weight: 900;
    color: #78350f;
    letter-spacing: 0.06em;
}

.ribbon-sub {
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 800;
    color: #0369a1;
    margin: 0.35rem 0 0 0;
}

.treasure-chest-stage {
    position: relative;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    user-select: none;
    transition: transform 0.3s var(--spring-bounce);
}

.treasure-chest-stage:hover { transform: scale(1.05); }
.treasure-chest-stage:active { transform: scale(0.98); }

.golden-sunburst {
    position: absolute;
    top: -20px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(250, 204, 21, 0.45) 0%, rgba(250, 204, 21, 0) 70%);
    pointer-events: none;
    z-index: 1;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.golden-sunburst.spin-active {
    opacity: 1;
    animation: sunburstSpin 15s linear infinite;
}

@keyframes sunburstSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.chest-box {
    position: relative;
    width: 140px;
    height: 100px;
    z-index: 5;
    perspective: 500px;
}

.chest-lid {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 48px;
    z-index: 10;
    transform-origin: bottom center;
    transition: transform 0.6s var(--spring-bounce);
}

.is-open .chest-lid {
    transform: rotateX(-75deg) translateY(-20px);
}

.lid-wood {
    position: relative;
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #92400e 0%, #78350f 100%);
    border-radius: 36px 36px 8px 8px;
    border: 3.5px solid #451a03;
    display: flex;
    justify-content: space-around;
    align-items: center;
}

.lid-iron-band {
    width: 12px;
    height: 100%;
    background: linear-gradient(180deg, #f59e0b 0%, #b45309 100%);
}

.lid-handle {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 14px;
    border: 3px solid #fde68a;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}

.chest-booty {
    position: absolute;
    top: 15px;
    left: 10px;
    right: 10px;
    height: 45px;
    z-index: 6;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.6);
    transition: all 0.5s var(--spring-bounce);
    pointer-events: none;
}

.chest-booty.visible {
    opacity: 1;
    transform: scale(1.1) translateY(-8px);
}

.booty-sparkles {
    font-size: 1.5rem;
}

.coins-pile {
    display: flex;
    align-items: center;
    gap: 0.15rem;
    font-size: 1.3rem;
}

.chest-body {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: linear-gradient(180deg, #78350f 0%, #451a03 100%);
    border-radius: 8px 8px 18px 18px;
    border: 3.5px solid #290e03;
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 5;
}

.body-iron-band {
    width: 12px;
    height: 100%;
    background: linear-gradient(180deg, #f59e0b 0%, #b45309 100%);
}

.chest-lock {
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 32px;
    background: radial-gradient(circle, #fde047 0%, #d97706 100%);
    border-radius: 50%;
    border: 2px solid #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 8;
}

.chest-action-pill {
    margin-top: 0.75rem;
    padding: 0.45rem 1.15rem;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.84rem;
    background: #ffffff;
    color: #b45309;
    box-shadow: var(--neu-card-shadow);
    border: 2px solid #fde68a;
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

.modal-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    border-radius: 28px;
    padding: 2rem;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
    border: 3px solid #ffffff;
}

.modal-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    font-size: 1.1rem;
    font-weight: 800;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.75rem;
}

.modal-step-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-family: var(--font-heading);
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
}

.modal-theme-tag {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
}

.modal-title {
    font-family: var(--font-heading);
    font-size: 1.45rem;
    font-weight: 900;
    color: var(--brand-navy);
    margin: 0 0 0.35rem 0;
    line-height: 1.25;
}

.modal-subtitle {
    font-family: var(--font-heading);
    font-size: 0.95rem;
    font-weight: 700;
    color: #0284c7;
    margin: 0 0 1.25rem 0;
}

.modal-meta-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    background: #f8fafc;
    border-radius: 16px;
    padding: 0.85rem;
    margin-bottom: 1.25rem;
    border: 1.5px solid #e2e8f0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.meta-icon { font-size: 1.2rem; }
.meta-label { display: block; font-size: 0.62rem; font-weight: 800; color: #94a3b8; }
.meta-value { display: block; font-family: var(--font-heading); font-size: 0.85rem; font-weight: 800; color: var(--brand-navy); }

.modal-body-section h4 {
    font-family: var(--font-heading);
    font-size: 0.95rem;
    font-weight: 800;
    margin: 0 0 0.4rem 0;
    color: #334155;
}

.modal-body-section p {
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.55;
    margin: 0 0 1.25rem 0;
}

/* Manage Content Button */
.modal-manage-row {
    margin-bottom: 1.5rem;
}

.btn-manage-contents {
    width: 100%;
    background: #f0fdf4;
    border: 2px dashed #86efac;
    color: #15803d;
    border-radius: 14px;
    padding: 0.75rem 1rem;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-manage-contents:hover {
    background: #dcfce7;
    border-color: #22c55e;
    transform: translateY(-1px);
}

.modal-actions-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.modal-secondary-btn {
    background: #f1f5f9;
    color: #475569;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 14px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
}

.modal-primary-btn {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #ffffff;
    border: none;
    padding: 0.65rem 1.35rem;
    border-radius: 14px;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.9rem;
    cursor: pointer;
    box-shadow: 0 4px 0 #9a3412, 0 8px 14px rgba(234, 88, 12, 0.35);
}

.modal-review-btn {
    background: #dcfce7;
    color: #15803d;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 14px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
}

.modal-locked-btn {
    background: #e2e8f0;
    color: #94a3b8;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 14px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.9rem;
    cursor: not-allowed;
}

.modal-bounce-enter-active { animation: modalPop 0.35s var(--spring-bounce); }
.modal-bounce-leave-active { animation: modalPop 0.25s reverse ease-in; }

@keyframes modalPop {
    from { opacity: 0; transform: scale(0.85) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Celebration Toast */
.celebration-toast {
    position: fixed;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    max-width: 540px;
    z-index: 90;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    border-radius: 24px;
    padding: 1.2rem 1.5rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    border: 2px solid #facc15;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.toast-content { display: flex; align-items: center; gap: 0.85rem; }
.toast-trophy { font-size: 2.2rem; }
.toast-title { margin: 0; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 900; color: #facc15; }
.toast-p { margin: 0.2rem 0 0 0; font-size: 0.82rem; color: #cbd5e1; }

.btn-toast-claim {
    background: #facc15;
    color: #713f12;
    border: none;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.85rem;
    padding: 0.6rem 1.15rem;
    border-radius: 9999px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(250, 204, 21, 0.4);
    white-space: nowrap;
}

.drawer-slide-enter-active { animation: slideUp 0.4s var(--spring-bounce); }
.drawer-slide-leave-active { animation: slideUp 0.3s reverse ease-in; }

@keyframes slideUp {
    from { opacity: 0; transform: translate(-50%, 60px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}

/* Empty State: Uncharted Reading Realm */
.empty-journey-container {
    padding: 2.5rem 1rem 5rem 1rem;
    display: flex;
    justify-content: center;
}

.empty-island-card {
    position: relative;
    max-width: 520px;
    width: 100%;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(14px);
    border-radius: 32px;
    padding: 3rem 2rem;
    text-align: center;
    box-shadow: var(--neu-card-shadow);
    border: 3px solid #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.empty-island-badge {
    font-size: 4rem;
    margin-bottom: 0.75rem;
    filter: drop-shadow(0 8px 16px rgba(14, 116, 144, 0.2));
    animation: floatIsland 5s ease-in-out infinite alternate;
}

@keyframes floatIsland {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(-10px) scale(1.05); }
}

.empty-kicker {
    display: inline-block;
    font-family: var(--font-heading);
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #0284c7;
    background: #e0f2fe;
    padding: 0.3rem 0.85rem;
    border-radius: 9999px;
    margin-bottom: 0.85rem;
}

.empty-title {
    font-family: var(--font-heading);
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--brand-navy);
    margin: 0 0 0.65rem 0;
}

.empty-desc {
    font-size: 0.95rem;
    color: #475569;
    line-height: 1.55;
    margin: 0 0 2rem 0;
    max-width: 440px;
}

.btn-3d-create-first-lecture {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border: none;
    border-radius: 18px;
    padding: 1rem 2rem;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 1.05rem;
    color: #ffffff;
    box-shadow: 0 5px 0 #9a3412, 0 10px 22px rgba(234, 88, 12, 0.4);
    cursor: pointer;
    transition: all 0.2s var(--spring-bounce);
}

.btn-3d-create-first-lecture:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 0 #9a3412, 0 14px 28px rgba(234, 88, 12, 0.5);
}

.btn-3d-create-first-lecture:active {
    transform: translateY(3px);
    box-shadow: 0 2px 0 #9a3412;
}

.empty-action-row {
    justify-content: center;
}

.empty-hud-text {
    font-family: var(--font-heading);
    font-size: 0.82rem;
    font-weight: 800;
    color: #0369a1;
}

@media (max-width: 1280px) {
    .flank-wing { width: 190px; opacity: 0.75; }
}

@media (max-width: 992px) {
    .flank-wing { display: none; }
}

@media (max-width: 640px) {
    .hud-module-name { max-width: 120px; }
    .btn-hud-create-lecture { font-size: 0.68rem; padding: 0.35rem 0.65rem; }
    .hero-chunky-title { font-size: 1.7rem; }
    .adventure-hero-banner { padding: 1.6rem 1rem 1.25rem 1rem; }
    .cluster-left { padding-left: calc(20% - 36px); gap: 0.75rem; }
    .cluster-right { padding-right: calc(20% - 36px); gap: 0.75rem; }
    .node-anchor-wrap { width: 72px; height: 72px; }
    .chunky-3d-button { width: 58px; height: 58px; }
    .badge-icon-check, .badge-num-active { font-size: 1.5rem; }
    .speech-bubble-card { max-width: 215px; padding: 0.85rem 0.95rem; }
    .bubble-title { font-size: 0.88rem; }
    .bubble-summary { font-size: 0.75rem; }
    .celebration-toast { flex-direction: column; text-align: center; padding: 1rem; }
    .toast-content { flex-direction: column; }
}
</style>
