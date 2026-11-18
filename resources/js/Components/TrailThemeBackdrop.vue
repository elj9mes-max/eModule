<script setup>
import { computed } from 'vue';
import { resolveTrailTheme } from '@/Composables/useTrailThemes';

/**
 * Scenery layer for a purchased expedition trail theme.
 * On the progress map it sits behind the S-curve trail; in the shop (sample) it also
 * draws a short trail so students can see the themed track colors.
 */
const props = defineProps({
    theme: {
        type: String,
        required: true,
    },
    sample: {
        type: Boolean,
        default: false,
    },
});

const resolvedTheme = computed(() => resolveTrailTheme(props.theme));

const sceneStyle = computed(() => ({
    backgroundImage: `${resolvedTheme.value.tile}, ${resolvedTheme.value.sky}`,
}));

const ambientStyle = computed(() => ({
    backgroundImage: resolvedTheme.value.ambient,
}));

const samplePath = 'M 30 70 C 30 40, 170 40, 170 20';
</script>

<template>
    <div v-if="resolvedTheme" class="trail-backdrop" :class="{ 'is-sample': sample }" aria-hidden="true">
        <div class="trail-scene" :style="sceneStyle"></div>
        <div
            v-if="resolvedTheme.ambient"
            class="trail-ambient"
            :class="`motion-${resolvedTheme.ambientMotion}`"
            :style="ambientStyle"
        ></div>

        <svg v-if="sample" class="trail-sample" viewBox="0 0 200 90" preserveAspectRatio="xMidYMid meet">
            <path :d="samplePath" fill="none" :stroke="resolvedTheme.track.rim" stroke-width="15" stroke-linecap="round" />
            <path :d="samplePath" fill="none" :stroke="resolvedTheme.track.base" stroke-width="10" stroke-linecap="round" />
            <path :d="samplePath" fill="none" :stroke="resolvedTheme.track.dash" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="1 10" />
            <circle cx="30" cy="70" r="7" fill="#10B981" :stroke="resolvedTheme.track.rim" stroke-width="3" />
            <circle cx="170" cy="20" r="7" fill="#FBBF24" :stroke="resolvedTheme.track.rim" stroke-width="3" />
        </svg>
    </div>
</template>

<style scoped>
.trail-backdrop {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

/* Sky gradient + scenery strip repeating down a map of any height */
.trail-scene {
    position: absolute;
    inset: 0;
    background-repeat: repeat-y, no-repeat;
    background-position: center top, center;
    background-size: min(100%, 760px) auto, 100% 100%;
}

.is-sample .trail-scene {
    background-size: 160% auto, 100% 100%;
    background-position: center 20%, center;
}

.trail-ambient {
    position: absolute;
    inset: 0;
    background-repeat: repeat;
    background-size: 300px 360px;
    opacity: 0.9;
}

.motion-fall { animation: trailFall 22s linear infinite; }
.motion-rise { animation: trailRise 26s linear infinite; }
.motion-twinkle { animation: trailTwinkle 4s ease-in-out infinite; }

@keyframes trailFall { from { background-position: 0 0; } to { background-position: 0 360px; } }
@keyframes trailRise { from { background-position: 0 0; } to { background-position: 0 -360px; } }
@keyframes trailTwinkle { 0%, 100% { opacity: 0.9; } 50% { opacity: 0.4; } }

.trail-sample {
    position: absolute;
    inset: 8% 10%;
    width: 80%;
    height: 84%;
}

@media (prefers-reduced-motion: reduce) {
    .trail-ambient { animation: none; }
}
</style>
