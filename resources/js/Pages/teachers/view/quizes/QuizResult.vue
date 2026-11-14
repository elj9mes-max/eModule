<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { flushAllClientCache, flushCacheTags, prefetchRoute } from '@/Composables/usePrefetch';
import QuizAnswerInspector from '@/Components/QuizAnswerInspector.vue';
import { csrfHeaders } from '@/Services/csrf';

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
            icon: '📘',
            theme: 'forest',
            grade_level: 'Grade 3 • Reading Legends',
        }),
    },
    quiz: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Quiz Challenge',
            slug: 'quiz-challenge',
            type: 'multiple_choice',
            description: '',
            reading_passage: '',
            contexts: [],
            xp_reward: 150,
            passing_percentage: 70,
            time_limit_seconds: null,
            sort_order: 1,
            is_published: true,
            questions_count: 0,
            questions: [],
            evaluation_items: [],
            reflection_prompts: [],
        }),
    },
    analytics: {
        type: Object,
        default: () => ({
            total_attempts: 0,
            unique_students: 0,
            pending_review_count: 0,
            graded_count: 0,
            passed_count: 0,
            average_score: 0,
            average_percentage: 0,
            pass_rate: 0,
            highest_score: 0,
            lowest_score: 0,
        }),
    },
    submissions: {
        type: Array,
        default: () => [],
    },
});

// Navigation
const goToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const goToStudio = () => {
    router.visit(`/teachers/modules/${props.module.slug}/quizzes/studio?type=${props.quiz.type}`);
};

const handleLogout = () => {
    flushAllClientCache();
    router.post('/logout');
};

// Main Active Tab: 'submissions' | 'blueprint'
const activeMainTab = ref('submissions');

// Filters for Submissions
const activeStatusFilter = ref('all'); // 'all' | 'pending_review' | 'passed' | 'failed'
const searchQuery = ref('');

const filteredSubmissions = computed(() => {
    return props.submissions.filter((sub) => {
        // Status filter
        if (activeStatusFilter.value === 'pending_review' && sub.status !== 'pending_review') {
            return false;
        }
        if (activeStatusFilter.value === 'passed' && (sub.status !== 'completed' || !sub.is_passed)) {
            return false;
        }
        if (activeStatusFilter.value === 'failed' && (sub.status !== 'completed' || sub.is_passed)) {
            return false;
        }

        // Search query
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            const studentName = sub.student?.name?.toLowerCase() || '';
            const studentEmail = sub.student?.email?.toLowerCase() || '';
            if (!studentName.includes(q) && !studentEmail.includes(q)) {
                return false;
            }
        }

        return true;
    });
});

// Format Badge Label & Icon
const quizTypeMeta = computed(() => {
    switch (props.quiz.type) {
        case 'evaluation':
            return { label: 'Evaluation & Stance Rubric', icon: '📊', colorClass: 'badge-eval' };
        case 'reflection':
            return { label: 'Open-Ended Reflection', icon: '💭', colorClass: 'badge-reflect' };
        case 'color_the_conflict':
            return { label: 'Color the Conflict', icon: '🎨', colorClass: 'badge-color' };
        case 'the_conflict_map':
            return { label: 'The Conflict Map', icon: '🗺️', colorClass: 'badge-map' };
        case 'matching_type':
            return { label: 'Concept Matching Type', icon: '🧩', colorClass: 'badge-match' };
        default:
            return { label: 'Multiple Choice Challenge', icon: '🔘', colorClass: 'badge-mcq' };
    }
});

const isSubjectiveQuiz = computed(() => {
    return props.quiz.type === 'evaluation' || props.quiz.type === 'reflection';
});

const itemTypeLabels = {
    multiple_choice: 'Multiple Choice',
    color_conflict: 'Color the Conflict',
    conflict_map: 'The Conflict Map',
    matching: 'Matching Type',
    evaluation: 'Evaluation',
    reflection: 'Reflection',
};

const getAnswerItemLabel = (ans) => {
    return itemTypeLabels[ans?.item_type] || 'Question';
};

const getAnswerResultMeta = (ans) => {
    if (activeGradingAttempt.value?.status === 'pending_review') {
        return {
            blockClass: 'block-pending',
            badgeClass: 'status-pill-pending',
            label: `Pending Grade • ${ans.points_earned ?? 0} / ${ans.points_possible} pts`,
        };
    }

    const earned = Number(ans.points_earned) || 0;
    const possible = Number(ans.points_possible) || 0;

    if (possible > 0 && earned >= possible) {
        return {
            blockClass: 'block-correct',
            badgeClass: 'status-pill-correct',
            label: `✓ Correct • ${earned} / ${possible} pts`,
        };
    }

    if (earned > 0) {
        return {
            blockClass: 'block-partial',
            badgeClass: 'status-pill-partial',
            label: `◐ Partial • ${earned} / ${possible} pts`,
        };
    }

    return {
        blockClass: 'block-wrong',
        badgeClass: 'status-pill-wrong',
        label: `✕ Incorrect • 0 / ${possible} pts`,
    };
};

// Interactive Grading Modal State
const activeGradingAttempt = ref(null);
const gradingAnswers = ref([]);
const teacherFeedbackText = ref('');
const isSubmittingGrade = ref(false);
const showToast = ref(false);
const toastMessage = ref('');

const openGradingModal = (sub) => {
    activeGradingAttempt.value = sub;
    teacherFeedbackText.value = sub.teacher_feedback || '';
    gradingAnswers.value = (sub.answers || []).map((ans) => ({
        id: ans.id,
        item_type: ans.item_type,
        item_id: ans.item_id,
        answer_data: ans.answer_data,
        answer_text: ans.answer_text,
        is_correct: ans.is_correct,
        points_earned: sub.status === 'completed' ? ans.points_earned : (ans.points_possible ?? 10),
        points_possible: ans.points_possible ?? 10,
        teacher_notes: ans.teacher_notes || '',
        context: ans.context,
    }));
};

const closeGradingModal = () => {
    activeGradingAttempt.value = null;
    gradingAnswers.value = [];
    teacherFeedbackText.value = '';
};

const handleModalKeydown = (event) => {
    if (event.key === 'Escape' && activeGradingAttempt.value) {
        closeGradingModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleModalKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleModalKeydown);
});

// Quick Points Setter
const setAnswerPoints = (index, points) => {
    if (gradingAnswers.value[index]) {
        gradingAnswers.value[index].points_earned = points;
    }
};

// Live Modal Scoring Previews
const liveTotalEarned = computed(() => {
    return gradingAnswers.value.reduce((acc, curr) => acc + (Number(curr.points_earned) || 0), 0);
});

const liveTotalPossible = computed(() => {
    const sum = gradingAnswers.value.reduce((acc, curr) => acc + (Number(curr.points_possible) || 0), 0);
    return Math.max(1, sum || activeGradingAttempt.value?.max_score || 1);
});

const livePercentage = computed(() => {
    return Math.round((liveTotalEarned.value / liveTotalPossible.value) * 100);
});

const liveIsPassed = computed(() => {
    const passThreshold = Number(props.quiz.passing_percentage) || 70;
    return livePercentage.value >= passThreshold;
});

const liveStars = computed(() => {
    const pct = livePercentage.value;
    const threshold = Number(props.quiz.passing_percentage) || 70;
    if (pct >= 90) return 3;
    if (pct >= threshold) return 2;
    if (pct >= 40) return 1;
    return 0;
});

// Submit Grade to Server
const submitGrade = () => {
    if (!activeGradingAttempt.value || isSubmittingGrade.value) return;

    isSubmittingGrade.value = true;

    const payload = {
        answers: gradingAnswers.value.map((ans) => ({
            id: ans.id,
            points_earned: Number(ans.points_earned) || 0,
            teacher_notes: ans.teacher_notes || null,
        })),
        teacher_feedback: teacherFeedbackText.value || null,
    };

    const endpoint = `/teachers/modules/${props.module.slug}/quizzes/${props.quiz.slug}/results/grade/${activeGradingAttempt.value.id}`;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...csrfHeaders(),
        },
        body: JSON.stringify(payload),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                flushCacheTags(['teacher-modules', 'teacher-grading', 'student-modules', `module-${props.module?.slug}`]);
                // Update local attempt in submissions array
                const targetSub = props.submissions.find((s) => s.id === activeGradingAttempt.value.id);
                if (targetSub) {
                    targetSub.status = 'completed';
                    targetSub.is_pending_review = false;
                    targetSub.score = data.attempt.score;
                    targetSub.max_score = data.attempt.max_score;
                    targetSub.percentage = data.attempt.percentage;
                    targetSub.stars = data.attempt.stars;
                    targetSub.is_passed = data.attempt.is_passed;
                    targetSub.xp_earned = data.attempt.xp_earned;
                    targetSub.teacher_feedback = data.attempt.teacher_feedback;

                    // Update answers in targetSub
                    gradingAnswers.value.forEach((ga) => {
                        const targetAns = targetSub.answers?.find((a) => a.id === ga.id);
                        if (targetAns) {
                            targetAns.points_earned = ga.points_earned;
                            targetAns.teacher_notes = ga.teacher_notes;
                            targetAns.is_correct = ga.points_earned > 0;
                        }
                    });
                }

                closeGradingModal();
                toastMessage.value = 'Grade & feedback successfully published!';
                showToast.value = true;
                setTimeout(() => {
                    showToast.value = false;
                }, 3500);
            }
        })
        .catch((err) => {
            console.error('Error submitting grade:', err);
            router.reload();
        })
        .finally(() => {
            isSubmittingGrade.value = false;
        });
};
</script>

<template>
    <Head :title="`${quiz.title} • Results & Grading • ${module.title}`" />

    <div class="quiz-results-page">
        <!-- Ambient Floating Decorative Elements -->
        <div class="floating-bg-assets" aria-hidden="true">
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">🎯</div>
            <div class="floating-sparkle sp-3">⭐</div>
            <div class="floating-sparkle sp-4">📝</div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="top-nav-bar">
            <div class="nav-left">
                <button type="button" class="btn-3d-back" @mouseenter="prefetchRoute(`/teachers/modules/${module.slug}`, ['teacher-modules', `module-${module.slug}`])" @focus="prefetchRoute(`/teachers/modules/${module.slug}`, ['teacher-modules', `module-${module.slug}`])" @click="goToModule">
                    <span class="btn-arrow">❮</span>
                    <span>Back to Journey Map</span>
                </button>
                <div class="brand-group">
                    <span class="brand-text">
                        <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                    </span>
                    <span class="nav-badge">Results Inspector</span>
                </div>
            </div>

            <div class="nav-module-chip">
                <span class="chip-icon">{{ module.icon }}</span>
                <span class="chip-title">{{ module.title }}</span>
            </div>

            <div class="nav-right">
                <button type="button" class="btn-studio-edit" @click="goToStudio">
                    <span>⚙️ Quiz Studio</span>
                </button>
                <div class="teacher-chip">
                    <span class="teacher-avatar">🧙‍♂️</span>
                    <span class="teacher-name">{{ user.name }}</span>
                </div>

            </div>
        </header>

        <!-- Main Stage Container -->
        <main class="results-stage-container">
            <!-- HERO HEADER BANNER: Bento KPI Summary -->
            <section class="hero-bento-section">
                <!-- Left Kicker Info -->
                <div class="quiz-header-card">
                    <div class="kicker-badge-row">
                        <span class="quiz-format-badge" :class="quizTypeMeta.colorClass">
                            {{ quizTypeMeta.icon }} {{ quizTypeMeta.label }}
                        </span>
                        <span class="milestone-badge">Step #{{ quiz.sort_order }}</span>
                        <span class="status-published-tag">{{ quiz.is_published ? '✓ Live' : 'Draft' }}</span>
                    </div>

                    <h1 class="quiz-hero-title">{{ quiz.title }}</h1>
                    <p v-if="quiz.description" class="quiz-hero-desc">{{ quiz.description }}</p>

                    <div class="quiz-meta-pills-row">
                        <div class="meta-pill">
                            <span class="meta-lbl">PASS THRESHOLD:</span>
                            <strong class="meta-val">{{ quiz.passing_percentage }}%</strong>
                        </div>
                        <div class="meta-pill">
                            <span class="meta-lbl">XP REWARD:</span>
                            <strong class="meta-val text-amber">+{{ quiz.xp_reward }} XP</strong>
                        </div>
                        <div v-if="quiz.time_limit_seconds" class="meta-pill">
                            <span class="meta-lbl">TIME LIMIT:</span>
                            <strong class="meta-val">{{ Math.round(quiz.time_limit_seconds / 60) }}m</strong>
                        </div>
                    </div>
                </div>

                <!-- Right Bento Analytics Tiles -->
                <div class="bento-metrics-grid">
                    <!-- Tile 1: Total Attempts -->
                    <div class="bento-tile tile-attempts">
                        <span class="tile-icon">👥</span>
                        <div class="tile-content">
                            <strong class="tile-number">{{ analytics.total_attempts }}</strong>
                            <span class="tile-label">Submissions ({{ analytics.unique_students }} Students)</span>
                        </div>
                    </div>

                    <!-- Tile 2: Pending Teacher Grading -->
                    <div
                        class="bento-tile tile-pending"
                        :class="{ 'tile-pulse-amber': analytics.pending_review_count > 0 }"
                        @click="activeStatusFilter = 'pending_review'"
                    >
                        <span class="tile-icon">⏳</span>
                        <div class="tile-content">
                            <strong class="tile-number text-amber">{{ analytics.pending_review_count }}</strong>
                            <span class="tile-label">Awaiting Teacher Grade</span>
                        </div>
                    </div>

                    <!-- Tile 3: Class Average -->
                    <div class="bento-tile tile-avg">
                        <span class="tile-icon">📈</span>
                        <div class="tile-content">
                            <strong class="tile-number">{{ analytics.average_percentage }}%</strong>
                            <span class="tile-label">Class Average Score</span>
                        </div>
                    </div>

                    <!-- Tile 4: Pass Rate -->
                    <div class="bento-tile tile-passrate">
                        <span class="tile-icon">🏆</span>
                        <div class="tile-content">
                            <strong class="tile-number text-green">{{ analytics.pass_rate }}%</strong>
                            <span class="tile-label">Mastery Pass Rate</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TABS NAVIGATION: Submissions & Grading vs. Blueprint -->
            <section class="main-tabs-bar">
                <div class="tab-buttons-wrap">
                    <button
                        type="button"
                        class="tab-nav-btn"
                        :class="{ 'tab-active': activeMainTab === 'submissions' }"
                        @click="activeMainTab = 'submissions'"
                    >
                        <span>📝 Student Submissions & Grading</span>
                        <span v-if="analytics.pending_review_count > 0" class="tab-pending-badge">
                            {{ analytics.pending_review_count }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="tab-nav-btn"
                        :class="{ 'tab-active': activeMainTab === 'blueprint' }"
                        @click="activeMainTab = 'blueprint'"
                    >
                        <span>🎯 Questions & Rubric Blueprint ({{ quiz.questions_count }})</span>
                    </button>
                </div>
            </section>

            <!-- TAB 1: STUDENT SUBMISSIONS & GRADING -->
            <section v-if="activeMainTab === 'submissions'" class="submissions-tab-view">
                <!-- Filters Strip -->
                <div class="submissions-filter-toolbar">
                    <div class="filter-pills-row">
                        <button
                            type="button"
                            class="filter-pill-btn"
                            :class="{ 'pill-active': activeStatusFilter === 'all' }"
                            @click="activeStatusFilter = 'all'"
                        >
                            All ({{ submissions.length }})
                        </button>
                        <button
                            v-if="isSubjectiveQuiz || analytics.pending_review_count > 0"
                            type="button"
                            class="filter-pill-btn"
                            :class="{ 'pill-active': activeStatusFilter === 'pending_review' }"
                            @click="activeStatusFilter = 'pending_review'"
                        >
                            ⏳ Pending Review ({{ analytics.pending_review_count }})
                        </button>
                        <button
                            type="button"
                            class="filter-pill-btn"
                            :class="{ 'pill-active': activeStatusFilter === 'passed' }"
                            @click="activeStatusFilter = 'passed'"
                        >
                            ✓ Passed ({{ analytics.passed_count }})
                        </button>
                        <button
                            type="button"
                            class="filter-pill-btn"
                            :class="{ 'pill-active': activeStatusFilter === 'failed' }"
                            @click="activeStatusFilter = 'failed'"
                        >
                            Needs Help ({{ Math.max(0, analytics.graded_count - analytics.passed_count) }})
                        </button>
                    </div>

                    <div class="search-input-box">
                        <span class="search-icon">🔍</span>
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search student name or email..."
                            class="student-search-input"
                        />
                    </div>
                </div>

                <!-- Submissions Table / Cards -->
                <div v-if="filteredSubmissions.length === 0" class="empty-results-box">
                    <span class="empty-emoji">🎒</span>
                    <h3 class="empty-title">No student submissions match this filter</h3>
                    <p class="empty-sub">
                        {{
                            activeStatusFilter === 'pending_review'
                                ? 'All submissions for this quiz have been graded!'
                                : 'When students complete this quiz milestone, their attempts and scores will appear here.'
                        }}
                    </p>
                </div>

                <div v-else class="submissions-list-cards">
                    <article
                        v-for="sub in filteredSubmissions"
                        :key="sub.id"
                        class="student-submission-row"
                        :class="{ 'row-is-pending': sub.status === 'pending_review' }"
                    >
                        <!-- Student Column -->
                        <div class="col-student-info">
                            <div class="student-avatar-bubble">
                                <span>👤</span>
                            </div>
                            <div class="student-name-block">
                                <h3 class="name-text">{{ sub.student?.name }}</h3>
                                <span class="email-text">{{ sub.student?.email }}</span>
                            </div>
                        </div>

                        <!-- Meta Column -->
                        <div class="col-attempt-meta">
                            <span class="attempt-number-tag">Attempt #{{ sub.attempt_number }}</span>
                            <span class="submitted-date-tag">Submitted: {{ sub.completed_at }}</span>
                            <span v-if="sub.time_spent_seconds" class="time-spent-tag">⏱ {{ sub.time_spent_seconds }}s</span>
                        </div>

                        <!-- Score / Status Column -->
                        <div class="col-status-score">
                            <template v-if="sub.status === 'pending_review'">
                                <span class="badge-pending-review">⏳ PENDING TEACHER GRADE</span>
                                <span class="sub-pending-items">{{ sub.answers?.length || 0 }} items to evaluate</span>
                            </template>
                            <template v-else>
                                <div class="graded-stars-display">
                                    <span v-for="st in 3" :key="st" class="star-icon" :class="{ 'star-earned': st <= sub.stars }">★</span>
                                    <strong class="graded-score-text">{{ sub.score }} / {{ sub.max_score }} ({{ sub.percentage }}%)</strong>
                                </div>
                                <span
                                    class="pass-status-pill"
                                    :class="sub.is_passed ? 'pill-pass' : 'pill-retry'"
                                >
                                    {{ sub.is_passed ? 'PASSED ✓' : 'NEEDS RETRY ↻' }}
                                </span>
                            </template>
                        </div>

                        <!-- Action Column -->
                        <div class="col-action">
                            <button
                                type="button"
                                class="btn-grade-trigger"
                                :class="sub.status === 'pending_review' ? 'btn-must-grade' : 'btn-view-answers'"
                                @click="openGradingModal(sub)"
                            >
                                <span v-if="sub.status === 'pending_review'">📝 Grade Student</span>
                                <span v-else-if="isSubjectiveQuiz">✏️ Edit Grade & Feedback</span>
                                <span v-else>🔍 View Answers</span>
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- TAB 2: QUESTIONS & RUBRIC BLUEPRINT -->
            <section v-else class="blueprint-tab-view">
                <div class="blueprint-header-strip">
                    <div>
                        <h2 class="blueprint-heading">Quest Milestone Blueprint</h2>
                        <p class="blueprint-sub">Inspect the underlying questions, answer keys, context clues, and grading rubrics for this challenge.</p>
                    </div>
                    <button type="button" class="btn-blueprint-studio" @click="goToStudio">
                        <span>✏️ Edit in Quiz Studio</span>
                    </button>
                </div>

                <!-- Reading Scenario Context Card -->
                <div v-if="quiz.reading_passage || quiz.contexts?.length > 0" class="blueprint-context-card">
                    <div class="context-badge-label">📖 Story Reading Passage & Scenario:</div>
                    <p class="context-passage-text">"{{ quiz.reading_passage || quiz.contexts[0]?.scenario }}"</p>
                </div>

                <!-- Questions List Blueprint -->
                <div class="blueprint-questions-list">
                    <!-- MCQ Items -->
                    <template v-if="quiz.type === 'multiple_choice'">
                        <div
                            v-for="(q, qIdx) in quiz.questions"
                            :key="q.id"
                            class="blueprint-q-card"
                        >
                            <div class="q-card-head">
                                <span class="q-number-pill">QUESTION {{ qIdx + 1 }}</span>
                                <span class="q-points-pill">{{ q.points }} Points</span>
                            </div>
                            <h3 class="q-text">{{ q.question_text }}</h3>
                            <div class="options-rubric-grid">
                                <div
                                    v-for="(opt, optIdx) in q.options"
                                    :key="opt.id"
                                    class="rubric-opt-box"
                                    :class="{ 'is-correct-opt': opt.is_correct }"
                                >
                                    <span class="opt-letter">{{ String.fromCharCode(65 + (optIdx % 26)) }}.</span>
                                    <span class="opt-body">{{ opt.option_text }}</span>
                                    <span v-if="opt.is_correct" class="opt-check">✓ Correct Answer</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Color the Conflict Items -->
                    <template v-else-if="quiz.type === 'color_the_conflict'">
                        <div
                            v-for="(card, cIdx) in quiz.conflict_cards"
                            :key="card.id"
                            class="blueprint-q-card"
                        >
                            <div class="q-card-head">
                                <span class="q-number-pill">CONFLICT CARD {{ cIdx + 1 }}</span>
                                <span class="q-points-pill">{{ card.points }} Points</span>
                            </div>
                            <h3 class="q-text">"{{ card.excerpt }}"</h3>
                            <div class="blueprint-key-row">
                                <span class="rubric-opt-box is-correct-opt">
                                    <span class="opt-body">✓ Answer Key: {{ card.conflict_label || card.conflict_type }}</span>
                                </span>
                            </div>
                            <p v-if="card.explanation" class="blueprint-note-text">{{ card.explanation }}</p>
                        </div>
                    </template>

                    <!-- Conflict Map Items -->
                    <template v-else-if="quiz.type === 'the_conflict_map'">
                        <div v-if="quiz.map_zones?.length" class="blueprint-zones-bar">
                            <div
                                v-for="zone in quiz.map_zones"
                                :key="zone.id"
                                class="blueprint-zone-chip"
                            >
                                <strong>{{ zone.label }}</strong>
                                <small v-if="zone.subtitle">{{ zone.subtitle }}</small>
                            </div>
                        </div>
                        <div
                            v-for="(evt, mIdx) in quiz.map_events"
                            :key="evt.id"
                            class="blueprint-q-card"
                        >
                            <div class="q-card-head">
                                <span class="q-number-pill">{{ evt.card_letter || `EVENT ${mIdx + 1}` }}</span>
                                <span class="q-points-pill">{{ evt.points }} Points</span>
                            </div>
                            <h3 class="q-text">{{ evt.event_text }}</h3>
                            <div class="blueprint-key-row">
                                <span class="rubric-opt-box is-correct-opt">
                                    <span class="opt-body">✓ Target Zone: {{ evt.target_conflict_label || evt.target_conflict_type }}</span>
                                </span>
                            </div>
                            <p v-if="evt.explanation" class="blueprint-note-text">{{ evt.explanation }}</p>
                        </div>
                    </template>

                    <!-- Matching Type Items -->
                    <template v-else-if="quiz.type === 'matching_type'">
                        <div v-if="quiz.matching_choices?.length" class="blueprint-choices-bank">
                            <span class="sentence-lbl">Column B Choices Bank:</span>
                            <div class="options-rubric-grid">
                                <div
                                    v-for="ch in quiz.matching_choices"
                                    :key="ch.id"
                                    class="rubric-opt-box"
                                >
                                    <span class="opt-letter">{{ ch.choice_letter }}.</span>
                                    <span class="opt-body">{{ ch.choice_text }}</span>
                                </div>
                            </div>
                        </div>
                        <div
                            v-for="(item, iIdx) in quiz.matching_items"
                            :key="item.id"
                            class="blueprint-q-card"
                        >
                            <div class="q-card-head">
                                <span class="q-number-pill">ITEM #{{ item.item_number || iIdx + 1 }}</span>
                                <span class="q-points-pill">{{ item.points }} Points</span>
                            </div>
                            <h3 class="q-text">{{ item.description }}</h3>
                            <div class="blueprint-key-row">
                                <span class="rubric-opt-box is-correct-opt">
                                    <span class="opt-letter">{{ item.correct_letter }}.</span>
                                    <span class="opt-body">{{ item.correct_choice_text }}</span>
                                    <span class="opt-check">✓ Correct Match</span>
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Evaluation Items -->
                    <template v-else-if="quiz.type === 'evaluation'">
                        <div
                            v-for="(item, eIdx) in quiz.evaluation_items"
                            :key="item.id"
                            class="blueprint-q-card"
                        >
                            <div class="q-card-head">
                                <span class="q-number-pill">EVALUATION TASK {{ eIdx + 1 }}</span>
                                <span class="q-points-pill">{{ item.points }} Points</span>
                            </div>
                            <h3 class="q-text">{{ item.question_prompt }}</h3>
                            <div v-if="item.options?.length" class="options-rubric-grid blueprint-eval-opts">
                                <div
                                    v-for="(opt, optIdx) in item.options"
                                    :key="opt.id"
                                    class="rubric-opt-box"
                                    :class="{ 'is-correct-opt': opt.is_correct }"
                                >
                                    <span class="opt-letter">{{ String.fromCharCode(65 + (optIdx % 26)) }}.</span>
                                    <span class="opt-body">{{ opt.option_text }}</span>
                                    <span v-if="opt.is_correct" class="opt-check">✓ Expected</span>
                                </div>
                            </div>
                            <div class="rubric-sentence-box">
                                <span class="sentence-lbl">Sentence Template:</span>
                                <code>{{ item.sentence_template }}</code>
                            </div>
                        </div>
                    </template>

                    <!-- Reflection Prompts -->
                    <template v-else-if="quiz.type === 'reflection'">
                        <div
                            v-for="(prompt, rIdx) in quiz.reflection_prompts"
                            :key="prompt.id"
                            class="blueprint-q-card"
                        >
                            <div class="q-card-head">
                                <span class="q-number-pill">REFLECTION PROMPT {{ rIdx + 1 }}</span>
                                <span class="q-points-pill">{{ prompt.points }} Points</span>
                            </div>
                            <h3 class="q-text">"{{ prompt.prompt_text }}"</h3>
                            <span class="reflection-min-badge">Minimum Words: {{ prompt.min_words || 5 }} words</span>
                        </div>
                    </template>
                </div>
            </section>
        </main>

        <!-- INTERACTIVE GRADING & ANSWERS MODAL (SLIDE-OVER / MODAL SHEET) -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div
                    v-if="activeGradingAttempt"
                    class="grading-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="grading-modal-title"
                    @click.self="closeGradingModal"
                >
                    <div class="grading-modal-card">
                        <!-- Header -->
                        <div class="modal-card-header">
                            <div class="header-left-meta">
                                <span class="header-avatar">🎓</span>
                                <div>
                                    <h2 id="grading-modal-title" class="header-student-name">
                                        {{ activeGradingAttempt.student?.name }}'s Quiz Submission
                                    </h2>
                                    <p class="header-quiz-subtitle">
                                        <strong>{{ quiz.title }}</strong> • Attempt #{{ activeGradingAttempt.attempt_number }} • Submitted {{ activeGradingAttempt.completed_at }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn-modal-close"
                                aria-label="Close modal"
                                @click="closeGradingModal"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Body with Questions & Answers -->
                        <div class="modal-card-body">
                            <div class="grading-answers-list">
                                <div
                                    v-for="(ans, aIdx) in gradingAnswers"
                                    :key="ans.id"
                                    class="grading-answer-block"
                                    :class="getAnswerResultMeta(ans).blockClass"
                                >
                                    <div class="answer-block-head">
                                        <div class="block-head-left">
                                            <span class="block-q-pill">QUESTION {{ aIdx + 1 }}</span>
                                            <span class="block-format-pill">{{ getAnswerItemLabel(ans) }}</span>
                                        </div>
                                        <div class="block-head-right">
                                            <span
                                                class="block-status-pill"
                                                :class="getAnswerResultMeta(ans).badgeClass"
                                            >
                                                {{ getAnswerResultMeta(ans).label }}
                                            </span>
                                            <span class="block-max-pts">Max {{ ans.points_possible }} pts</span>
                                        </div>
                                    </div>

                                    <!-- Full Question, Choices & Color-Coded Student Submission Inspector -->
                                    <QuizAnswerInspector :answer="ans" />

                                    <!-- Teacher Points Input & Notes -->
                                    <div class="answer-grading-strip">
                                        <div class="input-points-group">
                                            <label class="lbl-score" :for="`score-input-${ans.id}`">Score Awarded:</label>
                                            <div class="pts-input-wrap">
                                                <input
                                                    :id="`score-input-${ans.id}`"
                                                    v-model.number="ans.points_earned"
                                                    type="number"
                                                    min="0"
                                                    :max="ans.points_possible"
                                                    class="input-pts-num"
                                                />
                                                <span class="pts-max-lbl">/ {{ ans.points_possible }} pts</span>
                                            </div>
                                        </div>

                                        <div class="quick-pts-pills">
                                            <button
                                                type="button"
                                                class="btn-quick-pill"
                                                @click="setAnswerPoints(aIdx, 0)"
                                            >
                                                0 pts
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-quick-pill"
                                                @click="setAnswerPoints(aIdx, Math.floor(ans.points_possible / 2))"
                                            >
                                                Half ({{ Math.floor(ans.points_possible / 2) }})
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-quick-pill"
                                                @click="setAnswerPoints(aIdx, ans.points_possible)"
                                            >
                                                Full ({{ ans.points_possible }})
                                            </button>
                                        </div>

                                        <div class="input-note-group">
                                            <input
                                                v-model="ans.teacher_notes"
                                                type="text"
                                                placeholder="Question feedback / teacher notes..."
                                                aria-label="Question feedback or teacher notes"
                                                class="input-q-note"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer with Real-time Scoring Summary -->
                        <div class="modal-card-footer">
                            <div class="live-scoring-summary">
                                <div class="score-preview-box">
                                    <span class="preview-lbl">FINAL SCORE:</span>
                                    <strong class="preview-val">{{ liveTotalEarned }} / {{ liveTotalPossible }} ({{ livePercentage }}%)</strong>
                                </div>
                                <div class="stars-preview-box">
                                    <span class="preview-lbl">STARS:</span>
                                    <span v-for="st in 3" :key="st" class="star-icon" :class="{ 'star-on': st <= liveStars }">★</span>
                                </div>
                                <div class="pass-preview-badge" :class="liveIsPassed ? 'badge-pass' : 'badge-retry'">
                                    {{ liveIsPassed ? 'MASTERED ✓' : 'RETRY NEEDED ↻' }}
                                </div>
                            </div>

                            <div class="modal-buttons-row">
                                <button type="button" class="btn-cancel-modal" @click="closeGradingModal">
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="btn-save-grade-action"
                                    :disabled="isSubmittingGrade"
                                    @click="submitGrade"
                                >
                                    <span v-if="isSubmittingGrade">Saving Grade... ⏳</span>
                                    <span v-else>Save & Publish Grade ✨</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Toast Notification -->
        <Teleport to="body">
            <div v-if="showToast" class="grade-toast-notification" role="status">
                <span class="toast-emoji">🎉</span>
                <span class="toast-text">{{ toastMessage }}</span>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.quiz-results-page {
    min-height: 100vh;
    background: radial-gradient(120% 80% at 50% 0%, #E0F2FE 0%, #EDF4FF 45%, #F5F8FF 100%);
    font-family: 'Nunito', sans-serif;
    color: #1E293B;
    position: relative;
    padding-bottom: 3.5rem;
    overflow-x: clip;
}

/* Background Atmosphere & Floating Sparkles */
.floating-bg-assets {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.floating-sparkle {
    position: absolute;
    font-size: 1.25rem;
    opacity: 0.28;
    filter: drop-shadow(0 2px 6px rgba(59, 130, 246, 0.25));
    animation: floatDecor 7s ease-in-out infinite;
}

.sp-1 { top: 10%; left: 3%; animation-delay: 0s; }
.sp-2 { top: 20%; right: 4%; animation-delay: 2.2s; }
.sp-3 { bottom: 22%; left: 5%; animation-delay: 4.4s; }
.sp-4 { bottom: 10%; right: 6%; animation-delay: 1.2s; }

@keyframes floatDecor {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(6deg); }
}

/* Top Navigation Bar: Compact Tactile Toy-Vinyl Header */
.top-nav-bar {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    border-bottom: 2.5px solid #CBD5E1;
    padding: 0.55rem clamp(1rem, 2.5vw, 2.25rem);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04), 0 1px 0 rgba(255, 255, 255, 0.8) inset;
}

.nav-left, .nav-right {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.btn-3d-back {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.38rem 0.95rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.84rem;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 2.5px 0 #94A3B8, 0 4px 8px rgba(15, 23, 42, 0.05);
    transition: transform 0.12s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.12s, color 0.12s;
}

.btn-3d-back:hover {
    transform: translateY(-1.5px);
    color: #0F172A;
    box-shadow: 0 4px 0 #94A3B8, 0 6px 12px rgba(15, 23, 42, 0.08);
}

.btn-3d-back:active {
    transform: translateY(1.5px);
    box-shadow: 0 1px 0 #94A3B8;
}

.btn-3d-back:focus-visible,
.btn-studio-edit:focus-visible,
.btn-blueprint-studio:focus-visible,
.tab-nav-btn:focus-visible,
.filter-pill-btn:focus-visible,
.btn-grade-trigger:focus-visible {
    outline: 2px solid #2563EB;
    outline-offset: 2px;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.brand-web {
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    color: #2563EB;
    font-size: 1.05rem;
    text-shadow: 0 1px 0 #DBEAFE;
}

.brand-read {
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    color: #F59E0B;
    font-size: 1.05rem;
    text-shadow: 0 1px 0 #FEF3C7;
}

.nav-badge {
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1.5px solid #BFDBFE;
    border-radius: 9999px;
    padding: 0.15rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 800;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.nav-module-chip {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 9999px;
    padding: 0.3rem 0.95rem;
    box-shadow: inset 0 1.5px 3px rgba(15, 23, 42, 0.04);
}

.chip-icon {
    font-size: 1rem;
}

.chip-title {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.86rem;
    color: #1E293B;
    max-inline-size: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-studio-edit {
    background: linear-gradient(180deg, #FEF3C7 0%, #FDE68A 100%);
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.38rem 0.95rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.84rem;
    color: #92400E;
    cursor: pointer;
    box-shadow: 0 2.5px 0 #D97706, 0 4px 8px rgba(245, 158, 11, 0.18);
    transition: transform 0.12s, box-shadow 0.12s;
}

.btn-studio-edit:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 4px 0 #D97706, 0 6px 12px rgba(245, 158, 11, 0.25);
}

.btn-studio-edit:active {
    transform: translateY(1.5px);
    box-shadow: 0 1px 0 #D97706;
}

.teacher-chip {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    background: #F1F5F9;
    border: 1.5px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.32rem 0.8rem;
    font-weight: 700;
    font-size: 0.82rem;
    color: #334155;
}


/* Stage Layout: Full Viewport Breadth with Scaled Down Compact Margins */
.results-stage-container {
    width: 100%;
    max-width: 100%;
    margin: 1.25rem 0 0;
    padding: 0 clamp(1rem, 2.5vw, 2.25rem);
    box-sizing: border-box;
    position: relative;
    z-index: 10;
}

/* Hero Bento Section: Scaled Down Proportions & Tighter Gaps */
.hero-bento-section {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.8fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
    align-items: stretch;
}

@media (min-width: 1540px) {
    .hero-bento-section {
        grid-template-columns: minmax(380px, 1fr) minmax(0, 2fr);
    }
}

.quiz-header-card {
    background: linear-gradient(180deg, #FFFFFF 0%, #FAFCFF 100%);
    border: 3px solid #CBD5E1;
    border-radius: 1.25rem;
    padding: 1.25rem 1.4rem;
    box-shadow: 0 5px 0 #CBD5E1, 0 10px 20px rgba(30, 41, 59, 0.04), inset 0 2px 0 rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
}

.kicker-badge-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.quiz-format-badge {
    padding: 0.22rem 0.65rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.76rem;
    letter-spacing: 0.02em;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.badge-eval { background: #FFE4E6; color: #BE123C; border: 1.5px solid #FDA4AF; }
.badge-reflect { background: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D; }
.badge-mcq { background: #EFF6FF; color: #1D4ED8; border: 1.5px solid #BFDBFE; }
.badge-color { background: #ECFDF5; color: #047857; border: 1.5px solid #A7F3D0; }
.badge-map { background: #EDE9FE; color: #6D28D9; border: 1.5px solid #DDD6FE; }
.badge-match { background: #F3E8FF; color: #7E22CE; border: 1.5px solid #E9D5FF; }

.milestone-badge {
    background: #F1F5F9;
    border: 1.5px solid #E2E8F0;
    border-radius: 9999px;
    padding: 0.2rem 0.6rem;
    font-size: 0.74rem;
    font-weight: 700;
    color: #475569;
}

.status-published-tag {
    background: #DCFCE7;
    color: #15803D;
    font-size: 0.74rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    border: 1.5px solid #86EFAC;
}

.quiz-hero-title {
    font-family: 'Fredoka', sans-serif;
    font-size: clamp(1.35rem, 1.8vw, 1.75rem);
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 0.35rem;
    line-height: 1.25;
}

.quiz-hero-desc {
    color: #475569;
    font-size: 0.88rem;
    line-height: 1.45;
    margin: 0 0 0.85rem;
    max-inline-size: 60ch;
}

.quiz-meta-pills-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
    margin-top: auto;
    padding-top: 0.35rem;
}

.meta-pill {
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.32rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
}

.meta-lbl {
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748B;
    letter-spacing: 0.03em;
}

.meta-val {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.94rem;
    font-weight: 800;
    color: #1E293B;
}

.text-amber { color: #D97706 !important; }
.text-green { color: #16A34A !important; }

/* Bento Metrics Grid: Compact 4-Tile Grid with Subsurface Highlights */
.bento-metrics-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.bento-tile {
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 3px solid #CBD5E1;
    border-radius: 1.15rem;
    padding: 0.95rem 1.15rem;
    box-shadow: 0 4.5px 0 #CBD5E1, 0 8px 16px rgba(15, 23, 42, 0.03), inset 0 1.5px 0 rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    gap: 0.85rem;
    transition: transform 0.12s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.12s;
    position: relative;
    overflow: hidden;
}

.bento-tile:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 6px 0 #94A3B8, 0 10px 20px rgba(15, 23, 42, 0.06);
}

.tile-icon {
    font-size: 1.75rem;
    width: 2.85rem;
    height: 2.85rem;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, #EFF6FF 100%);
    border: 2px solid #BFDBFE;
    border-radius: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.8), 0 2px 4px rgba(37, 99, 235, 0.08);
}

.tile-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.tile-number {
    font-family: 'Fredoka', sans-serif;
    font-size: clamp(1.45rem, 1.9vw, 1.85rem);
    font-weight: 800;
    line-height: 1.1;
    color: #0F172A;
    font-variant-numeric: tabular-nums;
}

.tile-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748B;
    margin-top: 0.15rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tile-pulse-amber {
    border-color: #F59E0B;
    background: linear-gradient(180deg, #FFFBEB 0%, #FEF3C7 100%);
    box-shadow: 0 4.5px 0 #D97706, 0 8px 18px rgba(245, 158, 11, 0.2), inset 0 1.5px 0 rgba(255, 255, 255, 0.9);
    cursor: pointer;
}

.tile-pulse-amber:hover {
    transform: translateY(-2px);
    box-shadow: 0 6.5px 0 #D97706, 0 12px 22px rgba(245, 158, 11, 0.28);
}

/* Tabs Navigation Bar: Compact Clay Pill Strip */
.main-tabs-bar {
    margin-bottom: 1rem;
}

.tab-buttons-wrap {
    display: inline-flex;
    background: #E2E8F0;
    padding: 0.3rem;
    border-radius: 9999px;
    gap: 0.3rem;
    box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.06);
}

.tab-nav-btn {
    border: none;
    background: transparent;
    padding: 0.48rem 1.25rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.88rem;
    color: #64748B;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    transition: all 0.12s;
}

.tab-nav-btn.tab-active {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 2.5px 6px rgba(15, 23, 42, 0.08);
}

.tab-pending-badge {
    background: #DC2626;
    color: #FFFFFF;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 0.1rem 0.45rem;
}

/* Submissions Filter Toolbar: Compact Responsive Well */
.submissions-filter-toolbar {
    background: #FFFFFF;
    border: 2.5px solid #CBD5E1;
    border-radius: 1.15rem;
    padding: 0.65rem 1.15rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 0 #CBD5E1, 0 6px 14px rgba(15, 23, 42, 0.03);
}

.filter-pills-row {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-pill-btn {
    border: 1.5px solid #CBD5E1;
    background: #F8FAFC;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.8rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.12s;
    box-shadow: 0 1.5px 0 #CBD5E1;
}

.filter-pill-btn:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.filter-pill-btn.pill-active {
    background: #2563EB;
    border-color: #1D4ED8;
    color: #FFFFFF;
    box-shadow: 0 2.5px 0 #1E40AF;
}

.search-input-box {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.35rem 0.95rem;
    transition: border-color 0.12s, box-shadow 0.12s;
    flex: 1;
    max-width: 320px;
    min-width: 220px;
    box-shadow: inset 0 1.5px 3px rgba(15, 23, 42, 0.04);
}

.search-input-box:focus-within {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15), inset 0 1.5px 3px rgba(15, 23, 42, 0.02);
    background: #FFFFFF;
}

.search-icon {
    font-size: 0.85rem;
    color: #64748B;
}

.student-search-input {
    border: none;
    background: transparent;
    font-weight: 700;
    font-size: 0.85rem;
    color: #0F172A;
    width: 100%;
}

.student-search-input:focus-visible {
    outline: none;
}

/* Submissions List: Compact Toy-Depth Enterprise Cards */
.submissions-list-cards {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    width: 100%;
}

.student-submission-row {
    background: #FFFFFF;
    border: 2.5px solid #CBD5E1;
    border-radius: 1.15rem;
    padding: 0.85rem 1.25rem;
    display: grid;
    grid-template-columns: minmax(220px, 1.8fr) minmax(160px, 1.2fr) minmax(190px, 1.3fr) auto;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 3.5px 0 #CBD5E1, 0 6px 12px rgba(15, 23, 42, 0.02);
    transition: transform 0.12s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.12s;
}

.student-submission-row:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 5px 0 #94A3B8, 0 8px 16px rgba(15, 23, 42, 0.05);
}

.student-submission-row.row-is-pending {
    border-color: #FCD34D;
    background: linear-gradient(180deg, #FFFDF5 0%, #FEFCE8 100%);
    box-shadow: 0 4px 0 #F59E0B, 0 8px 16px rgba(245, 158, 11, 0.1);
}

.col-student-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.student-avatar-bubble {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 9999px;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, #EEF2FF 100%);
    border: 2px solid #C7D2FE;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: inset 0 1.5px 3px rgba(255, 255, 255, 0.9), 0 2px 4px rgba(99, 102, 241, 0.08);
}

.student-name-block {
    min-width: 0;
}

.name-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.email-text {
    font-size: 0.76rem;
    color: #64748B;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.col-attempt-meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    font-variant-numeric: tabular-nums;
}

.attempt-number-tag {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.78rem;
    font-weight: 800;
    color: #2563EB;
}

.submitted-date-tag, .time-spent-tag {
    font-size: 0.74rem;
    color: #64748B;
    font-weight: 600;
}

.col-status-score {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    font-variant-numeric: tabular-nums;
}

.badge-pending-review {
    display: inline-block;
    background: #FEF3C7;
    color: #B45309;
    border: 1.5px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.2rem 0.65rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.72rem;
    letter-spacing: 0.02em;
}

.sub-pending-items {
    display: block;
    font-size: 0.72rem;
    color: #92400E;
    font-weight: 700;
}

.graded-stars-display {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.2rem;
}

.star-icon {
    font-size: 0.95rem;
    color: #CBD5E1;
}

.star-icon.star-earned, .star-icon.star-on {
    color: #F59E0B;
    filter: drop-shadow(0 1px 2px rgba(245, 158, 11, 0.4));
}

.graded-score-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.88rem;
    font-weight: 800;
    color: #1E293B;
    margin-left: 0.35rem;
}

.pass-status-pill {
    display: inline-block;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.7rem;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
}

.pill-pass { background: #DCFCE7; color: #15803D; border: 1px solid #86EFAC; }
.pill-retry { background: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; }

.col-action {
    display: flex;
    justify-content: flex-end;
}

.btn-grade-trigger {
    border: none;
    border-radius: 9999px;
    padding: 0.48rem 1.15rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.84rem;
    cursor: pointer;
    transition: transform 0.12s, box-shadow 0.12s;
    white-space: nowrap;
}

.btn-must-grade {
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    color: #FFFFFF;
    box-shadow: 0 3px 0 #92400E, 0 6px 12px rgba(245, 158, 11, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.4);
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
}

.btn-must-grade:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 4.5px 0 #92400E, 0 8px 16px rgba(245, 158, 11, 0.38);
}

.btn-must-grade:active {
    transform: translateY(1.5px);
    box-shadow: 0 1px 0 #92400E;
}

.btn-view-answers {
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2px solid #CBD5E1;
    color: #334155;
    box-shadow: 0 2.5px 0 #CBD5E1, 0 4px 8px rgba(15, 23, 42, 0.04);
}

.btn-view-answers:hover {
    transform: translateY(-1.5px);
    background: #F8FAFC;
    color: #0F172A;
    box-shadow: 0 4px 0 #CBD5E1;
}

.btn-view-answers:active {
    transform: translateY(1.5px);
    box-shadow: 0 1px 0 #CBD5E1;
}

/* Empty State */
.empty-results-box {
    background: #FFFFFF;
    border: 3px dashed #CBD5E1;
    border-radius: 1.25rem;
    padding: 3.5rem 1.5rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
}

.empty-emoji {
    font-size: 2.85rem;
    display: block;
    margin-bottom: 0.5rem;
}

.empty-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    margin: 0 0 0.35rem;
    color: #0F172A;
}

.empty-sub {
    color: #64748B;
    font-size: 0.88rem;
    margin: 0;
    max-inline-size: 55ch;
    margin-inline: auto;
}

/* Blueprint Tab: Scaled Down Proportions & Multi-Column Grid */
.blueprint-tab-view {
    background: #FFFFFF;
    border: 3px solid #CBD5E1;
    border-radius: 1.35rem;
    padding: 1.35rem clamp(1rem, 2vw, 1.75rem);
    box-shadow: 0 5px 0 #CBD5E1, 0 10px 20px rgba(15, 23, 42, 0.03);
}

.blueprint-header-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
    border-bottom: 2px solid #F1F5F9;
    padding-bottom: 0.85rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.blueprint-heading {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 0.25rem;
}

.blueprint-sub {
    color: #64748B;
    font-size: 0.85rem;
    margin: 0;
}

.btn-blueprint-studio {
    background: linear-gradient(180deg, #EFF6FF 0%, #DBEAFE 100%);
    border: 2px solid #93C5FD;
    border-radius: 9999px;
    padding: 0.4rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    color: #1D4ED8;
    cursor: pointer;
    box-shadow: 0 2.5px 0 #60A5FA;
    transition: transform 0.12s, box-shadow 0.12s;
}

.btn-blueprint-studio:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 4px 0 #60A5FA;
}

.btn-blueprint-studio:active {
    transform: translateY(1.5px);
    box-shadow: 0 1px 0 #60A5FA;
}

.blueprint-context-card {
    background: linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 100%);
    border: 2px solid #CBD5E1;
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: inset 0 1.5px 3px rgba(15, 23, 42, 0.03);
}

.context-badge-label {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.8rem;
    color: #475569;
    margin-bottom: 0.35rem;
}

.context-passage-text {
    font-style: italic;
    color: #1E293B;
    line-height: 1.5;
    margin: 0;
    font-size: 0.9rem;
}

/* Blueprint Questions: Responsive Scaled Down Multi-Column Grid */
.blueprint-questions-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 380px), 1fr));
    gap: 1rem;
    align-items: start;
}

.blueprint-q-card {
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2px solid #CBD5E1;
    border-radius: 1.15rem;
    padding: 1.15rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 100%;
    box-sizing: border-box;
    box-shadow: 0 3px 0 #CBD5E1, 0 4px 8px rgba(15, 23, 42, 0.02);
    transition: transform 0.12s, box-shadow 0.12s;
}

.blueprint-q-card:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 5px 0 #94A3B8, 0 8px 14px rgba(15, 23, 42, 0.05);
}

.q-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.6rem;
}

.q-number-pill {
    background: #E2E8F0;
    color: #334155;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.72rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
}

.q-points-pill {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.8rem;
    color: #2563EB;
}

.q-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.98rem;
    font-weight: 700;
    color: #1E293B;
    margin: 0 0 0.85rem;
    line-height: 1.35;
}

.options-rubric-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.rubric-opt-box {
    background: #FFFFFF;
    border: 1.5px solid #CBD5E1;
    border-radius: 0.75rem;
    padding: 0.6rem 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.84rem;
    box-shadow: 0 1.5px 0 #E2E8F0;
}

.rubric-opt-box.is-correct-opt {
    border-color: #22C55E;
    background: #F0FDF4;
    font-weight: 700;
    color: #15803D;
    box-shadow: 0 2px 0 #86EFAC;
}

.opt-letter {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
}

.opt-check {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 800;
    background: #DCFCE7;
    color: #15803D;
    padding: 0.15rem 0.45rem;
    border-radius: 9999px;
}

.rubric-sentence-box {
    background: #FFFFFF;
    border: 1.5px solid #CBD5E1;
    border-radius: 0.75rem;
    padding: 0.75rem 0.95rem;
}

.sentence-lbl {
    display: block;
    font-size: 0.76rem;
    font-weight: 700;
    color: #64748B;
    margin-bottom: 0.25rem;
}

.reflection-min-badge {
    display: inline-block;
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FCD34D;
    border-radius: 9999px;
    padding: 0.25rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
}

.blueprint-key-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.blueprint-note-text {
    margin: 0.6rem 0 0;
    font-size: 0.82rem;
    color: #475569;
    font-weight: 600;
    line-height: 1.4;
}

.blueprint-zones-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.65rem;
}

.blueprint-zone-chip {
    background: #EFF6FF;
    border: 1.5px solid #BFDBFE;
    border-radius: 0.85rem;
    padding: 0.65rem 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    font-family: 'Fredoka', sans-serif;
    color: #1E3A8A;
    font-size: 0.85rem;
}

.blueprint-zone-chip small {
    font-family: 'Nunito', sans-serif;
    font-size: 0.76rem;
    color: #475569;
    font-weight: 600;
}

.blueprint-choices-bank {
    background: #F8FAFC;
    border: 1.5px solid #CBD5E1;
    border-radius: 0.85rem;
    padding: 0.85rem;
}

.blueprint-eval-opts {
    margin-bottom: 0.65rem;
}

/* ==========================================================================
   GRADING / RESULTS MODAL (Scaled & Polished 3D Storybook Bezel)
   ========================================================================== */
.grading-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    z-index: 100;
}

.grading-modal-card {
    background: #FFFFFF;
    border: 3.5px solid #3B82F6;
    border-radius: 1.5rem;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.28), 0 6px 0 #1D4ED8;
    width: 100%;
    max-inline-size: min(92vw, 860px);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: popModal 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes popModal {
    0% { transform: scale(0.94); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.modal-card-header {
    background: linear-gradient(180deg, #EFF6FF 0%, #DBEAFE 100%);
    border-bottom: 2.5px solid #BFDBFE;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
}

.header-left-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-avatar {
    font-size: 2rem;
    filter: drop-shadow(0 2px 4px rgba(37, 99, 235, 0.2));
}

.header-student-name {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #1E3A8A;
    margin: 0;
}

.header-quiz-subtitle {
    font-size: 0.82rem;
    color: #475569;
    margin: 0.15rem 0 0;
}

.btn-modal-close {
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 9999px;
    width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #64748B;
    cursor: pointer;
    box-shadow: 0 2px 0 #CBD5E1;
    transition: color 0.12s, background 0.12s;
}

.btn-modal-close:hover {
    color: #0F172A;
    background: #F1F5F9;
}

.btn-modal-close:focus-visible,
.btn-quick-pill:focus-visible,
.btn-cancel-modal:focus-visible,
.btn-save-grade-action:focus-visible {
    outline: 2px solid #2563EB;
    outline-offset: 2px;
}

.modal-card-body {
    flex: 1;
    min-block-size: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.grading-answers-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.grading-answer-block {
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    border-radius: 1.15rem;
    padding: 1.15rem;
    box-shadow: 0 2px 0 #E2E8F0;
}

.grading-answer-block.block-correct {
    border-color: #86EFAC;
    background: #F9FDFB;
}

.grading-answer-block.block-wrong {
    border-color: #FCA5A5;
    background: #FFFBFB;
}

.grading-answer-block.block-partial {
    border-color: #FCD34D;
    background: #FFFDF7;
}

.answer-block-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.block-head-left,
.block-head-right {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.block-q-pill {
    background: #E2E8F0;
    color: #334155;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
}

.block-format-pill {
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #BFDBFE;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.72rem;
    padding: 0.18rem 0.55rem;
    border-radius: 9999px;
}

.block-status-pill {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
}

.block-status-pill.status-pill-correct {
    background: #DCFCE7;
    color: #166534;
    border: 1.5px solid #22C55E;
}

.block-status-pill.status-pill-wrong {
    background: #FEE2E2;
    color: #991B1B;
    border: 1.5px solid #EF4444;
}

.block-status-pill.status-pill-partial {
    background: #FEF3C7;
    color: #92400E;
    border: 1.5px solid #F59E0B;
}

.block-status-pill.status-pill-pending {
    background: #FEF3C7;
    color: #B45309;
    border: 1.5px solid #F59E0B;
}

.block-max-pts {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.78rem;
    color: #64748B;
}

/* Scoring Controls Strip */
.answer-grading-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.85rem;
    background: #FFFFFF;
    border: 1.5px solid #CBD5E1;
    border-radius: 0.85rem;
    padding: 0.7rem 0.95rem;
    margin-top: 0.75rem;
    box-shadow: 0 1.5px 0 #E2E8F0;
}

.input-points-group {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.lbl-score {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.8rem;
    color: #475569;
}

.pts-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.input-pts-num {
    width: 54px;
    border: 2px solid #3B82F6;
    border-radius: 0.5rem;
    padding: 0.3rem 0.4rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    text-align: center;
    color: #1E3A8A;
}

.input-pts-num:focus-visible {
    outline: 2px solid #2563EB;
    outline-offset: 2px;
}

.pts-max-lbl {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.8rem;
    color: #64748B;
}

.quick-pts-pills {
    display: flex;
    gap: 0.35rem;
}

.btn-quick-pill {
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.25rem 0.6rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.74rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 1.5px 0 #CBD5E1;
    transition: all 0.12s;
}

.btn-quick-pill:hover {
    background: #3B82F6;
    color: #FFFFFF;
    border-color: #2563EB;
}

.input-note-group {
    flex: 1;
    min-width: 180px;
}

.input-q-note {
    width: 100%;
    border: 1.5px solid #CBD5E1;
    border-radius: 0.5rem;
    padding: 0.35rem 0.65rem;
    font-size: 0.84rem;
    font-weight: 600;
}

.input-q-note:focus-visible {
    border-color: #2563EB;
    outline: 2px solid #2563EB;
    outline-offset: 1px;
}

/* Overall Feedback */
.overall-feedback-box {
    background: #FFFBEB;
    border: 2px solid #FCD34D;
    border-radius: 1rem;
    padding: 1.15rem;
}

.feedback-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.92rem;
    color: #92400E;
    margin-bottom: 0.45rem;
}

.feedback-heading small {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    font-size: 0.78rem;
    color: #B45309;
}

.feedback-textarea {
    width: 100%;
    border: 2px solid #FDE68A;
    border-radius: 0.75rem;
    padding: 0.75rem;
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    font-size: 0.88rem;
    color: #1E293B;
    box-sizing: border-box;
}

.feedback-textarea:focus-visible {
    border-color: #F59E0B;
    background: #FFFFFF;
    outline: 2px solid #D97706;
    outline-offset: 2px;
}

/* Modal Footer */
.modal-card-footer {
    background: #F8FAFC;
    border-top: 2.5px solid #CBD5E1;
    padding: 0.95rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.live-scoring-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.score-preview-box, .stars-preview-box {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.preview-lbl {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.78rem;
    color: #64748B;
}

.preview-val {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: #1E3A8A;
}

.pass-preview-badge {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
}

.pass-preview-badge.badge-pass {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #86EFAC;
}

.pass-preview-badge.badge-retry {
    background: #FEE2E2;
    color: #B91C1C;
    border: 1px solid #FCA5A5;
}

.modal-buttons-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.btn-cancel-modal {
    border: 2px solid #CBD5E1;
    background: #FFFFFF;
    border-radius: 9999px;
    padding: 0.5rem 1.15rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    color: #64748B;
    cursor: pointer;
    box-shadow: 0 2.5px 0 #CBD5E1;
    transition: all 0.12s;
}

.btn-cancel-modal:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.btn-save-grade-action {
    border: none;
    background: linear-gradient(180deg, #4ADE80 0%, #16A34A 100%);
    border-radius: 9999px;
    padding: 0.55rem 1.35rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.9rem;
    color: #FFFFFF;
    cursor: pointer;
    box-shadow: 0 3px 0 #15803D, 0 6px 14px rgba(34, 197, 94, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.4);
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.18);
    transition: transform 0.12s, box-shadow 0.12s;
}

.btn-save-grade-action:hover:not(:disabled) {
    transform: translateY(-1.5px);
    box-shadow: 0 4.5px 0 #15803D, 0 8px 16px rgba(34, 197, 94, 0.38);
}

.btn-save-grade-action:active:not(:disabled) {
    transform: translateY(1.5px);
    box-shadow: 0 1px 0 #15803D;
}

.btn-save-grade-action:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast */
.grade-toast-notification {
    position: fixed;
    bottom: 1.75rem;
    right: 1.75rem;
    background: #15803D;
    color: #FFFFFF;
    padding: 0.75rem 1.45rem;
    border-radius: 9999px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 0.55rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.92rem;
    z-index: 200;
    animation: toastSlide 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes toastSlide {
    0% { transform: translateY(16px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

/* Reduced Motion Safety */
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

@media (max-width: 1080px) {
    .hero-bento-section {
        grid-template-columns: 1fr;
    }

    .student-submission-row {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .col-status-score {
        text-align: left;
        align-items: flex-start;
    }

    .graded-stars-display {
        justify-content: flex-start;
    }

    .col-action {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 720px) {
    .top-nav-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .nav-left, .nav-right {
        justify-content: space-between;
    }

    .nav-module-chip {
        display: none;
    }

    .bento-metrics-grid {
        grid-template-columns: 1fr;
    }

    .student-submission-row {
        grid-template-columns: 1fr;
    }

    .options-rubric-grid {
        grid-template-columns: 1fr;
    }

    .modal-card-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .modal-buttons-row {
        justify-content: flex-end;
    }
}
</style>

