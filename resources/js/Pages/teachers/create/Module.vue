<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Components/TeacherLayout.vue';
import { generateModuleDescription } from '@/Services/geminiAi.js';

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
    classroom: {
        type: Object,
        default: null,
    },
    pending_grading_count: {
        type: Number,
        default: 0,
    },
});

const themes = [
    {
        id: 'forest',
        label: 'Whispering Forest',
        desc: 'Emerald Woodland',
        icon: '🌲',
        color: '#06D6A0',
    },
    {
        id: 'ocean',
        label: 'Sunken Atlantis',
        desc: 'Deep Azure Sea',
        icon: '🌊',
        color: '#2395EB',
    },
    {
        id: 'cosmic',
        label: 'Cosmic Nebula',
        desc: 'Starlight Galaxy',
        icon: '🚀',
        color: '#845EC2',
    },
    {
        id: 'volcano',
        label: 'Dino Volcano',
        desc: 'Prehistoric Flame',
        icon: '🌋',
        color: '#FF6B4A',
    },
];

const xpPresets = [100, 250, 500, 1000];

const bannerInputRef = ref(null);
const coverPreviewUrl = ref(null);
const isDraggingBanner = ref(false);

const isGeneratingDesc = ref(false);
const aiDescError = ref('');
const aiGeneratedSuccess = ref(false);

const triggerAiModuleDescription = async () => {
    const title = form.title.trim();
    if (!title) {
        aiDescError.value = 'Please enter a module title first!';
        return;
    }

    isGeneratingDesc.value = true;
    aiDescError.value = '';
    aiGeneratedSuccess.value = false;

    try {
        const result = await generateModuleDescription({
            title,
            gradeLevel: '',
            theme: form.theme,
        });

        if (result?.description) {
            form.description = result.description;
            aiGeneratedSuccess.value = true;
            setTimeout(() => {
                aiGeneratedSuccess.value = false;
            }, 3000);
        }
    } catch (err) {
        aiDescError.value = err.message || 'Unable to generate description with AI. Please try again.';
    } finally {
        isGeneratingDesc.value = false;
    }
};

const form = useForm({
    title: '',
    description: '',
    theme: 'forest',
    icon: '🌲',
    cover_image: null,
    xp_bonus: 250,
    boss_name: 'Grammar Goblin',
    max_students: null,
    status: 'draft',
    classroom_id: props.classroom?.id ?? null,
});

const selectTheme = (themeOption) => {
    form.theme = themeOption.id;
    if (['🌲', '🌊', '🚀', '🌋'].includes(form.icon)) {
        form.icon = themeOption.icon;
    }
};

const applyBannerFile = (file) => {
    if (!file || !file.type.startsWith('image/')) {
        return;
    }
    if (coverPreviewUrl.value) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }
    form.cover_image = file;
    coverPreviewUrl.value = URL.createObjectURL(file);
};

const handleBannerSelect = (event) => {
    const file = event.target.files?.[0];
    applyBannerFile(file);
};

const handleBannerDrop = (event) => {
    isDraggingBanner.value = false;
    const file = event.dataTransfer?.files?.[0];
    applyBannerFile(file);
};

const removeBanner = () => {
    if (coverPreviewUrl.value) {
        URL.revokeObjectURL(coverPreviewUrl.value);
        coverPreviewUrl.value = null;
    }
    form.cover_image = null;
    if (bannerInputRef.value) {
        bannerInputRef.value.value = '';
    }
};

const submitModule = () => {
    form.post('/teachers/modules');
};

const goToDashboard = () => {
    router.visit(props.classroom ? `/teachers/dashboard?class_id=${props.classroom.id}` : '/teachers/dashboard');
};
</script>

<template>
    <Head title="Create Module • WEBREAD" />

    <TeacherLayout
        :user="user"
        active-nav="modules"
        :pending-grading-count="pending_grading_count"
    >
        <!-- MAIN STAGE: CREATE MODULE FORGE -->
        <section class="game-stage">
            <!-- Header Bar -->
            <div class="panel-header-row">
                <div>
                    <div class="forge-badge-pill">✨ MODULE ARCHITECT FORGE</div>
                    <h1 class="panel-title">🏰 Create New Module</h1>
                    <p class="panel-sub">
                        Craft a new learning module for your students.
                    </p>
                </div>
                <div class="header-buttons">
                    <button type="button" class="btn-3d btn-back-action" @click="goToDashboard">
                        <span>← BACK TO YOUR MODULES</span>
                    </button>
                </div>
            </div>

            <!-- Builder Grid -->
            <div class="builder-grid">
                <!-- Tactile 3D + Neumorphic Form -->
                <form class="module-form-card" @submit.prevent="submitModule">
                    <!-- Section 1: Module Identity -->
                    <div class="form-section">
                        <h2 class="section-heading">
                            <span class="section-num">1</span>
                            <span>Module Identity & Overview</span>
                        </h2>

                        <div class="field-group">
                            <label for="module-title" class="field-label">MODULE TITLE *</label>
                            <input
                                id="module-title"
                                v-model="form.title"
                                type="text"
                                class="neu-input"
                                placeholder="e.g., Whispering Forest: Phonics & Fables"
                                required
                            />
                            <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>
                        </div>

                        <div class="field-group">
                            <label for="module-status" class="field-label">MODULE STATUS</label>
                            <select id="module-status" v-model="form.status" class="neu-select">
                                <option value="draft">📝 Draft (Hidden)</option>
                                <option value="published">🌟 Published (Active)</option>
                            </select>
                            <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
                        </div>

                        <div class="field-group">
                            <div class="field-header-row">
                                <label for="module-desc" class="field-label">MODULE DESCRIPTION</label>
                                <button
                                    type="button"
                                    class="ai-generate-desc-btn"
                                    :class="{ 'is-loading': isGeneratingDesc, 'is-success': aiGeneratedSuccess }"
                                    :disabled="isGeneratingDesc"
                                    title="Auto-generate a kid-friendly description from your module title"
                                    @click="triggerAiModuleDescription"
                                >
                                    <span v-if="isGeneratingDesc" class="ai-sparkle-spin">✨</span>
                                    <span v-else-if="aiGeneratedSuccess" class="ai-check-icon">✓</span>
                                    <span v-else class="ai-sparkle-icon">✨</span>
                                    <span class="ai-btn-text">
                                        {{ isGeneratingDesc ? 'Generating...' : aiGeneratedSuccess ? 'Generated!' : 'Generate with AI' }}
                                    </span>
                                </button>
                            </div>
                            <textarea
                                id="module-desc"
                                v-model="form.description"
                                rows="3"
                                class="neu-textarea"
                                placeholder="Describe what reading skills, stories, and multimedia lectures students will explore inside this module..."
                            ></textarea>
                            <p v-if="aiDescError" class="ai-desc-error">{{ aiDescError }}</p>
                            <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
                        </div>
                    </div>

                    <!-- Section 2: Module Banner / Cover Image & Biome Theme -->
                    <div class="form-section">
                        <h2 class="section-heading">
                            <span class="section-num">2</span>
                            <span>Module Banner & Biome Theme</span>
                        </h2>

                        <!-- Module Banner / Cover Image Upload -->
                        <div class="field-group">
                            <span class="field-label">UPLOAD MODULE BANNER / COVER IMAGE (OPTIONAL)</span>
                            <input
                                ref="bannerInputRef"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden-file-input"
                                @change="handleBannerSelect"
                            />

                            <div
                                v-if="!coverPreviewUrl"
                                class="banner-upload-well"
                                :class="{ 'dragging-active': isDraggingBanner }"
                                @click="bannerInputRef?.click()"
                                @dragover.prevent="isDraggingBanner = true"
                                @dragleave.prevent="isDraggingBanner = false"
                                @drop.prevent="handleBannerDrop"
                            >
                                <div class="upload-icon-bubble">🖼️</div>
                                <div class="upload-well-text">
                                    <span class="upload-main-txt">Click to upload or drag & drop module banner</span>
                                    <span class="upload-sub-txt">Supports JPG, PNG, WEBP • Recommended 16:9 ratio (Max 5MB)</span>
                                </div>
                                <button type="button" class="btn-3d btn-browse-banner" @click.stop="bannerInputRef?.click()">
                                    <span>📁 CHOOSE IMAGE</span>
                                </button>
                            </div>

                            <div v-else class="banner-uploaded-preview-box">
                                <img :src="coverPreviewUrl" alt="Module Cover Banner Preview" class="banner-uploaded-img" />
                                <div class="banner-uploaded-bar">
                                    <span class="banner-file-name">🖼️ {{ form.cover_image?.name || 'Banner Selected' }}</span>
                                    <div class="banner-bar-actions">
                                        <button type="button" class="btn-3d btn-change-banner" @click="bannerInputRef?.click()">
                                            <span>🔄 CHANGE</span>
                                        </button>
                                        <button type="button" class="btn-3d btn-remove-banner" @click="removeBanner">
                                            <span>✖ REMOVE</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.cover_image" class="field-error">{{ form.errors.cover_image }}</p>
                        </div>

                        <div class="field-group">
                            <span class="field-label">CHOOSE BIOME COLOR THEME</span>
                            <div class="theme-picker-grid">
                                <button
                                    v-for="t in themes"
                                    :key="t.id"
                                    type="button"
                                    class="theme-option-btn"
                                    :class="[`theme-opt-${t.id}`, { active: form.theme === t.id }]"
                                    @click="selectTheme(t)"
                                >
                                    <span class="theme-opt-icon">{{ t.icon }}</span>
                                    <span class="theme-opt-title">{{ t.label }}</span>
                                    <span class="theme-opt-sub">{{ t.desc }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Gamification & Quest Rules -->
                    <div class="form-section">
                        <h2 class="section-heading">
                            <span class="section-num">3</span>
                            <span>Quest Rewards</span>
                        </h2>

                        <div class="field-row-2">
                            <div class="field-group">
                                <label for="module-xp" class="field-label">GUILD XP BONUS (COMPLETION REWARD) *</label>
                                <div class="xp-input-wrap">
                                    <input
                                        id="module-xp"
                                        v-model.number="form.xp_bonus"
                                        type="number"
                                        min="0"
                                        max="100000"
                                        step="10"
                                        class="neu-input xp-num-input"
                                        placeholder="250"
                                        required
                                    />
                                    <span class="xp-unit-badge">+XP</span>
                                </div>
                                <div class="xp-preset-row">
                                    <button
                                        v-for="preset in xpPresets"
                                        :key="preset"
                                        type="button"
                                        class="xp-preset-pill"
                                        :class="{ active: Number(form.xp_bonus) === preset }"
                                        @click="form.xp_bonus = preset"
                                    >
                                        +{{ preset }} XP
                                    </button>
                                </div>
                                <p v-if="form.errors.xp_bonus" class="field-error">{{ form.errors.xp_bonus }}</p>
                            </div>

                            <div class="field-group">
                                <label for="module-boss" class="field-label">FINAL BOSS CHALLENGE</label>
                                <input
                                    id="module-boss"
                                    v-model="form.boss_name"
                                    type="text"
                                    class="neu-input"
                                    placeholder="Grammar Goblin"
                                />
                            </div>
                        </div>

                        <!-- Target classroom: the one the teacher clicked "Add New Module" from -->
                        <div v-if="classroom" class="target-classroom-badge">
                            <span class="target-classroom-icon">{{ classroom.icon || '🏫' }}</span>
                            <span>This module will be added to <strong>{{ classroom.name }}</strong></span>
                        </div>
                        <p v-if="form.errors.classroom_id" class="field-error">{{ form.errors.classroom_id }}</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-footer-actions">
                        <button type="button" class="btn-3d btn-cancel-action" @click="goToDashboard">
                            <span>CANCEL</span>
                        </button>
                        <button type="submit" class="btn-3d btn-submit-module" :disabled="form.processing">
                            <span>{{ form.processing ? 'SUMMONING MODULE...' : '✨ CREATE & SAVE MODULE' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </TeacherLayout>
</template>

<style scoped>
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

.btn-3d:active:not(:disabled) {
    transform: translateY(4px) !important;
    box-shadow:
        0 1px 0 #1D2440,
        inset 3px 3px 6px rgba(0, 0, 0, 0.22),
        inset -2px -2px 5px rgba(255, 255, 255, 0.4) !important;
}

/* ==========================================================================
   MAIN STAGE & BUILDER LAYOUT
   ========================================================================== */
.game-stage {
    position: relative;
    z-index: 5;
    max-width: 1480px;
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

.forge-badge-pill {
    display: inline-block;
    background: linear-gradient(145deg, #F2ECFF 0%, #E2D4FC 100%);
    border: 2px solid #845EC2;
    color: #58398A;
    font-size: 0.68rem;
    font-weight: 900;
    padding: 0.16rem 0.65rem;
    border-radius: 9999px;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
    box-shadow:
        inset 1px 1px 3px rgba(255, 255, 255, 0.9),
        0 2px 0 rgba(132, 94, 194, 0.28);
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
    max-width: 620px;
}

.btn-back-action {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: var(--game-navy);
    padding: 0.6rem 1.1rem;
    font-size: 0.82rem;
    box-shadow:
        0 4px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(164, 176, 204, 0.4);
}

.btn-back-action:hover {
    transform: translateY(-2px);
    box-shadow:
        0 7px 0 var(--game-border),
        inset 2px 2px 5px rgba(255, 255, 255, 0.95);
}

/* Builder Form Container */
.builder-grid {
    display: block;
    max-width: 900px;
    margin: 0 auto;
}

/* Form Card */
.module-form-card {
    background: var(--neu-surface);
    border: 3.5px solid var(--game-border);
    border-radius: 30px;
    padding: 1.9rem 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
    box-shadow:
        0 9px 0 var(--game-border),
        -7px -7px 16px var(--neu-light),
        12px 18px 30px var(--neu-navy-ambient),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px dashed rgba(43, 51, 84, 0.14);
}

.form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0.25rem;
}

.section-heading {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-size: 1.2rem;
    font-weight: 900;
    margin: 0;
    color: var(--game-navy);
}

.section-num {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: linear-gradient(145deg, #38A3F5 0%, #1579C4 100%);
    color: #FFFFFF;
    border: 2px solid #0E5287;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.92rem;
    box-shadow:
        0 3px 0 #0E5287,
        inset 1px 1px 3px rgba(255, 255, 255, 0.65);
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.field-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.field-row-3 {
    display: grid;
    grid-template-columns: 1.1fr 1.1fr 0.8fr;
    gap: 1rem;
}

.field-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
}

.ai-generate-desc-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.75rem;
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 50%, #BFDBFE 100%);
    border: 1.5px solid #93C5FD;
    border-radius: 9999px;
    color: #1D4ED8;
    font-family: var(--font-game);
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.ai-generate-desc-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
    border-color: #60A5FA;
    color: #1E40AF;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(37, 99, 235, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.ai-generate-desc-btn:active:not(:disabled) {
    transform: translateY(1px);
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.15);
}

.ai-generate-desc-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
}

.ai-generate-desc-btn.is-success {
    background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
    border-color: #6EE7B7;
    color: #065F46;
}

.ai-sparkle-spin {
    display: inline-block;
    animation: ai-spin 1s linear infinite;
}

@keyframes ai-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ai-desc-error {
    margin: 0.2rem 0 0 0;
    font-size: 0.76rem;
    font-weight: 800;
    color: #DC2626;
}

.field-label {
    font-size: 0.74rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    color: #5C6684;
}

/* Recessed Neumorphic Inputs */
.neu-input,
.neu-textarea,
.neu-select {
    width: 100%;
    background: #EEF1F9;
    border: 2.5px solid var(--game-border);
    border-radius: 16px;
    padding: 0.78rem 1rem;
    font-family: var(--font-game);
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--game-navy);
    outline: none;
    box-sizing: border-box;
    box-shadow:
        inset 3px 3px 7px rgba(154, 166, 194, 0.55),
        inset -3px -3px 7px rgba(255, 255, 255, 0.95),
        0 1px 0 rgba(255, 255, 255, 0.9);
    transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
}

.neu-input:focus,
.neu-textarea:focus,
.neu-select:focus {
    background: #F6F8FD;
    border-color: var(--game-blue);
    box-shadow:
        inset 3px 3px 7px rgba(35, 149, 235, 0.2),
        inset -3px -3px 7px rgba(255, 255, 255, 0.98),
        0 0 0 3px rgba(35, 149, 235, 0.18);
}

.neu-input::placeholder,
.neu-textarea::placeholder {
    color: #9AA3BA;
    font-weight: 700;
}

.field-error {
    margin: 0.15rem 0 0 0;
    font-size: 0.78rem;
    font-weight: 800;
    color: #D9381E;
}

/* Module Banner / Cover Image Upload Well */
.hidden-file-input {
    display: none;
}

.banner-upload-well {
    background: #EAEEF8;
    border: 2.5px dashed #8B96B2;
    border-radius: 20px;
    padding: 1.15rem 1.35rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    cursor: pointer;
    box-shadow:
        inset 3px 3px 7px rgba(154, 166, 194, 0.5),
        inset -3px -3px 7px rgba(255, 255, 255, 0.95);
    transition: border-color 0.15s ease, background-color 0.15s ease, transform 0.15s var(--spring-bounce);
}

.banner-upload-well:hover,
.banner-upload-well.dragging-active {
    background: #F2F5FC;
    border-color: var(--game-blue);
}

.upload-icon-bubble {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: linear-gradient(145deg, #FFFFFF 0%, #E6ECF8 100%);
    border: 2px solid var(--game-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
}

.upload-well-text {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    flex: 1;
}

.upload-main-txt {
    font-size: 0.9rem;
    font-weight: 900;
    color: var(--game-navy);
}

.upload-sub-txt {
    font-family: var(--font-ui);
    font-size: 0.76rem;
    font-weight: 700;
    color: #7B85A1;
}

.btn-browse-banner {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: var(--game-navy);
    padding: 0.55rem 0.95rem;
    font-size: 0.78rem;
    flex-shrink: 0;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
}

.banner-uploaded-preview-box {
    background: #EEF1F9;
    border: 2.5px solid var(--game-border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow:
        inset 3px 3px 7px rgba(154, 166, 194, 0.45),
        0 4px 0 var(--game-border);
}

.banner-uploaded-img {
    width: 100%;
    height: 155px;
    object-fit: cover;
    display: block;
    border-bottom: 2.5px solid var(--game-border);
}

.banner-uploaded-bar {
    padding: 0.65rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.banner-file-name {
    font-size: 0.8rem;
    font-weight: 900;
    color: var(--game-navy);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 240px;
}

.banner-bar-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-change-banner {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: var(--game-navy);
    padding: 0.4rem 0.75rem;
    font-size: 0.74rem;
    box-shadow: 0 3px 0 var(--game-border);
}

.btn-remove-banner {
    background: linear-gradient(145deg, #FFF5F5 0%, #FFE0E0 100%);
    color: #CF1322;
    border-color: #CF1322;
    padding: 0.4rem 0.75rem;
    font-size: 0.74rem;
    box-shadow: 0 3px 0 #CF1322;
}

/* XP Bonus Integer Input & Presets */
.xp-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.xp-num-input {
    padding-right: 3.4rem;
}

.xp-unit-badge {
    position: absolute;
    right: 0.75rem;
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #573F00;
    border: 1.5px solid #8A6400;
    border-radius: 8px;
    padding: 0.15rem 0.45rem;
    font-size: 0.7rem;
    font-weight: 900;
    pointer-events: none;
    box-shadow: 0 2px 0 #8A6400;
}

.xp-preset-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.25rem;
}

.xp-preset-pill {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    border: 1.5px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.2rem 0.55rem;
    font-family: var(--font-game);
    font-size: 0.68rem;
    font-weight: 900;
    color: #5C6684;
    cursor: pointer;
    box-shadow: 0 2px 0 var(--game-border);
    transition: transform 0.12s var(--spring-bounce);
}

.xp-preset-pill:hover {
    transform: translateY(-1px);
}

.xp-preset-pill.active {
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #4A3500;
    border-color: #8A6400;
    box-shadow: 0 2px 0 #8A6400;
}

/* Module Card Banner Preview */
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

/* Biome Theme Picker Grid */
.theme-picker-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.85rem;
}

.theme-option-btn {
    background: linear-gradient(145deg, #FFFFFF 0%, #EFF2FA 100%);
    border: 3px solid var(--game-border);
    border-radius: 20px;
    padding: 0.85rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.2rem;
    cursor: pointer;
    font-family: var(--font-game);
    text-align: left;
    box-shadow:
        0 4px 0 var(--game-border),
        inset 2px 2px 5px rgba(255, 255, 255, 0.95),
        inset -2px -2px 5px rgba(164, 176, 204, 0.35);
    transition: transform 0.16s var(--spring-bounce), box-shadow 0.16s var(--spring-bounce);
}

.theme-option-btn:hover {
    transform: translateY(-2px);
}

.theme-opt-icon {
    font-size: 1.55rem;
    line-height: 1;
    margin-bottom: 0.15rem;
}

.theme-opt-title {
    font-size: 0.95rem;
    font-weight: 900;
    color: var(--game-navy);
}

.theme-opt-sub {
    font-size: 0.74rem;
    font-weight: 800;
    color: #7B85A1;
}

.theme-opt-forest.active {
    background: linear-gradient(145deg, #ECFCF7 0%, #D0F5E9 100%);
    border-color: #038F6A;
    box-shadow:
        0 5px 0 #038F6A,
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -2px 5px rgba(6, 214, 160, 0.3);
}

.theme-opt-ocean.active {
    background: linear-gradient(145deg, #ECF6FE 0%, #D2E9FC 100%);
    border-color: #1467A6;
    box-shadow:
        0 5px 0 #1467A6,
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -2px 5px rgba(35, 149, 235, 0.3);
}

.theme-opt-cosmic.active {
    background: linear-gradient(145deg, #F5F0FC 0%, #E3D6F8 100%);
    border-color: #58398A;
    box-shadow:
        0 5px 0 #58398A,
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -2px 5px rgba(132, 94, 194, 0.3);
}

.theme-opt-volcano.active {
    background: linear-gradient(145deg, #FFF2EE 0%, #FCDAD1 100%);
    border-color: #BF3E22;
    box-shadow:
        0 5px 0 #BF3E22,
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -2px 5px rgba(255, 107, 74, 0.3);
}

/* Emblem Icon Picker */
.icon-picker-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}

.icon-choice-btn {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    border: 2.5px solid var(--game-border);
    font-size: 1.4rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow:
        0 3px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 4px rgba(164, 176, 204, 0.4);
    transition: transform 0.15s var(--spring-bounce);
}

.icon-choice-btn:hover {
    transform: translateY(-2px);
}

.icon-choice-btn.active {
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    border-color: #8A6400;
    box-shadow:
        0 4px 0 #8A6400,
        inset 2px 2px 4px rgba(255, 255, 255, 0.85);
    transform: translateY(-2px) scale(1.06);
}

/* Form Footer Buttons */
.form-footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.9rem;
    padding-top: 0.5rem;
}

.btn-cancel-action {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: #5C6684;
    padding: 0.85rem 1.4rem;
    font-size: 0.9rem;
    box-shadow:
        0 4px 0 var(--game-border),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
}

.btn-submit-module {
    background: linear-gradient(145deg, #06D6A0 0%, #04A87D 100%);
    color: #FFFFFF;
    padding: 0.85rem 1.75rem;
    font-size: 0.96rem;
    border-color: #026B4F;
    box-shadow:
        0 5px 0 #026B4F,
        0 10px 20px rgba(6, 214, 160, 0.35),
        inset 2px 2px 5px rgba(255, 255, 255, 0.6),
        inset -2px -3px 5px rgba(2, 107, 79, 0.45);
}

.btn-submit-module:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 7px 0 #026B4F,
        0 14px 24px rgba(6, 214, 160, 0.45),
        inset 2px 2px 5px rgba(255, 255, 255, 0.7);
}

.field-help-sub {
    font-size: 0.78rem;
    font-family: var(--font-ui);
    color: #6C7594;
    margin: -0.15rem 0 0.2rem 0;
    font-weight: 700;
}

.target-classroom-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 16px;
    background: rgba(6, 214, 160, 0.12);
    border: 2px dashed rgba(6, 214, 160, 0.55);
    font-family: var(--font-ui);
    font-weight: 700;
}

.target-classroom-icon {
    font-size: 1.4rem;
}

/* ==========================================================================
   RESPONSIVE DESIGN
   ========================================================================== */
@media (max-width: 980px) {
    .field-row-2,
    .field-row-3 {
        grid-template-columns: 1fr;
    }
}
</style>

