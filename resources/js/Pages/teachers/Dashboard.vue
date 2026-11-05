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
                        <!-- <button type="button" class="btn-3d btn-ranking-action" @click="goToStudents">
                            <span>🏆 VIEW STUDENT RANKINGS</span>
                        </button> -->
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
                        role="button"
                        tabindex="0"
                        :aria-label="'Open learning module: ' + mod.title"
                        @click="openModule(mod)"
                        @keydown.enter="openModule(mod)"
                        @keydown.space.prevent="openModule(mod)"
                    >
                        <!-- 3D Clay Decorative Accent: Star perched on top frame rim -->
                        <div class="clay-accent clay-star" aria-hidden="true">
                            <svg viewBox="0 0 54 54" class="clay-star-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                                </defs>
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
                                <defs>
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
                                </defs>
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
                                <defs>
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
                                </defs>
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
                            <span v-if="mod.status === 'draft'" class="clay-badge-draft">DRAFT</span>
                        </div>

                        <!-- Stepped Neumorphic Bezel holding Center Picture Window -->
                        <div class="frame-inner-bezel">
                            <div class="frame-window">
                                <img
                                    v-if="mod.cover_image_url"
                                    :src="mod.cover_image_url"
                                    :alt="mod.title"
                                    class="frame-center-image"
                                />
                                <!-- 3D Cartoon Fallback Fantasy Landscape (when no cover image uploaded) -->
                                <div v-else class="frame-fallback-scene" aria-hidden="true">
                                    <svg viewBox="0 0 600 360" preserveAspectRatio="xMidYMid slice" class="fallback-scenery-svg">
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
                            </div>
                        </div>

                        <!-- 3D Extruded Neumorphic Plaque with Module Title -->
                        <div class="clay-title-plaque">
                            <span class="plaque-title-text" :title="mod.title">
                                {{ mod.title }}
                            </span>
                        </div>
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

/* Kingdoms Grid: Fixed 2 Modules Per Row with Generous Spacing for 3D Overlaps */
.kingdoms-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 3rem 1.85rem;
    padding-top: 0.5rem;
    padding-bottom: 2rem;
}

/* 3D CARTOON NEUMORPHISM KINGDOM CARD */
.kingdom-card {
    --frame-grad-1: #D4DEFD;
    --frame-grad-2: #E8E1F8;
    --frame-grad-3: #FDE4EB;
    --frame-grad-4: #FEE7DD;
    --frame-bezel-1: #C2CEF7;
    --frame-bezel-2: #E5DBF5;
    --frame-shadow-dark: rgba(72, 60, 108, 0.24);

    position: relative;
    border-radius: 36px;
    padding: 16px;
    background: linear-gradient(135deg, var(--frame-grad-1) 0%, var(--frame-grad-2) 32%, var(--frame-grad-3) 68%, var(--frame-grad-4) 100%);
    box-shadow:
        -12px 18px 34px -4px var(--frame-shadow-dark),
        0 6px 14px rgba(0, 0, 0, 0.05),
        inset 3px 3px 6px rgba(255, 255, 255, 0.95),
        inset -4px -4px 7px rgba(162, 172, 208, 0.42);
    cursor: pointer;
    user-select: none;
    outline: none;
    transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.kingdom-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow:
        -16px 26px 42px -4px var(--frame-shadow-dark),
        0 10px 22px rgba(0, 0, 0, 0.08),
        inset 3px 3px 6px rgba(255, 255, 255, 1),
        inset -4px -4px 7px rgba(162, 172, 208, 0.48);
}

.kingdom-card:active {
    transform: translateY(2px) scale(0.985);
    box-shadow:
        -6px 10px 18px -2px var(--frame-shadow-dark),
        0 3px 8px rgba(0, 0, 0, 0.06),
        inset 4px 4px 8px rgba(140, 150, 185, 0.45),
        inset -2px -2px 5px rgba(255, 255, 255, 0.85);
}

.kingdom-card:focus-visible {
    box-shadow:
        0 0 0 4px #8B5CF6,
        -14px 22px 38px var(--frame-shadow-dark);
}

/* Biome Color Variations for Frame */
.theme-forest {
    --frame-grad-1: #D1F4E6;
    --frame-grad-2: #E1F7EC;
    --frame-grad-3: #FDE8D7;
    --frame-grad-4: #FEECE2;
    --frame-bezel-1: #B4ECCF;
    --frame-bezel-2: #C8F0DC;
    --frame-shadow-dark: rgba(30, 95, 75, 0.22);
}

.theme-ocean {
    --frame-grad-1: #CCE6FD;
    --frame-grad-2: #DEEFFE;
    --frame-grad-3: #FDE2EE;
    --frame-grad-4: #FEEDEB;
    --frame-bezel-1: #B3DAFC;
    --frame-bezel-2: #CBE6FD;
    --frame-shadow-dark: rgba(30, 80, 125, 0.24);
}

.theme-cosmic {
    --frame-grad-1: #D8D2FB;
    --frame-grad-2: #E8D8FA;
    --frame-grad-3: #FCE0ED;
    --frame-grad-4: #FEDDE3;
    --frame-bezel-1: #C6BCF8;
    --frame-bezel-2: #DACAF6;
    --frame-shadow-dark: rgba(75, 45, 115, 0.26);
}

.theme-volcano {
    --frame-grad-1: #FEE1D6;
    --frame-grad-2: #FEDBD4;
    --frame-grad-3: #FEE7C8;
    --frame-grad-4: #FEF0D4;
    --frame-bezel-1: #FCCEC0;
    --frame-bezel-2: #FDC7B8;
    --frame-shadow-dark: rgba(135, 55, 35, 0.24);
}

/* Stepped Inner Frame Bezel */
.frame-inner-bezel {
    border-radius: 26px;
    padding: 7px;
    background: linear-gradient(135deg, var(--frame-bezel-1) 0%, var(--frame-bezel-2) 100%);
    box-shadow:
        inset 2.5px 2.5px 5px rgba(50, 40, 80, 0.2),
        inset -2.5px -2.5px 5px rgba(255, 255, 255, 0.85);
}

/* Recessed Center Picture Window */
.frame-window {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    aspect-ratio: 16 / 9.5;
    background: #AEE2FF;
    box-shadow: inset 0 3px 8px rgba(0, 0, 0, 0.32);
}

.frame-center-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}

.kingdom-card:hover .frame-center-image {
    transform: scale(1.03);
}

.frame-fallback-scene {
    width: 100%;
    height: 100%;
    display: flex;
}

.fallback-scenery-svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* 3D Decorative Overlapping Clay Accents */
.clay-accent {
    position: absolute;
    pointer-events: none;
    z-index: 10;
}

/* Clay Star: Peeking on top rim */
.clay-star {
    top: -14px;
    right: 28%;
    width: 48px;
    height: 48px;
    transform: rotate(10deg);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.kingdom-card:hover .clay-star {
    transform: rotate(22deg) scale(1.15) translateY(-3px);
}

.clay-star-svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

/* Clay Clouds on Left and Right Frame Edges */
.clay-cloud-left {
    top: 36%;
    left: -18px;
    width: 82px;
    height: 54px;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.kingdom-card:hover .clay-cloud-left {
    transform: translateX(-4px) scale(1.06);
}

.clay-cloud-right {
    top: 28%;
    right: -18px;
    width: 82px;
    height: 54px;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.kingdom-card:hover .clay-cloud-right {
    transform: translateX(4px) scale(1.06);
}

.clay-cloud-svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

/* Discrete Floating Clay Status Badges */
.clay-card-badges {
    position: absolute;
    top: 22px;
    left: 22px;
    z-index: 11;
    display: flex;
    gap: 0.35rem;
    pointer-events: none;
}

.clay-badge-new {
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    background: linear-gradient(135deg, #FFD166 0%, #FF8C00 100%);
    color: #4A1D00;
    padding: 0.22rem 0.55rem;
    border-radius: 9999px;
    box-shadow:
        0 3px 6px rgba(255, 140, 0, 0.35),
        inset 0 1px 2px rgba(255, 255, 255, 0.8);
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

.clay-badge-draft {
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    background: linear-gradient(135deg, #FFF1F2 0%, #FFE4E6 100%);
    color: #E11D48;
    padding: 0.22rem 0.55rem;
    border-radius: 9999px;
    box-shadow:
        0 2px 4px rgba(225, 29, 72, 0.2),
        inset 0 1px 2px rgba(255, 255, 255, 0.9);
}

/* 3D Extruded Neumorphic Plaque with Bubble Title at Bottom */
.clay-title-plaque {
    position: absolute;
    bottom: -18px;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 64px);
    max-width: 88%;
    min-height: 52px;
    padding: 0.55rem 1.25rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FDF2F7 0%, #FCE4EF 35%, #F7C6DF 80%, #F1AED2 100%);
    border: 2px solid rgba(255, 255, 255, 0.85);
    box-shadow:
        0 10px 22px rgba(110, 50, 95, 0.24),
        0 4px 8px rgba(0, 0, 0, 0.07),
        inset 0 3px 3px rgba(255, 255, 255, 0.98),
        inset 0 -3px 5px rgba(180, 50, 115, 0.26);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    z-index: 12;
    transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.kingdom-card:hover .clay-title-plaque {
    transform: translateX(-50%) translateY(-2px);
    box-shadow:
        0 14px 28px rgba(110, 50, 95, 0.3),
        0 6px 10px rgba(0, 0, 0, 0.09),
        inset 0 3px 3px rgba(255, 255, 255, 1),
        inset 0 -3px 5px rgba(180, 50, 115, 0.3);
}

.plaque-title-text {
    font-family: 'Fredoka', cursive, sans-serif;
    font-weight: 900;
    font-size: 1.15rem;
    line-height: 1.15;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    background: linear-gradient(90deg, #FF6B8B 0%, #FFA26B 20%, #F6C90E 40%, #06D6A0 60%, #4EA8DE 80%, #9D4EDD 95%, #FF6B8B 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 2px 0 #FFFFFF) drop-shadow(0 3px 2px rgba(70, 30, 80, 0.2));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
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
