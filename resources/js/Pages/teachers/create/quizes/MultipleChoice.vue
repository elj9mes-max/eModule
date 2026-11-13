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

const xpPresets = [100, 150, 200, 250, 500];
const passPresets = [60, 70, 75, 80, 90];
const optionLetters = ['A', 'B', 'C', 'D', 'E', 'F'];

const form = useForm({
    title: '',
    description: '',
    xp_reward: 150,
    passing_percentage: 70,
    time_limit_seconds: null,
    is_published: true,
    contexts: [
        {
            scenario: '',
            hint: '',
        },
    ],
    scenario: '',
    hint: '',
    reading_passage: '',
    questions: [
        {
            question_text: '',
            selection_type: 'single',
            passage_context: '',
            hint: '',
            explanation: '',
            points: 10,
            options: [
                { option_text: '', is_correct: true, feedback: '' },
                { option_text: '', is_correct: false, feedback: '' },
                { option_text: '', is_correct: false, feedback: '' },
                { option_text: '', is_correct: false, feedback: '' },
            ],
        },
    ],
});

const previewTotalPoints = computed(() => {
    return form.questions.reduce((sum, q) => sum + (Number(q.points) || 10), 0);
});

// Adding and removing contexts
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
        form.contexts[0].scenario = '';
        form.contexts[0].hint = '';
    }
};

// Gemini AI Quiz Assistant
const showAiQuizModal = ref(false);
const aiPassagePrompt = ref('');
const aiQuestionCount = ref(3);
const isGeneratingQuizAi = ref(false);
const aiQuizError = ref('');

const generateQuestionsWithAi = async () => {
    const prompt = aiPassagePrompt.value.trim() || form.reading_passage.trim() || form.title.trim() || props.module.title;
    if (!prompt) {
        aiQuizError.value = 'Please provide a story passage, reading excerpt, or topic.';
        return;
    }

    isGeneratingQuizAi.value = true;
    aiQuizError.value = '';

    try {
        const result = await generateQuizAssist({
            quizType: 'multiple_choice',
            topicOrPassage: prompt,
            questionCount: aiQuestionCount.value,
            gradeLevel: props.module.grade_level,
        });

        if (result) {
            if (result.title && !form.title) {
                form.title = result.title;
            }
            if (result.description && !form.description) {
                form.description = result.description;
            }
            if (result.reading_passage && !form.reading_passage) {
                form.reading_passage = result.reading_passage;
            }

            if (Array.isArray(result.questions) && result.questions.length > 0) {
                const isOnlyFirstQuestionEmpty = form.questions.length === 1 && !form.questions[0].question_text.trim();
                if (isOnlyFirstQuestionEmpty) {
                    form.questions = [];
                }

                result.questions.forEach((q) => {
                    form.questions.push({
                        question_text: q.question_text || '',
                        selection_type: q.selection_type || 'single',
                        passage_context: q.passage_context || '',
                        hint: q.hint || '',
                        explanation: q.explanation || '',
                        points: Number(q.points) || 10,
                        options: Array.isArray(q.options) && q.options.length > 0
                            ? q.options.map((opt) => ({
                                option_text: opt.option_text || '',
                                is_correct: Boolean(opt.is_correct),
                                feedback: opt.feedback || '',
                            }))
                            : [
                                { option_text: '', is_correct: true, feedback: '' },
                                { option_text: '', is_correct: false, feedback: '' },
                                { option_text: '', is_correct: false, feedback: '' },
                                { option_text: '', is_correct: false, feedback: '' },
                            ],
                    });
                });
            }
            showAiQuizModal.value = false;
        }
    } catch (err) {
        aiQuizError.value = err.message || 'Gemini quiz generation failed.';
    } finally {
        isGeneratingQuizAi.value = false;
    }
};

// Adding and removing questions
const addQuestion = () => {
    form.questions.push({
        question_text: '',
        selection_type: 'single',
        passage_context: '',
        hint: '',
        explanation: '',
        points: 10,
        options: [
            { option_text: '', is_correct: true, feedback: '' },
            { option_text: '', is_correct: false, feedback: '' },
            { option_text: '', is_correct: false, feedback: '' },
            { option_text: '', is_correct: false, feedback: '' },
        ],
    });
};

const removeQuestion = (index) => {
    if (form.questions.length <= 1) {
        return;
    }
    form.questions.splice(index, 1);
    if (activeSimQuestionIndex.value >= form.questions.length) {
        activeSimQuestionIndex.value = form.questions.length - 1;
    }
};

// Adding and removing choices for a question
const addOption = (questionIndex) => {
    const q = form.questions[questionIndex];
    if (q.options.length < 6) {
        q.options.push({
            option_text: '',
            is_correct: false,
            feedback: '',
        });
    }
};

const removeOption = (questionIndex, optionIndex) => {
    const q = form.questions[questionIndex];
    if (q.options.length > 2) {
        q.options.splice(optionIndex, 1);
        if (!q.options.some((o) => o.is_correct)) {
            q.options[0].is_correct = true;
        }
    }
};

// Toggle option correctness
const setCorrectOption = (questionIndex, optionIndex) => {
    const q = form.questions[questionIndex];
    if (q.selection_type === 'single') {
        q.options.forEach((opt, idx) => {
            opt.is_correct = idx === optionIndex;
        });
    } else {
        q.options[optionIndex].is_correct = !q.options[optionIndex].is_correct;
        if (!q.options.some((o) => o.is_correct)) {
            q.options[optionIndex].is_correct = true;
        }
    }
};

const onSelectionTypeChange = (questionIndex) => {
    const q = form.questions[questionIndex];
    if (q.selection_type === 'single') {
        let foundFirst = false;
        q.options.forEach((opt) => {
            if (opt.is_correct && !foundFirst) {
                foundFirst = true;
            } else {
                opt.is_correct = false;
            }
        });
        if (!foundFirst && q.options.length > 0) {
            q.options[0].is_correct = true;
        }
    }
};

const goToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const submitQuiz = () => {
    const firstFilled = form.contexts.find((c) => c.scenario && c.scenario.trim() !== '');
    if (firstFilled) {
        form.reading_passage = firstFilled.scenario;
        form.scenario = firstFilled.scenario;
        form.hint = firstFilled.hint || '';
    }
    form.post(`/teachers/modules/${props.module.slug}/quizzes/multiple-choice`, {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};

// Student Simulation Mode
const showLivePreview = ref(false);
const activeSimQuestionIndex = ref(0);
const simulatedSelections = ref({});
const simShowHint = ref({});
const simChecked = ref({});

const openLivePreview = () => {
    simulatedSelections.value = {};
    simShowHint.value = {};
    simChecked.value = {};
    form.questions.forEach((_, idx) => {
        simulatedSelections.value[idx] = [];
        simShowHint.value[idx] = false;
        simChecked.value[idx] = false;
    });
    activeSimQuestionIndex.value = 0;
    showLivePreview.value = true;
};

const closeLivePreview = () => {
    showLivePreview.value = false;
};

const toggleSimOption = (qIdx, oIdx) => {
    if (simChecked.value[qIdx]) return;
    const q = form.questions[qIdx];
    if (q.selection_type === 'single') {
        simulatedSelections.value[qIdx] = [oIdx];
    } else {
        const current = simulatedSelections.value[qIdx] || [];
        if (current.includes(oIdx)) {
            simulatedSelections.value[qIdx] = current.filter(i => i !== oIdx);
        } else {
            simulatedSelections.value[qIdx] = [...current, oIdx];
        }
    }
};
</script>

<template>
    <Head :title="`Create Multiple Choice Quiz • ${module.title}`" />

    <div class="multiple-choice-page">
        <!-- Top Header Navigation -->
        <header class="studio-header">
            <div class="header-left">
                <button type="button" class="neu-btn secondary-btn" @click="goToModule">
                    ← Back to Journey
                </button>
                <div class="header-titles">
                    <span class="step-pill">
                        🎯 Step {{ module.next_sort_order }} Milestone • Multiple Choice Quiz
                    </span>
                    <h1 class="page-title">
                        🎯 Multiple Choice Challenge Studio
                    </h1>
                </div>
            </div>

            <div class="header-actions">
                <button
                    type="button"
                    class="neu-btn preview-action-btn"
                    @click="openLivePreview"
                >
                    👀 Student Simulation
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
            <!-- Left Column: Quiz Info & Scenario Context -->
            <div class="studio-sidebar">
                <!-- Quiz Core Settings Neu-Card -->
                <div class="neu-card">
                    <div class="card-header">
                        <span class="card-icon">⚙️</span>
                        <h2 class="card-title">Activity Settings</h2>
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
                            placeholder="e.g. Chapter 1 Checkpoint: Conflict & Character Motivations"
                            required
                        />
                        <div v-if="form.errors.title" class="input-error">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quiz-directions">
                            Instructions / Objective
                        </label>
                        <textarea
                            id="quiz-directions"
                            v-model="form.description"
                            rows="3"
                            class="neu-textarea"
                            placeholder="e.g. Answer these reading quest questions to test your understanding of the story."
                        ></textarea>
                    </div>

                    <!-- XP Reward -->
                    <div class="form-group">
                        <label class="form-label">
                            XP Reward 🏆
                        </label>
                        <div class="preset-pill-group">
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
                        <input
                            v-model.number="form.xp_reward"
                            type="number"
                            min="10"
                            max="50000"
                            class="neu-input mt-2"
                            placeholder="e.g. 150"
                        />
                    </div>

                    <!-- Passing Score -->
                    <div class="form-group">
                        <label class="form-label">
                            Passing Percentage 🎯
                        </label>
                        <div class="preset-pill-group">
                            <button
                                v-for="pass in passPresets"
                                :key="pass"
                                type="button"
                                class="preset-pill"
                                :class="{ active: form.passing_percentage === pass }"
                                @click="form.passing_percentage = pass"
                            >
                                {{ pass }}%
                            </button>
                        </div>
                        <input
                            v-model.number="form.passing_percentage"
                            type="number"
                            min="1"
                            max="100"
                            class="neu-input mt-2"
                            placeholder="e.g. 70"
                        />
                    </div>

                    <!-- Time Limit -->
                    <div class="form-group">
                        <label class="form-label" for="time-limit">
                            Time Limit (Optional) ⏱️
                        </label>
                        <input
                            id="time-limit"
                            v-model.number="form.time_limit_seconds"
                            type="number"
                            min="10"
                            step="30"
                            class="neu-input"
                            placeholder="e.g. 300 (seconds) or leave empty for untimed"
                        />
                    </div>

                    <!-- Publish Status -->
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input
                                v-model="form.is_published"
                                type="checkbox"
                                class="neu-checkbox"
                            />
                            <span>Publish immediately for students</span>
                        </label>
                    </div>
                </div>

                <!-- Shared Story Scenario Contexts Neu-Card -->
                <div class="neu-card">
                    <div class="card-header justify-between">
                        <div class="flex items-center gap-2">
                            <span class="card-icon">📜</span>
                            <h2 class="card-title">Story / Reference Context</h2>
                        </div>
                        <button
                            type="button"
                            class="neu-btn small-btn accent-btn"
                            @click="addContext"
                        >
                            + Add Context
                        </button>
                    </div>
                    <p class="card-subtitle">
                        Provide a reading passage or story excerpt students can refer to while answering questions.
                    </p>

                    <div
                        v-for="(ctx, cIdx) in form.contexts"
                        :key="cIdx"
                        class="context-item-box"
                    >
                        <div class="context-item-header">
                            <span class="font-bold text-xs text-slate-700">Context Excerpt #{{ cIdx + 1 }}</span>
                            <button
                                v-if="form.contexts.length > 1"
                                type="button"
                                class="text-rose-600 text-xs font-bold hover:underline"
                                @click="removeContext(cIdx)"
                            >
                                Remove
                            </button>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label text-xs">Story Excerpt or Passage</label>
                            <textarea
                                v-model="ctx.scenario"
                                rows="3"
                                class="neu-textarea text-xs"
                                placeholder="Paste or write the reading excerpt or narrative situation for students to examine..."
                            ></textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Scenario Hint / Reading Clue (Optional)</label>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="neu-input text-xs"
                                placeholder="e.g. Note how the elders reacted when the bamboo bridge collapsed."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Questions Deck Builder -->
            <div class="studio-main">
                <div class="main-header-banner">
                    <div>
                        <h2 class="main-title">
                            Question Deck & Answer Keys
                        </h2>
                        <p class="main-subtitle">
                            Craft questions, configure single or multiple correct answers, and provide helpful hints.
                        </p>
                    </div>

                    <div class="header-badges">
                        <div class="summary-pill points-pill">
                            🏆 Total Points: <span class="font-black">{{ previewTotalPoints }}</span>
                        </div>
                        <div class="summary-pill count-pill">
                            📝 Questions: <span class="font-black">{{ form.questions.length }}</span>
                        </div>
                        <button
                            type="button"
                            class="neu-btn small-btn primary-action-btn"
                            @click="addQuestion"
                        >
                            ➕ Add Question
                        </button>
                        <button
                            type="button"
                            class="neu-btn small-btn ai-action-btn"
                            @click="showAiQuizModal = true"
                        >
                            ✨ AI Generate Questions
                        </button>
                    </div>
                </div>

                <!-- Validation Errors List -->
                <div v-if="Object.keys(form.errors).length > 0" class="errors-banner">
                    <div class="errors-header">
                        ⚠️ Please check the following items before publishing:
                    </div>
                    <ul class="errors-list">
                        <li v-for="(err, key) in form.errors" :key="key">
                            {{ err }}
                        </li>
                    </ul>
                </div>

                <!-- Questions List -->
                <div class="questions-list">
                    <div
                        v-for="(question, qIdx) in form.questions"
                        :key="qIdx"
                        class="neu-card question-card"
                    >
                        <!-- Card Header -->
                        <div class="question-card-header">
                            <div class="flex items-center gap-3">
                                <span class="question-badge">
                                    Question #{{ qIdx + 1 }}
                                </span>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">
                                    {{ question.selection_type === 'single' ? 'Single Choice' : 'Multi-Select' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <div class="points-input-box">
                                    <label class="text-xs font-bold text-slate-700">Pts:</label>
                                    <input
                                        v-model.number="question.points"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="neu-input points-input"
                                    />
                                </div>
                                <button
                                    v-if="form.questions.length > 1"
                                    type="button"
                                    class="neu-btn delete-btn"
                                    title="Delete Question"
                                    @click="removeQuestion(qIdx)"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="question-card-body">
                            <!-- Selection Type Pill Row -->
                            <div class="selection-type-row">
                                <button
                                    type="button"
                                    class="type-pill"
                                    :class="{ active: question.selection_type === 'single' }"
                                    @click="question.selection_type = 'single'; onSelectionTypeChange(qIdx)"
                                >
                                    🔘 Single Choice (1 Answer)
                                </button>
                                <button
                                    type="button"
                                    class="type-pill"
                                    :class="{ active: question.selection_type === 'multiple' }"
                                    @click="question.selection_type = 'multiple'; onSelectionTypeChange(qIdx)"
                                >
                                    ☑️ Multi-Select (Multiple Answers)
                                </button>
                            </div>

                            <!-- Question Prompt -->
                            <div class="form-group mt-3">
                                <label class="form-label">
                                    Question Prompt <span class="required">*</span>
                                </label>
                                <textarea
                                    v-model="question.question_text"
                                    rows="2"
                                    class="neu-textarea"
                                    placeholder="e.g. What was the primary obstacle faced by the protagonist in Chapter 1?"
                                    required
                                ></textarea>
                                <div v-if="form.errors[`questions.${qIdx}.question_text`]" class="input-error">
                                    {{ form.errors[`questions.${qIdx}.question_text`] }}
                                </div>
                            </div>

                            <!-- Passage Context Excerpt (Optional) -->
                            <div class="form-group">
                                <label class="form-label text-slate-600">
                                    Question-Specific Passage Excerpt (Optional)
                                </label>
                                <input
                                    v-model="question.passage_context"
                                    type="text"
                                    class="neu-input text-xs"
                                    placeholder="e.g. 'The thunder roared across the bay as the boat pitched violently...'"
                                />
                            </div>

                            <!-- Answer Choices Panel -->
                            <div class="choices-panel">
                                <div class="choices-panel-header">
                                    <span class="text-sm font-black text-slate-800">
                                        Answer Choices & Answer Keys (Click badge to mark correct)
                                    </span>
                                    <span class="text-xs font-bold text-slate-500">
                                        {{ question.options.length }} / 6 Choices
                                    </span>
                                </div>

                                <div class="choices-list">
                                    <div
                                        v-for="(option, oIdx) in question.options"
                                        :key="oIdx"
                                        class="choice-item-row"
                                        :class="{ 'is-correct': option.is_correct }"
                                    >
                                        <!-- Correct Toggle Badge -->
                                        <button
                                            type="button"
                                            class="choice-correct-btn"
                                            :class="{ active: option.is_correct }"
                                            @click="setCorrectOption(qIdx, oIdx)"
                                            :title="option.is_correct ? 'Correct Answer' : 'Mark as Correct Answer'"
                                        >
                                            <span class="choice-letter">{{ optionLetters[oIdx] }}</span>
                                            <span class="choice-check">{{ option.is_correct ? '✓' : '○' }}</span>
                                        </button>

                                        <!-- Option Text Input -->
                                        <input
                                            v-model="option.option_text"
                                            type="text"
                                            class="neu-input choice-input"
                                            :placeholder="`Choice ${optionLetters[oIdx]} text...`"
                                            required
                                        />

                                        <!-- Delete Choice Button -->
                                        <button
                                            v-if="question.options.length > 2"
                                            type="button"
                                            class="neu-btn choice-delete-btn"
                                            title="Delete Choice"
                                            @click="removeOption(qIdx, oIdx)"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <!-- Add Choice Button -->
                                <button
                                    v-if="question.options.length < 6"
                                    type="button"
                                    class="neu-btn small-btn add-choice-btn"
                                    @click="addOption(qIdx)"
                                >
                                    + Add Choice {{ optionLetters[question.options.length] }}
                                </button>
                            </div>

                            <!-- Hint & Explanation Duo -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="form-group mb-0">
                                    <label class="form-label text-xs text-amber-800">
                                        💡 Story Hint for Struggling Students
                                    </label>
                                    <input
                                        v-model="question.hint"
                                        type="text"
                                        class="neu-input text-xs"
                                        placeholder="e.g. Look back at the dialogue on page 4."
                                    />
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs text-emerald-800">
                                        📖 Explanation / Lesson Rationale
                                    </label>
                                    <input
                                        v-model="question.explanation"
                                        type="text"
                                        class="neu-input text-xs"
                                        placeholder="e.g. The passage states explicitly that the river bridge collapsed."
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Add Button -->
                <div class="bottom-actions">
                    <button
                        type="button"
                        class="neu-btn add-prompt-bottom-btn"
                        @click="addQuestion"
                    >
                        ➕ Add Another Question
                    </button>
                </div>
            </div>
        </form>

        <!-- Gemini AI Quiz Generator Modal -->
        <div v-if="showAiQuizModal" class="modal-backdrop" @click.self="showAiQuizModal = false">
            <div class="simulation-modal neu-card ai-quiz-modal-card">
                <div class="sim-header">
                    <div>
                        <span class="sim-badge ai-badge-gemini">✨ GOOGLE GEMINI AI</span>
                        <h2 class="sim-title">Auto-Generate Reading Comprehension Questions</h2>
                    </div>
                    <button type="button" class="neu-btn close-modal-btn" @click="showAiQuizModal = false">
                        ✕ Close
                    </button>
                </div>

                <div class="ai-modal-body">
                    <p class="ai-modal-intro">
                        Paste a story passage, reading excerpt, or lecture concept. Gemini AI will generate questions, 4 plausible options, the correct answer, hints, and explanations tailored for <strong>{{ module.grade_level }}</strong>!
                    </p>

                    <div class="form-group">
                        <label class="form-label">
                            <span>Story Passage or Reading Topic</span>
                        </label>
                        <textarea
                            v-model="aiPassagePrompt"
                            class="neu-textarea"
                            rows="5"
                            :placeholder="form.reading_passage ? 'Leave blank to use reading passage already on this quiz, or type a topic/excerpt...' : 'Paste story text, article paragraph, or topic here...'"
                        ></textarea>
                    </div>

                    <div class="ai-options-row">
                        <div class="form-group count-group">
                            <label class="form-label">
                                <span>Number of Questions</span>
                            </label>
                            <div class="ai-count-picker">
                                <button
                                    v-for="n in [1, 3, 5, 8]"
                                    :key="n"
                                    type="button"
                                    class="ai-count-btn"
                                    :class="{ 'active': aiQuestionCount === n }"
                                    @click="aiQuestionCount = n"
                                >
                                    {{ n }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <p v-if="aiQuizError" class="ai-error-notice">⚠️ {{ aiQuizError }}</p>
                </div>

                <div class="ai-modal-footer">
                    <button
                        type="button"
                        class="neu-btn cancel-btn"
                        @click="showAiQuizModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="neu-btn ai-confirm-btn"
                        :disabled="isGeneratingQuizAi"
                        @click="generateQuestionsWithAi"
                    >
                        <span v-if="isGeneratingQuizAi">⏳ Generating with Gemini...</span>
                        <span v-else>✨ Generate & Add Questions</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Student Simulation Modal -->
        <div v-if="showLivePreview" class="modal-backdrop" @click.self="closeLivePreview">
            <div class="simulation-modal neu-card">
                <div class="sim-header">
                    <div>
                        <span class="sim-badge">🎮 Student Simulation Mode</span>
                        <h2 class="sim-title">{{ form.title || 'Multiple Choice Quiz' }}</h2>
                    </div>
                    <button type="button" class="neu-btn close-modal-btn" @click="closeLivePreview">
                        ✕ Close
                    </button>
                </div>

                <!-- Simulation Body -->
                <div class="sim-content">
                    <!-- Story Reference Box if exists -->
                    <div
                        v-if="form.contexts.some(c => c.scenario && c.scenario.trim())"
                        class="sim-reference-card"
                    >
                        <div class="font-bold text-amber-900 text-sm mb-1 flex items-center gap-1.5">
                            <span>📜</span> Story Excerpt / Reference
                        </div>
                        <div
                            v-for="(ctx, idx) in form.contexts.filter(c => c.scenario && c.scenario.trim())"
                            :key="idx"
                            class="text-xs text-amber-950 mb-2 italic bg-amber-100/60 p-2.5 rounded-lg border border-amber-200"
                        >
                            "{{ ctx.scenario }}"
                            <div v-if="ctx.hint" class="mt-1 font-sans text-xs text-amber-800 font-semibold not-italic">
                                💡 Tip: {{ ctx.hint }}
                            </div>
                        </div>
                    </div>

                    <!-- Questions Navigation Tabs -->
                    <div class="sim-tabs">
                        <button
                            v-for="(q, qIdx) in form.questions"
                            :key="qIdx"
                            type="button"
                            class="sim-tab"
                            :class="{ active: activeSimQuestionIndex === qIdx }"
                            @click="activeSimQuestionIndex = qIdx"
                        >
                            Question {{ qIdx + 1 }}
                        </button>
                    </div>

                    <!-- Active Question View -->
                    <div v-if="form.questions[activeSimQuestionIndex]" class="sim-prompt-box">
                        <div class="sim-prompt-header">
                            <span class="sim-prompt-number">
                                Question {{ activeSimQuestionIndex + 1 }} of {{ form.questions.length }}
                                <span class="ml-2 font-bold text-xs lowercase text-indigo-600">
                                    ({{ form.questions[activeSimQuestionIndex].selection_type === 'single' ? 'single choice' : 'multi-select' }})
                                </span>
                            </span>
                            <span class="sim-points-badge">
                                🏆 {{ form.questions[activeSimQuestionIndex].points }} Points
                            </span>
                        </div>

                        <!-- Specific Passage Excerpt if present -->
                        <div
                            v-if="form.questions[activeSimQuestionIndex].passage_context"
                            class="text-xs italic bg-slate-100 text-slate-700 p-2 rounded-lg border border-slate-200 mb-3"
                        >
                            "{{ form.questions[activeSimQuestionIndex].passage_context }}"
                        </div>

                        <h3 class="sim-question-text">
                            {{ form.questions[activeSimQuestionIndex].question_text || 'Question prompt text...' }}
                        </h3>

                        <!-- Guiding Hint Accordion -->
                        <div
                            v-if="form.questions[activeSimQuestionIndex].hint"
                            class="sim-hint-card"
                        >
                            <button
                                type="button"
                                class="sim-hint-toggle"
                                @click="simShowHint[activeSimQuestionIndex] = !simShowHint[activeSimQuestionIndex]"
                            >
                                <span>💡 Need a hint? Click to toggle</span>
                                <span>{{ simShowHint[activeSimQuestionIndex] ? '▲' : '▼' }}</span>
                            </button>
                            <div
                                v-if="simShowHint[activeSimQuestionIndex]"
                                class="sim-hint-body"
                            >
                                {{ form.questions[activeSimQuestionIndex].hint }}
                            </div>
                        </div>

                        <!-- Interactive Options Cards -->
                        <div class="sim-options-grid">
                            <div
                                v-for="(opt, oIdx) in form.questions[activeSimQuestionIndex].options"
                                :key="oIdx"
                                class="sim-option-card"
                                :class="{
                                    selected: (simulatedSelections[activeSimQuestionIndex] || []).includes(oIdx),
                                    'correct-reveal': simChecked[activeSimQuestionIndex] && opt.is_correct,
                                    'wrong-reveal': simChecked[activeSimQuestionIndex] && (simulatedSelections[activeSimQuestionIndex] || []).includes(oIdx) && !opt.is_correct
                                }"
                                @click="toggleSimOption(activeSimQuestionIndex, oIdx)"
                            >
                                <div class="sim-opt-badge">
                                    {{ optionLetters[oIdx] }}
                                </div>
                                <div class="sim-opt-text">
                                    {{ opt.option_text || `Choice ${optionLetters[oIdx]}` }}
                                </div>
                                <div class="sim-opt-indicator">
                                    <span v-if="(simulatedSelections[activeSimQuestionIndex] || []).includes(oIdx)">✓</span>
                                </div>
                            </div>
                        </div>

                        <!-- Check Answer & Explanation in Simulation -->
                        <div class="mt-4 flex items-center justify-between pt-3 border-t border-slate-200">
                            <button
                                type="button"
                                class="neu-btn small-btn accent-btn"
                                @click="simChecked[activeSimQuestionIndex] = !simChecked[activeSimQuestionIndex]"
                            >
                                {{ simChecked[activeSimQuestionIndex] ? '🔄 Reset Selection' : '🎯 Check Answer' }}
                            </button>

                            <div v-if="simChecked[activeSimQuestionIndex] && form.questions[activeSimQuestionIndex].explanation" class="text-xs text-emerald-800 font-bold">
                                💡 Explanation: {{ form.questions[activeSimQuestionIndex].explanation }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Footer -->
                <div class="sim-footer">
                    <span class="text-xs text-slate-600 font-bold">
                        Simulation mode allows you to test student option selection and answer keys.
                    </span>
                    <button type="button" class="neu-btn primary-action-btn" @click="closeLivePreview">
                        Done Testing
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Base Styles & Fonts */
.multiple-choice-page {
    min-height: 100vh;
    background: #fdfbf7;
    font-family: 'Nunito', sans-serif;
    color: #1e293b;
    padding-bottom: 80px;
}

/* Header */
.studio-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 2rem;
    background: #ffffff;
    border-bottom: 3px solid #1e293b;
    box-shadow: 0 4px 0 #1e293b;
    position: sticky;
    top: 0;
    z-index: 40;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.step-pill {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #2563eb;
    background: #dbeafe;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    border: 2px solid #1e293b;
    box-shadow: 0 2px 0 #1e293b;
    margin-bottom: 0.2rem;
}

.page-title {
    font-family: 'Fredoka', cursive;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Neu Buttons */
.neu-btn {
    font-family: 'Fredoka', cursive;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 0.6rem 1.25rem;
    border-radius: 14px;
    border: 3px solid #1e293b;
    box-shadow: 0 4px 0 #1e293b;
    cursor: pointer;
    transition: all 0.12s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

.neu-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #1e293b;
}

.neu-btn:active:not(:disabled) {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #1e293b;
}

.neu-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.secondary-btn {
    background: #f1f5f9;
    color: #334155;
}

.primary-action-btn {
    background: #22c55e;
    color: #ffffff;
}

.preview-action-btn {
    background: #38bdf8;
    color: #0f172a;
}

.accent-btn {
    background: #fef08a;
    color: #713f12;
}

.delete-btn {
    background: #fee2e2;
    color: #991b1b;
    padding: 0.4rem 0.65rem;
    font-size: 0.85rem;
}

.small-btn {
    padding: 0.35rem 0.75rem;
    font-size: 0.8rem;
    border-radius: 10px;
    border-width: 2px;
    box-shadow: 0 2px 0 #1e293b;
}

/* Neu Cards */
.neu-card {
    background: #ffffff;
    border: 3px solid #1e293b;
    border-radius: 20px;
    box-shadow: 0 6px 0 #1e293b;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.card-icon {
    font-size: 1.3rem;
}

.card-title {
    font-family: 'Fredoka', cursive;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
}

.card-subtitle {
    font-size: 0.825rem;
    color: #64748b;
    font-weight: 700;
    margin-top: -0.5rem;
    margin-bottom: 1rem;
}

/* Studio Layout */
.studio-layout {
    max-width: 1380px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 1.75rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .studio-layout {
        grid-template-columns: 1fr;
    }
}

/* Inputs & Form Controls */
.form-group {
    margin-bottom: 1.15rem;
}

.form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.4rem;
}

.required {
    color: #ef4444;
}

.neu-input, .neu-textarea {
    width: 100%;
    font-family: 'Nunito', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    background: #f8fafc;
    border: 2.5px solid #1e293b;
    border-radius: 12px;
    padding: 0.65rem 0.85rem;
    box-shadow: inset 0 2px 0 rgba(0,0,0,0.04);
    outline: none;
    transition: all 0.15s ease;
}

.neu-input:focus, .neu-textarea:focus {
    background: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.input-error {
    font-size: 0.8rem;
    font-weight: 800;
    color: #dc2626;
    margin-top: 0.3rem;
}

/* Preset Pills */
.preset-pill-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.preset-pill {
    font-family: 'Fredoka', cursive;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.3rem 0.65rem;
    background: #f1f5f9;
    color: #475569;
    border: 2px solid #1e293b;
    border-radius: 8px;
    box-shadow: 0 2px 0 #1e293b;
    cursor: pointer;
    transition: all 0.1s ease;
}

.preset-pill:hover {
    background: #e2e8f0;
}

.preset-pill.active {
    background: #3b82f6;
    color: #ffffff;
    border-color: #1e293b;
}

/* Checkbox */
.checkbox-group {
    margin-top: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 800;
    font-size: 0.85rem;
    cursor: pointer;
}

.neu-checkbox {
    width: 1.15rem;
    height: 1.15rem;
    border: 2px solid #1e293b;
    border-radius: 6px;
    cursor: pointer;
}

/* Context Excerpt Box */
.context-item-box {
    background: #fdfaf3;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
}

.context-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.4rem;
}

/* Main Studio Column */
.main-header-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border: 3px solid #1e293b;
    border-radius: 18px;
    box-shadow: 0 6px 0 #1e293b;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
}

.main-title {
    font-family: 'Fredoka', cursive;
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
}

.main-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 700;
    margin-top: 0.2rem;
}

.header-badges {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.summary-pill {
    font-size: 0.825rem;
    font-weight: 800;
    padding: 0.4rem 0.8rem;
    border: 2px solid #1e293b;
    border-radius: 10px;
    box-shadow: 0 2px 0 #1e293b;
}

.points-pill {
    background: #fef08a;
    color: #854d0e;
}

.count-pill {
    background: #e0e7ff;
    color: #3730a3;
}

/* Errors Banner */
.errors-banner {
    background: #fef2f2;
    border: 3px solid #dc2626;
    border-radius: 14px;
    box-shadow: 0 4px 0 #dc2626;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.errors-header {
    font-family: 'Fredoka', cursive;
    font-size: 0.95rem;
    font-weight: 800;
    color: #991b1b;
    margin-bottom: 0.4rem;
}

.errors-list {
    list-style-type: disc;
    padding-left: 1.25rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: #b91c1c;
}

/* Question Card */
.question-card {
    transition: all 0.2s ease;
    border-left: 8px solid #3b82f6;
}

.question-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px dashed #cbd5e1;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
}

.question-badge {
    font-family: 'Fredoka', cursive;
    font-weight: 800;
    font-size: 0.95rem;
    background: #3b82f6;
    color: #ffffff;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    border: 2px solid #1e293b;
    box-shadow: 0 2px 0 #1e293b;
}

.points-input-box {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    background: #f8fafc;
    border: 2px solid #1e293b;
    border-radius: 10px;
    padding: 0.25rem 0.5rem;
}

.points-input {
    width: 65px;
    padding: 0.2rem 0.4rem;
    font-size: 0.85rem;
    border-width: 1.5px;
}

/* Selection Type Pills */
.selection-type-row {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.type-pill {
    font-family: 'Fredoka', cursive;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 0.45rem 0.95rem;
    border: 2px solid #1e293b;
    border-radius: 10px;
    background: #f8fafc;
    color: #475569;
    box-shadow: 0 2px 0 #1e293b;
    cursor: pointer;
    transition: all 0.12s ease;
}

.type-pill:hover {
    background: #f1f5f9;
}

.type-pill.active {
    background: #dbeafe;
    color: #1e40af;
    border-color: #1e293b;
}

/* Choices Panel */
.choices-panel {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1rem 1.25rem;
    margin-top: 1rem;
}

.choices-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.choices-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.choice-item-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.4rem 0.6rem;
    transition: all 0.12s ease;
}

.choice-item-row.is-correct {
    border-color: #22c55e;
    background: #f0fdf4;
}

.choice-correct-btn {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 8px;
    border: 2px solid #cbd5e1;
    background: #f1f5f9;
    color: #64748b;
    font-family: 'Fredoka', cursive;
    font-weight: 800;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.12s ease;
}

.choice-correct-btn.active {
    background: #22c55e;
    color: #ffffff;
    border-color: #1e293b;
    box-shadow: 0 2px 0 #1e293b;
}

.choice-letter {
    font-weight: 900;
}

.choice-check {
    font-size: 0.95rem;
}

.choice-input {
    flex: 1;
    border-width: 1.5px;
    padding: 0.45rem 0.7rem;
    font-size: 0.9rem;
}

.choice-delete-btn {
    background: #fee2e2;
    color: #991b1b;
    padding: 0.35rem 0.6rem;
    font-size: 0.75rem;
    border-width: 1.5px;
    border-radius: 8px;
    box-shadow: 0 1px 0 #1e293b;
}

.add-choice-btn {
    margin-top: 0.75rem;
    background: #ffffff;
    color: #3b82f6;
    border-style: dashed;
}

/* Bottom Actions */
.bottom-actions {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.add-prompt-bottom-btn {
    background: #f8fafc;
    color: #1e293b;
    width: 100%;
    max-width: 450px;
    padding: 0.85rem;
    border-style: dashed;
}

.add-prompt-bottom-btn:hover {
    background: #f1f5f9;
}

/* Simulation Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}

.simulation-modal {
    width: 100%;
    max-width: 760px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0;
    margin-bottom: 0;
}

.sim-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.75rem;
    background: #f1f5f9;
    border-bottom: 3px solid #1e293b;
}

.sim-badge {
    font-family: 'Fredoka', cursive;
    font-size: 0.8rem;
    font-weight: 800;
    color: #0284c7;
    background: #e0f2fe;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    border: 1.5px solid #0284c7;
}

.sim-title {
    font-family: 'Fredoka', cursive;
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    margin-top: 0.2rem;
}

.close-modal-btn {
    background: #fee2e2;
    color: #991b1b;
    padding: 0.4rem 0.8rem;
}

.sim-content {
    padding: 1.5rem 1.75rem;
    overflow-y: auto;
    flex: 1;
}

.sim-reference-card {
    background: #fffbeb;
    border: 2px solid #fde68a;
    border-radius: 12px;
    padding: 0.85rem;
    margin-bottom: 1.25rem;
}

.sim-tabs {
    display: flex;
    gap: 0.4rem;
    overflow-x: auto;
    padding-bottom: 0.4rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.sim-tab {
    font-family: 'Fredoka', cursive;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 8px 8px 0 0;
    border: 2px solid transparent;
    color: #64748b;
    cursor: pointer;
}

.sim-tab.active {
    color: #2563eb;
    background: #dbeafe;
    border-color: #1e293b;
    border-bottom: none;
    font-weight: 800;
}

.sim-prompt-box {
    background: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 16px;
    padding: 1.25rem;
}

.sim-prompt-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.sim-prompt-number {
    font-size: 0.8rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
}

.sim-points-badge {
    font-size: 0.8rem;
    font-weight: 800;
    background: #fef08a;
    color: #854d0e;
    padding: 0.2rem 0.55rem;
    border-radius: 8px;
    border: 1.5px solid #1e293b;
}

.sim-question-text {
    font-family: 'Fredoka', cursive;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.4;
    margin-bottom: 1rem;
}

.sim-hint-card {
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 10px;
    padding: 0.6rem 0.85rem;
    margin-bottom: 1rem;
}

.sim-hint-toggle {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    font-weight: 800;
    color: #1d4ed8;
    background: none;
    border: none;
    cursor: pointer;
}

.sim-hint-body {
    font-size: 0.825rem;
    font-weight: 700;
    color: #1e40af;
    margin-top: 0.4rem;
    padding-top: 0.4rem;
    border-top: 1px dashed #bfdbfe;
}

/* Sim Options Grid */
.sim-options-grid {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.sim-option-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sim-option-card:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.sim-option-card.selected {
    background: #eff6ff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.sim-option-card.correct-reveal {
    background: #dcfce7 !important;
    border-color: #22c55e !important;
}

.sim-option-card.wrong-reveal {
    background: #fee2e2 !important;
    border-color: #ef4444 !important;
}

.sim-opt-badge {
    font-family: 'Fredoka', cursive;
    font-weight: 800;
    font-size: 0.85rem;
    background: #ffffff;
    border: 2px solid #1e293b;
    border-radius: 8px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sim-opt-text {
    flex: 1;
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b;
}

.sim-opt-indicator {
    font-weight: 900;
    color: #3b82f6;
    font-size: 1rem;
}

.sim-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.75rem;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
}

/* Gemini AI Question Generator Styles */
.ai-action-btn {
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #FFFFFF !important;
    box-shadow: 0 3px 0 #3730A3 !important;
}

.ai-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 0 #3730A3 !important;
}

.ai-badge-gemini {
    background: #EEF2FF !important;
    color: #4F46E5 !important;
    border: 1px solid #C7D2FE !important;
}

.ai-modal-intro {
    font-size: 0.85rem;
    color: #475569;
    line-height: 1.5;
    margin: 0 0 1rem 0;
}

.ai-options-row {
    margin-top: 1rem;
}

.ai-count-picker {
    display: flex;
    gap: 0.5rem;
}

.ai-count-btn {
    width: 44px;
    height: 40px;
    border-radius: 10px;
    border: 2px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    font-family: var(--font-heading);
    font-size: 0.9rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ai-count-btn.active {
    background: #4F46E5;
    border-color: #4338CA;
    color: #FFFFFF;
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
}

.ai-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem 1.75rem;
    background: #F8FAFC;
    border-top: 2px solid #E2E8F0;
}

.ai-confirm-btn {
    background: linear-gradient(180deg, #4F46E5 0%, #4338CA 100%) !important;
    color: #FFFFFF !important;
    box-shadow: 0 3px 0 #312E81 !important;
}

.ai-confirm-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.cancel-btn {
    background: #F1F5F9;
    color: #475569;
}
</style>
