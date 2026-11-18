<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Components/StudentLayout.vue';
import { useSound } from '@/Services/soundManager';
import { spawnPixarBurst, usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';

const {
    playPop,
    playSparkle,
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

// View mode: 'grid' | 'list'
const viewMode = ref('grid');

// Search & Filter state
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

onMounted(() => {
    try {
        const savedView = window.localStorage?.getItem('emodule_classmates_view');
        if (savedView === 'list' || savedView === 'grid') {
            viewMode.value = savedView;
        }
    } catch {}
});

const setViewMode = (mode) => {
    viewMode.value = mode;
    playClick();
    try {
        window.localStorage?.setItem('emodule_classmates_view', mode);
    } catch {}
};

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
        // Keep current user pinned near top if desired or natural sort
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
        spawnPixarBurst(event.currentTarget, 16, activeWorld.value);
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
    }, 2800);
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
</script>

<template>
    <Head title="Classmates • WEBREAD" />

    <StudentLayout
        :user="user"
        active-nav="classmates"
        :full-width="true"
    >
        <div class="clay-classmates-page">
            <!-- =========================================================================
                 1. COMPACT CONTROL DECK (Cohesive with Classrooms.vue & Shop.vue)
                 ========================================================================= -->
            <header class="classmates-control-deck">
                <!-- Top Row: Medallion + Title + Result Count + View Switcher -->
                <div class="deck-top-row">
                    <div class="deck-title-block">
                        <span class="deck-friends-medallion" aria-hidden="true">👥</span>
                        <div class="deck-title-meta">
                            <h1 class="deck-title">Classmates</h1>
                            <span class="deck-counter">
                                {{ filteredClassmates.length }} {{ filteredClassmates.length === 1 ? 'classmate' : 'classmates' }}
                            </span>
                        </div>
                    </div>

                    <!-- View Switcher (Grid / List) -->
                    <div class="deck-view-switch" role="group" aria-label="Layout view">
                        <button
                            type="button"
                            class="view-btn"
                            :class="{ 'is-active': viewMode === 'grid' }"
                            title="Grid View"
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
                            title="List View"
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

                <!-- Mid Row: Classroom Pills Carousel + Search Bar -->
                <div class="deck-filters-row">
                    <!-- Classroom Filter Pills -->
                    <div v-if="classrooms.length > 0" class="room-pills-scroll" role="tablist" aria-label="Classroom filters">
                        <button
                            type="button"
                            class="room-pill-btn"
                            :class="{ 'is-active': activeRoomFilter === 'all' }"
                            role="tab"
                            :aria-selected="activeRoomFilter === 'all'"
                            @click="selectRoomFilter('all')"
                        >
                            <span class="room-pill-icon">🏫</span>
                            <span class="room-pill-name">All Rooms</span>
                            <span class="room-pill-badge">{{ classroomCounts.all }}</span>
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
                            <span class="room-pill-icon">{{ room.icon || '📚' }}</span>
                            <span class="room-pill-name">{{ room.name }}</span>
                            <span class="room-pill-badge">{{ classroomCounts[room.id] || 0 }}</span>
                        </button>
                    </div>

                    <!-- Search Input & Sort Selector -->
                    <div class="deck-search-wrap">
                        <div class="clay-search-compact" :class="{ 'has-query': searchQuery }" role="search">
                            <span class="search-mini-lens" aria-hidden="true">🔍</span>
                            <input
                                v-model="searchQuery"
                                type="search"
                                class="search-mini-input"
                                placeholder="Find classmate by name…"
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

                        <!-- Quick Sort Dropdown -->
                        <div class="sort-select-wrap">
                            <label for="sort-classmates-by" class="sr-only">Sort classmates</label>
                            <select
                                id="sort-classmates-by"
                                v-model="sortBy"
                                class="sort-mini-select"
                                @change="playClick"
                            >
                                <option value="xp">⚡ Most XP</option>
                                <option value="stars">⭐ Most Stars</option>
                                <option value="level">👑 Highest Level</option>
                                <option value="name">🔤 Alphabetical</option>
                            </select>
                        </div>
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
                 2. EMPTY STATES
                 ========================================================================= -->
            <!-- Empty state: Not enrolled in any classrooms -->
            <div v-if="classrooms.length === 0" class="clay-empty-card">
                <div class="empty-icon-medallion" aria-hidden="true">🎒</div>
                <h2 class="empty-card-title">Join a Classroom Adventure!</h2>
                <p class="empty-card-desc">
                    You haven't joined any classrooms yet. Enter a 6-digit class code from your teacher to explore adventures and meet your classmates!
                </p>
                <button
                    type="button"
                    class="clay-primary-btn"
                    @click="goToClasses"
                >
                    <span class="btn-face">
                        <span>🏫</span>
                        <span>Go to Classrooms</span>
                    </span>
                </button>
            </div>

            <!-- Empty state: Enrolled, but no classmates match search or room filter -->
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
                            ? `No classmates matched "${searchQuery}". Try searching for another name!`
                            : 'You are the first explorer in this room! When other classmates join with the class code, they will appear here.'
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
                 3. CLASSMATES DIRECTORY (GRID & LIST VIEWS)
                 ========================================================================= -->
            <div
                v-else
                class="classmates-content-stage"
                :class="viewMode === 'grid' ? 'is-grid-layout' : 'is-list-layout'"
            >
                <article
                    v-for="classmate in filteredClassmates"
                    :key="classmate.id"
                    class="classmate-card"
                    :class="{ 'is-me': classmate.is_current_user }"
                >
                    <!-- Card Header: Avatar Medallion + Level + "You" badge -->
                    <div class="card-avatar-row">
                        <div class="avatar-pod-wrap">
                            <span
                                class="classmate-avatar-sphere"
                                :style="{ background: classmate.avatarBg }"
                                aria-hidden="true"
                            >
                                <span class="avatar-emoji-glyph">{{ getAvatarEmoji(classmate.avatar) }}</span>
                            </span>
                            <span class="classmate-lvl-pill">LVL {{ classmate.level }}</span>
                        </div>

                        <div class="card-badges-row">
                            <span v-if="classmate.is_current_user" class="you-badge" aria-label="This is you">
                                🌟 You
                            </span>
                            <span
                                v-for="room in classmate.shared_classrooms.slice(0, 2)"
                                :key="room.id"
                                class="shared-room-tag"
                                :title="room.name"
                            >
                                <span class="room-tag-icon">{{ room.icon || '🏷️' }}</span>
                                <span class="room-tag-name">{{ room.name }}</span>
                            </span>
                            <span
                                v-if="classmate.shared_classrooms.length > 2"
                                class="shared-room-more"
                                :title="`${classmate.shared_classrooms.length - 2} more classrooms`"
                            >
                                +{{ classmate.shared_classrooms.length - 2 }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body: Student Identity -->
                    <div class="card-info-block">
                        <h2 class="classmate-name" :title="classmate.name">
                            {{ classmate.name }}
                        </h2>
                        <span class="classmate-title-kicker">{{ classmate.level_title }}</span>
                    </div>

                    <!-- Card Stats Readout -->
                    <div class="card-stats-deck">
                        <div class="stat-pill stat-xp" title="Total Experience">
                            <span class="stat-icon" aria-hidden="true">⚡</span>
                            <span class="stat-val">{{ Number(classmate.xp).toLocaleString() }}</span>
                            <span class="stat-lbl">XP</span>
                        </div>

                        <div class="stat-pill stat-stars" title="Total Reading Stars">
                            <span class="stat-icon" aria-hidden="true">⭐</span>
                            <span class="stat-val">{{ Number(classmate.stars).toLocaleString() }}</span>
                            <span class="stat-lbl">Stars</span>
                        </div>

                        <div class="stat-pill stat-quizzes" title="Quizzes Passed">
                            <span class="stat-icon" aria-hidden="true">🏆</span>
                            <span class="stat-val">{{ classmate.quizzes_passed }}</span>
                            <span class="stat-lbl">Quiz</span>
                        </div>
                    </div>

                    <!-- Card Tactile Actions -->
                    <div class="card-actions-strip">
                        <button
                            type="button"
                            class="cheer-btn"
                            :class="{ 'has-cheered': cheeredClassmates.has(classmate.id) }"
                            :aria-label="`Give high five to ${classmate.name}`"
                            @click="cheerForClassmate(classmate, $event)"
                        >
                            <span class="cheer-btn-face">
                                <span>{{ cheeredClassmates.has(classmate.id) ? '💖' : '🎉' }}</span>
                                <span>{{ cheeredClassmates.has(classmate.id) ? 'Cheered!' : 'High Five' }}</span>
                            </span>
                        </button>

                        <button
                            type="button"
                            class="inspect-btn"
                            :aria-label="`Inspect ${classmate.name}'s profile`"
                            @click="openInspectModal(classmate)"
                        >
                            <span class="inspect-btn-face">
                                <span>Inspect</span>
                                <span aria-hidden="true">→</span>
                            </span>
                        </button>
                    </div>
                </article>
            </div>

            <!-- =========================================================================
                 4. PIXAR SQUASH-AND-STRETCH INSPECT MODAL
                 ========================================================================= -->
            <transition name="modal-bounce">
                <div
                    v-if="inspectModalOpen && inspectingClassmate"
                    class="modal-backdrop-scrim"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="`${inspectingClassmate.name}'s Profile Card`"
                    @click.self="closeInspectModal"
                >
                    <div class="pixar-inspect-modal">
                        <!-- Top Close Cross -->
                        <button
                            type="button"
                            class="modal-close-cross"
                            aria-label="Close dialog"
                            @click="closeInspectModal"
                        >
                            ✕
                        </button>

                        <!-- Modal Header With Big Glow Avatar -->
                        <div class="modal-hero-deck">
                            <div class="modal-avatar-halo" aria-hidden="true">
                                <span
                                    class="modal-avatar-bubble"
                                    :style="{ background: inspectingClassmate.avatarBg }"
                                >
                                    <span class="modal-emoji-char">
                                        {{ getAvatarEmoji(inspectingClassmate.avatar) }}
                                    </span>
                                </span>
                                <span class="modal-lvl-badge">LVL {{ inspectingClassmate.level }}</span>
                            </div>

                            <div class="modal-hero-meta">
                                <span v-if="inspectingClassmate.is_current_user" class="modal-you-pill">
                                    🌟 That's You!
                                </span>
                                <h3 class="modal-hero-name">{{ inspectingClassmate.name }}</h3>
                                <p class="modal-hero-title">{{ inspectingClassmate.level_title }}</p>
                            </div>
                        </div>

                        <!-- Stats Pedestal -->
                        <div class="modal-stats-matrix">
                            <div class="matrix-tile">
                                <span class="tile-icon">⭐</span>
                                <span class="tile-num">{{ Number(inspectingClassmate.stars).toLocaleString() }}</span>
                                <span class="tile-sub">Total Stars</span>
                            </div>
                            <div class="matrix-tile">
                                <span class="tile-icon">⚡</span>
                                <span class="tile-num">{{ Number(inspectingClassmate.xp).toLocaleString() }}</span>
                                <span class="tile-sub">Reading XP</span>
                            </div>
                            <div class="matrix-tile">
                                <span class="tile-icon">🏆</span>
                                <span class="tile-num">{{ inspectingClassmate.quizzes_passed }}</span>
                                <span class="tile-sub">Quizzes Passed</span>
                            </div>
                            <div class="matrix-tile">
                                <span class="tile-icon">📖</span>
                                <span class="tile-num">{{ inspectingClassmate.lectures_completed }}</span>
                                <span class="tile-sub">Chapters Read</span>
                            </div>
                        </div>

                        <!-- Shared Classrooms Section -->
                        <div class="modal-rooms-deck">
                            <h4 class="rooms-deck-heading">
                                <span>🏫</span>
                                <span>Shared Classrooms ({{ inspectingClassmate.shared_classrooms.length }})</span>
                            </h4>
                            <div class="rooms-deck-list">
                                <div
                                    v-for="room in inspectingClassmate.shared_classrooms"
                                    :key="room.id"
                                    class="modal-room-chip"
                                >
                                    <span class="room-chip-icon">{{ room.icon || '📚' }}</span>
                                    <div class="room-chip-info">
                                        <span class="room-chip-title">{{ room.name }}</span>
                                        <span v-if="room.grade_level" class="room-chip-sub">{{ room.grade_level }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Bottom Actions -->
                        <div class="modal-actions-tray">
                            <button
                                type="button"
                                class="clay-primary-btn modal-cheer-btn"
                                @click="cheerForClassmate(inspectingClassmate, $event)"
                            >
                                <span class="btn-face">
                                    <span>🎉</span>
                                    <span>Give High Five!</span>
                                </span>
                            </button>

                            <button
                                type="button"
                                class="clay-secondary-btn modal-profile-btn"
                                @click="goToProfile(inspectingClassmate.id)"
                            >
                                <span class="btn-face">
                                    <span>View Full Profile</span>
                                    <span aria-hidden="true">→</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   PAGE CONTAINER & 3D CLAY SYSTEM
   ========================================================================== */
.clay-classmates-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.75rem 0.85rem 5.5rem;
    box-sizing: border-box;
}

/* ==========================================================================
   1. CONTROL DECK (Matches Shop.vue & Classrooms.vue)
   ========================================================================== */
.classmates-control-deck {
    background: var(--px-surface-card, #FFFFFF);
    border: 2px solid var(--px-card-border, #E2E8F0);
    border-radius: 20px;
    padding: 0.85rem 1rem;
    box-shadow:
        0 4px 0 var(--px-card-ledge, #CBD5E1),
        0 10px 24px rgba(var(--px-shadow-rgb, 15, 23, 42), 0.08),
        inset 0 1px 2px var(--px-rim-top, rgba(255, 255, 255, 0.9));
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.15rem;
}

.deck-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.deck-title-block {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.deck-friends-medallion {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(145deg, #DDD6FE 0%, #8B5CF6 100%);
    border: 2px solid #7C3AED;
    box-shadow:
        0 3px 0 #5B21B6,
        inset 0 2px 2px rgba(255, 255, 255, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.deck-title-meta {
    display: flex;
    flex-direction: column;
}

.deck-title {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.45rem;
    line-height: 1.15;
    color: var(--px-ink, #0F172A);
    margin: 0;
}

.deck-counter {
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
}

/* View Switcher */
.deck-view-switch {
    display: inline-flex;
    align-items: center;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-card-border, #E2E8F0);
    border-radius: 12px;
    padding: 0.16rem;
    gap: 0.15rem;
}

.view-btn {
    border: none;
    background: transparent;
    padding: 0.28rem 0.55rem;
    border-radius: 9px;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.72rem;
    color: var(--px-ink-muted, #64748B);
    display: flex;
    align-items: center;
    gap: 0.3rem;
    cursor: pointer;
    transition: all 160ms ease;
}

.view-btn.is-active {
    background: #FFFFFF;
    color: #7C3AED;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

/* Filters Row */
.deck-filters-row {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

/* Room Pills Horizontal Scroll */
.room-pills-scroll {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
}

.room-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.32rem 0.65rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    background: var(--px-surface-well, #F8FAFC);
    color: var(--px-ink, #1E293B);
    font-size: 0.76rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 160ms ease;
    flex-shrink: 0;
}

.room-pill-btn:hover {
    transform: translateY(-1px);
    border-color: #A78BFA;
}

.room-pill-btn.is-active {
    background: linear-gradient(145deg, #8B5CF6 0%, #6D28D9 100%);
    border-color: #5B21B6;
    color: #FFFFFF;
    box-shadow: 0 3px 0 #4C1D95;
    transform: translateY(-1px);
}

.room-pill-icon {
    font-size: 0.88rem;
}

.room-pill-badge {
    background: rgba(0, 0, 0, 0.08);
    padding: 0.08rem 0.38rem;
    border-radius: 9999px;
    font-size: 0.68rem;
    font-weight: 800;
}

.room-pill-btn.is-active .room-pill-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #FFFFFF;
}

/* Compact Search + Sort */
.deck-search-wrap {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    width: 100%;
}

.clay-search-compact {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    border-radius: 12px;
    padding: 0.34rem 0.65rem;
    gap: 0.4rem;
}

.search-mini-lens {
    font-size: 0.85rem;
    opacity: 0.7;
}

.search-mini-input {
    border: none;
    background: transparent;
    width: 100%;
    font-size: 0.82rem;
    color: var(--px-ink, #0F172A);
    outline: none;
    font-weight: 600;
}

.search-mini-input::placeholder {
    color: var(--px-ink-muted, #94A3B8);
}

.search-mini-count {
    font-size: 0.68rem;
    font-weight: 800;
    color: #7C3AED;
    background: #EDE9FE;
    padding: 0.1rem 0.38rem;
    border-radius: 6px;
    white-space: nowrap;
}

.search-mini-clear {
    border: none;
    background: rgba(0, 0, 0, 0.1);
    color: var(--px-ink, #0F172A);
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.65rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sort-select-wrap {
    flex-shrink: 0;
}

.sort-mini-select {
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    border-radius: 12px;
    background: var(--px-surface-well, #F8FAFC);
    color: var(--px-ink, #1E293B);
    padding: 0.36rem 0.55rem;
    font-size: 0.76rem;
    font-weight: 700;
    cursor: pointer;
    outline: none;
}

/* ==========================================================================
   FLOATING CHEER TOAST
   ========================================================================== */
.cheer-toast-pill {
    position: fixed;
    top: 5.25rem;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(145deg, #8B5CF6 0%, #6D28D9 100%);
    color: #FFFFFF;
    border: 2px solid #5B21B6;
    padding: 0.45rem 1.15rem;
    border-radius: 9999px;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.85rem;
    box-shadow:
        0 4px 0 #4C1D95,
        0 8px 24px rgba(109, 40, 217, 0.35);
    z-index: 100;
    pointer-events: none;
}

.toast-pop-enter-active,
.toast-pop-leave-active {
    transition: all 240ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-pop-enter-from,
.toast-pop-leave-to {
    opacity: 0;
    transform: translate(-50%, -14px) scale(0.9);
}

/* ==========================================================================
   2. EMPTY STATES
   ========================================================================== */
.clay-empty-card {
    background: var(--px-surface-card, #FFFFFF);
    border: 2px solid var(--px-card-border, #E2E8F0);
    border-radius: 24px;
    padding: 2.25rem 1.5rem;
    text-align: center;
    box-shadow:
        0 4px 0 var(--px-card-ledge, #CBD5E1),
        0 12px 28px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    max-width: 460px;
    margin: 1.5rem auto;
}

.empty-icon-medallion {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: linear-gradient(145deg, #EDE9FE 0%, #DDD6FE 100%);
    border: 2px solid #C4B5FD;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 4px 0 #A78BFA;
}

.empty-card-title {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.35rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
}

.empty-card-desc {
    font-size: 0.86rem;
    line-height: 1.45;
    color: var(--px-ink-muted, #64748B);
    margin: 0;
}

/* ==========================================================================
   3. CLASSMATES GRID & LIST LAYOUTS
   ========================================================================== */
.classmates-content-stage.is-grid-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .classmates-content-stage.is-grid-layout {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
}

@media (min-width: 1080px) {
    .classmates-content-stage.is-grid-layout {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.15rem;
    }
}

.classmates-content-stage.is-list-layout {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

/* ==========================================================================
   CLASSMATE CARD (Tactile 3D Claymorphism)
   ========================================================================== */
.classmate-card {
    background: var(--px-surface-card, #FFFFFF);
    border: 2px solid var(--px-card-border, #E2E8F0);
    border-radius: 18px;
    padding: 0.75rem;
    box-shadow:
        0 4px 0 var(--px-card-ledge, #CBD5E1),
        0 8px 18px rgba(0, 0, 0, 0.05),
        inset 0 1px 1px rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    transition: transform 180ms cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 180ms ease;
    box-sizing: border-box;
    position: relative;
}

.classmate-card:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 var(--px-card-ledge, #CBD5E1),
        0 14px 24px rgba(0, 0, 0, 0.08);
}

.classmate-card.is-me {
    border-color: #8B5CF6;
    background: linear-gradient(180deg, #FAF5FF 0%, #FFFFFF 100%);
    box-shadow:
        0 4px 0 #7C3AED,
        0 8px 20px rgba(124, 58, 237, 0.12);
}

/* Card Avatar Row */
.card-avatar-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
}

.avatar-pod-wrap {
    position: relative;
    flex-shrink: 0;
}

.classmate-avatar-sphere {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 3px 0 rgba(0, 0, 0, 0.14),
        inset 0 2px 3px rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-emoji-glyph {
    font-size: 1.6rem;
    line-height: 1;
}

.classmate-lvl-pill {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: #0F172A;
    color: #FACC15;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.58rem;
    padding: 0.08rem 0.32rem;
    border-radius: 9999px;
    border: 1.5px solid #FFFFFF;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.card-badges-row {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    min-width: 0;
}

.you-badge {
    background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.62rem;
    padding: 0.1rem 0.42rem;
    border-radius: 9999px;
    box-shadow: 0 2px 0 #B45309;
}

.shared-room-tag {
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink-muted, #475569);
    font-size: 0.62rem;
    font-weight: 700;
    padding: 0.1rem 0.36rem;
    border-radius: 6px;
    max-width: 110px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
}

.room-tag-icon {
    font-size: 0.68rem;
}

.shared-room-more {
    font-size: 0.58rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    background: var(--px-surface-well, #E2E8F0);
    padding: 0.06rem 0.28rem;
    border-radius: 4px;
}

/* Card Info Block */
.card-info-block {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.classmate-name {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.02rem;
    line-height: 1.2;
    color: var(--px-ink, #0F172A);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.classmate-title-kicker {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Card Stats Deck */
.card-stats-deck {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--px-surface-well, #F8FAFC);
    border: 1px solid var(--px-card-border, #E2E8F0);
    border-radius: 10px;
    padding: 0.32rem 0.45rem;
    gap: 0.25rem;
}

.stat-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 0;
}

.stat-icon {
    font-size: 0.72rem;
    line-height: 1;
}

.stat-val {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.78rem;
    color: var(--px-ink, #0F172A);
    line-height: 1.1;
}

.stat-lbl {
    font-size: 0.54rem;
    font-weight: 800;
    color: var(--px-ink-muted, #94A3B8);
    text-transform: uppercase;
}

/* Card Tactile Actions */
.card-actions-strip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: auto;
}

.cheer-btn,
.inspect-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.72rem;
    border-radius: 10px;
    outline: none;
    flex: 1;
}

.cheer-btn-face {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.36rem 0.45rem;
    border-radius: 10px;
    background: linear-gradient(145deg, #DDD6FE 0%, #C4B5FD 100%);
    border: 1.5px solid #8B5CF6;
    color: #4C1D95;
    box-shadow: 0 2px 0 #7C3AED;
    transition: transform 120ms ease, box-shadow 120ms ease;
    white-space: nowrap;
}

.cheer-btn:active .cheer-btn-face {
    transform: translateY(2px);
    box-shadow: 0 0 0 #7C3AED;
}

.cheer-btn.has-cheered .cheer-btn-face {
    background: linear-gradient(145deg, #FCE7F3 0%, #FBCFE8 100%);
    border-color: #EC4899;
    color: #9D174D;
    box-shadow: 0 2px 0 #DB2777;
}

.inspect-btn-face {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    padding: 0.36rem 0.45rem;
    border-radius: 10px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink, #334155);
    box-shadow: 0 2px 0 var(--px-card-ledge, #CBD5E1);
    transition: transform 120ms ease, box-shadow 120ms ease;
    white-space: nowrap;
}

.inspect-btn:active .inspect-btn-face {
    transform: translateY(2px);
    box-shadow: 0 0 0 var(--px-card-ledge, #CBD5E1);
}

/* ==========================================================================
   LIST VIEW OVERRIDES
   ========================================================================== */
.classmates-content-stage.is-list-layout .classmate-card {
    flex-direction: row;
    align-items: center;
    padding: 0.65rem 0.85rem;
    gap: 0.85rem;
}

.classmates-content-stage.is-list-layout .card-avatar-row {
    margin-right: 0;
}

.classmates-content-stage.is-list-layout .card-info-block {
    flex: 1;
    min-width: 0;
}

.classmates-content-stage.is-list-layout .card-stats-deck {
    width: auto;
    min-width: 180px;
    padding: 0.3rem 0.75rem;
}

.classmates-content-stage.is-list-layout .card-actions-strip {
    margin-top: 0;
    min-width: 170px;
}

@media (max-width: 640px) {
    .classmates-content-stage.is-list-layout .classmate-card {
        flex-direction: column;
        align-items: stretch;
    }

    .classmates-content-stage.is-list-layout .card-stats-deck {
        min-width: 0;
        width: 100%;
    }

    .classmates-content-stage.is-list-layout .card-actions-strip {
        min-width: 0;
        width: 100%;
    }
}

/* ==========================================================================
   4. PIXAR SQUASH-AND-STRETCH INSPECT MODAL
   ========================================================================== */
.modal-backdrop-scrim {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    box-sizing: border-box;
}

.pixar-inspect-modal {
    background: var(--px-surface-card, #FFFFFF);
    border: 3px solid var(--px-card-border, #E2E8F0);
    border-radius: 28px;
    padding: 1.5rem;
    width: 100%;
    max-width: 440px;
    box-shadow:
        0 8px 0 var(--px-card-ledge, #CBD5E1),
        0 24px 48px rgba(0, 0, 0, 0.25),
        inset 0 2px 2px rgba(255, 255, 255, 0.95);
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
    box-sizing: border-box;
}

.modal-close-cross {
    position: absolute;
    top: 0.85rem;
    right: 0.85rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1.5px solid var(--px-card-border, #CBD5E1);
    background: var(--px-surface-well, #F1F5F9);
    color: var(--px-ink, #334155);
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 120ms ease;
}

.modal-close-cross:hover {
    transform: scale(1.08);
}

.modal-hero-deck {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.modal-avatar-halo {
    position: relative;
    flex-shrink: 0;
}

.modal-avatar-bubble {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    border: 3px solid #FFFFFF;
    box-shadow:
        0 4px 0 rgba(0, 0, 0, 0.16),
        0 8px 18px rgba(124, 58, 237, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-emoji-char {
    font-size: 2.3rem;
    line-height: 1;
}

.modal-lvl-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: #0F172A;
    color: #FACC15;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.68rem;
    padding: 0.12rem 0.45rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
}

.modal-hero-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.modal-you-pill {
    display: inline-block;
    align-self: flex-start;
    background: #F59E0B;
    color: #FFFFFF;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.64rem;
    padding: 0.1rem 0.45rem;
    border-radius: 9999px;
    margin-bottom: 0.2rem;
}

.modal-hero-name {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.35rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
    line-height: 1.2;
}

.modal-hero-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--px-ink-muted, #64748B);
    margin: 0;
}

/* Modal Stats Matrix */
.modal-stats-matrix {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.55rem;
}

.matrix-tile {
    background: var(--px-surface-well, #F8FAFC);
    border: 1.5px solid var(--px-card-border, #E2E8F0);
    border-radius: 14px;
    padding: 0.65rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.tile-icon {
    font-size: 1.15rem;
}

.tile-num {
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 1.15rem;
    color: var(--px-ink, #0F172A);
    line-height: 1.15;
}

.tile-sub {
    font-size: 0.64rem;
    font-weight: 800;
    color: var(--px-ink-muted, #94A3B8);
    text-transform: uppercase;
}

/* Modal Shared Classrooms */
.modal-rooms-deck {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.rooms-deck-heading {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.88rem;
    color: var(--px-ink, #0F172A);
    margin: 0;
}

.rooms-deck-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    max-height: 110px;
    overflow-y: auto;
}

.modal-room-chip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-card-border, #CBD5E1);
    border-radius: 10px;
    padding: 0.35rem 0.55rem;
}

.room-chip-icon {
    font-size: 1rem;
}

.room-chip-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.room-chip-title {
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--px-ink, #0F172A);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-chip-sub {
    font-size: 0.64rem;
    color: var(--px-ink-muted, #64748B);
}

/* Modal Actions Tray */
.modal-actions-tray {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.clay-primary-btn,
.clay-secondary-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    font-family: var(--font-display, 'Lilita One', sans-serif);
    font-size: 0.92rem;
    border-radius: 14px;
    outline: none;
    width: 100%;
}

.clay-primary-btn .btn-face {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.65rem 1rem;
    border-radius: 14px;
    background: linear-gradient(145deg, #8B5CF6 0%, #6D28D9 100%);
    border: 2px solid #5B21B6;
    color: #FFFFFF;
    box-shadow: 0 4px 0 #4C1D95;
    transition: transform 120ms ease, box-shadow 120ms ease;
}

.clay-primary-btn:active .btn-face {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #4C1D95;
}

.clay-secondary-btn .btn-face {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.55rem 1rem;
    border-radius: 14px;
    background: var(--px-surface-well, #F1F5F9);
    border: 2px solid var(--px-card-border, #CBD5E1);
    color: var(--px-ink, #334155);
    box-shadow: 0 3px 0 var(--px-card-ledge, #CBD5E1);
    transition: transform 120ms ease, box-shadow 120ms ease;
}

.clay-secondary-btn:active .btn-face {
    transform: translateY(2px);
    box-shadow: 0 1px 0 var(--px-card-ledge, #CBD5E1);
}

/* Modal Bounce Transition */
.modal-bounce-enter-active {
    transition: opacity 220ms ease, transform 240ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-bounce-leave-active {
    transition: opacity 160ms ease, transform 160ms ease;
}

.modal-bounce-enter-from {
    opacity: 0;
    transform: scale(0.85);
}

.modal-bounce-leave-to {
    opacity: 0;
    transform: scale(0.92);
}

/* ==========================================================================
   MOBILE RESPONSIVE ADJUSTMENTS
   ========================================================================== */
@media (max-width: 480px) {
    .clay-classmates-page {
        padding: 0.5rem 0.55rem 5.5rem;
    }

    .classmates-control-deck {
        padding: 0.65rem 0.75rem;
        border-radius: 16px;
    }

    .deck-title {
        font-size: 1.25rem;
    }

    .deck-friends-medallion {
        width: 38px;
        height: 38px;
        font-size: 1.15rem;
    }

    .classmates-content-stage.is-grid-layout {
        gap: 0.55rem;
    }

    .classmate-card {
        padding: 0.6rem;
        border-radius: 14px;
    }

    .classmate-avatar-sphere {
        width: 40px;
        height: 40px;
    }

    .avatar-emoji-glyph {
        font-size: 1.35rem;
    }

    .classmate-name {
        font-size: 0.92rem;
    }

    .card-actions-strip {
        flex-direction: column;
        gap: 0.3rem;
    }

    .cheer-btn,
    .inspect-btn {
        width: 100%;
    }
}
</style>
