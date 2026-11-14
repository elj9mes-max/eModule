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

/* Desktop-only 3D tilt: one pointer listener on the grid, writes batched in rAF. */
const canTilt = ref(false);
let tiltFrame = 0;

const handleGridPointerMove = (event) => {
    if (!canTilt.value) {
        return;
    }

    const card = event.target.closest?.('.classroom-card');
    if (!card) {
        return;
    }

    const { clientX, clientY } = event;
    cancelAnimationFrame(tiltFrame);
    tiltFrame = requestAnimationFrame(() => {
        const rect = card.getBoundingClientRect();
        const offsetX = (clientX - rect.left) / rect.width - 0.5;
        const offsetY = (clientY - rect.top) / rect.height - 0.5;
        card.style.setProperty('--ry', `${(offsetX * 12).toFixed(2)}deg`);
        card.style.setProperty('--rx', `${(offsetY * -9).toFixed(2)}deg`);
    });
};

const handleGridPointerOut = (event) => {
    const card = event.target.closest?.('.classroom-card');
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
});

onBeforeUnmount(() => cancelAnimationFrame(tiltFrame));
</script>

<template>
    <Head title="My Classrooms • WEBREAD" />

    <StudentLayout :user="user" active-nav="classes">
        <section class="clay-classrooms-page" aria-labelledby="classrooms-heading">
            <StudentJoinCodeCard />

            <div v-if="showSearch" class="classrooms-toolbar">
                <div class="clay-search" :class="{ 'has-query': searchQuery }" role="search">
                    <label for="classroom-search" class="sr-only">Search classrooms</label>

                    <!-- 3D clay magnifier: glossy lens + chunky handle, lit from top-left -->
                    <span class="search-orb" aria-hidden="true">
                        <svg viewBox="0 0 40 40" width="34" height="34">
                            <defs>
                                <radialGradient id="pxSearchLens" cx="34%" cy="30%" r="75%">
                                    <stop offset="0%" class="lens-stop-hi" />
                                    <stop offset="55%" class="lens-stop-mid" />
                                    <stop offset="100%" class="lens-stop-lo" />
                                </radialGradient>
                                <linearGradient id="pxSearchHandle" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" class="handle-stop-hi" />
                                    <stop offset="100%" class="handle-stop-lo" />
                                </linearGradient>
                            </defs>
                            <ellipse cx="21" cy="37" rx="13" ry="2.4" class="orb-contact-shadow" />
                            <rect x="23" y="22" width="8" height="15" rx="4" transform="rotate(-45 27 29.5)" fill="url(#pxSearchHandle)" />
                            <circle cx="16" cy="16" r="12.5" class="lens-rim" />
                            <circle cx="16" cy="16" r="9" fill="url(#pxSearchLens)" />
                            <ellipse cx="12.5" cy="11.5" rx="4" ry="2.4" transform="rotate(-35 12.5 11.5)" fill="#FFFFFF" opacity="0.85" />
                        </svg>
                    </span>

                    <input
                        id="classroom-search"
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="search"
                        class="search-input"
                        placeholder="Find a class, teacher or grade…"
                        autocomplete="off"
                        enterkeyhint="search"
                        @keydown.esc="clearSearch"
                    />

                    <transition name="search-pop">
                        <span v-if="searchQuery" class="search-count" aria-hidden="true">
                            {{ filteredClassrooms.length }} found
                        </span>
                    </transition>

                    <transition name="search-pop">
                        <button v-if="searchQuery" type="button" class="search-clear" aria-label="Clear search" @click="clearSearch">
                            <span class="search-clear-face" aria-hidden="true">✕</span>
                        </button>
                    </transition>

                    <span class="sr-only" aria-live="polite">
                        {{ searchQuery ? `${filteredClassrooms.length} ${filteredClassrooms.length === 1 ? 'classroom' : 'classrooms'} found` : '' }}
                    </span>
                </div>
            </div>

            <div
                v-if="hasAnything"
                class="classrooms-grid"
                @pointermove.passive="handleGridPointerMove"
                @pointerout="handleGridPointerOut"
            >
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
                    <!-- Lit diorama scene: key light top-left, rolling clay hills, floating orbs -->
                    <span class="card-scene" aria-hidden="true">
                        <span class="scene-cloud scene-cloud--1"></span>
                        <span class="scene-cloud scene-cloud--2"></span>
                        <span class="scene-orb scene-orb--1"></span>
                        <span class="scene-orb scene-orb--2"></span>
                        <span class="scene-orb scene-orb--3"></span>
                        <svg class="scene-hills" viewBox="0 0 320 70" preserveAspectRatio="none">
                            <path class="hill-back" d="M0,40 C55,14 120,46 185,24 C240,6 290,30 320,20 L320,70 L0,70 Z" />
                            <path class="hill-front" d="M0,54 C70,30 140,64 215,40 C262,26 296,44 320,38 L320,70 L0,70 Z" />
                        </svg>
                        <span class="scene-kicker">{{ card.kicker }}</span>
                    </span>

                    <!-- 3D clay medallion that breaks out of the scene -->
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

                <div v-if="searchQuery && filteredClassrooms.length === 0" class="classrooms-no-match">
                    <!-- Puzzled clay blob mascot -->
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

            <div v-else class="classrooms-empty">
                <span class="classrooms-empty-emoji" aria-hidden="true">🏫</span>
                <h2 class="classrooms-empty-title">No classrooms yet!</h2>
                <p class="classrooms-empty-desc">
                    Ask your teacher for your 6-digit classroom code and enter it above to join your first classroom.
                </p>
            </div>
        </section>
    </StudentLayout>
</template>

<style scoped>
.clay-classrooms-page {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    padding-top: 0.15rem;
    font-family: var(--font-body);
}

.classrooms-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
}

.classrooms-heading {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.35rem, 3.5vw, 1.75rem);
    font-weight: 700;
    color: var(--px-ink);
}

.classrooms-subheading {
    margin: 0.15rem 0 0;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

/* ==========================================================================
   COMPACT CLAY SEARCH PILL
   Raised clay tray → pressed-in input well → springy chips & 3D clear key.
   ========================================================================== */
.clay-search {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    width: 100%;
    max-width: 460px;
    min-height: 56px;
    padding: 5px 6px 5px 7px;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 5px 0 var(--px-ledge),
        0 14px 22px -12px rgba(var(--px-shadow-rgb), 0.45),
        inset 0 2px 0 var(--px-rim-top);
}

.clay-search:focus-within {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.search-orb {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    transform-origin: 40% 85%;
}

.clay-search:focus-within .search-orb {
    animation: search-orb-wiggle 0.6s var(--spring-bounce);
}

.lens-stop-hi { stop-color: var(--px-secondary-lit); }
.lens-stop-mid { stop-color: var(--px-secondary); }
.lens-stop-lo { stop-color: var(--px-secondary-pedestal); }
.handle-stop-hi { stop-color: var(--px-accent-lit); }
.handle-stop-lo { stop-color: var(--px-accent-belly); }

.lens-rim {
    fill: var(--px-accent);
    stroke: var(--px-accent-pedestal);
    stroke-width: 1.5;
}

.orb-contact-shadow {
    fill: rgba(var(--px-shadow-rgb), 0.22);
}

.search-input {
    flex: 1;
    min-width: 0;
    height: 40px;
    padding: 0 0.95rem;
    border: 0;
    border-radius: 999px;
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--px-ink);
    caret-color: var(--px-focus-stroke);
    -webkit-appearance: none;
    appearance: none;
}

.search-input::placeholder {
    color: var(--px-ink-subtle);
    opacity: 1;
}

.search-input:focus {
    outline: none;
}

.search-input::-webkit-search-cancel-button,
.search-input::-webkit-search-decoration {
    -webkit-appearance: none;
}

.search-count {
    flex-shrink: 0;
    padding: 0.28rem 0.65rem;
    border-radius: 999px;
    background: var(--px-tint-info-bg);
    box-shadow: inset 0 2px 0 var(--px-rim-top), 0 2px 0 var(--px-tint-info-line);
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--px-tint-info-ink);
    white-space: nowrap;
}

/* 3D clear key: the button is the dark edge, the face rides above it */
.search-clear {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: var(--px-danger-pedestal);
    cursor: pointer;
    filter: drop-shadow(0 4px 4px rgba(var(--px-shadow-rgb), 0.28));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.search-clear-face {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-danger-lit) 0%, var(--px-danger) 60%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -2px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 0.9rem;
    font-weight: 700;
    color: #FFFFFF;
    transform: translateY(-4px);
    transition: transform 0.25s var(--spring-bounce);
}

@media (hover: hover) {
    .search-clear:hover .search-clear-face {
        transform: translateY(-6px);
    }
}

.search-clear:active .search-clear-face {
    transform: translateY(-1px) scale(0.96, 0.92);
    transition-duration: 0.06s;
}

.search-clear:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.search-pop-enter-active {
    transition: transform 0.4s var(--spring-bounce), opacity 0.2s ease;
}

.search-pop-leave-active {
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.search-pop-enter-from,
.search-pop-leave-to {
    opacity: 0;
    transform: scale(0.5);
}

@keyframes search-orb-wiggle {
    0% { transform: scale(1) rotate(0deg); }
    35% { transform: scale(1.18, 0.88) rotate(-12deg); }
    65% { transform: scale(0.94, 1.08) rotate(8deg); }
    100% { transform: scale(1) rotate(0deg); }
}

@media (max-width: 380px) {
    .search-count {
        display: none;
    }
}

.classrooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
    gap: 0.9rem;
}

/* ==========================================================================
   3D CLAY CLASSROOM DIORAMA CARD
   One key light top-left; every shadow is tinted with the room hue.
   ========================================================================== */
.classroom-card {
    --room-accent: #4F46E5;
    --room-ledge: #312E81;
    --room-soft: var(--px-tint-info-bg);
    --lift: 0px;

    position: relative;
    display: flex;
    flex-direction: column;
    border-radius: 30px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--room-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 6px 0 color-mix(in srgb, var(--room-accent) 40%, var(--px-ledge)),
        0 16px 28px -10px color-mix(in srgb, var(--room-accent) 45%, rgba(var(--px-shadow-rgb), 0.5)),
        inset 0 2px 0 var(--px-rim-top),
        inset 0 -5px 0 rgba(var(--px-shadow-rgb), 0.05);
    color: inherit;
    text-decoration: none;
    transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)) translateY(var(--lift));
    transform-style: preserve-3d;
    transition: transform 0.35s var(--spring-bounce);
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    animation: card-pop 0.6s var(--spring-bounce) backwards;
    animation-delay: calc(var(--i, 0) * 70ms);
}

/* Bigger hover shadow lives on ::after and only fades opacity */
.classroom-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    pointer-events: none;
    box-shadow: 0 30px 40px -14px color-mix(in srgb, var(--room-accent) 55%, rgba(var(--px-shadow-rgb), 0.6));
    opacity: 0;
    transition: opacity 0.3s ease;
}

@media (hover: hover) {
    .classroom-card:hover {
        --lift: -6px;
    }

    .classroom-card:hover::after {
        opacity: 1;
    }

    .classroom-card:hover .scene-orb,
    .classroom-card:hover .scene-cloud,
    .classroom-card:hover .medallion-icon {
        animation-play-state: running;
    }

    .classroom-card:hover .card-enter-face {
        transform: translateY(-7px);
    }
}

.classroom-card:active {
    --lift: 1px;
    transition-duration: 0.08s;
}

.classroom-card:active .card-enter-face {
    transform: translateY(-1px) scale(0.98, 0.94);
    transition-duration: 0.06s;
}

.classroom-card:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

.classroom-card:focus-visible .scene-orb,
.classroom-card:focus-visible .scene-cloud {
    animation-play-state: running;
}

.classroom-card.room-theme-forest { --room-accent: #059669; --room-ledge: #065F46; --room-soft: color-mix(in srgb, var(--px-tint-success-bg) 55%, var(--px-surface-card)); }
.classroom-card.room-theme-ocean { --room-accent: #0284C7; --room-ledge: #075985; --room-soft: color-mix(in srgb, var(--px-tint-info-bg) 55%, var(--px-surface-card)); }
.classroom-card.room-theme-cosmic { --room-accent: #7C3AED; --room-ledge: #5B21B6; --room-soft: color-mix(in srgb, var(--px-tint-magic-bg) 55%, var(--px-surface-card)); }
.classroom-card.room-theme-volcano { --room-accent: #EA580C; --room-ledge: #9A3412; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)); }
.classroom-card.is-code { --room-accent: #D97706; --room-ledge: #92400E; --room-soft: color-mix(in srgb, var(--px-tint-warn-bg) 55%, var(--px-surface-card)); }

/* ---------- Diorama scene ---------- */
.card-scene {
    position: relative;
    display: block;
    height: 104px;
    margin: 8px 8px 0;
    border-radius: 23px;
    overflow: hidden;
    background:
        radial-gradient(circle at 16% 8%, rgba(255, 255, 255, calc(0.75 * var(--px-gloss, 1))) 0%, rgba(255, 255, 255, 0) 38%),
        linear-gradient(172deg,
            color-mix(in srgb, var(--room-accent) 24%, var(--px-surface-card)) 0%,
            color-mix(in srgb, var(--room-accent) 58%, var(--px-surface-card)) 100%);
    box-shadow:
        inset 0 3px 8px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 -2px 0 rgba(255, 255, 255, 0.35);
}

.scene-hills {
    position: absolute;
    left: 0;
    bottom: -1px;
    width: 100%;
    height: 52px;
}

.hill-back {
    fill: color-mix(in srgb, var(--room-accent) 72%, #FFFFFF);
    opacity: 0.9;
}

.hill-front {
    fill: var(--room-accent);
    stroke: color-mix(in srgb, var(--room-accent) 45%, #FFFFFF);
    stroke-width: 2;
}

.scene-cloud {
    position: absolute;
    height: 13px;
    border-radius: 999px;
    background: #FFFFFF;
    opacity: calc(0.35 + 0.55 * var(--px-gloss, 1));
    box-shadow: inset 0 -3px 0 color-mix(in srgb, var(--room-accent) 22%, #FFFFFF);
    animation: scene-drift 6s ease-in-out infinite alternate paused;
}

.scene-cloud::before {
    content: '';
    position: absolute;
    left: 22%;
    bottom: 6px;
    width: 46%;
    height: 16px;
    border-radius: 50%;
    background: inherit;
}

.scene-cloud--1 { top: 16px; left: 30%; width: 44px; }
.scene-cloud--2 { top: 30px; left: 68%; width: 32px; animation-delay: -3s; }

/* Clay spheres: layered radial light, tinted contact shadow */
.scene-orb {
    position: absolute;
    border-radius: 50%;
    box-shadow: 0 4px 6px -2px rgba(var(--px-shadow-rgb), 0.35);
    animation: scene-bob 3.6s ease-in-out infinite paused;
}

.scene-orb--1 {
    top: 12px;
    right: 18%;
    width: 16px;
    height: 16px;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #FFE066 34%, #F59E0B 78%, #B45309 100%);
}

.scene-orb--2 {
    top: 44px;
    left: 18%;
    width: 10px;
    height: 10px;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #FF9EB5 36%, #E11D48 100%);
    animation-delay: -1.2s;
}

.scene-orb--3 {
    top: 20px;
    right: 6%;
    width: 9px;
    height: 9px;
    background: radial-gradient(circle at 32% 26%, #FFFFFF 0%, #7DE7F7 36%, #0891B2 100%);
    animation-delay: -2.4s;
}

.scene-kicker {
    position: absolute;
    top: 9px;
    left: 10px;
    padding: 0.16rem 0.6rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--px-surface-card) 88%, transparent);
    box-shadow: inset 0 1px 0 var(--px-rim-top), 0 2px 4px rgba(var(--px-shadow-rgb), 0.16);
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--room-accent) 45%, var(--px-ink));
}

/* ---------- Clay medallion breaking out of the scene edge ---------- */
.card-medallion {
    position: absolute;
    top: 74px;
    left: 20px;
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: radial-gradient(circle at 32% 26%,
        color-mix(in srgb, var(--room-accent) 30%, #FFFFFF) 0%,
        var(--room-accent) 58%,
        var(--room-ledge) 100%);
    border: 4px solid var(--px-surface-card);
    box-shadow:
        0 5px 0 var(--room-ledge),
        0 12px 16px -6px color-mix(in srgb, var(--room-accent) 60%, rgba(var(--px-shadow-rgb), 0.6));
    transform: translateZ(34px);
}

/* Specular glint from the top-left key light */
.card-medallion::before {
    content: '';
    position: absolute;
    top: 7px;
    left: 11px;
    width: 20px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: rotate(-28deg);
}

.medallion-icon {
    position: relative;
    font-size: 1.75rem;
    line-height: 1;
    filter: drop-shadow(0 2px 0 rgba(0, 0, 0, 0.18));
    animation: medallion-wiggle 0.9s var(--spring-bounce) infinite alternate paused;
}

/* ---------- Body ---------- */
.classroom-card-body {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 0;
    padding: 2.35rem 1.1rem 1.1rem;
    transform: translateZ(16px);
}

.classroom-card-name {
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--px-ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.classroom-card-meta {
    font-size: 0.78rem;
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
    gap: 0.6rem;
    margin-top: 0.65rem;
}

.classroom-card-count {
    padding: 0.28rem 0.7rem;
    border-radius: 9999px;
    background: color-mix(in srgb, var(--room-accent) 14%, var(--px-surface-card));
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.12);
    color: color-mix(in srgb, var(--room-accent) 45%, var(--px-ink));
    font-size: 0.74rem;
    font-weight: 800;
    white-space: nowrap;
}

/* Decorative 3D press button: the span is the dark edge, the face rides above it */
.card-enter {
    flex-shrink: 0;
    border-radius: 999px;
    background: var(--room-ledge);
    filter: drop-shadow(0 5px 6px color-mix(in srgb, var(--room-accent) 40%, transparent));
}

.card-enter-face {
    display: block;
    padding: 0.5rem 1rem;
    border-radius: inherit;
    background: linear-gradient(180deg,
        color-mix(in srgb, var(--room-accent) 62%, #FFFFFF) 0%,
        var(--room-accent) 55%,
        color-mix(in srgb, var(--room-accent) 80%, var(--room-ledge)) 100%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -3px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 0.86rem;
    font-weight: 700;
    color: #FFFFFF;
    text-shadow: 0 1px 0 var(--room-ledge);
    transform: translateY(-5px);
    transition: transform 0.25s var(--spring-bounce);
}

@keyframes card-pop {
    0% { transform: perspective(900px) scale(0.6); opacity: 0; }
    60% { transform: perspective(900px) scale(1.06, 0.95); opacity: 1; }
    80% { transform: perspective(900px) scale(0.98, 1.03); }
    100% { transform: perspective(900px) scale(1); }
}

@keyframes scene-bob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

@keyframes scene-drift {
    from { transform: translateX(-8px); }
    to { transform: translateX(10px); }
}

@keyframes medallion-wiggle {
    from { transform: rotate(-8deg) scale(1.04); }
    to { transform: rotate(8deg) scale(1.1); }
}

@media (prefers-reduced-motion: reduce) {
    .classroom-card,
    .classroom-card *,
    .classroom-card::after {
        animation: none !important;
        transition-duration: 0.01ms !important;
    }
}

.classrooms-no-match {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
    padding: 1.4rem 1rem 1.5rem;
    border-radius: 28px;
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-soft) 100%);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 5px 0 var(--px-ledge),
        0 14px 24px -12px rgba(var(--px-shadow-rgb), 0.4),
        inset 0 2px 0 var(--px-rim-top);
    text-align: center;
    animation: search-pop-in 0.55s var(--spring-bounce) backwards;
}

.no-match-blob {
    width: 72px;
    height: 70px;
    animation: no-match-bob 3.6s ease-in-out infinite;
}

.blob-stop-mid { stop-color: var(--px-orb-5); }
.blob-stop-lo { stop-color: color-mix(in srgb, var(--px-orb-5) 60%, #18143B); }

.no-match-question {
    font-family: var(--font-display);
    font-size: 16px;
    font-weight: 700;
    fill: var(--px-accent);
    stroke: var(--px-accent-pedestal);
    stroke-width: 0.8px;
}

.no-match-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 700;
    color: var(--px-ink);
    overflow-wrap: anywhere;
}

.no-match-reset {
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: var(--px-secondary-pedestal);
    cursor: pointer;
    filter: drop-shadow(0 5px 6px rgba(var(--px-shadow-rgb), 0.28));
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.no-match-reset-face {
    display: block;
    min-height: 44px;
    padding: 0.7rem 1.3rem;
    border-radius: inherit;
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 60%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.45), inset 0 -3px 0 rgba(0, 0, 0, 0.12);
    font-family: var(--font-display);
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--px-secondary-ink);
    transform: translateY(-5px);
    transition: transform 0.25s var(--spring-bounce);
}

@media (hover: hover) {
    .no-match-reset:hover .no-match-reset-face {
        transform: translateY(-7px);
    }
}

.no-match-reset:active .no-match-reset-face {
    transform: translateY(-1px) scale(0.98, 0.95);
    transition-duration: 0.06s;
}

.no-match-reset:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

@keyframes search-pop-in {
    0% { transform: scale(0.85); opacity: 0; }
    60% { transform: scale(1.03, 0.97); opacity: 1; }
    100% { transform: scale(1); }
}

@keyframes no-match-bob {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(-4deg); }
}

@media (prefers-reduced-motion: reduce) {
    .search-orb,
    .no-match-blob,
    .classrooms-no-match {
        animation: none !important;
    }
}

.classrooms-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    padding: 2.5rem 1.5rem;
    border-radius: 24px;
    background: var(--px-surface-card);
    border: 2.5px dashed var(--px-line);
    text-align: center;
}

.classrooms-empty-emoji {
    font-size: 2.6rem;
}

.classrooms-empty-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.3rem;
    color: var(--px-ink);
}

.classrooms-empty-desc {
    margin: 0;
    max-width: 28rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--px-ink-muted);
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
</style>
