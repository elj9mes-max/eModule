<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { spawnPixarBurst, usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';

const {
    playPop,
    playBonus,
    playClick,
} = useSound();

const { activeWorld } = usePixarTheme();
useWorldOnDocument(activeWorld);

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    classrooms: {
        type: Array,
        default: () => [],
    },
    classmates: {
        type: Array,
        default: () => [],
    },
    active_classroom_id: {
        type: Number,
        default: null,
    },
});

const AVATAR_EMOJIS = {
    fox: '🦊',
    astro: '🐱',
    knight: '🐻',
    owl: '🦉',
    bear: '🐼',
    dragon: '🐲',
};

// Search & Filter state (List-only compact mode)
const searchQuery = ref('');
const activeRoomFilter = ref(props.active_classroom_id ? String(props.active_classroom_id) : 'all');
const sortBy = ref('xp'); // 'xp' | 'stars' | 'level' | 'name'

// High Five Cheer Toast & Tracking
const cheeredClassmates = ref(new Set());
const cheerToast = ref('');
let toastTimeout = null;

// Inspect Modal
const inspectModalOpen = ref(false);
const inspectingClassmate = ref(null);

const handleKeydown = (event) => {
    if (event.key === 'Escape' && inspectModalOpen.value) {
        closeInspectModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
});

const getAvatarEmoji = (avatarKey) => {
    return AVATAR_EMOJIS[avatarKey] || '🦊';
};

// Filtered and sorted classmates
const filteredClassmates = computed(() => {
    let list = [...props.classmates];

    // Filter by classroom pill
    if (activeRoomFilter.value !== 'all') {
        const roomId = Number(activeRoomFilter.value);
        list = list.filter((cm) =>
            (cm.shared_classrooms || []).some((r) => r.id === roomId),
        );
    }

    // Filter by search query
    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter((cm) => {
            const nameMatch = cm.name.toLowerCase().includes(q);
            const roomMatch = (cm.shared_classrooms || []).some((r) =>
                r.name.toLowerCase().includes(q),
            );
            return nameMatch || roomMatch;
        });
    }

    // Sort
    list.sort((a, b) => {
        if (sortBy.value === 'xp') {
            return (b.xp || 0) - (a.xp || 0);
        }
        if (sortBy.value === 'stars') {
            return (b.stars || 0) - (a.stars || 0);
        }
        if (sortBy.value === 'level') {
            return (b.level || 1) - (a.level || 1);
        }
        if (sortBy.value === 'name') {
            return a.name.localeCompare(b.name);
        }
        return 0;
    });

    return list;
});

// Classroom counts
const classroomCounts = computed(() => {
    const counts = { all: props.classmates.length };
    props.classrooms.forEach((room) => {
        counts[room.id] = props.classmates.filter((cm) =>
            (cm.shared_classrooms || []).some((r) => r.id === room.id),
        ).length;
    });
    return counts;
});

const selectRoomFilter = (roomId) => {
    activeRoomFilter.value = String(roomId);
    playPop();
};

const clearSearch = () => {
    searchQuery.value = '';
    playPop();
};

const cheerForClassmate = (classmate, event) => {
    playBonus();
    if (event?.currentTarget) {
        spawnPixarBurst(event.currentTarget, 14, activeWorld.value);
    }

    cheeredClassmates.value.add(classmate.id);

    cheerToast.value = classmate.is_current_user
        ? '🎉 High five to yourself, reading hero!'
        : `🎉 You cheered for ${classmate.name.split(' ')[0]}!`;

    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
    toastTimeout = setTimeout(() => {
        cheerToast.value = '';
    }, 2600);
};

const openInspectModal = (classmate) => {
    playClick();
    inspectingClassmate.value = classmate;
    inspectModalOpen.value = true;
};

const closeInspectModal = () => {
    playClick();
    inspectModalOpen.value = false;
    inspectingClassmate.value = null;
};

const goToProfile = (classmateId) => {
    playClick();
    router.visit(`/students/classmates/${classmateId}`);
};

const goToClasses = () => {
    playClick();
    router.visit('/students/classes');
};

const rankBadgeClass = (index) => {
    if (sortBy.value === 'name') {
        return '';
    }
    if (index === 0) {
        return 'is-rank-1';
    }
    if (index === 1) {
        return 'is-rank-2';
    }
    if (index === 2) {
        return 'is-rank-3';
    }
    return '';
};
</script>

<template>
    <Head title="Classmates • WEBREAD" />

    <StudentLayout
        :user="user"
        active-nav="classmates"
    >
        <section class="clay-classmates-page" aria-labelledby="classmates-heading">
            <!-- =========================================================================
                 1. COMPACT 3D PIXAR CONTROL DECK
                 ========================================================================= -->
            <header class="classmates-control-deck">
                <span class="deck-specular-gloss" aria-hidden="true"></span>

                <!-- Top Row: 3D Medallion + Title + Counter + Tactile Sort Switcher -->
                <div class="deck-top-row">
                    <div class="deck-title-block">
                        <span class="deck-friends-medallion" aria-hidden="true">
                            <span class="medallion-gloss"></span>
                            <span class="medallion-emoji">👥</span>
                        </span>
                        <div class="deck-title-meta">
                            <div class="deck-title-inline">
                                <h1 id="classmates-heading" class="deck-title">Classmates</h1>
                                <span class="deck-counter-pill">
                                    {{ filteredClassmates.length }}
                                </span>
                            </div>
                            <p class="deck-subtitle">
                                Classroom Explorers Directory
                            </p>
                        </div>
                    </div>

                    <!-- Tactile 3D Sort Segmented Bar -->
                    <div class="deck-sort-group" role="group" aria-label="Sort classmates by">
                        <button
                            type="button"
                            class="sort-pill-btn"
                            :class="{ 'is-active': sortBy === 'xp' }"
                            @click="sortBy = 'xp'; playClick()"
                        >
                            <span aria-hidden="true">⚡</span>
                            <span>XP</span>
                        </button>
                        <button
                            type="button"
                            class="sort-pill-btn"
                            :class="{ 'is-active': sortBy === 'stars' }"
                            @click="sortBy = 'stars'; playClick()"
                        >
                            <span aria-hidden="true">⭐</span>
                            <span>Stars</span>
                        </button>
                        <button
                            type="button"
                            class="sort-pill-btn"
                            :class="{ 'is-active': sortBy === 'level' }"
                            @click="sortBy = 'level'; playClick()"
                        >
                            <span aria-hidden="true">👑</span>
                            <span>Level</span>
                        </button>
                        <button
                            type="button"
                            class="sort-pill-btn"
                            :class="{ 'is-active': sortBy === 'name' }"
                            @click="sortBy = 'name'; playClick()"
                        >
                            <span>A–Z</span>
                        </button>
                    </div>
                </div>

                <!-- Bottom Row: Compact Search Well + Classroom Filter Pills -->
                <div class="deck-filters-row">
                    <div class="clay-search-well" :class="{ 'has-query': searchQuery }" role="search">
                        <label for="classmate-search-input" class="sr-only">Find classmate by name or room</label>
                        <span class="search-mini-lens" aria-hidden="true">🔍</span>
                        <input
                            id="classmate-search-input"
                            v-model="searchQuery"
                            type="search"
                            class="search-mini-input"
                            placeholder="Search classmate or room…"
                            autocomplete="off"
                            enterkeyhint="search"
                            @keydown.esc="clearSearch"
                        />
                        <span v-if="searchQuery" class="search-mini-count">{{ filteredClassmates.length }}</span>
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="search-mini-clear"
                            aria-label="Clear search"
                            @click="clearSearch"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Classroom Filter Pills -->
                    <div
                        v-if="classrooms.length > 0"
                        class="room-pills-scroll"
                        role="tablist"
                        aria-label="Filter by classroom"
                    >
                        <button
                            type="button"
                            class="room-pill-btn"
                            :class="{ 'is-active': activeRoomFilter === 'all' }"
                            role="tab"
                            :aria-selected="activeRoomFilter === 'all'"
                            @click="selectRoomFilter('all')"
                        >
                            <span class="room-pill-face">
                                <span class="room-pill-icon" aria-hidden="true">🏫</span>
                                <span class="room-pill-name">All</span>
                                <span class="room-pill-badge">{{ classroomCounts.all }}</span>
                            </span>
                        </button>

                        <button
                            v-for="room in classrooms"
                            :key="room.id"
                            type="button"
                            class="room-pill-btn"
                            :class="{ 'is-active': activeRoomFilter === String(room.id) }"
                            role="tab"
                            :aria-selected="activeRoomFilter === String(room.id)"
                            @click="selectRoomFilter(room.id)"
                        >
                            <span class="room-pill-face">
                                <span class="room-pill-icon" aria-hidden="true">{{ room.icon || '📚' }}</span>
                                <span class="room-pill-name">{{ room.name }}</span>
                                <span class="room-pill-badge">{{ classroomCounts[room.id] || 0 }}</span>
                            </span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Floating Cheer Toast Notification -->
            <transition name="toast-pop">
                <div v-if="cheerToast" class="cheer-toast-pill" role="status" aria-live="polite">
                    {{ cheerToast }}
                </div>
            </transition>

            <!-- =========================================================================
                 2. COMPACT EMPTY STATES
                 ========================================================================= -->
            <div v-if="classrooms.length === 0" class="clay-empty-card">
                <div class="empty-icon-medallion" aria-hidden="true">🎒</div>
                <h2 class="empty-card-title">Join a Classroom Adventure!</h2>
                <p class="empty-card-desc">
                    Enter a 6-digit class code from your teacher to explore modules and meet your classmates!
                </p>
                <button
                    type="button"
                    class="clay-primary-btn"
                    @click="goToClasses"
                >
                    <span class="btn-face">
                        <span aria-hidden="true">🏫</span>
                        <span>Go to Classrooms</span>
                    </span>
                </button>
            </div>

            <div v-else-if="filteredClassmates.length === 0" class="clay-empty-card">
                <div class="empty-icon-medallion" aria-hidden="true">
                    {{ searchQuery ? '🔍' : '🌱' }}
                </div>
                <h2 class="empty-card-title">
                    {{ searchQuery ? 'No Classmates Found' : 'First Explorer Here!' }}
                </h2>
                <p class="empty-card-desc">
                    {{
                        searchQuery
                            ? `No classmates matched "${searchQuery}". Try another name!`
                            : 'You are the first explorer in this room! Classmates will appear here when they join.'
                    }}
                </p>
                <button
                    v-if="searchQuery"
                    type="button"
                    class="clay-secondary-btn"
                    @click="clearSearch"
                >
                    <span class="btn-face">Clear Search</span>
                </button>
            </div>

            <!-- =========================================================================
                 3. COMPACT 3D PIXAR LIST DIRECTORY (LIST ONLY)
                 ========================================================================= -->
            <ol
                v-else
                class="classmates-list-stage"
                aria-label="Classmates directory"
            >
                <li
                    v-for="(classmate, index) in filteredClassmates"
                    :key="classmate.id"
                    class="classmate-list-item"
                    :style="{ '--i': Math.min(index, 10) }"
                >
                    <article
                        class="classmate-row"
                        :class="[rankBadgeClass(index), { 'is-me': classmate.is_current_user }]"
                    >
                        <span class="row-top-gloss" aria-hidden="true"></span>

                        <!-- Rank Number Pill -->
                        <span class="row-rank-badge" :class="rankBadgeClass(index)" aria-hidden="true">
                            {{ index + 1 }}
                        </span>

                        <!-- 3D Glossy Avatar Orb + Level Pill -->
                        <button
                            type="button"
                            class="row-avatar-btn"
                            :aria-label="`Inspect ${classmate.name}`"
                            @click="openInspectModal(classmate)"
                        >
                            <span
                                class="classmate-avatar-sphere"
                                :style="{ background: classmate.avatarBg }"
                                aria-hidden="true"
                            >
                                <span class="avatar-specular"></span>
                                <span class="avatar-emoji-glyph">{{ getAvatarEmoji(classmate.avatar) }}</span>
                            </span>
                            <span class="classmate-lvl-pill">LV {{ classmate.level }}</span>
                        </button>

                        <!-- Main Identity & Mobile-Inline Stats -->
                        <div class="row-identity-col" @click="openInspectModal(classmate)">
                            <div class="row-name-line">
                                <h2 class="classmate-name" :title="classmate.name">
                                    {{ classmate.name }}
                                </h2>
                                <span v-if="classmate.is_current_user" class="you-badge" aria-label="This is you">
                                    YOU
                                </span>
                            </div>

                            <div class="row-sub-line">
                                <span class="classmate-title-kicker">{{ classmate.level_title }}</span>

                                <div v-if="classmate.shared_classrooms?.length" class="row-rooms-inline">
                                    <span
                                        v-for="room in classmate.shared_classrooms.slice(0, 1)"
                                        :key="room.id"
                                        class="shared-room-tag"
                                        :title="room.name"
                                    >
                                        <span aria-hidden="true">{{ room.icon || '📚' }}</span>
                                        <span class="room-tag-text">{{ room.name }}</span>
                                    </span>
                                    <span
                                        v-if="classmate.shared_classrooms.length > 1"
                                        class="shared-room-more"
                                        :title="`${classmate.shared_classrooms.length - 1} more classrooms`"
                                    >
                                        +{{ classmate.shared_classrooms.length - 1 }}
                                    </span>
                                </div>
                            </div>

                            <!-- Compact Inline Stats (always visible on mobile, hidden on desktop where dedicated column shows) -->
                            <div class="row-mobile-stats" aria-label="Student stats">
                                <span class="mini-stat is-xp" title="Total Experience">
                                    <span aria-hidden="true">⚡</span>
                                    <strong>{{ Number(classmate.xp).toLocaleString() }}</strong>
                                </span>
                                <span class="mini-stat is-stars" title="Reading Stars">
                                    <span aria-hidden="true">⭐</span>
                                    <strong>{{ Number(classmate.stars).toLocaleString() }}</strong>
                                </span>
                                <span class="mini-stat is-quiz" title="Quizzes Passed">
                                    <span aria-hidden="true">🏆</span>
                                    <strong>{{ classmate.quizzes_passed }}</strong>
                                </span>
                            </div>
                        </div>

                        <!-- Desktop Recessed Stats Well -->
                        <div class="row-stats-deck" aria-label="Student stats">
                            <div class="stat-pill stat-xp" title="Total Experience">
                                <span class="stat-icon" aria-hidden="true">⚡</span>
                                <div class="stat-copy">
                                    <span class="stat-val">{{ Number(classmate.xp).toLocaleString() }}</span>
                                    <span class="stat-lbl">XP</span>
                                </div>
                            </div>

                            <div class="stat-pill stat-stars" title="Total Reading Stars">
                                <span class="stat-icon" aria-hidden="true">⭐</span>
                                <div class="stat-copy">
                                    <span class="stat-val">{{ Number(classmate.stars).toLocaleString() }}</span>
                                    <span class="stat-lbl">Stars</span>
                                </div>
                            </div>

                            <div class="stat-pill stat-quizzes" title="Quizzes Passed">
                                <span class="stat-icon" aria-hidden="true">🏆</span>
                                <div class="stat-copy">
                                    <span class="stat-val">{{ classmate.quizzes_passed }}</span>
                                    <span class="stat-lbl">Quiz</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tactile 3D Actions -->
                        <div class="row-actions-strip">
                            <button
                                type="button"
                                class="cheer-btn"
                                :class="{ 'has-cheered': cheeredClassmates.has(classmate.id) }"
                                :aria-label="`Give high five to ${classmate.name}`"
                                @click.stop="cheerForClassmate(classmate, $event)"
                            >
                                <span class="cheer-btn-face">
                                    <span class="cheer-emoji" aria-hidden="true">
                                        {{ cheeredClassmates.has(classmate.id) ? '💖' : '🎉' }}
                                    </span>
                                    <span class="cheer-label">
                                        {{ cheeredClassmates.has(classmate.id) ? 'Cheered' : 'High Five' }}
                                    </span>
                                </span>
                            </button>

                            <button
                                type="button"
                                class="inspect-btn"
                                :aria-label="`Inspect ${classmate.name}'s profile`"
                                @click.stop="openInspectModal(classmate)"
                            >
                                <span class="inspect-btn-face">
                                    <span class="inspect-label">View</span>
                                    <span class="inspect-arrow" aria-hidden="true">›</span>
                                </span>
                            </button>
                        </div>
                    </article>
                </li>
            </ol>

            <!-- =========================================================================
                 4. PIXAR SQUASH-AND-STRETCH INSPECT MODAL
                 ========================================================================= -->
            <transition name="modal-bounce">
                <div
                    v-if="inspectModalOpen && inspectingClassmate"
                    class="modal-backdrop-scrim"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="inspect-modal-title"
                    @click.self="closeInspectModal"
                >
                    <div class="pixar-inspect-modal">
                        <span class="modal-top-gloss" aria-hidden="true"></span>

                        <!-- Top Close Cross -->
                        <button
                            type="button"
                            class="modal-close-cross"
                            aria-label="Close dialog"
                            @click="closeInspectModal"
                        >
                            <span class="close-cross-face" aria-hidden="true">✕</span>
                        </button>

                        <!-- Modal Hero Header -->
                        <div class="modal-hero-deck">
                            <div class="modal-avatar-halo" aria-hidden="true">
                                <span
                                    class="modal-avatar-bubble"
                                    :style="{ background: inspectingClassmate.avatarBg }"
                                >
                                    <span class="avatar-specular"></span>
                                    <span class="modal-emoji-char">
                                        {{ getAvatarEmoji(inspectingClassmate.avatar) }}
                                    </span>
                                </span>
                                <span class="modal-lvl-badge">LV {{ inspectingClassmate.level }}</span>
                            </div>

                            <div class="modal-hero-meta">
                                <span v-if="inspectingClassmate.is_current_user" class="modal-you-pill">
                                    🌟 That's You!
                                </span>
                                <h3 id="inspect-modal-title" class="modal-hero-name">
                                    {{ inspectingClassmate.name }}
                                </h3>
                                <p class="modal-hero-title">{{ inspectingClassmate.level_title }}</p>
                            </div>
                        </div>

                        <!-- Compact 4-Tile Stats Pedestal -->
                        <div class="modal-stats-matrix">
                            <div class="matrix-tile is-stars">
                                <span class="tile-icon" aria-hidden="true">⭐</span>
                                <span class="tile-num">{{ Number(inspectingClassmate.stars).toLocaleString() }}</span>
                                <span class="tile-sub">Stars</span>
                            </div>
                            <div class="matrix-tile is-xp">
                                <span class="tile-icon" aria-hidden="true">⚡</span>
                                <span class="tile-num">{{ Number(inspectingClassmate.xp).toLocaleString() }}</span>
                                <span class="tile-sub">XP</span>
                            </div>
                            <div class="matrix-tile is-quiz">
                                <span class="tile-icon" aria-hidden="true">🏆</span>
                                <span class="tile-num">{{ inspectingClassmate.quizzes_passed }}</span>
                                <span class="tile-sub">Quizzes</span>
                            </div>
                            <div class="matrix-tile is-read">
                                <span class="tile-icon" aria-hidden="true">📖</span>
                                <span class="tile-num">{{ inspectingClassmate.lectures_completed }}</span>
                                <span class="tile-sub">Chapters</span>
                            </div>
                        </div>

                        <!-- Shared Classrooms Section -->
                        <div v-if="inspectingClassmate.shared_classrooms?.length" class="modal-rooms-deck">
                            <h4 class="rooms-deck-heading">
                                <span aria-hidden="true">🏫</span>
                                <span>Shared Classrooms ({{ inspectingClassmate.shared_classrooms.length }})</span>
                            </h4>
                            <div class="rooms-deck-list">
                                <div
                                    v-for="room in inspectingClassmate.shared_classrooms"
                                    :key="room.id"
                                    class="modal-room-chip"
                                >
                                    <span class="room-chip-icon" aria-hidden="true">{{ room.icon || '📚' }}</span>
                                    <div class="room-chip-info">
                                        <span class="room-chip-title">{{ room.name }}</span>
                                        <span v-if="room.grade_level" class="room-chip-sub">{{ room.grade_level }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Side-by-Side 3D Actions -->
                        <div class="modal-actions-tray">
                            <button
                                type="button"
                                class="clay-primary-btn modal-cheer-btn"
                                @click="cheerForClassmate(inspectingClassmate, $event)"
                            >
                                <span class="btn-face">
                                    <span aria-hidden="true">🎉</span>
                                    <span>High Five!</span>
                                </span>
                            </button>

                            <button
                                type="button"
                                class="clay-secondary-btn modal-profile-btn"
                                @click="goToProfile(inspectingClassmate.id)"
                            >
                                <span class="btn-face">
                                    <span>Full Profile</span>
                                    <span aria-hidden="true">→</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   PIXAR 3D GRAPHIC UI — COMPACT LIST DIRECTORY
   Volumetric 3-Point Lighting • Chromatic Contact Shadows • Zero Layout Shift
   ========================================================================== */
.clay-classmates-page {
    --font-display: var(--px-font-display, 'Fredoka', ui-rounded, 'Nunito', system-ui, sans-serif);
    --font-body: var(--px-font-body, 'Nunito', ui-rounded, system-ui, sans-serif);
    --spring-bounce: var(--px-ease-spring, cubic-bezier(0.34, 1.56, 0.64, 1));

    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
    max-width: 780px;
    margin: 0 auto;
    padding: 0.25rem 0 2rem;
    box-sizing: border-box;
    font-family: var(--font-body);
    color: var(--px-ink, #1E1B4B);
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
   1. COMPACT 3D PIXAR CONTROL DECK
   ========================================================================== */
.classmates-control-deck {
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFDF9) 0%, var(--px-surface-soft, #F4F0FF) 100%);
    border: 2.5px solid var(--px-card-border, #CBD5E1);
    border-radius: 20px;
    padding: 0.65rem 0.8rem;
    box-shadow:
        0 4px 0 var(--px-ledge, #CBD5E1),
        0 10px 20px -8px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.16),
        inset 0 2px 0 var(--px-rim-top, rgba(255, 255, 255, 0.92));
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.deck-specular-gloss {
    position: absolute;
    top: 3px;
    left: 16px;
    right: 16px;
    height: 7px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.deck-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.deck-title-block {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}

.deck-friends-medallion {
    position: relative;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: radial-gradient(circle at 32% 24%, #EDE9FE 0%, #8B5CF6 55%, #5B21B6 100%);
    border: 2px solid var(--px-surface-card, #FFFFFF);
    box-shadow:
        0 2.5px 0 #4C1D95,
        0 5px 10px -3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.28),
        inset 0 1.5px 2px rgba(255, 255, 255, 0.72);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.medallion-gloss {
    position: absolute;
    top: 3px;
    left: 5px;
    width: 11px;
    height: 5px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.65);
    transform: rotate(-18deg);
    pointer-events: none;
}

.medallion-emoji {
    font-size: 1.08rem;
    line-height: 1;
    filter: drop-shadow(0 1px 1px rgba(28, 22, 62, 0.28));
}

.deck-title-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.deck-title-inline {
    display: flex;
    align-items: center;
    gap: 0.38rem;
}

.deck-title {
    font-family: var(--font-display);
    font-size: clamp(1.08rem, 0.98rem + 0.5vw, 1.25rem);
    font-weight: 800;
    line-height: 1.1;
    color: var(--px-ink, #1E1B4B);
    margin: 0;
    letter-spacing: -0.01em;
}

.deck-counter-pill {
    padding: 0.06rem 0.42rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #EDE9FE 0%, #DDD6FE 100%);
    border: 1.5px solid #A78BFA;
    box-shadow: 0 1.5px 0 #8B5CF6;
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
    color: #4C1D95;
    line-height: 1.15;
}

.deck-subtitle {
    margin: 0.04rem 0 0;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Tactile 3D Sort Segmented Track */
.deck-sort-group {
    display: inline-flex;
    align-items: center;
    gap: 0.16rem;
    padding: 0.18rem;
    border-radius: 9999px;
    background: var(--px-surface-well, #E2E8F0);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    box-shadow: inset 0 1.5px 3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.1);
    flex-shrink: 0;
}

.sort-pill-btn {
    border: none;
    background: transparent;
    padding: 0.24rem 0.52rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--px-ink-muted, #475569);
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    cursor: pointer;
    white-space: nowrap;
    transition: transform 140ms var(--spring-bounce), background 140ms ease, color 140ms ease, box-shadow 140ms ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.sort-pill-btn:focus-visible {
    outline: 2px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.sort-pill-btn.is-active {
    background: linear-gradient(180deg, #A78BFA 0%, #7C3AED 55%, #5B21B6 100%);
    color: #FFFFFF;
    box-shadow:
        0 2px 0 #3B0764,
        inset 0 1px 1px rgba(255, 255, 255, 0.55);
}

.sort-pill-btn:active {
    transform: scale(0.95);
}

/* Filters Row: Search + Room Pills */
.deck-filters-row {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

@media (min-width: 640px) {
    .deck-filters-row {
        flex-direction: row;
        align-items: center;
        gap: 0.55rem;
    }
}

/* Recessed Clay Search Well (Fixed 38px height — never stretches vertically!) */
.clay-search-well {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.38rem;
    width: 100%;
    height: 38px;
    min-height: 38px;
    max-height: 38px;
    flex: 0 0 auto;
    padding: 0 0.65rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, var(--px-surface-well, #EEF2FF) 0%, var(--px-surface-card, #FFFFFF) 100%);
    border: 2px solid var(--px-card-border, #CBD5E1);
    box-shadow:
        0 2px 0 var(--px-ledge, #CBD5E1),
        inset 0 2px 3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.08);
    transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
    box-sizing: border-box;
}

@media (min-width: 640px) {
    .clay-search-well {
        width: 230px;
        flex: 0 0 230px;
    }
}

.clay-search-well:focus-within {
    background: var(--px-surface-card, #FFFFFF);
    border-color: #8B5CF6;
    box-shadow:
        0 2px 0 #6D28D9,
        0 0 0 3px rgba(139, 92, 246, 0.2);
}

.search-mini-lens {
    font-size: 0.82rem;
    line-height: 1;
    opacity: 0.8;
    flex-shrink: 0;
}

.search-mini-input {
    border: none;
    background: transparent;
    width: 100%;
    height: 100%;
    min-width: 0;
    padding: 0;
    font-family: var(--font-body);
    font-size: max(16px, 0.85rem);
    font-weight: 700;
    color: var(--px-ink, #0F172A);
    outline: none;
}

.search-mini-input::placeholder {
    color: var(--px-ink-muted, #64748B);
    font-weight: 600;
}

.search-mini-count {
    font-family: var(--font-display);
    font-size: 0.64rem;
    font-weight: 800;
    color: #5B21B6;
    background: #EDE9FE;
    padding: 0.06rem 0.38rem;
    border-radius: 999px;
    white-space: nowrap;
}

.search-mini-clear {
    border: none;
    background: #EF4444;
    color: #FFFFFF;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.62rem;
    font-weight: 900;
    cursor: pointer;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    box-shadow: 0 1.5px 0 #B91C1C;
}

.search-mini-clear:focus-visible {
    outline: 2px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

/* Room Pills Horizontal Scroll */
.room-pills-scroll {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    overflow-x: auto;
    padding: 1px 2px 3px;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    flex: 1 1 auto;
    min-width: 0;
}

.room-pills-scroll::-webkit-scrollbar {
    display: none;
}

.room-pill-btn {
    border: none;
    background: transparent;
    padding: 0;
    margin-bottom: 2px;
    border-radius: 9999px;
    cursor: pointer;
    flex-shrink: 0;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.room-pill-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.room-pill-face {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    height: 32px;
    padding: 0 0.6rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-soft, #F1F5F9) 100%);
    box-shadow:
        0 2px 0 var(--px-ledge, #CBD5E1),
        inset 0 1px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    color: var(--px-ink, #1E293B);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
    box-sizing: border-box;
    transition: transform 150ms var(--spring-bounce), box-shadow 150ms ease;
}

.room-pill-btn.is-active .room-pill-face {
    background: linear-gradient(180deg, #A78BFA 0%, #7C3AED 52%, #5B21B6 100%);
    border-color: #4C1D95;
    color: #FFFFFF;
    box-shadow:
        0 2.5px 0 #3B0764,
        inset 0 1.5px 1px rgba(255, 255, 255, 0.55);
}

.room-pill-btn:active .room-pill-face {
    transform: translate3d(0, 2px, 0) scaleX(1.03) scaleY(0.95);
    box-shadow: 0 0 0 transparent;
}

.room-pill-icon {
    font-size: 0.8rem;
    line-height: 1;
}

.room-pill-name {
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-pill-badge {
    background: rgba(var(--px-shadow-rgb, 28, 22, 62), 0.1);
    padding: 0.04rem 0.36rem;
    border-radius: 9999px;
    font-size: 0.62rem;
    font-weight: 800;
}

.room-pill-btn.is-active .room-pill-badge {
    background: rgba(255, 255, 255, 0.24);
    color: #FFFFFF;
}

/* ==========================================================================
   FLOATING CHEER TOAST
   ========================================================================== */
.cheer-toast-pill {
    position: fixed;
    top: calc(4.8rem + env(safe-area-inset-top, 0px));
    left: 50%;
    transform: translate3d(-50%, 0, 0);
    background: linear-gradient(180deg, #A78BFA 0%, #7C3AED 55%, #5B21B6 100%);
    color: #FFFFFF;
    border: 2px solid #4C1D95;
    padding: 0.4rem 1rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    white-space: nowrap;
    box-shadow:
        0 4px 0 #3B0764,
        0 10px 24px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.32),
        inset 0 1.5px 1px rgba(255, 255, 255, 0.6);
    z-index: 120;
    pointer-events: none;
}

.toast-pop-enter-active,
.toast-pop-leave-active {
    transition: opacity 220ms ease, transform 240ms var(--spring-bounce);
}

.toast-pop-enter-from,
.toast-pop-leave-to {
    opacity: 0;
    transform: translate3d(-50%, -12px, 0) scale(0.9);
}

/* ==========================================================================
   2. COMPACT EMPTY STATES
   ========================================================================== */
.clay-empty-card {
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-soft, #F8FAFC) 100%);
    border: 2.5px solid var(--px-card-border, #E2E8F0);
    border-radius: 22px;
    padding: 1.6rem 1.25rem;
    text-align: center;
    box-shadow:
        0 5px 0 var(--px-ledge, #CBD5E1),
        0 14px 28px -10px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.16),
        inset 0 2px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.55rem;
    max-width: 420px;
    margin: 0.75rem auto;
}

.empty-icon-medallion {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    background: radial-gradient(circle at 32% 24%, #FFFFFF 0%, #DDD6FE 65%, #A78BFA 100%);
    border: 2px solid #8B5CF6;
    display: grid;
    place-items: center;
    font-size: 1.65rem;
    box-shadow:
        0 3.5px 0 #6D28D9,
        inset 0 2px 2px rgba(255, 255, 255, 0.8);
}

.empty-card-title {
    font-family: var(--font-display);
    font-size: 1.18rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
}

.empty-card-desc {
    font-size: 0.82rem;
    line-height: 1.45;
    font-weight: 600;
    color: var(--px-ink-muted, #64748B);
    margin: 0;
}

/* ==========================================================================
   3. COMPACT 3D PIXAR LIST DIRECTORY (HORIZONTAL ON ALL VIEWPORTS)
   ========================================================================== */
.classmates-list-stage {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.classmate-list-item {
    animation: rowPopIn 360ms var(--spring-bounce) backwards;
    animation-delay: calc(var(--i, 0) * 35ms);
}

@keyframes rowPopIn {
    0% {
        opacity: 0;
        transform: translate3d(0, 10px, 0) scale(0.97);
    }
    100% {
        opacity: 1;
        transform: translate3d(0, 0, 0) scale(1);
    }
}

.classmate-row {
    --row-border: var(--px-card-border, #E2E8F0);
    --row-ledge: var(--px-ledge, #CBD5E1);
    --row-bg-top: var(--px-surface-card, #FFFDF9);
    --row-bg-bot: var(--px-surface-soft, #F8FAFC);

    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.5rem 0.65rem;
    border-radius: 18px;
    background: linear-gradient(180deg, var(--row-bg-top) 0%, var(--row-bg-bot) 100%);
    border: 2px solid var(--row-border);
    box-shadow:
        0 3.5px 0 var(--row-ledge),
        0 8px 18px -8px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.16),
        inset 0 1.5px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    transition: transform 180ms var(--spring-bounce), box-shadow 180ms ease;
    box-sizing: border-box;
}

.row-top-gloss {
    position: absolute;
    top: 2px;
    left: 14px;
    right: 14px;
    height: 6px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.68) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

@media (hover: hover) and (pointer: fine) {
    .classmate-row:hover {
        transform: translate3d(0, -2px, 0);
        box-shadow:
            0 5px 0 var(--row-ledge),
            0 12px 22px -8px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.24),
            inset 0 1.5px 0 var(--px-rim-top, rgba(255, 255, 255, 0.95));
    }
}

.classmate-row.is-me {
    --row-border: #8B5CF6;
    --row-ledge: #6D28D9;
    --row-bg-top: color-mix(in srgb, #EDE9FE 40%, var(--px-surface-card, #FFFFFF));
    --row-bg-bot: var(--px-surface-card, #FFFFFF);
}

/* Rank Badge */
.row-rank-badge {
    width: 24px;
    height: 24px;
    border-radius: 8px;
    background: var(--px-surface-well, #EEF2FF);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    box-shadow: inset 0 1px 2px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.1);
    display: grid;
    place-items: center;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    flex-shrink: 0;
}

.row-rank-badge.is-rank-1 {
    background: radial-gradient(circle at 30% 25%, #FFFBEB 0%, #FACC15 65%, #CA8A04 100%);
    border-color: #A16207;
    color: #422006;
    box-shadow: 0 2px 0 #854D0E, inset 0 1px 1px rgba(255, 255, 255, 0.8);
}

.row-rank-badge.is-rank-2 {
    background: radial-gradient(circle at 30% 25%, #FFFFFF 0%, #CBD5E1 68%, #64748B 100%);
    border-color: #475569;
    color: #0F172A;
    box-shadow: 0 2px 0 #334155, inset 0 1px 1px rgba(255, 255, 255, 0.8);
}

.row-rank-badge.is-rank-3 {
    background: radial-gradient(circle at 30% 25%, #FFEDD5 0%, #FB923C 68%, #C2410C 100%);
    border-color: #9A3412;
    color: #431407;
    box-shadow: 0 2px 0 #7C2D12, inset 0 1px 1px rgba(255, 255, 255, 0.75);
}

/* 3D Glossy Avatar Pod */
.row-avatar-btn {
    position: relative;
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    flex-shrink: 0;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.row-avatar-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
    border-radius: 50%;
}

.classmate-avatar-sphere {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 2.5px solid var(--px-surface-card, #FFFFFF);
    box-shadow:
        0 3px 0 rgba(var(--px-shadow-rgb, 28, 22, 62), 0.2),
        inset 0 -3px 5px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.14),
        inset 0 2px 3px rgba(255, 255, 255, 0.85);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.avatar-specular {
    position: absolute;
    top: 3px;
    left: 6px;
    width: 14px;
    height: 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.72);
    transform: rotate(-22deg);
    pointer-events: none;
}

.avatar-emoji-glyph {
    font-size: 1.38rem;
    line-height: 1;
    filter: drop-shadow(0 1px 1px rgba(28, 22, 62, 0.2));
}

.classmate-lvl-pill {
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(180deg, #312E81 0%, #0F172A 100%);
    color: #FACC15;
    font-family: var(--font-display);
    font-size: 0.54rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    padding: 0.05rem 0.34rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-surface-card, #FFFFFF);
    box-shadow: 0 1.5px 2px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.25);
    white-space: nowrap;
}

/* Identity Column */
.row-identity-col {
    display: flex;
    flex-direction: column;
    gap: 0.14rem;
    flex: 1 1 auto;
    min-width: 0;
    cursor: pointer;
}

.row-name-line {
    display: flex;
    align-items: center;
    gap: 0.32rem;
    min-width: 0;
}

.classmate-name {
    font-family: var(--font-display);
    font-size: 0.94rem;
    font-weight: 800;
    line-height: 1.15;
    color: var(--px-ink, #0F172A);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.you-badge {
    flex-shrink: 0;
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    color: #271300;
    font-family: var(--font-display);
    font-size: 0.56rem;
    font-weight: 900;
    letter-spacing: 0.04em;
    padding: 0.06rem 0.36rem;
    border-radius: 9999px;
    border: 1px solid #B45309;
    box-shadow: 0 1.5px 0 #92400E, inset 0 1px 0 rgba(255, 255, 255, 0.65);
}

.row-sub-line {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
}

.classmate-title-kicker {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.row-rooms-inline {
    display: inline-flex;
    align-items: center;
    gap: 0.22rem;
    min-width: 0;
    flex-shrink: 0;
}

.shared-room-tag {
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink-muted, #475569);
    font-size: 0.6rem;
    font-weight: 800;
    padding: 0.05rem 0.34rem;
    border-radius: 6px;
    max-width: 96px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
}

.room-tag-text {
    overflow: hidden;
    text-overflow: ellipsis;
}

.shared-room-more {
    font-size: 0.56rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    background: var(--px-surface-well, #E2E8F0);
    padding: 0.05rem 0.26rem;
    border-radius: 5px;
}

/* Mobile Inline Stats Strip (Visible < 680px) */
.row-mobile-stats {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.28rem;
    margin-top: 0.1rem;
}

.mini-stat {
    display: inline-flex;
    align-items: center;
    gap: 0.16rem;
    padding: 0.06rem 0.36rem;
    border-radius: 6px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #E2E8F0);
    font-size: 0.64rem;
    color: var(--px-ink, #1E293B);
    line-height: 1.2;
}

.mini-stat strong {
    font-family: var(--font-display);
    font-weight: 800;
}

/* Desktop Recessed Stats Deck (Visible >= 680px) */
.row-stats-deck {
    display: none;
    align-items: center;
    background: var(--px-surface-well, #F8FAFC);
    border: 1.5px solid var(--px-card-border, #E2E8F0);
    box-shadow: inset 0 1.5px 3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.08);
    border-radius: 12px;
    padding: 0.24rem 0.55rem;
    gap: 0.65rem;
    flex-shrink: 0;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 0.28rem;
}

.stat-icon {
    font-size: 0.82rem;
    line-height: 1;
}

.stat-copy {
    display: flex;
    flex-direction: column;
}

.stat-val {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    line-height: 1.05;
}

.stat-lbl {
    font-size: 0.52rem;
    font-weight: 800;
    color: var(--px-ink-muted, #94A3B8);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

@media (min-width: 680px) {
    .row-mobile-stats {
        display: none;
    }

    .row-stats-deck {
        display: inline-flex;
    }
}

/* Row Tactile Actions (Zero Layout-Shift 3D Buttons) */
.row-actions-strip {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.cheer-btn,
.inspect-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    margin-bottom: 2px;
    font-family: var(--font-display);
    border-radius: 12px;
    outline: none;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.cheer-btn:focus-visible,
.inspect-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.cheer-btn-face {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.24rem;
    min-height: 36px;
    min-width: 36px;
    padding: 0.28rem 0.6rem;
    border-radius: 11px;
    background: linear-gradient(180deg, #EDE9FE 0%, #DDD6FE 52%, #C4B5FD 100%);
    border: 1.5px solid #8B5CF6;
    color: #3B0764;
    font-size: 0.72rem;
    font-weight: 800;
    box-shadow:
        0 2.5px 0 #6D28D9,
        inset 0 1.5px 0 rgba(255, 255, 255, 0.8);
    transition: transform 140ms var(--spring-bounce), box-shadow 140ms ease;
    white-space: nowrap;
}

.cheer-btn:active .cheer-btn-face {
    transform: translate3d(0, 2px, 0) scaleX(1.035) scaleY(0.95);
    box-shadow: 0 0.5px 0 #6D28D9;
}

.cheer-btn.has-cheered .cheer-btn-face {
    background: linear-gradient(180deg, #FCE7F3 0%, #FBCFE8 55%, #F9A8D4 100%);
    border-color: #DB2777;
    color: #831843;
    box-shadow:
        0 2.5px 0 #9D174D,
        inset 0 1.5px 0 rgba(255, 255, 255, 0.8);
}

.inspect-btn-face {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.18rem;
    min-height: 36px;
    min-width: 34px;
    padding: 0.28rem 0.55rem;
    border-radius: 11px;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-soft, #F1F5F9) 100%);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink, #334155);
    font-size: 0.72rem;
    font-weight: 800;
    box-shadow:
        0 2.5px 0 var(--px-ledge, #CBD5E1),
        inset 0 1.5px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    transition: transform 140ms var(--spring-bounce), box-shadow 140ms ease;
    white-space: nowrap;
}

.inspect-btn:active .inspect-btn-face {
    transform: translate3d(0, 2px, 0) scaleX(1.035) scaleY(0.95);
    box-shadow: 0 0.5px 0 var(--px-ledge, #CBD5E1);
}

.inspect-arrow {
    font-size: 0.95rem;
    line-height: 1;
}

/* ==========================================================================
   4. PIXAR SQUASH-AND-STRETCH INSPECT MODAL
   ========================================================================== */
.modal-backdrop-scrim {
    position: fixed;
    inset: 0;
    background: rgba(var(--px-shadow-rgb, 15, 23, 42), 0.62);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem;
    padding-bottom: calc(0.85rem + env(safe-area-inset-bottom, 0px));
    box-sizing: border-box;
}

.pixar-inspect-modal {
    position: relative;
    overflow-x: hidden;
    overflow-y: auto;
    max-height: calc(100dvh - 2rem);
    background: linear-gradient(180deg, var(--px-surface-card, #FFFDF9) 0%, var(--px-surface-soft, #F4F0FF) 100%);
    border: 3px solid var(--px-card-border, #E2E8F0);
    border-radius: 26px;
    padding: 1.15rem;
    width: 100%;
    max-width: 400px;
    box-shadow:
        0 8px 0 var(--px-ledge, #CBD5E1),
        0 24px 48px rgba(var(--px-shadow-rgb, 15, 23, 42), 0.34),
        inset 0 2px 2px var(--px-rim-top, rgba(255, 255, 255, 0.95));
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    box-sizing: border-box;
}

.modal-top-gloss {
    position: absolute;
    top: 4px;
    left: 22px;
    right: 22px;
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.modal-close-cross {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    z-index: 2;
}

.modal-close-cross:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
    border-radius: 50%;
}

.close-cross-face {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    background: var(--px-surface-well, #F1F5F9);
    box-shadow: 0 2px 0 var(--px-ledge, #CBD5E1);
    color: var(--px-ink, #334155);
    font-size: 0.8rem;
    font-weight: 800;
    display: grid;
    place-items: center;
    transition: transform 120ms var(--spring-bounce);
}

.modal-close-cross:active .close-cross-face {
    transform: translate3d(0, 2px, 0) scale(0.94);
}

.modal-hero-deck {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding-right: 2rem;
}

.modal-avatar-halo {
    position: relative;
    flex-shrink: 0;
}

.modal-avatar-bubble {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid var(--px-surface-card, #FFFFFF);
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb, 28, 22, 62), 0.18),
        0 8px 18px rgba(124, 58, 237, 0.22),
        inset 0 2px 4px rgba(255, 255, 255, 0.85);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.modal-emoji-char {
    font-size: 2rem;
    line-height: 1;
}

.modal-lvl-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: linear-gradient(180deg, #312E81 0%, #0F172A 100%);
    color: #FACC15;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    border: 2px solid var(--px-surface-card, #FFFFFF);
}

.modal-hero-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.modal-you-pill {
    display: inline-block;
    align-self: flex-start;
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    color: #271300;
    font-family: var(--font-display);
    font-size: 0.6rem;
    font-weight: 900;
    padding: 0.08rem 0.42rem;
    border-radius: 9999px;
    margin-bottom: 0.15rem;
}

.modal-hero-name {
    font-family: var(--font-display);
    font-size: 1.2rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
    line-height: 1.15;
    overflow: hidden;
    text-overflow: ellipsis;
}

.modal-hero-title {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    margin: 0.1rem 0 0;
}

/* Modal Compact Stats Matrix */
.modal-stats-matrix {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.4rem;
}

@media (min-width: 420px) {
    .modal-stats-matrix {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.matrix-tile {
    background: var(--px-surface-well, #F8FAFC);
    border: 1.5px solid var(--px-card-border, #E2E8F0);
    box-shadow: inset 0 2px 3px rgba(var(--px-shadow-rgb, 28, 22, 62), 0.07);
    border-radius: 14px;
    padding: 0.45rem 0.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.tile-icon {
    font-size: 0.95rem;
    line-height: 1.1;
}

.tile-num {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    line-height: 1.15;
}

.tile-sub {
    font-size: 0.56rem;
    font-weight: 800;
    color: var(--px-ink-muted, #94A3B8);
    text-transform: uppercase;
}

/* Modal Shared Classrooms */
.modal-rooms-deck {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.rooms-deck-heading {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-family: var(--font-display);
    font-size: 0.78rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
}

.rooms-deck-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    max-height: 96px;
    overflow-y: auto;
}

.modal-room-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #CBD5E1);
    border-radius: 10px;
    padding: 0.25rem 0.5rem;
    min-width: 0;
}

.room-chip-icon {
    font-size: 0.88rem;
}

.room-chip-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.room-chip-title {
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-chip-sub {
    font-size: 0.58rem;
    color: var(--px-ink-muted, #64748B);
}

/* Modal Side-by-Side Actions Tray */
.modal-actions-tray {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.clay-primary-btn,
.clay-secondary-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    margin-bottom: 3px;
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    border-radius: 14px;
    outline: none;
    width: 100%;
    touch-action: manipulation;
}

.clay-primary-btn:focus-visible,
.clay-secondary-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke, #38BDF8);
    outline-offset: 2px;
}

.clay-primary-btn .btn-face {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 44px;
    padding: 0.5rem 0.75rem;
    border-radius: 14px;
    background: linear-gradient(180deg, #A78BFA 0%, #7C3AED 52%, #5B21B6 100%);
    border: 2px solid #4C1D95;
    color: #FFFFFF;
    box-shadow:
        0 3.5px 0 #3B0764,
        inset 0 1.5px 1px rgba(255, 255, 255, 0.55);
    transition: transform 140ms var(--spring-bounce), box-shadow 140ms ease;
    white-space: nowrap;
}

.clay-primary-btn:active .btn-face {
    transform: translate3d(0, 3px, 0) scaleX(1.035) scaleY(0.95);
    box-shadow: 0 0.5px 0 #3B0764;
}

.clay-secondary-btn .btn-face {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 44px;
    padding: 0.5rem 0.75rem;
    border-radius: 14px;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-soft, #F1F5F9) 100%);
    border: 2px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink, #334155);
    box-shadow:
        0 3.5px 0 var(--px-ledge, #CBD5E1),
        inset 0 1.5px 0 var(--px-rim-top, rgba(255, 255, 255, 0.9));
    transition: transform 140ms var(--spring-bounce), box-shadow 140ms ease;
    white-space: nowrap;
}

.clay-secondary-btn:active .btn-face {
    transform: translate3d(0, 3px, 0) scaleX(1.035) scaleY(0.95);
    box-shadow: 0 0.5px 0 var(--px-ledge, #CBD5E1);
}

/* Modal Bounce Transition */
.modal-bounce-enter-active {
    transition: opacity 220ms ease, transform 260ms var(--spring-bounce);
}

.modal-bounce-leave-active {
    transition: opacity 160ms ease, transform 160ms ease;
}

.modal-bounce-enter-from {
    opacity: 0;
    transform: scale(0.86) translate3d(0, 12px, 0);
}

.modal-bounce-leave-to {
    opacity: 0;
    transform: scale(0.93);
}

/* ==========================================================================
   5. COMPACT MOBILE RESPONSIVENESS (320px - 480px)
   ========================================================================== */
@media (max-width: 480px) {
    .clay-classmates-page {
        gap: 0.55rem;
        padding: 0.1rem 0 1.5rem;
    }

    .classmates-control-deck {
        padding: 0.55rem 0.65rem;
        border-radius: 18px;
        gap: 0.45rem;
    }

    .deck-friends-medallion {
        width: 34px;
        height: 34px;
        border-radius: 11px;
    }

    .medallion-emoji {
        font-size: 1rem;
    }

    .deck-subtitle {
        display: none;
    }

    .classmates-list-stage {
        gap: 0.42rem;
    }

    .classmate-row {
        padding: 0.42rem 0.5rem;
        gap: 0.42rem;
        border-radius: 15px;
    }

    .row-rank-badge {
        width: 20px;
        height: 20px;
        font-size: 0.64rem;
        border-radius: 6px;
    }

    .classmate-avatar-sphere {
        width: 38px;
        height: 38px;
    }

    .avatar-emoji-glyph {
        font-size: 1.2rem;
    }

    .classmate-lvl-pill {
        font-size: 0.5rem;
        padding: 0.03rem 0.28rem;
    }

    .classmate-name {
        font-size: 0.86rem;
    }

    .row-rooms-inline {
        display: none;
    }

    .cheer-btn-face {
        padding: 0.24rem 0.48rem;
        min-height: 34px;
        font-size: 0.68rem;
    }

    .inspect-btn-face {
        padding: 0.24rem 0.42rem;
        min-height: 34px;
    }

    .inspect-label {
        display: none;
    }
}

@media (max-width: 359px) {
    .cheer-label {
        display: none;
    }

    .cheer-btn-face {
        padding: 0.24rem 0.4rem;
    }
}

/* ==========================================================================
   6. REDUCED MOTION FALLBACK (WCAG 2.3.3 / PIXAR A11Y-001)
   ========================================================================== */
@media (prefers-reduced-motion: reduce) {
    .classmate-list-item,
    .classmate-row,
    .room-pill-face,
    .cheer-btn-face,
    .inspect-btn-face,
    .clay-primary-btn .btn-face,
    .clay-secondary-btn .btn-face,
    .modal-bounce-enter-active,
    .modal-bounce-leave-active,
    .toast-pop-enter-active,
    .toast-pop-leave-active {
        animation: none !important;
        transition: none !important;
    }
}
</style>
