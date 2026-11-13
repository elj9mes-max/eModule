<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useSound } from '@/Services/soundManager';

defineProps({
    laravelVersion: {
        type: String,
        default: '12',
    },
    phpVersion: {
        type: String,
        default: '8.5',
    },
});

const {
    state: soundState,
    soundManager,
    playPop,
    playSuccess,
    playIncorrect,
    playClick,
    toggleMute,
} = useSound();

const prefersReducedMotion = () =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Portal state
const role = ref('student'); // 'student' | 'teacher'
const username = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);
const loginStatus = ref('idle'); // 'idle' | 'submitting' | 'success' | 'error'
const errorMessage = ref('');
const invalidField = ref(null); // 'username' | 'password' | 'both' | null
const cardShaking = ref(false);
const confettiPieces = ref([]);

// Refs for Pixar interactive motion & modals
const loginCardRef = ref(null);
const loginSubmitBtnRef = ref(null);
const dockRef = ref(null);
const studentTabRef = ref(null);
const teacherTabRef = ref(null);
const dockIndicatorStyle = ref({ width: '0px', transform: 'translateX(0px)' });
const cardTiltStyle = ref({});
const pointerParallax = ref({ x: 0, y: 0 });

const registerDialogRef = ref(null);
const isClosingModal = ref(false);

const selectedWorld = ref('auto'); // 'auto' | 'toybox' | 'luca-coast' | 'memory-orb' | 'marigold'

const activeTheme = computed(() => {
    if (selectedWorld.value !== 'auto') {
        return selectedWorld.value;
    }
    return role.value === 'teacher' ? 'luca-coast' : 'toybox';
});

const WORLD_PRESETS = [
    {
        id: 'toybox',
        label: 'Sunny Toybox',
        swatch: 'linear-gradient(135deg, #55C2FF 0%, #FFBE1A 52%, #FF5547 100%)',
        burst: ['#FF5E4D', '#1D78F2', '#FFD028', '#22D68A', '#7E6BFF'],
    },
    {
        id: 'luca-coast',
        label: 'Portorosso Coast',
        swatch: 'linear-gradient(135deg, #FF8E53 0%, #FFE066 50%, #10C7B5 100%)',
        burst: ['#28DEC9', '#FF8342', '#FFD633', '#00B8D4', '#FF5E7E'],
    },
    {
        id: 'memory-orb',
        label: 'Memory Orb Studio',
        swatch: 'linear-gradient(135deg, #7C3AED 0%, #FF5FA2 52%, #38D6FF 100%)',
        burst: ['#FFD23F', '#38D6FF', '#FF5FA2', '#A875FF', '#3DE89D'],
    },
    {
        id: 'marigold',
        label: 'Fiesta Marigold',
        swatch: 'linear-gradient(135deg, #FF9924 0%, #E82C77 52%, #37DEC6 100%)',
        burst: ['#FFA426', '#FF4D94', '#37DEC6', '#FFD94A', '#B56BFF'],
    },
];

const getActiveWorldPreset = () =>
    WORLD_PRESETS.find((w) => w.id === activeTheme.value) || WORLD_PRESETS[0];

const spawnPixarBurst = (anchorEl, count = 14) => {
    if (prefersReducedMotion() || !anchorEl || typeof document === 'undefined') {
        return;
    }

    const rect = anchorEl.getBoundingClientRect();
    const originX = rect.left + rect.width / 2;
    const originY = rect.top + rect.height / 2;
    const palette = getActiveWorldPreset().burst;

    for (let i = 0; i < count; i += 1) {
        const dot = document.createElement('span');
        const size = 8 + (i % 4) * 3;
        const color = palette[i % palette.length];
        dot.setAttribute('aria-hidden', 'true');
        Object.assign(dot.style, {
            position: 'fixed',
            width: `${size}px`,
            height: `${size}px`,
            left: `${originX - size / 2}px`,
            top: `${originY - size / 2}px`,
            borderRadius: '50%',
            pointerEvents: 'none',
            zIndex: '200',
            background: `radial-gradient(circle at 32% 28%, #FFFFFF 0%, ${color} 68%)`,
            boxShadow: 'inset 0 2px 2px rgba(255, 255, 255, 0.85), 0 4px 10px rgba(28, 22, 62, 0.22)',
        });
        document.body.appendChild(dot);

        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.35;
        const dist = 54 + Math.random() * 64;
        const dx = Math.cos(angle) * dist;
        const dy = Math.sin(angle) * dist - 18;

        const anim = dot.animate(
            [
                { transform: 'translate3d(0, 0, 0) scale(0.4)', opacity: 1 },
                { transform: `translate3d(${dx * 0.72}px, ${dy * 0.72}px, 0) scale(1.15)`, opacity: 1, offset: 0.45 },
                { transform: `translate3d(${dx}px, ${dy + 26}px, 0) scale(0.2)`, opacity: 0 },
            ],
            {
                duration: 660 + Math.random() * 180,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                fill: 'forwards',
            },
        );
        anim.onfinish = () => dot.remove();
    }
};

const updateDockIndicator = () => {
    const activeTab = role.value === 'teacher' ? teacherTabRef.value : studentTabRef.value;
    if (!activeTab) {
        return;
    }

    dockIndicatorStyle.value = {
        width: `${activeTab.offsetWidth}px`,
        transform: `translate3d(${activeTab.offsetLeft}px, 0, 0)`,
    };
};

const runThemeTransition = (mutateFn) => {
    if (
        typeof document !== 'undefined' &&
        typeof document.startViewTransition === 'function' &&
        !prefersReducedMotion()
    ) {
        document.startViewTransition(async () => {
            mutateFn();
            await nextTick();
            updateDockIndicator();
        });
    } else {
        mutateFn();
        nextTick(() => updateDockIndicator());
    }
};

const selectWorld = (worldId, eventEl = null) => {
    if (activeTheme.value === worldId) {
        return;
    }

    playPop(1.18);
    runThemeTransition(() => {
        selectedWorld.value = worldId;
    });

    if (eventEl) {
        spawnPixarBurst(eventEl, 10);
    }
};

const setRole = (nextRole, eventEl = null) => {
    if (role.value === nextRole) {
        return;
    }

    const apply = () => {
        role.value = nextRole;
        if (selectedWorld.value === 'toybox' || selectedWorld.value === 'luca-coast') {
            selectedWorld.value = 'auto';
        }
        if (loginStatus.value === 'error') {
            loginStatus.value = 'idle';
            errorMessage.value = '';
            invalidField.value = null;
        }
    };

    playPop(nextRole === 'teacher' ? 1.15 : 0.95);
    runThemeTransition(apply);

    if (eventEl) {
        spawnPixarBurst(eventEl, 8);
    }
};

const fillDemo = (targetRole, event) => {
    if (targetRole === 'teacher') {
        username.value = 'teacher@example.com';
        password.value = 'password';
        setRole('teacher');
    } else {
        username.value = 'student@example.com';
        password.value = 'password';
        setRole('student');
    }
    if (loginStatus.value === 'error') {
        loginStatus.value = 'idle';
        errorMessage.value = '';
        invalidField.value = null;
    }
    playPop(1.2);
    if (event?.currentTarget) {
        spawnPixarBurst(event.currentTarget, 8);
    }
};

const triggerError = (msg, field = 'both') => {
    errorMessage.value = msg || 'Please check your login details and try again.';
    loginStatus.value = 'error';
    invalidField.value = field;
    cardShaking.value = true;
    playIncorrect();
    setTimeout(() => {
        cardShaking.value = false;
    }, 520);
};

const clearFieldError = (field) => {
    if (invalidField.value === field || invalidField.value === 'both') {
        invalidField.value = null;
    }
    if (loginStatus.value === 'error') {
        loginStatus.value = 'idle';
        errorMessage.value = '';
    }
};

const spawnConfetti = () => {
    const colors = [...getActiveWorldPreset().burst, '#FFFFFF'];
    const pieces = [];
    for (let i = 0; i < 36; i++) {
        pieces.push({
            id: i,
            x: Math.random() * 100,
            color: colors[Math.floor(Math.random() * colors.length)],
            size: Math.random() * 9 + 6,
            delay: Math.random() * 0.35,
            rotation: Math.random() * 360,
            shape: Math.random() > 0.4 ? 'rect' : 'circle',
        });
    }
    confettiPieces.value = pieces;
    setTimeout(() => {
        confettiPieces.value = [];
    }, 3500);
};

const handleSubmit = () => {
    if (!username.value.trim()) {
        triggerError('Please enter your Username or Email.', 'username');
        return;
    }
    if (!password.value) {
        triggerError('Please enter your Password.', 'password');
        return;
    }

    loginStatus.value = 'submitting';
    errorMessage.value = '';
    invalidField.value = null;

    router.post(
        '/login',
        {
            username: username.value,
            password: password.value,
            remember: rememberMe.value,
        },
        {
            onError: (errors) => {
                const firstError = Object.values(errors)[0] || 'Invalid credentials. Please try again.';
                triggerError(firstError, errors.password ? 'password' : 'username');
            },
            onSuccess: () => {
                loginStatus.value = 'success';
                playSuccess();
                spawnPixarBurst(loginSubmitBtnRef.value || loginCardRef.value, 16);
                spawnConfetti();
            },
            onFinish: () => {
                if (loginStatus.value !== 'success') {
                    loginStatus.value = 'idle';
                }
            },
        },
    );
};

// Registration modal state (<dialog>)
const showRegisterModal = ref(false);
const registerRole = ref('student'); // 'student' | 'teacher'
const registerForm = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'student',
});
const registerStatus = ref('idle'); // 'idle' | 'submitting' | 'error'
const registerErrorMessage = ref('');
const regInvalidField = ref(null);
const regSubmitBtnRef = ref(null);

const openRegisterModal = (targetRole) => {
    registerRole.value = targetRole || 'student';
    registerForm.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: registerRole.value,
    };
    registerStatus.value = 'idle';
    registerErrorMessage.value = '';
    regInvalidField.value = null;
    isClosingModal.value = false;
    showRegisterModal.value = true;
    playPop(1.08);

    nextTick(() => {
        const dialog = registerDialogRef.value;
        if (dialog && !dialog.open) {
            dialog.showModal();
        }
    });
};

const closeRegisterModal = () => {
    const dialog = registerDialogRef.value;
    if (!dialog || !dialog.open) {
        showRegisterModal.value = false;
        return;
    }

    const finalizeClose = () => {
        isClosingModal.value = false;
        showRegisterModal.value = false;
        registerErrorMessage.value = '';
        registerStatus.value = 'idle';
        regInvalidField.value = null;
        if (dialog.open) {
            dialog.close();
        }
    };

    if (prefersReducedMotion()) {
        finalizeClose();
        return;
    }

    isClosingModal.value = true;
    dialog.addEventListener('animationend', finalizeClose, { once: true });
    setTimeout(finalizeClose, 260);
};

const onDialogClick = (event) => {
    if (event.target === registerDialogRef.value) {
        closeRegisterModal();
    }
};

const setRegisterRole = (targetRole) => {
    registerRole.value = targetRole;
    registerForm.value.role = targetRole;
    playClick();
};

const handleRegisterSubmit = () => {
    if (!registerForm.value.name.trim()) {
        registerErrorMessage.value = 'Please enter your full name.';
        registerStatus.value = 'error';
        regInvalidField.value = 'name';
        playIncorrect();
        return;
    }
    if (!registerForm.value.email.trim()) {
        registerErrorMessage.value = 'Please enter your email address.';
        registerStatus.value = 'error';
        regInvalidField.value = 'email';
        playIncorrect();
        return;
    }
    if (!registerForm.value.password) {
        registerErrorMessage.value = 'Please enter a password.';
        registerStatus.value = 'error';
        regInvalidField.value = 'password';
        playIncorrect();
        return;
    }
    if (registerForm.value.password.length < 8) {
        registerErrorMessage.value = 'Password must be at least 8 characters.';
        registerStatus.value = 'error';
        regInvalidField.value = 'password';
        playIncorrect();
        return;
    }
    if (registerForm.value.password !== registerForm.value.password_confirmation) {
        registerErrorMessage.value = 'Password confirmation does not match.';
        registerStatus.value = 'error';
        regInvalidField.value = 'password_confirmation';
        playIncorrect();
        return;
    }

    registerStatus.value = 'submitting';
    registerErrorMessage.value = '';
    regInvalidField.value = null;

    router.post('/register', registerForm.value, {
        onError: (errors) => {
            registerStatus.value = 'error';
            registerErrorMessage.value = Object.values(errors)[0] || 'Registration failed. Please check your inputs.';
            regInvalidField.value = Object.keys(errors)[0] || null;
            playIncorrect();
        },
        onSuccess: () => {
            playSuccess();
            spawnPixarBurst(regSubmitBtnRef.value, 16);
            closeRegisterModal();
            spawnConfetti();
        },
        onFinish: () => {
            if (registerStatus.value === 'submitting') {
                registerStatus.value = 'idle';
            }
        },
    });
};

const resetDemo = () => {
    username.value = '';
    password.value = '';
    loginStatus.value = 'idle';
    errorMessage.value = '';
    invalidField.value = null;
    showPassword.value = false;
};

// Interactive 3D Diorama Tilt & Subtle Background Parallax (Fine pointer only)
let canFinePointer = false;

const handleCardPointerMove = (e) => {
    if (!canFinePointer || prefersReducedMotion() || !loginCardRef.value) {
        return;
    }
    const rect = loginCardRef.value.getBoundingClientRect();
    const px = (e.clientX - rect.left) / rect.width - 0.5;
    const py = (e.clientY - rect.top) / rect.height - 0.5;
    const rx = (-py * 5.5).toFixed(2);
    const ry = (px * 5.5).toFixed(2);
    cardTiltStyle.value = {
        transform: `perspective(960px) rotateX(${rx}deg) rotateY(${ry}deg) translate3d(0, -3px, 0)`,
    };
};

const handleCardPointerLeave = () => {
    cardTiltStyle.value = {};
};

const handleStagePointerMove = (e) => {
    if (!canFinePointer || prefersReducedMotion()) {
        return;
    }
    const nx = (e.clientX / window.innerWidth - 0.5) * 2;
    const ny = (e.clientY / window.innerHeight - 0.5) * 2;
    pointerParallax.value = {
        x: Math.round(nx * 10),
        y: Math.round(ny * 8),
    };
};

const handleResize = () => {
    updateDockIndicator();
};

const handleVisibilityChange = () => {
    if (document.hidden) {
        if (soundState?.isInitialized) {
            soundState.isBgmPlaying = false;
        }
        if (soundManager?.audioCtx?.state === 'running') {
            soundManager.audioCtx.suspend().catch(() => {});
        }
    }
};

watch(role, () => {
    nextTick(() => updateDockIndicator());
});

onMounted(() => {
    canFinePointer =
        typeof window !== 'undefined' &&
        window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    nextTick(() => {
        updateDockIndicator();
        if (typeof document !== 'undefined' && document.fonts?.ready) {
            document.fonts.ready.then(() => updateDockIndicator());
        }
    });

    window.addEventListener('resize', handleResize);
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <Head title="Welcome to WEBREAD • Learning Portal" />

    <div
        class="webread-page pixar-stage"
        :data-pixar-theme="activeTheme"
        @pointermove="handleStagePointerMove"
    >
        <!-- Plane 2b: Volumetric Drifting Clouds & Twinkling Firefly Sparks -->
        <div class="pixar-cloud-field" aria-hidden="true">
            <svg class="pixar-cloud pixar-cloud--1" viewBox="0 0 260 110" fill="none">
                <defs>
                    <linearGradient id="pxCloudGrad1" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.96" />
                        <stop offset="68%" stop-color="#FFFDF9" stop-opacity="0.88" />
                        <stop offset="100%" stop-color="#D9EEFF" stop-opacity="0.72" />
                    </linearGradient>
                </defs>
                <path
                    d="M44 92h170c22 0 38-15 38-34 0-17-12-31-29-34-6-15-21-24-39-24-15 0-28 7-36 18-7-4-16-7-25-7-22 0-40 16-43 37-19 2-32 16-32 32 0 7 3 12 10 12h86Z"
                    fill="url(#pxCloudGrad1)"
                />
            </svg>
            <svg class="pixar-cloud pixar-cloud--2" viewBox="0 0 220 96" fill="none">
                <path
                    d="M36 80h148c19 0 32-13 32-29 0-14-10-26-25-29-6-13-18-21-33-21-13 0-24 6-31 16-6-4-13-6-21-6-19 0-34 14-36 32-16 2-28 13-28 27 0 6 3 10 9 10h85Z"
                    fill="url(#pxCloudGrad1)"
                />
            </svg>
            <svg class="pixar-cloud pixar-cloud--3" viewBox="0 0 190 84" fill="none">
                <path
                    d="M32 70h126c16 0 27-11 27-25 0-12-9-22-21-25-5-11-15-18-28-18-11 0-20 5-26 14-5-3-11-5-18-5-16 0-29 12-31 27-14 2-24 11-24 23 0 5 3 9 8 9h77Z"
                    fill="url(#pxCloudGrad1)"
                />
            </svg>
        </div>

        <div class="pixar-sparkle-field" aria-hidden="true">
            <span class="pixar-sparkle pixar-sparkle--1"></span>
            <span class="pixar-sparkle pixar-sparkle--2"></span>
            <span class="pixar-sparkle pixar-sparkle--3"></span>
            <span class="pixar-sparkle pixar-sparkle--4"></span>
            <span class="pixar-sparkle pixar-sparkle--5"></span>
            <span class="pixar-sparkle pixar-sparkle--6"></span>
        </div>

        <!-- Plane 3: Drifting Volumetric Memory Orbs -->
        <div class="pixar-orb-field" aria-hidden="true">
            <span class="pixar-orb pixar-orb--1"></span>
            <span class="pixar-orb pixar-orb--2"></span>
            <span class="pixar-orb pixar-orb--3"></span>
            <span class="pixar-orb pixar-orb--4"></span>
            <span class="pixar-orb pixar-orb--5"></span>
            <span class="pixar-orb pixar-orb--6"></span>
        </div>

        <!-- Plane 4: Tri-Ridge Multi-Stop Gradient Storybook Horizon Hills -->
        <div class="pixar-hills" aria-hidden="true">
            <svg viewBox="0 0 1440 240" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="pxHillFar" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--px-hill-far-1)" />
                        <stop offset="100%" stop-color="var(--px-hill-far-2)" />
                    </linearGradient>
                    <linearGradient id="pxHillMid" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--px-hill-mid-1)" />
                        <stop offset="100%" stop-color="var(--px-hill-mid-2)" />
                    </linearGradient>
                    <linearGradient id="pxHillNear" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--px-hill-near-1)" />
                        <stop offset="100%" stop-color="var(--px-hill-near-2)" />
                    </linearGradient>
                </defs>
                <path
                    d="M0,126 C210,36 450,148 730,78 C1010,12 1240,118 1440,68 L1440,240 L0,240 Z"
                    fill="url(#pxHillFar)"
                />
                <path
                    d="M0,152 C260,82 510,192 840,120 C1100,64 1280,152 1440,118 L1440,240 L0,240 Z"
                    fill="url(#pxHillMid)"
                />
                <path
                    d="M0,184 C310,126 570,218 900,154 C1150,106 1310,176 1440,148 L1440,240 L0,240 Z"
                    fill="url(#pxHillNear)"
                />
                <path
                    d="M0,184 C310,126 570,218 900,154 C1150,106 1310,176 1440,148"
                    fill="none"
                    stroke="var(--px-hill-rim)"
                    stroke-width="3"
                />
            </svg>
        </div>

        <!-- Confetti Celebration Container (GPU Transform Only) -->
        <div v-if="confettiPieces.length > 0" class="confetti-container" aria-hidden="true">
            <div
                v-for="piece in confettiPieces"
                :key="piece.id"
                class="confetti-item"
                :class="piece.shape"
                :style="{
                    left: `${piece.x}%`,
                    backgroundColor: piece.color,
                    width: `${piece.size}px`,
                    height: `${piece.size * (piece.shape === 'rect' ? 1.5 : 1)}px`,
                    animationDelay: `${piece.delay}s`,
                    '--confetti-rot': `${piece.rotation}deg`,
                }"
            ></div>
        </div>

        <!-- Top Utility Bar: Pixar Color World Marbles & Opt-In Foley -->
        <div class="top-utility-bar">
            <div class="pixar-world-picker" role="group" aria-label="Color world theme">
                <span class="world-picker-label">Worlds</span>
                <button
                    v-for="world in WORLD_PRESETS"
                    :key="world.id"
                    type="button"
                    class="world-swatch-btn"
                    :class="{ 'is-active': activeTheme === world.id }"
                    :style="{ background: world.swatch }"
                    :aria-pressed="activeTheme === world.id"
                    :aria-label="`Switch to ${world.label} theme`"
                    :title="world.label"
                    @click="(e) => selectWorld(world.id, e.currentTarget)"
                ></button>
            </div>

            <button
                type="button"
                class="pixar-foley-btn"
                :aria-pressed="!soundState.isMuted"
                :aria-label="soundState.isMuted ? 'Enable portal sound effects' : 'Mute portal sound effects'"
                @click="toggleMute"
            >
                <svg v-if="!soundState.isMuted" class="foley-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor" fill-opacity="0.22" />
                    <path d="M15.54 8.46a5 5 0 0 1 0 7.07" />
                    <path d="M19.07 4.93a10 10 0 0 1 0 14.14" />
                </svg>
                <svg v-else class="foley-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
                    <line x1="23" y1="9" x2="17" y2="15" />
                    <line x1="17" y1="9" x2="23" y2="15" />
                </svg>
                <span>{{ soundState.isMuted ? 'Sound Off' : 'Sound On' }}</span>
            </button>
        </div>

        <!-- 3D Pop-Up World Flanking Diorama Wings (Left & Right) -->
        <aside
            class="scene-wing wing-left"
            aria-hidden="true"
            :style="{ transform: `translate3d(${pointerParallax.x * -0.45}px, ${pointerParallax.y * -0.35}px, 0)` }"
        >
            <img
                src="/images/book_left_trees_mountains.png"
                alt=""
                width="965"
                height="777"
                decoding="async"
                class="asset-pop asset-book-left"
            />
            <img
                src="/images/boy_left_on_book_laptop.png"
                alt=""
                width="838"
                height="905"
                decoding="async"
                class="asset-pop asset-boy-laptop"
            />
            <div class="diorama-floor-anchor floor-left">
                <span class="diorama-contact-shadow"></span>
                <img
                    src="/images/girl_left_reading_book.png"
                    alt=""
                    width="945"
                    height="878"
                    decoding="async"
                    class="asset-pop asset-girl-reading"
                />
            </div>
        </aside>

        <aside
            class="scene-wing wing-right"
            aria-hidden="true"
            :style="{ transform: `translate3d(${pointerParallax.x * 0.45}px, ${pointerParallax.y * -0.35}px, 0)` }"
        >
            <img
                src="/images/book_right_mountains_river.png"
                alt=""
                width="996"
                height="640"
                decoding="async"
                class="asset-pop asset-book-right"
            />
            <img
                src="/images/book_with_message_and_laptop_girl.png"
                alt=""
                width="1030"
                height="682"
                decoding="async"
                class="asset-pop asset-book-laptop-girl"
            />
            <div class="diorama-floor-anchor floor-right">
                <span class="diorama-contact-shadow"></span>
                <img
                    src="/images/girl_right_using_tablet.png"
                    alt=""
                    width="942"
                    height="814"
                    decoding="async"
                    class="asset-pop asset-girl-tablet"
                />
            </div>
        </aside>

        <!-- Sculpted Volumetric Gears & Stars -->
        <div class="ambient-decorations" aria-hidden="true">
            <div class="float-gear gear-1">
                <svg width="46" height="46" viewBox="0 0 24 24">
                    <defs>
                        <radialGradient id="pxGearGold" cx="35%" cy="28%" r="72%">
                            <stop offset="0%" stop-color="#FFF2A6" />
                            <stop offset="52%" stop-color="#FFBE1A" />
                            <stop offset="100%" stop-color="#C87E00" />
                        </radialGradient>
                    </defs>
                    <path
                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"
                        fill="url(#pxGearGold)"
                        stroke="#FFFFFF"
                        stroke-opacity="0.7"
                        stroke-width="0.9"
                    />
                    <circle cx="12" cy="12" r="3.4" fill="#FFFDF9" />
                </svg>
            </div>

            <div class="float-gear gear-2">
                <svg width="48" height="48" viewBox="0 0 24 24">
                    <defs>
                        <radialGradient id="pxGearPlum" cx="35%" cy="28%" r="72%">
                            <stop offset="0%" stop-color="#C8BFFF" />
                            <stop offset="52%" stop-color="#7C6CE6" />
                            <stop offset="100%" stop-color="#4B3BA8" />
                        </radialGradient>
                    </defs>
                    <path
                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"
                        fill="url(#pxGearPlum)"
                        stroke="#FFFFFF"
                        stroke-opacity="0.65"
                        stroke-width="0.9"
                    />
                    <circle cx="12" cy="12" r="3.4" fill="#FFFDF9" />
                </svg>
            </div>

            <!-- Volumetric 3-Stop Radial Stars -->
            <div class="float-star star-1">
                <svg width="28" height="28" viewBox="0 0 36 36">
                    <defs>
                        <radialGradient id="pxStar1" cx="35%" cy="28%" r="70%">
                            <stop offset="0%" stop-color="#FFF7BA" />
                            <stop offset="55%" stop-color="#FFBE1A" />
                            <stop offset="100%" stop-color="#D98300" />
                        </radialGradient>
                    </defs>
                    <path d="M18 2.5L21.9 12.6L32.8 14L24.5 21.1L26.8 31.8L18 26.2L9.2 31.8L11.5 21.1L3.2 14L14.1 12.6L18 2.5Z" fill="url(#pxStar1)" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="float-star star-2">
                <svg width="24" height="24" viewBox="0 0 36 36">
                    <path d="M18 2.5L21.9 12.6L32.8 14L24.5 21.1L26.8 31.8L18 26.2L9.2 31.8L11.5 21.1L3.2 14L14.1 12.6L18 2.5Z" fill="url(#pxStar1)" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="float-star star-3">
                <svg width="26" height="26" viewBox="0 0 36 36">
                    <path d="M18 2.5L21.9 12.6L32.8 14L24.5 21.1L26.8 31.8L18 26.2L9.2 31.8L11.5 21.1L3.2 14L14.1 12.6L18 2.5Z" fill="url(#pxStar1)" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <!-- Central Interactive Pixar Portal Column -->
        <main class="login-core">
            <!-- Header Section -->
            <header class="webread-header">
                <div class="welcome-badge">
                    <div class="welcome-row">
                    <!-- Sculpted Volumetric 3D Stacked Books Emblem -->
                    <span class="mini-3d-books" aria-hidden="true">
                        <svg width="42" height="34" viewBox="0 0 42 34" fill="none">
                            <defs>
                                <linearGradient id="pxBookCoral" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#FF9684" />
                                    <stop offset="55%" stop-color="#D63327" />
                                    <stop offset="100%" stop-color="#9A1B12" />
                                </linearGradient>
                                <linearGradient id="pxBookBlue" x1="0" y1="0" x2="0.4" y2="1">
                                    <stop offset="0%" stop-color="#69B8FF" />
                                    <stop offset="55%" stop-color="#186CE0" />
                                    <stop offset="100%" stop-color="#0C459B" />
                                </linearGradient>
                            </defs>
                            <rect x="3" y="9" width="20" height="21" rx="4.5" fill="url(#pxBookCoral)" transform="rotate(-11 3 9)" stroke="#FFFFFF" stroke-opacity="0.85" stroke-width="1.2" />
                            <rect x="15" y="4" width="21" height="24" rx="5" fill="url(#pxBookBlue)" stroke="#FFFFFF" stroke-opacity="0.9" stroke-width="1.4" />
                            <rect x="18" y="8.5" width="14" height="3.2" rx="1.6" fill="#FFFFFF" fill-opacity="0.88" />
                            <rect x="18" y="14" width="9" height="2.4" rx="1.2" fill="#BAE6FD" fill-opacity="0.88" />
                            <path d="M29 4V13L31.5 11.2L34 13V4Z" fill="#FFBE1A" />
                        </svg>
                    </span>

                    <span class="welcome-text">Welcome to</span>

                    <!-- Volumetric Toy Gears Duo -->
                    <span class="mini-gears" aria-hidden="true">
                        <svg width="40" height="28" viewBox="0 0 40 28" fill="none">
                            <defs>
                                <radialGradient id="pxMiniOrb1" cx="32%" cy="28%" r="72%">
                                    <stop offset="0%" stop-color="#D8D2FF" />
                                    <stop offset="55%" stop-color="#7C6CE6" />
                                    <stop offset="100%" stop-color="#4A3CA8" />
                                </radialGradient>
                                <radialGradient id="pxMiniOrb2" cx="32%" cy="28%" r="72%">
                                    <stop offset="0%" stop-color="#A6E4FF" />
                                    <stop offset="55%" stop-color="#186CE0" />
                                    <stop offset="100%" stop-color="#0B4498" />
                                </radialGradient>
                            </defs>
                            <circle cx="11" cy="13" r="8" fill="url(#pxMiniOrb1)" stroke="#FFFFFF" stroke-width="1.2" />
                            <circle cx="11" cy="13" r="3" fill="#FFFDF9" />
                            <circle cx="27" cy="16" r="9.5" fill="url(#pxMiniOrb2)" stroke="#FFFFFF" stroke-width="1.2" />
                            <circle cx="27" cy="16" r="3.6" fill="#FFFDF9" />
                        </svg>
                    </span>
                    </div>
                </div>

                <!-- Big Sculpted 3D WEBREAD Title -->
                <h1 class="brand-title">
                    <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                </h1>
            </header>

            <!-- Volumetric Clay Diorama Login Card with Overlapping Pixar Mascot Crest -->
            <div
                ref="loginCardRef"
                class="pixar-login-card"
                :class="{ 'card-shake': cardShaking }"
                :style="cardTiltStyle"
                @pointermove="handleCardPointerMove"
                @pointerleave="handleCardPointerLeave"
            >
                <!-- Floating 3D Pixar Lumina Orb Mascot Crest -->
                <div class="pixar-card-crest" :class="{ 'crest-teacher': role === 'teacher', 'crest-happy': loginStatus === 'success' }" aria-hidden="true">
                    <svg viewBox="0 0 120 120" width="76" height="76" class="mascot-svg">
                        <defs>
                            <radialGradient id="pxMascotBodyStudent" cx="34%" cy="28%" r="72%">
                                <stop offset="0%" stop-color="#FFF0A8" />
                                <stop offset="46%" stop-color="#FFBE1A" />
                                <stop offset="86%" stop-color="#E38B00" />
                                <stop offset="100%" stop-color="#A85900" />
                            </radialGradient>
                            <radialGradient id="pxMascotBodyTeacher" cx="34%" cy="28%" r="72%">
                                <stop offset="0%" stop-color="#9BF5EA" />
                                <stop offset="46%" stop-color="#14B8A6" />
                                <stop offset="86%" stop-color="#087E72" />
                                <stop offset="100%" stop-color="#044E47" />
                            </radialGradient>
                            <linearGradient id="pxMascotRim" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.88" />
                                <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0" />
                            </linearGradient>
                            <filter id="pxMascotDrop" x="-25%" y="-25%" width="150%" height="150%">
                                <feDropShadow dx="0" dy="8" stdDeviation="6" flood-color="#1E1A3B" flood-opacity="0.24" />
                            </filter>
                        </defs>

                        <!-- Main Sculpted Volumetric Sphere -->
                        <circle
                            cx="60"
                            cy="60"
                            r="46"
                            :fill="role === 'teacher' ? 'url(#pxMascotBodyTeacher)' : 'url(#pxMascotBodyStudent)'"
                            filter="url(#pxMascotDrop)"
                        />
                        <!-- Upper Softbox Specular Crescent -->
                        <ellipse cx="45" cy="34" rx="22" ry="12" transform="rotate(-24 45 34)" fill="url(#pxMascotRim)" />
                        <!-- Crisp Specular Sparkle -->
                        <circle cx="35" cy="29" r="4.8" fill="#FFFFFF" opacity="0.94" />

                        <!-- Expressive Eyes with Double Catchlights -->
                        <template v-if="loginStatus === 'success'">
                            <path d="M40 58 Q47 50 54 58" fill="none" stroke="#1E1A3B" stroke-width="4.2" stroke-linecap="round" />
                            <path d="M66 58 Q73 50 80 58" fill="none" stroke="#1E1A3B" stroke-width="4.2" stroke-linecap="round" />
                        </template>
                        <template v-else>
                            <g class="mascot-eyes-blink">
                                <ellipse cx="47" cy="59" rx="5.5" ry="7.6" fill="#1E1A3B" />
                                <ellipse cx="73" cy="59" rx="5.5" ry="7.6" fill="#1E1A3B" />
                                <circle cx="45" cy="55.8" r="2.2" fill="#FFFFFF" />
                                <circle cx="71" cy="55.8" r="2.2" fill="#FFFFFF" />
                                <circle cx="49" cy="62.2" r="1.1" fill="#FFFFFF" opacity="0.8" />
                                <circle cx="75" cy="62.2" r="1.1" fill="#FFFFFF" opacity="0.8" />
                            </g>
                        </template>

                        <!-- Teacher Mode Sculpted Round Spectacles -->
                        <g v-if="role === 'teacher'" stroke="#FFFDF7" stroke-width="2.6" fill="rgba(255, 255, 255, 0.14)">
                            <circle cx="47" cy="59" r="11.2" />
                            <circle cx="73" cy="59" r="11.2" />
                            <path d="M58.2 59 Q60 56.5 61.8 59" fill="none" />
                        </g>

                        <!-- Subsurface Rosy Cheeks -->
                        <ellipse cx="36" cy="70" rx="6.2" ry="3.5" fill="#FF6B5B" opacity="0.46" />
                        <ellipse cx="84" cy="70" rx="6.2" ry="3.5" fill="#FF6B5B" opacity="0.46" />

                        <!-- Expressive Mouth -->
                        <circle v-if="loginStatus === 'error'" cx="60" cy="76" r="5" fill="#1E1A3B" />
                        <path v-else d="M50 72 Q60 82 70 72" fill="none" stroke="#1E1A3B" stroke-width="3.8" stroke-linecap="round" />
                    </svg>
                </div>

                <!-- Error Message Banner -->
                <div v-if="errorMessage" class="error-banner" role="alert">
                    <svg class="alert-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span class="error-text">{{ errorMessage }}</span>
                </div>

                <!-- Success State Overlay -->
                <div v-if="loginStatus === 'success'" class="success-banner" role="status">
                    <div class="success-badge-orb" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="success-title">Welcome to WebRead!</div>
                    <div class="success-sub">Signed in as {{ role === 'student' ? 'Student Explorer' : 'Instructor' }}. Opening portal...</div>
                    <button type="button" class="pixar-btn pixar-btn--clay pixar-btn--sm" @click="resetDemo">
                        <span class="pixar-btn__label">Reset Form</span>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="login-form" novalidate>
                    <!-- Input 1: USERNAME / EMAIL -->
                    <div
                        class="pixar-field"
                        :class="{ 'is-invalid': invalidField === 'username' || invalidField === 'both' }"
                    >
                        <label class="pixar-label" for="username">Username or Email</label>
                        <div class="pixar-input-wrap">
                            <span class="pixar-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <input
                                id="username"
                                v-model="username"
                                type="text"
                                class="pixar-input"
                                placeholder="e.g. student@example.com"
                                autocomplete="username"
                                required
                                @input="clearFieldError('username')"
                            />
                        </div>
                    </div>

                    <!-- Input 2: PASSWORD -->
                    <div
                        class="pixar-field"
                        :class="{ 'is-invalid': invalidField === 'password' || invalidField === 'both' }"
                    >
                        <label class="pixar-label" for="password">Password</label>
                        <div class="pixar-input-wrap">
                            <span class="pixar-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="3" ry="3" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </span>
                            <input
                                id="password"
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="pixar-input pixar-input--password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                                @input="clearFieldError('password')"
                            />
                            <button
                                type="button"
                                class="pill-eye-toggle"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                :title="showPassword ? 'Hide password' : 'Show password'"
                                @click="showPassword = !showPassword"
                            >
                                <svg v-if="showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Tactile Recessed Keep Signed In Checkbox -->
                    <div class="remember-row">
                        <label class="pixar-check">
                            <input v-model="rememberMe" class="pixar-check__input" type="checkbox" />
                            <span class="pixar-check__box" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                            </span>
                            <span class="remember-label">Keep me signed in</span>
                        </label>
                    </div>

                    <!-- Zero-Layout-Shift Sculpted Toy-Vinyl Primary CTA -->
                    <div class="btn-container">
                        <button
                            ref="loginSubmitBtnRef"
                            type="submit"
                            class="pixar-btn pixar-btn--lg btn-login-cta"
                            :aria-busy="loginStatus === 'submitting'"
                            :disabled="loginStatus === 'submitting' || loginStatus === 'success'"
                        >
                            <span class="pixar-btn__label">
                                <template v-if="loginStatus === 'success'">PORTAL UNLOCKED</template>
                                <template v-else>ENTER PORTAL</template>
                            </span>
                        </button>
                    </div>

                    <!-- Divider: OR CREATE AN ACCOUNT -->
                    <div class="auth-divider">
                        <span class="divider-line"></span>
                        <span class="divider-text">OR CREATE AN ACCOUNT</span>
                        <span class="divider-line"></span>
                    </div>

                    <!-- Create Account Actions: Student & Teacher -->
                    <div class="signup-buttons-container">
                        <button
                            type="button"
                            class="pixar-btn pixar-btn--secondary pixar-btn--sm btn-signup-full"
                            :disabled="loginStatus === 'submitting' || loginStatus === 'success'"
                            @click="openRegisterModal('student')"
                        >
                            <svg class="btn-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                <path d="M6 12v5c3 3 9 3 12 0v-5" />
                            </svg>
                            <span class="pixar-btn__label">Create Student Account</span>
                        </button>

                        <button
                            type="button"
                            class="pixar-btn pixar-btn--accent pixar-btn--sm btn-signup-full"
                            :disabled="loginStatus === 'submitting' || loginStatus === 'success'"
                            @click="openRegisterModal('teacher')"
                        >
                            <svg class="btn-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="pixar-btn__label">Create Teacher Account</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bottom Pixar Sliding Pill Dock & Greeting -->
            <footer class="webread-footer">
                <div class="greeting-text" aria-live="polite">
                    HI {{ role.toUpperCase() }}!
                </div>

                <div ref="dockRef" class="pixar-dock" role="tablist" aria-label="Portal Role Theme Switcher">
                    <span class="pixar-dock__indicator" :style="dockIndicatorStyle" aria-hidden="true"></span>
                    <button
                        ref="studentTabRef"
                        type="button"
                        role="tab"
                        class="pixar-dock__tab"
                        :aria-selected="role === 'student'"
                        @click="(e) => setRole('student', e.currentTarget)"
                    >
                        Student World
                    </button>
                    <button
                        ref="teacherTabRef"
                        type="button"
                        role="tab"
                        class="pixar-dock__tab"
                        :aria-selected="role === 'teacher'"
                        @click="(e) => setRole('teacher', e.currentTarget)"
                    >
                        Teacher Studio
                    </button>
                </div>

                <!-- Quick Test Credentials Pill Bar -->
                <div class="demo-pills-bar">
                    <span class="demo-pills-label">Quick autofill:</span>
                    <button type="button" class="demo-pill" @click="(e) => fillDemo('student', e)">
                        <svg class="demo-pill-svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z" />
                            <path d="M3.5 9.654V14a1 1 0 00.629.928l5.5 2.2a1 1 0 00.742 0l5.5-2.2A1 1 0 0016.5 14V9.654l-5.318 2.279a2.5 2.5 0 01-1.964 0L3.5 9.654z" />
                        </svg>
                        <span>Student Demo</span>
                    </button>
                    <button type="button" class="demo-pill" @click="(e) => fillDemo('teacher', e)">
                        <svg class="demo-pill-svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" />
                        </svg>
                        <span>Teacher Demo</span>
                    </button>
                </div>
            </footer>
        </main>

        <!-- Native Top-Layer Storybook Registration <dialog> -->
        <dialog
            ref="registerDialogRef"
            class="pixar-modal"
            :class="{ 'is-closing': isClosingModal }"
            aria-labelledby="register-modal-title"
            @click="onDialogClick"
            @cancel.prevent="closeRegisterModal"
        >
            <div class="pixar-modal__shell">
                <!-- Floating 3D Emblem Crest overlapping top rim -->
                <div class="pixar-modal__crest" aria-hidden="true">
                    <svg viewBox="0 0 48 48" width="40" height="40" fill="none">
                        <path
                            d="M24 6L28.8 16.5L40.4 17.9L31.8 25.8L34.1 37.2L24 31.4L13.9 37.2L16.2 25.8L7.6 17.9L19.2 16.5L24 6Z"
                            fill="currentColor"
                            fill-opacity="0.92"
                            stroke="#FFFFFF"
                            stroke-width="2"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>

                <button
                    type="button"
                    class="pixar-modal__close"
                    aria-label="Close registration modal"
                    @click="closeRegisterModal"
                >
                    &times;
                </button>

                <!-- Role Selector Tabs -->
                <div class="modal-header" data-pixar-stagger style="--stagger-i: 0">
                    <div class="modal-role-pill-bar">
                        <button
                            type="button"
                            class="modal-role-pill"
                            :class="{ active: registerRole === 'student' }"
                            @click="setRegisterRole('student')"
                        >
                            Student
                        </button>
                        <button
                            type="button"
                            class="modal-role-pill"
                            :class="{ active: registerRole === 'teacher' }"
                            @click="setRegisterRole('teacher')"
                        >
                            Teacher
                        </button>
                    </div>
                    <h2 id="register-modal-title" class="modal-title">
                        {{ registerRole === 'student' ? 'Create Student Account' : 'Create Teacher Account' }}
                    </h2>
                    <p class="modal-sub">
                        {{
                            registerRole === 'student'
                                ? 'Join WebRead to embark on reading quests and earn badges!'
                                : 'Manage interactive modules and inspire reading habits.'
                        }}
                    </p>
                </div>

                <!-- Error Alert -->
                <div v-if="registerErrorMessage" class="error-banner" role="alert">
                    <svg class="alert-svg-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span class="error-text">{{ registerErrorMessage }}</span>
                </div>

                <!-- Modal Form with Recessed Clay Inputs -->
                <form @submit.prevent="handleRegisterSubmit" class="modal-form" novalidate>
                    <div
                        class="pixar-field"
                        data-pixar-stagger
                        style="--stagger-i: 1"
                        :class="{ 'is-invalid': regInvalidField === 'name' }"
                    >
                        <label class="pixar-label" for="reg-name">Full Name</label>
                        <div class="pixar-input-wrap">
                            <span class="pixar-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <input
                                id="reg-name"
                                v-model="registerForm.name"
                                type="text"
                                class="pixar-input"
                                placeholder="Alex Explorer"
                                autocomplete="name"
                                required
                                @input="regInvalidField = null"
                            />
                        </div>
                    </div>

                    <div
                        class="pixar-field"
                        data-pixar-stagger
                        style="--stagger-i: 2"
                        :class="{ 'is-invalid': regInvalidField === 'email' }"
                    >
                        <label class="pixar-label" for="reg-email">Email Address</label>
                        <div class="pixar-input-wrap">
                            <span class="pixar-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="3" />
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                </svg>
                            </span>
                            <input
                                id="reg-email"
                                v-model="registerForm.email"
                                type="email"
                                class="pixar-input"
                                placeholder="alex@example.com"
                                autocomplete="email"
                                required
                                @input="regInvalidField = null"
                            />
                        </div>
                    </div>

                    <div
                        class="pixar-field"
                        data-pixar-stagger
                        style="--stagger-i: 3"
                        :class="{ 'is-invalid': regInvalidField === 'password' }"
                    >
                        <label class="pixar-label" for="reg-password">Password (Min 8 Characters)</label>
                        <div class="pixar-input-wrap">
                            <span class="pixar-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="3" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </span>
                            <input
                                id="reg-password"
                                v-model="registerForm.password"
                                type="password"
                                class="pixar-input"
                                placeholder="At least 8 characters"
                                autocomplete="new-password"
                                required
                                @input="regInvalidField = null"
                            />
                        </div>
                    </div>

                    <div
                        class="pixar-field"
                        data-pixar-stagger
                        style="--stagger-i: 4"
                        :class="{ 'is-invalid': regInvalidField === 'password_confirmation' }"
                    >
                        <label class="pixar-label" for="reg-password-confirmation">Confirm Password</label>
                        <div class="pixar-input-wrap">
                            <span class="pixar-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                            </span>
                            <input
                                id="reg-password-confirmation"
                                v-model="registerForm.password_confirmation"
                                type="password"
                                class="pixar-input"
                                placeholder="Repeat your password"
                                autocomplete="new-password"
                                required
                                @input="regInvalidField = null"
                            />
                        </div>
                    </div>

                    <div class="btn-container" data-pixar-stagger style="--stagger-i: 5">
                        <button
                            ref="regSubmitBtnRef"
                            type="submit"
                            class="pixar-btn pixar-btn--lg btn-modal-submit"
                            :class="registerRole === 'teacher' ? 'pixar-btn--primary' : 'pixar-btn--secondary'"
                            :aria-busy="registerStatus === 'submitting'"
                            :disabled="registerStatus === 'submitting'"
                        >
                            <span class="pixar-btn__label">
                                {{ registerRole === 'student' ? 'CREATE STUDENT ACCOUNT' : 'CREATE TEACHER ACCOUNT' }}
                            </span>
                        </button>
                    </div>

                    <div class="modal-footer-switch" data-pixar-stagger style="--stagger-i: 6">
                        <span>Already have an account?</span>
                        <button type="button" class="link-back-login" @click="closeRegisterModal">
                            Log In
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    </div>
</template>

<style scoped>
/* ==========================================================================
   PIXAR UI DESIGN SYSTEM — VIBRANT LIVING TOKENS & 4 ATMOSPHERIC WORLDS
   ========================================================================== */
.webread-page,
.webread-page[data-pixar-theme="toybox"] {
    --px-sky-top: #42BAFF;
    --px-sky-mid: #7EE0FF;
    --px-sky-bottom: #FFF0A8;
    --px-sun-bloom: rgba(255, 214, 66, 0.72);
    --px-secondary-bloom: rgba(255, 110, 150, 0.34);

    --px-hill-far-1: #8B96FF;
    --px-hill-far-2: #5D6DF5;
    --px-hill-mid-1: #29E0BF;
    --px-hill-mid-2: #0DB596;
    --px-hill-near-1: #4BE364;
    --px-hill-near-2: #11A349;
    --px-hill-rim: rgba(212, 255, 173, 0.88);
    --px-cloud-opacity: 0.92;
    --px-sparkle-color: #FFF7B2;
    --px-wing-glow: rgba(255, 232, 130, 0.48);
    --px-card-aura: rgba(255, 200, 56, 0.42);

    --px-card-bg-top: rgba(255, 253, 249, 0.96);
    --px-card-bg-bottom: rgba(243, 236, 255, 0.93);
    --px-surface-card: #FFFDF9;
    --px-surface-card-bottom: #F3ECFF;
    --px-surface-well: #EAE4FC;
    --px-surface-well-focus: #FFFFFF;
    --px-surface-elevated: #FFFFFF;
    --px-surface-glass: rgba(255, 253, 249, 0.88);
    --px-backdrop-scrim: rgba(28, 22, 62, 0.56);

    --px-ink: #18143B;
    --px-ink-muted: #4C4670;
    --px-ink-subtle: #625C88;

    /* Primary: Coral Toy Vinyl */
    --px-primary-lit: #FF7866;
    --px-primary: #DE2B1E;
    --px-primary-belly: #B51B10;
    --px-primary-pedestal: #821008;
    --px-primary-ink: #FFFFFF;
    --px-primary-glow: rgba(230, 65, 51, 0.42);

    /* Secondary: Sky Cobalt */
    --px-secondary-lit: #52ADFF;
    --px-secondary: #146CE8;
    --px-secondary-belly: #0E50B5;
    --px-secondary-pedestal: #08347D;
    --px-secondary-ink: #FFFFFF;
    --px-secondary-glow: rgba(20, 108, 232, 0.40);

    /* Accent: Sunny Joy Gold */
    --px-accent-lit: #FFE661;
    --px-accent: #FFBF0F;
    --px-accent-belly: #E39900;
    --px-accent-pedestal: #AB6D00;
    --px-accent-ink: #261800;
    --px-accent-glow: rgba(255, 191, 15, 0.42);

    --px-brand-web-fill: #146CE8;
    --px-brand-read-fill: #DE2B1E;
    --px-brand-stroke: #FFFDF9;
    --px-link-ink: var(--px-secondary);
    --px-greeting-shadow: 0 3px 0 rgba(255, 255, 255, 0.9), 0 8px 18px rgba(28, 22, 62, 0.24);
    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(255, 226, 128, 0.58) 58%, rgba(255, 180, 56, 0.28) 100%);

    /* Semantic Feedback */
    --px-success-lit: #3DE094;
    --px-success: #117E4D;
    --px-success-glow: rgba(17, 126, 77, 0.36);
    --px-success-bg: #ECFDF5;
    --px-success-ink: #064E3B;
    --px-success-sub: #047857;

    --px-danger-lit: #FF6B76;
    --px-danger: #CF2935;
    --px-danger-pedestal: #911720;
    --px-danger-glow: rgba(207, 41, 53, 0.36);
    --px-error-bg: #FFF1F2;
    --px-error-ink: #911720;

    /* Floating Memory Orbs */
    --px-orb-1: #FF627A;
    --px-orb-2: #3DB2FF;
    --px-orb-3: #FFD028;
    --px-orb-4: #3CE6A6;
    --px-orb-5: #A77BFF;
    --px-orb-6: #FF9447;

    /* Chromatic Shadows & Lighting */
    --px-shadow-rgb: 28, 22, 62;
    --px-rim-top: rgba(255, 255, 255, 0.95);
    --px-card-border: rgba(255, 255, 255, 0.9);
    --px-well-shadow: inset 0 4px 8px rgba(var(--px-shadow-rgb), 0.12),
        inset 0 1px 2px rgba(var(--px-shadow-rgb), 0.08),
        0 2px 0 rgba(255, 255, 255, 0.88);
    --px-card-shadow:
        0 28px 54px -14px rgba(var(--px-shadow-rgb), 0.24),
        0 10px 22px -6px rgba(var(--px-shadow-rgb), 0.14),
        0 3px 6px rgba(var(--px-shadow-rgb), 0.09),
        inset 0 2px 2px var(--px-rim-top),
        inset 0 -10px 24px -8px rgba(var(--px-shadow-rgb), 0.07);
    --px-focus-ring: rgba(20, 108, 232, 0.34);
    --px-focus-stroke: #146CE8;

    --px-font-display: 'Fredoka', ui-rounded, 'SF Pro Rounded', 'Nunito', system-ui, sans-serif;
    --px-font-body: 'Nunito', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
    --px-ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
    --px-ease-settle: cubic-bezier(0.22, 1, 0.36, 1);
    --px-btn-depth: 6px;
    --px-noise-svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.55'/%3E%3C/svg%3E");

    position: relative;
    isolation: isolate;
    min-height: 100vh;
    width: 100%;
    overflow-x: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4.15rem 1.25rem 2.25rem;
    box-sizing: border-box;
    font-family: var(--px-font-body);
    color: var(--px-ink);
    background: linear-gradient(
        166deg,
        var(--px-sky-top) 0%,
        var(--px-sky-mid) 50%,
        var(--px-sky-bottom) 100%
    );
    transition: background 420ms var(--px-ease-settle);
}

.webread-page[data-pixar-theme="luca-coast"] {
    --px-sky-top: #FF955C;
    --px-sky-mid: #FFD675;
    --px-sky-bottom: #38E4DF;
    --px-sun-bloom: rgba(255, 118, 92, 0.62);
    --px-secondary-bloom: rgba(46, 229, 219, 0.42);

    --px-hill-far-1: #B482FF;
    --px-hill-far-2: #7D52E6;
    --px-hill-mid-1: #2CE6D2;
    --px-hill-mid-2: #0AB09E;
    --px-hill-near-1: #12C7AE;
    --px-hill-near-2: #067D6E;
    --px-hill-rim: rgba(180, 255, 245, 0.85);
    --px-cloud-opacity: 0.9;
    --px-sparkle-color: #FFF3B0;
    --px-wing-glow: rgba(255, 184, 108, 0.48);
    --px-card-aura: rgba(20, 212, 192, 0.38);

    --px-card-bg-top: rgba(255, 253, 247, 0.96);
    --px-card-bg-bottom: rgba(255, 240, 220, 0.93);
    --px-surface-card: #FFFDF7;
    --px-surface-card-bottom: #FFF0DC;
    --px-surface-well: #F7EBDA;
    --px-surface-glass: rgba(255, 253, 247, 0.88);
    --px-backdrop-scrim: rgba(26, 40, 66, 0.56);

    --px-ink: #182940;
    --px-ink-muted: #435773;
    --px-ink-subtle: #5B6F8C;

    /* Primary: Vespa Teal Emerald */
    --px-primary-lit: #3AE8D2;
    --px-primary: #078578;
    --px-primary-belly: #05665C;
    --px-primary-pedestal: #03453E;
    --px-primary-ink: #FFFFFF;
    --px-primary-glow: rgba(7, 133, 120, 0.42);

    /* Secondary: Amalfi Tangerine Flame */
    --px-secondary-lit: #FF9357;
    --px-secondary: #D44711;
    --px-secondary-belly: #A83307;
    --px-secondary-pedestal: #782002;
    --px-secondary-ink: #FFFFFF;
    --px-secondary-glow: rgba(212, 71, 17, 0.38);

    /* Accent: Gelato Lemon Gold */
    --px-accent-lit: #FFE86B;
    --px-accent: #FFB814;
    --px-accent-belly: #D99500;
    --px-accent-pedestal: #9E6A00;
    --px-accent-ink: #241700;
    --px-accent-glow: rgba(255, 184, 20, 0.4);

    --px-brand-web-fill: #D44711;
    --px-brand-read-fill: #078578;
    --px-brand-stroke: #FFFDF7;
    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.95) 0%, rgba(155, 245, 234, 0.58) 58%, rgba(20, 184, 166, 0.28) 100%);

    --px-orb-1: #2CE6D2;
    --px-orb-2: #FF884D;
    --px-orb-3: #FFE054;
    --px-orb-4: #4CD4FF;
    --px-orb-5: #FF6B8B;
    --px-orb-6: #A5F36B;

    --px-shadow-rgb: 26, 40, 66;
    --px-focus-ring: rgba(7, 133, 120, 0.32);
    --px-focus-stroke: #078578;
}

.webread-page[data-pixar-theme="memory-orb"] {
    color-scheme: dark;
    --px-sky-top: #1E124A;
    --px-sky-mid: #3A1F80;
    --px-sky-bottom: #672BA8;
    --px-sun-bloom: rgba(255, 92, 178, 0.44);
    --px-secondary-bloom: rgba(56, 208, 255, 0.36);

    --px-hill-far-1: #7B4DFF;
    --px-hill-far-2: #4B28A8;
    --px-hill-mid-1: #4F46E5;
    --px-hill-mid-2: #2D268A;
    --px-hill-near-1: #2C2266;
    --px-hill-near-2: #16103A;
    --px-hill-rim: rgba(105, 215, 255, 0.65);
    --px-cloud-opacity: 0.22;
    --px-sparkle-color: #8BE9FF;
    --px-wing-glow: rgba(105, 215, 255, 0.34);
    --px-card-aura: rgba(167, 123, 255, 0.42);

    --px-card-bg-top: rgba(44, 34, 92, 0.94);
    --px-card-bg-bottom: rgba(28, 21, 64, 0.94);
    --px-surface-card: #2C225C;
    --px-surface-card-bottom: #1C1540;
    --px-surface-well: #161033;
    --px-surface-well-focus: #221A4C;
    --px-surface-elevated: #362B6E;
    --px-surface-glass: rgba(39, 31, 82, 0.84);
    --px-backdrop-scrim: rgba(9, 6, 24, 0.72);

    --px-ink: #FFF9F2;
    --px-ink-muted: #D4CEFA;
    --px-ink-subtle: #B3AAE6;

    /* Primary: Memory Joy Gold */
    --px-primary-lit: #FFE76B;
    --px-primary: #FFC421;
    --px-primary-belly: #E09B00;
    --px-primary-pedestal: #A36B00;
    --px-primary-ink: #241800;
    --px-primary-glow: rgba(255, 196, 33, 0.45);

    /* Secondary: Console Cyan */
    --px-secondary-lit: #6CE0FF;
    --px-secondary: #0E84C9;
    --px-secondary-belly: #0A5F94;
    --px-secondary-pedestal: #063F66;
    --px-secondary-ink: #FFFFFF;
    --px-secondary-glow: rgba(14, 132, 201, 0.46);

    /* Accent: Core Berry Magenta */
    --px-accent-lit: #FF82BA;
    --px-accent: #D42874;
    --px-accent-belly: #A81856;
    --px-accent-pedestal: #780D3B;
    --px-accent-ink: #FFFFFF;
    --px-accent-glow: rgba(212, 40, 116, 0.44);

    --px-brand-web-fill: #6CE0FF;
    --px-brand-read-fill: #FFC421;
    --px-brand-stroke: #1B1340;
    --px-link-ink: var(--px-secondary-lit);
    --px-greeting-shadow: 0 3px 0 rgba(9, 6, 24, 0.82), 0 8px 22px rgba(108, 224, 255, 0.28);
    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.88) 0%, rgba(167, 123, 255, 0.56) 58%, rgba(255, 196, 33, 0.34) 100%);

    --px-success-bg: #0D382B;
    --px-success-ink: #D1FAE5;
    --px-success-sub: #A7F3D0;
    --px-error-bg: #4A142C;
    --px-error-ink: #FFE4EA;

    --px-orb-1: #FFD447;
    --px-orb-2: #48C9FF;
    --px-orb-3: #FF6B9E;
    --px-orb-4: #A77BFF;
    --px-orb-5: #45E69F;
    --px-orb-6: #FF9E42;

    --px-shadow-rgb: 9, 6, 24;
    --px-rim-top: rgba(255, 255, 255, 0.28);
    --px-card-border: rgba(167, 139, 250, 0.3);
    --px-well-shadow: inset 0 4px 10px rgba(4, 2, 16, 0.58),
        inset 0 1px 2px rgba(4, 2, 16, 0.44),
        0 1px 0 rgba(255, 255, 255, 0.16);
    --px-card-shadow:
        0 28px 56px -12px rgba(4, 2, 18, 0.62),
        0 10px 24px -4px rgba(4, 2, 18, 0.42),
        inset 0 1.5px 1px var(--px-rim-top),
        inset 0 -12px 24px -8px rgba(126, 94, 255, 0.22);
    --px-focus-ring: rgba(108, 224, 255, 0.4);
    --px-focus-stroke: #6CE0FF;
}

.webread-page[data-pixar-theme="marigold"] {
    color-scheme: dark;
    --px-sky-top: #29103D;
    --px-sky-mid: #571963;
    --px-sky-bottom: #8C2260;
    --px-sun-bloom: rgba(255, 174, 48, 0.48);
    --px-secondary-bloom: rgba(55, 222, 198, 0.28);

    --px-hill-far-1: #C43B96;
    --px-hill-far-2: #7D1F68;
    --px-hill-mid-1: #D93D6B;
    --px-hill-mid-2: #8C1B4A;
    --px-hill-near-1: #4A1754;
    --px-hill-near-2: #240B2E;
    --px-hill-rim: rgba(255, 198, 92, 0.68);
    --px-cloud-opacity: 0.2;
    --px-sparkle-color: #FFD269;
    --px-wing-glow: rgba(255, 174, 48, 0.36);
    --px-card-aura: rgba(245, 134, 22, 0.42);

    --px-card-bg-top: rgba(54, 27, 72, 0.94);
    --px-card-bg-bottom: rgba(36, 17, 50, 0.94);
    --px-surface-card: #361B48;
    --px-surface-card-bottom: #241132;
    --px-surface-well: #1E0D2B;
    --px-surface-well-focus: #2D1540;
    --px-surface-elevated: #45235C;
    --px-surface-glass: rgba(54, 27, 72, 0.86);
    --px-backdrop-scrim: rgba(18, 6, 26, 0.72);

    --px-ink: #FFF8F0;
    --px-ink-muted: #EBCEF0;
    --px-ink-subtle: #C9A3CF;

    /* Primary: Marigold Candle Flame */
    --px-primary-lit: #FFC054;
    --px-primary: #F58414;
    --px-primary-belly: #CC6000;
    --px-primary-pedestal: #944100;
    --px-primary-ink: #261100;
    --px-primary-glow: rgba(245, 132, 20, 0.46);

    /* Secondary: Papel Picado Pink */
    --px-secondary-lit: #FF70A8;
    --px-secondary: #D4246C;
    --px-secondary-belly: #A81651;
    --px-secondary-pedestal: #780A37;
    --px-secondary-ink: #FFFFFF;
    --px-secondary-glow: rgba(212, 36, 108, 0.44);

    /* Accent: Spirit Alebrije Mint */
    --px-accent-lit: #87FFEE;
    --px-accent: #37DEC6;
    --px-accent-belly: #1AB39D;
    --px-accent-pedestal: #0D8070;
    --px-accent-ink: #042923;
    --px-accent-glow: rgba(55, 222, 198, 0.4);

    --px-brand-web-fill: #87FFEE;
    --px-brand-read-fill: #FFC054;
    --px-brand-stroke: #29103D;
    --px-link-ink: var(--px-secondary-lit);
    --px-greeting-shadow: 0 3px 0 rgba(18, 6, 26, 0.84), 0 8px 22px rgba(255, 192, 84, 0.28);
    --px-crest-bg: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.88) 0%, rgba(255, 184, 77, 0.54) 58%, rgba(212, 36, 108, 0.32) 100%);

    --px-success-bg: #0D382B;
    --px-success-ink: #D1FAE5;
    --px-success-sub: #A7F3D0;
    --px-error-bg: #4F1530;
    --px-error-ink: #FFE4EA;

    --px-orb-1: #FFAC38;
    --px-orb-2: #FF639B;
    --px-orb-3: #49E6CE;
    --px-orb-4: #FFD66E;
    --px-orb-5: #C47DFF;
    --px-orb-6: #FF785A;

    --px-shadow-rgb: 18, 6, 26;
    --px-rim-top: rgba(255, 214, 153, 0.28);
    --px-card-border: rgba(255, 186, 112, 0.28);
    --px-well-shadow: inset 0 4px 10px rgba(10, 2, 18, 0.58),
        inset 0 1px 2px rgba(10, 2, 18, 0.44),
        0 1px 0 rgba(255, 214, 153, 0.16);
    --px-card-shadow:
        0 28px 56px -12px rgba(10, 2, 18, 0.62),
        0 10px 24px -4px rgba(10, 2, 18, 0.42),
        inset 0 1.5px 1px var(--px-rim-top),
        inset 0 -12px 24px -8px rgba(245, 134, 22, 0.2);
    --px-focus-ring: rgba(255, 192, 84, 0.4);
    --px-focus-stroke: #FFC054;
}

/* Plane 2: Dual Studio Sun & Bounce Light Blooms */
.pixar-stage::before {
    content: "";
    position: fixed;
    inset: -15% -10% auto -10%;
    height: 85vh;
    background:
        radial-gradient(ellipse 60% 52% at 20% 14%, var(--px-sun-bloom) 0%, transparent 68%),
        radial-gradient(ellipse 52% 48% at 82% 24%, var(--px-secondary-bloom) 0%, transparent 70%);
    pointer-events: none;
    z-index: -4;
}

/* Plane 5: Anti-Banding Tactile Cinema Grain */
.pixar-stage::after {
    content: "";
    position: fixed;
    inset: 0;
    background-image: var(--px-noise-svg);
    background-repeat: repeat;
    opacity: 0.032;
    pointer-events: none;
    z-index: 90;
}

/* Plane 2b: Sculpted Fluffy Storybook Clouds & Firefly Sparkles */
.pixar-cloud-field,
.pixar-sparkle-field {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: -3;
}

.pixar-cloud {
    position: absolute;
    height: auto;
    opacity: var(--px-cloud-opacity);
    filter: drop-shadow(0 12px 24px rgba(var(--px-shadow-rgb), 0.12));
    will-change: transform;
    animation: px-cloud-drift var(--_cdur, 28s) ease-in-out infinite alternate;
}

.pixar-cloud--1 { width: clamp(160px, 18vw, 250px); top: 8%; left: 10%; --_cdur: 26s; }
.pixar-cloud--2 { width: clamp(140px, 15vw, 210px); top: 14%; right: 12%; --_cdur: 32s; animation-delay: -9s; }
.pixar-cloud--3 { width: clamp(110px, 12vw, 175px); top: 34%; left: 26%; --_cdur: 24s; animation-delay: -5s; }

@keyframes px-cloud-drift {
    0% { transform: translate3d(-14px, 0, 0); }
    100% { transform: translate3d(20px, -8px, 0); }
}

.pixar-sparkle {
    position: absolute;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: radial-gradient(circle, #FFFFFF 0%, var(--px-sparkle-color) 55%, transparent 100%);
    box-shadow: 0 0 14px 3px var(--px-sparkle-color);
    will-change: transform, opacity;
    animation: px-twinkle var(--_sdur, 4.4s) ease-in-out infinite alternate;
}

.pixar-sparkle--1 { top: 16%; left: 22%; --_sdur: 3.8s; }
.pixar-sparkle--2 { top: 26%; right: 24%; --_sdur: 4.9s; animation-delay: -1.4s; }
.pixar-sparkle--3 { top: 48%; left: 18%; --_sdur: 4.2s; animation-delay: -2.2s; }
.pixar-sparkle--4 { top: 54%; right: 20%; --_sdur: 5.1s; animation-delay: -0.7s; }
.pixar-sparkle--5 { top: 18%; left: 42%; width: 7px; height: 7px; --_sdur: 3.4s; animation-delay: -1.9s; }
.pixar-sparkle--6 { bottom: 26%; right: 36%; width: 8px; height: 8px; --_sdur: 4.5s; animation-delay: -2.8s; }

@keyframes px-twinkle {
    0% { transform: translate3d(0, 6px, 0) scale(0.65); opacity: 0.35; }
    100% { transform: translate3d(4px, -10px, 0) scale(1.25); opacity: 0.95; }
}

/* Plane 3: Drifting Volumetric Memory Orbs */
.pixar-orb-field {
    position: fixed;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: -2;
}

.pixar-orb {
    position: absolute;
    border-radius: 50%;
    background:
        radial-gradient(circle at 28% 24%, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.56) 15%, transparent 38%),
        radial-gradient(circle at 58% 60%, var(--_orb-color, var(--px-orb-1)) 0%, var(--_orb-color, var(--px-orb-1)) 55%, rgba(255, 255, 255, 0.12) 100%);
    box-shadow:
        inset 0 -10px 22px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 4px 10px rgba(255, 255, 255, 0.72),
        0 18px 40px -8px rgba(var(--px-shadow-rgb), 0.24);
    opacity: 0.88;
    will-change: transform;
    animation: px-orb-float var(--_orb-dur, 18s) ease-in-out infinite alternate;
    animation-delay: var(--_orb-delay, 0s);
}

.pixar-orb--1 { width: 132px; height: 132px; top: 8%; left: 5%; --_orb-color: var(--px-orb-1); --_orb-dur: 16s; }
.pixar-orb--2 { width: 88px; height: 88px; top: 19%; right: 10%; --_orb-color: var(--px-orb-2); --_orb-dur: 13s; --_orb-delay: -4s; }
.pixar-orb--3 { width: 162px; height: 162px; bottom: 14%; left: 12%; --_orb-color: var(--px-orb-3); --_orb-dur: 21s; --_orb-delay: -7s; opacity: 0.82; }
.pixar-orb--4 { width: 104px; height: 104px; bottom: 20%; right: 14%; --_orb-color: var(--px-orb-4); --_orb-dur: 15s; --_orb-delay: -2s; }
.pixar-orb--5 { width: 54px; height: 54px; top: 38%; left: 32%; --_orb-color: var(--px-orb-5); --_orb-dur: 14s; --_orb-delay: -6s; opacity: 0.78; }
.pixar-orb--6 { width: 62px; height: 62px; top: 44%; right: 30%; --_orb-color: var(--px-orb-6); --_orb-dur: 17s; --_orb-delay: -9s; opacity: 0.8; }

@keyframes px-orb-float {
    0% {
        transform: translate3d(0, 0, 0) scale(1);
    }
    50% {
        transform: translate3d(18px, -24px, 0) scale(1.05);
    }
    100% {
        transform: translate3d(-14px, 14px, 0) scale(0.96);
    }
}

/* Plane 4: Tri-Ridge Multi-Stop Gradient Storybook Horizon Hills */
.pixar-hills {
    position: fixed;
    left: 0;
    right: 0;
    bottom: -2px;
    height: clamp(120px, 21vh, 235px);
    pointer-events: none;
    z-index: -1;
}

.pixar-hills svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* Top-Right Pixar World Marbles & Foley Utility Bar */
.top-utility-bar {
    position: fixed;
    top: 0.9rem;
    right: 1.15rem;
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.pixar-world-picker {
    display: inline-flex;
    align-items: center;
    gap: 0.42rem;
    padding: 0.36rem 0.72rem;
    border-radius: 999px;
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-glass);
    backdrop-filter: blur(14px) saturate(145%);
    -webkit-backdrop-filter: blur(14px) saturate(145%);
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb), 0.16),
        0 10px 20px -6px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 2px var(--px-rim-top);
}

.world-picker-label {
    font-family: var(--px-font-display);
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: var(--px-ink);
    padding-right: 0.15rem;
}

.world-swatch-btn {
    position: relative;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.9);
    padding: 0;
    cursor: pointer;
    box-shadow:
        inset 0 2px 3px rgba(255, 255, 255, 0.82),
        0 3px 6px rgba(var(--px-shadow-rgb), 0.24);
    transition: transform 220ms var(--px-ease-spring), box-shadow 200ms ease;
}

.world-swatch-btn:hover {
    transform: translate3d(0, -2px, 0) scale(1.16);
}

.world-swatch-btn:active {
    transform: scale(0.92);
}

.world-swatch-btn.is-active {
    transform: scale(1.18);
    box-shadow:
        0 0 0 2.5px var(--px-ink),
        0 4px 10px rgba(var(--px-shadow-rgb), 0.28),
        inset 0 2px 3px rgba(255, 255, 255, 0.85);
}

.world-swatch-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.pixar-foley-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.48rem 0.95rem;
    border-radius: 999px;
    border: 1.5px solid var(--px-card-border);
    background: var(--px-surface-glass);
    backdrop-filter: blur(14px) saturate(145%);
    -webkit-backdrop-filter: blur(14px) saturate(145%);
    color: var(--px-ink);
    font-family: var(--px-font-display);
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow:
        0 4px 0 rgba(var(--px-shadow-rgb), 0.16),
        0 10px 20px -6px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 2px var(--px-rim-top);
    transition: transform 200ms var(--px-ease-spring), box-shadow 180ms var(--px-ease-settle);
}

.pixar-foley-btn:hover {
    transform: translate3d(0, -2px, 0) scale(1.03);
}

.pixar-foley-btn:active {
    transform: translate3d(0, 2px, 0) scaleX(1.03) scaleY(0.96);
    box-shadow:
        0 2px 0 rgba(var(--px-shadow-rgb), 0.16),
        0 4px 10px -4px rgba(var(--px-shadow-rgb), 0.14),
        inset 0 2px 2px var(--px-rim-top);
}

.pixar-foley-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

.foley-svg {
    width: 16px;
    height: 16px;
    color: var(--px-link-ink);
}

/* ==========================================================================
   3D POP-UP SCENE WINGS (LEFT & RIGHT DIORAMAS + BACKLIGHT HALO)
   ========================================================================== */
.scene-wing {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 31vw;
    max-width: 450px;
    z-index: 2;
    pointer-events: none;
    transition: transform 240ms var(--px-ease-settle), opacity 0.3s ease;
}

.scene-wing::before {
    content: "";
    position: absolute;
    inset: 14% 4% 8% 4%;
    border-radius: 50%;
    background: radial-gradient(circle at 50% 52%, var(--px-wing-glow) 0%, transparent 70%);
    pointer-events: none;
}

.wing-left {
    left: 0;
}

.wing-right {
    right: 0;
}

.asset-pop {
    position: absolute;
    height: auto;
    display: block;
    user-select: none;
    pointer-events: none;
    filter: drop-shadow(0 16px 24px rgba(var(--px-shadow-rgb), 0.18));
}

.asset-book-left {
    top: 7%;
    left: 4%;
    width: 72%;
    max-width: 300px;
    animation: floatBookLeft 7s ease-in-out infinite;
}

.asset-boy-laptop {
    top: 39%;
    right: 6%;
    width: 48%;
    max-width: 195px;
    animation: floatBoy 6.2s ease-in-out infinite 0.4s;
}

.diorama-floor-anchor {
    position: absolute;
    bottom: 0;
    width: 78%;
    max-width: 330px;
    pointer-events: none;
}

.floor-left {
    left: 3%;
}

.floor-right {
    right: 3%;
}

.diorama-contact-shadow {
    position: absolute;
    left: 12%;
    right: 12%;
    bottom: 10px;
    height: 22px;
    border-radius: 50%;
    background: radial-gradient(ellipse at center, rgba(var(--px-shadow-rgb), 0.24) 0%, transparent 72%);
}

.asset-girl-reading {
    position: relative;
    width: 100%;
    animation: floatGirlLeft 6.6s ease-in-out infinite 0.8s;
}

.asset-book-right {
    top: 7%;
    right: 5%;
    width: 72%;
    max-width: 300px;
    animation: floatBookRight 7.2s ease-in-out infinite 0.2s;
}

.asset-book-laptop-girl {
    top: 38%;
    left: 5%;
    width: 68%;
    max-width: 280px;
    animation: floatBookLaptop 6.5s ease-in-out infinite 0.7s;
}

.asset-girl-tablet {
    position: relative;
    width: 100%;
    animation: floatGirlRight 6s ease-in-out infinite 1.2s;
}

@keyframes floatBookLeft {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -12px, 0) rotate(-1.5deg); }
}

@keyframes floatBoy {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -10px, 0) rotate(1.2deg); }
}

@keyframes floatGirlLeft {
    0%, 100% { transform: translate3d(0, 0, 0); }
    50% { transform: translate3d(0, -8px, 0); }
}

@keyframes floatBookRight {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -12px, 0) rotate(1.5deg); }
}

@keyframes floatBookLaptop {
    0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
    50% { transform: translate3d(0, -10px, 0) rotate(-1.2deg); }
}

@keyframes floatGirlRight {
    0%, 100% { transform: translate3d(0, 0, 0); }
    50% { transform: translate3d(0, -8px, 0); }
}

/* ==========================================================================
   AMBIENT FLOATING GEARS & STARS
   ========================================================================== */
.ambient-decorations {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
}

.float-gear {
    position: absolute;
    filter: drop-shadow(0 8px 12px rgba(var(--px-shadow-rgb), 0.14));
}

.gear-1 {
    top: 11%;
    left: 27%;
    animation: spinSlow 20s linear infinite;
}

.gear-2 {
    top: 9%;
    right: 27%;
    animation: spinReverse 24s linear infinite;
}

.float-star {
    position: absolute;
    filter: drop-shadow(0 6px 10px rgba(255, 190, 26, 0.42));
    animation: pulseStar 3.2s ease-in-out infinite alternate;
}

.star-1 { top: 6%; left: 48%; animation-delay: 0.2s; }
.star-2 { bottom: 14%; left: 31%; animation-delay: 1.2s; }
.star-3 { bottom: 13%; right: 31%; animation-delay: 0.8s; }

@keyframes spinSlow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes spinReverse {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}

@keyframes pulseStar {
    0% { transform: scale(0.88) rotate(0deg); opacity: 0.78; }
    100% { transform: scale(1.14) rotate(14deg); opacity: 1; }
}

/* ==========================================================================
   CONFETTI CONTAINER (GPU TRANSFORM ONLY)
   ========================================================================== */
.confetti-container {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 140;
    overflow: hidden;
}

.confetti-item {
    position: fixed;
    top: -20px;
    box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.75);
    animation: confettiFall 2.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

.confetti-item.circle {
    border-radius: 50%;
}

.confetti-item.rect {
    border-radius: 3px;
}

@keyframes confettiFall {
    0% {
        opacity: 1;
        transform: translate3d(0, 0, 0) rotate(var(--confetti-rot, 0deg));
    }
    100% {
        opacity: 0;
        transform: translate3d(0, 108vh, 0) rotate(calc(var(--confetti-rot, 0deg) + 720deg));
    }
}

/* ==========================================================================
   CENTRAL CORE COLUMN & HEADER
   ========================================================================== */
.login-core {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 410px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0 auto;
}

.webread-header {
    text-align: center;
    margin-bottom: 2.55rem;
}

.welcome-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.34rem 1.05rem;
    border-radius: 999px;
    background: var(--px-surface-glass);
    border: 1.5px solid var(--px-card-border);
    backdrop-filter: blur(12px) saturate(140%);
    -webkit-backdrop-filter: blur(12px) saturate(140%);
    box-shadow:
        0 8px 20px -6px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 2px var(--px-rim-top);
    margin-bottom: 0.38rem;
}

.welcome-row {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    margin-bottom: 0;
}

.welcome-text {
    font-family: var(--px-font-display);
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--px-ink);
    letter-spacing: -0.01em;
}

.mini-3d-books,
.mini-gears {
    display: inline-flex;
    align-items: center;
    filter: drop-shadow(0 4px 6px rgba(var(--px-shadow-rgb), 0.18));
}

.brand-title {
    font-family: var(--px-font-display);
    font-size: clamp(2.8rem, 5.2vw, 3.75rem);
    font-weight: 900;
    line-height: 1;
    margin: 0.15rem 0 0;
    letter-spacing: -0.015em;
    user-select: none;
}

.brand-web {
    color: var(--px-brand-web-fill);
    -webkit-text-stroke: 6px var(--px-brand-stroke);
    paint-order: stroke fill;
    text-shadow:
        0 5px 0 var(--px-secondary-pedestal),
        0 12px 22px var(--px-secondary-glow);
}

.brand-read {
    color: var(--px-brand-read-fill);
    -webkit-text-stroke: 6px var(--px-brand-stroke);
    paint-order: stroke fill;
    text-shadow:
        0 5px 0 var(--px-primary-pedestal),
        0 12px 22px var(--px-primary-glow);
}

/* ==========================================================================
   VOLUMETRIC CLAY DIORAMA LOGIN CARD + FLOATING MASCOT CREST
   ========================================================================== */
.pixar-login-card {
    position: relative;
    width: 100%;
    border-radius: 34px;
    padding: 3.15rem 1.85rem 1.75rem;
    box-sizing: border-box;
    transform-style: preserve-3d;
    background: linear-gradient(
        172deg,
        var(--px-card-bg-top) 0%,
        var(--px-card-bg-bottom) 100%
    );
    backdrop-filter: blur(18px) saturate(145%);
    -webkit-backdrop-filter: blur(18px) saturate(145%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        var(--px-card-shadow),
        0 0 46px -10px var(--px-card-aura);
    transition:
        background 360ms var(--px-ease-settle),
        transform 280ms var(--px-ease-settle),
        box-shadow 280ms var(--px-ease-settle);
}

/* Soft top specular rim sheen */
.pixar-login-card::before {
    content: "";
    position: absolute;
    inset: 6px 24px auto 24px;
    height: 36px;
    border-radius: 999px;
    background: linear-gradient(
        180deg,
        var(--px-rim-top) 0%,
        rgba(255, 255, 255, 0) 100%
    );
    opacity: 0.72;
    pointer-events: none;
}

/* Overlapping Floating 3D Mascot Orb Crest */
.pixar-card-crest {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translate(-50%, -52%) translateZ(26px);
    width: 84px;
    height: 84px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: var(--px-crest-bg);
    border: 3px solid var(--px-card-border);
    box-shadow:
        0 14px 30px -4px var(--px-accent-glow),
        0 6px 14px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 2px 3px rgba(255, 255, 255, 0.92);
    pointer-events: none;
    transition: transform 320ms var(--px-ease-spring), box-shadow 320ms ease;
}

.pixar-card-crest.crest-teacher {
    box-shadow:
        0 14px 30px -4px var(--px-primary-glow),
        0 6px 14px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 2px 3px rgba(255, 255, 255, 0.92);
}

.pixar-card-crest.crest-happy {
    transform: translate(-50%, -58%) translateZ(34px) scale(1.08);
}

.mascot-svg {
    display: block;
}

.mascot-eyes-blink {
    transform-box: fill-box;
    transform-origin: center;
    animation: px-mascot-blink 4.6s ease-in-out infinite;
}

@keyframes px-mascot-blink {
    0%, 92%, 100% {
        transform: scaleY(1);
    }
    96% {
        transform: scaleY(0.12);
    }
}

.pixar-login-card.card-shake {
    animation: px-wiggle 480ms var(--px-ease-spring) both;
}

@keyframes px-wiggle {
    0%, 100% { transform: translate3d(0, 0, 0); }
    22% { transform: translate3d(-7px, 0, 0); }
    48% { transform: translate3d(6px, 0, 0); }
    74% { transform: translate3d(-3px, 0, 0); }
}

/* ==========================================================================
   ALERTS / BANNERS
   ========================================================================== */
.error-banner {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    background: var(--px-error-bg);
    border: 2px solid var(--px-danger-lit);
    box-shadow:
        0 4px 0 var(--px-danger),
        0 8px 16px -4px var(--px-danger-glow);
    border-radius: 18px;
    padding: 0.58rem 0.95rem;
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--px-error-ink);
    margin-bottom: 1.05rem;
    animation: popIn 260ms var(--px-ease-spring);
}

.alert-svg-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: var(--px-danger-lit);
}

.success-banner {
    text-align: center;
    background: var(--px-success-bg);
    border: 2px solid var(--px-success-lit);
    box-shadow:
        0 6px 0 var(--px-success),
        0 12px 22px -6px var(--px-success-glow);
    border-radius: 22px;
    padding: 1.15rem 1rem;
    margin-bottom: 1.15rem;
    animation: popIn 300ms var(--px-ease-spring);
}

.success-badge-orb {
    width: 44px;
    height: 44px;
    margin: 0 auto 0.45rem;
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: #FFFFFF;
    background: radial-gradient(circle at 32% 28%, var(--px-success-lit) 0%, var(--px-success) 75%);
    box-shadow: 0 5px 12px var(--px-success-glow), inset 0 2px 2px rgba(255, 255, 255, 0.7);
}

.success-title {
    font-family: var(--px-font-display);
    font-size: 1.12rem;
    font-weight: 800;
    color: var(--px-success-ink);
    margin-bottom: 0.2rem;
}

.success-sub {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--px-success-sub);
    margin-bottom: 0.75rem;
}

@keyframes popIn {
    0% { transform: scale(0.92); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

/* ==========================================================================
   CARVED RECESSED CLAY FORM WELLS & TACTILE CONTROLS
   ========================================================================== */
.login-form,
.modal-form {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.pixar-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.pixar-label {
    font-family: var(--px-font-display);
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--px-ink);
    padding-inline: 0.35rem;
}

.pixar-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.pixar-input-icon {
    position: absolute;
    left: 0.95rem;
    display: inline-flex;
    color: var(--px-ink-muted);
    pointer-events: none;
    transition: transform 280ms var(--px-ease-spring), color 180ms ease;
}

.pixar-input-wrap:focus-within .pixar-input-icon {
    transform: scale(1.16) translateY(-1px);
    color: var(--px-focus-stroke);
}

.pixar-input {
    width: 100%;
    min-height: 48px;
    padding: 0.72rem 1rem 0.72rem 2.75rem;
    border-radius: 18px;
    border: 2px solid transparent;
    font-family: var(--px-font-body);
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--px-ink);
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    box-sizing: border-box;
    transition:
        background-color 200ms ease,
        border-color 200ms ease,
        box-shadow 220ms var(--px-ease-settle),
        transform 220ms var(--px-ease-spring);
}

.pixar-input--password {
    padding-right: 3rem;
}

.pixar-input::placeholder {
    color: var(--px-ink-subtle);
    font-weight: 600;
}

.pixar-input:focus {
    outline: none;
    background: var(--px-surface-well-focus);
    border-color: var(--px-focus-stroke);
    transform: translateY(-1px);
    box-shadow:
        0 0 0 4px var(--px-focus-ring),
        0 10px 20px -8px rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.06);
}

.pixar-field.is-invalid .pixar-input {
    border-color: var(--px-danger);
    box-shadow:
        0 0 0 4px var(--px-danger-glow),
        var(--px-well-shadow);
    animation: px-wiggle 380ms var(--px-ease-spring);
}

.pill-eye-toggle {
    position: absolute;
    right: 0.65rem;
    width: 34px;
    height: 34px;
    background: transparent;
    border: 0;
    border-radius: 50%;
    color: var(--px-ink-muted);
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: transform 180ms var(--px-ease-spring), color 160ms ease, background-color 160ms ease;
}

.pill-eye-toggle:hover {
    color: var(--px-ink);
    background: rgba(var(--px-shadow-rgb), 0.06);
    transform: scale(1.1);
}

.pill-eye-toggle:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

/* Custom Tactile Pixar Checkbox */
.remember-row {
    display: flex;
    align-items: center;
    padding-inline: 0.25rem;
}

.pixar-check {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    cursor: pointer;
    user-select: none;
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.pixar-check__input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.pixar-check__box {
    width: 24px;
    height: 24px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: var(--px-surface-well);
    box-shadow: var(--px-well-shadow);
    transition:
        transform 260ms var(--px-ease-spring),
        background 200ms ease,
        box-shadow 200ms ease;
}

.pixar-check__box svg {
    width: 14px;
    height: 14px;
    stroke: var(--px-secondary-ink);
    stroke-width: 3.2;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
    stroke-dasharray: 24;
    stroke-dashoffset: 24;
    transition: stroke-dashoffset 260ms var(--px-ease-settle) 60ms;
}

.pixar-check__input:checked + .pixar-check__box {
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    box-shadow:
        0 3px 0 var(--px-secondary-pedestal),
        0 6px 12px var(--px-secondary-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.65);
    transform: scale(1.06);
}

.pixar-check__input:checked + .pixar-check__box svg {
    stroke-dashoffset: 0;
}

.pixar-check__input:focus-visible + .pixar-check__box {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

/* ==========================================================================
   ZERO-LAYOUT-SHIFT SCULPTED TOY-VINYL BUTTONS
   ========================================================================== */
.btn-container {
    display: flex;
    justify-content: center;
    margin-top: 0.2rem;
}

.pixar-btn {
    --_lit: var(--px-primary-lit);
    --_base: var(--px-primary);
    --_belly: var(--px-primary-belly);
    --_pedestal: var(--px-primary-pedestal);
    --_ink: var(--px-primary-ink);
    --_glow: var(--px-primary-glow);
    --_depth: var(--px-btn-depth);

    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    min-height: 46px;
    padding: 0.72rem 1.55rem;
    margin-bottom: var(--_depth);
    border: 0;
    border-radius: 999px;
    font-family: var(--px-font-display);
    font-size: 0.96rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.1;
    color: var(--_ink);
    cursor: pointer;
    user-select: none;
    overflow: hidden;
    background: linear-gradient(180deg, var(--_lit) 0%, var(--_base) 46%, var(--_belly) 100%);
    box-shadow:
        0 var(--_depth) 0 var(--_pedestal),
        0 calc(var(--_depth) + 10px) 22px -6px var(--_glow),
        0 3px 6px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 2px 2px rgba(255, 255, 255, 0.62),
        inset 0 -5px 10px rgba(var(--px-shadow-rgb), 0.16);
    transform: translate3d(0, 0, 0) scale(1);
    transition:
        transform 240ms var(--px-ease-spring),
        box-shadow 200ms var(--px-ease-settle),
        filter 180ms ease;
}

.pixar-btn::before {
    content: "";
    position: absolute;
    inset: 3px 8px auto 8px;
    height: 44%;
    border-radius: inherit;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.48) 0%, rgba(255, 255, 255, 0.04) 100%);
    pointer-events: none;
}

.pixar-btn::after {
    content: "";
    position: absolute;
    top: -20%;
    left: -45%;
    width: 36%;
    height: 140%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.34) 50%, transparent 100%);
    transform: skewX(-24deg) translateX(-120%);
    transition: transform 520ms var(--px-ease-settle);
    pointer-events: none;
}

.pixar-btn:hover:not(:disabled) {
    transform: translate3d(0, -2px, 0) scale(1.025);
    box-shadow:
        0 calc(var(--_depth) + 2px) 0 var(--_pedestal),
        0 calc(var(--_depth) + 16px) 28px -5px var(--_glow),
        0 5px 10px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 3px rgba(255, 255, 255, 0.72),
        inset 0 -5px 10px rgba(var(--px-shadow-rgb), 0.14);
}

.pixar-btn:hover:not(:disabled)::after {
    transform: skewX(-24deg) translateX(460%);
}

.pixar-btn:active:not(:disabled) {
    transform: translate3d(0, calc(var(--_depth) - 2px), 0) scaleX(1.035) scaleY(0.95);
    box-shadow:
        0 2px 0 var(--_pedestal),
        0 4px 10px -2px var(--_glow),
        0 1px 3px rgba(var(--px-shadow-rgb), 0.22),
        inset 0 2px 2px rgba(255, 255, 255, 0.48),
        inset 0 -3px 6px rgba(var(--px-shadow-rgb), 0.2);
    transition-duration: 75ms;
}

.pixar-btn:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 4px;
}

.pixar-btn:disabled {
    cursor: not-allowed;
    filter: grayscale(0.45) saturate(0.65);
    opacity: 0.76;
    transform: translate3d(0, 2px, 0);
}

.pixar-btn--secondary {
    --_lit: var(--px-secondary-lit);
    --_base: var(--px-secondary);
    --_belly: var(--px-secondary-belly);
    --_pedestal: var(--px-secondary-pedestal);
    --_ink: var(--px-secondary-ink);
    --_glow: var(--px-secondary-glow);
}

.pixar-btn--accent {
    --_lit: var(--px-accent-lit);
    --_base: var(--px-accent);
    --_belly: var(--px-accent-belly);
    --_pedestal: var(--px-accent-pedestal);
    --_ink: var(--px-accent-ink);
    --_glow: var(--px-accent-glow);
}

.pixar-btn--clay {
    --_lit: #FFFFFF;
    --_base: #FFFDF9;
    --_belly: #EAE4FC;
    --_pedestal: rgba(var(--px-shadow-rgb), 0.24);
    --_ink: #18143B;
    --_glow: rgba(var(--px-shadow-rgb), 0.14);
}

.pixar-btn--sm {
    --_depth: 4px;
    min-height: 42px;
    padding: 0.55rem 1.1rem;
    font-size: 0.88rem;
}

.pixar-btn--lg {
    --_depth: 6px;
    min-height: 52px;
    padding: 0.85rem 2rem;
    font-size: 1.02rem;
}

.pixar-btn[aria-busy="true"] .pixar-btn__label {
    opacity: 0;
}

.pixar-btn[aria-busy="true"]::after {
    content: "•••";
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    width: auto;
    height: auto;
    background: none;
    transform: none;
    font-size: 1.35rem;
    letter-spacing: 0.2em;
    animation: px-pulse-dots 780ms ease-in-out infinite alternate;
}

@keyframes px-pulse-dots {
    from { transform: scale(0.92); opacity: 0.72; }
    to { transform: scale(1.1); opacity: 1; }
}

.btn-login-cta {
    width: 100%;
    letter-spacing: 0.05em;
}

.btn-signup-full {
    width: 100%;
}

.btn-svg-icon {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.teacher-icon {
    color: var(--px-primary);
}

/* ==========================================================================
   SIGNUP DIVIDER & BUTTON GROUP
   ========================================================================== */
.auth-divider {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0.45rem 0 0.2rem;
}

.divider-line {
    flex: 1;
    height: 1.5px;
    background: rgba(var(--px-shadow-rgb), 0.14);
    border-radius: 999px;
}

.divider-text {
    font-family: var(--px-font-display);
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--px-ink-subtle);
    letter-spacing: 0.06em;
}

.signup-buttons-container {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    width: 100%;
}

/* ==========================================================================
   FOOTER: PIXAR SLIDING ROLE DOCK & DEMO BAR
   ========================================================================== */
.webread-footer {
    margin-top: 1.45rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    text-align: center;
}

.greeting-text {
    font-family: var(--px-font-display);
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--px-ink);
    letter-spacing: 0.04em;
    line-height: 1.08;
    text-shadow: var(--px-greeting-shadow);
}

.pixar-dock {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem;
    border-radius: 999px;
    background: var(--px-surface-glass);
    border: 1.5px solid var(--px-card-border);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    box-shadow:
        0 14px 28px -10px rgba(var(--px-shadow-rgb), 0.2),
        inset 0 1.5px 1px var(--px-rim-top);
}

.pixar-dock__indicator {
    position: absolute;
    top: 0.4rem;
    left: 0;
    height: calc(100% - 0.8rem);
    border-radius: 999px;
    background: linear-gradient(180deg, var(--px-secondary-lit) 0%, var(--px-secondary) 100%);
    box-shadow:
        0 4px 0 var(--px-secondary-pedestal),
        0 8px 16px -4px var(--px-secondary-glow),
        inset 0 2px 2px rgba(255, 255, 255, 0.55);
    transition:
        transform 360ms var(--px-ease-spring),
        width 360ms var(--px-ease-spring);
    pointer-events: none;
    z-index: 0;
}

.pixar-dock__tab {
    position: relative;
    z-index: 1;
    border: 0;
    background: transparent;
    padding: 0.52rem 1.05rem;
    border-radius: 999px;
    font-family: var(--px-font-display);
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--px-ink);
    cursor: pointer;
    transition: color 220ms ease;
}

.pixar-dock__tab[aria-selected="true"] {
    color: var(--px-secondary-ink);
}

.pixar-dock__tab:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.demo-pills-bar {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 0.48rem;
    flex-wrap: wrap;
    padding: 0.42rem 0.78rem;
    border-radius: 999px;
    background: var(--px-surface-glass);
    border: 1.5px solid var(--px-card-border);
    backdrop-filter: blur(12px) saturate(140%);
    -webkit-backdrop-filter: blur(12px) saturate(140%);
    box-shadow: 0 10px 22px -8px rgba(var(--px-shadow-rgb), 0.22);
}

.demo-pills-label {
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--px-ink);
}

.demo-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: var(--px-surface-elevated);
    border: 1.5px solid rgba(var(--px-shadow-rgb), 0.14);
    color: var(--px-ink);
    font-family: var(--px-font-display);
    font-size: 0.76rem;
    font-weight: 700;
    padding: 0.32rem 0.75rem;
    border-radius: 999px;
    cursor: pointer;
    box-shadow: 0 3px 0 rgba(var(--px-shadow-rgb), 0.12);
    transition: transform 180ms var(--px-ease-spring), border-color 180ms ease, color 180ms ease;
}

.demo-pill:hover {
    border-color: var(--px-link-ink);
    color: var(--px-link-ink);
    transform: translate3d(0, -2px, 0);
}

.demo-pill:active {
    transform: translate3d(0, 1px, 0) scale(0.97);
}

.demo-pill:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.demo-pill-svg {
    width: 14px;
    height: 14px;
}

/* ==========================================================================
   STORYBOOK NATIVE <dialog> MODAL WITH OVERLAPPING CREST
   ========================================================================== */
dialog.pixar-modal {
    position: fixed;
    inset: 0;
    margin: auto;
    padding: 0;
    width: min(460px, calc(100vw - 2rem));
    max-height: calc(100dvh - 3rem);
    overflow: visible;
    border: 0;
    background: transparent;
    color: var(--px-ink);
}

dialog.pixar-modal::backdrop {
    background: var(--px-backdrop-scrim);
    backdrop-filter: blur(10px) saturate(135%);
    -webkit-backdrop-filter: blur(10px) saturate(135%);
    animation: px-backdrop-in 260ms ease forwards;
}

.pixar-modal__shell {
    position: relative;
    border-radius: 32px;
    padding: 2.95rem 1.85rem 1.75rem;
    background: linear-gradient(176deg, var(--px-surface-elevated) 0%, var(--px-surface-card-bottom) 100%);
    border: 2px solid var(--px-card-border);
    box-shadow:
        0 32px 64px -16px rgba(var(--px-shadow-rgb), 0.45),
        0 12px 24px -8px rgba(var(--px-shadow-rgb), 0.25),
        inset 0 2px 3px var(--px-rim-top);
}

.pixar-modal__crest {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translate(-50%, -52%);
    width: 74px;
    height: 74px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    color: var(--px-accent-ink);
    background: radial-gradient(
        circle at 32% 28%,
        #FFFFFF 0%,
        var(--px-accent-lit) 22%,
        var(--px-accent) 68%,
        var(--px-accent-pedestal) 100%
    );
    box-shadow:
        0 14px 26px -6px var(--px-accent-glow),
        0 6px 12px rgba(var(--px-shadow-rgb), 0.28),
        inset 0 3px 4px rgba(255, 255, 255, 0.85);
}

.pixar-modal__close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    cursor: pointer;
    display: grid;
    place-items: center;
    font-family: var(--px-font-display);
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--px-ink);
    background: var(--px-surface-well);
    box-shadow:
        0 3px 0 rgba(var(--px-shadow-rgb), 0.16),
        inset 0 2px 2px var(--px-rim-top);
    transition: transform 200ms var(--px-ease-spring), background-color 180ms ease;
}

.pixar-modal__close:hover {
    transform: scale(1.1) rotate(90deg);
}

.pixar-modal__close:active {
    transform: translateY(2px) scale(0.94);
}

.pixar-modal__close:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 3px;
}

dialog.pixar-modal[open] .pixar-modal__shell {
    animation: px-modal-spring 480ms var(--px-ease-spring) forwards;
}

dialog.pixar-modal.is-closing .pixar-modal__shell {
    animation: px-modal-out 220ms var(--px-ease-settle) forwards;
}

dialog.pixar-modal.is-closing::backdrop {
    animation: px-backdrop-out 220ms ease forwards;
}

dialog.pixar-modal[open] [data-pixar-stagger] {
    opacity: 0;
    transform: translate3d(0, 12px, 0) scale(0.96);
    animation: px-stagger-in 380ms var(--px-ease-spring) forwards;
    animation-delay: calc(var(--stagger-i, 0) * 55ms + 90ms);
}

@keyframes px-modal-spring {
    0% { opacity: 0; transform: translate3d(0, 34px, 0) scale(0.84, 1.12); }
    55% { opacity: 1; transform: translate3d(0, -6px, 0) scale(1.03, 0.97); }
    100% { opacity: 1; transform: translate3d(0, 0, 0) scale(1, 1); }
}

@keyframes px-modal-out {
    from { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
    to { opacity: 0; transform: translate3d(0, 22px, 0) scale(0.92); }
}

@keyframes px-backdrop-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes px-backdrop-out {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes px-stagger-in {
    to { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
}

.modal-header {
    text-align: center;
    margin-bottom: 1rem;
}

.modal-role-pill-bar {
    display: inline-flex;
    background: var(--px-surface-well);
    border-radius: 999px;
    padding: 0.25rem;
    gap: 0.25rem;
    margin-bottom: 0.7rem;
    box-shadow: var(--px-well-shadow);
}

.modal-role-pill {
    background: transparent;
    border: 0;
    font-family: var(--px-font-display);
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--px-ink-muted);
    padding: 0.36rem 0.95rem;
    border-radius: 999px;
    cursor: pointer;
    transition: all 200ms var(--px-ease-spring);
}

.modal-role-pill.active {
    background: var(--px-surface-elevated);
    color: var(--px-ink);
    box-shadow: 0 3px 8px rgba(var(--px-shadow-rgb), 0.14);
}

.modal-role-pill:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

.modal-title {
    font-family: var(--px-font-display);
    font-size: 1.48rem;
    font-weight: 900;
    color: var(--px-ink);
    margin: 0 0 0.3rem;
    line-height: 1.15;
}

.modal-sub {
    font-size: 0.84rem;
    color: var(--px-ink-muted);
    font-weight: 700;
    margin: 0;
    line-height: 1.4;
}

.btn-modal-submit {
    width: 100%;
}

.modal-footer-switch {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--px-ink-muted);
}

.link-back-login {
    background: none;
    border: 0;
    color: var(--px-link-ink);
    font-family: var(--px-font-display);
    font-weight: 800;
    font-size: 0.86rem;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
    padding: 0.15rem 0.25rem;
    border-radius: 6px;
}

.link-back-login:focus-visible {
    outline: 3px solid var(--px-focus-stroke);
    outline-offset: 2px;
}

/* ==========================================================================
   RESPONSIVENESS & REDUCED MOTION
   ========================================================================== */
@media (max-width: 1120px) {
    .scene-wing {
        width: 24vw;
        opacity: 0.78;
    }
}

@media (max-width: 920px) {
    .scene-wing {
        display: none;
    }

    .float-gear,
    .float-star {
        opacity: 0.55;
    }
}

@media (max-width: 480px) {
    .webread-page {
        padding: 4.5rem 1rem 2rem;
    }

    .top-utility-bar {
        right: 0.75rem;
        left: 0.75rem;
        justify-content: space-between;
    }

    .world-picker-label {
        display: none;
    }

    .pixar-login-card {
        padding: 2.85rem 1.3rem 1.45rem;
        border-radius: 28px;
    }

    .greeting-text {
        font-size: 1.55rem;
    }

    .pixar-dock__tab {
        padding: 0.46rem 0.85rem;
        font-size: 0.82rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }

    .pixar-orb,
    .pixar-cloud,
    .pixar-sparkle,
    .mascot-eyes-blink {
        animation: none !important;
    }
}
</style>
