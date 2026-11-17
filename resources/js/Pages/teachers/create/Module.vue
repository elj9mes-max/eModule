<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Components/TeacherLayout.vue';
import { generateModuleDescription } from '@/Services/geminiAi.js';
import { useSound } from '@/Services/soundManager';

const { playClick, playPop, playBoing, playSparkle } = useSound();

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

    playClick();
    isGeneratingDesc.value = true;
    aiDescError.value = '';
    aiGeneratedSuccess.value = false;

    try {
        const result = await generateModuleDescription({
            title,
            gradeLevel: props.classroom?.name || '',
            theme: form.theme,
        });

        if (result?.description) {
            form.description = result.description;
            playSparkle();
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
    playPop(1.1);
    form.theme = themeOption.id;
    if (['🌲', '🌊', '🚀', '🌋'].includes(form.icon)) {
        form.icon = themeOption.icon;
    }
};

const selectXpPreset = (preset) => {
    playPop(1.15);
    form.xp_bonus = preset;
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
    playSparkle();
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
    playClick();
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
    playClick();
    form.post('/teachers/modules');
};

const goToDashboard = () => {
    playClick();
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
        <!-- MAIN STAGE: WIDE COMPACT MODULE FORGE -->
        <section class="game-stage">
            <!-- Header Bar -->
            <div class="panel-header-row">
                <div class="header-title-block">
                    <div class="forge-badge-pill">✨ MODULE ARCHITECT FORGE</div>
                    <h1 class="panel-title">🏰 Create New Module</h1>
                </div>
                <div class="header-buttons">
                    <button type="button" class="btn-3d btn-back-action" @click="goToDashboard">
                        <span>← BACK TO MODULES</span>
                    </button>
                </div>
            </div>

            <!-- Wide 2-Column Builder Studio -->
            <div class="builder-grid">
                <form class="module-form-card" @submit.prevent="submitModule">
                    <div class="form-studio-columns">
                        <!-- LEFT PANE: Identity, Story & Biome Theme -->
                        <div class="studio-col studio-col--left">
                            <div class="col-header">
                                <span class="col-num">1</span>
                                <h2 class="col-title">Module Identity & Biome Theme</h2>
                            </div>

                            <!-- Row: Title & Status -->
                            <div class="field-row-split">
                                <div class="field-group flex-1">
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

                                <div class="field-group status-group">
                                    <label for="module-status" class="field-label">STATUS</label>
                                    <select id="module-status" v-model="form.status" class="neu-select">
                                        <option value="draft">📝 Draft</option>
                                        <option value="published">🌟 Live</option>
                                    </select>
                                    <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
                                </div>
                            </div>

                            <!-- Description with AI Generate -->
                            <div class="field-group">
                                <div class="field-header-row">
                                    <label for="module-desc" class="field-label">DESCRIPTION</label>
                                    <button
                                        type="button"
                                        class="ai-generate-desc-btn"
                                        :class="{ 'is-loading': isGeneratingDesc, 'is-success': aiGeneratedSuccess }"
                                        :disabled="isGeneratingDesc"
                                        title="Auto-generate a kid-friendly description from your title"
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
                                    rows="2"
                                    class="neu-textarea"
                                    placeholder="Describe the adventure, reading goals, and challenges waiting inside..."
                                ></textarea>
                                <p v-if="aiDescError" class="ai-desc-error">{{ aiDescError }}</p>
                                <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
                            </div>

                            <!-- Biome Theme Selector (Compact 4-grid) -->
                            <div class="field-group">
                                <span class="field-label">CHOOSE BIOME THEME</span>
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
                                        <div class="theme-opt-copy">
                                            <span class="theme-opt-title">{{ t.label }}</span>
                                            <span class="theme-opt-sub">{{ t.desc }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT PANE: Visual Banner & Quest Rewards -->
                        <div class="studio-col studio-col--right">
                            <div class="col-header">
                                <span class="col-num">2</span>
                                <h2 class="col-title">Cover Art & Quest Rewards</h2>
                            </div>

                            <!-- Compact Module Banner / Cover Image Upload -->
                            <div class="field-group">
                                <span class="field-label">COVER BANNER (OPTIONAL)</span>
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
                                        <span class="upload-main-txt">Click or drag banner image</span>
                                        <span class="upload-sub-txt">JPG, PNG, WEBP • 16:9 ratio (Max 5MB)</span>
                                    </div>
                                    <button type="button" class="btn-3d btn-browse-banner" @click.stop="bannerInputRef?.click()">
                                        <span>📁 CHOOSE</span>
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

                            <!-- Rewards: XP Bonus & Boss Row -->
                            <div class="field-row-2">
                                <div class="field-group">
                                    <label for="module-xp" class="field-label">GUILD XP REWARD *</label>
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
                                            @click="selectXpPreset(preset)"
                                        >
                                            +{{ preset }}
                                        </button>
                                    </div>
                                    <p v-if="form.errors.xp_bonus" class="field-error">{{ form.errors.xp_bonus }}</p>
                                </div>

                                <div class="field-group">
                                    <label for="module-boss" class="field-label">BOSS CHALLENGE</label>
                                    <input
                                        id="module-boss"
                                        v-model="form.boss_name"
                                        type="text"
                                        class="neu-input"
                                        placeholder="Grammar Goblin"
                                    />
                                </div>
                            </div>

                            <!-- Target Classroom Badge (If assigned to a room) -->
                            <div v-if="classroom" class="target-classroom-badge">
                                <span class="target-classroom-icon">{{ classroom.icon || '🏫' }}</span>
                                <div class="target-classroom-text">
                                    <span>Target Classroom:</span>
                                    <strong>{{ classroom.name }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Docked Form Footer Actions -->
                    <div class="form-footer-actions">
                        <button type="button" class="btn-3d btn-cancel-action" @click="goToDashboard">
                            <span>CANCEL</span>
                        </button>
                        <button type="submit" class="btn-3d btn-submit-module" :disabled="form.processing">
                            <span>{{ form.processing ? 'SUMMONING...' : '✨ CREATE & SAVE MODULE' }}</span>
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
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-weight: 900;
    letter-spacing: 0.04em;
    border-radius: 9999px;
    border: 2px solid #1D2440;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s var(--spring-bounce);
    outline: none;
    user-select: none;
}

.btn-3d:active:not(:disabled) {
    transform: translateY(3px) !important;
    box-shadow: 0 1px 0 #1D2440 !important;
}

/* ==========================================================================
   MAIN STAGE & WIDER BUILDER LAYOUT
   ========================================================================== */
.game-stage {
    position: relative;
    z-index: 5;
    max-width: 1240px;
    width: 100%;
    margin: 0.25rem auto 1.5rem auto;
    padding: 0 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    box-sizing: border-box;
}

/* Compact Header Bar */
.panel-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.65rem;
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F5F0E4 100%));
    border: 2.5px solid var(--game-border, #1D2440);
    border-radius: 18px;
    padding: 0.65rem 1rem;
    box-shadow:
        0 4px 0 var(--game-border, #1D2440),
        -3px -3px 8px var(--neu-light, rgba(255, 255, 255, 0.92)),
        6px 10px 18px var(--neu-navy-ambient, rgba(29, 36, 64, 0.1)),
        var(--neu-bevel-light, inset 2px 2px 4px rgba(255, 255, 255, 0.95)),
        var(--neu-bevel-dark, inset -2px -2px 4px rgba(194, 185, 166, 0.3));
}

.header-title-block {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.forge-badge-pill {
    display: inline-block;
    background: linear-gradient(145deg, #F2ECFF 0%, #E2D4FC 100%);
    border: 1.5px solid #845EC2;
    color: #58398A;
    font-size: 0.65rem;
    font-weight: 900;
    padding: 0.12rem 0.55rem;
    border-radius: 9999px;
    letter-spacing: 0.05em;
    box-shadow: 0 1.5px 0 rgba(132, 94, 194, 0.28);
}

.panel-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 1.25rem;
    font-weight: 900;
    margin: 0;
    color: var(--game-navy, #1D2440);
    line-height: 1.1;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.85);
}

.btn-back-action {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: var(--game-navy, #1D2440);
    padding: 0.42rem 0.85rem;
    font-size: 0.74rem;
    box-shadow: 0 2.5px 0 var(--game-border, #1D2440);
}

.btn-back-action:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 4px 0 var(--game-border, #1D2440);
}

/* Builder Container: Wider Canvas */
.builder-grid {
    width: 100%;
}

/* Form Card */
.module-form-card {
    background: var(--neu-surface, linear-gradient(145deg, #FFFFFF 0%, #F5F0E4 100%));
    border: 2.5px solid var(--game-border, #1D2440);
    border-radius: 22px;
    padding: 1.2rem 1.4rem;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
    box-shadow:
        0 6px 0 var(--game-border, #1D2440),
        -5px -5px 12px rgba(255, 255, 255, 0.95),
        8px 16px 28px rgba(29, 36, 64, 0.12),
        var(--neu-bevel-light, inset 2px 2px 4px rgba(255, 255, 255, 0.95)),
        var(--neu-bevel-dark, inset -3px -3px 6px rgba(194, 185, 166, 0.3));
    box-sizing: border-box;
}

/* 2-Column Split Studio Layout */
.form-studio-columns {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 1.4rem;
}

.studio-col {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.col-header {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px dashed rgba(43, 51, 84, 0.14);
}

.col-num {
    width: 24px;
    height: 24px;
    border-radius: 7px;
    background: linear-gradient(145deg, #38A3F5 0%, #1579C4 100%);
    color: #FFFFFF;
    border: 1.5px solid #0E5287;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.76rem;
    font-weight: 900;
    box-shadow: 0 2px 0 #0E5287;
}

.col-title {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.98rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    margin: 0;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.field-row-split {
    display: flex;
    gap: 0.75rem;
}

.flex-1 {
    flex: 1;
}

.status-group {
    width: 140px;
}

.field-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.field-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.4rem;
}

.field-label {
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
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
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 12px;
    padding: 0.48rem 0.75rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--game-navy, #1D2440);
    outline: none;
    box-sizing: border-box;
    box-shadow:
        inset 2px 2px 5px rgba(154, 166, 194, 0.45),
        inset -2px -2px 5px rgba(255, 255, 255, 0.9);
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.neu-input:focus,
.neu-textarea:focus,
.neu-select:focus {
    background: #F8FAFD;
    border-color: #2563EB;
    box-shadow: inset 1px 1px 3px rgba(35, 149, 235, 0.15), 0 0 0 2.5px rgba(35, 149, 235, 0.2);
}

.neu-textarea {
    resize: vertical;
    min-height: 58px;
    line-height: 1.35;
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-weight: 600;
    font-size: 0.84rem;
}

.field-error {
    margin: 0.1rem 0 0 0;
    font-size: 0.72rem;
    font-weight: 800;
    color: #D9381E;
}

/* AI Generate Description Button */
.ai-generate-desc-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 50%, #BFDBFE 100%);
    border: 1.5px solid #93C5FD;
    border-radius: 9999px;
    color: #1D4ED8;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 1.5px 3px rgba(37, 99, 235, 0.12);
    transition: all 0.15s ease;
}

.ai-generate-desc-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
    transform: translateY(-1px);
}

.ai-desc-error {
    margin: 0.15rem 0 0 0;
    font-size: 0.72rem;
    font-weight: 800;
    color: #DC2626;
}

/* Biome Theme Picker Grid */
.theme-picker-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.55rem;
}

.theme-option-btn {
    background: linear-gradient(145deg, #FFFFFF 0%, #EFF2FA 100%);
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 14px;
    padding: 0.45rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    cursor: pointer;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    text-align: left;
    box-shadow: 0 2.5px 0 var(--game-border, #1D2440);
    transition: transform 0.14s var(--spring-bounce);
}

.theme-option-btn:hover {
    transform: translateY(-1.5px);
}

.theme-opt-icon {
    font-size: 1.25rem;
    line-height: 1;
    flex-shrink: 0;
}

.theme-opt-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.theme-opt-title {
    font-size: 0.82rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.theme-opt-sub {
    font-size: 0.65rem;
    font-weight: 800;
    color: #7B85A1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.theme-opt-forest.active {
    background: linear-gradient(145deg, #ECFCF7 0%, #D0F5E9 100%);
    border-color: #038F6A;
    box-shadow: 0 3px 0 #038F6A;
}

.theme-opt-ocean.active {
    background: linear-gradient(145deg, #ECF6FE 0%, #D2E9FC 100%);
    border-color: #1467A6;
    box-shadow: 0 3px 0 #1467A6;
}

.theme-opt-cosmic.active {
    background: linear-gradient(145deg, #F5F0FC 0%, #E3D6F8 100%);
    border-color: #58398A;
    box-shadow: 0 3px 0 #58398A;
}

.theme-opt-volcano.active {
    background: linear-gradient(145deg, #FFF2EE 0%, #FCDAD1 100%);
    border-color: #BF3E22;
    box-shadow: 0 3px 0 #BF3E22;
}

/* Banner Upload Well */
.hidden-file-input {
    display: none;
}

.banner-upload-well {
    background: #EAEEF8;
    border: 2px dashed #8B96B2;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    cursor: pointer;
    box-shadow: inset 2px 2px 4px rgba(154, 166, 194, 0.4);
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.banner-upload-well:hover,
.banner-upload-well.dragging-active {
    background: #F2F5FC;
    border-color: #2563EB;
}

.upload-icon-bubble {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #FFFFFF;
    border: 1.5px solid var(--game-border, #1D2440);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 2px 0 var(--game-border, #1D2440);
}

.upload-well-text {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.upload-main-txt {
    font-size: 0.78rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
}

.upload-sub-txt {
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.68rem;
    font-weight: 700;
    color: #7B85A1;
}

.btn-browse-banner {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: var(--game-navy, #1D2440);
    padding: 0.35rem 0.65rem;
    font-size: 0.7rem;
    flex-shrink: 0;
    box-shadow: 0 2px 0 var(--game-border, #1D2440);
}

.banner-uploaded-preview-box {
    background: #EEF1F9;
    border: 2px solid var(--game-border, #1D2440);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 3px 0 var(--game-border, #1D2440);
}

.banner-uploaded-img {
    width: 100%;
    height: 84px;
    object-fit: cover;
    display: block;
    border-bottom: 2px solid var(--game-border, #1D2440);
}

.banner-uploaded-bar {
    padding: 0.45rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.banner-file-name {
    font-size: 0.74rem;
    font-weight: 900;
    color: var(--game-navy, #1D2440);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 180px;
}

.banner-bar-actions {
    display: flex;
    gap: 0.35rem;
}

.btn-change-banner,
.btn-remove-banner {
    padding: 0.25rem 0.55rem;
    font-size: 0.65rem;
    box-shadow: 0 2px 0 var(--game-border, #1D2440);
}

.btn-remove-banner {
    background: #FFE4E6;
    color: #BE123C;
    border-color: #BE123C;
    box-shadow: 0 2px 0 #BE123C;
}

/* XP Input & Presets */
.xp-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.xp-num-input {
    padding-right: 3rem;
}

.xp-unit-badge {
    position: absolute;
    right: 0.55rem;
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #573F00;
    border: 1px solid #8A6400;
    border-radius: 6px;
    padding: 0.1rem 0.35rem;
    font-size: 0.65rem;
    font-weight: 900;
    pointer-events: none;
}

.xp-preset-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.15rem;
}

.xp-preset-pill {
    background: #FFFFFF;
    border: 1.5px solid var(--game-border, #1D2440);
    border-radius: 9999px;
    padding: 0.12rem 0.45rem;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.65rem;
    font-weight: 900;
    color: #5C6684;
    cursor: pointer;
    box-shadow: 0 1.5px 0 var(--game-border, #1D2440);
    transition: transform 0.12s var(--spring-bounce);
}

.xp-preset-pill:hover {
    transform: translateY(-1px);
}

.xp-preset-pill.active {
    background: linear-gradient(145deg, #FFE46B 0%, #FFB703 100%);
    color: #4A3500;
    border-color: #8A6400;
    box-shadow: 0 1.5px 0 #8A6400;
}

/* Target Classroom Badge */
.target-classroom-badge {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.5rem 0.75rem;
    border-radius: 12px;
    background: rgba(6, 214, 160, 0.12);
    border: 1.5px dashed rgba(6, 214, 160, 0.65);
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.78rem;
    color: #065F46;
}

.target-classroom-icon {
    font-size: 1.25rem;
    line-height: 1;
}

.target-classroom-text {
    display: flex;
    gap: 0.3rem;
}

/* Docked Form Footer */
.form-footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.65rem;
    padding-top: 0.4rem;
    border-top: 1.5px solid rgba(43, 51, 84, 0.1);
}

.btn-cancel-action {
    background: linear-gradient(145deg, #FFFFFF 0%, #EAEFF8 100%);
    color: #5C6684;
    padding: 0.5rem 1rem;
    font-size: 0.78rem;
    box-shadow: 0 3px 0 var(--game-border, #1D2440);
}

.btn-submit-module {
    background: linear-gradient(145deg, #06D6A0 0%, #04A87D 100%);
    color: #FFFFFF;
    padding: 0.5rem 1.25rem;
    font-size: 0.82rem;
    border-color: #026B4F;
    box-shadow: 0 3.5px 0 #026B4F, 0 6px 14px rgba(6, 214, 160, 0.3);
}

.btn-submit-module:hover:not(:disabled) {
    transform: translateY(-1.5px);
    box-shadow: 0 5px 0 #026B4F, 0 8px 18px rgba(6, 214, 160, 0.4);
}

/* Responsive */
@media (max-width: 860px) {
    .form-studio-columns {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

@media (max-width: 540px) {
    .field-row-split,
    .field-row-2 {
        flex-direction: column;
        grid-template-columns: 1fr;
    }
    .status-group {
        width: 100%;
    }
}
</style>

