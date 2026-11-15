<script setup>
defineProps({
    theme: {
        type: String,
        default: 'lagoon',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="pixar-lagoon-paper-backdrop"
        :class="[`is-theme-${theme}`, { 'is-compact': compact }]"
        aria-hidden="true"
    >
        <!-- =====================================================================
             THEME 1: LAGOON BREEZE (SUNLIT CORAL LAGOON & GOLDEN SANDBAR)
             ===================================================================== -->
        <template v-if="theme === 'lagoon'">
            <!-- PLANE 1: Atmospheric Sun Bloom & Tropical Caustic Glow -->
            <div class="lagoon-sun-bloom"></div>
            <div class="lagoon-caustic-veil"></div>

            <!-- PLANE 2: Top Sunlit Coastal Canopy, God-Rays, Clouds & Palm Fronds -->
            <svg
                class="lagoon-top-svg"
                viewBox="0 0 840 190"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <defs>
                    <radialGradient id="pxLagoonSunOrb" cx="34%" cy="28%" r="70%">
                        <stop offset="0%" stop-color="#FFFBEB" />
                        <stop offset="45%" stop-color="#FDE047" />
                        <stop offset="85%" stop-color="#F59E0B" />
                        <stop offset="100%" stop-color="#D97706" />
                    </radialGradient>

                    <linearGradient id="pxLagoonRay" x1="0" y1="0" x2="0.3" y2="1">
                        <stop offset="0%" stop-color="#FEF08A" stop-opacity="0.52" />
                        <stop offset="55%" stop-color="#7DD3FC" stop-opacity="0.18" />
                        <stop offset="100%" stop-color="#38BDF8" stop-opacity="0" />
                    </linearGradient>

                    <linearGradient id="pxLagoonTopWave" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#38BDF8" stop-opacity="0.32" />
                        <stop offset="50%" stop-color="#2DD4BF" stop-opacity="0.26" />
                        <stop offset="100%" stop-color="#0EA5E9" stop-opacity="0.34" />
                    </linearGradient>

                    <linearGradient id="pxPalmLeafLit" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#34D399" />
                        <stop offset="55%" stop-color="#10B981" />
                        <stop offset="100%" stop-color="#047857" />
                    </linearGradient>

                    <linearGradient id="pxPalmLeafDeep" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#10B981" />
                        <stop offset="100%" stop-color="#065F46" />
                    </linearGradient>

                    <linearGradient id="pxLagoonCloud" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.95" />
                        <stop offset="72%" stop-color="#E0F2FE" stop-opacity="0.88" />
                        <stop offset="100%" stop-color="#BAE6FD" stop-opacity="0.78" />
                    </linearGradient>
                </defs>

                <!-- Soft Diagonal Sunbeams / Underwater God-Rays -->
                <polygon points="0,0 190,0 340,190 70,190" fill="url(#pxLagoonRay)" />
                <polygon points="150,0 290,0 470,190 255,190" fill="url(#pxLagoonRay)" opacity="0.7" />
                <polygon points="340,0 440,0 610,190 460,190" fill="url(#pxLagoonRay)" opacity="0.45" />

                <!-- Soft Coastal Horizon Waves at Top -->
                <path
                    d="M0,0 L840,0 L840,68 C710,94 590,42 440,72 C290,102 150,48 0,78 Z"
                    fill="url(#pxLagoonTopWave)"
                />
                <path
                    d="M0,56 C160,30 310,82 470,52 C630,22 740,68 840,46"
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    opacity="0.65"
                />

                <!-- Puffy Sunlit Coastal Clouds -->
                <g opacity="0.88">
                    <ellipse cx="640" cy="36" rx="52" ry="18" fill="url(#pxLagoonCloud)" />
                    <ellipse cx="615" cy="30" rx="32" ry="20" fill="#FFFFFF" opacity="0.92" />
                    <ellipse cx="668" cy="32" rx="28" ry="16" fill="#FFFFFF" opacity="0.85" />

                    <ellipse cx="255" cy="28" rx="44" ry="15" fill="url(#pxLagoonCloud)" />
                    <ellipse cx="236" cy="23" rx="26" ry="16" fill="#FFFFFF" opacity="0.9" />
                </g>

                <!-- Sculpted Pixar Sun Orb in Top-Left Sky -->
                <g transform="translate(86, 42)">
                    <circle cx="0" cy="0" r="34" fill="#FEF08A" opacity="0.32" />
                    <circle cx="0" cy="0" r="23" fill="url(#pxLagoonSunOrb)" />
                    <ellipse cx="-7" cy="-8" rx="9" ry="5.5" transform="rotate(-24 -7 -8)" fill="#FFFFFF" opacity="0.7" />
                    <circle cx="-11" cy="-10" r="2.4" fill="#FFFFFF" opacity="0.92" />
                </g>

                <!-- Top-Left Overhanging Tropical Palm Fronds -->
                <g class="lagoon-palm-sway-left">
                    <path
                        d="M-12,18 C36,6 92,22 134,54 C88,46 38,44 -12,48 Z"
                        fill="url(#pxPalmLeafDeep)"
                    />
                    <path
                        d="M-14,-4 C48,-6 112,16 158,52 C104,36 46,24 -14,26 Z"
                        fill="url(#pxPalmLeafLit)"
                    />
                    <path
                        d="M-10,6 C42,6 98,22 146,48"
                        fill="none"
                        stroke="#A7F3D0"
                        stroke-width="2"
                        stroke-linecap="round"
                        opacity="0.8"
                    />
                    <path
                        d="M-10,36 C24,34 62,48 92,76 C54,66 18,62 -10,66 Z"
                        fill="url(#pxPalmLeafLit)"
                        opacity="0.9"
                    />
                </g>

                <!-- Top-Right Overhanging Tropical Palm Fronds -->
                <g class="lagoon-palm-sway-right">
                    <path
                        d="M852,14 C802,4 748,20 708,52 C752,44 802,40 852,44 Z"
                        fill="url(#pxPalmLeafDeep)"
                    />
                    <path
                        d="M854,-4 C794,-4 732,16 688,50 C740,34 796,22 854,24 Z"
                        fill="url(#pxPalmLeafLit)"
                    />
                    <path
                        d="M850,6 C798,6 744,20 698,46"
                        fill="none"
                        stroke="#A7F3D0"
                        stroke-width="2"
                        stroke-linecap="round"
                        opacity="0.8"
                    />
                </g>
            </svg>

            <!-- PLANE 3: Floating 3D Pixar Translucent Water Bubbles along Margins -->
            <div class="lagoon-bubbles-layer">
                <span class="lagoon-orb orb-1"></span>
                <span class="lagoon-orb orb-2"></span>
                <span class="lagoon-orb orb-3"></span>
                <span class="lagoon-orb orb-4"></span>
                <span class="lagoon-orb orb-5"></span>
                <span class="lagoon-orb orb-6"></span>
            </div>

            <!-- PLANE 4: Bottom Coral Lagoon Reef, Rolling Turquoise Swells & Golden Sandbars -->
            <svg
                class="lagoon-bottom-svg"
                viewBox="0 0 840 240"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient id="pxLagoonWaveBack" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#67E8F9" stop-opacity="0.58" />
                        <stop offset="55%" stop-color="#22D3EE" stop-opacity="0.72" />
                        <stop offset="100%" stop-color="#0284C7" stop-opacity="0.85" />
                    </linearGradient>

                    <linearGradient id="pxLagoonWaveMid" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#2DD4BF" stop-opacity="0.76" />
                        <stop offset="55%" stop-color="#0EA5E9" stop-opacity="0.88" />
                        <stop offset="100%" stop-color="#0369A1" stop-opacity="0.95" />
                    </linearGradient>

                    <linearGradient id="pxLagoonWaveFront" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#38BDF8" stop-opacity="0.85" />
                        <stop offset="50%" stop-color="#0284C7" stop-opacity="0.94" />
                        <stop offset="100%" stop-color="#075985" stop-opacity="0.98" />
                    </linearGradient>

                    <linearGradient id="pxLagoonSandbar" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FEF08A" />
                        <stop offset="48%" stop-color="#FBBF24" />
                        <stop offset="100%" stop-color="#D97706" />
                    </linearGradient>

                    <linearGradient id="pxCoralPink" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#FDA4AF" />
                        <stop offset="50%" stop-color="#FB7185" />
                        <stop offset="100%" stop-color="#E11D48" />
                    </linearGradient>

                    <linearGradient id="pxCoralAmber" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FDE047" />
                        <stop offset="55%" stop-color="#FB923C" />
                        <stop offset="100%" stop-color="#EA580C" />
                    </linearGradient>

                    <radialGradient id="pxStarfishBody" cx="34%" cy="28%" r="72%">
                        <stop offset="0%" stop-color="#FDBA74" />
                        <stop offset="52%" stop-color="#F97316" />
                        <stop offset="100%" stop-color="#C2410C" />
                    </radialGradient>

                    <radialGradient id="pxPearlShell" cx="36%" cy="30%" r="70%">
                        <stop offset="0%" stop-color="#FFFBEB" />
                        <stop offset="55%" stop-color="#FDE68A" />
                        <stop offset="100%" stop-color="#F59E0B" />
                    </radialGradient>
                </defs>

                <!-- Distant Sandy Atoll & Mini Palm Silhouette on Right Horizon -->
                <g opacity="0.82" transform="translate(650, 74)">
                    <ellipse cx="52" cy="54" rx="68" ry="16" fill="url(#pxLagoonSandbar)" />
                    <path d="M52,52 Q56,24 68,8 Q62,28 58,52 Z" fill="#92400E" />
                    <circle cx="68" cy="10" r="14" fill="#10B981" />
                    <circle cx="58" cy="13" r="11" fill="#34D399" />
                    <circle cx="78" cy="13" r="11" fill="#059669" />
                    <circle cx="64" cy="6" r="3" fill="#FEF08A" opacity="0.9" />
                </g>

                <!-- Wave Layer 1: Gentle Back Turquoise Swell -->
                <path
                    class="lagoon-wave-back"
                    d="M-20,122 C110,88 230,148 370,114 C510,80 650,142 860,106 L860,240 L-20,240 Z"
                    fill="url(#pxLagoonWaveBack)"
                />
                <path
                    d="M-20,122 C110,88 230,148 370,114 C510,80 650,142 860,106"
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    opacity="0.65"
                />

                <!-- Wave Layer 2: Mid Tropical Teal Swell -->
                <path
                    class="lagoon-wave-mid"
                    d="M-20,148 C130,176 270,116 430,148 C590,180 710,122 860,146 L860,240 L-20,240 Z"
                    fill="url(#pxLagoonWaveMid)"
                />
                <path
                    d="M-20,148 C130,176 270,116 430,148 C590,180 710,122 860,146"
                    fill="none"
                    stroke="#E0F2FE"
                    stroke-width="3"
                    stroke-linecap="round"
                    opacity="0.72"
                />

                <!-- Tiny Playful Tropical Reef Fish Silhouettes -->
                <g transform="translate(275, 162)" opacity="0.88">
                    <ellipse cx="14" cy="8" rx="11" ry="6.5" fill="url(#pxCoralAmber)" />
                    <polygon points="24,8 33,2 31,14" fill="#FB923C" />
                    <circle cx="8" cy="6.5" r="1.8" fill="#0F172A" />
                    <circle cx="7.4" cy="5.8" r="0.7" fill="#FFFFFF" />
                    <ellipse cx="13" cy="5" rx="5" ry="1.8" fill="#FFFFFF" opacity="0.45" />
                </g>
                <g transform="translate(545, 154) scale(-0.85, 0.85)" opacity="0.82">
                    <ellipse cx="14" cy="8" rx="11" ry="6.5" fill="url(#pxCoralPink)" />
                    <polygon points="24,8 33,2 31,14" fill="#FB7185" />
                    <circle cx="8" cy="6.5" r="1.8" fill="#0F172A" />
                    <circle cx="7.4" cy="5.8" r="0.7" fill="#FFFFFF" />
                </g>

                <!-- Wave Layer 3: Foreground Deep Crystal Lagoon Swell -->
                <path
                    class="lagoon-wave-front"
                    d="M-20,178 C120,152 260,202 420,176 C580,150 720,196 860,172 L860,240 L-20,240 Z"
                    fill="url(#pxLagoonWaveFront)"
                />
                <path
                    d="M-20,178 C120,152 260,202 420,176 C580,150 720,196 860,172"
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="3.2"
                    stroke-linecap="round"
                    opacity="0.82"
                />

                <!-- Left & Right Golden Sandbar Coves -->
                <path
                    d="M-10,188 C75,182 155,204 215,240 L-10,240 Z"
                    fill="url(#pxLagoonSandbar)"
                />
                <path
                    d="M-10,188 C75,182 155,204 215,240"
                    fill="none"
                    stroke="#FFFBEB"
                    stroke-width="2.5"
                    opacity="0.75"
                />

                <path
                    d="M850,186 C760,180 680,204 620,240 L850,240 Z"
                    fill="url(#pxLagoonSandbar)"
                />
                <path
                    d="M850,186 C760,180 680,204 620,240"
                    fill="none"
                    stroke="#FFFBEB"
                    stroke-width="2.5"
                    opacity="0.75"
                />

                <!-- Left Sculpted Sea Kelp & Staghorn Coral -->
                <g transform="translate(26, 134)">
                    <path d="M12,72 C2,52 22,34 10,12 C22,28 8,48 22,72 Z" fill="#10B981" />
                    <path d="M26,74 C18,56 34,40 26,22 C36,36 24,54 34,74 Z" fill="#34D399" />
                    <path
                        d="M46,76 L46,44 C46,34 34,28 34,18 C34,13 40,13 41,18 C42,24 46,28 48,32 L48,14 C48,9 55,9 55,14 L55,34 C58,29 63,24 64,18 C65,13 71,14 70,20 C69,30 56,36 56,46 L56,76 Z"
                        fill="url(#pxCoralPink)"
                    />
                    <circle cx="37" cy="18" r="1.8" fill="#FFE4E6" />
                    <circle cx="51" cy="14" r="1.8" fill="#FFE4E6" />
                    <circle cx="67" cy="19" r="1.8" fill="#FFE4E6" />
                </g>

                <!-- Sculpted 3D Pixar Starfish on Left Sandbar -->
                <g transform="translate(116, 198) rotate(-10)">
                    <path
                        d="M18,2 L23,12 L34,14 L26,22 L28,33 L18,27 L8,33 L10,22 L2,14 L13,12 Z"
                        fill="url(#pxStarfishBody)"
                        stroke="#9A3412"
                        stroke-width="1.5"
                        stroke-linejoin="round"
                    />
                    <circle cx="16" cy="15" r="2.2" fill="#FFEDD5" opacity="0.85" />
                    <circle cx="18" cy="20" r="1.3" fill="#FFEDD5" opacity="0.7" />
                    <circle cx="13" cy="19" r="1.2" fill="#FFEDD5" opacity="0.7" />
                    <circle cx="22" cy="19" r="1.2" fill="#FFEDD5" opacity="0.7" />
                </g>

                <!-- Right Sculpted Coral & Pearl Scallop Shell -->
                <g transform="translate(736, 138)">
                    <path d="M48,74 C58,54 40,36 52,14 C40,30 54,50 38,74 Z" fill="#059669" />
                    <path
                        d="M18,74 L18,46 C18,36 8,30 8,20 C8,15 14,15 15,20 C16,26 20,30 22,34 L22,16 C22,11 29,11 29,16 L29,36 C32,31 37,26 38,20 C39,15 45,16 44,22 C43,32 30,38 30,48 L30,74 Z"
                        fill="url(#pxCoralAmber)"
                    />
                    <circle cx="25" cy="16" r="1.8" fill="#FEF9C3" />
                </g>

                <g transform="translate(684, 202) rotate(8)">
                    <path
                        d="M4,20 C2,9 11,2 20,2 C29,2 38,9 36,20 C31,24 25,26 20,26 C15,26 9,24 4,20 Z"
                        fill="url(#pxPearlShell)"
                        stroke="#B45309"
                        stroke-width="1.5"
                        stroke-linejoin="round"
                    />
                    <path d="M20,4 L20,24 M13,6 L16,24 M27,6 L24,24" stroke="#D97706" stroke-width="1.2" opacity="0.55" />
                    <circle cx="20" cy="19" r="4.2" fill="#FFFFFF" />
                    <circle cx="18.6" cy="17.6" r="1.5" fill="#E0F2FE" />
                </g>

                <circle cx="235" cy="205" r="5" fill="#FFFFFF" opacity="0.55" />
                <circle cx="248" cy="196" r="3" fill="#FFFFFF" opacity="0.65" />
                <circle cx="420" cy="212" r="4.5" fill="#FFFFFF" opacity="0.5" />
                <circle cx="590" cy="200" r="5.5" fill="#FFFFFF" opacity="0.55" />
                <circle cx="604" cy="191" r="3.2" fill="#FFFFFF" opacity="0.65" />
            </svg>
        </template>

        <!-- =====================================================================
             THEME 2: FOREST SCROLL (ENCHANTED EMERALD WOODLAND & MOSSY GLADE)
             ===================================================================== -->
        <template v-else-if="theme === 'forest'">
            <!-- PLANE 1: Woodland Sunbeam Bloom & Mossy Mist Veil -->
            <div class="forest-sun-bloom"></div>
            <div class="forest-glade-veil"></div>

            <!-- PLANE 2: Top Enchanted Canopy, Hanging Vines & Golden Sunbeams SVG -->
            <svg
                class="lagoon-top-svg"
                viewBox="0 0 840 190"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient id="pxForestSunbeam" x1="0" y1="0" x2="0.35" y2="1">
                        <stop offset="0%" stop-color="#FEF08A" stop-opacity="0.55" />
                        <stop offset="55%" stop-color="#6EE7B7" stop-opacity="0.22" />
                        <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
                    </linearGradient>

                    <linearGradient id="pxCanopyLit" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#34D399" />
                        <stop offset="60%" stop-color="#10B981" />
                        <stop offset="100%" stop-color="#059669" />
                    </linearGradient>

                    <linearGradient id="pxCanopyDeep" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#059669" />
                        <stop offset="100%" stop-color="#064E3B" />
                    </linearGradient>

                    <radialGradient id="pxForestSunOrb" cx="34%" cy="28%" r="70%">
                        <stop offset="0%" stop-color="#FFFBEB" />
                        <stop offset="50%" stop-color="#FDE047" />
                        <stop offset="100%" stop-color="#D97706" />
                    </radialGradient>
                </defs>

                <!-- Diagonal Sun-Dappled Woodland God-Rays -->
                <polygon points="30,0 195,0 360,190 120,190" fill="url(#pxForestSunbeam)" />
                <polygon points="220,0 350,0 520,190 330,190" fill="url(#pxForestSunbeam)" opacity="0.72" />
                <polygon points="430,0 530,0 680,190 540,190" fill="url(#pxForestSunbeam)" opacity="0.45" />

                <!-- Warm Sun Peeking Through Upper Canopy -->
                <g transform="translate(135, 36)">
                    <circle cx="0" cy="0" r="32" fill="#FEF08A" opacity="0.36" />
                    <circle cx="0" cy="0" r="19" fill="url(#pxForestSunOrb)" />
                    <circle cx="-6" cy="-6" r="4" fill="#FFFFFF" opacity="0.75" />
                </g>

                <!-- Deep Background Foliage Silhouette Across Top Edge -->
                <path
                    d="M-10,0 L850,0 L850,48 C770,68 700,28 620,52 C530,74 450,24 360,46 C260,68 175,26 85,56 L-10,48 Z"
                    fill="url(#pxCanopyDeep)"
                    opacity="0.88"
                />

                <!-- Foreground Sculpted Leaf Clusters & Hanging Vines -->
                <g class="lagoon-palm-sway-left">
                    <circle cx="28" cy="18" r="38" fill="url(#pxCanopyLit)" />
                    <circle cx="82" cy="14" r="34" fill="url(#pxCanopyLit)" />
                    <circle cx="136" cy="10" r="28" fill="#6EE7B7" opacity="0.85" />
                    <!-- Specular Rim Catchlight -->
                    <path
                        d="M4,28 Q45,42 96,24"
                        fill="none"
                        stroke="#D1FAE5"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        opacity="0.8"
                    />
                    <!-- Hanging Botanical Vines Left -->
                    <path
                        d="M44,42 Q36,78 48,112"
                        fill="none"
                        stroke="#059669"
                        stroke-width="2.2"
                        stroke-linecap="round"
                    />
                    <ellipse cx="40" cy="68" rx="7" ry="4" transform="rotate(-25 40 68)" fill="#34D399" />
                    <ellipse cx="50" cy="86" rx="7" ry="4" transform="rotate(22 50 86)" fill="#10B981" />
                    <circle cx="48" cy="112" r="3.5" fill="#FDE047" />
                </g>

                <g class="lagoon-palm-sway-right">
                    <circle cx="812" cy="20" r="40" fill="url(#pxCanopyLit)" />
                    <circle cx="754" cy="14" r="34" fill="url(#pxCanopyLit)" />
                    <circle cx="702" cy="10" r="26" fill="#6EE7B7" opacity="0.85" />
                    <path
                        d="M792,42 Q802,76 790,106"
                        fill="none"
                        stroke="#059669"
                        stroke-width="2.2"
                        stroke-linecap="round"
                    />
                    <ellipse cx="798" cy="66" rx="7" ry="4" transform="rotate(24 798 66)" fill="#34D399" />
                    <ellipse cx="788" cy="86" rx="7" ry="4" transform="rotate(-20 788 86)" fill="#10B981" />
                    <circle cx="790" cy="106" r="3.5" fill="#FDE047" />
                </g>
            </svg>

            <!-- PLANE 3: Floating Glowing Firefly / Will-o'-the-Wisp Orbs -->
            <div class="lagoon-bubbles-layer">
                <span class="forest-firefly orb-1"></span>
                <span class="forest-firefly orb-2"></span>
                <span class="forest-firefly orb-3"></span>
                <span class="forest-firefly orb-4"></span>
                <span class="forest-firefly orb-5"></span>
                <span class="forest-firefly orb-6"></span>
            </div>

            <!-- PLANE 4: Bottom Rolling Woodland Hills, Evergreen Pines, Cozy Cottage & Spotted Toadstool SVG -->
            <svg
                class="lagoon-bottom-svg"
                viewBox="0 0 840 240"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient id="pxForestHillBack" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#6EE7B7" stop-opacity="0.65" />
                        <stop offset="100%" stop-color="#10B981" stop-opacity="0.85" />
                    </linearGradient>

                    <linearGradient id="pxForestHillMid" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#34D399" stop-opacity="0.88" />
                        <stop offset="100%" stop-color="#059669" stop-opacity="0.96" />
                    </linearGradient>

                    <linearGradient id="pxForestHillFront" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#10B981" />
                        <stop offset="55%" stop-color="#047857" />
                        <stop offset="100%" stop-color="#064E3B" />
                    </linearGradient>

                    <radialGradient id="pxMushroomCap" cx="34%" cy="28%" r="72%">
                        <stop offset="0%" stop-color="#FDA4AF" />
                        <stop offset="48%" stop-color="#F43F5E" />
                        <stop offset="100%" stop-color="#9F1239" />
                    </radialGradient>

                    <linearGradient id="pxMushroomStem" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#FFFBEB" />
                        <stop offset="65%" stop-color="#FDE68A" />
                        <stop offset="100%" stop-color="#D97706" />
                    </linearGradient>
                </defs>

                <!-- Distant Misty Pine Mountain Peaks -->
                <polygon points="90,175 185,78 280,175" fill="#A7F3D0" opacity="0.55" />
                <polygon points="540,175 645,68 750,175" fill="#6EE7B7" opacity="0.5" />

                <!-- Hill Layer 1: Soft Back Meadow -->
                <path
                    d="M-20,145 C140,95 290,165 460,125 C620,88 740,145 860,118 L860,240 L-20,240 Z"
                    fill="url(#pxForestHillBack)"
                />

                <!-- Cozy Storybook Woodland Cottage on Mid-Right Hill -->
                <g transform="translate(618, 102)" opacity="0.92">
                    <rect x="14" y="24" width="34" height="28" rx="4" fill="#FFFBEB" stroke="#065F46" stroke-width="2" />
                    <polygon points="31,4 6,26 56,26" fill="#F59E0B" stroke="#B45309" stroke-width="2" stroke-linejoin="round" />
                    <rect x="26" y="34" width="10" height="18" rx="5" fill="#78350F" />
                    <circle cx="31" cy="18" r="4.5" fill="#FEF08A" stroke="#B45309" stroke-width="1.2" />
                </g>

                <!-- Hill Layer 2: Mid Emerald Woodland -->
                <path
                    d="M-20,168 C160,132 330,192 510,154 C670,120 770,172 860,152 L860,240 L-20,240 Z"
                    fill="url(#pxForestHillMid)"
                />
                <path
                    d="M-20,168 C160,132 330,192 510,154 C670,120 770,172 860,152"
                    fill="none"
                    stroke="#D1FAE5"
                    stroke-width="2.5"
                    opacity="0.7"
                />

                <!-- Sculpted Evergreen Pine Trees (Left & Right) -->
                <g transform="translate(36, 106)">
                    <rect x="20" y="54" width="9" height="26" rx="2" fill="#78350F" />
                    <polygon points="24.5,14 4,56 45,56" fill="#047857" />
                    <polygon points="24.5,2 9,38 40,38" fill="#10B981" />
                    <circle cx="24.5" cy="2" r="3.5" fill="#FEF08A" />
                </g>
                <g transform="translate(82, 124) scale(0.82)">
                    <rect x="20" y="54" width="9" height="26" rx="2" fill="#78350F" />
                    <polygon points="24.5,14 4,56 45,56" fill="#065F46" />
                    <polygon points="24.5,2 9,38 40,38" fill="#34D399" />
                </g>
                <g transform="translate(748, 104)">
                    <rect x="20" y="54" width="9" height="26" rx="2" fill="#78350F" />
                    <polygon points="24.5,14 4,56 45,56" fill="#047857" />
                    <polygon points="24.5,2 9,38 40,38" fill="#10B981" />
                    <circle cx="24.5" cy="2" r="3.5" fill="#FEF08A" />
                </g>

                <!-- Hill Layer 3: Foreground Deep Mossy Glade -->
                <path
                    d="M-20,192 C140,168 310,214 480,188 C650,162 760,204 860,184 L860,240 L-20,240 Z"
                    fill="url(#pxForestHillFront)"
                />
                <path
                    d="M-20,192 C140,168 310,214 480,188 C650,162 760,204 860,184"
                    fill="none"
                    stroke="#A7F3D0"
                    stroke-width="3"
                    opacity="0.78"
                />

                <!-- Sculpted 3D Pixar Spotted Red Toadstool Mushroom on Left Foreground -->
                <g transform="translate(132, 182)">
                    <path d="M14,22 L11,44 C11,48 25,48 25,44 L22,22 Z" fill="url(#pxMushroomStem)" />
                    <path
                        d="M2,24 C0,8 10,2 18,2 C26,2 36,8 34,24 C26,27 10,27 2,24 Z"
                        fill="url(#pxMushroomCap)"
                    />
                    <circle cx="11" cy="11" r="3.2" fill="#FFFBEB" opacity="0.92" />
                    <circle cx="22" cy="9" r="2.4" fill="#FFFBEB" opacity="0.9" />
                    <circle cx="17" cy="18" r="2.8" fill="#FFFBEB" opacity="0.9" />
                    <circle cx="27" cy="17" r="2" fill="#FFFBEB" opacity="0.85" />
                </g>

                <!-- Golden Woodland Wildflowers & Fern Cluster on Right Foreground -->
                <g transform="translate(684, 190)">
                    <path d="M18,38 Q12,22 6,12" stroke="#34D399" stroke-width="2.5" fill="none" stroke-linecap="round" />
                    <path d="M22,38 Q24,20 30,10" stroke="#34D399" stroke-width="2.5" fill="none" stroke-linecap="round" />
                    <circle cx="6" cy="12" r="5.5" fill="#FDE047" stroke="#F59E0B" stroke-width="1.5" />
                    <circle cx="6" cy="12" r="2" fill="#FFFFFF" />
                    <circle cx="30" cy="10" r="6" fill="#FBBF24" stroke="#D97706" stroke-width="1.5" />
                    <circle cx="30" cy="10" r="2.2" fill="#FFFFFF" />
                </g>

                <!-- Glowing Dewdrops along Bottom Grass -->
                <circle cx="245" cy="212" r="4" fill="#FEF08A" opacity="0.7" />
                <circle cx="430" cy="218" r="3.5" fill="#A7F3D0" opacity="0.7" />
                <circle cx="575" cy="208" r="4.5" fill="#FEF08A" opacity="0.65" />
            </svg>
        </template>

        <!-- =====================================================================
             THEME 3: STORY PARCHMENT (ROYAL ILLUMINATED STORYBOOK & GOLDEN SCROLL)
             ===================================================================== -->
        <template v-else-if="theme === 'story'">
            <!-- PLANE 1: Warm Candlelight Gold Bloom & Vintage Vellum Vignette -->
            <div class="story-sun-bloom"></div>
            <div class="story-parchment-veil"></div>

            <!-- PLANE 2: Top Royal Storybook Arch, Floating Sky Lanterns & Golden Filigree SVG -->
            <svg
                class="lagoon-top-svg"
                viewBox="0 0 840 190"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient id="pxStoryRay" x1="0" y1="0" x2="0.25" y2="1">
                        <stop offset="0%" stop-color="#FDE047" stop-opacity="0.45" />
                        <stop offset="60%" stop-color="#FBBF24" stop-opacity="0.16" />
                        <stop offset="100%" stop-color="#F59E0B" stop-opacity="0" />
                    </linearGradient>

                    <linearGradient id="pxStoryGoldTrim" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#FEF08A" />
                        <stop offset="45%" stop-color="#F59E0B" />
                        <stop offset="100%" stop-color="#B45309" />
                    </linearGradient>

                    <radialGradient id="pxStoryLantern" cx="38%" cy="32%" r="70%">
                        <stop offset="0%" stop-color="#FFFBEB" />
                        <stop offset="48%" stop-color="#FDE047" />
                        <stop offset="85%" stop-color="#F97316" />
                        <stop offset="100%" stop-color="#B45309" />
                    </radialGradient>

                    <linearGradient id="pxStoryRibbon" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#FDE68A" stop-opacity="0.55" />
                        <stop offset="50%" stop-color="#FCD34D" stop-opacity="0.32" />
                        <stop offset="100%" stop-color="#FDE68A" stop-opacity="0.55" />
                    </linearGradient>
                </defs>

                <!-- Warm Golden Storybook Light Rays -->
                <polygon points="60,0 220,0 350,190 120,190" fill="url(#pxStoryRay)" />
                <polygon points="620,0 780,0 720,190 490,190" fill="url(#pxStoryRay)" opacity="0.75" />

                <!-- Scrolled Parchment Valance Ribbon Across Top -->
                <path
                    d="M0,0 L840,0 L840,54 C690,26 560,72 420,44 C280,16 150,68 0,48 Z"
                    fill="url(#pxStoryRibbon)"
                />
                <path
                    d="M18,14 L822,14"
                    stroke="url(#pxStoryGoldTrim)"
                    stroke-width="2"
                    stroke-dasharray="8 6"
                    opacity="0.65"
                />

                <!-- Top-Left Illuminated Filigree Corner Arch -->
                <g transform="translate(14, 12)">
                    <path
                        d="M4,64 L4,16 C4,8 10,4 18,4 L88,4"
                        fill="none"
                        stroke="url(#pxStoryGoldTrim)"
                        stroke-width="3.5"
                        stroke-linecap="round"
                    />
                    <path
                        d="M12,48 C12,24 24,12 56,12"
                        fill="none"
                        stroke="#D97706"
                        stroke-width="2"
                        stroke-linecap="round"
                        opacity="0.75"
                    />
                    <circle cx="18" cy="18" r="6" fill="url(#pxStoryGoldTrim)" />
                    <circle cx="16.5" cy="16.5" r="2" fill="#FFFBEB" />
                </g>

                <!-- Top-Right Illuminated Filigree Corner Arch -->
                <g transform="translate(826, 12) scale(-1, 1)">
                    <path
                        d="M4,64 L4,16 C4,8 10,4 18,4 L88,4"
                        fill="none"
                        stroke="url(#pxStoryGoldTrim)"
                        stroke-width="3.5"
                        stroke-linecap="round"
                    />
                    <path
                        d="M12,48 C12,24 24,12 56,12"
                        fill="none"
                        stroke="#D97706"
                        stroke-width="2"
                        stroke-linecap="round"
                        opacity="0.75"
                    />
                    <circle cx="18" cy="18" r="6" fill="url(#pxStoryGoldTrim)" />
                    <circle cx="16.5" cy="16.5" r="2" fill="#FFFBEB" />
                </g>

                <!-- Floating Pixar Glowing Sky Lanterns & 4-Point Star Sparkles -->
                <g class="lagoon-palm-sway-left" transform="translate(92, 26)">
                    <circle cx="14" cy="20" r="22" fill="#FEF08A" opacity="0.35" />
                    <rect x="4" y="6" width="20" height="26" rx="7" fill="url(#pxStoryLantern)" stroke="#92400E" stroke-width="1.4" />
                    <ellipse cx="14" cy="32" rx="7" ry="2.5" fill="#FEF08A" />
                    <circle cx="10" cy="14" r="3" fill="#FFFFFF" opacity="0.75" />
                </g>

                <g class="lagoon-palm-sway-right" transform="translate(722, 22) scale(0.88)">
                    <circle cx="14" cy="20" r="22" fill="#FEF08A" opacity="0.35" />
                    <rect x="4" y="6" width="20" height="26" rx="7" fill="url(#pxStoryLantern)" stroke="#92400E" stroke-width="1.4" />
                    <ellipse cx="14" cy="32" rx="7" ry="2.5" fill="#FEF08A" />
                    <circle cx="10" cy="14" r="3" fill="#FFFFFF" opacity="0.75" />
                </g>

                <!-- Twinkling 4-Point Storybook Stars -->
                <path d="M235,20 L238,28 L246,31 L238,34 L235,42 L232,34 L224,31 L232,28 Z" fill="#F59E0B" opacity="0.75" />
                <path d="M610,18 L612,24 L618,26 L612,28 L610,34 L608,28 L602,26 L608,24 Z" fill="#D97706" opacity="0.7" />
            </svg>

            <!-- PLANE 3: Floating Golden Pixie-Dust / Memory Sparks -->
            <div class="lagoon-bubbles-layer">
                <span class="story-spark-orb orb-1"></span>
                <span class="story-spark-orb orb-2"></span>
                <span class="story-spark-orb orb-3"></span>
                <span class="story-spark-orb orb-4"></span>
                <span class="story-spark-orb orb-5"></span>
                <span class="story-spark-orb orb-6"></span>
            </div>

            <!-- PLANE 4: Bottom Golden Storybook Hills, Castle Spire, Wax Seal Stamp & Feather Quill SVG -->
            <svg
                class="lagoon-bottom-svg"
                viewBox="0 0 840 240"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient id="pxStoryHillBack" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FDE68A" stop-opacity="0.68" />
                        <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.82" />
                    </linearGradient>

                    <linearGradient id="pxStoryHillMid" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FCD34D" stop-opacity="0.85" />
                        <stop offset="100%" stop-color="#D97706" stop-opacity="0.94" />
                    </linearGradient>

                    <linearGradient id="pxStoryHillFront" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#F59E0B" />
                        <stop offset="55%" stop-color="#B45309" />
                        <stop offset="100%" stop-color="#78350F" />
                    </linearGradient>

                    <radialGradient id="pxWaxSeal" cx="34%" cy="28%" r="72%">
                        <stop offset="0%" stop-color="#FDA4AF" />
                        <stop offset="48%" stop-color="#E11D48" />
                        <stop offset="100%" stop-color="#881337" />
                    </radialGradient>

                    <linearGradient id="pxQuillFeather" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#FFFBEB" />
                        <stop offset="50%" stop-color="#FDE047" />
                        <stop offset="100%" stop-color="#D97706" />
                    </linearGradient>
                </defs>

                <!-- Distant Fairytale Castle Spire Silhouette on Right Horizon -->
                <g transform="translate(642, 78)" opacity="0.78">
                    <rect x="18" y="34" width="26" height="44" fill="#D97706" />
                    <polygon points="31,6 13,34 49,34" fill="#B45309" />
                    <rect x="6" y="46" width="14" height="32" fill="#B45309" />
                    <polygon points="13,26 3,46 23,46" fill="#92400E" />
                    <circle cx="31" cy="48" r="4" fill="#FFFBEB" />
                </g>

                <!-- Back Golden Storybook Hill -->
                <path
                    d="M-20,148 C140,104 310,168 470,132 C630,96 740,152 860,124 L860,240 L-20,240 Z"
                    fill="url(#pxStoryHillBack)"
                />

                <!-- Mid Amber Parchment Hill -->
                <path
                    d="M-20,174 C160,142 330,196 510,164 C670,134 760,182 860,160 L860,240 L-20,240 Z"
                    fill="url(#pxStoryHillMid)"
                />
                <path
                    d="M-20,174 C160,142 330,196 510,164 C670,134 760,182 860,160"
                    fill="none"
                    stroke="#FFFBEB"
                    stroke-width="2.5"
                    opacity="0.72"
                />

                <!-- Foreground Rich Bronze-Ochre Scroll Base -->
                <path
                    d="M-20,198 C150,176 320,218 490,194 C660,170 760,208 860,188 L860,240 L-20,240 Z"
                    fill="url(#pxStoryHillFront)"
                />
                <path
                    d="M-20,198 C150,176 320,218 490,194 C660,170 760,208 860,188"
                    fill="none"
                    stroke="#FDE68A"
                    stroke-width="3"
                    opacity="0.82"
                />

                <!-- Left Foreground: Sculpted 3D Crimson Royal Wax Seal & Ribbon -->
                <g transform="translate(46, 174)">
                    <!-- Gold Ribbon Tails -->
                    <path d="M18,34 L6,58 L18,52 L24,60 L28,36 Z" fill="#FDE047" stroke="#B45309" stroke-width="1.2" />
                    <path d="M32,34 L46,56 L34,52 L28,60 L24,36 Z" fill="#F59E0B" stroke="#92400E" stroke-width="1.2" />
                    <!-- Sculpted Wax Seal Disc -->
                    <circle cx="26" cy="24" r="20" fill="url(#pxWaxSeal)" stroke="#881337" stroke-width="1.8" />
                    <circle cx="26" cy="24" r="14" fill="none" stroke="#FECDD3" stroke-width="1.4" stroke-dasharray="4 2" opacity="0.8" />
                    <!-- Embossed Golden Star Emblem -->
                    <path
                        d="M26,14 L29,21 L36,22 L31,27 L32,34 L26,30 L20,34 L21,27 L16,22 L23,21 Z"
                        fill="#FDE047"
                    />
                    <circle cx="19" cy="16" r="3" fill="#FFE4E6" opacity="0.75" />
                </g>

                <!-- Right Foreground: Sculpted Golden Inkwell & Plumed Storyteller Feather Quill -->
                <g transform="translate(716, 148)">
                    <!-- Inkwell Pot -->
                    <rect x="14" y="54" width="28" height="22" rx="6" fill="#1E1B4B" stroke="#FDE047" stroke-width="2" />
                    <rect x="19" y="48" width="18" height="7" rx="2.5" fill="#F59E0B" stroke="#92400E" stroke-width="1.5" />
                    <ellipse cx="22" cy="62" rx="4" ry="6" fill="#6366F1" opacity="0.65" />
                    <!-- Plumed Feather Quill -->
                    <path
                        d="M28,50 C36,30 52,12 68,2 C64,22 50,38 32,48 Z"
                        fill="url(#pxQuillFeather)"
                        stroke="#B45309"
                        stroke-width="1.4"
                    />
                    <path d="M26,54 L66,4" stroke="#FFFBEB" stroke-width="2" stroke-linecap="round" />
                </g>

                <!-- Compass Star & Sparkle Dots -->
                <path d="M420,198 L423,208 L433,211 L423,214 L420,224 L417,214 L407,211 L417,208 Z" fill="#FEF08A" opacity="0.85" />
                <circle cx="265" cy="212" r="3.5" fill="#FEF08A" opacity="0.7" />
                <circle cx="585" cy="210" r="4" fill="#FEF08A" opacity="0.7" />
            </svg>
        </template>
    </div>
</template>

<style scoped>
.pixar-lagoon-paper-backdrop {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
    border-radius: inherit;
}

/* ==========================================================================
   1. LAGOON THEME SURFACE & BLOOMS
   ========================================================================== */
.pixar-lagoon-paper-backdrop.is-theme-lagoon {
    background:
        radial-gradient(circle at 14% 8%, rgba(254, 240, 138, 0.52) 0%, rgba(254, 240, 138, 0) 34%),
        radial-gradient(circle at 88% 16%, rgba(56, 189, 248, 0.28) 0%, rgba(56, 189, 248, 0) 42%),
        radial-gradient(circle at 50% 94%, rgba(45, 212, 191, 0.3) 0%, rgba(45, 212, 191, 0) 45%),
        linear-gradient(180deg, #CCFBF1 0%, #E0F7FF 18%, #F0FDFF 48%, #ECFEFF 74%, #BAE6FD 92%, #FDE68A 100%);
}

.lagoon-sun-bloom {
    position: absolute;
    top: -40px;
    left: -30px;
    width: 280px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(254, 249, 195, 0.72) 0%,
        rgba(253, 224, 71, 0.28) 45%,
        rgba(56, 189, 248, 0) 75%
    );
}

.lagoon-caustic-veil {
    position: absolute;
    inset: 0;
    opacity: 0.35;
    background-image:
        radial-gradient(ellipse 140px 42px at 22% 32%, rgba(255, 255, 255, 0.65) 0%, transparent 70%),
        radial-gradient(ellipse 170px 48px at 78% 46%, rgba(255, 255, 255, 0.55) 0%, transparent 70%),
        radial-gradient(ellipse 150px 40px at 35% 68%, rgba(165, 243, 252, 0.45) 0%, transparent 70%);
}

/* ==========================================================================
   2. FOREST THEME SURFACE & BLOOMS
   ========================================================================== */
.pixar-lagoon-paper-backdrop.is-theme-forest {
    background:
        radial-gradient(circle at 18% 8%, rgba(254, 240, 138, 0.5) 0%, rgba(254, 240, 138, 0) 36%),
        radial-gradient(circle at 86% 18%, rgba(110, 231, 183, 0.32) 0%, rgba(110, 231, 183, 0) 42%),
        radial-gradient(circle at 50% 94%, rgba(52, 211, 153, 0.32) 0%, rgba(52, 211, 153, 0) 46%),
        linear-gradient(180deg, #D1FAE5 0%, #ECFDF5 20%, #F0FDF4 52%, #DCFCE7 80%, #A7F3D0 94%, #6EE7B7 100%);
}

.forest-sun-bloom {
    position: absolute;
    top: -35px;
    left: 20px;
    width: 290px;
    height: 230px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(254, 249, 195, 0.72) 0%,
        rgba(167, 243, 208, 0.34) 48%,
        rgba(16, 185, 129, 0) 76%
    );
}

.forest-glade-veil {
    position: absolute;
    inset: 0;
    opacity: 0.35;
    background-image:
        radial-gradient(ellipse 150px 44px at 24% 34%, rgba(255, 255, 255, 0.65) 0%, transparent 70%),
        radial-gradient(ellipse 165px 46px at 76% 48%, rgba(209, 250, 229, 0.65) 0%, transparent 70%);
}

/* ==========================================================================
   3. STORY PARCHMENT THEME SURFACE & BLOOMS
   ========================================================================== */
.pixar-lagoon-paper-backdrop.is-theme-story {
    background:
        radial-gradient(circle at 16% 10%, rgba(254, 240, 138, 0.56) 0%, rgba(254, 240, 138, 0) 36%),
        radial-gradient(circle at 84% 12%, rgba(253, 224, 71, 0.42) 0%, rgba(253, 224, 71, 0) 38%),
        radial-gradient(circle at 50% 50%, rgba(255, 253, 245, 0.92) 0%, rgba(254, 243, 199, 0.45) 75%),
        linear-gradient(180deg, #FEF3C7 0%, #FFFBEB 20%, #FFFDF5 55%, #FEF3C7 84%, #FDE68A 95%, #F59E0B 100%);
}

.story-sun-bloom {
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translate3d(-50%, 0, 0);
    width: 380px;
    height: 210px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(254, 249, 195, 0.75) 0%,
        rgba(253, 224, 71, 0.28) 50%,
        rgba(245, 158, 11, 0) 78%
    );
}

.story-parchment-veil {
    position: absolute;
    inset: 10px;
    border-radius: inherit;
    border: 1.5px dashed rgba(217, 119, 6, 0.28);
    box-shadow: inset 0 0 42px rgba(217, 119, 6, 0.12);
}

/* ==========================================================================
   SHARED SVG PLANES & FLOATING ORBS (GPU-ONLY ANIMATION)
   ========================================================================== */
.lagoon-top-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 155px;
    display: block;
}

.pixar-lagoon-paper-backdrop.is-compact .lagoon-top-svg {
    height: 105px;
}

.lagoon-bottom-svg {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 175px;
    display: block;
}

.pixar-lagoon-paper-backdrop.is-compact .lagoon-bottom-svg {
    height: 118px;
}

.lagoon-bubbles-layer {
    position: absolute;
    inset: 0;
}

.lagoon-orb,
.forest-firefly,
.story-spark-orb {
    position: absolute;
    border-radius: 50%;
    animation: pxLagoonBubbleFloat 7s ease-in-out infinite alternate;
}

.lagoon-orb {
    background: radial-gradient(
        circle at 32% 28%,
        rgba(255, 255, 255, 0.95) 0%,
        rgba(224, 242, 254, 0.68) 36%,
        rgba(56, 189, 248, 0.28) 74%,
        rgba(45, 212, 191, 0.45) 100%
    );
    border: 1.5px solid rgba(255, 255, 255, 0.78);
    box-shadow:
        inset 0 2px 3px rgba(255, 255, 255, 0.9),
        0 4px 10px rgba(2, 132, 199, 0.14);
}

.forest-firefly {
    background: radial-gradient(
        circle at 34% 28%,
        #FFFBEB 0%,
        #FEF08A 42%,
        #4ADE80 82%,
        #10B981 100%
    );
    border: 1.5px solid rgba(255, 255, 255, 0.85);
    box-shadow:
        0 0 12px rgba(250, 204, 21, 0.55),
        0 4px 10px rgba(5, 150, 105, 0.18);
}

.story-spark-orb {
    background: radial-gradient(
        circle at 34% 28%,
        #FFFFFF 0%,
        #FEF08A 45%,
        #FBBF24 82%,
        #D97706 100%
    );
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    box-shadow:
        0 0 12px rgba(245, 158, 11, 0.48),
        0 4px 10px rgba(180, 83, 9, 0.16);
}

.orb-1 {
    width: 20px;
    height: 20px;
    left: 18px;
    top: 28%;
    animation-delay: 0s;
}

.orb-2 {
    width: 13px;
    height: 13px;
    left: 34px;
    top: 52%;
    animation-delay: 1.2s;
}

.orb-3 {
    width: 24px;
    height: 24px;
    right: 20px;
    top: 34%;
    animation-delay: 0.6s;
}

.orb-4 {
    width: 14px;
    height: 14px;
    right: 38px;
    top: 60%;
    animation-delay: 2.1s;
}

.orb-5 {
    width: 17px;
    height: 17px;
    left: 22px;
    bottom: 24%;
    animation-delay: 1.7s;
}

.orb-6 {
    width: 12px;
    height: 12px;
    right: 26px;
    bottom: 26%;
    animation-delay: 2.8s;
}

.lagoon-palm-sway-left {
    transform-origin: 0% 0%;
    animation: pxLagoonPalmSway 6.5s ease-in-out infinite alternate;
}

.lagoon-palm-sway-right {
    transform-origin: 100% 0%;
    animation: pxLagoonPalmSway 7.2s ease-in-out infinite alternate-reverse;
}

.lagoon-wave-back {
    animation: pxLagoonWaveDrift 8s ease-in-out infinite alternate;
}

.lagoon-wave-mid {
    animation: pxLagoonWaveDrift 6.5s ease-in-out infinite alternate-reverse;
}

@keyframes pxLagoonBubbleFloat {
    0% {
        transform: translate3d(0, 0, 0) scale(1);
    }
    100% {
        transform: translate3d(3px, -12px, 0) scale(1.06);
    }
}

@keyframes pxLagoonPalmSway {
    0% {
        transform: rotate(0deg) scale(1);
    }
    100% {
        transform: rotate(1.8deg) scale(1.01);
    }
}

@keyframes pxLagoonWaveDrift {
    0% {
        transform: translate3d(-6px, 0, 0);
    }
    100% {
        transform: translate3d(6px, 2px, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .lagoon-orb,
    .forest-firefly,
    .story-spark-orb,
    .lagoon-palm-sway-left,
    .lagoon-palm-sway-right,
    .lagoon-wave-back,
    .lagoon-wave-mid {
        animation: none !important;
    }
}
</style>
