<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useSound } from '@/Services/soundManager';

const { duckBgm } = useSound();

const props = defineProps({
    user: {
        type: Object,
        default: () => ({ name: 'Alex Explorer' }),
    },
    module: {
        type: Object,
        default: null,
    },
    lecture: {
        type: Object,
        default: null,
    },
    contents: {
        type: Array,
        default: () => [],
    },
    siblingLectures: {
        type: Array,
        default: () => [],
    },
});

/* ==========================================================================
   FALLBACK DEMO STORY PAGES (WHEN VIEWED WITHOUT DATABASE RECORDS)
   ========================================================================== */
const fallbackModule = {
    id: 1,
    title: 'Whispering Woods: Reading Quest',
    slug: 'whispering-woods',
    grade_level: 'Grade 4 • Interactive Story Realm',
    theme: 'forest',
    icon: '🌲',
    xp_bonus: 300,
    boss_name: 'Elder Oak Guardian',
};

const fallbackLecture = {
    id: 101,
    title: 'The Lantern of Whispering Pines',
    slug: 'lantern-of-whispering-pines',
    description: 'Hover or tap highlighted words to unlock their meanings, or listen to the full-page story narration!',
    estimated_duration_minutes: 6,
    xp_reward: 150,
    sort_order: 1,
};

const fallbackContents = [
    {
        id: 1,
        type: 'text',
        layout_style: 'interactive_story',
        title: 'Page 1: The Glowing Cedar Bark',
        content: `
            <div style="background:linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border:3px solid #F59E0B; border-radius:24px; padding:1.5rem 1.75rem; margin:0 0 1.25rem; box-shadow:0 8px 20px rgba(245, 158, 11, 0.14);">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.75rem;">
                    <span style="background:#D97706; color:#FFFFFF; font-size:0.72rem; font-weight:900; text-transform:uppercase; letter-spacing:0.08em; padding:0.28rem 0.75rem; border-radius:999px;">📖 Interactive Story • Hover or Tap Highlighted Words</span>
                    <span style="font-size:0.76rem; font-weight:800; color:#92400E;">✨ Tap any glowing word to reveal its meaning!</span>
                </div>
                <h1 style="font-family:'Fredoka',sans-serif; font-size:1.85rem; color:#78350F; margin:0 0 0.75rem;">The Lantern of Whispering Pines</h1>
                <p style="font-size:1.12rem; line-height:1.9; color:#1E293B; margin:0 0 0.9rem;">
                    Deep within the Emerald Woods, the <span class="vocab-gloss-token" tabindex="0" role="button" data-vocab-word="ancient" data-vocab-pronunciation="AYN-shunt" data-vocab-meaning="Belonging to the very distant past; hundreds of years old." style="background:linear-gradient(180deg, #FEF9C3 0%, #FDE047 100%); color:#78350F; font-weight:900; padding:0.08rem 0.42rem; border-radius:8px; border-bottom:3px solid #D97706; cursor:pointer; display:inline-block;">ancient</span> cedar trees hummed with forgotten stories. Young explorer Mira held up her brass lantern, watching its <span class="vocab-gloss-token" tabindex="0" role="button" data-vocab-word="luminous" data-vocab-pronunciation="LOO-muh-nus" data-vocab-meaning="Radiating or reflecting bright, warm light in the dark." style="background:linear-gradient(180deg, #FEF9C3 0%, #FDE047 100%); color:#78350F; font-weight:900; padding:0.08rem 0.42rem; border-radius:8px; border-bottom:3px solid #D97706; cursor:pointer; display:inline-block;">luminous</span> amber beam awaken silver letters along the mossy bark.
                </p>
                <p style="font-size:1.12rem; line-height:1.9; color:#1E293B; margin:0;">
                    By searching carefully for <span class="vocab-gloss-token" tabindex="0" role="button" data-vocab-word="context clues" data-vocab-pronunciation="KON-tekst klooz" data-vocab-meaning="Hints found within a sentence or paragraph that help a reader figure out the meaning of an unfamiliar word." style="background:linear-gradient(180deg, #FEF9C3 0%, #FDE047 100%); color:#78350F; font-weight:900; padding:0.08rem 0.42rem; border-radius:8px; border-bottom:3px solid #D97706; cursor:pointer; display:inline-block;">context clues</span> in the carved verses, Mira pieced together the secret trail to the Timber Crossing before twilight fell.
                </p>
            </div>
        `,
        vocabulary_glosses: [
            {
                word: 'ancient',
                pronunciation: 'AYN-shunt',
                meaning: 'Belonging to the very distant past; hundreds of years old.',
            },
            {
                word: 'luminous',
                pronunciation: 'LOO-muh-nus',
                meaning: 'Radiating or reflecting bright, warm light in the dark.',
            },
            {
                word: 'context clues',
                pronunciation: 'KON-tekst klooz',
                meaning: 'Hints found within a sentence or paragraph that help a reader figure out the meaning of an unfamiliar word.',
            },
        ],
        caption: 'Hover or tap any golden word inside the story passage to discover its definition!',
        sort_order: 1,
    },
    {
        id: 2,
        type: 'text',
        layout_style: 'interactive_story',
        title: 'Page 2: Reading Skill — Decoding Unfamiliar Words',
        content: `
            <h2 style="font-family:'Fredoka',sans-serif; font-size:1.55rem; color:#0F172A; margin:0 0 0.65rem;">Key Reading Strategy: Context Clues</h2>
            <p style="font-size:1.06rem; line-height:1.8; color:#334155; margin:0 0 1rem;">
                Great explorers never skip an unfamiliar word! When you spot a new word like <span class="vocab-gloss-token" tabindex="0" role="button" data-vocab-word="inscription" data-vocab-pronunciation="in-SKRIP-shun" data-vocab-meaning="Words or symbols carved, engraved, or written on a stone, monument, or book." style="background:linear-gradient(180deg, #FEF9C3 0%, #FDE047 100%); color:#78350F; font-weight:900; padding:0.08rem 0.42rem; border-radius:8px; border-bottom:3px solid #D97706; cursor:pointer; display:inline-block;">inscription</span>, look at the words right before and after it to unlock its meaning.
            </p>
            <div style="background:linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border:2.5px solid #F59E0B; border-radius:18px; padding:1rem 1.25rem; margin:0.9rem 0; color:#78350F;">
                <strong style="display:block; font-family:'Fredoka',sans-serif; font-size:1rem; margin-bottom:0.25rem;">💡 Explorer Reading Tip</strong>
                <span style="font-size:0.95rem; font-weight:700;">Look for synonyms, examples, or descriptions nearby in the same paragraph!</span>
            </div>
        `,
        vocabulary_glosses: [
            {
                word: 'inscription',
                pronunciation: 'in-SKRIP-shun',
                meaning: 'Words or symbols carved, engraved, or written on a stone, monument, or book.',
            },
        ],
        caption: 'Practice spotting context clues before heading to the Forest Rune Decoder Quiz!',
        sort_order: 2,
    },
];

/* ==========================================================================
   RESOLVED MODULE, LECTURE & PAGES
   ========================================================================== */
const activeModule = computed(() => props.module || fallbackModule);
const activeLecture = computed(() => props.lecture || fallbackLecture);

const pages = computed(() => {
    if (Array.isArray(props.contents) && props.contents.length > 0) {
        return props.contents;
    }

    if (props.lecture?.body) {
        return [
            {
                id: 'lecture-body-1',
                type: 'text',
                layout_style: 'standard',
                title: props.lecture.title || 'Page 1',
                content: props.lecture.body,
                vocabulary_glosses: [],
                sort_order: 1,
            },
        ];
    }

    return fallbackContents;
});

const currentPageIndex = ref(0);
const visitedPageIndices = ref(new Set([0]));
const discoveredWordsMap = ref({});
const activeVocabGloss = ref(null);
const showMobilePagesDrawer = ref(false);
const showCompletionCelebration = ref(false);

// Reader Text Zoom: 100%, 112%, 125%
const zoomLevels = [100, 112, 125];
const currentZoomIndex = ref(0);
const readerZoomPercent = computed(() => zoomLevels[currentZoomIndex.value]);

const cycleReaderZoom = () => {
    currentZoomIndex.value = (currentZoomIndex.value + 1) % zoomLevels.length;
};

const currentPage = computed(() => {
    return pages.value[currentPageIndex.value] || pages.value[0] || null;
});

const totalPages = computed(() => pages.value.length);

const readingProgressPercent = computed(() => {
    if (totalPages.value <= 1) {
        return 100;
    }

    return Math.round((visitedPageIndices.value.size / totalPages.value) * 100);
});

// Strip authoring-only attributes/classes from teacher HTML so the student sees a clean 1:1 page
const sanitizedPageHtml = computed(() => {
    const raw = currentPage.value?.content || '';
    if (!raw) {
        return '';
    }

    return raw
        .replace(/\bis-studio-selected-el\b/g, '')
        .replace(/\bcontenteditable="true"/gi, 'contenteditable="false"');
});

// Plain text of current page for fallback TTS narration when no audio file was uploaded
const currentPagePlainText = computed(() => {
    const raw = currentPage.value?.content || '';
    if (!raw) {
        return currentPage.value?.title || '';
    }

    if (typeof DOMParser !== 'undefined') {
        try {
            const doc = new DOMParser().parseFromString(raw, 'text/html');
            return (doc.body?.textContent || '').replace(/\s+/g, ' ').trim();
        } catch {
            // Fallback regex strip
        }
    }

    return raw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
});

// Merge structured vocabulary_glosses with any inline .vocab-gloss-token spans in the HTML
const activePageVocabularyList = computed(() => {
    const page = currentPage.value;
    if (!page) {
        return [];
    }

    const list = [];
    const seen = new Set();

    if (Array.isArray(page.vocabulary_glosses)) {
        for (const item of page.vocabulary_glosses) {
            if (item?.word && item?.meaning) {
                const key = item.word.trim().toLowerCase();
                if (!seen.has(key)) {
                    seen.add(key);
                    list.push({
                        word: item.word.trim(),
                        pronunciation: item.pronunciation || '',
                        meaning: item.meaning.trim(),
                    });
                }
            }
        }
    }

    if (typeof DOMParser !== 'undefined' && page.content) {
        try {
            const doc = new DOMParser().parseFromString(page.content, 'text/html');
            const tokens = doc.querySelectorAll('.vocab-gloss-token, [data-vocab-meaning]');
            tokens.forEach((el) => {
                const word = (el.getAttribute('data-vocab-word') || el.textContent || '').trim();
                const meaning = (el.getAttribute('data-vocab-meaning') || '').trim();
                const pronunciation = (el.getAttribute('data-vocab-pronunciation') || '').trim();
                if (word && meaning) {
                    const key = word.toLowerCase();
                    if (!seen.has(key)) {
                        seen.add(key);
                        list.push({ word, pronunciation, meaning });
                    }
                }
            });
        } catch {
            // Ignore DOMParser errors in SSR
        }
    }

    return list;
});

const discoveredCountOnCurrentPage = computed(() => {
    return activePageVocabularyList.value.filter(
        (g) => discoveredWordsMap.value[g.word.toLowerCase()]
    ).length;
});

/* ==========================================================================
   HUD AUDIO NARRATION CONTROLS (UPLOADED AUDIO OR WEB SPEECH READ-ALOUD)
   ========================================================================== */
const narrationAudioRef = ref(null);
const isNarrationPlaying = ref(false);
const isSpeechFallbackPlaying = ref(false);
const narrationCurrentTime = ref(0);
const narrationDuration = ref(0);
const narrationSpeed = ref(1);
const speedOptions = [0.85, 1, 1.15];

const isAnyNarrationActive = computed(() => {
    return isNarrationPlaying.value || isSpeechFallbackPlaying.value;
});

watch(isAnyNarrationActive, (active) => {
    duckBgm(active);
});

const cycleNarrationSpeed = () => {
    const idx = speedOptions.indexOf(narrationSpeed.value);
    const nextRate = speedOptions[(idx + 1) % speedOptions.length];
    narrationSpeed.value = nextRate;

    if (narrationAudioRef.value) {
        narrationAudioRef.value.playbackRate = nextRate;
    }
};

const toggleNarrationPlay = () => {
    // Case 1: Teacher uploaded a dedicated Full-Page Read-Aloud Audio file
    if (currentPage.value?.narration_audio_url && narrationAudioRef.value) {
        const audio = narrationAudioRef.value;
        if (audio.paused) {
            audio.playbackRate = narrationSpeed.value;
            audio.play().catch(() => {});
        } else {
            audio.pause();
        }

        return;
    }

    // Case 2: Fallback to browser SpeechSynthesis reading the page aloud
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        if (isSpeechFallbackPlaying.value) {
            window.speechSynthesis.cancel();
            isSpeechFallbackPlaying.value = false;

            return;
        }

        const textToRead = currentPagePlainText.value;
        if (!textToRead) {
            return;
        }

        try {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(textToRead);
            utterance.rate = narrationSpeed.value;
            utterance.onend = () => {
                isSpeechFallbackPlaying.value = false;
            };
            utterance.onerror = () => {
                isSpeechFallbackPlaying.value = false;
            };
            isSpeechFallbackPlaying.value = true;
            window.speechSynthesis.speak(utterance);
        } catch {
            isSpeechFallbackPlaying.value = false;
        }
    }
};

const onNarrationTimeUpdate = () => {
    if (!narrationAudioRef.value) {
        return;
    }
    narrationCurrentTime.value = narrationAudioRef.value.currentTime || 0;
};

const onNarrationLoadedMetadata = () => {
    if (!narrationAudioRef.value) {
        return;
    }
    narrationDuration.value = narrationAudioRef.value.duration || 0;
    narrationAudioRef.value.playbackRate = narrationSpeed.value;
};

const onNarrationSeek = (event) => {
    const audio = narrationAudioRef.value;
    if (!audio || !narrationDuration.value) {
        return;
    }
    const newTime = Number(event.target.value);
    audio.currentTime = newTime;
    narrationCurrentTime.value = newTime;
};

const formatAudioTime = (seconds) => {
    const sec = Math.max(0, Math.floor(Number(seconds) || 0));
    const mins = Math.floor(sec / 60);
    const rem = sec % 60;

    return `${mins}:${rem < 10 ? '0' : ''}${rem}`;
};

const resetAudioPlayer = () => {
    if (narrationAudioRef.value) {
        narrationAudioRef.value.pause();
        narrationAudioRef.value.currentTime = 0;
    }
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        try {
            window.speechSynthesis.cancel();
        } catch {
            // Ignore
        }
    }
    isNarrationPlaying.value = false;
    isSpeechFallbackPlaying.value = false;
    narrationCurrentTime.value = 0;
    narrationDuration.value = 0;
};

/* ==========================================================================
   INTERACTIVE WORD MEANING REVEAL (HOVER / CLICK / VOICE PRONUNCIATION)
   ========================================================================== */
const markWordDiscovered = (word) => {
    if (!word) {
        return;
    }
    discoveredWordsMap.value = {
        ...discoveredWordsMap.value,
        [word.trim().toLowerCase()]: true,
    };
};

const revealVocabGloss = (gloss) => {
    if (!gloss?.meaning) {
        return;
    }

    activeVocabGloss.value = {
        word: gloss.word,
        pronunciation: gloss.pronunciation || '',
        meaning: gloss.meaning,
    };
    markWordDiscovered(gloss.word);
};

const handleArtboardWordInteraction = (event) => {
    const token = event.target?.closest?.('.vocab-gloss-token, [data-vocab-meaning]');
    if (!token) {
        return;
    }

    const word = (token.getAttribute('data-vocab-word') || token.textContent || 'Word').trim();
    const meaning = (token.getAttribute('data-vocab-meaning') || '').trim();
    const pronunciation = (token.getAttribute('data-vocab-pronunciation') || '').trim();

    if (meaning) {
        revealVocabGloss({ word, pronunciation, meaning });
    }
};

const speakVocabWordAloud = (word) => {
    if (!word || typeof window === 'undefined' || !('speechSynthesis' in window)) {
        return;
    }

    try {
        window.speechSynthesis.cancel();
        isSpeechFallbackPlaying.value = false;
        const utterance = new SpeechSynthesisUtterance(word);
        utterance.rate = 0.88;
        utterance.pitch = 1.05;
        window.speechSynthesis.speak(utterance);
    } catch {
        // Ignore if speechSynthesis is unsupported
    }
};

/* ==========================================================================
   PAGE NAVIGATION & COMPLETION HANDLERS
   ========================================================================== */
const goToPage = (index) => {
    if (index < 0 || index >= totalPages.value) {
        return;
    }

    resetAudioPlayer();
    activeVocabGloss.value = null;
    currentPageIndex.value = index;
    visitedPageIndices.value = new Set([...visitedPageIndices.value, index]);
    showMobilePagesDrawer.value = false;

    nextTick(() => {
        const paperEl = document.getElementById('student-studio-artboard-top');
        if (paperEl) {
            paperEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

const goPrevPage = () => {
    if (currentPageIndex.value > 0) {
        goToPage(currentPageIndex.value - 1);
    }
};

const goNextPage = () => {
    if (currentPageIndex.value < totalPages.value - 1) {
        goToPage(currentPageIndex.value + 1);
    }
};

const backToMapUrl = computed(() => {
    if (props.module?.slug) {
        return `/students/modules/${props.module.slug}`;
    }

    return '/students/progress';
});

const returnToExpeditionMap = () => {
    router.visit(backToMapUrl.value);
};

const completeStoryLecture = () => {
    resetAudioPlayer();
    showCompletionCelebration.value = true;

    if (props.module?.slug && activeLecture.value?.slug) {
        fetch(`/students/modules/${props.module.slug}/lectures/${activeLecture.value.slug}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        }).catch((err) => console.error('Lecture completion error:', err));
    }
};

const handleKeydown = (event) => {
    if (event.key === 'ArrowRight' && currentPageIndex.value < totalPages.value - 1) {
        goNextPage();
    } else if (event.key === 'ArrowLeft' && currentPageIndex.value > 0) {
        goPrevPage();
    } else if (event.key === 'Escape' && activeVocabGloss.value) {
        activeVocabGloss.value = null;
    }
};

watch(currentPageIndex, () => {
    resetAudioPlayer();
});

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    resetAudioPlayer();
});
</script>

<template>
    <Head :title="`${activeLecture.title} • ${activeModule.title} • WEBREAD`" />

    <div class="clay-standalone-reader-shell">
        <!-- Rich 3D Cartoon Sky & Clouds Backdrop -->
        <div class="reader-sky-backdrop" aria-hidden="true">
            <div class="sky-sun-orb"></div>
            <div class="sky-cloud-puff cloud-left"></div>
            <div class="sky-cloud-puff cloud-right"></div>
        </div>

        <section class="clay-lecture-reader-page" aria-labelledby="student-lecture-title">
            <!-- =========================================================================
                 1. RESTRUCTURED FLOATING 3D CLAY READER STICKY HUD (WITH AUDIO NARRATION)
                 ========================================================================= -->
            <header class="clay-reader-sticky-hud">
                <!-- Top Specular Gloss Highlight -->
                <span class="hud-top-gloss" aria-hidden="true"></span>

                <div class="hud-main-bar">
                    <!-- ZONE 1 (LEFT): BACK TO MAP + MOBILE PAGES TOGGLE -->
                    <div class="hud-zone-left">
                        <Link
                            :href="backToMapUrl"
                            class="clay-hud-back-btn"
                            aria-label="Back to Island Expedition Map"
                        >
                            <svg viewBox="0 0 20 20" class="back-icon-svg" aria-hidden="true">
                                <path
                                    d="M12.5 4.5L7 10l5.5 5.5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <span>Island Map</span>
                        </Link>

                        <button
                            type="button"
                            class="clay-hud-pages-toggle"
                            @click="showMobilePagesDrawer = !showMobilePagesDrawer"
                        >
                            <span>📑 {{ currentPageIndex + 1 }}/{{ totalPages }}</span>
                        </button>
                    </div>

                    <!-- ZONE 2 (CENTER): MODULE PILL, LECTURE TITLE & GLOSSY PROGRESS TRACK -->
                    <div class="hud-zone-center">
                        <div class="hud-title-row">
                            <span class="hud-world-pill">
                                <span class="hud-biome-dot" aria-hidden="true"></span>
                                <span>{{ activeModule.icon || '🌲' }} {{ activeModule.title }}</span>
                            </span>

                            <h1 id="student-lecture-title" class="hud-lecture-title">
                                {{ activeLecture.title }}
                            </h1>

                            <span class="hud-page-fraction-pill">
                                Page {{ currentPageIndex + 1 }} of {{ totalPages }}
                            </span>
                        </div>

                        <div class="hud-progress-row">
                            <div class="clay-hud-progress-track">
                                <div
                                    class="clay-hud-progress-fill"
                                    :style="{ width: `${readingProgressPercent}%` }"
                                >
                                    <span class="progress-fill-shine"></span>
                                </div>
                            </div>
                            <span class="hud-progress-pct">{{ readingProgressPercent }}%</span>
                        </div>
                                                <div
                            class="clay-hud-narration-pod"
                            :class="{ 'is-playing': isAnyNarrationActive }"
                        >
                            <button
                                type="button"
                                class="clay-hud-narration-play-btn"
                                :class="{ 'is-playing': isAnyNarrationActive }"
                                :aria-label="
                                    isAnyNarrationActive
                                        ? 'Pause Page Narration Audio'
                                        : 'Play Page Narration Audio'
                                "
                                @click="toggleNarrationPlay"
                            >
                                <!-- Play / Pause Icon -->
                                <svg
                                    v-if="!isAnyNarrationActive"
                                    viewBox="0 0 20 20"
                                    class="narration-play-svg"
                                    aria-hidden="true"
                                >
                                    <path d="M6.5 4.2L15.8 10L6.5 15.8V4.2Z" fill="currentColor" />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 20 20"
                                    class="narration-play-svg"
                                    aria-hidden="true"
                                >
                                    <rect x="5.5" y="4.5" width="3.2" height="11" rx="1.2" fill="currentColor" />
                                    <rect x="11.3" y="4.5" width="3.2" height="11" rx="1.2" fill="currentColor" />
                                </svg>

                                <span class="narration-btn-label">
                                    {{ isAnyNarrationActive ? 'Pause Reading' : 'Play Reading' }}
                                </span>

                                <span v-if="isAnyNarrationActive" class="hud-soundwave" aria-hidden="true">
                                    <i></i><i></i><i></i>
                                </span>
                            </button>

                            <button
                                type="button"
                                class="clay-hud-speed-btn"
                                title="Change Narration Reading Speed"
                                @click="cycleNarrationSpeed"
                            >
                                {{ narrationSpeed }}x
                            </button>
                        </div>
                    </div>

                    <!-- ZONE 3 (RIGHT): EMBEDDED AUDIO NARRATION PLAY BUTTON + TEXT ZOOM + XP -->
                    <div class="hud-zone-right">
                        <!-- Audio Narration Control Pod inside Sticky HUD -->


                        <!-- Text Zoom Pill -->
                        <button
                            type="button"
                            class="clay-hud-zoom-btn"
                            title="Adjust Reading Text Size"
                            @click="cycleReaderZoom"
                        >
                            <span>Aa {{ readerZoomPercent }}%</span>
                        </button>

                        <!-- XP Reward Pill -->
                        <span class="clay-hud-xp-pill" title="Story Completion Reward">
                            <svg viewBox="0 0 16 16" class="xp-bolt-svg" aria-hidden="true">
                                <path d="M9.5 1L3 9h4.5L6.5 15L13 7H8.5L9.5 1z" fill="currentColor" />
                            </svg>
                            <span>+{{ activeLecture.xp_reward || 150 }} XP</span>
                        </span>
                    </div>
                </div>

                <!-- Expandable Audio Scrubber Strip inside HUD (Shown when Teacher Uploaded Page Audio & Playing/Loaded) -->
                <div
                    v-if="currentPage?.narration_audio_url && (isNarrationPlaying || narrationCurrentTime > 0)"
                    class="hud-audio-scrubber-bar"
                >
                    <span class="hud-audio-tag">
                        🎧 {{ currentPage.narration_audio_name || 'Page Narration Track' }}
                    </span>
                    <span class="hud-audio-time">{{ formatAudioTime(narrationCurrentTime) }}</span>
                    <input
                        type="range"
                        class="hud-audio-range"
                        min="0"
                        :max="narrationDuration || 100"
                        step="0.1"
                        :value="narrationCurrentTime"
                        aria-label="Seek page narration audio"
                        @input="onNarrationSeek"
                    />
                    <span class="hud-audio-time">{{ formatAudioTime(narrationDuration) }}</span>
                </div>

                <!-- Hidden Native Audio Element for Teacher Uploaded Page Narration -->
                <audio
                    v-if="currentPage?.narration_audio_url"
                    ref="narrationAudioRef"
                    :src="currentPage.narration_audio_url"
                    preload="metadata"
                    class="sr-only-audio"
                    @play="isNarrationPlaying = true"
                    @pause="isNarrationPlaying = false"
                    @ended="isNarrationPlaying = false"
                    @timeupdate="onNarrationTimeUpdate"
                    @loadedmetadata="onNarrationLoadedMetadata"
                ></audio>
            </header>

            <!-- =========================================================================
                 2. MAIN WORKSPACE: LEFT PAGE RAIL + CENTER 1:1 STUDIO PAPER ARTBOARD
                 ========================================================================= -->
            <div class="reader-workspace-grid">
                <!-- LEFT SIDEBAR: STORY PAGES & CHAPTER SWITCHER -->
                <aside
                    class="clay-pages-sidebar"
                    :class="{ 'is-mobile-open': showMobilePagesDrawer }"
                    aria-label="Story Pages Navigation"
                >
                    <div class="sidebar-header-row">
                        <div>
                            <span class="sidebar-kicker">STORY SCROLL</span>
                            <h2 class="sidebar-heading">Lecture Pages ({{ totalPages }})</h2>
                        </div>
                        <button
                            type="button"
                            class="sidebar-mobile-close"
                            aria-label="Close pages drawer"
                            @click="showMobilePagesDrawer = false"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="pages-cards-list">
                        <button
                            v-for="(pageItem, pIdx) in pages"
                            :key="pageItem.id || pIdx"
                            type="button"
                            class="clay-page-thumb-btn"
                            :class="{
                                'is-current': pIdx === currentPageIndex,
                                'is-visited': visitedPageIndices.has(pIdx),
                            }"
                            @click="goToPage(pIdx)"
                        >
                            <div class="page-thumb-top">
                                <span class="page-num-pill">PAGE {{ pIdx + 1 }}</span>
                                <span v-if="visitedPageIndices.has(pIdx)" class="page-visited-check" title="Read">
                                    ✓
                                </span>
                            </div>

                            <strong class="page-thumb-title">
                                {{ pageItem.title || `Story Page ${pIdx + 1}` }}
                            </strong>

                            <div class="page-thumb-badges">
                                <span v-if="pageItem.narration_audio_url" class="thumb-feature-tag tag-audio">
                                    🎧 Read-Aloud
                                </span>
                                <span
                                    v-if="
                                        pageItem.layout_style === 'interactive_story' ||
                                        (pageItem.vocabulary_glosses && pageItem.vocabulary_glosses.length > 0)
                                    "
                                    class="thumb-feature-tag tag-vocab"
                                >
                                    🔍 Word Meanings
                                </span>
                            </div>
                        </button>
                    </div>

                    <!-- Other Lectures in This Module -->
                    <div v-if="siblingLectures && siblingLectures.length > 1" class="sibling-lectures-box">
                        <span class="sidebar-kicker">OTHER CHAPTERS IN MODULE</span>
                        <div class="sibling-links-stack">
                            <Link
                                v-for="sib in siblingLectures"
                                :key="sib.id"
                                :href="`/students/modules/${activeModule.slug}/lectures/${sib.slug}`"
                                class="sibling-lecture-link"
                                :class="{ 'is-active-lecture': sib.id === activeLecture.id }"
                            >
                                <span class="sib-title">{{ sib.title }}</span>
                                <span class="sib-xp">+{{ sib.xp_reward }} XP</span>
                            </Link>
                        </div>
                    </div>
                </aside>

                <!-- CENTER STAGE: 1:1 TEACHER STUDIO PAPER ARTBOARD -->
                <main id="student-studio-artboard-top" class="reader-artboard-stage">
                    <!-- A. THE 1:1 STUDIO PAPER ARTBOARD SHEET -->
                    <article
                        v-if="currentPage"
                        class="student-studio-paper-sheet"
                        :style="{ '--reader-scale': readerZoomPercent / 100 }"
                    >
                        <!-- Paper Sheet Top Meta Header (Matches Teacher Studio Page Header) -->
                        <div class="paper-sheet-meta-bar">
                            <div class="paper-meta-left">
                                <span class="paper-page-chip">
                                    PAGE {{ currentPageIndex + 1 }} OF {{ totalPages }}
                                </span>
                                <span class="paper-section-title">
                                    {{ currentPage.title || `Story Page ${currentPageIndex + 1}` }}
                                </span>
                            </div>

                            <span
                                v-if="activePageVocabularyList.length > 0"
                                class="paper-interactive-hint"
                            >
                                🔍 Tap any glowing word to open its meaning!
                            </span>
                        </div>

                        <!-- 1:1 Rich Document Content Area (Mirrors Teacher's .paper-editable-body) -->
                        <div
                            v-if="sanitizedPageHtml"
                            class="student-paper-content-body"
                            @click="handleArtboardWordInteraction"
                            @keydown.enter.prevent="handleArtboardWordInteraction"
                            @keydown.space.prevent="handleArtboardWordInteraction"
                            v-html="sanitizedPageHtml"
                        ></div>

                        <!-- Standalone Media Block Support (If Teacher Saved an Image / Audio / Video Block) -->
                        <div
                            v-if="currentPage.type === 'image' && currentPage.media_url"
                            class="student-standalone-media-pod"
                        >
                            <img
                                :src="currentPage.media_url"
                                :alt="currentPage.title || 'Story Illustration'"
                                class="standalone-story-img"
                            />
                        </div>

                        <div
                            v-else-if="currentPage.type === 'audio' && (currentPage.media_url || currentPage.external_url)"
                            class="student-standalone-media-pod"
                        >
                            <audio
                                controls
                                class="standalone-story-audio"
                                :src="currentPage.media_url || currentPage.external_url"
                            ></audio>
                        </div>

                        <div
                            v-else-if="currentPage.type === 'video' && (currentPage.media_url || currentPage.external_url)"
                            class="student-standalone-media-pod"
                        >
                            <video
                                controls
                                class="standalone-story-video"
                                :src="currentPage.media_url || currentPage.external_url"
                            ></video>
                        </div>

                        <!-- Teacher Page Caption / Footnote -->
                        <div v-if="currentPage.caption" class="paper-teacher-caption-box">
                            <span class="caption-icon" aria-hidden="true">💬</span>
                            <p class="caption-text">{{ currentPage.caption }}</p>
                        </div>

                        <!-- Interactive Vocabulary Discovery Strip at the Bottom of the Sheet -->
                        <div
                            v-if="activePageVocabularyList.length > 0"
                            class="paper-vocabulary-glossary-strip"
                        >
                            <div class="glossary-strip-head">
                                <span class="glossary-strip-title">
                                    📚 Interactive Words on This Page
                                </span>
                                <span class="glossary-discovery-counter">
                                    Discovered {{ discoveredCountOnCurrentPage }} / {{ activePageVocabularyList.length }}
                                </span>
                            </div>

                            <div class="glossary-words-pills">
                                <button
                                    v-for="(gloss, gIdx) in activePageVocabularyList"
                                    :key="gIdx"
                                    type="button"
                                    class="clay-glossary-word-pill"
                                    :class="{
                                        'is-active': activeVocabGloss?.word === gloss.word,
                                        'is-discovered': discoveredWordsMap[gloss.word.toLowerCase()],
                                    }"
                                    @click="revealVocabGloss(gloss)"
                                >
                                    <span class="pill-icon">
                                        {{ discoveredWordsMap[gloss.word.toLowerCase()] ? '✓' : '🔍' }}
                                    </span>
                                    <span>{{ gloss.word }}</span>
                                </button>
                            </div>
                        </div>
                    </article>

                    <!-- C. STICKY BOTTOM PAGE-TURN & COMPLETE STORY BAR -->
                    <footer class="clay-page-turn-dock">
                        <button
                            type="button"
                            class="clay-turn-btn btn-prev"
                            :disabled="currentPageIndex === 0"
                            @click="goPrevPage"
                        >
                            <span>◀ Previous Page</span>
                        </button>

                        <div class="page-dots-indicator" role="tablist" aria-label="Page indicators">
                            <button
                                v-for="(p, dotIdx) in pages"
                                :key="p.id || dotIdx"
                                type="button"
                                class="page-dot-btn"
                                :class="{
                                    'is-active': dotIdx === currentPageIndex,
                                    'is-visited': visitedPageIndices.has(dotIdx),
                                }"
                                :aria-label="`Go to Page ${dotIdx + 1}`"
                                @click="goToPage(dotIdx)"
                            >
                                {{ dotIdx + 1 }}
                            </button>
                        </div>

                        <button
                            v-if="currentPageIndex < totalPages - 1"
                            type="button"
                            class="clay-turn-btn btn-next"
                            @click="goNextPage"
                        >
                            <span>Next Page ▶</span>
                        </button>

                        <button
                            v-else
                            type="button"
                            class="clay-turn-btn btn-complete"
                            @click="completeStoryLecture"
                        >
                            <span>Complete Story & Claim +{{ activeLecture.xp_reward || 150 }} XP ✓</span>
                        </button>
                    </footer>
                </main>
            </div>
        </section>

        <!-- =========================================================================
             3. INTERACTIVE WORD MEANING SPOTLIGHT MODAL (NO SCROLLING NEEDED)
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="meaning-pop">
                <div
                    v-if="activeVocabGloss"
                    class="clay-word-meaning-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="spotlight-modal-word"
                    @click.self="activeVocabGloss = null"
                >
                    <div class="clay-word-meaning-spotlight">
                        <span class="spotlight-top-gloss" aria-hidden="true"></span>

                        <div class="spotlight-top-row">
                            <div class="spotlight-badge-wrap">
                                <span class="spotlight-icon-orb" aria-hidden="true">🔍</span>
                                <span class="spotlight-kicker">✨ WORD MEANING UNLOCKED</span>
                            </div>

                            <button
                                type="button"
                                class="spotlight-close-btn"
                                aria-label="Close word meaning modal"
                                @click="activeVocabGloss = null"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="spotlight-word-hero-box">
                            <div class="spotlight-word-line">
                                <strong id="spotlight-modal-word" class="spotlight-word">
                                    {{ activeVocabGloss.word }}
                                </strong>
                                <span v-if="activeVocabGloss.pronunciation" class="spotlight-pronunciation">
                                    /{{ activeVocabGloss.pronunciation }}/
                                </span>
                            </div>

                            <button
                                type="button"
                                class="clay-speak-word-btn"
                                title="Hear this word pronounced aloud"
                                @click="speakVocabWordAloud(activeVocabGloss.word)"
                            >
                                <span>🔊 Hear Word</span>
                            </button>
                        </div>

                        <div class="spotlight-definition-well">
                            <span class="definition-well-label">MEANING & DEFINITION</span>
                            <p class="spotlight-definition">
                                {{ activeVocabGloss.meaning }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="clay-spotlight-got-it-btn"
                            @click="activeVocabGloss = null"
                        >
                            <span>Got It! Keep Reading ✓</span>
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- =========================================================================
             4. STORY LECTURE COMPLETION CELEBRATION MODAL
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="meaning-pop">
                <div
                    v-if="showCompletionCelebration"
                    class="clay-celebration-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="story-complete-heading"
                    @click.self="showCompletionCelebration = false"
                >
                    <div class="clay-celebration-dialog">
                        <div class="celebration-trophy-badge" aria-hidden="true">🏆</div>
                        <span class="celebration-kicker">STORY LECTURE COMPLETED!</span>
                        <h2 id="story-complete-heading" class="celebration-title">
                            Awesome Reading, {{ user?.name || 'Explorer' }}!
                        </h2>
                        <p class="celebration-subtitle">
                            You finished all {{ totalPages }} {{ totalPages === 1 ? 'page' : 'pages' }} of
                            <strong>{{ activeLecture.title }}</strong>!
                        </p>

                        <div class="celebration-rewards-row">
                            <div class="reward-pill-card">
                                <span class="reward-lbl">XP EARNED</span>
                                <strong class="reward-val text-amber">+{{ activeLecture.xp_reward || 150 }} XP</strong>
                            </div>
                            <div class="reward-pill-card">
                                <span class="reward-lbl">MASTERY</span>
                                <strong class="reward-val text-emerald">★★★ 3 Stars</strong>
                            </div>
                        </div>

                        <div class="celebration-actions">
                            <button
                                type="button"
                                class="clay-celebration-primary-btn"
                                @click="returnToExpeditionMap"
                            >
                                <span>Continue on Island Expedition Map ▶</span>
                            </button>
                            <button
                                type="button"
                                class="clay-celebration-secondary-btn"
                                @click="showCompletionCelebration = false"
                            >
                                <span>Stay & Review Story Pages</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800&family=Nunito:wght@600;700;800;900&display=swap');

.clay-standalone-reader-shell {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    position: relative;
    min-height: 100vh;
    background: linear-gradient(180deg, #DBEAFE 0%, #EEF2FF 48%, #F0FDF4 100%);
    color: #1E1B4B;
    overflow-x: clip;
}

.reader-sky-backdrop {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}

.sky-sun-orb {
    position: absolute;
    top: -80px;
    right: -60px;
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(254, 240, 138, 0.65) 0%, rgba(254, 240, 138, 0) 70%);
}

.sky-cloud-puff {
    position: absolute;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.65);
    filter: blur(2px);
}

.cloud-left {
    top: 14%;
    left: -40px;
    width: 220px;
    height: 76px;
}

.cloud-right {
    bottom: 18%;
    right: -50px;
    width: 250px;
    height: 84px;
}

.clay-lecture-reader-page {
    position: relative;
    z-index: 1;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0.75rem 0.85rem 3rem;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

/* ==========================================================================
   1. RESTRUCTURED FLOATING 3D CLAY READER STICKY HUD (WITH AUDIO NARRATION)
   ========================================================================== */
.clay-reader-sticky-hud {
    position: sticky;
    top: 0.6rem;
    z-index: 45;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(241, 245, 249, 0.96) 100%);
    backdrop-filter: blur(14px);
    border-radius: 26px;
    padding: 0.65rem 0.9rem;
    border: 3px solid #FFFFFF;
    box-shadow:
        0 7px 0 #CBD5E1,
        0 16px 32px rgba(30, 27, 75, 0.14),
        inset 0 2px 3px rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.hud-top-gloss {
    position: absolute;
    top: 4px;
    left: 22px;
    right: 22px;
    height: 8px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.85) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.hud-main-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* ZONE 1: Left Navigation */
.hud-zone-left {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
}

.clay-hud-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    padding: 0.44rem 0.82rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #6366F1 0%, #4338CA 100%);
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
    box-shadow:
        0 3.5px 0 #312E81,
        0 6px 12px rgba(67, 56, 202, 0.25),
        inset 0 2px 2px rgba(255, 255, 255, 0.4);
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.18s var(--spring-bounce), box-shadow 0.18s ease;
}

.clay-hud-back-btn:hover {
    transform: translateY(-1px);
}

.clay-hud-back-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #312E81;
}

.back-icon-svg {
    width: 15px;
    height: 15px;
}

.clay-hud-pages-toggle {
    display: inline-flex;
    align-items: center;
    padding: 0.42rem 0.72rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #B45309;
}

/* ZONE 2: Center Module Pill, Lecture Title & Progress Bar */
.hud-zone-center {
    flex: 1 1 280px;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.32rem;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 0.42rem 0.75rem;
    box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.04);
}

.hud-title-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}

.hud-world-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.14rem 0.52rem;
    border-radius: 9999px;
    background: #DCFCE7;
    border: 1.5px solid #86EFAC;
    color: #065F46;
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}

.hud-biome-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 6px #10B981;
}

.hud-lecture-title {
    margin: 0;
    flex: 1 1 auto;
    min-width: 0;
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 800;
    color: #1E1B4B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hud-page-fraction-pill {
    padding: 0.14rem 0.5rem;
    border-radius: 9999px;
    background: #E0E7FF;
    color: #3730A3;
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
    white-space: nowrap;
    flex-shrink: 0;
}

.hud-progress-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.clay-hud-progress-track {
    flex: 1 1 auto;
    height: 9px;
    border-radius: 9999px;
    background: #E2E8F0;
    border: 1.5px solid #CBD5E1;
    box-shadow: inset 0 1.5px 3px rgba(15, 23, 42, 0.12);
    overflow: hidden;
    position: relative;
}

.clay-hud-progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #10B981 0%, #34D399 55%, #FACC15 100%);
    transition: width 0.45s var(--spring-bounce);
    position: relative;
}

.progress-fill-shine {
    position: absolute;
    top: 1px;
    left: 5px;
    right: 5px;
    height: 3px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.65);
}

.hud-progress-pct {
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
    color: #475569;
    flex-shrink: 0;
}

/* ZONE 3: Right Audio Narration Play Button + Zoom + XP */
.hud-zone-right {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
    flex-wrap: wrap;
}

.clay-hud-narration-pod {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem;
    border-radius: 9999px;
    background: #EFF6FF;
    border: 2px solid #93C5FD;
    box-shadow: 0 3px 0 #60A5FA;
}

.clay-hud-narration-pod.is-playing {
    background: #FFFBEB;
    border-color: #FBBF24;
    box-shadow: 0 3px 0 #D97706;
}

.clay-hud-narration-play-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.34rem 0.78rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    background: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow:
        0 2.5px 0 #075985,
        inset 0 1.5px 2px rgba(255, 255, 255, 0.45);
    transition: transform 0.16s var(--spring-bounce);
}

.clay-hud-narration-play-btn:hover {
    transform: translateY(-1px);
}

.clay-hud-narration-play-btn.is-playing {
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    color: #FFFFFF;
    box-shadow:
        0 2.5px 0 #78350F,
        inset 0 1.5px 2px rgba(255, 255, 255, 0.45);
}

.narration-play-svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

.narration-btn-label {
    white-space: nowrap;
}

.hud-soundwave {
    display: inline-flex;
    align-items: flex-end;
    gap: 2px;
    height: 12px;
}

.hud-soundwave i {
    display: block;
    width: 2.5px;
    height: 100%;
    background: #FFFFFF;
    border-radius: 2px;
    animation: hudWaveBounce 0.55s ease-in-out infinite alternate;
}

.hud-soundwave i:nth-child(2) { animation-delay: 0.16s; }
.hud-soundwave i:nth-child(3) { animation-delay: 0.32s; }

@keyframes hudWaveBounce {
    from { height: 4px; }
    to { height: 12px; }
}

.clay-hud-speed-btn {
    padding: 0.28rem 0.52rem;
    border-radius: 9999px;
    border: 1.5px solid #BFDBFE;
    background: #FFFFFF;
    color: #1E40AF;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    cursor: pointer;
}

.clay-hud-zoom-btn {
    padding: 0.38rem 0.68rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    background: linear-gradient(180deg, #F8FAFC 0%, #E2E8F0 100%);
    color: #334155;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #94A3B8;
}

.clay-hud-xp-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.22rem;
    padding: 0.38rem 0.72rem;
    border-radius: 9999px;
    border: 2px solid #FFFFFF;
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    box-shadow: 0 3px 0 #B45309;
}

.xp-bolt-svg {
    width: 12px;
    height: 12px;
}

/* Expandable Scrubber Row inside HUD when Uploaded Audio is Active */
.hud-audio-scrubber-bar {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.32rem 0.68rem;
    border-radius: 14px;
    background: #EFF6FF;
    border: 1.5px solid #BFDBFE;
}

.hud-audio-tag {
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
    color: #1E40AF;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.hud-audio-time {
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    color: #1E3A8A;
}

.hud-audio-range {
    flex: 1;
    accent-color: #0284C7;
    cursor: pointer;
    height: 6px;
}

.sr-only-audio {
    display: none;
}

/* ==========================================================================
   2. WORKSPACE GRID: LEFT PAGE RAIL + CENTER ARTBOARD
   ========================================================================== */
.reader-workspace-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    align-items: start;
}

.clay-pages-sidebar {
    display: none;
    background: rgba(255, 255, 255, 0.96);
    border: 3px solid #FFFFFF;
    border-radius: 26px;
    padding: 1rem;
    box-shadow:
        0 7px 0 #CBD5E1,
        0 14px 28px rgba(30, 27, 75, 0.1);
}

.clay-pages-sidebar.is-mobile-open {
    display: block;
}

.sidebar-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.sidebar-kicker {
    display: block;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #6366F1;
}

.sidebar-heading {
    margin: 0;
    font-family: var(--font-display);
    font-size: 0.98rem;
    color: #1E1B4B;
}

.sidebar-mobile-close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid #CBD5E1;
    background: #F8FAFC;
    font-weight: 900;
    cursor: pointer;
}

.pages-cards-list {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.clay-page-thumb-btn {
    width: 100%;
    text-align: left;
    padding: 0.7rem 0.85rem;
    border-radius: 16px;
    border: 2.5px solid #E2E8F0;
    background: #F8FAFC;
    cursor: pointer;
    transition: all 0.16s ease;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.clay-page-thumb-btn:hover {
    border-color: #93C5FD;
    background: #EFF6FF;
    transform: translateY(-1px);
}

.clay-page-thumb-btn.is-current {
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    border-color: #4F46E5;
    box-shadow: 0 4px 0 #4F46E5;
}

.page-thumb-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-num-pill {
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    padding: 0.12rem 0.48rem;
    border-radius: 999px;
    background: #E2E8F0;
    color: #334155;
}

.clay-page-thumb-btn.is-current .page-num-pill {
    background: #4F46E5;
    color: #FFFFFF;
}

.page-visited-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #10B981;
    color: #FFFFFF;
    font-size: 0.65rem;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.page-thumb-title {
    font-family: var(--font-body);
    font-size: 0.82rem;
    font-weight: 800;
    color: #1E293B;
}

.page-thumb-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.thumb-feature-tag {
    font-size: 0.62rem;
    font-weight: 800;
    padding: 0.1rem 0.42rem;
    border-radius: 6px;
}

.tag-audio {
    background: #DBEAFE;
    color: #1D4ED8;
}

.tag-vocab {
    background: #FEF3C7;
    color: #92400E;
}

.sibling-lectures-box {
    margin-top: 1.1rem;
    padding-top: 0.9rem;
    border-top: 2px dashed #E2E8F0;
}

.sibling-links-stack {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    margin-top: 0.45rem;
}

.sibling-lecture-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    padding: 0.5rem 0.65rem;
    border-radius: 12px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 800;
    color: #334155;
}

.sibling-lecture-link.is-active-lecture {
    background: #FEF3C7;
    border-color: #F59E0B;
    color: #78350F;
}

.sib-xp {
    font-size: 0.66rem;
    color: #D97706;
    white-space: nowrap;
}

/* ==========================================================================
   3. INTERACTIVE WORD MEANING SPOTLIGHT MODAL
   ========================================================================== */
.reader-artboard-stage {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
}

.clay-word-meaning-backdrop {
    position: fixed;
    inset: 0;
    z-index: 85;
    background: rgba(15, 23, 42, 0.56);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.clay-word-meaning-spotlight {
    position: relative;
    width: 100%;
    max-width: 440px;
    background: linear-gradient(180deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 3.5px solid #FFFFFF;
    border-radius: 28px;
    padding: 1.35rem 1.35rem 1.25rem;
    box-shadow:
        0 9px 0 #D97706,
        0 24px 48px rgba(15, 23, 42, 0.34),
        inset 0 2px 3px rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.spotlight-top-gloss {
    position: absolute;
    top: 5px;
    left: 24px;
    right: 24px;
    height: 8px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.85) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.spotlight-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.spotlight-badge-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.spotlight-icon-orb {
    width: 32px;
    height: 32px;
    border-radius: 11px;
    background: linear-gradient(180deg, #FBBF24 0%, #D97706 100%);
    border: 2px solid #FFFFFF;
    box-shadow: 0 3px 0 #92400E;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
}

.spotlight-kicker {
    display: inline-block;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #92400E;
    background: rgba(255, 255, 255, 0.75);
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    border: 1.5px solid #FCD34D;
}

.spotlight-word-hero-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.78);
    border: 2px solid #FDE68A;
    border-radius: 20px;
    padding: 0.75rem 0.95rem;
}

.spotlight-word-line {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.spotlight-word {
    font-family: var(--font-display);
    font-size: 1.45rem;
    font-weight: 800;
    color: #78350F;
    line-height: 1.15;
}

.spotlight-pronunciation {
    font-family: var(--font-body);
    font-size: 0.86rem;
    font-weight: 800;
    color: #B45309;
    background: #FEF3C7;
    padding: 0.12rem 0.5rem;
    border-radius: 8px;
}

.clay-speak-word-btn {
    padding: 0.36rem 0.78rem;
    border-radius: 999px;
    border: 2px solid #FFFFFF;
    background: linear-gradient(180deg, #38BDF8 0%, #0284C7 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #075985;
    transition: transform 0.15s ease;
}

.clay-speak-word-btn:hover {
    transform: translateY(-1px);
}

.spotlight-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #F59E0B;
    background: #FFFFFF;
    color: #78350F;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 2.5px 0 #D97706;
}

.spotlight-definition-well {
    background: #FFFFFF;
    border: 2.5px solid #FCD34D;
    border-radius: 20px;
    padding: 0.9rem 1.05rem;
    box-shadow: inset 0 2px 4px rgba(180, 83, 9, 0.06);
}

.definition-well-label {
    display: block;
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.07em;
    color: #B45309;
    margin-bottom: 0.28rem;
}

.spotlight-definition {
    margin: 0;
    font-family: var(--font-body);
    font-size: 1rem;
    font-weight: 800;
    color: #451A03;
    line-height: 1.55;
}

.clay-spotlight-got-it-btn {
    width: 100%;
    padding: 0.72rem 1rem;
    border-radius: 18px;
    border: 2.5px solid #FFFFFF;
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.88rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 4px 0 #065F46;
    transition: transform 0.15s ease;
}

.clay-spotlight-got-it-btn:hover {
    transform: translateY(-1px);
}

/* ==========================================================================
   4. THE 1:1 STUDIO PAPER ARTBOARD SHEET (EXACT TEACHER DESIGN MATCH)
   ========================================================================== */
.student-studio-paper-sheet {
    --reader-scale: 1;
    width: 100%;
    max-width: 820px;
    margin: 0 auto;
    background: #FFFFFF;
    border-radius: 26px;
    border: 3px solid #FFFFFF;
    box-shadow:
        0 10px 0 #CBD5E1,
        0 24px 50px rgba(15, 23, 42, 0.12);
    padding: clamp(1.25rem, 4vw, 2.75rem);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.paper-sheet-meta-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding-bottom: 0.8rem;
    border-bottom: 2px dashed #E2E8F0;
}

.paper-meta-left {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.paper-page-chip {
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    background: #EEF2FF;
    border: 1.5px solid #818CF8;
    color: #4338CA;
    font-family: var(--font-display);
    font-size: 0.66rem;
    font-weight: 800;
}

.paper-section-title {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: #334155;
}

.paper-interactive-hint {
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
    background: #FEF3C7;
    border: 1.5px solid #F59E0B;
    color: #92400E;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
}

/* Mirrors .paper-editable-body from Teachers LectureContent.vue */
.student-paper-content-body {
    font-family: 'Nunito', sans-serif;
    font-size: calc(1.05rem * var(--reader-scale, 1));
    line-height: 1.8;
    color: #1E293B;
    word-break: break-word;
}

.student-paper-content-body :deep(img),
.student-paper-content-body :deep(video),
.student-paper-content-body :deep(audio),
.student-paper-content-body :deep(table) {
    max-width: 100%;
}

.student-paper-content-body :deep(.vocab-gloss-token) {
    background: linear-gradient(180deg, #FEF9C3 0%, #FDE047 100%);
    color: #78350F;
    font-weight: 900;
    padding: 0.08rem 0.42rem;
    border-radius: 8px;
    border-bottom: 3px solid #D97706;
    cursor: pointer;
    display: inline-block;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.student-paper-content-body :deep(.vocab-gloss-token:hover),
.student-paper-content-body :deep(.vocab-gloss-token:focus) {
    transform: translateY(-2px);
    box-shadow: 0 5px 12px rgba(217, 119, 6, 0.32);
    outline: none;
}

.student-standalone-media-pod {
    margin: 0.5rem 0;
}

.standalone-story-img,
.standalone-story-video {
    width: 100%;
    border-radius: 18px;
    border: 3px solid #E2E8F0;
}

.standalone-story-audio {
    width: 100%;
}

.paper-teacher-caption-box {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    padding: 0.75rem 1rem;
    border-radius: 16px;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
}

.caption-icon {
    font-size: 1rem;
}

.caption-text {
    margin: 0;
    font-family: var(--font-body);
    font-size: 0.85rem;
    font-weight: 800;
    color: #475569;
}

/* Interactive Vocabulary Strip at Bottom of Sheet */
.paper-vocabulary-glossary-strip {
    margin-top: 0.5rem;
    padding: 0.9rem 1.05rem;
    border-radius: 18px;
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 2.5px solid #FCD34D;
}

.glossary-strip-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 0.55rem;
}

.glossary-strip-title {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: #92400E;
}

.glossary-discovery-counter {
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    padding: 0.12rem 0.55rem;
    border-radius: 999px;
    background: #FFFFFF;
    color: #B45309;
    border: 1.5px solid #F59E0B;
}

.glossary-words-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.clay-glossary-word-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.32rem 0.72rem;
    border-radius: 999px;
    border: 2px solid #F59E0B;
    background: #FFFFFF;
    color: #78350F;
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #D97706;
    transition: transform 0.15s ease;
}

.clay-glossary-word-pill:hover,
.clay-glossary-word-pill.is-active {
    background: #FDE047;
    transform: translateY(-2px);
}

.clay-glossary-word-pill.is-discovered {
    border-color: #10B981;
    box-shadow: 0 3px 0 #059669;
}

/* ==========================================================================
   5. BOTTOM STICKY PAGE-TURN DOCK
   ========================================================================== */
.clay-page-turn-dock {
    width: 100%;
    max-width: 820px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.96);
    border: 3px solid #FFFFFF;
    border-radius: 24px;
    padding: 0.75rem 1rem;
    box-shadow:
        0 6px 0 #CBD5E1,
        0 14px 28px rgba(30, 27, 75, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.clay-turn-btn {
    padding: 0.6rem 1.15rem;
    border-radius: 16px;
    font-family: var(--font-display);
    font-size: 0.84rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.clay-turn-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.btn-prev {
    background: #F1F5F9;
    border: 2.5px solid #94A3B8;
    color: #334155;
    box-shadow: 0 4px 0 #64748B;
}

.btn-next {
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%);
    border: 2.5px solid #312E81;
    color: #FFFFFF;
    box-shadow: 0 4px 0 #312E81;
}

.btn-complete {
    background: linear-gradient(180deg, #34D399 0%, #10B981 100%);
    border: 2.5px solid #065F46;
    color: #FFFFFF;
    box-shadow: 0 4px 0 #065F46;
}

.page-dots-indicator {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.page-dot-btn {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    border: 2px solid #CBD5E1;
    background: #F8FAFC;
    color: #475569;
    font-family: var(--font-display);
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
}

.page-dot-btn.is-visited {
    border-color: #6EE7B7;
    background: #ECFDF5;
    color: #065F46;
}

.page-dot-btn.is-active {
    background: #4F46E5;
    border-color: #312E81;
    color: #FFFFFF;
    box-shadow: 0 2px 0 #312E81;
}

/* ==========================================================================
   6. COMPLETION CELEBRATION MODAL
   ========================================================================== */
.clay-celebration-backdrop {
    position: fixed;
    inset: 0;
    z-index: 80;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.clay-celebration-dialog {
    width: 100%;
    max-width: 440px;
    background: #FFFFFF;
    border: 4px solid #FDE047;
    border-radius: 30px;
    padding: 1.65rem 1.45rem;
    text-align: center;
    box-shadow:
        0 10px 0 #D97706,
        0 24px 48px rgba(0, 0, 0, 0.3);
}

.celebration-trophy-badge {
    width: 68px;
    height: 68px;
    margin: 0 auto 0.65rem;
    border-radius: 22px;
    background: linear-gradient(180deg, #FEF08A 0%, #F59E0B 100%);
    border: 3px solid #B45309;
    box-shadow: 0 5px 0 #B45309;
    font-size: 2.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.celebration-kicker {
    display: inline-block;
    padding: 0.2rem 0.7rem;
    border-radius: 999px;
    background: #FEF3C7;
    color: #B45309;
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
}

.celebration-title {
    margin: 0.45rem 0 0.3rem;
    font-family: var(--font-display);
    font-size: 1.4rem;
    color: #1E1B4B;
}

.celebration-subtitle {
    margin: 0 0 1rem;
    font-family: var(--font-body);
    font-size: 0.88rem;
    font-weight: 700;
    color: #475569;
}

.celebration-rewards-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    margin-bottom: 1.15rem;
}

.reward-pill-card {
    padding: 0.65rem;
    border-radius: 16px;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
}

.reward-lbl {
    display: block;
    font-family: var(--font-display);
    font-size: 0.62rem;
    color: #64748B;
}

.reward-val {
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 800;
}

.text-amber { color: #D97706; }
.text-emerald { color: #059669; }

.celebration-actions {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.clay-celebration-primary-btn {
    width: 100%;
    padding: 0.78rem 1rem;
    border-radius: 16px;
    border: 2.5px solid #065F46;
    background: linear-gradient(180deg, #34D399 0%, #10B981 100%);
    color: #FFFFFF;
    font-family: var(--font-display);
    font-size: 0.9rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 4px 0 #065F46;
}

.clay-celebration-secondary-btn {
    width: 100%;
    padding: 0.62rem 1rem;
    border-radius: 14px;
    border: 2px solid #CBD5E1;
    background: #F8FAFC;
    color: #475569;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
}

.meaning-pop-enter-active,
.meaning-pop-leave-active {
    transition: all 0.22s ease;
}

.meaning-pop-enter-from,
.meaning-pop-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.97);
}

@media (max-width: 767px) {
    .hud-zone-center {
        order: 3;
        flex: 1 1 100%;
    }

    .hud-world-pill {
        display: none;
    }
}

@media (min-width: 1024px) {
    .reader-workspace-grid {
        grid-template-columns: 280px minmax(0, 1fr);
    }

    .clay-pages-sidebar {
        display: block;
        position: sticky;
        top: 96px;
    }

    .clay-hud-pages-toggle,
    .sidebar-mobile-close {
        display: none;
    }
}
</style>
