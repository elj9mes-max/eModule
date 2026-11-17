<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * 6-digit join card: a classroom code opens that classroom, a module code opens Code Adventures.
 */
const joinCode = ref('');
const isJoining = ref(false);
const joinError = ref(null);
const joinSuccess = ref(null);

const onCodeInput = (e) => {
    joinCode.value = e.target.value.replace(/\D/g, '').slice(0, 6);
    joinError.value = null;
    joinSuccess.value = null;
};

const submitJoinCode = () => {
    const code = joinCode.value.trim();
    if (!/^\d{6}$/.test(code)) {
        joinError.value = 'Please enter a 6-digit number (e.g. 482910).';
        return;
    }

    isJoining.value = true;
    joinError.value = null;
    joinSuccess.value = null;

    router.post(
        '/students/modules/join',
        { join_code: code },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                isJoining.value = false;
                joinCode.value = '';
                joinSuccess.value = page.props.flash?.success || 'Adventure unlocked! Welcome to your new reading world!';
                window.setTimeout(() => {
                    joinSuccess.value = null;
                }, 6000);
            },
            onError: (errors) => {
                isJoining.value = false;
                joinError.value = errors.join_code || 'Unable to join adventure. Please check the code and try again.';
            },
        },
    );
};
</script>

<template>
    <div class="clay-join-quest-card">
        <span class="join-bubble-gloss" aria-hidden="true"></span>
        <div class="join-card-decor-orb join-orb-1" aria-hidden="true"></div>

        <div class="join-card-content">
            <div class="join-header-row">
                <div class="join-title-pod">
                    <span class="join-icon-emoji" aria-hidden="true">🔑</span>
                    <div class="join-meta">
                        <strong class="join-title">Join Classroom</strong>
                        <span class="join-sub">Enter 6-digit code</span>
                    </div>
                </div>
                <span class="join-badge-pill">QUICK JOIN</span>
            </div>

            <form class="join-form-row" @submit.prevent="submitJoinCode">
                <div class="join-input-wrap">
                    <input
                        :value="joinCode"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        class="clay-code-input"
                        :disabled="isJoining"
                        aria-label="Enter 6-digit classroom code"
                        @input="onCodeInput"
                    />
                    <span v-if="joinCode.length > 0" class="input-counter-pill">
                        {{ joinCode.length }}/6
                    </span>
                </div>

                <button
                    type="submit"
                    class="clay-join-submit-btn"
                    :disabled="isJoining || joinCode.length !== 6"
                >
                    <span class="cta-btn-shine" aria-hidden="true"></span>
                    <span v-if="isJoining" class="join-loader-icon" aria-hidden="true">🌀</span>
                    <span>{{ isJoining ? '...' : '🚀 JOIN' }}</span>
                </button>
            </form>
        </div>

        <!-- Inline Alerts / Feedback -->
        <transition name="alert-pop">
            <div v-if="joinError" class="join-feedback-banner feedback-error" role="alert">
                <span class="feedback-icon">⚠️</span>
                <span class="feedback-text">{{ joinError }}</span>
                <button type="button" class="feedback-dismiss-btn" aria-label="Dismiss error" @click="joinError = null">✕</button>
            </div>
        </transition>

        <transition name="alert-pop">
            <div v-if="joinSuccess" class="join-feedback-banner feedback-success" role="status">
                <span class="feedback-icon">🎉</span>
                <span class="feedback-text">{{ joinSuccess }}</span>
                <button type="button" class="feedback-dismiss-btn" aria-label="Dismiss message" @click="joinSuccess = null">✕</button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.clay-join-quest-card {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    position: relative;
    overflow: hidden;
    border-radius: 20px;
    padding: 0.85rem 1rem;
    background: linear-gradient(135deg, #312E81 0%, #3730A3 50%, #4F46E5 100%);
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 6px 0 #1E1B4B,
        0 10px 20px rgba(30, 27, 75, 0.3),
        inset 0 2px 4px rgba(255, 255, 255, 0.35);
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    box-sizing: border-box;
}

.join-bubble-gloss {
    position: absolute;
    top: 3px;
    left: 14px;
    right: 14px;
    height: 8px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.join-card-decor-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.join-orb-1 {
    width: 80px;
    height: 80px;
    top: -20px;
    right: -15px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.28) 0%, transparent 70%);
}

.join-card-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.join-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.join-title-pod {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.join-icon-emoji {
    font-size: 1.35rem;
    line-height: 1;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
}

.join-meta {
    display: flex;
    flex-direction: column;
}

.join-title {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 800;
    line-height: 1.1;
    color: #FFFFFF;
}

.join-sub {
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.75);
    font-weight: 600;
}

.join-badge-pill {
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    background: rgba(245, 158, 11, 0.25);
    color: #FDE68A;
    border: 1px solid rgba(251, 191, 36, 0.5);
    padding: 0.12rem 0.45rem;
    border-radius: 9999px;
}

.join-form-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
}

.join-input-wrap {
    position: relative;
    flex: 1;
    min-width: 0;
}

.clay-code-input {
    width: 100%;
    height: 40px;
    padding: 0 0.65rem;
    font-family: var(--font-display);
    font-size: 1.12rem;
    font-weight: 800;
    letter-spacing: 0.22em;
    text-align: center;
    color: var(--px-ink);
    background: var(--px-surface-card);
    border: 2px solid var(--px-tint-info-line);
    border-radius: 12px;
    box-shadow:
        inset 0 2px 4px rgba(30, 27, 75, 0.15),
        0 2.5px 0 var(--px-tint-info-line);
    outline: none;
    box-sizing: border-box;
    transition: all 0.18s ease;
}

.clay-code-input::placeholder {
    color: #A5B4FC;
    letter-spacing: 0.16em;
    opacity: 0.7;
}

.clay-code-input:focus {
    border-color: #F59E0B;
    box-shadow:
        inset 0 2px 4px rgba(245, 158, 11, 0.2),
        0 3px 0 #D97706,
        0 0 0 2.5px rgba(245, 158, 11, 0.3);
}

.clay-code-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.input-counter-pill {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.56rem;
    font-weight: 800;
    font-family: var(--font-display);
    background: #4F46E5;
    color: #FFFFFF;
    padding: 0.08rem 0.3rem;
    border-radius: 4px;
    pointer-events: none;
}

.clay-join-submit-btn {
    position: relative;
    overflow: hidden;
    height: 40px;
    padding: 0 0.85rem;
    border: 2px solid var(--px-card-border);
    border-radius: 12px;
    background: linear-gradient(145deg, #10B981 0%, #059669 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    cursor: pointer;
    box-shadow:
        0 3.5px 0 #065F46,
        0 6px 12px rgba(5, 150, 105, 0.3),
        inset 0 1.5px 2px rgba(255, 255, 255, 0.45);
    transition: transform 0.14s var(--spring-bounce), box-shadow 0.14s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.cta-btn-shine {
    position: absolute;
    top: 2px;
    left: 8px;
    right: 8px;
    height: 35%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.5) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.clay-join-submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 5px 0 #065F46,
        0 8px 16px rgba(5, 150, 105, 0.4);
}

.clay-join-submit-btn:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 1.5px 0 #065F46;
}

.clay-join-submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: linear-gradient(145deg, #6B7280 0%, #4B5563 100%);
    box-shadow: 0 2.5px 0 #374151;
}

.join-loader-icon {
    display: inline-block;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.join-feedback-banner {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.65rem;
    border-radius: 10px;
    font-family: var(--font-body);
    font-size: 0.76rem;
    font-weight: 700;
}

.feedback-error {
    background: rgba(239, 68, 68, 0.25);
    border: 1.5px solid rgba(248, 113, 113, 0.7);
    color: #FEE2E2;
}

.feedback-success {
    background: rgba(16, 185, 129, 0.3);
    border: 1.5px solid rgba(52, 211, 153, 0.8);
    color: #D1FAE5;
}

.feedback-icon {
    font-size: 0.95rem;
}

.feedback-text {
    flex: 1;
}

.feedback-dismiss-btn {
    background: transparent;
    border: none;
    color: inherit;
    font-size: 0.8rem;
    font-weight: 800;
    cursor: pointer;
    padding: 0.1rem 0.25rem;
    border-radius: 4px;
    opacity: 0.75;
}

.feedback-dismiss-btn:hover {
    opacity: 1;
}

.alert-pop-enter-active,
.alert-pop-leave-active {
    transition: all 0.25s var(--spring-bounce);
}

.alert-pop-enter-from,
.alert-pop-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
