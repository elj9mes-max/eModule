<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
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
            next_sort_order: 2,
        }),
    },
    initialQuizType: {
        type: String,
        default: 'multiple-choice',
    },
    existingQuizzes: {
        type: Array,
        default: () => [],
    },
});

/* ==========================================================================
   1. QUIZ FORMAT DEFINITIONS & METADATA (ALL 6 QUIZ ENGINES)
   ========================================================================== */
const quizFormats = [
    {
        id: 'multiple-choice',
        dbType: 'multiple_choice',
        name: 'Multiple Choice',
        shortLabel: 'Multi-Choice',
        icon: '🔘',
        accent: '#EA580C',
        bgSoft: '#FFF7ED',
        borderSoft: '#FDBA74',
        defaultXp: 150,
        itemNoun: 'Question',
        endpointSuffix: 'multiple-choice',
        description: 'Single or multi-select questions with A–F choices, per-option feedback, hints & explanations.',
    },
    {
        id: 'color-the-conflict',
        dbType: 'color_the_conflict',
        name: 'Color the Conflict',
        shortLabel: 'Color Conflict',
        icon: '🎨',
        accent: '#10B981',
        bgSoft: '#ECFDF5',
        borderSoft: '#6EE7B7',
        defaultXp: 200,
        itemNoun: 'Flashcard',
        endpointSuffix: 'color-the-conflict',
        description: 'Interactive flashcards where students classify story excerpts into 4 color-coded conflict types.',
    },
    {
        id: 'the-conflict-map',
        dbType: 'the_conflict_map',
        name: 'The Conflict Map',
        shortLabel: 'Conflict Map',
        icon: '🗺️',
        accent: '#2563EB',
        bgSoft: '#EFF6FF',
        borderSoft: '#93C5FD',
        defaultXp: 180,
        itemNoun: 'Event Card',
        endpointSuffix: 'the-conflict-map',
        description: 'Plot navigator with customizable conflict drop-zones and draggable/selectable event bank cards.',
    },
    {
        id: 'matching-type',
        dbType: 'matching_type',
        name: 'Matching Type',
        shortLabel: 'Matching',
        icon: '🧩',
        accent: '#7C3AED',
        bgSoft: '#F5F3FF',
        borderSoft: '#C4B5FD',
        defaultXp: 220,
        itemNoun: 'Match Item',
        endpointSuffix: 'matching-type',
        description: 'Two-column connector pairing Column A story descriptions with Column B lettered choices.',
    },
    {
        id: 'evaluation',
        dbType: 'evaluation',
        name: 'Evaluation',
        shortLabel: 'Evaluation',
        icon: '📊',
        accent: '#E11D48',
        bgSoft: '#FFF1F2',
        borderSoft: '#FDA4AF',
        defaultXp: 250,
        itemNoun: 'Eval Section',
        endpointSuffix: 'evaluation',
        description: 'Two-step critical thinking rubric with Word Helper chips, stance options & [stance]/[blank] templates.',
    },
    {
        id: 'reflection',
        dbType: 'reflection',
        name: 'Reflection',
        shortLabel: 'Reflection',
        icon: '💭',
        accent: '#D97706',
        bgSoft: '#FFFBEB',
        borderSoft: '#FCD34D',
        defaultXp: 200,
        itemNoun: 'Prompt',
        endpointSuffix: 'reflection',
        description: 'Open-ended guided reflection prompts with word-count meters, starter sentences & guiding hints.',
    },
];

const conflictCategories = [
    {
        type: 'character_vs_character',
        label: 'Character vs. Character',
        subtitle: 'Interpersonal conflict between individuals',
        colorTheme: 'red',
        colorHex: '#EF4444',
        borderHex: '#B91C1C',
        bgLight: '#FEF2F2',
        icon: '🔴',
    },
    {
        type: 'character_vs_society',
        label: 'Character vs. Society',
        subtitle: 'Rules, laws, customs & community institutions',
        colorTheme: 'blue',
        colorHex: '#3B82F6',
        borderHex: '#1D4ED8',
        bgLight: '#EFF6FF',
        icon: '🔵',
    },
    {
        type: 'character_vs_nature',
        label: 'Character vs. Nature',
        subtitle: 'Weather, wilderness & environmental forces',
        colorTheme: 'green',
        colorHex: '#10B981',
        borderHex: '#047857',
        bgLight: '#ECFDF5',
        icon: '🟢',
    },
    {
        type: 'character_vs_self',
        label: 'Character vs. Self',
        subtitle: 'Internal doubts, fear & moral dilemmas',
        colorTheme: 'amber',
        colorHex: '#F59E0B',
        borderHex: '#B45309',
        bgLight: '#FFFBEB',
        icon: '🟡',
    },
];

const xpPresets = [100, 150, 200, 250, 500];
const passPresets = [60, 70, 75, 80, 90];
const wordPresets = [50, 100, 150, 250, 500];
const optionLetters = ['A', 'B', 'C', 'D', 'E', 'F'];
const getLetter = (index) => String.fromCharCode(65 + (index % 26));
const getCardLetter = (index) => `Card ${getLetter(index)}`;

/* ==========================================================================
   2. DEFAULT FACTORY HELPERS FOR EACH OF THE 6 QUIZ FORMATS
   ========================================================================== */
const createDefaultMcQuestion = () => ({
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

const createDefaultColorCard = (order = 1) => ({
    excerpt: '',
    conflict_type: 'character_vs_character',
    hint: '',
    explanation: '',
    feedback_incorrect: '',
    points: 10,
    sort_order: order,
});

const createDefaultMapZones = () => [
    {
        conflict_type: 'character_vs_character',
        label: 'CHARACTER VS CHARACTER',
        subtitle: 'Interpersonal conflict between individuals',
        color_theme: 'blue',
        sort_order: 1,
    },
    {
        conflict_type: 'character_vs_society',
        label: 'CHARACTER VS SOCIETY',
        subtitle: 'Rules, laws, customs & institutions',
        color_theme: 'orange',
        sort_order: 2,
    },
    {
        conflict_type: 'character_vs_nature',
        label: 'CHARACTER VS NATURE',
        subtitle: 'Weather, environmental forces & natural hazards',
        color_theme: 'green',
        sort_order: 3,
    },
];

const createDefaultMapEvent = (index = 0) => ({
    card_letter: getCardLetter(index),
    event_text: '',
    target_conflict_type: 'character_vs_character',
    hint: '',
    explanation: '',
    points: 10,
    sort_order: index + 1,
});

const createDefaultMatchingChoices = () => [
    { choice_letter: 'A', choice_text: 'Character vs. Character' },
    { choice_letter: 'B', choice_text: 'Character vs. Nature' },
    { choice_letter: 'C', choice_text: 'Character vs. Society' },
    { choice_letter: 'D', choice_text: 'Character vs. Self' },
];

const createDefaultMatchingItem = (number = 1, defaultLetter = 'A') => ({
    item_number: number,
    description: '',
    correct_choice_letter: defaultLetter,
    hint: '',
    explanation: '',
    points: 10,
    sort_order: number,
});

const createDefaultEvalItem = (order = 1) => ({
    section_title: '',
    conflict_type: 'character_vs_character',
    question_prompt: '',
    step1_prompt: 'Step 1: Pick one option:',
    sentence_template: '',
    hint: '',
    points: 25,
    sort_order: order,
    options: [
        { option_text: '', stance_value: '', is_correct: true },
        { option_text: '', stance_value: '', is_correct: false },
    ],
});

const createDefaultReflectionPrompt = (order = 1) => ({
    prompt_text: '',
    max_words: 150,
    min_words: null,
    guiding_hint: '',
    starter_sentence: '',
    points: 50,
    sort_order: order,
});

/* ==========================================================================
   3. UNIFIED STUDIO REACTIVE STATE
   ========================================================================== */
const normalizeInitialFormat = (raw) => {
    const map = {
        multiple_choice: 'multiple-choice',
        color_the_conflict: 'color-the-conflict',
        the_conflict_map: 'the-conflict-map',
        matching_type: 'matching-type',
        evaluation: 'evaluation',
        reflection: 'reflection',
    };
    const resolved = map[raw] || raw;

    return quizFormats.some((f) => f.id === resolved) ? resolved : 'multiple-choice';
};

const activeFormatId = ref(normalizeInitialFormat(props.initialQuizType));
const activeLeftTab = ref('formats'); // 'formats' | 'context' | 'templates' | 'settings' | 'saved'
const isLeftDrawerOpen = ref(true);
const activeSlideIndex = ref(0);
const showContextBannerOnCanvas = ref(true);
const isSubmitting = ref(false);
const validationErrors = ref({});
const studioToastMessage = ref(null);

const studioState = reactive({
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
    // 1. Multiple Choice
    questions: [createDefaultMcQuestion()],
    // 2. Color the Conflict
    cards: [createDefaultColorCard(1)],
    // 3. The Conflict Map
    zones: createDefaultMapZones(),
    events: [createDefaultMapEvent(0)],
    // 4. Matching Type
    choices: createDefaultMatchingChoices(),
    matching_items: [createDefaultMatchingItem(1, 'A')],
    // 5. Evaluation
    word_helpers: ['courage', 'community', 'responsibility', 'empathy'],
    evaluation_items: [createDefaultEvalItem(1)],
    // 6. Reflection
    prompts: [createDefaultReflectionPrompt(1)],
});

const activeFormatMeta = computed(() => {
    return quizFormats.find((f) => f.id === activeFormatId.value) || quizFormats[0];
});

const triggerStudioToast = (msg) => {
    studioToastMessage.value = msg;
    window.setTimeout(() => {
        if (studioToastMessage.value === msg) {
            studioToastMessage.value = null;
        }
    }, 3200);
};

/* ==========================================================================
   4. 50-STEP UNDO / REDO HISTORY STACK
   ========================================================================== */
const historyStack = ref([]);
const historyIndex = ref(-1);
const isRestoringHistory = ref(false);

const captureHistorySnapshot = () => {
    if (isRestoringHistory.value) {
        return;
    }

    const snapshot = JSON.stringify({
        activeFormatId: activeFormatId.value,
        activeSlideIndex: activeSlideIndex.value,
        studioState: JSON.parse(JSON.stringify(studioState)),
    });

    if (historyIndex.value >= 0 && historyStack.value[historyIndex.value] === snapshot) {
        return;
    }

    const nextStack = historyStack.value.slice(0, historyIndex.value + 1);
    nextStack.push(snapshot);
    if (nextStack.length > 50) {
        nextStack.shift();
    }

    historyStack.value = nextStack;
    historyIndex.value = nextStack.length - 1;
};

const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(() => historyIndex.value < historyStack.value.length - 1);

const restoreFromSnapshot = (snapshotStr) => {
    try {
        isRestoringHistory.value = true;
        const parsed = JSON.parse(snapshotStr);
        activeFormatId.value = parsed.activeFormatId;
        activeSlideIndex.value = parsed.activeSlideIndex;
        Object.assign(studioState, parsed.studioState);
    } finally {
        window.setTimeout(() => {
            isRestoringHistory.value = false;
        }, 40);
    }
};

const undoStudio = () => {
    if (!canUndo.value) {
        return;
    }
    historyIndex.value -= 1;
    restoreFromSnapshot(historyStack.value[historyIndex.value]);
};

const redoStudio = () => {
    if (!canRedo.value) {
        return;
    }
    historyIndex.value += 1;
    restoreFromSnapshot(historyStack.value[historyIndex.value]);
};

/* ==========================================================================
   5. ACTIVE SLIDES / ITEMS LIST & NAVIGATION ACROSS ALL 6 FORMATS
   ========================================================================== */
const activeItemsArray = computed(() => {
    switch (activeFormatId.value) {
        case 'multiple-choice':
            return studioState.questions;
        case 'color-the-conflict':
            return studioState.cards;
        case 'the-conflict-map':
            return studioState.events;
        case 'matching-type':
            return studioState.matching_items;
        case 'evaluation':
            return studioState.evaluation_items;
        case 'reflection':
            return studioState.prompts;
        default:
            return studioState.questions;
    }
});

const currentSlideItem = computed(() => {
    const arr = activeItemsArray.value;
    return arr[activeSlideIndex.value] || arr[0] || null;
});

const totalPoints = computed(() => {
    return activeItemsArray.value.reduce((sum, item) => sum + (Number(item.points) || 0), 0);
});

const selectQuizFormat = (formatId) => {
    if (activeFormatId.value === formatId) {
        return;
    }
    activeFormatId.value = formatId;
    activeSlideIndex.value = 0;
    validationErrors.value = {};
    const fmt = quizFormats.find((f) => f.id === formatId);
    if (fmt && (!studioState.xp_reward || studioState.xp_reward === 150)) {
        studioState.xp_reward = fmt.defaultXp;
    }
    captureHistorySnapshot();
};

const getSlideSummaryLabel = (item, idx) => {
    switch (activeFormatId.value) {
        case 'multiple-choice':
            return item.question_text?.trim() || `Untitled Question ${idx + 1}`;
        case 'color-the-conflict':
            return item.excerpt?.trim() || `Conflict Excerpt Card ${idx + 1}`;
        case 'the-conflict-map':
            return item.event_text?.trim() || `${item.card_letter || getCardLetter(idx)} Event`;
        case 'matching-type':
            return item.description?.trim() || `Column A Item #${idx + 1}`;
        case 'evaluation':
            return item.section_title?.trim() || item.question_prompt?.trim() || `Evaluation Section ${idx + 1}`;
        case 'reflection':
            return item.prompt_text?.trim() || `Reflection Prompt ${idx + 1}`;
        default:
            return `Item ${idx + 1}`;
    }
};

const addSlideItem = () => {
    const arr = activeItemsArray.value;
    const nextIdx = arr.length;

    if (activeFormatId.value === 'multiple-choice') {
        studioState.questions.push(createDefaultMcQuestion());
    } else if (activeFormatId.value === 'color-the-conflict' && arr.length < 20) {
        studioState.cards.push(createDefaultColorCard(nextIdx + 1));
    } else if (activeFormatId.value === 'the-conflict-map' && arr.length < 30) {
        const defaultZone = studioState.zones[0]?.conflict_type || 'character_vs_character';
        const evt = createDefaultMapEvent(nextIdx);
        evt.target_conflict_type = defaultZone;
        studioState.events.push(evt);
    } else if (activeFormatId.value === 'matching-type' && arr.length < 30) {
        const firstChoice = studioState.choices[0]?.choice_letter || 'A';
        studioState.matching_items.push(createDefaultMatchingItem(nextIdx + 1, firstChoice));
    } else if (activeFormatId.value === 'evaluation' && arr.length < 20) {
        studioState.evaluation_items.push(createDefaultEvalItem(nextIdx + 1));
    } else if (activeFormatId.value === 'reflection' && arr.length < 20) {
        studioState.prompts.push(createDefaultReflectionPrompt(nextIdx + 1));
    }

    activeSlideIndex.value = activeItemsArray.value.length - 1;
    captureHistorySnapshot();
};

const duplicateSlideItem = (index) => {
    const arr = activeItemsArray.value;
    const source = arr[index];
    if (!source) {
        return;
    }

    const clone = JSON.parse(JSON.stringify(source));
    arr.splice(index + 1, 0, clone);
    reindexActiveSlideItems();
    activeSlideIndex.value = index + 1;
    captureHistorySnapshot();
    triggerStudioToast(`${activeFormatMeta.value.itemNoun} duplicated!`);
};

const removeSlideItem = (index) => {
    const arr = activeItemsArray.value;
    if (arr.length <= 1) {
        triggerStudioToast(`At least 1 ${activeFormatMeta.value.itemNoun.toLowerCase()} is required.`);
        return;
    }

    arr.splice(index, 1);
    reindexActiveSlideItems();
    if (activeSlideIndex.value >= arr.length) {
        activeSlideIndex.value = arr.length - 1;
    }
    captureHistorySnapshot();
};

const moveSlideItem = (index, direction) => {
    const arr = activeItemsArray.value;
    const target = index + direction;
    if (target < 0 || target >= arr.length) {
        return;
    }

    const [moved] = arr.splice(index, 1);
    arr.splice(target, 0, moved);
    reindexActiveSlideItems();
    activeSlideIndex.value = target;
    captureHistorySnapshot();
};

const reindexActiveSlideItems = () => {
    if (activeFormatId.value === 'the-conflict-map') {
        studioState.events.forEach((evt, idx) => {
            evt.card_letter = getCardLetter(idx);
            evt.sort_order = idx + 1;
        });
    } else if (activeFormatId.value === 'matching-type') {
        studioState.matching_items.forEach((item, idx) => {
            item.item_number = idx + 1;
            item.sort_order = idx + 1;
        });
    } else {
        activeItemsArray.value.forEach((item, idx) => {
            item.sort_order = idx + 1;
        });
    }
};

/* ==========================================================================
   6. FORMAT-SPECIFIC HELPERS (OPTIONS, ZONES, CHOICES, WORD HELPERS)
   ========================================================================== */
// Contexts
const addContextScenario = () => {
    studioState.contexts.push({ scenario: '', hint: '' });
    captureHistorySnapshot();
};

const removeContextScenario = (idx) => {
    if (studioState.contexts.length > 1) {
        studioState.contexts.splice(idx, 1);
    } else {
        studioState.contexts[0].scenario = '';
        studioState.contexts[0].hint = '';
    }
    captureHistorySnapshot();
};

// Format 1: Multiple Choice Options
const addMcOption = (question) => {
    if (question.options.length < 6) {
        question.options.push({ option_text: '', is_correct: false, feedback: '' });
        captureHistorySnapshot();
    }
};

const removeMcOption = (question, optIdx) => {
    if (question.options.length > 2) {
        question.options.splice(optIdx, 1);
        if (!question.options.some((o) => o.is_correct)) {
            question.options[0].is_correct = true;
        }
        captureHistorySnapshot();
    }
};

const toggleMcCorrectOption = (question, optIdx) => {
    if (question.selection_type === 'single') {
        question.options.forEach((o, i) => {
            o.is_correct = i === optIdx;
        });
    } else {
        question.options[optIdx].is_correct = !question.options[optIdx].is_correct;
        if (!question.options.some((o) => o.is_correct)) {
            question.options[optIdx].is_correct = true;
        }
    }
    captureHistorySnapshot();
};

const onMcSelectionTypeChange = (question) => {
    if (question.selection_type === 'single') {
        let found = false;
        question.options.forEach((o) => {
            if (o.is_correct && !found) {
                found = true;
            } else {
                o.is_correct = false;
            }
        });
        if (!found && question.options[0]) {
            question.options[0].is_correct = true;
        }
    }
    captureHistorySnapshot();
};

// Format 3: Conflict Map Drop Zones
const addMapZone = () => {
    if (studioState.zones.length >= 6) {
        return;
    }
    const usedTypes = new Set(studioState.zones.map((z) => z.conflict_type));
    const availableCat = conflictCategories.find((c) => !usedTypes.has(c.type)) || conflictCategories[3];

    studioState.zones.push({
        conflict_type: availableCat.type,
        label: availableCat.label.toUpperCase(),
        subtitle: availableCat.subtitle,
        color_theme: availableCat.colorTheme,
        sort_order: studioState.zones.length + 1,
    });
    captureHistorySnapshot();
};

const removeMapZone = (zIdx) => {
    if (studioState.zones.length <= 2) {
        triggerStudioToast('At least 2 drop zones are required.');
        return;
    }
    studioState.zones.splice(zIdx, 1);
    const fallbackType = studioState.zones[0]?.conflict_type || 'character_vs_character';
    studioState.events.forEach((evt) => {
        if (!studioState.zones.some((z) => z.conflict_type === evt.target_conflict_type)) {
            evt.target_conflict_type = fallbackType;
        }
    });
    captureHistorySnapshot();
};

// Format 4: Matching Type Column B Choices
const addMatchingChoice = () => {
    if (studioState.choices.length < 26) {
        const nextLetter = getLetter(studioState.choices.length);
        studioState.choices.push({
            choice_letter: nextLetter,
            choice_text: '',
        });
        captureHistorySnapshot();
    }
};

const removeMatchingChoice = (cIdx) => {
    if (studioState.choices.length <= 2) {
        triggerStudioToast('At least 2 Column B choices are required.');
        return;
    }
    studioState.choices.splice(cIdx, 1);
    studioState.choices.forEach((c, idx) => {
        c.choice_letter = getLetter(idx);
    });
    const fallbackLetter = studioState.choices[0]?.choice_letter || 'A';
    studioState.matching_items.forEach((itm) => {
        if (!studioState.choices.some((c) => c.choice_letter === itm.correct_choice_letter)) {
            itm.correct_choice_letter = fallbackLetter;
        }
    });
    captureHistorySnapshot();
};

// Format 5: Evaluation Word Helpers & Options
const newWordHelperInput = ref('');

const addWordHelperChip = () => {
    const trimmed = newWordHelperInput.value.trim();
    if (trimmed && !studioState.word_helpers.includes(trimmed)) {
        studioState.word_helpers.push(trimmed);
        newWordHelperInput.value = '';
        captureHistorySnapshot();
    }
};

const removeWordHelperChip = (wIdx) => {
    studioState.word_helpers.splice(wIdx, 1);
    captureHistorySnapshot();
};

const addEvalOption = (evalItem) => {
    if (evalItem.options.length < 6) {
        evalItem.options.push({ option_text: '', stance_value: '', is_correct: false });
        captureHistorySnapshot();
    }
};

const removeEvalOption = (evalItem, oIdx) => {
    if (evalItem.options.length > 2) {
        evalItem.options.splice(oIdx, 1);
        captureHistorySnapshot();
    }
};

const insertTokenIntoEvalTemplate = (evalItem, token) => {
    evalItem.sentence_template = `${(evalItem.sentence_template || '').trim()} ${token}`.trim();
    captureHistorySnapshot();
};

/* ==========================================================================
   7. ONE-CLICK STARTER TEMPLATES FOR ALL 6 FORMATS
   ========================================================================== */
const applyStarterTemplate = (formatId = activeFormatId.value) => {
    activeFormatId.value = formatId;
    activeSlideIndex.value = 0;

    studioState.contexts = [
        {
            scenario:
                'During the fierce monsoon storm in Barangay San Luis, young Mira noticed the wooden footbridge beginning to crack. Even though the wind howled through the bamboo grove, she rang the warning bell to alert her neighbors.',
            hint: 'Pay close attention to the forces of nature and how Mira responds to protect her community.',
        },
    ];

    if (formatId === 'multiple-choice') {
        studioState.title = 'Comprehension Check: The Monsoon Warning Bell';
        studioState.description = 'Read the story scenario carefully and choose the best answer for each question.';
        studioState.xp_reward = 150;
        studioState.questions = [
            {
                question_text: 'What type of literary conflict is shown when Mira struggles against the fierce monsoon storm?',
                selection_type: 'single',
                passage_context: 'Even though the wind howled through the bamboo grove, she rang the warning bell.',
                hint: 'Think about whether the obstacle is another person, a community rule, or a force of nature.',
                explanation: 'A monsoon storm is an environmental force, making this Character vs. Nature.',
                points: 15,
                options: [
                    { option_text: 'Character vs. Nature', is_correct: true, feedback: 'Spot on! The storm is a force of nature.' },
                    { option_text: 'Character vs. Character', is_correct: false, feedback: 'Mira is not arguing with another person.' },
                    { option_text: 'Character vs. Society', is_correct: false, feedback: 'This is about weather, not community laws.' },
                    { option_text: 'Character vs. Technology', is_correct: false, feedback: 'No machines are involved in the conflict.' },
                ],
            },
        ];
    } else if (formatId === 'color-the-conflict') {
        studioState.title = 'Color the Conflict: Barangay Tales';
        studioState.description = 'Read each story excerpt and color-code the type of conflict the character faces.';
        studioState.xp_reward = 200;
        studioState.cards = [
            {
                excerpt: 'Tomas gripped the boat oar tightly as giant waves crashed over the wooden outrigger.',
                conflict_type: 'character_vs_nature',
                hint: 'Look at what is threatening the boat.',
                explanation: 'Giant ocean waves are forces of nature.',
                feedback_incorrect: 'Check if the challenge comes from weather/nature or another person.',
                points: 15,
                sort_order: 1,
            },
            {
                excerpt: 'Lina wondered in silence whether she should confess that she accidentally broke the lantern.',
                conflict_type: 'character_vs_self',
                hint: 'Where is the struggle happening—outside or inside Lina’s conscience?',
                explanation: 'Lina is wrestling with her own honesty and conscience (Character vs. Self).',
                feedback_incorrect: 'Notice that Lina is deciding within her own heart and mind.',
                points: 15,
                sort_order: 2,
            },
        ];
    } else if (formatId === 'the-conflict-map') {
        studioState.title = 'The Conflict Map: Sorting Story Events';
        studioState.description = 'Place each story event card into its matching conflict category zone!';
        studioState.xp_reward = 180;
        studioState.zones = createDefaultMapZones();
        studioState.events = [
            {
                card_letter: 'Card A',
                event_text: 'Two rival team captains argue over who should lead the rescue raft.',
                target_conflict_type: 'character_vs_character',
                hint: 'Two individuals are clashing directly.',
                explanation: 'An argument between two people is Character vs. Character.',
                points: 15,
                sort_order: 1,
            },
            {
                card_letter: 'Card B',
                event_text: 'A sudden landslide blocks the mountain trail before the explorers can cross.',
                target_conflict_type: 'character_vs_nature',
                hint: 'What caused the trail to be blocked?',
                explanation: 'A landslide is a natural hazard (Character vs. Nature).',
                points: 15,
                sort_order: 2,
            },
        ];
    } else if (formatId === 'matching-type') {
        studioState.title = 'Matching Type: Story Elements & Conflicts';
        studioState.description = 'Match each story event in Column A with its correct literary term in Column B.';
        studioState.xp_reward = 220;
        studioState.choices = createDefaultMatchingChoices();
        studioState.matching_items = [
            {
                item_number: 1,
                description: 'Mira braves the howling monsoon wind to ring the village warning bell.',
                correct_choice_letter: 'B',
                hint: 'Look for the conflict involving weather and nature.',
                explanation: 'Braving a monsoon wind is Character vs. Nature (Choice B).',
                points: 15,
                sort_order: 1,
            },
            {
                item_number: 2,
                description: 'Leo debates within himself whether he is brave enough to cross the swaying bridge.',
                correct_choice_letter: 'D',
                hint: 'This struggle takes place inside Leo’s own mind.',
                explanation: 'Overcoming personal fear is Character vs. Self (Choice D).',
                points: 15,
                sort_order: 2,
            },
        ];
    } else if (formatId === 'evaluation') {
        studioState.title = 'Critical Evaluation: Heroic Choices in the Storm';
        studioState.description = 'Pick your stance in Step 1 and complete the reasoning template in Step 2 using the Word Helpers.';
        studioState.xp_reward = 250;
        studioState.word_helpers = ['brave', 'community', 'warning bell', 'neighbors', 'safety'];
        studioState.evaluation_items = [
            {
                section_title: 'Evaluating Mira’s Decision',
                conflict_type: 'character_vs_nature',
                question_prompt: 'Did Mira make the right decision by staying to ring the warning bell during the storm?',
                step1_prompt: 'Step 1: Choose your stance:',
                sentence_template: 'I believe Mira was [stance] because she prioritized the [blank] of her community.',
                hint: 'Use words from the Word Helper bank to explain how her action helped the barangay.',
                points: 25,
                sort_order: 1,
                options: [
                    { option_text: 'Heroic & Responsible', stance_value: 'heroic and responsible', is_correct: true },
                    { option_text: 'Too Reckless', stance_value: 'too reckless', is_correct: false },
                ],
            },
        ];
    } else if (formatId === 'reflection') {
        studioState.title = 'Story Reflection: Bayanihan & Courage';
        studioState.description = 'Write a thoughtful reflection connecting Mira’s courage in the story to your own life.';
        studioState.xp_reward = 200;
        studioState.prompts = [
            {
                prompt_text: 'Describe a time when you or someone in your community helped others during a difficult situation. What motivated that choice?',
                max_words: 150,
                min_words: 20,
                guiding_hint: 'Think about how empathy and bayanihan inspire people to act bravely.',
                starter_sentence: 'One moment when I saw true community spirit was when',
                points: 50,
                sort_order: 1,
            },
        ];
    }

    captureHistorySnapshot();
    triggerStudioToast(`Loaded starter template for ${activeFormatMeta.value.name}!`);
};

// Load an existing saved quiz from the module into the Studio canvas
const loadExistingQuizIntoStudio = (savedQuiz) => {
    const targetFormat = normalizeInitialFormat(savedQuiz.type);
    activeFormatId.value = targetFormat;
    activeSlideIndex.value = 0;

    studioState.title = `${savedQuiz.title} (Copy)`;
    studioState.description = savedQuiz.description || '';
    studioState.xp_reward = savedQuiz.xp_reward || 150;
    studioState.passing_percentage = savedQuiz.passing_percentage || 70;
    studioState.time_limit_seconds = savedQuiz.time_limit_seconds || null;
    studioState.is_published = Boolean(savedQuiz.is_published ?? true);
    studioState.contexts =
        savedQuiz.contexts && savedQuiz.contexts.length > 0
            ? JSON.parse(JSON.stringify(savedQuiz.contexts))
            : [{ scenario: '', hint: '' }];

    if (targetFormat === 'multiple-choice' && savedQuiz.questions?.length) {
        studioState.questions = JSON.parse(JSON.stringify(savedQuiz.questions));
    } else if (targetFormat === 'color-the-conflict' && savedQuiz.cards?.length) {
        studioState.cards = JSON.parse(JSON.stringify(savedQuiz.cards));
    } else if (targetFormat === 'the-conflict-map') {
        if (savedQuiz.zones?.length) {
            studioState.zones = JSON.parse(JSON.stringify(savedQuiz.zones));
        }
        if (savedQuiz.events?.length) {
            studioState.events = JSON.parse(JSON.stringify(savedQuiz.events));
        }
    } else if (targetFormat === 'matching-type') {
        if (savedQuiz.choices?.length) {
            studioState.choices = JSON.parse(JSON.stringify(savedQuiz.choices));
        }
        if (savedQuiz.matching_items?.length) {
            studioState.matching_items = JSON.parse(JSON.stringify(savedQuiz.matching_items));
        }
    } else if (targetFormat === 'evaluation') {
        if (savedQuiz.word_helpers?.length) {
            studioState.word_helpers = JSON.parse(JSON.stringify(savedQuiz.word_helpers));
        }
        if (savedQuiz.evaluation_items?.length) {
            studioState.evaluation_items = JSON.parse(JSON.stringify(savedQuiz.evaluation_items));
        }
    } else if (targetFormat === 'reflection' && savedQuiz.prompts?.length) {
        studioState.prompts = JSON.parse(JSON.stringify(savedQuiz.prompts));
    }

    captureHistorySnapshot();
    triggerStudioToast(`Loaded "${savedQuiz.title}" into Studio!`);
};

const deleteSavedQuiz = (savedQuiz) => {
    if (!window.confirm(`Delete quiz "${savedQuiz.title}" from this module?`)) {
        return;
    }
    router.delete(`/teachers/modules/${props.module.slug}/quizzes/${savedQuiz.id}`, {
        preserveScroll: true,
    });
};

/* ==========================================================================
   8. INTERACTIVE STUDENT SIMULATOR MODAL (PLAY-TEST ALL 6 QUIZ TYPES)
   ========================================================================== */
const showSimulatorModal = ref(false);
const simAnswers = ref({});
const simTextInputs = ref({});
const simChecked = ref(false);

const openStudentSimulator = () => {
    simAnswers.value = {};
    simTextInputs.value = {};
    simChecked.value = false;
    showSimulatorModal.value = true;
};

const checkSimulatorAnswers = () => {
    simChecked.value = true;
};

/* ==========================================================================
   9. BUILD PAYLOAD & PUBLISH TO DEDICATED BACKEND STORE ENDPOINT
   ========================================================================== */
const buildSubmissionPayload = () => {
    const nonEmptyContexts = studioState.contexts
        .filter((c) => c.scenario && c.scenario.trim() !== '')
        .map((c) => ({
            scenario: c.scenario.trim(),
            hint: c.hint ? c.hint.trim() : null,
        }));

    const basePayload = {
        title: studioState.title.trim(),
        description: studioState.description?.trim() || null,
        xp_reward: Number(studioState.xp_reward) || 150,
        passing_percentage: Number(studioState.passing_percentage) || 70,
        time_limit_seconds: studioState.time_limit_seconds ? Number(studioState.time_limit_seconds) : null,
        is_published: Boolean(studioState.is_published),
        contexts: nonEmptyContexts.length > 0 ? nonEmptyContexts : null,
    };

    if (activeFormatId.value === 'multiple-choice') {
        return {
            ...basePayload,
            reading_passage: nonEmptyContexts[0]?.scenario || null,
            questions: studioState.questions.map((q) => ({
                question_text: q.question_text,
                selection_type: q.selection_type || 'single',
                passage_context: q.passage_context || null,
                hint: q.hint || null,
                explanation: q.explanation || null,
                points: Number(q.points) || 10,
                options: q.options.map((o) => ({
                    option_text: o.option_text,
                    is_correct: Boolean(o.is_correct),
                    feedback: o.feedback || null,
                })),
            })),
        };
    }

    if (activeFormatId.value === 'color-the-conflict') {
        return {
            ...basePayload,
            cards: studioState.cards.map((c, idx) => ({
                excerpt: c.excerpt,
                conflict_type: c.conflict_type,
                hint: c.hint || null,
                explanation: c.explanation || null,
                feedback_incorrect: c.feedback_incorrect || null,
                points: Number(c.points) || 10,
                sort_order: idx + 1,
            })),
        };
    }

    if (activeFormatId.value === 'the-conflict-map') {
        return {
            ...basePayload,
            zones: studioState.zones.map((z, idx) => ({
                conflict_type: z.conflict_type,
                label: z.label,
                subtitle: z.subtitle || null,
                color_theme: z.color_theme || 'blue',
                sort_order: idx + 1,
            })),
            events: studioState.events.map((e, idx) => ({
                card_letter: e.card_letter || getCardLetter(idx),
                event_text: e.event_text,
                target_conflict_type: e.target_conflict_type,
                hint: e.hint || null,
                explanation: e.explanation || null,
                points: Number(e.points) || 10,
                sort_order: idx + 1,
            })),
        };
    }

    if (activeFormatId.value === 'matching-type') {
        return {
            ...basePayload,
            choices: studioState.choices.map((c) => ({
                choice_letter: c.choice_letter,
                choice_text: c.choice_text,
            })),
            items: studioState.matching_items.map((itm, idx) => ({
                item_number: idx + 1,
                description: itm.description,
                correct_choice_letter: itm.correct_choice_letter,
                hint: itm.hint || null,
                explanation: itm.explanation || null,
                points: Number(itm.points) || 10,
                sort_order: idx + 1,
            })),
        };
    }

    if (activeFormatId.value === 'evaluation') {
        return {
            ...basePayload,
            word_helpers: studioState.word_helpers.filter((w) => w && w.trim() !== ''),
            items: studioState.evaluation_items.map((itm, idx) => ({
                section_title: itm.section_title,
                conflict_type: itm.conflict_type || 'character_vs_character',
                question_prompt: itm.question_prompt,
                step1_prompt: itm.step1_prompt || 'Step 1: Pick one option:',
                sentence_template: itm.sentence_template,
                hint: itm.hint || null,
                points: Number(itm.points) || 25,
                sort_order: idx + 1,
                options: itm.options.map((opt) => ({
                    option_text: opt.option_text,
                    stance_value: opt.stance_value || null,
                    is_correct: Boolean(opt.is_correct),
                })),
            })),
        };
    }

    // Reflection
    return {
        ...basePayload,
        prompts: studioState.prompts.map((p, idx) => ({
            prompt_text: p.prompt_text,
            max_words: Number(p.max_words) || 150,
            min_words: p.min_words ? Number(p.min_words) : null,
            guiding_hint: p.guiding_hint || null,
            starter_sentence: p.starter_sentence || null,
            points: Number(p.points) || 50,
            sort_order: idx + 1,
        })),
    };
};

const publishQuizFromStudio = () => {
    validationErrors.value = {};
    const endpoint = `/teachers/modules/${props.module.slug}/quizzes/${activeFormatMeta.value.endpointSuffix}`;
    const payload = buildSubmissionPayload();

    isSubmitting.value = true;
    router.post(endpoint, payload, {
        preserveScroll: true,
        onError: (errors) => {
            validationErrors.value = errors || {};
            isSubmitting.value = false;
            triggerStudioToast('Please complete all required fields highlighted in red.');
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const handleBackToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const handleGlobalKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
        e.preventDefault();
        undoStudio();
    } else if (
        ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') ||
        ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'z')
    ) {
        e.preventDefault();
        redoStudio();
    }
};

watch(
    () => props.initialQuizType,
    (newType) => {
        if (newType) {
            activeFormatId.value = normalizeInitialFormat(newType);
        }
    }
);

onMounted(() => {
    captureHistorySnapshot();
    window.addEventListener('keydown', handleGlobalKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <Head :title="`Quiz Studio (${activeFormatMeta.name}) • ${module.title}`" />

    <div class="quiz-studio-shell">
        <!-- =========================================================================
             1. TOP STUDIO COMMAND BAR (FULL-WIDTH 100VW HEADER)
             ========================================================================= -->
        <header class="studio-top-command-bar">
            <div class="command-left">
                <button type="button" class="studio-back-btn" @click="handleBackToModule">
                    <span>← Module Map</span>
                </button>

                <div class="studio-module-chip">
                    <span class="module-emoji">{{ module.icon || '📘' }}</span>
                    <span class="module-name-trunc">{{ module.title }}</span>
                </div>

                <!-- Active Quiz Format Badge -->
                <div
                    class="active-format-pill"
                    :style="{
                        backgroundColor: activeFormatMeta.bgSoft,
                        borderColor: activeFormatMeta.borderSoft,
                        color: activeFormatMeta.accent,
                    }"
                >
                    <span>{{ activeFormatMeta.icon }}</span>
                    <strong>{{ activeFormatMeta.name }}</strong>
                </div>
            </div>

            <!-- Center Editable Quiz Title Input -->
            <div class="command-center">
                <input
                    v-model="studioState.title"
                    type="text"
                    class="studio-title-input"
                    :class="{ 'has-error': validationErrors.title }"
                    :placeholder="`Enter ${activeFormatMeta.name} Quiz Title...`"
                    @blur="captureHistorySnapshot"
                />
            </div>

            <!-- Right Studio Actions: Undo/Redo, Stats, Template, Simulator & Publish -->
            <div class="command-right">
                <div class="undo-redo-group" role="group" aria-label="Undo and Redo">
                    <button
                        type="button"
                        class="studio-icon-btn"
                        :disabled="!canUndo"
                        title="Undo (Ctrl+Z)"
                        @click="undoStudio"
                    >
                        ↶
                    </button>
                    <button
                        type="button"
                        class="studio-icon-btn"
                        :disabled="!canRedo"
                        title="Redo (Ctrl+Y)"
                        @click="redoStudio"
                    >
                        ↷
                    </button>
                </div>

                <div class="studio-stats-pills">
                    <span class="stat-pill pill-pts" title="Total Quiz Points">
                        🎯 {{ totalPoints }} pts
                    </span>
                    <span class="stat-pill pill-xp" title="Student XP Bounty">
                        ⚡ +{{ studioState.xp_reward }} XP
                    </span>
                </div>

                <button
                    type="button"
                    class="studio-action-btn btn-template"
                    title="Fill with Sample Template"
                    @click="applyStarterTemplate(activeFormatId)"
                >
                    <span>✨ Fill Sample</span>
                </button>

                <button
                    type="button"
                    class="studio-action-btn btn-simulator"
                    @click="openStudentSimulator"
                >
                    <span>📱 Test as Student</span>
                </button>

                <button
                    type="button"
                    class="studio-action-btn btn-publish"
                    :disabled="isSubmitting"
                    @click="publishQuizFromStudio"
                >
                    <span>{{ isSubmitting ? 'Publishing...' : '🚀 Publish Quiz' }}</span>
                </button>
            </div>
        </header>

        <!-- Validation Error Summary Strip (If Any) -->
        <div
            v-if="Object.keys(validationErrors).length > 0"
            class="studio-error-banner"
            role="alert"
        >
            <strong>⚠️ Please review the following before publishing:</strong>
            <div class="error-list-inline">
                <span v-for="(errMsg, errKey) in validationErrors" :key="errKey" class="error-chip">
                    {{ Array.isArray(errMsg) ? errMsg[0] : errMsg }}
                </span>
            </div>
        </div>

        <!-- Toast Feedback Banner -->
        <Transition name="studio-pop">
            <div v-if="studioToastMessage" class="studio-toast-pill" role="status" aria-live="polite">
                {{ studioToastMessage }}
            </div>
        </Transition>

        <!-- =========================================================================
             2. MAIN 3-PANE FULL-SCREEN STUDIO WORKSPACE
             ========================================================================= -->
        <div class="studio-workspace-body">
            <!-- PANE A: LEFT CANVA ICON RAIL + EXPANDABLE TOOL SHELF -->
            <div class="studio-left-dock">
                <nav class="canva-icon-rail" aria-label="Quiz Studio Tools">
                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': activeLeftTab === 'formats' && isLeftDrawerOpen }"
                        @click="
                            activeLeftTab === 'formats'
                                ? (isLeftDrawerOpen = !isLeftDrawerOpen)
                                : ((activeLeftTab = 'formats'), (isLeftDrawerOpen = true))
                        "
                    >
                        <span class="rail-icon">🎮</span>
                        <span class="rail-lbl">Formats</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': activeLeftTab === 'context' && isLeftDrawerOpen }"
                        @click="
                            activeLeftTab === 'context'
                                ? (isLeftDrawerOpen = !isLeftDrawerOpen)
                                : ((activeLeftTab = 'context'), (isLeftDrawerOpen = true))
                        "
                    >
                        <span class="rail-icon">📜</span>
                        <span class="rail-lbl">Passages</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': activeLeftTab === 'templates' && isLeftDrawerOpen }"
                        @click="
                            activeLeftTab === 'templates'
                                ? (isLeftDrawerOpen = !isLeftDrawerOpen)
                                : ((activeLeftTab = 'templates'), (isLeftDrawerOpen = true))
                        "
                    >
                        <span class="rail-icon">✨</span>
                        <span class="rail-lbl">Presets</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': activeLeftTab === 'settings' && isLeftDrawerOpen }"
                        @click="
                            activeLeftTab === 'settings'
                                ? (isLeftDrawerOpen = !isLeftDrawerOpen)
                                : ((activeLeftTab = 'settings'), (isLeftDrawerOpen = true))
                        "
                    >
                        <span class="rail-icon">⚙️</span>
                        <span class="rail-lbl">Scoring</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': activeLeftTab === 'saved' && isLeftDrawerOpen }"
                        @click="
                            activeLeftTab === 'saved'
                                ? (isLeftDrawerOpen = !isLeftDrawerOpen)
                                : ((activeLeftTab = 'saved'), (isLeftDrawerOpen = true))
                        "
                    >
                        <span class="rail-icon">📚</span>
                        <span class="rail-lbl">Quizzes ({{ existingQuizzes.length }})</span>
                    </button>
                </nav>

                <!-- Expandable Tool Shelf -->
                <aside v-if="isLeftDrawerOpen" class="canva-tool-shelf">
                    <div class="shelf-header">
                        <h2 class="shelf-title">
                            <template v-if="activeLeftTab === 'formats'">6 Quiz Formats</template>
                            <template v-else-if="activeLeftTab === 'context'">Story Passages</template>
                            <template v-else-if="activeLeftTab === 'templates'">Starter Presets</template>
                            <template v-else-if="activeLeftTab === 'settings'">Quiz & XP Rules</template>
                            <template v-else>Module Quizzes</template>
                        </h2>
                        <button
                            type="button"
                            class="shelf-collapse-btn"
                            title="Collapse Panel"
                            @click="isLeftDrawerOpen = false"
                        >
                            ◀
                        </button>
                    </div>

                    <!-- TAB 1: ALL 6 QUIZ FORMATS SWITCHER -->
                    <div v-if="activeLeftTab === 'formats'" class="shelf-scroll-content">
                        <p class="shelf-helper-copy">
                            Choose any of the 6 interactive quiz formats below to design on the studio canvas:
                        </p>

                        <div class="formats-cards-stack">
                            <button
                                v-for="fmt in quizFormats"
                                :key="fmt.id"
                                type="button"
                                class="format-selector-card"
                                :class="{ 'is-selected': activeFormatId === fmt.id }"
                                :style="{ '--fmt-accent': fmt.accent, '--fmt-bg': fmt.bgSoft }"
                                @click="selectQuizFormat(fmt.id)"
                            >
                                <div class="fmt-card-top">
                                    <span class="fmt-icon-badge">{{ fmt.icon }}</span>
                                    <div class="fmt-title-col">
                                        <strong>{{ fmt.name }}</strong>
                                        <span>+{{ fmt.defaultXp }} XP Default</span>
                                    </div>
                                    <span v-if="activeFormatId === fmt.id" class="fmt-active-dot">ACTIVE</span>
                                </div>
                                <p class="fmt-card-desc">{{ fmt.description }}</p>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: STORY CONTEXTS / PASSAGES -->
                    <div v-else-if="activeLeftTab === 'context'" class="shelf-scroll-content">
                        <p class="shelf-helper-copy">
                            Attach story scenarios or reading excerpts that students read alongside this quiz:
                        </p>

                        <label class="shelf-checkbox-row">
                            <input v-model="showContextBannerOnCanvas" type="checkbox" />
                            <span>Show Story Passage editor on canvas</span>
                        </label>

                        <div
                            v-for="(ctx, cIdx) in studioState.contexts"
                            :key="cIdx"
                            class="shelf-context-box"
                        >
                            <div class="context-box-head">
                                <strong>📜 Story Scenario #{{ cIdx + 1 }}</strong>
                                <button
                                    type="button"
                                    class="mini-danger-btn"
                                    @click="removeContextScenario(cIdx)"
                                >
                                    Clear
                                </button>
                            </div>
                            <textarea
                                v-model="ctx.scenario"
                                rows="4"
                                class="studio-textarea"
                                placeholder="Paste or type the story passage or conflict scenario..."
                            ></textarea>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="studio-input"
                                placeholder="Optional reading hint for students..."
                            />
                        </div>

                        <button type="button" class="shelf-add-btn" @click="addContextScenario">
                            + Add Another Story Scenario
                        </button>
                    </div>

                    <!-- TAB 3: STARTER TEMPLATES -->
                    <div v-else-if="activeLeftTab === 'templates'" class="shelf-scroll-content">
                        <p class="shelf-helper-copy">
                            One-click literary conflict & comprehension templates for every quiz format:
                        </p>

                        <div class="formats-cards-stack">
                            <div
                                v-for="fmt in quizFormats"
                                :key="fmt.id"
                                class="template-preset-card"
                            >
                                <div class="fmt-card-top">
                                    <span class="fmt-icon-badge">{{ fmt.icon }}</span>
                                    <div class="fmt-title-col">
                                        <strong>{{ fmt.name }} Sample</strong>
                                        <span>Pre-filled story scenario + items</span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="shelf-load-template-btn"
                                    @click="applyStarterTemplate(fmt.id)"
                                >
                                    ✨ Load {{ fmt.shortLabel }} Sample
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: SCORING, XP & TIMER SETTINGS -->
                    <div v-else-if="activeLeftTab === 'settings'" class="shelf-scroll-content">
                        <div class="shelf-field-group">
                            <label class="shelf-label">Quiz Directions / Description</label>
                            <textarea
                                v-model="studioState.description"
                                rows="3"
                                class="studio-textarea"
                                placeholder="Instructions shown to students before starting..."
                            ></textarea>
                        </div>

                        <div class="shelf-field-group">
                            <label class="shelf-label">XP Reward Bounty ({{ studioState.xp_reward }} XP)</label>
                            <div class="preset-chips-row">
                                <button
                                    v-for="xp in xpPresets"
                                    :key="xp"
                                    type="button"
                                    class="preset-chip"
                                    :class="{ 'is-active': Number(studioState.xp_reward) === xp }"
                                    @click="studioState.xp_reward = xp"
                                >
                                    +{{ xp }} XP
                                </button>
                            </div>
                            <input
                                v-model.number="studioState.xp_reward"
                                type="number"
                                min="10"
                                max="50000"
                                class="studio-input"
                            />
                        </div>

                        <div class="shelf-field-group">
                            <label class="shelf-label">Passing Score ({{ studioState.passing_percentage }}%)</label>
                            <div class="preset-chips-row">
                                <button
                                    v-for="pct in passPresets"
                                    :key="pct"
                                    type="button"
                                    class="preset-chip"
                                    :class="{ 'is-active': Number(studioState.passing_percentage) === pct }"
                                    @click="studioState.passing_percentage = pct"
                                >
                                    {{ pct }}%
                                </button>
                            </div>
                        </div>

                        <div class="shelf-field-group">
                            <label class="shelf-label">Optional Time Limit (Seconds)</label>
                            <input
                                v-model.number="studioState.time_limit_seconds"
                                type="number"
                                min="10"
                                max="7200"
                                class="studio-input"
                                placeholder="No timer (leave blank)"
                            />
                        </div>

                        <label class="shelf-checkbox-row">
                            <input v-model="studioState.is_published" type="checkbox" />
                            <span>Publish immediately on Module Map</span>
                        </label>
                    </div>

                    <!-- TAB 5: EXISTING QUIZZES IN MODULE -->
                    <div v-else class="shelf-scroll-content">
                        <p class="shelf-helper-copy">
                            Existing quizzes in <strong>{{ module.title }}</strong>. Load any quiz into the canvas or remove it:
                        </p>

                        <div v-if="existingQuizzes.length === 0" class="empty-saved-box">
                            No quizzes published in this module yet.
                        </div>

                        <div v-else class="saved-quizzes-list">
                            <div
                                v-for="sq in existingQuizzes"
                                :key="sq.id"
                                class="saved-quiz-card"
                            >
                                <div class="saved-quiz-top">
                                    <span class="saved-type-tag">{{ sq.type }}</span>
                                    <span class="saved-xp-tag">+{{ sq.xp_reward }} XP</span>
                                </div>
                                <strong class="saved-quiz-title">{{ sq.title }}</strong>
                                <div class="saved-quiz-actions">
                                    <button
                                        type="button"
                                        class="mini-load-btn"
                                        @click="loadExistingQuizIntoStudio(sq)"
                                    >
                                        📂 Load into Studio
                                    </button>
                                    <button
                                        type="button"
                                        class="mini-danger-btn"
                                        @click="deleteSavedQuiz(sq)"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- =====================================================================
                 PANE B: CENTER STAGE INTERACTIVE QUIZ ARTBOARD CANVAS
                 ===================================================================== -->
            <main class="studio-center-canvas-stage">
                <div class="canvas-artboard-sheet">
                    <!-- 1. COLLAPSIBLE STORY SCENARIO / READING PASSAGE BANNER -->
                    <section v-if="showContextBannerOnCanvas" class="artboard-context-banner">
                        <div class="context-banner-head">
                            <div class="context-head-left">
                                <span class="context-pill-badge">📜 STORY SCENARIO / READING PASSAGE</span>
                                <span class="context-sub-hint">
                                    Displayed to students alongside the quiz ({{ studioState.contexts.length }}
                                    {{ studioState.contexts.length === 1 ? 'passage' : 'passages' }})
                                </span>
                            </div>
                            <div class="context-head-actions">
                                <button type="button" class="mini-outline-btn" @click="addContextScenario">
                                    + Add Passage
                                </button>
                                <button
                                    type="button"
                                    class="mini-outline-btn"
                                    @click="showContextBannerOnCanvas = false"
                                >
                                    Hide
                                </button>
                            </div>
                        </div>

                        <div class="context-scenarios-grid">
                            <div
                                v-for="(ctx, cIdx) in studioState.contexts"
                                :key="cIdx"
                                class="context-scenario-card"
                            >
                                <textarea
                                    v-model="ctx.scenario"
                                    rows="2"
                                    class="studio-textarea"
                                    placeholder="Write or paste the story passage or conflict scenario here..."
                                    @blur="captureHistorySnapshot"
                                ></textarea>
                                <div class="context-hint-row">
                                    <input
                                        v-model="ctx.hint"
                                        type="text"
                                        class="studio-input"
                                        placeholder="💡 Guiding hint for this passage (optional)..."
                                    />
                                    <button
                                        v-if="studioState.contexts.length > 1"
                                        type="button"
                                        class="mini-danger-btn"
                                        @click="removeContextScenario(cIdx)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 2. FORMAT-SPECIFIC VISUAL CARD EDITOR -->
                    <section v-if="currentSlideItem" class="artboard-active-item-card">
                        <!-- Top Slide Header Bar -->
                        <div class="item-card-header">
                            <div class="item-header-left">
                                <span
                                    class="item-number-badge"
                                    :style="{ backgroundColor: activeFormatMeta.accent }"
                                >
                                    {{ activeFormatMeta.itemNoun.toUpperCase() }} {{ activeSlideIndex + 1 }} OF
                                    {{ activeItemsArray.length }}
                                </span>
                                <span class="item-format-sub">
                                    {{ activeFormatMeta.icon }} {{ activeFormatMeta.name }} Builder
                                </span>
                            </div>

                            <div class="item-header-right">
                                <label class="points-inline-box">
                                    <span>Points:</span>
                                    <input
                                        v-model.number="currentSlideItem.points"
                                        type="number"
                                        min="1"
                                        max="1000"
                                        class="points-number-input"
                                    />
                                </label>

                                <button
                                    type="button"
                                    class="mini-outline-btn"
                                    title="Duplicate this item"
                                    @click="duplicateSlideItem(activeSlideIndex)"
                                >
                                    ⧉ Duplicate
                                </button>

                                <button
                                    type="button"
                                    class="mini-danger-btn"
                                    title="Delete this item"
                                    @click="removeSlideItem(activeSlideIndex)"
                                >
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>

                        <!-- =========================================================
                             ENGINE 1: MULTIPLE CHOICE VISUAL EDITOR
                             ========================================================= -->
                        <div v-if="activeFormatId === 'multiple-choice'" class="engine-body">
                            <div class="engine-row-split">
                                <div class="field-col-grow">
                                    <label class="field-label">Question Prompt *</label>
                                    <textarea
                                        v-model="currentSlideItem.question_text"
                                        rows="2"
                                        class="studio-textarea large-prompt-input"
                                        placeholder="Type your multiple-choice question here..."
                                        @blur="captureHistorySnapshot"
                                    ></textarea>
                                </div>

                                <div class="field-col-side">
                                    <label class="field-label">Answer Mode</label>
                                    <select
                                        v-model="currentSlideItem.selection_type"
                                        class="studio-select"
                                        @change="onMcSelectionTypeChange(currentSlideItem)"
                                    >
                                        <option value="single">🔘 Single Correct Answer</option>
                                        <option value="multiple">☑️ Multiple Correct Answers</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Answer Choices Grid (A - F) -->
                            <div class="mc-options-header">
                                <span class="field-label">
                                    Answer Choices ({{ currentSlideItem.options.length }} / 6) — Click badge to mark
                                    Correct ✓
                                </span>
                                <button
                                    v-if="currentSlideItem.options.length < 6"
                                    type="button"
                                    class="mini-add-choice-btn"
                                    @click="addMcOption(currentSlideItem)"
                                >
                                    + Add Choice {{ optionLetters[currentSlideItem.options.length] }}
                                </button>
                            </div>

                            <div class="mc-options-grid">
                                <div
                                    v-for="(opt, oIdx) in currentSlideItem.options"
                                    :key="oIdx"
                                    class="mc-option-card"
                                    :class="{ 'is-correct-choice': opt.is_correct }"
                                >
                                    <div class="mc-opt-top">
                                        <button
                                            type="button"
                                            class="mc-letter-toggle"
                                            :class="{ 'is-correct': opt.is_correct }"
                                            @click="toggleMcCorrectOption(currentSlideItem, oIdx)"
                                        >
                                            <span>{{ optionLetters[oIdx] }}</span>
                                            <small>{{ opt.is_correct ? '✓ Correct' : 'Mark Correct' }}</small>
                                        </button>

                                        <button
                                            v-if="currentSlideItem.options.length > 2"
                                            type="button"
                                            class="mc-remove-opt-btn"
                                            title="Remove choice"
                                            @click="removeMcOption(currentSlideItem, oIdx)"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <input
                                        v-model="opt.option_text"
                                        type="text"
                                        class="studio-input"
                                        :placeholder="`Enter Choice ${optionLetters[oIdx]} text...`"
                                    />

                                    <input
                                        v-model="opt.feedback"
                                        type="text"
                                        class="studio-input opt-feedback-input"
                                        placeholder="Optional feedback when student picks this..."
                                    />
                                </div>
                            </div>

                            <div class="engine-row-two-col">
                                <div>
                                    <label class="field-label">💡 Student Hint (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.hint"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Clue shown if student needs help..."
                                    />
                                </div>
                                <div>
                                    <label class="field-label">✨ Answer Explanation (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.explanation"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Why is this answer correct?"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- =========================================================
                             ENGINE 2: COLOR THE CONFLICT FLASHCARD EDITOR
                             ========================================================= -->
                        <div v-else-if="activeFormatId === 'color-the-conflict'" class="engine-body">
                            <div>
                                <label class="field-label">Story Excerpt on Flashcard *</label>
                                <textarea
                                    v-model="currentSlideItem.excerpt"
                                    rows="3"
                                    class="studio-textarea large-prompt-input"
                                    placeholder="Enter the story passage or conflict scene that students will classify..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="field-label">
                                    Select the Correct Conflict Color Category *
                                </label>
                                <div class="conflict-swatches-grid">
                                    <button
                                        v-for="cat in conflictCategories"
                                        :key="cat.type"
                                        type="button"
                                        class="conflict-color-swatch"
                                        :class="{ 'is-selected': currentSlideItem.conflict_type === cat.type }"
                                        :style="{
                                            '--swatch-color': cat.colorHex,
                                            '--swatch-border': cat.borderHex,
                                            '--swatch-bg': cat.bgLight,
                                        }"
                                        @click="currentSlideItem.conflict_type = cat.type"
                                    >
                                        <span class="swatch-icon">{{ cat.icon }}</span>
                                        <div class="swatch-copy">
                                            <strong>{{ cat.label }}</strong>
                                            <small>{{ cat.subtitle }}</small>
                                        </div>
                                        <span
                                            v-if="currentSlideItem.conflict_type === cat.type"
                                            class="swatch-check"
                                        >
                                            ✓
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="engine-row-three-col">
                                <div>
                                    <label class="field-label">💡 Hint (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.hint"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Hint for students..."
                                    />
                                </div>
                                <div>
                                    <label class="field-label">✓ Correct Explanation</label>
                                    <input
                                        v-model="currentSlideItem.explanation"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Shown when matched..."
                                    />
                                </div>
                                <div>
                                    <label class="field-label">↩ Retry Feedback</label>
                                    <input
                                        v-model="currentSlideItem.feedback_incorrect"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Shown if wrong color picked..."
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- =========================================================
                             ENGINE 3: THE CONFLICT MAP (DROP ZONES + EVENT CARDS)
                             ========================================================= -->
                        <div v-else-if="activeFormatId === 'the-conflict-map'" class="engine-body">
                            <!-- Drop Zones Manager Bar -->
                            <div class="map-zones-manager-box">
                                <div class="mc-options-header">
                                    <span class="field-label">
                                        🗺️ Conflict Category Drop Zones ({{ studioState.zones.length }} / 6)
                                    </span>
                                    <button
                                        v-if="studioState.zones.length < 6"
                                        type="button"
                                        class="mini-add-choice-btn"
                                        @click="addMapZone"
                                    >
                                        + Add Drop Zone
                                    </button>
                                </div>

                                <div class="map-zones-strip">
                                    <div
                                        v-for="(zone, zIdx) in studioState.zones"
                                        :key="zIdx"
                                        class="map-zone-pill-card"
                                        :class="{
                                            'is-target-zone':
                                                currentSlideItem.target_conflict_type === zone.conflict_type,
                                        }"
                                    >
                                        <div class="zone-card-top">
                                            <select v-model="zone.conflict_type" class="studio-select-mini">
                                                <option
                                                    v-for="cat in conflictCategories"
                                                    :key="cat.type"
                                                    :value="cat.type"
                                                >
                                                    {{ cat.icon }} {{ cat.label }}
                                                </option>
                                            </select>
                                            <button
                                                v-if="studioState.zones.length > 2"
                                                type="button"
                                                class="mc-remove-opt-btn"
                                                @click="removeMapZone(zIdx)"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <input
                                            v-model="zone.label"
                                            type="text"
                                            class="studio-input"
                                            placeholder="Zone Heading Label..."
                                        />
                                        <button
                                            type="button"
                                            class="assign-zone-btn"
                                            :class="{
                                                'is-assigned':
                                                    currentSlideItem.target_conflict_type === zone.conflict_type,
                                            }"
                                            @click="currentSlideItem.target_conflict_type = zone.conflict_type"
                                        >
                                            {{
                                                currentSlideItem.target_conflict_type === zone.conflict_type
                                                    ? '✓ Target Zone for This Card'
                                                    : 'Set as Target Zone'
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Event Bank Card Editor -->
                            <div class="engine-row-split">
                                <div class="field-col-side">
                                    <label class="field-label">Card Badge Label</label>
                                    <input
                                        v-model="currentSlideItem.card_letter"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Card A"
                                    />
                                </div>
                                <div class="field-col-grow">
                                    <label class="field-label">Story Event Description *</label>
                                    <textarea
                                        v-model="currentSlideItem.event_text"
                                        rows="3"
                                        class="studio-textarea large-prompt-input"
                                        placeholder="Describe the story event that students will drag/place into the target zone..."
                                    ></textarea>
                                </div>
                            </div>

                            <div class="engine-row-two-col">
                                <div>
                                    <label class="field-label">💡 Hint (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.hint"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Clue for placing this event card..."
                                    />
                                </div>
                                <div>
                                    <label class="field-label">✨ Explanation (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.explanation"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Why does this event belong in that zone?"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- =========================================================
                             ENGINE 4: MATCHING TYPE (COLUMN A & COLUMN B CONNECTOR)
                             ========================================================= -->
                        <div v-else-if="activeFormatId === 'matching-type'" class="engine-body">
                            <div class="matching-two-col-stage">
                                <!-- Left: Active Column A Description Item -->
                                <div class="matching-col-a-editor">
                                    <label class="field-label">
                                        Column A — Item #{{ activeSlideIndex + 1 }} Description *
                                    </label>
                                    <textarea
                                        v-model="currentSlideItem.description"
                                        rows="4"
                                        class="studio-textarea large-prompt-input"
                                        placeholder="Enter the story event, quote, or description in Column A..."
                                    ></textarea>

                                    <div class="matching-answer-key-box">
                                        <span class="field-label">
                                            🔗 Connected Answer in Column B:
                                            <strong>Choice {{ currentSlideItem.correct_choice_letter }}</strong>
                                        </span>
                                        <div class="preset-chips-row">
                                            <button
                                                v-for="ch in studioState.choices"
                                                :key="ch.choice_letter"
                                                type="button"
                                                class="preset-chip"
                                                :class="{
                                                    'is-active':
                                                        currentSlideItem.correct_choice_letter === ch.choice_letter,
                                                }"
                                                @click="currentSlideItem.correct_choice_letter = ch.choice_letter"
                                            >
                                                {{ ch.choice_letter }}: {{ ch.choice_text || '(Empty)' }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="engine-row-two-col">
                                        <div>
                                            <label class="field-label">💡 Hint (Optional)</label>
                                            <input
                                                v-model="currentSlideItem.hint"
                                                type="text"
                                                class="studio-input"
                                                placeholder="Optional hint..."
                                            />
                                        </div>
                                        <div>
                                            <label class="field-label">✨ Explanation (Optional)</label>
                                            <input
                                                v-model="currentSlideItem.explanation"
                                                type="text"
                                                class="studio-input"
                                                placeholder="Optional explanation..."
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Shared Column B Choices Pool -->
                                <div class="matching-col-b-pool">
                                    <div class="mc-options-header">
                                        <span class="field-label">
                                            Column B — Shared Answer Pool ({{ studioState.choices.length }})
                                        </span>
                                        <button
                                            type="button"
                                            class="mini-add-choice-btn"
                                            @click="addMatchingChoice"
                                        >
                                            + Add Choice {{ getLetter(studioState.choices.length) }}
                                        </button>
                                    </div>

                                    <div class="col-b-choices-list">
                                        <div
                                            v-for="(choice, cIdx) in studioState.choices"
                                            :key="cIdx"
                                            class="col-b-choice-row"
                                            :class="{
                                                'is-linked':
                                                    currentSlideItem.correct_choice_letter === choice.choice_letter,
                                            }"
                                        >
                                            <button
                                                type="button"
                                                class="col-b-letter-pill"
                                                @click="currentSlideItem.correct_choice_letter = choice.choice_letter"
                                            >
                                                {{ choice.choice_letter }}
                                            </button>
                                            <input
                                                v-model="choice.choice_text"
                                                type="text"
                                                class="studio-input"
                                                :placeholder="`Column B Choice ${choice.choice_letter}...`"
                                            />
                                            <button
                                                v-if="studioState.choices.length > 2"
                                                type="button"
                                                class="mc-remove-opt-btn"
                                                @click="removeMatchingChoice(cIdx)"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- =========================================================
                             ENGINE 5: EVALUATION (WORD HELPERS + 2-STEP STANCE & BLANK)
                             ========================================================= -->
                        <div v-else-if="activeFormatId === 'evaluation'" class="engine-body">
                            <!-- Word Helper Bank Bar -->
                            <div class="word-helpers-bar">
                                <div class="word-helpers-head">
                                    <span class="field-label">
                                        🧰 Shared Word Helper Bank (Students can tap or reference these words)
                                    </span>
                                    <div class="word-helper-add-inline">
                                        <input
                                            v-model="newWordHelperInput"
                                            type="text"
                                            class="studio-input"
                                            placeholder="Add helper word..."
                                            @keydown.enter.prevent="addWordHelperChip"
                                        />
                                        <button
                                            type="button"
                                            class="mini-add-choice-btn"
                                            @click="addWordHelperChip"
                                        >
                                            + Add Word
                                        </button>
                                    </div>
                                </div>

                                <div class="preset-chips-row">
                                    <span
                                        v-for="(w, wIdx) in studioState.word_helpers"
                                        :key="wIdx"
                                        class="word-helper-chip"
                                    >
                                        {{ w }}
                                        <button type="button" @click="removeWordHelperChip(wIdx)">✕</button>
                                    </span>
                                </div>
                            </div>

                            <div class="engine-row-two-col">
                                <div>
                                    <label class="field-label">Section Title *</label>
                                    <input
                                        v-model="currentSlideItem.section_title"
                                        type="text"
                                        class="studio-input"
                                        placeholder="e.g., Evaluating Mira’s Decision"
                                    />
                                </div>
                                <div>
                                    <label class="field-label">Conflict Theme Tag</label>
                                    <select v-model="currentSlideItem.conflict_type" class="studio-select">
                                        <option
                                            v-for="cat in conflictCategories"
                                            :key="cat.type"
                                            :value="cat.type"
                                        >
                                            {{ cat.icon }} {{ cat.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Critical Thinking Question Prompt *</label>
                                <textarea
                                    v-model="currentSlideItem.question_prompt"
                                    rows="2"
                                    class="studio-textarea large-prompt-input"
                                    placeholder="Ask the critical evaluation question..."
                                ></textarea>
                            </div>

                            <!-- Step 1: Stance Options -->
                            <div class="eval-step-box">
                                <div class="mc-options-header">
                                    <span class="field-label">Step 1: Student Stance Choice Buttons *</span>
                                    <button
                                        v-if="currentSlideItem.options.length < 6"
                                        type="button"
                                        class="mini-add-choice-btn"
                                        @click="addEvalOption(currentSlideItem)"
                                    >
                                        + Add Stance Option
                                    </button>
                                </div>

                                <div class="mc-options-grid">
                                    <div
                                        v-for="(opt, oIdx) in currentSlideItem.options"
                                        :key="oIdx"
                                        class="mc-option-card"
                                        :class="{ 'is-correct-choice': opt.is_correct }"
                                    >
                                        <div class="mc-opt-top">
                                            <button
                                                type="button"
                                                class="mc-letter-toggle"
                                                :class="{ 'is-correct': opt.is_correct }"
                                                @click="opt.is_correct = !opt.is_correct"
                                            >
                                                <span>Option {{ oIdx + 1 }}</span>
                                                <small>{{ opt.is_correct ? '✓ Valid Stance' : 'Mark Valid' }}</small>
                                            </button>
                                            <button
                                                v-if="currentSlideItem.options.length > 2"
                                                type="button"
                                                class="mc-remove-opt-btn"
                                                @click="removeEvalOption(currentSlideItem, oIdx)"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <input
                                            v-model="opt.option_text"
                                            type="text"
                                            class="studio-input"
                                            placeholder="Button Label (e.g., Heroic & Responsible)"
                                        />
                                        <input
                                            v-model="opt.stance_value"
                                            type="text"
                                            class="studio-input opt-feedback-input"
                                            placeholder="Value inserted into [stance] token..."
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Sentence Template with [stance] and [blank] tokens -->
                            <div class="eval-step-box">
                                <div class="mc-options-header">
                                    <span class="field-label">
                                        Step 2: Guided Sentence Template * (Use <code>[stance]</code> and
                                        <code>[blank]</code>)
                                    </span>
                                    <div class="token-insert-btns">
                                        <button
                                            type="button"
                                            class="mini-add-choice-btn"
                                            @click="insertTokenIntoEvalTemplate(currentSlideItem, '[stance]')"
                                        >
                                            + Insert [stance]
                                        </button>
                                        <button
                                            type="button"
                                            class="mini-add-choice-btn"
                                            @click="insertTokenIntoEvalTemplate(currentSlideItem, '[blank]')"
                                        >
                                            + Insert [blank]
                                        </button>
                                    </div>
                                </div>

                                <textarea
                                    v-model="currentSlideItem.sentence_template"
                                    rows="2"
                                    class="studio-textarea"
                                    placeholder="e.g., I believe the character was [stance] because [blank]."
                                ></textarea>
                            </div>
                        </div>

                        <!-- =========================================================
                             ENGINE 6: REFLECTION (GUIDED ESSAY & JOURNAL PROMPT)
                             ========================================================= -->
                        <div v-else class="engine-body">
                            <div>
                                <label class="field-label">Reflection Question / Journal Prompt *</label>
                                <textarea
                                    v-model="currentSlideItem.prompt_text"
                                    rows="3"
                                    class="studio-textarea large-prompt-input"
                                    placeholder="Write the open-ended reflection prompt encouraging students to connect the story to their values..."
                                ></textarea>
                            </div>

                            <div class="engine-row-two-col">
                                <div>
                                    <label class="field-label">
                                        Maximum Word Limit * ({{ currentSlideItem.max_words }} words)
                                    </label>
                                    <div class="preset-chips-row">
                                        <button
                                            v-for="wLim in wordPresets"
                                            :key="wLim"
                                            type="button"
                                            class="preset-chip"
                                            :class="{ 'is-active': Number(currentSlideItem.max_words) === wLim }"
                                            @click="currentSlideItem.max_words = wLim"
                                        >
                                            {{ wLim }} words
                                        </button>
                                    </div>
                                    <input
                                        v-model.number="currentSlideItem.max_words"
                                        type="number"
                                        min="10"
                                        max="5000"
                                        class="studio-input"
                                    />
                                </div>

                                <div>
                                    <label class="field-label">Minimum Words (Optional)</label>
                                    <input
                                        v-model.number="currentSlideItem.min_words"
                                        type="number"
                                        min="1"
                                        max="5000"
                                        class="studio-input"
                                        placeholder="Optional minimum word count..."
                                    />
                                </div>
                            </div>

                            <div class="engine-row-two-col">
                                <div>
                                    <label class="field-label">✍️ Starter Sentence Scaffold (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.starter_sentence"
                                        type="text"
                                        class="studio-input"
                                        placeholder="e.g., One important lesson I learned from this story is..."
                                    />
                                </div>
                                <div>
                                    <label class="field-label">💡 Guiding Hint (Optional)</label>
                                    <input
                                        v-model="currentSlideItem.guiding_hint"
                                        type="text"
                                        class="studio-input"
                                        placeholder="Helpful reflection tip for students..."
                                    />
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>

            <!-- =====================================================================
                 PANE C: RIGHT SLIDE / ITEM NAVIGATOR DOCK
                 ===================================================================== -->
            <aside class="studio-right-navigator" aria-label="Quiz Items Slide Navigator">
                <div class="navigator-header">
                    <div>
                        <span class="navigator-kicker">SLIDE DECK</span>
                        <h3 class="navigator-title">
                            {{ activeFormatMeta.itemNoun }}s ({{ activeItemsArray.length }})
                        </h3>
                    </div>
                    <button type="button" class="nav-add-slide-btn" @click="addSlideItem">
                        + Add {{ activeFormatMeta.itemNoun }}
                    </button>
                </div>

                <div class="navigator-slides-list">
                    <div
                        v-for="(slide, sIdx) in activeItemsArray"
                        :key="sIdx"
                        class="navigator-slide-card"
                        :class="{ 'is-active-slide': sIdx === activeSlideIndex }"
                        role="button"
                        tabindex="0"
                        @click="activeSlideIndex = sIdx"
                    >
                        <div class="slide-card-top">
                            <span class="slide-index-badge">
                                #{{ sIdx + 1 }}
                            </span>
                            <span class="slide-pts-badge">{{ slide.points || 10 }} pts</span>
                        </div>

                        <p class="slide-summary-text">
                            {{ getSlideSummaryLabel(slide, sIdx) }}
                        </p>

                        <div class="slide-card-actions" @click.stop>
                            <button
                                type="button"
                                class="slide-ctrl-btn"
                                :disabled="sIdx === 0"
                                title="Move Up"
                                @click="moveSlideItem(sIdx, -1)"
                            >
                                ↑
                            </button>
                            <button
                                type="button"
                                class="slide-ctrl-btn"
                                :disabled="sIdx === activeItemsArray.length - 1"
                                title="Move Down"
                                @click="moveSlideItem(sIdx, 1)"
                            >
                                ↓
                            </button>
                            <button
                                type="button"
                                class="slide-ctrl-btn"
                                title="Duplicate"
                                @click="duplicateSlideItem(sIdx)"
                            >
                                ⧉
                            </button>
                            <button
                                type="button"
                                class="slide-ctrl-btn is-danger"
                                :disabled="activeItemsArray.length <= 1"
                                title="Delete"
                                @click="removeSlideItem(sIdx)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- =========================================================================
             3. LIVE INTERACTIVE STUDENT SIMULATOR MODAL (PLAY-TEST ALL 6 FORMATS)
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="studio-pop">
                <div
                    v-if="showSimulatorModal"
                    class="simulator-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="sim-modal-title"
                    @click.self="showSimulatorModal = false"
                >
                    <div class="simulator-modal-card">
                        <div class="sim-modal-header">
                            <div>
                                <span class="sim-kicker">
                                    📱 INTERACTIVE STUDENT PREVIEW • {{ activeFormatMeta.name.toUpperCase() }}
                                </span>
                                <h2 id="sim-modal-title" class="sim-title">
                                    {{ studioState.title || `Untitled ${activeFormatMeta.name} Challenge` }}
                                </h2>
                            </div>
                            <button
                                type="button"
                                class="sim-close-btn"
                                @click="showSimulatorModal = false"
                            >
                                ✕ Close Preview
                            </button>
                        </div>

                        <div class="sim-modal-scroll-body">
                            <!-- Story Context Scenario Preview -->
                            <div
                                v-if="studioState.contexts.some((c) => c.scenario?.trim())"
                                class="sim-context-box"
                            >
                                <strong>📜 Story Passage</strong>
                                <p
                                    v-for="(ctx, idx) in studioState.contexts.filter((c) => c.scenario?.trim())"
                                    :key="idx"
                                >
                                    "{{ ctx.scenario }}"
                                </p>
                            </div>

                            <!-- Simulator for Multiple Choice -->
                            <div v-if="activeFormatId === 'multiple-choice'" class="sim-items-stack">
                                <div
                                    v-for="(q, qIdx) in studioState.questions"
                                    :key="qIdx"
                                    class="sim-question-card"
                                >
                                    <span class="sim-item-badge">Question {{ qIdx + 1 }} ({{ q.points }} pts)</span>
                                    <h4>{{ q.question_text || 'Question prompt...' }}</h4>
                                    <div class="sim-options-list">
                                        <button
                                            v-for="(opt, oIdx) in q.options"
                                            :key="oIdx"
                                            type="button"
                                            class="sim-opt-btn"
                                            :class="{
                                                'is-picked': simAnswers[qIdx] === oIdx,
                                                'is-correct': simChecked && opt.is_correct,
                                                'is-wrong': simChecked && simAnswers[qIdx] === oIdx && !opt.is_correct,
                                            }"
                                            @click="simAnswers[qIdx] = oIdx"
                                        >
                                            <strong>{{ optionLetters[oIdx] }}.</strong>
                                            <span>{{ opt.option_text || `Option ${optionLetters[oIdx]}` }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Simulator for Color the Conflict -->
                            <div v-else-if="activeFormatId === 'color-the-conflict'" class="sim-items-stack">
                                <div
                                    v-for="(card, cIdx) in studioState.cards"
                                    :key="cIdx"
                                    class="sim-question-card"
                                >
                                    <span class="sim-item-badge">Flashcard {{ cIdx + 1 }} ({{ card.points }} pts)</span>
                                    <h4>"{{ card.excerpt || 'Excerpt text...' }}"</h4>
                                    <div class="sim-options-list">
                                        <button
                                            v-for="cat in conflictCategories"
                                            :key="cat.type"
                                            type="button"
                                            class="sim-opt-btn"
                                            :class="{
                                                'is-picked': simAnswers[cIdx] === cat.type,
                                                'is-correct': simChecked && card.conflict_type === cat.type,
                                            }"
                                            @click="simAnswers[cIdx] = cat.type"
                                        >
                                            <span>{{ cat.icon }} {{ cat.label }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Simulator for The Conflict Map -->
                            <div v-else-if="activeFormatId === 'the-conflict-map'" class="sim-items-stack">
                                <div
                                    v-for="(evt, eIdx) in studioState.events"
                                    :key="eIdx"
                                    class="sim-question-card"
                                >
                                    <span class="sim-item-badge">{{ evt.card_letter }} ({{ evt.points }} pts)</span>
                                    <h4>{{ evt.event_text || 'Event description...' }}</h4>
                                    <div class="sim-options-list">
                                        <button
                                            v-for="(z, zIdx) in studioState.zones"
                                            :key="zIdx"
                                            type="button"
                                            class="sim-opt-btn"
                                            :class="{
                                                'is-picked': simAnswers[eIdx] === z.conflict_type,
                                                'is-correct': simChecked && evt.target_conflict_type === z.conflict_type,
                                            }"
                                            @click="simAnswers[eIdx] = z.conflict_type"
                                        >
                                            <span>🗺️ Drop in: {{ z.label }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Simulator for Matching Type -->
                            <div v-else-if="activeFormatId === 'matching-type'" class="sim-items-stack">
                                <div
                                    v-for="(itm, iIdx) in studioState.matching_items"
                                    :key="iIdx"
                                    class="sim-question-card"
                                >
                                    <span class="sim-item-badge">Column A #{{ iIdx + 1 }} ({{ itm.points }} pts)</span>
                                    <h4>{{ itm.description || 'Column A description...' }}</h4>
                                    <div class="sim-options-list">
                                        <button
                                            v-for="ch in studioState.choices"
                                            :key="ch.choice_letter"
                                            type="button"
                                            class="sim-opt-btn"
                                            :class="{
                                                'is-picked': simAnswers[iIdx] === ch.choice_letter,
                                                'is-correct':
                                                    simChecked && itm.correct_choice_letter === ch.choice_letter,
                                            }"
                                            @click="simAnswers[iIdx] = ch.choice_letter"
                                        >
                                            <strong>{{ ch.choice_letter }}:</strong>
                                            <span>{{ ch.choice_text }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Simulator for Evaluation -->
                            <div v-else-if="activeFormatId === 'evaluation'" class="sim-items-stack">
                                <div
                                    v-for="(itm, iIdx) in studioState.evaluation_items"
                                    :key="iIdx"
                                    class="sim-question-card"
                                >
                                    <span class="sim-item-badge">{{ itm.section_title || `Section ${iIdx + 1}` }}</span>
                                    <h4>{{ itm.question_prompt || 'Evaluation prompt...' }}</h4>
                                    <div class="sim-options-list">
                                        <button
                                            v-for="(opt, oIdx) in itm.options"
                                            :key="oIdx"
                                            type="button"
                                            class="sim-opt-btn"
                                            :class="{ 'is-picked': simAnswers[iIdx] === oIdx }"
                                            @click="simAnswers[iIdx] = oIdx"
                                        >
                                            <span>{{ opt.option_text || `Option ${oIdx + 1}` }}</span>
                                        </button>
                                    </div>
                                    <p class="sim-template-preview">
                                        <strong>Step 2 Template:</strong> {{ itm.sentence_template }}
                                    </p>
                                </div>
                            </div>

                            <!-- Simulator for Reflection -->
                            <div v-else class="sim-items-stack">
                                <div
                                    v-for="(p, pIdx) in studioState.prompts"
                                    :key="pIdx"
                                    class="sim-question-card"
                                >
                                    <span class="sim-item-badge">
                                        Prompt {{ pIdx + 1 }} • Max {{ p.max_words }} words ({{ p.points }} pts)
                                    </span>
                                    <h4>{{ p.prompt_text || 'Reflection prompt...' }}</h4>
                                    <textarea
                                        v-model="simTextInputs[pIdx]"
                                        rows="3"
                                        class="studio-textarea"
                                        :placeholder="p.starter_sentence || 'Write your reflection response...'"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="sim-modal-footer">
                            <button type="button" class="studio-action-btn btn-simulator" @click="checkSimulatorAnswers">
                                ✓ Check Answers in Simulator
                            </button>
                            <button
                                type="button"
                                class="studio-action-btn btn-publish"
                                @click="
                                    showSimulatorModal = false;
                                    publishQuizFromStudio();
                                "
                            >
                                🚀 Looks Great — Publish Quiz
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.quiz-studio-shell {
    --font-display: 'Fredoka', sans-serif;
    --font-body: 'Nunito', sans-serif;
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #F1F5F9;
    color: #0F172A;
    overflow: hidden;
    font-family: var(--font-body);
}

/* ==========================================================================
   1. TOP STUDIO COMMAND BAR
   ========================================================================== */
.studio-top-command-bar {
    height: 64px;
    background: #0F172A;
    color: #FFFFFF;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    border-bottom: 2px solid #1E293B;
    flex-shrink: 0;
    z-index: 30;
}

.command-left,
.command-right {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-shrink: 0;
}

.studio-back-btn {
    padding: 0.42rem 0.8rem;
    border-radius: 10px;
    border: 1.5px solid #334155;
    background: #1E293B;
    color: #F8FAFC;
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.studio-back-btn:hover {
    background: #334155;
}

.studio-module-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.32rem 0.68rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    font-size: 0.76rem;
    font-weight: 800;
    color: #CBD5E1;
}

.module-name-trunc {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.active-format-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.32rem 0.75rem;
    border-radius: 999px;
    border: 2px solid transparent;
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
}

.command-center {
    flex: 1 1 260px;
    max-width: 460px;
}

.studio-title-input {
    width: 100%;
    padding: 0.48rem 0.85rem;
    border-radius: 12px;
    border: 2px solid #334155;
    background: #1E293B;
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 700;
}

.studio-title-input:focus {
    outline: none;
    border-color: #38BDF8;
    background: #0F172A;
}

.studio-title-input.has-error {
    border-color: #EF4444;
}

.undo-redo-group {
    display: inline-flex;
    background: #1E293B;
    border-radius: 10px;
    border: 1.5px solid #334155;
    overflow: hidden;
}

.studio-icon-btn {
    width: 34px;
    height: 34px;
    border: none;
    background: transparent;
    color: #F8FAFC;
    font-size: 1rem;
    font-weight: 900;
    cursor: pointer;
}

.studio-icon-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.studio-stats-pills {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.stat-pill {
    padding: 0.32rem 0.65rem;
    border-radius: 999px;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
}

.pill-pts {
    background: #1E293B;
    color: #38BDF8;
    border: 1.5px solid #0284C7;
}

.pill-xp {
    background: #451A03;
    color: #FDE047;
    border: 1.5px solid #D97706;
}

.studio-action-btn {
    padding: 0.46rem 0.9rem;
    border-radius: 12px;
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
    border: 2px solid transparent;
    transition: transform 0.14s ease;
}

.studio-action-btn:hover {
    transform: translateY(-1px);
}

.btn-template {
    background: #1E293B;
    border-color: #475569;
    color: #E2E8F0;
}

.btn-simulator {
    background: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    color: #FFFFFF;
}

.btn-publish {
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    color: #FFFFFF;
}

.studio-error-banner {
    background: #FEF2F2;
    border-bottom: 2px solid #FCA5A5;
    color: #991B1B;
    padding: 0.45rem 1rem;
    font-size: 0.78rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.error-list-inline {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.error-chip {
    background: #FEE2E2;
    padding: 0.12rem 0.55rem;
    border-radius: 999px;
    font-weight: 800;
}

.studio-toast-pill {
    position: fixed;
    top: 76px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 90;
    background: #0F172A;
    color: #FDE047;
    border: 2px solid #F59E0B;
    padding: 0.5rem 1.1rem;
    border-radius: 999px;
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
}

/* ==========================================================================
   2. MAIN 3-PANE WORKSPACE BODY
   ========================================================================== */
.studio-workspace-body {
    flex: 1;
    min-height: 0;
    display: flex;
    overflow: hidden;
}

/* PANE A: LEFT CANVA RAIL + TOOL SHELF */
.studio-left-dock {
    display: flex;
    height: 100%;
    flex-shrink: 0;
}

.canva-icon-rail {
    width: 74px;
    background: #0F172A;
    border-right: 1.5px solid #1E293B;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.65rem 0.35rem;
    gap: 0.4rem;
}

.rail-tab-btn {
    width: 100%;
    padding: 0.55rem 0.2rem;
    border-radius: 12px;
    border: none;
    background: transparent;
    color: #94A3B8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.rail-tab-btn:hover {
    color: #FFFFFF;
    background: rgba(255, 255, 255, 0.06);
}

.rail-tab-btn.is-active {
    background: #1E293B;
    color: #38BDF8;
    box-shadow: inset 3px 0 0 #38BDF8;
}

.rail-icon {
    font-size: 1.2rem;
}

.rail-lbl {
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 700;
}

.canva-tool-shelf {
    width: 310px;
    background: #FFFFFF;
    border-right: 2px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.shelf-header {
    padding: 0.85rem 1rem;
    border-bottom: 1.5px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.shelf-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.98rem;
    font-weight: 800;
    color: #0F172A;
}

.shelf-collapse-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1.5px solid #CBD5E1;
    background: #F8FAFC;
    cursor: pointer;
    font-size: 0.72rem;
}

.shelf-scroll-content {
    flex: 1;
    overflow-y: auto;
    padding: 0.9rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.shelf-helper-copy {
    margin: 0;
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748B;
    line-height: 1.4;
}

.formats-cards-stack {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.format-selector-card {
    width: 100%;
    text-align: left;
    padding: 0.75rem 0.85rem;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    background: #F8FAFC;
    cursor: pointer;
    transition: all 0.16s ease;
}

.format-selector-card:hover {
    border-color: var(--fmt-accent);
    background: var(--fmt-bg);
}

.format-selector-card.is-selected {
    border-color: var(--fmt-accent);
    background: var(--fmt-bg);
    box-shadow: 0 4px 0 var(--fmt-accent);
}

.fmt-card-top {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.fmt-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.fmt-title-col {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.fmt-title-col strong {
    font-family: var(--font-display);
    font-size: 0.86rem;
    color: #0F172A;
}

.fmt-title-col span {
    font-size: 0.68rem;
    font-weight: 800;
    color: #64748B;
}

.fmt-active-dot {
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    background: var(--fmt-accent);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
}

.fmt-card-desc {
    margin: 0.4rem 0 0;
    font-size: 0.73rem;
    font-weight: 700;
    color: #475569;
    line-height: 1.35;
}

/* PANE B: CENTER STAGE CANVAS */
.studio-center-canvas-stage {
    flex: 1;
    min-width: 0;
    height: 100%;
    overflow-y: auto;
    padding: 1.35rem;
    background:
        radial-gradient(#CBD5E1 1.2px, transparent 1.2px) 0 0 / 22px 22px,
        #F1F5F9;
}

.canvas-artboard-sheet {
    max-width: 920px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.artboard-context-banner {
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 2.5px solid #F59E0B;
    border-radius: 22px;
    padding: 1rem 1.2rem;
    box-shadow: 0 6px 18px rgba(245, 158, 11, 0.12);
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.context-banner-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.context-head-left {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.context-pill-badge {
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
    background: #D97706;
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
}

.context-sub-hint {
    font-size: 0.75rem;
    font-weight: 800;
    color: #92400E;
}

.context-head-actions {
    display: flex;
    gap: 0.4rem;
}

.context-scenarios-grid {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.context-scenario-card {
    background: rgba(255, 255, 255, 0.85);
    border: 1.5px solid #FCD34D;
    border-radius: 14px;
    padding: 0.65rem;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.context-hint-row {
    display: flex;
    gap: 0.45rem;
}

/* Active Slide Item Card on Artboard */
.artboard-active-item-card {
    background: #FFFFFF;
    border: 3px solid #E2E8F0;
    border-radius: 26px;
    padding: 1.4rem 1.55rem;
    box-shadow:
        0 10px 0 #CBD5E1,
        0 20px 40px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.item-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.65rem;
    padding-bottom: 0.85rem;
    border-bottom: 2px dashed #E2E8F0;
}

.item-header-left,
.item-header-right {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.item-number-badge {
    padding: 0.26rem 0.75rem;
    border-radius: 999px;
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
}

.item-format-sub {
    font-family: var(--font-display);
    font-size: 0.86rem;
    font-weight: 700;
    color: #475569;
}

.points-inline-box {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #F8FAFC;
    border: 1.5px solid #CBD5E1;
    padding: 0.22rem 0.55rem;
    border-radius: 10px;
    font-size: 0.76rem;
    font-weight: 800;
}

.points-number-input {
    width: 58px;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    padding: 0.15rem 0.35rem;
    font-weight: 800;
}

/* Engine Common Form Controls */
.engine-body {
    display: flex;
    flex-direction: column;
    gap: 1.05rem;
}

.engine-row-split {
    display: flex;
    gap: 0.85rem;
    flex-wrap: wrap;
}

.field-col-grow {
    flex: 1 1 380px;
}

.field-col-side {
    flex: 0 0 230px;
}

.engine-row-two-col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 0.85rem;
}

.engine-row-three-col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.field-label {
    display: block;
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.35rem;
}

.studio-input,
.studio-textarea,
.studio-select {
    width: 100%;
    padding: 0.58rem 0.8rem;
    border-radius: 12px;
    border: 2px solid #CBD5E1;
    background: #F8FAFC;
    color: #0F172A;
    font-family: var(--font-body);
    font-size: 0.88rem;
    font-weight: 700;
}

.studio-input:focus,
.studio-textarea:focus,
.studio-select:focus {
    outline: none;
    border-color: #4F46E5;
    background: #FFFFFF;
}

.large-prompt-input {
    font-size: 1rem;
    font-weight: 800;
}

/* Multiple Choice Options Grid */
.mc-options-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.mc-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.75rem;
}

.mc-option-card {
    background: #F8FAFC;
    border: 2.5px solid #E2E8F0;
    border-radius: 16px;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.mc-option-card.is-correct-choice {
    background: #ECFDF5;
    border-color: #10B981;
    box-shadow: 0 4px 0 #10B981;
}

.mc-opt-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mc-letter-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.24rem 0.65rem;
    border-radius: 999px;
    border: 1.5px solid #CBD5E1;
    background: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
}

.mc-letter-toggle.is-correct {
    background: #10B981;
    border-color: #059669;
    color: #FFFFFF;
}

.opt-feedback-input {
    font-size: 0.78rem;
}

/* Color the Conflict Swatches */
.conflict-swatches-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.conflict-color-swatch {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.8rem 0.9rem;
    border-radius: 16px;
    border: 2.5px solid #E2E8F0;
    background: #FFFFFF;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s ease;
}

.conflict-color-swatch.is-selected {
    background: var(--swatch-bg);
    border-color: var(--swatch-color);
    box-shadow: 0 4px 0 var(--swatch-border);
}

.swatch-icon {
    font-size: 1.4rem;
}

.swatch-copy {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.swatch-copy strong {
    font-family: var(--font-display);
    font-size: 0.84rem;
    color: #0F172A;
}

.swatch-copy small {
    font-size: 0.7rem;
    color: #475569;
}

.swatch-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--swatch-color);
    color: #FFFFFF;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
}

/* Conflict Map Drop Zones Strip */
.map-zones-manager-box {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 0.9rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.map-zones-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 0.65rem;
}

.map-zone-pill-card {
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 14px;
    padding: 0.65rem;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.map-zone-pill-card.is-target-zone {
    border-color: #2563EB;
    background: #EFF6FF;
    box-shadow: 0 3px 0 #2563EB;
}

.zone-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
}

.studio-select-mini {
    flex: 1;
    padding: 0.3rem 0.45rem;
    border-radius: 8px;
    border: 1.5px solid #CBD5E1;
    font-size: 0.75rem;
    font-weight: 800;
}

.assign-zone-btn {
    padding: 0.35rem 0.6rem;
    border-radius: 8px;
    border: 1.5px solid #93C5FD;
    background: #FFFFFF;
    color: #1D4ED8;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

.assign-zone-btn.is-assigned {
    background: #2563EB;
    border-color: #1E40AF;
    color: #FFFFFF;
}

/* Matching Type Two-Column Stage */
.matching-two-col-stage {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.matching-col-a-editor,
.matching-col-b-pool {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 0.9rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.col-b-choices-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.col-b-choice-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem;
    border-radius: 12px;
    border: 1.5px solid transparent;
}

.col-b-choice-row.is-linked {
    background: #F5F3FF;
    border-color: #8B5CF6;
}

.col-b-letter-pill {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    border: 2px solid #7C3AED;
    background: #EDE9FE;
    color: #5B21B6;
    font-family: var(--font-display);
    font-weight: 800;
    cursor: pointer;
    flex-shrink: 0;
}

/* Evaluation Word Helpers & Steps */
.word-helpers-bar,
.eval-step-box {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 0.9rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.word-helpers-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.word-helper-add-inline {
    display: flex;
    gap: 0.4rem;
}

.word-helper-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    background: #FFF1F2;
    border: 1.5px solid #FDA4AF;
    color: #9F1239;
    font-family: var(--font-display);
    font-size: 0.75rem;
    font-weight: 800;
}

.word-helper-chip button {
    border: none;
    background: transparent;
    color: #E11D48;
    font-weight: 900;
    cursor: pointer;
}

.token-insert-btns {
    display: flex;
    gap: 0.4rem;
}

/* PANE C: RIGHT SLIDE / ITEM NAVIGATOR DOCK */
.studio-right-navigator {
    width: 270px;
    background: #FFFFFF;
    border-left: 2px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    height: 100%;
    flex-shrink: 0;
}

.navigator-header {
    padding: 0.85rem 0.9rem;
    border-bottom: 1.5px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
}

.navigator-kicker {
    display: block;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 800;
    color: #6366F1;
    letter-spacing: 0.08em;
}

.navigator-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.9rem;
    color: #0F172A;
}

.nav-add-slide-btn {
    padding: 0.38rem 0.68rem;
    border-radius: 10px;
    border: none;
    background: #4F46E5;
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

.navigator-slides-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.navigator-slide-card {
    padding: 0.68rem 0.75rem;
    border-radius: 14px;
    border: 2px solid #E2E8F0;
    background: #F8FAFC;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    transition: all 0.14s ease;
}

.navigator-slide-card.is-active-slide {
    background: #EEF2FF;
    border-color: #4F46E5;
    box-shadow: 0 3px 0 #4F46E5;
}

.slide-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.slide-index-badge {
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: #E2E8F0;
    color: #334155;
}

.navigator-slide-card.is-active-slide .slide-index-badge {
    background: #4F46E5;
    color: #FFFFFF;
}

.slide-pts-badge {
    font-size: 0.68rem;
    font-weight: 800;
    color: #D97706;
}

.slide-summary-text {
    margin: 0;
    font-size: 0.78rem;
    font-weight: 800;
    color: #1E293B;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.slide-card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.25rem;
}

.slide-ctrl-btn {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    font-size: 0.7rem;
    font-weight: 800;
    cursor: pointer;
}

.slide-ctrl-btn.is-danger {
    color: #DC2626;
}

/* Shared Mini Buttons & Chips */
.preset-chips-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.38rem;
    margin-bottom: 0.4rem;
}

.preset-chip {
    padding: 0.26rem 0.62rem;
    border-radius: 999px;
    border: 1.5px solid #CBD5E1;
    background: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
}

.preset-chip.is-active {
    background: #4F46E5;
    border-color: #312E81;
    color: #FFFFFF;
}

.mini-add-choice-btn,
.mini-outline-btn,
.mini-load-btn {
    padding: 0.3rem 0.65rem;
    border-radius: 9px;
    border: 1.5px solid #CBD5E1;
    background: #FFFFFF;
    color: #1E293B;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

.mini-danger-btn,
.mc-remove-opt-btn {
    padding: 0.25rem 0.55rem;
    border-radius: 8px;
    border: 1.5px solid #FCA5A5;
    background: #FEF2F2;
    color: #DC2626;
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

.shelf-add-btn,
.shelf-load-template-btn {
    width: 100%;
    padding: 0.55rem;
    border-radius: 12px;
    border: 2px dashed #818CF8;
    background: #EEF2FF;
    color: #4338CA;
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    cursor: pointer;
    margin-top: 0.35rem;
}

.template-preset-card,
.shelf-context-box,
.saved-quiz-card {
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 14px;
    padding: 0.7rem;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.saved-quiz-top,
.context-box-head,
.saved-quiz-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
}

.saved-type-tag {
    font-size: 0.64rem;
    font-weight: 800;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: #E0E7FF;
    color: #3730A3;
}

.saved-xp-tag {
    font-size: 0.68rem;
    font-weight: 800;
    color: #D97706;
}

.saved-quiz-title {
    font-size: 0.82rem;
    color: #0F172A;
}

/* ==========================================================================
   3. STUDENT SIMULATOR MODAL
   ========================================================================== */
.simulator-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 95;
    background: rgba(15, 23, 42, 0.68);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

.simulator-modal-card {
    width: 100%;
    max-width: 720px;
    max-height: 88vh;
    background: #FFFFFF;
    border-radius: 28px;
    border: 4px solid #38BDF8;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
}

.sim-modal-header {
    padding: 1rem 1.35rem;
    background: #0F172A;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.sim-kicker {
    display: block;
    font-family: var(--font-display);
    font-size: 0.66rem;
    color: #38BDF8;
}

.sim-title {
    margin: 0.15rem 0 0;
    font-family: var(--font-display);
    font-size: 1.15rem;
}

.sim-close-btn {
    padding: 0.4rem 0.75rem;
    border-radius: 10px;
    border: 1.5px solid #475569;
    background: #1E293B;
    color: #FFFFFF;
    font-weight: 800;
    cursor: pointer;
}

.sim-modal-scroll-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.sim-context-box {
    background: #FFFBEB;
    border: 2px solid #F59E0B;
    border-radius: 16px;
    padding: 0.85rem 1rem;
}

.sim-items-stack {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.sim-question-card {
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 1rem;
}

.sim-item-badge {
    display: inline-block;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    background: #E0E7FF;
    color: #3730A3;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    margin-bottom: 0.35rem;
}

.sim-options-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    margin-top: 0.65rem;
}

.sim-opt-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 0.85rem;
    border-radius: 12px;
    border: 2px solid #CBD5E1;
    background: #FFFFFF;
    text-align: left;
    font-weight: 800;
    cursor: pointer;
}

.sim-opt-btn.is-picked {
    border-color: #4F46E5;
    background: #EEF2FF;
}

.sim-opt-btn.is-correct {
    border-color: #10B981;
    background: #ECFDF5;
    color: #065F46;
}

.sim-opt-btn.is-wrong {
    border-color: #EF4444;
    background: #FEF2F2;
    color: #991B1B;
}

.sim-modal-footer {
    padding: 0.9rem 1.35rem;
    border-top: 1.5px solid #E2E8F0;
    display: flex;
    justify-content: flex-end;
    gap: 0.65rem;
}
</style>

