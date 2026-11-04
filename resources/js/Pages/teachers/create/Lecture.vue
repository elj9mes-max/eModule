<script setup>
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

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
            icon: '📘',
            theme: 'forest',
            grade_level: 'Grade 3 • Reading Legends',
            next_sort_order: 1,
        }),
    },
});

const xpPresets = [100, 150, 200, 250, 500];

const form = useForm({
    title: '',
    description: '',
    body: '',
    xp_reward: 150,
    is_published: true,
});

const previewTitle = computed(() => form.title.trim() || 'Untitled Reading Milestone');
const previewDesc = computed(() => form.description.trim() || 'Students will explore reading passages, vocabulary terms, and interactive storytelling.');
const previewXp = computed(() => Number(form.xp_reward) || 150);

const submitLecture = () => {
    form.post(`/teachers/modules/${props.module.slug}/lectures`);
};

const goToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head :title="`Create Lecture • ${module.title}`" />

    <div class="create-lecture-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Ambient Floating Decorative Elements -->
        <div class="floating-bg-assets" aria-hidden="true">
            <img src="/images/book_left_trees_mountains.png" alt="" class="bg-asset bg-book-left" />
            <img src="/images/book_right_mountains_river.png" alt="" class="bg-asset bg-book-right" />
            <div class="floating-sparkle sp-1">✨</div>
            <div class="floating-sparkle sp-2">⭐</div>
            <div class="floating-sparkle sp-3">📜</div>
        </div>

        <!-- Top Navigation Bar -->
        <header class="top-nav-bar">
            <div class="nav-left">
                <button type="button" class="btn-3d-back" @click="goToModule">
                    <span class="back-arrow">❮</span>
                    <span>Back to Journey Map</span>
                </button>

                <div class="brand-group">
                    <span class="brand-text">
                        <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                    </span>
                    <span class="nav-badge">Lecture Builder</span>
                </div>
            </div>

            <div class="nav-module-chip">
                <span class="chip-icon">{{ module.icon }}</span>
                <span class="chip-title">{{ module.title }}</span>
            </div>

            <div class="nav-right">
                <div class="teacher-chip">
                    <span>🧙‍♂️</span>
                    <span>{{ user.name }}</span>
                </div>
                <button type="button" class="btn-exit-mini" @click="handleLogout">
                    Exit
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="lecture-builder-stage">
            <div class="builder-header-block">
                <span class="banner-pill">🗺️ STEP-CLUSTER GENERATOR</span>
                <h1 class="builder-title">Create New Reading Lecture</h1>
                <p class="builder-subtitle">
                    Add a new milestone node to <strong>{{ module.title }}</strong>'s vertical adventure trail. Students will journey here, earn XP, and unlock story treasures!
                </p>
            </div>

            <div class="builder-grid">
                <!-- FORM CARD (LEFT COLUMN) -->
                <section class="neu-card form-card">
                    <form @submit.prevent="submitLecture" class="lecture-form">
                        <!-- Step Order Badge -->
                        <div class="form-kicker-row">
                            <span class="step-num-pill">
                                📍 Milestone Step #{{ module.next_sort_order }}
                            </span>
                            <span class="module-link-text">Part of {{ module.title }}</span>
                        </div>

                        <!-- Lecture Title -->
                        <div class="form-group">
                            <label for="lecture-title" class="form-label">
                                <span>Lecture Title</span>
                                <span class="required-star">*</span>
                            </label>
                            <input
                                id="lecture-title"
                                v-model="form.title"
                                type="text"
                                class="neu-input"
                                placeholder="e.g., Short Discussion: Understanding Conflict in Narrative Texts"
                                required
                            />
                            <p v-if="form.errors.title" class="error-msg">{{ form.errors.title }}</p>
                        </div>

                        <!-- Short Description / Summary -->
                        <div class="form-group">
                            <label for="lecture-desc" class="form-label">
                                <span>Short Objective / Summary</span>
                            </label>
                            <input
                                id="lecture-desc"
                                v-model="form.description"
                                type="text"
                                class="neu-input"
                                placeholder="Brief 1-2 sentence preview for the S-curve speech card callout"
                                maxlength="2000"
                            />
                            <p v-if="form.errors.description" class="error-msg">{{ form.errors.description }}</p>
                        </div>

                        <!-- Lecture Body / Rich Text Guide -->
                        <div class="form-group">
                            <label for="lecture-body" class="form-label">
                                <span>Lecture Body & Teaching Notes</span>
                            </label>
                            <textarea
                                id="lecture-body"
                                v-model="form.body"
                                class="neu-textarea"
                                rows="5"
                                placeholder="Write the introductory narrative, core lesson concepts, discussion prompts, or vocabulary highlights..."
                            ></textarea>
                            <p v-if="form.errors.body" class="error-msg">{{ form.errors.body }}</p>
                        </div>

                        <!-- XP Reward -->
                        <div class="form-group xp-form-group">
                            <label class="form-label">
                                <span>⚡ XP Reward</span>
                            </label>
                            <div class="preset-pill-row">
                                <button
                                    v-for="xp in xpPresets"
                                    :key="xp"
                                    type="button"
                                    class="preset-pill"
                                    :class="{ 'active': form.xp_reward === xp }"
                                    @click="form.xp_reward = xp"
                                >
                                    +{{ xp }}
                                </button>
                            </div>
                            <input
                                v-model.number="form.xp_reward"
                                type="number"
                                min="0"
                                max="10000"
                                class="neu-input"
                            />
                            <p v-if="form.errors.xp_reward" class="error-msg">{{ form.errors.xp_reward }}</p>
                        </div>

                        <!-- Publishing Status Toggle -->
                        <div class="toggle-group-card">
                            <div class="toggle-info">
                                <span class="toggle-title">Publish Milestone Immediately</span>
                                <span class="toggle-sub">Visible to students on their adventure progress map</span>
                            </div>
                            <label class="switch-control">
                                <input v-model="form.is_published" type="checkbox" />
                                <span class="slider-round"></span>
                            </label>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions-row">
                            <button
                                type="button"
                                class="btn-cancel"
                                @click="goToModule"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="btn-3d-submit"
                                :disabled="form.processing"
                            >
                                <span v-if="!form.processing">✨ Create Lecture Milestone</span>
                                <span v-else>Summoning...</span>
                            </button>
                        </div>
                    </form>
                </section>

                <!-- PREVIEW CARD (RIGHT COLUMN) -->
                <aside class="neu-card preview-card">
                    <div class="preview-header">
                        <span class="preview-badge">LIVE S-CURVE PREVIEW</span>
                        <span class="preview-hint">How this step appears on the map</span>
                    </div>

                    <!-- Step Node Simulation -->
                    <div class="simulated-step-pod">
                        <!-- 3D Toy Button Preview -->
                        <div class="sim-button-wrap">
                            <div class="sim-terrain-pad">
                                <span class="terrain-icon">🌴</span>
                            </div>
                            <div class="sim-chunky-btn">
                                <span class="sim-gloss"></span>
                                <span class="sim-num">{{ module.next_sort_order }}</span>
                            </div>
                        </div>

                        <!-- Directional Speech Bubble Preview -->
                        <div class="sim-speech-card">
                            <div class="sim-pointer-triangle"></div>
                            <div class="sim-bubble-top">
                                <span class="sim-step-tag">STEP {{ module.next_sort_order }}</span>
                                <span class="sim-xp-pill">+{{ previewXp }} XP</span>
                            </div>
                            <h3 class="sim-title">{{ previewTitle }}</h3>
                            <p class="sim-desc">{{ previewDesc }}</p>
                            <div class="sim-bubble-bot">
                                <span class="sim-quest-tag">📖 Milestone Quest</span>
                                <span class="sim-status">
                                    {{ form.is_published ? 'Published 🔥' : 'Draft 🔒' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Next Step Info Box -->
                    <div class="next-step-tip">
                        <span class="tip-icon">💡</span>
                        <div class="tip-text">
                            <strong>After creating this lecture:</strong>
                            <p>You can upload audio narrations, video animations, and downloadable story passages in the <em>Lecture Content Builder</em>.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>

<style scoped>
.create-lecture-page {
    --brand-navy: #1D2440;
    --brand-blue: #2395EB;
    --brand-blue-shadow: #1467A6;
    --brand-coral: #FF6B4A;
    --brand-coral-shadow: #BF3E22;
    --brand-green: #06D6A0;
    --brand-green-shadow: #038F6A;
    --brand-gold: #FFC000;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-heading: 'Fredoka', system-ui, sans-serif;
    --font-body: 'Nunito', system-ui, sans-serif;

    /* Neumorphic Tokens */
    --neu-bg: #e0f2fe;
    --neu-card-shadow: 10px 10px 24px rgba(14, 116, 144, 0.16), -10px -10px 24px rgba(255, 255, 255, 0.95);
    --neu-inset: inset 2px 2px 5px rgba(15, 23, 42, 0.08), inset -2px -2px 5px rgba(255, 255, 255, 0.9);

    min-height: 100vh;
    background: linear-gradient(180deg, #e0f2fe 0%, #bae6fd 60%, #7dd3fc 100%);
    font-family: var(--font-body);
    color: var(--brand-navy);
    position: relative;
    padding-bottom: 5rem;
    overflow-x: hidden;
}

/* Background floating scenery */
.floating-bg-assets {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 1;
}

.bg-asset {
    position: absolute;
    width: 25vw;
    max-width: 320px;
    opacity: 0.35;
    filter: drop-shadow(0 14px 24px rgba(14, 116, 144, 0.18));
}

.bg-book-left {
    top: 15%;
    left: -3%;
    animation: floatAssetLeft 7s ease-in-out infinite alternate;
}

.bg-book-right {
    bottom: 8%;
    right: -3%;
    animation: floatAssetRight 8s ease-in-out infinite alternate;
}

@keyframes floatAssetLeft {
    from { transform: translateY(0) rotate(-1deg); }
    to { transform: translateY(-16px) rotate(1deg); }
}

@keyframes floatAssetRight {
    from { transform: translateY(0) rotate(1deg); }
    to { transform: translateY(-16px) rotate(-1deg); }
}

.floating-sparkle {
    position: absolute;
    font-size: 1.8rem;
    opacity: 0.6;
}

.sp-1 { top: 12%; left: 22%; }
.sp-2 { top: 35%; right: 18%; }
.sp-3 { bottom: 20%; left: 15%; }

/* Navigation Bar */
.top-nav-bar {
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

.btn-3d-back {
    background: #f1f5f9;
    border: 1.5px solid #e2e8f0;
    border-radius: 9999px;
    padding: 0.45rem 1rem;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.82rem;
    color: #334155;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}

.btn-3d-back:hover {
    background: #e2e8f0;
    transform: translateX(-2px);
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.brand-text {
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 1.35rem;
}

.brand-web { color: #2395eb; }
.brand-read { color: #ff6b4a; }

.nav-badge {
    background: #fef3c7;
    color: #b45309;
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 800;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
}

.nav-module-chip {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: #ffffff;
    border: 1.5px solid #e0f2fe;
    padding: 0.4rem 0.9rem;
    border-radius: 9999px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.chip-title {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.88rem;
    color: #0369a1;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.teacher-chip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: #f1f5f9;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.82rem;
}

.btn-exit-mini {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.78rem;
    cursor: pointer;
}

/* Main Builder Stage */
.lecture-builder-stage {
    position: relative;
    z-index: 10;
    max-width: 1100px;
    margin: 2rem auto 0 auto;
    padding: 0 1.25rem;
}

.builder-header-block {
    text-align: center;
    margin-bottom: 2rem;
}

.banner-pill {
    display: inline-block;
    padding: 0.35rem 0.95rem;
    background: #e0f2fe;
    color: #0284c7;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    margin-bottom: 0.6rem;
    border: 1.5px solid #bae6fd;
}

.builder-title {
    font-family: var(--font-heading);
    font-size: 2.2rem;
    font-weight: 900;
    color: #0369a1;
    margin: 0 0 0.5rem 0;
    text-shadow: 0 2px 4px rgba(2, 132, 199, 0.15);
}

.builder-subtitle {
    font-size: 0.95rem;
    color: #475569;
    max-width: 620px;
    margin: 0 auto;
    line-height: 1.5;
}

.builder-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.75rem;
    align-items: start;
}

/* Neumorphic Cards */
.neu-card {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    border-radius: 28px;
    box-shadow: var(--neu-card-shadow);
    border: 2.5px solid #ffffff;
    padding: 2rem;
}

.form-kicker-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1.5px solid #f1f5f9;
}

.step-num-pill {
    background: #e0f2fe;
    color: #0369a1;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.78rem;
    padding: 0.3rem 0.75rem;
    border-radius: 9999px;
}

.module-link-text {
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748b;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.35rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-family: var(--font-heading);
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--brand-navy);
    margin-bottom: 0.45rem;
}

.required-star {
    color: #ef4444;
}

.neu-input,
.neu-textarea {
    width: 100%;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.75rem 1rem;
    font-family: var(--font-body);
    font-size: 0.95rem;
    color: #1e293b;
    box-shadow: inset 1px 2px 4px rgba(0, 0, 0, 0.04);
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.neu-input:focus,
.neu-textarea:focus {
    outline: none;
    border-color: #2395eb;
    box-shadow: 0 0 0 3px rgba(35, 149, 235, 0.18);
}

.form-row-duo {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.preset-pill-row {
    display: flex;
    gap: 0.35rem;
    margin-bottom: 0.45rem;
    flex-wrap: wrap;
}

.preset-pill {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    padding: 0.2rem 0.55rem;
    font-family: var(--font-heading);
    font-size: 0.75rem;
    font-weight: 800;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.preset-pill.active {
    background: #f97316;
    color: #ffffff;
    border-color: #ea580c;
    box-shadow: 0 2px 5px rgba(234, 88, 12, 0.3);
}

.input-compact {
    padding: 0.55rem 0.85rem;
    font-weight: 800;
    font-family: var(--font-heading);
}

.error-msg {
    margin: 0.35rem 0 0 0;
    color: #ef4444;
    font-size: 0.78rem;
    font-weight: 700;
}

/* Toggle Group Card */
.toggle-group-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.75rem;
}

.toggle-title {
    display: block;
    font-family: var(--font-heading);
    font-size: 0.92rem;
    font-weight: 900;
    color: #1e293b;
}

.toggle-sub {
    display: block;
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 0.15rem;
}

.switch-control {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}

.switch-control input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider-round {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #cbd5e1;
    border-radius: 9999px;
    transition: 0.3s;
}

.slider-round:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input:checked + .slider-round {
    background-color: #06d6a0;
}

input:checked + .slider-round:before {
    transform: translateX(22px);
}

/* Form Actions */
.form-actions-row {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
}

.btn-cancel {
    background: #f1f5f9;
    border: none;
    padding: 0.75rem 1.4rem;
    border-radius: 14px;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
}

.btn-3d-submit {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border: none;
    border-radius: 14px;
    padding: 0.8rem 1.75rem;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.95rem;
    color: #ffffff;
    box-shadow: 0 4px 0 #9a3412, 0 8px 16px rgba(234, 88, 12, 0.35);
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-3d-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #9a3412, 0 12px 20px rgba(234, 88, 12, 0.45);
}

.btn-3d-submit:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 2px 0 #9a3412;
}

.btn-3d-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Preview Card */
.preview-card {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1.5px solid #f1f5f9;
    padding-bottom: 0.75rem;
}

.preview-badge {
    background: #fef3c7;
    color: #b45309;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.72rem;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
}

.preview-hint {
    font-size: 0.72rem;
    color: #94a3b8;
}

.simulated-step-pod {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem 0.5rem;
    background: #f0f9ff;
    border-radius: 20px;
    border: 1.5px dashed #bae6fd;
}

.sim-button-wrap {
    position: relative;
    width: 76px;
    height: 76px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sim-terrain-pad {
    position: absolute;
    width: 90px;
    height: 50px;
    bottom: -6px;
    background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
    border-radius: 50%;
    box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.6), 0 4px 8px rgba(0, 0, 0, 0.12);
}

.terrain-icon {
    position: absolute;
    top: -8px;
    right: -2px;
    font-size: 1.1rem;
}

.sim-chunky-btn {
    position: relative;
    z-index: 5;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(145deg, #fb7185 0%, #ea580c 100%);
    box-shadow: 0 5px 0 #c2410c, 0 8px 14px rgba(234, 88, 12, 0.4);
    border: 3px solid #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sim-gloss {
    position: absolute;
    top: 3px;
    left: 8px;
    right: 8px;
    height: 14px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.75) 0%, transparent 100%);
    border-radius: 50%;
}

.sim-num {
    font-family: var(--font-heading);
    font-size: 1.6rem;
    font-weight: 900;
    color: #ffffff;
    text-shadow: 0 2px 0 #9a3412;
}

.sim-speech-card {
    position: relative;
    width: 100%;
    max-width: 290px;
    background: #ffffff;
    border-radius: 20px;
    padding: 1.1rem;
    box-shadow: 0 6px 18px rgba(14, 116, 144, 0.12);
    border: 2px solid #fed7aa;
}

.sim-pointer-triangle {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid #ffffff;
}

.sim-bubble-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.35rem;
}

.sim-step-tag {
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 900;
    background: #dcfce7;
    color: #15803d;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
}

.sim-xp-pill {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.72rem;
    color: #f59e0b;
    background: #fef3c7;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
}

.sim-title {
    font-family: var(--font-heading);
    font-size: 0.95rem;
    font-weight: 900;
    margin: 0 0 0.35rem 0;
    line-height: 1.3;
    color: var(--brand-navy);
}

.sim-desc {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0 0 0.65rem 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.sim-bubble-bot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    border-top: 1px solid #f1f5f9;
    padding-top: 0.45rem;
}

.sim-status {
    color: #ea580c;
}

.next-step-tip {
    display: flex;
    gap: 0.75rem;
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 16px;
    padding: 0.9rem;
}

.tip-icon {
    font-size: 1.4rem;
}

.tip-text strong {
    display: block;
    font-family: var(--font-heading);
    font-size: 0.85rem;
    color: #92400e;
    margin-bottom: 0.2rem;
}

.tip-text p {
    font-size: 0.78rem;
    color: #78350f;
    margin: 0;
    line-height: 1.4;
}

@media (max-width: 860px) {
    .builder-grid {
        grid-template-columns: 1fr;
    }
}
</style>

