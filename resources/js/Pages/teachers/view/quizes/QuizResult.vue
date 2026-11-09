<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';

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

const getDisplayStance = (ans) => {
    if (!ans) return 'NO STANCE';
    if (ans.answer_data?.stance) {
        return ans.answer_data.stance;
    }
    const optionId = ans.answer_data?.option_id;
    if (optionId && ans.context?.options) {
        const opt = ans.context.options.find((o) => o.id === optionId);
        if (opt) return opt.stance_value || opt.option_text;
    }
    return 'NO STANCE';
};

const getStanceClass = (ans) => {
    const stance = (getDisplayStance(ans) || '').toLowerCase();
    if (stance === 'no stance') return 'stance-none';
    if (stance.includes('right') || stance.includes('support') || stance.includes('agree') || stance.includes('yes') || stance.includes('friend')) {
        return 'stance-right';
    }
    if (stance.includes('wrong') || stance.includes('oppose') || stance.includes('disagree') || stance.includes('no') || stance.includes('enem')) {
        return 'stance-wrong';
    }
    return 'stance-neutral';
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
        points_earned: sub.status === 'completed' ? ans.points_earned : (ans.points_possible || 10),
        points_possible: ans.points_possible || 10,
        teacher_notes: ans.teacher_notes || '',
        context: ans.context,
    }));
};

const closeGradingModal = () => {
    activeGradingAttempt.value = null;
    gradingAnswers.value = [];
    teacherFeedbackText.value = '';
};

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
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
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
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

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
                <button type="button" class="btn-3d-back" @click="goToModule">
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
                <button type="button" class="btn-logout-mini" @click="handleLogout" title="Log Out">
                    <span>Exit</span>
                </button>
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
                                    <span class="opt-letter">{{ ['A','B','C','D','E','F'][optIdx] }}.</span>
                                    <span class="opt-body">{{ opt.option_text }}</span>
                                    <span v-if="opt.is_correct" class="opt-check">✓ Correct Answer</span>
                                </div>
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
                                >
                                    <div class="answer-block-head">
                                        <span class="block-q-pill">QUESTION {{ aIdx + 1 }}</span>
                                        <span class="block-max-pts">Max {{ ans.points_possible }} Points</span>
                                    </div>

                                    <!-- 1. EVALUATION FORMAT QUESTION & ANSWER -->
                                    <div v-if="ans.item_type === 'evaluation'" class="answer-content-area">
                                        <div v-if="ans.context?.question_prompt" class="eval-prompt-callout">
                                            <strong>Question Prompt:</strong> {{ ans.context.question_prompt }}
                                        </div>

                                        <div class="eval-stance-row">
                                            <span class="label-stance">Chosen Stance:</span>
                                            <span
                                                class="stance-pill"
                                                :class="getStanceClass(ans)"
                                            >
                                                <span v-if="getStanceClass(ans) === 'stance-right'" aria-hidden="true">✓</span>
                                                <span v-else-if="getStanceClass(ans) === 'stance-wrong'" aria-hidden="true">✕</span>
                                                <span v-else-if="getStanceClass(ans) === 'stance-neutral'" aria-hidden="true">●</span>
                                                {{ getDisplayStance(ans).toUpperCase() }}
                                            </span>
                                        </div>

                                        <div class="eval-sentence-block">
                                            <span class="label-written">Student Sentence & Fill-in-the-Blank Reasoning:</span>
                                            <blockquote class="student-quote-box">
                                                "{{ ans.answer_text || (ans.answer_data?.blanks ? ans.answer_data.blanks.join(' | ') : 'No blank input') }}"
                                            </blockquote>
                                        </div>
                                    </div>

                                    <!-- 2. REFLECTION FORMAT QUESTION & ANSWER -->
                                    <div v-else-if="ans.item_type === 'reflection'" class="answer-content-area">
                                        <div v-if="ans.context?.prompt_text" class="reflection-prompt-callout">
                                            <strong>Prompt:</strong> "{{ ans.context.prompt_text }}"
                                        </div>

                                        <div class="reflection-wordcount-row">
                                            <span class="badge-wordcount">
                                                ✍️ Word Count: <strong>{{ ans.answer_data?.word_count || 0 }} words</strong>
                                                <small v-if="ans.context?.min_words">(Minimum required: {{ ans.context.min_words }})</small>
                                            </span>
                                        </div>

                                        <blockquote class="student-quote-box essay-quote">
                                            "{{ ans.answer_text || ans.answer_data?.text || 'No reflection response.' }}"
                                        </blockquote>
                                    </div>

                                    <!-- 3. MULTIPLE CHOICE / OTHER FORMATS -->
                                    <div v-else class="answer-content-area">
                                        <div v-if="ans.context?.question_text" class="mcq-prompt-callout">
                                            <strong>Question:</strong> {{ ans.context.question_text }}
                                        </div>
                                        <p class="generic-answer-line">
                                            <strong>Student Answer:</strong> {{ ans.answer_text }}
                                        </p>
                                    </div>

                                    <!-- Teacher Points Input & Notes (Evaluation & Reflection) -->
                                    <div class="answer-grading-strip">
                                        <div class="input-points-group">
                                            <label class="lbl-score">Score Awarded:</label>
                                            <div class="pts-input-wrap">
                                                <input
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
                                                class="input-q-note"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Overall Teacher Encouragement Feedback -->
                            <div class="overall-feedback-box">
                                <label class="feedback-heading">
                                    <span>💬 Teacher Encouragement & Feedback:</span>
                                    <small>(Shown directly to {{ activeGradingAttempt.student?.name }} on their completed quiz card)</small>
                                </label>
                                <textarea
                                    v-model="teacherFeedbackText"
                                    rows="3"
                                    placeholder="Write personalized advice, praise their critical thinking, or recommend reading strategies..."
                                    class="feedback-textarea"
                                ></textarea>
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
    background: #F0F6FE;
    font-family: 'Nunito', sans-serif;
    color: #1E293B;
    position: relative;
    padding-bottom: 4rem;
    overflow-x: hidden;
}

/* Background Sparkles */
.floating-bg-assets {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.floating-sparkle {
    position: absolute;
    font-size: 1.5rem;
    opacity: 0.25;
    animation: floatDecor 6s ease-in-out infinite;
}

.sp-1 { top: 12%; left: 4%; animation-delay: 0s; }
.sp-2 { top: 22%; right: 6%; animation-delay: 2s; }
.sp-3 { bottom: 25%; left: 7%; animation-delay: 4s; }
.sp-4 { bottom: 12%; right: 8%; animation-delay: 1s; }

@keyframes floatDecor {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(8deg); }
}

/* Top Navigation Bar */
.top-nav-bar {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    border-bottom: 3px solid #E2E8F0;
    padding: 0.75rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.nav-left, .nav-right {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.btn-3d-back {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.45rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 3px 0 #CBD5E1;
    transition: transform 0.15s;
}

.btn-3d-back:hover {
    transform: translateY(-2px);
    color: #0F172A;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.brand-web {
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    color: #2563EB;
}

.brand-read {
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    color: #F59E0B;
}

.nav-badge {
    background: #EFF6FF;
    color: #2563EB;
    border: 1px solid #BFDBFE;
    border-radius: 9999px;
    padding: 0.15rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 800;
}

.nav-module-chip {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 9999px;
    padding: 0.35rem 1rem;
}

.chip-icon {
    font-size: 1.1rem;
}

.chip-title {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    color: #1E293B;
}

.btn-studio-edit {
    background: #FEF3C7;
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.45rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: #92400E;
    cursor: pointer;
    box-shadow: 0 3px 0 #D97706;
    transition: transform 0.15s;
}

.btn-studio-edit:hover {
    transform: translateY(-2px);
}

.teacher-chip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: #F1F5F9;
    border-radius: 9999px;
    padding: 0.35rem 0.85rem;
    font-weight: 700;
    font-size: 0.85rem;
}

.btn-logout-mini {
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.35rem 0.75rem;
    font-weight: 700;
    font-size: 0.8rem;
    color: #64748B;
    cursor: pointer;
}

/* Stage Layout */
.results-stage-container {
    max-width: 1280px;
    margin: 1.5rem auto 0;
    padding: 0 1.5rem;
    position: relative;
    z-index: 10;
}

/* Hero Bento Section */
.hero-bento-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.quiz-header-card {
    background: #FFFFFF;
    border: 3.5px solid #E2E8F0;
    border-radius: 1.5rem;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 6px 0 #E2E8F0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.kicker-badge-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.65rem;
}

.quiz-format-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.78rem;
}

.badge-eval { background: #FFE4E6; color: #BE123C; border: 1.5px solid #FDA4AF; }
.badge-reflect { background: #FEF3C7; color: #92400E; border: 1.5px solid #FCD34D; }
.badge-mcq { background: #EFF6FF; color: #1D4ED8; border: 1.5px solid #BFDBFE; }
.badge-color { background: #ECFDF5; color: #047857; border: 1.5px solid #A7F3D0; }
.badge-map { background: #EDE9FE; color: #6D28D9; border: 1.5px solid #DDD6FE; }
.badge-match { background: #F3E8FF; color: #7E22CE; border: 1.5px solid #E9D5FF; }

.milestone-badge {
    background: #F1F5F9;
    border-radius: 9999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
}

.status-published-tag {
    background: #DCFCE7;
    color: #15803D;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
}

.quiz-hero-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.65rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 0.4rem;
    line-height: 1.25;
}

.quiz-hero-desc {
    color: #64748B;
    font-size: 0.92rem;
    line-height: 1.45;
    margin: 0 0 1rem;
}

.quiz-meta-pills-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.meta-pill {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.35rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.meta-lbl {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748B;
}

.meta-val {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 800;
    color: #1E293B;
}

.text-amber { color: #D97706 !important; }
.text-green { color: #16A34A !important; }

/* Bento Metrics Grid */
.bento-metrics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.bento-tile {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 5px 0 #E2E8F0;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.15s, box-shadow 0.15s;
}

.bento-tile:hover {
    transform: translateY(-2px);
}

.tile-icon {
    font-size: 2rem;
    width: 3.25rem;
    height: 3.25rem;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tile-content {
    display: flex;
    flex-direction: column;
}

.tile-number {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1.1;
    color: #0F172A;
}

.tile-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748B;
}

.tile-pulse-amber {
    border-color: #F59E0B;
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    box-shadow: 0 5px 0 #D97706, 0 10px 20px rgba(245, 158, 11, 0.2);
    cursor: pointer;
}

/* Tabs Navigation Bar */
.main-tabs-bar {
    margin-bottom: 1.5rem;
}

.tab-buttons-wrap {
    display: inline-flex;
    background: #E2E8F0;
    padding: 0.35rem;
    border-radius: 9999px;
    gap: 0.35rem;
}

.tab-nav-btn {
    border: none;
    background: transparent;
    padding: 0.6rem 1.4rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #64748B;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.15s;
}

.tab-nav-btn.tab-active {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
}

.tab-pending-badge {
    background: #DC2626;
    color: #FFFFFF;
    border-radius: 9999px;
    font-size: 0.72rem;
    padding: 0.1rem 0.5rem;
}

/* Submissions Filter Toolbar */
.submissions-filter-toolbar {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 0.85rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 0 #E2E8F0;
}

.filter-pills-row {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.filter-pill-btn {
    border: 2px solid #E2E8F0;
    background: #F8FAFC;
    padding: 0.4rem 0.9rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.82rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.filter-pill-btn.pill-active {
    background: #2563EB;
    border-color: #1D4ED8;
    color: #FFFFFF;
}

.search-input-box {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    border-radius: 0.75rem;
    padding: 0.4rem 0.85rem;
}

.student-search-input {
    border: none;
    background: transparent;
    outline: none;
    font-weight: 700;
    font-size: 0.88rem;
    width: 220px;
}

/* Submissions List */
.submissions-list-cards {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.student-submission-row {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1.15rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    box-shadow: 0 4px 0 #E2E8F0;
    transition: transform 0.15s, box-shadow 0.15s;
}

.student-submission-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #CBD5E1;
}

.student-submission-row.row-is-pending {
    border-color: #FCD34D;
    background: linear-gradient(135deg, #FFFDF5 0%, #FEFCE8 100%);
    box-shadow: 0 4px 0 #F59E0B;
}

.col-student-info {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 220px;
}

.student-avatar-bubble {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 9999px;
    background: #EEF2FF;
    border: 2px solid #C7D2FE;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

.name-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
}

.email-text {
    font-size: 0.78rem;
    color: #64748B;
    font-weight: 600;
}

.col-attempt-meta {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.attempt-number-tag {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.78rem;
    font-weight: 800;
    color: #2563EB;
}

.submitted-date-tag, .time-spent-tag {
    font-size: 0.75rem;
    color: #64748B;
    font-weight: 600;
}

.col-status-score {
    text-align: right;
    min-width: 170px;
}

.badge-pending-review {
    display: inline-block;
    background: #FEF3C7;
    color: #B45309;
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.2rem 0.65rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.72rem;
}

.sub-pending-items {
    display: block;
    font-size: 0.72rem;
    color: #92400E;
    font-weight: 700;
    margin-top: 0.2rem;
}

.graded-stars-display {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.2rem;
    margin-bottom: 0.25rem;
}

.star-icon {
    font-size: 0.95rem;
    color: #CBD5E1;
}

.star-icon.star-earned, .star-icon.star-on {
    color: #F59E0B;
}

.graded-score-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
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

.btn-grade-trigger {
    border: none;
    border-radius: 9999px;
    padding: 0.6rem 1.35rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
    white-space: nowrap;
}

.btn-must-grade {
    background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 0 #92400E, 0 6px 14px rgba(245, 158, 11, 0.35);
}

.btn-must-grade:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #92400E;
}

.btn-view-answers {
    background: #F1F5F9;
    border: 2px solid #CBD5E1;
    color: #334155;
    box-shadow: 0 3px 0 #CBD5E1;
}

.btn-view-answers:hover {
    transform: translateY(-2px);
    background: #E2E8F0;
}

/* Empty State */
.empty-results-box {
    background: #FFFFFF;
    border: 3px dashed #CBD5E1;
    border-radius: 1.5rem;
    padding: 3.5rem 1.5rem;
    text-align: center;
}

.empty-emoji {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
}

.empty-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    margin: 0 0 0.35rem;
}

.empty-sub {
    color: #64748B;
    font-size: 0.9rem;
    margin: 0;
}

/* Blueprint Tab */
.blueprint-tab-view {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 1.5rem;
    padding: 1.75rem;
    box-shadow: 0 5px 0 #E2E8F0;
}

.blueprint-header-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #F1F5F9;
    padding-bottom: 1rem;
}

.blueprint-heading {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 0.25rem;
}

.blueprint-sub {
    color: #64748B;
    font-size: 0.88rem;
    margin: 0;
}

.btn-blueprint-studio {
    background: #EFF6FF;
    border: 2px solid #BFDBFE;
    border-radius: 9999px;
    padding: 0.45rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: #1D4ED8;
    cursor: pointer;
}

.blueprint-context-card {
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.context-badge-label {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.82rem;
    color: #475569;
    margin-bottom: 0.4rem;
}

.context-passage-text {
    font-style: italic;
    color: #1E293B;
    line-height: 1.5;
    margin: 0;
}

.blueprint-questions-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.blueprint-q-card {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1.25rem;
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
    font-size: 0.82rem;
    color: #2563EB;
}

.q-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1E293B;
    margin: 0 0 0.85rem;
}

.options-rubric-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.6rem;
}

.rubric-opt-box {
    background: #FFFFFF;
    border: 2px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.65rem 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
}

.rubric-opt-box.is-correct-opt {
    border-color: #22C55E;
    background: #F0FDF4;
    font-weight: 700;
    color: #15803D;
}

.opt-letter {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
}

.opt-check {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 800;
    background: #DCFCE7;
    color: #15803D;
    padding: 0.15rem 0.45rem;
    border-radius: 9999px;
}

.rubric-sentence-box {
    background: #FFFFFF;
    border: 2px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
}

.sentence-lbl {
    display: block;
    font-size: 0.8rem;
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
    font-size: 0.8rem;
    font-weight: 700;
}

/* ==========================================================================
   GRADING / RESULTS MODAL
   ========================================================================== */
.grading-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 100;
}

.grading-modal-card {
    background: #FFFFFF;
    border: 4px solid #3B82F6;
    border-radius: 2rem;
    box-shadow: 0 24px 50px rgba(0, 0, 0, 0.3), 0 8px 0 #1D4ED8;
    width: 100%;
    max-width: 860px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: popModal 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes popModal {
    0% { transform: scale(0.92); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.modal-card-header {
    background: #EFF6FF;
    border-bottom: 3px solid #DBEAFE;
    padding: 1.25rem 1.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-left-meta {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.header-avatar {
    font-size: 2.2rem;
}

.header-student-name {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #1E3A8A;
    margin: 0;
}

.header-quiz-subtitle {
    font-size: 0.85rem;
    color: #64748B;
    margin: 0.15rem 0 0;
}

.btn-modal-close {
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 9999px;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #64748B;
    cursor: pointer;
    box-shadow: 0 2px 0 #CBD5E1;
}

.btn-modal-close:hover {
    color: #0F172A;
}

.modal-card-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.grading-answers-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.grading-answer-block {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1.25rem;
}

.answer-block-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.block-q-pill {
    background: #E2E8F0;
    color: #334155;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
}

.block-max-pts {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    color: #2563EB;
}

.eval-prompt-callout, .reflection-prompt-callout, .mcq-prompt-callout {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.92rem;
    margin-bottom: 0.75rem;
}

.eval-stance-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.label-stance, .label-written {
    font-weight: 800;
    font-size: 0.82rem;
    color: #475569;
}

.stance-pill {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 0.25rem 0.8rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.stance-pill.stance-right {
    background: #DCFCE7;
    color: #166534;
    border: 2px solid #22C55E;
}

.stance-pill.stance-wrong {
    background: #FEE2E2;
    color: #991B1B;
    border: 2px solid #EF4444;
}

.stance-pill.stance-neutral {
    background: #EFF6FF;
    color: #1E40AF;
    border: 2px solid #60A5FA;
}

.stance-pill.stance-none {
    background: #F1F5F9;
    color: #64748B;
    border: 2px dashed #CBD5E1;
}

.student-quote-box {
    background: #FFFFFF;
    border-left: 4px solid #3B82F6;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    color: #1E293B;
    margin: 0.35rem 0 0;
    line-height: 1.5;
}

.essay-quote {
    white-space: pre-wrap;
}

.reflection-wordcount-row {
    margin-bottom: 0.5rem;
}

.badge-wordcount {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FCD34D;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 700;
}

/* Scoring Controls Strip */
.answer-grading-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.85rem;
    background: #FFFFFF;
    border: 2px solid #E2E8F0;
    border-radius: 0.85rem;
    padding: 0.75rem 1rem;
    margin-top: 0.85rem;
}

.input-points-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.lbl-score {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.82rem;
    color: #475569;
}

.pts-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.input-pts-num {
    width: 58px;
    border: 2px solid #3B82F6;
    border-radius: 0.5rem;
    padding: 0.35rem 0.45rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    text-align: center;
    color: #1E3A8A;
    outline: none;
}

.pts-max-lbl {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.82rem;
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
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-quick-pill:hover {
    background: #3B82F6;
    color: #FFFFFF;
    border-color: #2563EB;
}

.input-note-group {
    flex: 1;
    min-width: 200px;
}

.input-q-note {
    width: 100%;
    border: 1px solid #CBD5E1;
    border-radius: 0.5rem;
    padding: 0.35rem 0.65rem;
    font-size: 0.85rem;
    font-weight: 600;
    outline: none;
}

.input-q-note:focus {
    border-color: #3B82F6;
}

/* Overall Feedback */
.overall-feedback-box {
    background: #FFFBEB;
    border: 2px solid #FCD34D;
    border-radius: 1rem;
    padding: 1.25rem;
}

.feedback-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #92400E;
    margin-bottom: 0.5rem;
}

.feedback-heading small {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    font-size: 0.8rem;
    color: #B45309;
}

.feedback-textarea {
    width: 100%;
    border: 2px solid #FDE68A;
    border-radius: 0.75rem;
    padding: 0.75rem;
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    font-size: 0.92rem;
    color: #1E293B;
    outline: none;
    box-sizing: border-box;
}

.feedback-textarea:focus {
    border-color: #F59E0B;
    background: #FFFFFF;
}

/* Modal Footer */
.modal-card-footer {
    background: #F8FAFC;
    border-top: 3px solid #E2E8F0;
    padding: 1rem 1.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.live-scoring-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.score-preview-box, .stars-preview-box {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.preview-lbl {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.78rem;
    color: #64748B;
}

.preview-val {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: #1E3A8A;
}

.pass-preview-badge {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.75rem;
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
    gap: 0.75rem;
}

.btn-cancel-modal {
    border: 2px solid #CBD5E1;
    background: #FFFFFF;
    border-radius: 9999px;
    padding: 0.6rem 1.25rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    color: #64748B;
    cursor: pointer;
    box-shadow: 0 3px 0 #CBD5E1;
}

.btn-save-grade-action {
    border: none;
    background: linear-gradient(145deg, #22C55E 0%, #16A34A 100%);
    border-radius: 9999px;
    padding: 0.65rem 1.5rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #FFFFFF;
    cursor: pointer;
    box-shadow: 0 4px 0 #15803D, 0 6px 14px rgba(34, 197, 94, 0.35);
    transition: transform 0.15s;
}

.btn-save-grade-action:hover:not(:disabled) {
    transform: translateY(-2px);
}

.btn-save-grade-action:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast */
.grade-toast-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: #15803D;
    color: #FFFFFF;
    padding: 0.85rem 1.5rem;
    border-radius: 9999px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    z-index: 200;
    animation: toastSlide 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes toastSlide {
    0% { transform: translateY(20px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

@media (max-width: 900px) {
    .hero-bento-section {
        grid-template-columns: 1fr;
    }

    .bento-metrics-grid {
        grid-template-columns: 1fr;
    }

    .student-submission-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .col-status-score {
        text-align: left;
    }

    .graded-stars-display {
        justify-content: flex-start;
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

