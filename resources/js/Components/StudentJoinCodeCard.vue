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
        <div class="join-card-decor-orb join-orb-2" aria-hidden="true"></div>

        <div class="join-card-body">

            <div class="join-copy-block">
                <div class="join-badge-row">
                    <span class="join-quest-kicker">
                        <span class="kicker-key-dot" aria-hidden="true"></span>
                        Enter Class Code
                    </span>
                    <span class="join-subbadge">6-Digit Code</span>
                </div>
            </div>

            <form class="join-action-pod" @submit.prevent="submitJoinCode">
                <div class="join-input-group">
                    <input
                        :value="joinCode"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        class="clay-code-input"
                        :disabled="isJoining"
                        aria-label="Enter 6-digit module quest code"
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
                    <span>{{ isJoining ? 'UNLOCKING...' : '🚀 JOIN QUEST' }}</span>
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
}

.cta-btn-shine {
    position: absolute;
    top: 3px;
    left: 14px;
    right: 14px;
    height: 35%;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, calc(0.55 * var(--px-gloss, 1))) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.clay-join-quest-card {
    position: relative;
    overflow: hidden;
    border-radius: 26px;
    padding: 1.15rem 1.35rem;
    background: linear-gradient(135deg, #312E81 0%, #3730A3 45%, #4F46E5 100%);
    border: 2.5px solid var(--px-card-border);
    box-shadow:
        0 8px 0 #1E1B4B,
        0 14px 28px rgba(30, 27, 75, 0.35),
        inset 0 3px 6px rgba(255, 255, 255, 0.35);
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    transition: transform 0.25s var(--spring-bounce);
}

.join-bubble-gloss {
    position: absolute;
    top: 5px;
    left: 20px;
    right: 20px;
    height: 12px;
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
    width: 110px;
    height: 110px;
    top: -30px;
    right: -20px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.3) 0%, transparent 70%);
}

.join-orb-2 {
    width: 90px;
    height: 90px;
    bottom: -35px;
    left: 15%;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.4) 0%, transparent 70%);
}

.join-card-body {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1.1rem;
    flex-wrap: wrap;
}

.join-icon-pod {
    position: relative;
    width: 58px;
    height: 58px;
    border-radius: 20px;
    background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 5px 0 #92400E,
        0 10px 18px rgba(180, 83, 9, 0.4),
        inset 0 2px 4px rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.join-icon-emoji {
    font-size: 1.75rem;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.3));
}

.join-icon-sparkle {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.95rem;
    animation: sparkle-float 2.4s ease-in-out infinite;
}

@keyframes sparkle-float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-3px) scale(1.15); }
}

.join-copy-block {
    flex: 1 1 260px;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.join-badge-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.join-quest-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.18rem 0.55rem;
    border-radius: 9999px;
    background: rgba(245, 158, 11, 0.25);
    border: 1.2px solid rgba(251, 191, 36, 0.6);
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #FDE68A;
}

.kicker-key-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #FBBF24;
    box-shadow: 0 0 6px #F59E0B;
}

.join-subbadge {
    font-size: 0.65rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.7);
    font-family: var(--font-display);
}

.join-headline {
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    line-height: 1.2;
    color: #FFFFFF;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
}

.join-description {
    font-family: var(--font-body);
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.82);
    margin: 0;
}

.join-action-pod {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex: 1 1 300px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.join-input-group {
    position: relative;
    flex: 1 1 140px;
    min-width: 140px;
    max-width: 220px;
}

.clay-code-input {
    width: 100%;
    height: 48px;
    padding: 0 0.85rem;
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: 0.28em;
    text-align: center;
    color: var(--px-ink);
    background: var(--px-surface-card);
    border: 2.5px solid var(--px-tint-info-line);
    border-radius: 16px;
    box-shadow:
        inset 0 3px 5px rgba(30, 27, 75, 0.15),
        0 4px 0 var(--px-tint-info-line);
    outline: none;
    transition: all 0.2s ease;
}

.clay-code-input::placeholder {
    color: #A5B4FC;
    letter-spacing: 0.18em;
    opacity: 0.75;
}

.clay-code-input:focus {
    border-color: #F59E0B;
    box-shadow:
        inset 0 2px 4px rgba(245, 158, 11, 0.2),
        0 4px 0 #D97706,
        0 0 0 3px rgba(245, 158, 11, 0.35);
}

.clay-code-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.input-counter-pill {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.6rem;
    font-weight: 800;
    font-family: var(--font-display);
    background: #4F46E5;
    color: #FFFFFF;
    padding: 0.1rem 0.35rem;
    border-radius: 6px;
    pointer-events: none;
}

.clay-join-submit-btn {
    position: relative;
    overflow: hidden;
    height: 48px;
    padding: 0 1.25rem;
    border: 2px solid var(--px-card-border);
    border-radius: 16px;
    background: linear-gradient(145deg, #10B981 0%, #059669 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    cursor: pointer;
    box-shadow:
        0 5px 0 #065F46,
        0 8px 16px rgba(5, 150, 105, 0.35),
        inset 0 2px 3px rgba(255, 255, 255, 0.4);
    transition: transform 0.15s var(--spring-bounce), box-shadow 0.15s ease;
    white-space: nowrap;
}

.clay-join-submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 7px 0 #065F46,
        0 12px 20px rgba(5, 150, 105, 0.45),
        inset 0 2px 4px rgba(255, 255, 255, 0.5);
}

.clay-join-submit-btn:active:not(:disabled) {
    transform: translateY(3px);
    box-shadow:
        0 2px 0 #065F46,
        0 3px 6px rgba(5, 150, 105, 0.3);
}

.clay-join-submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: linear-gradient(145deg, #6B7280 0%, #4B5563 100%);
    box-shadow: 0 4px 0 #374151;
    border-color: rgba(255, 255, 255, 0.4);
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
    gap: 0.55rem;
    padding: 0.55rem 0.85rem;
    border-radius: 14px;
    font-family: var(--font-body);
    font-size: 0.82rem;
    font-weight: 700;
}

.feedback-error {
    background: rgba(239, 68, 68, 0.2);
    border: 1.5px solid rgba(248, 113, 113, 0.6);
    color: #FEE2E2;
}

.feedback-success {
    background: rgba(16, 185, 129, 0.25);
    border: 1.5px solid rgba(52, 211, 153, 0.7);
    color: #D1FAE5;
}

.feedback-icon {
    font-size: 1.05rem;
}

.feedback-text {
    flex: 1;
}

.feedback-dismiss-btn {
    background: transparent;
    border: none;
    color: inherit;
    font-size: 0.85rem;
    font-weight: 800;
    cursor: pointer;
    padding: 0.15rem 0.35rem;
    border-radius: 6px;
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
    transform: translateY(-6px);
}
</style>
