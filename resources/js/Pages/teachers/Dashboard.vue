<script setup>
import { Head, router } from '@inertiajs/vue3';

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
});

const handleLogout = () => {
    router.post('/logout');
};

const goToStudents = () => {
    router.visit('/teachers/students');
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

    <div class="game-dashboard-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Floating 3D Pop-Up Book Background Assets -->
        <div class="game-bg-elements" aria-hidden="true">
            <img src="/images/book_left_trees_mountains.png" alt="" class="bg-asset bg-book-left" />
            <img src="/images/book_right_mountains_river.png" alt="" class="bg-asset bg-book-right" />
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

                <div class="inventory-pill guild-pill" title="Active Guild Members" @click="goToStudents" style="cursor: pointer;">
                    <span class="pill-icon guild-icon">🎒</span>
                    <div class="pill-data">
                        <span class="pill-num">118</span>
                        <span class="pill-lbl">STUDENTS</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN GAME ARENA CONTAINER -->
        <main class="game-stage">
            <!-- YOUR MODULES -->
            <section class="tab-panel world-tab">
                <div class="panel-header-row">
                    <div>
                        <h1 class="panel-title">🏰 Your Modules</h1>
                        <p class="panel-sub">Select a module to manage lectures, multimedia lessons, reading quests, and milestones.</p>
                    </div>
                    <div class="header-buttons">
                        <button type="button" class="btn-3d btn-ranking-action" @click="goToStudents">
                            <span>🏆 VIEW STUDENT RANKINGS</span>
                        </button>
                        <button type="button" class="btn-3d btn-primary-action" @click="goToCreateModule">
                            <span>✨ + ADD NEW MODULE</span>
                        </button>
                    </div>
                </div>

                <div v-if="modules.length > 0" class="kingdoms-grid">
                    <div
                        v-for="mod in modules"
                        :key="mod.id"
                        class="kingdom-card"
                        :class="`theme-${mod.theme}`"
                    >
                        <!-- Optional Module Cover Banner -->
                        <div v-if="mod.cover_image_url" class="module-card-banner-wrap">
                            <img :src="mod.cover_image_url" :alt="mod.title" class="module-card-banner-img" />
                        </div>

                        <!-- Module Top Badge -->
                        <div class="kingdom-top">
                            <div class="realm-avatar-badge">
                                <span class="realm-emoji">{{ mod.icon }}</span>
                            </div>
                            <div class="realm-badges-wrap">
                                <span v-if="mod.is_new" class="realm-new-pill">
                                    ✨ NEW
                                </span>
                                <div class="realm-status-pill" :class="{ 'boss-alert': mod.status === 'draft' }">
                                    {{ mod.status_label }}
                                </div>
                            </div>
                        </div>

                        <!-- Module Info -->
                        <h3 class="kingdom-title">{{ mod.title }}</h3>
                        <p class="kingdom-grade">{{ mod.grade_level }}</p>

                        <!-- Module Stats Block -->
                        <div class="kingdom-stats-box">
                            <div class="stat-item">
                                <span class="stat-label">Lectures</span>
                                <span class="stat-value">📖 {{ mod.lectures_count }} Lessons</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Completion XP</span>
                                <span class="stat-value buff-value">⚡ +{{ mod.xp_bonus }} XP</span>
                            </div>
                        </div>

                        <!-- Capacity Info -->
                        <div class="realm-progress-wrap">
                            <div class="progress-labels">
                                <span>Max Students</span>
                                <span class="progress-pct">🎒 {{ mod.max_students }} Seats</span>
                            </div>
                            <div class="realm-track">
                                <div class="realm-fill" style="width: 100%"></div>
                            </div>
                        </div>

                        <!-- Boss Encounter Preview -->
                        <div class="boss-preview-box">
                            <span class="boss-icon">👹</span>
                            <span class="boss-text">Boss: <strong>{{ mod.boss_name }}</strong></span>
                        </div>

                        <!-- Card Action -->
                        <button
                            type="button"
                            class="btn-3d btn-enter-realm"
                            @click="openModule(mod)"
                        >
                            <span>OPEN MODULE</span>
                            <span class="arrow-icon">➔</span>
                        </button>
                    </div>
                </div>

                <div v-else class="empty-modules-card">
                    <div class="empty-module-badge">🏰</div>
                    <h3 class="empty-module-title">No Modules Created Yet</h3>
                    <p class="empty-module-desc">
                        Summon your first learning module to start adding lectures, rich text lessons, images, audio narrations, and videos!
                    </p>
                    <button type="button" class="btn-3d btn-primary-action" @click="goToCreateModule">
                        <span>✨ + CREATE YOUR FIRST MODULE</span>
                    </button>
                </div>
            </section>
        </main>

        <!-- 3D CARTOON BOTTOM GAME NAVIGATION DOCK -->
        <nav class="game-bottom-dock" aria-label="Game Console Dock">
            <!-- Dock Item: Your Modules (Active) -->
            <button
                type="button"
                class="dock-item active"
                title="Your Modules"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏰</span>
                </div>
                <span class="dock-label">MODULES</span>
            </button>

            <!-- Dock Item: Students Ranking -->
            <button
                type="button"
                class="dock-item"
                @click="goToStudents"
                title="Student Leaderboard & Rankings"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏆</span>
                </div>
                <span class="dock-label">STUDENTS</span>
            </button>

            <div class="dock-divider"></div>

            <!-- Dock Item: Log Out -->
            <button
                type="button"
                class="dock-item logout-item"
                @click="handleLogout"
                title="Exit Game"
            >
                <div class="dock-bubble exit-bubble">
                    <span class="dock-icon">🚪</span>
                </div>
                <span class="dock-label">EXIT</span>
            </button>
        </nav>
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
    padding-bottom: 7.5rem;
    overflow-x: hidden;
    box-sizing: border-box;
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

/* 3D + Neumorphic Tactile Button Base */
.btn-3d {
    font-family: var(--font-game);
    font-weight: 900;
    letter-spacing: 0.04em;
    border-radius: 9999px;
    border: 2.5px solid #1D2440;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s var(--spring-bounce), filter 0.14s ease;
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

/* ==========================================================================
   GAME HUD (TOP STATUS ROW)
   ========================================================================== */
.game-hud {
    position: relative;
    z-index: 10;
    padding: 1.25rem 2rem;
    max-width: 1300px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
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

/* Color-themed Neumorphic Icon Wells inside HUD Pills */
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
   MAIN GAME STAGE & KINGDOMS
   ========================================================================== */
.game-stage {
    position: relative;
    z-index: 5;
    max-width: 1320px;
    margin: 0.35rem auto 2rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.panel-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.15rem;
    flex-wrap: wrap;
    gap: 0.85rem;
    background: var(--neu-surface);
    border: 3px solid var(--game-border);
    border-radius: 22px;
    padding: 0.95rem 1.4rem;
    box-shadow:
        0 5px 0 var(--game-border),
        -5px -5px 12px var(--neu-light),
        8px 12px 22px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
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

/* Kingdoms Grid: Fixed 2 Modules Per Row */
.kingdoms-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem;
}

/* Molded Neumorphic 3D Cartoon Kingdom Card (Compact) */
.kingdom-card {
    --realm-accent: #2395EB;
    --realm-accent-dark: #1467A6;
    --realm-surface-tint: #F4F7FC;
    --realm-well-bg: #EEF3FA;
    --realm-well-shadow: rgba(164, 179, 204, 0.45);

    background: linear-gradient(155deg, #FFFFFF 0%, var(--realm-surface-tint) 100%);
    border: 3px solid var(--game-border);
    border-top-width: 5px;
    border-radius: 22px;
    padding: 1.15rem 1.3rem;
    display: flex;
    flex-direction: column;
    box-shadow:
        0 6px 0 var(--game-border),
        -5px -5px 12px var(--neu-light),
        8px 12px 22px rgba(29, 36, 64, 0.12),
        inset 2px 2px 5px rgba(255, 255, 255, 0.95),
        inset -3px -3px 7px rgba(175, 185, 208, 0.32);
    transition: transform 0.22s var(--spring-bounce), box-shadow 0.22s var(--spring-bounce);
    position: relative;
}

.kingdom-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 9px 0 var(--game-border),
        -6px -6px 14px var(--neu-light),
        10px 16px 26px rgba(29, 36, 64, 0.16),
        inset 2px 2px 5px rgba(255, 255, 255, 0.98),
        inset -3px -3px 7px rgba(175, 185, 208, 0.38);
}

/* Color-Themed Biome Neumorphic Palettes */
.theme-forest {
    --realm-accent: #06D6A0;
    --realm-accent-dark: #038F6A;
    --realm-surface-tint: #F0FBF7;
    --realm-well-bg: #E7F7F1;
    --realm-well-shadow: rgba(6, 160, 120, 0.22);
    border-top-color: #06D6A0;
}

.theme-ocean {
    --realm-accent: #2395EB;
    --realm-accent-dark: #1467A6;
    --realm-surface-tint: #F0F7FE;
    --realm-well-bg: #E6F2FC;
    --realm-well-shadow: rgba(35, 149, 235, 0.22);
    border-top-color: #2395EB;
}

.theme-cosmic {
    --realm-accent: #845EC2;
    --realm-accent-dark: #58398A;
    --realm-surface-tint: #F6F2FC;
    --realm-well-bg: #EFE9FA;
    --realm-well-shadow: rgba(132, 94, 194, 0.22);
    border-top-color: #845EC2;
}

.theme-volcano {
    --realm-accent: #FF6B4A;
    --realm-accent-dark: #BF3E22;
    --realm-surface-tint: #FFF3F0;
    --realm-well-bg: #FDECE7;
    --realm-well-shadow: rgba(255, 107, 74, 0.22);
    border-top-color: #FF6B4A;
}

.kingdom-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.module-card-banner-wrap {
    margin: -0.35rem -0.45rem 0.85rem -0.45rem;
    height: 115px;
    border-radius: 16px;
    border: 2.5px solid var(--game-border);
    overflow: hidden;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 0 2px 4px rgba(0, 0, 0, 0.15);
}

.module-card-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Tactile Neumorphic 3D Avatar Badge */
.realm-avatar-badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(145deg, #FFFFFF 0%, var(--realm-well-bg) 100%);
    border: 2px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 3px 0 var(--game-border),
        3px 4px 8px var(--realm-well-shadow),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px var(--realm-well-shadow);
}

.realm-emoji {
    font-size: 1.45rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.12));
}

.realm-badges-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.realm-new-pill {
    font-size: 0.65rem;
    font-weight: 900;
    background: linear-gradient(135deg, #FFD166 0%, #FF8C00 100%);
    color: #4A1D00;
    border: 2px solid #FF8C00;
    padding: 0.18rem 0.55rem;
    border-radius: 9999px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    box-shadow:
        0 2px 0 #D97706,
        0 0 10px rgba(255, 179, 0, 0.45),
        inset 0 1px 2px rgba(255, 255, 255, 0.7);
    animation: newBadgePulse 1.8s infinite ease-in-out;
}

@keyframes newBadgePulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08) rotate(-1deg);
    }
}

.realm-status-pill {
    font-size: 0.68rem;
    font-weight: 900;
    background: linear-gradient(145deg, #F2FCF9 0%, #E0F7F0 100%);
    color: #038F6A;
    border: 2px solid #06D6A0;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    letter-spacing: 0.03em;
    box-shadow:
        0 2px 0 rgba(6, 214, 160, 0.35),
        inset 1px 1px 3px rgba(255, 255, 255, 0.9),
        inset -1px -1px 3px rgba(6, 214, 160, 0.2);
}

.realm-status-pill.boss-alert {
    background: linear-gradient(145deg, #FFF5F5 0%, #FFE3E3 100%);
    color: #CF1322;
    border-color: #FF4D4F;
    box-shadow:
        0 2px 0 rgba(255, 77, 79, 0.35),
        inset 1px 1px 3px rgba(255, 255, 255, 0.9),
        inset -1px -1px 3px rgba(255, 77, 79, 0.25);
    animation: alertPulse 1.2s infinite alternate;
}

@keyframes alertPulse {
    from { transform: scale(1); }
    to { transform: scale(1.06); }
}

.kingdom-title {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.15rem 0;
    line-height: 1.15;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.9);
}

.kingdom-grade {
    font-size: 0.78rem;
    color: #7A849E;
    font-weight: 800;
    margin: 0 0 0.85rem 0;
}

/* Recessed Neumorphic Stats Well */
.kingdom-stats-box {
    background: var(--realm-well-bg);
    border: 2px solid rgba(43, 51, 84, 0.14);
    border-radius: 14px;
    padding: 0.6rem 0.85rem;
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.85rem;
    box-shadow:
        inset 3px 3px 6px var(--realm-well-shadow),
        inset -3px -3px 6px rgba(255, 255, 255, 0.92),
        0 1px 0 rgba(255, 255, 255, 0.8);
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.64rem;
    font-weight: 900;
    color: #7F8AA6;
    letter-spacing: 0.04em;
}

.stat-value {
    font-size: 0.86rem;
    font-weight: 900;
    color: var(--game-navy);
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.8);
}

.buff-value {
    color: var(--realm-accent-dark);
}

.realm-progress-wrap {
    margin-bottom: 0.85rem;
}

.progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    font-weight: 900;
    margin-bottom: 0.3rem;
}

.progress-pct {
    color: var(--realm-accent-dark);
}

/* Carved Neumorphic Progress Tube */
.realm-track {
    width: 100%;
    height: 11px;
    background: var(--realm-well-bg);
    border: 2px solid var(--game-border);
    border-radius: 9999px;
    overflow: hidden;
    box-shadow:
        inset 2px 3px 5px rgba(29, 36, 64, 0.25),
        inset -2px -2px 4px rgba(255, 255, 255, 0.85),
        0 1px 0 rgba(255, 255, 255, 0.9);
}

.realm-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--realm-accent), #06D6A0);
    border-radius: 9999px;
    box-shadow:
        inset 0 2px 3px rgba(255, 255, 255, 0.65),
        inset 0 -2px 3px rgba(0, 0, 0, 0.2);
}

/* Pressed Neumorphic Boss Encounter Preview Box */
.boss-preview-box {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(145deg, #FFF5E4 0%, #FFFBF2 100%);
    border: 2px dashed #F4B942;
    border-radius: 12px;
    padding: 0.4rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 800;
    color: #805B00;
    margin-bottom: 0.95rem;
    box-shadow:
        inset 2px 2px 5px rgba(217, 155, 38, 0.2),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.boss-icon {
    font-size: 1.05rem;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.12));
}

.btn-enter-realm {
    width: 100%;
    padding: 0.65rem 0.9rem;
    font-size: 0.86rem;
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #4A3500;
    border-color: #8A6400;
    box-shadow:
        0 4px 0 #8A6400,
        0 6px 14px rgba(255, 183, 3, 0.28),
        inset 2px 2px 5px rgba(255, 255, 255, 0.8),
        inset -2px -3px 5px rgba(184, 133, 0, 0.35);
    margin-top: auto;
}

.btn-enter-realm:hover {
    transform: translateY(-2px);
    box-shadow:
        0 6px 0 #8A6400,
        0 10px 18px rgba(255, 183, 3, 0.38),
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -3px 5px rgba(184, 133, 0, 0.35);
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

/* ==========================================================================
   3D CARTOON + NEUMORPHIC BOTTOM GAME NAVIGATION DOCK
   ========================================================================== */
.game-bottom-dock {
    position: fixed;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 999;
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 38px;
    padding: 0.65rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow:
        0 10px 0 var(--game-border),
        -6px -6px 16px rgba(255, 255, 255, 0.85),
        0 22px 42px rgba(29, 36, 64, 0.25),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    user-select: none;
    max-width: 95vw;
}

.dock-item {
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.5rem;
    transition: transform 0.2s var(--spring-bounce);
    outline: none;
}

.dock-item:hover {
    transform: translateY(-4px);
}

.dock-bubble {
    width: 50px;
    height: 50px;
    border-radius: 18px;
    background: linear-gradient(145deg, #FFFFFF 0%, #E9EDF7 100%);
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
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
    transform: translateY(-4px) scale(1.08);
}

.dock-item.active .dock-bubble .dock-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.dock-label {
    font-family: var(--font-game);
    font-size: 0.72rem;
    font-weight: 900;
    color: #727B94;
    letter-spacing: 0.05em;
    transition: color 0.15s ease;
}

.dock-item.active .dock-label {
    color: var(--game-blue);
}

.dock-divider {
    width: 3px;
    height: 42px;
    background: #DFE4F2;
    border-radius: 9999px;
    box-shadow:
        inset 1px 1px 2px rgba(163, 174, 198, 0.6),
        1px 0 0 rgba(255, 255, 255, 0.9);
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

    .game-bottom-dock {
        bottom: 1rem;
        gap: 0.65rem;
        padding: 0.5rem 0.85rem;
        border-radius: 28px;
    }

    .dock-bubble {
        width: 42px;
        height: 42px;
        font-size: 1.3rem;
    }

    .dock-label {
        font-size: 0.65rem;
    }

    .bg-asset {
        display: none;
    }
}

@media (max-width: 720px) {
    .kingdoms-grid {
        grid-template-columns: 1fr;
    }
}
</style>
