<script setup>
import { router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { flushAllClientCache, prefetchRoute } from '@/Composables/usePrefetch';
import { useSound } from '@/Services/soundManager';

const { playClick, playPop, playBoing } = useSound();

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
    activeNav: {
        type: String,
        default: 'modules', // 'modules' | 'classes' | 'students' | 'grading'
    },
    pendingGradingCount: {
        type: Number,
        default: 0,
    },
    studentCount: {
        type: Number,
        default: 118,
    },
});

const isLogoutModalOpen = ref(false);

const prefetchTeacherRoute = (url, tags = ['teacher-portal']) => {
    prefetchRoute(url, tags, ['15s', '60s']);
};

const openLogoutModal = () => {
    playBoing(0.95);
    isLogoutModalOpen.value = true;
};

const closeLogoutModal = () => {
    playPop(1.15);
    isLogoutModalOpen.value = false;
};

const confirmLogout = () => {
    playClick();
    flushAllClientCache();
    router.post('/logout');
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isLogoutModalOpen.value) {
        closeLogoutModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

const navigateTo = (routeUrl, navKey) => {
    if (props.activeNav === navKey) return;
    playPop(1.1);
    router.visit(routeUrl);
};
</script>

<template>
    <div class="game-dashboard-page">
        <!-- Floating 3D Pop-Up Book Background Assets -->
        <div class="game-bg-elements" aria-hidden="true">
            <img src="/images/book_left_trees_mountains.png" alt="" width="965" height="777" decoding="async" class="bg-asset bg-book-left" />
            <img src="/images/book_right_mountains_river.png" alt="" width="996" height="640" decoding="async" class="bg-asset bg-book-right" />
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">⭐</div>
            <div class="floating-sparkle sp-3">✨</div>
            <div class="floating-sparkle sp-4">🌟</div>
        </div>

        <!-- 3D CARTOON GAME HUD (TOP STATUS BAR) -->
        <header class="game-hud">
            <!-- Player Status / Level Badge -->
            <div class="hud-profile-card">
                <div class="hud-avatar-wrapper">
                    <div class="avatar-ring">
                        <span class="avatar-emoji">🧙‍♂️</span>
                    </div>
                    <span class="avatar-lvl-badge">LVL 42</span>
                </div>

                <div class="hud-profile-meta">
                    <div class="hud-name-row">
                        <span class="hud-title-crown">👑</span>
                        <span class="hud-name">{{ user.name }}</span>
                        <span class="instructor-tag">MASTER INSTRUCTOR</span>
                    </div>

                    <!-- XP Meter Bar -->
                    <div class="hud-xp-meter">
                        <div class="xp-track">
                            <div class="xp-fill" style="width: 84%">
                                <div class="xp-shine"></div>
                            </div>
                        </div>
                        <span class="xp-label">8,450 / 10,000 XP</span>
                    </div>
                </div>
            </div>

            <!-- Game Resource Indicators -->
            <div class="hud-inventory-meters">
                <div class="inventory-pill gems-pill" title="Classroom Reading Gems">
                    <span class="pill-icon gem-icon">💎</span>
                    <div class="pill-data">
                        <span class="pill-num">1,450</span>
                        <span class="pill-lbl">GEMS</span>
                    </div>
                </div>

                <div class="inventory-pill stars-pill" title="Guild Star Power">
                    <span class="pill-icon star-icon">⭐</span>
                    <div class="pill-data">
                        <span class="pill-num">3,820</span>
                        <span class="pill-lbl">STARS</span>
                    </div>
                </div>

                <div class="inventory-pill energy-pill" title="Classroom Energy">
                    <span class="pill-icon energy-icon">⚡</span>
                    <div class="pill-data">
                        <span class="pill-num">95%</span>
                        <span class="pill-lbl">ENERGY</span>
                    </div>
                </div>

                <div
                    class="inventory-pill guild-pill"
                    :class="{ 'is-active': activeNav === 'students' }"
                    title="Active Guild Members"
                    @mouseenter="prefetchTeacherRoute('/teachers/students', ['teacher-students'])"
                    @click="navigateTo('/teachers/students', 'students')"
                    style="cursor: pointer;"
                >
                    <span class="pill-icon guild-icon">🎒</span>
                    <div class="pill-data">
                        <span class="pill-num">{{ studentCount }}</span>
                        <span class="pill-lbl">STUDENTS</span>
                    </div>
                </div>

                <div
                    class="inventory-pill grading-pill"
                    :class="{ 'has-pending': pendingGradingCount > 0, 'is-active': activeNav === 'grading' }"
                    title="Grade Student Submissions"
                    @mouseenter="prefetchTeacherRoute('/teachers/grading', ['teacher-grading'])"
                    @click="navigateTo('/teachers/grading', 'grading')"
                    style="cursor: pointer;"
                >
                    <span class="pill-icon grading-icon">📝</span>
                    <div class="pill-data">
                        <span class="pill-num">{{ pendingGradingCount }}</span>
                        <span class="pill-lbl">GRADING</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN STAGE WRAPPER -->
        <main class="game-stage">
            <slot />
        </main>

        <!-- EXTRA / MODALS / TOASTS SLOT -->
        <slot name="modals" />

        <!-- 3D CARTOON VERTICAL NAVIGATION RAIL (FLOATING LEFT) -->
        <nav class="game-vertical-dock" aria-label="Game Console Navigation Rail">
            <!-- Studio Emblem -->
            <!-- <div class="dock-brand-emblem" title="WEBREAD Master Studio" aria-hidden="true">
                <span class="emblem-sparkle">🏰</span>
            </div> -->

            <!-- Dock Item: Modules -->
            <button
                type="button"
                class="dock-item"
                :class="{ active: activeNav === 'modules' }"
                @mouseenter="prefetchTeacherRoute('/teachers/dashboard', ['teacher-modules'])"
                @click="navigateTo('/teachers/dashboard', 'modules')"
                title="Your Modules"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏰</span>
                </div>
                <span class="dock-label">MODULES</span>
            </button>

            <!-- Dock Item: Classrooms -->
            <button
                type="button"
                class="dock-item"
                :class="{ active: activeNav === 'classes' }"
                @mouseenter="prefetchTeacherRoute('/teachers/classes', ['teacher-classes'])"
                @click="navigateTo('/teachers/classes', 'classes')"
                title="Classrooms & Rooms"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏫</span>
                </div>
                <span class="dock-label">CLASSES</span>
            </button>

            <!-- Dock Item: Students -->
            <button
                type="button"
                class="dock-item"
                :class="{ active: activeNav === 'students' }"
                @mouseenter="prefetchTeacherRoute('/teachers/students', ['teacher-students'])"
                @click="navigateTo('/teachers/students', 'students')"
                title="Student Leaderboard & Rankings"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏆</span>
                </div>
                <span class="dock-label">STUDENTS</span>
            </button>

            <div class="dock-divider" aria-hidden="true"></div>

            <!-- Dock Item: Log Out (Opens Pixar Graphic Confirmation Modal) -->
            <button
                type="button"
                class="dock-item logout-item"
                @click="openLogoutModal"
                title="Exit Portal"
            >
                <div class="dock-bubble exit-bubble">
                    <span class="dock-icon">🚪</span>
                </div>
                <span class="dock-label">EXIT</span>
            </button>
        </nav>

        <!-- PIXAR GRAPHICS LOGOUT CONFIRMATION MODAL -->
        <teleport to="body">
            <transition name="pixar-modal-spring">
                <div
                    v-if="isLogoutModalOpen"
                    class="pixar-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="logout-modal-title"
                    @click.self="closeLogoutModal"
                >
                    <div class="pixar-modal-shell">
                        <!-- 3D Overlapping Floating Crest -->
                        <div class="pixar-modal__crest" aria-hidden="true">
                            <span class="crest-emoji">🚪</span>
                            <span class="crest-sparkle">✨</span>
                        </div>

                        <!-- Top-Right Close Button -->
                        <button
                            type="button"
                            class="pixar-modal__close"
                            aria-label="Close dialog"
                            @click="closeLogoutModal"
                        >
                            ✕
                        </button>

                        <div class="modal-content-stack">
                            <span class="modal-kicker">STUDIO LOGOUT</span>
                            <h2 id="logout-modal-title" class="modal-title">Ready to Leave the Realm?</h2>
                            <p class="modal-desc">
                                Your classroom worlds, student progress, and grading milestones are safely sealed inside the Master Instructor Vault.
                            </p>

                            <!-- Mini Vault Status Badge -->
                            <div class="modal-vault-status">
                                <span class="vault-icon">🏰</span>
                                <div class="vault-info">
                                    <strong class="vault-title">Instructor Vault Synchronized</strong>
                                    <span class="vault-sub">All reading quests are saved & active for students</span>
                                </div>
                                <span class="vault-check">✅</span>
                            </div>

                            <!-- Tactile Pixar Button Row -->
                            <div class="modal-actions-row">
                                <button
                                    type="button"
                                    class="pixar-btn pixar-btn--stay"
                                    @click="closeLogoutModal"
                                >
                                    <span>Stay in Studio</span>
                                </button>
                                <button
                                    type="button"
                                    class="pixar-btn pixar-btn--exit"
                                    @click="confirmLogout"
                                >
                                    <span>Yes, Sign Out ➔</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<style scoped>
/* ==========================================================================
   CARTOON 3D + NEUMORPHISM GAME TOKENS & RESET
   ========================================================================== */
.game-dashboard-page {
    --game-navy: #1D2440;
    --game-border: #2B3354;
    --game-blue: #2395EB;
    --game-blue-shadow: #1467A6;
    --game-coral: #FF6B4A;
    --game-coral-shadow: #BF3E22;
    --game-gold: #FFC000;
    --game-gold-shadow: #B88500;
    --game-green: #06D6A0;
    --game-green-shadow: #038F6A;
    --game-purple: #845EC2;
    --game-purple-shadow: #58398A;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-game: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;

    /* Neumorphic Tactile Lighting Tokens */
    --neu-bg-base: #F5F0E3;
    --neu-surface: linear-gradient(145deg, #FFFFFF 0%, #F3EFE4 100%);
    --neu-light: rgba(255, 255, 255, 0.92);
    --neu-shadow: rgba(196, 187, 168, 0.55);
    --neu-navy-ambient: rgba(29, 36, 64, 0.12);
    --neu-bevel-light: inset 3px 3px 6px rgba(255, 255, 255, 0.95);
    --neu-bevel-dark: inset -4px -4px 8px rgba(194, 185, 166, 0.38);
    --neu-well-inset:
        inset 4px 4px 8px rgba(176, 184, 206, 0.48),
        inset -4px -4px 8px rgba(255, 255, 255, 0.95);

    min-height: 100vh;
    background-color: var(--neu-bg-base);
    background-image:
        radial-gradient(circle at 20% 15%, rgba(255, 255, 255, 0.7) 0%, transparent 45%),
        radial-gradient(circle at 85% 80%, rgba(226, 217, 197, 0.6) 0%, transparent 50%),
        radial-gradient(#DFD7C4 2px, transparent 2px),
        radial-gradient(#DFD7C4 2px, var(--neu-bg-base) 2px);
    background-size: 100% 100%, 100% 100%, 36px 36px, 36px 36px;
    background-position: 0 0, 0 0, 0 0, 18px 18px;
    font-family: var(--font-game);
    color: var(--game-navy);
    position: relative;
    overflow-x: clip;
    box-sizing: border-box;
}

/* Offset page content for the left floating vertical rail in desktop view */
@media (min-width: 901px) {
    .game-dashboard-page {
        padding-left: clamp(5.75rem, 6.8vw, 7.8rem);
        padding-bottom: 3rem;
    }
}

@media (max-width: 900px) {
    .game-dashboard-page {
        padding-bottom: 7rem;
    }
}

/* Soft Matte Neumorphic Grain Texture Overlay */
.game-dashboard-page::before {
    content: '';
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 2;
    opacity: 0.035;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}

/* 3D Floating Scenery Assets */
.game-bg-elements {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 1;
}

.bg-asset {
    position: absolute;
    width: 28vw;
    max-width: 380px;
    opacity: 0.28;
    filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.08));
}

.bg-book-left {
    bottom: 5%;
    left: -4%;
    animation: floatIslandLeft 7s ease-in-out infinite alternate;
}

.bg-book-right {
    top: 15%;
    right: -4%;
    animation: floatIslandRight 8s ease-in-out infinite alternate;
}

@keyframes floatIslandLeft {
    from { transform: translateY(0) rotate(-1deg); }
    to { transform: translateY(-16px) rotate(1deg); }
}

@keyframes floatIslandRight {
    from { transform: translateY(0) rotate(1deg); }
    to { transform: translateY(-18px) rotate(-1.5deg); }
}

.floating-sparkle {
    position: absolute;
    font-size: 1.5rem;
    opacity: 0.45;
    animation: sparklePulse 3s ease-in-out infinite alternate;
}

.sp-1 { top: 12%; left: 18%; animation-delay: 0.2s; }
.sp-2 { top: 22%; right: 24%; animation-delay: 0.9s; }
.sp-3 { bottom: 25%; left: 32%; animation-delay: 1.5s; }
.sp-4 { bottom: 35%; right: 15%; animation-delay: 2.1s; }

@keyframes sparklePulse {
    from { transform: scale(0.8) translateY(0); opacity: 0.2; }
    to { transform: scale(1.2) translateY(-8px); opacity: 0.7; }
}

/* ==========================================================================
   GAME HUD (TOP STATUS ROW)
   ========================================================================== */
.game-hud {
    position: relative;
    z-index: 10;
    padding: 1.25rem clamp(1.25rem, 3vw, 3rem);
    width: 100%;
    max-width: none;
    margin: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    box-sizing: border-box;
}

.hud-profile-card {
    background: var(--neu-surface);
    border: 3px solid var(--game-border);
    border-radius: 26px;
    padding: 0.7rem 1.45rem 0.7rem 0.9rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow:
        0 6px 0 var(--game-border),
        -6px -6px 14px var(--neu-light),
        8px 12px 22px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
}

.hud-avatar-wrapper {
    position: relative;
}

.avatar-ring {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(145deg, #FFEA85 0%, #FFA800 100%);
    border: 3px solid #1D2440;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 3px 0 #1D2440,
        inset 3px 3px 6px rgba(255, 255, 255, 0.85),
        inset -3px -3px 6px rgba(184, 120, 0, 0.45);
}

.avatar-emoji {
    font-size: 1.9rem;
    line-height: 1;
    filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.15));
}

.avatar-lvl-badge {
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(145deg, #FF7E62 0%, #EB4D2C 100%);
    color: #FFFFFF;
    font-size: 0.65rem;
    font-weight: 900;
    padding: 0.1rem 0.48rem;
    border-radius: 9999px;
    border: 2px solid #1D2440;
    white-space: nowrap;
    box-shadow:
        0 2px 0 #1D2440,
        inset 1px 1px 2px rgba(255, 255, 255, 0.6);
}

.hud-profile-meta {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.hud-name-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.hud-title-crown {
    font-size: 1.1rem;
    filter: drop-shadow(0 2px 2px rgba(255, 192, 0, 0.35));
}

.hud-name {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--game-navy);
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.8);
}

.instructor-tag {
    font-size: 0.65rem;
    font-weight: 900;
    background: linear-gradient(145deg, #F0F8FF 0%, #D9EEFD 100%);
    color: #1467A6;
    border: 1.5px solid #2395EB;
    padding: 0.15rem 0.55rem;
    border-radius: 8px;
    letter-spacing: 0.04em;
    box-shadow:
        inset 1px 1px 2px rgba(255, 255, 255, 0.9),
        inset -1px -1px 2px rgba(35, 149, 235, 0.18),
        0 2px 0 rgba(35, 149, 235, 0.25);
}

.hud-xp-meter {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

/* Neumorphic Recessed XP Track */
.xp-track {
    width: 145px;
    height: 14px;
    background: #E2E6F0;
    border: 2px solid #1D2440;
    border-radius: 9999px;
    overflow: hidden;
    position: relative;
    box-shadow:
        inset 2px 3px 5px rgba(29, 36, 64, 0.25),
        inset -2px -2px 4px rgba(255, 255, 255, 0.85),
        0 1px 0 rgba(255, 255, 255, 0.9);
}

.xp-fill {
    height: 100%;
    background: linear-gradient(90deg, #06D6A0 0%, #2395EB 100%);
    border-radius: 9999px;
    position: relative;
    box-shadow:
        inset 0 2px 3px rgba(255, 255, 255, 0.65),
        inset 0 -2px 3px rgba(0, 0, 0, 0.2);
}

.xp-shine {
    position: absolute;
    top: 2px;
    left: 4px;
    right: 4px;
    height: 3px;
    background: rgba(255, 255, 255, 0.65);
    border-radius: 9999px;
}

.xp-label {
    font-size: 0.72rem;
    font-weight: 900;
    color: #6C7594;
}

.hud-inventory-meters {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex-wrap: wrap;
}

.inventory-pill {
    background: var(--neu-surface);
    border: 3px solid var(--game-border);
    border-radius: 22px;
    padding: 0.45rem 1.05rem 0.45rem 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    box-shadow:
        0 5px 0 var(--game-border),
        -4px -4px 10px var(--neu-light),
        6px 9px 16px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    transition: transform 0.18s var(--spring-bounce), box-shadow 0.18s var(--spring-bounce);
    user-select: none;
}

.inventory-pill:hover {
    transform: translateY(-3px);
    box-shadow:
        0 8px 0 var(--game-border),
        -5px -5px 12px var(--neu-light),
        8px 14px 20px rgba(29, 36, 64, 0.16),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
}

.inventory-pill.is-active {
    border-color: #2395EB;
    box-shadow:
        0 6px 0 #1467A6,
        0 0 12px rgba(35, 149, 235, 0.35),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
}

.inventory-pill:active {
    transform: translateY(2px);
    box-shadow:
        0 2px 0 var(--game-border),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
}

.pill-icon {
    font-size: 1.35rem;
    line-height: 1;
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #EFF2FA;
    box-shadow:
        inset 2px 2px 5px rgba(163, 174, 198, 0.55),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
    border: 1.5px solid rgba(29, 36, 64, 0.12);
}

.gems-pill .pill-icon {
    background: linear-gradient(145deg, #E8F6FF, #F5FBFF);
    box-shadow:
        inset 2px 2px 5px rgba(35, 149, 235, 0.22),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.stars-pill .pill-icon {
    background: linear-gradient(145deg, #FFF7DF, #FFFDF5);
    box-shadow:
        inset 2px 2px 5px rgba(255, 183, 3, 0.28),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.energy-pill .pill-icon {
    background: linear-gradient(145deg, #E6FBF5, #F4FFFC);
    box-shadow:
        inset 2px 2px 5px rgba(6, 214, 160, 0.25),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.guild-pill .pill-icon {
    background: linear-gradient(145deg, #F3EDFC, #FAF7FF);
    box-shadow:
        inset 2px 2px 5px rgba(132, 94, 194, 0.25),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.grading-pill .pill-icon {
    background: linear-gradient(145deg, #FFF0EC, #FFF7F5);
    box-shadow:
        inset 2px 2px 5px rgba(255, 107, 74, 0.25),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.grading-pill.has-pending {
    border-color: #FF6B4A;
    animation: gentlePulse 2.4s infinite;
}

@keyframes gentlePulse {
    0%, 100% {
        box-shadow:
            0 5px 0 var(--game-border),
            0 0 0 rgba(255, 107, 74, 0);
    }
    50% {
        box-shadow:
            0 5px 0 #BF3E22,
            0 0 14px rgba(255, 107, 74, 0.45);
    }
}

.pill-data {
    display: flex;
    flex-direction: column;
}

.pill-num {
    font-size: 1.15rem;
    font-weight: 900;
    line-height: 1;
    color: var(--game-navy);
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.9);
}

.pill-lbl {
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    color: #8C96B0;
    margin-top: 0.15rem;
}

/* ==========================================================================
   MAIN GAME STAGE
   ========================================================================== */
.game-stage {
    position: relative;
    z-index: 5;
    width: 100%;
    max-width: none;
    margin: 0.35rem 0 2rem 0;
    padding: 0 clamp(1.25rem, 3vw, 3rem);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    box-sizing: border-box;
}

/* ==========================================================================
   3D CARTOON VERTICAL NAVIGATION RAIL (FLOATING LEFT)
   ========================================================================== */
.game-vertical-dock {
    position: fixed;
    left: clamp(1rem, 1.8vw, 2.2rem);
    top: 50%;
    transform: translateY(-50%);
    z-index: 999;
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 42px;
    padding: 1.25rem 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.1rem;
    box-shadow:
        0 10px 0 var(--game-border),
        -6px -6px 16px rgba(255, 255, 255, 0.85),
        12px 20px 40px rgba(29, 36, 64, 0.22),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    user-select: none;
    transition: transform 0.25s var(--spring-bounce);
}

.dock-brand-emblem {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    background: linear-gradient(145deg, #FFEA85 0%, #FFA800 100%);
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.9),
        inset -2px -2px 4px rgba(184, 120, 0, 0.45);
    margin-bottom: 0.15rem;
}

.dock-item {
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.4rem;
    transition: transform 0.2s var(--spring-bounce);
}

.dock-item:hover {
    transform: translateX(4px) scale(1.05);
}

.dock-item:focus-visible {
    outline: 2px solid var(--game-blue);
    outline-offset: 4px;
    border-radius: 14px;
}

.dock-bubble {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    background: linear-gradient(145deg, #FFFFFF 0%, #E9EDF7 100%);
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.55rem;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 5px rgba(255, 255, 255, 0.95),
        inset -2px -2px 5px rgba(164, 176, 204, 0.45);
    position: relative;
    transition: all 0.2s var(--spring-bounce);
}

.dock-item.active .dock-bubble {
    background: linear-gradient(145deg, #38A3F5 0%, #1579C4 100%);
    border-color: #0E5287;
    box-shadow:
        0 4px 0 #0E5287,
        0 6px 14px rgba(35, 149, 235, 0.35),
        inset 2px 2px 5px rgba(255, 255, 255, 0.6),
        inset -2px -3px 6px rgba(14, 82, 135, 0.5);
    transform: translateX(4px) scale(1.08);
}

.dock-item.active .dock-bubble .dock-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.dock-label {
    font-family: var(--font-game);
    font-size: 0.68rem;
    font-weight: 900;
    color: #727B94;
    letter-spacing: 0.05em;
    transition: color 0.15s ease;
}

.dock-item.active .dock-label {
    color: var(--game-blue);
}

.dock-divider {
    width: 36px;
    height: 3px;
    background: #DFE4F2;
    border-radius: 9999px;
    box-shadow:
        inset 1px 1px 2px rgba(163, 174, 198, 0.6),
        0 1px 0 rgba(255, 255, 255, 0.9);
}

.logout-item:hover .exit-bubble {
    background: linear-gradient(145deg, #FFF5F5 0%, #FFE0E0 100%);
    border-color: #FF4D4F;
    box-shadow:
        0 3px 0 #CF1322,
        inset 2px 2px 4px rgba(255, 255, 255, 0.9),
        inset -2px -2px 4px rgba(255, 77, 79, 0.25);
}

.logout-item:hover .dock-label {
    color: #E03131;
}

/* ==========================================================================
   PIXAR GRAPHICS LOGOUT CONFIRMATION MODAL
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
}

.pixar-modal-shell {
    position: relative;
    width: 100%;
    max-width: 480px;
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
}

/* 3D Overlapping Floating Crest */
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

/* Top-Right Close Button */
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

.pixar-modal__close:active {
    transform: translateY(2px) scale(0.95);
    box-shadow: 0 1px 0 #94A3B8;
}

.modal-content-stack {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.85rem;
}

.modal-kicker {
    font-family: var(--font-game);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #EA580C;
    background: #FFF7ED;
    border: 1.5px solid #FDBA74;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    box-shadow: 0 2px 0 #F97316;
}

.modal-title {
    font-family: var(--font-game);
    font-size: 1.65rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0;
    line-height: 1.2;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.85);
}

.modal-desc {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.95rem;
    font-weight: 600;
    color: #556080;
    margin: 0;
    line-height: 1.5;
}

/* Vault Status Card */
.modal-vault-status {
    width: 100%;
    background: linear-gradient(145deg, #ECFDF5 0%, #F0FDF4 100%);
    border: 2px solid #86EFAC;
    border-radius: 18px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow:
        0 4px 0 #4ADE80,
        inset 1px 1px 2px rgba(255, 255, 255, 0.9);
    margin: 0.4rem 0;
    text-align: left;
    box-sizing: border-box;
}

.vault-icon {
    font-size: 1.6rem;
    flex-shrink: 0;
}

.vault-info {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    flex: 1;
}

.vault-title {
    font-family: var(--font-game);
    font-size: 0.88rem;
    font-weight: 900;
    color: #065F46;
}

.vault-sub {
    font-family: var(--font-ui);
    font-size: 0.76rem;
    color: #047857;
    font-weight: 600;
}

.vault-check {
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Modal Actions Buttons */
.modal-actions-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    width: 100%;
    margin-top: 0.5rem;
}

.pixar-btn {
    flex: 1;
    font-family: var(--font-game);
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
    box-sizing: border-box;
}

.pixar-btn--stay {
    background: linear-gradient(145deg, #FFFFFF 0%, #E9EDF7 100%);
    color: #334155;
    box-shadow:
        0 4px 0 var(--game-border, #1D2440),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(164, 176, 204, 0.4);
}

.pixar-btn--stay:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 var(--game-border, #1D2440),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
}

.pixar-btn--stay:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 var(--game-border, #1D2440);
}

.pixar-btn--exit {
    background: linear-gradient(145deg, #FF7B60 0%, #EB4D2C 50%, #C92A0D 100%);
    color: #FFFFFF;
    border-color: #8C1C07;
    box-shadow:
        0 4px 0 #8C1C07,
        0 8px 16px rgba(201, 42, 13, 0.35),
        inset 2px 2px 4px rgba(255, 255, 255, 0.65),
        inset -2px -2px 4px rgba(140, 28, 7, 0.35);
}

.pixar-btn--exit:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #8C1C07,
        0 12px 20px rgba(201, 42, 13, 0.45),
        inset 2px 2px 4px rgba(255, 255, 255, 0.75);
}

.pixar-btn--exit:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #8C1C07;
}

/* Modal Spring Animation */
.pixar-modal-spring-enter-active {
    animation: pxModalSpring 0.38s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.pixar-modal-spring-leave-active {
    animation: pxModalSpring 0.22s cubic-bezier(0.34, 1.56, 0.64, 1) reverse both;
}

@keyframes pxModalSpring {
    0% {
        opacity: 0;
        transform: scale(0.68) translateY(40px);
    }
    60% {
        opacity: 1;
        transform: scale(1.035) translateY(-6px);
    }
    85% {
        transform: scale(0.985) translateY(2px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* ==========================================================================
   RESPONSIVE DESIGN
   ========================================================================== */
@media (max-width: 900px) {
    .game-hud {
        flex-direction: column;
        align-items: stretch;
        padding: 1rem 1.25rem;
    }

    .hud-profile-card {
        justify-content: flex-start;
    }

    .hud-inventory-meters {
        justify-content: space-between;
    }

    /* In mobile/tablet, switch to bottom horizontal dock */
    .game-vertical-dock {
        top: auto;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        flex-direction: row;
        gap: 0.65rem;
        padding: 0.5rem 0.85rem;
        border-radius: 28px;
    }

    .dock-brand-emblem {
        display: none;
    }

    .dock-divider {
        width: 3px;
        height: 32px;
    }

    .dock-bubble {
        width: 44px;
        height: 44px;
        font-size: 1.35rem;
    }

    .dock-label {
        font-size: 0.62rem;
    }

    .dock-item:hover {
        transform: translateY(-3px);
    }

    .dock-item.active .dock-bubble {
        transform: translateY(-3px) scale(1.06);
    }

    .bg-asset {
        display: none;
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
