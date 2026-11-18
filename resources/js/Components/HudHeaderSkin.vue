<script setup>
import { computed, useId } from 'vue';

/**
 * Decorative, purchasable background for the student HUD header.
 * Skin ids are stored on shop items as metadata.header_skin.
 */
const props = defineProps({
    skin: {
        type: String,
        required: true,
    },
    // Wash the art with the active world's surface color so header text stays readable.
    scrim: {
        type: Boolean,
        default: true,
    },
});

const knownSkins = ['sunset-meadow', 'ocean-breeze', 'candy-clouds', 'starlight-night', 'aurora-sky', 'cosmic-voyage'];

const isKnownSkin = computed(() => knownSkins.includes(props.skin));

// Unique SVG ids so several previews (shop cards + header) can share one page.
const uid = useId();
const svgId = (name) => `${uid}-${name}`;
const url = (name) => `url(#${svgId(name)})`;
</script>

<template>
    <div v-if="isKnownSkin" class="hud-skin" :class="`hud-skin--${skin}`" aria-hidden="true">
        <!-- SUNSET MEADOW: warm sky, glowing sun, rolling clay hills -->
        <svg v-if="skin === 'sunset-meadow'" class="hud-skin-art" viewBox="0 0 1040 120" preserveAspectRatio="xMaxYMid slice">
            <defs>
                <radialGradient :id="svgId('sun')" cx="0.5" cy="0.5" r="0.5">
                    <stop offset="0%" stop-color="#FFF7D6" />
                    <stop offset="55%" stop-color="#FFD166" />
                    <stop offset="100%" stop-color="#FF9F43" />
                </radialGradient>
                <radialGradient :id="svgId('glow')" cx="0.5" cy="0.5" r="0.5">
                    <stop offset="0%" stop-color="#FFE7A3" stop-opacity="0.85" />
                    <stop offset="100%" stop-color="#FFE7A3" stop-opacity="0" />
                </radialGradient>
                <linearGradient :id="svgId('hillFar')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#F6A97B" />
                    <stop offset="100%" stop-color="#E9876A" />
                </linearGradient>
                <linearGradient :id="svgId('hillNear')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#8FD48A" />
                    <stop offset="100%" stop-color="#4FA868" />
                </linearGradient>
            </defs>
            <circle class="skin-pulse" cx="840" cy="52" r="70" :fill="url('glow')" />
            <circle cx="840" cy="52" r="28" :fill="url('sun')" />
            <g class="skin-drift" fill="#FFFFFF" fill-opacity="0.82">
                <path d="M600 36h70c9 0 15-6 15-13s-6-13-14-13c-3-7-10-11-18-11-7 0-13 3-16 8-3-2-7-3-11-3-10 0-17 7-18 16-8 1-13 6-13 12 0 3 1 4 5 4Z" />
                <path d="M948 30h52c7 0 11-5 11-10s-4-10-10-10c-2-5-8-8-13-8-6 0-10 2-12 6-3-1-5-2-8-2-8 0-13 5-14 12-6 1-10 5-10 9 0 2 1 3 4 3Z" />
            </g>
            <path d="M0 92 C140 62 300 98 470 76 C640 54 780 90 900 70 C970 60 1010 66 1040 62 L1040 120 L0 120 Z" :fill="url('hillFar')" />
            <path d="M0 108 C180 86 360 116 560 96 C740 78 880 106 1040 88 L1040 120 L0 120 Z" :fill="url('hillNear')" />
            <g fill="#FFF3B0">
                <circle cx="720" cy="100" r="3" /><circle cx="760" cy="94" r="2.5" /><circle cx="930" cy="96" r="3" /><circle cx="980" cy="102" r="2.5" />
            </g>
        </svg>

        <!-- OCEAN BREEZE: layered waves, a tiny sailboat, rising bubbles -->
        <svg v-else-if="skin === 'ocean-breeze'" class="hud-skin-art" viewBox="0 0 1040 120" preserveAspectRatio="xMaxYMid slice">
            <defs>
                <linearGradient :id="svgId('waveBack')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#5EC8E8" />
                    <stop offset="100%" stop-color="#2E9CC7" />
                </linearGradient>
                <linearGradient :id="svgId('waveFront')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2BB3A8" />
                    <stop offset="100%" stop-color="#14857F" />
                </linearGradient>
            </defs>
            <g class="skin-bob">
                <path d="M842 66 L842 30 L868 62 Z" fill="#FFFFFF" />
                <path d="M838 66 L838 38 L818 62 Z" fill="#FFE3B3" />
                <path d="M812 68 H878 L868 80 H822 Z" fill="#F0774E" />
            </g>
            <path class="skin-sway" d="M-40 80 C60 66 140 92 240 80 C340 68 420 92 520 80 C620 68 700 92 800 80 C900 68 980 92 1080 80 L1080 120 L-40 120 Z" :fill="url('waveBack')" />
            <path class="skin-sway skin-sway--reverse" d="M-40 96 C70 84 150 106 260 96 C370 86 450 106 560 96 C670 86 750 106 860 96 C970 86 1030 104 1080 98 L1080 120 L-40 120 Z" :fill="url('waveFront')" />
            <g class="skin-rise" fill="#FFFFFF" fill-opacity="0.75">
                <circle cx="700" cy="60" r="4" /><circle cx="712" cy="44" r="2.5" /><circle cx="960" cy="56" r="3.5" /><circle cx="975" cy="40" r="2" />
            </g>
        </svg>

        <!-- CANDY CLOUDS: puffy pastel clouds and sprinkles -->
        <svg v-else-if="skin === 'candy-clouds'" class="hud-skin-art" viewBox="0 0 1040 120" preserveAspectRatio="xMaxYMid slice">
            <defs>
                <linearGradient :id="svgId('puff')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#FFFFFF" />
                    <stop offset="100%" stop-color="#FBD3EC" />
                </linearGradient>
            </defs>
            <g class="skin-drift">
                <path d="M640 78h110c14 0 24-10 24-21s-9-20-22-21c-4-11-14-17-26-17-10 0-19 5-24 12-5-3-11-4-17-4-15 0-27 11-28 25-13 1-21 10-21 19 0 5 2 7 4 7Z" :fill="url('puff')" />
                <path d="M880 64h96c12 0 21-9 21-18s-8-18-19-18c-4-9-12-15-22-15-9 0-16 4-21 10-4-2-9-4-14-4-13 0-23 10-24 22-11 1-18 8-18 16 0 4 1 7 1 7Z" :fill="url('puff')" />
            </g>
            <path d="M0 104 C150 90 280 112 430 100 C600 86 760 112 900 98 C960 92 1010 96 1040 94 L1040 120 L0 120 Z" fill="#F9C2E0" />
            <g class="skin-twinkle">
                <rect x="560" y="30" width="12" height="4" rx="2" fill="#F472B6" transform="rotate(-30 566 32)" />
                <rect x="800" y="24" width="12" height="4" rx="2" fill="#60A5FA" transform="rotate(25 806 26)" />
                <rect x="830" y="86" width="12" height="4" rx="2" fill="#FBBF24" transform="rotate(-15 836 88)" />
                <rect x="1000" y="78" width="12" height="4" rx="2" fill="#34D399" transform="rotate(35 1006 80)" />
                <rect x="610" y="96" width="12" height="4" rx="2" fill="#A78BFA" transform="rotate(10 616 98)" />
            </g>
        </svg>

        <!-- STARLIGHT NIGHT: crescent moon, twinkling stars, sleepy mountains -->
        <svg v-else-if="skin === 'starlight-night'" class="hud-skin-art" viewBox="0 0 1040 120" preserveAspectRatio="xMaxYMid slice">
            <defs>
                <radialGradient :id="svgId('moonGlow')" cx="0.5" cy="0.5" r="0.5">
                    <stop offset="0%" stop-color="#FDF6C9" stop-opacity="0.6" />
                    <stop offset="100%" stop-color="#FDF6C9" stop-opacity="0" />
                </radialGradient>
                <mask :id="svgId('crescent')">
                    <rect width="1040" height="120" fill="#FFFFFF" />
                    <circle cx="884" cy="38" r="22" fill="#000000" />
                </mask>
            </defs>
            <circle class="skin-pulse" cx="870" cy="46" r="56" :fill="url('moonGlow')" />
            <circle cx="870" cy="46" r="24" fill="#FDF3B5" :mask="url('crescent')" />
            <g class="skin-twinkle" fill="#FFFFFF">
                <circle cx="560" cy="24" r="2" /><circle cx="640" cy="52" r="1.5" /><circle cx="720" cy="18" r="2.4" />
                <circle cx="780" cy="66" r="1.6" /><circle cx="960" cy="22" r="2.2" /><circle cx="1000" cy="60" r="1.6" />
            </g>
            <g class="skin-twinkle skin-twinkle--late" fill="#C7D2FE">
                <circle cx="600" cy="40" r="1.6" /><circle cx="690" cy="34" r="1.4" /><circle cx="930" cy="76" r="1.6" /><circle cx="520" cy="60" r="1.4" />
            </g>
            <path d="M0 104 L120 82 L210 98 L330 74 L450 96 L560 78 L690 100 L800 80 L920 98 L1040 84 L1040 120 L0 120 Z" fill="#2A2470" />
            <path d="M0 112 C200 100 380 118 600 106 C800 96 940 114 1040 104 L1040 120 L0 120 Z" fill="#1B1650" />
        </svg>

        <!-- AURORA SKY: dancing northern-light ribbons over pine silhouettes -->
        <svg v-else-if="skin === 'aurora-sky'" class="hud-skin-art" viewBox="0 0 1040 120" preserveAspectRatio="xMaxYMid slice">
            <defs>
                <linearGradient :id="svgId('ribbonA')" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#34D399" stop-opacity="0" />
                    <stop offset="35%" stop-color="#34D399" stop-opacity="0.85" />
                    <stop offset="70%" stop-color="#22D3EE" stop-opacity="0.75" />
                    <stop offset="100%" stop-color="#A78BFA" stop-opacity="0" />
                </linearGradient>
                <linearGradient :id="svgId('ribbonB')" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#A78BFA" stop-opacity="0" />
                    <stop offset="45%" stop-color="#C084FC" stop-opacity="0.7" />
                    <stop offset="100%" stop-color="#34D399" stop-opacity="0" />
                </linearGradient>
                <filter :id="svgId('soft')" x="-10%" y="-50%" width="120%" height="200%">
                    <feGaussianBlur stdDeviation="5" />
                </filter>
            </defs>
            <g :filter="url('soft')">
                <path class="skin-sway" d="M420 54 C540 18 640 70 760 36 C860 8 960 52 1080 26" fill="none" :stroke="url('ribbonA')" stroke-width="20" stroke-linecap="round" />
                <path class="skin-sway skin-sway--reverse" d="M480 72 C600 44 700 86 820 58 C920 36 1000 70 1080 50" fill="none" :stroke="url('ribbonB')" stroke-width="14" stroke-linecap="round" />
            </g>
            <g class="skin-twinkle" fill="#E0F2FE">
                <circle cx="610" cy="20" r="1.6" /><circle cx="900" cy="16" r="1.8" /><circle cx="1010" cy="34" r="1.4" /><circle cx="740" cy="12" r="1.4" />
            </g>
            <g fill="#0B2A33">
                <path d="M600 120 L618 84 L636 120 Z" /><path d="M630 120 L652 74 L674 120 Z" />
                <path d="M880 120 L900 80 L920 120 Z" /><path d="M912 120 L936 70 L960 120 Z" /><path d="M950 120 L968 88 L986 120 Z" />
            </g>
            <path d="M0 112 C220 104 420 118 640 110 C840 102 960 114 1040 108 L1040 120 L0 120 Z" fill="#0B2A33" />
        </svg>

        <!-- COSMIC VOYAGE: ringed planet, rocket with a sparkle trail -->
        <svg v-else-if="skin === 'cosmic-voyage'" class="hud-skin-art" viewBox="0 0 1040 120" preserveAspectRatio="xMaxYMid slice">
            <defs>
                <linearGradient :id="svgId('planet')" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#FDBA74" />
                    <stop offset="60%" stop-color="#F97316" />
                    <stop offset="100%" stop-color="#9A3412" />
                </linearGradient>
                <linearGradient :id="svgId('moonlet')" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#99F6E4" />
                    <stop offset="100%" stop-color="#0F766E" />
                </linearGradient>
                <linearGradient :id="svgId('trail')" x1="1" y1="0" x2="0" y2="0">
                    <stop offset="0%" stop-color="#FDE68A" stop-opacity="0.95" />
                    <stop offset="100%" stop-color="#F472B6" stop-opacity="0" />
                </linearGradient>
            </defs>
            <g class="skin-twinkle" fill="#FFFFFF">
                <circle cx="540" cy="22" r="1.8" /><circle cx="600" cy="90" r="1.4" /><circle cx="700" cy="40" r="2" />
                <circle cx="790" cy="100" r="1.4" /><circle cx="1000" cy="24" r="1.8" /><circle cx="1020" cy="96" r="1.4" />
            </g>
            <ellipse cx="900" cy="64" rx="62" ry="12" fill="none" stroke="#FCD34D" stroke-width="5" stroke-opacity="0.55" transform="rotate(-14 900 64)" />
            <circle cx="900" cy="64" r="30" :fill="url('planet')" />
            <path d="M838 64 A62 12 0 0 0 962 64" fill="none" stroke="#FCD34D" stroke-width="5" transform="rotate(-14 900 64)" />
            <circle cx="990" cy="96" r="10" :fill="url('moonlet')" />
            <g class="skin-bob">
                <path d="M560 84 C620 76 680 66 730 52" fill="none" :stroke="url('trail')" stroke-width="6" stroke-linecap="round" />
                <g transform="translate(742 48) rotate(-28)">
                    <path d="M-14 -7 H8 C16 -7 22 0 22 0 C22 0 16 7 8 7 H-14 Z" fill="#F8FAFC" />
                    <circle cx="4" cy="0" r="3.4" fill="#38BDF8" />
                    <path d="M-14 -7 L-22 -14 L-8 -7 Z M-14 7 L-22 14 L-8 7 Z" fill="#F43F5E" />
                </g>
            </g>
        </svg>

        <span v-if="scrim" class="hud-skin-scrim"></span>
    </div>
</template>

<style scoped>
.hud-skin {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.hud-skin-art {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
}

/* Sky gradients per skin */
.hud-skin--sunset-meadow { background: linear-gradient(180deg, #FFB38A 0%, #FFD6A0 48%, #FFEBC9 100%); }
.hud-skin--ocean-breeze { background: linear-gradient(180deg, #7DD3FC 0%, #BAE6FD 55%, #E0F7FF 100%); }
.hud-skin--candy-clouds { background: linear-gradient(180deg, #F9C6E4 0%, #E9D5FF 55%, #FDF2F8 100%); }
.hud-skin--starlight-night { background: linear-gradient(180deg, #1E1B4B 0%, #312E81 60%, #3F3A9E 100%); }
.hud-skin--aurora-sky { background: linear-gradient(180deg, #07222E 0%, #0E3A4A 55%, #16304A 100%); }
.hud-skin--cosmic-voyage {
    background:
        radial-gradient(circle at 78% 30%, rgba(168, 85, 247, 0.45) 0%, transparent 45%),
        linear-gradient(180deg, #1E1340 0%, #2A1458 55%, #0F172A 100%);
}

/*
 * Readability scrim in the active world's surface color: strongest behind the
 * avatar/name on the left and along the XP readout at the bottom, lighter on the
 * right so the art still shows around the currency pills.
 */
.hud-skin-scrim {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(0deg,
            color-mix(in srgb, var(--px-surface-card, #FFFFFF) 72%, transparent) 0%,
            transparent 48%),
        linear-gradient(90deg,
            color-mix(in srgb, var(--px-surface-card, #FFFFFF) 88%, transparent) 0%,
            color-mix(in srgb, var(--px-surface-card, #FFFFFF) 78%, transparent) 32%,
            color-mix(in srgb, var(--px-surface-card, #FFFFFF) 42%, transparent) 62%,
            color-mix(in srgb, var(--px-surface-card, #FFFFFF) 22%, transparent) 100%);
}

/* Gentle ambient motion (disabled by reduced-motion and html[data-perf="lite"]) */
.skin-drift { animation: skinDrift 14s ease-in-out infinite alternate; }
.skin-sway { animation: skinSway 9s ease-in-out infinite alternate; }
.skin-sway--reverse { animation-direction: alternate-reverse; animation-duration: 11s; }
.skin-bob { animation: skinBob 4.5s ease-in-out infinite; }
.skin-rise { animation: skinRise 6s ease-in-out infinite; }
.skin-pulse { animation: skinPulse 5s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
.skin-twinkle { animation: skinTwinkle 3.2s ease-in-out infinite; }
.skin-twinkle--late { animation-delay: 1.6s; }

@keyframes skinDrift { from { transform: translateX(0); } to { transform: translateX(-26px); } }
@keyframes skinSway { from { transform: translateX(0); } to { transform: translateX(-34px); } }
@keyframes skinBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
@keyframes skinRise { 0% { transform: translateY(6px); opacity: 0; } 30% { opacity: 1; } 100% { transform: translateY(-14px); opacity: 0; } }
@keyframes skinPulse { 0%, 100% { transform: scale(1); opacity: 0.9; } 50% { transform: scale(1.08); opacity: 1; } }
@keyframes skinTwinkle { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }

@media (prefers-reduced-motion: reduce) {
    .hud-skin * { animation: none !important; }
}
</style>
