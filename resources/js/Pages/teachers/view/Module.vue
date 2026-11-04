<script setup>
import { Head, router } from '@inertiajs/vue3';
import ReadingJourneyMap from '@/Components/ReadingJourneyMap.vue';

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
    module: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Conflict and Community Values',
            slug: 'conflict-and-community-values',
            description: 'Journey through historical archipelagos, resolve narrative tensions, and explore the deep power of Bayanihan.',
            grade_level: 'Grade 3 • Reading Legends',
            theme: 'forest',
            icon: '🌲',
            cover_image_url: null,
            xp_bonus: 1250,
            boss_name: 'Grammar Goblin',
            max_students: 35,
            status: 'published',
        }),
    },
    lectures: {
        type: Array,
        default: () => [],
    },
});

const handleBack = () => {
    router.visit('/teachers/dashboard');
};

const handleLogout = () => {
    router.post('/logout');
};

const goToCreateLecture = () => {
    router.visit(`/teachers/modules/${props.module.slug}/lectures/create`);
};
</script>

<template>
    <Head :title="`${module.title} • Reading Journey Map`" />

    <div class="module-view-page">
        <!-- Top Game Header Bar matching Dashboard conventions -->
        <header class="module-header-nav">
            <div class="nav-left">
                <button type="button" class="btn-back-nav" @click="handleBack">
                    <span class="back-arrow">❮</span>
                    <span>Dashboard</span>
                </button>

                <div class="brand-group">
                    <span class="brand-text">
                        <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                    </span>
                    <span class="nav-badge">Island Explorer</span>
                </div>
            </div>

            <div class="nav-center-module">
                <span class="module-icon-pill">{{ module.icon }}</span>
                <span class="module-nav-title">{{ module.title }}</span>
                <span class="module-lecture-count">{{ lectures.length }} Lectures</span>
            </div>

            <div class="nav-right">
                <!-- + Add Lecture Action Button -->
                <button
                    type="button"
                    class="btn-nav-create-lecture"
                    @click="goToCreateLecture"
                >
                    <span>✨ + CREATE LECTURE</span>
                </button>

                <div class="user-chip">
                    <span class="user-avatar">🧙‍♂️</span>
                    <span class="user-name">{{ user.name }}</span>
                </div>

                <button type="button" class="btn-logout-mini" @click="handleLogout" title="Log Out">
                    <span>Exit</span>
                </button>
            </div>
        </header>

        <!-- Embedded Vertical Gamified Reading Journey Map with Database Lectures -->
        <ReadingJourneyMap
            :module="module"
            :lectures="lectures"
            :module-title="module.title"
            :module-grade="module.grade_level"
            :total-xp="module.xp_bonus || 1250"
        />
    </div>
</template>

<style scoped>
.module-view-page {
    min-height: 100vh;
    background: #e0f2fe;
    font-family: 'Nunito', system-ui, sans-serif;
}

.module-header-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1.75rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    border-bottom: 2px solid rgba(226, 232, 240, 0.8);
    position: sticky;
    top: 0;
    z-index: 50;
    gap: 1rem;
}

.nav-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.btn-back-nav {
    background: #f1f5f9;
    border: 1.5px solid #e2e8f0;
    border-radius: 9999px;
    padding: 0.4rem 0.9rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    color: #334155;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}

.btn-back-nav:hover {
    background: #e2e8f0;
    transform: translateX(-2px);
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.brand-text {
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 1.35rem;
    letter-spacing: -0.02em;
}

.brand-web { color: #2395eb; }
.brand-read { color: #ff6b4a; }

.nav-badge {
    background: #fef3c7;
    color: #b45309;
    font-family: 'Fredoka', sans-serif;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
}

.nav-center-module {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    max-width: 360px;
}

.module-icon-pill {
    font-size: 1.1rem;
}

.module-nav-title {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.88rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.module-lecture-count {
    background: #e0f2fe;
    color: #0369a1;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.72rem;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-nav-create-lecture {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #ffffff;
    border: none;
    border-radius: 9999px;
    padding: 0.45rem 1rem;
    font-family: 'Fredoka', sans-serif;
    font-weight: 900;
    font-size: 0.78rem;
    box-shadow: 0 3px 0 #9a3412, 0 4px 8px rgba(234, 88, 12, 0.3);
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-nav-create-lecture:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 0 #9a3412, 0 6px 12px rgba(234, 88, 12, 0.4);
}

.btn-nav-create-lecture:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #9a3412;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: #f1f5f9;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
}

.user-name {
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    color: #1e293b;
}

.btn-logout-mini {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-family: 'Fredoka', sans-serif;
    font-weight: 800;
    font-size: 0.78rem;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-logout-mini:hover {
    background: #fca5a5;
}

@media (max-width: 900px) {
    .nav-center-module {
        display: none;
    }
}

@media (max-width: 640px) {
    .brand-group {
        display: none;
    }

    .btn-nav-create-lecture span {
        font-size: 0.72rem;
    }
}
</style>
