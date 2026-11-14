<script setup>
import { computed } from 'vue';
import { useSaveQueue } from '@/Composables/useSaveQueue.js';

const { state, pendingCount, overallProgress, retry, dismiss, closePanel } = useSaveQueue();

const failedCount = computed(() => state.jobs.filter((job) => job.status === 'failed').length);

const headline = computed(() => {
    if (pendingCount.value > 0) {
        return 'Saving & uploading…';
    }

    return failedCount.value > 0 ? `${failedCount.value} failed — retry below` : 'All saved';
});

const jobIcon = (job) => {
    if (job.type === 'upload') {
        return '⬆️';
    }

    return job.contentId ? '💾' : '✨';
};
</script>

<template>
    <Transition name="save-queue-pop">
        <aside v-if="state.isPanelOpen && state.jobs.length" class="save-queue-panel" role="status" aria-live="polite">
            <header class="save-queue-header">
                <div>
                    <strong>{{ headline }}</strong>
                    <small>{{ pendingCount }} pending · {{ overallProgress }}% overall</small>
                </div>
                <button type="button" class="save-queue-close" aria-label="Close save queue" @click="closePanel">✕</button>
            </header>

            <ul class="save-queue-list">
                <li v-for="job in state.jobs" :key="job.id" class="save-queue-item" :class="`is-${job.status}`">
                    <div class="save-queue-row">
                        <span class="save-queue-title">{{ jobIcon(job) }} {{ job.label }}</span>
                        <span class="save-queue-percent">
                            <template v-if="job.status === 'queued'">Queued</template>
                            <template v-else-if="job.status === 'failed'">Failed</template>
                            <template v-else>{{ job.progress }}%</template>
                        </span>
                    </div>
                    <small v-if="job.meta.context" class="save-queue-context">{{ job.meta.context }}</small>
                    <div class="save-queue-track">
                        <div class="save-queue-bar" :style="{ width: `${job.progress}%` }"></div>
                    </div>
                    <div v-if="job.status === 'failed'" class="save-queue-error">
                        <span>{{ job.error }}</span>
                        <button type="button" @click="retry(job)">Retry</button>
                        <button type="button" @click="dismiss(job)">Dismiss</button>
                    </div>
                </li>
            </ul>
        </aside>
    </Transition>
</template>

<style scoped>
.save-queue-panel {
    position: fixed;
    right: 1.25rem;
    bottom: 1.25rem;
    z-index: 1000;
    width: min(340px, calc(100vw - 2rem));
    max-height: 50vh;
    display: flex;
    flex-direction: column;
    border-radius: 18px;
    background: var(--px-surface-card);
    border: 2px solid var(--px-line);
    box-shadow: 0 18px 40px rgba(var(--px-shadow-rgb), 0.25);
    font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--px-ink);
    overflow: hidden;
}

.save-queue-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #0F172A;
    color: #F8FAFC;
}

.save-queue-header strong {
    display: block;
    font-family: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 0.9rem;
}

.save-queue-header small {
    color: var(--px-ink-subtle);
    font-size: 0.72rem;
    font-weight: 700;
}

.save-queue-close {
    border: none;
    background: transparent;
    color: var(--px-ink-subtle);
    font-size: 0.9rem;
    cursor: pointer;
}

.save-queue-list {
    list-style: none;
    margin: 0;
    padding: 0.5rem 0.75rem 0.75rem;
    overflow-y: auto;
}

.save-queue-item {
    padding: 0.55rem 0.25rem;
    border-bottom: 1px solid var(--px-line);
}

.save-queue-item:last-child {
    border-bottom: none;
}

.save-queue-row {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 800;
}

.save-queue-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.save-queue-context {
    display: block;
    color: var(--px-ink-muted);
    font-size: 0.68rem;
    font-weight: 700;
}

.save-queue-percent {
    flex-shrink: 0;
    color: var(--px-tint-info-ink);
    font-variant-numeric: tabular-nums;
}

.save-queue-track {
    height: 6px;
    margin-top: 0.35rem;
    border-radius: 9999px;
    background: var(--px-track);
    overflow: hidden;
}

.save-queue-bar {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #38BDF8, #6366F1);
    transition: width 0.25s ease;
}

.save-queue-item.is-done .save-queue-bar {
    background: #22C55E;
}

.save-queue-item.is-done .save-queue-percent {
    color: var(--px-tint-success-ink);
}

.save-queue-item.is-failed .save-queue-bar {
    background: #EF4444;
}

.save-queue-item.is-failed .save-queue-percent {
    color: var(--px-tint-danger-ink);
}

.save-queue-item.is-queued .save-queue-percent {
    color: var(--px-ink-muted);
}

.save-queue-error {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.35rem;
    font-size: 0.72rem;
    color: var(--px-tint-danger-ink);
}

.save-queue-error span {
    flex: 1;
}

.save-queue-error button {
    border: 1px solid var(--px-tint-danger-line);
    border-radius: 9999px;
    background: color-mix(in srgb, var(--px-tint-danger-bg) 55%, var(--px-surface-card));
    color: var(--px-tint-danger-ink);
    padding: 0.15rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 800;
    cursor: pointer;
}

.save-queue-pop-enter-active,
.save-queue-pop-leave-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.save-queue-pop-enter-from,
.save-queue-pop-leave-to {
    opacity: 0;
    transform: translateY(16px);
}
</style>
