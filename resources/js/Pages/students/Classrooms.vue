<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import StudentLayout from '../../Components/StudentLayout.vue';
import StudentJoinCodeCard from '../../Components/StudentJoinCodeCard.vue';
import { prefetchRoute } from '@/Composables/usePrefetch';
import { shouldReduceEffects } from '@/Composables/usePerformanceMode';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({ id: null, name: 'Student', role: 'student' }),
    },
    classrooms: {
        type: Array,
        default: () => [],
    },
    direct_modules_count: {
        type: Number,
        default: 0,
    },
});

const searchQuery = ref('');
const searchInputRef = ref(null);

const showSearch = computed(() => props.classrooms.length > 0);

const clearSearch = () => {
    searchQuery.value = '';
    searchInputRef.value?.focus();
};

const filteredClassrooms = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) {
        return props.classrooms;
    }

    return props.classrooms.filter((room) =>
        [room.name, room.teacher_name, room.grade_level]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(query)),
    );
});

const hasAnything = computed(() => props.classrooms.length > 0 || props.direct_modules_count > 0);

const classroomUrl = (room) => `/students/classes/${room.id}`;
const codeAdventuresUrl = '/students/classes/code-adventures';

const openUrl = (url) => {
    router.visit(url);
};

const moduleCountLabel = (count) => `${count} ${count === 1 ? 'module' : 'modules'}`;

/**
 * One list for the grid: the optional "Code Adventures" card first, then classrooms.
 *
 * @type {import('vue').ComputedRef<Array<{key: string, url: string, themeClass: string, kicker: string, icon: string, name: string, meta: string, count: number}>>}
 */
const classroomCards = computed(() => {
    const cards = filteredClassrooms.value.map((room) => ({
        key: `room-${room.id}`,
        url: classroomUrl(room),
        themeClass: `room-theme-${room.theme}`,
        kicker: 'Classroom',
        icon: room.icon || '🏫',
        name: room.name,
        meta: [room.teacher_name ? `👩‍🏫 ${room.teacher_name}` : null, room.grade_level].filter(Boolean).join(' · '),
        count: room.modules_count ?? 0,
    }));

    if (props.direct_modules_count > 0 && !searchQuery.value) {
        cards.unshift({
            key: 'code-adventures',
            url: codeAdventuresUrl,
            themeClass: 'is-code',
            kicker: 'Module codes',
            icon: '🔑',
            name: 'Code Adventures',
            meta: 'Modules you joined with a module code',
            count: props.direct_modules_count,
        });
    }

    return cards;
});

const viewMode = ref('grid'); // 'grid' | 'list'

const totalModulesCount = computed(() => {
    const fromRooms = props.classrooms.reduce((acc, r) => acc + (r.modules_count ?? 0), 0);
    return fromRooms + (props.direct_modules_count ?? 0);
});

const setViewMode = (mode) => {
    viewMode.value = mode;
    try {
        window.localStorage?.setItem('emodule_student_classrooms_view', mode);
    } catch {}
};

/* Desktop-only 3D tilt: one pointer listener on the grid, writes batched in rAF. */
const canTilt = ref(false);
let tiltFrame = 0;

const handleGridPointerMove = (event) => {
    if (!canTilt.value) {
        return;
    }

    const card = event.target.closest?.('.classroom-card, .classroom-list-row');
    if (!card) {
        return;
    }

    const { clientX, clientY } = event;
    cancelAnimationFrame(tiltFrame);
    tiltFrame = requestAnimationFrame(() => {
        const rect = card.getBoundingClientRect();
        const offsetX = (clientX - rect.left) / rect.width - 0.5;
        const offsetY = (clientY - rect.top) / rect.height - 0.5;
        card.style.setProperty('--ry', `${(offsetX * 8).toFixed(2)}deg`);
        card.style.setProperty('--rx', `${(offsetY * -6).toFixed(2)}deg`);
    });
};

const handleGridPointerOut = (event) => {
    const card = event.target.closest?.('.classroom-card, .classroom-list-row');
    if (card && !card.contains(event.relatedTarget)) {
        cancelAnimationFrame(tiltFrame);
        card.style.removeProperty('--rx');
        card.style.removeProperty('--ry');
    }
};

onMounted(() => {
    canTilt.value =
        window.matchMedia('(hover: hover) and (pointer: fine)').matches &&
        !shouldReduceEffects();
    try {
        const savedView = window.localStorage?.getItem('emodule_student_classrooms_view');
        if (savedView === 'list' || savedView === 'grid') {
            viewMode.value = savedView;
        }
    } catch {}
});

onBeforeUnmount(() => cancelAnimationFrame(tiltFrame));
</script>

<template>
    <Head title="My Classrooms • WEBREAD" />

    <StudentLayout :user="user" active-nav="classes">
        <section class="clay-classrooms-page" aria-labelledby="classrooms-heading">
            <div class="classrooms-hub-layout">
                <!-- SIDEBAR: QUICK JOIN & ADVENTURE STATS -->
                <aside class="classrooms-hub-sidebar">
                    <StudentJoinCodeCard />
                </aside>

                <!-- MAIN WORKSPACE: SEARCH, CONTROLS & CLASSROOM CARDS -->
                <div class="classrooms-hub-main">
                    <!-- Top Control Deck -->
                    <div class="stage-control-deck">
                        <div class="deck-top-row">
                            <div class="deck-title-block">
                                <h1 id="classrooms-heading" class="deck-title">🏫 My Classrooms</h1>
                                <span v-if="classrooms.length > 0" class="deck-room-counter">
                                    {{ filteredClassrooms.length }} {{ filteredClassrooms.length === 1 ? 'room' : 'rooms' }}
                                </span>
                            </div>

                            <!-- View Mode Switcher -->
                            <div v-if="classroomCards.length > 0" class="deck-view-switch" role="group" aria-label="Classroom layout">
                                <button
                                    type="button"
                                    class="view-btn"
                                    :class="{ 'is-active': viewMode === 'grid' }"
                                    title="Grid View (3D Diorama Cards)"
                                    aria-label="Grid View"
                                    @click="setViewMode('grid')"
                                >
                                    <svg viewBox="0 0 16 16" width="13" height="13" fill="currentColor" aria-hidden="true">
                                        <rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.5" />
                                        <rect x="9" y="1.5" width="5.5" height="5.5" rx="1.5" />
                                        <rect x="1.5" y="9" width="5.5" height="5.5" rx="1.5" />
                                        <rect x="9" y="9" width="5.5" height="5.5" rx="1.5" />
                                    </svg>
                                    <span>Grid</span>
                                </button>
                                <button
                                    type="button"
                                    class="view-btn"
                                    :class="{ 'is-active': viewMode === 'list' }"
                                    title="List View (Compact Horizontal Tiles)"
                                    aria-label="List View"
                                    @click="setViewMode('list')"
                                >
                                    <svg viewBox="0 0 16 16" width="13" height="13" fill="currentColor" aria-hidden="true">
                                        <rect x="1.5" y="2" width="13" height="3" rx="1" />
                                        <rect x="1.5" y="6.5" width="13" height="3" rx="1" />
                                        <rect x="1.5" y="11" width="13" height="3" rx="1" />
                                    </svg>
                                    <span>List</span>
                                </button>
                            </div>
                        </div>

                        <!-- Compact Search Bar -->
                        <div v-if="showSearch" class="deck-search-bar">
                            <div class="clay-search-compact" :class="{ 'has-query': searchQuery }" role="search">
                                <label for="classroom-search" class="sr-only">Search classrooms</label>
                                <span class="search-mini-lens" aria-hidden="true">🔍</span>
                                <input
                                    id="classroom-search"
                                    ref="searchInputRef"
                                    v-model="searchQuery"
                                    type="search"
                                    class="search-mini-input"
                                    placeholder="Find class, teacher, or grade…"
                                    autocomplete="off"
                                    enterkeyhint="search"
                                    @keydown.esc="clearSearch"
                                />
                                <span v-if="searchQuery" class="search-mini-count">{{ filteredClassrooms.length }}</span>
                                <button v-if="searchQuery" type="button" class="search-mini-clear" aria-label="Clear search" @click="clearSearch">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- CLASSROOMS VIEW CONTAINER (GRID OR LIST) -->
                    <div
                        v-if="hasAnything"
                        :class="[viewMode === 'grid' ? 'classrooms-compact-grid' : 'classrooms-compact-list']"
                        @pointermove.passive="handleGridPointerMove"
                        @pointerout="handleGridPointerOut"
                    >
                        <!-- GRID VIEW CARD -->
                        <template v-if="viewMode === 'grid'">
                            <a
                                v-for="(card, index) in classroomCards"
                                :key="card.key"
                                :href="card.url"
                                class="classroom-card"
                                :class="card.themeClass"
                                :style="{ '--i': Math.min(index, 8) }"
                                @click.prevent="openUrl(card.url)"
                                @mouseenter="prefetchRoute(card.url, ['student-classes'])"
                                @focus="prefetchRoute(card.url, ['student-classes'])"
                            >
                                <!-- Lit diorama scene: key light top-left, rolling clay hills -->
                                <span class="card-scene" aria-hidden="true">
                                    <span class="scene-cloud scene-cloud--1"></span>
                                    <span class="scene-cloud scene-cloud--2"></span>
                                    <span class="scene-orb scene-orb--1"></span>
                                    <svg class="scene-hills" viewBox="0 0 320 70" preserveAspectRatio="none">
                                        <path class="hill-back" d="M0,40 C55,14 120,46 185,24 C240,6 290,30 320,20 L320,70 L0,70 Z" />
                                        <path class="hill-front" d="M0,54 C70,30 140,64 215,40 C262,26 296,44 320,38 L320,70 L0,70 Z" />
                                    </svg>
                                    <span class="scene-kicker">{{ card.kicker }}</span>
                                </span>

                                <!-- 3D clay medallion -->
                                <span class="card-medallion" aria-hidden="true">
                                    <span class="medallion-icon">{{ card.icon }}</span>
                                </span>

                                <span class="classroom-card-body">
                                    <span class="classroom-card-name">{{ card.name }}</span>
                                    <span v-if="card.meta" class="classroom-card-meta">{{ card.meta }}</span>

                                    <span class="classroom-card-footer">
                                        <span class="classroom-card-count">📚 {{ moduleCountLabel(card.count) }}</span>
                                        <span class="card-enter" aria-hidden="true">
                                            <span class="card-enter-face">Enter ›</span>
                                        </span>
                                    </span>
                                </span>
                            </a>
                        </template>

                        <!-- LIST VIEW TILE ROW -->
                        <template v-else>
                            <a
                                v-for="(card, index) in classroomCards"
                                :key="`list-${card.key}`"
                                :href="card.url"
                                class="classroom-list-row"
                                :class="card.themeClass"
                                :style="{ '--i': Math.min(index, 8) }"
                                @click.prevent="openUrl(card.url)"
                                @mouseenter="prefetchRoute(card.url, ['student-classes'])"
                                @focus="prefetchRoute(card.url, ['student-classes'])"
                            >
                                <div class="list-medallion" aria-hidden="true">
                                    <span class="list-icon">{{ card.icon }}</span>
                                </div>

                                <div class="list-main-info">
                                    <div class="list-title-row">
                                        <strong class="list-title">{{ card.name }}</strong>
                                        <span class="list-kicker">{{ card.kicker }}</span>
                                    </div>
                                    <span v-if="card.meta" class="list-meta">{{ card.meta }}</span>
                                </div>

                                <div class="list-actions-right">
                                    <span class="list-count-badge">📚 {{ moduleCountLabel(card.count) }}</span>
                                    <span class="list-enter-btn" aria-hidden="true">
                                        <span class="list-enter-face">Enter ›</span>
                                    </span>
                                </div>
                            </a>
                        </template>

                        <!-- Search No Match -->
                        <div v-if="searchQuery && filteredClassrooms.length === 0" class="classrooms-no-match">
                            <svg class="no-match-blob" viewBox="0 0 64 62" aria-hidden="true">
                                <defs>
                                    <radialGradient id="pxPuzzledBlob" cx="34%" cy="28%" r="78%">
                                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.9" />
                                        <stop offset="32%" class="blob-stop-mid" />
                                        <stop offset="100%" class="blob-stop-lo" />
                                    </radialGradient>
                                </defs>
                                <ellipse cx="32" cy="58.5" rx="19" ry="3" class="orb-contact-shadow" />
                                <path d="M8 40 C8 20 18 10 32 10 C46 10 56 20 56 40 C56 51 46 56 32 56 C18 56 8 51 8 40 Z" fill="url(#pxPuzzledBlob)" />
                                <ellipse cx="24" cy="32" rx="5" ry="6" fill="#FFFFFF" />
                                <ellipse cx="40" cy="32" rx="5" ry="6" fill="#FFFFFF" />
                                <circle cx="25.5" cy="30.5" r="2.8" fill="#18143B" />
                                <circle cx="41.5" cy="30.5" r="2.8" fill="#18143B" />
                                <circle cx="26.6" cy="29.4" r="1" fill="#FFFFFF" />
                                <circle cx="42.6" cy="29.4" r="1" fill="#FFFFFF" />
                                <path d="M26 45 Q32 41.5 38 45" stroke="#18143B" stroke-width="2.4" stroke-linecap="round" fill="none" />
                                <text x="50" y="12" class="no-match-question">?</text>
                            </svg>
                            <p class="no-match-title">No classroom matches “{{ searchQuery }}”</p>
                            <button type="button" class="no-match-reset" @click="clearSearch">
                                <span class="no-match-reset-face">Show all classrooms</span>
                            </button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="classrooms-empty">
                        <span class="classrooms-empty-emoji" aria-hidden="true">🏫</span>
                        <h2 class="classrooms-empty-title">No classrooms joined yet!</h2>
                        <p class="classrooms-empty-desc">
                            Ask your teacher for your 6-digit class code and enter it on the left to start your reading adventure.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </StudentLayout>
</template>

<style scoped>
.clay-classrooms-page {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    font-family: var(--font-body);
    width: 100%;
}

/* ==========================================================================
   TWO-PANEL WORKSPACE HUB (SPLIT LAYOUT)
   ========================================================================== */
.classrooms-hub-layout {
    display: grid;
    grid-template-columns: 290px 1fr;
    gap: 1rem;
    align-items: start;
    width: 100%;
}

/* ---------- Left Panel / Sidebar ---------- */
.classrooms-hub-sidebar {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    position: sticky;
    top: 0.65rem;
}

.hub-overview-card {
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 2px solid var(--px-card-border);
    border-radius: 20px;
    padding: 0.75rem 0.95rem;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    box-shadow:
        0 4px 0 var(--px-ledge),
        0 10px 20px -8px rgba(var(--px-shadow-rgb), 0.25),
        inset 0 1px 0 var(--px-rim-top);
}

.overview-header {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.overview-icon {
    font-size: 1.25rem;
    line-height: 1;
}

.overview-meta {
    display: flex;
    flex-direction: column;
}

.overview-title {
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--px-ink);
    line-height: 1.1;
}

.overview-sub {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.overview-stats-row {
    display: flex;
    gap: 0.45rem;
}

.overview-stat-pill {
    flex: 1;
    background: var(--px-surface-well);
    border: 1.5px solid var(--px-line);
    border-radius: 12px;
    padding: 0.35rem 0.45rem;
    text-align: center;
    box-shadow: var(--px-well-shadow);
}

.stat-num {
    font-family: var(--font-display);
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--px-ink);
    line-height: 1;
    display: block;
}

.stat-lbl {
    font-size: 0.56rem;
    font-weight: 800;
    color: var(--px-ink-muted);
    letter-spacing: 0.05em;
}

.direct-shortcut-tile {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.65rem;
    border-radius: 12px;
    background: linear-gradient(145deg, #FEF3C7 0%, #FDE68A 100%);
    border: 1.5px solid #F59E0B;
    cursor: pointer;
    text-align: left;
    transition: transform 0.15s ease;
    box-shadow: 0 2px 0 #D97706;
}

.direct-shortcut-tile:hover {
    transform: translateY(-1.5px);
}

.shortcut-icon {
    font-size: 1.15rem;
    line-height: 1;
}

.shortcut-text {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.shortcut-title {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: #92400E;
    line-height: 1.1;
}

.shortcut-count {
    font-size: 0.65rem;
    font-weight: 700;
    color: #B45309;
}

.shortcut-arrow {
    font-size: 0.8rem;
    font-weight: 900;
    color: #92400E;
}

/* ---------- Right Panel / Main Workspace ---------- */
.classrooms-hub-main {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    min-width: 0;
}

.stage-control-deck {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.deck-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.deck-title-block {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.deck-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--px-ink);
}

.deck-room-counter {
    padding: 0.12rem 0.5rem;
    border-radius: 9999px;
    background: var(--px-tint-info-bg);
    border: 1px solid var(--px-tint-info-line);
    color: var(--px-tint-info-ink);
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    white-space: nowrap;
}

.deck-view-switch {
    display: inline-flex;
    align-items: center;
    background: var(--px-surface-well);
    border: 1.5px solid var(--px-line);
    border-radius: 9999px;
    padding: 2px;
    box-shadow: var(--px-well-shadow);
}

.view-btn {
    border: none;
    background: transparent;
    padding: 0.22rem 0.55rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink-muted);
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.view-btn.is-active {
    background: var(--px-surface-card);
    color: var(--px-ink);
    box-shadow: 0 2px 4px rgba(var(--px-shadow-rgb), 0.15), inset 0 1px 0 var(--px-rim-top);
}

/* Compact Search Bar */
.deck-search-bar {
    width: 100%;
}

.clay-search-compact {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    width: 100%;
    height: 38px;
    padding: 0 0.55rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 3px 0 var(--px-ledge),
        inset 0 1px 0 var(--px-rim-top);
    box-sizing: border-box;
}

.search-mini-lens {
    font-size: 0.92rem;
    line-height: 1;
    opacity: 0.8;
}

.search-mini-input {
    flex: 1;
    height: 28px;
    border: none;
    background: transparent;
    font-family: var(--font-display);
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--px-ink);
    outline: none;
    min-width: 0;
}

.search-mini-input::placeholder {
    color: var(--px-ink-subtle);
    opacity: 0.85;
}

.search-mini-count {
    font-size: 0.65rem;
    font-weight: 800;
    background: var(--px-tint-info-bg);
    color: var(--px-tint-info-ink);
    padding: 0.1rem 0.45rem;
    border-radius: 9999px;
    white-space: nowrap;
}

.search-mini-clear {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #EF4444;
    color: #FFFFFF;
    border: none;
    font-size: 0.65rem;
    font-weight: 900;
    display: grid;
    place-items: center;
    cursor: pointer;
    flex-shrink: 0;
}

/* ==========================================================================
   VIEW MODE 1: COMPACT 3D GRID CARDS
   ========================================================================== */
.classrooms-compact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr));
    gap: 0.75rem;
}

.classroom-card {
    --room-accent: #4F46E5;
    --room-ledge: #312E81;
    --room-soft: var(--px-tint-info-bg);
    --lift: 0px;

    position: relative;
    display: flex;
    flex-direction: column;
    border-radius: 20px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--room-soft) 100%);
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 5px 0 color-mix(in srgb, var(--room-accent) 40%, var(--px-ledge)),
        0 12px 20px -8px color-mix(in srgb, var(--room-accent) 45%, rgba(var(--px-shadow-rgb), 0.4)),
        inset 0 1.5px 0 var(--px-rim-top);
    color: inherit;
    text-decoration: none;
    transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)) translateY(var(--lift));
    transform-style: preserve-3d;
    transition: transform 0.28s var(--spring-bounce);
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    animation: card-pop 0.5s var(--spring-bounce) backwards;
    animation-delay: calc(var(--i, 0) * 60ms);
}

@media (hover: hover) {
    .classroom-card:hover {
        --lift: -4px;
    }

    .classroom-card:hover .card-enter-face {
        transform: translateY(-5px);
    }

    .classroom-card:hover .card-medallion {
        transform: translateZ(28px) scale(1.08) rotate(-4deg);
    }
}

.classroom-card:active {
    --lift: 1px;
    transition-duration: 0.08s;
}

.classroom-card:active .card-enter-face {
    transform: translateY(-1px) scale(0.96);
}

.classroom-card.room-theme-forest { --room-accent: #059669; --room-ledge: #065F46; --room-soft: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card)); }
.classroom-card.room-theme-ocean { --room-accent: #0284C7; --room-ledge: #075985; --room-soft: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)); }
.classroom-card.room-theme-cosmic { --room-accent: #7C3AED; --room-ledge: #5B21B6; --room-soft: color-mix(in srgb, var(--px-tint-magic-bg) 55%, var(--px-surface-card)); }
.classroom-card.room-theme-volcano { --room-accent: #EA580C; --room-ledge: #9A3412; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)); }
.classroom-card.is-code { --room-accent: #D97706; --room-ledge: #92400E; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)); }

/* Compact Diorama Scene */
.card-scene {
    position: relative;
    display: block;
    height: 52px;
    margin: 6px 6px 0;
    border-radius: 14px;
    overflow: hidden;
    background:
        radial-gradient(circle at 16% 8%, rgba(255, 255, 255, 0.7) 0%, transparent 40%),
        linear-gradient(172deg,
            color-mix(in srgb, var(--room-accent) 24%, var(--px-surface-card)) 0%,
            color-mix(in srgb, var(--room-accent) 58%, var(--px-surface-card)) 100%);
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.15);
}

.scene-hills {
    position: absolute;
    left: 0;
    bottom: -1px;
    width: 100%;
    height: 32px;
}

.hill-back {
    fill: color-mix(in srgb, var(--room-accent) 72%, #FFFFFF);
    opacity: 0.9;
}

.hill-front {
    fill: var(--room-accent);
    stroke: color-mix(in srgb, var(--room-accent) 45%, #FFFFFF);
    stroke-width: 1.5;
}

.scene-cloud {
    position: absolute;
    height: 9px;
    border-radius: 999px;
    background: #FFFFFF;
    opacity: 0.7;
}

.scene-cloud--1 { top: 8px; left: 28%; width: 28px; }
.scene-cloud--2 { top: 16px; left: 65%; width: 20px; }

.scene-orb--1 {
    position: absolute;
    top: 8px;
    right: 14%;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #F59E0B 78%, #B45309 100%);
}

.scene-kicker {
    position: absolute;
    top: 5px;
    left: 7px;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--px-surface-card) 88%, transparent);
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--room-accent) 55%, var(--px-ink));
}

/* Compact Medallion */
.card-medallion {
    position: absolute;
    top: 32px;
    left: 14px;
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%,
        color-mix(in srgb, var(--room-accent) 30%, #FFFFFF) 0%,
        var(--room-accent) 58%,
        var(--room-ledge) 100%);
    border: 3px solid var(--px-surface-card);
    box-shadow:
        0 3px 0 var(--room-ledge),
        0 8px 12px -4px color-mix(in srgb, var(--room-accent) 60%, rgba(var(--px-shadow-rgb), 0.5));
    transform: translateZ(24px);
    transition: transform 0.25s var(--spring-bounce);
}

.medallion-icon {
    font-size: 1.25rem;
    line-height: 1;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.2));
}

/* Compact Card Body */
.classroom-card-body {
    display: flex;
    flex-direction: column;
    gap: 0.12rem;
    min-width: 0;
    padding: 1.2rem 0.75rem 0.7rem;
    transform: translateZ(12px);
}

.classroom-card-name {
    font-family: var(--font-display);
    font-size: 0.98rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--px-ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.classroom-card-meta {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.classroom-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    margin-top: 0.45rem;
}

.classroom-card-count {
    padding: 0.18rem 0.5rem;
    border-radius: 9999px;
    background: color-mix(in srgb, var(--room-accent) 14%, var(--px-surface-card));
    box-shadow: inset 0 1px 2px rgba(var(--px-shadow-rgb), 0.1);
    color: color-mix(in srgb, var(--room-accent) 60%, var(--px-ink));
    font-size: 0.68rem;
    font-weight: 800;
    white-space: nowrap;
}

.card-enter {
    flex-shrink: 0;
    border-radius: 999px;
    background: var(--room-ledge);
}

.card-enter-face {
    display: block;
    padding: 0.32rem 0.75rem;
    border-radius: inherit;
    background: linear-gradient(180deg,
        color-mix(in srgb, var(--room-accent) 62%, #FFFFFF) 0%,
        var(--room-accent) 55%,
        color-mix(in srgb, var(--room-accent) 80%, var(--room-ledge)) 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5), inset 0 -2px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    color: #FFFFFF;
    transform: translateY(-3.5px);
    transition: transform 0.2s var(--spring-bounce);
}

/* ==========================================================================
   VIEW MODE 2: ULTRA-COMPACT LIST TILES
   ========================================================================== */
.classrooms-compact-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.classroom-list-row {
    --room-accent: #4F46E5;
    --room-ledge: #312E81;
    --room-soft: var(--px-tint-info-bg);

    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.45rem 0.75rem;
    border-radius: 16px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--room-soft) 100%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 3.5px 0 color-mix(in srgb, var(--room-accent) 40%, var(--px-ledge)),
        0 6px 14px -6px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 1px 0 var(--px-rim-top);
    text-decoration: none;
    color: inherit;
    transition: transform 0.15s var(--spring-bounce), box-shadow 0.15s ease;
    cursor: pointer;
    animation: card-pop 0.4s var(--spring-bounce) backwards;
    animation-delay: calc(var(--i, 0) * 40ms);
}

.classroom-list-row:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5.5px 0 color-mix(in srgb, var(--room-accent) 40%, var(--px-ledge)),
        0 10px 18px -6px rgba(var(--px-shadow-rgb), 0.28);
}

.classroom-list-row:active {
    transform: translateY(1.5px);
    box-shadow: 0 1.5px 0 color-mix(in srgb, var(--room-accent) 40%, var(--px-ledge));
}

.classroom-list-row.room-theme-forest { --room-accent: #059669; --room-ledge: #065F46; --room-soft: color-mix(in srgb, var(--px-tint-success-bg) 40%, var(--px-surface-card)); }
.classroom-list-row.room-theme-ocean { --room-accent: #0284C7; --room-ledge: #075985; --room-soft: color-mix(in srgb, var(--px-tint-info-bg) 40%, var(--px-surface-card)); }
.classroom-list-row.room-theme-cosmic { --room-accent: #7C3AED; --room-ledge: #5B21B6; --room-soft: color-mix(in srgb, var(--px-tint-magic-bg) 40%, var(--px-surface-card)); }
.classroom-list-row.room-theme-volcano { --room-accent: #EA580C; --room-ledge: #9A3412; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 40%, var(--px-surface-card)); }
.classroom-list-row.is-code { --room-accent: #D97706; --room-ledge: #92400E; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 40%, var(--px-surface-card)); }

.list-medallion {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: radial-gradient(circle at 32% 26%,
        color-mix(in srgb, var(--room-accent) 30%, #FFFFFF) 0%,
        var(--room-accent) 58%,
        var(--room-ledge) 100%);
    border: 2px solid var(--px-surface-card);
    box-shadow: 0 2px 0 var(--room-ledge);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.list-icon {
    font-size: 1.2rem;
    line-height: 1;
}

.list-main-info {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    flex: 1;
    min-width: 0;
}

.list-title-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.list-title {
    font-family: var(--font-display);
    font-size: 0.94rem;
    font-weight: 700;
    color: var(--px-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.list-kicker {
    font-size: 0.54rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 0.08rem 0.35rem;
    border-radius: 4px;
    background: color-mix(in srgb, var(--room-accent) 15%, var(--px-surface-card));
    color: color-mix(in srgb, var(--room-accent) 70%, var(--px-ink));
    white-space: nowrap;
}

.list-meta {
    font-size: 0.72rem;
    color: var(--px-ink-muted);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.list-actions-right {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
}

.list-count-badge {
    font-size: 0.68rem;
    font-weight: 800;
    padding: 0.18rem 0.48rem;
    border-radius: 9999px;
    background: color-mix(in srgb, var(--room-accent) 14%, var(--px-surface-card));
    color: color-mix(in srgb, var(--room-accent) 60%, var(--px-ink));
    white-space: nowrap;
}

.list-enter-btn {
    background: var(--room-ledge);
    border-radius: 999px;
    display: inline-block;
}

.list-enter-face {
    display: block;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    color: #FFFFFF;
    background: linear-gradient(180deg,
        color-mix(in srgb, var(--room-accent) 62%, #FFFFFF) 0%,
        var(--room-accent) 55%,
        color-mix(in srgb, var(--room-accent) 80%, var(--room-ledge)) 100%);
    padding: 0.28rem 0.65rem;
    border-radius: inherit;
    transform: translateY(-2.5px);
    transition: transform 0.15s ease;
}

.classroom-list-row:hover .list-enter-face {
    transform: translateY(-4px);
}

.classroom-list-row:active .list-enter-face {
    transform: translateY(-1px);
}

/* ==========================================================================
   EMPTY STATE & NO SEARCH MATCH
   ========================================================================== */
.classrooms-no-match {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1rem;
    border-radius: 20px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 4px 0 var(--px-ledge),
        0 10px 20px -8px rgba(var(--px-shadow-rgb), 0.3),
        inset 0 1px 0 var(--px-rim-top);
    text-align: center;
}

.no-match-blob {
    width: 56px;
    height: 54px;
    animation: no-match-bob 3.6s ease-in-out infinite;
}

.blob-stop-mid { stop-color: var(--px-orb-5); }
.blob-stop-lo { stop-color: color-mix(in srgb, var(--px-orb-5) 60%, #18143B); }

.no-match-question {
    font-family: var(--font-display);
    font-size: 16px;
    font-weight: 700;
    fill: var(--px-accent);
}

.no-match-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--px-ink);
}

.no-match-reset {
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: var(--px-secondary-pedestal);
    cursor: pointer;
}

.no-match-reset-face {
    display: block;
    padding: 0.45rem 1rem;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 60%);
    box-shadow: inset 0 1.5px 0 rgba(255, 255, 255, 0.45);
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--px-secondary-ink);
    transform: translateY(-3px);
    transition: transform 0.2s var(--spring-bounce);
}

.no-match-reset:hover .no-match-reset-face {
    transform: translateY(-4.5px);
}

.no-match-reset:active .no-match-reset-face {
    transform: translateY(-1px);
}

.classrooms-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 2rem 1.25rem;
    border-radius: 20px;
    background: var(--px-surface-card);
    border: 2px dashed var(--px-line);
    text-align: center;
}

.classrooms-empty-emoji {
    font-size: 2.2rem;
}

.classrooms-empty-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.15rem;
    color: var(--px-ink);
}

.classrooms-empty-desc {
    margin: 0;
    max-width: 24rem;
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--px-ink-muted);
}

/* ==========================================================================
   ANIMATIONS & ACCESSIBILITY
   ========================================================================== */
@keyframes card-pop {
    0% { transform: scale(0.85); opacity: 0; }
    60% { transform: scale(1.02); opacity: 1; }
    100% { transform: scale(1); }
}

@keyframes no-match-bob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ==========================================================================
   RESPONSIVE DESIGN
   ========================================================================== */
@media (max-width: 820px) {
    .classrooms-hub-layout {
        grid-template-columns: 1fr;
        gap: 0.85rem;
    }

    .classrooms-hub-sidebar {
        position: static;
    }

    .classrooms-compact-grid {
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 180px), 1fr));
    }
}

@media (max-width: 520px) {
    .deck-top-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.4rem;
    }

    .deck-view-switch {
        align-self: flex-end;
    }

    .list-kicker {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    .classroom-card,
    .classroom-list-row,
    .no-match-blob {
        animation: none !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
