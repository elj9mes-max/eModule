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

const xpPresets = [100, 150, 200, 250, 300];
const passPresets = [60, 70, 75, 80, 90];

// Helper to create a default evaluation item with empty defaults and rich placeholders
const createDefaultItem = (order = 1) => ({
    section_title: '',
    conflict_type: 'character_vs_character',
    question_prompt: '',
    step1_prompt: 'Step 1: Pick one option:',
    sentence_template: '',
    hint: '',
    points: 25,
    sort_order: order,
    options: [
        { option_text: '', stance_value: '', is_correct: false },
        { option_text: '', stance_value: '', is_correct: true },
    ],
});

// Form setup with clean empty inputs (relying on placeholders)
const form = useForm({
    title: '',
    description: '',
    xp_reward: 250,
    passing_percentage: 70,
    time_limit_seconds: null,
    is_published: true,
    contexts: [
        {
            scenario: '',
            hint: '',
        },
    ],
    word_helpers: [],
    items: [
        createDefaultItem(1),
    ],
});

// Quick Word Helper Input
const newWordInput = ref('');
const addWordHelper = () => {
    const trimmed = newWordInput.value.trim();
    if (trimmed && !form.word_helpers.includes(trimmed)) {
        form.word_helpers.push(trimmed);
        newWordInput.value = '';
    }
};

const removeWordHelper = (index) => {
    form.word_helpers.splice(index, 1);
};

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

// Item Management
const addItem = () => {
    if (form.items.length < 20) {
        const nextOrder = form.items.length + 1;
        const newItem = {
            section_title: '',
            conflict_type: 'character_vs_society',
            question_prompt: '',
            step1_prompt: 'Step 1: Pick one option:',
            sentence_template: '',
            hint: '',
            points: 25,
            sort_order: nextOrder,
            options: [
                { option_text: '', stance_value: '', is_correct: false },
                { option_text: '', stance_value: '', is_correct: true },
            ],
        };
        form.items.push(newItem);
    }
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
        form.items.forEach((item, idx) => {
            item.sort_order = idx + 1;
        });
    }
};

// Step 1 Options Management for an Item
const addOptionToItem = (item) => {
    if (item.options.length < 6) {
        item.options.push({
            option_text: '',
            stance_value: '',
            is_correct: false,
        });
    }
};

const removeOptionFromItem = (item, optIndex) => {
    if (item.options.length > 2) {
        item.options.splice(optIndex, 1);
    }
};

// Friendly Blank & Stance Inserters
const insertStanceToken = (item) => {
    item.sentence_template = (item.sentence_template || '') + ' [stance]';
};

const insertLongBlankToken = (item) => {
    item.sentence_template = (item.sentence_template || '') + ' [blank]';
};

// Calculations
const totalPoints = computed(() => {
    return form.items.reduce((sum, itm) => sum + (parseInt(itm.points, 10) || 0), 0);
});

// Student Simulation State
const showPreviewModal = ref(false);
const studentOptionPicks = ref({}); // { [itemIdx]: optIndex }
const studentBlankAnswers = ref({}); // { [itemIdx]: 'text' }
const activeFocusedItemIdx = ref(null);
const previewChecked = ref(false);
const previewRevealedHints = ref({});

const openLivePreview = () => {
    studentOptionPicks.value = {};
    studentBlankAnswers.value = {};
    activeFocusedItemIdx.value = null;
    previewChecked.value = false;
    previewRevealedHints.value = {};
    showPreviewModal.value = true;
};

const closeLivePreview = () => {
    showPreviewModal.value = false;
};

const handleWordHelperChipClick = (word) => {
    if (activeFocusedItemIdx.value !== null) {
        const current = studentBlankAnswers.value[activeFocusedItemIdx.value] || '';
        studentBlankAnswers.value[activeFocusedItemIdx.value] = current ? `${current} ${word}` : word;
    }
};

const toggleHintForPreview = (itemIdx) => {
    previewRevealedHints.value[itemIdx] = !previewRevealedHints.value[itemIdx];
};

const getSelectedStanceText = (item, itemIdx) => {
    const selectedOptIdx = studentOptionPicks.value[itemIdx];
    if (selectedOptIdx === undefined || !item.options[selectedOptIdx]) {
        return null;
    }
    const opt = item.options[selectedOptIdx];
    return opt.stance_value || opt.option_text;
};

const checkStudentAnswers = () => {
    previewChecked.value = true;
};

const handleBack = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const submitQuiz = () => {
    form.post(`/teachers/modules/${props.module.slug}/quizzes/evaluation`, {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head :title="`Create Evaluation • ${module.title}`" />

    <div class="evaluation-page">
        <!-- Top Header Navigation -->
        <header class="studio-header">
            <div class="header-left">
                <button type="button" class="neu-btn secondary-btn" @click="handleBack">
                    ← Back to Journey
                </button>
                <div class="header-titles">
                    <span class="step-pill">
                        🎯 Step {{ module.next_sort_order }} Milestone • Story Map Evaluation
                    </span>
                    <h1 class="page-title">
                        📊 Story Map Evaluation Studio
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
            <!-- Left Column: Quiz Info, Word Helpers & Scenarios Context -->
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
                            placeholder="e.g. Story Map Analysis: Evaluating Conflict & Values"
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
                            <span class="stat-label">Word Helpers</span>
                            <span class="stat-value">{{ form.word_helpers.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Questions</span>
                            <span class="stat-value">{{ form.items.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total Score</span>
                            <span class="stat-value">{{ totalPoints }} pts</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Word Helper Box Neu-Card -->
                <div class="neu-card word-helpers-neu-card">
                    <div class="card-header">
                        <span class="card-icon">💡</span>
                        <div class="header-text-group">
                            <h2 class="card-title">Quick Word Helper Box</h2>
                            <p class="card-subtitle">
                                Vocabulary chips students can reference and tap to complete their sentences.
                            </p>
                        </div>
                    </div>

                    <div class="word-input-row">
                        <input
                            v-model="newWordInput"
                            type="text"
                            class="neu-input word-chip-input"
                            placeholder="Add word (e.g. kindness) & hit enter"
                            @keydown.enter.prevent="addWordHelper"
                        />
                        <button
                            type="button"
                            class="neu-btn add-word-btn"
                            @click="addWordHelper"
                        >
                            ➕ Add
                        </button>
                    </div>

                    <!-- Word Chips -->
                    <div class="word-chips-cloud">
                        <div v-if="form.word_helpers.length === 0" class="empty-words-placeholder">
                            (No word helper chips added yet. Type words above to build the helper box for students!)
                        </div>
                        <span
                            v-for="(word, wIdx) in form.word_helpers"
                            :key="wIdx"
                            class="word-helper-chip"
                        >
                            {{ word }}
                            <button
                                type="button"
                                class="chip-remove-btn"
                                title="Remove word"
                                @click="removeWordHelper(wIdx)"
                            >
                                ✕
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Scenario Contexts Neu-Card (Shared quiz_contexts) -->
                <div class="neu-card context-section-card">
                    <div class="card-header">
                        <span class="card-icon">📖</span>
                        <div class="header-text-group">
                            <h2 class="card-title">Scenario Contexts</h2>
                            <p class="card-subtitle">
                                The story title and world background for this analysis.
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
                            <label class="form-label">Scenario Narrative (e.g. "Woven in Bayanihan")</label>
                            <textarea
                                v-model="ctx.scenario"
                                class="neu-textarea small-textarea"
                                placeholder="Describe the story context for these analysis questions..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Context Hint</label>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="neu-input"
                                placeholder="General advice for answering..."
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

            <!-- Right Column: Evaluation Questions Builder -->
            <div class="studio-cards-column">
                <div class="items-header-bar">
                    <div class="header-info">
                        <h2 class="section-heading">
                            📝 Evaluation Questions (Step 1 Stance + Step 2 Sentence Starter)
                        </h2>
                        <p class="section-subheading">
                            Step 1 choice automatically fills the <code>[stance]</code> blank, and students write their explanation in the <code>[blank]</code>.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="neu-btn add-item-btn"
                        @click="addItem"
                    >
                        ➕ Add Evaluation Question
                    </button>
                </div>

                <!-- Items List -->
                <div class="items-list">
                    <div
                        v-for="(item, iIdx) in form.items"
                        :key="iIdx"
                        class="neu-card eval-item-editor-card"
                    >
                        <!-- Card Header -->
                        <div class="eval-card-top">
                            <div class="eval-header-inputs">
                                <input
                                    v-model="item.section_title"
                                    type="text"
                                    class="section-title-input"
                                    placeholder="e.g. 1. EVALUATING CHARACTER VS. CHARACTER"
                                    required
                                />
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
                                    title="Delete this question"
                                    @click="removeItem(iIdx)"
                                >
                                    🗑️ Remove
                                </button>
                            </div>
                        </div>

                        <!-- Question Prompt -->
                        <div class="form-group">
                            <label class="form-label">
                                Critical Thinking Question <span class="required">*</span>
                            </label>
                            <textarea
                                v-model="item.question_prompt"
                                class="neu-textarea question-textarea"
                                placeholder="e.g. Was Aling Marta right to take all the dye to meet the council's demands? Why or why not?"
                                rows="2"
                                required
                            ></textarea>
                        </div>

                        <!-- Step 1: Valid Stance Choice Buttons Editor -->
                        <div class="step-subcard step-1-card">
                            <div class="step-subcard-header">
                                <span class="step-badge">Step 1</span>
                                <input
                                    v-model="item.step1_prompt"
                                    type="text"
                                    class="step-prompt-input"
                                    placeholder="Step 1: Pick one option:"
                                />
                                <button
                                    type="button"
                                    class="neu-btn add-sub-btn"
                                    @click="addOptionToItem(item)"
                                >
                                    ➕ Add Stance Option
                                </button>
                            </div>

                            <div class="options-pill-grid">
                                <div
                                    v-for="(opt, oIdx) in item.options"
                                    :key="oIdx"
                                    class="option-edit-row"
                                >
                                    <label class="correct-checkbox-wrap" title="Toggle recommended stance">
                                        <input
                                            v-model="opt.is_correct"
                                            type="checkbox"
                                        />
                                        <span class="chk-label">Valid</span>
                                    </label>
                                    <input
                                        v-model="opt.option_text"
                                        type="text"
                                        class="neu-input opt-text-input"
                                        :placeholder="`Option ${oIdx + 1} (e.g. No, she was wrong.)`"
                                        required
                                    />
                                    <input
                                        v-model="opt.stance_value"
                                        type="text"
                                        class="neu-input stance-fill-input"
                                        title="Word that fills the [stance] blank"
                                        placeholder="Fills [stance] (e.g. wrong)"
                                    />
                                    <button
                                        v-if="item.options.length > 2"
                                        type="button"
                                        class="remove-sub-btn"
                                        @click="removeOptionFromItem(item, oIdx)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Sentence Starter with Stance & Long Blank Inserters -->
                        <div class="step-subcard step-2-card">
                            <div class="step-subcard-header">
                                <span class="step-badge step-2-badge">Step 2</span>
                                <span class="step-instruction-label">Sentence Starter Template:</span>

                                <!-- Quick Inserter Action Buttons -->
                                <div class="inserter-buttons-row">
                                    <button
                                        type="button"
                                        class="neu-btn insert-stance-btn"
                                        title="Insert the stance placeholder filled by Step 1"
                                        @click="insertStanceToken(item)"
                                    >
                                        🎯 Insert Stance [stance]
                                    </button>
                                    <button
                                        type="button"
                                        class="neu-btn insert-blank-btn"
                                        title="Insert a long written answer blank for the student explanation"
                                        @click="insertLongBlankToken(item)"
                                    >
                                        ✏️ Insert Long Blank [blank]
                                    </button>
                                </div>
                            </div>

                            <div class="form-group template-input-group">
                                <textarea
                                    v-model="item.sentence_template"
                                    class="neu-textarea template-textarea"
                                    placeholder="e.g. Aling Marta was [stance] (right / wrong) because she should have [blank]."
                                    rows="2"
                                    required
                                ></textarea>
                                <span class="template-helper">
                                    💡 <em>Use the buttons above: <code>[stance]</code> automatically displays the student's Step 1 choice, and <code>[blank]</code> provides a line/box for their written explanation. No complicated checking configuration needed!</em>
                                </span>
                            </div>
                        </div>

                        <!-- Scaffolding Hint -->
                        <div class="form-group">
                            <label class="form-label">
                                💡 Question Scaffolding Hint (Optional)
                            </label>
                            <input
                                v-model="item.hint"
                                type="text"
                                class="neu-input"
                                placeholder="e.g. Think about whether sharing or taking everything is fair!"
                            />
                        </div>
                    </div>
                </div>

                <!-- Add Question Bottom Button -->
                <div class="cards-bottom-action">
                    <button
                        type="button"
                        class="neu-btn add-card-big-btn"
                        @click="addItem"
                    >
                        ➕ Add Another Evaluation Question
                    </button>
                </div>
            </div>
        </form>

        <!-- Live Student Simulation Preview Modal -->
        <div v-if="showPreviewModal" class="preview-backdrop" @click.self="closeLivePreview">
            <div class="preview-modal neu-card">
                <div class="preview-modal-header">
                    <div class="preview-header-left">
                        <span class="preview-pill">Student Pace Activity Preview</span>
                        <h2 class="preview-title">{{ form.title || 'Story Map Analysis' }}</h2>
                    </div>
                    <button type="button" class="close-modal-btn" @click="closeLivePreview">✕</button>
                </div>

                <div class="simulation-body">
                    <div class="sim-directions-banner">
                        <p class="sim-directions-text">
                            👉 <strong>{{ form.description }}</strong>
                        </p>
                    </div>

                    <!-- Quick Word Helper Box in Student View -->
                    <div v-if="form.word_helpers.length > 0" class="sim-word-helper-box">
                        <div class="sim-box-title">
                            <strong>Quick Word Helper Box</strong>
                            <small class="sim-box-subtitle">(Click any word to insert into your explanation!)</small>
                        </div>
                        <div class="sim-helper-chips">
                            <button
                                v-for="(word, wIdx) in form.word_helpers"
                                :key="wIdx"
                                type="button"
                                class="sim-word-chip"
                                @click="handleWordHelperChipClick(word)"
                            >
                                {{ word }}
                            </button>
                        </div>
                    </div>

                    <!-- Evaluation Items in Student Simulation -->
                    <div class="sim-questions-list">
                        <div
                            v-for="(item, iIdx) in form.items"
                            :key="iIdx"
                            class="sim-question-card"
                        >
                            <div class="sim-item-header">
                                <h3 class="sim-item-title">{{ item.section_title }}</h3>
                                <span class="sim-pts-badge">{{ item.points }} pts</span>
                            </div>

                            <p class="sim-prompt-text">{{ item.question_prompt }}</p>

                            <!-- Step 1: Choice Buttons -->
                            <div class="sim-step-block">
                                <span class="sim-step-label">{{ item.step1_prompt }}</span>
                                <div class="sim-options-row">
                                    <button
                                        v-for="(opt, oIdx) in item.options"
                                        :key="oIdx"
                                        type="button"
                                        class="sim-choice-button"
                                        :class="{ selected: studentOptionPicks[iIdx] === oIdx }"
                                        @click="studentOptionPicks[iIdx] = oIdx"
                                    >
                                        <span class="bracket-box">
                                            {{ studentOptionPicks[iIdx] === oIdx ? '✓' : '' }}
                                        </span>
                                        <span>{{ opt.option_text }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Sentence Starter with Auto-Filled Stance & Long Blank -->
                            <div class="sim-step-block">
                                <span class="sim-step-label">Step 2: Complete the sentence starter:</span>
                                <div class="sim-sentence-starter-box">
                                    <template
                                        v-for="(segment, sIdx) in (item.sentence_template || '').split(/(?:\[stance\]|\[blank\])/g)"
                                        :key="sIdx"
                                    >
                                        <span class="sentence-text">{{ segment }}</span>

                                        <!-- Render either Stance or Blank depending on template structure -->
                                        <span
                                            v-if="sIdx === 0 && (item.sentence_template || '').includes('[stance]')"
                                            class="inline-stance-badge"
                                            :class="{ filled: getSelectedStanceText(item, iIdx) }"
                                        >
                                            {{ getSelectedStanceText(item, iIdx) || '________ (pick in Step 1)' }}
                                        </span>
                                        <span
                                            v-else-if="sIdx === 1 && (item.sentence_template || '').includes('[blank]')"
                                            class="inline-blank-wrapper"
                                        >
                                            <textarea
                                                v-model="studentBlankAnswers[iIdx]"
                                                class="sim-inline-textarea"
                                                placeholder="Write your explanation here..."
                                                rows="2"
                                                @focus="activeFocusedItemIdx = iIdx"
                                            ></textarea>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Scaffolding Hint Accordion -->
                            <div v-if="item.hint" class="sim-hint-wrap">
                                <button
                                    type="button"
                                    class="sim-hint-toggle"
                                    @click="toggleHintForPreview(iIdx)"
                                >
                                    💡 {{ previewRevealedHints[iIdx] ? 'Hide Hint' : 'Need a Hint?' }}
                                </button>
                                <div v-if="previewRevealedHints[iIdx]" class="sim-hint-bubble">
                                    <strong>Hint:</strong> {{ item.hint }}
                                </div>
                            </div>

                            <!-- Feedback on Check -->
                            <div v-if="previewChecked" class="sim-check-feedback">
                                <span v-if="item.options[studentOptionPicks[iIdx]]?.is_correct" class="feedback-good">
                                    ✅ Great thinking! You selected a valid stance and reasoned your answer.
                                </span>
                                <span v-else class="feedback-bad">
                                    🤔 Reconsider your choice: reflect on whether this action was fair or cooperative.
                                </span>
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
                            🔄 Reset Simulation
                        </button>
                        <button
                            type="button"
                            class="neu-btn check-answers-btn"
                            @click="checkStudentAnswers"
                        >
                            <span>✓ Check Answers</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.evaluation-page {
    font-family: 'Nunito', sans-serif;
    min-height: 100vh;
    background: #fdfaf6;
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
    background: #ffe4e6;
    color: #be123c;
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
.preview-action-btn { background: #fee2e2; color: #9f1239; }
.primary-action-btn { background: #e11d48; color: #ffffff; }

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
    border-color: #e11d48;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.2);
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
    border-color: #e11d48;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.2);
}

.small-textarea { min-height: 70px; }
.question-textarea { min-height: 65px; font-size: 1rem; }
.template-textarea { min-height: 70px; font-size: 1rem; }

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
    background: #e11d48;
    border-color: #be123c;
    color: #ffffff;
}

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

/* Word Helper Box Neu-Card */
.word-helpers-neu-card {
    border-top: 6px solid #e11d48;
}

.word-input-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
}

.word-chip-input {
    flex: 1;
    padding: 0.45rem 0.65rem;
    font-size: 0.88rem;
}

.add-word-btn {
    padding: 0.45rem 0.85rem;
    font-size: 0.85rem;
    background: #ffe4e6;
    color: #be123c;
}

.word-chips-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.empty-words-placeholder {
    font-size: 0.8rem;
    font-style: italic;
    color: #94a3b8;
    line-height: 1.4;
}

.word-helper-chip {
    background: #fdf2f8;
    border: 2px solid #fbcfe8;
    color: #9d174d;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.chip-remove-btn {
    background: transparent;
    border: none;
    color: #be185d;
    font-weight: 800;
    cursor: pointer;
    font-size: 0.75rem;
    padding: 0;
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

/* Items Builder Column */
.items-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 1rem;
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

.add-item-btn {
    background: #fff1f2;
    color: #e11d48;
}

/* Evaluation Item Editor Card */
.eval-item-editor-card {
    border-left: 8px solid #e11d48;
    margin-bottom: 1.75rem;
}

.eval-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.section-title-input {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #1e293b;
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    min-width: 280px;
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

/* Step Subcards */
.step-subcard {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1.25rem;
}

.step-subcard-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}

.step-badge {
    background: #3b82f6;
    color: #ffffff;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.8rem;
    font-weight: 800;
    padding: 0.2rem 0.65rem;
    border-radius: 8px;
    text-transform: uppercase;
}

.step-2-badge {
    background: #10b981;
}

.step-instruction-label {
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e293b;
}

.step-prompt-input {
    flex: 1;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    background: transparent;
    border: none;
    outline: none;
    color: #1e293b;
    min-width: 200px;
}

.add-sub-btn {
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
}

.inserter-buttons-row {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
    flex-wrap: wrap;
}

.insert-stance-btn {
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
}

.insert-blank-btn {
    background: #ecfdf5;
    color: #047857;
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
}

.options-pill-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 0.65rem;
}

.option-edit-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.4rem 0.65rem;
}

.correct-checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    cursor: pointer;
}

.chk-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
}

.opt-text-input {
    flex: 2;
    padding: 0.35rem 0.6rem;
    font-size: 0.9rem;
}

.stance-fill-input {
    flex: 1.2;
    padding: 0.35rem 0.6rem;
    font-size: 0.85rem;
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
}

.remove-sub-btn {
    background: #fee2e2;
    border: none;
    color: #ef4444;
    border-radius: 6px;
    width: 22px;
    height: 22px;
    font-weight: 800;
    cursor: pointer;
}

.template-helper {
    display: block;
    font-size: 0.8rem;
    color: #475569;
    margin-top: 0.35rem;
}

.add-card-big-btn {
    width: 100%;
    background: #ffffff;
    border-style: dashed;
    padding: 1rem;
    font-size: 1.1rem;
    color: #e11d48;
}

/* Student Simulation Modal */
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
    max-width: 900px;
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
    background: #fff1f2;
    border: 2px dashed #fecdd3;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.25rem;
}

.sim-directions-text {
    margin: 0;
    font-size: 0.95rem;
    color: #9f1239;
}

/* Quick Word Helper Box in Student View */
.sim-word-helper-box {
    background: #f8fafc;
    border: 3px solid #1e293b;
    border-radius: 16px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.sim-box-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
    color: #1e293b;
    margin-bottom: 0.65rem;
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.sim-box-subtitle {
    font-size: 0.8rem;
    color: #64748b;
}

.sim-helper-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.sim-word-chip {
    background: #ffffff;
    border: 2px solid #1e293b;
    border-radius: 10px;
    padding: 0.35rem 0.75rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
    cursor: pointer;
    box-shadow: 0 2px 0 #1e293b;
    transition: all 0.1s ease;
}

.sim-word-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 0 #1e293b;
    background: #ffe4e6;
}

/* Questions in Simulation */
.sim-questions-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.sim-question-card {
    background: #ffffff;
    border: 3px solid #1e293b;
    border-radius: 18px;
    padding: 1.5rem;
    box-shadow: 0 4px 0 #1e293b;
}

.sim-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.5rem;
    margin-bottom: 0.75rem;
}

.sim-item-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.sim-pts-badge {
    background: #ecfdf5;
    color: #047857;
    font-weight: 800;
    font-size: 0.8rem;
    padding: 0.2rem 0.55rem;
    border-radius: 8px;
}

.sim-prompt-text {
    font-size: 1rem;
    line-height: 1.5;
    color: #1e293b;
    font-weight: 700;
    margin: 0 0 1.25rem 0;
}

.sim-step-block {
    margin-bottom: 1.25rem;
}

.sim-step-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 800;
    color: #64748b;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.sim-options-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.sim-choice-button {
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.6rem 1rem;
    font-family: 'Nunito', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.15s ease;
}

.bracket-box {
    width: 20px;
    height: 20px;
    border: 2px solid #94a3b8;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #e11d48;
}

.sim-choice-button.selected {
    border-color: #e11d48;
    background: #fff1f2;
    color: #be123c;
}

.sim-choice-button.selected .bracket-box {
    border-color: #e11d48;
    background: #e11d48;
    color: #ffffff;
}

/* Inline Sentence Blanks in Simulation */
.sim-sentence-starter-box {
    background: #fffbeb;
    border: 2px dashed #fcd34d;
    border-radius: 14px;
    padding: 1.25rem;
    font-size: 1.05rem;
    line-height: 2;
    color: #1e293b;
}

.sentence-text {
    font-weight: 700;
}

/* Auto-filled Stance badge */
.inline-stance-badge {
    display: inline-block;
    background: #f1f5f9;
    border: 2px dashed #94a3b8;
    color: #64748b;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 0.2rem 0.65rem;
    border-radius: 8px;
    margin: 0 0.35rem;
    vertical-align: middle;
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.inline-stance-badge.filled {
    background: #dbeafe;
    border: 2px solid #2563eb;
    color: #1d4ed8;
    box-shadow: 0 2px 0 #1d4ed8;
    transform: scale(1.05);
}

.inline-blank-wrapper {
    display: block;
    margin-top: 0.65rem;
}

.sim-inline-textarea {
    width: 100%;
    background: #ffffff;
    border: 2px solid #1e293b;
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    font-family: 'Nunito', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    box-shadow: 0 2px 0 #1e293b;
    box-sizing: border-box;
    resize: vertical;
}

.sim-inline-textarea:focus {
    outline: none;
    border-color: #e11d48;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.25), 0 3px 0 #be123c;
    background: #fff1f2;
}

.sim-hint-wrap {
    margin-top: 0.5rem;
}

.sim-hint-toggle {
    background: transparent;
    border: none;
    font-weight: 800;
    font-size: 0.85rem;
    color: #b45309;
    cursor: pointer;
    text-decoration: underline;
}

.sim-hint-bubble {
    background: #fefce8;
    border: 2px solid #fef08a;
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    margin-top: 0.4rem;
    font-size: 0.88rem;
    color: #854d0e;
}

.sim-check-feedback {
    margin-top: 0.75rem;
    padding: 0.6rem 0.85rem;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 700;
}

.feedback-good {
    color: #047857;
    background: #ecfdf5;
    display: block;
    padding: 0.4rem;
    border-radius: 8px;
}

.feedback-bad {
    color: #b91c1c;
    background: #fef2f2;
    display: block;
    padding: 0.4rem;
    border-radius: 8px;
}

.preview-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px dashed #cbd5e1;
    padding-top: 1.25rem;
}

.check-answers-btn {
    background: #e11d48;
    color: #ffffff;
}
</style>
