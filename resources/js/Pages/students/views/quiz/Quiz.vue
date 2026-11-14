<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { csrfHeaders } from '@/Services/csrf';
import { markReturnToMapFocus } from '@/Composables/useMapReturnFocus';
import { Head, router } from '@inertiajs/vue3';
import { useSound } from '@/Services/soundManager';
import { flushCacheTags, prefetchRoute } from '@/Composables/usePrefetch';
import { usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';
import RewardCelebrationModal from '@/Components/RewardCelebrationModal.vue';

const {
    playPop,
    playSparkle,
    playSuccess,
    playCorrect,
    playIncorrect,
    playClick,
    playBonus,
} = useSound();

const { activeWorld } = usePixarTheme();
useWorldOnDocument(activeWorld);

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
/**
 * Builds a world-aware pastel card from one saturated hue: pale tinted card in
 * daylight worlds, glowing dark card with light ink in night worlds.
 */
const worldTint = (hue) => ({
    bg: `color-mix(in srgb, ${hue} 12%, var(--px-surface-card))`,
    border: `color-mix(in srgb, ${hue} 55%, var(--px-surface-card))`,
    text: `color-mix(in srgb, ${hue} 42%, var(--px-ink))`,
});

const conflictTypes = [
    {
        value: 'character_vs_character',
        label: 'Character vs. Character',
        short: 'vs. Character',
        swatch: '#EF4444',
        ...worldTint('#EF4444'),
    },
    {
        value: 'character_vs_self',
        label: 'Character vs. Self',
        short: 'vs. Self',
        swatch: '#8B5CF6',
        ...worldTint('#8B5CF6'),
    },
    {
        value: 'character_vs_nature',
        label: 'Character vs. Nature',
        short: 'vs. Nature',
        swatch: '#10B981',
        ...worldTint('#10B981'),
    },
    {
        value: 'character_vs_society',
        label: 'Character vs. Society',
        short: 'vs. Society',
        swatch: '#F59E0B',
        ...worldTint('#F59E0B'),
    },
    {
        value: 'character_vs_technology',
        label: 'Character vs. Technology',
        short: 'vs. Tech',
        swatch: '#0EA5E9',
        ...worldTint('#0EA5E9'),
    },
    {
        value: 'character_vs_supernatural',
        label: 'Character vs. Supernatural',
        short: 'vs. Supernatural',
        swatch: '#EC4899',
        ...worldTint('#EC4899'),
    },
];

const getConflictMeta = (value) => {
    return conflictTypes.find((c) => c.value === value) || conflictTypes[0];
};

const zoneThemeColors = {
    blue: { ...worldTint('#2563EB'), header: '#2563EB' },
    emerald: { ...worldTint('#059669'), header: '#059669' },
    amber: { ...worldTint('#D97706'), header: '#D97706' },
    purple: { ...worldTint('#7C3AED'), header: '#7C3AED' },
    rose: { ...worldTint('#E11D48'), header: '#E11D48' },
    cyan: { ...worldTint('#0891B2'), header: '#0891B2' },
};

const getZoneTheme = (themeKey) => {
    return zoneThemeColors[themeKey] || zoneThemeColors.blue;
};

/* ==========================================================================
   DEMO QUIZZES FOR ALL 6 FORMATS (FALLBACK & FORMAT SWITCHER PREVIEW)
   ========================================================================== */
const fallbackModule = null;
const demoQuizzesByFormat = {};

/* ==========================================================================
   ACTIVE QUIZ RESOLUTION (STRICTLY FROM DATABASE)
   ========================================================================== */
const activeDemoFormat = ref(props.quiz?.type || 'multiple_choice');

const activeModule = computed(() => props.module || {
    id: null,
    title: '',
    slug: '',
    grade_level: '',
    theme: 'forest',
});

const activeQuiz = computed(() => {
    return props.quiz || null;
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
const activeMatchingChoiceLetter = ref(null);

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
/** XP / star deltas the server awarded for the submission made in this session. */
const latestProgression = ref(null);

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
    activeMatchingChoiceLetter.value = null;
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
const dragHoverZoneType = ref(null);
const touchDraggingEventId = ref(null);

const getZoneByConflictType = (conflictType) => {
    return (activeQuiz.value?.zones || []).find((z) => z.conflict_type === conflictType) || null;
};

const getConflictEmoji = (conflictType) => {
    switch (conflictType) {
        case 'character_vs_character':
            return '⚔️';
        case 'character_vs_nature':
            return '🌪️';
        case 'character_vs_self':
            return '🧠';
        case 'character_vs_society':
            return '🏛️';
        default:
            return '🎯';
    }
};

const assignEventToZone = (eventId, zoneConflictType) => {
    playPop(1.2);
    mapAssignments.value = {
        ...mapAssignments.value,
        [eventId]: zoneConflictType,
    };
    mapChecked.value = false;
    selectedMapEventId.value = null;
    dragHoverZoneType.value = null;
    touchDraggingEventId.value = null;
    draggedEventId.value = null;
};

const unassignEvent = (eventId) => {
    const next = { ...mapAssignments.value };
    delete next[eventId];
    mapAssignments.value = next;
    mapChecked.value = false;
};

const handleEventDragStart = (eventId) => {
    draggedEventId.value = eventId;
    selectedMapEventId.value = eventId;
};

const handleZoneDrop = (zoneConflictType) => {
    if (draggedEventId.value !== null) {
        assignEventToZone(draggedEventId.value, zoneConflictType);
        draggedEventId.value = null;
    }
    dragHoverZoneType.value = null;
};

const handleZoneTap = (zoneConflictType) => {
    if (selectedMapEventId.value !== null) {
        assignEventToZone(selectedMapEventId.value, zoneConflictType);
    }
};

const handleTouchStart = (eventId) => {
    touchDraggingEventId.value = eventId;
    selectedMapEventId.value = eventId;
};

const handleTouchMove = (e) => {
    if (!touchDraggingEventId.value || !e.touches || e.touches.length === 0) return;
    if (e.cancelable) {
        e.preventDefault();
    }
    const touch = e.touches[0];
    const el = document.elementFromPoint(touch.clientX, touch.clientY);
    const bucket = el?.closest('[data-zone-type]');
    if (bucket) {
        dragHoverZoneType.value = bucket.getAttribute('data-zone-type');
    } else {
        dragHoverZoneType.value = null;
    }
};

const handleTouchEnd = () => {
    if (touchDraggingEventId.value && dragHoverZoneType.value) {
        assignEventToZone(touchDraggingEventId.value, dragHoverZoneType.value);
    }
    touchDraggingEventId.value = null;
    dragHoverZoneType.value = null;
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
   4. MATCHING TYPE HANDLERS & TABLE COLOR PALETTES
   ========================================================================== */
const matchingChoicePalette = [
    { key: 'coral', ...worldTint('#F43F5E'), badgeBg: '#E11D48', badgeText: '#FFFFFF', ring: '#F43F5E' },
    { key: 'amber', ...worldTint('#F59E0B'), badgeBg: '#D97706', badgeText: '#FFFFFF', ring: '#F59E0B' },
    { key: 'emerald', ...worldTint('#10B981'), badgeBg: '#059669', badgeText: '#FFFFFF', ring: '#10B981' },
    { key: 'sky', ...worldTint('#0EA5E9'), badgeBg: '#0284C7', badgeText: '#FFFFFF', ring: '#0EA5E9' },
    { key: 'purple', ...worldTint('#8B5CF6'), badgeBg: '#7C3AED', badgeText: '#FFFFFF', ring: '#8B5CF6' },
    { key: 'rose', ...worldTint('#EC4899'), badgeBg: '#DB2777', badgeText: '#FFFFFF', ring: '#EC4899' },
    { key: 'indigo', ...worldTint('#6366F1'), badgeBg: '#4F46E5', badgeText: '#FFFFFF', ring: '#6366F1' },
    { key: 'teal', ...worldTint('#14B8A6'), badgeBg: '#0D9488', badgeText: '#FFFFFF', ring: '#14B8A6' },
];

const getChoiceColorByLetter = (letter) => {
    const choices = activeQuiz.value?.choices || [];
    const idx = choices.findIndex((c) => c.choice_letter === letter);
    const safeIdx = idx >= 0 ? idx % matchingChoicePalette.length : 0;
    return matchingChoicePalette[safeIdx];
};

const getChoiceColorByIndex = (idx) => {
    return matchingChoicePalette[idx % matchingChoicePalette.length];
};

const getClueRowStyle = (itemId) => {
    const matchedLetter = matchingSelected.value[itemId];
    if (matchedLetter) {
        const theme = getChoiceColorByLetter(matchedLetter);
        return {
            backgroundColor: theme.bg,
            borderColor: theme.border,
            boxShadow: `0 3px 0 ${theme.border}`,
        };
    }
    return {};
};

const getMatchedBadgeStyle = (letter) => {
    const theme = getChoiceColorByLetter(letter);
    return {
        backgroundColor: theme.badgeBg,
        color: theme.badgeText,
    };
};

const getOptionRowStyle = (choiceLetter, idx) => {
    const theme = getChoiceColorByIndex(idx);
    const isSelected = activeMatchingChoiceLetter.value === choiceLetter;
    return {
        backgroundColor: theme.bg,
        borderColor: isSelected ? theme.ring : theme.border,
        boxShadow: isSelected ? `0 0 0 3px ${theme.ring}, 0 4px 10px rgba(0,0,0,0.06)` : `0 3px 0 ${theme.border}`,
    };
};

const getOptionLetterCircleStyle = (idx) => {
    const theme = getChoiceColorByIndex(idx);
    return {
        backgroundColor: theme.badgeBg,
        color: theme.badgeText,
    };
};

const isChoiceMatched = (choiceLetter) => {
    return Object.values(matchingSelected.value).includes(choiceLetter);
};

const getClueNumberByAssignedChoice = (choiceLetter) => {
    const entry = Object.entries(matchingSelected.value).find(([, letter]) => letter === choiceLetter);
    if (!entry) return null;
    const item = (activeQuiz.value?.matching_items || []).find((i) => i.id === Number(entry[0]));
    return item ? item.item_number : null;
};

const getClueNumberById = (itemId) => {
    const item = (activeQuiz.value?.matching_items || []).find((i) => i.id === itemId);
    return item ? item.item_number : '';
};

const assignMatchingChoice = (itemId, choiceLetter) => {
    playPop(1.2);
    matchingSelected.value = {
        ...matchingSelected.value,
        [itemId]: choiceLetter,
    };
    matchingChecked.value = false;
    activeMatchingItemId.value = null;
    activeMatchingChoiceLetter.value = null;
};

// Tap a clue in Column A
const handleClueTap = (itemId) => {
    // If a choice from Column B was already selected, connect it immediately!
    if (activeMatchingChoiceLetter.value !== null) {
        assignMatchingChoice(itemId, activeMatchingChoiceLetter.value);
        return;
    }

    // Toggle active clue selection
    if (activeMatchingItemId.value === itemId) {
        activeMatchingItemId.value = null;
    } else {
        playPop(1.1);
        activeMatchingItemId.value = itemId;
        activeMatchingChoiceLetter.value = null;
    }
};

// Tap an option in Column B
const handleOptionTap = (choiceLetter) => {
    // If a clue from Column A was already selected, connect it immediately!
    if (activeMatchingItemId.value !== null) {
        assignMatchingChoice(activeMatchingItemId.value, choiceLetter);
        return;
    }

    // Toggle active choice selection
    if (activeMatchingChoiceLetter.value === choiceLetter) {
        activeMatchingChoiceLetter.value = null;
    } else {
        playPop(1.1);
        activeMatchingChoiceLetter.value = choiceLetter;
        activeMatchingItemId.value = null;
    }
};

const unmatchClue = (itemId) => {
    playPop(0.9);
    const next = { ...matchingSelected.value };
    delete next[itemId];
    matchingSelected.value = next;
    matchingChecked.value = false;
    if (activeMatchingItemId.value === itemId) {
        activeMatchingItemId.value = null;
    }
};

const clearMatchingSelection = () => {
    activeMatchingItemId.value = null;
    activeMatchingChoiceLetter.value = null;
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

/**
 * Everything the reward modal shows, taken from the server's attempt + progression
 * (falling back to the local score only in demo mode, where nothing is saved).
 */
const quizReward = computed(() => {
    const attempt = serverAttemptResult.value;
    const progression = latestProgression.value;
    const passThreshold = Number(activeQuiz.value?.passing_percentage || 70);

    if (isAttemptPendingReview.value) {
        return {
            state: 'pending',
            passed: true,
            stars: null,
            starsGained: 0,
            xp: 0,
            level: null,
            totalXp: null,
            leveledUp: false,
            metrics: [{ label: 'SCORE', value: 'Teacher graded' }],
            message: '',
        };
    }

    if (!attempt) {
        const summary = scoreSummary.value;

        return {
            state: 'earned',
            passed: summary.passed,
            stars: summary.stars,
            starsGained: 0,
            xp: summary.passed ? Number(activeQuiz.value?.xp_reward || 150) : 50,
            level: null,
            totalXp: null,
            leveledUp: false,
            metrics: [
                { label: 'SCORE', value: `${summary.pct}%` },
                { label: 'POINTS', value: `${summary.earned} / ${summary.total}` },
            ],
            message: summary.passed ? '' : `Passing is ${passThreshold}%. Keep practicing to earn all 3 stars!`,
        };
    }

    const metrics = [
        { label: 'SCORE', value: `${Math.round(Number(attempt.percentage || 0))}%` },
        { label: 'POINTS', value: `${attempt.score} / ${attempt.max_score}` },
    ];
    const failedNote = attempt.is_passed ? '' : `Passing is ${passThreshold}%. Keep practicing to earn all 3 stars!`;

    if (progression) {
        const gainedSomething = Number(progression.xp_delta) > 0 || Number(progression.star_delta) > 0;

        return {
            state: gainedSomething ? 'earned' : 'repeat',
            passed: Boolean(attempt.is_passed),
            stars: Number(attempt.stars || 0),
            starsGained: Number(progression.star_delta || 0),
            xp: Number(progression.xp_delta || 0),
            level: progression.level ?? null,
            totalXp: progression.total_xp ?? null,
            leveledUp: Boolean(progression.leveled_up),
            metrics,
            message: failedNote,
        };
    }

    return {
        state: 'review',
        passed: Boolean(attempt.is_passed),
        stars: Number(attempt.stars || 0),
        starsGained: 0,
        xp: Number(attempt.xp_earned || 0),
        level: null,
        totalXp: null,
        leveledUp: false,
        metrics,
        message: failedNote,
    };
});

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
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...csrfHeaders(),
        },
        body: JSON.stringify(payload),
    })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok && !data.attempt) {
                throw new Error(data.message || `Quiz submission failed (Status: ${res.status})`);
            }
            return data;
        })
        .then((data) => {
            if (data.attempt) {
                serverAttemptResult.value = data.attempt;
            }
            latestProgression.value = data.success ? data.progression || null : null;
            if (props.module?.slug) {
                const tags = ['student-modules', `module-${props.module.slug}`];
                flushCacheTags([...tags, 'student-classes']);
                prefetchRoute(`/students/modules/${props.module.slug}`, tags, ['15s', '60s']);
            }
            showCompletionModal.value = true;
            playSuccess();
        })
        .catch((err) => {
            console.error('Quiz submission error:', err);
            alert(err.message || 'Unable to submit your quiz. Please refresh the page and try again.');
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
    markReturnToMapFocus();

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

const isLeavingToMap = ref(false);

/** Closing the reward card (✕, backdrop or Escape) leaves for the map so a finished quiz can't be retaken. */
const closeRewardCardToMap = () => {
    if (isLeavingToMap.value) {
        return;
    }

    isLeavingToMap.value = true;
    showCompletionModal.value = false;
    goBackToMap();
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && showCompletionModal.value) {
        closeRewardCardToMap();
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

    <div class="clay-student-quiz-shell" :data-pixar-theme="activeWorld">
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
                        <h1 class="quiz-hud-main-title">{{ activeQuiz?.title || 'Quiz Arena' }}</h1>
                    </div>
                </div>

                <!-- Center: Interactive Progress Bar & Step Counter (Only when taking active quiz) -->
                <div v-if="!existingAttempt && activeQuiz && activeItemsList.length > 0" class="quiz-hud-progress-pod">
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
                        <span>+{{ activeQuiz?.xp_reward || 150 }} XP</span>
                    </div>

                    <button
                        v-if="!existingAttempt && activeQuiz && activeItemsList.length > 0"
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
                <!-- EMPTY STATE WHEN THERE IS NO QUIZ OR QUESTIONS IN THE DATABASE -->
                <section v-if="!activeQuiz || activeItemsList.length === 0" class="clay-empty-quiz-card">
                    <div class="empty-quiz-content">
                        <span class="empty-quiz-icon" aria-hidden="true">🎮</span>
                        <h2 class="empty-quiz-title">No Quiz Questions Yet</h2>
                        <p class="empty-quiz-desc">
                            This quiz challenge currently has no questions published in the database. Please check back once questions are added!
                        </p>
                        <button type="button" class="clay-taken-back-btn" @click="goBackToMap">
                            🗺️ Back to Expedition Map
                        </button>
                    </div>
                </section>

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
                                <!-- <span class="selection-mode-tag">
                                    {{
                                        question.selection_type === 'multiple'
                                            ? 'Select ALL that apply'
                                            : 'Pick ONE best answer'
                                    }}
                                </span> -->
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

                        <!-- <h3 class="swatch-picker-heading">
                            Tap a Conflict Color Swatch to paint this scene:
                        </h3> -->

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
                            <!-- <button
                                type="button"
                                class="clay-check-answer-btn"
                                :disabled="!colorSelected[card.id]"
                                @click="checkConflictCard(card)"
                            >
                                Verify Conflict Color ✓
                            </button> -->

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
                <div class="clay-interactive-card conflict-map-stage-card">
                    <div class="card-top-meta">
                        <span class="item-step-pill">
                            Interactive Conflict Map • {{ answeredItemsCount }}/{{ (activeQuiz.events || []).length }} Sorted
                        </span>
                        <span class="selection-mode-tag">
                            Grab event card below & drag up into bucket (or tap card, then tap bucket)
                        </span>
                    </div>

                    <!-- 1. TOP HORIZONTALLY ALIGNED DROP ZONES AREA -->
                    <div class="conflict-drop-zone-top-section">
                        <div class="drop-zones-section-header">
                            <h3 class="map-col-title-compact">
                                <span class="header-icon">🎯</span>
                                <span>Conflict Zone Buckets</span>
                            </h3>
                            <span class="drop-zones-sub-lbl">
                                {{ selectedMapEventId ? 'Card selected! Tap a bucket to place' : 'Drop cards here' }}
                            </span>
                        </div>

                        <div class="map-zones-horizontal-row">
                            <div
                                v-for="zone in activeQuiz.zones"
                                :key="zone.id || zone.conflict_type"
                                class="clay-map-zone-bucket"
                                :class="{
                                    'is-drag-over': dragHoverZoneType === zone.conflict_type,
                                    'is-tap-target': Boolean(selectedMapEventId),
                                }"
                                :data-zone-type="zone.conflict_type"
                                :style="{
                                    backgroundColor: getZoneTheme(zone.color_theme).bg,
                                    borderColor: dragHoverZoneType === zone.conflict_type ? '#2563eb' : getZoneTheme(zone.color_theme).border,
                                }"
                                @dragover.prevent="dragHoverZoneType = zone.conflict_type"
                                @dragleave="dragHoverZoneType === zone.conflict_type ? dragHoverZoneType = null : null"
                                @drop.prevent="handleZoneDrop(zone.conflict_type)"
                                @click="handleZoneTap(zone.conflict_type)"
                            >
                                <div class="zone-bucket-header">
                                    <div class="zone-header-top-row">
                                        <span class="zone-type-emoji-pill" aria-hidden="true">
                                            {{ getConflictEmoji(zone.conflict_type) }}
                                        </span>
                                        <span
                                            class="zone-count-badge"
                                            :style="{ backgroundColor: getZoneTheme(zone.color_theme).header }"
                                        >
                                            {{ getZoneEvents(zone.conflict_type).length }}
                                        </span>
                                    </div>
                                    <strong
                                        class="zone-bucket-title"
                                        :style="{ color: getZoneTheme(zone.color_theme).text }"
                                        :title="zone.label + (zone.subtitle ? ' — ' + zone.subtitle : '')"
                                    >
                                        {{ zone.label }}
                                    </strong>
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
                                        Drop here 🎯
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. BOTTOM EVENT CARDS AREA (WITHOUT event-quick-zone-row) -->
                    <div class="conflict-events-bottom-section">
                        <div class="events-section-header">
                            <h3 class="map-col-title-compact">
                                <span class="header-icon">📜</span>
                                <span>Story Event Cards</span>
                            </h3>
                            <span class="events-status-lbl">
                                Grab & drag any card up into a bucket
                            </span>
                        </div>

                        <div class="map-events-cards-grid">
                            <div
                                v-for="ev in activeQuiz.events"
                                :key="ev.id"
                                class="clay-map-event-card"
                                :class="{
                                    'is-selected-for-tap': selectedMapEventId === ev.id,
                                    'is-assigned': Boolean(mapAssignments[ev.id]),
                                    'is-touch-dragging': touchDraggingEventId === ev.id,
                                    'is-correct':
                                        mapChecked && mapAssignments[ev.id] === ev.target_conflict_type,
                                    'is-wrong':
                                        mapChecked &&
                                        mapAssignments[ev.id] &&
                                        mapAssignments[ev.id] !== ev.target_conflict_type,
                                }"
                                draggable="true"
                                @dragstart="handleEventDragStart(ev.id)"
                                @dragend="draggedEventId = null"
                                @touchstart.passive="handleTouchStart(ev.id)"
                                @touchmove.prevent="handleTouchMove($event)"
                                @touchend="handleTouchEnd"
                                @touchcancel="handleTouchEnd"
                                @click="selectedMapEventId = selectedMapEventId === ev.id ? null : ev.id"
                            >
                                <div class="event-card-top">
                                    <div class="event-card-badge-row">
                                        <span class="event-letter-pill">Event {{ ev.card_letter }}</span>
                                        <span class="event-drag-handle-hint" title="Grab & Drag">⠿ Drag</span>
                                    </div>
                                    <span class="event-pts-lbl">{{ ev.points || 10 }} pts</span>
                                </div>

                                <p class="event-card-body">{{ ev.event_text }}</p>

                                <!-- Card Assignment Status Bar (Replaces event-quick-zone-row) -->
                                <div class="event-card-status-bar">
                                    <div v-if="mapAssignments[ev.id]" class="event-assigned-indicator">
                                        <span class="assigned-dot">●</span>
                                        <span class="assigned-text">
                                            Placed in <strong>{{ getZoneByConflictType(mapAssignments[ev.id])?.label || 'Bucket' }}</strong>
                                        </span>
                                        <button
                                            type="button"
                                            class="event-card-unassign-btn"
                                            title="Remove from bucket"
                                            @click.stop="unassignEvent(ev.id)"
                                        >
                                            Remove ✕
                                        </button>
                                    </div>
                                    <div v-else class="event-unassigned-hint">
                                        <span class="drag-icon-hand">✋</span>
                                        <span>Grab & drag up to bucket</span>
                                    </div>
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
                    </div>

                    <div class="card-check-row">
                        <!-- <button
                            type="button"
                            class="clay-check-answer-btn"
                            :disabled="answeredItemsCount === 0"
                            @click="checkConflictMap"
                        >
                            Check Conflict Map ✓
                        </button> -->
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 4: MATCHING TYPE (COLUMN A DESCRIPTIONS ↔ COLUMN B CHOICES)
                 ===================================================================== -->
            <section v-else-if="quizType === 'matching_type'" class="quiz-format-stage">
                <div class="clay-interactive-card matching-interactive-card">
                    <div class="card-top-meta">
                        <span class="item-step-pill">
                            Matching Table • {{ answeredItemsCount }}/{{ (activeQuiz.matching_items || []).length }} Connected
                        </span>
                        <span class="selection-mode-tag">
                            Tap an answer on the right, then tap its clue on the left — or vice versa!
                        </span>
                    </div>

                    <!-- Dynamic Match Instruction Strip -->
                    <div class="matching-instruction-strip">
                        <span class="matching-guide-step">
                            <template v-if="activeMatchingChoiceLetter">
                                👉 Option <strong>[{{ activeMatchingChoiceLetter }}]</strong> selected! Now tap its matching clue in Column A.
                            </template>
                            <template v-else-if="activeMatchingItemId">
                                👉 Clue <strong>#{{ getClueNumberById(activeMatchingItemId) }}</strong> selected! Now tap its answer in Column B.
                            </template>
                            <template v-else>
                                💡 Tap an option in Column B then tap its clue in Column A (or tap Column A first)!
                            </template>
                        </span>
                        <button
                            v-if="activeMatchingItemId || activeMatchingChoiceLetter"
                            type="button"
                            class="matching-cancel-selection-btn"
                            @click="clearMatchingSelection"
                        >
                            Cancel Selection ✕
                        </button>
                    </div>

                    <!-- Structured Dual-Column Matching Table -->
                    <div class="matching-table-wrap">
                        <div class="matching-table-grid">
                            <!-- Table Header -->
                            <div class="matching-table-header col-a-header">
                                <span class="table-col-badge">Column A</span>
                                <span class="table-col-title">Clues & Descriptions</span>
                            </div>
                            <div class="matching-table-header col-b-header">
                                <span class="table-col-badge">Column B</span>
                                <span class="table-col-title">Answer Options</span>
                            </div>

                            <!-- Column A: Clues Lane -->
                            <div class="matching-col-lane lane-clues">
                                <div
                                    v-for="item in activeQuiz.matching_items"
                                    :key="item.id"
                                    class="clay-matching-table-row clue-row"
                                    :class="{
                                        'is-active-item': activeMatchingItemId === item.id,
                                        'is-matched': Boolean(matchingSelected[item.id]),
                                        'is-target-ready': Boolean(activeMatchingChoiceLetter),
                                        'is-correct': matchingChecked && matchingSelected[item.id] === item.correct_choice_letter,
                                        'is-wrong': matchingChecked && matchingSelected[item.id] && matchingSelected[item.id] !== item.correct_choice_letter,
                                    }"
                                    :style="getClueRowStyle(item.id)"
                                    @click="handleClueTap(item.id)"
                                >
                                    <div class="clue-row-top">
                                        <span class="clue-num-pill">#{{ item.item_number }}</span>
                                        <div
                                            v-if="matchingSelected[item.id]"
                                            class="clue-matched-badge-pill"
                                            :style="getMatchedBadgeStyle(matchingSelected[item.id])"
                                        >
                                            <span>Matched <strong>[{{ matchingSelected[item.id] }}]</strong></span>
                                            <button
                                                type="button"
                                                class="clue-unmatch-btn"
                                                title="Clear match"
                                                aria-label="Clear match"
                                                @click.stop="unmatchClue(item.id)"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <span v-else class="clue-unmatched-status">
                                            {{ activeMatchingChoiceLetter ? 'Tap to connect 👈' : 'Unmatched' }}
                                        </span>
                                    </div>

                                    <p class="clue-text">{{ item.description }}</p>

                                    <p
                                        v-if="matchingChecked && matchingSelected[item.id]"
                                        class="event-map-feedback"
                                        :class="matchingSelected[item.id] === item.correct_choice_letter ? 'text-emerald' : 'text-rose'"
                                    >
                                        {{ matchingSelected[item.id] === item.correct_choice_letter ? `✓ Correct! ${item.explanation || ''}` : `✕ Hint: ${item.hint || 'Try another answer.'}` }}
                                    </p>
                                </div>
                            </div>

                            <!-- Column B: Colored Answer Options Lane -->
                            <div class="matching-col-lane lane-options">
                                <div
                                    v-for="(choice, cIdx) in activeQuiz.choices"
                                    :key="choice.id || choice.choice_letter"
                                    class="clay-matching-table-row option-row"
                                    :class="{
                                        'is-active-choice': activeMatchingChoiceLetter === choice.choice_letter,
                                        'is-used-by-clue': isChoiceMatched(choice.choice_letter),
                                        'is-target-ready': Boolean(activeMatchingItemId),
                                    }"
                                    :style="getOptionRowStyle(choice.choice_letter, cIdx)"
                                    @click="handleOptionTap(choice.choice_letter)"
                                >
                                    <div class="option-row-header">
                                        <span class="option-letter-circle" :style="getOptionLetterCircleStyle(cIdx)">
                                            {{ choice.choice_letter }}
                                        </span>
                                        <span v-if="isChoiceMatched(choice.choice_letter)" class="option-used-indicator">
                                            ✓ On Clue #{{ getClueNumberByAssignedChoice(choice.choice_letter) }}
                                        </span>
                                        <span v-else-if="activeMatchingItemId" class="option-tap-prompt">
                                            Tap to connect 👉
                                        </span>
                                    </div>
                                    <span class="option-text">{{ choice.choice_text }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =====================================================================
                 FORMAT 5: EVALUATION (WORD HELPERS + STEP 1 STANCE + STEP 2 BLANKS)
                 ===================================================================== -->
            <section v-else-if="quizType === 'evaluation'" class="quiz-format-stage eval-quiz-stage">
                <!-- Shared Word Helpers Bank -->
                <div
                    v-if="activeQuiz.word_helpers && activeQuiz.word_helpers.length > 0"
                    class="clay-word-helpers-bar"
                >
                    <div class="word-helpers-head">
                        <span class="helpers-kicker">💡 WORD BANK</span>
                        <span class="helpers-sub">Tap word to insert into active blank</span>
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
                    class="clay-interactive-card eval-interactive-card"
                >
                    <div v-if="item">
                        <div class="card-top-meta">
                            <span class="item-step-pill">{{ item.section_title || `Evaluation ${eIdx + 1}` }}</span>
                            <span class="item-points-tag">{{ item.points || 20 }} pts</span>
                        </div>

                        <h2 class="question-main-prompt eval-main-prompt">{{ item.question_prompt }}</h2>

                        <!-- Step 1: Pick Stance Option -->
                        <div class="eval-step-box eval-step-one">
                            <div class="eval-step-head">
                                <span class="eval-step-badge step-1-badge">STEP 1</span>
                                <span class="eval-step-kicker">{{ item.step1_prompt || 'Pick your stance:' }}</span>
                            </div>

                            <div class="eval-options-grid">
                                <button
                                    v-for="(opt, oIdx) in item.options"
                                    :key="opt.id || oIdx"
                                    type="button"
                                    class="clay-eval-option-btn"
                                    :class="{
                                        'is-selected': ensureEvaluationState(item).optionId === opt.id,
                                    }"
                                    @click="selectEvaluationOption(item, opt.id)"
                                >
                                    <span class="eval-letter-badge">{{ String.fromCharCode(65 + oIdx) }}</span>
                                    <span class="eval-option-text">{{ opt.option_text }}</span>
                                    <span class="eval-option-indicator" aria-hidden="true">
                                        <span v-if="ensureEvaluationState(item).optionId === opt.id" class="eval-indicator-check">✓</span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Interactive Sentence Template with Auto-Filled Stance & Inline Blanks -->
                        <div class="eval-step-box eval-step-two">
                            <div class="eval-step-header-row">
                                <div class="eval-step-head">
                                    <span class="eval-step-badge step-2-badge">STEP 2</span>
                                    <span class="eval-step-kicker">Complete reasoning:</span>
                                </div>
                                <span class="eval-step-guide-pill">
                                    <span class="guide-dot" aria-hidden="true"></span>
                                    <span>Stance auto-fills • Fill blanks</span>
                                </span>
                            </div>

                            <div class="sentence-template-canvas">
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
                                                :placeholder="`Type answer...`"
                                                :aria-label="`Answer blank ${segment.blankIdx + 1}`"
                                                :value="ensureEvaluationState(item).blanks[segment.blankIdx] || ''"
                                                @focus="focusedBlankInfo = { itemId: item.id, blankIdx: segment.blankIdx }"
                                                @input="updateEvaluationBlank(item, segment.blankIdx, $event.target.value)"
                                            />
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="card-check-row">
                            <!-- <button
                                type="button"
                                class="clay-check-answer-btn"
                                @click="checkEvaluationItem(item)"
                            >
                                Check Evaluation ✓
                            </button> -->

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
                            <!-- <button
                                type="button"
                                class="clay-check-answer-btn"
                                @click="checkReflectionPrompt(prompt)"
                            >
                                Save & Verify Reflection ✓
                            </button> -->

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
        </template>
    </main>

        <!-- =========================================================================
             3. QUIZ REWARD CELEBRATION (shared Pixar reward modal)
             ========================================================================= -->
        <RewardCelebrationModal
            :open="showCompletionModal"
            kind="quiz"
            :state="quizReward.state"
            :item-title="activeQuiz.title"
            :passed="quizReward.passed"
            :stars="quizReward.stars"
            :stars-gained="quizReward.starsGained"
            :xp-amount="quizReward.xp"
            :level="quizReward.level"
            :total-xp="quizReward.totalXp"
            :leveled-up="quizReward.leveledUp"
            :metrics="quizReward.metrics"
            :message="quizReward.message"
            primary-label="Back to Adventure Map →"
            :secondary-label="!existingAttempt && !isAttemptPendingReview && !serverAttemptResult ? 'Play Again ↻' : ''"
            @primary="goBackToMap"
            @secondary="resetQuizSession"
            @close="closeRewardCardToMap"
        >
            <div v-if="latestTeacherFeedback" class="summary-feedback-box">
                <div class="feedback-badge-row">
                    <span class="feedback-emoji">👨‍🏫</span>
                    <strong class="feedback-badge-lbl">Teacher Feedback:</strong>
                </div>
                <p class="feedback-text">"{{ latestTeacherFeedback }}"</p>
            </div>
        </RewardCelebrationModal>
    </div>
</template>

<style scoped>
.clay-student-quiz-shell {
    min-height: 100vh;
    min-height: 100dvh;
    background:
        radial-gradient(ellipse 60% 52% at 20% 14%, var(--px-sun-bloom, rgba(56, 189, 248, 0.18)) 0%, transparent 68%),
        radial-gradient(ellipse 52% 48% at 82% 24%, var(--px-secondary-bloom, rgba(250, 204, 21, 0.18)) 0%, transparent 70%),
        linear-gradient(166deg, var(--px-sky-top, #eff6ff) 0%, var(--px-sky-mid, #f8fafc) 54%, var(--px-sky-bottom, #fefce8) 100%);
    font-family: var(--px-font-body, 'Nunito', sans-serif);
    color: var(--px-ink, #0f172a);
    padding-bottom: 4rem;
    transition: background 380ms var(--px-ease-settle, ease);
}

/* ==========================================================================
   ALREADY COMPLETED STATE BANNER
   ========================================================================== */
.clay-already-taken-card {
    background: linear-gradient(135deg, color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-success-bg) 100%);
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
    border: 3px solid var(--px-card-border);
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
    color: var(--px-tint-success-ink);
}

.already-taken-title {
    margin: 0 0 0.4rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--px-tint-success-ink);
    line-height: 1.25;
}

.already-taken-desc {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.5;
    color: var(--px-tint-success-ink);
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
    border: 2.5px solid var(--px-card-border);
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
    background: var(--px-surface-card);
    border: 2.5px solid #059669;
    border-radius: 16px;
    color: var(--px-tint-success-ink);
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
    background: linear-gradient(180deg, var(--px-surface-card, rgba(255, 255, 255, 0.94)) 0%, var(--px-surface-card-bottom, rgba(248, 250, 252, 0.94)) 100%);
    backdrop-filter: blur(12px);
    border-bottom: 3px solid var(--px-card-border, #cbd5e1);
    box-shadow: 0 8px 24px rgba(var(--px-shadow-rgb, 15, 23, 42), 0.12);
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
    background: var(--px-surface-elevated, #ffffff);
    border: 2.5px solid var(--px-card-border, #94a3b8);
    border-bottom-width: 4.5px;
    border-radius: 14px;
    padding: 0.45rem 0.85rem;
    font-weight: 900;
    font-size: 0.86rem;
    color: var(--px-ink, #1e293b);
    cursor: pointer;
    transition: transform 0.15s ease, background-color 0.15s ease;
}

.clay-hud-back-btn:hover {
    background: var(--px-surface-well, #f1f5f9);
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
    background: var(--px-tint-info-bg);
    color: var(--px-tint-info-ink);
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
    color: var(--px-ink-muted, #64748b);
}

.quiz-hud-main-title {
    font-family: var(--px-font-display, 'Fredoka', sans-serif);
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--px-ink, #0f172a);
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
    color: var(--px-ink-muted, #475569);
    margin-bottom: 0.25rem;
}

.clay-quiz-progress-track {
    height: 12px;
    background: var(--px-track);
    border-radius: 999px;
    overflow: hidden;
    border: 2px solid var(--px-line);
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
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-danger-ink);
    border: 2px solid var(--px-tint-danger-line);
    padding: 0.38rem 0.75rem;
    border-radius: 12px;
    font-weight: 900;
    font-size: 0.84rem;
}

.clay-hint-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-warn-ink);
    border: 2px solid var(--px-tint-warn-line);
    border-bottom-width: 4px;
    padding: 0.42rem 0.8rem;
    border-radius: 13px;
    font-weight: 900;
    font-size: 0.82rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clay-hint-toggle-btn.is-active {
    background: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-tint-warn-line));
    border-color: #d97706;
}

.clay-xp-reward-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: linear-gradient(135deg, var(--px-tint-warn-bg) 0%, color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-tint-warn-line)) 100%);
    color: var(--px-tint-warn-ink);
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
    border-top: 1.5px dashed var(--px-line);
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
    color: var(--px-ink-subtle, #64748b);
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
    background: var(--px-surface-well, #f1f5f9);
    color: var(--px-ink-muted, #475569);
    border: 2px solid var(--px-card-border, #cbd5e1);
    border-radius: 999px;
    padding: 0.28rem 0.75rem;
    font-size: 0.76rem;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clay-format-pill:hover {
    background: var(--px-surface-elevated, #e2e8f0);
    color: var(--px-ink, #0f172a);
}

.clay-format-pill.is-active {
    background: linear-gradient(180deg, var(--px-secondary-lit, #3b82f6) 0%, var(--px-secondary, #2563eb) 100%);
    color: var(--px-secondary-ink, #ffffff);
    border-color: var(--px-card-border, #1d4ed8);
    box-shadow: 0 3px 10px var(--px-secondary-glow, rgba(37, 99, 235, 0.25));
}

.clay-view-mode-btn {
    background: var(--px-surface-card);
    color: var(--px-ink);
    border: 2px solid var(--px-line);
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
    background: linear-gradient(135deg, color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-warn-bg) 100%);
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
    color: var(--px-tint-warn-ink);
}

.passage-collapse-btn {
    background: color-mix(in srgb, var(--px-surface-card) 80%, transparent);
    color: var(--px-tint-warn-ink);
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
    color: var(--px-tint-warn-ink);
    margin: 0;
}

.passage-context-hint {
    margin: 0.6rem 0 0;
    font-size: 0.88rem;
    color: var(--px-tint-warn-ink);
    background: color-mix(in srgb, var(--px-surface-card) 70%, transparent);
    padding: 0.45rem 0.75rem;
    border-radius: 12px;
}

/* Global Hint Callout */
.clay-active-hint-callout {
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
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
    color: var(--px-tint-info-ink);
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
    background: var(--px-surface-card);
    border: 3px solid var(--px-line);
    border-bottom-width: 7px;
    border-radius: 28px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 14px 32px rgba(var(--px-shadow-rgb), 0.07);
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
    background: var(--px-surface-soft);
    color: var(--px-ink);
    font-size: 0.76rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    border: 2px solid var(--px-line);
}

.item-meta-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.selection-mode-tag {
    background: var(--px-tint-magic-bg);
    color: var(--px-tint-magic-ink);
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
}

.item-points-tag {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-success-ink);
    border: 1.5px solid var(--px-tint-success-line);
    font-size: 0.76rem;
    font-weight: 900;
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
}

.question-excerpt-quote {
    margin: 0 0 0.9rem;
    padding: 0.75rem 1rem;
    background: var(--px-surface-soft);
    border-left: 4px solid #3b82f6;
    border-radius: 12px;
    font-style: italic;
    font-weight: 700;
    color: var(--px-ink);
}

.question-main-prompt {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.35rem;
    line-height: 1.4;
    color: var(--px-ink);
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
    background: var(--px-surface-soft);
    border: 2.5px solid var(--px-line);
    border-bottom-width: 5px;
    border-radius: 18px;
    padding: 0.9rem 1.15rem;
    cursor: pointer;
    transition: all 0.16s ease;
}

.clay-mcq-option-btn:hover {
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    border-color: var(--px-tint-info-line);
    transform: translateY(-1px);
}

.clay-mcq-option-btn.is-selected {
    background: var(--px-tint-info-bg);
    border-color: #2563eb;
}

.clay-mcq-option-btn.is-correct {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border-color: #10b981;
}

.clay-mcq-option-btn.is-wrong {
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    border-color: #ef4444;
}

.mcq-letter-badge {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    background: var(--px-surface-card);
    border: 2px solid var(--px-line-strong);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--px-ink);
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
    color: var(--px-ink);
}

.mcq-option-feedback {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.mcq-check-indicator {
    font-weight: 900;
    font-size: 1.1rem;
    color: var(--px-tint-info-ink);
}

/* ==========================================================================
   2. COLOR THE CONFLICT SWATCHES
   ========================================================================== */
.conflict-excerpt-canvas {
    background: var(--px-surface-soft);
    border: 3px solid var(--px-line);
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
    color: var(--px-ink-muted);
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
    color: var(--px-ink);
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
    box-shadow: 0 0 0 3px rgba(var(--px-shadow-rgb), 0.12);
}

.swatch-paint-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid var(--px-card-border);
    box-shadow: 0 2px 5px rgba(var(--px-shadow-rgb), 0.15);
    flex-shrink: 0;
}

/* ==========================================================================
   3. THE CONFLICT MAP (TOP HORIZONTAL DROP ZONE & BOTTOM EVENT CARDS)
   ========================================================================== */
.conflict-drop-zone-top-section {
    background: var(--px-surface-soft);
    border: 2px solid var(--px-line);
    border-radius: 20px;
    padding: 0.85rem 1rem;
    margin-bottom: 1.25rem;
}

.drop-zones-section-header,
.events-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.65rem;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.map-col-title-compact {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.98rem;
    color: var(--px-ink);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.drop-zones-sub-lbl,
.events-status-lbl {
    font-size: 0.74rem;
    font-weight: 800;
    color: var(--px-ink-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.map-zones-horizontal-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}

.clay-map-zone-bucket {
    border: 2.5px dashed var(--px-tint-info-line);
    border-radius: 16px;
    padding: 0.75rem 0.85rem;
    min-height: 95px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
}

.clay-map-zone-bucket.is-drag-over {
    border-style: solid !important;
    border-color: #2563eb !important;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 22px rgba(37, 99, 235, 0.22);
    filter: brightness(1.04);
}

.clay-map-zone-bucket.is-tap-target {
    cursor: pointer;
    box-shadow: 0 0 0 2px #3b82f6;
}

.zone-bucket-header {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 0.45rem;
}

.zone-header-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.zone-type-emoji-pill {
    font-size: 0.95rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
}

.zone-bucket-title {
    display: block;
    font-family: 'Fredoka', sans-serif;
    font-size: clamp(0.78rem, 2vw, 0.92rem);
    line-height: 1.25;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
}

.zone-count-badge {
    color: #ffffff;
    font-size: 0.66rem;
    font-weight: 900;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    flex-shrink: 0;
}

.zone-placed-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    max-height: 120px;
    overflow-y: auto;
}

.placed-event-chip {
    background: var(--px-surface-card);
    border: 1.5px solid var(--px-line);
    border-radius: 10px;
    padding: 0.3rem 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.76rem;
    font-weight: 800;
    box-shadow: 0 1px 3px rgba(var(--px-shadow-rgb), 0.05);
}

.placed-letter {
    background: #2563eb;
    color: #ffffff;
    width: 18px;
    height: 18px;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    font-weight: 900;
    flex-shrink: 0;
}

.placed-excerpt {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.74rem;
    color: var(--px-ink);
}

.placed-remove-btn {
    background: var(--px-tint-danger-bg);
    color: var(--px-tint-danger-ink);
    border: none;
    border-radius: 5px;
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: 900;
    font-size: 0.7rem;
    transition: transform 0.1s ease;
}

.placed-remove-btn:hover {
    transform: scale(1.15);
}

.zone-empty-placeholder {
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink-subtle);
    text-align: center;
    padding: 0.55rem 0.35rem;
    border: 1.5px dashed rgba(148, 163, 184, 0.45);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.5);
}

/* Conflict Events Bottom Grid */
.conflict-events-bottom-section {
    margin-top: 0.25rem;
}

.map-events-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.85rem;
}

.clay-map-event-card {
    background: var(--px-surface-soft);
    border: 2.5px solid var(--px-line);
    border-bottom-width: 5px;
    border-radius: 18px;
    padding: 0.85rem 1rem;
    cursor: grab;
    transition: all 0.15s ease;
    user-select: none;
    touch-action: none;
}

.clay-map-event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(var(--px-shadow-rgb), 0.06);
    border-color: var(--px-line-strong);
}

.clay-map-event-card:active {
    cursor: grabbing;
}

.clay-map-event-card.is-selected-for-tap {
    border-color: #2563eb;
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

.clay-map-event-card.is-assigned {
    border-color: var(--px-tint-info-line);
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
}

.clay-map-event-card.is-touch-dragging {
    opacity: 0.9;
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.25);
    border-color: #2563eb;
    z-index: 30;
}

.clay-map-event-card.is-correct {
    border-color: #10b981;
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
}

.clay-map-event-card.is-wrong {
    border-color: #ef4444;
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
}

.event-card-top,
.matching-item-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.45rem;
}

.event-card-badge-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.event-drag-handle-hint {
    font-size: 0.68rem;
    font-weight: 900;
    color: var(--px-ink-muted);
    background: var(--px-track);
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    letter-spacing: 0.03em;
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
    font-size: 0.74rem;
    font-weight: 800;
    color: var(--px-ink-muted);
}

.event-card-body,
.matching-item-desc {
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--px-ink);
    margin: 0 0 0.65rem;
    line-height: 1.45;
}

.event-card-status-bar {
    padding-top: 0.5rem;
    border-top: 1.5px dashed var(--px-line);
}

.event-assigned-indicator {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    font-size: 0.76rem;
    color: var(--px-tint-info-ink);
    background: var(--px-tint-info-bg);
    padding: 0.3rem 0.6rem;
    border-radius: 8px;
    font-weight: 700;
}

.assigned-dot {
    color: var(--px-tint-info-ink);
    font-size: 0.7rem;
}

.assigned-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.event-card-unassign-btn {
    background: var(--px-surface-card);
    color: var(--px-tint-danger-ink);
    border: 1px solid var(--px-tint-danger-line);
    border-radius: 6px;
    padding: 0.15rem 0.45rem;
    font-size: 0.68rem;
    font-weight: 900;
    cursor: pointer;
    flex-shrink: 0;
}

.event-unassigned-hint {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.74rem;
    font-weight: 800;
    color: var(--px-ink-muted);
}

.drag-icon-hand {
    font-size: 0.85rem;
}

/* ==========================================================================
   4. MATCHING TYPE TABLE ARENA (SIDE-BY-SIDE COLOR CONNECT)
   ========================================================================== */
.matching-interactive-card {
    padding: 1.1rem 1.25rem;
}

.matching-instruction-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    border: 2px solid var(--px-tint-info-line);
    border-radius: 14px;
    padding: 0.6rem 0.85rem;
    margin-bottom: 0.95rem;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.matching-guide-step {
    font-size: 0.84rem;
    font-weight: 800;
    color: var(--px-tint-info-ink);
    line-height: 1.35;
}

.matching-cancel-selection-btn {
    background: var(--px-tint-danger-bg);
    color: var(--px-tint-danger-ink);
    border: 1px solid var(--px-tint-danger-line);
    border-radius: 8px;
    padding: 0.22rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.15s ease;
}

.matching-cancel-selection-btn:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

/* Matching Table Wrapper */
.matching-table-wrap {
    background: var(--px-surface-card);
    border: 2.5px solid var(--px-line);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(var(--px-shadow-rgb), 0.04);
}

.matching-table-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-collapse: collapse;
}

.matching-table-header {
    background: var(--px-surface-soft);
    padding: 0.65rem 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 2px solid var(--px-line);
}

.matching-table-header.col-a-header {
    border-right: 2px solid var(--px-line);
}

.table-col-badge {
    background: #1e293b;
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 900;
    padding: 0.18rem 0.55rem;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.table-col-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--px-ink);
}

/* Matching Column Lanes */
.matching-col-lane {
    display: flex;
    flex-direction: column;
    padding: 0.65rem;
    gap: 0.55rem;
}

.matching-col-lane.lane-clues {
    border-right: 2px solid var(--px-line);
    background: var(--px-surface-soft);
}

.matching-col-lane.lane-options {
    background: var(--px-surface-card);
}

/* Interactive Clue & Option Table Rows */
.clay-matching-table-row {
    border-radius: 14px;
    padding: 0.65rem 0.8rem;
    border: 2px solid var(--px-line);
    cursor: pointer;
    transition: all 0.16s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    user-select: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.clay-matching-table-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--px-shadow-rgb), 0.05);
}

/* Clue Row (Column A) */
.clue-row {
    background: var(--px-surface-card);
    border-color: var(--px-line);
    border-bottom-width: 3.5px;
}

.clue-row.is-active-item {
    border-color: #2563eb !important;
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25) !important;
    transform: translateY(-2px);
}

.clue-row.is-target-ready:not(.is-matched) {
    animation: targetPulse 1.8s infinite;
}

@keyframes targetPulse {
    0%, 100% { border-color: var(--px-tint-info-line); }
    50% { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
}

.clue-row-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    margin-bottom: 0.35rem;
    flex-wrap: wrap;
}

.clue-num-pill {
    background: #334155;
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 900;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    flex-shrink: 0;
}

.clue-matched-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 800;
    box-shadow: 0 1px 3px rgba(var(--px-shadow-rgb), 0.1);
}

.clue-unmatch-btn {
    background: rgba(0, 0, 0, 0.18);
    color: #ffffff;
    border: none;
    border-radius: 4px;
    width: 15px;
    height: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.62rem;
    font-weight: 900;
    margin-left: 0.15rem;
    transition: transform 0.1s ease;
}

.clue-unmatch-btn:hover {
    transform: scale(1.2);
    background: rgba(0, 0, 0, 0.35);
}

.clue-unmatched-status {
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-ink-subtle);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.clue-text {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--px-ink);
    margin: 0;
    line-height: 1.35;
}

/* Option Row (Column B) */
.option-row {
    border-bottom-width: 3.5px;
}

.option-row.is-active-choice {
    transform: translateY(-2px) scale(1.02);
    border-width: 2.5px !important;
}

.option-row-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    margin-bottom: 0.3rem;
}

.option-letter-circle {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 0.82rem;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(var(--px-shadow-rgb), 0.1);
}

.option-used-indicator {
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-tint-success-ink);
    background: var(--px-tint-success-bg);
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
}

.option-tap-prompt {
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-tint-info-ink);
    background: var(--px-tint-info-bg);
    padding: 0.12rem 0.4rem;
    border-radius: 6px;
}

.option-text {
    font-size: 0.86rem;
    font-weight: 800;
    line-height: 1.35;
    color: var(--px-ink);
}

.event-map-feedback {
    margin: 0.5rem 0 0;
    font-size: 0.82rem;
    font-weight: 800;
}

.text-emerald {
    color: var(--px-tint-success-ink);
}

.text-rose {
    color: var(--px-tint-danger-ink);
}

/* ==========================================================================
   5. EVALUATION & 6. REFLECTION STYLES
   ========================================================================== */
.eval-quiz-stage {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.clay-word-helpers-bar {
    background: linear-gradient(135deg, color-mix(in srgb, var(--px-tint-magic-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-magic-bg) 100%);
    border: 2.5px solid #a78bfa;
    border-bottom: 4px solid #8b5cf6;
    border-radius: 18px;
    padding: 0.75rem 1rem;
    box-shadow: 0 3px 8px rgba(139, 92, 246, 0.12);
}

.word-helpers-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 0.45rem;
}

.helpers-kicker {
    background: #7c3aed;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 900;
    padding: 0.18rem 0.55rem;
    border-radius: 999px;
    letter-spacing: 0.04em;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.helpers-sub {
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--px-tint-magic-ink);
}

.word-helpers-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.clay-word-helper-chip {
    background: var(--px-surface-card);
    color: var(--px-tint-magic-ink);
    border: 2px solid #8b5cf6;
    border-bottom-width: 3.5px;
    border-radius: 999px;
    padding: 0.28rem 0.75rem;
    font-weight: 900;
    font-size: 0.82rem;
    cursor: pointer;
    transition: transform 0.14s ease, background-color 0.14s ease;
    display: inline-flex;
    align-items: center;
}

.clay-word-helper-chip:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--px-tint-magic-bg) 55%, var(--px-surface-card));
}

.clay-word-helper-chip:active {
    transform: translateY(1px);
}

.eval-interactive-card {
    padding: 1.15rem 1.35rem;
}

.eval-main-prompt {
    font-size: 1.12rem;
    font-weight: 900;
    line-height: 1.35;
    color: var(--px-ink);
    margin: 0.4rem 0 0.85rem;
}

.eval-step-box {
    background: var(--px-surface-soft);
    border: 2px solid var(--px-line);
    border-radius: 16px;
    padding: 0.85rem 1rem;
    margin-bottom: 0.8rem;
}

.eval-step-box.eval-step-one {
    background: var(--px-surface-soft);
    border-color: var(--px-line);
}

.eval-step-box.eval-step-two {
    background: var(--px-surface-card);
    border-color: var(--px-line);
    box-shadow: 0 2px 6px rgba(var(--px-shadow-rgb), 0.03);
}

.eval-step-head {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.eval-step-badge {
    font-size: 0.66rem;
    font-weight: 900;
    padding: 0.16rem 0.5rem;
    border-radius: 7px;
    letter-spacing: 0.05em;
    flex-shrink: 0;
}

.step-1-badge {
    background: #3b82f6;
    color: #ffffff;
}

.step-2-badge {
    background: #8b5cf6;
    color: #ffffff;
}

.eval-step-kicker {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--px-ink);
    margin: 0;
}

.eval-step-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.65rem;
}

.eval-step-guide-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-info-ink);
    border: 1.5px solid var(--px-tint-info-line);
    border-radius: 999px;
    padding: 0.18rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 800;
}

.guide-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #3b82f6;
    flex-shrink: 0;
}

/* Step 1 Options Grid & Clay Option Button */
.eval-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.55rem;
    margin-top: 0.6rem;
}

.clay-eval-option-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    width: 100%;
    text-align: left;
    background: var(--px-surface-card);
    border: 2px solid var(--px-line);
    border-bottom: 4px solid var(--px-line-strong);
    border-radius: 13px;
    padding: 0.55rem 0.75rem;
    min-height: 44px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.clay-eval-option-btn:hover {
    background: var(--px-surface-soft);
    border-color: var(--px-line-strong);
    transform: translateY(-1px);
}

.clay-eval-option-btn.is-selected {
    background: linear-gradient(135deg, color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-info-bg) 100%);
    border-color: #3b82f6;
    border-bottom-color: #1d4ed8;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.16);
}

.eval-letter-badge {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 900;
    background: var(--px-surface-soft);
    color: var(--px-ink-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease;
}

.clay-eval-option-btn.is-selected .eval-letter-badge {
    background: #2563eb;
    color: #ffffff;
}

.eval-option-text {
    font-size: 0.86rem;
    font-weight: 800;
    line-height: 1.25;
    color: var(--px-ink);
    flex: 1;
}

.clay-eval-option-btn.is-selected .eval-option-text {
    color: var(--px-tint-info-ink);
}

.eval-option-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--px-line);
    background: var(--px-surface-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease;
}

.clay-eval-option-btn.is-selected .eval-option-indicator {
    border-color: #2563eb;
    background: #2563eb;
    color: #ffffff;
}

.eval-indicator-check {
    font-size: 0.72rem;
    font-weight: 900;
    line-height: 1;
}

/* Step 2 Sentence Template Canvas & Builder */
.sentence-template-canvas {
    background: linear-gradient(180deg, var(--px-surface-card) 0%, color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)) 100%);
    border: 2px solid var(--px-line);
    border-radius: 14px;
    padding: 0.85rem 1rem;
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.03);
}

.sentence-template-builder {
    font-size: 1rem;
    font-weight: 700;
    line-height: 2.15;
    color: var(--px-ink);
}

.sentence-static-text {
    color: var(--px-ink);
    font-weight: 700;
}

/* Dynamic Stance Token Slot */
.clay-stance-token-slot {
    display: inline-flex;
    align-items: center;
    vertical-align: middle;
    margin: 0.12rem 0.25rem;
}

.clay-stance-filled-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: linear-gradient(135deg, color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-success-bg) 100%);
    color: var(--px-tint-success-ink);
    border: 2px solid #22c55e;
    border-bottom: 3.5px solid #16a34a;
    border-radius: 10px;
    padding: 0.18rem 0.6rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.86rem;
    font-weight: 700;
    box-shadow: 0 2px 5px rgba(34, 197, 94, 0.22);
    animation: stancePopIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes stancePopIn {
    0% { transform: scale(0.88); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.stance-check-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #16a34a;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 900;
}

.stance-filled-text {
    font-weight: 700;
    letter-spacing: 0.01em;
}

.clay-stance-empty-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: var(--px-tint-warn-bg);
    color: var(--px-tint-warn-ink);
    border: 1.5px dashed #f59e0b;
    border-radius: 10px;
    padding: 0.18rem 0.55rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: default;
}

.stance-empty-icon {
    font-size: 0.85rem;
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
    margin: 0.12rem 0.25rem;
    max-width: 100%;
}

.clay-inline-blank-input {
    min-width: 130px;
    max-width: 100%;
    width: auto;
    height: 36px;
    padding: 0.2rem 0.65rem;
    border: 2px solid #3b82f6;
    border-bottom: 3.5px solid #2563eb;
    border-radius: 10px;
    background: var(--px-surface-card);
    font-family: 'Nunito', sans-serif;
    font-size: 1rem; /* 16px prevents iOS Safari auto-zoom */
    font-weight: 800;
    color: var(--px-tint-info-ink);
    outline: none;
    transition: all 0.16s ease;
    box-shadow: inset 0 2px 4px rgba(30, 58, 138, 0.05);
}

.clay-inline-blank-input:focus,
.clay-inline-blank-input.is-active-focus {
    border-color: #1d4ed8;
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.28), inset 0 2px 4px rgba(30, 58, 138, 0.08);
}

.clay-inline-blank-input.is-blank-filled {
    border-color: #60a5fa;
    background: var(--px-surface-soft);
}

.reflection-guiding-note {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border: 2px solid var(--px-tint-success-line);
    border-radius: 14px;
    padding: 0.65rem 0.95rem;
    color: var(--px-tint-success-ink);
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
    background: var(--px-surface-soft);
    border: 2px dashed var(--px-line);
    border-radius: 14px;
    padding: 0.6rem 0.9rem;
    margin-bottom: 0.85rem;
}

.starter-sentence-preview {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.clay-starter-insert-btn {
    background: var(--px-tint-info-bg);
    color: var(--px-tint-info-ink);
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
    border: 2.5px solid var(--px-line);
    border-radius: 18px;
    padding: 1rem;
    font-family: 'Nunito', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.65;
    color: var(--px-ink);
    background: var(--px-surface-soft);
}

.clay-reflection-textarea:focus {
    outline: none;
    border-color: #2563eb;
    background: var(--px-surface-card);
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
    color: var(--px-ink-muted);
}

.word-goal-chip {
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 900;
}

.word-goal-chip.goal-met {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-success-ink);
}

.word-goal-chip.goal-pending {
    background: var(--px-tint-warn-bg);
    color: var(--px-tint-warn-ink);
}

/* ==========================================================================
   CHECK BUTTON & FEEDBACK BANNERS
   ========================================================================== */
.card-check-row {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 2px dashed var(--px-line);
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
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border: 2px solid #34d399;
    color: var(--px-tint-success-ink);
}

.feedback-pill-banner.banner-incorrect {
    background: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card));
    border: 2px solid var(--px-tint-warn-line);
    color: var(--px-tint-warn-ink);
}

/* ==========================================================================
   BOTTOM STEPPER NAVIGATION
   ========================================================================== */
.clay-quiz-bottom-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: var(--px-surface-card);
    border: 3px solid var(--px-line);
    border-bottom-width: 6px;
    border-radius: 22px;
    padding: 0.85rem 1.25rem;
}

.clay-step-nav-btn {
    background: var(--px-surface-soft);
    color: var(--px-ink);
    border: 2px solid var(--px-line-strong);
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
    border: 2px solid var(--px-line);
    background: var(--px-surface-soft);
    font-weight: 900;
    font-size: 0.82rem;
    color: var(--px-ink-muted);
    cursor: pointer;
}

.clay-step-dot.is-current {
    background: #2563eb;
    border-color: #1d4ed8;
    color: #ffffff;
}

.summary-feedback-box {
    background: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card));
    border: 2px dashed #F59E0B;
    border-radius: 16px;
    padding: 0.85rem 1rem;
    margin: 1rem 0;
    text-align: left;
}

.clay-already-taken-card.card-pending-review {
    background: linear-gradient(135deg, color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)) 0%, var(--px-tint-warn-bg) 100%);
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
    color: var(--px-tint-warn-ink);
}

.card-pending-review .already-taken-desc {
    color: var(--px-tint-warn-ink);
}

.card-pending-review .already-taken-date {
    color: var(--px-tint-warn-ink);
}

.clay-teacher-feedback-box {
    margin-top: 0.85rem;
    background: color-mix(in srgb, var(--px-surface-card) 90%, transparent);
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
    color: var(--px-tint-warn-ink);
}

.feedback-text {
    margin: 0;
    font-style: italic;
    font-weight: 600;
    color: var(--px-ink);
    font-size: 0.9rem;
    line-height: 1.45;
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
    .conflict-drop-zone-top-section {
        padding: 0.65rem 0.75rem;
        border-radius: 16px;
        margin-bottom: 0.9rem;
    }

    .map-zones-horizontal-row {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 0.45rem;
    }

    .clay-map-zone-bucket {
        padding: 0.55rem 0.6rem;
        min-height: 90px;
        border-radius: 14px;
        border-width: 2px;
    }

    .zone-type-emoji-pill {
        font-size: 0.85rem;
    }

    .zone-bucket-title {
        font-size: 0.76rem;
        line-height: 1.2;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
    }

    .zone-count-badge {
        font-size: 0.62rem;
        padding: 0.1rem 0.35rem;
    }

    .placed-event-chip {
        padding: 0.22rem 0.35rem;
        font-size: 0.68rem;
        gap: 0.3rem;
    }

    .placed-letter {
        width: 16px;
        height: 16px;
        font-size: 0.62rem;
    }

    .placed-excerpt {
        max-width: 70px;
        font-size: 0.68rem;
    }

    .zone-empty-placeholder {
        font-size: 0.68rem;
        padding: 0.45rem 0.2rem;
    }

    .map-events-cards-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .clay-map-event-card {
        padding: 0.75rem 0.85rem;
        border-radius: 16px;
    }

    .event-card-body {
        font-size: 0.88rem;
        line-height: 1.45;
    }

    /* Matching Table on Mobile */
    .matching-interactive-card {
        padding: 0.65rem 0.5rem;
        border-radius: 18px;
    }

    .matching-instruction-strip {
        padding: 0.45rem 0.65rem;
        gap: 0.35rem;
    }

    .matching-guide-step {
        font-size: 0.74rem;
    }

    .matching-table-wrap {
        border-radius: 14px;
        border-width: 2px;
    }

    .matching-table-header {
        padding: 0.45rem 0.55rem;
        gap: 0.35rem;
    }

    .table-col-badge {
        font-size: 0.62rem;
        padding: 0.12rem 0.35rem;
    }

    .table-col-title {
        font-size: 0.78rem;
    }

    .matching-col-lane {
        padding: 0.35rem;
        gap: 0.4rem;
    }

    .clay-matching-table-row {
        padding: 0.45rem 0.5rem;
        border-radius: 10px;
    }

    .clue-num-pill {
        font-size: 0.65rem;
        padding: 0.1rem 0.35rem;
    }

    .clue-matched-badge-pill {
        font-size: 0.64rem;
        padding: 0.1rem 0.35rem;
    }

    .clue-text,
    .option-text {
        font-size: 0.78rem;
        line-height: 1.3;
    }

    .option-letter-circle {
        width: 22px;
        height: 22px;
        font-size: 0.72rem;
        border-radius: 6px;
    }

    .option-used-indicator,
    .option-tap-prompt {
        font-size: 0.62rem;
        padding: 0.1rem 0.3rem;
    }

    /* 6. EVALUATION ARENA */
    .eval-quiz-stage {
        gap: 0.65rem;
    }

    .clay-word-helpers-bar {
        padding: 0.5rem 0.75rem;
        border-radius: 15px;
        border-width: 2px;
        border-bottom-width: 3.5px;
    }

    .word-helpers-head {
        margin-bottom: 0.35rem;
        gap: 0.35rem;
    }

    .helpers-kicker {
        font-size: 0.64rem;
        padding: 0.14rem 0.45rem;
    }

    .helpers-sub {
        font-size: 0.72rem;
    }

    .word-helpers-chips {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 0.2rem;
        gap: 0.35rem;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .word-helpers-chips::-webkit-scrollbar {
        display: none;
    }

    .clay-word-helper-chip {
        min-height: 32px;
        padding: 0.25rem 0.65rem;
        font-size: 0.76rem;
        white-space: nowrap;
        flex-shrink: 0;
        border-width: 1.5px;
        border-bottom-width: 3px;
    }

    .eval-interactive-card {
        padding: 0.85rem 0.95rem;
        border-radius: 18px;
    }

    .eval-main-prompt {
        font-size: 1rem;
        line-height: 1.35;
        margin: 0.35rem 0 0.65rem;
    }

    .eval-step-box {
        padding: 0.65rem 0.75rem;
        border-radius: 14px;
        margin-bottom: 0.65rem;
    }

    .eval-step-head {
        gap: 0.35rem;
        margin-bottom: 0.45rem;
    }

    .eval-step-badge {
        font-size: 0.62rem;
        padding: 0.12rem 0.4rem;
    }

    .eval-step-kicker {
        font-size: 0.75rem;
    }

    .eval-step-header-row {
        gap: 0.35rem;
        margin-bottom: 0.5rem;
    }

    .eval-step-guide-pill {
        font-size: 0.68rem;
        padding: 0.14rem 0.45rem;
    }

    .eval-options-grid {
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.45rem;
        margin-top: 0.45rem;
    }

    .clay-eval-option-btn {
        padding: 0.45rem 0.65rem;
        min-height: 42px;
        border-radius: 11px;
        gap: 0.45rem;
    }

    .eval-letter-badge {
        width: 24px;
        height: 24px;
        font-size: 0.72rem;
        border-radius: 7px;
    }

    .eval-option-text {
        font-size: 0.8rem;
        line-height: 1.25;
    }

    .eval-option-indicator {
        width: 18px;
        height: 18px;
    }

    .eval-indicator-check {
        font-size: 0.65rem;
    }

    .sentence-template-canvas {
        padding: 0.65rem 0.75rem;
        border-radius: 12px;
    }

    .sentence-template-builder {
        font-size: 0.92rem;
        line-height: 2.0;
    }

    .clay-stance-token-slot {
        margin: 0.1rem 0.2rem;
    }

    .clay-stance-filled-pill {
        font-size: 0.8rem;
        padding: 0.16rem 0.5rem;
        border-radius: 8px;
        border-bottom-width: 3px;
        gap: 0.25rem;
    }

    .stance-check-badge {
        width: 14px;
        height: 14px;
        font-size: 0.6rem;
    }

    .clay-stance-empty-pill {
        font-size: 0.75rem;
        padding: 0.15rem 0.45rem;
        border-radius: 8px;
    }

    .clay-blank-input-slot {
        margin: 0.1rem 0.2rem;
    }

    .clay-inline-blank-input {
        min-width: 110px;
        max-width: 100%;
        height: 34px;
        font-size: 1rem; /* 16px prevents iOS Safari auto-zoom */
        padding: 0.25rem 0.55rem;
        border-radius: 8px;
        border-bottom-width: 3px;
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
        background: color-mix(in srgb, var(--px-surface-card) 96%, transparent);
        backdrop-filter: blur(12px);
        box-shadow:
            0 8px 24px rgba(var(--px-shadow-rgb), 0.16),
            0 2px 6px rgba(var(--px-shadow-rgb), 0.08);
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

/* Empty Quiz State */
.clay-empty-quiz-card {
    background: var(--px-surface-card);
    border: 3px dashed var(--px-line);
    border-radius: 28px;
    padding: 3.5rem 2rem;
    text-align: center;
    max-width: 540px;
    margin: 2rem auto;
    width: 100%;
    box-shadow: 0 16px 36px rgba(var(--px-shadow-rgb), 0.06);
}

.empty-quiz-icon {
    font-size: 3.5rem;
    display: block;
    margin-bottom: 1rem;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
}

.empty-quiz-title {
    font-family: var(--font-heading, 'Fredoka', sans-serif);
    font-size: 1.6rem;
    color: var(--px-ink);
    margin: 0 0 0.75rem 0;
    font-weight: 800;
}

.empty-quiz-desc {
    font-size: 0.95rem;
    color: var(--px-ink-muted);
    line-height: 1.6;
    margin: 0 0 1.75rem 0;
}
</style>

