<script setup>
import { computed } from 'vue';
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
            context: null,
            contexts: [],
            xp_reward: 150,
            passing_percentage: 70,
            time_limit_seconds: null,
            sort_order: 1,
            is_published: true,
            questions_count: 0,
            questions: [],
        }),
    },
});

const optionLetters = ['A', 'B', 'C', 'D', 'E', 'F'];

const totalPoints = computed(() => {
    return (props.quiz.questions || []).reduce((sum, q) => sum + (Number(q.points) || 0), 0);
});

const goToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head :title="`${quiz.title} • Milestone Step #${quiz.sort_order} • ${module.title}`" />

    <div class="view-quiz-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Ambient Floating Decorative Elements -->
        <div class="floating-bg-assets" aria-hidden="true">
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">🎯</div>
            <div class="floating-sparkle sp-3">⭐</div>
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
                    <span class="nav-badge">Quiz Inspector</span>
                </div>
            </div>

            <div class="nav-module-chip">
                <span class="chip-icon">{{ module.icon }}</span>
                <span class="chip-title">{{ module.title }}</span>
            </div>

            <div class="nav-right">
                <div class="teacher-chip">
                    <span>🧙‍♂️</span>
                    <span>{{ user.name }}</span>
                </div>
                <button type="button" class="btn-exit-mini" @click="handleLogout">
                    Exit
                </button>
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="quiz-view-stage">
            <!-- Header Block -->
            <div class="view-header-block">
                <div class="step-badge-row">
                    <span class="step-cluster-pill">📍 Milestone Step #{{ quiz.sort_order }}</span>
                    <span class="quiz-type-pill">🎯 Multiple Choice Quest</span>
                </div>
                <h1 class="view-title">{{ quiz.title }}</h1>
                <p v-if="quiz.description" class="view-desc">{{ quiz.description }}</p>

                <!-- Stat Pills Strip -->
                <div class="stat-pills-strip">
                    <div class="stat-capsule xp-capsule">
                        <span class="cap-icon">⚡</span>
                        <span class="cap-val">+{{ quiz.xp_reward }} XP Reward</span>
                    </div>
                    <div class="stat-capsule questions-capsule">
                        <span class="cap-icon">🎯</span>
                        <span class="cap-val">{{ quiz.questions?.length || 0 }} Questions ({{ totalPoints }} Pts)</span>
                    </div>
                    <div class="stat-capsule pass-capsule">
                        <span class="cap-icon">⭐</span>
                        <span class="cap-val">{{ quiz.passing_percentage }}% Passing Score</span>
                    </div>
                    <div v-if="quiz.time_limit_seconds" class="stat-capsule time-capsule">
                        <span class="cap-icon">⏱️</span>
                        <span class="cap-val">{{ Math.round(quiz.time_limit_seconds / 60) }} Min Time Limit</span>
                    </div>
                </div>
            </div>

            <!-- Quiz Scenario Contexts Section -->
            <section
                v-if="quiz.contexts && quiz.contexts.length > 0"
                class="contexts-view-section"
            >
                <h2 class="section-heading">
                    <span>📖 Quiz Scenario Contexts</span>
                    <span class="heading-badge">{{ quiz.contexts.length }} {{ quiz.contexts.length === 1 ? 'Context' : 'Contexts' }}</span>
                </h2>

                <div class="contexts-view-grid">
                    <div
                        v-for="(ctx, cIdx) in quiz.contexts"
                        :key="ctx.id || cIdx"
                        class="neu-card passage-card"
                    >
                        <div class="passage-header">
                            <span class="card-icon">📖</span>
                            <h3>Context Scenario #{{ cIdx + 1 }}</h3>
                        </div>
                        <div class="passage-body">
                            {{ ctx.scenario }}
                        </div>
                        <div v-if="ctx.hint" class="scenario-hint-row">
                            <span class="hint-pill">💡 Scenario Hint:</span>
                            <span class="hint-text">{{ ctx.hint }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fallback for single scenario/passage if contexts array empty -->
            <section
                v-else-if="quiz.context?.scenario || quiz.reading_passage"
                class="neu-card passage-card"
            >
                <div class="passage-header">
                    <span class="card-icon">📖</span>
                    <h3>Story Scenario Context</h3>
                </div>
                <div class="passage-body">
                    {{ quiz.context?.scenario || quiz.reading_passage }}
                </div>
                <div v-if="quiz.context?.hint" class="scenario-hint-row">
                    <span class="hint-pill">💡 Scenario Hint:</span>
                    <span class="hint-text">{{ quiz.context.hint }}</span>
                </div>
            </section>

            <!-- Questions List -->
            <section class="questions-section">
                <h2 class="section-heading">
                    <span>Quiz Questions</span>
                    <span class="heading-badge">{{ quiz.questions?.length || 0 }} Total</span>
                </h2>

                <div v-if="!quiz.questions || quiz.questions.length === 0" class="empty-questions-card">
                    <p>No questions have been configured for this quiz milestone.</p>
                </div>

                <div v-else class="questions-list">
                    <article
                        v-for="(q, qIndex) in quiz.questions"
                        :key="q.id || qIndex"
                        class="neu-card question-card"
                    >
                        <header class="q-header">
                            <div class="q-kicker-group">
                                <span class="q-num-pill">Question #{{ qIndex + 1 }}</span>
                                <span class="q-type-badge">
                                    {{ q.selection_type === 'multiple' ? '☑️ Multiple Answers' : '🔘 Single Answer' }}
                                </span>
                            </div>
                            <span class="q-points-pill">+{{ q.points }} pts</span>
                        </header>

                        <div v-if="q.passage_context" class="q-context-quote">
                            <span class="quote-icon">“</span>
                            <span class="quote-text">{{ q.passage_context }}</span>
                            <span class="quote-icon">”</span>
                        </div>

                        <h3 class="q-text">{{ q.question_text }}</h3>

                        <!-- Choices List -->
                        <div class="options-grid">
                            <div
                                v-for="(opt, optIndex) in q.options"
                                :key="opt.id || optIndex"
                                class="option-pill-view"
                                :class="{ 'is-correct-choice': opt.is_correct }"
                            >
                                <span class="opt-letter">{{ optionLetters[optIndex] || (optIndex + 1) }}</span>
                                <div class="opt-content">
                                    <span class="opt-text">{{ opt.option_text }}</span>
                                    <span v-if="opt.is_correct" class="correct-tag">✓ Correct Answer</span>
                                    <p v-if="opt.feedback" class="opt-feedback">
                                        💡 <em>{{ opt.feedback }}</em>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Hints and Explanations -->
                        <footer v-if="q.hint || q.explanation" class="q-footer-meta">
                            <div v-if="q.hint" class="meta-item hint-meta">
                                <span class="meta-badge">💡 Hint:</span>
                                <span class="meta-text">{{ q.hint }}</span>
                            </div>
                            <div v-if="q.explanation" class="meta-item explanation-meta">
                                <span class="meta-badge">📖 Explanation:</span>
                                <span class="meta-text">{{ q.explanation }}</span>
                            </div>
                        </footer>
                    </article>
                </div>
            </section>

            <!-- Bottom Back Action -->
            <div class="bottom-action-bar">
                <button type="button" class="btn-3d-return" @click="goToModule">
                    <span>🗺️ Back to Reading Journey Map</span>
                </button>
            </div>
        </main>
    </div>
</template>

<style scoped>
:root {
    --font-heading: 'Fredoka', cursive, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
}

.view-quiz-page {
    min-height: 100vh;
    background: #0ea5e9;
    background: radial-gradient(circle at 50% 10%, #38bdf8 0%, #0284c7 45%, #0369a1 100%);
    color: #1e293b;
    font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    position: relative;
    padding-bottom: 5rem;
}

/* Ambient Floating Sparkles */
.floating-bg-assets {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
}

.floating-sparkle {
    position: absolute;
    font-size: 1.75rem;
    filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.6));
    animation: floatBob 4s ease-in-out infinite alternate;
}

.sp-1 { top: 12%; left: 8%; animation-delay: 0s; }
.sp-2 { top: 35%; right: 9%; animation-delay: 1.2s; }
.sp-3 { bottom: 18%; left: 14%; animation-delay: 2.1s; }

@keyframes floatBob {
    from { transform: translateY(0) scale(1); }
    to { transform: translateY(-16px) scale(1.08); }
}

/* Top Nav Bar */
.top-nav-bar {
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    border-bottom: 3.5px solid #0284c7;
    padding: 0.75rem 1.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 18px rgba(2, 132, 199, 0.18);
}

.nav-left, .nav-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-3d-back {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border: 2px solid #cbd5e1;
    color: #334155;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    box-shadow: 0 3px 0 #94a3b8;
    transition: transform 0.1s ease, box-shadow 0.1s ease;
}

.btn-3d-back:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 4.5px 0 #94a3b8;
}

.btn-3d-back:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #94a3b8;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.brand-text {
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 900;
    font-size: 1.35rem;
    letter-spacing: -0.02em;
}

.brand-web { color: #0284c7; }
.brand-read { color: #f97316; }

.nav-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 8px;
    border: 1px solid #bae6fd;
}

.nav-module-chip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    padding: 0.4rem 0.9rem;
    border-radius: 12px;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.9rem;
    color: #0f172a;
}

.teacher-chip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    padding: 0.35rem 0.8rem;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.85rem;
    color: #334155;
}

.btn-exit-mini {
    background: transparent;
    border: 1.5px solid #cbd5e1;
    color: #64748b;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-weight: 800;
    font-size: 0.75rem;
    cursor: pointer;
}

/* Stage */
.quiz-view-stage {
    position: relative;
    z-index: 10;
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
}

/* Header Block */
.view-header-block {
    background: #ffffff;
    border-radius: 24px;
    padding: 2rem;
    border: 3.5px solid #ffffff;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}

.step-badge-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.75rem;
}

.step-cluster-pill {
    background: #ffedd5;
    color: #c2410c;
    border: 1.5px solid #fed7aa;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 0.3rem 0.75rem;
    border-radius: 10px;
}

.quiz-type-pill {
    background: #f0fdf4;
    color: #15803d;
    border: 1.5px solid #bbf7d0;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 0.3rem 0.75rem;
    border-radius: 10px;
}

.view-title {
    font-family: 'Fredoka', cursive, sans-serif;
    font-size: 2.15rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.2;
    margin: 0.25rem 0 0.5rem 0;
}

.view-desc {
    color: #475569;
    font-size: 1.05rem;
    line-height: 1.6;
    margin: 0 0 1.25rem 0;
}

.stat-pills-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px dashed #e2e8f0;
}

.stat-capsule {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.9rem;
    border-radius: 12px;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
}

.xp-capsule { background: #fef3c7; color: #b45309; border: 1.5px solid #fde68a; }
.questions-capsule { background: #e0f2fe; color: #0369a1; border: 1.5px solid #bae6fd; }
.pass-capsule { background: #dcfce7; color: #15803d; border: 1.5px solid #bbf7d0; }
.time-capsule { background: #ede9fe; color: #6d28d9; border: 1.5px solid #ddd6fe; }

/* Contexts Section */
.contexts-view-section {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.contexts-view-grid {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

/* Neumorphic Cards */
.neu-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 1.75rem;
    border: 3px solid #ffffff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.passage-card {
    background: #fffbeb;
    border: 3px solid #fef3c7;
}

.passage-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.passage-header h3 {
    font-family: 'Fredoka', cursive, sans-serif;
    font-size: 1.2rem;
    font-weight: 800;
    color: #92400e;
    margin: 0;
}

.passage-body {
    font-size: 1.05rem;
    line-height: 1.75;
    color: #451a03;
    white-space: pre-wrap;
    background: #ffffff;
    padding: 1.25rem;
    border-radius: 14px;
    border: 1.5px solid #fde68a;
}

.scenario-hint-row {
    margin-top: 0.85rem;
    padding: 0.65rem 0.95rem;
    background: #fefce8;
    border: 1.5px solid #fef08a;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.hint-pill {
    background: #fde047;
    color: #854d0e;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.78rem;
    padding: 0.2rem 0.55rem;
    border-radius: 8px;
    flex-shrink: 0;
}

.scenario-hint-row .hint-text {
    font-size: 0.9rem;
    color: #713f12;
    font-weight: 600;
}

/* Questions Section */
.questions-section {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Fredoka', cursive, sans-serif;
    font-size: 1.45rem;
    font-weight: 900;
    color: #ffffff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    margin: 0;
}

.heading-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
    padding: 0.25rem 0.7rem;
    border-radius: 10px;
    font-size: 0.8rem;
    backdrop-filter: blur(6px);
}

.questions-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.question-card {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.q-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.q-kicker-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.q-num-pill {
    background: #ffedd5;
    color: #c2410c;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 0.25rem 0.65rem;
    border-radius: 8px;
}

.q-type-badge {
    background: #f1f5f9;
    color: #475569;
    font-weight: 700;
    font-size: 0.75rem;
    padding: 0.25rem 0.55rem;
    border-radius: 8px;
}

.q-points-pill {
    background: #fef3c7;
    color: #b45309;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 0.25rem 0.65rem;
    border-radius: 8px;
}

.q-context-quote {
    background: #f8fafc;
    border-left: 3.5px solid #0284c7;
    padding: 0.65rem 1rem;
    border-radius: 8px;
    font-style: italic;
    color: #334155;
    font-size: 0.92rem;
}

.q-text {
    font-family: 'Fredoka', cursive, sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.4;
    margin: 0;
}

/* Options */
.options-grid {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.option-pill-view {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.75rem 1rem;
    transition: all 0.15s ease;
}

.is-correct-choice {
    background: #f0fdf4;
    border-color: #22c55e;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
}

.opt-letter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #e2e8f0;
    color: #475569;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.is-correct-choice .opt-letter {
    background: #22c55e;
    color: #ffffff;
}

.opt-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.opt-text {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
}

.correct-tag {
    display: inline-block;
    color: #15803d;
    font-size: 0.75rem;
    font-weight: 800;
}

.opt-feedback {
    margin: 0.2rem 0 0 0;
    font-size: 0.82rem;
    color: #475569;
}

.q-footer-meta {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1.5px dashed #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.meta-item {
    font-size: 0.85rem;
    color: #334155;
    display: flex;
    gap: 0.4rem;
}

.meta-badge {
    font-weight: 800;
    color: #0369a1;
}

/* Bottom Bar */
.bottom-action-bar {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.btn-3d-return {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border: none;
    color: #ffffff;
    padding: 0.85rem 1.85rem;
    border-radius: 16px;
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 900;
    font-size: 1.05rem;
    cursor: pointer;
    box-shadow: 0 4.5px 0 #9a3412, 0 10px 20px rgba(234, 88, 12, 0.4);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-3d-return:hover {
    transform: translateY(-2px);
    box-shadow: 0 6.5px 0 #9a3412, 0 14px 26px rgba(234, 88, 12, 0.5);
}

.btn-3d-return:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #9a3412;
}
</style>
