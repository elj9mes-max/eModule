<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

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
            next_sort_order: 2,
        }),
    },
});

const xpPresets = [100, 150, 200, 250, 500];
const passPresets = [60, 70, 75, 80, 90];

// Conflict Categories Specification
const conflictCategories = [
    {
        type: 'character_vs_character',
        label: 'Character vs. Character',
        subtitle: 'Interpersonal conflict between individuals',
        colorKey: 'red',
        colorHex: '#EF4444',
        borderHex: '#B91C1C',
        bgLight: '#FEF2F2',
        icon: '🔴',
    },
    {
        type: 'character_vs_society',
        label: 'Character vs. Society',
        subtitle: 'Rules, laws, customs & community institutions',
        colorKey: 'blue',
        colorHex: '#3B82F6',
        borderHex: '#1D4ED8',
        bgLight: '#EFF6FF',
        icon: '🔵',
    },
    {
        type: 'character_vs_nature',
        label: 'Character vs. Nature',
        subtitle: 'Weather, wilderness & environmental forces',
        colorKey: 'green',
        colorHex: '#10B981',
        borderHex: '#047857',
        bgLight: '#ECFDF5',
        icon: '🟢',
    },
    {
        type: 'character_vs_self',
        label: 'Character vs. Self',
        subtitle: 'Internal doubts, fear & moral dilemmas',
        colorKey: 'amber',
        colorHex: '#F59E0B',
        borderHex: '#B45309',
        bgLight: '#FFFBEB',
        icon: '🟡',
    },
];

// Helper to create an empty flashcard
const createDefaultCard = (order = 1) => ({
    excerpt: '',
    conflict_type: 'character_vs_character',
    hint: '',
    explanation: '',
    feedback_incorrect: '',
    points: 10,
    sort_order: order,
});

// Setup form with 1 default card (user can add more)
const form = useForm({
    title: '',
    description: '',
    xp_reward: 200,
    passing_percentage: 70,
    time_limit_seconds: null,
    is_published: true,
    contexts: [
        {
            scenario: '',
            hint: '',
        },
    ],
    cards: [
        createDefaultCard(1),
    ],
});

// Context Scenario Management
const addContext = () => {
    form.contexts.push({
        scenario: '',
        hint: '',
    });
};

const removeContext = (index) => {
    if (form.contexts.length > 1) {
        form.contexts.splice(index, 1);
    } else {
        form.contexts[0] = { scenario: '', hint: '' };
    }
};

// Flashcard Management
const addCard = () => {
    if (form.cards.length < 20) {
        form.cards.push(createDefaultCard(form.cards.length + 1));
    }
};

const removeCard = (index) => {
    if (form.cards.length > 1) {
        form.cards.splice(index, 1);
        form.cards.forEach((card, idx) => {
            card.sort_order = idx + 1;
        });
    }
};

const selectCardCategory = (cardIndex, categoryType) => {
    form.cards[cardIndex].conflict_type = categoryType;
};

// Calculations
const totalPoints = computed(() => {
    return form.cards.reduce((sum, card) => sum + (parseInt(card.points, 10) || 0), 0);
});

// Live Preview Simulation State
const showPreviewModal = ref(false);
const previewCardIndex = ref(0);
const previewRevealedHint = ref(false);
const previewSelectedCategory = ref(null);
const previewFeedbackState = ref(null); // 'correct' | 'try_again'

const currentPreviewCard = computed(() => {
    return form.cards[previewCardIndex.value] || form.cards[0];
});

const openLivePreview = () => {
    previewCardIndex.value = 0;
    previewRevealedHint.value = false;
    previewSelectedCategory.value = null;
    previewFeedbackState.value = null;
    showPreviewModal.value = true;
};

const closeLivePreview = () => {
    showPreviewModal.value = false;
};

const togglePreviewHint = () => {
    previewRevealedHint.value = !previewRevealedHint.value;
};

const handlePreviewCategoryClick = (category) => {
    previewSelectedCategory.value = category.type;
    if (category.type === currentPreviewCard.value.conflict_type) {
        previewFeedbackState.value = 'correct';
    } else {
        previewFeedbackState.value = 'try_again';
    }
};

const nextPreviewCard = () => {
    if (previewCardIndex.value < form.cards.length - 1) {
        previewCardIndex.value++;
        previewRevealedHint.value = false;
        previewSelectedCategory.value = null;
        previewFeedbackState.value = null;
    }
};

const prevPreviewCard = () => {
    if (previewCardIndex.value > 0) {
        previewCardIndex.value--;
        previewRevealedHint.value = false;
        previewSelectedCategory.value = null;
        previewFeedbackState.value = null;
    }
};

const handleBack = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const submitQuiz = () => {
    form.post(`/teachers/modules/${props.module.slug}/quizzes/color-the-conflict`, {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head :title="`Create Color the Conflict • ${module.title}`" />

    <div class="color-the-conflict-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Top Header Navigation -->
        <header class="studio-header">
            <div class="header-left">
                <button type="button" class="neu-btn secondary-btn" @click="handleBack">
                    ← Back to Journey
                </button>
                <div class="header-titles">
                    <h1 class="page-title">
                        🎨 Color the Conflict Quiz Studio
                    </h1>
                </div>
            </div>

            <div class="header-actions">
                <button
                    type="button"
                    class="neu-btn preview-action-btn"
                    @click="openLivePreview"
                >
                    👀 Student Preview
                </button>
                <button
                    type="button"
                    class="neu-btn primary-action-btn"
                    :disabled="form.processing"
                    @click="submitQuiz"
                >
                    <span v-if="form.processing">Saving Quiz...</span>
                    <span v-else>💾 Publish Quiz Challenge</span>
                </button>
            </div>
        </header>

        <!-- Main Form Container -->
        <form @submit.prevent="submitQuiz" class="studio-layout">
            <!-- Left Column: Quiz Info & Scenarios Context -->
            <div class="studio-sidebar">
                <!-- Quiz Core Settings Neu-Card -->
                <div class="neu-card">
                    <div class="card-header">
                        <span class="card-icon">⚙️</span>
                        <h2 class="card-title">Quiz Settings</h2>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quiz-title">
                            Quiz Title <span class="required">*</span>
                        </label>
                        <input
                            id="quiz-title"
                            v-model="form.title"
                            type="text"
                            class="neu-input"
                            placeholder="e.g. Color the Conflict: The Storm's Test"
                            required
                        />
                        <div v-if="form.errors.title" class="input-error">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quiz-description">
                            Instructions / Overview
                        </label>
                        <textarea
                            id="quiz-description"
                            v-model="form.description"
                            class="neu-textarea small-textarea"
                            placeholder="Read each excerpt carefully, uncover any hints if needed, then click the matching colored category to identify the literary conflict."
                            rows="3"
                        ></textarea>
                    </div>

                    <!-- XP Reward -->
                    <div class="form-group">
                        <label class="form-label">
                            XP Reward: <strong>{{ form.xp_reward }} XP</strong>
                        </label>
                        <div class="preset-pill-row">
                            <button
                                v-for="xp in xpPresets"
                                :key="xp"
                                type="button"
                                class="preset-pill"
                                :class="{ active: form.xp_reward === xp }"
                                @click="form.xp_reward = xp"
                            >
                                +{{ xp }} XP
                            </button>
                        </div>
                    </div>

                    <!-- Passing Score -->
                    <div class="form-group">
                        <label class="form-label">
                            Passing Score: <strong>{{ form.passing_percentage }}%</strong>
                        </label>
                        <div class="preset-pill-row">
                            <button
                                v-for="pct in passPresets"
                                :key="pct"
                                type="button"
                                class="preset-pill"
                                :class="{ active: form.passing_percentage === pct }"
                                @click="form.passing_percentage = pct"
                            >
                                {{ pct }}%
                            </button>
                        </div>
                    </div>

                    <div class="meta-stats-box">
                        <div class="stat-item">
                            <span class="stat-label">Total Cards</span>
                            <span class="stat-value">{{ form.cards.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total Score</span>
                            <span class="stat-value">{{ totalPoints }} pts</span>
                        </div>
                    </div>
                </div>

                <!-- Scenario Contexts Neu-Card (Separated Context Section) -->
                <div class="neu-card context-section-card">
                    <div class="card-header">
                        <span class="card-icon">📖</span>
                        <div class="header-text-group">
                            <h2 class="card-title">Scenario Contexts</h2>
                            <p class="card-subtitle">
                                Story background or world-building basis for this quiz challenge.
                            </p>
                        </div>
                    </div>

                    <div
                        v-for="(ctx, cIdx) in form.contexts"
                        :key="cIdx"
                        class="context-entry-neu"
                    >
                        <div class="context-entry-header">
                            <span class="context-index-badge">
                                🔖 Context #{{ cIdx + 1 }}
                            </span>
                            <button
                                v-if="form.contexts.length > 1"
                                type="button"
                                class="remove-btn"
                                title="Remove this context"
                                @click="removeContext(cIdx)"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Scenario Narrative
                            </label>
                            <textarea
                                v-model="ctx.scenario"
                                class="neu-textarea small-textarea"
                                placeholder="Describe the scene context or story arc setting that provides background for the excerpts..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Context Hint
                            </label>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="neu-input"
                                placeholder="Clue for students about this scenario's themes..."
                            />
                        </div>
                    </div>

                    <button
                        type="button"
                        class="neu-btn add-context-btn"
                        @click="addContext"
                    >
                        ➕ Add Another Scenario Context
                    </button>
                </div>
            </div>

            <!-- Right Column: 6 Sequential Flashcards -->
            <div class="studio-cards-column">
                <div class="cards-column-header">
                    <div class="header-info">
                        <h2 class="section-heading">
                            🎴 Flashcard Sequence (Card X of {{ form.cards.length }})
                        </h2>
                        <p class="section-subheading">
                            Learners will read each excerpt sequentially and tap the matching color-coded category button.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="neu-btn add-card-top-btn"
                        @click="addCard"
                    >
                        ➕ Add Flashcard
                    </button>
                </div>

                <!-- List of Flashcards -->
                <div class="cards-list">
                    <div
                        v-for="(card, index) in form.cards"
                        :key="index"
                        class="neu-card flashcard-editor-card"
                    >
                        <!-- Card Ribbon Header -->
                        <div class="card-editor-top">
                            <div class="card-counter-badge">
                                <span class="counter-number">Card {{ index + 1 }} of {{ form.cards.length }}</span>
                                <span class="target-type-pill" :class="`pill-${card.conflict_type}`">
                                    {{ conflictCategories.find(c => c.type === card.conflict_type)?.label || 'Conflict' }}
                                </span>
                            </div>

                            <div class="card-top-actions">
                                <div class="points-input-wrap">
                                    <label class="points-label">Points:</label>
                                    <input
                                        v-model.number="card.points"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="neu-input points-input"
                                    />
                                </div>
                                <button
                                    v-if="form.cards.length > 1"
                                    type="button"
                                    class="remove-card-btn"
                                    title="Delete this card"
                                    @click="removeCard(index)"
                                >
                                    🗑️ Remove
                                </button>
                            </div>
                        </div>

                        <!-- Excerpt Textarea -->
                        <div class="form-group">
                            <label class="form-label">
                                Excerpt / Story Passage <span class="required">*</span>
                            </label>
                            <textarea
                                v-model="card.excerpt"
                                class="neu-textarea excerpt-textarea"
                                placeholder="Paste or type the narrative excerpt for learners to read and analyze..."
                                rows="3"
                                required
                            ></textarea>
                            <div
                                v-if="form.errors[`cards.${index}.excerpt`]"
                                class="input-error"
                            >
                                {{ form.errors[`cards.${index}.excerpt`] }}
                            </div>
                        </div>

                        <!-- Target Conflict Category Selection (Color Coded Buttons) -->
                        <div class="form-group">
                            <label class="form-label">
                                Correct Conflict Category <span class="required">*</span>
                                <span class="helper-text">(Click to select the target correct answer)</span>
                            </label>

                            <div class="category-button-grid">
                                <button
                                    v-for="cat in conflictCategories"
                                    :key="cat.type"
                                    type="button"
                                    class="category-select-btn"
                                    :class="[
                                        `cat-${cat.colorKey}`,
                                        { active: card.conflict_type === cat.type }
                                    ]"
                                    @click="selectCardCategory(index, cat.type)"
                                >
                                    <div class="cat-btn-inner">
                                        <span class="cat-icon">{{ cat.icon }}</span>
                                        <div class="cat-text">
                                            <span class="cat-title">{{ cat.label }}</span>
                                            <span class="cat-subtitle">{{ cat.subtitle }}</span>
                                        </div>
                                    </div>
                                    <span v-if="card.conflict_type === cat.type" class="cat-check">
                                        ✓ Correct Answer
                                    </span>
                                </button>
                            </div>
                            <div
                                v-if="form.errors[`cards.${index}.conflict_type`]"
                                class="input-error"
                            >
                                {{ form.errors[`cards.${index}.conflict_type`] }}
                            </div>
                        </div>

                        <!-- Scaffolding / Need a Hint Input -->
                        <div class="form-group hint-feedback-group">
                            <div class="group-row">
                                <div class="sub-input-col">
                                    <label class="form-label">
                                        💡 "Need a Hint?" Guidance (Optional)
                                    </label>
                                    <input
                                        v-model="card.hint"
                                        type="text"
                                        class="neu-input"
                                        placeholder="e.g. Look at who or what is opposing the main character..."
                                    />
                                    <span class="sub-helper">
                                        Revealed when learner taps the collapsible hint button.
                                    </span>
                                </div>

                                <div class="sub-input-col">
                                    <label class="form-label">
                                        ✅ Reinforcement Feedback (Explanation)
                                    </label>
                                    <input
                                        v-model="card.explanation"
                                        type="text"
                                        class="neu-input"
                                        placeholder="e.g. Correct! The harsh storm and freezing winds are natural forces."
                                    />
                                    <span class="sub-helper">
                                        Displayed immediately upon selecting the correct category.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Add Card Button -->
                <div class="cards-bottom-action">
                    <button
                        type="button"
                        class="neu-btn add-card-big-btn"
                        @click="addCard"
                    >
                        ➕ Add Another Conflict Card
                    </button>
                </div>
            </div>
        </form>

        <!-- Live Student Preview Modal -->
        <div v-if="showPreviewModal" class="preview-backdrop" @click.self="closeLivePreview">
            <div class="preview-modal neu-card">
                <div class="preview-modal-header">
                    <div class="preview-header-left">
                        <span class="preview-pill">Student Quiz Experience Preview</span>
                        <h2 class="preview-title">{{ form.title || 'Color the Conflict Challenge' }}</h2>
                    </div>
                    <button type="button" class="close-modal-btn" @click="closeLivePreview">✕</button>
                </div>

                <!-- Flashcard Simulation Container -->
                <div class="simulation-body">
                    <!-- Progress Step Indicator -->
                    <div class="progression-bar-wrap">
                        <div class="progression-label">
                            <span>Card <strong>{{ previewCardIndex + 1 }}</strong> of {{ form.cards.length }}</span>
                            <span class="xp-badge">+{{ currentPreviewCard.points }} XP</span>
                        </div>
                        <div class="progression-track">
                            <div
                                class="progression-fill"
                                :style="{ width: `${((previewCardIndex + 1) / form.cards.length) * 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- The Excerpt Flashcard -->
                    <div class="simulated-flashcard">
                        <div class="card-ribbon">
                            <span class="ribbon-text">Story Excerpt</span>
                        </div>
                        <p class="simulated-excerpt">
                            {{ currentPreviewCard.excerpt || 'No excerpt written yet for this card.' }}
                        </p>

                        <!-- Optional Collapsible Hint Button -->
                        <div class="simulated-hint-container">
                            <button
                                type="button"
                                class="hint-toggle-btn"
                                @click="togglePreviewHint"
                            >
                                💡 {{ previewRevealedHint ? 'Hide Hint' : 'Need a Hint?' }}
                            </button>
                            <div v-if="previewRevealedHint" class="hint-reveal-bubble">
                                <strong>Hint:</strong> {{ currentPreviewCard.hint || 'No hint provided for this card.' }}
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Display Banner -->
                    <div
                        v-if="previewFeedbackState"
                        class="feedback-banner"
                        :class="previewFeedbackState"
                    >
                        <div v-if="previewFeedbackState === 'correct'" class="feedback-content">
                            <span class="feedback-icon">🎉</span>
                            <div class="feedback-text">
                                <strong>Awesome job! That's correct!</strong>
                                <p>{{ currentPreviewCard.explanation || 'You identified the right conflict!' }}</p>
                            </div>
                        </div>
                        <div v-else class="feedback-content">
                            <span class="feedback-icon">🤔</span>
                            <div class="feedback-text">
                                <strong>Not quite! Try again:</strong>
                                <p>{{ currentPreviewCard.feedback_incorrect || 'Reread the excerpt and think about the opposing force.' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Category Tap Buttons -->
                    <div class="simulated-categories-grid">
                        <button
                            v-for="cat in conflictCategories"
                            :key="cat.type"
                            type="button"
                            class="simulated-cat-btn"
                            :class="[
                                `sim-cat-${cat.colorKey}`,
                                {
                                    selected: previewSelectedCategory === cat.type,
                                    correct: previewFeedbackState === 'correct' && currentPreviewCard.conflict_type === cat.type
                                }
                            ]"
                            @click="handlePreviewCategoryClick(cat)"
                        >
                            <span class="sim-icon">{{ cat.icon }}</span>
                            <span class="sim-label">{{ cat.label }}</span>
                        </button>
                    </div>

                    <!-- Navigation Footer in Modal -->
                    <div class="preview-modal-footer">
                        <button
                            type="button"
                            class="neu-btn secondary-btn"
                            :disabled="previewCardIndex === 0"
                            @click="prevPreviewCard"
                        >
                            ← Previous Card
                        </button>
                        <button
                            type="button"
                            class="neu-btn primary-action-btn"
                            :disabled="previewCardIndex === form.cards.length - 1"
                            @click="nextPreviewCard"
                        >
                            Next Card →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.color-the-conflict-page {
    font-family: 'Nunito', sans-serif;
    min-height: 100vh;
    background: #f0f7ff;
    padding: 1.5rem 2rem 4rem;
    color: #1e293b;
}

/* Header */
.studio-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border: 3px solid #1e293b;
    border-radius: 20px;
    padding: 1rem 1.5rem;
    box-shadow: 0 6px 0 #1e293b;
    margin-bottom: 2rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.header-titles {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.step-pill {
    display: inline-block;
    background: #dbeafe;
    color: #1e40af;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.65rem;
    border-radius: 8px;
    width: fit-content;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.page-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Neu Buttons */
.neu-btn {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    padding: 0.65rem 1.25rem;
    border: 3px solid #1e293b;
    border-radius: 14px;
    cursor: pointer;
    box-shadow: 0 4px 0 #1e293b;
    transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.neu-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #1e293b;
}

.neu-btn:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #1e293b;
}

.neu-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.secondary-btn {
    background: #ffffff;
    color: #1e293b;
}

.preview-action-btn {
    background: #e0e7ff;
    color: #3730a3;
}

.primary-action-btn {
    background: #10b981;
    color: #ffffff;
}

/* Layout */
.studio-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .studio-layout {
        grid-template-columns: 1fr;
    }
}

/* Neu Cards */
.neu-card {
    background: #ffffff;
    border: 3px solid #1e293b;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 6px 0 #1e293b;
    margin-bottom: 1.5rem;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.75rem;
}

.card-icon {
    font-size: 1.5rem;
}

.card-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.card-subtitle {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
}

/* Form Controls */
.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-weight: 800;
    font-size: 0.88rem;
    color: #334155;
    margin-bottom: 0.4rem;
}

.required {
    color: #ef4444;
}

.helper-text {
    font-weight: 600;
    font-size: 0.8rem;
    color: #64748b;
    margin-left: 0.4rem;
}

.neu-input {
    width: 100%;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.65rem 0.85rem;
    font-family: 'Nunito', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.neu-input:focus {
    outline: none;
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.neu-textarea {
    width: 100%;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
    font-family: 'Nunito', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    background: #f8fafc;
    resize: vertical;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.neu-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.small-textarea {
    min-height: 70px;
}

.excerpt-textarea {
    min-height: 90px;
    font-size: 1rem;
    line-height: 1.5;
}

.input-error {
    color: #ef4444;
    font-size: 0.8rem;
    font-weight: 700;
    margin-top: 0.3rem;
}

/* Preset Pills */
.preset-pill-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.4rem;
}

.preset-pill {
    background: #f1f5f9;
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.3rem 0.65rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.preset-pill.active {
    background: #3b82f6;
    border-color: #1d4ed8;
    color: #ffffff;
}

/* Stats Box */
.meta-stats-box {
    display: flex;
    justify-content: space-around;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 14px;
    padding: 0.75rem;
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
}

.stat-value {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.2rem;
    font-weight: 800;
    color: #1e293b;
}

/* Context Scenarios Section */
.context-section-card {
    border-top: 6px solid #8b5cf6;
}

.context-entry-neu {
    background: #faf5ff;
    border: 2px solid #d8b4fe;
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.context-entry-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.65rem;
}

.context-index-badge {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #7c3aed;
}

.remove-btn {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #dc2626;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    font-weight: 800;
    cursor: pointer;
}

.add-context-btn {
    width: 100%;
    background: #ede9fe;
    color: #6d28d9;
    font-size: 0.9rem;
    padding: 0.65rem;
}

/* Cards Column */
.cards-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-heading {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.section-subheading {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0.2rem 0 0 0;
}

.add-card-top-btn {
    background: #ecfdf5;
    color: #047857;
}

/* Flashcard Editor Card */
.flashcard-editor-card {
    position: relative;
    border-left: 8px solid #3b82f6;
    margin-bottom: 2rem;
}

.card-editor-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.card-counter-badge {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.counter-number {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e293b;
}

.target-type-pill {
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
    text-transform: uppercase;
}

.pill-character_vs_character { background: #fee2e2; color: #b91c1c; }
.pill-character_vs_society { background: #dbeafe; color: #1d4ed8; }
.pill-character_vs_nature { background: #d1fae5; color: #047857; }
.pill-character_vs_self { background: #fef3c7; color: #b45309; }

.card-top-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.points-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.points-label {
    font-size: 0.82rem;
    font-weight: 800;
    color: #64748b;
}

.points-input {
    width: 65px;
    text-align: center;
    padding: 0.35rem;
    font-size: 0.9rem;
}

.remove-card-btn {
    background: #fff1f2;
    border: 2px solid #fecdd3;
    border-radius: 10px;
    padding: 0.4rem 0.75rem;
    font-weight: 800;
    font-size: 0.8rem;
    color: #e11d48;
    cursor: pointer;
    transition: all 0.15s ease;
}

.remove-card-btn:hover {
    background: #ffe4e6;
    border-color: #fda4af;
}

/* Category Button Grid */
.category-button-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 0.75rem;
}

.category-select-btn {
    position: relative;
    border: 3px solid #cbd5e1;
    border-radius: 16px;
    padding: 0.85rem 1rem;
    background: #ffffff;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 0 #cbd5e1;
}

.category-select-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #cbd5e1;
}

.cat-btn-inner {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.cat-icon {
    font-size: 1.4rem;
}

.cat-text {
    display: flex;
    flex-direction: column;
}

.cat-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #1e293b;
}

.cat-subtitle {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 0.15rem;
    line-height: 1.3;
}

.cat-check {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    font-weight: 800;
    text-align: right;
    text-transform: uppercase;
}

/* Category Active Color Themes */
.category-select-btn.cat-red.active {
    background: #fef2f2;
    border-color: #ef4444;
    box-shadow: 0 5px 0 #b91c1c;
}
.category-select-btn.cat-red.active .cat-title { color: #b91c1c; }
.category-select-btn.cat-red.active .cat-check { color: #b91c1c; }

.category-select-btn.cat-blue.active {
    background: #eff6ff;
    border-color: #3b82f6;
    box-shadow: 0 5px 0 #1d4ed8;
}
.category-select-btn.cat-blue.active .cat-title { color: #1d4ed8; }
.category-select-btn.cat-blue.active .cat-check { color: #1d4ed8; }

.category-select-btn.cat-green.active {
    background: #ecfdf5;
    border-color: #10b981;
    box-shadow: 0 5px 0 #047857;
}
.category-select-btn.cat-green.active .cat-title { color: #047857; }
.category-select-btn.cat-green.active .cat-check { color: #047857; }

.category-select-btn.cat-amber.active {
    background: #fffbeb;
    border-color: #f59e0b;
    box-shadow: 0 5px 0 #b45309;
}
.category-select-btn.cat-amber.active .cat-title { color: #b45309; }
.category-select-btn.cat-amber.active .cat-check { color: #b45309; }

/* Sub Inputs Row */
.group-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .group-row {
        grid-template-columns: 1fr;
    }
}

.sub-helper {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.add-card-big-btn {
    width: 100%;
    background: #ffffff;
    border-style: dashed;
    padding: 1rem;
    font-size: 1.1rem;
    color: #2563eb;
}

/* Live Preview Modal */
.preview-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1.5rem;
}

.preview-modal {
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border: 4px solid #1e293b;
    border-radius: 24px;
    padding: 2rem;
    box-shadow: 0 12px 0 #1e293b;
}

.preview-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.preview-pill {
    display: inline-block;
    background: #fef3c7;
    color: #b45309;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
    text-transform: uppercase;
    margin-bottom: 0.35rem;
}

.preview-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0;
}

.close-modal-btn {
    background: #f1f5f9;
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    width: 32px;
    height: 32px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
}

/* Progression Bar */
.progression-bar-wrap {
    margin-bottom: 1.5rem;
}

.progression-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    font-weight: 800;
    color: #475569;
    margin-bottom: 0.4rem;
}

.xp-badge {
    background: #ecfdf5;
    color: #047857;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
}

.progression-track {
    width: 100%;
    height: 12px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    border: 2px solid #cbd5e1;
}

.progression-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #10b981);
    transition: width 0.3s ease;
}

/* Simulated Flashcard */
.simulated-flashcard {
    background: #f8fafc;
    border: 3px solid #1e293b;
    border-radius: 18px;
    padding: 1.75rem;
    box-shadow: 0 6px 0 #1e293b;
    position: relative;
    margin-bottom: 1.5rem;
}

.card-ribbon {
    position: absolute;
    top: -12px;
    left: 20px;
    background: #3b82f6;
    color: #ffffff;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    border: 2px solid #1e293b;
}

.simulated-excerpt {
    font-size: 1.05rem;
    line-height: 1.6;
    color: #1e293b;
    font-weight: 600;
    margin: 0.5rem 0 1.25rem 0;
}

.simulated-hint-container {
    border-top: 1px dashed #cbd5e1;
    padding-top: 0.75rem;
}

.hint-toggle-btn {
    background: #fffbeb;
    border: 2px solid #fde68a;
    border-radius: 10px;
    padding: 0.35rem 0.85rem;
    font-size: 0.85rem;
    font-weight: 800;
    color: #b45309;
    cursor: pointer;
    box-shadow: 0 2px 0 #fde68a;
}

.hint-reveal-bubble {
    background: #fefce8;
    border: 2px solid #fef08a;
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    margin-top: 0.5rem;
    font-size: 0.88rem;
    color: #854d0e;
    animation: fadeIn 0.2s ease;
}

/* Feedback Banner */
.feedback-banner {
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 3px solid;
    animation: bounceIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.feedback-banner.correct {
    background: #ecfdf5;
    border-color: #10b981;
    color: #065f46;
}

.feedback-banner.try_again {
    background: #fff1f2;
    border-color: #f43f5e;
    color: #9f1239;
}

.feedback-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.feedback-icon {
    font-size: 1.75rem;
}

.feedback-text p {
    margin: 0.2rem 0 0 0;
    font-size: 0.88rem;
}

/* Simulated Category Buttons */
.simulated-categories-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.85rem;
    margin-bottom: 1.5rem;
}

.simulated-cat-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
    border: 3px solid #1e293b;
    border-radius: 14px;
    padding: 0.85rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 0 #1e293b;
    transition: all 0.1s ease;
}

.simulated-cat-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #1e293b;
}

.simulated-cat-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #1e293b;
}

.sim-cat-red { background: #fee2e2; color: #b91c1c; }
.sim-cat-blue { background: #dbeafe; color: #1d4ed8; }
.sim-cat-green { background: #d1fae5; color: #047857; }
.sim-cat-amber { background: #fef3c7; color: #b45309; }

.simulated-cat-btn.selected {
    box-shadow: 0 0 0 4px #1e293b, 0 6px 0 #1e293b;
}

.simulated-cat-btn.correct {
    background: #10b981 !important;
    color: #ffffff !important;
}

/* Modal Footer */
.preview-modal-footer {
    display: flex;
    justify-content: space-between;
    border-top: 2px dashed #e2e8f0;
    padding-top: 1rem;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes bounceIn {
    0% { transform: scale(0.95); opacity: 0; }
    60% { transform: scale(1.02); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
