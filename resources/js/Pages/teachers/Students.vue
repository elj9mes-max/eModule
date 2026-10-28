<script setup>
import { ref, computed } from 'vue';
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

const goToDashboard = () => {
    router.visit('/teachers/dashboard');
};

// Search & Filter State
const searchQuery = ref('');
const selectedKingdom = ref('all');
const sortBy = ref('stars'); // 'stars' | 'level' | 'streak'
const kudosCount = ref({});

// Student Adventurers Data
const allStudents = ref([
    {
        rank: 1,
        name: 'Maya "Star-Reader" Lin',
        level: 34,
        kingdom: 'Whispering Forest',
        kingdomIcon: '🌲',
        stars: 48,
        streak: 18,
        booksRead: 42,
        badge: 'Guild Grand Champion',
        avatar: '🦊',
        podiumColor: '#FFD166',
        quote: 'Books are portals to magical realms!',
    },
    {
        rank: 2,
        name: 'Leo "Speed-Reader" Vance',
        level: 32,
        kingdom: 'Cosmic Nebula',
        kingdomIcon: '🚀',
        stars: 44,
        streak: 15,
        booksRead: 39,
        badge: 'Master Storyteller',
        avatar: '🦁',
        podiumColor: '#E0E2EC',
        quote: 'Speed reading across galaxies!',
    },
    {
        rank: 3,
        name: 'Aria "Word-Weaver" Patel',
        level: 31,
        kingdom: 'Sunken Atlantis',
        kingdomIcon: '🌊',
        stars: 41,
        streak: 14,
        booksRead: 36,
        badge: 'Syntax Wizard',
        avatar: '🐼',
        podiumColor: '#FFC09F',
        quote: 'Unlocking ancient coral mysteries.',
    },
    {
        rank: 4,
        name: 'Kai "Quest-Master" Thorne',
        level: 29,
        kingdom: 'Dino Volcanoes',
        kingdomIcon: '🌋',
        stars: 38,
        streak: 12,
        booksRead: 31,
        badge: 'Dragon Reader',
        avatar: '🐯',
    },
    {
        rank: 5,
        name: 'Zoe "Bookworm" Alvarez',
        level: 28,
        kingdom: 'Whispering Forest',
        kingdomIcon: '🌲',
        stars: 35,
        streak: 11,
        booksRead: 29,
        badge: 'Lore Chronicler',
        avatar: '🐰',
    },
    {
        rank: 6,
        name: 'Noah "Cyber-Coder" Chen',
        level: 27,
        kingdom: 'Cosmic Nebula',
        kingdomIcon: '🚀',
        stars: 34,
        streak: 9,
        booksRead: 27,
        badge: 'Sci-Fi Scribe',
        avatar: '🐨',
    },
    {
        rank: 7,
        name: 'Sophia "Myth-Seeker" Rossi',
        level: 26,
        kingdom: 'Sunken Atlantis',
        kingdomIcon: '🌊',
        stars: 32,
        streak: 8,
        booksRead: 25,
        badge: 'Rune Decoder',
        avatar: '🦄',
    },
    {
        rank: 8,
        name: 'Ethan "Rhyme-Hunter" Brooks',
        level: 24,
        kingdom: 'Dino Volcanoes',
        kingdomIcon: '🌋',
        stars: 29,
        streak: 7,
        booksRead: 22,
        badge: 'Fossil Detective',
        avatar: '🐸',
    },
    {
        rank: 9,
        name: 'Elena "Story-Speller" Gomez',
        level: 23,
        kingdom: 'Whispering Forest',
        kingdomIcon: '🌲',
        stars: 28,
        streak: 6,
        booksRead: 20,
        badge: 'Forest Wanderer',
        avatar: '🦉',
    },
    {
        rank: 10,
        name: 'Marcus "Gale-Walker" Young',
        level: 21,
        kingdom: 'Cosmic Nebula',
        kingdomIcon: '🚀',
        stars: 25,
        streak: 5,
        booksRead: 18,
        badge: 'Space Explorer',
        avatar: '🐺',
    },
]);

// Top 3 Podium
const topThree = computed(() => {
    return allStudents.value.slice(0, 3);
});

// Filtered & Sorted Adventurers
const filteredStudents = computed(() => {
    let list = allStudents.value.slice(3); // Ranks 4+ for table view

    if (selectedKingdom.value !== 'all') {
        list = list.filter((s) => s.kingdom === selectedKingdom.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(
            (s) =>
                s.name.toLowerCase().includes(q) ||
                s.kingdom.toLowerCase().includes(q) ||
                s.badge.toLowerCase().includes(q)
        );
    }

    return list.sort((a, b) => {
        if (sortBy.value === 'stars') return b.stars - a.stars;
        if (sortBy.value === 'level') return b.level - a.level;
        if (sortBy.value === 'streak') return b.streak - a.streak;
        return a.rank - b.rank;
    });
});

const sendKudos = (studentName) => {
    kudosCount.value[studentName] = (kudosCount.value[studentName] || 0) + 1;
};
</script>

<template>
    <Head title="Student Guild Leaderboard • WEBREAD" />

    <div class="game-dashboard-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Floating 3D Pop-Up Scenery Assets -->
        <div class="game-bg-elements" aria-hidden="true">
            <img src="/images/book_left_trees_mountains.png" alt="" class="bg-asset bg-book-left" />
            <img src="/images/book_right_mountains_river.png" alt="" class="bg-asset bg-book-right" />
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">⭐</div>
            <div class="floating-sparkle sp-3">👑</div>
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

                <div class="inventory-pill guild-pill" title="Guild Members">
                    <span class="pill-icon guild-icon">🎒</span>
                    <div class="pill-data">
                        <span class="pill-num">118</span>
                        <span class="pill-lbl">STUDENTS</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN ARENA STAGE -->
        <main class="game-stage">
            <!-- PAGE TITLE BAR -->
            <section class="ranking-header-bar">
                <div class="header-text-group">
                    <div class="title-pill-tag">
                        <span>🏆 GUILD HONOR ROLL</span>
                    </div>
                    <h1 class="page-main-title">Student Adventurer Rankings</h1>
                    <p class="page-sub-desc">
                        Honoring reading champions, daily quest streaks, and milestone book completions across all realms!
                    </p>
                </div>

                <div class="header-action-group">
                    <button type="button" class="btn-3d btn-bonus-reward" @click="sendKudos('Guild')">
                        <span>🌟 AWARD +50 GUILD STARS</span>
                    </button>
                </div>
            </section>

            <!-- 3D CARTOON TOP 3 WINNERS PODIUM -->
            <section class="podium-section" aria-label="Top 3 Champion Podium">
                <div class="podium-grid">
                    <!-- Rank #2: Silver Stand (Left) -->
                    <div class="podium-column rank-2-col">
                        <div class="podium-avatar-card">
                            <span class="crown-badge silver-crown">🥈</span>
                            <div class="podium-avatar-bubble silver-bubble">
                                <span class="avatar-face">{{ topThree[1].avatar }}</span>
                            </div>
                            <span class="podium-name">{{ topThree[1].name }}</span>
                            <span class="podium-badge-label">{{ topThree[1].badge }}</span>
                            <div class="podium-stats-pill">
                                <span>⭐ {{ topThree[1].stars }} Stars</span>
                                <span>🔥 {{ topThree[1].streak }}d</span>
                            </div>
                            <p class="podium-quote">"{{ topThree[1].quote }}"</p>
                        </div>
                        <div class="podium-block step-silver">
                            <span class="podium-step-num">2</span>
                            <span class="podium-step-label">SILVER CHAMPION</span>
                        </div>
                    </div>

                    <!-- Rank #1: Gold Stand (Center, Tallest) -->
                    <div class="podium-column rank-1-col">
                        <div class="gold-halo-effect"></div>
                        <div class="podium-avatar-card gold-card">
                            <span class="crown-badge gold-crown">👑 🥇</span>
                            <div class="podium-avatar-bubble gold-bubble">
                                <span class="avatar-face">{{ topThree[0].avatar }}</span>
                            </div>
                            <span class="podium-name gold-name">{{ topThree[0].name }}</span>
                            <span class="podium-badge-label gold-badge-label">{{ topThree[0].badge }}</span>
                            <div class="podium-stats-pill gold-stats">
                                <span>⭐ {{ topThree[0].stars }} Stars</span>
                                <span>🔥 {{ topThree[0].streak }}d Streak</span>
                            </div>
                            <p class="podium-quote">"{{ topThree[0].quote }}"</p>
                        </div>
                        <div class="podium-block step-gold">
                            <span class="podium-step-num">1</span>
                            <span class="podium-step-label">GRAND CHAMPION</span>
                        </div>
                    </div>

                    <!-- Rank #3: Bronze Stand (Right) -->
                    <div class="podium-column rank-3-col">
                        <div class="podium-avatar-card">
                            <span class="crown-badge bronze-crown">🥉</span>
                            <div class="podium-avatar-bubble bronze-bubble">
                                <span class="avatar-face">{{ topThree[2].avatar }}</span>
                            </div>
                            <span class="podium-name">{{ topThree[2].name }}</span>
                            <span class="podium-badge-label">{{ topThree[2].badge }}</span>
                            <div class="podium-stats-pill">
                                <span>⭐ {{ topThree[2].stars }} Stars</span>
                                <span>🔥 {{ topThree[2].streak }}d</span>
                            </div>
                            <p class="podium-quote">"{{ topThree[2].quote }}"</p>
                        </div>
                        <div class="podium-block step-bronze">
                            <span class="podium-step-num">3</span>
                            <span class="podium-step-label">BRONZE CHAMPION</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- GUILD FILTER & CONTROLS BAR -->
            <section class="controls-card">
                <!-- Search Box -->
                <div class="search-box-pill">
                    <span class="search-icon">🔍</span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="search-input"
                        placeholder="Search student adventurer or badge..."
                    />
                </div>

                <!-- Realm Filter Dropdown / Pills -->
                <div class="filter-pills-row">
                    <button
                        type="button"
                        class="filter-pill-btn"
                        :class="{ active: selectedKingdom === 'all' }"
                        @click="selectedKingdom = 'all'"
                    >
                        🏰 All Realms
                    </button>
                    <button
                        type="button"
                        class="filter-pill-btn"
                        :class="{ active: selectedKingdom === 'Whispering Forest' }"
                        @click="selectedKingdom = 'Whispering Forest'"
                    >
                        🌲 Whispering Forest
                    </button>
                    <button
                        type="button"
                        class="filter-pill-btn"
                        :class="{ active: selectedKingdom === 'Sunken Atlantis' }"
                        @click="selectedKingdom = 'Sunken Atlantis'"
                    >
                        🌊 Sunken Atlantis
                    </button>
                    <button
                        type="button"
                        class="filter-pill-btn"
                        :class="{ active: selectedKingdom === 'Cosmic Nebula' }"
                        @click="selectedKingdom = 'Cosmic Nebula'"
                    >
                        🚀 Cosmic Nebula
                    </button>
                    <button
                        type="button"
                        class="filter-pill-btn"
                        :class="{ active: selectedKingdom === 'Dino Volcanoes' }"
                        @click="selectedKingdom = 'Dino Volcanoes'"
                    >
                        🌋 Dino Volcanoes
                    </button>
                </div>

                <!-- Sort By Selector -->
                <div class="sort-selector-wrap">
                    <span class="sort-lbl">SORT:</span>
                    <select v-model="sortBy" class="sort-select">
                        <option value="stars">⭐ Most Stars</option>
                        <option value="level">⚡ Highest Level</option>
                        <option value="streak">🔥 Longest Streak</option>
                    </select>
                </div>
            </section>

            <!-- FULL GUILD LEADERBOARD TABLE -->
            <section class="guild-roster-card">
                <div class="guild-roster-header">
                    <span class="col-rank">RANK</span>
                    <span class="col-adventurer">STUDENT ADVENTURER</span>
                    <span class="col-level">LEVEL</span>
                    <span class="col-realm">ASSIGNED REALM</span>
                    <span class="col-streak">STREAK</span>
                    <span class="col-stars">STARS</span>
                    <span class="col-actions">ACTION</span>
                </div>

                <div v-if="filteredStudents.length > 0" class="guild-roster-body">
                    <div
                        v-for="student in filteredStudents"
                        :key="student.rank"
                        class="guild-adventurer-row"
                    >
                        <!-- Rank -->
                        <div class="col-rank">
                            <span class="rank-badge">#{{ student.rank }}</span>
                        </div>

                        <!-- Adventurer Details -->
                        <div class="col-adventurer">
                            <div class="adventurer-avatar-box">
                                <span>{{ student.avatar }}</span>
                            </div>
                            <div class="adventurer-meta">
                                <span class="adventurer-name">{{ student.name }}</span>
                                <span class="adventurer-honor-tag">{{ student.badge }}</span>
                            </div>
                        </div>

                        <!-- Level -->
                        <div class="col-level">
                            <span class="level-pill-badge">LVL {{ student.level }}</span>
                        </div>

                        <!-- Realm -->
                        <div class="col-realm">
                            <span class="realm-name-tag">
                                {{ student.kingdomIcon }} {{ student.kingdom }}
                            </span>
                        </div>

                        <!-- Streak -->
                        <div class="col-streak">
                            <span class="streak-pill">🔥 {{ student.streak }}d</span>
                        </div>

                        <!-- Stars -->
                        <div class="col-stars">
                            <span class="star-rating-val">⭐ {{ student.stars }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="col-actions">
                            <button
                                type="button"
                                class="btn-3d btn-award-kudos"
                                @click="sendKudos(student.name)"
                                :title="`Send praise to ${student.name}`"
                            >
                                <span class="kudos-icon">👏</span>
                                <span class="kudos-txt">
                                    {{ (kudosCount[student.name] || 0) > 0 ? `+${kudosCount[student.name]} Kudos` : 'Send Kudos' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="empty-roster-state">
                    <span class="empty-icon">🔍</span>
                    <h3 class="empty-title">No Adventurers Found</h3>
                    <p class="empty-desc">Try clearing your search term or switching to "All Realms".</p>
                </div>
            </section>
        </main>

        <!-- 3D CARTOON BOTTOM GAME NAVIGATION DOCK -->
        <nav class="game-bottom-dock" aria-label="Game Console Dock">
            <!-- Dock Item: World / Kingdoms (Back to Dashboard) -->
            <button
                type="button"
                class="dock-item"
                @click="goToDashboard"
            >
                <div class="dock-bubble">
                    <span class="dock-icon">🏰</span>
                </div>
                <span class="dock-label">KINGDOMS</span>
            </button>

            <!-- Dock Item: Students Ranking (Active) -->
            <button
                type="button"
                class="dock-item active"
                title="Student Guild Rankings"
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
   CARTOON 3D GAME THEME TOKENS
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

/* Background floating scenery */
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

.sp-1 { top: 10%; left: 18%; animation-delay: 0.2s; }
.sp-2 { top: 18%; right: 20%; animation-delay: 0.9s; }
.sp-3 { bottom: 25%; left: 28%; animation-delay: 1.5s; }
.sp-4 { bottom: 32%; right: 12%; animation-delay: 2.1s; }

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
   GAME STAGE & HEADER
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

.ranking-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #FFFFFF;
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    padding: 1.6rem 2.2rem;
    box-shadow: 
        0 8px 0 var(--game-border),
        0 14px 28px rgba(29, 36, 64, 0.08);
    flex-wrap: wrap;
    gap: 1.25rem;
}

.title-pill-tag {
    display: inline-block;
    background: #FFF3EB;
    border: 2px solid #FF8D70;
    color: #D64627;
    font-size: 0.75rem;
    font-weight: 900;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    margin-bottom: 0.4rem;
}

.page-main-title {
    font-size: 2.1rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.35rem 0;
    line-height: 1.15;
}

.page-sub-desc {
    font-family: var(--font-ui);
    font-size: 0.95rem;
    font-weight: 700;
    color: #6C7594;
    margin: 0;
    max-width: 650px;
}

.btn-bonus-reward {
    background: linear-gradient(135deg, #FFDE59 0%, #FFB703 100%);
    color: #573F00;
    padding: 0.85rem 1.65rem;
    font-size: 0.98rem;
    border-color: #8A6400;
    box-shadow: 0 5px 0 #8A6400, 0 8px 18px rgba(255, 183, 3, 0.35);
}

.btn-bonus-reward:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 #8A6400, 0 12px 22px rgba(255, 183, 3, 0.45);
}

/* ==========================================================================
   TOP 3 CHAMPION PODIUM
   ========================================================================== */
.podium-section {
    width: 100%;
}

.podium-grid {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 1.5rem;
    max-width: 950px;
    margin: 0 auto;
    padding: 1.5rem 0 0.5rem 0;
    flex-wrap: wrap;
}

.podium-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 270px;
    position: relative;
}

.gold-halo-effect {
    position: absolute;
    top: -15px;
    width: 160px;
    height: 160px;
    background: radial-gradient(circle, rgba(255, 209, 102, 0.45) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
}

.podium-avatar-card {
    width: 100%;
    background: #FFFFFF;
    border: 3.5px solid var(--game-border);
    border-radius: 24px;
    padding: 1.4rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    box-shadow: 
        0 7px 0 var(--game-border),
        0 12px 24px rgba(29, 36, 64, 0.08);
    margin-bottom: 0.85rem;
    z-index: 1;
    box-sizing: border-box;
}

.podium-avatar-card.gold-card {
    border-color: #8A6400;
    background: linear-gradient(180deg, #FFFDF5 0%, #FFF9E6 100%);
    box-shadow: 
        0 8px 0 #8A6400,
        0 14px 28px rgba(138, 100, 0, 0.15);
    transform: scale(1.04);
}

.crown-badge {
    position: absolute;
    top: -16px;
    font-size: 1.7rem;
    line-height: 1;
    filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.15));
}

.gold-crown {
    font-size: 2.1rem;
    top: -20px;
    animation: bounceCrown 2s infinite ease-in-out alternate;
}

@keyframes bounceCrown {
    from { transform: translateY(0); }
    to { transform: translateY(-6px); }
}

.podium-avatar-bubble {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 3px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    margin: 0.4rem 0 0.65rem 0;
}

.gold-bubble {
    background: linear-gradient(135deg, #FFE885 0%, #FFB703 100%);
    border-color: #8A6400;
    box-shadow: inset 0 3px 6px rgba(255, 255, 255, 0.8);
}

.silver-bubble {
    background: linear-gradient(135deg, #F0F2F8 0%, #C8CDDE 100%);
    border-color: #4C5574;
}

.bronze-bubble {
    background: linear-gradient(135deg, #FFD2BD 0%, #E0855A 100%);
    border-color: #7A3F1F;
}

.podium-name {
    font-size: 1.15rem;
    font-weight: 900;
    color: var(--game-navy);
    line-height: 1.2;
    margin-bottom: 0.2rem;
}

.gold-name {
    color: #4A3500;
    font-size: 1.25rem;
}

.podium-badge-label {
    font-size: 0.72rem;
    font-weight: 900;
    color: #727B94;
    margin-bottom: 0.65rem;
}

.gold-badge-label {
    color: #8A6400;
}

.podium-stats-pill {
    display: inline-flex;
    gap: 0.65rem;
    background: #F1F3F9;
    border: 2px solid #D6DCEE;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 900;
    color: var(--game-navy);
    margin-bottom: 0.6rem;
}

.podium-stats-pill.gold-stats {
    background: #FFF2CC;
    border-color: #FFD166;
    color: #614600;
}

.podium-quote {
    font-family: var(--font-ui);
    font-size: 0.78rem;
    font-style: italic;
    color: #7B849C;
    margin: 0;
    line-height: 1.35;
}

/* 3D Podium Step Blocks */
.podium-block {
    width: 100%;
    border: 3.5px solid var(--game-border);
    border-radius: 20px 20px 10px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    text-shadow: 0 2px 0 rgba(0, 0, 0, 0.2);
    box-sizing: border-box;
}

.step-gold {
    height: 140px;
    background: linear-gradient(180deg, #FFD166 0%, #FFB703 100%);
    border-color: #8A6400;
    box-shadow: 0 10px 0 #735300;
}

.step-silver {
    height: 105px;
    background: linear-gradient(180deg, #D4D8E8 0%, #9DA5C2 100%);
    border-color: #4C5574;
    box-shadow: 0 8px 0 #394057;
}

.step-bronze {
    height: 80px;
    background: linear-gradient(180deg, #F0A584 0%, #C46E4A 100%);
    border-color: #7A3F1F;
    box-shadow: 0 8px 0 #5C2F17;
}

.podium-step-num {
    font-size: 3rem;
    font-weight: 900;
    line-height: 1;
}

.podium-step-label {
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    margin-top: 0.2rem;
}

/* ==========================================================================
   SEARCH & FILTERS CONTROL BAR
   ========================================================================== */
.controls-card {
    background: #FFFFFF;
    border: 3.5px solid var(--game-border);
    border-radius: 26px;
    padding: 1.25rem 1.65rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    flex-wrap: wrap;
    box-shadow: 
        0 6px 0 var(--game-border),
        0 10px 20px rgba(29, 36, 64, 0.06);
}

.search-box-pill {
    flex: 1;
    min-width: 240px;
    height: 46px;
    background: #F4F6FB;
    border: 2.5px solid var(--game-border);
    border-radius: 9999px;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    gap: 0.5rem;
}

.search-icon {
    font-size: 1.1rem;
    opacity: 0.6;
}

.search-input {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    font-family: var(--font-game);
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--game-navy);
}

.search-input::placeholder {
    color: #9AA3BA;
}

.filter-pills-row {
    display: flex;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.filter-pill-btn {
    background: #F1F3F9;
    border: 2px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.4rem 0.85rem;
    font-family: var(--font-game);
    font-size: 0.8rem;
    font-weight: 900;
    color: #586382;
    cursor: pointer;
    transition: all 0.15s ease;
}

.filter-pill-btn:hover {
    background: #FFFFFF;
    transform: translateY(-1px);
}

.filter-pill-btn.active {
    background: var(--game-blue);
    color: #FFFFFF;
    border-color: #0E5287;
    box-shadow: 0 3px 0 #0E5287;
}

.sort-selector-wrap {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.sort-lbl {
    font-size: 0.75rem;
    font-weight: 900;
    color: #7E87A0;
}

.sort-select {
    background: #F4F6FB;
    border: 2.5px solid var(--game-border);
    border-radius: 12px;
    padding: 0.45rem 0.85rem;
    font-family: var(--font-game);
    font-size: 0.85rem;
    font-weight: 900;
    color: var(--game-navy);
    cursor: pointer;
    outline: none;
}

/* ==========================================================================
   FULL GUILD LEADERBOARD ROSTER
   ========================================================================== */
.guild-roster-card {
    background: #FFFFFF;
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    box-shadow: 0 8px 0 var(--game-border);
    overflow: hidden;
}

.guild-roster-header {
    background: #F1F3F9;
    border-bottom: 3px solid var(--game-border);
    padding: 1rem 1.65rem;
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 900;
    color: #697494;
    letter-spacing: 0.05em;
}

.guild-adventurer-row {
    padding: 1.1rem 1.65rem;
    border-bottom: 2px solid #EAEFF8;
    display: flex;
    align-items: center;
    transition: background-color 0.15s ease;
}

.guild-adventurer-row:last-child {
    border-bottom: none;
}

.guild-adventurer-row:hover {
    background-color: #F8FAFD;
}

.col-rank { width: 65px; }
.col-adventurer { flex: 2.5; display: flex; align-items: center; gap: 0.9rem; }
.col-level { width: 100px; }
.col-realm { flex: 1.8; }
.col-streak { width: 100px; }
.col-stars { width: 110px; }
.col-actions { width: 150px; text-align: right; }

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #E8ECF5;
    border: 2.5px solid var(--game-border);
    font-size: 0.85rem;
    font-weight: 900;
    color: var(--game-navy);
}

.adventurer-avatar-box {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #F4F6FB;
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    box-shadow: 0 3px 0 var(--game-border);
}

.adventurer-meta {
    display: flex;
    flex-direction: column;
}

.adventurer-name {
    font-size: 1.05rem;
    font-weight: 900;
    color: var(--game-navy);
}

.adventurer-honor-tag {
    font-size: 0.72rem;
    font-weight: 800;
    color: #8C96B0;
}

.level-pill-badge {
    background: #E8F4FD;
    color: #1579C4;
    border: 2px solid #2395EB;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 900;
}

.realm-name-tag {
    font-size: 0.88rem;
    font-weight: 800;
    color: #4C5574;
}

.streak-pill {
    background: #FFF1EE;
    color: #D94423;
    border: 1.5px solid #FF8D70;
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 900;
}

.star-rating-val {
    font-size: 1.05rem;
    font-weight: 900;
    color: #B88500;
}

.btn-award-kudos {
    background: #FFFFFF;
    color: var(--game-navy);
    padding: 0.5rem 0.95rem;
    font-size: 0.82rem;
    box-shadow: 0 3px 0 var(--game-border);
}

.btn-award-kudos:hover {
    background: #FFF9ED;
    border-color: #FFA800;
    transform: translateY(-1px);
}

.empty-roster-state {
    padding: 3.5rem 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 0.75rem;
}

.empty-title {
    font-size: 1.35rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.35rem 0;
}

.empty-desc {
    font-family: var(--font-ui);
    color: #727B94;
    font-weight: 700;
    font-size: 0.9rem;
    margin: 0;
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

    .podium-grid {
        align-items: center;
    }

    .podium-column {
        width: 100%;
        max-width: 320px;
    }

    .step-gold, .step-silver, .step-bronze {
        height: 60px;
    }

    .col-realm, .col-streak {
        display: none;
    }

    .bg-asset {
        display: none;
    }
}
</style>
