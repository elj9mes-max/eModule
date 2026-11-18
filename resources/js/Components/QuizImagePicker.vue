<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { csrfHeaders } from '@/Services/csrf';

/**
 * Click-or-drop picture picker for quiz cards.
 * Uploads immediately to `uploadUrl` and emits the stored { path, url } so the parent
 * can keep the path in its own state until the quiz is saved.
 */
const props = defineProps({
    imageUrl: {
        type: String,
        default: null,
    },
    uploadUrl: {
        type: String,
        required: true,
    },
    maxMegabytes: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['uploaded', 'removed']);

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

const fileInputRef = ref(null);
const isUploading = ref(false);
const isDragOver = ref(false);
const errorMessage = ref('');

// The parent remounts this picker per card; an upload finishing after that must not attach to another card.
let isUnmounted = false;
onBeforeUnmount(() => {
    isUnmounted = true;
});

const openFilePicker = () => {
    if (!isUploading.value) {
        fileInputRef.value?.click();
    }
};

const validateFile = (file) => {
    if (!ACCEPTED_TYPES.includes(file.type)) {
        return 'Use a JPG, PNG, WEBP, or GIF picture.';
    }
    if (file.size > props.maxMegabytes * 1024 * 1024) {
        return `Pictures may not be larger than ${props.maxMegabytes} MB.`;
    }

    return '';
};

const uploadFile = async (file) => {
    errorMessage.value = validateFile(file);
    if (errorMessage.value) {
        return;
    }

    const body = new FormData();
    body.append('image', file);
    isUploading.value = true;

    try {
        const response = await fetch(props.uploadUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...csrfHeaders(),
            },
            body,
        });
        const data = await response.json().catch(() => null);

        if (isUnmounted) {
            return;
        }

        if (!response.ok || !data?.path) {
            errorMessage.value = data?.errors?.image?.[0] || data?.message || 'The picture could not be uploaded. Please try again.';
            return;
        }

        emit('uploaded', { path: data.path, url: data.url });
    } catch {
        if (isUnmounted) {
            return;
        }
        errorMessage.value = 'Network problem while uploading. Please try again.';
    } finally {
        isUploading.value = false;
    }
};

const handleFileChosen = (event) => {
    const [file] = event.target.files || [];
    event.target.value = '';
    if (file) {
        uploadFile(file);
    }
};

const handleDrop = (event) => {
    isDragOver.value = false;
    const [file] = event.dataTransfer?.files || [];
    if (file) {
        uploadFile(file);
    }
};

const removeImage = () => {
    errorMessage.value = '';
    emit('removed');
};
</script>

<template>
    <div class="quiz-image-picker">
        <div v-if="imageUrl" class="picker-preview">
            <img :src="imageUrl" alt="Card picture preview" class="picker-preview-img" />
            <div class="picker-preview-actions">
                <button type="button" class="picker-btn" :disabled="isUploading" @click="openFilePicker">
                    {{ isUploading ? 'Uploading…' : '🔄 Replace' }}
                </button>
                <button type="button" class="picker-btn is-danger" :disabled="isUploading" @click="removeImage">
                    ✕ Remove
                </button>
            </div>
        </div>

        <button
            v-else
            type="button"
            class="picker-dropzone"
            :class="{ 'is-drag-over': isDragOver, 'is-uploading': isUploading }"
            :disabled="isUploading"
            @click="openFilePicker"
            @dragover.prevent="isDragOver = true"
            @dragleave="isDragOver = false"
            @drop.prevent="handleDrop"
        >
            <span class="dropzone-icon" aria-hidden="true">{{ isUploading ? '⏳' : '🖼️' }}</span>
            <span class="dropzone-title">{{ isUploading ? 'Uploading picture…' : 'Add a picture' }}</span>
            <span class="dropzone-sub">Click or drop an image • JPG, PNG, WEBP, GIF • up to {{ maxMegabytes }} MB</span>
        </button>

        <p v-if="errorMessage" class="picker-error" role="alert">{{ errorMessage }}</p>

        <input
            ref="fileInputRef"
            type="file"
            :accept="ACCEPTED_TYPES.join(',')"
            class="picker-file-input"
            tabindex="-1"
            aria-hidden="true"
            @change="handleFileChosen"
        />
    </div>
</template>

<style scoped>
.quiz-image-picker {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.picker-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    width: 100%;
    min-height: 110px;
    padding: 0.85rem;
    border-radius: 14px;
    border: 2px dashed #A5B4FC;
    background: #EEF2FF;
    color: #3730A3;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
}

.picker-dropzone:hover:not(:disabled),
.picker-dropzone.is-drag-over {
    border-color: #4F46E5;
    background: #E0E7FF;
    transform: translateY(-1px);
}

.picker-dropzone.is-uploading {
    cursor: progress;
    opacity: 0.8;
}

.dropzone-icon {
    font-size: 1.5rem;
}

.dropzone-title {
    font-family: var(--font-display, inherit);
    font-size: 0.85rem;
    font-weight: 800;
}

.dropzone-sub {
    font-size: 0.68rem;
    font-weight: 700;
    color: #6366F1;
}

.picker-preview {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.55rem;
    border-radius: 14px;
    border: 2px solid #CBD5E1;
    background: #F8FAFC;
}

.picker-preview-img {
    width: 128px;
    height: 96px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid #FFFFFF;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
    background: #FFFFFF;
}

.picker-preview-actions {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.picker-btn {
    padding: 0.35rem 0.75rem;
    border-radius: 9px;
    border: 1.5px solid #A5B4FC;
    background: #FFFFFF;
    color: #3730A3;
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
}

.picker-btn.is-danger {
    border-color: #FCA5A5;
    background: #FEF2F2;
    color: #DC2626;
}

.picker-btn:disabled {
    opacity: 0.6;
    cursor: progress;
}

.picker-error {
    margin: 0;
    font-size: 0.74rem;
    font-weight: 700;
    color: #DC2626;
}

.picker-file-input {
    display: none;
}
</style>
