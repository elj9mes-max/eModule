<script setup>
import { ref, computed } from 'vue';
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
        }),
    },
    lecture: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Chapter 1: Phonics and Folklore',
            slug: 'chapter-1-phonics-and-folklore',
            description: 'Learn foundational reading strategies with audio narration and illustrated folklore.',
            body: '',
            xp_reward: 150,
            sort_order: 1,
            is_published: true,
        }),
    },
    existing_contents: {
        type: Array,
        default: () => [],
    },
});

const contentTypes = [
    {
        id: 'text',
        label: 'Story Passage',
        icon: '📝',
        desc: 'Rich text passage, moral dilemmas, or key vocabulary',
        accept: null,
        hint: 'Write or paste the narrative text for students to read.',
    },
    {
        id: 'image',
        label: 'Illustration',
        icon: '🖼️',
        desc: 'Storybook character art, island map, or comic strip',
        accept: 'image/png,image/jpeg,image/webp,image/gif',
        hint: 'Upload visual aids, character illustrations, or scene backdrops.',
    },
    {
        id: 'audio',
        label: 'Read-Along Audio',
        icon: '🎧',
        desc: 'Voiceover pronunciation, guided narration, or sound effects',
        accept: 'audio/mpeg,audio/wav,audio/mp4,audio/ogg',
        hint: 'Upload teacher voice narration to assist struggling readers.',
    },
    {
        id: 'video',
        label: 'Animated Video',
        icon: '🎬',
        desc: 'Animated lesson video, puppet reading, or YouTube embed',
        accept: 'video/mp4,video/webm',
        hint: 'Upload short story animations or provide an external video link.',
    },
    {
        id: 'document',
        label: 'Printable Sheet',
        icon: '📄',
        desc: 'PDF story worksheet, coloring page, or reading guide',
        accept: '.pdf,.doc,.docx',
        hint: 'Upload supplementary reading worksheets or activity guides.',
    },
];

const selectedType = ref('text');
const mediaPreviewUrl = ref(null);
const fileInputRef = ref(null);
const isDraggingFile = ref(false);

const form = useForm({
    type: 'text',
    title: '',
    content: '',
    media_file: null,
    external_url: '',
    duration_seconds: null,
    alt_text: '',
    caption: '',
});

const selectContentType = (typeId) => {
    selectedType.value = typeId;
    form.type = typeId;
    removeSelectedFile();
};

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        applyFile(file);
    }
};

const handleFileDrop = (event) => {
    isDraggingFile.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        applyFile(file);
    }
};

const applyFile = (file) => {
    form.media_file = file;
    if (mediaPreviewUrl.value) {
        URL.revokeObjectURL(mediaPreviewUrl.value);
    }
    if (file.type.startsWith('image/') || file.type.startsWith('audio/') || file.type.startsWith('video/')) {
        mediaPreviewUrl.value = URL.createObjectURL(file);
    } else {
        mediaPreviewUrl.value = null;
    }
};

const removeSelectedFile = () => {
    if (mediaPreviewUrl.value) {
        URL.revokeObjectURL(mediaPreviewUrl.value);
        mediaPreviewUrl.value = null;
    }
    form.media_file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const submitContent = () => {
    form.post(`/teachers/modules/${props.module.slug}/lectures/${props.lecture.slug}/contents`, {
        forceFormData: true,
        onSuccess: () => {
            // Reset fields for convenient continuous block creation
            form.title = '';
            form.content = '';
            form.external_url = '';
            form.duration_seconds = null;
            form.alt_text = '';
            form.caption = '';
            removeSelectedFile();
        },
    });
};

const goToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const handleLogout = () => {
    router.post('/logout');
};

const getContentTypeMeta = (typeKey) => {
    return contentTypes.find((t) => t.id === typeKey) || contentTypes[0];
};
</script>

<template>
    <Head :title="`Lecture Content • ${lecture.title}`" />

    <div class="content-builder-page">
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
            <div class="floating-sparkle sp-3">🎧</div>
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
                    <span class="nav-badge">Content Studio</span>
                </div>
            </div>

            <div class="nav-lecture-chip">
                <span class="chip-icon">📜</span>
                <span class="chip-lecture-title">{{ lecture.title }}</span>
                <span class="chip-count-pill">{{ existing_contents.length }} Blocks</span>
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

        <!-- Main Studio Container -->
        <main class="studio-container">
            <!-- Header Block -->
            <div class="studio-header">
                <span class="banner-pill">🎨 MULTIMEDIA STORY STUDIO</span>
                <h1 class="studio-title">Enrich Lecture Content</h1>
                <p class="studio-subtitle">
                    Add story passages, voice narrations, animated visual aids, and interactive reading questions to <strong>{{ lecture.title }}</strong>.
                </p>
            </div>

            <!-- Studio 2-Column Layout -->
            <div class="studio-grid">
                <!-- LEFT COLUMN: CONTENT CREATOR CARD -->
                <section class="neu-card builder-form-card">
                    <!-- Step 1: Content Type Selector Tabs -->
                    <div class="content-type-selector">
                        <span class="selector-label">1. CHOOSE CONTENT BLOCK TYPE</span>
                        <div class="type-tabs-grid">
                            <button
                                v-for="type in contentTypes"
                                :key="type.id"
                                type="button"
                                class="type-tab-btn"
                                :class="{ 'active': selectedType === type.id }"
                                @click="selectContentType(type.id)"
                            >
                                <span class="tab-icon">{{ type.icon }}</span>
                                <div class="tab-meta">
                                    <span class="tab-title">{{ type.label }}</span>
                                    <span class="tab-desc">{{ type.desc }}</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Form Input Fields -->
                    <form @submit.prevent="submitContent" class="block-form">
                        <div class="form-section-title">
                            <span>2. CONFIGURE {{ selectedType.toUpperCase() }} BLOCK</span>
                        </div>

                        <!-- Block Title -->
                        <div class="form-group">
                            <label for="block-title" class="form-label">
                                <span>Block Title / Heading</span>
                                <span class="optional-tag">(Optional)</span>
                            </label>
                            <input
                                id="block-title"
                                v-model="form.title"
                                type="text"
                                class="neu-input"
                                :placeholder="`e.g., ${selectedType === 'audio' ? 'Teacher Read-Along Voiceover' : selectedType === 'image' ? 'Island Scene Map' : 'Story Passage: Meeting the Guardian'}`"
                            />
                            <p v-if="form.errors.title" class="error-msg">{{ form.errors.title }}</p>
                        </div>

                        <!-- 1. TEXT TYPE: Rich Textarea -->
                        <div v-if="selectedType === 'text'" class="form-group">
                            <label for="block-content" class="form-label">
                                <span>Reading Story Passage & Vocabulary</span>
                                <span class="required-star">*</span>
                            </label>
                            <textarea
                                id="block-content"
                                v-model="form.content"
                                rows="8"
                                class="neu-textarea"
                                placeholder="Type or paste the reading story, moral dilemma, guided comprehension questions, or vocabulary focus words here..."
                                required
                            ></textarea>
                            <p v-if="form.errors.content" class="error-msg">{{ form.errors.content }}</p>
                        </div>

                        <!-- 2. IMAGE / AUDIO / VIDEO / DOCUMENT: File Upload Zone -->
                        <div v-if="['image', 'audio', 'video', 'document'].includes(selectedType)" class="form-group">
                            <label class="form-label">
                                <span>Upload {{ selectedType.toUpperCase() }} File</span>
                            </label>

                            <!-- Dropzone -->
                            <div
                                class="file-dropzone"
                                :class="{ 'dragging': isDraggingFile, 'has-file': form.media_file }"
                                @dragover.prevent="isDraggingFile = true"
                                @dragleave.prevent="isDraggingFile = false"
                                @drop.prevent="handleFileDrop"
                                @click="fileInputRef?.click()"
                            >
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    class="hidden-file-input"
                                    :accept="getContentTypeMeta(selectedType).accept"
                                    @change="handleFileSelect"
                                />

                                <div v-if="!form.media_file" class="dropzone-empty">
                                    <span class="dropzone-icon">{{ getContentTypeMeta(selectedType).icon }}</span>
                                    <strong class="dropzone-prompt">Click to select or drag & drop file here</strong>
                                    <span class="dropzone-hint">{{ getContentTypeMeta(selectedType).hint }} (Max 50MB)</span>
                                </div>

                                <div v-else class="dropzone-filled" @click.stop>
                                    <div class="file-info-badge">
                                        <span class="file-icon">{{ getContentTypeMeta(selectedType).icon }}</span>
                                        <div class="file-details">
                                            <span class="file-name">{{ form.media_file.name }}</span>
                                            <span class="file-size">{{ Math.round(form.media_file.size / 1024) }} KB</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-remove-file" @click="removeSelectedFile">
                                        ✕ Remove
                                    </button>
                                </div>
                            </div>
                            <p v-if="form.errors.media_file" class="error-msg">{{ form.errors.media_file }}</p>

                            <!-- Live Media Previews -->
                            <div v-if="mediaPreviewUrl" class="live-preview-box">
                                <span class="preview-tag">File Preview</span>

                                <!-- Image Preview -->
                                <img
                                    v-if="selectedType === 'image'"
                                    :src="mediaPreviewUrl"
                                    alt="Uploaded preview"
                                    class="preview-img"
                                />

                                <!-- Audio Player Preview -->
                                <audio
                                    v-else-if="selectedType === 'audio'"
                                    controls
                                    class="preview-audio"
                                    :src="mediaPreviewUrl"
                                ></audio>

                                <!-- Video Player Preview -->
                                <video
                                    v-else-if="selectedType === 'video'"
                                    controls
                                    class="preview-video"
                                    :src="mediaPreviewUrl"
                                ></video>
                            </div>
                        </div>

                        <!-- External URL Option (for Videos / Audios) -->
                        <div v-if="['video', 'audio'].includes(selectedType)" class="form-group">
                            <label for="block-external-url" class="form-label">
                                <span>Or Stream via External Link / Embed</span>
                                <span class="optional-tag">(YouTube, Vimeo, Cloud Stream)</span>
                            </label>
                            <input
                                id="block-external-url"
                                v-model="form.external_url"
                                type="url"
                                class="neu-input"
                                placeholder="https://www.youtube.com/watch?v=..."
                            />
                            <p v-if="form.errors.external_url" class="error-msg">{{ form.errors.external_url }}</p>
                        </div>

                        <!-- Media Duration & Caption Row -->
                        <div v-if="['image', 'audio', 'video', 'document'].includes(selectedType)" class="form-row-duo">
                            <!-- Duration in seconds -->
                            <div v-if="['audio', 'video'].includes(selectedType)" class="form-group">
                                <label for="block-duration" class="form-label">
                                    <span>Duration (Seconds)</span>
                                </label>
                                <input
                                    id="block-duration"
                                    v-model.number="form.duration_seconds"
                                    type="number"
                                    min="0"
                                    class="neu-input"
                                    placeholder="e.g., 120"
                                />
                            </div>

                            <!-- Alt Text for Images -->
                            <div v-if="selectedType === 'image'" class="form-group">
                                <label for="block-alt" class="form-label">
                                    <span>Accessibility Alt Text</span>
                                </label>
                                <input
                                    id="block-alt"
                                    v-model="form.alt_text"
                                    type="text"
                                    class="neu-input"
                                    placeholder="Describe the image for screen readers"
                                />
                            </div>

                            <!-- Caption -->
                            <div class="form-group">
                                <label for="block-caption" class="form-label">
                                    <span>Student Caption / Note</span>
                                </label>
                                <input
                                    id="block-caption"
                                    v-model="form.caption"
                                    type="text"
                                    class="neu-input"
                                    placeholder="Short caption displayed under media"
                                />
                            </div>
                        </div>

                        <!-- Submit Block Button -->
                        <div class="form-submit-row">
                            <button
                                type="submit"
                                class="btn-3d-add-block"
                                :disabled="form.processing"
                            >
                                <span v-if="!form.processing">✨ Add Block to Lecture</span>
                                <span v-else>Uploading & Saving...</span>
                            </button>
                        </div>
                    </form>
                </section>

                <!-- RIGHT COLUMN: EXISTING CONTENTS TIMELINE / FEED -->
                <aside class="neu-card timeline-card">
                    <div class="timeline-header">
                        <div class="timeline-title-wrap">
                            <span class="timeline-icon">📚</span>
                            <div>
                                <h3 class="timeline-title">Lecture Story Flow</h3>
                                <span class="timeline-subtitle">{{ existing_contents.length }} content block{{ existing_contents.length === 1 ? '' : 's' }} in order</span>
                            </div>
                        </div>

                        <button type="button" class="btn-return-map" @click="goToModule">
                            <span>➔ Done & Return</span>
                        </button>
                    </div>

                    <!-- List of Existing Blocks -->
                    <div v-if="existing_contents.length > 0" class="contents-feed">
                        <div
                            v-for="(block, idx) in existing_contents"
                            :key="block.id"
                            class="feed-block-card"
                            :class="`block-${block.type}`"
                        >
                            <div class="feed-block-top">
                                <div class="feed-type-pill">
                                    <span>{{ getContentTypeMeta(block.type).icon }}</span>
                                    <span>#{{ idx + 1 }} • {{ getContentTypeMeta(block.type).label }}</span>
                                </div>
                                <span v-if="block.created_at" class="feed-time">{{ block.created_at }}</span>
                            </div>

                            <h4 class="feed-block-title">
                                {{ block.title || `Untitled ${getContentTypeMeta(block.type).label}` }}
                            </h4>

                            <!-- Text Content Preview -->
                            <p v-if="block.type === 'text' && block.content" class="feed-text-preview">
                                {{ block.content }}
                            </p>

                            <!-- Image Media Preview -->
                            <div v-else-if="block.type === 'image' && block.media_url" class="feed-media-wrap">
                                <img :src="block.media_url" :alt="block.alt_text || 'Illustration'" class="feed-img" />
                            </div>

                            <!-- Audio Media Preview -->
                            <div v-else-if="block.type === 'audio' && (block.media_url || block.external_url)" class="feed-media-wrap">
                                <audio controls class="feed-audio-player" :src="block.media_url || block.external_url"></audio>
                            </div>

                            <!-- Video Preview -->
                            <div v-else-if="block.type === 'video'" class="feed-media-wrap">
                                <video v-if="block.media_url" controls class="feed-video-player" :src="block.media_url"></video>
                                <a
                                    v-else-if="block.external_url"
                                    :href="block.external_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="external-video-link"
                                >
                                    🎬 Open Video Link ➔
                                </a>
                            </div>

                            <!-- Caption -->
                            <div v-if="block.caption" class="feed-caption">
                                <em>💬 {{ block.caption }}</em>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="empty-feed-card">
                        <span class="empty-icon">📜</span>
                        <h4>No Content Blocks Added Yet</h4>
                        <p>Use the form on the left to write reading passages, upload audio narration, or embed animated story videos!</p>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>

<style scoped>
.content-builder-page {
    --brand-navy: #1D2440;
    --brand-blue: #2395EB;
    --brand-coral: #FF6B4A;
    --brand-green: #06D6A0;
    --brand-gold: #FFC000;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-heading: 'Fredoka', system-ui, sans-serif;
    --font-body: 'Nunito', system-ui, sans-serif;

    /* Neumorphic Tokens */
    --neu-card-shadow: 10px 10px 24px rgba(14, 116, 144, 0.16), -10px -10px 24px rgba(255, 255, 255, 0.95);

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
    animation: floatLeft 7s ease-in-out infinite alternate;
}

.bg-book-right {
    bottom: 8%;
    right: -3%;
    animation: floatRight 8s ease-in-out infinite alternate;
}

@keyframes floatLeft {
    from { transform: translateY(0) rotate(-1deg); }
    to { transform: translateY(-16px) rotate(1deg); }
}

@keyframes floatRight {
    from { transform: translateY(0) rotate(1deg); }
    to { transform: translateY(-16px) rotate(-1deg); }
}

.floating-sparkle {
    position: absolute;
    font-size: 1.8rem;
    opacity: 0.6;
}

.sp-1 { top: 10%; left: 20%; }
.sp-2 { top: 30%; right: 15%; }
.sp-3 { bottom: 25%; left: 12%; }

/* Top Navigation Bar */
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
    background: #e0f2fe;
    color: #0284c7;
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 800;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
}

.nav-lecture-chip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #ffffff;
    border: 1.5px solid #e0f2fe;
    padding: 0.4rem 0.95rem;
    border-radius: 9999px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.chip-lecture-title {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.88rem;
    color: #0369a1;
    max-width: 260px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chip-count-pill {
    background: #dcfce7;
    color: #15803d;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.72rem;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
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

/* Studio Layout */
.studio-container {
    position: relative;
    z-index: 10;
    max-width: 1200px;
    margin: 2rem auto 0 auto;
    padding: 0 1.25rem;
}

.studio-header {
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

.studio-title {
    font-family: var(--font-heading);
    font-size: 2.2rem;
    font-weight: 900;
    color: #0369a1;
    margin: 0 0 0.5rem 0;
    text-shadow: 0 2px 4px rgba(2, 132, 199, 0.15);
}

.studio-subtitle {
    font-size: 0.95rem;
    color: #475569;
    max-width: 620px;
    margin: 0 auto;
    line-height: 1.5;
}

.studio-grid {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 1.75rem;
    align-items: start;
}

/* Neumorphic Cards */
.neu-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(14px);
    border-radius: 28px;
    box-shadow: var(--neu-card-shadow);
    border: 2.5px solid #ffffff;
    padding: 2rem;
}

/* Content Type Tabs */
.content-type-selector {
    margin-bottom: 1.75rem;
}

.selector-label {
    display: block;
    font-family: var(--font-heading);
    font-size: 0.76rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    color: #0284c7;
    margin-bottom: 0.75rem;
}

.type-tabs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 0.65rem;
}

.type-tab-btn {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 0.85rem 0.65rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s var(--spring-bounce);
}

.type-tab-btn:hover {
    background: #ffffff;
    border-color: #2395eb;
    transform: translateY(-2px);
}

.type-tab-btn.active {
    background: #ffffff;
    border-color: #f97316;
    box-shadow: 0 6px 14px rgba(249, 115, 22, 0.2);
    transform: translateY(-3px);
}

.tab-icon {
    font-size: 1.6rem;
    margin-bottom: 0.35rem;
}

.tab-title {
    font-family: var(--font-heading);
    font-size: 0.82rem;
    font-weight: 900;
    color: var(--brand-navy);
}

.tab-desc {
    font-size: 0.68rem;
    color: #64748b;
    margin-top: 0.2rem;
    line-height: 1.25;
    display: none;
}

/* Block Form */
.form-section-title {
    font-family: var(--font-heading);
    font-size: 0.76rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    color: #ea580c;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1.5px solid #f1f5f9;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-family: var(--font-heading);
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--brand-navy);
    margin-bottom: 0.4rem;
}

.required-star { color: #ef4444; }
.optional-tag { font-size: 0.72rem; color: #94a3b8; font-weight: 600; }

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
    gap: 1rem;
}

/* Dropzone */
.file-dropzone {
    border: 2px dashed #93c5fd;
    border-radius: 18px;
    background: #f0f9ff;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.file-dropzone:hover,
.file-dropzone.dragging {
    border-color: #2563eb;
    background: #e0f2fe;
}

.file-dropzone.has-file {
    border-style: solid;
    border-color: #86efac;
    background: #f0fdf4;
}

.hidden-file-input {
    display: none;
}

.dropzone-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
}

.dropzone-icon {
    font-size: 2.2rem;
}

.dropzone-prompt {
    font-family: var(--font-heading);
    font-size: 0.95rem;
    color: #0369a1;
}

.dropzone-hint {
    font-size: 0.75rem;
    color: #64748b;
}

.dropzone-filled {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.file-info-badge {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.file-icon {
    font-size: 1.7rem;
}

.file-details {
    text-align: left;
}

.file-name {
    display: block;
    font-family: var(--font-heading);
    font-size: 0.88rem;
    font-weight: 800;
    color: #1e293b;
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-size {
    font-size: 0.72rem;
    color: #64748b;
}

.btn-remove-file {
    background: #fee2e2;
    color: #b91c1c;
    border: none;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
}

/* Media Live Preview */
.live-preview-box {
    margin-top: 0.85rem;
    padding: 0.85rem;
    background: #ffffff;
    border-radius: 14px;
    border: 1.5px solid #e2e8f0;
}

.preview-tag {
    display: block;
    font-size: 0.68rem;
    font-weight: 800;
    color: #64748b;
    margin-bottom: 0.45rem;
}

.preview-img {
    max-width: 100%;
    max-height: 220px;
    object-fit: contain;
    border-radius: 10px;
}

.preview-audio {
    width: 100%;
}

.preview-video {
    max-width: 100%;
    max-height: 240px;
    border-radius: 10px;
}

/* Submit Block Button */
.form-submit-row {
    margin-top: 1.5rem;
}

.btn-3d-add-block {
    width: 100%;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border: none;
    border-radius: 16px;
    padding: 0.95rem 1.5rem;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 1rem;
    color: #ffffff;
    box-shadow: 0 4px 0 #9a3412, 0 8px 16px rgba(234, 88, 12, 0.35);
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-3d-add-block:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #9a3412, 0 12px 20px rgba(234, 88, 12, 0.45);
}

.btn-3d-add-block:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 2px 0 #9a3412;
}

.btn-3d-add-block:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Timeline Column */
.timeline-card {
    display: flex;
    flex-direction: column;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    border-bottom: 1.5px solid #f1f5f9;
    margin-bottom: 1.25rem;
}

.timeline-title-wrap {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.timeline-icon {
    font-size: 1.8rem;
}

.timeline-title {
    font-family: var(--font-heading);
    font-size: 1.15rem;
    font-weight: 900;
    margin: 0;
    color: var(--brand-navy);
}

.timeline-subtitle {
    font-size: 0.75rem;
    color: #64748b;
}

.btn-return-map {
    background: #dcfce7;
    color: #15803d;
    border: none;
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.78rem;
    padding: 0.45rem 0.85rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.btn-return-map:hover {
    transform: scale(1.05);
}

.contents-feed {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    max-height: 640px;
    overflow-y: auto;
    padding-right: 0.35rem;
}

.feed-block-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 1rem;
    transition: transform 0.2s ease;
}

.feed-block-card:hover {
    transform: translateX(3px);
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.feed-block-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.35rem;
}

.feed-type-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.72rem;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    background: #e0f2fe;
    color: #0369a1;
}

.feed-time {
    font-size: 0.7rem;
    color: #94a3b8;
}

.feed-block-title {
    font-family: var(--font-heading);
    font-size: 0.92rem;
    font-weight: 800;
    margin: 0 0 0.35rem 0;
    color: #1e293b;
}

.feed-text-preview {
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.4;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.feed-media-wrap {
    margin-top: 0.45rem;
}

.feed-img {
    max-width: 100%;
    max-height: 120px;
    border-radius: 8px;
    object-fit: cover;
}

.feed-audio-player {
    width: 100%;
    height: 36px;
}

.feed-video-player {
    max-width: 100%;
    max-height: 140px;
    border-radius: 8px;
}

.external-video-link {
    display: inline-block;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 0.78rem;
    color: #2563eb;
    text-decoration: underline;
}

.feed-caption {
    margin-top: 0.35rem;
    font-size: 0.72rem;
    color: #64748b;
}

.empty-feed-card {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #f8fafc;
    border-radius: 20px;
    border: 2px dashed #cbd5e1;
}

.empty-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.empty-feed-card h4 {
    font-family: var(--font-heading);
    font-size: 1.05rem;
    font-weight: 900;
    color: #334155;
    margin: 0 0 0.35rem 0;
}

.empty-feed-card p {
    font-size: 0.82rem;
    color: #64748b;
    margin: 0;
    line-height: 1.45;
}

.error-msg {
    margin: 0.35rem 0 0 0;
    color: #ef4444;
    font-size: 0.78rem;
    font-weight: 700;
}

@media (max-width: 960px) {
    .studio-grid {
        grid-template-columns: 1fr;
    }
}
</style>

