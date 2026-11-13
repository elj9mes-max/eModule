<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeacherLayout from '@/Components/TeacherLayout.vue';
import { flushAllClientCache } from '@/Composables/usePrefetch';
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
    classrooms: {
        type: Array,
        default: () => [],
    },
    available_modules: {
        type: Array,
        default: () => [],
    },
    pending_grading_count: {
        type: Number,
        default: 0,
    },
});

const showCreateModal = ref(false);
const showAssignModal = ref(false);
const activeClassroomForAssign = ref(null);
const copiedCodeId = ref(null);
const actionNotice = ref(null);

const createForm = useForm({
    name: '',
    grade_level: 'Grade 3 • Reading Legends',
    theme: 'forest',
    icon: '🏫',
    description: '',
    max_students: 45,
    module_ids: [],
});

const themeOptions = [
    { id: 'forest', label: 'Emerald Woods', icon: '🌲', color: '#10B981', gradient: 'linear-gradient(135deg, #A7F3D0, #10B981)' },
    { id: 'ocean', label: 'Coral Lagoon', icon: '🌊', color: '#0EA5E9', gradient: 'linear-gradient(135deg, #BAE6FD, #0284C7)' },
    { id: 'cosmic', label: 'Starlight Sky', icon: '🚀', color: '#8B5CF6', gradient: 'linear-gradient(135deg, #DDD6FE, #7C3AED)' },
    { id: 'volcano', label: 'Sunset Peaks', icon: '🌋', color: '#F97316', gradient: 'linear-gradient(135deg, #FED7AA, #EA580C)' },
];

const iconOptions = ['🏫', '🌲', '🚀', '🌊', '⭐', '🏰', '🦊', '🦁', '🦉', '💎'];

const copyClassCode = async (c) => {
    playSparkle();
    const codeStr = String(c.class_code);
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

    copiedCodeId.value = c.id;
    actionNotice.value = `Access Code ${codeStr} copied! Give this 6-digit code to students to join ${c.name}.`;
    setTimeout(() => {
        if (copiedCodeId.value === c.id) {
            copiedCodeId.value = null;
        }
    }, 2500);
    setTimeout(() => {
        if (actionNotice.value?.includes(codeStr)) {
            actionNotice.value = null;
        }
    }, 4500);
};

const openCreateModal = () => {
    playBoing(0.95);
    createForm.reset();
    createForm.module_ids = [];
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    playPop(1.1);
    showCreateModal.value = false;
};

const submitCreateClassroom = () => {
    playClick();
    createForm.post('/teachers/classes', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const openAssignModal = (classroom) => {
    playBoing(0.95);
    activeClassroomForAssign.value = classroom;
    showAssignModal.value = true;
};

const closeAssignModal = () => {
    playPop(1.1);
    showAssignModal.value = false;
    activeClassroomForAssign.value = null;
};

const assignModuleToClass = (moduleId) => {
    if (!activeClassroomForAssign.value) return;
    playClick();
    router.post(
        `/teachers/classes/${activeClassroomForAssign.value.id}/modules`,
        { module_id: moduleId },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeAssignModal();
            },
        }
    );
};

const viewClassStudents = (classroom) => {
    playClick();
    router.visit(`/teachers/students?class_id=${classroom.id}`);
};

const viewClassModules = (classroom) => {
    playClick();
    router.visit(`/teachers/dashboard?class_id=${classroom.id}`);
};
</script>

<template>
    <Head title="Classrooms & Rooms • WEBREAD" />

    <TeacherLayout
        :user="user"
        active-nav="classes"
        :pending-grading-count="pending_grading_count"
    >
        <section class="tab-panel classes-tab">
            <!-- Stage Header Row -->
            <div class="panel-header-row">
                <div class="header-title-block">
                    <div class="header-kicker">CLASSROOM GUILDS</div>
                    <h1 class="panel-title">🏫 Your Classrooms</h1>
                    <p class="panel-sub">
                        Organize your students into separate rooms, assign reading adventure modules, and view fair classroom leaderboards!
                    </p>
                </div>
                <div class="header-buttons">
                    <button
                        type="button"
                        class="btn-3d btn-primary-action"
                        @click="openCreateModal"
                    >
                        <span>✨ + NEW CLASS ROOM</span>
                    </button>
                </div>
            </div>

            <!-- Classrooms Diorama Grid -->
            <div v-if="classrooms.length > 0" class="classrooms-grid">
                <article
                    v-for="c in classrooms"
                    :key="c.id"
                    class="classroom-card"
                    :class="`theme-${c.theme || 'forest'}`"
                >
                    <!-- PIXAR POP-UP DIORAMA WINDOW APERTURE -->
                    <div
                        class="classroom-diorama-stage is-clickable"
                        role="button"
                        tabindex="0"
                        :title="`Open ${c.name} modules dashboard`"
                        @click="viewClassModules(c)"
                        @keydown.enter="viewClassModules(c)"
                        @keydown.space.prevent="viewClassModules(c)"
                    >
                        <!-- Theme SVG: Emerald Woods -->
                        <svg
                            v-if="c.theme === 'forest' || !c.theme"
                            class="diorama-svg"
                            viewBox="0 0 500 240"
                            preserveAspectRatio="xMidYMid slice"
                            aria-hidden="true"
                        >
                            <defs>
                                <linearGradient :id="`skyForest-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#A7F3D0" />
                                    <stop offset="60%" stop-color="#E0F2FE" />
                                    <stop offset="100%" stop-color="#FEF3C7" />
                                </linearGradient>
                                <linearGradient :id="`hillEmerald1-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#34D399" />
                                    <stop offset="100%" stop-color="#059669" />
                                </linearGradient>
                                <linearGradient :id="`hillEmerald2-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#6EE7B7" />
                                    <stop offset="100%" stop-color="#10B981" />
                                </linearGradient>
                            </defs>
                            <rect width="500" height="240" :fill="`url(#skyForest-${c.id})`" />
                            <polygon points="120,180 200,80 280,180" fill="#99F6E4" opacity="0.75" />
                            <polygon points="200,80 185,105 215,105" fill="#FFFFFF" />
                            <polygon points="240,190 320,95 400,190" fill="#5EEAD4" opacity="0.65" />
                            <ellipse cx="90" cy="55" rx="35" ry="16" fill="#FFFFFF" opacity="0.85" />
                            <ellipse cx="115" cy="48" rx="24" ry="20" fill="#FFFFFF" opacity="0.95" />
                            <ellipse cx="380" cy="65" rx="42" ry="18" fill="#FFFFFF" opacity="0.8" />
                            <path d="M-20,170 Q110,130 250,160 T520,150 L520,240 L-20,240 Z" :fill="`url(#hillEmerald2-${c.id})`" />
                            <path d="M-20,190 Q140,165 310,195 T520,180 L520,240 L-20,240 Z" :fill="`url(#hillEmerald1-${c.id})`" />
                            <g transform="translate(60, 130)">
                                <rect x="18" y="45" width="8" height="22" rx="2" fill="#78350F" />
                                <polygon points="22,10 5,48 39,48" fill="#047857" />
                                <polygon points="22,2 9,32 35,32" fill="#10B981" />
                                <circle cx="22" cy="2" r="3.5" fill="#FDE047" />
                            </g>
                            <g transform="translate(400, 125)">
                                <rect x="18" y="48" width="8" height="25" rx="2" fill="#78350F" />
                                <polygon points="22,12 4,50 40,50" fill="#065F46" />
                                <polygon points="22,4 8,34 36,34" fill="#059669" />
                                <circle cx="22" cy="4" r="3.5" fill="#FDE047" />
                            </g>
                            <g transform="translate(210, 120)">
                                <rect x="20" y="30" width="40" height="35" rx="4" fill="#FFFFFF" stroke="#065F46" stroke-width="2.5" />
                                <polygon points="40,2 12,32 68,32" fill="#F59E0B" stroke="#B45309" stroke-width="2" />
                                <rect x="34" y="44" width="12" height="21" rx="6" fill="#065F46" />
                                <circle cx="40" cy="22" r="5" fill="#FDE047" />
                            </g>
                        </svg>

                        <!-- Theme SVG: Coral Lagoon -->
                        <svg
                            v-else-if="c.theme === 'ocean'"
                            class="diorama-svg"
                            viewBox="0 0 500 240"
                            preserveAspectRatio="xMidYMid slice"
                            aria-hidden="true"
                        >
                            <defs>
                                <linearGradient :id="`skyOcean-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#38BDF8" />
                                    <stop offset="55%" stop-color="#BAE6FD" />
                                    <stop offset="100%" stop-color="#FED7AA" />
                                </linearGradient>
                                <linearGradient :id="`waveGrad1-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#38BDF8" />
                                    <stop offset="100%" stop-color="#0284C7" />
                                </linearGradient>
                                <linearGradient :id="`waveGrad2-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#0EA5E9" />
                                    <stop offset="100%" stop-color="#0369A1" />
                                </linearGradient>
                            </defs>
                            <rect width="500" height="240" :fill="`url(#skyOcean-${c.id})`" />
                            <ellipse cx="250" cy="148" rx="85" ry="18" fill="#FDE68A" />
                            <path d="M235,148 Q238,115 250,95 Q252,118 245,148 Z" fill="#92400E" />
                            <circle cx="250" cy="95" r="18" fill="#10B981" />
                            <circle cx="240" cy="98" r="14" fill="#34D399" />
                            <circle cx="260" cy="98" r="14" fill="#059669" />
                            <path d="M-20,165 Q60,145 140,165 T300,165 T460,165 T520,165 L520,240 L-20,240 Z" :fill="`url(#waveGrad1-${c.id})`" />
                            <path d="M-20,166 Q60,146 140,166 T300,166 T460,166 T520,166" stroke="#FFFFFF" stroke-width="3" fill="none" opacity="0.75" />
                            <path d="M-20,192 Q70,172 160,192 T340,192 T520,192 L520,240 L-20,240 Z" :fill="`url(#waveGrad2-${c.id})`" />
                            <circle cx="80" cy="210" r="6" fill="#FFFFFF" opacity="0.5" />
                            <circle cx="95" cy="202" r="3.5" fill="#FFFFFF" opacity="0.6" />
                            <circle cx="410" cy="205" r="5" fill="#FFFFFF" opacity="0.5" />
                        </svg>

                        <!-- Theme SVG: Starlight Sky -->
                        <svg
                            v-else-if="c.theme === 'cosmic'"
                            class="diorama-svg"
                            viewBox="0 0 500 240"
                            preserveAspectRatio="xMidYMid slice"
                            aria-hidden="true"
                        >
                            <defs>
                                <linearGradient :id="`skyCosmic-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#0F0C29" />
                                    <stop offset="50%" stop-color="#302B63" />
                                    <stop offset="100%" stop-color="#24243E" />
                                </linearGradient>
                                <radialGradient :id="`nebula-${c.id}`" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" stop-color="#C084FC" stop-opacity="0.6" />
                                    <stop offset="60%" stop-color="#818CF8" stop-opacity="0.3" />
                                    <stop offset="100%" stop-color="#302B63" stop-opacity="0" />
                                </radialGradient>
                            </defs>
                            <rect width="500" height="240" :fill="`url(#skyCosmic-${c.id})`" />
                            <circle cx="280" cy="110" r="90" :fill="`url(#nebula-${c.id})`" />
                            <g transform="translate(110, 80) rotate(-18)">
                                <ellipse cx="0" cy="0" rx="36" ry="10" fill="none" stroke="#FDE047" stroke-width="6" opacity="0.85" />
                                <circle cx="0" cy="0" r="22" fill="#F59E0B" />
                                <path d="M-22,0 A22,22 0 0,0 22,0 Z" fill="#D97706" />
                                <ellipse cx="0" cy="0" rx="36" ry="10" fill="none" stroke="#FEF08A" stroke-width="2.5" opacity="0.95" />
                            </g>
                            <path d="M380,45 A20,20 0 1 0 405,75 A16,16 0 1 1 380,45 Z" fill="#FEF08A" />
                            <circle cx="60" cy="40" r="2.5" fill="#FFFFFF" />
                            <circle cx="210" cy="35" r="3" fill="#FDE047" />
                            <circle cx="320" cy="50" r="2" fill="#FFFFFF" />
                            <circle cx="440" cy="100" r="3" fill="#A78BFA" />
                            <circle cx="180" cy="120" r="2" fill="#FFFFFF" />
                            <circle cx="340" cy="150" r="2.5" fill="#FDE047" />
                            <path d="M-20,185 Q120,165 260,180 T520,175 L520,240 L-20,240 Z" fill="#1E1B4B" />
                            <path d="M-20,205 Q150,190 320,205 T520,198 L520,240 L-20,240 Z" fill="#0F172A" />
                        </svg>

                        <!-- Theme SVG: Sunset Peaks -->
                        <svg
                            v-else-if="c.theme === 'volcano'"
                            class="diorama-svg"
                            viewBox="0 0 500 240"
                            preserveAspectRatio="xMidYMid slice"
                            aria-hidden="true"
                        >
                            <defs>
                                <linearGradient :id="`skyVolcano-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FFEDD5" />
                                    <stop offset="40%" stop-color="#FDBA74" />
                                    <stop offset="100%" stop-color="#F43F5E" />
                                </linearGradient>
                                <linearGradient :id="`lavaGrad-${c.id}`" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FEF08A" />
                                    <stop offset="50%" stop-color="#F59E0B" />
                                    <stop offset="100%" stop-color="#DC2626" />
                                </linearGradient>
                            </defs>
                            <rect width="500" height="240" :fill="`url(#skyVolcano-${c.id})`" />
                            <polygon points="70,180 160,85 250,180" fill="#881337" opacity="0.75" />
                            <polygon points="210,180 320,70 430,180" fill="#4C0519" />
                            <ellipse cx="320" cy="70" rx="18" ry="7" fill="#FEF08A" />
                            <path d="M316,70 Q325,100 318,140 Q312,160 320,180" :stroke="`url(#lavaGrad-${c.id})`" stroke-width="5" fill="none" stroke-linecap="round" />
                            <path d="M-20,175 Q110,150 240,170 T520,165 L520,240 L-20,240 Z" fill="#9F1239" />
                            <path d="M-20,198 Q140,180 300,198 T520,192 L520,240 L-20,240 Z" fill="#4C0519" />
                            <circle cx="280" cy="115" r="3" fill="#FDE047" opacity="0.9" />
                            <circle cx="350" cy="95" r="2.5" fill="#F59E0B" opacity="0.85" />
                            <circle cx="310" cy="50" r="3.5" fill="#FEF08A" opacity="0.95" />
                        </svg>

                        <!-- Pixar Specular Lens Glare -->
                        <div class="frame-lens-glare" aria-hidden="true"></div>

                        <!-- 3D Floating Mascot Pod (Top-Left) -->
                        <div class="diorama-mascot-pod" aria-hidden="true">
                            <span class="mascot-emoji">{{ c.icon || '🏫' }}</span>
                        </div>

                        <!-- Grade Badge Pill (Top-Right) -->
                        <div class="diorama-grade-pill">
                            <span>{{ c.grade_level || 'Reading Cohort' }}</span>
                        </div>
                    </div>

                    <!-- 3D Extruded Neumorphic Plaque with Classroom Title -->
                    <div
                        class="clay-title-plaque is-clickable"
                        role="button"
                        tabindex="0"
                        :title="`Open ${c.name} modules dashboard`"
                        @click="viewClassModules(c)"
                        @keydown.enter="viewClassModules(c)"
                        @keydown.space.prevent="viewClassModules(c)"
                    >
                        <span class="plaque-rivet rivet-left" aria-hidden="true"></span>
                        <h2 class="plaque-title-text" :title="c.name">
                            {{ c.name }}
                        </h2>
                        <span class="plaque-rivet rivet-right" aria-hidden="true"></span>
                    </div>

                    <!-- 3D Embossed Access Code Keycard Ledge -->
                    <div
                        class="pixar-code-keycard"
                        :class="{ 'is-copied': copiedCodeId === c.id }"
                        role="button"
                        tabindex="0"
                        :title="`Click to copy 6-digit access code ${c.class_code}`"
                        @click="copyClassCode(c)"
                        @keydown.enter="copyClassCode(c)"
                        @keydown.space.prevent="copyClassCode(c)"
                    >
                        <div class="keycard-left">
                            <span class="keycard-icon">{{ copiedCodeId === c.id ? '✅' : '🗝️' }}</span>
                            <div class="keycard-meta">
                                <span class="keycard-label">STUDENT ACCESS CODE:</span>
                                <strong class="keycard-digits">{{ c.class_code }}</strong>
                            </div>
                        </div>
                        <span class="keycard-btn-action">{{ copiedCodeId === c.id ? 'COPIED!' : 'COPY' }}</span>
                    </div>

                    <!-- Toy Vinyl Stats Badges -->
                    <div class="card-toy-stats">
                        <div class="toy-stat-pill stat-students" title="Enrolled Students">
                            <span class="stat-icon">👥</span>
                            <div class="stat-meta">
                                <strong class="stat-val">{{ c.students_count }}</strong>
                                <span class="stat-lbl">{{ c.students_count === 1 ? 'STUDENT' : 'STUDENTS' }}</span>
                            </div>
                        </div>
                        <div
                            class="toy-stat-pill stat-modules is-clickable"
                            role="button"
                            tabindex="0"
                            :title="`View assigned worlds for ${c.name}`"
                            @click="viewClassModules(c)"
                            @keydown.enter="viewClassModules(c)"
                            @keydown.space.prevent="viewClassModules(c)"
                        >
                            <span class="stat-icon">🏰</span>
                            <div class="stat-meta">
                                <strong class="stat-val">{{ c.modules_count }}</strong>
                                <span class="stat-lbl">{{ c.modules_count === 1 ? 'WORLD' : 'WORLDS' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3D Tactile Action Buttons -->
                    <div class="card-footer-actions">
                        <button
                            type="button"
                            class="pixar-btn-card btn-roster"
                            @click="viewClassStudents(c)"
                        >
                            <span>🏆 Room Leaderboard</span>
                        </button>

                        <button
                            type="button"
                            class="pixar-btn-card btn-assign"
                            @click="openAssignModal(c)"
                        >
                            <span>➕ Assign World</span>
                        </button>
                    </div>
                </article>
            </div>

            <!-- Pixar Storybook Empty State Stage -->
            <div v-else class="empty-classes-stage">
                <div class="empty-diorama-frame">
                    <svg class="empty-castle-svg" viewBox="0 0 320 220" aria-hidden="true">
                        <defs>
                            <linearGradient id="emptyCastleSky" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#E0F2FE" />
                                <stop offset="100%" stop-color="#FEF9C3" />
                            </linearGradient>
                        </defs>
                        <rect width="320" height="220" rx="24" fill="url(#emptyCastleSky)" />
                        <ellipse cx="70" cy="50" rx="30" ry="14" fill="#FFFFFF" opacity="0.9" />
                        <ellipse cx="250" cy="65" rx="35" ry="16" fill="#FFFFFF" opacity="0.85" />
                        <rect x="100" y="90" width="35" height="75" rx="4" fill="#6366F1" />
                        <polygon points="117,55 95,90 140,90" fill="#4F46E5" />
                        <rect x="185" y="90" width="35" height="75" rx="4" fill="#6366F1" />
                        <polygon points="202,55 180,90 225,90" fill="#4F46E5" />
                        <rect x="125" y="110" width="70" height="55" rx="4" fill="#FFFFFF" stroke="#4F46E5" stroke-width="3" />
                        <rect x="145" y="130" width="30" height="35" rx="15" fill="#F59E0B" />
                        <text x="150" y="45" font-size="28">✨</text>
                        <text x="50" y="110" font-size="22">🎒</text>
                        <text x="240" y="125" font-size="22">⭐</text>
                    </svg>
                </div>
                <div class="empty-kicker">CLASSROOM GUILDS</div>
                <h2 class="empty-title">Summon Your First Classroom Realm</h2>
                <p class="empty-desc">
                    Create separate classrooms (e.g. "Grade 3 - Section Emerald") to generate exclusive 6-digit access codes for your students, assign reading adventure worlds, and host fair room leaderboards!
                </p>
                <button
                    type="button"
                    class="btn-3d btn-primary-action btn-summon-lg"
                    @click="openCreateModal"
                >
                    <span>✨ + SUMMON NEW CLASS ROOM</span>
                </button>
            </div>
        </section>

        <template #modals>
            <!-- Toast Notice -->
            <transition name="toast-slide">
                <div v-if="actionNotice" class="action-notice-toast">
                    <span class="toast-icon">🗝️</span>
                    <span class="toast-msg">{{ actionNotice }}</span>
                </div>
            </transition>

            <!-- PIXAR STORYBOOK CREATE CLASSROOM MODAL -->
            <teleport to="body">
                <transition name="pixar-modal-spring">
                    <div
                        v-if="showCreateModal"
                        class="pixar-modal-backdrop"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="create-room-title"
                        @click.self="closeCreateModal"
                    >
                        <div class="pixar-modal-shell">
                            <!-- 3D Overlapping Crest -->
                            <div class="pixar-modal__crest" aria-hidden="true">
                                <span class="crest-emoji">🏫</span>
                                <span class="crest-sparkle">✨</span>
                            </div>

                            <button
                                type="button"
                                class="pixar-modal__close"
                                aria-label="Close dialog"
                                @click="closeCreateModal"
                            >
                                ✕
                            </button>

                            <div class="dialog-header">
                                <span class="modal-kicker">SUMMON REALM</span>
                                <h2 id="create-room-title" class="modal-title">New Classroom Room</h2>
                                <p class="modal-desc">
                                    Students will use the generated 6-digit code to join this room and automatically unlock its reading worlds.
                                </p>
                            </div>

                            <form class="modal-form-body" @submit.prevent="submitCreateClassroom">
                                <div class="form-group">
                                    <label class="form-label" for="className">Class / Room Name *</label>
                                    <input
                                        id="className"
                                        v-model="createForm.name"
                                        type="text"
                                        class="clay-recessed-input"
                                        placeholder="e.g. Grade 3 - Emerald"
                                        required
                                    />
                                    <p v-if="createForm.errors.name" class="field-error">{{ createForm.errors.name }}</p>
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label class="form-label" for="gradeLevel">Grade Level</label>
                                        <input
                                            id="gradeLevel"
                                            v-model="createForm.grade_level"
                                            type="text"
                                            class="clay-recessed-input"
                                            placeholder="e.g. Grade 3 • Reading Legends"
                                        />
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="maxStudents">Max Capacity</label>
                                        <input
                                            id="maxStudents"
                                            v-model.number="createForm.max_students"
                                            type="number"
                                            min="5"
                                            max="150"
                                            class="clay-recessed-input"
                                        />
                                    </div>
                                </div>

                                <!-- Mascot Icon Picker with 3D Pop Buttons -->
                                <div class="form-group">
                                    <label class="form-label">Room Mascot Emblem</label>
                                    <div class="icon-selector-row">
                                        <button
                                            v-for="ic in iconOptions"
                                            :key="ic"
                                            type="button"
                                            class="toy-mascot-btn"
                                            :class="{ selected: createForm.icon === ic }"
                                            @click="createForm.icon = ic; playPop(1.2);"
                                        >
                                            <span class="mascot-ico">{{ ic }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Biome Theme Picker -->
                                <div class="form-group">
                                    <label class="form-label">Realm Environment & Lighting</label>
                                    <div class="theme-selector-grid">
                                        <button
                                            v-for="th in themeOptions"
                                            :key="th.id"
                                            type="button"
                                            class="theme-opt-card"
                                            :class="{ selected: createForm.theme === th.id }"
                                            @click="createForm.theme = th.id; playPop(1.1);"
                                        >
                                            <span class="theme-opt-icon">{{ th.icon }}</span>
                                            <span class="theme-opt-label">{{ th.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Initial Module Assignment -->
                                <div v-if="available_modules.length > 0" class="form-group">
                                    <label class="form-label">Assign Starting Reading Worlds (Optional)</label>
                                    <div class="modules-assign-check-list">
                                        <label
                                            v-for="mod in available_modules"
                                            :key="mod.id"
                                            class="module-check-item"
                                        >
                                            <input
                                                v-model="createForm.module_ids"
                                                type="checkbox"
                                                :value="mod.id"
                                            />
                                            <span class="check-mod-icon">{{ mod.icon || '📘' }}</span>
                                            <span class="check-mod-title">{{ mod.title }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="modal-form-actions">
                                    <button
                                        type="button"
                                        class="pixar-btn pixar-btn--cancel"
                                        @click="closeCreateModal"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="pixar-btn pixar-btn--submit"
                                        :disabled="createForm.processing"
                                    >
                                        <span>{{ createForm.processing ? 'Summoning...' : '✨ Create Room' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </transition>
            </teleport>

            <!-- PIXAR STORYBOOK ASSIGN MODULE MODAL -->
            <teleport to="body">
                <transition name="pixar-modal-spring">
                    <div
                        v-if="showAssignModal && activeClassroomForAssign"
                        class="pixar-modal-backdrop"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="assign-world-title"
                        @click.self="closeAssignModal"
                    >
                        <div class="pixar-modal-shell">
                            <!-- 3D Overlapping Crest -->
                            <div class="pixar-modal__crest" aria-hidden="true">
                                <span class="crest-emoji">🏰</span>
                                <span class="crest-sparkle">✨</span>
                            </div>

                            <button
                                type="button"
                                class="pixar-modal__close"
                                aria-label="Close dialog"
                                @click="closeAssignModal"
                            >
                                ✕
                            </button>

                            <div class="dialog-header">
                                <span class="modal-kicker">EXPEDITIONS</span>
                                <h2 id="assign-world-title" class="modal-title">Assign to {{ activeClassroomForAssign.name }}</h2>
                                <p class="modal-desc">
                                    Select a reading adventure world to immediately unlock for all students enrolled in this room:
                                </p>
                            </div>

                            <div v-if="available_modules.length > 0" class="assign-modules-grid">
                                <div
                                    v-for="mod in available_modules"
                                    :key="mod.id"
                                    class="assign-module-row"
                                >
                                    <div class="assign-mod-meta">
                                        <span class="assign-mod-icon">{{ mod.icon || '📘' }}</span>
                                        <div>
                                            <strong class="assign-mod-title">{{ mod.title }}</strong>
                                            <span class="assign-mod-grade">{{ mod.grade_level }}</span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="pixar-btn-card btn-assign-action"
                                        @click="assignModuleToClass(mod.id)"
                                    >
                                        <span>+ Assign</span>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="empty-assign-hint">
                                No modules created yet. Create a module first in your Dashboard!
                            </div>
                        </div>
                    </div>
                </transition>
            </teleport>
        </template>
    </TeacherLayout>
</template>

<style scoped>
/* ==========================================================================
   STAGE HEADER & BUTTONS
   ========================================================================== */
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

.header-title-block {
    display: flex;
    flex-direction: column;
}

.header-kicker {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #4F46E5;
    text-transform: uppercase;
}

.panel-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.45rem;
    font-weight: 900;
    margin: 0.1rem 0;
    color: var(--game-navy, #1D2440);
    text-shadow: 1px 2px 0 rgba(255, 255, 255, 0.85);
}

.panel-sub {
    font-size: 0.84rem;
    font-family: var(--font-ui, 'Nunito', sans-serif);
    color: #6C7594;
    margin: 0;
    font-weight: 700;
}

.header-buttons {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}

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

.btn-primary-action {
    background: linear-gradient(145deg, #38A3F5 0%, #1677C2 100%);
    color: #FFFFFF;
    padding: 0.6rem 1.25rem;
    font-size: 0.84rem;
    border-color: #0E5287;
    box-shadow:
        0 4px 0 #0E5287,
        0 6px 14px rgba(35, 149, 235, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.75),
        inset -2px -2px 4px rgba(14, 82, 135, 0.35);
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #0E5287,
        0 10px 18px rgba(35, 149, 235, 0.45),
        inset 2px 2px 4px rgba(255, 255, 255, 0.85),
        inset -2px -2px 4px rgba(14, 82, 135, 0.35);
}

/* ==========================================================================
   CLASSROOMS PIXAR DIORAMA GRID & CARDS
   ========================================================================== */
.classrooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.classroom-card {
    position: relative;
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F5F0E4 100%));
    border-radius: 28px;
    border: 3.5px solid var(--game-border, #1D2440);
    padding: 1.15rem;
    box-shadow:
        0 8px 0 var(--game-border, #1D2440),
        -5px -5px 12px rgba(255, 255, 255, 0.95),
        8px 16px 28px rgba(29, 36, 64, 0.14),
        var(--neu-bevel-light, inset 3px 3px 6px rgba(255, 255, 255, 0.95)),
        var(--neu-bevel-dark, inset -4px -4px 8px rgba(194, 185, 166, 0.35));
    display: flex;
    flex-direction: column;
    gap: 0.95rem;
    transition: transform 0.22s var(--spring-bounce, cubic-bezier(0.34, 1.56, 0.64, 1)), box-shadow 0.22s ease;
    box-sizing: border-box;
}

.classroom-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 12px 0 var(--game-border, #1D2440),
        -6px -6px 16px rgba(255, 255, 255, 0.95),
        10px 22px 34px rgba(29, 36, 64, 0.2);
}

/* Pop-Up Diorama Window */
.classroom-diorama-stage {
    position: relative;
    width: 100%;
    height: 165px;
    border-radius: 20px;
    border: 3px solid var(--game-border, #1D2440);
    overflow: hidden;
    background: #000000;
    box-shadow:
        inset 0 3px 6px rgba(0, 0, 0, 0.35),
        0 2px 0 rgba(255, 255, 255, 0.85);
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
}

.classroom-diorama-stage.is-clickable {
    cursor: pointer;
}

.classroom-diorama-stage.is-clickable:hover {
    transform: scale(1.02);
    box-shadow:
        inset 0 3px 6px rgba(0, 0, 0, 0.25),
        0 8px 18px rgba(35, 149, 235, 0.25);
}

.clay-title-plaque.is-clickable {
    cursor: pointer;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.2s ease;
}

.clay-title-plaque.is-clickable:hover {
    transform: translateY(-2px);
    border-color: #2563EB;
}

.toy-stat-pill.is-clickable {
    cursor: pointer;
    transition: transform 0.15s ease;
}

.toy-stat-pill.is-clickable:hover {
    transform: translateY(-2px);
}

.diorama-svg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Diagonal Lens Glare Sweep */
.frame-lens-glare {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0.12) 35%, transparent 60%);
}

/* Floating 3D Mascot Emblem Orb */
.diorama-mascot-pod {
    position: absolute;
    top: 10px;
    left: 12px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFEA85 0%, #FFB703 55%, #D97706 100%);
    border: 2.5px solid var(--game-border, #1D2440);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 4px 10px rgba(0, 0, 0, 0.25),
        0 2.5px 0 #1D2440,
        inset 2px 2px 4px rgba(255, 255, 255, 0.9),
        inset -2px -2px 4px rgba(180, 83, 9, 0.4);
    z-index: 2;
}

.mascot-emoji {
    font-size: 1.55rem;
    line-height: 1;
    filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.2));
}

/* Grade Tag Pill (Top Right) */
.diorama-grade-pill {
    position: absolute;
    top: 10px;
    right: 12px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(8px);
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 9999px;
    padding: 0.15rem 0.65rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    box-shadow: 0 2px 0 var(--game-border, #1D2440);
    letter-spacing: 0.02em;
    z-index: 2;
}

/* 3D Extruded Neumorphic Plaque with Rivets */
.clay-title-plaque {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    background: linear-gradient(145deg, #FFFFFF 0%, #EFF2FA 100%);
    border: 2.5px solid var(--game-border, #1D2440);
    border-radius: 16px;
    padding: 0.45rem 0.85rem;
    box-shadow:
        0 3px 0 var(--game-border, #1D2440),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(164, 176, 204, 0.4);
}

.plaque-title-text {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.05rem;
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
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, #D4AF37 0%, #AA771C 100%);
    border: 1px solid #553300;
    box-shadow: inset 1px 1px 1px rgba(255, 255, 255, 0.8);
    flex-shrink: 0;
}

/* 3D Access Code Keycard Ledge */
.pixar-code-keycard {
    background: linear-gradient(145deg, #FEF9C3 0%, #FEF08A 45%, #FDE047 100%);
    border: 2.5px solid #B45309;
    border-radius: 16px;
    padding: 0.55rem 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    cursor: pointer;
    box-shadow:
        0 3.5px 0 #92400E,
        inset 2px 2px 3px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(180, 83, 9, 0.25);
    transition: transform 0.15s var(--spring-bounce), box-shadow 0.15s ease;
    user-select: none;
}

.pixar-code-keycard:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5.5px 0 #92400E,
        0 8px 16px rgba(180, 83, 9, 0.25),
        inset 2px 2px 3px rgba(255, 255, 255, 0.95);
}

.pixar-code-keycard:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #92400E;
}

.pixar-code-keycard.is-copied {
    background: linear-gradient(145deg, #DCFCE7 0%, #86EFAC 100%);
    border-color: #166534;
    box-shadow: 0 3px 0 #14532D;
}

.keycard-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.keycard-icon {
    font-size: 1.3rem;
}

.keycard-meta {
    display: flex;
    flex-direction: column;
}

.keycard-label {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.58rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    color: #92400E;
}

.keycard-digits {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.15rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #451A03;
    line-height: 1.1;
}

.keycard-btn-action {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
    font-weight: 900;
    color: #92400E;
    background: rgba(255, 255, 255, 0.7);
    border: 1.5px solid #B45309;
    border-radius: 9999px;
    padding: 0.2rem 0.55rem;
    box-shadow: 0 1.5px 0 #92400E;
}

/* Toy Vinyl Stats */
.card-toy-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}

.toy-stat-pill {
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F1F4FA 100%));
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 16px;
    padding: 0.4rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow:
        0 2.5px 0 var(--game-border, #1D2440),
        inset 1px 1px 2px rgba(255, 255, 255, 0.9);
}

.stat-icon {
    font-size: 1.25rem;
}

.stat-meta {
    display: flex;
    flex-direction: column;
}

.stat-val {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    line-height: 1;
}

.stat-lbl {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #64748B;
    margin-top: 0.15rem;
}

/* Card 3D Footer Action Buttons */
.card-footer-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    margin-top: 0.2rem;
}

.pixar-btn-card {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    font-size: 0.8rem;
    border-radius: 9999px;
    border: 2px solid #1D2440;
    padding: 0.55rem 0.5rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s var(--spring-bounce);
    outline: none;
    user-select: none;
    white-space: nowrap;
}

.pixar-btn-card:hover {
    transform: translateY(-2px);
}

.pixar-btn-card:active {
    transform: translateY(3px) !important;
}

.btn-roster {
    background: linear-gradient(145deg, #FFEA85 0%, #FFB703 100%);
    color: #451A03;
    border-color: #8A6400;
    box-shadow:
        0 3.5px 0 #8A6400,
        inset 1px 1px 2px rgba(255, 255, 255, 0.9);
}

.btn-roster:active {
    box-shadow: 0 1px 0 #8A6400 !important;
}

.btn-assign {
    background: linear-gradient(145deg, #38A3F5 0%, #1677C2 100%);
    color: #FFFFFF;
    border-color: #0E5287;
    box-shadow:
        0 3.5px 0 #0E5287,
        inset 1px 1px 2px rgba(255, 255, 255, 0.7);
}

.btn-assign:active {
    box-shadow: 0 1px 0 #0E5287 !important;
}

/* ==========================================================================
   EMPTY STATE STAGE
   ========================================================================== */
.empty-classes-stage {
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F8F5EE 100%));
    border: 3.5px solid var(--game-border, #1D2440);
    border-radius: 32px;
    padding: 3rem 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow:
        0 10px 0 var(--game-border, #1D2440),
        -6px -6px 16px rgba(255, 255, 255, 0.9),
        8px 18px 36px rgba(29, 36, 64, 0.15);
    margin-top: 1rem;
}

.empty-diorama-frame {
    width: 240px;
    height: 165px;
    margin-bottom: 1.25rem;
    filter: drop-shadow(0 12px 20px rgba(0, 0, 0, 0.12));
}

.empty-castle-svg {
    width: 100%;
    height: 100%;
    border-radius: 20px;
    border: 2.5px solid var(--game-border, #1D2440);
}

.empty-kicker {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #4F46E5;
}

.empty-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.8rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0.25rem 0 0.5rem 0;
}

.empty-desc {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.95rem;
    font-weight: 600;
    color: #556080;
    max-width: 520px;
    margin: 0 0 1.5rem 0;
    line-height: 1.5;
}

.btn-summon-lg {
    padding: 0.85rem 1.85rem;
    font-size: 0.95rem;
}

/* ==========================================================================
   PIXAR STORYBOOK MODALS & TOAST
   ========================================================================== */
.pixar-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(29, 36, 64, 0.6);
    backdrop-filter: blur(12px) saturate(1.4);
    -webkit-backdrop-filter: blur(12px) saturate(1.4);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    box-sizing: border-box;
    overflow-y: auto;
}

.pixar-modal-shell {
    position: relative;
    width: 100%;
    max-width: 560px;
    background: linear-gradient(150deg, #FFFFFF 0%, #FFFDF9 50%, #F5EFE3 100%);
    border: 3.5px solid var(--game-border, #1D2440);
    border-radius: 32px;
    padding: 2.75rem 2rem 2rem 2rem;
    box-shadow:
        0 16px 0 var(--game-border, #1D2440),
        0 30px 55px rgba(29, 36, 64, 0.35),
        inset 3px 3px 6px rgba(255, 255, 255, 0.95),
        inset -3px -3px 6px rgba(194, 185, 166, 0.35);
    box-sizing: border-box;
    max-height: 90vh;
    overflow-y: auto;
}

.pixar-modal__crest {
    position: absolute;
    top: -38px;
    left: 50%;
    transform: translateX(-50%);
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #FFEA85 0%, #FFB703 55%, #D97706 100%);
    border: 3.5px solid #1D2440;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 8px 18px rgba(217, 119, 6, 0.45),
        0 4px 0 #1D2440,
        inset 3px 3px 6px rgba(255, 255, 255, 0.95),
        inset -3px -3px 6px rgba(180, 83, 9, 0.4);
    z-index: 10;
}

.crest-emoji {
    font-size: 2.2rem;
    line-height: 1;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.crest-sparkle {
    position: absolute;
    top: 2px;
    right: 4px;
    font-size: 1.1rem;
    animation: sparklePulse 2s ease-in-out infinite alternate;
}

.pixar-modal__close {
    position: absolute;
    top: 1rem;
    right: 1.25rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #F1F4FA;
    border: 2px solid #CBD5E1;
    color: #64748B;
    font-size: 1.1rem;
    font-weight: 900;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 0 #94A3B8, inset 1px 1px 2px rgba(255, 255, 255, 0.9);
    transition: transform 0.15s var(--spring-bounce), background 0.15s ease;
}

.pixar-modal__close:hover {
    transform: scale(1.1);
    background: #FFFFFF;
    color: #0F172A;
}

.dialog-header {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 1.25rem;
}

.modal-kicker {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #4F46E5;
    background: #EEF2FF;
    border: 1.5px solid #A5B4FC;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    box-shadow: 0 2px 0 #818CF8;
}

.modal-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.55rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0;
    line-height: 1.2;
}

.modal-desc {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.88rem;
    font-weight: 600;
    color: #556080;
    margin: 0;
    line-height: 1.45;
}

.modal-form-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.85rem;
}

.form-label {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.82rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
}

.clay-recessed-input {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--game-navy, #1D2440);
    background: #FFFFFF;
    border: 2.5px solid #CBD5E1;
    border-radius: 14px;
    padding: 0.65rem 0.95rem;
    outline: none;
    box-shadow:
        inset 2px 3px 5px rgba(29, 36, 64, 0.12),
        inset -2px -2px 3px rgba(255, 255, 255, 0.85);
    transition: all 0.15s ease;
}

.clay-recessed-input:focus {
    border-color: #38BDF8;
    box-shadow:
        0 0 0 3px rgba(56, 189, 248, 0.25),
        inset 1px 1px 3px rgba(29, 36, 64, 0.08);
}

.field-error {
    font-family: var(--font-ui);
    font-size: 0.75rem;
    font-weight: 700;
    color: #DC2626;
    margin: 0.15rem 0 0 0;
}

/* Mascot Button Strip */
.icon-selector-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.toy-mascot-btn {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(145deg, #FFFFFF 0%, #EEF2FA 100%);
    border: 2px solid var(--game-border, #1D2440);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow:
        0 3px 0 var(--game-border, #1D2440),
        inset 1px 1px 2px rgba(255, 255, 255, 0.9);
    transition: transform 0.15s var(--spring-bounce), background 0.15s ease;
}

.toy-mascot-btn:hover {
    transform: translateY(-2px) scale(1.08);
}

.toy-mascot-btn.selected {
    background: linear-gradient(145deg, #FFEA85 0%, #FFA800 100%);
    border-color: #8A6400;
    box-shadow:
        0 4px 0 #8A6400,
        0 0 12px rgba(255, 183, 3, 0.5),
        inset 2px 2px 3px rgba(255, 255, 255, 0.9);
    transform: translateY(-3px) scale(1.12);
}

/* Theme Selector Grid */
.theme-selector-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(115px, 1fr));
    gap: 0.65rem;
}

.theme-opt-card {
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 16px;
    padding: 0.65rem 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
    box-shadow: 0 3px 0 #94A3B8;
    transition: all 0.15s var(--spring-bounce);
}

.theme-opt-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #94A3B8;
}

.theme-opt-card.selected {
    border-color: #4F46E5;
    background: #EEF2FF;
    box-shadow:
        0 4px 0 #3730A3,
        0 0 12px rgba(99, 102, 241, 0.35);
    transform: translateY(-3px);
}

.theme-opt-icon {
    font-size: 1.5rem;
}

.theme-opt-label {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--game-navy, #1D2440);
    text-align: center;
}

/* Modules Checkbox List */
.modules-assign-check-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    max-height: 150px;
    overflow-y: auto;
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 14px;
    padding: 0.55rem;
    box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.08);
}

.module-check-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.35rem 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.1s ease;
}

.module-check-item:hover {
    background: #F1F5F9;
}

.check-mod-icon {
    font-size: 1.2rem;
}

.check-mod-title {
    font-family: var(--font-ui);
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--game-navy, #1D2440);
}

/* Modal Form Action Buttons */
.modal-form-actions {
    display: flex;
    gap: 0.85rem;
    margin-top: 0.65rem;
}

.pixar-btn {
    flex: 1;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    font-size: 0.92rem;
    letter-spacing: 0.03em;
    border-radius: 9999px;
    border: 2.5px solid var(--game-border, #1D2440);
    padding: 0.75rem 1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s var(--spring-bounce);
    user-select: none;
}

.pixar-btn--cancel {
    background: linear-gradient(145deg, #FFFFFF 0%, #E9EDF7 100%);
    color: #334155;
    box-shadow:
        0 4px 0 var(--game-border, #1D2440),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
}

.pixar-btn--cancel:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 var(--game-border, #1D2440);
}

.pixar-btn--cancel:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 var(--game-border, #1D2440);
}

.pixar-btn--submit {
    background: linear-gradient(145deg, #38A3F5 0%, #1677C2 100%);
    color: #FFFFFF;
    border-color: #0E5287;
    box-shadow:
        0 4px 0 #0E5287,
        0 8px 16px rgba(35, 149, 235, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.75);
}

.pixar-btn--submit:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #0E5287,
        0 12px 22px rgba(35, 149, 235, 0.45);
}

.pixar-btn--submit:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #0E5287;
}

/* Assign Modules Grid */
.assign-modules-grid {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    max-height: 280px;
    overflow-y: auto;
    padding-right: 0.25rem;
}

.assign-module-row {
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    border-radius: 16px;
    padding: 0.65rem 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    box-shadow: 0 2px 0 #94A3B8;
}

.assign-mod-meta {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.assign-mod-icon {
    font-size: 1.5rem;
}

.assign-mod-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.92rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    display: block;
}

.assign-mod-grade {
    font-family: var(--font-ui);
    font-size: 0.72rem;
    color: #64748B;
    font-weight: 700;
}

.btn-assign-action {
    background: linear-gradient(145deg, #10B981 0%, #059669 100%);
    color: #FFFFFF;
    border-color: #064E3B;
    box-shadow: 0 3px 0 #064E3B;
    padding: 0.45rem 0.95rem;
}

.btn-assign-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #064E3B;
}

.btn-assign-action:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #064E3B;
}

.empty-assign-hint {
    font-family: var(--font-ui);
    font-size: 0.88rem;
    color: #64748B;
    text-align: center;
    padding: 1.5rem 0;
}

/* Toast Slide */
.action-notice-toast {
    position: fixed;
    top: 1.5rem;
    right: 2rem;
    z-index: 10000;
    background: linear-gradient(145deg, #FEF9C3 0%, #FDE047 100%);
    border: 2.5px solid #B45309;
    border-radius: 9999px;
    padding: 0.65rem 1.35rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    box-shadow: 0 8px 20px rgba(180, 83, 9, 0.35), 0 3px 0 #92400E;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    font-size: 0.88rem;
    color: #451A03;
}

.toast-slide-enter-active {
    animation: pxToastSlide 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.toast-slide-leave-active {
    animation: pxToastSlide 0.25s ease reverse both;
}

@keyframes pxToastSlide {
    0% { transform: translateY(-30px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

/* Pixar Modal Spring */
.pixar-modal-spring-enter-active {
    animation: pxModalSpring 0.38s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.pixar-modal-spring-leave-active {
    animation: pxModalSpring 0.22s cubic-bezier(0.34, 1.56, 0.64, 1) reverse both;
}

@keyframes pxModalSpring {
    0% { opacity: 0; transform: scale(0.68) translateY(40px); }
    60% { opacity: 1; transform: scale(1.035) translateY(-6px); }
    85% { transform: scale(0.985) translateY(2px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes sparklePulse {
    from { transform: scale(0.8) translateY(0); opacity: 0.2; }
    to { transform: scale(1.2) translateY(-6px); opacity: 0.7; }
}

@media (max-width: 640px) {
    .classrooms-grid {
        grid-template-columns: 1fr;
    }
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
</style>
