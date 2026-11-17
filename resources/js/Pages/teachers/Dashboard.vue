<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ClaySearchBar from '@/Components/ClaySearchBar.vue';
import TeacherLayout from '@/Components/TeacherLayout.vue';
import { flushAllClientCache, prefetchRoute } from '@/Composables/usePrefetch';
import { useSound } from '@/Services/soundManager';

const { playClick, playPop, playBoing, playSparkle } = useSound();

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
    modules: {
        type: Array,
        default: () => [],
    },
    classrooms: {
        type: Array,
        default: () => [],
    },
    selected_classroom_id: {
        type: Number,
        default: null,
    },
    selected_classroom: {
        type: Object,
        default: null,
    },
    pending_grading_count: {
        type: Number,
        default: 0,
    },
});

const filterByClassroom = (classId) => {
    playPop(1.1);
    router.visit(classId ? `/teachers/dashboard?class_id=${classId}` : '/teachers/dashboard', {
        preserveState: false,
        preserveScroll: true,
    });
};

const copiedCodeId = ref(null);
const moduleSearch = ref('');

const MODULE_VIEW_STORAGE_KEY = 'teacher-dashboard-module-view';

/** Reads the preferred module layout, defaulting to the list view. */
const readStoredModuleView = () => {
    try {
        return window.localStorage.getItem(MODULE_VIEW_STORAGE_KEY) === 'grid' ? 'grid' : 'list';
    } catch {
        return 'list';
    }
};

const moduleView = ref(typeof window === 'undefined' ? 'list' : readStoredModuleView());

const setModuleView = (view) => {
    playPop(1.1);
    moduleView.value = view;

    try {
        window.localStorage.setItem(MODULE_VIEW_STORAGE_KEY, view);
    } catch {
        // Storage unavailable (private mode): keep the in-memory choice only.
    }
};

/** Modules matching the search text by title, join code or status. */
const filteredModules = computed(() => {
    const needle = moduleSearch.value.trim().toLowerCase();

    if (needle === '') {
        return props.modules;
    }

    return props.modules.filter((mod) =>
        [mod.title, mod.join_code, mod.status]
            .some((field) => String(field ?? '').toLowerCase().includes(needle)),
    );
});

const totalLessons = computed(() =>
    props.modules.reduce((acc, m) => acc + (Number(m.lectures_count) || 0), 0)
);

const liveCount = computed(() =>
    props.modules.filter((m) => m.status === 'published').length
);

const draftCount = computed(() =>
    props.modules.filter((m) => m.status === 'draft').length
);

const copyModuleCode = async (mod) => {
    if (!mod?.join_code) return;
    playSparkle();
    const codeStr = String(mod.join_code);
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(codeStr);
        } else {
            throw new Error('Clipboard fallback');
        }
    } catch {
        const textarea = document.createElement('textarea');
        textarea.value = codeStr;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }

    copiedCodeId.value = mod.id;
    setTimeout(() => {
        if (copiedCodeId.value === mod.id) {
            copiedCodeId.value = null;
        }
    }, 2500);
};

const togglingModuleId = ref(null);

const toggleModuleStatus = (mod) => {
    if (!mod?.slug || togglingModuleId.value === mod.id) return;
    playClick();
    togglingModuleId.value = mod.id;
    const nextStatus = mod.status === 'published' ? 'draft' : 'published';

    router.patch(
        `/teachers/modules/${mod.slug}/status`,
        { status: nextStatus },
        {
            preserveScroll: true,
            preserveState: false,
            onFinish: () => {
                togglingModuleId.value = null;
            },
        }
    );
};

const handleLogout = () => {
    flushAllClientCache();
    router.post('/logout');
};

const prefetchTeacherRoute = (url, tags = ['teacher-modules']) => {
    prefetchRoute(url, tags, ['15s', '60s']);
};

const goToStudents = () => {
    router.visit('/teachers/students');
};

/** Student ranking filtered to the classroom whose modules are showing (all students otherwise). */
const classRankingUrl = computed(() =>
    props.selected_classroom_id ? `/teachers/students?class_id=${props.selected_classroom_id}` : '/teachers/students',
);

const goToClassRanking = () => {
    playClick();
    router.visit(classRankingUrl.value);
};

const goToClasses = () => {
    playClick();
    router.visit('/teachers/classes');
};

const goToGrading = () => {
    playClick();
    router.visit('/teachers/grading');
};

/** Create-module URL that carries the classroom being viewed, so the new module lands in it. */
const createModuleUrl = computed(() =>
    props.selected_classroom_id
        ? `/teachers/create/module?class_id=${props.selected_classroom_id}`
        : '/teachers/create/module',
);

const goToCreateModule = () => {
    playBoing(0.95);
    router.visit(createModuleUrl.value);
};

/**
 * Module URL that carries the classroom this list is filtered by, so the module's Back button
 * (and its prefetch-cached copy) always return to this exact list.
 */
const moduleUrl = (mod) =>
    props.selected_classroom_id
        ? `/teachers/modules/${mod.slug}?class_id=${props.selected_classroom_id}`
        : `/teachers/modules/${mod.slug}`;

const openModule = (mod) => {
    playClick();
    router.visit(moduleUrl(mod));
};
</script>

<template>
    <Head title="Your Modules HQ • WEBREAD" />

    <TeacherLayout
        :user="user"
        active-nav="classes"
        :pending-grading-count="pending_grading_count"
    >
        <!-- YOUR MODULES -->
        <section class="tab-panel world-tab">
            <!-- Sleek Compact Command Deck -->
            <div class="modules-command-deck">
                <div class="deck-main-row">
                    <!-- Title & Metrics -->
                    <div class="deck-title-group">
                        <div class="deck-title-line">
                            <h1 class="deck-title">
                                🏰 {{ selected_classroom ? selected_classroom.name : 'Modules HQ' }}
                            </h1>
                            <span v-if="modules.length > 0" class="deck-count-badge">
                                {{ filteredModules.length }} {{ filteredModules.length === 1 ? 'module' : 'modules' }}
                            </span>
                        </div>
                        <div v-if="modules.length > 0" class="deck-summary-strip">
                            <span class="summary-chip" title="Total Lessons Across Modules">
                                📖 <strong>{{ totalLessons }}</strong> lessons
                            </span>
                            <span class="summary-dot" aria-hidden="true">•</span>
                            <span class="summary-chip" title="Published Live Modules">
                                🌟 <strong>{{ liveCount }}</strong> live
                            </span>
                            <span class="summary-dot" aria-hidden="true">•</span>
                            <span class="summary-chip" title="Draft Modules">
                                📝 <strong>{{ draftCount }}</strong> draft
                            </span>
                        </div>
                    </div>

                    <!-- Actions & Controls -->
                    <div class="deck-actions-group">
                        <ClaySearchBar
                            v-if="modules.length > 0"
                            v-model="moduleSearch"
                            label="Search modules"
                            placeholder="Search modules or code…"
                            :result-count="filteredModules.length"
                        />

                        <!-- Layout View Switcher (Grid vs List) -->
                        <div v-if="modules.length > 0" class="deck-view-switch" role="group" aria-label="Module layout">
                            <button
                                type="button"
                                class="view-toggle-btn"
                                :class="{ 'is-active': moduleView === 'grid' }"
                                title="Grid View (Compact 3D Bento Cards)"
                                aria-label="Grid View"
                                @click="setModuleView('grid')"
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
                                class="view-toggle-btn"
                                :class="{ 'is-active': moduleView === 'list' }"
                                title="List View (Compact Horizontal Rows)"
                                aria-label="List View"
                                @click="setModuleView('list')"
                            >
                                <svg viewBox="0 0 16 16" width="13" height="13" fill="currentColor" aria-hidden="true">
                                    <rect x="1.5" y="2" width="13" height="3" rx="1" />
                                    <rect x="1.5" y="6.5" width="13" height="3" rx="1" />
                                    <rect x="1.5" y="11" width="13" height="3" rx="1" />
                                </svg>
                                <span>List</span>
                            </button>
                        </div>

                        <!-- Class Ranking Button -->
                        <button
                            type="button"
                            class="btn-3d btn-ranking-action"
                            :aria-label="selected_classroom ? `View ${selected_classroom.name} student ranking` : 'View student ranking'"
                            @mouseenter="prefetchTeacherRoute(classRankingUrl, ['teacher-students'])"
                            @focus="prefetchTeacherRoute(classRankingUrl, ['teacher-students'])"
                            @click="goToClassRanking"
                        >
                            <span>🏆 {{ selected_classroom ? 'RANKING' : 'STUDENT RANKING' }}</span>
                        </button>

                        <!-- Create Module Button -->
                        <button
                            type="button"
                            class="btn-3d btn-primary-action btn-create-compact"
                            @mouseenter="prefetchTeacherRoute(createModuleUrl, ['teacher-modules'])"
                            @focus="prefetchTeacherRoute(createModuleUrl, ['teacher-modules'])"
                            @click="goToCreateModule"
                        >
                            <span>✨ + NEW MODULE</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Shared SVG Definitions for fallback scenery -->
            <svg v-if="modules.length > 0" width="0" height="0" style="position: absolute; pointer-events: none;" aria-hidden="true">
                <defs>
                    <linearGradient id="scenerySky" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#AEE2FF" />
                        <stop offset="45%" stop-color="#D0EDFE" />
                        <stop offset="100%" stop-color="#FFF2E7" />
                    </linearGradient>
                    <linearGradient id="mountPink" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#EAA6BF" />
                        <stop offset="100%" stop-color="#C782A6" />
                    </linearGradient>
                    <linearGradient id="mountPurple" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#CBB5EA" />
                        <stop offset="100%" stop-color="#9C82CA" />
                    </linearGradient>
                    <linearGradient id="hillGreen1" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#B2EDB8" />
                        <stop offset="100%" stop-color="#72D086" />
                    </linearGradient>
                    <linearGradient id="hillGreen2" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#78E3AC" />
                        <stop offset="100%" stop-color="#40B880" />
                    </linearGradient>
                    <linearGradient id="hillYellow" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#FDE89C" />
                        <stop offset="100%" stop-color="#F6C852" />
                    </linearGradient>
                    <linearGradient id="pathGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#FCE1AE" />
                        <stop offset="100%" stop-color="#F3C377" />
                    </linearGradient>
                </defs>
            </svg>

            <!-- Search Empty State -->
            <div v-if="modules.length > 0 && filteredModules.length === 0" class="search-empty-card">
                <div class="search-empty-orb" aria-hidden="true">🔍</div>
                <div class="search-empty-copy">
                    <h2 class="search-empty-title">No modules match “{{ moduleSearch.trim() }}”</h2>
                    <p class="search-empty-desc">Try another module title, join code, or “draft” / “published”.</p>
                </div>
                <button type="button" class="btn-3d btn-primary-action btn-sm" @click="moduleSearch = ''">
                    <span>↺ CLEAR SEARCH</span>
                </button>
            </div>

            <!-- Modules Content Wrap -->
            <div v-else-if="modules.length > 0" class="modules-content-wrap">
                <!-- HIGH-DENSITY COMPACT LIST VIEW -->
                <div v-if="moduleView === 'list'" class="quest-list">
                    <article
                        v-for="(mod, index) in filteredModules"
                        :key="mod.id"
                        class="quest-row"
                        :class="`theme-${mod.theme || 'forest'}`"
                        :style="{ '--i': Math.min(index, 8) }"
                        role="button"
                        tabindex="0"
                        :aria-label="'Open learning module: ' + mod.title"
                        @mouseenter="prefetchTeacherRoute(moduleUrl(mod), ['teacher-modules', `module-${mod.slug}`])"
                        @focus="prefetchTeacherRoute(moduleUrl(mod), ['teacher-modules', `module-${mod.slug}`])"
                        @click="openModule(mod)"
                        @keydown.enter.self="openModule(mod)"
                        @keydown.space.self.prevent="openModule(mod)"
                    >
                        <!-- Left: Compact Cover / Icon Orb -->
                        <div class="row-thumb-box">
                            <img
                                v-if="mod.cover_image_url"
                                :src="mod.cover_image_url"
                                :alt="mod.title"
                                loading="lazy"
                                decoding="async"
                                class="row-thumb-img"
                            />
                            <div v-else class="row-thumb-fallback" aria-hidden="true">
                                <span class="row-fallback-sun"></span>
                                <span class="row-fallback-hill"></span>
                            </div>
                            <span class="row-thumb-icon" aria-hidden="true">{{ mod.icon || '📘' }}</span>
                            <span v-if="mod.is_new" class="row-new-dot" title="New Module">✨</span>
                        </div>

                        <!-- Content: Title, Grade, Status, Stats -->
                        <div class="row-content">
                            <div class="row-header-line">
                                <h2 class="row-title" :title="mod.title">{{ mod.title }}</h2>
                                <span class="row-grade-chip">🎒 {{ mod.grade_level || 'All Grades' }}</span>
                                <span
                                    class="row-status-chip"
                                    :class="mod.status === 'published' ? 'is-live' : 'is-draft'"
                                >
                                    {{ mod.status === 'published' ? '🌟 Live' : '📝 Draft' }}
                                </span>
                            </div>

                            <div class="row-meta-line">
                                <span class="row-stat-item">
                                    📖 <strong>{{ mod.lectures_count ?? 0 }}</strong> {{ (mod.lectures_count ?? 0) === 1 ? 'lesson' : 'lessons' }}
                                </span>
                                <span v-if="mod.xp_bonus" class="row-stat-item">
                                    ⭐ <strong>+{{ mod.xp_bonus }}</strong> XP
                                </span>
                                <span v-if="mod.description" class="row-desc-preview" :title="mod.description">
                                    {{ mod.description }}
                                </span>
                            </div>
                        </div>

                        <!-- Center-Right: Join Code Keycard Pill -->
                        <div
                            v-if="mod.join_code"
                            class="row-code-keycard"
                            :class="{ 'is-copied': copiedCodeId === mod.id }"
                            role="button"
                            tabindex="0"
                            :title="`Quest code ${mod.join_code}. Click to copy`"
                            @click.stop="copyModuleCode(mod)"
                            @keydown.enter.stop="copyModuleCode(mod)"
                            @keydown.space.stop.prevent="copyModuleCode(mod)"
                        >
                            <span class="code-ico">{{ copiedCodeId === mod.id ? '✅' : '🔑' }}</span>
                            <span class="code-lbl">CODE:</span>
                            <strong class="code-val">{{ mod.join_code }}</strong>
                            <span class="code-btn">{{ copiedCodeId === mod.id ? 'COPIED!' : 'COPY' }}</span>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="row-actions">
                            <button
                                type="button"
                                class="row-action-btn btn-toggle-status"
                                :class="mod.status === 'draft' ? 'btn-publish' : 'btn-unpublish'"
                                :disabled="togglingModuleId === mod.id"
                                :title="mod.status === 'draft' ? 'Publish module' : 'Set to draft'"
                                @click.stop="toggleModuleStatus(mod)"
                            >
                                <span>{{ togglingModuleId === mod.id ? '⏳' : (mod.status === 'draft' ? '🚀 Publish' : '📝 Draft') }}</span>
                            </button>

                            <button
                                type="button"
                                class="row-action-btn btn-open-module"
                                :title="'Open ' + mod.title"
                                @click.stop="openModule(mod)"
                            >
                                <span>Open ➔</span>
                            </button>
                        </div>
                    </article>
                </div>

                <!-- COMPACT BENTO GRID VIEW -->
                <div v-else class="kingdoms-grid">
                    <article
                        v-for="mod in filteredModules"
                        :key="mod.id"
                        class="kingdom-card"
                        :class="`theme-${mod.theme || 'forest'}`"
                        role="button"
                        tabindex="0"
                        :aria-label="'Open learning module: ' + mod.title"
                        @mouseenter="prefetchTeacherRoute(moduleUrl(mod), ['teacher-modules', `module-${mod.slug}`])"
                        @focus="prefetchTeacherRoute(moduleUrl(mod), ['teacher-modules', `module-${mod.slug}`])"
                        @click="openModule(mod)"
                        @keydown.enter="openModule(mod)"
                        @keydown.space.prevent="openModule(mod)"
                    >
                        <!-- Compact Diorama Window Aperture -->
                        <div class="card-diorama-frame">
                            <div class="frame-window">
                                <img
                                    v-if="mod.cover_image_url"
                                    :src="mod.cover_image_url"
                                    :alt="mod.title"
                                    loading="lazy"
                                    decoding="async"
                                    class="frame-center-image"
                                />
                                <!-- 3D Fallback Landscape -->
                                <div v-else class="frame-fallback-scene" aria-hidden="true">
                                    <svg viewBox="0 0 400 200" preserveAspectRatio="xMidYMid slice" class="fallback-scenery-svg">
                                        <rect width="400" height="200" fill="url(#scenerySky)" />
                                        <path d="M60 140 L120 70 L180 140 Z" fill="url(#mountPink)" />
                                        <path d="M140 150 L220 50 L300 150 Z" fill="url(#mountPurple)" />
                                        <path d="M-20 150 Q60 110 160 140 T380 130 L420 200 L-20 200 Z" fill="url(#hillGreen1)" />
                                        <path d="M-20 165 Q110 130 240 165 T420 150 L420 200 L-20 200 Z" fill="url(#hillGreen2)" />
                                    </svg>
                                </div>
                                <div class="frame-lens-glare" aria-hidden="true"></div>
                            </div>

                            <!-- Mascot Pod -->
                            <div class="card-mascot-pod" aria-hidden="true">
                                <span class="mascot-emoji">{{ mod.icon || '📘' }}</span>
                            </div>

                            <!-- Grade Tag -->
                            <div class="card-grade-badge">
                                <span>{{ mod.grade_level || 'All Grades' }}</span>
                            </div>

                            <span v-if="mod.is_new" class="card-new-badge">✨ NEW</span>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <!-- Clay Plaque Title -->
                            <div class="clay-title-plaque">
                                <span class="plaque-rivet rivet-left" aria-hidden="true"></span>
                                <h2 class="plaque-title-text" :title="mod.title">
                                    {{ mod.title }}
                                </h2>
                                <span class="plaque-rivet rivet-right" aria-hidden="true"></span>
                            </div>

                            <!-- Stats & XP Pill -->
                            <div class="card-stats-row">
                                <span class="card-stat-chip">
                                    📖 <strong>{{ mod.lectures_count ?? 0 }}</strong> {{ (mod.lectures_count ?? 0) === 1 ? 'lesson' : 'lessons' }}
                                </span>
                                <span v-if="mod.xp_bonus" class="card-stat-chip xp-chip">
                                    ⭐ <strong>+{{ mod.xp_bonus }}</strong> XP
                                </span>
                            </div>

                            <!-- 3D Quest Code Pill -->
                            <div
                                v-if="mod.join_code"
                                class="card-quest-code-pill"
                                :class="{ 'is-copied': copiedCodeId === mod.id }"
                                role="button"
                                tabindex="0"
                                :title="`Quest Code: ${mod.join_code} (Click to copy)`"
                                @click.stop="copyModuleCode(mod)"
                                @keydown.enter.stop="copyModuleCode(mod)"
                                @keydown.space.stop.prevent="copyModuleCode(mod)"
                            >
                                <span class="code-pill-icon">{{ copiedCodeId === mod.id ? '✅' : '🔑' }}</span>
                                <span class="code-pill-lbl">CODE:</span>
                                <strong class="code-pill-val">{{ mod.join_code }}</strong>
                                <span class="code-pill-action">{{ copiedCodeId === mod.id ? 'COPIED!' : 'COPY' }}</span>
                            </div>

                            <!-- Card Footer Action Buttons -->
                            <div class="card-actions-row">
                                <button
                                    type="button"
                                    class="card-action-btn btn-status-toggle"
                                    :class="mod.status === 'published' ? 'is-published' : 'is-draft'"
                                    :disabled="togglingModuleId === mod.id"
                                    :title="mod.status === 'draft' ? 'Click to publish' : 'Click to unpublish'"
                                    @click.stop="toggleModuleStatus(mod)"
                                >
                                    <span v-if="togglingModuleId === mod.id">⏳ Saving…</span>
                                    <span v-else-if="mod.status === 'published'">🌟 Live</span>
                                    <span v-else>📝 Draft</span>
                                </button>

                                <button
                                    type="button"
                                    class="card-action-btn btn-open-card"
                                    :title="'Open ' + mod.title"
                                    @click.stop="openModule(mod)"
                                >
                                    <span>Open ➔</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Empty Modules State -->
            <div v-else class="empty-modules-card">
                <div class="empty-module-badge">🏰</div>
                <h3 class="empty-module-title">
                    {{ selected_classroom ? `No Modules Assigned to ${selected_classroom.name} Yet` : 'No Modules Created Yet' }}
                </h3>
                <p class="empty-module-desc">
                    {{ selected_classroom ? `Assign reading adventure worlds to ${selected_classroom.name} from the Classrooms page so students in this room can embark on their journey.` : 'Summon your first learning module to start adding lectures, rich text lessons, images, audio narrations, and videos!' }}
                </p>
                <div class="empty-actions-row">
                    <button v-if="selected_classroom" type="button" class="btn-3d btn-grade-action" @click="goToClasses">
                        <span>🏫 GO TO CLASSROOMS & ASSIGN</span>
                    </button>
                    <button type="button" class="btn-3d btn-primary-action btn-summon-lg" @click="goToCreateModule">
                        <span>✨ + CREATE NEW MODULE</span>
                    </button>
                </div>
            </div>
        </section>
    </TeacherLayout>
</template>

<style scoped>
/* ==========================================================================
   SLEEK COMPACT COMMAND DECK
   ========================================================================== */
.modules-command-deck {
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F5F0E4 100%));
    border: 2.5px solid var(--game-border, #1D2440);
    border-radius: 18px;
    padding: 0.65rem 1rem;
    margin-bottom: 1rem;
    box-shadow:
        0 4px 0 var(--game-border, #1D2440),
        -3px -3px 8px var(--neu-light, rgba(255, 255, 255, 0.92)),
        6px 10px 18px var(--neu-navy-ambient, rgba(29, 36, 64, 0.1)),
        var(--neu-bevel-light, inset 2px 2px 4px rgba(255, 255, 255, 0.95)),
        var(--neu-bevel-dark, inset -2px -2px 4px rgba(194, 185, 166, 0.3));
}

.deck-main-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.deck-title-group {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.deck-title-line {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.deck-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.25rem;
    font-weight: 900;
    margin: 0;
    color: var(--game-navy, #1D2440);
    line-height: 1.1;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.85);
}

.deck-count-badge {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.7rem;
    font-weight: 800;
    background: #EEF2FF;
    color: #4F46E5;
    border: 1.5px solid #818CF8;
    padding: 0.12rem 0.55rem;
    border-radius: 9999px;
    box-shadow: 0 1.5px 0 #818CF8;
}

.deck-summary-strip {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.76rem;
    color: #556080;
    font-weight: 700;
}

.summary-chip strong {
    color: var(--game-navy, #1D2440);
}

.summary-dot {
    color: #94A3B8;
}

.deck-actions-group {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

/* Layout Switcher */
.deck-view-switch {
    display: inline-flex;
    background: #E2E8F0;
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 12px;
    padding: 2.5px;
    gap: 2.5px;
    box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.15);
}

.view-toggle-btn {
    border: none;
    background: transparent;
    border-radius: 8px;
    padding: 0.32rem 0.65rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.74rem;
    font-weight: 800;
    color: #64748B;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.16s ease;
    user-select: none;
}

.view-toggle-btn:hover {
    color: #1D2440;
}

.view-toggle-btn.is-active {
    background: #FFFFFF;
    color: #1D2440;
    box-shadow: 0 2px 5px rgba(29, 36, 64, 0.18), 0 1px 0 #94A3B8;
}

/* Room Selector Strip */
.room-selector-strip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.65rem;
    padding-top: 0.6rem;
    border-top: 1.5px solid rgba(29, 36, 64, 0.08);
}

.room-selector-label {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #64748B;
}

.room-filter-pill {
    padding: 0.28rem 0.65rem;
    border-radius: 999px;
    border: 1.5px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 2px 0 #CBD5E1;
    transition: transform 0.14s ease, background 0.14s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.room-filter-pill:hover {
    transform: translateY(-1.5px);
    border-color: #0284C7;
}

.room-filter-pill.active {
    background: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    border-color: #0369A1;
    color: #FFFFFF;
    box-shadow: 0 2.5px 0 #0369A1;
    transform: translateY(-1.5px);
}

.room-manage-link {
    margin-left: auto;
    padding: 0.28rem 0.65rem;
    border-radius: 999px;
    background: #F1F5F9;
    border: 1.5px solid #CBD5E1;
    color: #475569;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.15s ease;
}

.room-manage-link:hover {
    background: #E2E8F0;
    color: #1E293B;
}

/* 3D Button Base */
.btn-3d {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    letter-spacing: 0.04em;
    border-radius: 9999px;
    border: 2px solid #1D2440;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: transform 0.18s var(--spring-bounce, cubic-bezier(0.34, 1.56, 0.64, 1)), box-shadow 0.18s ease;
    outline: none;
    user-select: none;
    position: relative;
    overflow: hidden;
}

.btn-3d::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 38%;
    height: 100%;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.55) 50%, rgba(255, 255, 255, 0) 100%);
    transform: translate3d(-145%, 0, 0) skewX(-24deg);
    pointer-events: none;
}

.btn-3d:hover::after {
    animation: pixarShineSweep 0.65s ease-out forwards;
}

.btn-3d:active {
    transform: translate3d(0, 3px, 0) scale(0.98) !important;
    box-shadow: 0 1px 0 #1D2440 !important;
}

.btn-primary-action {
    background: linear-gradient(145deg, #38A3F5 0%, #1677C2 100%);
    color: #FFFFFF;
    padding: 0.48rem 0.95rem;
    font-size: 0.78rem;
    border-color: #0E5287;
    box-shadow:
        0 3.5px 0 #0E5287,
        0 5px 12px rgba(35, 149, 235, 0.32),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.8),
        inset -1.5px -1.5px 3px rgba(14, 82, 135, 0.35);
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #0E5287, 0 8px 16px rgba(35, 149, 235, 0.4);
}

.btn-ranking-action {
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #573F00;
    padding: 0.48rem 0.85rem;
    font-size: 0.76rem;
    border-color: #8A6400;
    box-shadow:
        0 3.5px 0 #8A6400,
        0 5px 12px rgba(255, 183, 3, 0.3),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.85);
}

.btn-ranking-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #8A6400, 0 8px 16px rgba(255, 183, 3, 0.4);
}

.btn-grade-action {
    background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    padding: 0.55rem 1.15rem;
    font-size: 0.82rem;
    border-color: #B45309;
    box-shadow: 0 3.5px 0 #92400E;
}

.btn-sm {
    padding: 0.4rem 0.85rem;
    font-size: 0.74rem;
}

.btn-summon-lg {
    padding: 0.65rem 1.45rem;
    font-size: 0.88rem;
}

/* ==========================================================================
   HIGH-DENSITY COMPACT LIST VIEW
   ========================================================================== */
.quest-list {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.quest-row {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    padding: 0.5rem 0.85rem;
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F5F0E4 100%));
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 16px;
    box-shadow:
        0 3.5px 0 var(--game-border, #1D2440),
        -2px -2px 6px rgba(255, 255, 255, 0.9),
        4px 8px 16px rgba(29, 36, 64, 0.08);
    transition: transform 0.16s var(--spring-bounce, cubic-bezier(0.34, 1.56, 0.64, 1)), box-shadow 0.16s ease;
    cursor: pointer;
    user-select: none;
    box-sizing: border-box;
}

.quest-row:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5px 0 var(--game-border, #1D2440),
        -3px -3px 8px rgba(255, 255, 255, 0.95),
        6px 12px 20px rgba(29, 36, 64, 0.12);
}

/* Row Thumbnail Box (Cover art + Icon) */
.row-thumb-box {
    position: relative;
    width: 60px;
    height: 44px;
    border-radius: 10px;
    overflow: hidden;
    border: 1.5px solid var(--game-border, #1D2440);
    background: #AEE2FF;
    flex-shrink: 0;
    box-shadow: 0 1.5px 0 var(--game-border, #1D2440);
}

.row-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.row-thumb-fallback {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #AEE2FF 0%, #D0EDFE 60%, #FFF2E7 100%);
    position: relative;
}

.row-fallback-sun {
    position: absolute;
    top: 6px;
    right: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #FBBF24;
}

.row-fallback-hill {
    position: absolute;
    bottom: -6px;
    left: -10px;
    right: -10px;
    height: 24px;
    border-radius: 50% 50% 0 0;
    background: #34D399;
}

.row-thumb-icon {
    position: absolute;
    bottom: 2px;
    right: 3px;
    font-size: 1rem;
    line-height: 1;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.4));
}

.row-new-dot {
    position: absolute;
    top: 2px;
    left: 2px;
    font-size: 0.7rem;
    line-height: 1;
}

/* Row Content */
.row-content {
    display: flex;
    flex-direction: column;
    gap: 0.18rem;
    flex: 1;
    min-width: 0;
}

.row-header-line {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.row-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.row-grade-chip {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.68rem;
    font-weight: 800;
    color: #475569;
    background: #E2E8F0;
    border: 1px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.08rem 0.45rem;
}

.row-status-chip {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.65rem;
    font-weight: 800;
    border-radius: 9999px;
    padding: 0.08rem 0.45rem;
}

.row-status-chip.is-live {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #86EFAC;
}

.row-status-chip.is-draft {
    background: #FFE4E6;
    color: #BE123C;
    border: 1px solid #FDA4AF;
}

.row-meta-line {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.74rem;
    color: #556080;
}

.row-stat-item strong {
    color: var(--game-navy, #1D2440);
}

.row-desc-preview {
    color: #64748B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 28ch;
}

/* Row Code Keycard */
.row-code-keycard {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: linear-gradient(145deg, #FEF9C3 0%, #FEF08A 100%);
    border: 1.5px solid #B45309;
    border-radius: 9999px;
    padding: 0.22rem 0.55rem;
    cursor: pointer;
    box-shadow: 0 2px 0 #92400E;
    user-select: none;
    transition: transform 0.14s ease;
    flex-shrink: 0;
}

.row-code-keycard:hover {
    transform: translateY(-1px);
}

.row-code-keycard.is-copied {
    background: linear-gradient(145deg, #DCFCE7 0%, #86EFAC 100%);
    border-color: #166534;
    box-shadow: 0 2px 0 #14532D;
}

.code-ico {
    font-size: 0.85rem;
}

.code-lbl {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.55rem;
    font-weight: 900;
    color: #92400E;
}

.code-val {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.85rem;
    font-weight: 900;
    letter-spacing: 0.07em;
    color: #451A03;
}

.code-btn {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.58rem;
    font-weight: 900;
    color: #92400E;
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid #B45309;
    border-radius: 9999px;
    padding: 0.08rem 0.4rem;
}

/* Row Actions */
.row-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.row-action-btn {
    border-radius: 9999px;
    border: 1.5px solid #1D2440;
    padding: 0.3rem 0.65rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    font-size: 0.72rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s ease;
    user-select: none;
    white-space: nowrap;
}

.row-action-btn:hover {
    transform: translateY(-1.5px);
}

.row-action-btn:active {
    transform: translateY(1.5px);
}

.btn-publish {
    background: linear-gradient(145deg, #34D399 0%, #059669 100%);
    color: #FFFFFF;
    border-color: #064E3B;
    box-shadow: 0 2px 0 #064E3B;
}

.btn-unpublish {
    background: linear-gradient(145deg, #FFE4E6 0%, #FDA4AF 100%);
    color: #9F1239;
    border-color: #BE123C;
    box-shadow: 0 2px 0 #BE123C;
}

.btn-open-module {
    background: linear-gradient(145deg, #60A5FA 0%, #2563EB 100%);
    color: #FFFFFF;
    border-color: #1E40AF;
    box-shadow: 0 2px 0 #1E40AF;
}

/* ==========================================================================
   COMPACT BENTO GRID VIEW
   ========================================================================== */
.kingdoms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
    gap: 1rem;
}

.kingdom-card {
    position: relative;
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F5F0E4 100%));
    border-radius: 20px;
    border: 2.5px solid var(--game-border, #1D2440);
    padding: 0.75rem;
    box-shadow:
        0 6px 0 var(--game-border, #1D2440),
        -4px -4px 10px rgba(255, 255, 255, 0.95),
        6px 14px 24px rgba(29, 36, 64, 0.14),
        var(--neu-bevel-light, inset 2px 2px 4px rgba(255, 255, 255, 0.95)),
        var(--neu-bevel-dark, inset -3px -3px 6px rgba(194, 185, 166, 0.3));
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    transform: translate3d(0, 0, 0);
    transition: transform 0.24s var(--spring-bounce), box-shadow 0.24s ease;
    cursor: pointer;
    box-sizing: border-box;
}

.kingdom-card:hover {
    transform: translate3d(0, -5px, 0) scale(1.012);
    box-shadow:
        0 10px 0 var(--game-border, #1D2440),
        -5px -5px 14px rgba(255, 255, 255, 0.95),
        10px 20px 32px rgba(29, 36, 64, 0.18);
}

.kingdom-card:hover .card-mascot-pod {
    transform: scale(1.1) rotate(-6deg) translateY(-2px);
}

/* Compact Diorama Frame */
.card-diorama-frame {
    position: relative;
    width: 100%;
    height: 84px;
    border-radius: 14px;
    border: 2px solid var(--game-border, #1D2440);
    overflow: hidden;
    background: #000000;
    box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.35), 0 1.5px 0 rgba(255, 255, 255, 0.85);
}

.frame-window {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.frame-center-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.kingdom-card:hover .frame-center-image {
    transform: scale(1.05);
}

.frame-fallback-scene {
    width: 100%;
    height: 100%;
}

.fallback-scenery-svg {
    width: 100%;
    height: 100%;
    display: block;
}

.frame-lens-glare {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 35%, transparent 60%);
}

.card-mascot-pod {
    position: absolute;
    top: 6px;
    left: 8px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFEA85 0%, #FFB703 55%, #D97706 100%);
    border: 2px solid var(--game-border, #1D2440);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 0 #1D2440, inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.9);
    z-index: 2;
    transition: transform 0.24s var(--spring-bounce);
}

.mascot-emoji {
    font-size: 1.15rem;
    line-height: 1;
}

.card-grade-badge {
    position: absolute;
    top: 6px;
    right: 8px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(6px);
    border: 1.5px solid var(--game-border, #1D2440);
    border-radius: 9999px;
    padding: 0.12rem 0.5rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.62rem;
    font-weight: 800;
    color: var(--game-navy, #1D2440);
    box-shadow: 0 1.5px 0 var(--game-border, #1D2440);
    z-index: 2;
}

.card-new-badge {
    position: absolute;
    bottom: 6px;
    left: 8px;
    background: #FBBF24;
    color: #451A03;
    border: 1px solid #B45309;
    border-radius: 9999px;
    padding: 0.08rem 0.45rem;
    font-family: var(--font-game);
    font-size: 0.6rem;
    font-weight: 900;
    z-index: 2;
}

/* Card Body */
.card-body {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.clay-title-plaque {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    background: linear-gradient(145deg, #FFFFFF 0%, #EFF2FA 100%);
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 12px;
    padding: 0.32rem 0.65rem;
    box-shadow:
        0 2.5px 0 var(--game-border, #1D2440),
        inset 1.5px 1.5px 3px rgba(255, 255, 255, 0.95),
        inset -1.5px -1.5px 3px rgba(164, 176, 204, 0.35);
}

.plaque-title-text {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.92rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0;
    text-align: center;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.plaque-rivet {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: linear-gradient(135deg, #D4AF37 0%, #AA771C 100%);
    border: 1px solid #553300;
    flex-shrink: 0;
}

/* Card Stats */
.card-stats-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.card-stat-chip {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.74rem;
    font-weight: 700;
    color: #475569;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9999px;
    padding: 0.15rem 0.55rem;
}

.card-stat-chip strong {
    color: var(--game-navy, #1D2440);
}

.xp-chip {
    color: #B45309;
    background: #FEF3C7;
    border-color: #FCD34D;
}

/* Card Quest Code Pill */
.card-quest-code-pill {
    background: linear-gradient(145deg, #FEF9C3 0%, #FEF08A 45%, #FDE047 100%);
    border: 2px solid #B45309;
    border-radius: 12px;
    padding: 0.32rem 0.65rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.45rem;
    cursor: pointer;
    box-shadow:
        0 2.5px 0 #92400E,
        inset 1.5px 1.5px 2px rgba(255, 255, 255, 0.95);
    user-select: none;
    transition: transform 0.14s var(--spring-bounce);
}

.card-quest-code-pill:hover {
    transform: translateY(-1.5px);
}

.card-quest-code-pill.is-copied {
    background: linear-gradient(145deg, #DCFCE7 0%, #86EFAC 100%);
    border-color: #166534;
    box-shadow: 0 2.5px 0 #14532D;
}

.code-pill-icon {
    font-size: 0.85rem;
}

.code-pill-lbl {
    font-family: var(--font-game);
    font-size: 0.55rem;
    font-weight: 900;
    color: #92400E;
}

.code-pill-val {
    font-family: var(--font-game);
    font-size: 0.92rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #451A03;
}

.code-pill-action {
    font-family: var(--font-game);
    font-size: 0.58rem;
    font-weight: 900;
    color: #92400E;
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid #B45309;
    border-radius: 9999px;
    padding: 0.12rem 0.45rem;
}

/* Card Actions Row */
.card-actions-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.45rem;
}

.card-action-btn {
    border-radius: 9999px;
    border: 1.5px solid #1D2440;
    padding: 0.38rem 0.5rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    font-size: 0.72rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    transition: transform 0.14s var(--spring-bounce);
    user-select: none;
    white-space: nowrap;
}

.card-action-btn:hover {
    transform: translateY(-1.5px);
}

.card-action-btn:active {
    transform: translateY(1.5px);
}

.btn-status-toggle.is-published {
    background: linear-gradient(145deg, #DCFCE7 0%, #86EFAC 100%);
    color: #14532D;
    border-color: #166534;
    box-shadow: 0 2.5px 0 #14532D;
}

.btn-status-toggle.is-draft {
    background: linear-gradient(145deg, #FFE4E6 0%, #FDA4AF 100%);
    color: #9F1239;
    border-color: #BE123C;
    box-shadow: 0 2.5px 0 #BE123C;
}

.btn-open-card {
    background: linear-gradient(145deg, #60A5FA 0%, #2563EB 100%);
    color: #FFFFFF;
    border-color: #1E40AF;
    box-shadow: 0 2.5px 0 #1E40AF;
}

/* ==========================================================================
   EMPTY STATES
   ========================================================================== */
.search-empty-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.15rem;
    border: 2.5px dashed #94A3C4;
    border-radius: 18px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F5F0E4 100%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.9), 0 6px 14px -6px rgba(29, 36, 64, 0.15);
}

.search-empty-copy {
    flex: 1;
    min-width: 12rem;
}

.search-empty-orb {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 1.25rem;
    background: radial-gradient(circle at 32% 26%, #FFF7D1 0%, #FBBF24 55%, #B45309 100%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.6), 0 2.5px 0 #92400E;
    animation: search-empty-bob 3.5s ease-in-out infinite;
}

.search-empty-title {
    margin: 0;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
}

.search-empty-desc {
    margin: 0.1rem 0 0;
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.78rem;
    font-weight: 700;
    color: #6C7594;
}

@keyframes search-empty-bob {
    50% { transform: translateY(-4px) rotate(-5deg); }
}

.empty-modules-card {
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F8F5EE 100%));
    border: 2.5px solid var(--game-border, #1D2440);
    border-radius: 24px;
    padding: 2.2rem 1.5rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow:
        0 6px 0 var(--game-border, #1D2440),
        -4px -4px 12px rgba(255, 255, 255, 0.9),
        6px 14px 28px rgba(29, 36, 64, 0.12);
    margin-top: 0.5rem;
}

.empty-module-badge {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: linear-gradient(145deg, #FFFFFF 0%, #E8EEF9 100%);
    border: 2px solid var(--game-border, #1D2440);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin-bottom: 0.85rem;
    box-shadow: 0 3px 0 var(--game-border, #1D2440);
}

.empty-module-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0 0 0.35rem 0;
}

.empty-module-desc {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.88rem;
    font-weight: 600;
    color: #556080;
    max-width: 480px;
    margin: 0 0 1.25rem 0;
    line-height: 1.45;
}

.empty-actions-row {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
}

@keyframes pixarShineSweep {
    0% { transform: translate3d(-145%, 0, 0) skewX(-24deg); }
    100% { transform: translate3d(280%, 0, 0) skewX(-24deg); }
}

@media (max-width: 768px) {
    .deck-main-row {
        flex-direction: column;
        align-items: stretch;
    }
    .deck-actions-group {
        justify-content: space-between;
    }
    .quest-row {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .row-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 640px) {
    .kingdoms-grid {
        grid-template-columns: 1fr;
    }
}
</style>
