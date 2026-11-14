<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ReadingJourneyMap from '@/Components/ReadingJourneyMap.vue';
import { flushAllClientCache, prefetchRoute } from '@/Composables/usePrefetch';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Teacher',
            email: 'teacher@example.com',
            role: 'teacher',
        }),
    },
    module: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Conflict and Community Values',
            slug: 'conflict-and-community-values',
            description: 'Journey through historical archipelagos, resolve narrative tensions, and explore the deep power of Bayanihan.',
            grade_level: 'Grade 3 • Reading Legends',
            theme: 'forest',
            icon: '🌲',
            cover_image_url: null,
            xp_bonus: 1250,
            boss_name: 'Grammar Goblin',
            max_students: 35,
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
    /** The modules list as the teacher left it (filtered by classroom, or all modules). */
    backLink: {
        type: Object,
        default: () => ({ url: '/teachers/dashboard', label: 'All Modules' }),
    },
});

const totalMilestonesCount = computed(() => (props.lectures?.length || 0) + (props.quizzes?.length || 0));
const totalMilestonesLabel = computed(() => {
    const total = totalMilestonesCount.value;
    if (total === 1) return '1 Milestone';
    return `${total} Milestones`;
});

// Modal state management
const showCreateChoiceModal = ref(false);
const showQuizTypesModal = ref(false);
const selectedQuiz = ref(null);
const quizNotice = ref(null);

// Quiz formats requested
const quizTypes = [
    {
        id: 'multiple-choice',
        name: 'Multiple Choice',
        icon: '🔘',
        theme: 'orange',
        badge: 'Classic Quiz',
        xpBonus: 150,
        description: 'Single or multi-select questions with answer choices, hints, and automated scoring.',
    },
    {
        id: 'color-the-conflict',
        name: 'Color the Conflict',
        icon: '🎨',
        theme: 'green',
        badge: 'Visual Quest',
        xpBonus: 200,
        description: 'Identify and color narrative conflicts, story elements, and character tensions.',
    },
    {
        id: 'the-conflict-map',
        name: 'The Conflict Map',
        icon: '🗺️',
        theme: 'blue',
        badge: 'Plot Navigator',
        xpBonus: 180,
        description: 'Map out the narrative arc, conflict progression, and story resolutions.',
    },
    {
        id: 'matching-type',
        name: 'Matching Type',
        icon: '🧩',
        theme: 'purple',
        badge: 'Concept Match',
        xpBonus: 220,
        description: 'Pair narrative concepts, matching story events with character motivations or morals.',
    },
    {
        id: 'evaluation',
        name: 'Evaluation',
        icon: '📊',
        theme: 'rose',
        badge: 'Comprehensive Review',
        xpBonus: 250,
        description: 'Structured reading comprehension rubric with graded multi-part critical thinking questions.',
    },
    {
        id: 'reflection',
        name: 'Reflection',
        icon: '💭',
        theme: 'amber',
        badge: 'Values & Insights',
        xpBonus: 180,
        description: 'Open-ended reflection prompts encouraging students to express personal and moral insights.',
    },
];

const backUrl = computed(() => props.backLink?.url || '/teachers/dashboard');

const handleBack = () => {
    router.visit(backUrl.value);
};

const handleLogout = () => {
    flushAllClientCache();
    router.post('/logout');
};

const openCreateChoiceModal = () => {
    selectedQuiz.value = null;
    quizNotice.value = null;
    showCreateChoiceModal.value = true;
    showQuizTypesModal.value = false;
    if (props.module?.slug) {
        prefetchRoute(`/teachers/modules/${props.module.slug}/lectures/create`, ['teacher-modules', `module-${props.module.slug}`], ['15s', '60s']);
    }
};

const closeAllModals = () => {
    showCreateChoiceModal.value = false;
    showQuizTypesModal.value = false;
};

const handleCreateLecture = () => {
    closeAllModals();
    router.visit(`/teachers/modules/${props.module.slug}/lectures/create`);
};

const handleOpenQuizModal = () => {
    showCreateChoiceModal.value = false;
    showQuizTypesModal.value = true;
};

const handleBackToChoiceModal = () => {
    showQuizTypesModal.value = false;
    showCreateChoiceModal.value = true;
};

const handleSelectQuiz = (quiz) => {
    selectedQuiz.value = quiz;
    if (quiz.id === 'multiple-choice' || quiz.id === 'color-the-conflict' || quiz.id === 'the-conflict-map' || quiz.id === 'matching-type' || quiz.id === 'evaluation' || quiz.id === 'reflection') {
        launchQuizBuilder(quiz);
    }
};

const launchQuizBuilder = (quiz) => {
    const supportedTypes = [
        'multiple-choice',
        'color-the-conflict',
        'the-conflict-map',
        'matching-type',
        'evaluation',
        'reflection',
    ];

    if (supportedTypes.includes(quiz.id)) {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/studio?type=${quiz.id}`);
        return;
    }

    quizNotice.value = `${quiz.name} challenge format selected! Studio coming soon.`;
    setTimeout(() => {
        quizNotice.value = null;
    }, 3500);
};

const isCodeCopied = ref(false);

const copyJoinCode = async () => {
    if (!props.module?.join_code) return;
    const codeStr = String(props.module.join_code);
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(codeStr);
        } else {
            throw new Error('Clipboard API unavailable');
        }
    } catch {
        const textarea = document.createElement('textarea');
        textarea.value = codeStr;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }

    isCodeCopied.value = true;
    quizNotice.value = `Quest Code ${codeStr} copied! Give this 6-digit code to your students to join.`;
    setTimeout(() => {
        isCodeCopied.value = false;
    }, 2500);
    setTimeout(() => {
        if (quizNotice.value?.includes('Quest Code')) {
            quizNotice.value = null;
        }
    }, 4000);
};
</script>

<template>
    <Head :title="`${module.title} • Reading Journey Map`" />

    <div class="module-view-page" :class="`page-theme-${module.theme || 'forest'}`">
        <!-- Ambient Floating Memory Orbs & Sparkles -->
        <div class="game-bg-elements" aria-hidden="true">
            <span class="pixar-bg-orb orb-1"></span>
            <span class="pixar-bg-orb orb-2"></span>
            <span class="pixar-bg-orb orb-3"></span>
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">⭐</div>
            <div class="floating-sparkle sp-3">✨</div>
            <div class="floating-sparkle sp-4">🌟</div>
        </div>

        <!-- Top Game Header Bar matching Dashboard conventions -->
        <header class="module-header-nav">
            <div class="nav-left">
                <button
                    type="button"
                    class="btn-back-nav"
                    :title="`Back to ${backLink.label}`"
                    :aria-label="`Back to ${backLink.label}`"
                    @mouseenter="prefetchRoute(backUrl, ['teacher-modules'])"
                    @focus="prefetchRoute(backUrl, ['teacher-modules'])"
                    @click="handleBack"
                >
                    <span class="back-arrow">❮</span>
                    <span class="back-nav-label">{{ backLink.label }}</span>
                </button>

                <div class="brand-group">
                    <span class="brand-text">
                        <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                    </span>
                    <span class="nav-badge">Island Explorer</span>
                </div>
            </div>

            <div class="nav-center-module">
                <span class="module-icon-pill">{{ module.icon }}</span>
                <span class="module-nav-title">{{ module.title }}</span>
                <span class="module-lecture-count">{{ totalMilestonesLabel }}</span>
            </div>

            <div class="nav-right">
                <!-- 6-Digit Student Quest Code Pill & 1-Click Copy -->
                <button
                    v-if="module.join_code"
                    type="button"
                    class="btn-quest-code"
                    :class="{ 'is-copied': isCodeCopied }"
                    title="Click to copy 6-digit student quest code"
                    @click="copyJoinCode"
                >
                    <span class="code-icon">{{ isCodeCopied ? '✅' : '🔑' }}</span>
                    <span class="code-label">CODE:</span>
                    <strong class="code-number">{{ module.join_code }}</strong>
                    <span class="code-badge-action">{{ isCodeCopied ? 'COPIED!' : 'COPY' }}</span>
                </button>

                <!-- 🚀 Pixar 3D Tactile Publish Quiz Action Button -->


                <!-- + Create Milestone Action Button -->
                <button
                    type="button"
                    class="btn-nav-create"
                    @click="openCreateChoiceModal"
                >
                    <span>✨ + CREATE</span>
                </button>

                <div class="user-chip">
                    <span class="user-avatar">🧙‍♂️</span>
                    <span class="user-name">{{ user.name }}</span>
                </div>
            </div>
        </header>

        <!-- Toast Notice for Quiz Actions -->
        <transition name="toast-slide">
            <div v-if="quizNotice" class="quiz-notice-toast">
                <span class="toast-icon">🎮</span>
                <span class="toast-msg">{{ quizNotice }}</span>
            </div>
        </transition>

        <!-- Embedded Vertical Gamified Reading Journey Map with Database Lectures and Quizzes -->
        <ReadingJourneyMap
            :module="module"
            :lectures="lectures"
            :quizzes="quizzes"
            :module-title="module.title"
            :module-grade="module.grade_level"
            :total-xp="module.xp_bonus || 1250"
            @create-milestone="openCreateChoiceModal"
        />

        <!-- MODAL 1: Choose Milestone Type (Create Lecture vs Create Quiz) -->
        <transition name="modal-bounce">
            <div
                v-if="showCreateChoiceModal"
                class="modal-backdrop-layer"
                @click.self="closeAllModals"
            >
                <div class="modal-dialog choice-dialog">
                    <!-- Overlapping 3D Storybook Crest Badge -->
                    <div class="modal-crest-badge" aria-hidden="true">
                        <span class="crest-icon">🗺️</span>
                    </div>

                    <button
                        type="button"
                        class="modal-btn-close"
                        @click="closeAllModals"
                        aria-label="Close dialog"
                    >
                        ✕
                    </button>

                    <div class="dialog-header">
                        <span class="dialog-badge">🗺️ MILESTONE GENERATOR</span>
                        <h2 class="dialog-title">Choose Milestone Type</h2>
                        <p class="dialog-subtitle">
                            Select what type of learning milestone to add to <strong>{{ module.title }}</strong>:
                        </p>
                    </div>

                    <div class="choice-cards-grid">
                        <!-- Card 1: Create Lecture -->
                        <div class="milestone-choice-card card-lecture" @click="handleCreateLecture">
                            <div class="choice-card-icon-wrap bg-lecture">
                                <span class="choice-icon">📖</span>
                            </div>
                            <div class="choice-card-meta">
                                <span class="choice-tag tag-lecture">STORY & READING</span>
                                <h3 class="choice-card-title">Create Lecture</h3>
                                <p class="choice-card-desc">
                                    Craft reading passages, story guides, and multimedia blocks (images, audio narration, and video lessons).
                                </p>
                            </div>
                            <button
                                type="button"
                                class="btn-3d-choice btn-choice-lecture"
                                @click.stop="handleCreateLecture"
                            >
                                <span>📖 Create Lecture Milestone ➔</span>
                            </button>
                        </div>

                        <!-- Card 2: Create Quiz -->
                        <div class="milestone-choice-card card-quiz" @click="handleOpenQuizModal">
                            <div class="choice-card-icon-wrap bg-quiz">
                                <span class="choice-icon">🎯</span>
                            </div>
                            <div class="choice-card-meta">
                                <span class="choice-tag tag-quiz">INTERACTIVE CHALLENGE</span>
                                <h3 class="choice-card-title">Create Quiz</h3>
                                <p class="choice-card-desc">
                                    Assess reading comprehension with interactive challenges, knowledge checks, drag-and-drop puzzles, and reflections.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="btn-3d-choice btn-choice-quiz"
                                @click.stop="handleOpenQuizModal"
                            >
                                <span>🎯 Create Quiz ➔</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- MODAL 2: Select Quiz Format Modal -->
        <transition name="modal-bounce">
            <div
                v-if="showQuizTypesModal"
                class="modal-backdrop-layer"
                @click.self="closeAllModals"
            >
                <div class="modal-dialog quiz-dialog">
                    <!-- Overlapping 3D Storybook Crest Badge -->
                    <div class="modal-crest-badge" aria-hidden="true">
                        <span class="crest-icon">🏆</span>
                    </div>

                    <div class="dialog-nav-bar">
                        <button
                            type="button"
                            class="btn-dialog-back"
                            @click="handleBackToChoiceModal"
                        >
                            <span>❮ Back to Milestone Types</span>
                        </button>
                        <button
                            type="button"
                            class="modal-btn-close"
                            @click="closeAllModals"
                            aria-label="Close dialog"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="dialog-header">
                        <span class="dialog-badge">🏆 QUEST CHALLENGE STUDIO</span>
                        <h2 class="dialog-title">Select Quiz Format</h2>
                        <p class="dialog-subtitle">
                            Choose an interactive assessment format to test student reading comprehension:
                        </p>
                    </div>

                    <!-- 6 Quizzes Grid -->
                    <div class="quiz-types-grid">
                        <div
                            v-for="quiz in quizTypes"
                            :key="quiz.id"
                            class="quiz-type-card"
                            :class="[
                                `theme-${quiz.theme}`,
                                { 'is-selected': selectedQuiz?.id === quiz.id }
                            ]"
                            @click="handleSelectQuiz(quiz)"
                        >
                            <div class="quiz-card-top">
                                <div class="quiz-icon-pod">
                                    <span class="quiz-emoji">{{ quiz.icon }}</span>
                                </div>
                                <span class="quiz-badge-pill">{{ quiz.badge }}</span>
                            </div>

                            <h3 class="quiz-type-title">{{ quiz.name }}</h3>
                            <p class="quiz-type-desc">{{ quiz.description }}</p>

                            <div class="quiz-card-footer">
                                <span class="quiz-xp-chip">⚡ +{{ quiz.xpBonus }} XP</span>
                                <span class="quiz-select-arrow">Select ➔</span>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Confirmation Footer Banner (if quiz selected) -->
                    <div v-if="selectedQuiz" class="quiz-selected-banner">
                        <div class="banner-info">
                            <span class="banner-icon">{{ selectedQuiz.icon }}</span>
                            <div>
                                <span class="banner-title">Selected: <strong>{{ selectedQuiz.name }}</strong></span>
                                <p class="banner-sub">Quiz challenge format selected for {{ module.title }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-3d-launch-quiz"
                            @click="launchQuizBuilder(selectedQuiz)"
                        >
                            <span>✨ Launch {{ selectedQuiz.name }} Studio ➔</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* ==========================================================================
   PIXAR-INSPIRED 3D GRAPHIC GAME SYSTEM & TOKENS
   ========================================================================== */
.module-view-page {
    --game-navy: #1D2440;
    --game-border: #2B3354;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-game: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;

    /* Theme defaults: Forest World */
    --px-sky-top: #F0FDF4;
    --px-sky-mid: #DCFCE7;
    --px-sky-bottom: #BBF7D0;
    --px-bloom: rgba(167, 243, 208, 0.45);
    --px-theme-shadow: 20, 60, 45;

    min-height: 100vh;
    background: linear-gradient(175deg, var(--px-sky-top) 0%, var(--px-sky-mid) 45%, var(--px-sky-bottom) 100%);
    font-family: var(--font-ui);
    color: var(--game-navy);
    position: relative;
    overflow-x: clip;
}

/* Atmospheric Biome Presets */
.page-theme-ocean {
    --px-sky-top: #F0F9FF;
    --px-sky-mid: #E0F2FE;
    --px-sky-bottom: #BAE6FD;
    --px-bloom: rgba(186, 230, 253, 0.45);
    --px-theme-shadow: 15, 55, 95;
}

.page-theme-cosmic {
    --px-sky-top: #FAF5FF;
    --px-sky-mid: #F3E8FF;
    --px-sky-bottom: #E9D5FF;
    --px-bloom: rgba(233, 213, 255, 0.45);
    --px-theme-shadow: 45, 25, 90;
}

.page-theme-volcano {
    --px-sky-top: #FFF7ED;
    --px-sky-mid: #FFEDD5;
    --px-sky-bottom: #FED7AA;
    --px-bloom: rgba(254, 215, 170, 0.45);
    --px-theme-shadow: 85, 35, 18;
}

/* Tactile Micro-Grain Film Overlay */
.module-view-page::before {
    content: '';
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 2;
    opacity: 0.035;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}

/* Ambient Floating Memory Orbs & Sparkles */
.game-bg-elements {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
}

.pixar-bg-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    will-change: transform;
}

.orb-1 {
    width: 130px;
    height: 130px;
    top: 14%;
    left: 4%;
    background: radial-gradient(circle at 32% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.45) 20%, rgba(56, 189, 248, 0.28) 60%, transparent 80%);
    box-shadow: 0 16px 36px -6px rgba(56, 189, 248, 0.3);
    animation: orbFloat 14s ease-in-out infinite alternate;
}

.orb-2 {
    width: 90px;
    height: 90px;
    top: 36%;
    right: 5%;
    background: radial-gradient(circle at 32% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.45) 20%, rgba(251, 191, 36, 0.28) 60%, transparent 80%);
    box-shadow: 0 14px 28px -4px rgba(251, 191, 36, 0.3);
    animation: orbFloat 12s ease-in-out infinite alternate-reverse;
}

.orb-3 {
    width: 150px;
    height: 150px;
    bottom: 12%;
    left: 8%;
    background: radial-gradient(circle at 32% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.4) 20%, rgba(167, 139, 250, 0.24) 65%, transparent 85%);
    box-shadow: 0 20px 42px -8px rgba(167, 139, 250, 0.28);
    animation: orbFloat 18s ease-in-out infinite alternate;
}

@keyframes orbFloat {
    0% { transform: translate3d(0, 0, 0) scale(1); }
    50% { transform: translate3d(14px, -20px, 0) scale(1.04); }
    100% { transform: translate3d(-10px, 12px, 0) scale(0.97); }
}

.floating-sparkle {
    position: absolute;
    font-size: 1.6rem;
    filter: drop-shadow(0 2px 6px rgba(255, 215, 0, 0.5));
    animation: sparkleFloat 6s ease-in-out infinite alternate;
}

.sp-1 { top: 12%; left: 6%; animation-duration: 5s; }
.sp-2 { top: 28%; right: 7%; animation-duration: 7s; animation-delay: 1s; }
.sp-3 { bottom: 22%; left: 9%; animation-duration: 6.5s; animation-delay: 2s; }
.sp-4 { bottom: 15%; right: 10%; animation-duration: 8s; animation-delay: 0.5s; }

@keyframes sparkleFloat {
    0% { transform: translateY(0) rotate(0deg) scale(0.9); opacity: 0.6; }
    50% { transform: translateY(-16px) rotate(15deg) scale(1.15); opacity: 1; }
    100% { transform: translateY(-30px) rotate(-10deg) scale(0.95); opacity: 0.7; }
}

/* ==========================================================================
   PIXAR TACTILE HUD TOP STATUS BAR
   ========================================================================== */
.module-header-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem clamp(1.25rem, 2.5vw, 2.5rem);
    background: linear-gradient(180deg, #FFFFFF 0%, #F5F8FE 60%, #EBF2FC 100%);
    border-bottom: 3.5px solid var(--game-border);
    box-shadow:
        0 6px 0 var(--game-border),
        0 14px 28px rgba(29, 36, 64, 0.12),
        inset 0 2px 1.5px rgba(255, 255, 255, 1),
        inset 0 -3px 5px rgba(175, 188, 218, 0.35);
    position: sticky;
    top: 0;
    z-index: 50;
    gap: 1.25rem;
    box-sizing: border-box;
}

.nav-left {
    display: flex;
    align-items: center;
    gap: 1.15rem;
}

.back-nav-label {
    max-width: 11rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-back-nav {
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5FB 50%, #E3EAF4 100%);
    border: 2.5px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.45rem 1.1rem;
    font-family: var(--font-game);
    font-weight: 800;
    font-size: 0.86rem;
    color: var(--game-navy);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    box-shadow:
        0 3.5px 0 var(--game-border),
        0 4px 10px rgba(29, 36, 64, 0.08),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -2px 3px rgba(165, 178, 208, 0.4);
    transform: translate3d(0, 0, 0);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-back-nav:hover {
    transform: translate3d(0, -2px, 0);
    box-shadow:
        0 5.5px 0 var(--game-border),
        0 8px 16px rgba(29, 36, 64, 0.12),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1);
}

.btn-back-nav:active {
    transform: translate3d(0, 2px, 0) scaleX(1.02) scaleY(0.96);
    box-shadow:
        0 1.5px 0 var(--game-border),
        inset 0 2px 4px rgba(165, 178, 208, 0.45);
}

.btn-back-nav:focus-visible {
    outline: 2px solid #2395EB;
    outline-offset: 3px;
}

.back-arrow {
    font-weight: 900;
    color: #2395EB;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.brand-text {
    font-family: var(--font-game);
    font-weight: 900;
    font-size: 1.4rem;
    letter-spacing: -0.02em;
    filter: drop-shadow(0 2px 0 rgba(255, 255, 255, 0.9));
}

.brand-web { color: #2395EB; }
.brand-read { color: #FF6B4A; }

.nav-badge {
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    color: #854D0E;
    border: 2px solid #F59E0B;
    font-family: var(--font-game);
    font-size: 0.68rem;
    font-weight: 900;
    padding: 0.18rem 0.6rem;
    border-radius: 9999px;
    letter-spacing: 0.04em;
    box-shadow:
        0 2px 0 #D97706,
        inset 0 1px 1px rgba(255, 255, 255, 0.9);
}

/* Center Module Info Console */
.nav-center-module {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    background: #E8F0FA;
    border: 2.5px solid var(--game-border);
    padding: 0.35rem 1rem;
    border-radius: 9999px;
    max-width: 420px;
    box-shadow:
        inset 0 3px 6px rgba(29, 36, 64, 0.2),
        inset 0 -1.5px 2px rgba(255, 255, 255, 0.95),
        0 1px 0 rgba(255, 255, 255, 0.85);
}

.module-icon-pill {
    font-size: 1.25rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.15));
}

.module-nav-title {
    font-family: var(--font-game);
    font-weight: 800;
    font-size: 0.92rem;
    color: var(--game-navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.module-lecture-count {
    background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
    color: #0369A1;
    border: 1.5px solid #38BDF8;
    font-family: var(--font-game);
    font-weight: 900;
    font-size: 0.72rem;
    padding: 0.18rem 0.6rem;
    border-radius: 9999px;
    box-shadow:
        0 2px 0 #0284C7,
        inset 0 1px 1px rgba(255, 255, 255, 0.8);
    white-space: nowrap;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

/* Extruded Hero Pixar + Create Button */
.btn-quest-code {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 52%, #D97706 100%);
    color: #451A03;
    border: 2px solid #78350F;
    border-radius: 9999px;
    padding: 0.45rem 0.95rem;
    font-family: var(--font-game);
    cursor: pointer;
    box-shadow:
        0 4px 0 #78350F,
        0 8px 16px rgba(217, 119, 6, 0.35),
        inset 0 2px 2px rgba(255, 255, 255, 0.85);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-quest-code:hover {
    transform: translate3d(0, -2px, 0);
    box-shadow:
        0 6px 0 #78350F,
        0 12px 20px rgba(217, 119, 6, 0.45),
        inset 0 2px 2px rgba(255, 255, 255, 0.95);
}

.btn-quest-code:active {
    transform: translate3d(0, 3px, 0);
    box-shadow:
        0 1.5px 0 #78350F,
        inset 0 2px 4px rgba(120, 53, 15, 0.4);
}

.btn-quest-code.is-copied {
    background: linear-gradient(180deg, #A7F3D0 0%, #10B981 50%, #059669 100%);
    border-color: #064E3B;
    color: #064E3B;
    box-shadow:
        0 4px 0 #064E3B,
        0 8px 16px rgba(16, 185, 129, 0.35);
}

.code-icon {
    font-size: 0.95rem;
}

.code-label {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    opacity: 0.85;
}

.code-number {
    font-size: 0.95rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    color: #1E1B4B;
}

.btn-quest-code.is-copied .code-number {
    color: #064E3B;
}

.code-badge-action {
    font-size: 0.62rem;
    font-weight: 900;
    background: rgba(0, 0, 0, 0.15);
    padding: 0.15rem 0.45rem;
    border-radius: 9999px;
    letter-spacing: 0.04em;
}



.btn-nav-create {
    position: relative;
    background: linear-gradient(180deg, #FFA94D 0%, #FF6B4A 48%, #E04322 100%);
    color: #FFFFFF;
    border: 2.5px solid #8C250E;
    border-radius: 9999px;
    padding: 0.55rem 1.35rem;
    font-family: var(--font-game);
    font-weight: 900;
    font-size: 0.86rem;
    letter-spacing: 0.04em;
    box-shadow:
        0 4.5px 0 #8C250E,
        0 10px 20px rgba(255, 107, 74, 0.4),
        inset 0 2px 2px rgba(255, 255, 255, 0.8),
        inset 0 -2px 4px rgba(140, 37, 14, 0.4);
    cursor: pointer;
    overflow: hidden;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transform: translate3d(0, 0, 0);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-nav-create::before {
    content: '';
    position: absolute;
    inset: 2px 6px auto 6px;
    height: 38%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.65) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.btn-nav-create:hover {
    transform: translate3d(0, -2px, 0) scale(1.02);
    box-shadow:
        0 6.5px 0 #8C250E,
        0 14px 26px rgba(255, 107, 74, 0.48),
        inset 0 2px 2px rgba(255, 255, 255, 0.9);
}

.btn-nav-create:active {
    transform: translate3d(0, 3px, 0) scaleX(1.03) scaleY(0.95);
    box-shadow:
        0 1.5px 0 #8C250E,
        inset 0 2px 4px rgba(140, 37, 14, 0.5);
}

.btn-nav-create:focus-visible {
    outline: 2px solid #FF6B4A;
    outline-offset: 3px;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(180deg, #FFFFFF 0%, #EEF4FD 100%);
    border: 2px solid var(--game-border);
    padding: 0.38rem 0.85rem;
    border-radius: 9999px;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 0.95),
        inset 0 -1.5px 2px rgba(165, 178, 208, 0.4);
}

.user-avatar {
    font-size: 1.15rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.15));
}

.user-name {
    font-family: var(--font-game);
    font-weight: 800;
    font-size: 0.84rem;
    color: var(--game-navy);
}



/* ==========================================================================
   STORYBOOK 3D MODALS WITH OVERLAPPING CRESTS
   ========================================================================== */
.modal-backdrop-layer {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(18, 24, 45, 0.68);
    backdrop-filter: blur(14px) saturate(140%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem 1.5rem 1.5rem;
    overflow-y: auto;
    box-sizing: border-box;
}

.modal-dialog {
    position: relative;
    width: 100%;
    max-inline-size: min(94vw, 920px);
    background: linear-gradient(165deg, #FFFFFF 0%, #FAF8F2 50%, #F1EBE0 100%);
    border: 3.5px solid var(--game-border);
    border-radius: 36px;
    padding: 2.5rem 2.25rem 2.25rem 2.25rem;
    box-shadow:
        0 14px 0 var(--game-border),
        0 28px 56px rgba(15, 23, 42, 0.38),
        inset 0 3px 2px rgba(255, 255, 255, 1),
        inset 0 -6px 12px rgba(160, 175, 210, 0.35);
    max-height: 88vh;
    overflow-y: auto;
    box-sizing: border-box;
}

.choice-dialog {
    max-inline-size: min(94vw, 780px);
}

.quiz-dialog {
    max-inline-size: min(94vw, 920px);
}

/* 3D Overlapping Storybook Crest Badge */
.modal-crest-badge {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: linear-gradient(145deg, #FFF9E6 0%, #FFD166 40%, #FF9F1C 100%);
    border: 3.5px solid #1D2440;
    box-shadow:
        0 5px 0 #1D2440,
        0 10px 22px rgba(255, 159, 28, 0.4),
        inset 0 2.5px 2px rgba(255, 255, 255, 1),
        inset 0 -3px 5px rgba(180, 100, 10, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 15;
    pointer-events: none;
}

.crest-icon {
    font-size: 1.85rem;
    filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.2));
}

/* 3D Round Close Button */
.modal-btn-close {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 42px;
    height: 42px;
    background: linear-gradient(180deg, #FFFFFF 0%, #FEE2E2 100%);
    border: 2.5px solid var(--game-border);
    border-radius: 50%;
    font-size: 1.15rem;
    font-weight: 900;
    color: #BE123C;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 3.5px 0 var(--game-border),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -2px 3px rgba(225, 29, 72, 0.3);
    transition: transform 0.24s var(--spring-bounce), box-shadow 0.2s ease;
    z-index: 10;
}

.modal-btn-close:hover {
    transform: rotate(90deg) scale(1.08);
    background: linear-gradient(180deg, #FFF1F2 0%, #FECDD3 100%);
    box-shadow:
        0 4.5px 0 var(--game-border),
        0 8px 16px rgba(225, 29, 72, 0.3);
}

.modal-btn-close:active {
    transform: rotate(90deg) scale(0.95);
    box-shadow: 0 1px 0 var(--game-border);
}

.modal-btn-close:focus-visible {
    outline: 2px solid #E11D48;
    outline-offset: 3px;
}

/* Dialog Nav Bar (Modal 2) */
.dialog-nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.btn-dialog-back {
    max-inline-size: fit-content;
    background: linear-gradient(180deg, #FFFFFF 0%, #EBF2FC 100%);
    border: 2px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.4rem 1.05rem;
    font-family: var(--font-game);
    font-weight: 800;
    font-size: 0.82rem;
    color: var(--game-navy);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1);
    transform: translate3d(0, 0, 0);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-dialog-back:hover {
    transform: translate3d(-2px, 0, 0);
    box-shadow: 0 4.5px 0 var(--game-border);
}

.btn-dialog-back:active {
    transform: translate3d(0, 0, 0);
    box-shadow: 0 1px 0 var(--game-border);
}

.btn-dialog-back:focus-visible {
    outline: 2px solid #2395EB;
    outline-offset: 3px;
}

/* Dialog Header */
.dialog-header {
    text-align: center;
    margin-bottom: 1.85rem;
}

.dialog-badge {
    display: inline-block;
    max-inline-size: fit-content;
    font-family: var(--font-game);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #0369A1;
    background: linear-gradient(180deg, #FFFFFF 0%, #E0F2FE 50%, #BAE6FD 100%);
    border: 2px solid #0284C7;
    padding: 0.25rem 0.9rem;
    border-radius: 9999px;
    margin-bottom: 0.65rem;
    box-shadow:
        0 3px 0 #0284C7,
        inset 0 1px 1px rgba(255, 255, 255, 0.9);
}

.dialog-title {
    font-family: var(--font-game);
    font-size: 1.95rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.4rem 0;
    text-shadow: 0 1.5px 0 rgba(255, 255, 255, 0.95);
}

.dialog-subtitle {
    font-size: 0.95rem;
    color: #64748B;
    margin: 0;
    font-weight: 700;
}

.dialog-subtitle strong {
    color: var(--game-navy);
}

/* ==========================================================================
   MODAL 1: 3D CLAY MILESTONE CHOICE CARDS
   ========================================================================== */
.choice-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.75rem;
}

.milestone-choice-card {
    position: relative;
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    padding: 1.85rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    user-select: none;
    box-shadow:
        0 8px 0 var(--game-border),
        0 16px 30px rgba(29, 36, 64, 0.14),
        inset 0 2.5px 2px rgba(255, 255, 255, 1),
        inset 0 -4px 8px rgba(160, 175, 210, 0.35);
    transform: translate3d(0, 0, 0);
    transition: transform 0.28s var(--spring-bounce), box-shadow 0.28s var(--spring-bounce);
}

.milestone-choice-card::before {
    content: '';
    position: absolute;
    inset: 3px 10px auto 10px;
    height: 38%;
    border-radius: 24px 24px 60px 60px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.75) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.milestone-choice-card:hover {
    transform: translate3d(0, -6px, 0) scale(1.02);
    box-shadow:
        0 14px 0 var(--game-border),
        0 24px 42px rgba(29, 36, 64, 0.2),
        inset 0 2.5px 2px rgba(255, 255, 255, 1);
}

.milestone-choice-card:active {
    transform: translate3d(0, 2px, 0) scale(0.985);
    box-shadow:
        0 3px 0 var(--game-border),
        inset 0 2px 4px rgba(160, 175, 210, 0.45);
}

.milestone-choice-card:focus-visible {
    outline: 3px solid #2395EB;
    outline-offset: 4px;
}

.card-lecture {
    background: linear-gradient(155deg, #FFFFFF 0%, #F0FDF8 50%, #D8F7EC 100%);
}

.card-quiz {
    background: linear-gradient(155deg, #FFFFFF 0%, #F0F7FF 50%, #DCEBFF 100%);
}

.choice-card-icon-wrap {
    width: 84px;
    height: 84px;
    border-radius: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.15rem;
    transition: transform 0.26s var(--spring-bounce);
}

.milestone-choice-card:hover .choice-card-icon-wrap {
    transform: scale(1.12) rotate(-5deg);
}

.bg-lecture {
    background: linear-gradient(145deg, #A7F3D0 0%, #34D399 50%, #059669 100%);
    border: 3px solid #064E3B;
    box-shadow:
        0 5px 0 #064E3B,
        0 10px 20px rgba(5, 150, 105, 0.35),
        inset 0 2.5px 2px rgba(255, 255, 255, 0.85),
        inset 0 -2.5px 4px rgba(6, 78, 59, 0.4);
}

.bg-quiz {
    background: linear-gradient(145deg, #BFDBFE 0%, #60A5FA 50%, #2563EB 100%);
    border: 3px solid #1E3A8A;
    box-shadow:
        0 5px 0 #1E3A8A,
        0 10px 20px rgba(37, 99, 235, 0.35),
        inset 0 2.5px 2px rgba(255, 255, 255, 0.85),
        inset 0 -2.5px 4px rgba(30, 58, 138, 0.4);
}

.choice-icon {
    font-size: 2.6rem;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
}

.choice-tag {
    display: inline-block;
    font-family: var(--font-game);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    margin-bottom: 0.6rem;
}

.tag-lecture {
    background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
    color: #065F46;
    border: 1.5px solid #059669;
    box-shadow: 0 2px 0 #059669, inset 0 1px 1px #fff;
}

.tag-quiz {
    background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
    color: #1E40AF;
    border: 1.5px solid #2563EB;
    box-shadow: 0 2px 0 #2563EB, inset 0 1px 1px #fff;
}

.choice-card-title {
    font-family: var(--font-game);
    font-size: 1.45rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.5rem 0;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.95);
}

.choice-card-desc {
    font-size: 0.86rem;
    color: #64748B;
    line-height: 1.5;
    margin: 0 0 1.35rem 0;
    min-height: 52px;
    font-weight: 700;
}

/* 3D Action Buttons inside Choice Cards */
.btn-3d-choice {
    position: relative;
    width: 100%;
    border-radius: 16px;
    padding: 0.8rem 1.15rem;
    font-family: var(--font-game);
    font-weight: 900;
    font-size: 0.92rem;
    color: #FFFFFF;
    cursor: pointer;
    overflow: hidden;
    transform: translate3d(0, 0, 0);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-3d-choice::before {
    content: '';
    position: absolute;
    inset: 2px 6px auto 6px;
    height: 38%;
    border-radius: 12px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.btn-choice-lecture {
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    border: 2.5px solid #064E3B;
    box-shadow:
        0 4.5px 0 #064E3B,
        0 10px 20px rgba(6, 214, 160, 0.35),
        inset 0 2px 2px rgba(255, 255, 255, 0.7);
}

.btn-choice-lecture:hover {
    transform: translate3d(0, -2px, 0);
    box-shadow:
        0 6.5px 0 #064E3B,
        0 14px 24px rgba(6, 214, 160, 0.45),
        inset 0 2px 2px rgba(255, 255, 255, 0.85);
}

.btn-choice-lecture:active {
    transform: translate3d(0, 3px, 0) scaleX(1.02) scaleY(0.96);
    box-shadow: 0 1.5px 0 #064E3B;
}

.btn-choice-lecture:focus-visible {
    outline: 2px solid #059669;
    outline-offset: 3px;
}

.btn-choice-quiz {
    background: linear-gradient(180deg, #60A5FA 0%, #1677C2 100%);
    border: 2.5px solid #0E5287;
    box-shadow:
        0 4.5px 0 #0E5287,
        0 10px 20px rgba(35, 149, 235, 0.35),
        inset 0 2px 2px rgba(255, 255, 255, 0.7);
}

.btn-choice-quiz:hover {
    transform: translate3d(0, -2px, 0);
    box-shadow:
        0 6.5px 0 #0E5287,
        0 14px 24px rgba(35, 149, 235, 0.45),
        inset 0 2px 2px rgba(255, 255, 255, 0.85);
}

.btn-choice-quiz:active {
    transform: translate3d(0, 3px, 0) scaleX(1.02) scaleY(0.96);
    box-shadow: 0 1.5px 0 #0E5287;
}

.btn-choice-quiz:focus-visible {
    outline: 2px solid #1677C2;
    outline-offset: 3px;
}

/* ==========================================================================
   MODAL 2: 6 QUIZ FORMAT 3D NEUMORPHIC CARDS
   ========================================================================== */
.quiz-types-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.15rem;
    margin-bottom: 1.75rem;
}

.quiz-type-card {
    position: relative;
    border: 3px solid var(--game-border);
    border-radius: 24px;
    padding: 1.25rem 1.15rem;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    user-select: none;
    box-shadow:
        0 5px 0 var(--game-border),
        0 10px 20px rgba(29, 36, 64, 0.1),
        inset 0 2px 2px rgba(255, 255, 255, 1),
        inset 0 -2px 4px rgba(160, 175, 210, 0.35);
    transform: translate3d(0, 0, 0);
    transition: transform 0.25s var(--spring-bounce), box-shadow 0.25s var(--spring-bounce), border-color 0.2s ease;
}

.quiz-type-card::before {
    content: '';
    position: absolute;
    inset: 2px 6px auto 6px;
    height: 32%;
    border-radius: 20px 20px 40px 40px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.65) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.quiz-type-card:hover {
    transform: translate3d(0, -4px, 0) scale(1.02);
    box-shadow:
        0 9px 0 var(--game-border),
        0 18px 28px rgba(29, 36, 64, 0.16),
        inset 0 2px 2px rgba(255, 255, 255, 1);
}

.quiz-type-card.is-selected {
    transform: translate3d(0, -3px, 0) scale(1.03);
    border-color: #F59E0B;
    background: linear-gradient(155deg, #FFFDF8 0%, #FFF3DC 100%) !important;
    box-shadow:
        0 0 0 3px #FFFFFF,
        0 0 0 6px #F59E0B,
        0 8px 0 #D97706,
        0 14px 28px rgba(245, 158, 11, 0.35);
}

.quiz-type-card:focus-visible {
    outline: 3px solid #2395EB;
    outline-offset: 3px;
}

/* Biome theme tints for the 6 quizzes */
.theme-orange {
    background: linear-gradient(155deg, #FFFFFF 0%, #FFF8F1 50%, #FEEDDC 100%);
}
.theme-green {
    background: linear-gradient(155deg, #FFFFFF 0%, #F1FDF6 50%, #DCFCE7 100%);
}
.theme-blue {
    background: linear-gradient(155deg, #FFFFFF 0%, #F1F8FE 50%, #DBEAFE 100%);
}
.theme-purple {
    background: linear-gradient(155deg, #FFFFFF 0%, #F9F5FE 50%, #F3E8FF 100%);
}
.theme-rose {
    background: linear-gradient(155deg, #FFFFFF 0%, #FFF4F6 50%, #FFE4E6 100%);
}
.theme-amber {
    background: linear-gradient(155deg, #FFFFFF 0%, #FFFBF1 50%, #FEF3C7 100%);
}

.quiz-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.quiz-icon-pod {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: linear-gradient(180deg, #FFFFFF 0%, #EEF4FD 100%);
    border: 2px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -1.5px 2px rgba(160, 175, 205, 0.35);
    transition: transform 0.25s var(--spring-bounce);
}

.quiz-type-card:hover .quiz-icon-pod {
    transform: scale(1.12) rotate(6deg);
}

.quiz-emoji {
    font-size: 1.55rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.15));
}

.quiz-badge-pill {
    font-family: var(--font-game);
    font-size: 0.65rem;
    font-weight: 900;
    padding: 0.18rem 0.55rem;
    border-radius: 9999px;
    letter-spacing: 0.03em;
}

.theme-orange .quiz-badge-pill { background: #FFEDD5; color: #C2410C; border: 1.5px solid #F97316; }
.theme-green .quiz-badge-pill { background: #DCFCE7; color: #15803D; border: 1.5px solid #22C55E; }
.theme-blue .quiz-badge-pill { background: #DBEAFE; color: #1D4ED8; border: 1.5px solid #3B82F6; }
.theme-purple .quiz-badge-pill { background: #F3E8FF; color: #7E22CE; border: 1.5px solid #A855F7; }
.theme-rose .quiz-badge-pill { background: #FFE4E6; color: #BE123C; border: 1.5px solid #F43F5E; }
.theme-amber .quiz-badge-pill { background: #FEF3C7; color: #B45309; border: 1.5px solid #F59E0B; }

.quiz-type-title {
    font-family: var(--font-game);
    font-size: 1.15rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.35rem 0;
    line-height: 1.2;
}

.quiz-type-desc {
    font-size: 0.8rem;
    color: #64748B;
    line-height: 1.45;
    margin: 0 0 0.95rem 0;
    flex-grow: 1;
    font-weight: 700;
}

.quiz-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px dashed rgba(43, 51, 84, 0.18);
    padding-top: 0.65rem;
}

.quiz-xp-chip {
    font-family: var(--font-game);
    font-size: 0.74rem;
    font-weight: 900;
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    color: #92400E;
    border: 1.5px solid #F59E0B;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
    box-shadow: 0 2px 0 #D97706, inset 0 1px 1px #fff;
}

.quiz-select-arrow {
    font-family: var(--font-game);
    font-size: 0.76rem;
    font-weight: 900;
    color: #2563EB;
    transition: transform 0.2s ease;
}

.quiz-type-card:hover .quiz-select-arrow {
    transform: translateX(3px);
}

/* Quiz Selected 3D Action Banner */
.quiz-selected-banner {
    background: linear-gradient(155deg, #FFF7ED 0%, #FFEDD5 100%);
    border: 3px solid #F97316;
    border-radius: 24px;
    padding: 1.1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.25rem;
    box-shadow:
        0 6px 0 #EA580C,
        0 12px 24px rgba(234, 88, 12, 0.22),
        inset 0 2px 2px rgba(255, 255, 255, 0.95);
}

.banner-info {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.banner-icon {
    font-size: 2.2rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
}

.banner-title {
    font-family: var(--font-game);
    font-size: 1.05rem;
    font-weight: 900;
    color: #9A3412;
}

.banner-sub {
    font-size: 0.8rem;
    color: #C2410C;
    margin: 0.15rem 0 0 0;
    font-weight: 700;
}

.btn-3d-launch-quiz {
    position: relative;
    background: linear-gradient(180deg, #FFA94D 0%, #FF6B4A 100%);
    color: #FFFFFF;
    border: 2.5px solid #8C250E;
    border-radius: 16px;
    padding: 0.75rem 1.45rem;
    font-family: var(--font-game);
    font-weight: 900;
    font-size: 0.9rem;
    letter-spacing: 0.03em;
    overflow: hidden;
    box-shadow:
        0 4px 0 #8C250E,
        0 10px 20px rgba(255, 107, 74, 0.35),
        inset 0 2px 2px rgba(255, 255, 255, 0.7);
    cursor: pointer;
    white-space: nowrap;
    transform: translate3d(0, 0, 0);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-3d-launch-quiz::before {
    content: '';
    position: absolute;
    inset: 2px 6px auto 6px;
    height: 38%;
    border-radius: 12px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.btn-3d-launch-quiz:hover {
    transform: translate3d(0, -2px, 0);
    box-shadow:
        0 6px 0 #8C250E,
        0 14px 26px rgba(255, 107, 74, 0.45);
}

.btn-3d-launch-quiz:active {
    transform: translate3d(0, 2px, 0) scaleX(1.02) scaleY(0.96);
    box-shadow: 0 1.5px 0 #8C250E;
}

.btn-3d-launch-quiz:focus-visible {
    outline: 2px solid #FF6B4A;
    outline-offset: 3px;
}

/* Toast Notice 3D Pill */
.quiz-notice-toast {
    position: fixed;
    top: 5.5rem;
    right: 2rem;
    z-index: 120;
    background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%);
    color: #FFFFFF;
    border: 3px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: var(--font-game);
    font-size: 0.92rem;
    font-weight: 900;
    box-shadow:
        0 6px 0 #B45309,
        0 14px 30px rgba(0, 0, 0, 0.35),
        inset 0 2px 2px rgba(255, 255, 255, 0.25);
}

/* Modal Animations */
.modal-bounce-enter-active {
    animation: modalBounceIn 0.35s var(--spring-bounce);
}

.modal-bounce-leave-active {
    animation: modalBounceIn 0.22s ease-in reverse;
}

@keyframes modalBounceIn {
    from {
        opacity: 0;
        transform: scale(0.88) translateY(24px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.toast-slide-enter-active {
    animation: toastSlide 0.3s var(--spring-bounce);
}

.toast-slide-leave-active {
    animation: toastSlide 0.25s ease-in reverse;
}

@keyframes toastSlide {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 900px) {
    .nav-center-module {
        display: none;
    }

    .quiz-types-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .brand-group {
        display: none;
    }

    .module-header-nav {
        padding: 0.75rem 1rem;
    }


    .choice-cards-grid {
        grid-template-columns: 1fr;
    }

    .quiz-types-grid {
        grid-template-columns: 1fr;
    }

    .modal-dialog {
        padding: 2.2rem 1.25rem 1.5rem 1.25rem;
        border-radius: 28px;
    }

    .dialog-title {
        font-size: 1.5rem;
    }

    .quiz-selected-banner {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }

    .banner-info {
        flex-direction: column;
    }
}

/* Accessibility & Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
