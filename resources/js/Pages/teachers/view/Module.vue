<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ReadingJourneyMap from '@/Components/ReadingJourneyMap.vue';

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

const handleBack = () => {
    router.visit('/teachers/dashboard');
};

const handleLogout = () => {
    router.post('/logout');
};

const openCreateChoiceModal = () => {
    selectedQuiz.value = null;
    quizNotice.value = null;
    showCreateChoiceModal.value = true;
    showQuizTypesModal.value = false;
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
    if (quiz.id === 'multiple-choice') {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/multiple-choice/create`);
        return;
    }

    if (quiz.id === 'color-the-conflict') {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/color-the-conflict/create`);
        return;
    }

    if (quiz.id === 'the-conflict-map') {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/the-conflict-map/create`);
        return;
    }

    if (quiz.id === 'matching-type') {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/matching-type/create`);
        return;
    }

    if (quiz.id === 'evaluation') {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/evaluation/create`);
        return;
    }

    if (quiz.id === 'reflection') {
        closeAllModals();
        router.visit(`/teachers/modules/${props.module.slug}/quizzes/reflection/create`);
        return;
    }

    quizNotice.value = `${quiz.name} challenge format selected! Studio coming soon.`;
    setTimeout(() => {
        quizNotice.value = null;
    }, 3500);
};
</script>

<template>
    <Head :title="`${module.title} • Reading Journey Map`" />

    <div class="module-view-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Ambient Floating Sparkles -->
        <div class="game-bg-elements" aria-hidden="true">
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">⭐</div>
            <div class="floating-sparkle sp-3">✨</div>
            <div class="floating-sparkle sp-4">🌟</div>
        </div>

        <!-- Top Game Header Bar matching Dashboard conventions -->
        <header class="module-header-nav">
            <div class="nav-left">
                <button type="button" class="btn-back-nav" @click="handleBack">
                    <span class="back-arrow">❮</span>
                    <span>Dashboard</span>
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

                <button type="button" class="btn-logout-mini" @click="handleLogout" title="Log Out">
                    <span>Exit</span>
                </button>
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
   3D CARTOON + NEUMORPHIC DESIGN SYSTEM
   ========================================================================== */
.module-view-page {
    --game-navy: #1D2440;
    --game-border: #2B3354;
    --neu-bg: #E6EFF9;
    --neu-surface: #F3F7FC;
    --neu-light: #FFFFFF;
    --neu-dark: #C6D3E5;
    --neu-shadow: rgba(29, 36, 64, 0.16);
    --neu-bevel-light: inset 2.5px 2.5px 5px rgba(255, 255, 255, 0.95);
    --neu-bevel-dark: inset -2.5px -2.5px 5px rgba(160, 175, 205, 0.42);
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    min-height: 100vh;
    background: #E0F2FE;
    font-family: 'Nunito', system-ui, sans-serif;
    color: var(--game-navy);
    position: relative;
    overflow-x: hidden;
}

/* Ambient Floating Background Sparkles */
.game-bg-elements {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
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
   3D CARTOON HUD TOP BAR
   ========================================================================== */
.module-header-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1.85rem;
    background: linear-gradient(180deg, #FFFFFF 0%, #EEF4FD 100%);
    border-bottom: 3.5px solid var(--game-border);
    box-shadow:
        0 6px 0 var(--game-border),
        0 14px 28px rgba(29, 36, 64, 0.12),
        inset 0 2px 0 rgba(255, 255, 255, 0.95),
        inset 0 -3px 5px rgba(175, 188, 218, 0.35);
    position: sticky;
    top: 0;
    z-index: 50;
    gap: 1.25rem;
}

.nav-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.btn-back-nav {
    background: linear-gradient(145deg, #FFFFFF 0%, #E8EEF8 100%);
    border: 2.5px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.45rem 1.05rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.84rem;
    color: var(--game-navy);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    box-shadow:
        0 3.5px 0 var(--game-border),
        0 4px 10px rgba(29, 36, 64, 0.08),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.95),
        inset -1.5px -1.5px 3px rgba(165, 178, 208, 0.4);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-back-nav:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5.5px 0 var(--game-border),
        0 8px 16px rgba(29, 36, 64, 0.12),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 1);
}

.btn-back-nav:active {
    transform: translateY(2px);
    box-shadow:
        0 1.5px 0 var(--game-border),
        inset 2px 2px 4px rgba(165, 178, 208, 0.45);
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
    font-family: 'Fredoka', sans-serif;
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
    font-family: 'Fredoka', sans-serif;
    font-size: 0.68rem;
    font-weight: 900;
    padding: 0.18rem 0.55rem;
    border-radius: 9999px;
    letter-spacing: 0.04em;
    box-shadow:
        0 2px 0 #D97706,
        inset 1px 1px 2px rgba(255, 255, 255, 0.8);
}

/* Center Module Info Console */
.nav-center-module {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    background: #E8F0FA;
    border: 2.5px solid var(--game-border);
    padding: 0.35rem 0.95rem;
    border-radius: 9999px;
    max-width: 380px;
    box-shadow:
        inset 2.5px 2.5px 5px rgba(29, 36, 64, 0.2),
        inset -2.5px -2.5px 5px rgba(255, 255, 255, 0.95),
        0 1px 0 rgba(255, 255, 255, 0.85);
}

.module-icon-pill {
    font-size: 1.2rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.15));
}

.module-nav-title {
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 0.9rem;
    color: var(--game-navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.module-lecture-count {
    background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
    color: #0369A1;
    border: 1.5px solid #38BDF8;
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 0.72rem;
    padding: 0.18rem 0.6rem;
    border-radius: 9999px;
    box-shadow:
        0 2px 0 #0284C7,
        inset 1px 1px 2px rgba(255, 255, 255, 0.8);
    white-space: nowrap;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

/* Extruded 3D + Create Button */
.btn-nav-create {
    background: linear-gradient(145deg, #FFB300 0%, #FF6B4A 100%);
    color: #FFFFFF;
    border: 2.5px solid #8C250E;
    border-radius: 9999px;
    padding: 0.5rem 1.25rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 0.85rem;
    letter-spacing: 0.04em;
    box-shadow:
        0 4px 0 #8C250E,
        0 8px 18px rgba(255, 107, 74, 0.38),
        inset 2px 2px 4px rgba(255, 255, 255, 0.7),
        inset -2px -2px 4px rgba(140, 37, 14, 0.4);
    cursor: pointer;
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.btn-nav-create:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #8C250E,
        0 12px 24px rgba(255, 107, 74, 0.48),
        inset 2px 2px 4px rgba(255, 255, 255, 0.85);
}

.btn-nav-create:active {
    transform: translateY(2px);
    box-shadow:
        0 1px 0 #8C250E,
        inset 2px 2px 4px rgba(140, 37, 14, 0.5);
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(145deg, #FFFFFF 0%, #EEF4FD 100%);
    border: 2px solid var(--game-border);
    padding: 0.38rem 0.85rem;
    border-radius: 9999px;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.95),
        inset -1.5px -1.5px 3px rgba(165, 178, 208, 0.4);
}

.user-avatar {
    font-size: 1.15rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.15));
}

.user-name {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.84rem;
    color: var(--game-navy);
}

.btn-logout-mini {
    background: linear-gradient(145deg, #FFF1F2 0%, #FFE4E6 100%);
    color: #BE123C;
    border: 2px solid #E11D48;
    padding: 0.38rem 0.85rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.8rem;
    cursor: pointer;
    box-shadow:
        0 3px 0 #9F1239,
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.95),
        inset -1.5px -1.5px 3px rgba(225, 29, 72, 0.25);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-logout-mini:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5px 0 #9F1239,
        0 8px 16px rgba(225, 29, 72, 0.25);
}

.btn-logout-mini:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #9F1239;
}

/* ==========================================================================
   3D CARTOON NEUMORPHIC MODALS
   ========================================================================== */
.modal-backdrop-layer {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    overflow-y: auto;
}

.modal-dialog {
    position: relative;
    width: 100%;
    background: linear-gradient(160deg, #FFFFFF 0%, #F5F9FE 55%, #EAF1FA 100%);
    border: 4px solid var(--game-border);
    border-radius: 36px;
    padding: 2.25rem 2.25rem 2rem 2.25rem;
    box-shadow:
        0 16px 0 var(--game-border),
        0 28px 60px rgba(15, 23, 42, 0.42),
        inset 3px 3px 6px rgba(255, 255, 255, 0.98),
        inset -4px -4px 8px rgba(160, 175, 210, 0.45);
    max-height: 90vh;
    overflow-y: auto;
}

.choice-dialog {
    max-width: 780px;
}

.quiz-dialog {
    max-width: 890px;
}

/* 3D Round Close Button */
.modal-btn-close {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 40px;
    height: 40px;
    background: linear-gradient(145deg, #FFFFFF 0%, #FEE2E2 100%);
    border: 2.5px solid var(--game-border);
    border-radius: 50%;
    font-size: 1.1rem;
    font-weight: 900;
    color: #BE123C;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 3.5px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(225, 29, 72, 0.3);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
    z-index: 10;
}

.modal-btn-close:hover {
    transform: rotate(90deg) scale(1.08);
    background: linear-gradient(145deg, #FFF1F2 0%, #FECDD3 100%);
    box-shadow:
        0 4.5px 0 var(--game-border),
        0 8px 16px rgba(225, 29, 72, 0.3);
}

.modal-btn-close:active {
    transform: rotate(90deg) scale(0.95);
    box-shadow: 0 1px 0 var(--game-border);
}

/* Dialog Nav Bar (Modal 2) */
.dialog-nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.btn-dialog-back {
    background: linear-gradient(145deg, #FFFFFF 0%, #E8EEF8 100%);
    border: 2px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.4rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.8rem;
    color: var(--game-navy);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.95);
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-dialog-back:hover {
    transform: translateX(-2px);
    box-shadow: 0 4.5px 0 var(--game-border);
}

.btn-dialog-back:active {
    transform: translateX(0);
    box-shadow: 0 1px 0 var(--game-border);
}

/* Dialog Header */
.dialog-header {
    text-align: center;
    margin-bottom: 2rem;
}

.dialog-badge {
    display: inline-block;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.74rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #0369A1;
    background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
    border: 2px solid #0284C7;
    padding: 0.25rem 0.85rem;
    border-radius: 9999px;
    margin-bottom: 0.65rem;
    box-shadow:
        0 3px 0 #0284C7,
        inset 1px 1px 2px rgba(255, 255, 255, 0.85);
}

.dialog-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.95rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.4rem 0;
    text-shadow: 1px 2px 0 rgba(255, 255, 255, 0.9);
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
    grid-template-columns: repeat(2, 1fr);
    gap: 1.75rem;
}

.milestone-choice-card {
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    padding: 1.75rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    user-select: none;
    position: relative;
    box-shadow:
        0 8px 0 var(--game-border),
        0 14px 26px rgba(29, 36, 64, 0.14),
        inset 2.5px 2.5px 5px rgba(255, 255, 255, 0.95),
        inset -3px -3px 6px rgba(160, 175, 210, 0.35);
    transition: transform 0.28s var(--spring-bounce), box-shadow 0.28s var(--spring-bounce);
}

.milestone-choice-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow:
        0 14px 0 var(--game-border),
        0 22px 38px rgba(29, 36, 64, 0.2),
        inset 2.5px 2.5px 5px rgba(255, 255, 255, 1);
}

.milestone-choice-card:active {
    transform: translateY(2px) scale(0.985);
    box-shadow:
        0 3px 0 var(--game-border),
        inset 3px 3px 6px rgba(160, 175, 210, 0.45);
}

.card-lecture {
    background: linear-gradient(155deg, #FFFFFF 0%, #F0FDF8 60%, #E0F7EF 100%);
}

.card-quiz {
    background: linear-gradient(155deg, #FFFFFF 0%, #F0F7FF 60%, #E3EFFF 100%);
}

.choice-card-icon-wrap {
    width: 82px;
    height: 82px;
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.15rem;
    transition: transform 0.25s var(--spring-bounce);
}

.milestone-choice-card:hover .choice-card-icon-wrap {
    transform: scale(1.1) rotate(-4deg);
}

.bg-lecture {
    background: linear-gradient(135deg, #A7F3D0 0%, #34D399 50%, #059669 100%);
    border: 3px solid #064E3B;
    box-shadow:
        0 5px 0 #064E3B,
        0 8px 18px rgba(5, 150, 105, 0.35),
        inset 2.5px 2.5px 5px rgba(255, 255, 255, 0.85),
        inset -2.5px -2.5px 5px rgba(6, 78, 59, 0.4);
}

.bg-quiz {
    background: linear-gradient(135deg, #BFDBFE 0%, #60A5FA 50%, #2563EB 100%);
    border: 3px solid #1E3A8A;
    box-shadow:
        0 5px 0 #1E3A8A,
        0 8px 18px rgba(37, 99, 235, 0.35),
        inset 2.5px 2.5px 5px rgba(255, 255, 255, 0.85),
        inset -2.5px -2.5px 5px rgba(30, 58, 138, 0.4);
}

.choice-icon {
    font-size: 2.6rem;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
}

.choice-tag {
    display: inline-block;
    font-family: 'Fredoka', sans-serif;
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
    box-shadow: 0 2px 0 #059669, inset 1px 1px 2px #fff;
}

.tag-quiz {
    background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
    color: #1E40AF;
    border: 1.5px solid #2563EB;
    box-shadow: 0 2px 0 #2563EB, inset 1px 1px 2px #fff;
}

.choice-card-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.45rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.5rem 0;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.9);
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
    width: 100%;
    border-radius: 16px;
    padding: 0.8rem 1.15rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 0.92rem;
    color: #FFFFFF;
    cursor: pointer;
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-choice-lecture {
    background: linear-gradient(145deg, #06D6A0 0%, #059669 100%);
    border: 2.5px solid #064E3B;
    box-shadow:
        0 4.5px 0 #064E3B,
        0 8px 16px rgba(6, 214, 160, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.7),
        inset -2px -2px 4px rgba(6, 78, 59, 0.35);
}

.btn-choice-lecture:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6.5px 0 #064E3B,
        0 12px 22px rgba(6, 214, 160, 0.45),
        inset 2px 2px 4px rgba(255, 255, 255, 0.85);
}

.btn-choice-lecture:active {
    transform: translateY(2px);
    box-shadow: 0 1.5px 0 #064E3B;
}

.btn-choice-quiz {
    background: linear-gradient(145deg, #38A3F5 0%, #1677C2 100%);
    border: 2.5px solid #0E5287;
    box-shadow:
        0 4.5px 0 #0E5287,
        0 8px 16px rgba(35, 149, 235, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.7),
        inset -2px -2px 4px rgba(14, 82, 135, 0.35);
}

.btn-choice-quiz:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6.5px 0 #0E5287,
        0 12px 22px rgba(35, 149, 235, 0.45),
        inset 2px 2px 4px rgba(255, 255, 255, 0.85);
}

.btn-choice-quiz:active {
    transform: translateY(2px);
    box-shadow: 0 1.5px 0 #0E5287;
}

/* ==========================================================================
   MODAL 2: 6 QUIZ FORMAT 3D NEUMORPHIC CARDS
   ========================================================================== */
.quiz-types-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.15rem;
    margin-bottom: 1.75rem;
}

.quiz-type-card {
    border: 3px solid var(--game-border);
    border-radius: 24px;
    padding: 1.25rem 1.15rem;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    user-select: none;
    box-shadow:
        0 5px 0 var(--game-border),
        0 8px 18px rgba(29, 36, 64, 0.1),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(160, 175, 210, 0.35);
    transition: transform 0.25s var(--spring-bounce), box-shadow 0.25s var(--spring-bounce), border-color 0.2s ease;
}

.quiz-type-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow:
        0 9px 0 var(--game-border),
        0 16px 26px rgba(29, 36, 64, 0.16),
        inset 2px 2px 4px rgba(255, 255, 255, 1);
}

.quiz-type-card.is-selected {
    transform: translateY(-3px) scale(1.03);
    border-color: #FF8C00;
    background: linear-gradient(155deg, #FFFDF8 0%, #FFF3DC 100%) !important;
    box-shadow:
        0 7px 0 #D97706,
        0 0 20px rgba(255, 140, 0, 0.4),
        inset 2.5px 2.5px 5px rgba(255, 255, 255, 1);
}

/* Biome theme tints for the 6 quizzes */
.theme-orange {
    background: linear-gradient(155deg, #FFFFFF 0%, #FFF8F1 60%, #FEEDDC 100%);
}
.theme-green {
    background: linear-gradient(155deg, #FFFFFF 0%, #F1FDF6 60%, #DCFCE7 100%);
}
.theme-blue {
    background: linear-gradient(155deg, #FFFFFF 0%, #F1F8FE 60%, #DBEAFE 100%);
}
.theme-purple {
    background: linear-gradient(155deg, #FFFFFF 0%, #F9F5FE 60%, #F3E8FF 100%);
}
.theme-rose {
    background: linear-gradient(155deg, #FFFFFF 0%, #FFF4F6 60%, #FFE4E6 100%);
}
.theme-amber {
    background: linear-gradient(155deg, #FFFFFF 0%, #FFFBF1 60%, #FEF3C7 100%);
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
    background: linear-gradient(145deg, #FFFFFF 0%, #EEF4FD 100%);
    border: 2px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.95),
        inset -1.5px -1.5px 3px rgba(160, 175, 205, 0.35);
    transition: transform 0.25s var(--spring-bounce);
}

.quiz-type-card:hover .quiz-icon-pod {
    transform: scale(1.1) rotate(6deg);
}

.quiz-emoji {
    font-size: 1.55rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.15));
}

.quiz-badge-pill {
    font-family: 'Fredoka', sans-serif;
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
    font-family: 'Fredoka', sans-serif;
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
    font-family: 'Fredoka', sans-serif;
    font-size: 0.74rem;
    font-weight: 900;
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    color: #92400E;
    border: 1.5px solid #F59E0B;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
    box-shadow: 0 2px 0 #D97706, inset 1px 1px 2px #fff;
}

.quiz-select-arrow {
    font-family: 'Fredoka', sans-serif;
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
    background: linear-gradient(145deg, #FFF7ED 0%, #FFEDD5 100%);
    border: 3px solid #F97316;
    border-radius: 24px;
    padding: 1.1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.25rem;
    box-shadow:
        0 6px 0 #EA580C,
        0 10px 24px rgba(234, 88, 12, 0.22),
        inset 2px 2px 4px rgba(255, 255, 255, 0.85);
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
    font-family: 'Fredoka', sans-serif;
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
    background: linear-gradient(145deg, #FF9F43 0%, #FF6B4A 100%);
    color: #FFFFFF;
    border: 2.5px solid #8C250E;
    border-radius: 16px;
    padding: 0.75rem 1.45rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 0.9rem;
    letter-spacing: 0.03em;
    box-shadow:
        0 4px 0 #8C250E,
        0 8px 18px rgba(255, 107, 74, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.7);
    cursor: pointer;
    white-space: nowrap;
    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce);
}

.btn-3d-launch-quiz:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #8C250E,
        0 12px 24px rgba(255, 107, 74, 0.45);
}

.btn-3d-launch-quiz:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #8C250E;
}

/* Toast Notice 3D Pill */
.quiz-notice-toast {
    position: fixed;
    top: 5.5rem;
    right: 2rem;
    z-index: 120;
    background: linear-gradient(145deg, #1E293B 0%, #0F172A 100%);
    color: #FFFFFF;
    border: 3px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.92rem;
    font-weight: 900;
    box-shadow:
        0 6px 0 #B45309,
        0 14px 30px rgba(0, 0, 0, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.25);
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
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .brand-group {
        display: none;
    }

    .module-header-nav {
        padding: 0.75rem 1rem;
    }

    .btn-nav-create span {
        font-size: 0.74rem;
    }

    .choice-cards-grid {
        grid-template-columns: 1fr;
    }

    .quiz-types-grid {
        grid-template-columns: 1fr;
    }

    .modal-dialog {
        padding: 1.6rem 1.25rem;
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
</style>
