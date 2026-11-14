<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { defineWord, generateLectureAssist } from '@/Services/geminiAi.js';
import { useSaveQueue } from '@/Composables/useSaveQueue.js';
import { DEFAULT_PAPER_THEME_ID, findPaperTheme, paperThemes } from '@/Services/paperThemes.js';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Master Storyteller',
            email: 'teacher@example.com',
            role: 'teacher',
        }),
    },
    module: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Conflict and Community Values',
            slug: 'conflict-and-community-values',
            icon: 'compass',
            theme: 'forest',
            grade_level: 'Grade 3–6 • Reading Legends',
        }),
    },
    lecture: {
        type: Object,
        default: () => ({
            id: 1,
            title: 'Chapter 1: Phonics and Folklore',
            slug: 'chapter-1-phonics-and-folklore',
            description: 'Learn foundational reading strategies with audio narration and illustrated folklore.',
            body: '',
            xp_reward: 150,
            sort_order: 1,
            is_published: true,
        }),
    },
    existing_contents: {
        type: Array,
        default: () => [],
    },
});

/* ==========================================================================
   PAPER THEMES, FONTS & HIGHLIGHT SWATCHES
   ========================================================================== */
const fontFamilies = [
    { id: 'Nunito, sans-serif', label: 'Nunito (Story Sans)' },
    { id: 'Fredoka, sans-serif', label: 'Fredoka (Playful Display)' },
    { id: 'Georgia, serif', label: 'Georgia (Book Serif)' },
];

const highlightSwatches = [
    { label: 'Sunny Gold', color: '#FEF08A', border: '#F59E0B', text: '#78350F' },
    { label: 'Mint Glow', color: '#A7F3D0', border: '#10B981', text: '#064E3B' },
    { label: 'Sky Blue', color: '#BAE6FD', border: '#0284C7', text: '#0C4A6E' },
    { label: 'Rose Coral', color: '#FECDD3', border: '#E11D48', text: '#881337' },
    { label: 'Lavender', color: '#E9D5FF', border: '#9333EA', text: '#4C1D95' },
];

/* ==========================================================================
   CANVA TEMPLATES (INCLUDING INTERACTIVE STORY WORD-MEANING LAYOUT)
   ========================================================================== */
const canvaTemplates = [
    {
        id: 'interactive-story-gloss',
        name: 'Interactive Story (Hover/Tap Word Meanings)',
        tag: '★ Interactive Story Layout',
        layoutStyle: 'interactive_story',
        desc: 'Story layout with highlighted vocabulary words that reveal their meaning on hover or click!',
        defaultTitle: 'The Lantern of Whispering Pines',
        presetGlosses: [
            {
                word: 'luminous',
                pronunciation: 'adj. • loo-muh-nuhs',
                meaning: 'Shining or glowing brightly, especially in the dark.',
                color: '#FEF08A',
            },
            {
                word: 'ancient',
                pronunciation: 'adj. • ayn-shuhnt',
                meaning: 'Belonging to a very distant past; hundreds or thousands of years old.',
                color: '#BAE6FD',
            },
            {
                word: 'persevere',
                pronunciation: 'verb • pur-suh-veer',
                meaning: 'To keep trying and moving forward even when a journey is difficult.',
                color: '#A7F3D0',
            },
        ],
        html: `<h2>Chapter 1: The Glowing Cedar Trail</h2><p>Deep within the Emerald Woods, the <span class="vocab-gloss-token" data-vocab-word="ancient" data-vocab-pronunciation="adj. • ayn-shuhnt" data-vocab-meaning="Belonging to a very distant past; hundreds or thousands of years old." style="background-color: #BAE6FD; border-bottom: 2px dashed #0284C7; padding: 1px 6px; border-radius: 6px; font-weight: 800; cursor: pointer;">ancient</span> cedar trees hummed with forgotten folklore. Young explorer <strong>Mira</strong> held up her brass lantern, watching its warm golden beam awaken <span class="vocab-gloss-token" data-vocab-word="luminous" data-vocab-pronunciation="adj. • loo-muh-nuhs" data-vocab-meaning="Shining or glowing brightly, especially in the dark." style="background-color: #FEF08A; border-bottom: 2px dashed #D97706; padding: 1px 6px; border-radius: 6px; font-weight: 800; cursor: pointer;">luminous</span> silver letters along the mossy bark.</p><p>Though the mountain mist grew chilly, Mira chose to <span class="vocab-gloss-token" data-vocab-word="persevere" data-vocab-pronunciation="verb • pur-suh-veer" data-vocab-meaning="To keep trying and moving forward even when a journey is difficult." style="background-color: #A7F3D0; border-bottom: 2px dashed #059669; padding: 1px 6px; border-radius: 6px; font-weight: 800; cursor: pointer;">persevere</span> along the winding stepping-stones until the hidden map revealed the Timber Crossing.</p><div class="studio-insert-block" data-studio-block="true" data-block-label="Interactive Story Tip" style="background: #FFFBEB; border: 2px solid #FDE68A; padding: 12px 16px; border-radius: 14px; margin: 16px 0; color: #78350F;"><strong>✨ Reader Tip:</strong> Hover over or tap any highlighted word in the story above to unlock its secret meaning!</div>`,
    },
    {
        id: 'chapter-opener',
        name: 'Chapter Opener',
        tag: 'Story Intro',
        layoutStyle: 'standard',
        desc: 'Append a chapter header, narrative paragraph, and guiding question box.',
        defaultTitle: 'Chapter 1: The Whispering Lantern',
        html: `<h2>The Mystery Begins in the Woods</h2><p>Deep within the Emerald Woods, the tallest cedar trees hummed with forgotten folklore. Young explorer <strong>Mira</strong> held up her brass lantern, watching its warm golden beam awaken <span class="vocab-gloss-token" data-vocab-word="luminous" data-vocab-pronunciation="adj. • loo-muh-nuhs" data-vocab-meaning="Shining or glowing brightly in the dark." style="background-color: #FEF08A; border-bottom: 2px dashed #D97706; padding: 1px 6px; border-radius: 6px; font-weight: 800; cursor: pointer;">luminous</span> silver letters along the mossy bark.</p><div class="studio-insert-block" data-studio-block="true" data-block-label="Guiding Reading Question" style="background: #EFF6FF; border-left: 4px solid #2563EB; padding: 14px 16px; border-radius: 12px; margin: 16px 0;"><strong>💡 Guiding Reading Question:</strong><br/>Why do you think the ancient storytellers hid their messages inside the cedar trees? Look for context clues in the paragraph above!</div><p>As twilight settled over the valley, Mira opened her leather-bound journal and sketched the first symbol she discovered.</p>`,
    },
    {
        id: 'conflict-dilemma',
        name: 'Conflict & Perspectives',
        tag: 'Character Study',
        layoutStyle: 'standard',
        desc: 'Compare two character viewpoints during a story dilemma.',
        defaultTitle: 'Two Sides of the Canyon Bridge',
        html: `<h2>Understanding Character Perspectives</h2><p>Every great story conflict happens when characters see the same problem in different ways:</p><div class="studio-insert-block" data-studio-block="true" data-block-label="Leo's Perspective" style="background: #F0F9FF; border-left: 4px solid #0284C7; padding: 14px 16px; border-radius: 12px; margin: 12px 0;"><strong>🗣️ Leo's Perspective (Courage):</strong><br/>"If we wait until morning, the village on the ridge won't receive the medicine scroll in time. We must cross carefully now!"</div><div class="studio-insert-block" data-studio-block="true" data-block-label="Toby's Perspective" style="background: #FFF1F2; border-left: 4px solid #E11D48; padding: 14px 16px; border-radius: 12px; margin: 12px 0;"><strong>🗣️ Toby's Perspective (Caution):</strong><br/>"The canyon wind is howling! Waiting for the storm to pass keeps the scroll dry and keeps our team safe."</div><p><strong>Think & Reflect:</strong> Which character's plan would you support, and why?</p>`,
    },
    {
        id: 'fable-moral',
        name: 'Fable & Moral Lesson',
        tag: 'Folklore',
        layoutStyle: 'standard',
        desc: 'Classic storybook passage paired with a golden moral takeaway banner.',
        defaultTitle: 'The Fox and the Starlight Stream',
        html: `<h2>The Fox and the Starlight Stream</h2><p>Once upon a time, a swift ember fox raced ahead of his companions to reach the mountain summit first. Along the steep trail, he came across a fallen boulder blocking the narrow path.</p><div class="studio-insert-block" data-studio-block="true" data-block-label="Moral of the Story Banner" style="background: linear-gradient(135deg, #FEF9C3 0%, #FDE68A 100%); border: 2px solid #F59E0B; padding: 16px 18px; border-radius: 16px; margin: 16px 0; color: #78350F;"><strong>🌟 Moral of the Story:</strong><br/>Speed may take you far alone, but teamwork and kindness open the paths that no single explorer can clear by themselves.</div><p><br/></p>`,
    },
];

const canvaElements = [
    {
        id: 'hint-box',
        label: 'Reading Hint Box',
        badge: '💡 Hint',
        color: '#2563EB',
        bg: '#EFF6FF',
        html: `<div class="studio-insert-block" data-studio-block="true" data-block-label="Reading Hint Box" style="background: #EFF6FF; border-left: 4px solid #2563EB; padding: 14px 16px; border-radius: 12px; margin: 14px 0;"><strong>💡 Reading Strategy Hint:</strong><br/>Look closely at the adjectives in this paragraph to discover how the character feels.</div><p><br/></p>`,
    },
    {
        id: 'vocab-card',
        label: 'Vocabulary Callout',
        badge: '🔑 Word',
        color: '#B45309',
        bg: '#FFFBEB',
        html: `<div class="studio-insert-block" data-studio-block="true" data-block-label="Vocabulary Callout" style="background: #FFFBEB; border: 2px solid #FDE68A; padding: 14px 16px; border-radius: 14px; margin: 14px 0;"><strong>🔑 Word Spotlight: [Word]</strong><br/><strong>Definition:</strong> Write a kid-friendly definition here.<br/><em>Example: "Use the vocabulary word in a vivid story sentence."</em></div><p><br/></p>`,
    },
    {
        id: 'dialogue-bubble',
        label: 'Character Dialogue',
        badge: '🗣️ Quote',
        color: '#059669',
        bg: '#ECFDF5',
        html: `<div class="studio-insert-block" data-studio-block="true" data-block-label="Character Dialogue" style="background: #ECFDF5; border: 2px dashed #34D399; padding: 14px 16px; border-radius: 16px; margin: 14px 0;"><strong>🗣️ Character Speaks:</strong><br/><em>"Every island holds a secret for readers who pay attention to the smallest details!"</em></div><p><br/></p>`,
    },
    {
        id: 'conflict-alert',
        label: 'Conflict Climax Box',
        badge: '⚡ Conflict',
        color: '#E11D48',
        bg: '#FFF1F2',
        html: `<div class="studio-insert-block" data-studio-block="true" data-block-label="Conflict Climax Box" style="background: #FFF1F2; border-left: 4px solid #E11D48; padding: 14px 16px; border-radius: 12px; margin: 14px 0;"><strong>⚡ Story Conflict Checkpoint:</strong><br/>What obstacle is standing in the main character's way right now, and what choices do they have?</div><p><br/></p>`,
    },
    {
        id: 'moral-banner',
        label: 'Golden Moral Banner',
        badge: '🌟 Moral',
        color: '#92400E',
        bg: '#FEF3C7',
        html: `<div class="studio-insert-block" data-studio-block="true" data-block-label="Golden Moral Banner" style="background: #FEF3C7; border: 2px solid #F59E0B; padding: 16px 18px; border-radius: 16px; margin: 14px 0; text-align: center;"><strong>🌟 Key Takeaway & Lesson</strong><br/>Summarize the big idea or moral lesson of this passage here!</div><p><br/></p>`,
    },
    {
        id: 'story-divider',
        label: 'Ornamental Divider',
        badge: '✦ Divider',
        color: '#6366F1',
        bg: '#EEF2FF',
        html: `<div class="studio-insert-block" data-studio-block="true" data-block-label="Ornamental Divider" style="margin: 18px 0; text-align: center;"><hr style="border: none; border-top: 2px dashed #CBD5E1; margin-bottom: 6px;" /><span style="color: #64748B; font-weight: 800; letter-spacing: 0.2em;">✦ ✦ ✦</span></div><p><br/></p>`,
    },
];

const storybookStickers = [
    { id: 's1', label: 'Forest & Mountains Book', src: '/images/book_left_trees_mountains.png' },
    { id: 's2', label: 'Explorer Boy on Book', src: '/images/boy_left_on_book_laptop.png' },
    { id: 's3', label: 'Reader Girl on Book', src: '/images/girl_left_reading_book.png' },
    { id: 's4', label: 'River & Mountains Book', src: '/images/book_right_mountains_river.png' },
    { id: 's5', label: 'Story Message Girl', src: '/images/book_with_message_and_laptop_girl.png' },
    { id: 's6', label: 'Tablet Explorer Girl', src: '/images/girl_right_using_tablet.png' },
];

const uploadMediaCategories = [
    {
        id: 'image',
        label: 'Illustration Frame',
        badgeColor: '#059669',
        badgeBg: '#D1FAE5',
        desc: 'Insert an image/illustration frame directly into your page without losing text',
        accept: 'image/png,image/jpeg,image/webp,image/gif',
    },
    {
        id: 'audio',
        label: 'Sound Effect / Clip',
        badgeColor: '#D97706',
        badgeBg: '#FEF3C7',
        desc: 'Embed an inline audio clip block inside the story body',
        accept: 'audio/mpeg,audio/wav,audio/mp4,audio/ogg',
    },
    {
        id: 'video',
        label: 'Animated Video Frame',
        badgeColor: '#7C3AED',
        badgeBg: '#EDE9FE',
        desc: 'Embed an animated story video or YouTube link onto your page',
        accept: 'video/mp4,video/webm',
    },
];

/* ==========================================================================
   REACTIVE STUDIO STATE
   ========================================================================== */
const activeToolboxTab = ref('templates'); // 'templates' | 'vocab' | 'elements' | 'media' | 'stickers'
const isLeftDrawerOpen = ref(true);
const isRightDeckOpen = ref(true);
const isFocusMode = ref(false);
const viewMode = ref('canvas'); // 'canvas' | 'preview'
const canvasZoom = ref(100);
const selectedFontFamily = ref(fontFamilies[0].id);
const editingContentId = ref(null);
const saveSuccessToast = ref('');

// Global background save queue (keeps uploading and showing progress even after leaving this page)
const saveQueue = useSaveQueue();
const pendingSaveCount = saveQueue.pendingCount;
let draftCounter = 0;
const newDraftKey = () => `lecture-${props.lecture.id}-${Date.now()}-${++draftCounter}`;
const canvasDraftKey = ref(newDraftKey());
let unsubscribeSaveQueue = null;

// Full-Page Read-Aloud Narration Audio State (Specifically for the whole page!)
const narrationFileInputRef = ref(null);
const pageNarrationPreviewUrl = ref(null);
const pageNarrationDisplayName = ref('');

// Interactive Word Meaning (Vocabulary Gloss) Creator State
const showVocabGlossModal = ref(false);
const vocabWordInput = ref('');
const vocabPronunciationInput = ref('');
const vocabMeaningInput = ref('');
const vocabColorInput = ref(highlightSwatches[0]);
let savedSelectionRange = null;

// Gemini AI Vocabulary Assistant State
const isAiDefiningWord = ref(false);
const aiDefineError = ref('');

// Gemini AI Story Generator State
const showAiStoryModal = ref(false);
const aiStoryTopic = ref('');
const isGeneratingAiStory = ref(false);
const aiStoryError = ref('');

// Active Hover/Click Word Meaning Popover (Works in both Canvas & Student Preview!)
const activeVocabTooltip = ref(null); // { word, pronunciation, meaning, x, y }

// Uploaded Inline Media Assets Tray
const uploadedAssetsTray = ref([]);
const uploadCategoryFilter = ref('image');
const externalMediaUrlInput = ref('');
const externalMediaCaptionInput = ref('');
const fileInputRef = ref(null);
const isDraggingFile = ref(false);

// Interactive Canvas Element Selection State (Remove / Move / Duplicate)
const editorSurfaceRef = ref(null);
const selectedCanvasBlockEl = ref(null);
const selectedCanvasBlockLabel = ref('');
const selectedBlockTopPx = ref(0);

const form = useForm({
    type: 'text',
    layout_style: 'interactive_story',
    paper_theme: DEFAULT_PAPER_THEME_ID,
    title: '',
    content: '',
    media_file: null,
    narration_audio_file: null,
    remove_narration_audio: false,
    vocabulary_glosses: [],
    external_url: '',
    duration_seconds: null,
    alt_text: '',
    caption: '',
});

/** The paper the current page is drawn on; saved with the page and shown to students. */
const activePaperTheme = computed(() => findPaperTheme(form.paper_theme));

/* ==========================================================================
   MULTI-STEP UNDO & REDO HISTORY ENGINE
   ========================================================================== */
const historyStack = ref([]);
const historyIndex = ref(-1);
const isRestoringHistory = ref(false);
let typingDebounceTimer = null;

const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(() => historyIndex.value < historyStack.value.length - 1);

const pushHistorySnapshot = () => {
    if (isRestoringHistory.value) {
        return;
    }

    const currentHtml = editorSurfaceRef.value ? editorSurfaceRef.value.innerHTML : form.content || '';
    const currentTitle = form.title || '';

    const latest = historyStack.value[historyIndex.value];
    if (latest && latest.content === currentHtml && latest.title === currentTitle) {
        return;
    }

    const nextStack = historyStack.value.slice(0, historyIndex.value + 1);
    nextStack.push({
        title: currentTitle,
        content: currentHtml,
    });

    if (nextStack.length > 50) {
        nextStack.shift();
    }

    historyStack.value = nextStack;
    historyIndex.value = nextStack.length - 1;
};

const scheduleHistorySnapshot = () => {
    if (typingDebounceTimer) {
        window.clearTimeout(typingDebounceTimer);
    }
    typingDebounceTimer = window.setTimeout(() => {
        pushHistorySnapshot();
    }, 380);
};

const handleUndo = () => {
    if (!canUndo.value) {
        return;
    }

    isRestoringHistory.value = true;
    clearSelectedCanvasBlock();
    historyIndex.value--;
    const snapshot = historyStack.value[historyIndex.value];
    form.title = snapshot.title;
    form.content = snapshot.content;

    nextTick(() => {
        if (editorSurfaceRef.value) {
            editorSurfaceRef.value.innerHTML = snapshot.content;
        }
        syncGlossesListFromDom();
        isRestoringHistory.value = false;
        triggerToast('↶ Undid last change');
    });
};

const handleRedo = () => {
    if (!canRedo.value) {
        return;
    }

    isRestoringHistory.value = true;
    clearSelectedCanvasBlock();
    historyIndex.value++;
    const snapshot = historyStack.value[historyIndex.value];
    form.title = snapshot.title;
    form.content = snapshot.content;

    nextTick(() => {
        if (editorSurfaceRef.value) {
            editorSurfaceRef.value.innerHTML = snapshot.content;
        }
        syncGlossesListFromDom();
        isRestoringHistory.value = false;
        triggerToast('↷ Redid change');
    });
};

const resetHistoryWithCurrentState = () => {
    const currentHtml = editorSurfaceRef.value ? editorSurfaceRef.value.innerHTML : form.content || '';
    historyStack.value = [
        {
            title: form.title || '',
            content: currentHtml,
        },
    ];
    historyIndex.value = 0;
    markCanvasClean();
};

/* ==========================================================================
   UNSAVED-CHANGES TRACKING (drives auto-save when leaving the page)
   ========================================================================== */
let canvasBaseline = { title: '', html: '', paperTheme: DEFAULT_PAPER_THEME_ID };

const currentCanvasHtml = () => {
    const html = editorSurfaceRef.value ? editorSurfaceRef.value.innerHTML : form.content || '';

    return html.replace(/\s?is-selected-studio-block/g, '');
};

const markCanvasClean = () => {
    canvasBaseline = { title: form.title || '', html: currentCanvasHtml(), paperTheme: form.paper_theme };
};

const isCanvasDirty = () =>
    (form.title || '') !== canvasBaseline.title
    || currentCanvasHtml() !== canvasBaseline.html
    || form.paper_theme !== canvasBaseline.paperTheme;

/* ==========================================================================
   INTERACTIVE WORD MEANING (VOCABULARY GLOSS) FUNCTIONS
   ========================================================================== */
const syncGlossesListFromDom = () => {
    if (!editorSurfaceRef.value) {
        return;
    }

    const tokens = editorSurfaceRef.value.querySelectorAll('.vocab-gloss-token, [data-vocab-meaning]');
    const extracted = [];
    tokens.forEach((el) => {
        const word = el.getAttribute('data-vocab-word') || el.innerText.trim();
        const meaning = el.getAttribute('data-vocab-meaning') || '';
        const pronunciation = el.getAttribute('data-vocab-pronunciation') || '';
        if (word && meaning) {
            extracted.push({
                word,
                meaning,
                pronunciation,
            });
        }
    });
    form.vocabulary_glosses = extracted;
};

const openVocabGlossCreatorFromSelection = () => {
    const selection = window.getSelection();
    let selectedText = '';

    if (
        selection &&
        selection.rangeCount > 0 &&
        editorSurfaceRef.value &&
        editorSurfaceRef.value.contains(selection.anchorNode)
    ) {
        savedSelectionRange = selection.getRangeAt(0).cloneRange();
        selectedText = selection.toString().trim();
    } else {
        savedSelectionRange = null;
    }

    vocabWordInput.value = selectedText;
    vocabPronunciationInput.value = '';
    vocabMeaningInput.value = '';
    vocabColorInput.value = highlightSwatches[0];
    aiDefineError.value = '';
    showVocabGlossModal.value = true;
};

const fetchAiWordMeaning = async () => {
    const word = vocabWordInput.value.trim();
    if (!word) {
        return;
    }

    isAiDefiningWord.value = true;
    aiDefineError.value = '';

    try {
        const result = await defineWord({
            word,
            context: plainTextContent.value || '',
            gradeLevel: props.module.grade_level || '',
        });

        if (result) {
            if (result.meaning) {
                vocabMeaningInput.value = result.meaning;
            }
            if (result.pronunciation) {
                vocabPronunciationInput.value = result.pronunciation;
            }
            if (result.suggested_color) {
                const match = highlightSwatches.find(s =>
                    s.label.toLowerCase().includes(result.suggested_color.toLowerCase())
                );
                if (match) {
                    vocabColorInput.value = match;
                }
            }
            triggerToast(`✨ Gemini defined "${word}"!`);
        }
    } catch (err) {
        aiDefineError.value = err.message || 'Gemini definition unavailable';
        triggerToast(`⚠️ ${err.message || 'Unable to define word'}`);
    } finally {
        isAiDefiningWord.value = false;
    }
};

const generateStoryWithAi = async () => {
    const topic = aiStoryTopic.value.trim() || props.lecture.title || props.module.title;
    if (!topic) {
        aiStoryError.value = 'Please provide a topic or theme.';
        return;
    }

    isGeneratingAiStory.value = true;
    aiStoryError.value = '';

    try {
        const result = await generateLectureAssist({
            topic,
            gradeLevel: props.module.grade_level,
            mode: 'full_story',
        });

        if (result && result.story_html) {
            form.content = result.story_html;
            form.layout_style = 'interactive_story';
            if (result.title && !form.title) {
                form.title = result.title;
            }
            if (editorSurfaceRef.value) {
                editorSurfaceRef.value.innerHTML = result.story_html;
            }
            syncGlossesListFromDom();
            pushHistorySnapshot();
            showAiStoryModal.value = false;
            triggerToast('✨ Generated adventure story with vocabulary glosses!');
        }
    } catch (err) {
        aiStoryError.value = err.message || 'Gemini story generation failed.';
        triggerToast(`⚠️ ${err.message || 'Could not generate story'}`);
    } finally {
        isGeneratingAiStory.value = false;
    }
};

const applyVocabMeaningToWord = () => {
    const word = vocabWordInput.value.trim();
    const meaning = vocabMeaningInput.value.trim();
    const pronunciation = vocabPronunciationInput.value.trim();
    const swatch = vocabColorInput.value || highlightSwatches[0];

    if (!word || !meaning) {
        return;
    }

    const escapedWord = word.replace(/"/g, '&quot;');
    const escapedMeaning = meaning.replace(/"/g, '&quot;');
    const escapedPron = pronunciation.replace(/"/g, '&quot;');

    const spanHtml = `<span class="vocab-gloss-token" data-vocab-word="${escapedWord}" data-vocab-pronunciation="${escapedPron}" data-vocab-meaning="${escapedMeaning}" style="background-color: ${swatch.color}; border-bottom: 2px dashed ${swatch.border}; padding: 1px 6px; border-radius: 6px; font-weight: 800; cursor: pointer;">${word}</span>`;

    if (savedSelectionRange && editorSurfaceRef.value) {
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedSelectionRange);

        if (!savedSelectionRange.collapsed) {
            document.execCommand('insertHTML', false, spanHtml);
        } else {
            editorSurfaceRef.value.innerHTML = (editorSurfaceRef.value.innerHTML || '') + ' ' + spanHtml + ' ';
        }
    } else if (editorSurfaceRef.value) {
        // Check if the word exists in plain text on the canvas and wrap its first un-glossed occurrence, or append
        const currentHtml = editorSurfaceRef.value.innerHTML || '';
        const regex = new RegExp(`\\b(${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})\\b`, 'i');
        if (regex.test(plainTextContent.value)) {
            editorSurfaceRef.value.innerHTML = currentHtml.replace(regex, spanHtml);
        } else {
            editorSurfaceRef.value.innerHTML = currentHtml + `<p>${spanHtml}</p>`;
        }
    }

    if (editorSurfaceRef.value) {
        form.content = editorSurfaceRef.value.innerHTML;
        form.layout_style = 'interactive_story';
        syncGlossesListFromDom();
        pushHistorySnapshot();
    }

    showVocabGlossModal.value = false;
    savedSelectionRange = null;
    triggerToast(`✨ Attached interactive meaning to "${word}"!`);
};

const removeVocabGlossWord = (wordToRemove) => {
    if (!editorSurfaceRef.value) {
        return;
    }

    const tokens = editorSurfaceRef.value.querySelectorAll('.vocab-gloss-token, [data-vocab-meaning]');
    tokens.forEach((el) => {
        const w = el.getAttribute('data-vocab-word') || el.innerText.trim();
        if (w.toLowerCase() === wordToRemove.toLowerCase()) {
            const textNode = document.createTextNode(el.innerText);
            el.replaceWith(textNode);
        }
    });

    form.content = editorSurfaceRef.value.innerHTML;
    syncGlossesListFromDom();
    pushHistorySnapshot();
    activeVocabTooltip.value = null;
    triggerToast(`Removed word meaning from "${wordToRemove}"`);
};

const handleVocabHoverOrClick = (event) => {
    const tokenEl = event.target.closest('.vocab-gloss-token, [data-vocab-meaning]');
    if (!tokenEl) {
        if (event.type === 'click') {
            activeVocabTooltip.value = null;
        }

        return;
    }

    const word = tokenEl.getAttribute('data-vocab-word') || tokenEl.innerText.trim();
    const meaning = tokenEl.getAttribute('data-vocab-meaning') || '';
    const pronunciation = tokenEl.getAttribute('data-vocab-pronunciation') || '';

    if (meaning) {
        activeVocabTooltip.value = {
            word,
            meaning,
            pronunciation,
        };
    }
};

/* ==========================================================================
   FULL-PAGE READ-ALOUD NARRATION AUDIO HANDLERS
   ========================================================================== */
const triggerPageNarrationUpload = () => {
    narrationFileInputRef.value?.click();
};

const handlePageNarrationFileChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    form.narration_audio_file = file;
    form.remove_narration_audio = false;
    pageNarrationDisplayName.value = file.name;

    if (pageNarrationPreviewUrl.value && pageNarrationPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(pageNarrationPreviewUrl.value);
    }
    pageNarrationPreviewUrl.value = URL.createObjectURL(file);
    triggerToast(`🔊 Attached full-page Read-Aloud audio: "${file.name}"`);
};

const removePageNarrationAudio = () => {
    if (pageNarrationPreviewUrl.value && pageNarrationPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(pageNarrationPreviewUrl.value);
    }
    pageNarrationPreviewUrl.value = null;
    pageNarrationDisplayName.value = '';
    form.narration_audio_file = null;
    form.remove_narration_audio = true;
    if (narrationFileInputRef.value) {
        narrationFileInputRef.value.value = '';
    }
    triggerToast('Removed page Read-Aloud narration audio.');
};

/* ==========================================================================
   COMPUTED DOCUMENT METRICS (WORD COUNT, CHAR COUNT, READING TIME)
   ========================================================================== */
const plainTextContent = computed(() => {
    const raw = form.content || '';

    return raw
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
});

const wordCount = computed(() => {
    if (!plainTextContent.value) {
        return 0;
    }

    return plainTextContent.value.split(' ').filter(Boolean).length;
});

const charCount = computed(() => plainTextContent.value.length);

const estimatedReadingMinutes = computed(() => {
    if (wordCount.value === 0) {
        return 1;
    }

    return Math.max(1, Math.ceil(wordCount.value / 130));
});

const activeEditingPageIndex = computed(() => {
    if (!editingContentId.value) {
        return (props.existing_contents?.length || 0) + backgroundDraftCards.value.length + 1;
    }

    const idx = (props.existing_contents || []).findIndex((c) => c.id === editingContentId.value);

    return idx >= 0 ? idx + 1 : 1;
});

/* ==========================================================================
   INTERACTIVE BLOCK SELECTION & REMOVAL INSIDE THE CANVAS
   ========================================================================== */
const clearSelectedCanvasBlock = () => {
    if (selectedCanvasBlockEl.value) {
        selectedCanvasBlockEl.value.classList.remove('is-selected-studio-block');
    }
    selectedCanvasBlockEl.value = null;
    selectedCanvasBlockLabel.value = '';
};

const handleCanvasClick = (event) => {
    handleVocabHoverOrClick(event);

    if (!editorSurfaceRef.value) {
        return;
    }

    const blockEl = event.target.closest('[data-studio-block="true"], figure, .studio-insert-block');
    if (blockEl && editorSurfaceRef.value.contains(blockEl)) {
        if (selectedCanvasBlockEl.value && selectedCanvasBlockEl.value !== blockEl) {
            selectedCanvasBlockEl.value.classList.remove('is-selected-studio-block');
        }
        selectedCanvasBlockEl.value = blockEl;
        blockEl.classList.add('is-selected-studio-block');
        selectedCanvasBlockLabel.value =
            blockEl.getAttribute('data-block-label') ||
            blockEl.querySelector('figcaption')?.innerText ||
            'Inserted Canvas Element';

        const surfaceRect = editorSurfaceRef.value.getBoundingClientRect();
        const blockRect = blockEl.getBoundingClientRect();
        selectedBlockTopPx.value = Math.max(0, blockRect.top - surfaceRect.top - 42);
    } else {
        clearSelectedCanvasBlock();
    }
};

const removeSelectedCanvasBlock = () => {
    if (!selectedCanvasBlockEl.value || !editorSurfaceRef.value) {
        return;
    }

    const label = selectedCanvasBlockLabel.value;
    selectedCanvasBlockEl.value.remove();
    clearSelectedCanvasBlock();
    form.content = editorSurfaceRef.value.innerHTML;
    syncGlossesListFromDom();
    pushHistorySnapshot();
    triggerToast(`Removed "${label}" from page`);
};

const moveSelectedCanvasBlock = (direction) => {
    if (!selectedCanvasBlockEl.value || !editorSurfaceRef.value) {
        return;
    }

    const el = selectedCanvasBlockEl.value;
    if (direction === 'up' && el.previousElementSibling) {
        el.parentNode.insertBefore(el, el.previousElementSibling);
    } else if (direction === 'down' && el.nextElementSibling) {
        el.parentNode.insertBefore(el.nextElementSibling, el);
    }
    form.content = editorSurfaceRef.value.innerHTML;
    pushHistorySnapshot();
};

const duplicateSelectedCanvasBlock = () => {
    if (!selectedCanvasBlockEl.value || !editorSurfaceRef.value) {
        return;
    }

    const clone = selectedCanvasBlockEl.value.cloneNode(true);
    clone.classList.remove('is-selected-studio-block');
    selectedCanvasBlockEl.value.insertAdjacentElement('afterend', clone);
    form.content = editorSurfaceRef.value.innerHTML;
    pushHistorySnapshot();
    triggerToast(`Duplicated "${selectedCanvasBlockLabel.value}"`);
};

/* ==========================================================================
   WYSIWYG DOCUMENT EDITOR & CANVA INSERTION FUNCTIONS
   ========================================================================== */
const syncEditorFromForm = () => {
    nextTick(() => {
        if (editorSurfaceRef.value) {
            clearSelectedCanvasBlock();
            const cleanHtml = (form.content || '').replace(/is-selected-studio-block/g, '');
            if (editorSurfaceRef.value.innerHTML !== cleanHtml) {
                editorSurfaceRef.value.innerHTML = cleanHtml;
            }
            syncGlossesListFromDom();
        }
    });
};

const onEditorInput = () => {
    if (editorSurfaceRef.value) {
        form.content = editorSurfaceRef.value.innerHTML;
        syncGlossesListFromDom();
        scheduleHistorySnapshot();
    }
};

const appendHtmlToPresentation = (htmlSnippet, toastMessage) => {
    nextTick(() => {
        if (editorSurfaceRef.value) {
            clearSelectedCanvasBlock();
            const current = editorSurfaceRef.value.innerHTML || '';
            editorSurfaceRef.value.innerHTML = current + htmlSnippet;
            form.content = editorSurfaceRef.value.innerHTML;
            syncGlossesListFromDom();
            pushHistorySnapshot();
            if (toastMessage) {
                triggerToast(toastMessage);
            }
        }
    });
};

const execFormat = (command, value = null) => {
    nextTick(() => {
        if (editorSurfaceRef.value) {
            editorSurfaceRef.value.focus();
            document.execCommand(command, false, value);
            form.content = editorSurfaceRef.value.innerHTML;
            pushHistorySnapshot();
        }
    });
};

const applyBlockHeading = (tag) => {
    execFormat('formatBlock', tag);
};

const applyHighlightColor = (hexColor) => {
    execFormat('hiliteColor', hexColor);
};

const applyTemplateToCanvas = (tpl, mode = 'append') => {
    if (!form.title) {
        form.title = tpl.defaultTitle;
    }
    if (tpl.layoutStyle) {
        form.layout_style = tpl.layoutStyle;
    }

    if (mode === 'replace' || !plainTextContent.value) {
        form.content = tpl.html;
        syncEditorFromForm();
        nextTick(() => pushHistorySnapshot());
        triggerToast(`Applied "${tpl.name}" layout to page!`);

        return;
    }

    appendHtmlToPresentation(tpl.html, `Added "${tpl.name}" section to your presentation!`);
};

const insertCanvaElement = (element) => {
    appendHtmlToPresentation(element.html, `Inserted "${element.label}" onto page!`);
};

const insertStoryStickerIntoDoc = (sticker) => {
    const stickerHtml = `<figure class="studio-insert-block" data-studio-block="true" data-block-label="${sticker.label}" style="margin: 18px auto; text-align: center; max-width: 280px; padding: 12px; border-radius: 16px; background: rgba(248, 250, 252, 0.7); border: 2px dashed #CBD5E1;"><img src="${sticker.src}" alt="${sticker.label}" style="width: 100%; height: auto; filter: drop-shadow(0 8px 16px rgba(15,23,42,0.15)); border-radius: 10px;" /><figcaption style="font-size: 0.82rem; color: #475569; font-weight: 700; margin-top: 6px;">${sticker.label}</figcaption></figure><p><br/></p>`;
    appendHtmlToPresentation(stickerHtml, `Added "${sticker.label}" artwork! (Click it anytime to remove or move)`);
};

/* ==========================================================================
   UPLOADS TAB: INSERT IMAGES / AUDIO / VIDEO INTO PRESENTATION WITHOUT LOSING TEXT
   ========================================================================== */
const currentUploadCategoryMeta = computed(() => {
    return (
        uploadMediaCategories.find((c) => c.id === uploadCategoryFilter.value) ||
        uploadMediaCategories[0]
    );
});

const triggerUploadBrowser = (categoryId = null) => {
    if (categoryId) {
        uploadCategoryFilter.value = categoryId;
    }
    nextTick(() => {
        fileInputRef.value?.click();
    });
};

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        processUploadedMediaFile(file);
    }
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleFileDrop = (event) => {
    isDraggingFile.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        processUploadedMediaFile(file);
    }
};

const insertMediaAssetOntoCanvas = (asset) => {
    // Media still uploading in the background is tagged so its local preview URL can be swapped for the server URL.
    const pendingAttr = asset.uploadJobId ? ` data-pending-upload="${asset.uploadJobId}"` : '';
    let mediaHtml = '';
    if (asset.kind === 'image') {
        mediaHtml = `<figure class="studio-insert-block" data-studio-block="true" data-block-label="Illustration: ${asset.name}" style="margin: 18px auto; text-align: center; max-width: 520px; padding: 14px; border-radius: 18px; background: #F8FAFC; border: 2px solid #E2E8F0; box-shadow: 0 6px 16px rgba(15,23,42,0.06);"><img${pendingAttr} src="${asset.src}" alt="${asset.name}" style="max-width: 100%; max-height: 360px; border-radius: 12px; object-fit: contain; margin: 0 auto; display: block;" /><figcaption style="font-size: 0.84rem; color: #475569; font-weight: 700; margin-top: 8px;">🖼️ ${asset.caption || asset.name}</figcaption></figure><p><br/></p>`;
    } else if (asset.kind === 'audio') {
        mediaHtml = `<div class="studio-insert-block" data-studio-block="true" data-block-label="Audio Clip: ${asset.name}" style="margin: 16px 0; padding: 14px 18px; border-radius: 16px; background: #FFFBEB; border: 2px solid #FDE68A;"><strong>🎧 Inline Sound Clip: ${asset.caption || asset.name}</strong><audio${pendingAttr} controls src="${asset.src}" style="width: 100%; margin-top: 8px;"></audio></div><p><br/></p>`;
    } else {
        mediaHtml = `<figure class="studio-insert-block" data-studio-block="true" data-block-label="Video: ${asset.name}" style="margin: 18px auto; text-align: center; max-width: 540px; padding: 14px; border-radius: 18px; background: #F5F3FF; border: 2px solid #DDD6FE;"><video${pendingAttr} controls src="${asset.src}" style="width: 100%; max-height: 340px; border-radius: 12px;"></video><figcaption style="font-size: 0.84rem; color: #5B21B6; font-weight: 700; margin-top: 8px;">🎬 ${asset.caption || asset.name}</figcaption></figure><p><br/></p>`;
    }

    appendHtmlToPresentation(mediaHtml, `Inserted "${asset.name}" into your presentation!`);
};

/**
 * Uploads the file as its own background queue job (with progress) and inserts a local preview right away.
 * The preview's blob URL is replaced with the stored file URL once the upload finishes.
 */
const processUploadedMediaFile = (file) => {
    const isImg = file.type.startsWith('image/');
    const isAud = file.type.startsWith('audio/');
    const kind = isImg ? 'image' : isAud ? 'audio' : 'video';
    const kindIcon = isImg ? '🖼️' : isAud ? '🎧' : '🎬';

    const uploadJobId = saveQueue.enqueue({
        type: 'upload',
        label: `${kindIcon} ${file.name}`,
        baseUrl: `/teachers/modules/${props.module.slug}/lectures/${props.lecture.slug}/media`,
        payload: { file },
        meta: {
            lectureId: props.lecture.id,
            context: `Uploading to ${props.lecture.title}`,
        },
    });

    const newAsset = {
        id: Date.now(),
        name: file.name,
        kind,
        src: URL.createObjectURL(file),
        sizeKb: Math.max(1, Math.round(file.size / 1024)),
        caption: externalMediaCaptionInput.value || file.name.replace(/\.[^/.]+$/, ''),
        uploadJobId,
    };
    uploadedAssetsTray.value.unshift(newAsset);
    insertMediaAssetOntoCanvas(newAsset);

    // Auto-save right behind the upload so the page keeps this media even if the teacher leaves before saving.
    nextTick(() => queuePageSave({ isAutoSave: true }));
};

/**
 * Point every canvas element (and tray asset) for a finished upload at the stored file URL.
 *
 * @returns {boolean} Whether the canvas changed.
 */
const applyUploadedUrlToCanvas = (uploadJobId, url) => {
    const wasClean = !isCanvasDirty();
    let changed = false;

    editorSurfaceRef.value?.querySelectorAll(`[data-pending-upload="${uploadJobId}"]`).forEach((el) => {
        el.setAttribute('src', url);
        el.removeAttribute('data-pending-upload');
        changed = true;
    });

    uploadedAssetsTray.value
        .filter((asset) => asset.uploadJobId === uploadJobId)
        .forEach((asset) => {
            if (asset.src?.startsWith('blob:')) {
                URL.revokeObjectURL(asset.src);
            }
            asset.src = url;
            asset.uploadJobId = null;
        });

    if (changed && editorSurfaceRef.value) {
        form.content = editorSurfaceRef.value.innerHTML;
    }

    // Swapping a preview URL for the stored URL is not a teacher edit, so it must not trigger a leave auto-save.
    if (changed && wasClean) {
        markCanvasClean();
    }

    return changed;
};

/**
 * Swap any already-finished uploads into the canvas, then return the page HTML with still-pending media
 * pointing at pending-upload tokens (the queue resolves those before sending the save).
 */
const buildContentHtmlForSave = () => {
    editorSurfaceRef.value?.querySelectorAll('[data-pending-upload]').forEach((el) => {
        const uploadJobId = Number(el.getAttribute('data-pending-upload'));
        const url = saveQueue.uploadedUrlFor(uploadJobId);
        if (url) {
            applyUploadedUrlToCanvas(uploadJobId, url);
        }
    });

    const template = document.createElement('template');
    template.innerHTML = currentCanvasHtml();
    template.content.querySelectorAll('[data-pending-upload]').forEach((el) => {
        const uploadJobId = Number(el.getAttribute('data-pending-upload'));
        el.setAttribute('src', saveQueue.uploadedUrlFor(uploadJobId) || saveQueue.pendingUploadToken(uploadJobId));
        el.removeAttribute('data-pending-upload');
    });

    return template.innerHTML;
};

const insertExternalMediaLinkOntoCanvas = () => {
    const url = externalMediaUrlInput.value.trim();
    if (!url) {
        return;
    }

    const caption = externalMediaCaptionInput.value.trim() || 'Embedded Media Resource';
    const isImageLink = /\.(png|jpe?g|webp|gif|svg)(\?.*)?$/i.test(url);

    if (isImageLink) {
        insertMediaAssetOntoCanvas({
            id: Date.now(),
            name: caption,
            kind: 'image',
            src: url,
            caption,
        });
    } else {
        const embedHtml = `<div class="studio-insert-block" data-studio-block="true" data-block-label="Media Link: ${caption}" style="margin: 16px 0; padding: 14px 18px; border-radius: 16px; background: #F5F3FF; border-left: 4px solid #7C3AED;"><strong>🎬 Embedded Media: ${caption}</strong><br/><a href="${url}" target="_blank" rel="noopener noreferrer" style="color: #6D28D9; font-weight: 800; text-decoration: underline;">${url}</a></div><p><br/></p>`;
        appendHtmlToPresentation(embedHtml, 'Inserted external media block into your presentation!');
    }

    externalMediaUrlInput.value = '';
    externalMediaCaptionInput.value = '';
};

/* ==========================================================================
   PAGE / BLOCK MANAGEMENT (CREATE NEW, LOAD EXISTING, UPDATE, DUPLICATE, DELETE)
   ========================================================================== */
const startNewBlankPage = () => {
    canvasDraftKey.value = newDraftKey();
    clearSelectedCanvasBlock();
    activeVocabTooltip.value = null;
    editingContentId.value = null;
    form.type = 'text';
    form.layout_style = 'interactive_story';
    form.title = '';
    form.content = '<p><br/></p>';
    form.external_url = '';
    form.duration_seconds = null;
    form.alt_text = '';
    form.caption = '';
    form.media_file = null;
    form.narration_audio_file = null;
    form.remove_narration_audio = false;
    form.vocabulary_glosses = [];
    pageNarrationPreviewUrl.value = null;
    pageNarrationDisplayName.value = '';
    syncEditorFromForm();
    nextTick(() => resetHistoryWithCurrentState());
};

const draftThumbCardRef = ref(null);

/**
 * New pages that were auto-saved when the teacher moved on. They stay in the deck as "Saving…"
 * cards until the background save lands in `existing_contents`, so a page never vanishes mid-save.
 *
 * @type {import('vue').Ref<Array<{ draftKey: string, title: string, snippet: string }>>}
 */
const backgroundDrafts = ref([]);

const backgroundDraftCards = computed(() =>
    backgroundDrafts.value
        .map((draft) => ({
            ...draft,
            job: saveQueue.state.jobs.find((job) => job.type === 'save' && job.draftKey === draft.draftKey),
        }))
        .filter(({ job }) => job && !(job.status === 'done' && (props.existing_contents || []).some((c) => c.id === job.contentId))),
);

/** Auto-save the canvas in the background before it is replaced, so typed work is never thrown away. */
const saveCanvasBeforeSwitching = () => {
    if (!isCanvasDirty()) {
        return;
    }

    if (!editingContentId.value) {
        backgroundDrafts.value.push({
            draftKey: canvasDraftKey.value,
            title: form.title,
            snippet: stripHtmlPreview(currentCanvasHtml()),
        });
    }

    queuePageSave({ isAutoSave: true });
};

/** "+ New Page": save the current page, then open a blank canvas and show its card in the deck immediately. */
const addNewBlankPage = () => {
    saveCanvasBeforeSwitching();
    startNewBlankPage();
    nextTick(() => draftThumbCardRef.value?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
};

/** Clicking a page card: keep any unsaved work on the current page, then open the chosen one. */
const openSavedPage = (block) => {
    if (block.id === editingContentId.value) {
        return;
    }

    saveCanvasBeforeSwitching();
    loadExistingPageOntoCanvas(block);
};

const loadExistingPageOntoCanvas = (block) => {
    canvasDraftKey.value = newDraftKey();
    clearSelectedCanvasBlock();
    activeVocabTooltip.value = null;
    editingContentId.value = block.id;
    form.type = 'text';
    form.layout_style = block.layout_style || 'interactive_story';
    form.paper_theme = block.paper_theme || DEFAULT_PAPER_THEME_ID;
    form.title = block.title || '';

    if (block.type === 'image' && block.media_url && !block.content) {
        form.content = `<figure class="studio-insert-block" data-studio-block="true" data-block-label="Illustration" style="margin: 18px auto; text-align: center; max-width: 520px; padding: 14px; border-radius: 18px; background: #F8FAFC; border: 2px solid #E2E8F0;"><img src="${block.media_url}" alt="${block.title || 'Illustration'}" style="max-width: 100%; max-height: 360px; border-radius: 12px;" /><figcaption style="font-size: 0.84rem; color: #475569; font-weight: 700; margin-top: 8px;">🖼️ ${block.caption || block.title || 'Illustration'}</figcaption></figure><p><br/></p>`;
    } else {
        form.content = block.content || '<p><br/></p>';
    }

    form.external_url = block.external_url || '';
    form.duration_seconds = block.duration_seconds ?? null;
    form.alt_text = block.alt_text || '';
    form.caption = block.caption || '';
    form.media_file = null;
    form.narration_audio_file = null;
    form.remove_narration_audio = false;
    form.vocabulary_glosses = block.vocabulary_glosses || [];
    pageNarrationPreviewUrl.value = block.narration_audio_url || null;
    pageNarrationDisplayName.value = block.narration_audio_name || (block.narration_audio_url ? 'Saved Page Narration Audio' : '');
    syncEditorFromForm();
    nextTick(() => resetHistoryWithCurrentState());
};

const duplicateBlockAsNewDraft = (block) => {
    loadExistingPageOntoCanvas(block);
    editingContentId.value = null;
    form.title = block.title ? `${block.title} (Copy)` : 'Copied Story Page';
    triggerToast('Duplicated as a new draft page!');
};

const deleteExistingBlock = (block) => {
    router.delete(
        `/teachers/modules/${props.module.slug}/lectures/${props.lecture.slug}/contents/${block.id}`,
        {
            preserveScroll: true,
            onSuccess: (page) => {
                if (editingContentId.value === block.id) {
                    const remaining = (page?.props?.existing_contents || props.existing_contents || []).filter((c) => c.id !== block.id);
                    if (remaining.length > 0) {
                        loadExistingPageOntoCanvas(remaining[0]);
                    } else {
                        startNewBlankPage();
                    }
                }
                triggerToast('Page block removed from lecture.');
            },
        },
    );
};

const submitContent = () => {
    queuePageSave();
};

/**
 * Snapshot the canvas and queue it for a background save.
 *
 * @param {{ isAutoSave?: boolean }} options
 */
const queuePageSave = ({ isAutoSave = false } = {}) => {
    clearSelectedCanvasBlock();
    const contentHtmlForSave = buildContentHtmlForSave();
    if (editorSurfaceRef.value) {
        form.content = editorSurfaceRef.value.innerHTML.replace(/is-selected-studio-block/g, '');
        syncGlossesListFromDom();
    }
    form.type = 'text';
    form.clearErrors();

    const snapshot = { ...form.data(), content: contentHtmlForSave };
    const canvasHtmlAtSave = form.content;

    saveQueue.enqueue({
        label: `${isAutoSave ? 'Auto-save: ' : ''}${snapshot.title || 'Untitled Page'}`,
        baseUrl: `/teachers/modules/${props.module.slug}/lectures/${props.lecture.slug}/contents`,
        contentId: editingContentId.value,
        draftKey: canvasDraftKey.value,
        payload: {
            ...snapshot,
            vocabulary_glosses: JSON.stringify(snapshot.vocabulary_glosses || []),
        },
        meta: {
            lectureId: props.lecture.id,
            context: props.lecture.title,
            contentSnapshot: canvasHtmlAtSave,
        },
    });

    markCanvasClean();
};

/**
 * Reacts to background saves finishing while this page is open: refreshes the page list,
 * shows a toast, and links a freshly created page to the canvas draft it came from.
 */
const handleSaveJobFinished = (job, result) => {
    if (job.meta.lectureId !== props.lecture.id) {
        return;
    }

    if (job.type === 'upload') {
        if (result.ok) {
            applyUploadedUrlToCanvas(job.id, result.url);
        }

        return;
    }

    const isCurrentDraft = canvasDraftKey.value === job.draftKey;

    if (!result.ok) {
        if (isCurrentDraft && result.errors) {
            form.setError(result.errors);
        }

        return;
    }

    triggerToast(result.wasCreate ? 'New page block published to lecture!' : 'Saved updates to page block!');

    const shouldLinkDraft = result.wasCreate && isCurrentDraft && !editingContentId.value;
    if (shouldLinkDraft) {
        editingContentId.value = result.id;
    }

    router.reload({
        only: ['existing_contents'],
        preserveScroll: true,
        onSuccess: () => {
            if (saveQueue.hasPendingForDraft(job.draftKey)) {
                return;
            }

            const savedBlock = (props.existing_contents || []).find((block) => block.id === result.id);
            if (!savedBlock) {
                return;
            }

            if (shouldLinkDraft && canvasDraftKey.value === job.draftKey) {
                const currentHtml = editorSurfaceRef.value ? editorSurfaceRef.value.innerHTML : form.content;
                if (currentHtml === job.meta.contentSnapshot) {
                    loadExistingPageOntoCanvas(savedBlock);
                }

                return;
            }

            // Teacher left and came back while this save was still queued: the canvas shows the stale copy,
            // so swap in the saved version as long as they have not started editing it.
            if (!isCurrentDraft && editingContentId.value === result.id && !isCanvasDirty()) {
                loadExistingPageOntoCanvas(savedBlock);
                triggerToast('Page refreshed with your latest saved changes!');
            }
        },
    });
};

const toggleFocusMode = () => {
    isFocusMode.value = !isFocusMode.value;
    if (isFocusMode.value) {
        isLeftDrawerOpen.value = false;
        isRightDeckOpen.value = false;
    } else {
        isLeftDrawerOpen.value = true;
        isRightDeckOpen.value = true;
    }
};

const triggerToast = (msg) => {
    saveSuccessToast.value = msg;
    window.setTimeout(() => {
        if (saveSuccessToast.value === msg) {
            saveSuccessToast.value = '';
        }
    }, 2800);
};

const goToModule = () => {
    router.visit(`/teachers/modules/${props.module.slug}`);
};

const stripHtmlPreview = (html) => {
    if (!html) {
        return '';
    }

    return html
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

/* ==========================================================================
   INSERTED BLOCK PROTECTION — blocks are removed only via the "✕ Remove" button
   ========================================================================== */
const STUDIO_BLOCK_SELECTOR = '[data-studio-block="true"], .studio-insert-block';
const BLOCK_MEDIA_SELECTOR = 'img, audio, video, hr';

const isStudioBlock = (node) => node?.nodeType === Node.ELEMENT_NODE && node.matches(STUDIO_BLOCK_SELECTOR);

/** The inserted block containing `node`, if any. */
const closestStudioBlock = (node) => {
    const el = node?.nodeType === Node.ELEMENT_NODE ? node : node?.parentElement;
    const block = el?.closest(STUDIO_BLOCK_SELECTOR);

    return block && editorSurfaceRef.value?.contains(block) && block !== editorSurfaceRef.value ? block : null;
};

/** Whether nothing readable sits between the start (or end) of `container` and the caret. */
const isCaretAtEdgeOf = (container, range, edge) => {
    const probe = document.createRange();
    probe.selectNodeContents(container);
    if (edge === 'start') {
        probe.setEnd(range.startContainer, range.startOffset);
    } else {
        probe.setStart(range.endContainer, range.endOffset);
    }

    return probe.toString().length === 0 && !probe.cloneContents().querySelector(BLOCK_MEDIA_SELECTOR);
};

/** The direct child of the editor surface holding the caret, plus its neighbours. */
const topLevelContext = (range) => {
    const surface = editorSurfaceRef.value;
    if (range.startContainer === surface) {
        return {
            line: null,
            previous: surface.childNodes[range.startOffset - 1] ?? null,
            next: surface.childNodes[range.startOffset] ?? null,
        };
    }

    let line = range.startContainer;
    while (line && line.parentNode !== surface) {
        line = line.parentNode;
    }

    return { line, previous: line?.previousSibling ?? null, next: line?.nextSibling ?? null };
};

const placeCaretAtEndOf = (el) => {
    const range = document.createRange();
    range.selectNodeContents(el);
    range.collapse(false);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
};

const isLineEmpty = (el) => el.textContent.length === 0 && !el.querySelector?.(BLOCK_MEDIA_SELECTOR);

const commitManualEdit = () => {
    form.content = editorSurfaceRef.value.innerHTML;
    syncGlossesListFromDom();
    pushHistorySnapshot();
};

/**
 * Stop the browser from deleting an inserted block while the teacher erases text.
 * Text inside a block can still be edited and fully cleared; the block box itself stays.
 */
const protectStudioBlocksOnInput = (event) => {
    if (!editorSurfaceRef.value || event.inputType.startsWith('format') || event.inputType.startsWith('history')) {
        return;
    }

    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);
    const isDelete = event.inputType.startsWith('delete');
    const isBackward = event.inputType.includes('Backward');

    if (!range.collapsed) {
        const startBlock = closestStudioBlock(range.startContainer);
        const endBlock = closestStudioBlock(range.endContainer);
        const spansBlocks = startBlock !== endBlock
            || Array.from(editorSurfaceRef.value.querySelectorAll(STUDIO_BLOCK_SELECTOR)).some(
                (block) => block !== startBlock && range.intersectsNode(block),
            );

        if (spansBlocks) {
            event.preventDefault();
            triggerToast('Use the "✕ Remove" button to delete an inserted block');

            return;
        }

        // Clearing every word inside a block: keep the (now empty) block on the page.
        if (startBlock && isDelete) {
            event.preventDefault();
            range.deleteContents();
            if (isLineEmpty(startBlock)) {
                startBlock.innerHTML = '<br>';
                placeCaretAtEndOf(startBlock);
            }
            commitManualEdit();
        }

        return;
    }

    if (!isDelete) {
        return;
    }

    // Caret inside a block, at its first/last character: the browser would merge or drop the block.
    const caretBlock = closestStudioBlock(range.startContainer);
    if (caretBlock) {
        if (isCaretAtEdgeOf(caretBlock, range, isBackward ? 'start' : 'end')) {
            event.preventDefault();
        }

        return;
    }

    // Caret in a line right after (or before) a block: never let the block be swallowed.
    const { line, previous, next } = topLevelContext(range);
    const neighbour = isBackward ? previous : next;
    if (!isStudioBlock(neighbour) || (line && !isCaretAtEdgeOf(line, range, isBackward ? 'start' : 'end'))) {
        return;
    }

    event.preventDefault();
    if (line && isLineEmpty(line)) {
        line.remove();
        if (isBackward) {
            placeCaretAtEndOf(neighbour);
        }
        commitManualEdit();
    }
};

const handleGlobalKeydown = (e) => {
    const isMod = e.ctrlKey || e.metaKey;
    if (isMod && e.key.toLowerCase() === 'z') {
        if (e.shiftKey) {
            e.preventDefault();
            handleRedo();
        } else {
            e.preventDefault();
            handleUndo();
        }

        return;
    }

    if (isMod && e.key.toLowerCase() === 'y') {
        e.preventDefault();
        handleRedo();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
    unsubscribeSaveQueue = saveQueue.onJobFinished(handleSaveJobFinished);

    let targetBlock = null;
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const targetId = Number(urlParams.get('content_id'));
        if (targetId && props.existing_contents?.length) {
            targetBlock = props.existing_contents.find((c) => c.id === targetId);
        }
    } catch {
        // ignore search param error
    }

    if (!targetBlock && props.existing_contents?.length > 0) {
        targetBlock = props.existing_contents[0];
    }

    if (targetBlock) {
        loadExistingPageOntoCanvas(targetBlock);
    } else {
        if (props.lecture?.title || props.lecture?.body || props.lecture?.description) {
            form.title = props.lecture.title || '';
            const rawBody = props.lecture.body || props.lecture.description || '';
            const bodyHtml = rawBody
                ? (rawBody.startsWith('<') ? rawBody : `<p>${rawBody.replace(/\n/g, '<br/>')}</p>`)
                : '<p><br/></p>';
            form.content = bodyHtml;
        }
        syncEditorFromForm();
        nextTick(() => resetHistoryWithCurrentState());
    }
});

onBeforeUnmount(() => {
    // Leaving the studio with unsaved edits: hand them to the background queue instead of losing them.
    if (isCanvasDirty()) {
        queuePageSave({ isAutoSave: true });
    }

    window.removeEventListener('keydown', handleGlobalKeydown);
    unsubscribeSaveQueue?.();
});
</script>

<template>
    <Head :title="`Studio • ${lecture.title} • WEBREAD`" />

    <div class="word-canva-studio-shell">
        <!-- Hidden File Inputs: 1) Inline Media Uploads, 2) Dedicated Full-Page Read-Aloud Narration Audio -->
        <input
            ref="fileInputRef"
            type="file"
            class="sr-only-file-input"
            :accept="currentUploadCategoryMeta.accept"
            @change="handleFileSelect"
        />
        <input
            ref="narrationFileInputRef"
            type="file"
            class="sr-only-file-input"
            accept="audio/mpeg,audio/wav,audio/mp4,audio/ogg,audio/webm,audio/aac"
            @change="handlePageNarrationFileChange"
        />

        <!-- =========================================================================
             1. ZONE 1: FULL-WIDTH TOP STUDIO COMMAND BAR
             ========================================================================= -->
        <header class="studio-top-command-bar">
            <div class="cmd-left-group">
                <button type="button" class="studio-back-btn" @click="goToModule">
                    <svg viewBox="0 0 20 20" class="cmd-icon-svg" aria-hidden="true">
                        <path
                            d="M12.5 4.5L7 10l5.5 5.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>Journey Map</span>
                </button>

                <div class="studio-doc-identity">
                    <div class="studio-brand-row">
                        <span class="studio-badge-pill">INTERACTIVE STORY STUDIO</span>
                        <span class="studio-module-crumb">{{ module.title }}</span>
                    </div>
                    <div class="studio-lecture-row">
                        <h1 class="studio-lecture-heading">{{ lecture.title }}</h1>
                        <span
                            class="studio-page-mode-pill"
                            :class="editingContentId ? 'mode-editing' : 'mode-new'"
                        >
                            {{
                                editingContentId
                                    ? `Editing Page #${activeEditingPageIndex}`
                                    : `New Draft (Page #${activeEditingPageIndex})`
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Center: Workspace View Switcher -->
            <div class="cmd-center-switcher" role="tablist" aria-label="Studio workspace mode">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="viewMode === 'canvas'"
                    class="workspace-mode-btn"
                    :class="{ 'is-active': viewMode === 'canvas' }"
                    @click="viewMode = 'canvas'"
                >
                    <svg viewBox="0 0 18 18" class="mode-svg" aria-hidden="true">
                        <rect x="2.5" y="2.5" width="13" height="13" rx="2.5" fill="none" stroke="currentColor" stroke-width="2" />
                        <line x1="5.5" y1="6.5" x2="12.5" y2="6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <line x1="5.5" y1="10" x2="10.5" y2="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <span>Studio Canvas</span>
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="viewMode === 'preview'"
                    class="workspace-mode-btn"
                    :class="{ 'is-active': viewMode === 'preview' }"
                    @click="viewMode = 'preview'"
                >
                    <svg viewBox="0 0 18 18" class="mode-svg" aria-hidden="true">
                        <rect x="4.5" y="1.8" width="9" height="14.4" rx="2" fill="none" stroke="currentColor" stroke-width="2" />
                        <circle cx="9" cy="13.5" r="0.9" fill="currentColor" />
                    </svg>
                    <span>Student Preview</span>
                </button>

                <button
                    type="button"
                    class="workspace-mode-btn"
                    :class="{ 'is-active': isFocusMode }"
                    title="Expand canvas to 100% screen width"
                    @click="toggleFocusMode"
                >
                    <svg viewBox="0 0 18 18" class="mode-svg" aria-hidden="true">
                        <path
                            d="M3 6.5V3h3.5M15 6.5V3h-3.5M3 11.5V15h3.5M15 11.5V15h-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>{{ isFocusMode ? 'Exit Full Focus' : 'Full Screen' }}</span>
                </button>
            </div>
        </header>

        <!-- =========================================================================
             2. ZONE 2: MICROSOFT WORD + CANVA FORMATTING RIBBON BAR
             ========================================================================= -->
        <div class="studio-formatting-ribbon">
            <!-- Undo & Redo History Controls -->
            <div class="ribbon-group">
                <button
                    type="button"
                    class="ribbon-history-btn"
                    :disabled="!canUndo"
                    title="Undo (Ctrl+Z)"
                    @click="handleUndo"
                >
                    <span>↶ Undo</span>
                </button>
                <button
                    type="button"
                    class="ribbon-history-btn"
                    :disabled="!canRedo"
                    title="Redo (Ctrl+Y)"
                    @click="handleRedo"
                >
                    <span>↷ Redo</span>
                </button>
            </div>

            <span class="ribbon-divider" aria-hidden="true"></span>

            <!-- INTERACTIVE WORD MEANING BUTTON (SELECT ANY WORD TO ATTACH HOVER/CLICK MEANING!) -->
            <div class="ribbon-group">
                <button
                    type="button"
                    class="ribbon-vocab-meaning-btn"
                    title="Highlight a selected word and attach an interactive meaning for students"
                    @click="openVocabGlossCreatorFromSelection"
                >
                    <span>🔍 + Add Word Meaning</span>
                </button>
            </div>

            <span class="ribbon-divider" aria-hidden="true"></span>

            <!-- Font Family Picker -->
            <div class="ribbon-group">
                <select
                    v-model="selectedFontFamily"
                    class="ribbon-select"
                    aria-label="Font Family"
                >
                    <option v-for="f in fontFamilies" :key="f.id" :value="f.id">
                        {{ f.label }}
                    </option>
                </select>
            </div>

            <!-- Heading Style Buttons -->
            <div class="ribbon-group">
                <button
                    type="button"
                    class="ribbon-tool-btn"
                    title="Normal Paragraph"
                    @click="applyBlockHeading('P')"
                >
                    Body
                </button>
                <button
                    type="button"
                    class="ribbon-tool-btn font-bold"
                    title="Large Chapter Heading"
                    @click="applyBlockHeading('H2')"
                >
                    H1
                </button>
                <button
                    type="button"
                    class="ribbon-tool-btn font-bold"
                    title="Section Subheading"
                    @click="applyBlockHeading('H3')"
                >
                    H2
                </button>
            </div>

            <span class="ribbon-divider" aria-hidden="true"></span>

            <!-- Bold / Italic / Underline / Lists -->
            <div class="ribbon-group">
                <button
                    type="button"
                    class="ribbon-icon-btn font-bold"
                    title="Bold (Ctrl+B)"
                    @click="execFormat('bold')"
                >
                    B
                </button>
                <button
                    type="button"
                    class="ribbon-icon-btn italic"
                    title="Italic (Ctrl+I)"
                    @click="execFormat('italic')"
                >
                    I
                </button>
                <button
                    type="button"
                    class="ribbon-icon-btn underline"
                    title="Underline (Ctrl+U)"
                    @click="execFormat('underline')"
                >
                    U
                </button>
                <button
                    type="button"
                    class="ribbon-icon-btn"
                    title="Bullet List"
                    @click="execFormat('insertUnorderedList')"
                >
                    • List
                </button>
            </div>

            <span class="ribbon-divider" aria-hidden="true"></span>

            <!-- Simple Text Color Highlight Swatches -->
            <div class="ribbon-group">
                <span class="ribbon-group-lbl">HIGHLIGHT:</span>
                <div class="highlight-swatch-row">
                    <button
                        v-for="sw in highlightSwatches"
                        :key="sw.label"
                        type="button"
                        class="swatch-dot-btn"
                        :style="{ backgroundColor: sw.color }"
                        :title="`Highlight selected text: ${sw.label}`"
                        @click="applyHighlightColor(sw.color)"
                    ></button>
                </div>
            </div>

            <span class="ribbon-divider" aria-hidden="true"></span>

            <!-- Paper Canvas Theme Selector -->
            <div class="ribbon-group">
                <span class="ribbon-group-lbl">PAPER:</span>
                <div class="paper-theme-dots">
                    <button
                        v-for="pt in paperThemes"
                        :key="pt.id"
                        type="button"
                        class="paper-theme-chip"
                        :class="{ 'is-active': activePaperTheme.id === pt.id }"
                        :style="{ backgroundColor: pt.bg, borderColor: pt.border }"
                        :title="pt.label"
                        @click="form.paper_theme = pt.id"
                    >
                        {{ pt.label.split(' ')[0] }}
                    </button>
                </div>
            </div>

            <!-- Quick Selected Block Delete Pill in Ribbon when an Element/Art is Selected -->
            <template v-if="selectedCanvasBlockEl">
                <span class="ribbon-divider" aria-hidden="true"></span>
                <div class="ribbon-group">
                    <button
                        type="button"
                        class="ribbon-delete-element-btn"
                        @click="removeSelectedCanvasBlock"
                    >
                        🗑 Remove "{{ selectedCanvasBlockLabel }}"
                    </button>
                </div>
            </template>
        </div>

        <!-- =========================================================================
             3. MAIN FULL-SCREEN 3-PANE STUDIO WORKSPACE
             ========================================================================= -->
        <div class="studio-workspace-body">
            <!-- =====================================================================
                 LEFT PANE: CANVA-STYLE CREATIVE TOOLBOX RAIL & SLIDE-OUT DRAWER
                 ===================================================================== -->
            <aside class="canva-left-dock" :class="{ 'drawer-collapsed': !isLeftDrawerOpen }">
                <div class="canva-icon-rail">
                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': isLeftDrawerOpen && activeToolboxTab === 'templates' }"
                        @click="
                            activeToolboxTab = 'templates';
                            isLeftDrawerOpen = true;
                        "
                    >
                        <svg viewBox="0 0 22 22" class="rail-svg" aria-hidden="true">
                            <rect x="3" y="3" width="16" height="6" rx="1.5" fill="none" stroke="currentColor" stroke-width="2" />
                            <rect x="3" y="12" width="7" height="7" rx="1.5" fill="none" stroke="currentColor" stroke-width="2" />
                            <rect x="12" y="12" width="7" height="7" rx="1.5" fill="none" stroke="currentColor" stroke-width="2" />
                        </svg>
                        <span>Layouts</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': isLeftDrawerOpen && activeToolboxTab === 'vocab' }"
                        @click="
                            activeToolboxTab = 'vocab';
                            isLeftDrawerOpen = true;
                        "
                    >
                        <svg viewBox="0 0 22 22" class="rail-svg" aria-hidden="true">
                            <circle cx="10" cy="10" r="6" fill="none" stroke="currentColor" stroke-width="2" />
                            <line x1="14.5" y1="14.5" x2="19" y2="19" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                        </svg>
                        <span>Meanings</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': isLeftDrawerOpen && activeToolboxTab === 'elements' }"
                        @click="
                            activeToolboxTab = 'elements';
                            isLeftDrawerOpen = true;
                        "
                    >
                        <svg viewBox="0 0 22 22" class="rail-svg" aria-hidden="true">
                            <polygon points="11,2.5 13.8,8.2 20,9.1 15.5,13.5 16.6,19.6 11,16.7 5.4,19.6 6.5,13.5 2,9.1 8.2,8.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                        </svg>
                        <span>Elements</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': isLeftDrawerOpen && activeToolboxTab === 'media' }"
                        @click="
                            activeToolboxTab = 'media';
                            isLeftDrawerOpen = true;
                        "
                    >
                        <svg viewBox="0 0 22 22" class="rail-svg" aria-hidden="true">
                            <path d="M4 15v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2M11 3v11M7 7l4-4 4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>Uploads</span>
                    </button>

                    <button
                        type="button"
                        class="rail-tab-btn"
                        :class="{ 'is-active': isLeftDrawerOpen && activeToolboxTab === 'stickers' }"
                        @click="
                            activeToolboxTab = 'stickers';
                            isLeftDrawerOpen = true;
                        "
                    >
                        <svg viewBox="0 0 22 22" class="rail-svg" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" fill="none" stroke="currentColor" stroke-width="2" />
                            <circle cx="8.5" cy="9.5" r="1" fill="currentColor" />
                            <circle cx="13.5" cy="9.5" r="1" fill="currentColor" />
                            <path d="M8 13.5c.8 1.3 2 2 3 2s2.2-.7 3-2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                        <span>Story Art</span>
                    </button>

                    <button
                        type="button"
                        class="rail-collapse-btn"
                        :title="isLeftDrawerOpen ? 'Collapse Toolbox' : 'Expand Toolbox'"
                        @click="isLeftDrawerOpen = !isLeftDrawerOpen"
                    >
                        {{ isLeftDrawerOpen ? '«' : '»' }}
                    </button>
                </div>

                <!-- Expandable Canva Shelf Drawer -->
                <div v-if="isLeftDrawerOpen" class="canva-shelf-drawer">
                    <!-- TAB 1: 1-CLICK STORY PAGE TEMPLATES -->
                    <div v-if="activeToolboxTab === 'templates'" class="shelf-section">
                        <div class="shelf-header">
                            <h3>Story Layouts</h3>
                            <p>Includes Interactive Story Layout with hover/click word meanings!</p>
                        </div>

                        <div class="shelf-cards-list">
                            <!-- Featured AI Story Writer Card -->
                            <div class="ai-story-writer-card">
                                <div class="tpl-top-row">
                                    <span class="ai-tag-pill">✨ GEMINI AI AUTHOR</span>
                                </div>
                                <strong class="tpl-name">Auto-Write Story & Vocab</strong>
                                <p class="tpl-desc">Give Gemini an idea, and it drafts a complete adventure chapter with interactive vocabulary words!</p>
                                <button
                                    type="button"
                                    class="btn-open-ai-story"
                                    @click="showAiStoryModal = true"
                                >
                                    ✨ Draft with Gemini
                                </button>
                            </div>

                            <div
                                v-for="tpl in canvaTemplates"
                                :key="tpl.id"
                                class="canva-template-card"
                                :class="{ 'featured-story-tpl': tpl.id === 'interactive-story-gloss' }"
                            >
                                <div class="tpl-top-row">
                                    <span class="tpl-tag-pill">{{ tpl.tag }}</span>
                                </div>
                                <strong class="tpl-name">{{ tpl.name }}</strong>
                                <p class="tpl-desc">{{ tpl.desc }}</p>
                                <div class="tpl-action-btns">
                                    <button
                                        type="button"
                                        class="tpl-btn-append"
                                        @click="applyTemplateToCanvas(tpl, 'append')"
                                    >
                                        + Add to Page
                                    </button>
                                    <button
                                        type="button"
                                        class="tpl-btn-replace"
                                        @click="applyTemplateToCanvas(tpl, 'replace')"
                                    >
                                        Replace Page
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: INTERACTIVE WORD MEANINGS (VOCABULARY GLOSSARY MANAGER) -->
                    <div v-else-if="activeToolboxTab === 'vocab'" class="shelf-section">
                        <div class="shelf-header">
                            <h3>Interactive Word Meanings</h3>
                            <p>
                                Highlight any word in your story on the canvas and click below so students can hover or tap it to reveal its meaning!
                            </p>
                        </div>

                        <button
                            type="button"
                            class="btn-open-vocab-creator"
                            @click="openVocabGlossCreatorFromSelection"
                        >
                            🔍 + Highlight Word & Add Meaning
                        </button>

                        <div class="vocab-gloss-list-wrap">
                            <span class="shelf-mini-lbl">
                                Active Words on This Page ({{ form.vocabulary_glosses?.length || 0 }}):
                            </span>

                            <div
                                v-if="form.vocabulary_glosses && form.vocabulary_glosses.length > 0"
                                class="vocab-gloss-cards"
                            >
                                <div
                                    v-for="(item, gIdx) in form.vocabulary_glosses"
                                    :key="`${item.word}-${gIdx}`"
                                    class="vocab-gloss-item-card"
                                >
                                    <div class="vocab-item-top">
                                        <strong class="vocab-item-word">{{ item.word }}</strong>
                                        <button
                                            type="button"
                                            class="vocab-item-del"
                                            title="Remove interactive meaning from this word"
                                            @click="removeVocabGlossWord(item.word)"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                    <span v-if="item.pronunciation" class="vocab-item-pron">
                                        {{ item.pronunciation }}
                                    </span>
                                    <p class="vocab-item-meaning">{{ item.meaning }}</p>
                                </div>
                            </div>

                            <div v-else class="vocab-empty-box">
                                Select a word on the page and click <strong>"+ Highlight Word & Add Meaning"</strong> to create interactive hover definitions.
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: INSERTABLE CANVA STORY CALLOUT ELEMENTS -->
                    <div v-else-if="activeToolboxTab === 'elements'" class="shelf-section">
                        <div class="shelf-header">
                            <h3>Story Callout Elements</h3>
                            <p>Click to insert onto your page. Click any element on the page to remove or reorder it!</p>
                        </div>

                        <div class="shelf-cards-list">
                            <button
                                v-for="el in canvaElements"
                                :key="el.id"
                                type="button"
                                class="canva-element-card"
                                :style="{ borderLeftColor: el.color, backgroundColor: el.bg }"
                                @click="insertCanvaElement(el)"
                            >
                                <span class="el-badge" :style="{ color: el.color }">{{ el.badge }}</span>
                                <strong class="el-title">{{ el.label }}</strong>
                                <span class="el-plus">+ Insert</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: CANVA UPLOADS (INSERTS IMAGES/MEDIA DIRECTLY INTO PRESENTATION WITHOUT HIDING TEXT!) -->
                    <div v-else-if="activeToolboxTab === 'media'" class="shelf-section">
                        <div class="shelf-header">
                            <h3>Insert Media into Page</h3>
                            <p>Add images, sound clips, or videos right into your presentation alongside your text</p>
                        </div>

                        <div class="upload-caption-field">
                            <label class="shelf-mini-lbl">Optional Caption for Next Media:</label>
                            <input
                                v-model="externalMediaCaptionInput"
                                type="text"
                                class="shelf-mini-input"
                                placeholder="e.g., Map of Whispering Woods"
                            />
                        </div>

                        <div class="shelf-media-types">
                            <button
                                v-for="cat in uploadMediaCategories"
                                :key="cat.id"
                                type="button"
                                class="media-switch-card"
                                @click="triggerUploadBrowser(cat.id)"
                            >
                                <span
                                    class="media-badge-dot"
                                    :style="{ backgroundColor: cat.badgeColor }"
                                ></span>
                                <div class="media-switch-copy">
                                    <strong>+ Upload {{ cat.label }}</strong>
                                    <span>{{ cat.desc }}</span>
                                </div>
                            </button>
                        </div>

                        <div
                            class="shelf-dropzone-box"
                            :class="{ 'is-dragging': isDraggingFile }"
                            @dragover.prevent="isDraggingFile = true"
                            @dragleave.prevent="isDraggingFile = false"
                            @drop.prevent="handleFileDrop"
                            @click="triggerUploadBrowser('image')"
                        >
                            <strong>📂 Drag & Drop Image / Audio / Video</strong>
                            <span>Automatically inserts onto your presentation canvas</span>
                        </div>

                        <div class="shelf-external-box">
                            <label class="shelf-mini-lbl">Or Insert by Image / Video URL:</label>
                            <div class="external-url-row">
                                <input
                                    v-model="externalMediaUrlInput"
                                    type="url"
                                    class="shelf-mini-input"
                                    placeholder="https://..."
                                />
                                <button
                                    type="button"
                                    class="btn-insert-url"
                                    @click="insertExternalMediaLinkOntoCanvas"
                                >
                                    Add
                                </button>
                            </div>
                        </div>

                        <div v-if="uploadedAssetsTray.length > 0" class="uploaded-tray-section">
                            <span class="shelf-mini-lbl">Uploaded in this Session (Click to Insert):</span>
                            <div class="uploaded-tray-list">
                                <div
                                    v-for="asset in uploadedAssetsTray"
                                    :key="asset.id"
                                    class="tray-asset-card"
                                >
                                    <img
                                        v-if="asset.kind === 'image'"
                                        :src="asset.src"
                                        :alt="asset.name"
                                        class="tray-asset-thumb"
                                    />
                                    <div class="tray-asset-meta">
                                        <strong>{{ asset.name }}</strong>
                                        <button
                                            type="button"
                                            class="tray-reinsert-btn"
                                            @click="insertMediaAssetOntoCanvas(asset)"
                                        >
                                            + Insert on Page
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: BUILT-IN 3D STORYBOOK STICKERS -->
                    <div v-else-if="activeToolboxTab === 'stickers'" class="shelf-section">
                        <div class="shelf-header">
                            <h3>3D Storybook Art</h3>
                            <p>Click to embed into your story page. Click any sticker on the canvas to remove it!</p>
                        </div>

                        <div class="stickers-grid">
                            <button
                                v-for="st in storybookStickers"
                                :key="st.id"
                                type="button"
                                class="sticker-art-card"
                                @click="insertStoryStickerIntoDoc(st)"
                            >
                                <img :src="st.src" :alt="st.label" class="sticker-thumb-img" />
                                <span class="sticker-lbl">{{ st.label }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- =====================================================================
                 CENTER PANE: UNIFIED INTERACTIVE WORD / CANVA ARTBOARD STAGE
                 ===================================================================== -->
            <main class="studio-center-stage">
                <div class="artboard-desk-scroller" @click.self="clearSelectedCanvasBlock">
                    <!-- MODE A: UNIFIED WORD / CANVA PRESENTATION CANVAS -->
                    <div
                        v-if="viewMode === 'canvas'"
                        class="paper-artboard-sheet"
                        :class="{ 'paper-is-lined': activePaperTheme.lined }"
                        :style="{
                            backgroundColor: activePaperTheme.bg,
                            color: activePaperTheme.text,
                            borderColor: activePaperTheme.border,
                            transform: `scale(${canvasZoom / 100})`,
                            transformOrigin: 'top center',
                        }"
                        @dragover.prevent="isDraggingFile = true"
                        @dragleave.prevent="isDraggingFile = false"
                        @drop.prevent="handleFileDrop"
                    >
                        <!-- Top Document Metadata Ribbon inside the Sheet -->
                        <div class="sheet-top-meta-bar">
                            <span class="sheet-type-chip">
                                INTERACTIVE STORY PAGE #{{ activeEditingPageIndex }}
                            </span>

                            <div class="sheet-meta-right">
                                <span class="sheet-undo-hint">
                                    ✨ {{ form.vocabulary_glosses?.length || 0 }} interactive word{{ (form.vocabulary_glosses?.length || 0) === 1 ? '' : 's' }} on page
                                </span>
                                <span class="sheet-xp-indicator">
                                    +{{ lecture.xp_reward || 150 }} XP
                                </span>
                            </div>
                        </div>

                        <!-- =========================================================
                             DEDICATED FULL-PAGE READ-ALOUD NARRATION AUDIO BAR
                             (Specifically for students who want to listen to the whole page!)
                             ========================================================= -->
                        <div class="page-narration-audio-bar">
                            <div class="narration-bar-left">
                                <span class="narration-icon-badge" aria-hidden="true">🔊</span>
                                <div class="narration-bar-copy">
                                    <strong>Full-Page Read-Aloud Narration Audio</strong>
                                    <span>
                                        For students who prefer listening to the reading of this entire page instead of reading silently
                                    </span>
                                </div>
                            </div>

                            <div class="narration-bar-controls">
                                <template v-if="pageNarrationPreviewUrl">
                                    <audio
                                        controls
                                        :src="pageNarrationPreviewUrl"
                                        class="narration-mini-player"
                                    ></audio>
                                    <button
                                        type="button"
                                        class="btn-narration-replace"
                                        @click="triggerPageNarrationUpload"
                                    >
                                        Replace Audio
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-narration-remove"
                                        @click="removePageNarrationAudio"
                                    >
                                        ✕
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        class="btn-upload-page-narration"
                                        @click="triggerPageNarrationUpload"
                                    >
                                        🎙️ Upload Page Read-Aloud Audio
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Direct On-Canvas Title Heading Input -->
                        <div class="sheet-title-field-wrap">
                            <input
                                v-model="form.title"
                                type="text"
                                class="sheet-heading-input"
                                placeholder="Untitled Story Chapter or Presentation Heading..."
                                @input="scheduleHistorySnapshot"
                            />
                            <p v-if="form.errors.title" class="canvas-error-msg">{{ form.errors.title }}</p>
                        </div>

                        <!-- LIVE INTERACTIVE WORD MEANING POPOVER BANNER (WHEN HOVERING/CLICKING A HIGHLIGHTED WORD) -->
                        <Transition name="studio-toast-pop">
                            <div v-if="activeVocabTooltip" class="live-vocab-meaning-banner">
                                <div class="vocab-banner-main">
                                    <span class="vocab-pill-tag">📖 WORD MEANING</span>
                                    <strong class="vocab-banner-word">{{ activeVocabTooltip.word }}</strong>
                                    <em v-if="activeVocabTooltip.pronunciation" class="vocab-banner-pron">
                                        ({{ activeVocabTooltip.pronunciation }})
                                    </em>
                                    <span class="vocab-banner-def">— {{ activeVocabTooltip.meaning }}</span>
                                </div>
                                <div class="vocab-banner-actions">
                                    <button
                                        type="button"
                                        class="vocab-banner-remove-btn"
                                        @click="removeVocabGlossWord(activeVocabTooltip.word)"
                                    >
                                        Remove Highlight
                                    </button>
                                    <button
                                        type="button"
                                        class="vocab-banner-close-btn"
                                        @click="activeVocabTooltip = null"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </Transition>

                        <!-- UNIFIED WYSIWYG PRESENTATION SURFACE -->
                        <div class="sheet-document-body-wrap">
                            <!-- FLOATING CANVA ELEMENT TOOLBAR WHEN AN INSERTED BLOCK / STICKER / IMAGE IS SELECTED -->
                            <Transition name="studio-toast-pop">
                                <div
                                    v-if="selectedCanvasBlockEl"
                                    class="floating-block-action-bar"
                                    :style="{ top: `${selectedBlockTopPx}px` }"
                                    @click.stop
                                >
                                    <span class="block-sel-name">{{ selectedCanvasBlockLabel }}</span>
                                    <button
                                        type="button"
                                        class="block-act-pill"
                                        title="Move element up"
                                        @click="moveSelectedCanvasBlock('up')"
                                    >
                                        ↑ Up
                                    </button>
                                    <button
                                        type="button"
                                        class="block-act-pill"
                                        title="Move element down"
                                        @click="moveSelectedCanvasBlock('down')"
                                    >
                                        ↓ Down
                                    </button>
                                    <button
                                        type="button"
                                        class="block-act-pill"
                                        title="Duplicate element"
                                        @click="duplicateSelectedCanvasBlock"
                                    >
                                        ⧉ Duplicate
                                    </button>
                                    <button
                                        type="button"
                                        class="block-act-pill pill-danger"
                                        title="Remove this element from page"
                                        @click="removeSelectedCanvasBlock"
                                    >
                                        ✕ Remove
                                    </button>
                                </div>
                            </Transition>

                            <div
                                ref="editorSurfaceRef"
                                class="wysiwyg-story-surface"
                                :style="{ fontFamily: selectedFontFamily }"
                                contenteditable="true"
                                role="textbox"
                                aria-multiline="true"
                                aria-label="Story presentation content editor"
                                data-placeholder="Click here and start writing your story passage, select any word and click '+ Add Word Meaning' in the top bar, or insert layouts & images from the left panel..."
                                @beforeinput="protectStudioBlocksOnInput"
                                @input="onEditorInput"
                                @click="handleCanvasClick"
                                @mouseover="handleVocabHoverOrClick"
                            ></div>
                            <p v-if="form.errors.content" class="canvas-error-msg">{{ form.errors.content }}</p>
                        </div>
                    </div>

                    <!-- MODE B: LIVE STUDENT MOBILE 3D CLAY PREVIEW (WITH FULL-PAGE AUDIO & HOVER/TAP WORD MEANINGS) -->
                    <div v-else class="student-device-preview-frame">
                        <div class="device-notch-bar" aria-hidden="true">
                            <span class="device-speaker"></span>
                        </div>

                        <div
                            class="device-screen-body"
                            :class="{ 'paper-is-lined': activePaperTheme.lined }"
                            :style="{ backgroundColor: activePaperTheme.bg, color: activePaperTheme.text }"
                        >
                            <span class="preview-kicker-pill">
                                LIVE STUDENT VIEW • PAGE #{{ activeEditingPageIndex }}
                            </span>
                            <h2 class="preview-story-title">
                                {{ form.title || `Story Scroll Part ${activeEditingPageIndex}` }}
                            </h2>

                            <!-- Dedicated Student Full-Page Read-Aloud Audio Player -->
                            <div v-if="pageNarrationPreviewUrl" class="student-preview-narration-card">
                                <div class="student-narration-head">
                                    <span>🔊 Listen to Full Page Reading</span>
                                    <small>Press play to hear this story page read aloud!</small>
                                </div>
                                <audio
                                    controls
                                    :src="pageNarrationPreviewUrl"
                                    class="student-narration-player"
                                ></audio>
                            </div>

                            <!-- Interactive Word Meaning Popover Card in Student Preview -->
                            <Transition name="studio-toast-pop">
                                <div v-if="activeVocabTooltip" class="student-vocab-popover-card">
                                    <div class="student-vocab-top">
                                        <span class="student-vocab-badge">✨ WORD MEANING</span>
                                        <button
                                            type="button"
                                            class="student-vocab-close"
                                            @click="activeVocabTooltip = null"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                    <strong class="student-vocab-word">{{ activeVocabTooltip.word }}</strong>
                                    <span v-if="activeVocabTooltip.pronunciation" class="student-vocab-pron">
                                        {{ activeVocabTooltip.pronunciation }}
                                    </span>
                                    <p class="student-vocab-def">{{ activeVocabTooltip.meaning }}</p>
                                </div>
                            </Transition>

                            <div
                                class="preview-rich-story-card"
                                @click="handleVocabHoverOrClick"
                                @mouseover="handleVocabHoverOrClick"
                                v-html="form.content || '<p>No story content written yet.</p>'"
                            ></div>

                            <div class="preview-complete-btn-mock">
                                COMPLETE QUEST & CLAIM +{{ lecture.xp_reward || 150 }} XP ✓
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Artboard Status & Zoom Bar -->
                <footer class="studio-bottom-status-bar">
                    <div class="status-left-metrics">
                        <span class="status-metric-pill">
                            <strong>{{ wordCount }}</strong> {{ wordCount === 1 ? 'word' : 'words' }}
                        </span>
                        <span class="status-metric-pill">
                            <strong>{{ form.vocabulary_glosses?.length || 0 }}</strong> interactive word meanings
                        </span>
                        <span class="status-metric-pill">
                            ⏱ ~{{ estimatedReadingMinutes }} min student read
                        </span>
                        <span class="status-metric-pill">
                            History: {{ historyIndex + 1 }}/{{ historyStack.length }}
                        </span>
                    </div>

                    <div class="status-right-zoom">
                        <button
                            type="button"
                            class="zoom-step-btn"
                            @click="canvasZoom = Math.max(75, canvasZoom - 10)"
                        >
                            −
                        </button>
                        <span class="zoom-readout">{{ canvasZoom }}%</span>
                        <button
                            type="button"
                            class="zoom-step-btn"
                            @click="canvasZoom = Math.min(135, canvasZoom + 10)"
                        >
                            +
                        </button>
                        <button
                            type="button"
                            class="zoom-reset-btn"
                            @click="canvasZoom = 100"
                        >
                            100%
                        </button>
                    </div>
                </footer>
            </main>

            <!-- =====================================================================
                 RIGHT PANE: CANVA / POWERPOINT STORY PAGES & LAYERS NAVIGATOR
                 ===================================================================== -->
            <aside class="studio-right-pages-dock" :class="{ 'deck-collapsed': !isRightDeckOpen }">
                <div class="pages-dock-header">
                    <button
                        type="button"
                        class="deck-toggle-btn"
                        :title="isRightDeckOpen ? 'Collapse Pages Deck' : 'Expand Pages Deck'"
                        @click="isRightDeckOpen = !isRightDeckOpen"
                    >
                        {{ isRightDeckOpen ? '»' : '«' }}
                    </button>

                    <div v-if="isRightDeckOpen" class="pages-dock-title-wrap">
                        <h3>Story Pages Deck</h3>
                        <span>{{ existing_contents.length }} saved • Click any page to edit</span>
                    </div>
                </div>

                <div v-if="isRightDeckOpen" class="pages-deck-scroller">
                    <div
                        v-for="(block, idx) in existing_contents"
                        :key="block.id"
                        class="page-thumb-card"
                        :class="{ 'is-selected-page': editingContentId === block.id }"
                        role="button"
                        tabindex="0"
                        @click="openSavedPage(block)"
                    >
                        <div class="thumb-card-top">
                            <span class="thumb-page-num">PAGE #{{ idx + 1 }}</span>
                            <div class="thumb-badges-right">
                                <span v-if="block.narration_audio_url" class="thumb-audio-indicator" title="Has Page Read-Aloud Audio">
                                    🔊
                                </span>
                                <span class="thumb-type-tag">
                                    {{ (block.vocabulary_glosses?.length || 0) > 0 ? `${block.vocabulary_glosses.length} Words` : 'Saved' }}
                                </span>
                            </div>
                        </div>

                        <strong class="thumb-page-title">
                            {{ block.title || `Story Page #${idx + 1}` }}
                        </strong>

                        <p v-if="block.content" class="thumb-page-snippet">
                            {{ stripHtmlPreview(block.content) || 'Formatted story presentation' }}
                        </p>

                        <div class="thumb-card-actions" @click.stop>
                            <button
                                type="button"
                                class="thumb-act-btn"
                                title="Duplicate into new draft"
                                @click="duplicateBlockAsNewDraft(block)"
                            >
                                Duplicate
                            </button>
                            <button
                                type="button"
                                class="thumb-act-btn act-delete"
                                title="Delete this page block"
                                @click="deleteExistingBlock(block)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- New pages auto-saved when the teacher moved on, still uploading in the background -->
                    <div
                        v-for="(draft, draftIdx) in backgroundDraftCards"
                        :key="draft.draftKey"
                        class="page-thumb-card draft-thumb-card"
                    >
                        <div class="thumb-card-top">
                            <span class="thumb-page-num">PAGE #{{ existing_contents.length + draftIdx + 1 }}</span>
                            <span class="thumb-type-tag badge-draft">
                                {{ draft.job.status === 'failed' ? 'Save failed' : 'Saving…' }}
                            </span>
                        </div>

                        <strong class="thumb-page-title">
                            {{ draft.title || `Story Page #${existing_contents.length + draftIdx + 1}` }}
                        </strong>

                        <p v-if="draft.snippet" class="thumb-page-snippet">{{ draft.snippet }}</p>
                    </div>

                    <!-- The unsaved page on the canvas shows up right away, before its first save -->
                    <div
                        v-if="!editingContentId"
                        ref="draftThumbCardRef"
                        class="page-thumb-card draft-thumb-card is-selected-page"
                    >
                        <div class="thumb-card-top">
                            <span class="thumb-page-num">PAGE #{{ activeEditingPageIndex }}</span>
                            <span class="thumb-type-tag badge-draft">
                                {{ saveQueue.hasPendingForDraft(canvasDraftKey) ? 'Saving…' : 'Not saved yet' }}
                            </span>
                        </div>

                        <strong class="thumb-page-title">
                            {{ form.title || `Story Page #${activeEditingPageIndex}` }}
                        </strong>

                        <p class="thumb-page-snippet">
                            {{ stripHtmlPreview(form.content) || 'Blank page • start writing on the canvas' }}
                        </p>
                    </div>
                </div>

                <div class="pages-dock-footer" :class="{ 'is-compact': !isRightDeckOpen }">
                    <button
                        type="button"
                        class="dock-new-page-btn"
                        title="Start a fresh blank page block"
                        @click="addNewBlankPage"
                    >
                        <span v-if="isRightDeckOpen">+ New Page</span>
                        <span v-else aria-label="New Page">+</span>
                    </button>

                    <button
                        type="button"
                        class="studio-publish-btn"
                        :title="editingContentId ? `Save Page #${activeEditingPageIndex}` : 'Publish Page to Lecture'"
                        @click="submitContent"
                    >
                        <span v-if="pendingSaveCount > 0" class="studio-save-queue-badge">{{ pendingSaveCount }}</span>
                        <template v-if="isRightDeckOpen">
                            <span v-if="editingContentId">💾 Save Page #{{ activeEditingPageIndex }}</span>
                            <span v-else>✨ Publish Page to Lecture</span>
                        </template>
                        <span v-else aria-label="Save Page">💾</span>
                    </button>
                </div>
            </aside>
        </div>

        <!-- =========================================================================
             INTERACTIVE WORD MEANING CREATOR MODAL
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="studio-toast-pop">
                <div
                    v-if="showVocabGlossModal"
                    class="vocab-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="vocab-modal-title"
                    @click.self="showVocabGlossModal = false"
                >
                    <div class="vocab-modal-card">
                        <div class="vocab-modal-header">
                            <span class="vocab-modal-badge">📖 INTERACTIVE STORY VOCABULARY</span>
                            <h3 id="vocab-modal-title">Highlight Word & Attach Meaning</h3>
                            <p>
                                When students hover over or tap this word in the story, a 3D meaning card will pop up!
                            </p>
                        </div>

                        <div class="vocab-modal-body">
                            <div class="vocab-field">
                                <div class="vocab-label-row">
                                    <label>Word or Phrase to Highlight *</label>
                                    <button
                                        type="button"
                                        class="btn-ai-define-pill"
                                        :disabled="!vocabWordInput.trim() || isAiDefiningWord"
                                        @click="fetchAiWordMeaning"
                                    >
                                        <span v-if="isAiDefiningWord">⏳ Gemini Defining...</span>
                                        <span v-else>✨ AI Auto-Define</span>
                                    </button>
                                </div>
                                <input
                                    v-model="vocabWordInput"
                                    type="text"
                                    class="vocab-input"
                                    placeholder="e.g., luminous"
                                    @keydown.enter.prevent="fetchAiWordMeaning"
                                />
                                <p v-if="aiDefineError" class="ai-error-inline">⚠️ {{ aiDefineError }}</p>
                            </div>

                            <div class="vocab-field">
                                <label>Pronunciation / Part of Speech (Optional)</label>
                                <input
                                    v-model="vocabPronunciationInput"
                                    type="text"
                                    class="vocab-input"
                                    placeholder="e.g., adj. • loo-muh-nuhs"
                                />
                            </div>

                            <div class="vocab-field">
                                <label>Student-Friendly Meaning / Definition *</label>
                                <textarea
                                    v-model="vocabMeaningInput"
                                    rows="3"
                                    class="vocab-textarea"
                                    placeholder="e.g., Shining or glowing brightly, especially in the dark."
                                ></textarea>
                            </div>

                            <div class="vocab-field">
                                <label>Highlight Badge Color</label>
                                <div class="vocab-color-picker">
                                    <button
                                        v-for="sw in highlightSwatches"
                                        :key="sw.label"
                                        type="button"
                                        class="vocab-color-pill"
                                        :class="{ 'is-chosen': vocabColorInput.label === sw.label }"
                                        :style="{ backgroundColor: sw.color, borderColor: sw.border, color: sw.text }"
                                        @click="vocabColorInput = sw"
                                    >
                                        {{ sw.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="vocab-modal-footer">
                            <button
                                type="button"
                                class="btn-vocab-cancel"
                                @click="showVocabGlossModal = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="btn-vocab-save"
                                :disabled="!vocabWordInput.trim() || !vocabMeaningInput.trim()"
                                @click="applyVocabMeaningToWord"
                            >
                                ✨ Highlight & Attach Meaning
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- =========================================================================
             GEMINI AI STORY WRITER MODAL
             ========================================================================= -->
        <Teleport to="body">
            <Transition name="studio-toast-pop">
                <div
                    v-if="showAiStoryModal"
                    class="vocab-modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="ai-story-modal-title"
                    @click.self="showAiStoryModal = false"
                >
                    <div class="vocab-modal-card ai-story-modal-card">
                        <div class="vocab-modal-header">
                            <span class="vocab-modal-badge ai-badge-accent">✨ GEMINI AI STORY GENERATOR</span>
                            <h3 id="ai-story-modal-title">Draft Reading Chapter with Vocabulary</h3>
                            <p>
                                Generate a rich narrative chapter tailored for <strong>{{ module.grade_level }}</strong>, complete with interactive vocabulary words that students can hover to read!
                            </p>
                        </div>

                        <div class="vocab-modal-body">
                            <div class="vocab-field">
                                <label>Story Topic or Prompt *</label>
                                <textarea
                                    v-model="aiStoryTopic"
                                    rows="3"
                                    class="vocab-textarea"
                                    :placeholder="`e.g., A brave child finds an old brass lantern in a misty pine forest that reveals hidden folklore letters. (Defaults to '${lecture.title}')`"
                                ></textarea>
                            </div>

                            <p v-if="aiStoryError" class="ai-error-inline">⚠️ {{ aiStoryError }}</p>
                        </div>

                        <div class="vocab-modal-footer">
                            <button
                                type="button"
                                class="btn-vocab-cancel"
                                @click="showAiStoryModal = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="btn-vocab-save ai-btn-primary"
                                :disabled="isGeneratingAiStory"
                                @click="generateStoryWithAi"
                            >
                                <span v-if="isGeneratingAiStory">⏳ Writing Story & Glosses...</span>
                                <span v-else>✨ Generate & Insert into Canvas</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- =========================================================================
             FLOATING STUDIO TOAST NOTIFICATION
             ========================================================================= -->
        <Transition name="studio-toast-pop">
            <div v-if="saveSuccessToast" class="studio-floating-toast" role="status" aria-live="polite">
                <span>✨ {{ saveSuccessToast }}</span>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* ==========================================================================
   FULL-SCREEN (100VW × 100VH) MICROSOFT WORD + CANVA STORY STUDIO
   ========================================================================== */
.word-canva-studio-shell {
    --font-heading: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    width: 100vw;
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: #F1F5F9;
    color: #0F172A;
    font-family: var(--font-body);
}

.sr-only-file-input {
    display: none;
}

/* ==========================================================================
   1. ZONE 1: TOP STUDIO COMMAND BAR
   ========================================================================== */
.studio-top-command-bar {
    height: 60px;
    flex-shrink: 0;
    background: linear-gradient(90deg, #0F172A 0%, #1E1B4B 55%, #1E3A8A 100%);
    color: #FFFFFF;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 2px solid rgba(255, 255, 255, 0.12);
    z-index: 40;
}

.cmd-left-group {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.studio-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.42rem 0.8rem;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.12);
    border: 1.5px solid rgba(255, 255, 255, 0.24);
    color: #FFFFFF;
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.18s ease;
}

.studio-back-btn:hover {
    background: rgba(255, 255, 255, 0.22);
    transform: translateX(-2px);
}

.cmd-icon-svg {
    width: 15px;
    height: 15px;
}

.studio-doc-identity {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.studio-brand-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.studio-badge-pill {
    padding: 0.1rem 0.45rem;
    border-radius: 6px;
    background: linear-gradient(90deg, #38BDF8 0%, #818CF8 100%);
    color: #0F172A;
    font-family: var(--font-heading);
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.05em;
}

.studio-module-crumb {
    font-size: 0.68rem;
    font-weight: 700;
    color: #94A3B8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.studio-lecture-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.studio-lecture-heading {
    margin: 0;
    font-family: var(--font-heading);
    font-size: 0.96rem;
    font-weight: 800;
    color: #FFFFFF;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 260px;
}

.studio-page-mode-pill {
    padding: 0.12rem 0.5rem;
    border-radius: 9999px;
    font-family: var(--font-heading);
    font-size: 0.62rem;
    font-weight: 700;
    flex-shrink: 0;
}

.mode-new {
    background: rgba(16, 185, 129, 0.22);
    border: 1px solid #34D399;
    color: #A7F3D0;
}

.mode-editing {
    background: rgba(245, 158, 11, 0.25);
    border: 1px solid #FBBF24;
    color: #FDE68A;
}

.cmd-center-switcher {
    display: inline-flex;
    align-items: center;
    background: rgba(15, 23, 42, 0.55);
    padding: 0.22rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    gap: 0.2rem;
}

.workspace-mode-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.36rem 0.78rem;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: #CBD5E1;
    font-family: var(--font-heading);
    font-size: 0.74rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s ease;
}

.workspace-mode-btn.is-active {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

.mode-svg {
    width: 14px;
    height: 14px;
}

.studio-publish-btn {
    position: relative;
    padding: 0.48rem 1.1rem;
    border-radius: 13px;
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    color: #FFFFFF;
    border: 2px solid #A7F3D0;
    box-shadow: 0 3.5px 0 #065F46, 0 8px 16px rgba(16, 185, 129, 0.32);
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 800;
    cursor: pointer;
}

/* ==========================================================================
   2. ZONE 2: WORD + CANVA FORMATTING RIBBON BAR
   ========================================================================== */
.studio-formatting-ribbon {
    min-height: 48px;
    flex-shrink: 0;
    background: #FFFFFF;
    border-bottom: 1.5px solid #E2E8F0;
    padding: 0.35rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    overflow-x: auto;
    z-index: 35;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}

.ribbon-group {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    flex-shrink: 0;
}

.ribbon-group-lbl {
    font-family: var(--font-heading);
    font-size: 0.64rem;
    font-weight: 800;
    color: #64748B;
    letter-spacing: 0.04em;
    margin-right: 0.15rem;
}

.ribbon-history-btn {
    padding: 0.3rem 0.65rem;
    border-radius: 8px;
    border: 1.5px solid #CBD5E1;
    background: #F8FAFC;
    color: #0F172A;
    font-family: var(--font-heading);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
}

.ribbon-history-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.ribbon-vocab-meaning-btn {
    padding: 0.32rem 0.78rem;
    border-radius: 9px;
    background: linear-gradient(135deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    border: 1.5px solid #D97706;
    box-shadow: 0 2px 0 #B45309;
    font-family: var(--font-heading);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.ribbon-vocab-meaning-btn:hover {
    transform: translateY(-1px);
}

.ribbon-divider {
    width: 1.5px;
    height: 24px;
    background: #E2E8F0;
    flex-shrink: 0;
}

.ribbon-select {
    padding: 0.28rem 0.55rem;
    border-radius: 8px;
    border: 1.5px solid #CBD5E1;
    background: #F8FAFC;
    font-family: var(--font-body);
    font-size: 0.75rem;
    font-weight: 800;
    color: #1E293B;
    cursor: pointer;
}

.ribbon-tool-btn,
.ribbon-icon-btn {
    padding: 0.28rem 0.55rem;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #1E293B;
    font-family: var(--font-body);
    font-size: 0.74rem;
    font-weight: 800;
    cursor: pointer;
}

.highlight-swatch-row {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.swatch-dot-btn {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 1.5px solid rgba(15, 23, 42, 0.2);
    cursor: pointer;
}

.paper-theme-dots {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.paper-theme-chip {
    padding: 0.2rem 0.48rem;
    border-radius: 6px;
    border: 1.5px solid #CBD5E1;
    font-family: var(--font-heading);
    font-size: 0.66rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
}

.paper-theme-chip.is-active {
    box-shadow: 0 0 0 2px #2563EB;
}

.ribbon-delete-element-btn {
    padding: 0.28rem 0.68rem;
    border-radius: 8px;
    background: #FFE4E6;
    border: 1.5px solid #F43F5E;
    color: #BE123C;
    font-family: var(--font-heading);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

/* ==========================================================================
   3. MAIN 3-PANE WORKSPACE BODY
   ========================================================================== */
.studio-workspace-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    overflow: hidden;
}

/* ==========================================================================
   LEFT PANE: CANVA CREATIVE TOOLBOX RAIL & DRAWER
   ========================================================================== */
.canva-left-dock {
    display: flex;
    flex-shrink: 0;
    background: #FFFFFF;
    border-right: 1.5px solid #E2E8F0;
    z-index: 25;
}

.canva-icon-rail {
    width: 68px;
    background: #0F172A;
    color: #94A3B8;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.65rem 0.35rem;
    gap: 0.45rem;
    flex-shrink: 0;
}

.rail-tab-btn {
    width: 100%;
    padding: 0.52rem 0.2rem;
    border-radius: 12px;
    border: none;
    background: transparent;
    color: #94A3B8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.22rem;
    font-family: var(--font-heading);
    font-size: 0.6rem;
    font-weight: 700;
    cursor: pointer;
}

.rail-tab-btn.is-active {
    background: linear-gradient(135deg, #2563EB 0%, #4F46E5 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.rail-svg {
    width: 20px;
    height: 20px;
}

.rail-collapse-btn {
    margin-top: auto;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.06);
    color: #CBD5E1;
    font-weight: 900;
    cursor: pointer;
}

.canva-shelf-drawer {
    width: 285px;
    height: 100%;
    overflow-y: auto;
    padding: 0.9rem 0.85rem;
    background: #F8FAFC;
}

.shelf-header {
    margin-bottom: 0.85rem;
}

.shelf-header h3 {
    margin: 0 0 0.2rem;
    font-family: var(--font-heading);
    font-size: 0.95rem;
    font-weight: 800;
    color: #0F172A;
}

.shelf-header p {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748B;
    line-height: 1.35;
}

.shelf-cards-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.canva-template-card {
    text-align: left;
    padding: 0.75rem 0.8rem;
    border-radius: 14px;
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
}

.canva-template-card.featured-story-tpl {
    border: 2px solid #F59E0B;
    background: linear-gradient(180deg, #FFFFFF 0%, #FFFBEB 100%);
}

.tpl-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tpl-tag-pill {
    padding: 0.12rem 0.45rem;
    border-radius: 6px;
    background: #FEF3C7;
    color: #B45309;
    font-family: var(--font-heading);
    font-size: 0.6rem;
    font-weight: 800;
}

.tpl-name {
    font-family: var(--font-heading);
    font-size: 0.84rem;
    font-weight: 800;
    color: #0F172A;
}

.tpl-desc {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748B;
    line-height: 1.35;
}

.tpl-action-btns {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.25rem;
}

.tpl-btn-append {
    flex: 1 1 auto;
    padding: 0.34rem 0.5rem;
    border-radius: 8px;
    background: #2563EB;
    color: #FFFFFF;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 700;
    cursor: pointer;
}

.tpl-btn-replace {
    padding: 0.34rem 0.5rem;
    border-radius: 8px;
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #CBD5E1;
    font-family: var(--font-heading);
    font-size: 0.65rem;
    font-weight: 700;
    cursor: pointer;
}

/* Interactive Word Meanings Shelf Tab */
.btn-open-vocab-creator {
    width: 100%;
    padding: 0.65rem 0.8rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #FEF08A 0%, #F59E0B 100%);
    color: #78350F;
    border: 2px solid #D97706;
    box-shadow: 0 3px 0 #B45309;
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
    margin-bottom: 0.85rem;
}

.vocab-gloss-list-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.vocab-gloss-cards {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.vocab-gloss-item-card {
    background: #FFFFFF;
    border: 1.5px solid #FDE68A;
    border-radius: 12px;
    padding: 0.6rem 0.7rem;
}

.vocab-item-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vocab-item-word {
    font-family: var(--font-heading);
    font-size: 0.82rem;
    color: #92400E;
    background: #FEF3C7;
    padding: 0.1rem 0.45rem;
    border-radius: 6px;
}

.vocab-item-del {
    border: none;
    background: #FFE4E6;
    color: #E11D48;
    border-radius: 6px;
    width: 22px;
    height: 22px;
    font-size: 0.7rem;
    font-weight: 800;
    cursor: pointer;
}

.vocab-item-pron {
    display: block;
    font-size: 0.66rem;
    font-style: italic;
    color: #64748B;
    margin-top: 0.2rem;
}

.vocab-item-meaning {
    margin: 0.25rem 0 0;
    font-size: 0.72rem;
    font-weight: 700;
    color: #334155;
    line-height: 1.35;
}

.vocab-empty-box {
    background: #FFFFFF;
    border: 1.5px dashed #CBD5E1;
    border-radius: 12px;
    padding: 0.85rem;
    font-size: 0.72rem;
    color: #64748B;
    line-height: 1.4;
}

/* Insertable Elements & Uploads */
.canva-element-card {
    text-align: left;
    padding: 0.68rem 0.75rem;
    border-radius: 12px;
    border: 1.5px solid #E2E8F0;
    border-left-width: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
}

.el-badge {
    font-family: var(--font-heading);
    font-size: 0.65rem;
    font-weight: 800;
}

.el-title {
    flex: 1 1 auto;
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 800;
    color: #1E293B;
}

.el-plus {
    font-family: var(--font-heading);
    font-size: 0.66rem;
    font-weight: 800;
    color: #2563EB;
}

.upload-caption-field {
    margin-bottom: 0.65rem;
}

.shelf-mini-lbl {
    display: block;
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.25rem;
}

.shelf-mini-input {
    width: 100%;
    padding: 0.45rem 0.65rem;
    border-radius: 9px;
    border: 1.5px solid #CBD5E1;
    background: #FFFFFF;
    font-size: 0.76rem;
    font-weight: 700;
    color: #0F172A;
}

.shelf-media-types {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.media-switch-card {
    text-align: left;
    padding: 0.68rem 0.75rem;
    border-radius: 12px;
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    cursor: pointer;
}

.media-badge-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.media-switch-copy {
    display: flex;
    flex-direction: column;
}

.media-switch-copy strong {
    font-family: var(--font-heading);
    font-size: 0.8rem;
    color: #0F172A;
}

.media-switch-copy span {
    font-size: 0.68rem;
    color: #64748B;
}

.shelf-dropzone-box {
    margin-top: 0.7rem;
    padding: 0.85rem 0.65rem;
    border-radius: 12px;
    border: 2px dashed #94A3B8;
    background: #FFFFFF;
    text-align: center;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.shelf-external-box {
    margin-top: 0.75rem;
    padding-top: 0.65rem;
    border-top: 1px dashed #CBD5E1;
}

.external-url-row {
    display: flex;
    gap: 0.35rem;
}

.btn-insert-url {
    padding: 0.42rem 0.7rem;
    border-radius: 9px;
    background: #0F172A;
    color: #FFFFFF;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

.uploaded-tray-section {
    margin-top: 0.85rem;
    padding-top: 0.65rem;
    border-top: 1px dashed #CBD5E1;
}

.uploaded-tray-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.tray-asset-card {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem;
    border-radius: 10px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
}

.tray-asset-thumb {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
}

.tray-asset-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1 1 auto;
}

.tray-reinsert-btn {
    align-self: flex-start;
    margin-top: 0.18rem;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    background: #DBEAFE;
    color: #1D4ED8;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.62rem;
    font-weight: 800;
    cursor: pointer;
}

.stickers-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.55rem;
}

.sticker-art-card {
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    cursor: pointer;
}

.sticker-thumb-img {
    width: 100%;
    height: 68px;
    object-fit: contain;
}

.sticker-lbl {
    font-family: var(--font-heading);
    font-size: 0.62rem;
    font-weight: 700;
    color: #334155;
    text-align: center;
}

/* ==========================================================================
   CENTER PANE: INTERACTIVE PAPER ARTBOARD STAGE
   ========================================================================== */
.studio-center-stage {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background-color: #E2E8F0;
    background-image: radial-gradient(#94A3B8 1px, transparent 1px);
    background-size: 20px 20px;
    position: relative;
}

.artboard-desk-scroller {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 1.5rem 2rem 3.5rem;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.paper-artboard-sheet {
    width: 100%;
    max-width: 820px;
    min-height: 680px;
    border-radius: 20px;
    border: 2px solid #E2E8F0;
    box-shadow:
        0 20px 50px rgba(15, 23, 42, 0.14),
        0 4px 12px rgba(15, 23, 42, 0.06);
    padding: 1.85rem 2.5rem 2.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.95rem;
    transition: background-color 0.25s ease, border-color 0.25s ease, transform 0.2s ease;
}

.paper-artboard-sheet.paper-is-lined,
.device-screen-body.paper-is-lined {
    background-image: repeating-linear-gradient(
        transparent,
        transparent 31px,
        rgba(148, 163, 184, 0.25) 31px,
        rgba(148, 163, 184, 0.25) 32px
    );
}

.sheet-top-meta-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding-bottom: 0.55rem;
    border-bottom: 1.5px dashed rgba(148, 163, 184, 0.4);
}

.sheet-type-chip {
    padding: 0.22rem 0.65rem;
    border-radius: 9999px;
    background: #DBEAFE;
    color: #1D4ED8;
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.sheet-meta-right {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.sheet-undo-hint {
    font-size: 0.7rem;
    font-weight: 800;
    color: #059669;
}

.sheet-xp-indicator {
    font-family: var(--font-heading);
    font-size: 0.72rem;
    font-weight: 800;
    color: #D97706;
}

/* DEDICATED FULL-PAGE READ-ALOUD NARRATION BAR */
.page-narration-audio-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.65rem 0.9rem;
    border-radius: 16px;
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 2px solid #FDE68A;
    box-shadow: 0 3px 0 rgba(217, 119, 6, 0.18);
}

.narration-bar-left {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
    flex: 1 1 220px;
}

.narration-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #F59E0B;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}

.narration-bar-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.narration-bar-copy strong {
    font-family: var(--font-heading);
    font-size: 0.78rem;
    color: #78350F;
}

.narration-bar-copy span {
    font-size: 0.68rem;
    font-weight: 700;
    color: #92400E;
}

.narration-bar-controls {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.narration-mini-player {
    height: 32px;
    max-width: 210px;
}

.btn-upload-page-narration {
    padding: 0.42rem 0.85rem;
    border-radius: 10px;
    background: #D97706;
    color: #FFFFFF;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 2.5px 0 #92400E;
}

.btn-narration-replace {
    padding: 0.3rem 0.6rem;
    border-radius: 8px;
    background: #FFFFFF;
    color: #92400E;
    border: 1.5px solid #F59E0B;
    font-family: var(--font-heading);
    font-size: 0.66rem;
    font-weight: 800;
    cursor: pointer;
}

.btn-narration-remove {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: #FFE4E6;
    color: #E11D48;
    border: 1px solid #FECDD3;
    font-weight: 800;
    cursor: pointer;
}

/* LIVE INTERACTIVE WORD MEANING POPOVER BANNER */
.live-vocab-meaning-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.6rem 0.9rem;
    border-radius: 14px;
    background: #0F172A;
    color: #FFFFFF;
    border: 2px solid #FACC15;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
}

.vocab-banner-main {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
    font-size: 0.8rem;
}

.vocab-pill-tag {
    padding: 0.14rem 0.48rem;
    border-radius: 6px;
    background: #FACC15;
    color: #78350F;
    font-family: var(--font-heading);
    font-size: 0.6rem;
    font-weight: 800;
}

.vocab-banner-word {
    font-family: var(--font-heading);
    font-size: 0.92rem;
    color: #FEF08A;
}

.vocab-banner-pron {
    color: #94A3B8;
    font-size: 0.74rem;
}

.vocab-banner-def {
    color: #F8FAFC;
    font-weight: 700;
}

.vocab-banner-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.vocab-banner-remove-btn {
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    background: #E11D48;
    color: #FFFFFF;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.64rem;
    font-weight: 700;
    cursor: pointer;
}

.vocab-banner-close-btn {
    background: transparent;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    font-weight: 800;
}

/* On-Canvas Document Heading Input */
.sheet-heading-input {
    width: 100%;
    border: none;
    background: transparent;
    font-family: var(--font-heading);
    font-size: 1.75rem;
    font-weight: 800;
    color: inherit;
    outline: none;
    padding: 0.2rem 0;
    border-bottom: 2px solid transparent;
}

.sheet-heading-input:focus {
    border-bottom-color: #3B82F6;
}

/* Rich WYSIWYG Story Surface & Interactive Block Selection */
.sheet-document-body-wrap {
    position: relative;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}

.floating-block-action-bar {
    position: absolute;
    right: 0;
    z-index: 30;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.32rem 0.55rem;
    border-radius: 12px;
    background: #0F172A;
    color: #FFFFFF;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
    border: 1.5px solid #38BDF8;
}

.block-sel-name {
    font-family: var(--font-heading);
    font-size: 0.65rem;
    font-weight: 700;
    color: #93C5FD;
    padding-right: 0.3rem;
    max-width: 160px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.block-act-pill {
    padding: 0.2rem 0.48rem;
    border-radius: 7px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.1);
    color: #FFFFFF;
    font-family: var(--font-heading);
    font-size: 0.65rem;
    font-weight: 700;
    cursor: pointer;
}

.block-act-pill.pill-danger {
    background: #E11D48;
    border-color: #FDA4AF;
    color: #FFFFFF;
}

.wysiwyg-story-surface {
    flex: 1 1 auto;
    min-height: 440px;
    outline: none;
    font-size: 1.05rem;
    line-height: 1.8;
    color: inherit;
    cursor: text;
}

.wysiwyg-story-surface:empty::before {
    content: attr(data-placeholder);
    color: #94A3B8;
    pointer-events: none;
}

.wysiwyg-story-surface :deep(h2) {
    font-family: var(--font-heading);
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0.75rem 0 0.4rem;
}

.wysiwyg-story-surface :deep(p) {
    margin: 0 0 0.85rem;
}

.wysiwyg-story-surface :deep(.vocab-gloss-token) {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.wysiwyg-story-surface :deep(.vocab-gloss-token:hover) {
    box-shadow: 0 3px 8px rgba(217, 119, 6, 0.3);
}

.wysiwyg-story-surface :deep([data-studio-block='true']),
.wysiwyg-story-surface :deep(figure) {
    cursor: pointer;
    transition: box-shadow 0.15s ease;
}

.wysiwyg-story-surface :deep(.is-selected-studio-block) {
    box-shadow: 0 0 0 3px #2563EB !important;
}

/* Student Mobile Preview Frame */
.student-device-preview-frame {
    width: 390px;
    min-height: 650px;
    border-radius: 40px;
    background: linear-gradient(180deg, #E0F2FE 0%, #BAE6FD 100%);
    border: 8px solid #1E293B;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
    padding: 0.85rem 1rem 1.5rem;
    display: flex;
    flex-direction: column;
}

.device-notch-bar {
    width: 110px;
    height: 18px;
    background: #1E293B;
    border-radius: 9999px;
    margin: 0 auto 0.85rem;
}

.device-screen-body {
    flex: 1 1 auto;
    background: #FFFFFF;
    transition: background-color 0.25s ease;
    border-radius: 28px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.preview-kicker-pill {
    align-self: flex-start;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    background: #DCFCE7;
    color: #065F46;
    font-family: var(--font-heading);
    font-size: 0.64rem;
    font-weight: 800;
}

.preview-story-title {
    margin: 0;
    font-family: var(--font-heading);
    font-size: 1.15rem;
    font-weight: 800;
    color: #1E1B4B;
}

.student-preview-narration-card {
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    border: 2px solid #F59E0B;
    border-radius: 16px;
    padding: 0.6rem 0.75rem;
    box-shadow: 0 4px 0 #D97706;
}

.student-narration-head {
    display: flex;
    flex-direction: column;
    margin-bottom: 0.35rem;
}

.student-narration-head span {
    font-family: var(--font-heading);
    font-size: 0.76rem;
    font-weight: 800;
    color: #78350F;
}

.student-narration-head small {
    font-size: 0.65rem;
    font-weight: 700;
    color: #92400E;
}

.student-narration-player {
    width: 100%;
    height: 34px;
}

.student-vocab-popover-card {
    background: linear-gradient(135deg, #1E1B4B 0%, #312E81 100%);
    color: #FFFFFF;
    border-radius: 16px;
    padding: 0.65rem 0.8rem;
    border: 2px solid #FACC15;
    box-shadow: 0 6px 0 #0F172A;
}

.student-vocab-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.2rem;
}

.student-vocab-badge {
    font-family: var(--font-heading);
    font-size: 0.58rem;
    font-weight: 800;
    color: #FDE047;
}

.student-vocab-close {
    background: transparent;
    border: none;
    color: #CBD5E1;
    cursor: pointer;
}

.student-vocab-word {
    font-family: var(--font-heading);
    font-size: 0.95rem;
    color: #FEF08A;
    margin-right: 0.4rem;
}

.student-vocab-pron {
    font-size: 0.7rem;
    color: #93C5FD;
    font-style: italic;
}

.student-vocab-def {
    margin: 0.25rem 0 0;
    font-size: 0.78rem;
    font-weight: 700;
    color: #F8FAFC;
    line-height: 1.35;
}

.preview-rich-story-card {
    flex: 1 1 auto;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 18px;
    padding: 0.9rem;
    font-size: 0.88rem;
    line-height: 1.65;
    color: #334155;
    overflow-y: auto;
}

.preview-complete-btn-mock {
    padding: 0.75rem;
    border-radius: 16px;
    background: linear-gradient(180deg, #34D399 0%, #059669 100%);
    color: #FFFFFF;
    text-align: center;
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 800;
    box-shadow: 0 4px 0 #065F46;
}

/* Bottom Status & Zoom Bar */
.studio-bottom-status-bar {
    height: 38px;
    flex-shrink: 0;
    background: #FFFFFF;
    border-top: 1.5px solid #CBD5E1;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.74rem;
    color: #475569;
}

.status-left-metrics {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.status-right-zoom {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.zoom-step-btn,
.zoom-reset-btn {
    padding: 0.15rem 0.48rem;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #F8FAFC;
    font-family: var(--font-heading);
    font-size: 0.7rem;
    font-weight: 800;
    cursor: pointer;
}

.zoom-readout {
    font-family: var(--font-heading);
    font-weight: 800;
    min-width: 40px;
    text-align: center;
}

/* ==========================================================================
   RIGHT PANE: STORY PAGES DECK NAVIGATOR
   ========================================================================== */
.studio-right-pages-dock {
    width: 280px;
    flex-shrink: 0;
    background: #FFFFFF;
    border-left: 1.5px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    z-index: 25;
}

.studio-right-pages-dock.deck-collapsed {
    width: 48px;
}

.pages-dock-header {
    padding: 0.75rem 0.8rem;
    border-bottom: 1.5px solid #E2E8F0;
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.deck-toggle-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    background: #F8FAFC;
    font-weight: 900;
    cursor: pointer;
}

.pages-dock-title-wrap h3 {
    margin: 0;
    font-family: var(--font-heading);
    font-size: 0.88rem;
    font-weight: 800;
    color: #0F172A;
}

.pages-dock-title-wrap span {
    font-size: 0.68rem;
    font-weight: 700;
    color: #64748B;
}

.pages-deck-scroller {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    background: #F8FAFC;
}

.page-thumb-card {
    background: #FFFFFF;
    border: 2px solid #E2E8F0;
    border-radius: 14px;
    padding: 0.68rem 0.75rem;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
}

.page-thumb-card.is-selected-page {
    border-color: #2563EB;
    background: #EFF6FF;
}

.draft-thumb-card {
    border-style: dashed;
}

.thumb-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.thumb-badges-right {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.thumb-audio-indicator {
    font-size: 0.72rem;
}

.thumb-page-num {
    font-family: var(--font-heading);
    font-size: 0.62rem;
    font-weight: 800;
    color: #475569;
}

.badge-draft {
    color: #059669;
}

.thumb-type-tag {
    padding: 0.1rem 0.42rem;
    border-radius: 6px;
    background: #E2E8F0;
    color: #334155;
    font-family: var(--font-heading);
    font-size: 0.58rem;
    font-weight: 800;
}

.thumb-page-title {
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 800;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.thumb-page-snippet {
    margin: 0;
    font-size: 0.68rem;
    color: #64748B;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.thumb-card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.35rem;
    padding-top: 0.3rem;
    border-top: 1px dashed #E2E8F0;
}

.thumb-act-btn {
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    font-family: var(--font-heading);
    font-size: 0.6rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
}

.thumb-act-btn.act-delete {
    color: #E11D48;
    border-color: #FECDD3;
    background: #FFF1F2;
}

.pages-dock-footer {
    flex-shrink: 0;
    padding: 0.75rem;
    border-top: 1.5px solid #E2E8F0;
    background: #FFFFFF;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.pages-dock-footer.is-compact {
    margin-top: auto;
    padding: 0.5rem 0.3rem;
    align-items: center;
}

.pages-dock-footer .studio-publish-btn {
    width: 100%;
}

.pages-dock-footer.is-compact .studio-publish-btn,
.pages-dock-footer.is-compact .dock-new-page-btn {
    width: 36px;
    padding: 0.4rem 0;
}

.dock-new-page-btn {
    width: 100%;
    padding: 0.5rem 0.85rem;
    border-radius: 12px;
    background: #F8FAFC;
    border: 2px dashed #94A3B8;
    color: #334155;
    font-family: var(--font-heading);
    font-size: 0.76rem;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.dock-new-page-btn:hover {
    background: #EFF6FF;
    border-color: #2563EB;
    color: #1D4ED8;
}

/* ==========================================================================
   VOCABULARY MEANING CREATOR MODAL
   ========================================================================== */
.vocab-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 150;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.vocab-modal-card {
    width: 100%;
    max-width: 440px;
    background: #FFFFFF;
    border-radius: 24px;
    border: 3px solid #FDE68A;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.35);
    padding: 1.35rem;
}

.vocab-modal-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    background: #FEF3C7;
    color: #B45309;
    font-family: var(--font-heading);
    font-size: 0.64rem;
    font-weight: 800;
    margin-bottom: 0.35rem;
}

.vocab-modal-header h3 {
    margin: 0 0 0.25rem;
    font-family: var(--font-heading);
    font-size: 1.2rem;
    color: #0F172A;
}

.vocab-modal-header p {
    margin: 0 0 1rem;
    font-size: 0.78rem;
    color: #64748B;
}

.vocab-modal-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vocab-field label {
    display: block;
    font-family: var(--font-heading);
    font-size: 0.74rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.25rem;
}

.vocab-input,
.vocab-textarea {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border-radius: 12px;
    border: 1.5px solid #CBD5E1;
    font-family: var(--font-body);
    font-size: 0.85rem;
    font-weight: 700;
    color: #0F172A;
}

.vocab-color-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.vocab-color-pill {
    padding: 0.28rem 0.6rem;
    border-radius: 8px;
    border: 2px solid transparent;
    font-family: var(--font-heading);
    font-size: 0.68rem;
    font-weight: 800;
    cursor: pointer;
}

.vocab-color-pill.is-chosen {
    box-shadow: 0 0 0 2px #0F172A;
}

.vocab-modal-footer {
    margin-top: 1.15rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
}

.btn-vocab-cancel {
    padding: 0.55rem 1rem;
    border-radius: 12px;
    background: #F1F5F9;
    color: #475569;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
}

.btn-vocab-save {
    padding: 0.55rem 1.1rem;
    border-radius: 12px;
    background: linear-gradient(180deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    border: none;
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 3px 0 #92400E;
}

.btn-vocab-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Floating Studio Toast */
.studio-floating-toast {
    position: fixed;
    bottom: 3.2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 90;
    padding: 0.6rem 1.2rem;
    border-radius: 9999px;
    background: #0F172A;
    color: #FDE047;
    border: 2px solid #38BDF8;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.35);
    font-family: var(--font-heading);
    font-size: 0.8rem;
    font-weight: 800;
}

/* ==========================================================================
   SAVE QUEUE PROGRESS PANEL
   ========================================================================== */
.studio-save-queue-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    margin-right: 0.35rem;
    padding: 0 0.3rem;
    border-radius: 9999px;
    background: #FDE047;
    color: #0F172A;
    font-size: 0.7rem;
    font-weight: 900;
}

.studio-toast-pop-enter-active,
.studio-toast-pop-leave-active {
    transition: all 0.25s var(--spring-bounce);
}

.studio-toast-pop-enter-from,
.studio-toast-pop-leave-to {
    opacity: 0;
    transform: translate(-50%, 16px);
}

/* Gemini AI Enhancements */
.vocab-label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.btn-ai-define-pill {
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
    border: 1.5px solid #93C5FD;
    color: #1D4ED8;
    font-family: var(--font-heading);
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-ai-define-pill:hover:not(:disabled) {
    background: #BFDBFE;
    transform: translateY(-1px);
}

.btn-ai-define-pill:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.ai-error-inline {
    font-size: 0.76rem;
    color: #DC2626;
    margin: 0.35rem 0 0 0;
    font-weight: 700;
}

.ai-story-writer-card {
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    border: 2px solid #818CF8;
    border-radius: 14px;
    padding: 0.75rem 0.8rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.12);
}

.ai-tag-pill {
    display: inline-block;
    background: #4F46E5;
    color: #FFFFFF;
    font-size: 0.68rem;
    font-weight: 900;
    padding: 0.15rem 0.45rem;
    border-radius: 9999px;
    margin-bottom: 0.35rem;
}

.btn-open-ai-story {
    margin-top: 0.5rem;
    width: 100%;
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    padding: 0.45rem 0.75rem;
    font-family: var(--font-heading);
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 2px 0 #3730A3;
}

.btn-open-ai-story:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 0 #3730A3;
}

.ai-badge-accent {
    background: #EEF2FF;
    color: #4F46E5;
    border: 1px solid #C7D2FE;
}

.ai-btn-primary {
    background: linear-gradient(180deg, #4F46E5 0%, #4338CA 100%) !important;
    box-shadow: 0 3px 0 #312E81 !important;
}
</style>
