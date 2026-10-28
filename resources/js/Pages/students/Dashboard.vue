<script setup>
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student',
            email: 'student@example.com',
            role: 'student',
        }),
    },
});

const handleLogout = () => {
    router.post('/logout');
};

const stats = [
    { label: 'Books Read', value: '14', icon: '📖', color: '#2395EB', bg: '#EBF5FF' },
    { label: 'Day Streak', value: '5 🔥', icon: '⚡', color: '#FF6B4A', bg: '#FFF0ED' },
    { label: 'Reading Stars', value: '280', icon: '⭐', color: '#FFB703', bg: '#FFF8E6' },
    { label: 'Badges Earned', value: '8', icon: '🏆', color: '#06D6A0', bg: '#E6FAF5' },
];

const lessons = [
    { title: 'The Magic of Forest Tales', author: 'Module 1', progress: 100, status: 'Completed', color: '#06D6A0' },
    { title: 'Journey to the Stars & Planets', author: 'Module 2', progress: 65, status: 'In Progress', color: '#2395EB' },
    { title: 'Underwater Explorers & Ocean Life', author: 'Module 3', progress: 20, status: 'Started', color: '#FF6B4A' },
    { title: 'Dinosaur Mysteries Unlocked', author: 'Module 4', progress: 0, status: 'Locked', color: '#A1A8BD' },
];
</script>

<template>
    <Head title="Student Dashboard • WEBREAD" />

    <div class="dashboard-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Top Navigation Bar -->
        <nav class="nav-bar">
            <div class="nav-brand">
                <span class="brand-title">
                    <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                </span>
                <span class="portal-badge student-badge">Student Portal</span>
            </div>

            <div class="nav-user">
                <div class="user-info">
                    <span class="user-name">{{ user.name }}</span>
                    <span class="user-role-pill">Student</span>
                </div>
                <button type="button" class="btn-logout" @click="handleLogout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="dashboard-container">
            <!-- Hero Welcome Card -->
            <section class="hero-card">
                <div class="hero-text">
                    <span class="hero-pill">✨ Learning Adventure</span>
                    <h1 class="hero-title">Welcome, {{ user.name }}! 👋</h1>
                    <p class="hero-sub">
                        Pick up right where you left off. Complete your daily reading quest to unlock more star rewards!
                    </p>
                </div>
                <div class="hero-actions">
                    <button type="button" class="btn-action primary-btn">🚀 Continue Reading</button>
                    <button type="button" class="btn-action secondary-btn">⭐ View Badges</button>
                </div>
            </section>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <div
                    v-for="(stat, index) in stats"
                    :key="index"
                    class="stat-card"
                    :style="{ borderTopColor: stat.color }"
                >
                    <div class="stat-icon" :style="{ backgroundColor: stat.bg }">
                        <span>{{ stat.icon }}</span>
                    </div>
                    <div class="stat-content">
                        <span class="stat-val" :style="{ color: stat.color }">{{ stat.value }}</span>
                        <span class="stat-lbl">{{ stat.label }}</span>
                    </div>
                </div>
            </section>

            <!-- Reading Modules / Lessons Grid -->
            <section class="section-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">My Reading Adventures</h2>
                        <p class="section-sub">Interactive books and reading challenges waiting for you</p>
                    </div>
                    <span class="lesson-count-badge">{{ lessons.length }} Modules</span>
                </div>

                <div class="lessons-grid">
                    <article v-for="(lesson, index) in lessons" :key="index" class="lesson-card">
                        <div class="lesson-top">
                            <span class="author-pill">{{ lesson.author }}</span>
                            <span
                                class="status-pill"
                                :class="lesson.status.toLowerCase().replace(' ', '-')"
                            >
                                {{ lesson.status }}
                            </span>
                        </div>
                        <h3 class="lesson-title">{{ lesson.title }}</h3>
                        <div class="progress-info">
                            <span>Progress</span>
                            <span class="progress-val">{{ lesson.progress }}%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div
                                class="progress-bar-fill"
                                :style="{
                                    width: `${lesson.progress}%`,
                                    backgroundColor: lesson.color,
                                }"
                            ></div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.dashboard-page {
    --brand-blue: #2395EB;
    --brand-coral: #FF6B4A;
    --brand-navy: #1D2A68;
    --font-heading: 'Fredoka', -apple-system, sans-serif;
    --font-body: 'Nunito', -apple-system, sans-serif;

    min-height: 100vh;
    background-color: #FFFDF7;
    background-image: 
        radial-gradient(#E8E5DC 1.4px, transparent 1.4px),
        radial-gradient(#E8E5DC 1.4px, #FFFDF7 1.4px);
    background-size: 32px 32px;
    background-position: 0 0, 16px 16px;
    font-family: var(--font-body);
    color: #242E5E;
    padding-bottom: 3rem;
}

.nav-bar {
    background: #FFFFFF;
    border-bottom: 2px solid #EFECE4;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 20;
    box-shadow: 0 4px 16px rgba(29, 42, 104, 0.04);
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.brand-title {
    font-family: var(--font-heading);
    font-size: 1.85rem;
    font-weight: 900;
    letter-spacing: -0.02em;
}

.brand-web {
    color: var(--brand-blue);
}

.brand-read {
    color: var(--brand-coral);
}

.portal-badge {
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    letter-spacing: 0.03em;
}

.student-badge {
    background: #EBF5FF;
    color: var(--brand-blue);
    border: 1.5px solid #C5E2FA;
}

.nav-user {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.user-name {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.98rem;
    color: var(--brand-navy);
}

.user-role-pill {
    font-size: 0.72rem;
    font-weight: 800;
    color: #06D6A0;
    background: #E6FAF5;
    padding: 0.1rem 0.5rem;
    border-radius: 6px;
}

.btn-logout {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #FFF2F2;
    color: #E03131;
    border: 1.5px solid #FFD8D8;
    padding: 0.45rem 0.9rem;
    border-radius: 12px;
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-logout:hover {
    background: #FFE3E3;
    transform: translateY(-1px);
}

.dashboard-container {
    max-width: 1140px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
}

.hero-card {
    background: linear-gradient(135deg, #2395EB 0%, #1768A8 100%);
    border-radius: 24px;
    padding: 2.5rem;
    color: #FFFFFF;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 12px 32px rgba(35, 149, 235, 0.2);
    flex-wrap: wrap;
    gap: 1.5rem;
}

.hero-pill {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 0.82rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    margin-bottom: 0.75rem;
}

.hero-title {
    font-family: var(--font-heading);
    font-size: 2.2rem;
    font-weight: 900;
    margin: 0 0 0.5rem 0;
    line-height: 1.15;
}

.hero-sub {
    margin: 0;
    font-size: 1rem;
    opacity: 0.92;
    max-width: 580px;
    line-height: 1.5;
}

.hero-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-action {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.92rem;
    padding: 0.75rem 1.4rem;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    border: none;
}

.primary-btn {
    background: linear-gradient(135deg, #FF6584 0%, #FF7B54 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(255, 107, 74, 0.35);
}

.primary-btn:hover {
    transform: translateY(-2px) scale(1.02);
}

.secondary-btn {
    background: rgba(255, 255, 255, 0.2);
    color: #FFFFFF;
    border: 1.5px solid rgba(255, 255, 255, 0.35);
}

.secondary-btn:hover {
    background: rgba(255, 255, 255, 0.28);
    transform: translateY(-2px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
}

.stat-card {
    background: #FFFFFF;
    border-radius: 18px;
    padding: 1.35rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-top: 5px solid;
    border: 1.5px solid #EFECE4;
    border-top-width: 5px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-val {
    font-family: var(--font-heading);
    font-size: 1.75rem;
    font-weight: 900;
    line-height: 1;
}

.stat-lbl {
    font-size: 0.85rem;
    font-weight: 700;
    color: #7A8098;
    margin-top: 0.25rem;
}

.section-card {
    background: #FFFFFF;
    border-radius: 22px;
    padding: 2rem;
    border: 1.5px solid #EFECE4;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-title {
    font-family: var(--font-heading);
    font-size: 1.45rem;
    font-weight: 900;
    color: var(--brand-navy);
    margin: 0;
}

.section-sub {
    margin: 0.25rem 0 0 0;
    color: #7A8098;
    font-size: 0.92rem;
}

.lesson-count-badge {
    background: #EBF5FF;
    color: var(--brand-blue);
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.85rem;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
}

.lessons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.25rem;
}

.lesson-card {
    background: #FAFAFC;
    border: 1.5px solid #E9EAF0;
    border-radius: 16px;
    padding: 1.4rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    transition: all 0.2s ease;
}

.lesson-card:hover {
    border-color: #2395EB;
    background: #FFFFFF;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(35, 149, 235, 0.08);
}

.lesson-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.author-pill {
    font-size: 0.72rem;
    font-weight: 800;
    color: #6C5CE7;
    background: #F2F0FD;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
}

.status-pill {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
}

.status-pill.completed {
    color: #06D6A0;
    background: #E6FAF5;
}

.status-pill.in-progress {
    color: #2395EB;
    background: #EBF5FF;
}

.status-pill.started {
    color: #FF6B4A;
    background: #FFF0ED;
}

.status-pill.locked {
    color: #A1A8BD;
    background: #EFF0F5;
}

.lesson-title {
    font-family: var(--font-heading);
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--brand-navy);
    margin: 0;
    line-height: 1.25;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #697086;
    font-weight: 700;
}

.progress-val {
    color: var(--brand-navy);
    font-weight: 800;
}

.progress-bar-bg {
    width: 100%;
    height: 8px;
    background: #E8EAF0;
    border-radius: 9999px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.4s ease;
}
</style>
