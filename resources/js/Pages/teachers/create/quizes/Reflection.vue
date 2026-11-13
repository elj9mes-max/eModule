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

const xpPresets = [100, 150, 200, 250, 300];
const passPresets = [60, 70, 75, 80, 90];
const wordPresets = [50, 100, 150, 250, 500];

// Helper to create a clean default prompt with empty values and placeholders
const createDefaultPrompt = (order = 1) => ({
    prompt_text: '',
    max_words: 150,
    min_words: null,
    guiding_hint: '',
    starter_sentence: '',
    points: 50,
    sort_order: order,
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
    prompts: [
        createDefaultPrompt(1),
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

// Gemini AI Reflection Assistant
const isGeneratingAiPrompts = ref(false);

const generateReflectionWithAi = async () => {
    isGeneratingAiPrompts.value = true;

    try {
        const result = await generateQuizAssist({
            quizType: 'reflection',
            topicOrPassage: form.description || form.title || props.module.title,
            questionCount: 2,
            gradeLevel: props.module.grade_level,
        });

        if (result) {
            if (result.title) {
                form.title = result.title;
            }
            if (result.description) {
                form.description = result.description;
            }
            if (Array.isArray(result.prompts) && result.prompts.length > 0) {
                form.prompts = result.prompts.map((p, idx) => ({
                    prompt_text: p.prompt_text || '',
                    max_words: Number(p.max_words) || 150,
                    min_words: p.min_words ? Number(p.min_words) : 20,
                    guiding_hint: p.guiding_hint || '',
                    starter_sentence: p.starter_sentence || '',
                    points: Number(p.points) || 50,
                    sort_order: idx + 1,
                }));
            }
        }
    } catch (err) {
        alert(err.message || 'Gemini reflection prompt generation failed.');
    } finally {
        isGeneratingAiPrompts.value = false;
    }
};

// Prompts Management
const addPrompt = () => {
    if (form.prompts.length < 20) {
        const nextOrder = form.prompts.length + 1;
        form.prompts.push(createDefaultPrompt(nextOrder));
    }
};

const removePrompt = (index) => {
    if (form.prompts.length > 1) {
        form.prompts.splice(index, 1);
        form.prompts.forEach((prompt, idx) => {
            prompt.sort_order = idx + 1;
        });
    }
};

const setMaxWords = (prompt, words) => {
    prompt.max_words = words;
};

// Total Calculated Points
const totalPoints = computed(() => {
    return form.prompts.reduce((sum, p) => sum + (Number(p.points) || 0), 0);
});

// Student Live Preview Modal
const showLivePreview = ref(false);
const activePreviewPromptIndex = ref(0);
const simulatedAnswers = ref({});
const showHintMap = ref({});

const openLivePreview = () => {
    simulatedAnswers.value = {};
    showHintMap.value = {};
    form.prompts.forEach((p, idx) => {
        simulatedAnswers.value[idx] = p.starter_sentence ? p.starter_sentence + ' ' : '';
        showHintMap.value[idx] = false;
    });
    activePreviewPromptIndex.value = 0;
    showLivePreview.value = true;
};

const closeLivePreview = () => {
    showLivePreview.value = false;
};

const countWords = (text) => {
    if (!text || typeof text !== 'string') return 0;
    const matches = text.trim().match(/\S+/g);
    return matches ? matches.length : 0;
};

const handleBack = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const submitQuiz = () => {
    form.post(`/teachers/modules/${props.module.slug}/quizzes/reflection`, {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head :title="`Create Reflection Activity • ${module.title}`" />

    <div class="reflection-page">
        <!-- Top Header Navigation -->
        <header class="studio-header">
            <div class="header-left">
                <button type="button" class="neu-btn secondary-btn" @click="handleBack">
                    ← Back to Journey
                </button>
                <div class="header-titles">
                    <span class="step-pill">
                        ✍️ Step {{ module.next_sort_order }} Milestone • Reflection Activity
                    </span>
                    <h1 class="page-title">
                        📖 Reading Reflection & Response Studio
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
                    <span v-if="form.processing">Saving Activity...</span>
                    <span v-else>💾 Publish Reflection</span>
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
                            Activity Title <span class="required">*</span>
                        </label>
                        <input
                            id="quiz-title"
                            v-model="form.title"
                            type="text"
                            class="neu-input"
                            placeholder="e.g. Personal Response: Lessons from the Loom"
                            required
                        />
                        <div v-if="form.errors.title" class="input-error">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quiz-directions">
                            Instructions / Directions
                        </label>
                        <textarea
                            id="quiz-directions"
                            v-model="form.description"
                            rows="3"
                            class="neu-textarea"
                            placeholder="e.g. Take a moment to reflect on what happened in the story. Write your honest thoughts in the journal blank below."
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
                            placeholder="e.g. 250"
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
                            placeholder="e.g. 600 (seconds) or leave empty for untimed"
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
                        Provide a story excerpt or scenario text students can reference while composing their reflection.
                    </p>

                    <div
                        v-for="(ctx, cIdx) in form.contexts"
                        :key="cIdx"
                        class="context-item-box"
                    >
                        <div class="context-item-header">
                            <span class="font-bold text-xs text-slate-700">Excerpt #{{ cIdx + 1 }}</span>
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
                            <label class="form-label text-xs">Story Excerpt or Reference Text</label>
                            <textarea
                                v-model="ctx.scenario"
                                rows="3"
                                class="neu-textarea text-xs"
                                placeholder="e.g. 'The weavers sat by the quiet sea as rain began to fall. Aling Marta looked at her remaining threads and held out the bowl of blue dye...'"
                            ></textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Teacher Hint or Thought Question (Optional)</label>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="neu-input text-xs"
                                placeholder="e.g. Focus on how both characters reacted when the storm hit."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Reflection Prompts Builder -->
            <div class="studio-main">
                <div class="main-header-banner">
                    <div>
                        <h2 class="main-title">
                            Reflection Prompts & Long Blanks
                        </h2>
                        <p class="main-subtitle">
                            Configure prompt questions, maximum word limits, and optional starter sentences for student writing.
                        </p>
                    </div>

                    <div class="header-badges">
                        <div class="summary-pill points-pill">
                            🏆 Total Points: <span class="font-black">{{ totalPoints }}</span>
                        </div>
                        <div class="summary-pill count-pill">
                            📝 Prompts: <span class="font-black">{{ form.prompts.length }}</span>
                        </div>
                        <button
                            type="button"
                            class="neu-btn small-btn primary-action-btn"
                            :disabled="form.prompts.length >= 20"
                            @click="addPrompt"
                        >
                            ➕ Add Prompt
                        </button>
                        <button
                            type="button"
                            class="neu-btn small-btn ai-action-btn"
                            :disabled="isGeneratingAiPrompts"
                            @click="generateReflectionWithAi"
                        >
                            <span v-if="isGeneratingAiPrompts">⏳ Generating...</span>
                            <span v-else>✨ AI Generate Prompts</span>
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

                <!-- Prompts List -->
                <div class="prompts-list">
                    <div
                        v-for="(prompt, pIdx) in form.prompts"
                        :key="pIdx"
                        class="neu-card prompt-card"
                    >
                        <!-- Card Header -->
                        <div class="prompt-card-header">
                            <div class="flex items-center gap-3">
                                <span class="prompt-badge">
                                    Prompt #{{ pIdx + 1 }}
                                </span>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">
                                    Long Response Journal
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <div class="points-input-box">
                                    <label class="text-xs font-bold text-slate-700">Pts:</label>
                                    <input
                                        v-model.number="prompt.points"
                                        type="number"
                                        min="1"
                                        max="1000"
                                        class="neu-input points-input"
                                    />
                                </div>
                                <button
                                    v-if="form.prompts.length > 1"
                                    type="button"
                                    class="neu-btn delete-btn"
                                    title="Delete Prompt"
                                    @click="removePrompt(pIdx)"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="prompt-card-body">
                            <!-- Reflection Instruction / Guiding Question -->
                            <div class="form-group">
                                <label class="form-label">
                                    Instruction or Guiding Question <span class="required">*</span>
                                </label>
                                <textarea
                                    v-model="prompt.prompt_text"
                                    rows="3"
                                    class="neu-textarea"
                                    placeholder="e.g. If you were Aling Marta, would you have shared the blue dye right away, or kept it to win the festival? Explain what you would have done and why."
                                    required
                                ></textarea>
                                <div v-if="form.errors[`prompts.${pIdx}.prompt_text`]" class="input-error">
                                    {{ form.errors[`prompts.${pIdx}.prompt_text`] }}
                                </div>
                            </div>

                            <!-- Word Limit Settings Row -->
                            <div class="word-limit-panel">
                                <div class="panel-header">
                                    <span class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                                        📏 Maximum Words Allowed:
                                    </span>
                                    <div class="preset-pill-group">
                                        <button
                                            v-for="preset in wordPresets"
                                            :key="preset"
                                            type="button"
                                            class="preset-pill word-preset-pill"
                                            :class="{ active: prompt.max_words === preset }"
                                            @click="setMaxWords(prompt, preset)"
                                        >
                                            {{ preset }} words
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <label class="text-xs font-black text-slate-700 block mb-1">
                                            Max Word Limit <span class="required">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model.number="prompt.max_words"
                                                type="number"
                                                min="10"
                                                max="5000"
                                                class="neu-input pr-16"
                                                placeholder="e.g. 150"
                                                required
                                            />
                                            <span class="input-suffix">words</span>
                                        </div>
                                        <div v-if="form.errors[`prompts.${pIdx}.max_words`]" class="input-error">
                                            {{ form.errors[`prompts.${pIdx}.max_words`] }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-xs font-black text-slate-700 block mb-1">
                                            Min Word Requirement (Optional)
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model.number="prompt.min_words"
                                                type="number"
                                                min="1"
                                                max="5000"
                                                class="neu-input pr-16"
                                                placeholder="e.g. 20 (optional)"
                                            />
                                            <span class="input-suffix">words</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Starter Sentence -->
                            <div class="form-group mt-4">
                                <label class="form-label">
                                    Starter Sentence (Optional Scaffolding)
                                </label>
                                <input
                                    v-model="prompt.starter_sentence"
                                    type="text"
                                    class="neu-input"
                                    placeholder="e.g. If I were in Aling Marta's position, I would..."
                                />
                                <span class="helper-text">
                                    When set, this starter will pre-populate the student's journal blank so young readers have a running start!
                                </span>
                            </div>

                            <!-- Guiding Hint / Clue -->
                            <div class="form-group">
                                <label class="form-label">
                                    Guiding Hint or Clue (Optional)
                                </label>
                                <input
                                    v-model="prompt.guiding_hint"
                                    type="text"
                                    class="neu-input"
                                    placeholder="e.g. Think about how sharing made both weavers finish on time."
                                />
                            </div>

                            <!-- Visual Teacher Preview Box of Student Answer Area -->
                            <div class="teacher-preview-box">
                                <div class="preview-box-header">
                                    <span class="preview-box-title">
                                        📝 Live Preview of Student Writing Area:
                                    </span>
                                    <span class="preview-word-badge">
                                        Max: {{ prompt.max_words || 150 }} words
                                    </span>
                                </div>
                                <div class="preview-mock-textarea">
                                    <span v-if="prompt.starter_sentence" class="mock-starter">
                                        {{ prompt.starter_sentence }}
                                    </span>
                                    <span class="mock-placeholder">
                                        [Student writes their long response here up to {{ prompt.max_words || 150 }} words...]
                                    </span>
                                </div>
                                <div class="preview-meter-bar">
                                    <div class="meter-track">
                                        <div class="meter-fill" style="width: 25%"></div>
                                    </div>
                                    <span class="meter-label">Live Word Counter: 0 / {{ prompt.max_words || 150 }} words</span>
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
                        :disabled="form.prompts.length >= 20"
                        @click="addPrompt"
                    >
                        ➕ Add Another Reflection Prompt
                    </button>
                </div>
            </div>
        </form>

        <!-- Student Simulation Modal -->
        <div v-if="showLivePreview" class="modal-backdrop" @click.self="closeLivePreview">
            <div class="simulation-modal neu-card">
                <div class="sim-header">
                    <div>
                        <span class="sim-badge">🎮 Student Simulation Mode</span>
                        <h2 class="sim-title">{{ form.title || 'Reading Reflection Journal' }}</h2>
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

                    <!-- Prompts Navigation Tabs -->
                    <div class="sim-tabs">
                        <button
                            v-for="(p, pIdx) in form.prompts"
                            :key="pIdx"
                            type="button"
                            class="sim-tab"
                            :class="{ active: activePreviewPromptIndex === pIdx }"
                            @click="activePreviewPromptIndex = pIdx"
                        >
                            Prompt {{ pIdx + 1 }}
                        </button>
                    </div>

                    <!-- Active Prompt View -->
                    <div v-if="form.prompts[activePreviewPromptIndex]" class="sim-prompt-box">
                        <div class="sim-prompt-header">
                            <span class="sim-prompt-number">
                                Question {{ activePreviewPromptIndex + 1 }} of {{ form.prompts.length }}
                            </span>
                            <span class="sim-points-badge">
                                🏆 {{ form.prompts[activePreviewPromptIndex].points }} Points
                            </span>
                        </div>

                        <h3 class="sim-question-text">
                            {{ form.prompts[activePreviewPromptIndex].prompt_text || 'Reflection question prompt...' }}
                        </h3>

                        <!-- Guiding Hint Accordion -->
                        <div
                            v-if="form.prompts[activePreviewPromptIndex].guiding_hint"
                            class="sim-hint-card"
                        >
                            <button
                                type="button"
                                class="sim-hint-toggle"
                                @click="showHintMap[activePreviewPromptIndex] = !showHintMap[activePreviewPromptIndex]"
                            >
                                <span>💡 Need a hint? Click to toggle</span>
                                <span>{{ showHintMap[activePreviewPromptIndex] ? '▲' : '▼' }}</span>
                            </button>
                            <div
                                v-if="showHintMap[activePreviewPromptIndex]"
                                class="sim-hint-body"
                            >
                                {{ form.prompts[activePreviewPromptIndex].guiding_hint }}
                            </div>
                        </div>

                        <!-- Long Blank Answer Area -->
                        <div class="sim-answer-area">
                            <label class="text-xs font-black text-slate-700 block mb-1">
                                Your Reflection Journal:
                            </label>
                            <textarea
                                v-model="simulatedAnswers[activePreviewPromptIndex]"
                                rows="6"
                                class="neu-textarea sim-textarea"
                                :class="{
                                    'limit-exceeded': countWords(simulatedAnswers[activePreviewPromptIndex]) > form.prompts[activePreviewPromptIndex].max_words
                                }"
                                placeholder="Write your thoughts here..."
                            ></textarea>

                            <!-- Word Counter Bar -->
                            <div class="sim-counter-bar">
                                <div class="sim-counter-info">
                                    <span
                                        class="counter-text"
                                        :class="{
                                            'text-rose-600 font-black': countWords(simulatedAnswers[activePreviewPromptIndex]) > form.prompts[activePreviewPromptIndex].max_words
                                        }"
                                    >
                                        {{ countWords(simulatedAnswers[activePreviewPromptIndex]) }} / {{ form.prompts[activePreviewPromptIndex].max_words }} words
                                    </span>

                                    <span
                                        v-if="form.prompts[activePreviewPromptIndex].min_words"
                                        class="min-indicator"
                                    >
                                        (Min: {{ form.prompts[activePreviewPromptIndex].min_words }} words)
                                    </span>

                                    <span
                                        v-if="countWords(simulatedAnswers[activePreviewPromptIndex]) > form.prompts[activePreviewPromptIndex].max_words"
                                        class="text-xs text-rose-600 font-bold"
                                    >
                                        ⚠️ Exceeds limit by {{ countWords(simulatedAnswers[activePreviewPromptIndex]) - form.prompts[activePreviewPromptIndex].max_words }} words
                                    </span>
                                </div>

                                <div class="sim-progress-track">
                                    <div
                                        class="sim-progress-fill"
                                        :style="{
                                            width: `${Math.min(100, (countWords(simulatedAnswers[activePreviewPromptIndex]) / form.prompts[activePreviewPromptIndex].max_words) * 100)}%`,
                                            backgroundColor: countWords(simulatedAnswers[activePreviewPromptIndex]) > form.prompts[activePreviewPromptIndex].max_words ? '#ef4444' : '#10b981'
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Footer -->
                <div class="sim-footer">
                    <span class="text-xs text-slate-600 font-bold">
                        Simulation mode allows you to test student typing experience and live word limits.
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
.reflection-page {
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
    color: #4f46e5;
    background: #e0e7ff;
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

.input-suffix {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    font-weight: 800;
    color: #64748b;
    pointer-events: none;
}

.helper-text {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 700;
    margin-top: 0.3rem;
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

.word-preset-pill.active {
    background: #8b5cf6;
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

/* Scenario Excerpt Box */
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

/* Prompt Card */
.prompt-card {
    transition: all 0.2s ease;
    border-left: 8px solid #8b5cf6;
}

.prompt-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px dashed #cbd5e1;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
}

.prompt-badge {
    font-family: 'Fredoka', cursive;
    font-weight: 800;
    font-size: 0.95rem;
    background: #8b5cf6;
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

/* Word Limit Panel */
.word-limit-panel {
    background: #faf5ff;
    border: 2px solid #e9d5ff;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin: 1rem 0;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.6rem;
}

/* Live Preview Mock Box */
.teacher-preview-box {
    margin-top: 1.25rem;
    background: #f8fafc;
    border: 2px dashed #94a3b8;
    border-radius: 14px;
    padding: 1rem;
}

.preview-box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.6rem;
}

.preview-box-title {
    font-size: 0.8rem;
    font-weight: 800;
    color: #475569;
}

.preview-word-badge {
    font-size: 0.75rem;
    font-weight: 800;
    background: #ede9fe;
    color: #6b21a8;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
}

.preview-mock-textarea {
    background: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.75rem;
    min-height: 80px;
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1.5;
}

.mock-starter {
    color: #1e293b;
    font-weight: 800;
}

.mock-placeholder {
    color: #94a3b8;
    font-style: italic;
}

.preview-meter-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.6rem;
}

.meter-track {
    flex: 1;
    height: 8px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
}

.meter-fill {
    height: 100%;
    background: #8b5cf6;
    border-radius: 9999px;
}

.meter-label {
    font-size: 0.75rem;
    font-weight: 800;
    color: #64748b;
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
    color: #4f46e5;
    background: #e0e7ff;
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

.sim-textarea {
    min-height: 140px;
    line-height: 1.6;
}

.sim-textarea.limit-exceeded {
    border-color: #ef4444;
    background: #fef2f2;
}

.sim-counter-bar {
    margin-top: 0.6rem;
}

.sim-counter-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.35rem;
}

.counter-text {
    font-size: 0.825rem;
    font-weight: 800;
    color: #334155;
}

.min-indicator {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
}

.sim-progress-track {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
}

.sim-progress-fill {
    height: 100%;
    transition: width 0.15s ease, background-color 0.15s ease;
}

.sim-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.75rem;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
}

/* Gemini AI Reflection Assistant Styles */
.ai-action-btn {
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #FFFFFF !important;
    box-shadow: 0 3px 0 #3730A3 !important;
    font-family: var(--font-heading);
    font-weight: 800;
}

.ai-action-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 0 #3730A3 !important;
}

.ai-action-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

