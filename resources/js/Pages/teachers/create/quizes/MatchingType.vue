<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { generateQuizAssist } from '@/Services/geminiAi.js';

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

const xpPresets = [100, 150, 200, 220, 250];
const passPresets = [60, 70, 75, 80, 90];

// Helper to letter choices (0 -> A, 1 -> B, ...)
const getLetter = (index) => String.fromCharCode(65 + (index % 26));

// Initial default blank choices for Column B
const defaultChoices = [
    { choice_letter: 'A', choice_text: '' },
    { choice_letter: 'B', choice_text: '' },
    { choice_letter: 'C', choice_text: '' },
    { choice_letter: 'D', choice_text: '' },
];

// Helper to create a single default item for Column A
const createDefaultItem = (number = 1, defaultChoiceLetter = 'A') => ({
    item_number: number,
    description: '',
    correct_choice_letter: defaultChoiceLetter,
    hint: '',
    explanation: '',
    points: 10,
    sort_order: number,
});

// Setup form with clean empty inputs
const form = useForm({
    title: '',
    description: '',
    xp_reward: 220,
    passing_percentage: 70,
    time_limit_seconds: null,
    is_published: true,
    contexts: [
        {
            scenario: '',
            hint: '',
        },
    ],
    choices: JSON.parse(JSON.stringify(defaultChoices)),
    items: [
        createDefaultItem(1, 'A'),
    ],
});

// Scenario Context Management
const addContext = () => {
    form.contexts.push({ scenario: '', hint: '' });
};

const removeContext = (index) => {
    if (form.contexts.length > 1) {
        form.contexts.splice(index, 1);
    } else {
        form.contexts[0] = { scenario: '', hint: '' };
    }
};

// Column B Choice Management
const addChoice = () => {
    if (form.choices.length < 26) {
        const nextLetter = getLetter(form.choices.length);
        form.choices.push({
            choice_letter: nextLetter,
            choice_text: '',
        });
    }
};

const removeChoice = (index) => {
    if (form.choices.length > 2) {
        const removedLetter = form.choices[index].choice_letter;
        form.choices.splice(index, 1);
        // Re-letter remaining choices
        form.choices.forEach((c, idx) => {
            c.choice_letter = getLetter(idx);
        });
        // If any item was pointing to the removed choice or an invalid choice, fallback to choice 0
        form.items.forEach((item) => {
            if (!form.choices.some((c) => c.choice_letter === item.correct_choice_letter)) {
                item.correct_choice_letter = form.choices[0].choice_letter;
            }
        });
    }
};

// Gemini AI Matching Quiz Assistant
const isGeneratingAiPairs = ref(false);
const aiMatchingError = ref('');

const generateMatchingWithAi = async () => {
    isGeneratingAiPairs.value = true;
    aiMatchingError.value = '';

    try {
        const result = await generateQuizAssist({
            quizType: 'matching_type',
            topicOrPassage: form.description || form.title || props.module.title,
            questionCount: 4,
            gradeLevel: props.module.grade_level,
        });

        if (result) {
            if (result.title) {
                form.title = result.title;
            }
            if (result.description) {
                form.description = result.description;
            }
            if (Array.isArray(result.choices) && result.choices.length > 0) {
                form.choices = result.choices.map((c, idx) => ({
                    choice_letter: c.choice_letter || getLetter(idx),
                    choice_text: c.choice_text || '',
                }));
            }
            if (Array.isArray(result.items) && result.items.length > 0) {
                form.items = result.items.map((item, idx) => ({
                    item_number: idx + 1,
                    description: item.description || '',
                    correct_choice_letter: item.correct_choice_letter || (form.choices[0]?.choice_letter || 'A'),
                    hint: item.hint || '',
                    explanation: item.explanation || '',
                    points: Number(item.points) || 10,
                    sort_order: idx + 1,
                }));
            }
        }
    } catch (err) {
        aiMatchingError.value = err.message || 'Gemini generation failed.';
        alert(aiMatchingError.value);
    } finally {
        isGeneratingAiPairs.value = false;
    }
};

// Column A Item Management
const addItem = () => {
    if (form.items.length < 30) {
        const nextNum = form.items.length + 1;
        const defaultTarget = form.choices[0]?.choice_letter || 'A';
        form.items.push(createDefaultItem(nextNum, defaultTarget));
    }
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
        form.items.forEach((item, idx) => {
            item.item_number = idx + 1;
            item.sort_order = idx + 1;
        });
    }
};

// Stats
const totalPoints = computed(() => {
    return form.items.reduce((sum, item) => sum + (parseInt(item.points, 10) || 0), 0);
});

// Student Simulation Preview State
const showPreviewModal = ref(false);
const studentSelections = ref({}); // { [itemIndex]: 'A' | 'B' ... }
const previewChecked = ref(false);

const openLivePreview = () => {
    studentSelections.value = {};
    previewChecked.value = false;
    showPreviewModal.value = true;
};

const closeLivePreview = () => {
    showPreviewModal.value = false;
};

const checkStudentMatches = () => {
    previewChecked.value = true;
};

const handleBack = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const submitQuiz = () => {
    form.post(`/teachers/modules/${props.module.slug}/quizzes/matching-type`, {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head :title="`Create Matching Type • ${module.title}`" />

    <div class="matching-type-page">
        <!-- Top Header Navigation -->
        <header class="studio-header">
            <div class="header-left">
                <button type="button" class="neu-btn secondary-btn" @click="handleBack">
                    ← Back to Journey
                </button>
                <div class="header-titles">
                    <span class="step-pill">
                        🎯 Step {{ module.next_sort_order }} Milestone • Matching Type
                    </span>
                    <h1 class="page-title">
                        🧩 Matching Type Quiz Studio
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
            <!-- Left Column: Quiz Info & Scenario Contexts -->
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
                            placeholder="e.g. Matching Type: Sabutan Weavers of Aurora"
                            required
                        />
                        <div v-if="form.errors.title" class="input-error">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quiz-directions">
                            Directions for Students <span class="required">*</span>
                        </label>
                        <textarea
                            id="quiz-directions"
                            v-model="form.description"
                            class="neu-textarea small-textarea"
                            rows="3"
                            required
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
                            <span class="stat-label">Column B Choices</span>
                            <span class="stat-value">{{ form.choices.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Column A Items</span>
                            <span class="stat-value">{{ form.items.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total Score</span>
                            <span class="stat-value">{{ totalPoints }} pts</span>
                        </div>
                    </div>
                </div>

                <!-- Scenario Contexts Neu-Card (Shared quiz_contexts) -->
                <div class="neu-card context-section-card">
                    <div class="card-header">
                        <span class="card-icon">📖</span>
                        <div class="header-text-group">
                            <h2 class="card-title">Scenario Contexts</h2>
                            <p class="card-subtitle">
                                The story world or narrative setting supporting this matching activity.
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
                            <label class="form-label">Scenario Narrative</label>
                            <textarea
                                v-model="ctx.scenario"
                                class="neu-textarea small-textarea"
                                placeholder="e.g. In the coastal town of San Luis, Aurora, Aling Rosa and Aling Marta struggle with the town council policy and the sudden tropical storm..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Context Hint</label>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="neu-input"
                                placeholder="Clue for learners about characters, places, and conflicts..."
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

            <!-- Right Column: Column B (Choices) & Column A (Descriptions) -->
            <div class="studio-cards-column">
                <!-- Column B (Choices Pool) Neu-Card -->
                <div class="neu-card choices-pool-card">
                    <div class="choices-header-bar">
                        <div class="header-info">
                            <h2 class="section-heading">
                                📋 Column B: Answer Choices Pool (A, B, C...)
                            </h2>
                            <p class="section-subheading">
                                These options will appear in the dropdowns and in the reference list on the student's screen.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="neu-btn add-choice-btn"
                            @click="addChoice"
                        >
                            ➕ Add Choice
                        </button>
                    </div>

                    <div class="choices-input-grid">
                        <div
                            v-for="(choice, cIdx) in form.choices"
                            :key="cIdx"
                            class="choice-row-neu"
                        >
                            <span class="choice-letter-badge">{{ choice.choice_letter }}.</span>
                            <input
                                v-model="choice.choice_text"
                                type="text"
                                class="neu-input choice-input"
                                :placeholder="`Choice ${choice.choice_letter} text (e.g. San Luis)`"
                                required
                            />
                            <button
                                v-if="form.choices.length > 2"
                                type="button"
                                class="remove-choice-btn"
                                title="Delete this choice"
                                @click="removeChoice(cIdx)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Column A (Descriptions Builder) -->
                <div class="items-header-bar">
                    <div class="header-info">
                        <h2 class="section-heading">
                            📝 Column A: Description Prompts (Item 1, 2...)
                        </h2>
                        <p class="section-subheading">
                            Each item describes an event from the story. Select the correct matching option from Column B.
                        </p>
                    </div>
                    <div class="items-actions-row">
                        <button
                            type="button"
                            class="neu-btn add-item-btn"
                            @click="addItem"
                        >
                            ➕ Add Matching Item
                        </button>
                        <button
                            type="button"
                            class="neu-btn ai-matching-btn"
                            :disabled="isGeneratingAiPairs"
                            @click="generateMatchingWithAi"
                        >
                            <span v-if="isGeneratingAiPairs">⏳ Generating...</span>
                            <span v-else>✨ AI Auto-Generate Pairs</span>
                        </button>
                    </div>
                </div>

                <!-- Items List -->
                <div class="items-list">
                    <div
                        v-for="(item, iIdx) in form.items"
                        :key="iIdx"
                        class="neu-card item-editor-card"
                    >
                        <div class="item-card-top">
                            <div class="item-number-wrap">
                                <span class="item-number-badge">Item {{ item.item_number }}</span>
                                <span class="target-match-tag">
                                    Matches: <strong>{{ item.correct_choice_letter }}. {{ form.choices.find(c => c.choice_letter === item.correct_choice_letter)?.choice_text || 'Choice' }}</strong>
                                </span>
                            </div>

                            <div class="card-top-actions">
                                <div class="points-input-wrap">
                                    <label class="points-label">Points:</label>
                                    <input
                                        v-model.number="item.points"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="neu-input points-input"
                                    />
                                </div>
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    class="remove-card-btn"
                                    title="Delete this item"
                                    @click="removeItem(iIdx)"
                                >
                                    🗑️ Remove
                                </button>
                            </div>
                        </div>

                        <!-- Description Prompt Text -->
                        <div class="form-group">
                            <label class="form-label">
                                Description Prompt (What happened in the story) <span class="required">*</span>
                            </label>
                            <textarea
                                v-model="item.description"
                                class="neu-textarea item-textarea"
                                placeholder="e.g. 1. The coastal town in Aurora where Aling Rosa and Aling Marta live."
                                rows="2"
                                required
                            ></textarea>
                            <div
                                v-if="form.errors[`items.${iIdx}.description`]"
                                class="input-error"
                            >
                                {{ form.errors[`items.${iIdx}.description`] }}
                            </div>
                        </div>

                        <!-- Target Choice Dropdown Selector -->
                        <div class="form-group target-choice-group">
                            <label class="form-label">
                                Correct Match from Column B <span class="required">*</span>
                            </label>
                            <select
                                v-model="item.correct_choice_letter"
                                class="neu-select target-select"
                                required
                            >
                                <option
                                    v-for="choice in form.choices"
                                    :key="choice.choice_letter"
                                    :value="choice.choice_letter"
                                >
                                    {{ choice.choice_letter }}. {{ choice.choice_text || '(Blank Choice)' }}
                                </option>
                            </select>
                        </div>

                        <!-- Scaffolding Hint & Explanation -->
                        <div class="form-group hint-feedback-group">
                            <div class="group-row">
                                <div class="sub-input-col">
                                    <label class="form-label">💡 Scaffolding Clue (Optional)</label>
                                    <input
                                        v-model="item.hint"
                                        type="text"
                                        class="neu-input"
                                        placeholder="e.g. Look back at the first paragraph where the setting is introduced..."
                                    />
                                </div>
                                <div class="sub-input-col">
                                    <label class="form-label">✅ Explanation / Learning Feedback</label>
                                    <input
                                        v-model="item.explanation"
                                        type="text"
                                        class="neu-input"
                                        placeholder="e.g. Correct! San Luis is the coastal weaving municipality in Aurora."
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Matching Item Bottom Button -->
                <div class="cards-bottom-action">
                    <button
                        type="button"
                        class="neu-btn add-card-big-btn"
                        @click="addItem"
                    >
                        ➕ Add Another Description Item
                    </button>
                </div>
            </div>
        </form>

        <!-- Live Student Simulation Preview Modal (Matches uploaded layout) -->
        <div v-if="showPreviewModal" class="preview-backdrop" @click.self="closeLivePreview">
            <div class="preview-modal neu-card">
                <div class="preview-modal-header">
                    <div class="preview-header-left">
                        <span class="preview-pill">Student Pace Activity Preview</span>
                        <h2 class="preview-title">{{ form.title || 'Matching Type' }}</h2>
                    </div>
                    <button type="button" class="close-modal-btn" @click="closeLivePreview">✕</button>
                </div>

                <div class="simulation-body">
                    <div class="sim-directions-banner">
                        <p class="sim-directions-text">
                            👉 <strong>{{ form.description }}</strong>
                        </p>
                    </div>

                    <!-- 2 Column Student Layout matching the user screenshot -->
                    <div class="sim-matching-grid">
                        <!-- Column A (Descriptions) -->
                        <div class="sim-column-a">
                            <h3 class="col-heading">COLUMN A (DESCRIPTIONS)</h3>

                            <div class="sim-items-list">
                                <div
                                    v-for="(item, idx) in form.items"
                                    :key="idx"
                                    class="sim-student-item-card"
                                    :class="{
                                        'is-correct': previewChecked && studentSelections[idx] === item.correct_choice_letter,
                                        'is-wrong': previewChecked && studentSelections[idx] !== item.correct_choice_letter && studentSelections[idx],
                                    }"
                                >
                                    <div class="card-item-bar">
                                        <span class="item-tag">Item {{ item.item_number }}</span>
                                        <select
                                            v-model="studentSelections[idx]"
                                            class="student-dropdown"
                                        >
                                            <option :value="undefined">-- Select --</option>
                                            <option
                                                v-for="choice in form.choices"
                                                :key="choice.choice_letter"
                                                :value="choice.choice_letter"
                                            >
                                                {{ choice.choice_letter }}. {{ choice.choice_text }}
                                            </option>
                                        </select>
                                    </div>

                                    <p class="item-desc-text">
                                        {{ item.description || 'No description entered yet.' }}
                                    </p>

                                    <!-- Feedback on check -->
                                    <div v-if="previewChecked" class="item-check-feedback">
                                        <span v-if="studentSelections[idx] === item.correct_choice_letter" class="feedback-good">
                                            ✅ Correct! {{ item.explanation }}
                                        </span>
                                        <span v-else class="feedback-bad">
                                            ❌ Correct answer was <strong>{{ item.correct_choice_letter }}. {{ form.choices.find(c => c.choice_letter === item.correct_choice_letter)?.choice_text }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column B (Choices Side Card) -->
                        <div class="sim-column-b">
                            <div class="choices-reference-card">
                                <h3 class="col-heading-b">COLUMN B (CHOICES)</h3>
                                <div class="reference-choices-list">
                                    <div
                                        v-for="choice in form.choices"
                                        :key="choice.choice_letter"
                                        class="ref-choice-pill"
                                    >
                                        <span class="ref-letter"><strong>{{ choice.choice_letter }}.</strong></span>
                                        <span class="ref-text">{{ choice.choice_text || '(Blank)' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="preview-modal-footer">
                        <button
                            type="button"
                            class="neu-btn secondary-btn"
                            @click="openLivePreview"
                        >
                            🔄 Reset Selections
                        </button>
                        <button
                            type="button"
                            class="neu-btn check-answers-btn"
                            @click="checkStudentMatches"
                        >
                            <span>✓ Check Matches</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.matching-type-page {
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
    background: #f3e8ff;
    color: #7e22ce;
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

.secondary-btn { background: #ffffff; color: #1e293b; }
.preview-action-btn { background: #e0e7ff; color: #3730a3; }
.primary-action-btn { background: #10b981; color: #ffffff; }

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
    box-sizing: border-box;
}

.neu-input:focus {
    outline: none;
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.neu-select {
    width: 100%;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.65rem 0.85rem;
    font-family: 'Nunito', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    background: #ffffff;
    box-sizing: border-box;
}

.neu-select:focus {
    outline: none;
    border-color: #3b82f6;
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

.item-textarea {
    min-height: 70px;
    font-size: 0.98rem;
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
}

.preset-pill.active {
    background: #8b5cf6;
    border-color: #6d28d9;
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

/* Choices Pool Card (Column B) */
.choices-pool-card {
    border-top: 6px solid #3b82f6;
    margin-bottom: 2rem;
}

.choices-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.section-heading {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.section-subheading {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0.2rem 0 0 0;
}

.add-choice-btn {
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.85rem;
}

.choices-input-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.75rem;
}

.choice-row-neu {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.4rem 0.6rem;
}

.choice-letter-badge {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: #1d4ed8;
    min-width: 25px;
}

.choice-input {
    padding: 0.4rem 0.65rem;
    font-size: 0.9rem;
}

.remove-choice-btn {
    background: #fee2e2;
    border: none;
    color: #ef4444;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    font-weight: 800;
    cursor: pointer;
}

/* Items Builder Bar (Column A) */
.items-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.add-item-btn {
    background: #f5f3ff;
    color: #6d28d9;
}

/* Item Editor Card */
.item-editor-card {
    border-left: 8px solid #8b5cf6;
    margin-bottom: 1.5rem;
}

.item-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.item-number-wrap {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.item-number-badge {
    background: #8b5cf6;
    color: #ffffff;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
}

.target-match-tag {
    font-size: 0.85rem;
    color: #475569;
}

.target-match-tag strong {
    color: #1d4ed8;
}

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
}

.target-choice-group {
    max-width: 450px;
}

.target-select {
    cursor: pointer;
}

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

.add-card-big-btn {
    width: 100%;
    background: #ffffff;
    border-style: dashed;
    padding: 1rem;
    font-size: 1.1rem;
    color: #7c3aed;
}

/* Student Simulation Preview Modal */
.preview-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1.5rem;
}

.preview-modal {
    width: 100%;
    max-width: 960px;
    max-height: 90vh;
    overflow-y: auto;
    background: #fdfbf7;
    border: 4px solid #1e293b;
    border-radius: 24px;
    padding: 2rem;
    box-shadow: 0 12px 0 #1e293b;
}

.preview-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px dashed #cbd5e1;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
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

.sim-directions-banner {
    background: #ffffff;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
}

.sim-directions-text {
    margin: 0;
    font-size: 0.95rem;
    color: #334155;
}

/* 2-Column Matching Layout */
.sim-matching-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
    margin-bottom: 2rem;
}

@media (max-width: 860px) {
    .sim-matching-grid {
        grid-template-columns: 1fr;
    }
}

.col-heading {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 800;
    color: #64748b;
    margin: 0 0 1rem 0;
    letter-spacing: 0.05em;
}

.sim-items-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.sim-student-item-card {
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.1rem;
    box-shadow: 0 2px 0 rgba(0, 0, 0, 0.04);
    transition: all 0.15s ease;
}

.sim-student-item-card.is-correct {
    border-color: #10b981;
    background: #f0fdf4;
}

.sim-student-item-card.is-wrong {
    border-color: #ef4444;
    background: #fef2f2;
}

.card-item-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.65rem;
    gap: 0.5rem;
}

.item-tag {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 800;
    color: #1e293b;
}

.student-dropdown {
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.4rem 0.75rem;
    font-family: 'Nunito', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    color: #1e293b;
    background: #ffffff;
    cursor: pointer;
    min-width: 140px;
}

.item-desc-text {
    font-size: 0.92rem;
    line-height: 1.5;
    color: #334155;
    margin: 0;
}

.item-check-feedback {
    margin-top: 0.65rem;
    padding-top: 0.5rem;
    border-top: 1px dashed #cbd5e1;
    font-size: 0.82rem;
    font-weight: 700;
}

.feedback-good { color: #047857; }
.feedback-bad { color: #b91c1c; }

/* Column B Side Card */
.choices-reference-card {
    background: #f4efe6;
    border: 2px solid #e2d9cc;
    border-radius: 18px;
    padding: 1.25rem;
    position: sticky;
    top: 1rem;
}

.col-heading-b {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.92rem;
    font-weight: 800;
    color: #64748b;
    margin: 0 0 1rem 0;
    letter-spacing: 0.05em;
}

.reference-choices-list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.ref-choice-pill {
    background: #ffffff;
    border: 1px solid #e2d9cc;
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    font-size: 0.88rem;
    color: #334155;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.ref-letter {
    color: #1e293b;
    font-size: 0.95rem;
}

.preview-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px dashed #cbd5e1;
    padding-top: 1.25rem;
}

.check-answers-btn {
    background: #8b5cf6;
    color: #ffffff;
}

/* Gemini AI Matching Assistant Styles */
.items-actions-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.ai-matching-btn {
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #FFFFFF !important;
    border: none !important;
    border-radius: 12px !important;
    font-family: var(--font-heading);
    font-weight: 800;
    box-shadow: 0 3px 0 #3730A3 !important;
    padding: 0.55rem 0.95rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ai-matching-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 0 #3730A3 !important;
}

.ai-matching-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

