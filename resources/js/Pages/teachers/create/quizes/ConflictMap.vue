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

const xpPresets = [100, 150, 180, 200, 250];
const passPresets = [60, 70, 75, 80, 90];

// Default Preset Drop Zones matching user's diagram
const defaultZones = [
    {
        conflict_type: 'character_vs_character',
        label: 'CHARACTER VS CHARACTER',
        subtitle: 'Interpersonal conflict between individuals',
        color_theme: 'blue',
        icon: '⚔️',
        sort_order: 1,
    },
    {
        conflict_type: 'character_vs_society',
        label: 'CHARACTER VS SOCIETY',
        subtitle: 'Rules, laws, customs & institutions',
        color_theme: 'orange',
        icon: '🏛️',
        sort_order: 2,
    },
    {
        conflict_type: 'character_vs_nature',
        label: 'CHARACTER VS NATURE',
        subtitle: 'Weather, environmental forces & wild natural hazards',
        color_theme: 'green',
        icon: '🌊',
        sort_order: 3,
    },
];

// Helper to letter cards (0 -> A, 1 -> B, ...)
const getCardLetter = (index) => `Card ${String.fromCharCode(65 + (index % 26))}`;

// Create initial default event card
const createDefaultEvent = (index = 0) => ({
    card_letter: getCardLetter(index),
    event_text: '',
    target_conflict_type: 'character_vs_character',
    hint: '',
    explanation: '',
    points: 10,
    sort_order: index + 1,
});

// Setup form with 1 default event card
const form = useForm({
    title: '',
    description: 'Drag or select each event card into its matching conflict category zone below.',
    xp_reward: 180,
    passing_percentage: 70,
    time_limit_seconds: null,
    is_published: true,
    contexts: [
        {
            scenario: '',
            hint: '',
        },
    ],
    zones: JSON.parse(JSON.stringify(defaultZones)),
    events: [
        createDefaultEvent(0),
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

// Event Bank Card Management
const addEventCard = () => {
    if (form.events.length < 30) {
        const nextIdx = form.events.length;
        form.events.push(createDefaultEvent(nextIdx));
    }
};

const removeEventCard = (index) => {
    if (form.events.length > 1) {
        form.events.splice(index, 1);
        form.events.forEach((evt, idx) => {
            evt.card_letter = getCardLetter(idx);
            evt.sort_order = idx + 1;
        });
    }
};

const setEventTargetZone = (eventIndex, conflictType) => {
    form.events[eventIndex].target_conflict_type = conflictType;
};

// Stats
const totalPoints = computed(() => {
    return form.events.reduce((sum, evt) => sum + (parseInt(evt.points, 10) || 0), 0);
});

// Student Simulation / Interactive Preview State
const showPreviewModal = ref(false);
const activeTabCardId = ref(null);
const previewPlacements = ref({}); // { [eventIndex]: conflict_type }
const previewChecked = ref(false);
const previewShowScenario = ref(true);

const openLivePreview = () => {
    previewPlacements.value = {};
    previewChecked.value = false;
    activeTabCardId.value = null;
    showPreviewModal.value = true;
};

const closeLivePreview = () => {
    showPreviewModal.value = false;
};

const selectEventToPlace = (eventIndex) => {
    activeTabCardId.value = activeTabCardId.value === eventIndex ? null : eventIndex;
};

const placeActiveCardInZone = (zoneType) => {
    if (activeTabCardId.value !== null) {
        previewPlacements.value[activeTabCardId.value] = zoneType;
        activeTabCardId.value = null;
        previewChecked.value = false;
    }
};

const returnCardToBank = (eventIndex) => {
    delete previewPlacements.value[eventIndex];
    previewChecked.value = false;
};

const getEventsInZone = (zoneType) => {
    return form.events
        .map((evt, idx) => ({ ...evt, originalIndex: idx }))
        .filter((evt) => previewPlacements.value[evt.originalIndex] === zoneType);
};

const getUnplacedEvents = computed(() => {
    return form.events
        .map((evt, idx) => ({ ...evt, originalIndex: idx }))
        .filter((evt) => previewPlacements.value[evt.originalIndex] === undefined);
});

const checkPlacements = () => {
    previewChecked.value = true;
};

const handleBack = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const submitQuiz = () => {
    form.post(`/teachers/modules/${props.module.slug}/quizzes/the-conflict-map`, {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head :title="`Create The Conflict Map • ${module.title}`" />

    <div class="conflict-map-page">
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
                    <span class="step-pill">
                        🎯 Step {{ module.next_sort_order }} Milestone • The Conflict Map
                    </span>
                    <h1 class="page-title">
                        🗺️ The Conflict Map Quiz Studio
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
                            placeholder="e.g. Activity 2: The Conflict Map (Drag-and-Drop)"
                            required
                        />
                        <div v-if="form.errors.title" class="input-error">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="quiz-description">
                            Instructions for Students
                        </label>
                        <textarea
                            id="quiz-description"
                            v-model="form.description"
                            class="neu-textarea small-textarea"
                            rows="2"
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
                            <span class="stat-label">Drop Zones</span>
                            <span class="stat-value">{{ form.zones.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Event Cards</span>
                            <span class="stat-value">{{ form.events.length }}</span>
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
                                The story world or narrative setting supporting this Conflict Map.
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
                                placeholder="e.g. In the weaving town of San Luis, tension flares between traditional artisans and modern town officials..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Context Hint</label>
                            <input
                                v-model="ctx.hint"
                                type="text"
                                class="neu-input"
                                placeholder="Clue for learners about the competing forces..."
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

            <!-- Right Column: Drop Zones & Event Bank Builder -->
            <div class="studio-cards-column">
                <!-- Drop Zones Preview & Config -->
                <div class="neu-card drop-zones-config-card">
                    <div class="zones-header-banner">
                        <span class="banner-title">CONFLICT</span>
                        <span class="banner-hint">Category Drop Zones Configured Below</span>
                    </div>

                    <div class="zones-preview-grid">
                        <div
                            v-for="(zone, zIdx) in form.zones"
                            :key="zone.conflict_type"
                            class="zone-column-neu"
                            :class="`zone-theme-${zone.color_theme}`"
                        >
                            <div class="zone-col-top">
                                <span class="zone-icon">{{ zone.icon }}</span>
                                <input
                                    v-model="zone.label"
                                    type="text"
                                    class="zone-label-input"
                                />
                            </div>
                            <div class="zone-drop-indicator">
                                (DROP ZONE)
                            </div>
                            <input
                                v-model="zone.subtitle"
                                type="text"
                                class="zone-subtitle-input"
                                placeholder="Description of this category..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Event Bank Cards Section -->
                <div class="event-bank-header">
                    <div class="header-info">
                        <h2 class="section-heading">
                            🗂️ Event Bank (Cards to Drag & Drop)
                        </h2>
                        <p class="section-subheading">
                            Learners will drag or select these event cards and place them into the corresponding category zone.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="neu-btn add-card-top-btn"
                        @click="addEventCard"
                    >
                        ➕ Add Event Card
                    </button>
                </div>

                <!-- Event Cards List -->
                <div class="events-list">
                    <div
                        v-for="(event, eIdx) in form.events"
                        :key="eIdx"
                        class="neu-card event-editor-card"
                    >
                        <!-- Card Top Bar -->
                        <div class="event-card-top">
                            <div class="card-letter-pill">
                                <span class="letter-badge">{{ event.card_letter }}</span>
                                <span
                                    class="zone-target-pill"
                                    :class="`pill-${event.target_conflict_type}`"
                                >
                                    Target: {{ form.zones.find(z => z.conflict_type === event.target_conflict_type)?.label || 'Zone' }}
                                </span>
                            </div>

                            <div class="card-top-actions">
                                <div class="points-input-wrap">
                                    <label class="points-label">Points:</label>
                                    <input
                                        v-model.number="event.points"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="neu-input points-input"
                                    />
                                </div>
                                <button
                                    v-if="form.events.length > 1"
                                    type="button"
                                    class="remove-card-btn"
                                    title="Delete this event card"
                                    @click="removeEventCard(eIdx)"
                                >
                                    🗑️ Remove
                                </button>
                            </div>
                        </div>

                        <!-- Narrative Event Text -->
                        <div class="form-group">
                            <label class="form-label">
                                Event Description <span class="required">*</span>
                            </label>
                            <textarea
                                v-model="event.event_text"
                                class="neu-textarea event-textarea"
                                placeholder="e.g. Card A: Marta takes the remaining batch of rare royal blue dye for her own design."
                                rows="2"
                                required
                            ></textarea>
                            <div
                                v-if="form.errors[`events.${eIdx}.event_text`]"
                                class="input-error"
                            >
                                {{ form.errors[`events.${eIdx}.event_text`] }}
                            </div>
                        </div>

                        <!-- Target Drop Zone Selector -->
                        <div class="form-group">
                            <label class="form-label">
                                Target Drop Zone <span class="required">*</span>
                                <span class="helper-text">(Where should this card land?)</span>
                            </label>
                            <div class="zone-selector-grid">
                                <button
                                    v-for="zone in form.zones"
                                    :key="zone.conflict_type"
                                    type="button"
                                    class="zone-choice-btn"
                                    :class="[
                                        `btn-theme-${zone.color_theme}`,
                                        { active: event.target_conflict_type === zone.conflict_type }
                                    ]"
                                    @click="setEventTargetZone(eIdx, zone.conflict_type)"
                                >
                                    <span class="choice-icon">{{ zone.icon }}</span>
                                    <div class="choice-text">
                                        <span class="choice-label">{{ zone.label }}</span>
                                        <span class="choice-sub">{{ zone.subtitle }}</span>
                                    </div>
                                    <span v-if="event.target_conflict_type === zone.conflict_type" class="choice-check">
                                        ✓ Target Zone
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Scaffolding Hint & Explanation -->
                        <div class="form-group hint-feedback-group">
                            <div class="group-row">
                                <div class="sub-input-col">
                                    <label class="form-label">💡 Scaffolding Clue (Optional)</label>
                                    <input
                                        v-model="event.hint"
                                        type="text"
                                        class="neu-input"
                                        placeholder="e.g. Notice whether Marta is opposing a person, an institution, or nature..."
                                    />
                                </div>
                                <div class="sub-input-col">
                                    <label class="form-label">✅ Explanation / Reinforcement</label>
                                    <input
                                        v-model="event.explanation"
                                        type="text"
                                        class="neu-input"
                                        placeholder="e.g. Correct! This shows direct conflict between two individuals."
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Event Card Button Bottom -->
                <div class="cards-bottom-action">
                    <button
                        type="button"
                        class="neu-btn add-card-big-btn"
                        @click="addEventCard"
                    >
                        ➕ Add Another Event Card
                    </button>
                </div>
            </div>
        </form>

        <!-- Live Student Drag & Drop Simulation Preview Modal -->
        <div v-if="showPreviewModal" class="preview-backdrop" @click.self="closeLivePreview">
            <div class="preview-modal neu-card">
                <div class="preview-modal-header">
                    <div class="preview-header-left">
                        <span class="preview-pill">Interactive Simulation Preview</span>
                        <h2 class="preview-title">{{ form.title || 'Activity 2: The Conflict Map' }}</h2>
                    </div>
                    <button type="button" class="close-modal-btn" @click="closeLivePreview">✕</button>
                </div>

                <div class="simulation-body">
                    <!-- Instruction bar -->
                    <div class="sim-instructions-box">
                        <p class="sim-instruction-text">
                            👉 <strong>{{ form.description }}</strong>
                            <br />
                            <small>Click any card in the Event Bank, then click the target Drop Zone above to place it.</small>
                        </p>
                    </div>

                    <!-- Drop Zones Area -->
                    <div class="sim-zones-wrapper">
                        <div class="sim-conflict-bar">CONFLICT</div>
                        <div class="sim-zones-columns">
                            <div
                                v-for="zone in form.zones"
                                :key="zone.conflict_type"
                                class="sim-zone-column"
                                :class="`sim-theme-${zone.color_theme}`"
                                @click="placeActiveCardInZone(zone.conflict_type)"
                            >
                                <div class="sim-zone-title">{{ zone.label }}</div>
                                <div class="sim-drop-target-zone">
                                    <span v-if="getEventsInZone(zone.conflict_type).length === 0" class="empty-drop-text">
                                        (DROP ZONE)
                                    </span>
                                    <div
                                        v-for="placedEvt in getEventsInZone(zone.conflict_type)"
                                        :key="placedEvt.originalIndex"
                                        class="placed-card-bubble"
                                        :class="{
                                            'is-correct': previewChecked && placedEvt.target_conflict_type === zone.conflict_type,
                                            'is-wrong': previewChecked && placedEvt.target_conflict_type !== zone.conflict_type,
                                        }"
                                    >
                                        <div class="bubble-header">
                                            <strong>{{ placedEvt.card_letter }}</strong>
                                            <button
                                                type="button"
                                                class="return-btn"
                                                title="Return to Bank"
                                                @click.stop="returnCardToBank(placedEvt.originalIndex)"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <p class="bubble-text">{{ placedEvt.event_text }}</p>
                                        <div v-if="previewChecked" class="bubble-result-badge">
                                            <span v-if="placedEvt.target_conflict_type === zone.conflict_type">✅ Correct</span>
                                            <span v-else>❌ Belongs in {{ form.zones.find(z => z.conflict_type === placedEvt.target_conflict_type)?.label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Event Bank Area -->
                    <div class="sim-event-bank-box">
                        <div class="bank-title-bar">
                            <span>Event Bank (Drag or Tap to Select Target):</span>
                            <span class="unplaced-count">{{ getUnplacedEvents.length }} Unplaced</span>
                        </div>

                        <div v-if="getUnplacedEvents.length === 0" class="all-placed-msg">
                            🎉 All event cards have been placed into drop zones!
                        </div>

                        <div class="bank-cards-grid">
                            <button
                                v-for="evt in getUnplacedEvents"
                                :key="evt.originalIndex"
                                type="button"
                                class="sim-bank-card"
                                :class="{ 'is-selected': activeTabCardId === evt.originalIndex }"
                                @click="selectEventToPlace(evt.originalIndex)"
                            >
                                <div class="bank-card-tag">{{ evt.card_letter }}</div>
                                <div class="bank-card-text">{{ evt.event_text }}</div>
                                <span v-if="activeTabCardId === evt.originalIndex" class="selected-tap-hint">
                                    Tap a drop zone above ↑
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Actions Footer -->
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
                            :disabled="getUnplacedEvents.length > 0"
                            @click="checkPlacements"
                        >
                            <span>✓ Check Placements</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.conflict-map-page {
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

.event-textarea {
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

/* Drop Zones Configuration Neu-Card */
.drop-zones-config-card {
    padding: 0;
    overflow: hidden;
    border: 3px solid #1e293b;
    margin-bottom: 2rem;
}

.zones-header-banner {
    background: #fde047;
    border-bottom: 3px solid #1e293b;
    padding: 0.75rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.banner-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.3rem;
    font-weight: 900;
    color: #1e293b;
    letter-spacing: 0.05em;
}

.banner-hint {
    font-size: 0.8rem;
    font-weight: 800;
    color: #854d0e;
}

.zones-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-collapse: collapse;
}

@media (max-width: 768px) {
    .zones-preview-grid {
        grid-template-columns: 1fr;
    }
}

.zone-column-neu {
    padding: 1.25rem 1rem;
    border-right: 3px solid #1e293b;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.zone-column-neu:last-child {
    border-right: none;
}

.zone-theme-blue { background: #bfdbfe; }
.zone-theme-orange { background: #fed7aa; }
.zone-theme-green { background: #bbf7d0; }

.zone-col-top {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.zone-icon {
    font-size: 1.3rem;
}

.zone-label-input {
    width: 100%;
    background: transparent;
    border: none;
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #1e293b;
    outline: none;
    text-transform: uppercase;
}

.zone-drop-indicator {
    background: rgba(255, 255, 255, 0.65);
    border: 2px dashed rgba(30, 41, 59, 0.4);
    border-radius: 10px;
    padding: 1rem 0.5rem;
    text-align: center;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    color: #475569;
}

.zone-subtitle-input {
    background: rgba(255, 255, 255, 0.5);
    border: 1px solid rgba(30, 41, 59, 0.2);
    border-radius: 8px;
    padding: 0.35rem 0.6rem;
    font-size: 0.78rem;
    font-weight: 600;
}

/* Event Bank Header */
.event-bank-header {
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

.add-card-top-btn {
    background: #ecfdf5;
    color: #047857;
}

/* Event Editor Card */
.event-editor-card {
    border-left: 8px solid #f59e0b;
    margin-bottom: 1.5rem;
}

.event-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.card-letter-pill {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.letter-badge {
    background: #1e293b;
    color: #ffffff;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
}

.zone-target-pill {
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
    text-transform: uppercase;
}

.pill-character_vs_character { background: #dbeafe; color: #1d4ed8; }
.pill-character_vs_society { background: #ffedd5; color: #c2410c; }
.pill-character_vs_nature { background: #dcfce7; color: #15803d; }
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
}

/* Zone Choice Selector Buttons */
.zone-selector-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.zone-choice-btn {
    border: 3px solid #cbd5e1;
    border-radius: 14px;
    padding: 0.75rem;
    background: #ffffff;
    cursor: pointer;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    transition: all 0.15s ease;
    box-shadow: 0 3px 0 #cbd5e1;
}

.zone-choice-btn.active {
    border-color: #1e293b;
    box-shadow: 0 5px 0 #1e293b;
}

.btn-theme-blue.active { background: #eff6ff; }
.btn-theme-orange.active { background: #fff7ed; }
.btn-theme-green.active { background: #f0fdf4; }

.choice-label {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.88rem;
    color: #1e293b;
}

.choice-sub {
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.2;
}

.choice-check {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.72rem;
    font-weight: 800;
    color: #059669;
    text-transform: uppercase;
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
    color: #2563eb;
}

/* Simulation Preview Modal */
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
    border-bottom: 2px dashed #e2e8f0;
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

.sim-instructions-box {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.25rem;
}

.sim-instruction-text {
    margin: 0;
    font-size: 0.92rem;
    color: #334155;
}

/* Sim Zones Columns */
.sim-zones-wrapper {
    border: 3px solid #1e293b;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.sim-conflict-bar {
    background: #fde047;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.2rem;
    font-weight: 900;
    text-align: center;
    padding: 0.5rem;
    border-bottom: 3px solid #1e293b;
}

.sim-zones-columns {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
}

.sim-zone-column {
    border-right: 3px solid #1e293b;
    padding: 1rem;
    min-height: 220px;
    cursor: pointer;
    transition: background 0.15s ease;
}

.sim-zone-column:last-child {
    border-right: none;
}

.sim-zone-column:hover {
    filter: brightness(0.97);
}

.sim-theme-blue { background: #dbeafe; }
.sim-theme-orange { background: #ffedd5; }
.sim-theme-green { background: #dcfce7; }

.sim-zone-title {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.95rem;
    font-weight: 800;
    text-align: center;
    margin-bottom: 0.75rem;
    color: #1e293b;
}

.sim-drop-target-zone {
    min-height: 150px;
    background: rgba(255, 255, 255, 0.65);
    border: 2px dashed rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.empty-drop-text {
    display: block;
    text-align: center;
    margin: auto;
    font-family: 'Fredoka', sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    color: #64748b;
}

.placed-card-bubble {
    background: #ffffff;
    border: 2px solid #1e293b;
    border-radius: 10px;
    padding: 0.6rem;
    box-shadow: 0 2px 0 #1e293b;
}

.placed-card-bubble.is-correct {
    border-color: #10b981;
    background: #ecfdf5;
}

.placed-card-bubble.is-wrong {
    border-color: #ef4444;
    background: #fef2f2;
}

.bubble-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
    font-size: 0.8rem;
}

.return-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-weight: 800;
}

.return-btn:hover {
    color: #ef4444;
}

.bubble-text {
    font-size: 0.82rem;
    line-height: 1.35;
    margin: 0;
}

.bubble-result-badge {
    margin-top: 0.4rem;
    font-size: 0.75rem;
    font-weight: 800;
}

/* Event Bank in Simulation */
.sim-event-bank-box {
    background: #f8fafc;
    border: 3px solid #1e293b;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.bank-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 800;
    font-size: 0.95rem;
    margin-bottom: 1rem;
    color: #1e293b;
}

.unplaced-count {
    background: #e2e8f0;
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
    font-size: 0.8rem;
}

.all-placed-msg {
    text-align: center;
    padding: 1.5rem;
    font-family: 'Fredoka', sans-serif;
    font-size: 1.1rem;
    color: #059669;
}

.bank-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.75rem;
}

.sim-bank-card {
    background: #ffffff;
    border: 2px solid #1e293b;
    border-radius: 12px;
    padding: 0.85rem;
    text-align: left;
    cursor: pointer;
    box-shadow: 0 3px 0 #1e293b;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.sim-bank-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #1e293b;
}

.sim-bank-card.is-selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px #3b82f6, 0 5px 0 #1e293b;
    background: #eff6ff;
}

.bank-card-tag {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: #1e293b;
}

.bank-card-text {
    font-size: 0.88rem;
    line-height: 1.4;
    color: #334155;
}

.selected-tap-hint {
    font-size: 0.75rem;
    font-weight: 800;
    color: #2563eb;
    margin-top: 0.25rem;
}

.preview-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px dashed #e2e8f0;
    padding-top: 1rem;
}

.check-answers-btn {
    background: #3b82f6;
    color: #ffffff;
}
</style>

