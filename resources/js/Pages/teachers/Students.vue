<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Components/TeacherLayout.vue';

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
    selected_classroom_id: {
        type: Number,
        default: null,
    },
    students: {
        type: Array,
        default: () => [],
    },
    pending_grading_count: {
        type: Number,
        default: 0,
    },
});

const handleLogout = () => {
    router.post('/logout');
};

const goToDashboard = () => {
    router.visit('/teachers/dashboard');
};

const goToClasses = () => {
    router.visit('/teachers/classes');
};

const selectedClassId = ref(props.selected_classroom_id);

const filterByClass = (classId) => {
    selectedClassId.value = classId;
    router.visit(classId ? `/teachers/students?class_id=${classId}` : '/teachers/students', {
        preserveState: true,
        preserveScroll: true,
    });
};

// Search & Filter State
const searchQuery = ref('');
const selectedKingdom = ref('all');
const sortBy = ref('stars'); // 'stars' | 'level' | 'streak'
const kudosCount = ref({});

// Dynamic Student Adventurers Data sourced from database
const allStudents = computed(() => {
    if (Array.isArray(props.students) && props.students.length > 0) {
        const avatars = ['🦊', '🦁', '🐼', '🐯', '🐰', '🐻', '🦉'];
        return props.students.map((s, idx) => {
            return {
                id: s.id,
                rank: s.rank || idx + 1,
                name: s.name,
                level: Number(s.level) || 1,
                kingdom: s.classroom_name || 'Classroom Adventurer',
                kingdomIcon: '🏫',
                stars: Number(s.stars) || 0,
                xp: Number(s.xp) || 0,
                streak: 1,
                booksRead: Number(s.lectures_completed) || 0,
                badge: s.badge || (idx === 0 ? 'Guild Grand Champion' : idx === 1 ? 'Master Storyteller' : idx === 2 ? 'Syntax Wizard' : 'Explorer'),
                avatar: avatars[idx % avatars.length],
                podiumColor: idx === 0 ? '#FFD166' : idx === 1 ? '#E0E2EC' : '#FFC09F',
                quote: s.classroom_grade || 'Reading adventure in progress!',
            };
        });
    }
    return [];
});

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

    <TeacherLayout
        :user="user"
        active-nav="students"
        :pending-grading-count="pending_grading_count"
    >
        <!-- CLASSROOM / ROOM SELECTOR PILLS -->
            <div class="room-selector-strip">
                <span class="room-selector-label">ROOM:</span>
                <button
                    type="button"
                    class="room-filter-pill"
                    :class="{ active: !selectedClassId }"
                    @click="filterByClass(null)"
                >
                    <span>🏫 All My Students ({{ props.students.length }})</span>
                </button>
                <button
                    v-for="c in classrooms"
                    :key="c.id"
                    type="button"
                    class="room-filter-pill"
                    :class="{ active: selectedClassId === c.id }"
                    @click="filterByClass(c.id)"
                >
                    <span>{{ c.icon }} {{ c.name }} ({{ c.students_count }})</span>
                </button>
                <button
                    type="button"
                    class="room-manage-link"
                    @click="goToClasses"
                >
                    <span>⚙️ Manage Rooms</span>
                </button>
            </div>

            <!-- Empty State if No Students Yet in Selected Room -->
            <div v-if="allStudents.length === 0" class="empty-classroom-students-card">
                <div class="empty-badge">👥</div>
                <h3 class="empty-title">No Students in this Room Yet</h3>
                <p class="empty-desc">
                    Share your 6-digit room access code with your students so they can join this reading room and appear on the leaderboard!
                </p>
                <button type="button" class="btn-goto-classes" @click="goToClasses">
                    <span>🗝️ View Room Access Codes</span>
                </button>
            </div>

            <!-- 3D CARTOON TOP 3 WINNERS PODIUM -->
            <section v-if="topThree.length > 0" class="podium-section" aria-label="Top 3 Champion Podium">
                <div class="podium-grid">
                    <!-- Rank #2: Silver Stand (Left) -->
                    <div v-if="topThree[1]" class="podium-column rank-2-col">
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
                    <div v-if="topThree[0]" class="podium-column rank-1-col">
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
                    <div v-if="topThree[2]" class="podium-column rank-3-col">
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
    </TeacherLayout>
</template>

<style scoped>
/* ==========================================================================
   CARTOON 3D + NEUMORPHISM GAME THEME TOKENS
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

    /* Neumorphic Tactile Lighting Tokens */
    --neu-bg-base: #F5F0E3;
    --neu-surface: linear-gradient(145deg, #FFFFFF 0%, #F3EFE4 100%);
    --neu-light: rgba(255, 255, 255, 0.92);
    --neu-shadow: rgba(196, 187, 168, 0.55);
    --neu-navy-ambient: rgba(29, 36, 64, 0.12);
    --neu-bevel-light: inset 3px 3px 6px rgba(255, 255, 255, 0.95);
    --neu-bevel-dark: inset -4px -4px 8px rgba(194, 185, 166, 0.38);

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
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s var(--spring-bounce);
    outline: none;
    user-select: none;
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
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    padding: 0.1rem 0.7rem;
    box-shadow:
        0 8px 0 var(--game-border),
        -6px -6px 14px var(--neu-light),
        10px 14px 28px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    flex-wrap: wrap;
    gap: 1.25rem;
}

.title-pill-tag {
    display: inline-block;
    background: linear-gradient(145deg, #FFF6EE 0%, #FFE6D6 100%);
    border: 2px solid #FF8D70;
    color: #D64627;
    font-size: 0.75rem;
    font-weight: 900;
    padding: 0.22rem 0.75rem;
    border-radius: 9999px;
    margin-bottom: 0.4rem;
    box-shadow:
        inset 1px 1px 3px rgba(255, 255, 255, 0.9),
        inset -1px -1px 3px rgba(214, 70, 39, 0.18),
        0 2px 0 rgba(255, 141, 112, 0.35);
}

.page-main-title {
    font-size: 2.1rem;
    font-weight: 900;
    color: var(--game-navy);
    margin: 0 0 0.35rem 0;
    line-height: 1.15;
    text-shadow: 1px 2px 0 rgba(255, 255, 255, 0.85);
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
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #573F00;
    padding: 0.85rem 1.65rem;
    font-size: 0.98rem;
    border-color: #8A6400;
    box-shadow:
        0 5px 0 #8A6400,
        0 8px 18px rgba(255, 183, 3, 0.35),
        inset 2px 2px 5px rgba(255, 255, 255, 0.8),
        inset -2px -3px 5px rgba(184, 133, 0, 0.35);
}

.btn-bonus-reward:hover {
    transform: translateY(-2px);
    box-shadow:
        0 7px 0 #8A6400,
        0 12px 22px rgba(255, 183, 3, 0.45),
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -3px 5px rgba(184, 133, 0, 0.35);
}

/* ==========================================================================
   TOP 3 CHAMPION PODIUM
   ========================================================================== */
/* Classroom / Room Selector Strip */
.room-selector-strip {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    background: rgba(255, 255, 255, 0.7);
    padding: 0.65rem 1rem;
    border-radius: 20px;
    border: 2px solid #E2E8F0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
}

.room-selector-label {
    font-family: var(--font-game);
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
    font-family: var(--font-game);
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
    font-family: var(--font-game);
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
}

.empty-classroom-students-card {
    text-align: center;
    background: #FFFFFF;
    border: 3px dashed #CBD5E1;
    border-radius: 26px;
    padding: 3rem 1.5rem;
    margin: 2rem 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.empty-classroom-students-card .empty-badge {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

.empty-classroom-students-card .empty-title {
    font-family: var(--font-game);
    font-size: 1.4rem;
    font-weight: 900;
    color: #0F172A;
    margin: 0;
}

.empty-classroom-students-card .empty-desc {
    color: #64748B;
    max-width: 440px;
    margin: 0.5rem 0 1.25rem 0;
    font-size: 0.9rem;
}

.btn-goto-classes {
    padding: 0.6rem 1.25rem;
    border-radius: 999px;
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%);
    color: #FFFFFF;
    border: 2px solid #312E81;
    font-family: var(--font-game);
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 4px 0 #312E81;
}

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
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 26px;
    padding: 1.4rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    box-shadow:
        0 7px 0 var(--game-border),
        -5px -5px 12px var(--neu-light),
        8px 14px 24px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    margin-bottom: 0.85rem;
    z-index: 1;
    box-sizing: border-box;
}

.podium-avatar-card.gold-card {
    border-color: #8A6400;
    background: linear-gradient(155deg, #FFFDF7 0%, #FFF3C4 100%);
    box-shadow:
        0 8px 0 #8A6400,
        -6px -6px 14px rgba(255, 255, 255, 0.95),
        0 16px 30px rgba(138, 100, 0, 0.18),
        inset 3px 3px 6px rgba(255, 255, 255, 0.95),
        inset -3px -3px 7px rgba(217, 155, 0, 0.25);
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
    width: 74px;
    height: 74px;
    border-radius: 50%;
    border: 3px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    margin: 0.4rem 0 0.65rem 0;
}

.gold-bubble {
    background: linear-gradient(145deg, #FFF099 0%, #FFB703 100%);
    border-color: #8A6400;
    box-shadow:
        0 3px 0 #8A6400,
        inset 3px 3px 6px rgba(255, 255, 255, 0.9),
        inset -3px -3px 6px rgba(184, 120, 0, 0.4);
}

.silver-bubble {
    background: linear-gradient(145deg, #F5F7FC 0%, #C8CDDE 100%);
    border-color: #4C5574;
    box-shadow:
        0 3px 0 #4C5574,
        inset 3px 3px 6px rgba(255, 255, 255, 0.9),
        inset -3px -3px 6px rgba(76, 85, 116, 0.35);
}

.bronze-bubble {
    background: linear-gradient(145deg, #FFE0CF 0%, #E0855A 100%);
    border-color: #7A3F1F;
    box-shadow:
        0 3px 0 #7A3F1F,
        inset 3px 3px 6px rgba(255, 255, 255, 0.85),
        inset -3px -3px 6px rgba(122, 63, 31, 0.4);
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

/* Recessed Neumorphic Podium Stats Pill */
.podium-stats-pill {
    display: inline-flex;
    gap: 0.65rem;
    background: #EDF0F8;
    border: 2px solid rgba(43, 51, 84, 0.14);
    padding: 0.3rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 900;
    color: var(--game-navy);
    margin-bottom: 0.6rem;
    box-shadow:
        inset 2px 2px 5px rgba(163, 174, 198, 0.5),
        inset -2px -2px 5px rgba(255, 255, 255, 0.95);
}

.podium-stats-pill.gold-stats {
    background: #FFF0BD;
    border-color: rgba(217, 155, 0, 0.35);
    color: #614600;
    box-shadow:
        inset 2px 2px 5px rgba(217, 155, 0, 0.3),
        inset -2px -2px 5px rgba(255, 255, 255, 0.9);
}

.podium-quote {
    font-family: var(--font-ui);
    font-size: 0.78rem;
    font-style: italic;
    color: #7B849C;
    margin: 0;
    line-height: 1.35;
}

/* 3D + Neumorphic Podium Step Blocks */
.podium-block {
    width: 100%;
    border: 3.5px solid var(--game-border);
    border-radius: 22px 22px 12px 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    text-shadow: 0 2px 0 rgba(0, 0, 0, 0.22);
    box-sizing: border-box;
}

.step-gold {
    height: 140px;
    background: linear-gradient(160deg, #FFE17D 0%, #FFB703 100%);
    border-color: #8A6400;
    box-shadow:
        0 10px 0 #735300,
        inset 3px 3px 7px rgba(255, 255, 255, 0.75),
        inset -4px -4px 8px rgba(138, 100, 0, 0.35);
}

.step-silver {
    height: 105px;
    background: linear-gradient(160deg, #E2E6F3 0%, #9DA5C2 100%);
    border-color: #4C5574;
    box-shadow:
        0 8px 0 #394057,
        inset 3px 3px 7px rgba(255, 255, 255, 0.8),
        inset -4px -4px 8px rgba(57, 64, 87, 0.35);
}

.step-bronze {
    height: 80px;
    background: linear-gradient(160deg, #F5B89C 0%, #C46E4A 100%);
    border-color: #7A3F1F;
    box-shadow:
        0 8px 0 #5C2F17,
        inset 3px 3px 7px rgba(255, 255, 255, 0.65),
        inset -4px -4px 8px rgba(92, 47, 23, 0.4);
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


/* Recessed Neumorphic Search Input Well */
.search-box-pill {
    flex: 1;
    min-width: 240px;
    height: 46px;
    background: #EDF1FA;
    border: 2.5px solid var(--game-border);
    border-radius: 9999px;
    display: flex;
    align-items: center;
    padding: 0 1.05rem;
    gap: 0.5rem;
    box-shadow:
        inset 3px 3px 6px rgba(156, 168, 196, 0.55),
        inset -3px -3px 6px rgba(255, 255, 255, 0.95);
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
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-pill-btn {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    border: 2px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.42rem 0.9rem;
    font-family: var(--font-game);
    font-size: 0.8rem;
    font-weight: 900;
    color: #586382;
    cursor: pointer;
    box-shadow:
        0 2px 0 var(--game-border),
        inset 1px 1px 3px rgba(255, 255, 255, 0.95),
        inset -1px -1px 3px rgba(163, 174, 198, 0.4);
    transition: all 0.15s var(--spring-bounce);
}

.filter-pill-btn:hover {
    transform: translateY(-2px);
}

.filter-pill-btn.active {
    background: linear-gradient(145deg, #38A3F5 0%, #1579C4 100%);
    color: #FFFFFF;
    border-color: #0E5287;
    box-shadow:
        0 3px 0 #0E5287,
        inset 2px 2px 4px rgba(255, 255, 255, 0.55),
        inset -2px -2px 4px rgba(14, 82, 135, 0.45);
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
    background: linear-gradient(145deg, #FFFFFF 0%, #EDF1FA 100%);
    border: 2.5px solid var(--game-border);
    border-radius: 14px;
    padding: 0.45rem 0.85rem;
    font-family: var(--font-game);
    font-size: 0.85rem;
    font-weight: 900;
    color: var(--game-navy);
    cursor: pointer;
    outline: none;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.9);
}

/* ==========================================================================
   FULL GUILD LEADERBOARD ROSTER
   ========================================================================== */
.guild-roster-card {
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 28px;
    box-shadow:
        0 8px 0 var(--game-border),
        -6px -6px 14px var(--neu-light),
        10px 16px 28px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    overflow: hidden;
}

.guild-roster-header {
    background: linear-gradient(180deg, #EEF2FA 0%, #E3E9F5 100%);
    border-bottom: 3px solid var(--game-border);
    padding: 1rem 1.65rem;
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 900;
    color: #697494;
    letter-spacing: 0.05em;
    box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.85);
}

.guild-adventurer-row {
    padding: 1.1rem 1.65rem;
    border-bottom: 2px solid rgba(226, 232, 245, 0.8);
    display: flex;
    align-items: center;
    transition: background-color 0.15s ease;
}

.guild-adventurer-row:last-child {
    border-bottom: none;
}

.guild-adventurer-row:hover {
    background-color: rgba(255, 255, 255, 0.75);
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
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(145deg, #F5F7FC 0%, #E2E7F4 100%);
    border: 2.5px solid var(--game-border);
    font-size: 0.85rem;
    font-weight: 900;
    color: var(--game-navy);
    box-shadow:
        0 2px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.9),
        inset -2px -2px 4px rgba(163, 174, 198, 0.45);
}

.adventurer-avatar-box {
    width: 50px;
    height: 50px;
    border-radius: 16px;
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF9 100%);
    border: 2.5px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 5px rgba(255, 255, 255, 0.95),
        inset -2px -2px 5px rgba(163, 174, 198, 0.4);
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
    background: linear-gradient(145deg, #F0F8FF 0%, #D9EEFD 100%);
    color: #1579C4;
    border: 2px solid #2395EB;
    padding: 0.22rem 0.68rem;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 900;
    box-shadow:
        inset 1px 1px 3px rgba(255, 255, 255, 0.9),
        0 2px 0 rgba(35, 149, 235, 0.25);
}

.realm-name-tag {
    font-size: 0.88rem;
    font-weight: 800;
    color: #4C5574;
}

.streak-pill {
    background: linear-gradient(145deg, #FFF5F2 0%, #FFE5DE 100%);
    color: #D94423;
    border: 1.5px solid #FF8D70;
    padding: 0.22rem 0.65rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 900;
    box-shadow:
        inset 1px 1px 3px rgba(255, 255, 255, 0.9),
        inset -1px -1px 3px rgba(217, 68, 35, 0.15);
}

.star-rating-val {
    font-size: 1.05rem;
    font-weight: 900;
    color: #B88500;
}

.btn-award-kudos {
    background: linear-gradient(145deg, #FFFFFF 0%, #F0F3FA 100%);
    color: var(--game-navy);
    padding: 0.5rem 0.95rem;
    font-size: 0.82rem;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(163, 174, 198, 0.35);
}

.btn-award-kudos:hover {
    background: linear-gradient(145deg, #FFFDF7 0%, #FFF0C2 100%);
    border-color: #FFA800;
    transform: translateY(-2px);
    box-shadow:
        0 5px 0 #B88500,
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
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
   RESPONSIVE DESIGN
   ========================================================================== */
@media (max-width: 900px) {
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
