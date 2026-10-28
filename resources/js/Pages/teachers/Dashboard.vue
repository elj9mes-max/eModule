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
});

const handleLogout = () => {
    router.post('/logout');
};

const goToStudents = () => {
    router.visit('/teachers/students');
};

// Game Biomes / Kingdoms (Classes)
const kingdoms = [
    {
        id: 1,
        name: 'Whispering Forest',
        grade: 'Grade 3 • Reading Legends',
        theme: 'forest',
        icon: '🌲',
        students: 32,
        maxStudents: 35,
        xpBonus: '+25% Phonics',
        progress: 88,
        stars: 3,
        boss: 'Grammar Goblin',
        status: 'Active Realm',
    },
    {
        id: 2,
        name: 'Sunken Atlantis',
        grade: 'Grade 4 • Ocean Comprehension',
        theme: 'ocean',
        icon: '🌊',
        students: 28,
        maxStudents: 30,
        xpBonus: '+30% Vocab',
        progress: 74,
        stars: 2,
        boss: 'Kraken of Syntax',
        status: 'Active Realm',
    },
    {
        id: 3,
        name: 'Cosmic Nebula',
        grade: 'Grade 5 • Sci-Fi Writers Guild',
        theme: 'cosmic',
        icon: '🚀',
        students: 30,
        maxStudents: 32,
        xpBonus: '+40% Creative',
        progress: 92,
        stars: 3,
        boss: 'Asteroid Dragon',
        status: 'Boss Battle',
    },
    {
        id: 4,
        name: 'Dino Volcanoes',
        grade: 'Grade 2 • Story Detectives',
        theme: 'volcano',
        icon: '🌋',
        students: 28,
        maxStudents: 30,
        xpBonus: '+20% Speed',
        progress: 60,
        stars: 2,
        boss: 'T-Rex Rhyme King',
        status: 'In Progress',
    },
];
</script>

<template>
    <Head title="Teacher Kingdoms HQ • WEBREAD" />

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
            <!-- WORLD / CLASSROOM KINGDOMS -->
            <section class="tab-panel world-tab">
                <div class="panel-header-row">
                    <div>
                        <h1 class="panel-title">🏰 Classroom Kingdoms</h1>
                        <p class="panel-sub">Select a classroom biome to manage student adventures, reading quests, and realm milestones.</p>
                    </div>
                    <div class="header-buttons">
                        <button type="button" class="btn-3d btn-ranking-action" @click="goToStudents">
                            <span>🏆 VIEW STUDENT RANKINGS</span>
                        </button>
                        <button type="button" class="btn-3d btn-primary-action">
                            <span>✨ + SUMMON NEW REALM</span>
                        </button>
                    </div>
                </div>

                <div class="kingdoms-grid">
                    <div
                        v-for="realm in kingdoms"
                        :key="realm.id"
                        class="kingdom-card"
                        :class="`theme-${realm.theme}`"
                    >
                        <!-- Realm Top Badge -->
                        <div class="kingdom-top">
                            <div class="realm-avatar-badge">
                                <span class="realm-emoji">{{ realm.icon }}</span>
                            </div>
                            <div class="realm-status-pill" :class="{ 'boss-alert': realm.status === 'Boss Battle' }">
                                {{ realm.status }}
                            </div>
                        </div>

                        <!-- Realm Info -->
                        <h3 class="kingdom-title">{{ realm.name }}</h3>
                        <p class="kingdom-grade">{{ realm.grade }}</p>

                        <!-- Realm Stats Block -->
                        <div class="kingdom-stats-box">
                            <div class="stat-item">
                                <span class="stat-label">Students</span>
                                <span class="stat-value">🎒 {{ realm.students }} / {{ realm.maxStudents }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Guild Buff</span>
                                <span class="stat-value buff-value">{{ realm.xpBonus }}</span>
                            </div>
                        </div>

                        <!-- Level Progress -->
                        <div class="realm-progress-wrap">
                            <div class="progress-labels">
                                <span>Realm Level</span>
                                <span class="progress-pct">{{ realm.progress }}%</span>
                            </div>
                            <div class="realm-track">
                                <div class="realm-fill" :style="{ width: `${realm.progress}%` }"></div>
                            </div>
                        </div>

                        <!-- Boss Encounter Preview -->
                        <div class="boss-preview-box">
                            <span class="boss-icon">👹</span>
                            <span class="boss-text">Boss: <strong>{{ realm.boss }}</strong></span>
                        </div>

                        <!-- Card Action -->
                        <button type="button" class="btn-3d btn-enter-realm">
                            <span>ENTER KINGDOM</span>
                            <span class="arrow-icon">➔</span>
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- 3D CARTOON BOTTOM GAME NAVIGATION DOCK -->
        <nav class="game-bottom-dock" aria-label="Game Console Dock">
            <!-- Dock Item: World / Kingdoms (Active) -->
            <button
                type="button"
                class="dock-item active"
                title="Classroom Kingdoms"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏰</span>
                </div>
                <span class="dock-label">KINGDOMS</span>
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
   CARTOON 3D GAME TOKENS & RESET
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
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-game: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;

    min-height: 100vh;
    background-color: #F8F4E8;
    background-image: 
        radial-gradient(#E2DBCA 2px, transparent 2px),
        radial-gradient(#E2DBCA 2px, #F8F4E8 2px);
    background-size: 36px 36px;
    background-position: 0 0, 18px 18px;
    font-family: var(--font-game);
    color: var(--game-navy);
    position: relative;
    padding-bottom: 7.5rem;
    overflow-x: hidden;
    box-sizing: border-box;
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

/* 3D Button Base */
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
    transition: transform 0.1s var(--spring-bounce), box-shadow 0.1s var(--spring-bounce);
    outline: none;
    user-select: none;
}

.btn-3d:active {
    transform: translateY(4px) !important;
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
    background: #FFFFFF;
    border: 3px solid var(--game-border);
    border-radius: 24px;
    padding: 0.65rem 1.4rem 0.65rem 0.85rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 
        0 6px 0 var(--game-border),
        0 10px 20px rgba(29, 36, 64, 0.08);
}

.hud-avatar-wrapper {
    position: relative;
}

.avatar-ring {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FFDE59 0%, #FFA800 100%);
    border: 3px solid #1D2440;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.8);
}

.avatar-emoji {
    font-size: 1.9rem;
    line-height: 1;
}

.avatar-lvl-badge {
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    background: #FF6B4A;
    color: #FFFFFF;
    font-size: 0.65rem;
    font-weight: 900;
    padding: 0.1rem 0.45rem;
    border-radius: 9999px;
    border: 2px solid #1D2440;
    white-space: nowrap;
    box-shadow: 0 2px 0 #1D2440;
}

.hud-profile-meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.hud-name-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.hud-title-crown {
    font-size: 1.1rem;
}

.hud-name {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--game-navy);
}

.instructor-tag {
    font-size: 0.65rem;
    font-weight: 900;
    background: #E8F4FD;
    color: #167AC6;
    border: 1.5px solid #2395EB;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
    letter-spacing: 0.04em;
}

.hud-xp-meter {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.xp-track {
    width: 140px;
    height: 12px;
    background: #E2E6F0;
    border: 2px solid #1D2440;
    border-radius: 9999px;
    overflow: hidden;
    position: relative;
}

.xp-fill {
    height: 100%;
    background: linear-gradient(90deg, #06D6A0, #2395EB);
    border-radius: 9999px;
    position: relative;
}

.xp-shine {
    position: absolute;
    top: 1px;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(255, 255, 255, 0.6);
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
    background: #FFFFFF;
    border: 3px solid var(--game-border);
    border-radius: 20px;
    padding: 0.45rem 1rem 0.45rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    box-shadow: 
        0 5px 0 var(--game-border),
        0 6px 14px rgba(29, 36, 64, 0.06);
    transition: transform 0.15s ease;
}

.inventory-pill:hover {
    transform: translateY(-2px);
}

.pill-icon {
    font-size: 1.45rem;
    line-height: 1;
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
    max-width: 1300px;
    margin: 0.5rem auto 2rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
}

.panel-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.panel-title {
    font-size: 1.95rem;
    font-weight: 900;
    margin: 0;
    color: var(--game-navy);
}

.panel-sub {
    font-size: 0.95rem;
    font-family: var(--font-ui);
    color: #6C7594;
    margin: 0.25rem 0 0 0;
    font-weight: 700;
}

.header-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-ranking-action {
    background: linear-gradient(135deg, #FFDE59 0%, #FFB703 100%);
    color: #573F00;
    padding: 0.75rem 1.35rem;
    font-size: 0.92rem;
    border-color: #8A6400;
    box-shadow: 0 5px 0 #8A6400, 0 8px 18px rgba(255, 183, 3, 0.35);
}

.btn-ranking-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 #8A6400;
}

.btn-primary-action {
    background: linear-gradient(135deg, #2395EB 0%, #1677C2 100%);
    color: #FFFFFF;
    padding: 0.75rem 1.4rem;
    font-size: 0.92rem;
    border-color: #0E5287;
    box-shadow: 0 5px 0 #0E5287, 0 8px 18px rgba(35, 149, 235, 0.35);
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 #0E5287, 0 12px 22px rgba(35, 149, 235, 0.45);
}

/* Kingdoms Grid */
.kingdoms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(285px, 1fr));
    gap: 1.5rem;
}

.kingdom-card {
    background: #FFFFFF;
    border: 3.5px solid var(--game-border);
    border-radius: 26px;
    padding: 1.6rem;
    display: flex;
    flex-direction: column;
    box-shadow: 
        0 8px 0 var(--game-border),
        0 14px 28px rgba(29, 36, 64, 0.08);
    transition: transform 0.2s var(--spring-bounce);
    position: relative;
}

.kingdom-card:hover {
    transform: translateY(-4px);
}

.theme-forest { border-top-color: #06D6A0; }
.theme-ocean { border-top-color: #2395EB; }
.theme-cosmic { border-top-color: #845EC2; }
.theme-volcano { border-top-color: #FF6B4A; }

.kingdom-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.realm-avatar-badge {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: #F4F6FB;
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 0 var(--game-border);
}

.realm-emoji {
    font-size: 1.85rem;
}

.realm-status-pill {
    font-size: 0.72rem;
    font-weight: 900;
    background: #EBF8F4;
    color: #038F6A;
    border: 2px solid #06D6A0;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    letter-spacing: 0.03em;
}

.realm-status-pill.boss-alert {
    background: #FFF1F0;
    color: #CF1322;
    border-color: #FF4D4F;
    animation: alertPulse 1.2s infinite alternate;
}

@keyframes alertPulse {
    from { transform: scale(1); }
    to { transform: scale(1.06); }
}

.kingdom-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.25rem 0;
    line-height: 1.15;
}

.kingdom-grade {
    font-size: 0.82rem;
    color: #7A849E;
    font-weight: 800;
    margin: 0 0 1.15rem 0;
}

.kingdom-stats-box {
    background: #F8F9FD;
    border: 2px solid #E2E6F2;
    border-radius: 16px;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.15rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.68rem;
    font-weight: 900;
    color: #8C96B0;
    letter-spacing: 0.04em;
}

.stat-value {
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--game-navy);
}

.buff-value {
    color: #038F6A;
}

.realm-progress-wrap {
    margin-bottom: 1.15rem;
}

.progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    font-weight: 900;
    margin-bottom: 0.35rem;
}

.progress-pct {
    color: var(--game-blue);
}

.realm-track {
    width: 100%;
    height: 12px;
    background: #E8EBF5;
    border: 2px solid var(--game-border);
    border-radius: 9999px;
    overflow: hidden;
}

.realm-fill {
    height: 100%;
    background: linear-gradient(90deg, #2395EB, #06D6A0);
    border-radius: 9999px;
}

.boss-preview-box {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: #FFF7EB;
    border: 2px dashed #FFD166;
    border-radius: 12px;
    padding: 0.45rem 0.75rem;
    font-size: 0.82rem;
    font-weight: 800;
    color: #805B00;
    margin-bottom: 1.25rem;
}

.boss-icon {
    font-size: 1.1rem;
}

.btn-enter-realm {
    width: 100%;
    padding: 0.8rem 1rem;
    font-size: 0.95rem;
    background: linear-gradient(135deg, #FFDE59 0%, #FFB703 100%);
    color: #4A3500;
    border-color: #8A6400;
    box-shadow: 0 4px 0 #8A6400;
    margin-top: auto;
}

.btn-enter-realm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #8A6400;
}

/* ==========================================================================
   3D CARTOON BOTTOM GAME NAVIGATION DOCK
   ========================================================================== */
.game-bottom-dock {
    position: fixed;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 999;
    background: #FFFFFF;
    border: 3.5px solid var(--game-border);
    border-radius: 36px;
    padding: 0.6rem 1.4rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 
        0 10px 0 var(--game-border),
        0 20px 40px rgba(29, 36, 64, 0.22);
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
    width: 48px;
    height: 48px;
    border-radius: 18px;
    background: #F1F3F9;
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 3px 0 var(--game-border);
    position: relative;
    transition: all 0.2s var(--spring-bounce);
}

.dock-item.active .dock-bubble {
    background: linear-gradient(135deg, #2395EB 0%, #1579C4 100%);
    border-color: #0E5287;
    box-shadow: 0 4px 0 #0E5287;
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
    width: 2px;
    height: 42px;
    background: #E2E6F2;
    border-radius: 9999px;
}

.logout-item:hover .exit-bubble {
    background: #FFF1F0;
    border-color: #FF4D4F;
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
</style>
