<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeacherLayout from '@/Components/TeacherLayout.vue';
import { flushAllClientCache, prefetchRoute } from '@/Composables/usePrefetch';

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
    router.visit(classId ? `/teachers/dashboard?class_id=${classId}` : '/teachers/dashboard', {
        preserveState: false,
        preserveScroll: true,
    });
};

const copiedCodeId = ref(null);

const copyModuleCode = async (mod) => {
    if (!mod?.join_code) return;
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

const goToClasses = () => {
    router.visit('/teachers/classes');
};

const goToGrading = () => {
    router.visit('/teachers/grading');
};

const goToCreateModule = () => {
    router.visit('/teachers/create/module');
};

const openModule = (mod) => {
    router.visit(`/teachers/modules/${mod.slug}`);
};
</script>

<template>
    <Head title="Your Modules HQ • WEBREAD" />

    <TeacherLayout
        :user="user"
        active-nav="modules"
        :pending-grading-count="pending_grading_count"
    >
        <!-- YOUR MODULES -->
        <section class="tab-panel world-tab">
                <div class="panel-header-row">
                    <div>
                        <h1 class="panel-title">
                            🏰 {{ selected_classroom ? `${selected_classroom.name} • Modules` : 'Your Modules' }}
                        </h1>
                        <p class="panel-sub">
                            {{ selected_classroom ? `Showing reading adventure worlds assigned to ${selected_classroom.name}.` : 'Select a module to manage lectures, multimedia lessons, reading quests, and milestones.' }}
                        </p>
                    </div>
                    <div class="header-buttons">
                        <button type="button" class="btn-3d btn-grade-action" @mouseenter="prefetchTeacherRoute('/teachers/grading', ['teacher-grading'])" @focus="prefetchTeacherRoute('/teachers/grading', ['teacher-grading'])" @click="goToGrading">
                            <span>📝 GRADE SUBMISSIONS <strong v-if="pending_grading_count > 0" class="btn-pending-tag">({{ pending_grading_count }})</strong></span>
                        </button>
                        <button type="button" class="btn-3d btn-primary-action" @mouseenter="prefetchTeacherRoute('/teachers/create/module', ['teacher-modules'])" @focus="prefetchTeacherRoute('/teachers/create/module', ['teacher-modules'])" @click="goToCreateModule">
                            <span>✨ + ADD NEW MODULE</span>
                        </button>
                    </div>
                </div>

                <!-- Classroom / Room Filter Strip (When Teacher has Classrooms) -->
                <div v-if="classrooms && classrooms.length > 0" class="room-selector-strip">
                    <span class="room-selector-label">FILTER BY ROOM:</span>
                    <button
                        type="button"
                        class="room-filter-pill"
                        :class="{ active: !selected_classroom_id }"
                        @click="filterByClassroom(null)"
                    >
                        <span>🏰 All Modules</span>
                    </button>
                    <button
                        v-for="c in classrooms"
                        :key="c.id"
                        type="button"
                        class="room-filter-pill"
                        :class="{ active: selected_classroom_id === c.id }"
                        @click="filterByClassroom(c.id)"
                    >
                        <span>{{ c.icon }} {{ c.name }} ({{ c.modules_count }})</span>
                    </button>
                    <button
                        type="button"
                        class="room-manage-link"
                        @click="goToClasses"
                    >
                        <span>⚙️ Manage Rooms</span>
                    </button>
                </div>

                <!-- Shared SVG Definitions hoisted once to prevent duplicate DOM IDs and per-card filter overhead -->
                <svg v-if="modules.length > 0" width="0" height="0" style="position: absolute; pointer-events: none;" aria-hidden="true">
                    <defs>
                        <filter id="clayStarDropShadow" x="-30%" y="-30%" width="160%" height="160%">
                            <feDropShadow dx="-2" dy="5" stdDeviation="3.5" flood-color="#8C5320" flood-opacity="0.32" />
                        </filter>
                        <radialGradient id="clayStarFill" cx="35%" cy="30%" r="65%">
                            <stop offset="0%" stop-color="#FFF2B2" />
                            <stop offset="40%" stop-color="#FCD34D" />
                            <stop offset="80%" stop-color="#F59E0B" />
                            <stop offset="100%" stop-color="#D97706" />
                        </radialGradient>
                        <linearGradient id="clayStarBevel" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.9" />
                            <stop offset="100%" stop-color="#B45309" stop-opacity="0.35" />
                        </linearGradient>
                        <filter id="cloudShadowLeft" x="-30%" y="-30%" width="170%" height="170%">
                            <feDropShadow dx="-4" dy="7" stdDeviation="5.5" flood-color="#404B6D" flood-opacity="0.22" />
                        </filter>
                        <linearGradient id="cloudGradLeft" x1="20%" y1="0%" x2="60%" y2="100%">
                            <stop offset="0%" stop-color="#FFFFFF" />
                            <stop offset="60%" stop-color="#F2F5FB" />
                            <stop offset="100%" stop-color="#D7E0F0" />
                        </linearGradient>
                        <radialGradient id="cloudPuffLeft" cx="30%" cy="25%" r="70%">
                            <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.95" />
                            <stop offset="60%" stop-color="#FFFFFF" stop-opacity="0" />
                        </radialGradient>
                        <filter id="cloudShadowRight" x="-30%" y="-30%" width="170%" height="170%">
                            <feDropShadow dx="4" dy="7" stdDeviation="5.5" flood-color="#404B6D" flood-opacity="0.22" />
                        </filter>
                        <linearGradient id="cloudGradRight" x1="40%" y1="0%" x2="40%" y2="100%">
                            <stop offset="0%" stop-color="#FFFFFF" />
                            <stop offset="60%" stop-color="#F2F5FB" />
                            <stop offset="100%" stop-color="#D7E0F0" />
                        </linearGradient>
                        <radialGradient id="cloudPuffRight" cx="35%" cy="25%" r="70%">
                            <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.95" />
                            <stop offset="60%" stop-color="#FFFFFF" stop-opacity="0" />
                        </radialGradient>
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

                <div v-if="modules.length > 0" class="kingdoms-grid">
                    <div
                        v-for="mod in modules"
                        :key="mod.id"
                        v-memo="[mod.id, mod.title, mod.theme, mod.status, mod.is_new, mod.cover_image_url, mod.join_code, copiedCodeId === mod.id, togglingModuleId === mod.id]"
                        class="kingdom-card"
                        :class="`theme-${mod.theme}`"
                        role="button"
                        tabindex="0"
                        :aria-label="'Open learning module: ' + mod.title"
                        @mouseenter="prefetchTeacherRoute(`/teachers/modules/${mod.slug}`, ['teacher-modules', `module-${mod.slug}`])"
                        @focus="prefetchTeacherRoute(`/teachers/modules/${mod.slug}`, ['teacher-modules', `module-${mod.slug}`])"
                        @click="openModule(mod)"
                        @keydown.enter="openModule(mod)"
                        @keydown.space.prevent="openModule(mod)"
                    >
                        <!-- 3D Clay Decorative Accent: Star perched on top frame rim -->
                        <div class="clay-accent clay-star" aria-hidden="true">
                            <svg viewBox="0 0 54 54" class="clay-star-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g filter="url(#clayStarDropShadow)">
                                    <path d="M27 5 C28.5 5 31.2 15 33 17 C34.8 19 45 20 46 21.5 C47 23 39.5 29.5 38.5 31.5 C37.5 33.5 40 43.5 38.5 44.5 C37 45.5 29 40 27 40 C25 40 17 45.5 15.5 44.5 C14 43.5 16.5 33.5 15.5 31.5 C14.5 29.5 7 23 8 21.5 C9 20 19.2 19 21 17 C22.8 15 25.5 5 27 5 Z"
                                        fill="url(#clayStarFill)" stroke="url(#clayStarBevel)" stroke-width="2.5" stroke-linejoin="round" />
                                    <ellipse cx="23" cy="16" rx="3.5" ry="2" transform="rotate(-25 23 16)" fill="#FFFFFF" fill-opacity="0.75" />
                                </g>
                            </svg>
                        </div>

                        <!-- 3D Clay Decorative Accent: Left Puffy Cloud -->
                        <div class="clay-accent clay-cloud clay-cloud-left" aria-hidden="true">
                            <svg viewBox="0 0 110 70" class="clay-cloud-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g filter="url(#cloudShadowLeft)">
                                    <path d="M26 52 C15 52 8 44 8 36 C8 28 14 22 22 21 C24 12 33 5 45 5 C56 5 65 11 68 20 C72 17 78 17 82 20 C88 24 90 31 88 37 C96 39 102 45 102 52 C102 60 95 65 85 65 L26 65 C18 65 14 59 16 54 Z"
                                        fill="url(#cloudGradLeft)" />
                                    <ellipse cx="45" cy="20" rx="16" ry="10" fill="url(#cloudPuffLeft)" />
                                    <ellipse cx="72" cy="30" rx="12" ry="8" fill="url(#cloudPuffLeft)" />
                                    <ellipse cx="26" cy="36" rx="10" ry="7" fill="url(#cloudPuffLeft)" />
                                </g>
                            </svg>
                        </div>

                        <!-- 3D Clay Decorative Accent: Right Puffy Cloud -->
                        <div class="clay-accent clay-cloud clay-cloud-right" aria-hidden="true">
                            <svg viewBox="0 0 110 70" class="clay-cloud-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g filter="url(#cloudShadowRight)">
                                    <path d="M24 61 C14 61 8 54 8 46 C8 39 13 33 20 31 C21 21 30 14 41 14 C48 14 55 18 58 24 C62 20 68 19 74 22 C81 26 84 34 82 40 C91 41 98 47 98 55 C98 62 91 67 82 67 L24 67 Z"
                                        fill="url(#cloudGradRight)" />
                                    <ellipse cx="42" cy="25" rx="14" ry="9" fill="url(#cloudPuffRight)" />
                                    <ellipse cx="68" cy="33" rx="11" ry="7" fill="url(#cloudPuffRight)" />
                                </g>
                            </svg>
                        </div>

                        <!-- Floating Discrete Status / New Clay Badges -->
                        <div class="clay-card-badges">
                            <span v-if="mod.is_new" class="clay-badge-new">✨ NEW</span>
                            <button
                                type="button"
                                class="clay-badge-draft"
                                :class="{
                                    'is-published': mod.status === 'published',
                                    'is-toggling': togglingModuleId === mod.id,
                                }"
                                :disabled="togglingModuleId === mod.id"
                                :title="mod.status === 'draft' ? 'Click to publish this module' : 'Click to unpublish (set to draft)'"
                                :aria-label="mod.status === 'draft' ? 'Draft module. Click to publish' : 'Published module. Click to set as draft'"
                                @click.stop="toggleModuleStatus(mod)"
                                @keydown.stop.enter="toggleModuleStatus(mod)"
                                @keydown.stop.space.prevent="toggleModuleStatus(mod)"
                            >
                                <span v-if="togglingModuleId === mod.id" class="badge-status-icon" aria-hidden="true">⏳</span>
                                <span v-else-if="mod.status === 'draft'" class="badge-status-icon" aria-hidden="true">📝</span>
                                <span v-else class="badge-status-icon" aria-hidden="true">🌟</span>
                                <span>{{ togglingModuleId === mod.id ? 'UPDATING...' : (mod.status === 'draft' ? 'DRAFT' : 'PUBLISHED') }}</span>
                            </button>
                        </div>

                        <!-- Stepped Neumorphic Bezel holding Center Picture Window -->
                        <div class="frame-inner-bezel">
                            <div class="frame-window">
                                <img
                                    v-if="mod.cover_image_url"
                                    :src="mod.cover_image_url"
                                    :alt="mod.title"
                                    loading="lazy"
                                    decoding="async"
                                    class="frame-center-image"
                                />
                                <!-- 3D Cartoon Fallback Fantasy Landscape (when no cover image uploaded) -->
                                <div v-else class="frame-fallback-scene" aria-hidden="true">
                                    <svg viewBox="0 0 600 360" preserveAspectRatio="xMidYMid slice" class="fallback-scenery-svg">
                                        <!-- Pastel Sky -->
                                        <rect width="600" height="360" fill="url(#scenerySky)" />

                                        <!-- Background Clouds -->
                                        <path d="M70 120 C70 100 90 85 115 85 C135 85 152 98 158 112 C168 108 180 114 184 124 C192 126 198 134 198 142 C198 152 188 158 175 158 L75 158 C62 158 55 148 58 138 C58 130 63 123 70 120 Z" fill="#FFFFFF" fill-opacity="0.85" />
                                        <path d="M360 90 C360 72 378 58 402 58 C422 58 438 70 444 84 C454 80 466 86 470 95 C478 97 484 104 484 112 C484 122 474 128 460 128 L365 128 C352 128 346 118 349 108 C349 100 354 93 360 90 Z" fill="#FFFFFF" fill-opacity="0.88" />

                                        <!-- Distant Snow-Capped Mountains -->
                                        <path d="M120 250 L195 120 L270 250 Z" fill="url(#mountPink)" />
                                        <path d="M195 120 L175 160 C185 168 205 168 215 160 Z" fill="#FFFFFF" />
                                        <path d="M220 260 L330 90 L440 260 Z" fill="url(#mountPurple)" />
                                        <path d="M330 90 L295 150 C315 162 345 162 365 150 Z" fill="#FFFFFF" />
                                        <path d="M380 260 L450 145 L520 260 Z" fill="url(#mountPink)" />
                                        <path d="M450 145 L430 180 C440 188 460 188 470 180 Z" fill="#FFFFFF" />

                                        <!-- Rolling Hills -->
                                        <path d="M-20 260 Q80 190 220 240 T460 230 Q540 240 620 210 L620 360 L-20 360 Z" fill="url(#hillGreen1)" />
                                        <path d="M-20 280 Q140 220 310 270 T620 250 L620 360 L-20 360 Z" fill="url(#hillGreen2)" />
                                        <path d="M-20 310 Q160 260 360 300 T620 280 L620 360 L-20 360 Z" fill="url(#hillYellow)" />

                                        <!-- Winding Cartoon Path -->
                                        <path d="M250 360 Q280 300 290 260 Q300 240 310 235 L330 238 Q320 245 315 265 Q310 305 350 360 Z" fill="url(#pathGrad)" />

                                        <!-- Candy Trees Left Side -->
                                        <rect x="75" y="225" width="10" height="35" rx="3" fill="#8B5E3C" />
                                        <circle cx="80" cy="215" r="28" fill="#B388FF" />
                                        <circle cx="72" cy="205" r="10" fill="#D1C4E9" fill-opacity="0.6" />
                                        <rect x="135" y="240" width="8" height="28" rx="2" fill="#8B5E3C" />
                                        <circle cx="139" cy="235" r="20" fill="#FF80AB" />
                                        <circle cx="134" cy="228" r="7" fill="#FFD1DC" fill-opacity="0.6" />
                                        <rect x="180" y="250" width="8" height="24" rx="2" fill="#8B5E3C" />
                                        <circle cx="184" cy="245" r="18" fill="#80D8FF" />

                                        <!-- Candy Trees Right Side -->
                                        <rect x="495" y="215" width="12" height="42" rx="3" fill="#8B5E3C" />
                                        <circle cx="501" cy="205" r="32" fill="#FF6E6E" />
                                        <circle cx="492" cy="192" r="12" fill="#FFA4A4" fill-opacity="0.6" />
                                        <rect x="445" y="235" width="10" height="32" rx="2.5" fill="#8B5E3C" />
                                        <circle cx="450" cy="225" r="22" fill="#FFA726" />
                                        <circle cx="444" cy="218" r="8" fill="#FFCC80" fill-opacity="0.6" />
                                        <polygon points="415,260 405,230 425,230" fill="#26A69A" />
                                        <polygon points="415,240 407,215 423,215" fill="#4DB6AC" />
                                    </svg>
                                </div>

                                <!-- Pixar Lens Gloss Glare -->
                                <div class="frame-lens-glare" aria-hidden="true"></div>

                                <!-- Interactive Hover Action Pill -->
                                <div class="frame-hover-pill" aria-hidden="true">
                                    <span class="hover-pill-text">OPEN MODULE</span>
                                    <span class="hover-pill-arrow">➔</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3D Extruded Neumorphic Plaque with Module Title -->
                        <div class="clay-title-plaque">
                            <span class="plaque-rivet rivet-left" aria-hidden="true"></span>
                            <span class="plaque-title-text" :title="mod.title">
                                {{ mod.title }}
                            </span>
                            <span class="plaque-rivet rivet-right" aria-hidden="true"></span>
                        </div>

                        <!-- 3D Quest Code Badge & 1-Click Copy -->
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
                    </div>
                </div>

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
                        <button type="button" class="btn-3d btn-primary-action" @click="goToCreateModule">
                            <span>✨ + CREATE NEW MODULE</span>
                        </button>
                    </div>
                </div>
            </section>
    </TeacherLayout>
</template>

<style scoped>
.btn-3d {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    letter-spacing: 0.04em;
    border-radius: 9999px;
    border: 2.5px solid #1D2440;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    transition: transform 0.14s var(--spring-bounce, cubic-bezier(0.34, 1.56, 0.64, 1)), box-shadow 0.14s var(--spring-bounce, cubic-bezier(0.34, 1.56, 0.64, 1)), filter 0.14s ease;
    outline: none;
    user-select: none;
    position: relative;
}

.btn-3d:active {
    transform: translateY(4px) !important;
    box-shadow:
        0 1px 0 #1D2440,
        inset 3px 3px 6px rgba(0, 0, 0, 0.22),
        inset -2px -2px 5px rgba(255, 255, 255, 0.4) !important;
}

/* Classroom / Room Filter Strip */
.room-selector-strip {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    background: rgba(255, 255, 255, 0.85);
    padding: 0.65rem 1rem;
    border-radius: 20px;
    border: 2px solid #E2E8F0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
}

.room-selector-label {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #64748B;
}

.room-filter-pill {
    padding: 0.45rem 0.95rem;
    border-radius: 999px;
    border: 2px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.82rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #CBD5E1;
    transition: transform 0.15s ease, background 0.15s ease;
}

.room-filter-pill:hover {
    transform: translateY(-2px);
    border-color: #0284C7;
}

.room-filter-pill.active {
    background: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    border-color: #0369A1;
    color: #FFFFFF;
    box-shadow: 0 3px 0 #0369A1, 0 6px 14px rgba(2, 132, 199, 0.3);
    transform: translateY(-2px);
}

.room-manage-link {
    margin-left: auto;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    background: #F1F5F9;
    border: 1.5px solid #CBD5E1;
    color: #475569;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.15s ease;
}

.room-manage-link:hover {
    background: #E2E8F0;
    color: #1E293B;
}

.empty-actions-row {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.panel-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.15rem;
    flex-wrap: wrap;
    gap: 0.85rem;
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F3EFE4 100%));
    border: 3px solid var(--game-border, #2B3354);
    border-radius: 22px;
    padding: 0.95rem 1.4rem;
    box-shadow:
        0 5px 0 var(--game-border, #2B3354),
        -5px -5px 12px var(--neu-light, rgba(255, 255, 255, 0.92)),
        8px 12px 22px var(--neu-navy-ambient, rgba(29, 36, 64, 0.12)),
        var(--neu-bevel-light, inset 3px 3px 6px rgba(255, 255, 255, 0.95)),
        var(--neu-bevel-dark, inset -4px -4px 8px rgba(194, 185, 166, 0.38));
}

.panel-title {
    font-size: 1.45rem;
    font-weight: 900;
    margin: 0;
    color: var(--game-navy);
    text-shadow: 1px 2px 0 rgba(255, 255, 255, 0.85);
}

.panel-sub {
    font-size: 0.84rem;
    font-family: var(--font-ui);
    color: #6C7594;
    margin: 0.15rem 0 0 0;
    font-weight: 700;
}

.header-buttons {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.btn-ranking-action {
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #573F00;
    padding: 0.6rem 1.1rem;
    font-size: 0.82rem;
    border-color: #8A6400;
    box-shadow:
        0 4px 0 #8A6400,
        0 6px 14px rgba(255, 183, 3, 0.3),
        inset 2px 2px 4px rgba(255, 255, 255, 0.75),
        inset -2px -2px 4px rgba(184, 133, 0, 0.35);
}

.btn-ranking-action:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #8A6400,
        0 10px 18px rgba(255, 183, 3, 0.4),
        inset 2px 2px 4px rgba(255, 255, 255, 0.85),
        inset -2px -2px 4px rgba(184, 133, 0, 0.35);
}

.btn-primary-action {
    background: linear-gradient(145deg, #38A3F5 0%, #1677C2 100%);
    color: #FFFFFF;
    padding: 0.6rem 1.15rem;
    font-size: 0.82rem;
    border-color: #0E5287;
    box-shadow:
        0 4px 0 #0E5287,
        0 6px 14px rgba(35, 149, 235, 0.3),
        inset 2px 2px 4px rgba(255, 255, 255, 0.55),
        inset -2px -2px 5px rgba(14, 82, 135, 0.45);
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #0E5287,
        0 10px 18px rgba(35, 149, 235, 0.4),
        inset 2px 2px 4px rgba(255, 255, 255, 0.65),
        inset -2px -2px 5px rgba(14, 82, 135, 0.45);
}

.btn-grade-action {
    background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    padding: 0.6rem 1.15rem;
    font-size: 0.82rem;
    border-color: #B45309;
    box-shadow:
        0 4px 0 #92400E,
        0 6px 14px rgba(245, 158, 11, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.65),
        inset -2px -2px 5px rgba(146, 64, 14, 0.4);
}

.btn-grade-action:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #92400E,
        0 10px 18px rgba(245, 158, 11, 0.45),
        inset 2px 2px 4px rgba(255, 255, 255, 0.75),
        inset -2px -2px 5px rgba(146, 64, 14, 0.4);
}

.btn-pending-tag {
    background: #DC2626;
    color: #FFF;
    border-radius: 9999px;
    padding: 0.1rem 0.45rem;
    font-size: 0.75rem;
    margin-left: 0.25rem;
}

.grading-pill.has-pending {
    border-color: #F59E0B;
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    box-shadow: 0 4px 0 #D97706, 0 6px 12px rgba(245, 158, 11, 0.3);
}

/* Kingdoms Grid: Responsive Multi-Column Layout (Auto-Fill) Utilizing Full Desktop Canvas */
.kingdoms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
    gap: 2.5rem clamp(1.5rem, 2vw, 2.25rem);
    padding-top: 0.75rem;
    padding-bottom: 2.5rem;
}

/* ==========================================================================
   PIXAR-INSPIRED VOLUMETRIC 3D KINGDOM CARDS
   ========================================================================== */
.kingdom-card {
    --frame-grad-1: #E4ECFF;
    --frame-grad-2: #E8E0FB;
    --frame-grad-3: #FDE6EE;
    --frame-grad-4: #FEECE5;
    --frame-bezel-1: #CAD8FB;
    --frame-bezel-2: #E9DEFA;
    --frame-pedestal: #B4BFE6;
    --frame-glow: rgba(132, 94, 194, 0.30);
    --frame-ambient: rgba(45, 32, 85, 0.16);
    --frame-belly-shadow: rgba(145, 130, 190, 0.36);
    --plaque-pedestal: rgba(132, 94, 194, 0.25);

    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border-radius: 32px;
    padding: 14px 14px 16px 14px;
    border: 3.5px solid rgba(255, 255, 255, 0.92);
    background: linear-gradient(145deg, var(--frame-grad-1) 0%, var(--frame-grad-2) 35%, var(--frame-grad-3) 70%, var(--frame-grad-4) 100%);
    box-shadow:
        0 8px 0 var(--frame-pedestal),
        0 16px 28px -4px var(--frame-glow),
        0 26px 46px -8px var(--frame-ambient),
        inset 0 3px 2px rgba(255, 255, 255, 0.98),
        inset 0 -8px 16px -4px var(--frame-belly-shadow);
    cursor: pointer;
    user-select: none;
    outline: none;
    content-visibility: auto;
    contain-intrinsic-size: 360px;
    transform: translate3d(0, 0, 0);
    transition:
        transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1),
        box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Volumetric Specular Glaze Crescent on Upper Card Face */
.kingdom-card::before {
    content: "";
    position: absolute;
    inset: 4px 14px auto 14px;
    height: 38%;
    border-radius: 30px 30px 90px 90px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.72) 0%, rgba(255, 255, 255, 0.02) 100%);
    pointer-events: none;
    z-index: 2;
}

/* Tactile 3D Lift on Hover with Dynamic Shadow Expansion */
.kingdom-card:hover {
    transform: translate3d(0, -9px, 0) scale(1.025);
    box-shadow:
        0 14px 0 var(--frame-pedestal),
        0 24px 38px -4px var(--frame-glow),
        0 36px 60px -10px var(--frame-ambient),
        inset 0 3px 2px rgba(255, 255, 255, 1),
        inset 0 -8px 16px -4px var(--frame-belly-shadow);
}

/* Playful Pixar Squash & Stretch on Active Click */
.kingdom-card:active {
    transform: translate3d(0, 4px, 0) scale(0.985, 0.975);
    box-shadow:
        0 3px 0 var(--frame-pedestal),
        0 8px 16px -2px var(--frame-glow),
        0 14px 24px -4px var(--frame-ambient),
        inset 0 2px 2px rgba(255, 255, 255, 0.9),
        inset 0 -4px 8px -2px var(--frame-belly-shadow);
}

.kingdom-card:focus-visible {
    box-shadow:
        0 0 0 3px #FFFFFF,
        0 0 0 6px #2395EB,
        0 14px 0 var(--frame-pedestal),
        0 22px 34px -4px var(--frame-glow);
}

/* Biome Color Variations with Pixar Subsurface Scattering (SSS) */
.theme-forest {
    --frame-grad-1: #D6F9EB;
    --frame-grad-2: #B8F2D7;
    --frame-grad-3: #FEEBCB;
    --frame-grad-4: #FFE3D1;
    --frame-bezel-1: #9DE7C4;
    --frame-bezel-2: #BCEFD7;
    --frame-pedestal: #70C59B;
    --frame-glow: rgba(14, 175, 120, 0.30);
    --frame-ambient: rgba(18, 65, 48, 0.16);
    --frame-belly-shadow: rgba(70, 160, 120, 0.36);
    --plaque-pedestal: rgba(14, 175, 120, 0.28);
}

.theme-ocean {
    --frame-grad-1: #D8EDFF;
    --frame-grad-2: #BFE3FF;
    --frame-grad-3: #FFE0F0;
    --frame-grad-4: #FFEAE4;
    --frame-bezel-1: #A3D4FD;
    --frame-bezel-2: #C6E4FD;
    --frame-pedestal: #78B5E8;
    --frame-glow: rgba(24, 130, 235, 0.30);
    --frame-ambient: rgba(15, 48, 90, 0.16);
    --frame-belly-shadow: rgba(65, 130, 195, 0.36);
    --plaque-pedestal: rgba(24, 130, 235, 0.28);
}

.theme-cosmic {
    --frame-grad-1: #E2DCFD;
    --frame-grad-2: #CFBEFA;
    --frame-grad-3: #FCE1F2;
    --frame-grad-4: #FFE0E6;
    --frame-bezel-1: #BCACF8;
    --frame-bezel-2: #D8CBFA;
    --frame-pedestal: #9883E6;
    --frame-glow: rgba(135, 85, 230, 0.32);
    --frame-ambient: rgba(45, 25, 95, 0.18);
    --frame-belly-shadow: rgba(115, 80, 185, 0.36);
    --plaque-pedestal: rgba(135, 85, 230, 0.28);
}

.theme-volcano {
    --frame-grad-1: #FFE7DC;
    --frame-grad-2: #FED3C4;
    --frame-grad-3: #FFF0CC;
    --frame-grad-4: #FFE3BA;
    --frame-bezel-1: #FDBCA6;
    --frame-bezel-2: #FED0BE;
    --frame-pedestal: #E09176;
    --frame-glow: rgba(235, 95, 45, 0.32);
    --frame-ambient: rgba(85, 30, 15, 0.16);
    --frame-belly-shadow: rgba(195, 90, 60, 0.36);
    --plaque-pedestal: rgba(235, 95, 45, 0.28);
}

/* Stepped Inner Frame Bezel (Sculpted Toy Tray) */
.frame-inner-bezel {
    position: relative;
    z-index: 1;
    border-radius: 26px;
    padding: 8px;
    background: linear-gradient(135deg, var(--frame-bezel-1) 0%, var(--frame-bezel-2) 100%);
    box-shadow:
        inset 0 3px 6px rgba(20, 25, 55, 0.22),
        inset 0 -2px 4px rgba(255, 255, 255, 0.9),
        0 1.5px 2px rgba(255, 255, 255, 0.9);
}

/* Recessed Center Picture Window (Diorama Lens) */
.frame-window {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    aspect-ratio: 16 / 9.5;
    background: #AEE2FF;
    box-shadow:
        inset 0 4px 10px rgba(10, 18, 45, 0.34),
        inset 0 0 0 1.5px rgba(0, 0, 0, 0.08);
}

.frame-center-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: translateZ(0);
    transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1), filter 0.45s ease;
}

.kingdom-card:hover .frame-center-image {
    transform: scale(1.05) translateZ(0);
    filter: brightness(1.03) saturate(1.08);
}

.frame-fallback-scene {
    width: 100%;
    height: 100%;
    display: flex;
    transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1), filter 0.45s ease;
}

.kingdom-card:hover .frame-fallback-scene {
    transform: scale(1.04);
    filter: brightness(1.03) saturate(1.06);
}

.fallback-scenery-svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* Pixar Crystal Toy Lens Glare */
.frame-lens-glare {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 4;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.42) 0%,
        rgba(255, 255, 255, 0.12) 26%,
        transparent 52%
    );
    transition: opacity 0.3s ease;
}

/* Interactive Hover Action Pill */
.frame-hover-pill {
    position: absolute;
    bottom: 12px;
    right: 12px;
    z-index: 6;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F5F7FC 100%);
    border: 2px solid #1D2440;
    box-shadow:
        0 3px 0 #1D2440,
        0 6px 14px rgba(0, 0, 0, 0.22),
        inset 0 1.5px 1px rgba(255, 255, 255, 1);
    opacity: 0;
    transform: translate3d(0, 10px, 0) scale(0.92);
    transition:
        opacity 0.22s ease,
        transform 0.26s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}

.kingdom-card:hover .frame-hover-pill {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
}

.hover-pill-text {
    font-family: var(--font-game);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    color: #1D2440;
}

.hover-pill-arrow {
    font-size: 0.75rem;
    color: var(--game-blue);
    transition: transform 0.2s ease;
}

.kingdom-card:hover .hover-pill-arrow {
    transform: translateX(2px);
}

/* 3D Decorative Overlapping Clay Accents */
.clay-accent {
    position: absolute;
    pointer-events: none;
    z-index: 10;
}

/* Clay Star: Peeking on top rim */
.clay-star {
    top: -16px;
    right: 22%;
    width: 48px;
    height: 48px;
    transform: rotate(10deg);
    filter: drop-shadow(0 4px 6px rgba(180, 100, 20, 0.32));
    animation: px-star-bob 4s ease-in-out infinite alternate;
    transition: transform 0.32s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.32s ease;
}

@keyframes px-star-bob {
    0% { transform: translateY(0) rotate(8deg); }
    100% { transform: translateY(-4px) rotate(14deg); }
}

.kingdom-card:hover .clay-star {
    animation-play-state: paused;
    transform: rotate(24deg) scale(1.18) translateY(-6px);
    filter: drop-shadow(0 8px 14px rgba(245, 158, 11, 0.5));
}

.clay-star-svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

/* Clay Clouds on Left and Right Frame Edges */
.clay-cloud-left {
    top: 34%;
    left: -18px;
    width: 82px;
    height: 54px;
    animation: px-cloud-bob-left 5.5s ease-in-out infinite alternate;
    transition: transform 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes px-cloud-bob-left {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-3px) rotate(-1.5deg); }
}

.kingdom-card:hover .clay-cloud-left {
    animation-play-state: paused;
    transform: translateX(-6px) scale(1.1) translateY(-2px);
}

.clay-cloud-right {
    top: 26%;
    right: -18px;
    width: 82px;
    height: 54px;
    animation: px-cloud-bob-right 6s ease-in-out infinite alternate;
    transition: transform 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes px-cloud-bob-right {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(3px) rotate(1.5deg); }
}

.kingdom-card:hover .clay-cloud-right {
    animation-play-state: paused;
    transform: translateX(6px) scale(1.1) translateY(-2px);
}

.clay-cloud-svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

/* Discrete Floating Clay Status Badges (Pixar Scout Pins) */
.clay-card-badges {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 11;
    display: flex;
    gap: 0.4rem;
    pointer-events: none;
}

.clay-badge-new {
    position: relative;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    background: linear-gradient(180deg, #FFE169 0%, #FFB703 50%, #FB8500 100%);
    color: #381A00;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    box-shadow:
        0 3px 0 #9E4F00,
        0 6px 12px rgba(251, 133, 0, 0.38),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 0.95),
        inset 0 -2px 3px rgba(158, 79, 0, 0.3);
    animation: newBadgePulse 2s infinite ease-in-out;
}

.clay-badge-new::before {
    content: "";
    position: absolute;
    inset: 1.5px 4px auto 4px;
    height: 38%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.75) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

@keyframes newBadgePulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08) rotate(-1deg);
    }
}

.clay-badge-draft {
    position: relative;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    background: linear-gradient(180deg, #FFFFFF 0%, #FFE4E6 50%, #FDA4AF 100%);
    color: #9F1239;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    border: 1.5px solid rgba(255, 255, 255, 0.95);
    box-shadow:
        0 3px 0 #BE123C,
        0 4px 10px rgba(190, 18, 60, 0.25),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -2px 3px rgba(190, 18, 60, 0.25);
    pointer-events: auto;
    cursor: pointer;
    user-select: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-family: inherit;
    line-height: 1;
    appearance: none;
    outline: none;
    transition: transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.15s ease, filter 0.15s ease;
}

.clay-badge-draft:hover:not(:disabled) {
    transform: translateY(-1.5px) scale(1.05);
    box-shadow:
        0 4.5px 0 #BE123C,
        0 6px 12px rgba(190, 18, 60, 0.32),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -2px 3px rgba(190, 18, 60, 0.25);
}

.clay-badge-draft:active:not(:disabled) {
    transform: translateY(2px) scale(0.97);
    box-shadow:
        0 1px 0 #BE123C,
        0 2px 4px rgba(190, 18, 60, 0.2),
        inset 0 1px 1px rgba(255, 255, 255, 0.8),
        inset 0 -1.5px 2px rgba(190, 18, 60, 0.25);
}

.clay-badge-draft:focus-visible {
    outline: 2px solid #38BDF8;
    outline-offset: 2px;
}

.clay-badge-draft.is-published {
    background: linear-gradient(180deg, #FFFFFF 0%, #DCFCE7 45%, #86EFAC 100%);
    color: #14532D;
    box-shadow:
        0 3px 0 #15803D,
        0 4px 10px rgba(21, 128, 61, 0.25),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -2px 3px rgba(21, 128, 61, 0.25);
}

.clay-badge-draft.is-published:hover:not(:disabled) {
    transform: translateY(-1.5px) scale(1.05);
    box-shadow:
        0 4.5px 0 #15803D,
        0 6px 12px rgba(21, 128, 61, 0.32),
        inset 0 1.5px 1.5px rgba(255, 255, 255, 1),
        inset 0 -2px 3px rgba(21, 128, 61, 0.25);
}

.clay-badge-draft.is-published:active:not(:disabled) {
    transform: translateY(2px) scale(0.97);
    box-shadow:
        0 1px 0 #15803D,
        0 2px 4px rgba(21, 128, 61, 0.2),
        inset 0 1px 1px rgba(255, 255, 255, 0.8),
        inset 0 -1.5px 2px rgba(21, 128, 61, 0.25);
}

.clay-badge-draft.is-toggling {
    opacity: 0.85;
    cursor: wait;
    filter: saturate(0.8);
}

.badge-status-icon {
    font-size: 0.72rem;
    line-height: 1;
}

.clay-badge-draft::before {
    content: "";
    position: absolute;
    inset: 1.5px 4px auto 4px;
    height: 38%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

/* 3D Sculpted Collector Nameplate Plaque (Integrated Inside Card Deck) */
.clay-title-plaque {
    position: relative;
    width: 100%;
    min-height: 48px;
    padding: 0.55rem 0.85rem;
    border-radius: 18px;
    background: #FFFFFF;
    border: 2px solid rgba(29, 36, 64, 0.12);
    box-shadow:
        0 3.5px 0 var(--plaque-pedestal, rgba(29, 36, 64, 0.14)),
        0 6px 14px rgba(29, 36, 64, 0.05),
        inset 0 1.5px 1px #FFFFFF,
        inset 0 -2px 4px rgba(29, 36, 64, 0.04);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    z-index: 5;
    box-sizing: border-box;
    transition:
        transform 0.24s cubic-bezier(0.34, 1.56, 0.64, 1),
        box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1),
        border-color 0.2s ease;
}

.clay-title-plaque::before {
    content: "";
    position: absolute;
    inset: 2px 6px auto 6px;
    height: 40%;
    border-radius: 14px 14px 40px 40px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.85) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.kingdom-card:hover .clay-title-plaque {
    transform: translateY(-2px);
    border-color: rgba(35, 149, 235, 0.45);
    box-shadow:
        0 5px 0 var(--plaque-pedestal, rgba(35, 149, 235, 0.35)),
        0 10px 20px rgba(35, 149, 235, 0.14),
        inset 0 1.5px 1px #FFFFFF;
}

.plaque-rivet {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, #E5B83B 50%, #996515 100%);
    box-shadow:
        0 1px 1px rgba(0, 0, 0, 0.2),
        inset 0 0.5px 0.5px rgba(255, 255, 255, 0.9);
    flex-shrink: 0;
}

.rivet-left {
    margin-right: 0.55rem;
}

.rivet-right {
    margin-left: 0.55rem;
}

.plaque-title-text {
    font-family: var(--font-game);
    font-weight: 800;
    font-size: clamp(0.92rem, 1.05vw, 1.08rem);
    line-height: 1.25;
    letter-spacing: 0.015em;
    color: var(--game-navy);
    margin: 0;
    text-align: center;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.95);
    transition: color 0.2s ease;
}

.kingdom-card:hover .plaque-title-text {
    color: #1467A6;
}

/* 3D Quest Code Pill on Module Cards */
.card-quest-code-pill {
    position: relative;
    width: 100%;
    margin-top: 0.45rem;
    padding: 0.35rem 0.65rem;
    border-radius: 14px;
    background: linear-gradient(180deg, #FEF9C3 0%, #FEF08A 45%, #FDE047 100%);
    border: 2px solid #D97706;
    box-shadow:
        0 3px 0 #92400E,
        0 4px 10px rgba(180, 83, 9, 0.18),
        inset 0 1.5px 1px rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    color: #78350F;
    font-family: var(--font-game, -apple-system, sans-serif);
    cursor: pointer;
    transition: transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.15s ease;
    z-index: 6;
}

.card-quest-code-pill:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5px 0 #92400E,
        0 8px 14px rgba(180, 83, 9, 0.28),
        inset 0 1.5px 1px #FFFFFF;
}

.card-quest-code-pill:active {
    transform: translateY(2px);
    box-shadow:
        0 1px 0 #92400E,
        inset 0 1.5px 3px rgba(120, 53, 15, 0.3);
}

.card-quest-code-pill.is-copied {
    background: linear-gradient(180deg, #D1FAE5 0%, #A7F3D0 45%, #6EE7B7 100%);
    border-color: #059669;
    box-shadow:
        0 3px 0 #065F46,
        0 4px 10px rgba(5, 150, 105, 0.2);
    color: #064E3B;
}

.code-pill-icon {
    font-size: 0.85rem;
}

.code-pill-lbl {
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    opacity: 0.85;
}

.code-pill-val {
    font-size: 0.88rem;
    font-weight: 900;
    letter-spacing: 0.14em;
    color: #1E1B4B;
}

.card-quest-code-pill.is-copied .code-pill-val {
    color: #064E3B;
}

.code-pill-action {
    font-size: 0.58rem;
    font-weight: 900;
    background: rgba(0, 0, 0, 0.1);
    padding: 0.12rem 0.4rem;
    border-radius: 9999px;
    letter-spacing: 0.04em;
}

/* Empty Modules State */
.empty-modules-card {
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    padding: 3.5rem 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow:
        0 8px 0 var(--game-border),
        -6px -6px 14px var(--neu-light),
        10px 16px 28px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
}

.empty-module-badge {
    width: 76px;
    height: 76px;
    border-radius: 24px;
    background: linear-gradient(145deg, #FFFFFF 0%, #E8EEF9 100%);
    border: 3px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 1rem;
    box-shadow:
        0 4px 0 var(--game-border),
        inset 3px 3px 6px rgba(255, 255, 255, 0.95),
        inset -3px -3px 6px rgba(164, 176, 204, 0.4);
}

.empty-module-title {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.4rem 0;
}

.empty-module-desc {
    font-family: var(--font-ui);
    font-size: 0.95rem;
    font-weight: 700;
    color: #6C7594;
    max-width: 520px;
    margin: 0 0 1.5rem 0;
}

@media (max-width: 720px) {
    .kingdoms-grid {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
