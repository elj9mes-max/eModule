<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useSound } from '@/Services/soundManager';

const {
    playPop,
    playSparkle,
    playSuccess,
    playCorrect,
    playIncorrect,
    playClick,
    playBonus,
} = useSound();

const props = defineProps({
    user: {
        type: Object,
        default: () => ({ name: 'Alex Explorer' }),
    },
    module: {
        type: Object,
        default: null,
    },
    quiz: {
        type: Object,
        default: null,
    },
    siblingQuizzes: {
        type: Array,
        default: () => [],
    },
    existingAttempt: {
        type: Object,
        default: null,
    },
});

/* ==========================================================================
   6 CONFLICT TYPE DEFINITIONS & COLOR SWATCHES
   ========================================================================== */
const conflictTypes = [
    {
        value: 'character_vs_character',
        label: 'Character vs. Character',
        short: 'vs. Character',
        swatch: '#EF4444',
        bg: '#FEF2F2',
        border: '#FCA5A5',
        text: '#991B1B',
    },
    {
        value: 'character_vs_self',
        label: 'Character vs. Self',
        short: 'vs. Self',
        swatch: '#8B5CF6',
        bg: '#F5F3FF',
        border: '#C4B5FD',
        text: '#5B21B6',
    },
    {
        value: 'character_vs_nature',
        label: 'Character vs. Nature',
        short: 'vs. Nature',
        swatch: '#10B981',
        bg: '#ECFDF5',
        border: '#6EE7B7',
        text: '#065F46',
    },
    {
        value: 'character_vs_society',
        label: 'Character vs. Society',
        short: 'vs. Society',
        swatch: '#F59E0B',
        bg: '#FFFBEB',
        border: '#FCD34D',
        text: '#92400E',
    },
    {
        value: 'character_vs_technology',
        label: 'Character vs. Technology',
        short: 'vs. Tech',
        swatch: '#0EA5E9',
        bg: '#F0F9FF',
        border: '#7DD3FC',
        text: '#075985',
    },
    {
        value: 'character_vs_supernatural',
        label: 'Character vs. Supernatural',
        short: 'vs. Supernatural',
        swatch: '#EC4899',
        bg: '#FDF2F8',
        border: '#F9A8D4',
        text: '#9D174D',
    },
];

const getConflictMeta = (value) => {
    return conflictTypes.find((c) => c.value === value) || conflictTypes[0];
};

const zoneThemeColors = {
    blue: { bg: '#EFF6FF', border: '#60A5FA', header: '#2563EB', text: '#1E3A8A' },
    emerald: { bg: '#ECFDF5', border: '#34D399', header: '#059669', text: '#064E3B' },
    amber: { bg: '#FFFBEB', border: '#FBBF24', header: '#D97706', text: '#78350F' },
    purple: { bg: '#F5F3FF', border: '#A78BFA', header: '#7C3AED', text: '#4C1D95' },
    rose: { bg: '#FFF1F2', border: '#FB7185', header: '#E11D48', text: '#881337' },
    cyan: { bg: '#ECFEFF', border: '#22D3EE', header: '#0891B2', text: '#164E63' },
};

const getZoneTheme = (themeKey) => {
    return zoneThemeColors[themeKey] || zoneThemeColors.blue;
};

/* ==========================================================================
   DEMO QUIZZES FOR ALL 6 FORMATS (FALLBACK & FORMAT SWITCHER PREVIEW)
   ========================================================================== */
const fallbackModule = {
    id: 1,
    title: 'Whispering Woods: Reading Quest',
    slug: 'whispering-woods',
    grade_level: 'Grade 4 • Interactive Quiz Arena',
    theme: 'forest',
    xp_bonus: 300,
    boss_name: 'Elder Oak Guardian',
};

const demoQuizzesByFormat = {
    multiple_choice: {
        id: 901,
        title: 'Forest Rune Decoder Challenge',
        slug: 'forest-rune-decoder-challenge',
        type: 'multiple_choice',
        description: 'Read the passage and pick the best answer for each question.',
        reading_passage:
            'Mira held her brass lantern close to the mossy stone. The inscription was "luminous," casting a bright golden glow that lit up the dark forest path around her boots.',
        contexts: [
            {
                id: 1,
                scenario:
                    'Mira held her brass lantern close to the mossy stone. The inscription was "luminous," casting a bright golden glow that lit up the dark forest path around her boots.',
                hint: 'Look at the words right after "luminous" for a context clue!',
            },
        ],
        xp_reward: 150,
        passing_percentage: 75,
        time_limit_seconds: null,
        questions: [
            {
                id: 101,
                question_text: 'Based on the story passage, what does the word "luminous" most nearly mean?',
                selection_type: 'single',
                passage_context: 'The inscription was "luminous," casting a bright golden glow...',
                hint: 'Notice what the inscription does to the dark forest path.',
                explanation: '"Casting a bright golden glow" shows that luminous means shining or glowing with light.',
                points: 10,
                options: [
                    {
                        id: 1,
                        option_text: 'Shining or glowing with bright light',
                        is_correct: true,
                        feedback: 'Spot on! The golden glow lights up the dark path.',
                    },
                    {
                        id: 2,
                        option_text: 'Heavy and covered in thick green moss',
                        is_correct: false,
                        feedback: 'The stone is mossy, but the inscription itself is glowing.',
                    },
                    {
                        id: 3,
                        option_text: 'Silent and completely invisible at night',
                        is_correct: false,
                        feedback: 'Look closely at how it lit up the path around her boots.',
                    },
                ],
            },
            {
                id: 102,
                question_text: 'Which TWO details from the passage show that Mira is exploring in a dark place?',
                selection_type: 'multiple',
                passage_context: '',
                hint: 'Select two choices that mention light tools or darkness.',
                explanation: 'Holding a lantern and lighting up a dark forest path both show it is dark.',
                points: 15,
                options: [
                    { id: 4, option_text: 'She held her brass lantern close', is_correct: true, feedback: '' },
                    { id: 5, option_text: 'It lit up the dark forest path', is_correct: true, feedback: '' },
                    { id: 6, option_text: 'The stone had green moss on it', is_correct: false, feedback: '' },
                ],
            },
        ],
    },
    color_the_conflict: {
        id: 902,
        title: 'Color the Conflict: Whispering Pines',
        slug: 'color-the-conflict-whispering-pines',
        type: 'color_the_conflict',
        description: 'Read each story excerpt and paint it with the matching Literary Conflict color swatch!',
        reading_passage:
            'Every great story has a conflict—a struggle that tests the hero. Identify whether the conflict is against another character, oneself, nature, or society.',
        contexts: [
            {
                id: 2,
                scenario:
                    'In the Whispering Woods, explorers face external storms, disagreements with rivals, and tough choices inside their own hearts.',
                hint: 'Ask yourself: Who or what is opposing the main character in this moment?',
            },
        ],
        xp_reward: 160,
        passing_percentage: 70,
        cards: [
            {
                id: 201,
                excerpt:
                    'A sudden mountain blizzard swept across the rope bridge, whipping icy wind against Leo as he tried to keep his footing on the frozen planks.',
                conflict_type: 'character_vs_nature',
                hint: 'Is Leo struggling against a person, his own feelings, or a force of weather?',
                explanation: 'Leo is battling a mountain blizzard and icy wind—a classic Character vs. Nature conflict!',
                feedback_incorrect: 'Look at the opponent: a mountain blizzard and icy wind are forces of nature.',
                points: 10,
            },
            {
                id: 202,
                excerpt:
                    'Mira stared at the two forest trails. Part of her wanted to turn back to safety, while another voice inside urged her to be brave and help her friends.',
                conflict_type: 'character_vs_self',
                hint: 'Where is this struggle happening—outside or inside Mira’s mind?',
                explanation: 'Mira is wrestling with her own fear and courage inside her heart (Character vs. Self).',
                feedback_incorrect: 'Notice that Mira is debating with her own feelings and doubts.',
                points: 10,
            },
            {
                id: 203,
                excerpt:
                    'Guard Captain Vance blocked the iron gate and crossed his arms. "No apprentice enters the library tower while I am on watch!" he barked at Tomas.',
                conflict_type: 'character_vs_character',
                hint: 'Two named individuals are directly opposing each other at the gate.',
                explanation: 'Tomas and Captain Vance are clashing directly—Character vs. Character!',
                feedback_incorrect: 'Captain Vance is another character directly blocking Tomas.',
                points: 10,
            },
        ],
    },
    the_conflict_map: {
        id: 903,
        title: 'The Conflict Map: Sort the Story Events',
        slug: 'the-conflict-map-sort-events',
        type: 'the_conflict_map',
        description: 'Place each story event card into the correct Conflict Zone bucket on the map!',
        reading_passage:
            'Sort each event from the chapter into the Conflict Zone bucket where it belongs.',
        contexts: [
            {
                id: 3,
                scenario:
                    'Drag an event card into a Conflict Bucket—or simply tap the matching zone button on any card!',
                hint: 'Match each event letter (A, B, C) to its conflict category.',
            },
        ],
        xp_reward: 180,
        passing_percentage: 70,
        zones: [
            {
                id: 301,
                conflict_type: 'character_vs_character',
                label: 'Character vs. Character',
                subtitle: 'Clashes between two characters',
                color_theme: 'rose',
            },
            {
                id: 302,
                conflict_type: 'character_vs_nature',
                label: 'Character vs. Nature',
                subtitle: 'Struggles with weather, terrain, or wild animals',
                color_theme: 'emerald',
            },
            {
                id: 303,
                conflict_type: 'character_vs_self',
                label: 'Character vs. Self',
                subtitle: 'Internal doubts, fear, or moral choices',
                color_theme: 'purple',
            },
        ],
        events: [
            {
                id: 311,
                card_letter: 'A',
                event_text: 'A flash flood washes away the wooden canoe while Sami paddles across the river.',
                target_conflict_type: 'character_vs_nature',
                hint: 'A flash flood is a natural force.',
                explanation: 'The river flood is a force of nature challenging Sami.',
                points: 10,
            },
            {
                id: 312,
                card_letter: 'B',
                event_text: 'Elena argues with her rival Marco over who should lead the rescue team.',
                target_conflict_type: 'character_vs_character',
                hint: 'Two characters (Elena and Marco) are arguing.',
                explanation: 'Elena and Marco are two characters in direct disagreement.',
                points: 10,
            },
            {
                id: 313,
                card_letter: 'C',
                event_text: 'Hana wonders if she is honest enough to admit she lost the village map.',
                target_conflict_type: 'character_vs_self',
                hint: 'Hana is struggling with her own conscience.',
                explanation: 'Deciding whether to confess a mistake is an internal Character vs. Self conflict.',
                points: 10,
            },
        ],
    },
    matching_type: {
        id: 904,
        title: 'Matching Challenge: Story Vocabulary & Clues',
        slug: 'matching-challenge-story-vocabulary',
        type: 'matching_type',
        description: 'Match each numbered clue in Column A with the correct lettered answer in Column B!',
        reading_passage:
            'Connect each reading concept or vocabulary clue on the left with its matching definition on the right.',
        contexts: [
            {
                id: 4,
                scenario:
                    'Tap a numbered item in Column A, then tap its matching letter in Column B (or click a letter badge directly on the item).',
                hint: 'Each letter in Column B matches one numbered description.',
            },
        ],
        xp_reward: 160,
        passing_percentage: 75,
        choices: [
            { id: 401, choice_letter: 'A', choice_text: 'Luminous (glowing brightly in the dark)' },
            { id: 402, choice_letter: 'B', choice_text: 'Protagonist (the main hero of the story)' },
            { id: 403, choice_letter: 'C', choice_text: 'Context Clues (nearby words that hint at meaning)' },
            { id: 404, choice_letter: 'D', choice_text: 'Setting (where and when the story takes place)' },
        ],
        matching_items: [
            {
                id: 411,
                item_number: 1,
                description: 'Words or phrases in a sentence that help you figure out an unfamiliar word.',
                correct_choice_letter: 'C',
                hint: 'You look around the text for these helpful hints.',
                explanation: 'Context Clues (C) are hints inside surrounding sentences.',
                points: 10,
            },
            {
                id: 412,
                item_number: 2,
                description: 'An adjective describing the bright golden light of Mira’s lantern.',
                correct_choice_letter: 'A',
                hint: 'Which choice means glowing brightly?',
                explanation: 'Luminous (A) means radiating or reflecting bright light.',
                points: 10,
            },
            {
                id: 413,
                item_number: 3,
                description: 'The time and place of the adventure, such as the Whispering Woods at twilight.',
                correct_choice_letter: 'D',
                hint: 'Think of the stage where the story happens.',
                explanation: 'Setting (D) tells us where and when a story occurs.',
                points: 10,
            },
        ],
    },
    evaluation: {
        id: 905,
        title: 'Evaluation Arena: Judge the Character’s Choice',
        slug: 'evaluation-arena-character-choice',
        type: 'evaluation',
        description: 'Step 1: Pick your stance. Step 2: Complete the reasoning sentence using Word Helpers!',
        reading_passage:
            'When the bridge rope snapped, Leo shared his last lantern with the younger travelers instead of rushing ahead alone.',
        contexts: [
            {
                id: 5,
                scenario:
                    'When the canyon wind blew out the torches, Leo slowed down and shared his brass lantern so the younger travelers could cross safely together.',
                hint: 'Use the Word Helpers bank to complete your Step 2 explanation!',
            },
        ],
        xp_reward: 200,
        passing_percentage: 70,
        word_helpers: ['compassionate', 'teamwork', 'selfless', 'protected', 'courageous'],
        evaluation_items: [
            {
                id: 501,
                section_title: 'Part 1: Evaluating Leo’s Leadership',
                conflict_type: 'character_vs_nature',
                question_prompt: 'Did Leo make the right decision by slowing down to share his lantern during the storm?',
                step1_prompt: 'Step 1: Choose your judgment:',
                sentence_template: 'I think Leo made a [stance] decision because his [blank] kept the younger travelers safe.',
                hint: 'Pick the positive stance in Step 1, then fill both blanks in Step 2 (tap a Word Helper chip to insert it!).',
                points: 20,
                options: [
                    {
                        id: 511,
                        option_text: 'Yes — protecting the group showed true leadership and empathy.',
                        stance_value: 'agree',
                        is_correct: true,
                    },
                    {
                        id: 512,
                        option_text: 'No — he should have left everyone behind in the dark.',
                        stance_value: 'disagree',
                        is_correct: false,
                    },
                ],
            },
        ],
    },
    reflection: {
        id: 906,
        title: 'Story Reflection Journal: Courage & Community',
        slug: 'story-reflection-journal',
        type: 'reflection',
        description: 'Write a thoughtful reflection connecting the story’s conflict to real life.',
        reading_passage:
            'Throughout the Whispering Woods expedition, the characters overcame storms and misunderstandings by listening calmly and helping one another.',
        contexts: [
            {
                id: 6,
                scenario:
                    'Reflect on how the characters in this module solved their conflicts through calm dialogue and bayanihan (helping one another).',
                hint: 'You can click "Insert Starter Sentence" to begin your paragraph smoothly!',
            },
        ],
        xp_reward: 180,
        passing_percentage: 70,
        prompts: [
            {
                id: 601,
                prompt_text:
                    'How can calm communication help resolve a disagreement with a friend or classmate in real life? Share an example.',
                min_words: 12,
                max_words: 120,
                guiding_hint: 'Describe how listening first prevents a small misunderstanding from growing bigger.',
                starter_sentence: 'When I have a disagreement with a friend, calm communication helps us because ',
                points: 25,
            },
        ],
    },
};

/* ==========================================================================
   ACTIVE QUIZ RESOLUTION & FORMAT SWITCHING
   ========================================================================== */
const activeDemoFormat = ref(props.quiz?.type || 'multiple_choice');

const activeModule = computed(() => props.module || fallbackModule);

const activeQuiz = computed(() => {
    if (props.quiz && props.quiz.type === activeDemoFormat.value) {
        return props.quiz;
    }

    return demoQuizzesByFormat[activeDemoFormat.value] || props.quiz || demoQuizzesByFormat.multiple_choice;
});

const quizType = computed(() => activeQuiz.value?.type || 'multiple_choice');

const formatCatalog = [
    { key: 'multiple_choice', label: 'Multiple Choice', short: 'MCQ' },
    { key: 'color_the_conflict', label: 'Color the Conflict', short: 'Color' },
    { key: 'the_conflict_map', label: 'Conflict Map', short: 'Map' },
    { key: 'matching_type', label: 'Matching Type', short: 'Match' },
    { key: 'evaluation', label: 'Evaluation', short: 'Evaluate' },
    { key: 'reflection', label: 'Reflection', short: 'Reflect' },
];

const currentFormatInfo = computed(() => {
    return formatCatalog.find((f) => f.key === quizType.value) || formatCatalog[0];
});

/* ==========================================================================
   PASSAGE / CONTEXT STATE & NAVIGATION STEPPER
   ========================================================================== */
const isPassageExpanded = ref(true);
const showHintBanner = ref(false);
const viewAllMode = ref(false);
const currentItemIndex = ref(0);
const showCompletionModal = ref(false);

const activeContexts = computed(() => {
    if (activeQuiz.value?.contexts?.length > 0) {
        return activeQuiz.value.contexts;
    }
    if (activeQuiz.value?.reading_passage) {
        return [{ id: 'passage-1', scenario: activeQuiz.value.reading_passage, hint: '' }];
    }
    return [];
});

// Unified list of interactive items for step-by-step navigation
const activeItemsList = computed(() => {
    const q = activeQuiz.value;
    if (!q) {
        return [];
    }

    switch (quizType.value) {
        case 'color_the_conflict':
            return q.cards || [];
        case 'the_conflict_map':
            return q.events || [];
        case 'matching_type':
            return q.matching_items || [];
        case 'evaluation':
            return q.evaluation_items || [];
        case 'reflection':
            return q.prompts || [];
        default:
            return q.questions || [];
    }
});

const totalItemsCount = computed(() => Math.max(1, activeItemsList.value.length));

const isAttemptPendingReview = computed(() => {
    return Boolean(
        serverAttemptResult.value?.status === 'pending_review' ||
        serverAttemptResult.value?.is_pending_review ||
        props.existingAttempt?.status === 'pending_review' ||
        props.existingAttempt?.is_pending_review
    );
});

const latestTeacherFeedback = computed(() => {
    return serverAttemptResult.value?.teacher_feedback || props.existingAttempt?.teacher_feedback || null;
});

const currentItem = computed(() => {
    return activeItemsList.value[currentItemIndex.value] || activeItemsList.value[0] || null;
});

/* ==========================================================================
   STUDENT INTERACTION ANSWERS & CHECK STATE FOR ALL 6 FORMATS
   ========================================================================== */
// 1. Multiple Choice: { [questionId]: [optionId, ...] }
const mcqSelected = ref({});
const mcqChecked = ref({});

// 2. Color the Conflict: { [cardId]: 'character_vs_nature' }
const colorSelected = ref({});
const colorChecked = ref({});

// 3. Conflict Map: { [eventId]: 'character_vs_nature' }
const mapAssignments = ref({});
const mapChecked = ref(false);
const draggedEventId = ref(null);
const selectedMapEventId = ref(null);

// 4. Matching Type: { [itemId]: 'A' }
const matchingSelected = ref({});
const matchingChecked = ref(false);
const activeMatchingItemId = ref(null);

// 5. Evaluation: { [itemId]: { optionId: null, blanks: ['', ''] } }
const evaluationState = ref({});
const evaluationChecked = ref({});
const focusedBlankInfo = ref({ itemId: null, blankIdx: 0 });

// 6. Reflection: { [promptId]: 'student essay...' }
const reflectionText = ref({});
const reflectionChecked = ref({});

// Server evaluation attempt result & submit loading state
const isSubmittingQuiz = ref(false);
const serverAttemptResult = ref(props.existingAttempt || null);

// Timer (if time_limit_seconds exists)
const remainingSeconds = ref(null);
let timerInterval = null;

const resetQuizSession = () => {
    currentItemIndex.value = 0;
    showHintBanner.value = false;
    showCompletionModal.value = false;
    mcqSelected.value = {};
    mcqChecked.value = {};
    colorSelected.value = {};
    colorChecked.value = {};
    mapAssignments.value = {};
    mapChecked.value = false;
    selectedMapEventId.value = null;
    matchingSelected.value = {};
    matchingChecked.value = false;
    activeMatchingItemId.value = null;
    evaluationState.value = {};
    evaluationChecked.value = {};
    reflectionText.value = {};
    reflectionChecked.value = {};

    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    if (props.existingAttempt) {
        serverAttemptResult.value = props.existingAttempt;
        return;
    }

    serverAttemptResult.value = null;

    if (activeQuiz.value?.time_limit_seconds) {
        remainingSeconds.value = Number(activeQuiz.value.time_limit_seconds);
        timerInterval = window.setInterval(() => {
            if (remainingSeconds.value !== null && remainingSeconds.value > 0) {
                remainingSeconds.value--;
            } else if (remainingSeconds.value === 0) {
                clearInterval(timerInterval);
                finishQuizAndShowSummary();
            }
        }, 1000);
    } else {
        remainingSeconds.value = null;
    }
};

watch(
    () => [props.quiz?.id, activeDemoFormat.value],
    () => {
        resetQuizSession();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

const formattedTimer = computed(() => {
    if (remainingSeconds.value === null) {
        return null;
    }
    const mins = Math.floor(remainingSeconds.value / 60);
    const secs = remainingSeconds.value % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

/* ==========================================================================
   1. MULTIPLE CHOICE HANDLERS
   ========================================================================== */
const isMcqOptionSelected = (question, optionId) => {
    const list = mcqSelected.value[question.id] || [];
    return list.includes(optionId);
};

const toggleMcqOption = (question, optionId) => {
    playPop(1.15);
    const current = mcqSelected.value[question.id] || [];
    if (question.selection_type === 'multiple') {
        const exists = current.includes(optionId);
        mcqSelected.value = {
            ...mcqSelected.value,
            [question.id]: exists ? current.filter((id) => id !== optionId) : [...current, optionId],
        };
    } else {
        mcqSelected.value = {
            ...mcqSelected.value,
            [question.id]: [optionId],
        };
    }
    mcqChecked.value = {
        ...mcqChecked.value,
        [question.id]: null,
    };
};

const checkMcqQuestion = (question) => {
    const picked = mcqSelected.value[question.id] || [];
    if (picked.length === 0) {
        return;
    }

    const correctIds = (question.options || []).filter((o) => o.is_correct).map((o) => o.id);
    const isExactMatch =
        picked.length === correctIds.length && correctIds.every((id) => picked.includes(id));

    if (isExactMatch) {
        playCorrect();
    } else {
        playIncorrect();
    }

    mcqChecked.value = {
        ...mcqChecked.value,
        [question.id]: isExactMatch ? 'correct' : 'incorrect',
    };
};

/* ==========================================================================
   2. COLOR THE CONFLICT HANDLERS
   ========================================================================== */
const selectConflictColor = (card, conflictTypeValue) => {
    playPop(1.1);
    colorSelected.value = {
        ...colorSelected.value,
        [card.id]: conflictTypeValue,
    };
    colorChecked.value = {
        ...colorChecked.value,
        [card.id]: null,
    };
};

const checkConflictCard = (card) => {
    const picked = colorSelected.value[card.id];
    if (!picked) {
        return;
    }

    const isMatch = picked === card.conflict_type;
    if (isMatch) {
        playCorrect();
    } else {
        playIncorrect();
    }

    colorChecked.value = {
        ...colorChecked.value,
        [card.id]: isMatch ? 'correct' : 'incorrect',
    };
};

/* ==========================================================================
   3. THE CONFLICT MAP HANDLERS (DRAG-AND-DROP + TAP-TO-ASSIGN)
   ========================================================================== */
const assignEventToZone = (eventId, zoneConflictType) => {
    playPop(1.2);
    mapAssignments.value = {
        ...mapAssignments.value,
        [eventId]: zoneConflictType,
    };
    mapChecked.value = false;
    selectedMapEventId.value = null;
};

const unassignEvent = (eventId) => {
    const next = { ...mapAssignments.value };
    delete next[eventId];
    mapAssignments.value = next;
    mapChecked.value = false;
};

const handleEventDragStart = (eventId) => {
    draggedEventId.value = eventId;
};

const handleZoneDrop = (zoneConflictType) => {
    if (draggedEventId.value !== null) {
        assignEventToZone(draggedEventId.value, zoneConflictType);
        draggedEventId.value = null;
    }
};

const handleZoneTap = (zoneConflictType) => {
    if (selectedMapEventId.value !== null) {
        assignEventToZone(selectedMapEventId.value, zoneConflictType);
    }
};

const getZoneEvents = (zoneConflictType) => {
    return (activeQuiz.value?.events || []).filter(
        (ev) => mapAssignments.value[ev.id] === zoneConflictType,
    );
};

const checkConflictMap = () => {
    mapChecked.value = true;
};

/* ==========================================================================
   4. MATCHING TYPE HANDLERS
   ========================================================================== */
const assignMatchingChoice = (itemId, choiceLetter) => {
    matchingSelected.value = {
        ...matchingSelected.value,
        [itemId]: choiceLetter,
    };
    matchingChecked.value = false;
    activeMatchingItemId.value = null;
};

const handleChoiceColumnTap = (choiceLetter) => {
    if (activeMatchingItemId.value !== null) {
        assignMatchingChoice(activeMatchingItemId.value, choiceLetter);
        return;
    }

    // Otherwise assign to the first unassigned item
    const firstUnassigned = (activeQuiz.value?.matching_items || []).find(
        (item) => !matchingSelected.value[item.id],
    );
    if (firstUnassigned) {
        assignMatchingChoice(firstUnassigned.id, choiceLetter);
    }
};

const getChoiceTextByLetter = (letter) => {
    const found = (activeQuiz.value?.choices || []).find((c) => c.choice_letter === letter);
    return found ? `${found.choice_letter}. ${found.choice_text}` : letter;
};

const checkMatchingQuiz = () => {
    matchingChecked.value = true;
};

/* ==========================================================================
   5. EVALUATION HANDLERS (2-STEP STANCE + SENTENCE TEMPLATE BLANKS)
   ========================================================================== */
const parseSentenceTemplate = (item) => {
    const raw = item?.sentence_template || 'I believe that [stance] because [blank].';
    const regex = /(\[stance\]|\[blank\]|\[___\]|___)/gi;
    const segments = [];
    let lastIndex = 0;
    let blankIndex = 0;
    let match;

    while ((match = regex.exec(raw)) !== null) {
        if (match.index > lastIndex) {
            segments.push({
                type: 'text',
                content: raw.slice(lastIndex, match.index),
            });
        }

        const tokenLower = match[0].toLowerCase();
        if (tokenLower === '[stance]') {
            segments.push({
                type: 'stance',
            });
        } else {
            segments.push({
                type: 'blank',
                blankIdx: blankIndex++,
            });
        }

        lastIndex = regex.lastIndex;
    }

    if (lastIndex < raw.length) {
        segments.push({
            type: 'text',
            content: raw.slice(lastIndex),
        });
    }

    // Safety fallback: if no blank token was present, provide at least one blank
    if (blankIndex === 0) {
        segments.push({
            type: 'blank',
            blankIdx: 0,
        });
    }

    return segments;
};

const ensureEvaluationState = (item) => {
    if (!item?.id) {
        return { optionId: null, blanks: [''] };
    }
    if (!evaluationState.value[item.id]) {
        const segments = parseSentenceTemplate(item);
        const blanksCount = Math.max(1, segments.filter((s) => s.type === 'blank').length);
        evaluationState.value[item.id] = {
            optionId: null,
            blanks: Array.from({ length: blanksCount }, () => ''),
        };
    }
    return evaluationState.value[item.id];
};

const getSelectedStanceOption = (item) => {
    const state = ensureEvaluationState(item);
    if (!state.optionId || !item?.options) {
        return null;
    }
    return item.options.find((o) => o.id === state.optionId) || null;
};

const getSentenceTemplateParts = (item) => {
    return parseSentenceTemplate(item);
};

const selectEvaluationOption = (item, optionId) => {
    const state = ensureEvaluationState(item);
    evaluationState.value = {
        ...evaluationState.value,
        [item.id]: {
            ...state,
            optionId,
        },
    };
    evaluationChecked.value = {
        ...evaluationChecked.value,
        [item.id]: null,
    };
};

const updateEvaluationBlank = (item, blankIdx, val) => {
    const state = ensureEvaluationState(item);
    const nextBlanks = [...state.blanks];
    nextBlanks[blankIdx] = val;
    evaluationState.value = {
        ...evaluationState.value,
        [item.id]: {
            ...state,
            blanks: nextBlanks,
        },
    };
    evaluationChecked.value = {
        ...evaluationChecked.value,
        [item.id]: null,
    };
};

const insertWordHelperIntoBlank = (word) => {
    const targetItem =
        (activeQuiz.value?.evaluation_items || []).find((i) => i.id === focusedBlankInfo.value.itemId) ||
        currentItem.value ||
        activeQuiz.value?.evaluation_items?.[0];

    if (!targetItem) {
        return;
    }

    const state = ensureEvaluationState(targetItem);
    let targetIdx = focusedBlankInfo.value.blankIdx ?? 0;

    // Prefer first empty blank if current blank is already filled
    const emptyIdx = state.blanks.findIndex((b) => !b || !b.trim());
    if (emptyIdx !== -1) {
        targetIdx = emptyIdx;
    }

    const existing = state.blanks[targetIdx] || '';
    const updated = existing.trim() ? `${existing.trim()} ${word}` : word;
    updateEvaluationBlank(targetItem, targetIdx, updated);
};

const checkEvaluationItem = (item) => {
    const state = ensureEvaluationState(item);
    const pickedOption = (item.options || []).find((o) => o.id === state.optionId);
    const hasFilledBlanks = state.blanks.every((b) => b && b.trim().length >= 2);

    if (!pickedOption || !hasFilledBlanks) {
        evaluationChecked.value = {
            ...evaluationChecked.value,
            [item.id]: 'incomplete',
        };
        return;
    }

    evaluationChecked.value = {
        ...evaluationChecked.value,
        [item.id]: pickedOption.is_correct ? 'correct' : 'incorrect',
    };
};

/* ==========================================================================
   6. REFLECTION HANDLERS (WORD COUNTER + STARTER SENTENCE)
   ========================================================================== */
const countWords = (text) => {
    const trimmed = (text || '').trim();
    if (!trimmed) {
        return 0;
    }
    return trimmed.split(/\s+/).length;
};

const getReflectionWordCount = (promptId) => {
    return countWords(reflectionText.value[promptId] || '');
};

const insertStarterSentence = (prompt) => {
    if (!prompt.starter_sentence) {
        return;
    }
    const current = reflectionText.value[prompt.id] || '';
    if (!current.trim()) {
        reflectionText.value = {
            ...reflectionText.value,
            [prompt.id]: prompt.starter_sentence.trim() + ' ',
        };
    } else if (!current.includes(prompt.starter_sentence.trim())) {
        reflectionText.value = {
            ...reflectionText.value,
            [prompt.id]: `${prompt.starter_sentence.trim()} ${current}`,
        };
    }
};

const checkReflectionPrompt = (prompt) => {
    const words = getReflectionWordCount(prompt.id);
    const min = Number(prompt.min_words || 10);
    const max = Number(prompt.max_words || 250);

    if (words < min) {
        reflectionChecked.value = {
            ...reflectionChecked.value,
            [prompt.id]: 'too_short',
        };
        return;
    }
    if (words > max) {
        reflectionChecked.value = {
            ...reflectionChecked.value,
            [prompt.id]: 'too_long',
        };
        return;
    }

    reflectionChecked.value = {
        ...reflectionChecked.value,
        [prompt.id]: 'completed',
    };
};

/* ==========================================================================
   SCORE, PROGRESS & COMPLETION SUMMARY COMPUTATIONS
   ========================================================================== */
const answeredItemsCount = computed(() => {
    const q = activeQuiz.value;
    if (!q) {
        return 0;
    }

    switch (quizType.value) {
        case 'color_the_conflict':
            return (q.cards || []).filter((c) => Boolean(colorSelected.value[c.id])).length;
        case 'the_conflict_map':
            return (q.events || []).filter((e) => Boolean(mapAssignments.value[e.id])).length;
        case 'matching_type':
            return (q.matching_items || []).filter((m) => Boolean(matchingSelected.value[m.id])).length;
        case 'evaluation':
            return (q.evaluation_items || []).filter((item) => {
                const st = evaluationState.value[item.id];
                return st && st.optionId && st.blanks.some((b) => b.trim().length > 0);
            }).length;
        case 'reflection':
            return (q.prompts || []).filter(
                (p) => getReflectionWordCount(p.id) >= Number(p.min_words || 5),
            ).length;
        default:
            return (q.questions || []).filter((qu) => (mcqSelected.value[qu.id] || []).length > 0).length;
    }
});

const progressPercentage = computed(() => {
    if (totalItemsCount.value === 0) {
        return 0;
    }
    return Math.min(100, Math.round((answeredItemsCount.value / totalItemsCount.value) * 100));
});

const scoreSummary = computed(() => {
    const q = activeQuiz.value;
    if (!q) {
        return { earned: 0, total: 10, pct: 100, stars: 3, passed: true };
    }

    let earned = 0;
    let total = 0;

    if (quizType.value === 'multiple_choice') {
        for (const question of q.questions || []) {
            const pts = Number(question.points || 10);
            total += pts;
            const picked = mcqSelected.value[question.id] || [];
            const correctIds = (question.options || []).filter((o) => o.is_correct).map((o) => o.id);
            if (picked.length === correctIds.length && correctIds.every((id) => picked.includes(id))) {
                earned += pts;
            }
        }
    } else if (quizType.value === 'color_the_conflict') {
        for (const card of q.cards || []) {
            const pts = Number(card.points || 10);
            total += pts;
            if (colorSelected.value[card.id] === card.conflict_type) {
                earned += pts;
            }
        }
    } else if (quizType.value === 'the_conflict_map') {
        for (const ev of q.events || []) {
            const pts = Number(ev.points || 10);
            total += pts;
            if (mapAssignments.value[ev.id] === ev.target_conflict_type) {
                earned += pts;
            }
        }
    } else if (quizType.value === 'matching_type') {
        for (const item of q.matching_items || []) {
            const pts = Number(item.points || 10);
            total += pts;
            if (matchingSelected.value[item.id] === item.correct_choice_letter) {
                earned += pts;
            }
        }
    } else if (quizType.value === 'evaluation') {
        for (const item of q.evaluation_items || []) {
            const pts = Number(item.points || 20);
            total += pts;
            const st = evaluationState.value[item.id];
            const pickedOpt = (item.options || []).find((o) => o.id === st?.optionId);
            const blanksOk = st?.blanks?.every((b) => b && b.trim().length >= 2);
            if (pickedOpt?.is_correct && blanksOk) {
                earned += pts;
            }
        }
    } else if (quizType.value === 'reflection') {
        for (const prompt of q.prompts || []) {
            const pts = Number(prompt.points || 20);
            total += pts;
            const words = getReflectionWordCount(prompt.id);
            if (words >= Number(prompt.min_words || 5)) {
                earned += pts;
            }
        }
    }

    const safeTotal = Math.max(1, total);
    const pct = Math.round((earned / safeTotal) * 100);
    const passThreshold = Number(q.passing_percentage || 70);
    const passed = pct >= passThreshold;
    const stars = pct >= 90 ? 3 : pct >= passThreshold ? 2 : pct >= 40 ? 1 : 0;

    return {
        earned,
        total: safeTotal,
        pct,
        stars,
        passed,
        passThreshold,
    };
});

const packageAnswersPayload = () => {
    switch (quizType.value) {
        case 'multiple_choice':
            return mcqSelected.value;
        case 'color_the_conflict':
            return colorSelected.value;
        case 'the_conflict_map':
            return mapAssignments.value;
        case 'matching_type':
            return matchingSelected.value;
        case 'evaluation': {
            const out = {};
            const items = activeQuiz.value?.evaluation_items || [];
            if (items.length > 0) {
                for (const item of items) {
                    const st = ensureEvaluationState(item);
                    const chosenOption = (item.options || []).find((o) => o.id === st.optionId);
                    out[item.id] = {
                        option_id: st.optionId,
                        stance: chosenOption?.stance_value || chosenOption?.option_text || null,
                        blanks: st.blanks || [],
                    };
                }
            } else {
                for (const [itemId, st] of Object.entries(evaluationState.value)) {
                    out[itemId] = {
                        option_id: st.optionId,
                        stance: null,
                        blanks: st.blanks || [],
                    };
                }
            }
            return out;
        }
        case 'reflection':
            return reflectionText.value;
        default:
            return {};
    }
};

const finishQuizAndShowSummary = () => {
    const q = activeQuiz.value;

    // In demo format fallback mode, do local presentation
    if (!props.module?.slug || !props.quiz?.slug) {
        showCompletionModal.value = true;
        playSuccess();
        return;
    }

    // Submit answers to server for persistent storage and server-side evaluation
    isSubmittingQuiz.value = true;
    const timeSpent = q.time_limit_seconds && remainingSeconds.value !== null
        ? Math.max(1, Number(q.time_limit_seconds) - remainingSeconds.value)
        : 60;

    const payload = {
        time_spent_seconds: timeSpent,
        answers: packageAnswersPayload(),
    };

    fetch(`/students/modules/${props.module.slug}/quizzes/${props.quiz.slug}/submit`, {
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
            if (data.attempt) {
                serverAttemptResult.value = data.attempt;
            }
            showCompletionModal.value = true;
            playSuccess();
        })
        .catch((err) => {
            console.error('Quiz submission error:', err);
            showCompletionModal.value = true;
            playSuccess();
        })
        .finally(() => {
            isSubmittingQuiz.value = false;
        });
};

/* ==========================================================================
   NAVIGATION HELPERS
   ========================================================================== */
const goPrevItem = () => {
    if (currentItemIndex.value > 0) {
        currentItemIndex.value--;
    }
};

const goNextItem = () => {
    if (currentItemIndex.value < totalItemsCount.value - 1) {
        currentItemIndex.value++;
    }
};

const goBackToMap = () => {
    if (activeModule.value?.slug && props.module) {
        router.visit(`/students/modules/${activeModule.value.slug}`);
        return;
    }
    router.visit('/students/progress');
};

const switchQuizFormat = (formatKey) => {
    // Check if there is a sibling quiz in the database with this format first
    const matchingSibling = (props.siblingQuizzes || []).find((sq) => sq.type === formatKey);
    if (matchingSibling && props.module?.slug && matchingSibling.slug !== props.quiz?.slug) {
        router.visit(`/students/modules/${props.module.slug}/quizzes/${matchingSibling.slug}`);
        return;
    }
    activeDemoFormat.value = formatKey;
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && showCompletionModal.value) {
        showCompletionModal.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head :title="`${activeQuiz?.title || 'Quiz Arena'} • ${activeModule?.title || 'WEBREAD'}`" />

    <div class="clay-student-quiz-shell">
        <!-- =========================================================================
             1. STICKY 3D CLAY QUIZ HUD BAR
             ========================================================================= -->
        <header class="clay-quiz-sticky-hud" role="banner">
            <div class="quiz-hud-top-row">
                <!-- Left: Back to Map + Quiz Title & Format Pill -->
                <div class="quiz-hud-brand">
                    <button
                        type="button"
                        class="clay-hud-back-btn"
                        aria-label="Back to Adventure Map"
                        @click="goBackToMap"
                    >
                        <svg viewBox="0 0 20 20" class="hud-icon-svg" aria-hidden="true">
                            <path
                                d="M12.5 4.5L7 10l5.5 5.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>Map</span>
                    </button>

                    <div class="quiz-hud-title-group">
                        <div class="quiz-hud-kicker-row">
                            <span class="quiz-format-badge">{{ currentFormatInfo.label }}</span>
                            <span class="quiz-module-kicker">{{ activeModule.title }}</span>
                        </div>
                        <h1 class="quiz-hud-main-title">{{ activeQuiz.title }}</h1>
                    </div>
                </div>

                <!-- Center: Interactive Progress Bar & Step Counter (Only when taking active quiz) -->
                <div v-if="!existingAttempt" class="quiz-hud-progress-pod">
                    <div class="progress-pod-labels">
                        <span class="progress-step-label">
                            Answered {{ answeredItemsCount }} of {{ totalItemsCount }}
                        </span>
                        <span class="progress-pct-label">{{ progressPercentage }}%</span>
                    </div>
                    <div
                        class="clay-quiz-progress-track"
                        role="progressbar"
                        :aria-valuenow="progressPercentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="clay-quiz-progress-fill"
                            :style="{ width: `${progressPercentage}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Right: Timer, Hint Toggle & XP Bounty -->
                <div class="quiz-hud-actions">
                    <div v-if="!existingAttempt && formattedTimer" class="clay-timer-pill" title="Time Remaining">
                        <svg viewBox="0 0 20 20" class="hud-icon-svg" aria-hidden="true">
                            <circle cx="10" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2" />
                            <path d="M10 8v3.5l2.2 1.5M8 2.5h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <span>{{ formattedTimer }}</span>
                    </div>

                    <button
                        v-if="!existingAttempt"
                        type="button"
                        class="clay-hint-toggle-btn"
                        :class="{ 'is-active': showHintBanner }"
                        @click="showHintBanner = !showHintBanner"
                    >
                        <svg viewBox="0 0 20 20" class="hud-icon-svg" aria-hidden="true">
                            <path
                                d="M10 2.5a5.5 5.5 0 0 0-3.5 9.7c.6.5 1 1.2 1 2v.3h5v-.3c0-.8.4-1.5 1-2A5.5 5.5 0 0 0 10 2.5zM8 17h4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                        <span>Hint</span>
                    </button>

                    <div class="clay-xp-reward-pill" title="Quiz XP Reward">
                        <svg viewBox="0 0 16 16" class="hud-icon-svg" aria-hidden="true">
                            <path d="M9.5 1L3 9h4.5L6.5 15L13 7H8.5L9.5 1z" fill="currentColor" />
                        </svg>
                        <span>+{{ activeQuiz.xp_reward || 150 }} XP</span>
                    </div>

                    <button
                        v-if="!existingAttempt"
                        type="button"
                        class="clay-finish-quiz-btn"
                        @click="finishQuizAndShowSummary"
                    >
                        <span>Submit Quiz ✓</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        class="clay-finish-quiz-btn is-completed-btn"
                        @click="showCompletionModal = true"
                    >
                        <span>Completed ★ ({{ existingAttempt.percentage }}%)</span>
                    </button>
                </div>
            </div>

            <!-- Format Switcher Strip (Allows switching between all 6 interactive quiz formats) -->

        </header>

        <!-- =========================================================================
             2. MAIN INTERACTIVE QUIZ ARENA STAGE
             ========================================================================= -->
        <main class="clay-quiz-main-stage">
            <!-- ALREADY COMPLETED STATUS BANNER -->
            <section v-if="existingAttempt" class="clay-already-taken-card" :class="{ 'card-pending-review': isAttemptPendingReview }" role="alert">
                <div class="already-taken-content">
                    <div v-if="isAttemptPendingReview" class="already-taken-badge pending-badge-box" aria-hidden="true">
                        <span class="pending-badge-icon">📝</span>
                        <span class="pending-badge-text">In Review</span>
                    </div>
                    <div v-else class="already-taken-badge" aria-hidden="true">
                        <span v-for="s in 3" :key="s" class="star-pill" :class="{ 'star-filled': s <= existingAttempt.stars }">★</span>
                    </div>
                    <div class="already-taken-copy">
                        <div class="already-taken-kicker-row">
                            <span v-if="isAttemptPendingReview" class="already-taken-status-pill pill-pending-amber">PENDING TEACHER REVIEW 📝</span>
                            <span v-else class="already-taken-status-pill">QUIZ ALREADY COMPLETED</span>
                            <span class="already-taken-date">Submitted on {{ existingAttempt.completed_at }}</span>
                        </div>
                        <h2 class="already-taken-title">
                            {{ isAttemptPendingReview ? 'Your response is awaiting teacher grading' : 'You have already completed this quest milestone!' }}
                        </h2>
                        <p class="already-taken-desc">
                            <template v-if="isAttemptPendingReview">
                                You have submitted this quest challenge! Because Evaluation and Reflection challenges contain opinion-based answers, your teacher will review your responses, give feedback, and set your score and stars. You can continue forward along your reading quest!
                            </template>
                            <template v-else>
                                Your official score is <strong>{{ existingAttempt.score }} / {{ existingAttempt.max_score }} ({{ existingAttempt.percentage }}%)</strong>, earning you <strong>+{{ existingAttempt.xp_earned }} XP</strong>. You cannot retake this quiz, but you can review your score summary or head back to the adventure map.
                            </template>
                        </p>
                        <!-- Teacher Feedback Note -->
                        <div v-if="latestTeacherFeedback" class="clay-teacher-feedback-box">
                            <div class="feedback-badge-row">
                                <span class="feedback-emoji">👨‍🏫</span>
                                <strong class="feedback-badge-lbl">Teacher Feedback:</strong>
                            </div>
                            <p class="feedback-text">"{{ latestTeacherFeedback }}"</p>
                        </div>
                    </div>
                </div>
                <div class="already-taken-actions">
                    <button type="button" class="clay-taken-review-btn" @click="showCompletionModal = true">
                        <span>{{ isAttemptPendingReview ? 'View Submission 📝' : 'Review Score Summary 🏆' }}</span>
                    </button>
                    <button type="button" class="clay-taken-back-btn" @click="goBackToMap">
                        <span>Back to Map →</span>
                    </button>
                </div>
            </section>

            <!-- ACTIVE QUIZ CONTENT (ONLY RENDERED WHEN QUIZ NOT ALREADY TAKEN) -->
            <template v-else>
                <!-- OPTIONAL COLLAPSIBLE READING PASSAGE / STORY SCENARIO BANNER -->
            <section
                v-if="activeContexts.length > 0"
                class="clay-passage-banner-card"
                aria-label="Story Reading Passage"
            >
                <div class="passage-banner-header">
                    <div class="passage-title-wrap">
                        <span class="passage-badge">STORY SCENARIO</span>
                        <span class="passage-sub">{{ activeQuiz.description }}</span>
                    </div>

                    <button
                        type="button"
                        class="passage-collapse-btn"
                        @click="isPassageExpanded = !isPassageExpanded"
                    >
                        {{ isPassageExpanded ? 'Hide Passage ▲' : 'Show Passage ▼' }}
                    </button>
                </div>

                <div v-show="isPassageExpanded" class="passage-banner-body">
                    <div
                        v-for="(ctx, cIdx) in activeContexts"
                        :key="ctx.id || cIdx"
                        class="passage-scenario-block"
                    >
                        <p class="passage-scenario-text">"{{ ctx.scenario }}"</p>
                        <p v-if="showHintBanner && ctx.hint" class="passage-context-hint">
                            <strong>Reading Clue:</strong> {{ ctx.hint }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- GLOBAL HINT BANNER WHEN TOGGLED -->
            <Transition name="fade-slide">
                <div v-if="showHintBanner && currentItem?.hint" class="clay-active-hint-callout" role="status">
                    <div class="hint-callout-badge">EXPLORER HINT</div>
                    <p class="hint-callout-text">{{ currentItem.hint }}</p>
                </div>
            </Transition>

            <!-- =====================================================================
                 FORMAT 1: MULTIPLE CHOICE (SINGLE & MULTI-SELECT)
                 ===================================================================== -->
            <section v-if="quizType === 'multiple_choice'" class="quiz-format-stage">
                <div
                    v-for="(question, qIdx) in viewAllMode ? activeQuiz.questions : [currentItem]"
                    :key="question?.id || qIdx"
                    class="clay-interactive-card"
                >
                    <div v-if="question">
                        <div class="card-top-meta">
                            <span class="item-step-pill">
                                Question {{ viewAllMode ? qIdx + 1 : currentItemIndex + 1 }} of {{ totalItemsCount }}
                            </span>
                            <div class="item-meta-right">
                                <span class="selection-mode-tag">
                                    {{
                                        question.selection_type === 'multiple'
                                            ? 'Select ALL that apply'
                                            : 'Pick ONE best answer'
                                    }}
                                </span>
                                <span class="item-points-tag">{{ question.points || 10 }} pts</span>
                            </div>
                        </div>

                        <blockquote v-if="question.passage_context" class="question-excerpt-quote">
                            "{{ question.passage_context }}"
                        </blockquote>

                        <h2 class="question-main-prompt">{{ question.question_text }}</h2>

                        <div class="mcq-options-grid">
                            <button
                                v-for="(opt, oIdx) in question.options"
                                :key="opt.id || oIdx"
                                type="button"
                                class="clay-mcq-option-btn"
                                :class="{
                                    'is-selected': isMcqOptionSelected(question, opt.id),
                                    'is-correct':
                                        mcqChecked[question.id] &&
                                        (opt.is_correct || (isMcqOptionSelected(question, opt.id) && opt.is_correct)),
                                    'is-wrong':
                                        mcqChecked[question.id] &&
                                        isMcqOptionSelected(question, opt.id) &&
                                        !opt.is_correct,
                                }"
                                @click="toggleMcqOption(question, opt.id)"
                            >
                                <span class="mcq-letter-badge">{{ String.fromCharCode(65 + oIdx) }}</span>
                                <div class="mcq-option-copy">
                                    <span class="mcq-option-text">{{ opt.option_text }}</span>
                                    <small
                                        v-if="mcqChecked[question.id] && isMcqOptionSelected(question, opt.id) && opt.feedback"
                                        class="mcq-option-feedback"
                                    >
                                        {{ opt.feedback }}
                                    </small>
                                </div>
                                <span class="mcq-check-indicator" aria-hidden="true">
                                    <template v-if="mcqChecked[question.id] && opt.is_correct">✓</template>
                                    <template
                                        v-else-if="
                                            mcqChecked[question.id] &&
                                            isMcqOptionSelected(question, opt.id) &&
                                            !opt.is_correct
                                        "
                                    >
                                        ✕
                                    </template>
                                    <template v-else-if="isMcqOptionSelected(question, opt.id)">●</template>
                                </span>
                            </button>
                        </div>

                        <!-- Action & Feedback Footer -->
                        <div class="card-check-row">
                            <!-- <button
                                type="button"
                                class="clay-check-answer-btn"
                                :disabled="!(mcqSelected[question.id]?.length > 0)"
                                @click="checkMcqQuestion(question)"
                            >
                                Check Answer ✓
                            </button> -->

                            <div
                                v-if="mcqChecked[question.id]"
                                class="feedback-pill-banner"
                                :class="
                                    mcqChecked[question.id] === 'correct' ? 'banner-correct' : 'banner-incorrect'
                                "
                            >
                                <strong>
                                    {{
                                        mcqChecked[question.id] === 'correct'
                                            ? 'Spot on, Explorer!'
                                            : 'Not quite — check the clue!'
                                    }}
                                </strong>
                                <span>
                                    {{
                                        question.explanation ||
                                        question.hint ||
                                        'Review the passage details above.'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 2: COLOR THE CONFLICT (STORY EXCERPT + 6 COLOR SWATCHES)
                 ===================================================================== -->
            <section v-else-if="quizType === 'color_the_conflict'" class="quiz-format-stage">
                <div
                    v-for="(card, cIdx) in viewAllMode ? activeQuiz.cards : [currentItem]"
                    :key="card?.id || cIdx"
                    class="clay-interactive-card"
                >
                    <div v-if="card">
                        <div class="card-top-meta">
                            <span class="item-step-pill">
                                Excerpt Card {{ viewAllMode ? cIdx + 1 : currentItemIndex + 1 }} of {{ totalItemsCount }}
                            </span>
                            <span class="item-points-tag">{{ card.points || 10 }} pts</span>
                        </div>

                        <!-- Story Excerpt Canvas that Tints with Selected Conflict Color -->
                        <div
                            class="conflict-excerpt-canvas"
                            :style="
                                colorSelected[card.id]
                                    ? {
                                          backgroundColor: getConflictMeta(colorSelected[card.id]).bg,
                                          borderColor: getConflictMeta(colorSelected[card.id]).swatch,
                                          color: getConflictMeta(colorSelected[card.id]).text,
                                      }
                                    : {}
                            "
                        >
                            <div class="excerpt-canvas-top">
                                <span class="excerpt-tag">STORY EXCERPT</span>
                                <span
                                    v-if="colorSelected[card.id]"
                                    class="selected-color-chip"
                                    :style="{ backgroundColor: getConflictMeta(colorSelected[card.id]).swatch }"
                                >
                                    Painted: {{ getConflictMeta(colorSelected[card.id]).label }}
                                </span>
                            </div>
                            <p class="excerpt-quote-body">"{{ card.excerpt }}"</p>
                        </div>

                        <h3 class="swatch-picker-heading">
                            Tap a Conflict Color Swatch to paint this scene:
                        </h3>

                        <!-- 6 Conflict Color Swatch Buttons -->
                        <div class="conflict-swatch-grid">
                            <button
                                v-for="ctype in conflictTypes"
                                :key="ctype.value"
                                type="button"
                                class="clay-swatch-btn"
                                :class="{
                                    'is-selected': colorSelected[card.id] === ctype.value,
                                    'is-correct':
                                        colorChecked[card.id] && card.conflict_type === ctype.value,
                                    'is-wrong':
                                        colorChecked[card.id] === 'incorrect' &&
                                        colorSelected[card.id] === ctype.value,
                                }"
                                :style="{
                                    '--swatch-color': ctype.swatch,
                                    '--swatch-bg': ctype.bg,
                                    '--swatch-border': ctype.border,
                                    '--swatch-text': ctype.text,
                                }"
                                @click="selectConflictColor(card, ctype.value)"
                            >
                                <span
                                    class="swatch-paint-dot"
                                    :style="{ backgroundColor: ctype.swatch }"
                                ></span>
                                <span class="swatch-label-text">{{ ctype.label }}</span>
                            </button>
                        </div>

                        <div class="card-check-row">
                            <button
                                type="button"
                                class="clay-check-answer-btn"
                                :disabled="!colorSelected[card.id]"
                                @click="checkConflictCard(card)"
                            >
                                Verify Conflict Color ✓
                            </button>

                            <div
                                v-if="colorChecked[card.id]"
                                class="feedback-pill-banner"
                                :class="
                                    colorChecked[card.id] === 'correct' ? 'banner-correct' : 'banner-incorrect'
                                "
                            >
                                <strong>
                                    {{
                                        colorChecked[card.id] === 'correct'
                                            ? `Correct! (${getConflictMeta(card.conflict_type).label})`
                                            : 'Try another color swatch!'
                                    }}
                                </strong>
                                <span>
                                    {{
                                        colorChecked[card.id] === 'correct'
                                            ? card.explanation
                                            : card.feedback_incorrect || card.hint
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 3: THE CONFLICT MAP (DRAG-AND-DROP + TAP-TO-ASSIGN ZONES)
                 ===================================================================== -->
            <section v-else-if="quizType === 'the_conflict_map'" class="quiz-format-stage">
                <div class="clay-interactive-card">
                    <div class="card-top-meta">
                        <span class="item-step-pill">
                            Interactive Conflict Map • {{ answeredItemsCount }}/{{ (activeQuiz.events || []).length }} Sorted
                        </span>
                        <span class="selection-mode-tag">
                            Drag cards into buckets OR tap a zone button on any card
                        </span>
                    </div>

                    <div class="conflict-map-board">
                        <!-- LEFT: EVENT CARDS DECK -->
                        <div class="map-events-column">
                            <h3 class="map-col-title">Story Event Cards</h3>

                            <div
                                v-for="ev in activeQuiz.events"
                                :key="ev.id"
                                class="clay-map-event-card"
                                :class="{
                                    'is-selected-for-tap': selectedMapEventId === ev.id,
                                    'is-assigned': Boolean(mapAssignments[ev.id]),
                                    'is-correct':
                                        mapChecked && mapAssignments[ev.id] === ev.target_conflict_type,
                                    'is-wrong':
                                        mapChecked &&
                                        mapAssignments[ev.id] &&
                                        mapAssignments[ev.id] !== ev.target_conflict_type,
                                }"
                                draggable="true"
                                @dragstart="handleEventDragStart(ev.id)"
                                @click="selectedMapEventId = selectedMapEventId === ev.id ? null : ev.id"
                            >
                                <div class="event-card-top">
                                    <span class="event-letter-pill">Event {{ ev.card_letter }}</span>
                                    <span class="event-pts-lbl">{{ ev.points || 10 }} pts</span>
                                </div>

                                <p class="event-card-body">{{ ev.event_text }}</p>

                                <!-- Quick 1-Tap Zone Assign Buttons (Accessible without dragging!) -->
                                <div class="event-quick-zone-row" @click.stop>
                                    <button
                                        v-for="zone in activeQuiz.zones"
                                        :key="zone.id || zone.conflict_type"
                                        type="button"
                                        class="quick-zone-chip"
                                        :class="{
                                            'is-active': mapAssignments[ev.id] === zone.conflict_type,
                                        }"
                                        @click="assignEventToZone(ev.id, zone.conflict_type)"
                                    >
                                        {{ zone.label }}
                                    </button>
                                </div>

                                <p
                                    v-if="mapChecked && mapAssignments[ev.id]"
                                    class="event-map-feedback"
                                    :class="
                                        mapAssignments[ev.id] === ev.target_conflict_type
                                            ? 'text-emerald'
                                            : 'text-rose'
                                    "
                                >
                                    {{
                                        mapAssignments[ev.id] === ev.target_conflict_type
                                            ? `✓ Correct! ${ev.explanation || ''}`
                                            : `✕ Hint: ${ev.hint || 'Try placing this event in another bucket.'}`
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- RIGHT: DROP-ZONE BUCKETS -->
                        <div class="map-zones-column">
                            <h3 class="map-col-title">Conflict Zone Buckets</h3>

                            <div
                                v-for="zone in activeQuiz.zones"
                                :key="zone.id || zone.conflict_type"
                                class="clay-map-zone-bucket"
                                :style="{
                                    backgroundColor: getZoneTheme(zone.color_theme).bg,
                                    borderColor: getZoneTheme(zone.color_theme).border,
                                }"
                                @dragover.prevent
                                @drop.prevent="handleZoneDrop(zone.conflict_type)"
                                @click="handleZoneTap(zone.conflict_type)"
                            >
                                <div class="zone-bucket-header">
                                    <div>
                                        <strong
                                            class="zone-bucket-title"
                                            :style="{ color: getZoneTheme(zone.color_theme).text }"
                                        >
                                            {{ zone.label }}
                                        </strong>
                                        <span v-if="zone.subtitle" class="zone-bucket-sub">
                                            {{ zone.subtitle }}
                                        </span>
                                    </div>
                                    <span
                                        class="zone-count-badge"
                                        :style="{ backgroundColor: getZoneTheme(zone.color_theme).header }"
                                    >
                                        {{ getZoneEvents(zone.conflict_type).length }} Placed
                                    </span>
                                </div>

                                <div class="zone-placed-list">
                                    <div
                                        v-for="placed in getZoneEvents(zone.conflict_type)"
                                        :key="placed.id"
                                        class="placed-event-chip"
                                    >
                                        <span class="placed-letter">{{ placed.card_letter }}</span>
                                        <span class="placed-excerpt">{{ placed.event_text }}</span>
                                        <button
                                            type="button"
                                            class="placed-remove-btn"
                                            aria-label="Remove event from bucket"
                                            @click.stop="unassignEvent(placed.id)"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <div
                                        v-if="getZoneEvents(zone.conflict_type).length === 0"
                                        class="zone-empty-placeholder"
                                    >
                                        Drop Event Card here or tap a zone button on any card
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-check-row">
                        <button
                            type="button"
                            class="clay-check-answer-btn"
                            :disabled="answeredItemsCount === 0"
                            @click="checkConflictMap"
                        >
                            Check Conflict Map ✓
                        </button>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 4: MATCHING TYPE (COLUMN A DESCRIPTIONS ↔ COLUMN B CHOICES)
                 ===================================================================== -->
            <section v-else-if="quizType === 'matching_type'" class="quiz-format-stage">
                <div class="clay-interactive-card">
                    <div class="card-top-meta">
                        <span class="item-step-pill">
                            Matching Challenge • {{ answeredItemsCount }}/{{ (activeQuiz.matching_items || []).length }} Matched
                        </span>
                        <span class="selection-mode-tag">
                            Tap an item in Column A and tap its letter in Column B
                        </span>
                    </div>

                    <div class="matching-arena-grid">
                        <!-- COLUMN A: NUMBERED DESCRIPTIONS -->
                        <div class="matching-col">
                            <h3 class="map-col-title">Column A: Clues & Descriptions</h3>

                            <div
                                v-for="item in activeQuiz.matching_items"
                                :key="item.id"
                                class="clay-matching-item-card"
                                :class="{
                                    'is-active-item': activeMatchingItemId === item.id,
                                    'is-correct':
                                        matchingChecked &&
                                        matchingSelected[item.id] === item.correct_choice_letter,
                                    'is-wrong':
                                        matchingChecked &&
                                        matchingSelected[item.id] &&
                                        matchingSelected[item.id] !== item.correct_choice_letter,
                                }"
                                @click="activeMatchingItemId = activeMatchingItemId === item.id ? null : item.id"
                            >
                                <div class="matching-item-top">
                                    <span class="item-number-badge">#{{ item.item_number }}</span>
                                    <span class="item-points-tag">{{ item.points || 10 }} pts</span>
                                </div>

                                <p class="matching-item-desc">{{ item.description }}</p>

                                <!-- Quick Letter Picker Pills -->
                                <div class="matching-letter-pills" @click.stop>
                                    <span class="letter-picker-lbl">Match with:</span>
                                    <button
                                        v-for="choice in activeQuiz.choices"
                                        :key="choice.id || choice.choice_letter"
                                        type="button"
                                        class="clay-letter-pill"
                                        :class="{
                                            'is-selected':
                                                matchingSelected[item.id] === choice.choice_letter,
                                        }"
                                        @click="assignMatchingChoice(item.id, choice.choice_letter)"
                                    >
                                        {{ choice.choice_letter }}
                                    </button>
                                </div>

                                <div v-if="matchingSelected[item.id]" class="matched-preview-chip">
                                    Matched: <strong>{{ getChoiceTextByLetter(matchingSelected[item.id]) }}</strong>
                                </div>

                                <p
                                    v-if="matchingChecked && matchingSelected[item.id]"
                                    class="event-map-feedback"
                                    :class="
                                        matchingSelected[item.id] === item.correct_choice_letter
                                            ? 'text-emerald'
                                            : 'text-rose'
                                    "
                                >
                                    {{
                                        matchingSelected[item.id] === item.correct_choice_letter
                                            ? `✓ Correct! ${item.explanation || ''}`
                                            : `✕ Hint: ${item.hint || 'Try another letter from Column B.'}`
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- COLUMN B: LETTERED CHOICES POOL -->
                        <div class="matching-col">
                            <h3 class="map-col-title">Column B: Answer Choices</h3>

                            <button
                                v-for="choice in activeQuiz.choices"
                                :key="choice.id || choice.choice_letter"
                                type="button"
                                class="clay-matching-choice-card"
                                @click="handleChoiceColumnTap(choice.choice_letter)"
                            >
                                <span class="choice-letter-circle">{{ choice.choice_letter }}</span>
                                <span class="choice-card-text">{{ choice.choice_text }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="card-check-row">
                        <button
                            type="button"
                            class="clay-check-answer-btn"
                            :disabled="answeredItemsCount === 0"
                            @click="checkMatchingQuiz"
                        >
                            Check All Matches ✓
                        </button>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 5: EVALUATION (WORD HELPERS + STEP 1 STANCE + STEP 2 BLANKS)
                 ===================================================================== -->
            <section v-else-if="quizType === 'evaluation'" class="quiz-format-stage">
                <!-- Shared Word Helpers Bank -->
                <div
                    v-if="activeQuiz.word_helpers && activeQuiz.word_helpers.length > 0"
                    class="clay-word-helpers-bar"
                >
                    <div class="word-helpers-head">
                        <span class="helpers-kicker">WORD HELPERS BANK</span>
                        <span class="helpers-sub">Tap any word chip to insert it into your Step 2 sentence blank!</span>
                    </div>
                    <div class="word-helpers-chips">
                        <button
                            v-for="(word, wIdx) in activeQuiz.word_helpers"
                            :key="wIdx"
                            type="button"
                            class="clay-word-helper-chip"
                            @click="insertWordHelperIntoBlank(word)"
                        >
                            + {{ word }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(item, eIdx) in viewAllMode ? activeQuiz.evaluation_items : [currentItem]"
                    :key="item?.id || eIdx"
                    class="clay-interactive-card"
                >
                    <div v-if="item">
                        <div class="card-top-meta">
                            <span class="item-step-pill">{{ item.section_title || `Evaluation ${eIdx + 1}` }}</span>
                            <span class="item-points-tag">{{ item.points || 20 }} pts</span>
                        </div>

                        <h2 class="question-main-prompt">{{ item.question_prompt }}</h2>

                        <!-- Step 1: Pick Stance Option -->
                        <div class="eval-step-box">
                            <span class="eval-step-kicker">{{ item.step1_prompt || 'Step 1: Pick one option:' }}</span>

                            <div class="mcq-options-grid">
                                <button
                                    v-for="(opt, oIdx) in item.options"
                                    :key="opt.id || oIdx"
                                    type="button"
                                    class="clay-mcq-option-btn"
                                    :class="{
                                        'is-selected': ensureEvaluationState(item).optionId === opt.id,
                                    }"
                                    @click="selectEvaluationOption(item, opt.id)"
                                >
                                    <span class="mcq-letter-badge">{{ String.fromCharCode(65 + oIdx) }}</span>
                                    <span class="mcq-option-text">{{ opt.option_text }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Interactive Sentence Template with Auto-Filled Stance & Inline Blanks -->
                        <div class="eval-step-box eval-step-two">
                            <div class="eval-step-header-row">
                                <span class="eval-step-kicker">
                                    Step 2: Complete the reasoning sentence:
                                </span>
                                <span class="eval-step-guide-pill">
                                    <span class="guide-dot" aria-hidden="true"></span>
                                    <span>Stance auto-fills from Step 1 • Type answer in blanks</span>
                                </span>
                            </div>

                            <div class="sentence-template-builder">
                                <template
                                    v-for="(segment, sIdx) in parseSentenceTemplate(item)"
                                    :key="sIdx"
                                >
                                    <!-- 1. Static Text Segment -->
                                    <span v-if="segment.type === 'text'" class="sentence-static-text">{{ segment.content }}</span>

                                    <!-- 2. Auto-Filled Stance Token (Filled from Step 1 Option Selection) -->
                                    <span
                                        v-else-if="segment.type === 'stance'"
                                        class="clay-stance-token-slot"
                                    >
                                        <span
                                            v-if="getSelectedStanceOption(item)"
                                            class="clay-stance-filled-pill"
                                            :title="`Selected Stance: ${getSelectedStanceOption(item).option_text}`"
                                        >
                                            <span class="stance-check-badge" aria-hidden="true">✓</span>
                                            <strong class="stance-filled-text">{{ getSelectedStanceOption(item).option_text }}</strong>
                                        </span>
                                        <span
                                            v-else
                                            class="clay-stance-empty-pill"
                                            title="Pick your stance in Step 1 above to fill this part"
                                        >
                                            <span class="stance-empty-icon" aria-hidden="true">👆</span>
                                            <span class="stance-empty-text">[Pick Stance in Step 1]</span>
                                        </span>
                                    </span>

                                    <!-- 3. Interactive Blank Input (Where student writes their answer) -->
                                    <span
                                        v-else-if="segment.type === 'blank'"
                                        class="clay-blank-input-slot"
                                    >
                                        <input
                                            type="text"
                                            class="clay-inline-blank-input"
                                            :class="{
                                                'is-active-focus': focusedBlankInfo.itemId === item.id && focusedBlankInfo.blankIdx === segment.blankIdx,
                                                'is-blank-filled': (ensureEvaluationState(item).blanks[segment.blankIdx] || '').trim().length > 0,
                                            }"
                                            :placeholder="`Write your answer here...`"
                                            :aria-label="`Answer blank ${segment.blankIdx + 1}`"
                                            :value="ensureEvaluationState(item).blanks[segment.blankIdx] || ''"
                                            @focus="focusedBlankInfo = { itemId: item.id, blankIdx: segment.blankIdx }"
                                            @input="updateEvaluationBlank(item, segment.blankIdx, $event.target.value)"
                                        />
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="card-check-row">
                            <button
                                type="button"
                                class="clay-check-answer-btn"
                                @click="checkEvaluationItem(item)"
                            >
                                Check Evaluation ✓
                            </button>

                            <div
                                v-if="evaluationChecked[item.id]"
                                class="feedback-pill-banner"
                                :class="
                                    evaluationChecked[item.id] === 'correct'
                                        ? 'banner-correct'
                                        : 'banner-incorrect'
                                "
                            >
                                <strong>
                                    <template v-if="evaluationChecked[item.id] === 'correct'">
                                        Great reasoning, Explorer!
                                    </template>
                                    <template v-else-if="evaluationChecked[item.id] === 'incomplete'">
                                        Almost there! Complete both Step 1 and Step 2 blanks.
                                    </template>
                                    <template v-else>
                                        Re-check Step 1 and refine your sentence!
                                    </template>
                                </strong>
                                <span>{{ item.hint || 'Support your judgment with evidence from the story.' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 6: REFLECTION (OPEN RESPONSE + STARTER SENTENCE + WORD COUNTER)
                 ===================================================================== -->
            <section v-else-if="quizType === 'reflection'" class="quiz-format-stage">
                <div
                    v-for="(prompt, rIdx) in viewAllMode ? activeQuiz.prompts : [currentItem]"
                    :key="prompt?.id || rIdx"
                    class="clay-interactive-card"
                >
                    <div v-if="prompt">
                        <div class="card-top-meta">
                            <span class="item-step-pill">
                                Reflection Prompt {{ viewAllMode ? rIdx + 1 : currentItemIndex + 1 }} of {{ totalItemsCount }}
                            </span>
                            <span class="item-points-tag">{{ prompt.points || 25 }} pts</span>
                        </div>

                        <h2 class="question-main-prompt">{{ prompt.prompt_text }}</h2>

                        <p v-if="prompt.guiding_hint" class="reflection-guiding-note">
                            <strong>Guiding Question:</strong> {{ prompt.guiding_hint }}
                        </p>

                        <!-- Starter Sentence Helper Button -->
                        <div v-if="prompt.starter_sentence" class="starter-sentence-bar">
                            <span class="starter-sentence-preview">
                                Starter: <em>"{{ prompt.starter_sentence }}..."</em>
                            </span>
                            <button
                                type="button"
                                class="clay-starter-insert-btn"
                                @click="insertStarterSentence(prompt)"
                            >
                                + Insert Starter Sentence
                            </button>
                        </div>

                        <textarea
                            v-model="reflectionText[prompt.id]"
                            rows="5"
                            class="clay-reflection-textarea"
                            placeholder="Write your thoughtful reflection here..."
                        ></textarea>

                        <!-- Live Word Counter & Goal Bar -->
                        <div class="reflection-word-meter">
                            <span class="word-count-status">
                                Word Count:
                                <strong>{{ getReflectionWordCount(prompt.id) }}</strong>
                                (Goal: {{ prompt.min_words || 10 }}–{{ prompt.max_words || 150 }} words)
                            </span>

                            <span
                                class="word-goal-chip"
                                :class="
                                    getReflectionWordCount(prompt.id) >= Number(prompt.min_words || 10)
                                        ? 'goal-met'
                                        : 'goal-pending'
                                "
                            >
                                {{
                                    getReflectionWordCount(prompt.id) >= Number(prompt.min_words || 10)
                                        ? '✓ Word Goal Reached!'
                                        : `${Math.max(0, Number(prompt.min_words || 10) - getReflectionWordCount(prompt.id))} more words needed`
                                }}
                            </span>
                        </div>

                        <div class="card-check-row">
                            <button
                                type="button"
                                class="clay-check-answer-btn"
                                @click="checkReflectionPrompt(prompt)"
                            >
                                Save & Verify Reflection ✓
                            </button>

                            <div
                                v-if="reflectionChecked[prompt.id]"
                                class="feedback-pill-banner"
                                :class="
                                    reflectionChecked[prompt.id] === 'completed'
                                        ? 'banner-correct'
                                        : 'banner-incorrect'
                                "
                            >
                                <strong>
                                    {{
                                        reflectionChecked[prompt.id] === 'completed'
                                            ? 'Reflection Saved! Wonderful insight!'
                                            : 'Keep writing to reach the minimum word count!'
                                    }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 STEPPER NAVIGATION BAR (FOR MULTI-CARD FORMATS IN SINGLE-CARD VIEW)
                 ===================================================================== -->
            <nav
                v-if="!viewAllMode && quizType !== 'the_conflict_map' && quizType !== 'matching_type' && totalItemsCount > 1"
                class="clay-quiz-bottom-nav"
                aria-label="Quiz Question Navigation"
            >
                <button
                    type="button"
                    class="clay-step-nav-btn"
                    :disabled="currentItemIndex === 0"
                    @click="goPrevItem"
                >
                    ← Previous
                </button>

                <div class="step-dots-row">
                    <button
                        v-for="idx in totalItemsCount"
                        :key="idx"
                        type="button"
                        class="clay-step-dot"
                        :class="{ 'is-current': currentItemIndex === idx - 1 }"
                        :aria-label="`Go to item ${idx}`"
                        @click="currentItemIndex = idx - 1"
                    >
                        {{ idx }}
                    </button>
                </div>

                <button
                    v-if="currentItemIndex < totalItemsCount - 1"
                    type="button"
                    class="clay-step-nav-btn nav-btn-next"
                    @click="goNextItem"
                >
                    Next →
                </button>
                <button
                    v-else-if="!existingAttempt"
                    type="button"
                    class="clay-step-nav-btn nav-btn-finish"
                    @click="finishQuizAndShowSummary"
                >
                    Finish Quiz ★
                </button>
                <button
                    v-else
                    type="button"
                    class="clay-step-nav-btn nav-btn-finish is-completed-nav-btn"
                    @click="showCompletionModal = true"
                >
                    View Result ★
                </button>
            </nav>
        </template>
    </main>

        <!-- =========================================================================
             3. CELEBRATION & QUIZ RESULTS SUMMARY MODAL (TELEPORTED TO BODY)
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div
                    v-if="showCompletionModal"
                    class="clay-summary-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="quiz-summary-heading"
                    @click.self="showCompletionModal = false"
                >
                    <div class="clay-summary-modal-card">
                        <button
                            type="button"
                            class="summary-modal-close"
                            aria-label="Close summary modal"
                            @click="showCompletionModal = false"
                        >
                            ✕
                        </button>

                        <!-- 3 Golden Stars Ribbon / Pending Badge -->
                        <div v-if="isAttemptPendingReview" class="summary-pending-row" aria-hidden="true">
                            <div class="summary-pending-tag">
                                <span class="summary-pending-icon">📝</span>
                                <span class="summary-pending-tag-lbl">Score Pending Teacher Review</span>
                            </div>
                        </div>
                        <div v-else class="summary-stars-row" aria-hidden="true">
                            <span
                                v-for="starIdx in 3"
                                :key="starIdx"
                                class="summary-star-badge"
                                :class="{
                                    'star-earned': starIdx <= (serverAttemptResult ? serverAttemptResult.stars : scoreSummary.stars),
                                }"
                            >
                                ★
                            </span>
                        </div>

                        <span
                            class="summary-status-pill"
                            :class="isAttemptPendingReview ? 'status-pending' : ((serverAttemptResult ? serverAttemptResult.is_passed : scoreSummary.passed) ? 'status-pass' : 'status-retry')"
                        >
                            {{
                                isAttemptPendingReview
                                    ? 'SUBMITTED FOR TEACHER GRADING 📝'
                                    : ((serverAttemptResult ? serverAttemptResult.is_passed : scoreSummary.passed)
                                        ? 'QUEST MILESTONE MASTERED!'
                                        : `KEEP PRACTICING • PASSING IS ${scoreSummary.passThreshold}%`)
                            }}
                        </span>

                        <h2 id="quiz-summary-heading" class="summary-modal-title">
                            {{ activeQuiz.title }}
                        </h2>

                        <p v-if="isAttemptPendingReview" class="summary-pending-explainer">
                            Great job expressing your thoughts! Because this challenge asks for your perspective and reasoning, your teacher will evaluate your answers and assign your score and stars shortly.
                        </p>

                        <div class="summary-metrics-grid">
                            <div class="summary-metric-box">
                                <span class="metric-lbl">SCORE</span>
                                <strong class="metric-val" :class="{ 'text-amber': isAttemptPendingReview }">
                                    {{ isAttemptPendingReview ? 'Pending' : `${serverAttemptResult ? serverAttemptResult.percentage : scoreSummary.pct}%` }}
                                </strong>
                            </div>
                            <div class="summary-metric-box">
                                <span class="metric-lbl">POINTS</span>
                                <strong class="metric-val">
                                    {{
                                        isAttemptPendingReview
                                            ? `Teacher Graded`
                                            : (serverAttemptResult
                                                ? `${serverAttemptResult.score} / ${serverAttemptResult.max_score}`
                                                : `${scoreSummary.earned} / ${scoreSummary.total}`)
                                    }}
                                </strong>
                            </div>
                            <div class="summary-metric-box">
                                <span class="metric-lbl">REWARD</span>
                                <strong class="metric-val text-amber">
                                    {{
                                        isAttemptPendingReview
                                            ? 'XP on Grade ⭐'
                                            : `+${serverAttemptResult ? serverAttemptResult.xp_earned : (scoreSummary.passed ? activeQuiz.xp_reward || 150 : 50)} XP`
                                    }}
                                </strong>
                            </div>
                        </div>

                        <!-- Teacher Feedback Note -->
                        <div v-if="latestTeacherFeedback" class="summary-feedback-box">
                            <div class="feedback-badge-row">
                                <span class="feedback-emoji">👨‍🏫</span>
                                <strong class="feedback-badge-lbl">Teacher Feedback:</strong>
                            </div>
                            <p class="feedback-text">"{{ latestTeacherFeedback }}"</p>
                        </div>

                        <div class="summary-modal-actions">
                            <button
                                v-if="!existingAttempt && !isAttemptPendingReview"
                                type="button"
                                class="clay-summary-secondary-btn"
                                @click="resetQuizSession"
                            >
                                Play Again ↻
                            </button>
                            <button
                                type="button"
                                class="clay-summary-primary-btn"
                                @click="goBackToMap"
                            >
                                Back to Adventure Map →
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@600;700;800;900&display=swap');

.clay-student-quiz-shell {
    min-height: 100vh;
    background:
        radial-gradient(circle at 12% 10%, rgba(56, 189, 248, 0.18), transparent 40%),
        radial-gradient(circle at 88% 15%, rgba(250, 204, 21, 0.18), transparent 40%),
        linear-gradient(180deg, #eff6ff 0%, #f8fafc 60%, #fefce8 100%);
    font-family: 'Nunito', sans-serif;
    color: #0f172a;
    padding-bottom: 4rem;
}

/* ==========================================================================
   ALREADY COMPLETED STATE BANNER
   ========================================================================== */
.clay-already-taken-card {
    background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
    border: 3.5px solid #10B981;
    border-radius: 28px;
    padding: 2rem 2.25rem;
    box-shadow:
        0 10px 0 #059669,
        0 20px 38px rgba(5, 150, 105, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin: 1.5rem auto 2rem;
    width: 100%;
}

.already-taken-content {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
    max-width: 720px;
}

.already-taken-badge {
    background: #059669;
    border: 3px solid #FFFFFF;
    border-radius: 20px;
    padding: 0.5rem 0.65rem;
    box-shadow: 0 4px 10px rgba(5, 150, 105, 0.35);
    display: flex;
    gap: 0.2rem;
    font-size: 1.25rem;
    color: #CBD5E1;
}

.already-taken-badge .star-filled {
    color: #FDE047;
    filter: drop-shadow(0 2px 4px rgba(234, 179, 8, 0.4));
}

.already-taken-kicker-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.35rem;
    flex-wrap: wrap;
}

.already-taken-status-pill {
    background: #059669;
    color: #FFFFFF;
    padding: 0.22rem 0.65rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.already-taken-date {
    font-size: 0.78rem;
    font-weight: 700;
    color: #047857;
}

.already-taken-title {
    margin: 0 0 0.4rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #064E3B;
    line-height: 1.25;
}

.already-taken-desc {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.5;
    color: #065F46;
    font-weight: 700;
}

.already-taken-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.clay-taken-review-btn {
    background: linear-gradient(180deg, #F59E0B 0%, #D97706 100%);
    border: 2.5px solid #FFFFFF;
    border-radius: 16px;
    color: #FFFFFF;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.75rem 1.25rem;
    box-shadow: 0 5px 0 #B45309, 0 10px 18px rgba(180, 83, 9, 0.25);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.clay-taken-review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 #B45309, 0 12px 22px rgba(180, 83, 9, 0.3);
}

.clay-taken-back-btn {
    background: #FFFFFF;
    border: 2.5px solid #059669;
    border-radius: 16px;
    color: #047857;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.75rem 1.15rem;
    box-shadow: 0 4px 0 #059669;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.clay-taken-back-btn:hover {
    transform: translateY(-2px);
}

.is-completed-btn {
    background: linear-gradient(180deg, #10B981 0%, #059669 100%) !important;
    box-shadow: 0 5px 0 #047857 !important;
}

.is-completed-nav-btn {
    background: linear-gradient(180deg, #10B981 0%, #059669 100%) !important;
    box-shadow: 0 5px 0 #047857 !important;
}

/* ==========================================================================
   1. STICKY TOP QUIZ HUD
   ========================================================================== */
.clay-quiz-sticky-hud {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    border-bottom: 3px solid #cbd5e1;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    padding: 0.75rem 1.25rem;
}

.quiz-hud-top-row {
    max-width: 1180px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.quiz-hud-brand {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.clay-hud-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #ffffff;
    border: 2.5px solid #94a3b8;
    border-bottom-width: 4.5px;
    border-radius: 14px;
    padding: 0.45rem 0.85rem;
    font-weight: 900;
    font-size: 0.86rem;
    color: #1e293b;
    cursor: pointer;
    transition: transform 0.15s ease, background-color 0.15s ease;
}

.clay-hud-back-btn:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

.hud-icon-svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.quiz-hud-title-group {
    min-width: 0;
}

.quiz-hud-kicker-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.quiz-format-badge {
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 0.7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
}

.quiz-module-kicker {
    font-size: 0.75rem;
    font-weight: 800;
    color: #64748b;
}

.quiz-hud-main-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0.1rem 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Progress Pod */
.quiz-hud-progress-pod {
    flex: 1;
    max-width: 280px;
    min-width: 180px;
}

.progress-pod-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.76rem;
    font-weight: 900;
    color: #475569;
    margin-bottom: 0.25rem;
}

.clay-quiz-progress-track {
    height: 12px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    border: 2px solid #cbd5e1;
}

.clay-quiz-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
    border-radius: 999px;
    transition: width 0.25s ease;
}

/* Right Actions */
.quiz-hud-actions {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.clay-timer-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #fef2f2;
    color: #b91c1c;
    border: 2px solid #fca5a5;
    padding: 0.38rem 0.75rem;
    border-radius: 12px;
    font-weight: 900;
    font-size: 0.84rem;
}

.clay-hint-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #fffbeb;
    color: #b45309;
    border: 2px solid #fcd34d;
    border-bottom-width: 4px;
    padding: 0.42rem 0.8rem;
    border-radius: 13px;
    font-weight: 900;
    font-size: 0.82rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clay-hint-toggle-btn.is-active {
    background: #fde68a;
    border-color: #d97706;
}

.clay-xp-reward-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 2px solid #f59e0b;
    padding: 0.4rem 0.75rem;
    border-radius: 13px;
    font-weight: 900;
    font-size: 0.82rem;
}

.clay-finish-quiz-btn {
    background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: 2px solid #1e40af;
    border-bottom-width: 4.5px;
    border-radius: 14px;
    padding: 0.48rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease, filter 0.15s ease;
}

.clay-finish-quiz-btn:hover {
    filter: brightness(1.06);
    transform: translateY(-1px);
}

/* Format Switcher Strip */
.quiz-hud-format-strip {
    max-width: 1180px;
    margin: 0.65rem auto 0;
    padding-top: 0.55rem;
    border-top: 1.5px dashed #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.format-strip-label {
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}

.format-pills-scroll {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    overflow-x: auto;
    flex: 1;
    padding-bottom: 0.15rem;
}

.clay-format-pill {
    background: #f1f5f9;
    color: #475569;
    border: 2px solid #cbd5e1;
    border-radius: 999px;
    padding: 0.28rem 0.75rem;
    font-size: 0.76rem;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clay-format-pill:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.clay-format-pill.is-active {
    background: #2563eb;
    color: #ffffff;
    border-color: #1d4ed8;
    box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
}

.clay-view-mode-btn {
    background: #ffffff;
    color: #334155;
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.26rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
}

/* ==========================================================================
   2. MAIN STAGE & READING PASSAGE BANNER
   ========================================================================== */
.clay-quiz-main-stage {
    max-width: 1020px;
    margin: 1.4rem auto 0;
    padding: 0 1rem;
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.clay-passage-banner-card {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 3px solid #f59e0b;
    border-bottom-width: 6px;
    border-radius: 24px;
    padding: 1.1rem 1.4rem;
    box-shadow: 0 10px 24px rgba(245, 158, 11, 0.12);
}

.passage-banner-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.passage-title-wrap {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.passage-badge {
    background: #d97706;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
}

.passage-sub {
    font-size: 0.85rem;
    font-weight: 800;
    color: #92400e;
}

.passage-collapse-btn {
    background: rgba(255, 255, 255, 0.8);
    color: #78350f;
    border: 2px solid #f59e0b;
    border-radius: 10px;
    padding: 0.25rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 900;
    cursor: pointer;
}

.passage-banner-body {
    margin-top: 0.8rem;
    padding-top: 0.75rem;
    border-top: 2px dashed rgba(217, 119, 6, 0.3);
}

.passage-scenario-text {
    font-size: 1.05rem;
    line-height: 1.7;
    font-weight: 800;
    color: #78350f;
    margin: 0;
}

.passage-context-hint {
    margin: 0.6rem 0 0;
    font-size: 0.88rem;
    color: #92400e;
    background: rgba(255, 255, 255, 0.7);
    padding: 0.45rem 0.75rem;
    border-radius: 12px;
}

/* Global Hint Callout */
.clay-active-hint-callout {
    background: #eff6ff;
    border: 2.5px solid #60a5fa;
    border-radius: 18px;
    padding: 0.85rem 1.15rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.hint-callout-badge {
    background: #2563eb;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 900;
    padding: 0.2rem 0.55rem;
    border-radius: 8px;
    flex-shrink: 0;
}

.hint-callout-text {
    margin: 0;
    font-size: 0.92rem;
    font-weight: 800;
    color: #1e3a8a;
}

/* ==========================================================================
   INTERACTIVE QUIZ CARDS (SHARED ACROSS ALL 6 FORMATS)
   ========================================================================== */
.quiz-format-stage {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.clay-interactive-card {
    background: #ffffff;
    border: 3px solid #cbd5e1;
    border-bottom-width: 7px;
    border-radius: 28px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
}

.card-top-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.item-step-pill {
    background: #f1f5f9;
    color: #334155;
    font-size: 0.76rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    border: 2px solid #cbd5e1;
}

.item-meta-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.selection-mode-tag {
    background: #ede9fe;
    color: #6d28d9;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
}

.item-points-tag {
    background: #ecfdf5;
    color: #047857;
    border: 1.5px solid #6ee7b7;
    font-size: 0.76rem;
    font-weight: 900;
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
}

.question-excerpt-quote {
    margin: 0 0 0.9rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border-left: 4px solid #3b82f6;
    border-radius: 12px;
    font-style: italic;
    font-weight: 700;
    color: #334155;
}

.question-main-prompt {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    line-height: 1.4;
    color: #0f172a;
    margin: 0 0 1.2rem;
}

/* ==========================================================================
   1. MULTIPLE CHOICE OPTIONS
   ========================================================================== */
.mcq-options-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.clay-mcq-option-btn {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    width: 100%;
    text-align: left;
    background: #f8fafc;
    border: 2.5px solid #cbd5e1;
    border-bottom-width: 5px;
    border-radius: 18px;
    padding: 0.9rem 1.15rem;
    cursor: pointer;
    transition: all 0.16s ease;
}

.clay-mcq-option-btn:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    transform: translateY(-1px);
}

.clay-mcq-option-btn.is-selected {
    background: #dbeafe;
    border-color: #2563eb;
}

.clay-mcq-option-btn.is-correct {
    background: #ecfdf5;
    border-color: #10b981;
}

.clay-mcq-option-btn.is-wrong {
    background: #fef2f2;
    border-color: #ef4444;
}

.mcq-letter-badge {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    background: #ffffff;
    border: 2px solid #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e293b;
    flex-shrink: 0;
}

.is-selected .mcq-letter-badge {
    background: #2563eb;
    border-color: #1d4ed8;
    color: #ffffff;
}

.mcq-option-copy {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.mcq-option-text {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

.mcq-option-feedback {
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
}

.mcq-check-indicator {
    font-weight: 900;
    font-size: 1.1rem;
    color: #2563eb;
}

/* ==========================================================================
   2. COLOR THE CONFLICT SWATCHES
   ========================================================================== */
.conflict-excerpt-canvas {
    background: #f8fafc;
    border: 3px solid #cbd5e1;
    border-radius: 22px;
    padding: 1.25rem 1.4rem;
    margin-bottom: 1.2rem;
    transition: all 0.22s ease;
}

.excerpt-canvas-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.6rem;
}

.excerpt-tag {
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #64748b;
}

.selected-color-chip {
    color: #ffffff;
    font-size: 0.74rem;
    font-weight: 900;
    padding: 0.22rem 0.7rem;
    border-radius: 999px;
}

.excerpt-quote-body {
    font-size: 1.14rem;
    line-height: 1.75;
    font-weight: 800;
    margin: 0;
}

.swatch-picker-heading {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.02rem;
    color: #334155;
    margin: 0 0 0.75rem;
}

.conflict-swatch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 0.7rem;
}

.clay-swatch-btn {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    background: var(--swatch-bg);
    border: 2.5px solid var(--swatch-border);
    border-bottom-width: 5px;
    border-radius: 16px;
    padding: 0.75rem 0.95rem;
    color: var(--swatch-text);
    font-weight: 900;
    font-size: 0.88rem;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.clay-swatch-btn:hover {
    transform: translateY(-2px);
}

.clay-swatch-btn.is-selected {
    border-color: var(--swatch-color);
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.12);
}

.swatch-paint-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
}

/* ==========================================================================
   3. THE CONFLICT MAP & 4. MATCHING TYPE BOARDS
   ========================================================================== */
.conflict-map-board,
.matching-arena-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

@media (max-width: 820px) {
    .conflict-map-board,
    .matching-arena-grid {
        grid-template-columns: 1fr;
    }
}

.map-col-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    color: #1e293b;
    margin: 0 0 0.75rem;
}

.map-events-column,
.map-zones-column,
.matching-col {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.clay-map-event-card,
.clay-matching-item-card {
    background: #f8fafc;
    border: 2.5px solid #cbd5e1;
    border-bottom-width: 5px;
    border-radius: 18px;
    padding: 0.95rem 1.1rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clay-map-event-card.is-selected-for-tap,
.clay-matching-item-card.is-active-item {
    border-color: #2563eb;
    background: #eff6ff;
}

.clay-map-event-card.is-correct,
.clay-matching-item-card.is-correct {
    border-color: #10b981;
    background: #ecfdf5;
}

.clay-map-event-card.is-wrong,
.clay-matching-item-card.is-wrong {
    border-color: #ef4444;
    background: #fef2f2;
}

.event-card-top,
.matching-item-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.45rem;
}

.event-letter-pill,
.item-number-badge {
    background: #1e293b;
    color: #ffffff;
    font-size: 0.74rem;
    font-weight: 900;
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
}

.event-pts-lbl {
    font-size: 0.75rem;
    font-weight: 800;
    color: #64748b;
}

.event-card-body,
.matching-item-desc {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.7rem;
    line-height: 1.5;
}

.event-quick-zone-row,
.matching-letter-pills {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.quick-zone-chip,
.clay-letter-pill {
    background: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.24rem 0.6rem;
    font-size: 0.74rem;
    font-weight: 900;
    color: #334155;
    cursor: pointer;
}

.quick-zone-chip.is-active,
.clay-letter-pill.is-selected {
    background: #2563eb;
    border-color: #1d4ed8;
    color: #ffffff;
}

.clay-map-zone-bucket {
    border: 3px dashed #93c5fd;
    border-radius: 20px;
    padding: 1rem;
    min-height: 130px;
}

.zone-bucket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.65rem;
}

.zone-bucket-title {
    display: block;
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
}

.zone-bucket-sub {
    font-size: 0.76rem;
    font-weight: 700;
    color: #475569;
}

.zone-count-badge {
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 900;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
}

.zone-placed-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.placed-event-chip {
    background: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.45rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.82rem;
    font-weight: 800;
}

.placed-letter {
    background: #2563eb;
    color: #ffffff;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    flex-shrink: 0;
}

.placed-excerpt {
    flex: 1;
}

.placed-remove-btn {
    background: #fee2e2;
    color: #b91c1c;
    border: none;
    border-radius: 6px;
    width: 22px;
    height: 22px;
    cursor: pointer;
    font-weight: 900;
}

.zone-empty-placeholder {
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    text-align: center;
    padding: 0.85rem 0.5rem;
}

.clay-matching-choice-card {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    width: 100%;
    text-align: left;
    background: #ffffff;
    border: 2.5px solid #cbd5e1;
    border-bottom-width: 5px;
    border-radius: 16px;
    padding: 0.85rem 1rem;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.clay-matching-choice-card:hover {
    transform: translateY(-1px);
    border-color: #60a5fa;
}

.choice-letter-circle {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #dbeafe;
    color: #1d4ed8;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.choice-card-text {
    font-size: 0.94rem;
    font-weight: 800;
    color: #1e293b;
}

.matched-preview-chip {
    margin-top: 0.55rem;
    background: #eff6ff;
    color: #1d4ed8;
    padding: 0.35rem 0.65rem;
    border-radius: 10px;
    font-size: 0.8rem;
}

.event-map-feedback {
    margin: 0.5rem 0 0;
    font-size: 0.82rem;
    font-weight: 800;
}

.text-emerald {
    color: #047857;
}

.text-rose {
    color: #be123c;
}

.text-amber {
    color: #d97706;
}

/* ==========================================================================
   5. EVALUATION & 6. REFLECTION STYLES
   ========================================================================== */
.clay-word-helpers-bar {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 3px solid #a78bfa;
    border-radius: 22px;
    padding: 1rem 1.25rem;
}

.word-helpers-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}

.helpers-kicker {
    background: #7c3aed;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 900;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

.helpers-sub {
    font-size: 0.8rem;
    font-weight: 800;
    color: #5b21b6;
}

.word-helpers-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.clay-word-helper-chip {
    background: #ffffff;
    color: #5b21b6;
    border: 2px solid #8b5cf6;
    border-bottom-width: 4px;
    border-radius: 999px;
    padding: 0.32rem 0.8rem;
    font-weight: 900;
    font-size: 0.84rem;
    cursor: pointer;
    transition: transform 0.14s ease;
}

.clay-word-helper-chip:hover {
    transform: translateY(-1px);
    background: #f5f3ff;
}

.eval-step-box {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 18px;
    padding: 1rem 1.15rem;
    margin-bottom: 1rem;
}

.eval-step-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
}

.eval-step-kicker {
    display: block;
    font-size: 0.8rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    margin: 0;
}

.eval-step-guide-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #eff6ff;
    color: #1e40af;
    border: 1.5px solid #bfdbfe;
    border-radius: 999px;
    padding: 0.2rem 0.6rem;
    font-size: 0.74rem;
    font-weight: 800;
}

.guide-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #3b82f6;
}

.sentence-template-builder {
    font-size: 1.08rem;
    font-weight: 800;
    line-height: 2.3;
    color: #0f172a;
}

/* Dynamic Stance Token Slot */
.clay-stance-token-slot {
    display: inline-flex;
    align-items: center;
    vertical-align: middle;
    margin: 0.15rem 0.35rem;
}

.clay-stance-filled-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #166534;
    border: 2px solid #22c55e;
    border-bottom-width: 4.5px;
    border-radius: 12px;
    padding: 0.25rem 0.75rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.98rem;
    font-weight: 700;
    box-shadow: 0 3px 6px rgba(34, 197, 94, 0.25);
    animation: stancePopIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes stancePopIn {
    0% { transform: scale(0.85); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.stance-check-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #16a34a;
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 900;
}

.stance-filled-text {
    font-weight: 700;
    letter-spacing: 0.02em;
}

.clay-stance-empty-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #fef3c7;
    color: #92400e;
    border: 2px dashed #f59e0b;
    border-radius: 12px;
    padding: 0.25rem 0.65rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: default;
}

.stance-empty-icon {
    font-size: 0.95rem;
    animation: bounceFinger 1.6s ease-in-out infinite alternate;
}

@keyframes bounceFinger {
    0% { transform: translateY(0); }
    100% { transform: translateY(-3px); }
}

/* Blank Input Slot */
.clay-blank-input-slot {
    display: inline-flex;
    align-items: center;
    vertical-align: middle;
    margin: 0.15rem 0.35rem;
}

.clay-inline-blank-input {
    min-width: 190px;
    max-width: 360px;
    padding: 0.32rem 0.75rem;
    border: 2.5px solid #3b82f6;
    border-bottom-width: 4.5px;
    border-radius: 12px;
    background: #ffffff;
    font-family: 'Nunito', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #1e3a8a;
    outline: none;
    transition: all 0.18s ease;
    box-shadow: inset 0 2px 4px rgba(30, 58, 138, 0.06);
}

.clay-inline-blank-input:focus,
.clay-inline-blank-input.is-active-focus {
    border-color: #1d4ed8;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.32), inset 0 2px 4px rgba(30, 58, 138, 0.08);
}

.clay-inline-blank-input.is-blank-filled {
    border-color: #60a5fa;
    background: #f8fafc;
}

.reflection-guiding-note {
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 14px;
    padding: 0.65rem 0.95rem;
    color: #166534;
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0 0 0.9rem;
}

.starter-sentence-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 14px;
    padding: 0.6rem 0.9rem;
    margin-bottom: 0.85rem;
}

.starter-sentence-preview {
    font-size: 0.86rem;
    font-weight: 700;
    color: #475569;
}

.clay-starter-insert-btn {
    background: #dbeafe;
    color: #1d4ed8;
    border: 2px solid #60a5fa;
    border-radius: 10px;
    padding: 0.3rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 900;
    cursor: pointer;
}

.clay-reflection-textarea {
    width: 100%;
    box-sizing: border-box;
    border: 2.5px solid #cbd5e1;
    border-radius: 18px;
    padding: 1rem;
    font-family: 'Nunito', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.65;
    color: #0f172a;
    background: #f8fafc;
}

.clay-reflection-textarea:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
}

.reflection-word-meter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.6rem;
    font-size: 0.84rem;
    font-weight: 800;
    color: #475569;
}

.word-goal-chip {
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 900;
}

.word-goal-chip.goal-met {
    background: #dcfce7;
    color: #15803d;
}

.word-goal-chip.goal-pending {
    background: #fef3c7;
    color: #b45309;
}

/* ==========================================================================
   CHECK BUTTON & FEEDBACK BANNERS
   ========================================================================== */
.card-check-row {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 2px dashed #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.clay-check-answer-btn {
    background: linear-gradient(180deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: 2px solid #047857;
    border-bottom-width: 4.5px;
    border-radius: 14px;
    padding: 0.6rem 1.25rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.clay-check-answer-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.feedback-pill-banner {
    flex: 1;
    min-width: 240px;
    padding: 0.7rem 1rem;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    font-size: 0.88rem;
}

.feedback-pill-banner.banner-correct {
    background: #ecfdf5;
    border: 2px solid #34d399;
    color: #065f46;
}

.feedback-pill-banner.banner-incorrect {
    background: #fffbeb;
    border: 2px solid #fbbf24;
    color: #92400e;
}

/* ==========================================================================
   BOTTOM STEPPER NAVIGATION
   ========================================================================== */
.clay-quiz-bottom-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: #ffffff;
    border: 3px solid #cbd5e1;
    border-bottom-width: 6px;
    border-radius: 22px;
    padding: 0.85rem 1.25rem;
}

.clay-step-nav-btn {
    background: #f1f5f9;
    color: #1e293b;
    border: 2px solid #94a3b8;
    border-bottom-width: 4px;
    border-radius: 14px;
    padding: 0.5rem 1.1rem;
    font-weight: 900;
    font-size: 0.88rem;
    cursor: pointer;
}

.clay-step-nav-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.clay-step-nav-btn.nav-btn-next {
    background: #2563eb;
    color: #ffffff;
    border-color: #1d4ed8;
}

.clay-step-nav-btn.nav-btn-finish {
    background: #f59e0b;
    color: #ffffff;
    border-color: #b45309;
}

.step-dots-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.clay-step-dot {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 2px solid #cbd5e1;
    background: #f8fafc;
    font-weight: 900;
    font-size: 0.82rem;
    color: #475569;
    cursor: pointer;
}

.clay-step-dot.is-current {
    background: #2563eb;
    border-color: #1d4ed8;
    color: #ffffff;
}

/* ==========================================================================
   3. COMPLETION SUMMARY MODAL
   ========================================================================== */
.clay-summary-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 90;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.clay-summary-modal-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    border: 4px solid #cbd5e1;
    border-bottom-width: 9px;
    border-radius: 32px;
    padding: 2rem 1.75rem;
    text-align: center;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.35);
}

.summary-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    background: #f8fafc;
    font-weight: 900;
    cursor: pointer;
}

.summary-stars-row {
    display: flex;
    justify-content: center;
    gap: 0.65rem;
    margin-bottom: 0.85rem;
}

.summary-star-badge {
    font-size: 2.6rem;
    color: #cbd5e1;
    text-shadow: 0 3px 0 #94a3b8;
}

.summary-star-badge.star-earned {
    color: #facc15;
    text-shadow: 0 4px 0 #b45309;
}

.summary-status-pill {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    padding: 0.28rem 0.8rem;
    border-radius: 999px;
    margin-bottom: 0.5rem;
}

.summary-status-pill.status-pass {
    background: #dcfce7;
    color: #15803d;
}

.summary-status-pill.status-retry {
    background: #fef3c7;
    color: #b45309;
}

.summary-status-pill.status-pending {
    background: #FEF3C7;
    color: #B45309;
    border: 1px solid #F59E0B;
}

.summary-pending-row {
    display: flex;
    justify-content: center;
    margin-bottom: 0.85rem;
}

.summary-pending-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #FEF3C7;
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.4rem 1.15rem;
}

.summary-pending-icon {
    font-size: 1.3rem;
}

.summary-pending-tag-lbl {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: #92400E;
}

.summary-pending-explainer {
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569;
    line-height: 1.45;
    margin: -0.5rem 0 1rem;
}

.summary-feedback-box {
    background: #FFFBEB;
    border: 2px dashed #F59E0B;
    border-radius: 16px;
    padding: 0.85rem 1rem;
    margin: 1rem 0;
    text-align: left;
}

.clay-already-taken-card.card-pending-review {
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border-color: #F59E0B;
    box-shadow: 0 10px 0 #D97706, 0 20px 38px rgba(217, 119, 6, 0.2);
}

.pending-badge-box {
    background: #D97706 !important;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 68px;
    padding: 0.5rem 0.65rem;
}

.pending-badge-icon {
    font-size: 1.5rem;
}

.pending-badge-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.72rem;
    font-weight: 800;
    color: #FFFFFF;
}

.pill-pending-amber {
    background: #D97706 !important;
}

.card-pending-review .already-taken-title {
    color: #78350F;
}

.card-pending-review .already-taken-desc {
    color: #92400E;
}

.card-pending-review .already-taken-date {
    color: #B45309;
}

.clay-teacher-feedback-box {
    margin-top: 0.85rem;
    background: rgba(255, 255, 255, 0.9);
    border: 2px dashed #F59E0B;
    border-radius: 14px;
    padding: 0.75rem 1rem;
}

.feedback-badge-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.25rem;
}

.feedback-emoji {
    font-size: 1.1rem;
}

.feedback-badge-lbl {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.82rem;
    color: #92400E;
}

.feedback-text {
    margin: 0;
    font-style: italic;
    font-weight: 600;
    color: #334155;
    font-size: 0.9rem;
    line-height: 1.45;
}

.summary-modal-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.55rem;
    color: #0f172a;
    margin: 0 0 1.2rem;
}

.summary-metrics-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.65rem;
    margin-bottom: 1.5rem;
}

.summary-metric-box {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 0.75rem 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.metric-lbl {
    font-size: 0.68rem;
    font-weight: 900;
    color: #64748b;
}

.metric-val {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.15rem;
    color: #0f172a;
}

.summary-modal-actions {
    display: flex;
    gap: 0.75rem;
}

.clay-summary-secondary-btn,
.clay-summary-primary-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border-radius: 16px;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
}

.clay-summary-secondary-btn {
    background: #f1f5f9;
    color: #334155;
    border: 2.5px solid #cbd5e1;
    border-bottom-width: 5px;
}

.clay-summary-primary-btn {
    background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: 2.5px solid #1e40af;
    border-bottom-width: 5px;
}

/* ==========================================================================
   MOBILE VIEW DESIGN & TOUCH OPTIMIZATIONS (<= 640px)
   ========================================================================== */
@media (max-width: 640px) {
    /* 1. COMPACT STICKY HUD */
    .clay-quiz-sticky-hud {
        padding: 0.5rem 0.75rem 0.45rem;
    }

    .quiz-hud-top-row {
        display: grid;
        grid-template-columns: auto 1fr auto;
        grid-template-areas:
            "brand brand actions"
            "progress progress progress";
        align-items: center;
        gap: 0.45rem 0.5rem;
    }

    .quiz-hud-brand {
        grid-area: brand;
        gap: 0.5rem;
        min-width: 0;
    }

    .clay-hud-back-btn {
        padding: 0.35rem 0.6rem;
        font-size: 0.78rem;
        border-radius: 11px;
        min-height: 38px;
    }

    .quiz-hud-kicker-row {
        gap: 0.3rem;
    }

    .quiz-format-badge {
        font-size: 0.62rem;
        padding: 0.1rem 0.45rem;
    }

    .quiz-module-kicker {
        display: none;
    }

    .quiz-hud-main-title {
        font-size: 0.95rem;
        max-width: 175px;
    }

    .quiz-hud-actions {
        grid-area: actions;
        gap: 0.35rem;
        justify-content: flex-end;
    }

    .clay-timer-pill {
        padding: 0.28rem 0.55rem;
        font-size: 0.78rem;
        min-height: 36px;
    }

    .clay-xp-reward-pill {
        display: none;
    }

    .clay-hint-toggle-btn {
        padding: 0.28rem 0.55rem;
        font-size: 0.76rem;
        min-height: 36px;
    }

    .clay-finish-quiz-btn {
        padding: 0.4rem 0.75rem;
        font-size: 0.78rem;
        border-radius: 12px;
        min-height: 36px;
    }

    .quiz-hud-progress-pod {
        grid-area: progress;
        max-width: 100%;
        min-width: 100%;
        margin-top: 0.15rem;
    }

    .progress-pod-labels {
        font-size: 0.7rem;
        margin-bottom: 0.2rem;
    }

    .clay-quiz-progress-track {
        height: 8px;
    }

    /* 2. MAIN STAGE & TOUCH PADDING */
    .clay-student-quiz-shell {
        padding-bottom: 5.5rem;
    }

    .clay-quiz-main-stage {
        margin-top: 0.65rem;
        padding: 0 0.65rem;
        gap: 0.85rem;
    }

    .clay-already-taken-card {
        padding: 1rem;
        gap: 1rem;
        border-radius: 20px;
    }

    .already-taken-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .already-taken-badge {
        font-size: 1.1rem;
        padding: 0.35rem 0.55rem;
    }

    .already-taken-title {
        font-size: 1.15rem;
    }

    .already-taken-desc {
        font-size: 0.86rem;
    }

    .already-taken-actions {
        width: 100%;
        flex-direction: column;
        gap: 0.6rem;
    }

    .clay-taken-review-btn,
    .clay-taken-back-btn {
        width: 100%;
        justify-content: center;
        padding: 0.75rem 1rem;
    }

    .clay-passage-banner-card {
        padding: 0.85rem 1rem;
        border-radius: 18px;
    }

    .passage-body-text {
        font-size: 0.92rem;
        line-height: 1.6;
    }

    .clay-interactive-card {
        padding: 1.1rem 1rem;
        border-radius: 22px;
        border-width: 2.5px;
        border-bottom-width: 5px;
    }

    .question-main-prompt {
        font-size: 1.15rem;
        line-height: 1.45;
        margin-bottom: 0.95rem;
    }

    .question-excerpt-quote {
        padding: 0.6rem 0.75rem;
        font-size: 0.88rem;
        line-height: 1.45;
        margin-bottom: 0.75rem;
    }

    .card-top-meta {
        margin-bottom: 0.75rem;
    }

    .item-step-pill {
        font-size: 0.7rem;
        padding: 0.22rem 0.6rem;
    }

    .item-points-tag {
        font-size: 0.7rem;
        padding: 0.18rem 0.5rem;
    }

    /* 3. MULTIPLE CHOICE */
    .mcq-options-grid {
        gap: 0.6rem;
    }

    .clay-mcq-option-btn {
        padding: 0.75rem 0.85rem;
        gap: 0.65rem;
        min-height: 52px;
        border-radius: 16px;
        border-width: 2px;
        border-bottom-width: 4px;
    }

    .clay-mcq-option-btn:active {
        transform: translateY(2px);
        border-bottom-width: 2px;
    }

    .mcq-letter-badge {
        width: 32px;
        height: 32px;
        font-size: 0.88rem;
        border-radius: 9px;
    }

    .mcq-option-text {
        font-size: 0.94rem;
        line-height: 1.35;
    }

    .clay-check-answer-btn {
        width: 100%;
        padding: 0.85rem 1rem;
        font-size: 0.92rem;
        justify-content: center;
        min-height: 48px;
    }

    /* 4. COLOR THE CONFLICT */
    .conflict-excerpt-canvas {
        padding: 1rem 1.1rem;
        border-radius: 18px;
    }

    .excerpt-quote-body {
        font-size: 1rem;
        line-height: 1.6;
    }

    .conflict-swatch-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.55rem;
    }

    .clay-swatch-btn {
        padding: 0.65rem 0.75rem;
        font-size: 0.8rem;
        gap: 0.45rem;
        border-radius: 14px;
        min-height: 48px;
    }

    .swatch-paint-dot {
        width: 18px;
        height: 18px;
    }

    /* 5. CONFLICT MAP & MATCHING ARENA */
    .clay-map-event-card,
    .clay-matching-item-card {
        padding: 0.8rem 0.85rem;
        border-radius: 16px;
    }

    .event-card-body,
    .matching-item-desc {
        font-size: 0.9rem;
        line-height: 1.45;
    }

    .event-quick-zone-row,
    .matching-letter-pills {
        gap: 0.45rem;
    }

    .quick-zone-chip {
        min-height: 38px;
        padding: 0.38rem 0.75rem;
        font-size: 0.8rem;
        border-radius: 12px;
    }

    .clay-letter-pill {
        min-width: 44px;
        min-height: 44px;
        padding: 0.4rem;
        font-size: 0.95rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .clay-matching-choice-card {
        padding: 0.75rem 0.85rem;
        gap: 0.65rem;
        border-radius: 14px;
    }

    .choice-letter-circle {
        width: 28px;
        height: 28px;
        font-size: 0.85rem;
    }

    .choice-card-text {
        font-size: 0.88rem;
    }

    .clay-map-zone-bucket {
        padding: 0.85rem;
        min-height: 100px;
    }

    /* 6. EVALUATION ARENA */
    .clay-word-helpers-bar {
        padding: 0.85rem 1rem;
        border-radius: 18px;
    }

    .clay-word-helper-chip {
        min-height: 38px;
        padding: 0.4rem 0.85rem;
        font-size: 0.84rem;
        display: inline-flex;
        align-items: center;
    }

    .sentence-template-builder {
        font-size: 0.98rem;
        line-height: 2.3;
    }

    .clay-stance-filled-pill {
        font-size: 0.88rem;
        padding: 0.2rem 0.55rem;
    }

    .clay-stance-empty-pill {
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
    }

    .clay-inline-blank-input {
        min-width: 140px;
        max-width: 100%;
        font-size: 1rem; /* 16px prevents iOS Safari auto-zoom */
        padding: 0.35rem 0.6rem;
    }

    /* 7. REFLECTION ARENA */
    .clay-reflection-textarea {
        font-size: 1rem; /* 16px prevents iOS Safari auto-zoom */
        padding: 0.75rem 0.85rem;
        border-radius: 16px;
    }

    .reflection-word-meter {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.4rem;
    }

    /* 8. FLOATING STICKY THUMB STEPPER NAV */
    .clay-quiz-bottom-nav {
        position: sticky;
        bottom: 0.65rem;
        z-index: 35;
        padding: 0.6rem 0.75rem;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(12px);
        box-shadow:
            0 8px 24px rgba(15, 23, 42, 0.16),
            0 2px 6px rgba(15, 23, 42, 0.08);
        gap: 0.45rem;
        border-width: 2.5px;
        border-bottom-width: 5px;
    }

    .clay-step-nav-btn {
        padding: 0.55rem 0.85rem;
        font-size: 0.82rem;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
    }

    .step-dots-row {
        gap: 0.3rem;
        overflow-x: auto;
        max-width: 135px;
        flex-wrap: nowrap;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }

    .clay-step-dot {
        width: 30px;
        height: 30px;
        font-size: 0.76rem;
        flex-shrink: 0;
        border-radius: 8px;
    }

    /* 9. COMPLETION MODAL */
    .clay-summary-modal-card {
        padding: 1.5rem 1.15rem;
        border-radius: 24px;
        max-height: 88vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .summary-star-badge {
        font-size: 2.1rem;
    }

    .summary-modal-title {
        font-size: 1.25rem;
        margin-bottom: 0.85rem;
    }

    .summary-metrics-grid {
        gap: 0.45rem;
        margin-bottom: 1.2rem;
    }

    .summary-metric-box {
        padding: 0.65rem 0.4rem;
        border-radius: 13px;
    }

    .metric-val {
        font-size: 1rem;
    }

    .summary-modal-actions {
        flex-direction: column;
        gap: 0.6rem;
    }

    .clay-summary-primary-btn,
    .clay-summary-secondary-btn {
        width: 100%;
        padding: 0.8rem 1rem;
        font-size: 0.92rem;
        min-height: 46px;
    }
}

/* ==========================================================================
   EXTRA COMPACT SCREENS (<= 380px, e.g. iPhone SE / Mini)
   ========================================================================== */
@media (max-width: 380px) {
    .quiz-hud-main-title {
        max-width: 120px;
    }

    .clay-step-nav-btn {
        padding: 0.5rem 0.65rem;
        font-size: 0.78rem;
    }

    .step-dots-row {
        max-width: 90px;
    }

    .conflict-swatch-grid {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

