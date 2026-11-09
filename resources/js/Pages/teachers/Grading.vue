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
    submissions: {
        type: Array,
        default: () => [],
    },
    modules: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'pending_review',
            module: null,
            type: 'all',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            pending_count: 0,
            graded_count: 0,
            total_count: 0,
        }),
    },
});

// Navigation
const handleBack = () => {
    router.visit('/teachers/dashboard');
};

const goToStudents = () => {
    router.visit('/teachers/students');
};

// Filter State
const currentStatusTab = ref(props.filters?.status || 'pending_review');
const selectedModuleId = ref(props.filters?.module || 'all');
const selectedType = ref(props.filters?.type || 'all');
const searchQuery = ref('');

// Filtered Submissions
const filteredSubmissions = computed(() => {
    return props.submissions.filter((sub) => {
        // Tab Status Filter
        if (currentStatusTab.value !== 'all') {
            if (currentStatusTab.value === 'pending_review' && sub.status !== 'pending_review') {
                return false;
            }
            if (currentStatusTab.value === 'completed' && sub.status !== 'completed') {
                return false;
            }
        }

        // Module Filter
        if (selectedModuleId.value !== 'all' && selectedModuleId.value) {
            if (String(sub.module?.id) !== String(selectedModuleId.value) && sub.module?.slug !== selectedModuleId.value) {
                return false;
            }
        }

        // Type Filter
        if (selectedType.value !== 'all' && selectedType.value) {
            if (sub.quiz?.type !== selectedType.value) {
                return false;
            }
        }

        // Search Query
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            const studentName = sub.student?.name?.toLowerCase() || '';
            const quizTitle = sub.quiz?.title?.toLowerCase() || '';
            const moduleTitle = sub.module?.title?.toLowerCase() || '';
            if (!studentName.includes(q) && !quizTitle.includes(q) && !moduleTitle.includes(q)) {
                return false;
            }
        }

        return true;
    });
});

// Active Grading Modal State
const activeGradingAttempt = ref(null);
const gradingAnswers = ref([]);
const teacherOverallFeedback = ref('');
const isSubmittingGrade = ref(false);
const showGradeSuccessToast = ref(false);

const openGradingModal = (submission) => {
    activeGradingAttempt.value = submission;
    teacherOverallFeedback.value = submission.teacher_feedback || '';
    gradingAnswers.value = (submission.answers || []).map((ans) => ({
        id: ans.id,
        points_earned: submission.status === 'completed' ? ans.points_earned : (ans.points_possible || 10),
        points_possible: ans.points_possible || 10,
        teacher_notes: ans.teacher_notes || '',
        item_type: ans.item_type,
        answer_data: ans.answer_data,
        answer_text: ans.answer_text,
        context: ans.context,
    }));
};

const closeGradingModal = () => {
    activeGradingAttempt.value = null;
    gradingAnswers.value = [];
    teacherOverallFeedback.value = '';
};

// Quick Grade Buttons
const setItemPoints = (index, points) => {
    if (gradingAnswers.value[index]) {
        gradingAnswers.value[index].points_earned = points;
    }
};

// Live Grade Previews
const liveTotalEarned = computed(() => {
    return gradingAnswers.value.reduce((acc, curr) => acc + (Number(curr.points_earned) || 0), 0);
});

const liveTotalPossible = computed(() => {
    return gradingAnswers.value.reduce((acc, curr) => acc + (Number(curr.points_possible) || 0), 0) || 1;
});

const livePercentage = computed(() => {
    return Math.round((liveTotalEarned.value / liveTotalPossible.value) * 100);
});

const liveStars = computed(() => {
    const pct = livePercentage.value;
    const threshold = activeGradingAttempt.value?.quiz?.passing_percentage || 70;
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
        teacher_feedback: teacherOverallFeedback.value || null,
    };

    fetch(`/teachers/grading/attempts/${activeGradingAttempt.value.id}`, {
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
                // Update local state in submissions list
                const sub = props.submissions.find((s) => s.id === activeGradingAttempt.value.id);
                if (sub) {
                    sub.status = 'completed';
                    sub.score = data.attempt.score;
                    sub.max_score = data.attempt.max_score;
                    sub.percentage = data.attempt.percentage;
                    sub.stars = data.attempt.stars;
                    sub.is_passed = data.attempt.is_passed;
                    sub.xp_earned = data.attempt.xp_earned;
                    sub.teacher_feedback = data.attempt.teacher_feedback;
                }
                closeGradingModal();
                showGradeSuccessToast.value = true;
                setTimeout(() => {
                    showGradeSuccessToast.value = false;
                }, 3500);
            }
        })
        .catch((err) => {
            console.error('Error submitting grade:', err);
            // Fallback: reload page via Inertia
            router.reload();
        })
        .finally(() => {
            isSubmittingGrade.value = false;
        });
};
</script>

<template>
    <Head title="Grade Student Submissions • WEBREAD" />

    <div class="grading-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Ambient Background Assets -->
        <div class="game-bg-elements" aria-hidden="true">
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">⭐</div>
            <div class="floating-sparkle sp-3">📝</div>
            <div class="floating-sparkle sp-4">🌟</div>
        </div>

        <!-- Top Game Header Bar -->
        <header class="grading-header-nav">
            <div class="nav-left">
                <button type="button" class="btn-back-nav" @click="handleBack">
                    <span class="back-arrow">❮</span>
                    <span class="back-label">HQ Dashboard</span>
                </button>
            </div>

            <div class="nav-center">
                <div class="page-title-badge">
                    <span class="badge-icon">📝</span>
                    <span class="badge-title">Teacher Grading & Review</span>
                </div>
            </div>

            <div class="nav-right">
                <button type="button" class="btn-secondary-nav" @click="goToStudents">
                    <span>🎒 Student Guild</span>
                </button>
            </div>
        </header>

        <!-- Main Content Arena -->
        <main class="grading-stage">
            <!-- Stats Banner Cards -->
            <section class="stats-overview-grid">
                <div class="stat-card pending-card" :class="{ 'has-items': stats.pending_count > 0 }" @click="currentStatusTab = 'pending_review'">
                    <div class="stat-icon-bubble">⏳</div>
                    <div class="stat-data">
                        <span class="stat-number">{{ stats.pending_count }}</span>
                        <span class="stat-label">Pending Review</span>
                    </div>
                </div>

                <div class="stat-card graded-card" @click="currentStatusTab = 'completed'">
                    <div class="stat-icon-bubble">✓</div>
                    <div class="stat-data">
                        <span class="stat-number">{{ stats.graded_count }}</span>
                        <span class="stat-label">Graded Submissions</span>
                    </div>
                </div>

                <div class="stat-card total-card" @click="currentStatusTab = 'all'">
                    <div class="stat-icon-bubble">📚</div>
                    <div class="stat-data">
                        <span class="stat-number">{{ stats.total_count }}</span>
                        <span class="stat-label">Total Submissions</span>
                    </div>
                </div>
            </section>

            <!-- Search & Filters Strip -->
            <section class="filters-toolbar-card">
                <!-- Status Tabs -->
                <div class="status-tab-group">
                    <button
                        type="button"
                        class="status-tab-btn"
                        :class="{ 'active-tab': currentStatusTab === 'pending_review' }"
                        @click="currentStatusTab = 'pending_review'"
                    >
                        <span>Pending Review ({{ stats.pending_count }})</span>
                    </button>
                    <button
                        type="button"
                        class="status-tab-btn"
                        :class="{ 'active-tab': currentStatusTab === 'completed' }"
                        @click="currentStatusTab = 'completed'"
                    >
                        <span>Graded ({{ stats.graded_count }})</span>
                    </button>
                    <button
                        type="button"
                        class="status-tab-btn"
                        :class="{ 'active-tab': currentStatusTab === 'all' }"
                        @click="currentStatusTab = 'all'"
                    >
                        <span>All ({{ stats.total_count }})</span>
                    </button>
                </div>

                <!-- Filters & Search -->
                <div class="filter-controls-group">
                    <!-- Module Select -->
                    <select v-model="selectedModuleId" class="filter-select">
                        <option value="all">🏰 All Modules</option>
                        <option v-for="mod in modules" :key="mod.id" :value="mod.id">
                            {{ mod.title }}
                        </option>
                    </select>

                    <!-- Format Select -->
                    <select v-model="selectedType" class="filter-select">
                        <option value="all">🎯 All Formats</option>
                        <option value="evaluation">📊 Evaluation</option>
                        <option value="reflection">💭 Reflection</option>
                        <option value="multiple_choice">🔘 Multiple Choice</option>
                        <option value="color_the_conflict">🎨 Color Conflict</option>
                        <option value="the_conflict_map">🗺️ Conflict Map</option>
                        <option value="matching_type">🧩 Matching</option>
                    </select>

                    <!-- Search Input -->
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input
                            v-model="searchQuery"
                            type="search"
                            class="search-input"
                            placeholder="Search student or quiz..."
                        />
                    </div>
                </div>
            </section>

            <!-- Submissions Grid / List -->
            <section class="submissions-container">
                <!-- Empty State -->
                <div v-if="filteredSubmissions.length === 0" class="empty-submissions-card">
                    <span class="empty-icon">🎉</span>
                    <h3 class="empty-title">
                        {{ currentStatusTab === 'pending_review' ? 'All Caught Up!' : 'No Submissions Found' }}
                    </h3>
                    <p class="empty-desc">
                        {{
                            currentStatusTab === 'pending_review'
                                ? 'There are no student quizzes waiting for teacher review right now.'
                                : 'No quiz submissions match your active filter criteria.'
                        }}
                    </p>
                </div>

                <!-- Submission Cards -->
                <div v-else class="submissions-grid">
                    <article
                        v-for="sub in filteredSubmissions"
                        :key="sub.id"
                        class="submission-card"
                        :class="{ 'is-pending': sub.status === 'pending_review' }"
                    >
                        <!-- Card Left: Student Meta -->
                        <div class="sub-student-meta">
                            <div class="sub-avatar-ring">
                                <span>👤</span>
                            </div>
                            <div class="sub-student-text">
                                <h3 class="student-name">{{ sub.student?.name }}</h3>
                                <span class="student-email">{{ sub.student?.email }}</span>
                            </div>
                        </div>

                        <!-- Card Middle: Quiz & Module Meta -->
                        <div class="sub-quiz-meta">
                            <div class="meta-tag-row">
                                <span
                                    class="quiz-type-badge"
                                    :class="`type-${sub.quiz?.type?.replace(/_/g, '-')}`"
                                >
                                    {{ sub.quiz?.type === 'evaluation' ? '📊 Evaluation' : (sub.quiz?.type === 'reflection' ? '💭 Reflection' : sub.quiz?.type) }}
                                </span>
                                <span class="module-title-tag">🏰 {{ sub.module?.title }}</span>
                            </div>
                            <h4 class="quiz-title">{{ sub.quiz?.title }}</h4>
                            <span class="submission-date">Submitted: {{ sub.completed_at || sub.started_at }}</span>
                        </div>

                        <!-- Card Right: Status & Action -->
                        <div class="sub-status-action">
                            <div class="status-indicator-box">
                                <template v-if="sub.status === 'pending_review'">
                                    <span class="badge-status-pending">⏳ PENDING REVIEW</span>
                                    <span class="status-sub-hint">{{ sub.answers?.length || 0 }} items to score</span>
                                </template>
                                <template v-else>
                                    <span class="badge-status-graded">✓ GRADED</span>
                                    <div class="graded-stars-row">
                                        <span v-for="s in 3" :key="s" class="star-icon" :class="{ 'star-on': s <= sub.stars }">★</span>
                                        <strong class="graded-score-label">{{ sub.score }} / {{ sub.max_score }} ({{ sub.percentage }}%)</strong>
                                    </div>
                                </template>
                            </div>

                            <button
                                type="button"
                                class="btn-grade-now"
                                :class="sub.status === 'pending_review' ? 'btn-pending-action' : 'btn-regrade-action'"
                                @click="openGradingModal(sub)"
                            >
                                <span>{{ sub.status === 'pending_review' ? '📝 Grade Submission' : '✏️ Review / Edit Grade' }}</span>
                            </button>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <!-- GRADING MODAL POPUP -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div
                    v-if="activeGradingAttempt"
                    class="grading-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="grading-dialog-title"
                    @click.self="closeGradingModal"
                >
                    <div class="grading-modal-sheet">
                        <!-- Modal Header -->
                        <div class="grading-sheet-header">
                            <div class="header-student-info">
                                <span class="sheet-avatar">🎓</span>
                                <div>
                                    <h2 id="grading-dialog-title" class="sheet-title">
                                        Grade {{ activeGradingAttempt.student?.name }}'s Submission
                                    </h2>
                                    <p class="sheet-subtitle">
                                        <strong>{{ activeGradingAttempt.quiz?.title }}</strong> • {{ activeGradingAttempt.module?.title }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn-sheet-close"
                                aria-label="Close grading modal"
                                @click="closeGradingModal"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Modal Body: Answers Checklist -->
                        <div class="grading-sheet-body">
                            <div class="items-list-container">
                                <div
                                    v-for="(ans, idx) in gradingAnswers"
                                    :key="ans.id"
                                    class="grading-item-card"
                                >
                                    <div class="item-card-header">
                                        <span class="item-number-pill">QUESTION {{ idx + 1 }}</span>
                                        <span class="item-type-label">
                                            {{ ans.item_type === 'evaluation' ? 'Opinion & Stance Evaluation' : 'Open-Ended Reflection' }}
                                        </span>
                                    </div>

                                    <!-- EVALUATION VIEW -->
                                    <div v-if="ans.item_type === 'evaluation'" class="item-answer-details">
                                        <div v-if="ans.context?.question_prompt" class="eval-prompt-box">
                                            <strong>Question Prompt:</strong> {{ ans.context.question_prompt }}
                                        </div>

                                        <!-- Step 1 Chosen Stance -->
                                        <div class="eval-stance-row">
                                            <span class="stance-lbl">Student Stance:</span>
                                            <span
                                                class="stance-val-badge"
                                                :class="ans.answer_data?.stance === 'right' || ans.answer_data?.stance === 'support' ? 'stance-support' : 'stance-oppose'"
                                            >
                                                {{ ans.answer_data?.stance ? ans.answer_data.stance.toUpperCase() : 'No stance selected' }}
                                            </span>
                                        </div>

                                        <!-- Step 2 Blanks Answer -->
                                        <div class="eval-sentence-box">
                                            <span class="sentence-lbl">Student Sentence & Explanation:</span>
                                            <p class="student-written-quote">
                                                "{{ ans.answer_text || (ans.answer_data?.blanks ? ans.answer_data.blanks.join(' | ') : 'No blank input') }}"
                                            </p>
                                        </div>
                                    </div>

                                    <!-- REFLECTION VIEW -->
                                    <div v-else-if="ans.item_type === 'reflection'" class="item-answer-details">
                                        <div v-if="ans.context?.prompt_text" class="reflection-prompt-box">
                                            <strong>Prompt:</strong> "{{ ans.context.prompt_text }}"
                                        </div>

                                        <div class="reflection-meta-row">
                                            <span class="word-count-badge">
                                                ✍️ {{ ans.answer_data?.word_count || 0 }} words
                                                <small v-if="ans.context?.min_words">(Min required: {{ ans.context.min_words }})</small>
                                            </span>
                                        </div>

                                        <div class="reflection-essay-box">
                                            <p class="student-essay-quote">
                                                "{{ ans.answer_text || ans.answer_data?.text || 'No response provided.' }}"
                                            </p>
                                        </div>
                                    </div>

                                    <!-- OTHER VIEW (MCQ, ETC) -->
                                    <div v-else class="item-answer-details">
                                        <p class="generic-answer-quote">Student Answer: {{ ans.answer_text }}</p>
                                    </div>

                                    <!-- Teacher Scoring Controls for this item -->
                                    <div class="item-score-controls">
                                        <div class="score-input-col">
                                            <label class="control-label">Score for this question:</label>
                                            <div class="score-input-wrap">
                                                <input
                                                    v-model.number="ans.points_earned"
                                                    type="number"
                                                    min="0"
                                                    :max="ans.points_possible"
                                                    class="item-points-input"
                                                />
                                                <span class="points-max-tag">/ {{ ans.points_possible }} pts</span>
                                            </div>
                                        </div>

                                        <div class="quick-score-pills">
                                            <button
                                                type="button"
                                                class="pill-quick-score"
                                                @click="setItemPoints(idx, 0)"
                                            >
                                                0 pts
                                            </button>
                                            <button
                                                type="button"
                                                class="pill-quick-score"
                                                @click="setItemPoints(idx, Math.floor(ans.points_possible / 2))"
                                            >
                                                Half ({{ Math.floor(ans.points_possible / 2) }})
                                            </button>
                                            <button
                                                type="button"
                                                class="pill-quick-score"
                                                @click="setItemPoints(idx, ans.points_possible)"
                                            >
                                                Full ({{ ans.points_possible }})
                                            </button>
                                        </div>

                                        <div class="teacher-item-note-col">
                                            <input
                                                v-model="ans.teacher_notes"
                                                type="text"
                                                class="item-note-input"
                                                placeholder="Optional question feedback..."
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Overall Teacher Feedback Textarea -->
                            <div class="overall-feedback-section">
                                <label class="overall-feedback-label">
                                    <span>💬 Teacher Encouragement & Overall Feedback:</span>
                                    <small>(This note will be shown to {{ activeGradingAttempt.student?.name }} on their quiz review)</small>
                                </label>
                                <textarea
                                    v-model="teacherOverallFeedback"
                                    rows="3"
                                    class="overall-feedback-textarea"
                                    placeholder="Write thoughtful advice, praise their critical thinking, or suggest reading strategies..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Modal Footer: Summary & Actions -->
                        <div class="grading-sheet-footer">
                            <div class="live-summary-pills">
                                <div class="live-pill score-pill">
                                    <span class="live-lbl">TOTAL SCORE:</span>
                                    <strong class="live-val">{{ liveTotalEarned }} / {{ liveTotalPossible }} ({{ livePercentage }}%)</strong>
                                </div>
                                <div class="live-pill stars-pill">
                                    <span class="live-lbl">AWARDED:</span>
                                    <span v-for="st in 3" :key="st" class="star-preview" :class="{ 'on': st <= liveStars }">★</span>
                                </div>
                            </div>

                            <div class="footer-action-buttons">
                                <button
                                    type="button"
                                    class="btn-cancel-grade"
                                    @click="closeGradingModal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="btn-submit-grade"
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
            <div v-if="showGradeSuccessToast" class="grade-success-toast" role="status">
                <span class="toast-icon">🎉</span>
                <span class="toast-msg">Grade saved and published successfully!</span>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.grading-page {
    min-height: 100vh;
    background: #F0F6FE;
    font-family: 'Nunito', sans-serif;
    color: #1E293B;
    position: relative;
    overflow-x: hidden;
    padding-bottom: 3rem;
}

/* Background floating sparkles */
.game-bg-elements {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.floating-sparkle {
    position: absolute;
    font-size: 1.5rem;
    opacity: 0.25;
    animation: floatSparkle 6s ease-in-out infinite;
}

.sp-1 { top: 10%; left: 5%; animation-delay: 0s; }
.sp-2 { top: 25%; right: 7%; animation-delay: 2s; }
.sp-3 { bottom: 20%; left: 8%; animation-delay: 4s; }
.sp-4 { bottom: 10%; right: 10%; animation-delay: 1s; }

@keyframes floatSparkle {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-15px) rotate(10deg); }
}

/* Header Bar */
.grading-header-nav {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    border-bottom: 3px solid #E2E8F0;
    padding: 0.75rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.btn-back-nav {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.5rem 1.1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 3px 0 #CBD5E1;
    transition: transform 0.15s, box-shadow 0.15s;
}

.btn-back-nav:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #CBD5E1;
    color: #0F172A;
}

.btn-secondary-nav {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #EFF6FF;
    border: 2px solid #BFDBFE;
    border-radius: 9999px;
    padding: 0.5rem 1.1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    color: #2563EB;
    cursor: pointer;
    box-shadow: 0 3px 0 #BFDBFE;
    transition: transform 0.15s;
}

.btn-secondary-nav:hover {
    transform: translateY(-2px);
}

.page-title-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.45rem 1.35rem;
    box-shadow: 0 3px 0 #D97706;
}

.badge-icon {
    font-size: 1.25rem;
}

.badge-title {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 1.05rem;
    color: #92400E;
}

/* Main Container */
.grading-stage {
    max-width: 1280px;
    margin: 1.75rem auto;
    padding: 0 1.5rem;
    position: relative;
    z-index: 10;
}

/* Stats Overview */
.stats-overview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}

.stat-card {
    background: #FFFFFF;
    border-radius: 1.25rem;
    border: 3px solid #E2E8F0;
    padding: 1.1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 5px 0 #E2E8F0;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.pending-card.has-items {
    border-color: #F59E0B;
    box-shadow: 0 5px 0 #D97706;
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
}

.stat-icon-bubble {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    background: #F1F5F9;
    border: 2px solid #CBD5E1;
}

.pending-card .stat-icon-bubble {
    background: #FEF3C7;
    border-color: #F59E0B;
}

.graded-card .stat-icon-bubble {
    background: #DCFCE7;
    border-color: #22C55E;
}

.total-card .stat-icon-bubble {
    background: #E0E7FF;
    border-color: #6366F1;
}

.stat-data {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1.1;
    color: #0F172A;
}

.stat-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Filters & Toolbar */
.filters-toolbar-card {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.75rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 4px 0 #E2E8F0;
}

.status-tab-group {
    display: flex;
    gap: 0.4rem;
    background: #F1F5F9;
    padding: 0.35rem;
    border-radius: 9999px;
    border: 2px solid #E2E8F0;
}

.status-tab-btn {
    border: none;
    background: transparent;
    padding: 0.45rem 1rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    color: #64748B;
    cursor: pointer;
    transition: all 0.15s;
}

.status-tab-btn.active-tab {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.filter-controls-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

.filter-select {
    border: 2px solid #CBD5E1;
    background: #F8FAFC;
    border-radius: 0.75rem;
    padding: 0.5rem 0.85rem;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    color: #334155;
    outline: none;
    cursor: pointer;
}

.filter-select:focus {
    border-color: #3B82F6;
    background: #FFFFFF;
}

.search-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    border-radius: 0.75rem;
    padding: 0.45rem 0.85rem;
}

.search-icon {
    font-size: 0.9rem;
    color: #94A3B8;
}

.search-input {
    border: none;
    background: transparent;
    outline: none;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    color: #1E293B;
    width: 180px;
}

/* Empty State */
.empty-submissions-card {
    background: #FFFFFF;
    border: 3px dashed #CBD5E1;
    border-radius: 1.5rem;
    padding: 4rem 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 3.5rem;
    display: block;
    margin-bottom: 0.75rem;
}

.empty-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #1E293B;
    margin-bottom: 0.5rem;
}

.empty-desc {
    color: #64748B;
    font-weight: 600;
    font-size: 0.95rem;
}

/* Submissions Grid */
.submissions-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.submission-card {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    box-shadow: 0 4px 0 #E2E8F0;
    transition: transform 0.15s, box-shadow 0.15s;
}

.submission-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #E2E8F0;
}

.submission-card.is-pending {
    border-color: #FCD34D;
    box-shadow: 0 4px 0 #F59E0B;
}

/* Card Left: Student */
.sub-student-meta {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 220px;
}

.sub-avatar-ring {
    width: 3rem;
    height: 3rem;
    border-radius: 9999px;
    background: #EEF2FF;
    border: 2px solid #C7D2FE;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.student-name {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
}

.student-email {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748B;
}

/* Card Middle: Quiz Meta */
.sub-quiz-meta {
    flex: 1;
}

.meta-tag-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.quiz-type-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    background: #F1F5F9;
    color: #475569;
}

.quiz-type-badge.type-evaluation {
    background: #FFE4E6;
    color: #BE123C;
    border: 1px solid #FDA4AF;
}

.quiz-type-badge.type-reflection {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FCD34D;
}

.module-title-tag {
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748B;
}

.quiz-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1E293B;
    margin: 0 0 0.2rem 0;
}

.submission-date {
    font-size: 0.78rem;
    font-weight: 600;
    color: #94A3B8;
}

/* Card Right: Status & Action */
.sub-status-action {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.status-indicator-box {
    text-align: right;
}

.badge-status-pending {
    display: inline-block;
    background: #FEF3C7;
    color: #B45309;
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.25rem 0.75rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.badge-status-graded {
    display: inline-block;
    background: #DCFCE7;
    color: #15803D;
    border: 2px solid #22C55E;
    border-radius: 9999px;
    padding: 0.25rem 0.75rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.75rem;
}

.status-sub-hint {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #94A3B8;
    margin-top: 0.2rem;
}

.graded-stars-row {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    margin-top: 0.2rem;
}

.star-icon {
    color: #CBD5E1;
    font-size: 0.95rem;
}

.star-icon.star-on {
    color: #F59E0B;
}

.graded-score-label {
    font-size: 0.8rem;
    font-weight: 800;
    color: #334155;
    margin-left: 0.35rem;
}

/* Action Buttons */
.btn-grade-now {
    border: none;
    border-radius: 9999px;
    padding: 0.65rem 1.35rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
    white-space: nowrap;
}

.btn-pending-action {
    background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 0 #92400E, 0 6px 12px rgba(245, 158, 11, 0.35);
}

.btn-pending-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #92400E, 0 8px 16px rgba(245, 158, 11, 0.45);
}

.btn-regrade-action {
    background: #F1F5F9;
    color: #475569;
    border: 2px solid #CBD5E1;
    box-shadow: 0 3px 0 #CBD5E1;
}

.btn-regrade-action:hover {
    transform: translateY(-2px);
    background: #E2E8F0;
}

/* ==========================================================================
   GRADING MODAL STYLING
   ========================================================================== */
.grading-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 100;
}

.grading-modal-sheet {
    background: #FFFFFF;
    border: 4px solid #3B82F6;
    border-radius: 2rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25), 0 8px 0 #1D4ED8;
    width: 100%;
    max-width: 820px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: popIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes popIn {
    0% { transform: scale(0.92); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.grading-sheet-header {
    background: #EFF6FF;
    border-bottom: 3px solid #DBEAFE;
    padding: 1.25rem 1.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-student-info {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.sheet-avatar {
    font-size: 2rem;
}

.sheet-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #1E3A8A;
    margin: 0;
}

.sheet-subtitle {
    font-size: 0.85rem;
    color: #64748B;
    margin: 0.15rem 0 0 0;
}

.btn-sheet-close {
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
    transition: transform 0.15s;
}

.btn-sheet-close:hover {
    transform: translateY(-2px);
    color: #0F172A;
}

.grading-sheet-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.items-list-container {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.grading-item-card {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 1.25rem;
    padding: 1.25rem;
}

.item-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.85rem;
}

.item-number-pill {
    background: #E2E8F0;
    color: #334155;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
}

.item-type-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748B;
}

/* Evaluation Specific Inside Modal */
.eval-prompt-box,
.reflection-prompt-box {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.92rem;
    color: #1E293B;
    margin-bottom: 0.75rem;
}

.eval-stance-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.75rem;
}

.stance-lbl {
    font-weight: 800;
    font-size: 0.85rem;
    color: #475569;
}

.stance-val-badge {
    padding: 0.25rem 0.85rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
}

.stance-val-badge.stance-support {
    background: #DCFCE7;
    color: #166534;
    border: 2px solid #22C55E;
}

.stance-val-badge.stance-oppose {
    background: #FEE2E2;
    color: #991B1B;
    border: 2px solid #EF4444;
}

.eval-sentence-box {
    margin-bottom: 1rem;
}

.sentence-lbl {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748B;
    margin-bottom: 0.25rem;
}

.student-written-quote,
.student-essay-quote {
    background: #FFFFFF;
    border-left: 4px solid #3B82F6;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    color: #1E293B;
    margin: 0;
    white-space: pre-wrap;
    line-height: 1.5;
}

.reflection-meta-row {
    margin-bottom: 0.5rem;
}

.word-count-badge {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FCD34D;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 700;
}

/* Scoring Controls Strip */
.item-score-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    background: #FFFFFF;
    border: 2px solid #E2E8F0;
    border-radius: 0.85rem;
    padding: 0.75rem 1rem;
    margin-top: 0.75rem;
}

.score-input-col {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.control-label {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
}

.score-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.item-points-input {
    width: 60px;
    border: 2px solid #3B82F6;
    border-radius: 0.5rem;
    padding: 0.35rem 0.5rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    text-align: center;
    color: #1E3A8A;
    outline: none;
}

.points-max-tag {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    color: #64748B;
}

.quick-score-pills {
    display: flex;
    gap: 0.35rem;
}

.pill-quick-score {
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

.pill-quick-score:hover {
    background: #3B82F6;
    color: #FFFFFF;
    border-color: #2563EB;
}

.teacher-item-note-col {
    flex: 1;
    min-width: 200px;
}

.item-note-input {
    width: 100%;
    border: 1px solid #CBD5E1;
    border-radius: 0.5rem;
    padding: 0.35rem 0.65rem;
    font-size: 0.85rem;
    font-weight: 600;
    outline: none;
}

.item-note-input:focus {
    border-color: #3B82F6;
}

/* Overall Feedback */
.overall-feedback-section {
    background: #FFFBEB;
    border: 2px solid #FCD34D;
    border-radius: 1rem;
    padding: 1.25rem;
}

.overall-feedback-label {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #92400E;
    margin-bottom: 0.5rem;
}

.overall-feedback-label small {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    font-size: 0.8rem;
    color: #B45309;
}

.overall-feedback-textarea {
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

.overall-feedback-textarea:focus {
    border-color: #F59E0B;
    background: #FFFFFF;
}

/* Modal Footer */
.grading-sheet-footer {
    background: #F8FAFC;
    border-top: 3px solid #E2E8F0;
    padding: 1rem 1.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.live-summary-pills {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.live-pill {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.live-lbl {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.8rem;
    color: #64748B;
}

.live-val {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: #1E3A8A;
}

.star-preview {
    font-size: 1.25rem;
    color: #CBD5E1;
}

.star-preview.on {
    color: #F59E0B;
}

.footer-action-buttons {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-cancel-grade {
    border: 2px solid #CBD5E1;
    background: #FFFFFF;
    border-radius: 9999px;
    padding: 0.6rem 1.25rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    color: #64748B;
    cursor: pointer;
    box-shadow: 0 3px 0 #CBD5E1;
}

.btn-submit-grade {
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
    transition: transform 0.15s, box-shadow 0.15s;
}

.btn-submit-grade:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #15803D, 0 8px 18px rgba(34, 197, 94, 0.45);
}

.btn-submit-grade:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast */
.grade-success-toast {
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
    animation: toastPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes toastPop {
    0% { transform: translateY(20px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

@media (max-width: 768px) {
    .stats-overview-grid {
        grid-template-columns: 1fr;
    }

    .submission-card {
        flex-direction: column;
        align-items: flex-start;
    }

    .sub-status-action {
        width: 100%;
        justify-content: space-between;
    }

    .grading-sheet-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .footer-action-buttons {
        justify-content: flex-end;
    }
}
</style>

