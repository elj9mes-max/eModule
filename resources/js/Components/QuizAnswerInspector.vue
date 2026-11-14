<script setup>
import { computed } from 'vue';

const props = defineProps({
    answer: {
        type: Object,
        required: true,
    },
});

const conflictCatalog = {
    character_vs_character: {
        label: 'Character vs. Character',
        subtitle: 'Interpersonal conflict between individuals',
        swatch: '#EF4444',
    },
    character_vs_society: {
        label: 'Character vs. Society',
        subtitle: 'Rules, laws, customs & community institutions',
        swatch: '#3B82F6',
    },
    character_vs_nature: {
        label: 'Character vs. Nature',
        subtitle: 'Weather, wilderness & environmental forces',
        swatch: '#10B981',
    },
    character_vs_self: {
        label: 'Character vs. Self',
        subtitle: 'Internal doubts, fear & moral dilemmas',
        swatch: '#F59E0B',
    },
    character_vs_technology: {
        label: 'Character vs. Technology',
        subtitle: 'Struggles with machines, tools, or systems',
        swatch: '#0EA5E9',
    },
    character_vs_supernatural: {
        label: 'Character vs. Supernatural',
        subtitle: 'Forces beyond natural laws',
        swatch: '#EC4899',
    },
};

const getConflictInfo = (typeValue) => {
    if (!typeValue) {
        return { label: 'Unassigned', subtitle: '', swatch: '#94A3B8' };
    }
    if (conflictCatalog[typeValue]) {
        return conflictCatalog[typeValue];
    }
    const formatted = String(typeValue)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
    return { label: formatted, subtitle: '', swatch: '#64748B' };
};

const optionLetter = (index) => {
    return String.fromCharCode(65 + (index % 26));
};

// 1. Multiple Choice Computed Helpers
const mcqSelectedIds = computed(() => {
    const raw = props.answer?.answer_data?.selected_option_ids;
    if (Array.isArray(raw)) {
        return raw.map((id) => Number(id)).filter((id) => !Number.isNaN(id));
    }
    if (props.answer?.item_type === 'multiple_choice' && props.answer?.answer_text) {
        return String(props.answer.answer_text)
            .split(',')
            .map((v) => Number(v.trim()))
            .filter((n) => !Number.isNaN(n) && n > 0);
    }
    return [];
});

const getMcqOptionState = (opt) => {
    const isCorrect = Boolean(opt.is_correct);
    const isSelected = mcqSelectedIds.value.includes(Number(opt.id));

    if (isCorrect && isSelected) {
        return {
            stateClass: 'choice-correct-selected',
            badgeText: 'Correct & Selected',
            kind: 'correct',
            isSelected: true,
        };
    }
    if (isCorrect && !isSelected) {
        return {
            stateClass: 'choice-correct-key',
            badgeText: props.answer?.context?.selection_type === 'multiple' ? 'Correct Answer (Missed)' : 'Correct Answer',
            kind: 'correct',
            isSelected: false,
        };
    }
    if (!isCorrect && isSelected) {
        return {
            stateClass: 'choice-wrong-selected',
            badgeText: "Student's Wrong Answer",
            kind: 'wrong',
            isSelected: true,
        };
    }
    return {
        stateClass: 'choice-neutral',
        badgeText: null,
        kind: 'neutral',
        isSelected: false,
    };
};

// 2. Color the Conflict Computed Helpers
const colorPickedType = computed(() => {
    return props.answer?.answer_data?.conflict_type || props.answer?.answer_text || '';
});

const colorCorrectType = computed(() => {
    return props.answer?.context?.correct_conflict_type || '';
});

const colorOptionsList = computed(() => {
    const base = props.answer?.context?.options?.length
        ? props.answer.context.options
        : Object.entries(conflictCatalog).slice(0, 4).map(([value, meta]) => ({
              value,
              label: meta.label,
              subtitle: meta.subtitle,
          }));

    const list = [...base];
    const extras = [colorCorrectType.value, colorPickedType.value].filter(Boolean);
    for (const val of extras) {
        if (!list.some((item) => item.value === val)) {
            const meta = getConflictInfo(val);
            list.push({
                value: val,
                label: meta.label,
                subtitle: meta.subtitle,
            });
        }
    }
    return list;
});

const getCategoryOptionState = (optValue, correctValue, pickedValue, nounLabel = 'Answer', selectedVerb = 'Selected') => {
    const isCorrect = Boolean(correctValue && optValue === correctValue);
    const isPicked = Boolean(pickedValue && optValue === pickedValue);

    if (isCorrect && isPicked) {
        return {
            stateClass: 'choice-correct-selected',
            badgeText: `Correct & ${selectedVerb}`,
            kind: 'correct',
        };
    }
    if (isCorrect && !isPicked) {
        return {
            stateClass: 'choice-correct-key',
            badgeText: `Correct ${nounLabel}`,
            kind: 'correct',
        };
    }
    if (!isCorrect && isPicked) {
        return {
            stateClass: 'choice-wrong-selected',
            badgeText: `Student's Wrong ${nounLabel}`,
            kind: 'wrong',
        };
    }
    return {
        stateClass: 'choice-neutral',
        badgeText: null,
        kind: 'neutral',
    };
};

// 3. Conflict Map Computed Helpers
const mapPickedZoneType = computed(() => {
    return props.answer?.answer_data?.assigned_conflict_type || props.answer?.answer_text || '';
});

const mapCorrectZoneType = computed(() => {
    return props.answer?.context?.target_conflict_type || '';
});

const mapZonesList = computed(() => {
    const base = props.answer?.context?.zones || [];
    const list = [...base];
    const extras = [mapCorrectZoneType.value, mapPickedZoneType.value].filter(Boolean);
    for (const val of extras) {
        if (!list.some((z) => z.conflict_type === val)) {
            const meta = getConflictInfo(val);
            list.push({
                conflict_type: val,
                label: meta.label,
                subtitle: meta.subtitle,
            });
        }
    }
    return list;
});

// 4. Matching Type Computed Helpers
const matchingPickedLetter = computed(() => {
    const raw = props.answer?.answer_data?.choice_letter || props.answer?.answer_text || '';
    return String(raw).trim().toUpperCase();
});

const matchingCorrectLetter = computed(() => {
    return String(props.answer?.context?.correct_letter || '').trim().toUpperCase();
});

const matchingChoicesList = computed(() => {
    return props.answer?.context?.choices || [];
});

// 5. Evaluation Computed Helpers
const evalSelectedOptionId = computed(() => {
    const id = props.answer?.answer_data?.option_id;
    return id !== null && id !== undefined ? Number(id) : null;
});

const evalSelectedOption = computed(() => {
    const opts = props.answer?.context?.options || [];
    if (evalSelectedOptionId.value === null) {
        return null;
    }
    return opts.find((o) => Number(o.id) === evalSelectedOptionId.value) || null;
});

const evalDisplayStance = computed(() => {
    if (props.answer?.answer_data?.stance) {
        return props.answer.answer_data.stance;
    }
    if (evalSelectedOption.value) {
        return evalSelectedOption.value.stance_value || evalSelectedOption.value.option_text;
    }
    return '';
});

const evalStanceIsCorrect = computed(() => {
    if (!evalSelectedOption.value) {
        return null;
    }
    return Boolean(evalSelectedOption.value.is_correct);
});

const evalOptionState = (opt) => {
    const isCorrect = Boolean(opt.is_correct);
    const isSelected = evalSelectedOptionId.value !== null && Number(opt.id) === evalSelectedOptionId.value;

    if (isCorrect && isSelected) {
        return {
            stateClass: 'choice-correct-selected',
            badgeText: 'Expected Stance & Selected',
            kind: 'correct',
        };
    }
    if (isCorrect && !isSelected) {
        return {
            stateClass: 'choice-correct-key',
            badgeText: 'Expected Stance',
            kind: 'correct',
        };
    }
    if (!isCorrect && isSelected) {
        return {
            stateClass: 'choice-wrong-selected',
            badgeText: "Student's Wrong Choice",
            kind: 'wrong',
        };
    }
    return {
        stateClass: 'choice-neutral',
        badgeText: null,
        kind: 'neutral',
    };
};

const evalSentenceSegments = computed(() => {
    const template = props.answer?.context?.sentence_template;
    if (!template) {
        return [];
    }

    const blanks = Array.isArray(props.answer?.answer_data?.blanks)
        ? props.answer.answer_data.blanks
        : [];

    const regex = /(\[stance\]|\{stance\}|\[blank\]|\{blank\d*\}|\[___\]|___)/gi;
    const segments = [];
    let lastIndex = 0;
    let blankIndex = 0;
    let match;

    while ((match = regex.exec(template)) !== null) {
        if (match.index > lastIndex) {
            segments.push({
                type: 'text',
                content: template.slice(lastIndex, match.index),
            });
        }

        const token = match[0].toLowerCase();
        if (token === '[stance]' || token === '{stance}') {
            segments.push({
                type: 'stance',
                value: evalDisplayStance.value,
                isCorrect: evalStanceIsCorrect.value,
            });
        } else {
            const val = blanks[blankIndex] ?? '';
            segments.push({
                type: 'blank',
                blankNumber: blankIndex + 1,
                value: typeof val === 'string' ? val.trim() : String(val ?? ''),
            });
            blankIndex++;
        }

        lastIndex = regex.lastIndex;
    }

    if (lastIndex < template.length) {
        segments.push({
            type: 'text',
            content: template.slice(lastIndex),
        });
    }

    return segments;
});

const evalFallbackAnswerText = computed(() => {
    if (props.answer?.answer_text) {
        return props.answer.answer_text;
    }
    if (Array.isArray(props.answer?.answer_data?.blanks) && props.answer.answer_data.blanks.length > 0) {
        return props.answer.answer_data.blanks.join(' | ');
    }
    return '';
});

// 6. Reflection Computed Helpers
const reflectionBody = computed(() => {
    return (props.answer?.answer_text || props.answer?.answer_data?.text || '').trim();
});

const reflectionWordCount = computed(() => {
    if (typeof props.answer?.answer_data?.word_count === 'number') {
        return props.answer.answer_data.word_count;
    }
    if (!reflectionBody.value) {
        return 0;
    }
    return reflectionBody.value.split(/\s+/).filter(Boolean).length;
});

const reflectionLengthStatus = computed(() => {
    const count = reflectionWordCount.value;
    const min = props.answer?.context?.min_words ? Number(props.answer.context.min_words) : null;
    const max = props.answer?.context?.max_words ? Number(props.answer.context.max_words) : null;

    if (count === 0) {
        return {
            badgeClass: 'wc-status-bad',
            kind: 'wrong',
            text: 'No response submitted (0 words)',
        };
    }
    if (min !== null && count < min) {
        return {
            badgeClass: 'wc-status-bad',
            kind: 'wrong',
            text: `${count} words • Below ${min}-word minimum`,
        };
    }
    if (max !== null && count > max) {
        return {
            badgeClass: 'wc-status-warn',
            kind: 'warn',
            text: `${count} words • Over ${max}-word limit`,
        };
    }
    const rangeLabel = min !== null && max !== null
        ? `${min}–${max} words`
        : min !== null
            ? `Min ${min} words`
            : 'Complete';
    return {
        badgeClass: 'wc-status-ok',
        kind: 'correct',
        text: `${count} words • Meets goal (${rangeLabel})`,
    };
});
</script>

<template>
    <div class="inspector-root">
        <!-- 1. MULTIPLE CHOICE FORMAT -->
        <template v-if="answer.item_type === 'multiple_choice'">
            <div v-if="answer.context?.passage_context" class="passage-excerpt-box">
                <span class="sub-kicker">Passage Context</span>
                <p class="passage-quote">"{{ answer.context.passage_context }}"</p>
            </div>

            <div class="question-prompt-box">
                <div class="prompt-header-line">
                    <span class="sub-kicker">
                        Full Question
                        <span v-if="answer.context?.selection_type === 'multiple'" class="mode-tag">Multi-Select</span>
                        <span v-else class="mode-tag">Single Choice</span>
                    </span>
                </div>
                <p class="prompt-main-text">
                    {{ answer.context?.question_text || 'Question text unavailable (removed from blueprint).' }}
                </p>
            </div>

            <div v-if="mcqSelectedIds.length === 0" class="missing-answer-banner">
                <svg class="inline-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span>Student did not select any choice for this question.</span>
            </div>

            <div v-if="answer.context?.options?.length" class="choices-stack">
                <div
                    v-for="(opt, optIdx) in answer.context.options"
                    :key="opt.id"
                    class="choice-row-card"
                    :class="getMcqOptionState(opt).stateClass"
                >
                    <span class="choice-key-pill">{{ optionLetter(optIdx) }}</span>
                    <div class="choice-text-col">
                        <span class="choice-body-text">{{ opt.option_text }}</span>
                        <span v-if="opt.feedback && (getMcqOptionState(opt).isSelected || opt.is_correct)" class="choice-feedback-hint">
                            {{ opt.feedback }}
                        </span>
                    </div>
                    <span
                        v-if="getMcqOptionState(opt).badgeText"
                        class="choice-status-pill"
                        :class="`pill-${getMcqOptionState(opt).kind}`"
                    >
                        <svg v-if="getMcqOptionState(opt).kind === 'correct'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                        <span>{{ getMcqOptionState(opt).badgeText }}</span>
                    </span>
                </div>
            </div>
            <p v-else class="fallback-answer-line">
                <strong>Submitted Option IDs:</strong> {{ answer.answer_text || 'None' }}
            </p>

            <div v-if="answer.context?.explanation" class="rationale-callout">
                <strong>Answer Key Rationale:</strong> {{ answer.context.explanation }}
            </div>
        </template>

        <!-- 2. COLOR THE CONFLICT FORMAT -->
        <template v-else-if="answer.item_type === 'color_conflict'">
            <div class="question-prompt-box">
                <span class="sub-kicker">Story Excerpt Card</span>
                <blockquote class="excerpt-blockquote">
                    "{{ answer.context?.excerpt || 'Card excerpt unavailable.' }}"
                </blockquote>
            </div>

            <div v-if="!colorPickedType" class="missing-answer-banner">
                <svg class="inline-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span>Student did not select a conflict color for this card.</span>
            </div>

            <div class="choices-grid-2">
                <div
                    v-for="opt in colorOptionsList"
                    :key="opt.value"
                    class="choice-row-card"
                    :class="getCategoryOptionState(opt.value, colorCorrectType, colorPickedType, 'Category', 'Selected').stateClass"
                >
                    <span
                        class="conflict-color-dot"
                        :style="{ backgroundColor: getConflictInfo(opt.value).swatch }"
                        aria-hidden="true"
                    ></span>
                    <div class="choice-text-col">
                        <span class="choice-body-text">{{ opt.label }}</span>
                        <span v-if="opt.subtitle" class="choice-subtext">{{ opt.subtitle }}</span>
                    </div>
                    <span
                        v-if="getCategoryOptionState(opt.value, colorCorrectType, colorPickedType, 'Category', 'Selected').badgeText"
                        class="choice-status-pill"
                        :class="`pill-${getCategoryOptionState(opt.value, colorCorrectType, colorPickedType, 'Category', 'Selected').kind}`"
                    >
                        <svg v-if="getCategoryOptionState(opt.value, colorCorrectType, colorPickedType, 'Category', 'Selected').kind === 'correct'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                        <span>{{ getCategoryOptionState(opt.value, colorCorrectType, colorPickedType, 'Category', 'Selected').badgeText }}</span>
                    </span>
                </div>
            </div>

            <div v-if="answer.context?.explanation" class="rationale-callout">
                <strong>Answer Key Rationale:</strong> {{ answer.context.explanation }}
            </div>
        </template>

        <!-- 3. THE CONFLICT MAP FORMAT -->
        <template v-else-if="answer.item_type === 'conflict_map'">
            <div class="question-prompt-box">
                <div class="prompt-header-line">
                    <span class="sub-kicker">
                        Story Event Card
                        <span v-if="answer.context?.card_letter" class="mode-tag">{{ answer.context.card_letter }}</span>
                    </span>
                </div>
                <p class="prompt-main-text">
                    {{ answer.context?.event_text || 'Event text unavailable.' }}
                </p>
            </div>

            <div v-if="!mapPickedZoneType" class="missing-answer-banner">
                <svg class="inline-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span>Student did not assign this event to any conflict zone.</span>
            </div>

            <div class="choices-grid-2">
                <div
                    v-for="zone in mapZonesList"
                    :key="zone.conflict_type"
                    class="choice-row-card"
                    :class="getCategoryOptionState(zone.conflict_type, mapCorrectZoneType, mapPickedZoneType, 'Zone', 'Placed').stateClass"
                >
                    <span
                        class="conflict-color-dot"
                        :style="{ backgroundColor: getConflictInfo(zone.conflict_type).swatch }"
                        aria-hidden="true"
                    ></span>
                    <div class="choice-text-col">
                        <span class="choice-body-text">{{ zone.label }}</span>
                        <span v-if="zone.subtitle" class="choice-subtext">{{ zone.subtitle }}</span>
                    </div>
                    <span
                        v-if="getCategoryOptionState(zone.conflict_type, mapCorrectZoneType, mapPickedZoneType, 'Zone', 'Placed').badgeText"
                        class="choice-status-pill"
                        :class="`pill-${getCategoryOptionState(zone.conflict_type, mapCorrectZoneType, mapPickedZoneType, 'Zone', 'Placed').kind}`"
                    >
                        <svg v-if="getCategoryOptionState(zone.conflict_type, mapCorrectZoneType, mapPickedZoneType, 'Zone', 'Placed').kind === 'correct'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                        <span>{{ getCategoryOptionState(zone.conflict_type, mapCorrectZoneType, mapPickedZoneType, 'Zone', 'Placed').badgeText }}</span>
                    </span>
                </div>
            </div>

            <div v-if="answer.context?.explanation" class="rationale-callout">
                <strong>Answer Key Rationale:</strong> {{ answer.context.explanation }}
            </div>
        </template>

        <!-- 4. MATCHING TYPE FORMAT -->
        <template v-else-if="answer.item_type === 'matching'">
            <div class="question-prompt-box">
                <div class="prompt-header-line">
                    <span class="sub-kicker">
                        Column A Prompt
                        <span v-if="answer.context?.item_number" class="mode-tag">Item #{{ answer.context.item_number }}</span>
                    </span>
                </div>
                <p class="prompt-main-text">
                    {{ answer.context?.description || 'Matching prompt unavailable.' }}
                </p>
            </div>

            <div v-if="!matchingPickedLetter" class="missing-answer-banner">
                <svg class="inline-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span>Student did not select a matching choice from Column B.</span>
            </div>

            <div v-if="matchingChoicesList.length" class="choices-grid-2">
                <div
                    v-for="ch in matchingChoicesList"
                    :key="ch.id || ch.choice_letter"
                    class="choice-row-card"
                    :class="getCategoryOptionState(String(ch.choice_letter).toUpperCase(), matchingCorrectLetter, matchingPickedLetter, 'Match', 'Selected').stateClass"
                >
                    <span class="choice-key-pill">{{ ch.choice_letter }}</span>
                    <div class="choice-text-col">
                        <span class="choice-body-text">{{ ch.choice_text }}</span>
                    </div>
                    <span
                        v-if="getCategoryOptionState(String(ch.choice_letter).toUpperCase(), matchingCorrectLetter, matchingPickedLetter, 'Match', 'Selected').badgeText"
                        class="choice-status-pill"
                        :class="`pill-${getCategoryOptionState(String(ch.choice_letter).toUpperCase(), matchingCorrectLetter, matchingPickedLetter, 'Match', 'Selected').kind}`"
                    >
                        <svg v-if="getCategoryOptionState(String(ch.choice_letter).toUpperCase(), matchingCorrectLetter, matchingPickedLetter, 'Match', 'Selected').kind === 'correct'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                        <span>{{ getCategoryOptionState(String(ch.choice_letter).toUpperCase(), matchingCorrectLetter, matchingPickedLetter, 'Match', 'Selected').badgeText }}</span>
                    </span>
                </div>
            </div>
            <div v-else class="choices-grid-2">
                <div v-if="matchingCorrectLetter" class="choice-row-card choice-correct-key">
                    <span class="choice-key-pill">{{ matchingCorrectLetter }}</span>
                    <div class="choice-text-col">
                        <span class="choice-body-text">{{ answer.context?.correct_choice_text || `Choice ${matchingCorrectLetter}` }}</span>
                    </div>
                    <span class="choice-status-pill pill-correct">✓ Correct Match</span>
                </div>
                <div v-if="matchingPickedLetter && matchingPickedLetter !== matchingCorrectLetter" class="choice-row-card choice-wrong-selected">
                    <span class="choice-key-pill">{{ matchingPickedLetter }}</span>
                    <div class="choice-text-col">
                        <span class="choice-body-text">Choice {{ matchingPickedLetter }}</span>
                    </div>
                    <span class="choice-status-pill pill-wrong">✕ Student's Wrong Answer</span>
                </div>
            </div>

            <div v-if="answer.context?.explanation" class="rationale-callout">
                <strong>Answer Key Rationale:</strong> {{ answer.context.explanation }}
            </div>
        </template>

        <!-- 5. EVALUATION FORMAT (STEP 1 STANCE OPTIONS + STEP 2 FULL SENTENCE) -->
        <template v-else-if="answer.item_type === 'evaluation'">
            <div class="question-prompt-box">
                <div class="prompt-header-line">
                    <span class="sub-kicker">
                        {{ answer.context?.section_title || 'Evaluation Question' }}
                        <span v-if="answer.context?.conflict_type" class="mode-tag">
                            {{ getConflictInfo(answer.context.conflict_type).label }}
                        </span>
                    </span>
                </div>
                <p class="prompt-main-text">
                    {{ answer.context?.question_prompt || 'Evaluation prompt unavailable.' }}
                </p>
            </div>

            <!-- Step 1 Stance Choices -->
            <div class="eval-step-section">
                <span class="sub-kicker">{{ answer.context?.step1_prompt || 'Step 1: Stance Choice' }}</span>

                <div v-if="evalSelectedOptionId === null && !evalDisplayStance" class="missing-answer-banner">
                    <svg class="inline-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                    <span>Student did not choose a Step 1 stance option.</span>
                </div>

                <div v-if="answer.context?.options?.length" class="choices-stack">
                    <div
                        v-for="(opt, optIdx) in answer.context.options"
                        :key="opt.id"
                        class="choice-row-card"
                        :class="evalOptionState(opt).stateClass"
                    >
                        <span class="choice-key-pill">{{ optionLetter(optIdx) }}</span>
                        <div class="choice-text-col">
                            <span class="choice-body-text">{{ opt.option_text }}</span>
                            <span v-if="opt.stance_value" class="choice-subtext">
                                Stance tag: <strong>{{ opt.stance_value }}</strong>
                            </span>
                        </div>
                        <span
                            v-if="evalOptionState(opt).badgeText"
                            class="choice-status-pill"
                            :class="`pill-${evalOptionState(opt).kind}`"
                        >
                            <svg v-if="evalOptionState(opt).kind === 'correct'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                            <span>{{ evalOptionState(opt).badgeText }}</span>
                        </span>
                    </div>
                </div>
                <div v-else class="fallback-stance-line">
                    <span class="choice-status-pill pill-neutral">
                        Chosen Stance: {{ evalDisplayStance || 'None' }}
                    </span>
                </div>
            </div>

            <!-- Step 2 Sentence Template Filled with Student Answers -->
            <div class="eval-step-section">
                <span class="sub-kicker">Step 2: Completed Sentence & Fill-in-the-Blank Reasoning</span>
                <div v-if="evalSentenceSegments.length" class="sentence-preview-box">
                    <template v-for="(seg, sIdx) in evalSentenceSegments" :key="sIdx">
                        <span v-if="seg.type === 'text'" class="sentence-plain-text">{{ seg.content }}</span>
                        <span
                            v-else-if="seg.type === 'stance'"
                            class="inline-fill-chip"
                            :class="seg.value ? (seg.isCorrect === true ? 'fill-chip-green' : seg.isCorrect === false ? 'fill-chip-red' : 'fill-chip-blue') : 'fill-chip-missing'"
                        >
                            {{ seg.value || 'no stance' }}
                        </span>
                        <span
                            v-else
                            class="inline-fill-chip"
                            :class="seg.value ? 'fill-chip-blue' : 'fill-chip-missing'"
                        >
                            {{ seg.value || `blank ${seg.blankNumber} empty` }}
                        </span>
                    </template>
                </div>
                <blockquote v-else class="student-quote-card">
                    {{ evalFallbackAnswerText ? `"${evalFallbackAnswerText}"` : 'No blank input provided.' }}
                </blockquote>
            </div>
        </template>

        <!-- 6. REFLECTION FORMAT -->
        <template v-else-if="answer.item_type === 'reflection'">
            <div class="question-prompt-box">
                <span class="sub-kicker">Reflection Prompt</span>
                <p class="prompt-main-text">
                    "{{ answer.context?.prompt_text || 'Reflection prompt unavailable.' }}"
                </p>
                <p v-if="answer.context?.guiding_hint" class="guiding-hint-note">
                    <strong>Rubric Hint:</strong> {{ answer.context.guiding_hint }}
                </p>
            </div>

            <div class="reflection-status-row">
                <span class="wordcount-status-pill" :class="reflectionLengthStatus.badgeClass">
                    <svg v-if="reflectionLengthStatus.kind === 'correct'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="reflectionLengthStatus.kind === 'wrong'" class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                    <svg v-else class="pill-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ reflectionLengthStatus.text }}</span>
                </span>
            </div>

            <blockquote v-if="reflectionBody" class="student-quote-card essay-body">
                "{{ reflectionBody }}"
            </blockquote>
            <p v-else class="missing-response-note">
                No written reflection response was submitted.
            </p>
        </template>

        <!-- FALLBACK FOR UNKNOWN ITEM TYPES -->
        <template v-else>
            <p class="fallback-answer-line">
                <strong>Student Response:</strong> {{ answer.answer_text || JSON.stringify(answer.answer_data) || 'No answer recorded.' }}
            </p>
        </template>
    </div>
</template>

<style scoped>
.inspector-root {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.sub-kicker {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.45rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.76rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--px-ink-muted);
    margin-bottom: 0.25rem;
}

.mode-tag {
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-info-ink);
    border: 1px solid var(--px-tint-info-line);
    border-radius: 9999px;
    padding: 0.08rem 0.5rem;
    font-size: 0.7rem;
    text-transform: none;
    letter-spacing: 0;
}

.passage-excerpt-box {
    background: var(--px-surface-card);
    border: 1.5px solid var(--px-line);
    border-left: 4px solid #6366F1;
    border-radius: 0.75rem;
    padding: 0.65rem 0.9rem;
}

.passage-quote {
    margin: 0;
    font-size: 0.88rem;
    font-style: italic;
    color: var(--px-ink);
    line-height: 1.5;
}

.question-prompt-box {
    background: var(--px-surface-card);
    border: 1.5px solid var(--px-line);
    border-radius: 0.85rem;
    padding: 0.8rem 1rem;
}

.prompt-header-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.prompt-main-text {
    margin: 0.1rem 0 0;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--px-ink);
    line-height: 1.45;
}

.excerpt-blockquote {
    margin: 0.2rem 0 0;
    padding-left: 0.85rem;
    border-left: 4px solid #3B82F6;
    font-family: 'Nunito', sans-serif;
    font-size: 0.96rem;
    font-weight: 700;
    color: var(--px-ink);
    line-height: 1.5;
}

.guiding-hint-note {
    margin: 0.45rem 0 0;
    font-size: 0.82rem;
    color: var(--px-ink-muted);
    line-height: 1.4;
}

.missing-answer-banner {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    border: 1.5px solid var(--px-tint-danger-line);
    color: var(--px-tint-danger-ink);
    border-radius: 0.75rem;
    padding: 0.55rem 0.85rem;
    font-size: 0.84rem;
    font-weight: 800;
}

.inline-svg-icon {
    width: 1.1rem;
    height: 1.1rem;
    flex-shrink: 0;
}

.choices-stack {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.choices-grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.55rem;
}

.choice-row-card {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0.9rem;
    border-radius: 0.85rem;
    border: 2px solid var(--px-line);
    background: var(--px-surface-card);
    transition: border-color 0.15s, background-color 0.15s;
}

.choice-row-card.choice-neutral {
    background: var(--px-surface-card);
    border-color: var(--px-line);
    color: var(--px-ink);
}

.choice-row-card.choice-correct-selected {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border-color: #16A34A;
    color: var(--px-tint-success-ink);
    box-shadow: 0 2px 0 var(--px-tint-success-line);
}

.choice-row-card.choice-correct-key {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border: 2px dashed #22C55E;
    color: var(--px-tint-success-ink);
}

.choice-row-card.choice-wrong-selected {
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    border-color: #EF4444;
    color: var(--px-tint-danger-ink);
    box-shadow: 0 2px 0 var(--px-tint-danger-line);
}

.choice-key-pill {
    flex-shrink: 0;
    min-width: 1.85rem;
    height: 1.85rem;
    padding: 0 0.35rem;
    border-radius: 0.55rem;
    background: var(--px-surface-soft);
    border: 1.5px solid var(--px-line);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--px-ink);
}

.choice-correct-selected .choice-key-pill,
.choice-correct-key .choice-key-pill {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    border-color: #22C55E;
    color: var(--px-tint-success-ink);
}

.choice-wrong-selected .choice-key-pill {
    background: var(--px-tint-danger-bg);
    border-color: #EF4444;
    color: var(--px-tint-danger-ink);
}

.conflict-color-dot {
    width: 1rem;
    height: 1rem;
    border-radius: 9999px;
    flex-shrink: 0;
    border: 2px solid rgba(15, 23, 42, 0.15);
}

.choice-text-col {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.choice-body-text {
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.38;
    word-break: break-word;
}

.choice-subtext {
    font-size: 0.77rem;
    font-weight: 600;
    color: var(--px-ink-muted);
    line-height: 1.3;
}

.choice-correct-selected .choice-subtext,
.choice-correct-key .choice-subtext {
    color: var(--px-tint-success-ink);
}

.choice-wrong-selected .choice-subtext {
    color: var(--px-tint-danger-ink);
}

.choice-feedback-hint {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--px-ink-muted);
    line-height: 1.35;
}

.choice-status-pill {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.22rem 0.65rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.74rem;
    white-space: nowrap;
}

.pill-svg-icon {
    width: 0.9rem;
    height: 0.9rem;
    flex-shrink: 0;
}

.choice-status-pill.pill-correct {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-success-ink);
    border: 1.5px solid #22C55E;
}

.choice-status-pill.pill-wrong {
    background: var(--px-tint-danger-bg);
    color: var(--px-tint-danger-ink);
    border: 1.5px solid #EF4444;
}

.choice-status-pill.pill-neutral {
    background: var(--px-surface-soft);
    color: var(--px-ink-muted);
    border: 1.5px solid var(--px-line);
}

.rationale-callout {
    background: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card));
    border: 1.5px solid var(--px-tint-info-line);
    border-radius: 0.75rem;
    padding: 0.55rem 0.85rem;
    font-size: 0.84rem;
    color: var(--px-tint-info-ink);
    line-height: 1.45;
}

.eval-step-section {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.sentence-preview-box {
    background: var(--px-surface-card);
    border: 2px solid var(--px-line);
    border-left: 4px solid #3B82F6;
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--px-ink);
    line-height: 1.85;
}

.sentence-plain-text {
    white-space: pre-wrap;
}

.inline-fill-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.1rem 0.55rem;
    margin: 0 0.2rem;
    border-radius: 0.5rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.9rem;
    vertical-align: baseline;
}

.fill-chip-green {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-success-ink);
    border: 1.5px solid #22C55E;
}

.fill-chip-red {
    background: var(--px-tint-danger-bg);
    color: var(--px-tint-danger-ink);
    border: 1.5px solid #EF4444;
}

.fill-chip-blue {
    background: var(--px-tint-info-bg);
    color: var(--px-tint-info-ink);
    border: 1.5px solid #60A5FA;
}

.fill-chip-missing {
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-danger-ink);
    border: 1.5px dashed #F87171;
    font-style: italic;
}

.reflection-status-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.wordcount-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.8rem;
    font-weight: 800;
}

.wc-status-ok {
    background: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-success-ink);
    border: 1.5px solid #22C55E;
}

.wc-status-warn {
    background: var(--px-tint-warn-bg);
    color: var(--px-tint-warn-ink);
    border: 1.5px solid #F59E0B;
}

.wc-status-bad {
    background: var(--px-tint-danger-bg);
    color: var(--px-tint-danger-ink);
    border: 1.5px solid #EF4444;
}

.student-quote-card {
    background: var(--px-surface-card);
    border: 1.5px solid var(--px-line);
    border-left: 4px solid #3B82F6;
    border-radius: 0.75rem;
    padding: 0.8rem 1rem;
    margin: 0;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
    font-size: 0.96rem;
    color: var(--px-ink);
    line-height: 1.55;
}

.essay-body {
    white-space: pre-wrap;
}

.missing-response-note,
.fallback-answer-line {
    margin: 0;
    padding: 0.65rem 0.9rem;
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    border: 1.5px dashed var(--px-tint-danger-line);
    border-radius: 0.75rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--px-tint-danger-ink);
}

@media (max-width: 640px) {
    .choice-row-card {
        flex-wrap: wrap;
    }

    .choice-status-pill {
        margin-left: auto;
    }
}
</style>

