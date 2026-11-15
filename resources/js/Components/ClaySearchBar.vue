<script setup>
import { ref } from 'vue';

/**
 * Compact clay-styled search field with a 3D magnifier orb, clear button and result counter.
 */
const searchQuery = defineModel({ type: String, default: '' });

defineProps({
    placeholder: {
        type: String,
        default: 'Search…',
    },
    label: {
        type: String,
        default: 'Search',
    },
    resultCount: {
        type: Number,
        default: null,
    },
});

const searchInput = ref(null);

const clearSearch = () => {
    searchQuery.value = '';
    searchInput.value?.focus();
};
</script>

<template>
    <div class="clay-search" :class="{ 'is-active': searchQuery.length > 0 }" role="search">
        <span class="clay-search__orb" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="15" height="15">
                <circle cx="10.5" cy="10.5" r="6" fill="none" stroke="currentColor" stroke-width="3" />
                <path d="M15 15l5 5" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" />
            </svg>
        </span>

        <input
            ref="searchInput"
            v-model="searchQuery"
            type="search"
            class="clay-search__input"
            :placeholder="placeholder"
            :aria-label="label"
            autocomplete="off"
            spellcheck="false"
            @keydown.esc="clearSearch"
        />

        <span
            v-if="searchQuery.length > 0 && resultCount !== null"
            class="clay-search__count"
            aria-live="polite"
        >
            {{ resultCount }} found
        </span>

        <button
            v-if="searchQuery.length > 0"
            type="button"
            class="clay-search__clear"
            aria-label="Clear search"
            @click="clearSearch"
        >
            <span class="clay-search__clear-face">✕</span>
        </button>
    </div>
</template>

<style scoped>
.clay-search {
    --search-hue: 255;
    --search-ink: #1D2440;
    --search-shadow: oklch(40% 0.12 var(--search-hue) / 0.22);

    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 0 1 20rem;
    width: 20rem;
    min-width: 11rem;
    max-width: 100%;
    min-height: 44px;
    padding: 0.3rem 0.35rem 0.3rem 0.35rem;
    border: 2.5px solid var(--search-ink);
    border-radius: 9999px;
    background: linear-gradient(180deg, #F3EFE4 0%, #FFFFFF 100%);
    box-shadow:
        inset 0 3px 6px var(--search-shadow),
        inset 0 -2px 0 rgba(255, 255, 255, 0.9),
        0 3px 0 var(--search-ink);
}

.clay-search::after {
    content: "";
    position: absolute;
    inset: -5px;
    border-radius: inherit;
    border: 3px solid #FBBF24;
    opacity: 0;
    transform: scale(0.97);
    transition: opacity 0.2s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}

.clay-search:focus-within::after {
    opacity: 1;
    transform: none;
}

.clay-search__orb {
    flex: none;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: #FFFFFF;
    background: radial-gradient(circle at 32% 26%, #8FD0FF 0%, #2395EB 52%, #0E5287 100%);
    box-shadow:
        inset 0 2px 0 rgba(255, 255, 255, 0.55),
        0 2px 0 #0E5287,
        0 4px 8px rgba(35, 149, 235, 0.35);
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.clay-search__orb svg {
    filter: drop-shadow(0 1px 0 rgba(14, 82, 135, 0.6));
}

.clay-search:focus-within .clay-search__orb,
.clay-search.is-active .clay-search__orb {
    transform: rotate(-14deg) scale(1.08);
}

.clay-search__input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: none;
    background: transparent;
    padding: 0.2rem 0;
    font-family: var(--font-ui, 'Nunito', sans-serif);
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--search-ink);
}

.clay-search__input::placeholder {
    color: #8A92AD;
    font-weight: 700;
}

.clay-search__input::-webkit-search-cancel-button {
    display: none;
}

.clay-search__count {
    flex: none;
    padding: 0.18rem 0.55rem;
    border-radius: 9999px;
    font-family: var(--font-game, 'Fredoka', sans-serif);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.03em;
    white-space: nowrap;
    color: #5B3A00;
    background: linear-gradient(180deg, #FDE68A 0%, #FBBF24 100%);
    border: 2px solid #B45309;
    box-shadow: inset 0 1.5px 0 rgba(255, 255, 255, 0.6);
    animation: clay-search-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.clay-search__clear {
    flex: none;
    width: 32px;
    height: 32px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: #B91C1C;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    animation: clay-search-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.clay-search__clear-face {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    font-size: 0.75rem;
    font-weight: 900;
    color: #FFFFFF;
    background: linear-gradient(180deg, #FCA5A5 0%, #EF4444 60%);
    box-shadow: inset 0 2px 0 rgba(255, 255, 255, 0.5);
    transform: translateY(-3px);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@media (hover: hover) {
    .clay-search__clear:hover .clay-search__clear-face {
        transform: translateY(-4px);
    }
}

.clay-search__clear:active .clay-search__clear-face {
    transform: translateY(-1px) scale(0.96, 0.92);
    transition-duration: 0.06s;
}

.clay-search__clear:focus-visible {
    outline: 3px solid #FBBF24;
    outline-offset: 2px;
}

@keyframes clay-search-pop {
    0% { transform: scale(0.6); opacity: 0; }
    60% { transform: scale(1.1, 0.92); }
    80% { transform: scale(0.97, 1.04); }
    100% { transform: none; opacity: 1; }
}

@media (max-width: 640px) {
    .clay-search {
        flex-basis: 100%;
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .clay-search *,
    .clay-search::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
