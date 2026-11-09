<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SoundController from '@/Components/SoundController.vue';
import { useSound } from '@/Services/soundManager';

const {
    state: soundState,
    playPop,
    playBoing,
    playSparkle,
    playWhoosh,
    playSuccess,
    playBonus,
    playClick,
} = useSound();

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Student',
            email: '',
            role: 'student',
        }),
    },
});

const isMounted = ref(false);
const isLaunching = ref(false);
const parallaxX = ref(0);
const parallaxY = ref(0);

let animationFrameId = null;
let launchTimeoutId = null;

const displayName = computed(() => (props.user?.name || 'Student').trim());

const nameLetters = computed(() =>
    Array.from(displayName.value).map((character) => (character === ' ' ? '\u00A0' : character)),
);

const timeGreeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return { icon: '☀️', text: 'Good morning' };
    }

    if (hour < 18) {
        return { icon: '🌤️', text: 'Good afternoon' };
    }

    return { icon: '🌙', text: 'Good evening' };
});

const floatingIcons = [
    { icon: '📚', top: '14%', left: '6%', delay: '0s', duration: '5.2s', size: 'lg', hideMobile: false },
    { icon: '✏️', top: '8%', left: '22%', delay: '0.7s', duration: '4.6s', size: 'md', hideMobile: true },
    { icon: '⚙️', top: '34%', left: '3%', delay: '1.2s', duration: '6s', size: 'md', hideMobile: true },
    { icon: '💬', top: '52%', left: '15%', delay: '0.4s', duration: '5.5s', size: 'sm', hideMobile: true },
    { icon: '📶', top: '62%', left: '4%', delay: '1.8s', duration: '4.9s', size: 'sm', hideMobile: true },
    { icon: '📖', top: '72%', left: '26%', delay: '2.2s', duration: '5.8s', size: 'md', hideMobile: true },
    { icon: '💡', top: '9%', right: '18%', delay: '0.3s', duration: '4.8s', size: 'md', hideMobile: false },
    { icon: '⚛️', top: '20%', right: '5%', delay: '1s', duration: '5.6s', size: 'lg', hideMobile: true },
    { icon: '💬', top: '36%', right: '20%', delay: '1.6s', duration: '5s', size: 'sm', hideMobile: true },
    { icon: '✏️', top: '45%', right: '3%', delay: '2.4s', duration: '6.2s', size: 'md', hideMobile: true },
    { icon: '📶', top: '60%', right: '14%', delay: '0.9s', duration: '4.4s', size: 'sm', hideMobile: true },
    { icon: '🌠', top: '74%', right: '28%', delay: '1.4s', duration: '5.4s', size: 'sm', hideMobile: false },
    { icon: '⭐', top: '86%', left: '44%', delay: '2s', duration: '4.2s', size: 'sm', hideMobile: true },
    { icon: '⚙️', top: '84%', right: '42%', delay: '0.6s', duration: '6.4s', size: 'sm', hideMobile: true },
];

/**
 * Track the pointer so background layers can drift with a subtle parallax.
 *
 * @param {MouseEvent} event
 */
const handlePointerMove = (event) => {
    if (animationFrameId) {
        return;
    }

    animationFrameId = window.requestAnimationFrame(() => {
        parallaxX.value = (event.clientX / window.innerWidth - 0.5) * 2;
        parallaxY.value = (event.clientY / window.innerHeight - 0.5) * 2;
        animationFrameId = null;
    });
};

const parallaxStyle = computed(() => ({
    '--px': parallaxX.value.toFixed(3),
    '--py': parallaxY.value.toFixed(3),
}));

const exploreDashboard = () => {
    if (isLaunching.value) {
        return;
    }

    isLaunching.value = true;
    playWhoosh();
    playSuccess();

    launchTimeoutId = window.setTimeout(() => {
        router.visit('/students/dashboard', {
            onFinish: () => {
                isLaunching.value = false;
            },
        });
    }, 450);
};

onMounted(() => {
    window.requestAnimationFrame(() => {
        isMounted.value = true;
    });

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isDesktopPointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    if (!prefersReducedMotion && isDesktopPointer) {
        window.addEventListener('mousemove', handlePointerMove, { passive: true });
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', handlePointerMove);

    if (animationFrameId) {
        window.cancelAnimationFrame(animationFrameId);
    }

    if (launchTimeoutId) {
        window.clearTimeout(launchTimeoutId);
    }
});
</script>

<template>
    <Head :title="`Welcome, ${displayName} • WEBREAD`" />

    <div
        class="welcome-page"
        :class="{ 'is-mounted': isMounted, 'is-launching': isLaunching }"
        :style="parallaxStyle"
    >
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Playful Sound & Music HUD Controller (Floating Widget) -->
        <SoundController variant="floating" />

        <!-- LAYER 1: Organic Neumorphic Blobs -->
        <div class="blob-layer" aria-hidden="true">
            <div class="blob blob-left"></div>
            <div class="blob blob-right"></div>
            <div class="blob blob-orange-left"></div>
            <div class="blob blob-orange-right"></div>
        </div>

        <!-- LAYER 2: Extruded Neumorphic Tiles -->
        <div class="tile-layer" aria-hidden="true">
            <div class="neu-tile tile-top"></div>
            <div class="neu-tile tile-right-a"></div>
            <div class="neu-tile tile-right-b"></div>
            <div class="neu-tile tile-bottom"></div>
        </div>

        <!-- LAYER 3: Floating 3D Learning Toy Badges -->
        <div class="icon-layer" aria-hidden="true">
            <span
                v-for="(item, index) in floatingIcons"
                :key="`icon-${index}`"
                class="toy-badge"
                :class="[`toy-${item.size}`, { 'hide-mobile': item.hideMobile }]"
                :style="{
                    top: item.top,
                    left: item.left,
                    right: item.right,
                    '--float-delay': item.delay,
                    '--float-duration': item.duration,
                    '--enter-delay': `${300 + index * 45}ms`,
                }"
                @mouseenter="playPop(1.15)"
                @click="playPop(1.3)"
            >
                {{ item.icon }}
            </span>
        </div>

        <!-- LAYER: Floating 3D Clouds -->
        <div class="clouds-layer" aria-hidden="true">
            <div class="neu-cloud cloud-left">
                <span class="cloud-puff puff-1"></span>
                <span class="cloud-puff puff-2"></span>
                <span class="cloud-puff puff-3"></span>
            </div>
            <div class="neu-cloud cloud-right">
                <span class="cloud-puff puff-1"></span>
                <span class="cloud-puff puff-2"></span>
                <span class="cloud-puff puff-3"></span>
            </div>
        </div>

        <!-- LAYER: Adventure Trail Vectors -->
        <div class="adventure-trail-layer" aria-hidden="true">
            <svg class="trail-svg" viewBox="0 0 1440 900" fill="none" preserveAspectRatio="none">
                <path class="trail-path trail-path-left" d="M 80 820 C 240 680, 340 700, 520 540 C 600 460, 640 480, 720 480" />
                <path class="trail-path trail-path-right" d="M 1360 820 C 1200 680, 1100 700, 920 540 C 840 460, 800 480, 720 480" />
            </svg>
        </div>

        <!-- LAYER 4: Rich 3D Storybook Realms & Character Diorama -->
        <div class="storybook-diorama-layer" aria-hidden="true">
            <!-- Left Mountain Pop-Up Book Island -->
            <div class="diorama-pod pod-book-left" @mouseenter="playPop(0.95)">
                <div class="island-pedestal"></div>
                <img src="/images/book_left_trees_mountains.png" alt="" class="diorama-img book-left-img" />
            </div>

            <!-- Foreground Left Reading Girl -->
            <div class="diorama-pod pod-girl-left" @mouseenter="playSparkle">
                <img src="/images/girl_left_reading_book.png" alt="" class="diorama-img girl-left-img" />
                <div class="char-sparkle-halo halo-left">✨</div>
            </div>

            <!-- Mid-Left Scholar Boy on Book with Laptop -->
            <div class="diorama-pod pod-boy-laptop" @mouseenter="playPop(1.1)">
                <img src="/images/boy_left_on_book_laptop.png" alt="" class="diorama-img boy-laptop-img" />
                <div class="floating-speech-chip chip-left" @mouseenter.stop="playPop(1.25)">
                    <span class="chip-emoji">💡</span>
                    <span>Ready for Chapter 1!</span>
                </div>
            </div>

            <!-- Right River/Waterfall Pop-Up Book Island -->
            <div class="diorama-pod pod-book-right" @mouseenter="playPop(0.95)">
                <div class="island-pedestal"></div>
                <img src="/images/book_right_mountains_river.png" alt="" class="diorama-img book-right-img" />
            </div>

            <!-- Foreground Right Tablet Girl -->
            <div class="diorama-pod pod-girl-right" @mouseenter="playSparkle">
                <img src="/images/girl_right_using_tablet.png" alt="" class="diorama-img girl-right-img" />
                <div class="char-sparkle-halo halo-right">⭐</div>
            </div>

            <!-- Mid-Right Girl with Message & Book -->
            <div class="diorama-pod pod-girl-message" @mouseenter="playPop(1.1)">
                <img src="/images/book_with_message_and_laptop_girl.png" alt="" class="diorama-img girl-message-img" />
                <div class="floating-speech-chip chip-right" @mouseenter.stop="playPop(1.25)">
                    <span class="chip-emoji">💬</span>
                    <span>Reading Quest Awaits!</span>
                </div>
            </div>
        </div>

        <!-- LAYER: Floating Gamified Adventure Loot & Badges -->
        <div class="gamified-badges-layer" aria-hidden="true">
            <!-- Badge 1: Daily Quest (Upper Left) -->
            <div
                class="float-quest-card quest-card-left"
                @mouseenter="playSparkle"
                @click="playSparkle"
            >
                <div class="quest-card-icon">📜</div>
                <div class="quest-card-text">
                    <span class="quest-label">DAILY QUEST</span>
                    <span class="quest-val">Module 1 Storyline</span>
                </div>
                <span class="quest-xp-tag">+150 XP</span>
            </div>

            <!-- Badge 2: Star Vault (Upper Right) -->
            <div
                class="float-quest-card quest-card-right"
                @mouseenter="playSparkle"
                @click="playSparkle"
            >
                <div class="quest-card-icon">💎</div>
                <div class="quest-card-text">
                    <span class="quest-label">READING VAULT</span>
                    <span class="quest-val">Treasure Chest</span>
                </div>
                <span class="quest-badge-pill">UNLOCKED</span>
            </div>

            <!-- Badge 3: Streak Protector (Bottom Left) -->
            <div
                class="float-mini-badge badge-streak"
                @mouseenter="playSparkle"
                @click="playSparkle"
            >
                <span class="badge-icon">🔥</span>
                <span class="badge-text">1-DAY STREAK READY</span>
            </div>

            <!-- Badge 4: Explorer Guild (Bottom Right) -->
            <div
                class="float-mini-badge badge-guild"
                @mouseenter="playSparkle"
                @click="playSparkle"
            >
                <span class="badge-icon">🧭</span>
                <span class="badge-text">EXPLORER GUILD</span>
            </div>
        </div>

        <!-- MAIN HERO -->
        <main class="hero-stage">
            <section class="hero-card" aria-labelledby="welcome-heading">
                <!-- Tactile Hanging Bookmark Ribbon -->
                <div class="hero-bookmark-ribbon" aria-hidden="true">
                    <div class="ribbon-tail"></div>
                    <span class="ribbon-star">⭐</span>
                </div>

                <!-- Corner 3D Gilded Rivets -->
                <div class="corner-rivet rivet-tl" aria-hidden="true"></div>
                <div class="corner-rivet rivet-tr" aria-hidden="true"></div>
                <div class="corner-rivet rivet-bl" aria-hidden="true"></div>
                <div class="corner-rivet rivet-br" aria-hidden="true"></div>

                <!-- Globe ↔ Map Book Journey -->
                <div class="journey-badges" aria-hidden="true">
                    <span class="journey-badge globe">🌍</span>
                    <svg class="journey-path" viewBox="0 0 120 40" fill="none">
                        <path d="M6 30 C 36 2, 84 2, 114 24" />
                    </svg>
                    <span class="journey-badge mapbook">🗺️</span>
                </div>

                <div class="role-chip">
                    <span class="chip-dot"></span>
                    🎒 STUDENT ADVENTURER
                </div>

                <p class="time-greeting">
                    <span class="time-icon">{{ timeGreeting.icon }}</span>
                    {{ timeGreeting.text }}!
                </p>

                <h1 id="welcome-heading" class="greeting">
                    <span class="sparkle sparkle-l1" aria-hidden="true">✦</span>
                    <span class="sparkle sparkle-l2" aria-hidden="true">✨</span>

                    <span class="greeting-hi">Hi,</span>
                    <span class="greeting-name" :aria-label="displayName">
                        <span
                            v-for="(letter, index) in nameLetters"
                            :key="`letter-${index}`"
                            class="name-letter"
                            :style="{ '--i': index }"
                            aria-hidden="true"
                        >{{ letter }}</span>
                    </span><span class="greeting-bang">!</span>

                    <span class="sparkle sparkle-r1" aria-hidden="true">✦</span>
                    <span class="sparkle sparkle-r2" aria-hidden="true">✨</span>
                </h1>

                <p class="subtitle">
                    Your reading adventure awaits! Discover new stories, earn XP, and unlock treasures along the way.
                </p>

                <!-- Welcome Bonus Perk Pill -->
                <div
                    class="welcome-perk-banner"
                    @mouseenter="playBonus"
                    @click="playBonus"
                >
                    <span class="perk-sparkle">✨</span>
                    <span class="perk-text">
                        <strong>+500 XP Welcome Bonus</strong> available when you explore today!
                    </span>
                    <span class="perk-badge">FREE REWARD</span>
                </div>

                <div class="explore-wrap">
                    <span class="explore-halo" aria-hidden="true"></span>
                    <button
                        type="button"
                        class="btn-explore"
                        :disabled="isLaunching"
                        aria-label="Explore your student dashboard"
                        @mouseenter="playPop(1.18)"
                        @click="exploreDashboard"
                    >
                        <span class="btn-gloss" aria-hidden="true"></span>
                        <span class="btn-label">
                            <span class="rocket" aria-hidden="true">🚀</span>
                            {{ isLaunching ? 'LAUNCHING…' : 'EXPLORE' }}
                        </span>
                        <span class="btn-arrow" aria-hidden="true">➔</span>
                    </button>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* ==========================================================================
   CARTOON 3D + NEUMORPHISM GAME TOKENS & BACKGROUND
   ========================================================================== */
.welcome-page {
    --game-navy: #1D2440;
    --game-border: #2B3354;
    --game-blue: #2395EB;
    --game-blue-shadow: #1467A6;
    --game-coral: #FF6B4A;
    --game-coral-shadow: #BF3E22;
    --game-gold: #FFC000;
    --game-gold-shadow: #B88500;
    --game-green: #06D6A0;
    --game-green-shadow: #038F6A;
    --game-purple: #845EC2;
    --game-purple-shadow: #58398A;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --font-game: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;

    /* Neumorphic Tactile Lighting Tokens */
    --neu-bg-base: #F5F0E3;
    --neu-surface: linear-gradient(145deg, #FFFFFF 0%, #F3EFE4 100%);
    --neu-light: rgba(255, 255, 255, 0.92);
    --neu-navy-ambient: rgba(29, 36, 64, 0.12);
    --neu-bevel-light: inset 3px 3px 6px rgba(255, 255, 255, 0.95);
    --neu-bevel-dark: inset -4px -4px 8px rgba(194, 185, 166, 0.38);

    /* Parallax inputs (-1 → 1), set from Vue */
    --px: 0;
    --py: 0;

    position: relative;
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.25rem;
    font-family: var(--font-game);
    color: var(--game-navy);
    background-color: var(--neu-bg-base);
    background-image:
        radial-gradient(circle at 50% 45%, rgba(255, 255, 255, 0.85) 0%, transparent 55%),
        radial-gradient(circle at 85% 80%, rgba(226, 217, 197, 0.6) 0%, transparent 50%),
        radial-gradient(#DFD7C4 2px, transparent 2px),
        radial-gradient(#DFD7C4 2px, var(--neu-bg-base) 2px);
    background-size: 100% 100%, 100% 100%, 36px 36px, 36px 36px;
    background-position: 0 0, 0 0, 0 0, 18px 18px;
    transition: filter 0.45s ease, opacity 0.45s ease, transform 0.45s ease;
}

/* Soft Matte Neumorphic Grain Texture Overlay */
.welcome-page::before {
    content: '';
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 30;
    opacity: 0.035;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}

.welcome-page.is-launching {
    filter: blur(6px) saturate(1.2);
    opacity: 0;
    transform: scale(1.04);
}

/* ==========================================================================
   LAYER 1: ORGANIC BLOBS
   ========================================================================== */
.blob-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    transform: translate(calc(var(--px) * -10px), calc(var(--py) * -10px));
    transition: transform 0.3s ease-out;
}

.blob {
    position: absolute;
    opacity: 0;
    transform: scale(0.6);
    transition: opacity 0.8s ease, transform 0.9s var(--spring-bounce);
    animation: blobMorph 12s ease-in-out infinite alternate;
}

.is-mounted .blob {
    opacity: 1;
    transform: scale(1);
}

.blob-left {
    width: min(34vw, 460px);
    height: min(72vh, 620px);
    top: 4%;
    left: -9%;
    background: linear-gradient(160deg, #38BDF8 0%, var(--game-blue) 55%, var(--game-blue-shadow) 100%);
    border-radius: 42% 58% 63% 37% / 41% 44% 56% 59%;
    box-shadow:
        inset 18px 18px 40px rgba(255, 255, 255, 0.35),
        inset -22px -22px 46px rgba(20, 103, 166, 0.45),
        14px 18px 40px rgba(35, 149, 235, 0.25);
}

.blob-right {
    width: min(32vw, 440px);
    height: min(80vh, 680px);
    top: -6%;
    right: -8%;
    background: linear-gradient(200deg, #5B6CF0 0%, var(--game-purple) 55%, var(--game-purple-shadow) 100%);
    border-radius: 58% 42% 38% 62% / 52% 38% 62% 48%;
    box-shadow:
        inset -18px 18px 40px rgba(255, 255, 255, 0.3),
        inset 22px -22px 46px rgba(88, 57, 138, 0.5),
        -14px 18px 40px rgba(132, 94, 194, 0.25);
    animation-delay: -4s;
}

.blob-orange-left {
    width: min(26vw, 340px);
    height: min(26vw, 340px);
    bottom: -10%;
    left: -6%;
    background: radial-gradient(circle at 35% 30%, #FFB703 0%, var(--game-coral) 60%, var(--game-coral-shadow) 100%);
    border-radius: 55% 45% 50% 50% / 50% 55% 45% 50%;
    box-shadow:
        inset 10px 10px 26px rgba(255, 255, 255, 0.4),
        inset -14px -14px 30px rgba(191, 62, 34, 0.4);
    animation-delay: -2s;
}

.blob-orange-right {
    width: min(24vw, 320px);
    height: min(24vw, 320px);
    bottom: -9%;
    right: -5%;
    background: radial-gradient(circle at 60% 30%, #C4A1FF 0%, var(--game-purple) 55%, var(--game-coral) 100%);
    border-radius: 45% 55% 52% 48% / 55% 45% 55% 45%;
    box-shadow:
        inset -10px 10px 26px rgba(255, 255, 255, 0.4),
        inset 14px -14px 30px rgba(88, 57, 138, 0.4);
    animation-delay: -6s;
}

@keyframes blobMorph {
    0% { border-radius: 42% 58% 63% 37% / 41% 44% 56% 59%; }
    50% { border-radius: 58% 42% 45% 55% / 55% 60% 40% 45%; }
    100% { border-radius: 48% 52% 38% 62% / 38% 52% 48% 62%; }
}

/* ==========================================================================
   LAYER 2: NEUMORPHIC TILES
   ========================================================================== */
.tile-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 2;
    transform: translate(calc(var(--px) * 6px), calc(var(--py) * 6px));
    transition: transform 0.3s ease-out;
}

.neu-tile {
    position: absolute;
    border-radius: 28px;
    background: var(--neu-surface);
    border: 3px solid rgba(43, 51, 84, 0.08);
    box-shadow:
        12px 14px 30px rgba(29, 36, 64, 0.1),
        -10px -10px 24px var(--neu-light),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    opacity: 0;
    transition: opacity 0.7s ease 150ms, transform 0.9s var(--spring-bounce) 150ms;
}

.neu-tile::after {
    content: '';
    position: absolute;
    inset: 14px;
    border-radius: 18px;
    background: linear-gradient(145deg, #E3F2FD 0%, #D6EBFB 100%);
    box-shadow:
        inset 4px 4px 10px rgba(35, 149, 235, 0.18),
        inset -4px -4px 10px rgba(255, 255, 255, 0.9);
}

.tile-top {
    width: 150px;
    height: 70px;
    top: -26px;
    left: 22%;
    transform: translateY(-60px);
    animation: tileDrift 9s ease-in-out infinite alternate;
}

.tile-right-a {
    width: 150px;
    height: 112px;
    top: 24%;
    right: 7%;
    transform: translateX(80px);
    animation: tileDrift 10s ease-in-out infinite alternate-reverse;
}

.tile-right-b {
    width: 150px;
    height: 112px;
    top: 50%;
    right: 7%;
    transform: translateX(80px);
    animation: tileDrift 11s ease-in-out infinite alternate;
}

.tile-bottom {
    width: 130px;
    height: 60px;
    bottom: -24px;
    left: 50%;
    margin-left: -65px;
    transform: translateY(60px);
    animation: tileDrift 8s ease-in-out infinite alternate-reverse;
}

.is-mounted .neu-tile {
    opacity: 1;
    transform: translate(0, 0);
}

@keyframes tileDrift {
    from { translate: 0 0; }
    to { translate: 0 -10px; }
}

/* ==========================================================================
   LAYER 3: FLOATING 3D TOY BADGES
   ========================================================================== */
.icon-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
    transform: translate(calc(var(--px) * 16px), calc(var(--py) * 16px));
    transition: transform 0.3s ease-out;
}

.toy-badge {
    position: absolute;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: linear-gradient(145deg, #FFFFFF 0%, #EEF1F8 100%);
    border: 2.5px solid var(--game-border);
    box-shadow:
        0 4px 0 var(--game-border),
        6px 8px 14px var(--neu-navy-ambient),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95),
        inset -2px -2px 5px rgba(164, 176, 204, 0.4);
    opacity: 0;
    scale: 0.4;
    pointer-events: auto;
    cursor: pointer;
    transition:
        opacity 0.5s ease var(--enter-delay, 300ms),
        scale 0.6s var(--spring-bounce) var(--enter-delay, 300ms),
        transform 0.2s var(--spring-bounce);
    animation: toyFloat var(--float-duration, 5s) ease-in-out var(--float-delay, 0s) infinite alternate;
}

.toy-badge:hover {
    transform: translateY(-8px) scale(1.18) rotate(4deg);
    box-shadow:
        0 6px 0 var(--game-border),
        8px 14px 20px var(--neu-navy-ambient),
        inset 2px 2px 4px rgba(255, 255, 255, 0.95);
}

.is-mounted .toy-badge {
    opacity: 1;
    scale: 1;
}

.toy-sm {
    width: 38px;
    height: 38px;
    font-size: 1.1rem;
    border-radius: 12px;
}

.toy-md {
    width: 48px;
    height: 48px;
    font-size: 1.4rem;
}

.toy-lg {
    width: 58px;
    height: 58px;
    font-size: 1.75rem;
    border-radius: 18px;
}

@keyframes toyFloat {
    0% { transform: translateY(0) rotate(-6deg); }
    100% { transform: translateY(-14px) rotate(6deg); }
}

/* ==========================================================================
   FLOATING 3D NEUMORPHIC CLOUDS
   ========================================================================== */
.clouds-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 2;
    overflow: hidden;
}

.neu-cloud {
    position: absolute;
    filter: drop-shadow(0 14px 24px rgba(29, 36, 64, 0.08));
    opacity: 0.85;
    animation: cloudDrift 24s ease-in-out infinite alternate;
}

.cloud-left {
    top: 6%;
    left: 4%;
    transform: translate(calc(var(--px) * 12px), calc(var(--py) * 8px));
}

.cloud-right {
    top: 10%;
    right: 5%;
    animation-delay: -12s;
    transform: translate(calc(var(--px) * -12px), calc(var(--py) * -8px));
}

.neu-cloud .cloud-puff {
    display: inline-block;
    background: linear-gradient(145deg, #FFFFFF 0%, #E8F4FA 100%);
    border-radius: 9999px;
    border: 3px solid rgba(255, 255, 255, 0.95);
    box-shadow:
        inset 2px 2px 5px rgba(255, 255, 255, 0.9),
        inset -2px -2px 5px rgba(186, 230, 253, 0.5);
    position: relative;
}

.cloud-left .puff-1 { width: 90px; height: 50px; }
.cloud-left .puff-2 { width: 70px; height: 42px; margin-left: -24px; top: -14px; }
.cloud-left .puff-3 { width: 80px; height: 46px; margin-left: -20px; }

.cloud-right .puff-1 { width: 85px; height: 48px; }
.cloud-right .puff-2 { width: 75px; height: 44px; margin-left: -22px; top: -12px; }
.cloud-right .puff-3 { width: 70px; height: 40px; margin-left: -18px; }

@keyframes cloudDrift {
    0% { transform: translateY(0) translateX(0); }
    100% { transform: translateY(-16px) translateX(25px); }
}

/* ==========================================================================
   ADVENTURE TRAIL VECTORS
   ========================================================================== */
.adventure-trail-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
}

.trail-svg {
    width: 100%;
    height: 100%;
    overflow: visible;
}

.trail-path {
    stroke: #F59E0B;
    stroke-width: 5;
    stroke-linecap: round;
    stroke-dasharray: 10 14;
    animation: dashFlow 20s linear infinite;
    opacity: 0.65;
    filter: drop-shadow(0 2px 6px rgba(245, 158, 11, 0.35));
}

@keyframes dashFlow {
    from { stroke-dashoffset: 400; }
    to { stroke-dashoffset: 0; }
}

/* ==========================================================================
   LAYER 4: RICH 3D STORYBOOK REALMS & CHARACTER DIORAMA
   ========================================================================== */
.storybook-diorama-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 5;
}

.diorama-pod {
    position: absolute;
    pointer-events: auto;
    cursor: pointer;
    transition: transform 0.35s ease-out, opacity 0.7s ease;
}

.diorama-pod:hover .diorama-img {
    transform: scale(1.05);
}

.diorama-img {
    display: block;
    width: 100%;
    height: auto;
    filter: drop-shadow(0 16px 26px rgba(29, 36, 64, 0.22));
    user-select: none;
    transition: transform 0.25s var(--spring-bounce);
}

/* Island Pedestal */
.island-pedestal {
    position: absolute;
    bottom: -15px;
    left: 8%;
    right: 8%;
    height: 38px;
    background: radial-gradient(ellipse at 50% 50%, rgba(29, 36, 64, 0.28) 0%, rgba(29, 36, 64, 0.08) 55%, transparent 75%);
    border-radius: 50%;
    filter: blur(5px);
    z-index: -1;
}

/* Pod 1: Left Mountain Pop-Up Book */
.pod-book-left {
    bottom: -1%;
    left: 0.5%;
    width: min(28vw, 360px);
    transform: translate(calc(var(--px) * -14px), calc(var(--py) * -10px));
    animation: bookFloatLeft 7s ease-in-out infinite alternate;
}

/* Pod 2: Right River/Waterfall Pop-Up Book */
.pod-book-right {
    bottom: -1%;
    right: 0.5%;
    width: min(28vw, 360px);
    transform: translate(calc(var(--px) * 14px), calc(var(--py) * -10px));
    animation: bookFloatRight 7.5s ease-in-out -3s infinite alternate;
}

@keyframes bookFloatLeft {
    0% { transform: translateY(0) rotate(-1deg); }
    100% { transform: translateY(-10px) rotate(1deg); }
}

@keyframes bookFloatRight {
    0% { transform: translateY(0) rotate(1deg); }
    100% { transform: translateY(-10px) rotate(-1deg); }
}

/* Pod 3: Foreground Left Reading Girl */
.pod-girl-left {
    bottom: 0;
    left: 12%;
    width: min(17vw, 210px);
    transform: translate(calc(var(--px) * -18px), calc(var(--py) * -12px));
    animation: girlBobLeft 5s ease-in-out infinite alternate;
}

/* Pod 4: Foreground Right Tablet Girl */
.pod-girl-right {
    bottom: 0;
    right: 12%;
    width: min(17vw, 210px);
    transform: translate(calc(var(--px) * 18px), calc(var(--py) * -12px));
    animation: girlBobRight 5.4s ease-in-out -2s infinite alternate;
}

@keyframes girlBobLeft {
    0% { transform: translateY(0); }
    100% { transform: translateY(-8px); }
}

@keyframes girlBobRight {
    0% { transform: translateY(0); }
    100% { transform: translateY(-8px); }
}

/* Pod 5: Mid-Left Scholar Boy on Book */
.pod-boy-laptop {
    top: 14%;
    left: 3%;
    width: min(20vw, 255px);
    transform: translate(calc(var(--px) * -22px), calc(var(--py) * 14px));
    animation: boyHover 6.2s ease-in-out infinite alternate;
}

@keyframes boyHover {
    0% { transform: translateY(0) rotate(1.5deg); }
    100% { transform: translateY(-15px) rotate(-1.5deg); }
}

/* Pod 6: Mid-Right Messenger Book Girl */
.pod-girl-message {
    top: 12%;
    right: 2.5%;
    width: min(22vw, 275px);
    transform: translate(calc(var(--px) * 22px), calc(var(--py) * 14px));
    animation: girlMsgHover 6.8s ease-in-out -3.5s infinite alternate;
}

@keyframes girlMsgHover {
    0% { transform: translateY(0) rotate(-1.5deg); }
    100% { transform: translateY(-14px) rotate(1.5deg); }
}

/* Floating Speech Chips on Characters */
.floating-speech-chip {
    position: absolute;
    bottom: -10px;
    background: #FFFFFF;
    border: 2.5px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.35rem 0.85rem;
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--game-navy);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
    pointer-events: auto;
    cursor: pointer;
    box-shadow:
        0 4px 0 var(--game-border),
        0 8px 18px rgba(29, 36, 64, 0.15);
    transition: transform 0.2s var(--spring-bounce);
}

.floating-speech-chip:hover {
    transform: translateY(-4px) scale(1.08);
}

.chip-left {
    left: 20%;
    animation: chipBounce 3s ease-in-out infinite alternate;
}

.chip-right {
    right: 15%;
    animation: chipBounce 3.2s ease-in-out -1.5s infinite alternate;
}

@keyframes chipBounce {
    0% { transform: translateY(0); }
    100% { transform: translateY(-6px); }
}

.char-sparkle-halo {
    position: absolute;
    top: -15px;
    font-size: 1.4rem;
    animation: haloTwinkle 2s ease-in-out infinite alternate;
}

.halo-left { left: 10px; }
.halo-right { right: 10px; }

@keyframes haloTwinkle {
    0% { transform: scale(0.85) rotate(-10deg); opacity: 0.6; }
    100% { transform: scale(1.2) rotate(15deg); opacity: 1; }
}

/* ==========================================================================
   GAMIFIED ADVENTURE LOOT & BADGES LAYER
   ========================================================================== */
.gamified-badges-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 6;
}

.float-quest-card {
    position: absolute;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(8px);
    border: 3px solid var(--game-border);
    border-radius: 20px;
    padding: 0.55rem 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    pointer-events: auto;
    cursor: pointer;
    box-shadow:
        0 6px 0 var(--game-border),
        0 14px 28px rgba(29, 36, 64, 0.14),
        inset 1px 1px 2px rgba(255, 255, 255, 0.9);
    transition: transform 0.25s var(--spring-bounce), box-shadow 0.25s ease;
}

.float-quest-card:hover {
    transform: translateY(-6px) scale(1.05);
    box-shadow:
        0 8px 0 var(--game-border),
        0 18px 32px rgba(29, 36, 64, 0.2);
}

.quest-card-left {
    top: 24%;
    left: 20%;
    animation: cardDrift 6s ease-in-out infinite alternate;
}

.quest-card-right {
    top: 22%;
    right: 19%;
    animation: cardDrift 6.5s ease-in-out -3s infinite alternate;
}

@keyframes cardDrift {
    0% { transform: translateY(0) rotate(-1deg); }
    100% { transform: translateY(-12px) rotate(1.5deg); }
}

.quest-card-icon {
    font-size: 1.55rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.quest-card-text {
    display: flex;
    flex-direction: column;
}

.quest-label {
    font-size: 0.62rem;
    font-weight: 900;
    color: #6C7594;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.quest-val {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--game-navy);
}

.quest-xp-tag {
    font-size: 0.7rem;
    font-weight: 900;
    background: linear-gradient(135deg, #FFD166 0%, #FF8C00 100%);
    color: #4A1D00;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    border: 1.5px solid #FF8C00;
    box-shadow: 0 2px 0 #D97706;
}

.quest-badge-pill {
    font-size: 0.68rem;
    font-weight: 900;
    background: linear-gradient(135deg, #D1FAE5 0%, #06D6A0 100%);
    color: #064E3B;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    border: 1.5px solid #059669;
    box-shadow: 0 2px 0 #047857;
}

.float-mini-badge {
    position: absolute;
    background: var(--neu-surface);
    border: 2.5px solid var(--game-border);
    border-radius: 9999px;
    padding: 0.4rem 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    font-weight: 900;
    color: var(--game-navy);
    pointer-events: auto;
    cursor: pointer;
    box-shadow:
        0 4px 0 var(--game-border),
        0 8px 16px rgba(29, 36, 64, 0.12);
    transition: transform 0.2s var(--spring-bounce);
}

.float-mini-badge:hover {
    transform: translateY(-4px) scale(1.08);
}

.badge-streak {
    bottom: 6%;
    left: 27%;
    animation: miniBadgeDrift 5s ease-in-out infinite alternate;
}

.badge-guild {
    bottom: 6%;
    right: 27%;
    animation: miniBadgeDrift 5.5s ease-in-out -2.5s infinite alternate;
}

@keyframes miniBadgeDrift {
    0% { transform: translateY(0); }
    100% { transform: translateY(-8px); }
}

/* ==========================================================================
   HERO CARD
   ========================================================================== */
.hero-stage {
    position: relative;
    z-index: 10;
    width: 100%;
    display: flex;
    justify-content: center;
}

.hero-card {
    position: relative;
    width: 100%;
    max-width: 720px;
    text-align: center;
    padding: 3.25rem 2.5rem 2.75rem;
    background: linear-gradient(150deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 244, 235, 0.96) 100%);
    backdrop-filter: blur(8px);
    border: 3.5px solid var(--game-border);
    border-radius: 44px;
    box-shadow:
        0 10px 0 var(--game-border),
        -10px -10px 24px var(--neu-light),
        14px 22px 44px rgba(29, 36, 64, 0.18),
        var(--neu-bevel-light),
        var(--neu-bevel-dark);
    opacity: 0;
    transform: translateY(-70px) scale(0.92);
    transition: opacity 0.6s ease 450ms, transform 0.9s var(--spring-bounce) 450ms;
}

.is-mounted .hero-card {
    opacity: 1;
    transform: translateY(0) scale(1);
}

/* Globe ↔ Map Book Journey */
.journey-badges {
    position: absolute;
    top: -34px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.journey-badge {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    background: linear-gradient(145deg, #FFFFFF 0%, #EAF4FD 100%);
    border: 3px solid var(--game-border);
    box-shadow:
        0 5px 0 var(--game-border),
        inset 2px 2px 5px rgba(255, 255, 255, 0.95),
        inset -2px -2px 5px rgba(35, 149, 235, 0.25);
    animation: toyFloat 4s ease-in-out infinite alternate;
}

.journey-badge.mapbook {
    background: linear-gradient(145deg, #FFF8E1 0%, #FFE9A8 100%);
    animation-delay: -2s;
}

.journey-path {
    width: 90px;
    height: 32px;
    overflow: visible;
}

.journey-path path {
    stroke: var(--game-blue);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-dasharray: 2 8;
    animation: dashTravel 1.6s linear infinite;
}

@keyframes dashTravel {
    to { stroke-dashoffset: -20; }
}

/* Role Chip */
.role-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.75rem;
    padding: 0.35rem 1rem;
    font-size: 0.74rem;
    font-weight: 900;
    letter-spacing: 0.07em;
    color: var(--game-blue-shadow);
    background: linear-gradient(145deg, #F0F8FF 0%, #D9EEFD 100%);
    border: 2px solid var(--game-blue);
    border-radius: 9999px;
    box-shadow:
        0 3px 0 rgba(35, 149, 235, 0.35),
        inset 1px 1px 3px rgba(255, 255, 255, 0.9);
}

.chip-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--game-green);
    box-shadow: 0 0 0 0 rgba(6, 214, 160, 0.6);
    animation: dotPulse 1.6s ease-out infinite;
}

@keyframes dotPulse {
    0% { box-shadow: 0 0 0 0 rgba(6, 214, 160, 0.6); }
    100% { box-shadow: 0 0 0 9px rgba(6, 214, 160, 0); }
}

.time-greeting {
    margin: 1rem 0 0.25rem;
    font-family: var(--font-ui);
    font-size: 1rem;
    font-weight: 900;
    color: #6C7594;
}

.time-icon {
    display: inline-block;
    animation: toyFloat 3s ease-in-out infinite alternate;
}

/* Greeting Headline */
.greeting {
    position: relative;
    margin: 0 0 0.85rem;
    font-family: var(--font-game);
    font-size: clamp(2.4rem, 6.5vw, 4.6rem);
    font-weight: 900;
    line-height: 1.08;
    letter-spacing: -0.01em;
    color: var(--game-navy);
    text-shadow:
        0 3px 0 #FFFFFF,
        0 6px 0 rgba(29, 36, 64, 0.14);
}

.greeting-hi {
    margin-right: 0.25em;
}

.greeting-name {
    display: inline;
}

.name-letter {
    display: inline-block;
    background: linear-gradient(90deg, var(--game-blue) 0%, var(--game-purple) 55%, var(--game-coral) 100%);
    background-size: 300% 100%;
    background-position: calc(var(--i) * 6%) 0;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    -webkit-text-stroke: 1px rgba(255, 255, 255, 0.6);
    text-shadow: none;
    filter: drop-shadow(0 4px 0 rgba(29, 36, 64, 0.16));
    opacity: 0;
    transform: translateY(24px) scale(0.6) rotate(-8deg);
    transition:
        opacity 0.35s ease calc(600ms + var(--i) * 45ms),
        transform 0.55s var(--spring-bounce) calc(600ms + var(--i) * 45ms);
}

.is-mounted .name-letter {
    opacity: 1;
    transform: translateY(0) scale(1) rotate(0);
}

.greeting-bang {
    display: inline-block;
    color: var(--game-coral);
    animation: bangWiggle 2.4s ease-in-out 1.6s infinite;
}

@keyframes bangWiggle {
    0%, 70%, 100% { transform: rotate(0); }
    80% { transform: rotate(12deg) scale(1.1); }
    90% { transform: rotate(-8deg); }
}

/* Twinkling Sparkles */
.sparkle {
    position: absolute;
    font-size: 0.42em;
    animation: twinkle 2.2s ease-in-out infinite;
    text-shadow: none;
}

.sparkle-l1 { left: -0.6em; top: 0.15em; color: #FFB703; }
.sparkle-l2 { left: -1.1em; top: 0.9em; font-size: 0.3em; animation-delay: 0.6s; }
.sparkle-r1 { right: -0.6em; top: 0.1em; color: var(--game-green); animation-delay: 0.3s; }
.sparkle-r2 { right: -1.1em; top: 0.95em; font-size: 0.3em; animation-delay: 1s; }

@keyframes twinkle {
    0%, 100% { opacity: 0.35; transform: scale(0.7) rotate(0deg); }
    50% { opacity: 1; transform: scale(1.15) rotate(20deg); }
}

.subtitle {
    margin: 0 auto 2rem;
    max-width: 480px;
    font-family: var(--font-ui);
    font-size: 1.02rem;
    font-weight: 800;
    line-height: 1.5;
    color: #6C7594;
}

/* ==========================================================================
   EXPLORE BUTTON
   ========================================================================== */
.explore-wrap {
    position: relative;
    display: inline-flex;
    opacity: 0;
    transform: translateY(30px) scale(0.8);
    transition: opacity 0.5s ease 1100ms, transform 0.7s var(--spring-bounce) 1100ms;
}

.is-mounted .explore-wrap {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.explore-halo {
    position: absolute;
    inset: -6px;
    border-radius: 9999px;
    border: 3px solid var(--game-green);
    pointer-events: none;
    animation: haloPulse 2s ease-out infinite;
}

@keyframes haloPulse {
    0% { opacity: 0.8; transform: scale(0.96); }
    100% { opacity: 0; transform: scale(1.18); }
}

.btn-explore {
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    min-width: 260px;
    padding: 1.05rem 2.4rem;
    font-family: var(--font-game);
    font-size: 1.3rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #FFFFFF;
    text-shadow: 0 2px 0 rgba(2, 107, 79, 0.6);
    background: linear-gradient(145deg, #2EE6B4 0%, var(--game-green) 45%, #04A87D 100%);
    border: 3px solid #026B4F;
    border-radius: 9999px;
    cursor: pointer;
    outline: none;
    user-select: none;
    box-shadow:
        0 7px 0 #026B4F,
        0 14px 26px rgba(6, 214, 160, 0.38),
        inset 2px 2px 5px rgba(255, 255, 255, 0.6),
        inset -2px -3px 6px rgba(2, 107, 79, 0.45);
    transition:
        transform 0.15s var(--spring-bounce),
        box-shadow 0.15s var(--spring-bounce);
}

.btn-explore:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow:
        0 10px 0 #026B4F,
        0 18px 30px rgba(6, 214, 160, 0.45),
        inset 2px 2px 5px rgba(255, 255, 255, 0.7),
        inset -2px -3px 6px rgba(2, 107, 79, 0.45);
}

.btn-explore:active:not(:disabled) {
    transform: translateY(5px);
    box-shadow:
        0 2px 0 #026B4F,
        inset 3px 3px 6px rgba(0, 0, 0, 0.22),
        inset -2px -2px 5px rgba(255, 255, 255, 0.4);
}

.btn-explore:focus-visible {
    outline: 4px solid var(--game-gold);
    outline-offset: 5px;
}

.btn-explore:disabled {
    cursor: wait;
    animation: launchPop 0.45s var(--spring-bounce) forwards;
}

@keyframes launchPop {
    0% { transform: scale(1); }
    40% { transform: scale(0.94) translateY(4px); }
    100% { transform: scale(1.12) translateY(-6px); }
}

.btn-gloss {
    position: absolute;
    top: 4px;
    left: 18px;
    right: 18px;
    height: 40%;
    border-radius: 9999px;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0));
    pointer-events: none;
}

.btn-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
}

.rocket {
    display: inline-block;
    transition: transform 0.3s var(--spring-bounce);
}

.btn-explore:hover:not(:disabled) .rocket {
    transform: translate(3px, -3px) rotate(-8deg);
}

.btn-explore:disabled .rocket {
    animation: rocketLaunch 0.45s ease-in forwards;
}

@keyframes rocketLaunch {
    to { transform: translate(30px, -40px) rotate(-20deg); opacity: 0; }
}

.btn-arrow {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.22);
    border: 2px solid rgba(255, 255, 255, 0.55);
    box-shadow: inset 1px 1px 3px rgba(255, 255, 255, 0.5);
    transition: transform 0.25s var(--spring-bounce);
}

.btn-explore:hover:not(:disabled) .btn-arrow {
    transform: translateX(6px);
}

/* ==========================================================================
   HERO CARD BOOKMARK, CORNER RIVETS & PERK BANNER
   ========================================================================== */
.hero-bookmark-ribbon {
    position: absolute;
    top: -6px;
    right: 48px;
    width: 32px;
    height: 52px;
    background: linear-gradient(180deg, #E11D48 0%, #BE123C 100%);
    border: 2px solid #881337;
    border-top: none;
    border-radius: 0 0 6px 6px;
    box-shadow:
        0 4px 10px rgba(190, 18, 60, 0.4),
        inset 1px 0 2px rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 12;
    animation: bookmarkSway 4s ease-in-out infinite alternate;
    transform-origin: top center;
}

@keyframes bookmarkSway {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(3deg); }
}

.ribbon-star {
    font-size: 0.9rem;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.25));
}

.ribbon-tail {
    position: absolute;
    bottom: -8px;
    left: 0;
    right: 0;
    height: 8px;
    background: inherit;
    clip-path: polygon(0% 0%, 100% 0%, 50% 100%);
}

.corner-rivet {
    position: absolute;
    width: 14px;
    height: 14px;
    background: radial-gradient(circle at 35% 35%, #FDE68A 0%, #D97706 65%, #92400E 100%);
    border: 1.5px solid #78350F;
    border-radius: 50%;
    box-shadow:
        0 2px 3px rgba(0, 0, 0, 0.25),
        inset 1px 1px 2px rgba(255, 255, 255, 0.8);
    z-index: 11;
}

.rivet-tl { top: 16px; left: 16px; }
.rivet-tr { top: 16px; right: 16px; }
.rivet-bl { bottom: 16px; left: 16px; }
.rivet-br { bottom: 16px; right: 16px; }

.welcome-perk-banner {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 2px solid #F59E0B;
    border-radius: 9999px;
    padding: 0.5rem 1.15rem;
    margin: 1.25rem auto 1.65rem auto;
    max-width: 90%;
    box-shadow:
        0 3px 0 #D97706,
        0 6px 14px rgba(245, 158, 11, 0.15);
}

.perk-sparkle {
    font-size: 1.15rem;
    animation: perkSparklePulse 1.8s infinite alternate;
}

@keyframes perkSparklePulse {
    0% { transform: scale(1); }
    100% { transform: scale(1.25) rotate(10deg); }
}

.perk-text {
    font-size: 0.85rem;
    color: #92400E;
    font-weight: 700;
}

.perk-text strong {
    color: #78350F;
    font-weight: 900;
}

.perk-badge {
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    background: #F59E0B;
    color: #FFFFFF;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    box-shadow: 0 1px 0 #B45309;
}

/* ==========================================================================
   RESPONSIVE DESIGN
   ========================================================================== */
@media (max-width: 1200px) {
    .quest-card-left { left: 8%; }
    .quest-card-right { right: 8%; }
    .badge-streak { left: 14%; }
    .badge-guild { right: 14%; }
    .pod-book-left, .pod-book-right { width: 280px; }
    .pod-boy-laptop, .pod-girl-message { width: 210px; }
}

@media (max-width: 1023px) {
    .toy-badge.hide-mobile:nth-child(odd) {
        display: none;
    }

    .tile-right-a,
    .tile-right-b {
        width: 110px;
        height: 84px;
        right: 2%;
    }

    .pod-book-left, .pod-book-right {
        width: 210px;
    }

    .pod-girl-left, .pod-girl-right {
        width: 140px;
    }

    .pod-boy-laptop, .pod-girl-message {
        width: 160px;
    }

    .float-quest-card {
        transform: scale(0.85);
    }

    .quest-card-left { left: 4%; top: 18%; }
    .quest-card-right { right: 4%; top: 18%; }
    .badge-streak, .badge-guild { display: none; }
}

@media (max-width: 639px) {
    .welcome-page {
        align-items: flex-start;
        padding: 4.5rem 1rem 9rem;
    }

    .toy-badge.hide-mobile,
    .neu-tile,
    .clouds-layer,
    .adventure-trail-layer,
    .gamified-badges-layer,
    .pod-boy-laptop,
    .pod-girl-message,
    .floating-speech-chip {
        display: none;
    }

    .blob-left {
        width: 55vw;
        height: 45vh;
        left: -25%;
    }

    .blob-right {
        width: 55vw;
        height: 50vh;
        right: -25%;
    }

    .pod-book-left {
        width: 135px;
        left: -8%;
        bottom: -5px;
    }

    .pod-book-right {
        width: 135px;
        right: -8%;
        bottom: -5px;
    }

    .pod-girl-left {
        width: 105px;
        left: 4%;
        bottom: 0;
    }

    .pod-girl-right {
        width: 105px;
        right: 4%;
        bottom: 0;
    }

    .hero-card {
        padding: 2.75rem 1.25rem 2rem;
        border-radius: 32px;
    }

    .welcome-perk-banner {
        flex-direction: column;
        border-radius: 18px;
        padding: 0.6rem 0.8rem;
        margin: 1rem 0;
    }

    .subtitle {
        font-size: 0.92rem;
    }

    .explore-wrap,
    .btn-explore {
        width: 100%;
    }

    .btn-explore {
        min-width: 0;
        font-size: 1.15rem;
    }

    .sparkle-l2,
    .sparkle-r2 {
        display: none;
    }
}

/* ==========================================================================
   REDUCED MOTION
   ========================================================================== */
@media (prefers-reduced-motion: reduce) {
    .blob,
    .neu-tile,
    .toy-badge,
    .kid,
    .journey-badge,
    .journey-path path,
    .chip-dot,
    .time-icon,
    .greeting-bang,
    .sparkle,
    .explore-halo {
        animation: none !important;
    }

    .blob-layer,
    .tile-layer,
    .icon-layer {
        transform: none !important;
    }

    .blob,
    .neu-tile,
    .toy-badge,
    .kid,
    .hero-card,
    .name-letter,
    .explore-wrap {
        transition-duration: 0.01ms !important;
        transition-delay: 0ms !important;
    }
}
</style>
