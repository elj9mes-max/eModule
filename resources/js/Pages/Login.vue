<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';

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

// Form state matching the WebRead design
const role = ref('student'); // 'student' | 'teacher'
const username = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);
const loginStatus = ref('idle'); // 'idle' | 'submitting' | 'success' | 'error'
const errorMessage = ref('');
const cardShaking = ref(false);
const confettiPieces = ref([]);

const toggleRole = () => {
    role.value = role.value === 'student' ? 'teacher' : 'student';
    if (loginStatus.value === 'error') {
        loginStatus.value = 'idle';
        errorMessage.value = '';
    }
};

const fillDemo = (targetRole) => {
    if (targetRole === 'teacher') {
        username.value = 'teacher@example.com';
        password.value = 'password';
        role.value = 'teacher';
    } else {
        username.value = 'student@example.com';
        password.value = 'password';
        role.value = 'student';
    }
    if (loginStatus.value === 'error') {
        loginStatus.value = 'idle';
        errorMessage.value = '';
    }
};

const triggerError = (msg) => {
    errorMessage.value = msg || 'Please check your login details and try again.';
    loginStatus.value = 'error';
    cardShaking.value = true;
    setTimeout(() => {
        cardShaking.value = false;
    }, 600);
};

const spawnConfetti = () => {
    const colors = ['#2395EB', '#FF6B4A', '#FFD166', '#06D6A0', '#FF6584', '#6C5CE7'];
    const pieces = [];
    for (let i = 0; i < 40; i++) {
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
        triggerError('Please enter your Username or Email.');
        return;
    }
    if (!password.value) {
        triggerError('Please enter your Password.');
        return;
    }

    loginStatus.value = 'submitting';
    errorMessage.value = '';

    router.post('/login', {
        username: username.value,
        password: password.value,
        remember: rememberMe.value,
    }, {
        onError: (errors) => {
            const firstError = Object.values(errors)[0] || 'Invalid credentials. Please try again.';
            triggerError(firstError);
        },
        onSuccess: () => {
            loginStatus.value = 'success';
            spawnConfetti();
        },
        onFinish: () => {
            if (loginStatus.value !== 'success') {
                loginStatus.value = 'idle';
            }
        },
    });
};

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
    showRegisterModal.value = true;
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
    registerErrorMessage.value = '';
    registerStatus.value = 'idle';
};

const setRegisterRole = (targetRole) => {
    registerRole.value = targetRole;
    registerForm.value.role = targetRole;
};

const handleRegisterSubmit = () => {
    if (!registerForm.value.name.trim()) {
        registerErrorMessage.value = 'Please enter your full name.';
        registerStatus.value = 'error';
        return;
    }
    if (!registerForm.value.email.trim()) {
        registerErrorMessage.value = 'Please enter your email address.';
        registerStatus.value = 'error';
        return;
    }
    if (!registerForm.value.password) {
        registerErrorMessage.value = 'Please enter a password.';
        registerStatus.value = 'error';
        return;
    }
    if (registerForm.value.password.length < 8) {
        registerErrorMessage.value = 'Password must be at least 8 characters.';
        registerStatus.value = 'error';
        return;
    }
    if (registerForm.value.password !== registerForm.value.password_confirmation) {
        registerErrorMessage.value = 'Password confirmation does not match.';
        registerStatus.value = 'error';
        return;
    }

    registerStatus.value = 'submitting';
    registerErrorMessage.value = '';

    router.post('/register', registerForm.value, {
        onError: (errors) => {
            registerStatus.value = 'error';
            registerErrorMessage.value = Object.values(errors)[0] || 'Registration failed. Please check your inputs.';
        },
        onSuccess: () => {
            showRegisterModal.value = false;
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
    showPassword.value = false;
};
</script>

<template>
    <Head title="Welcome to WEBREAD • Learning Portal" />

    <div class="webread-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <!-- Confetti Particle Burst Container -->
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
                    transform: `rotate(${piece.rotation}deg)`,
                }"
            ></div>
        </div>

        <!-- 3D Pop-Up World Flanking Scenes (Left & Right Wings with 6 Cutout Assets) -->
        <aside class="scene-wing wing-left" aria-hidden="true">
            <!-- 1. Top Pop-Up Storybook with Trees and Mountains -->
            <img
                src="/images/book_left_trees_mountains.png"
                alt="3D Pop-Up Book with Nature, Trees, and Mountains"
                class="asset-pop asset-book-left"
            />
            <!-- 2. Boy sitting on book with laptop/tablet -->
            <img
                src="/images/boy_left_on_book_laptop.png"
                alt="3D Cartoon Boy Student on Book Studying with Laptop"
                class="asset-pop asset-boy-laptop"
            />
            <!-- 3. Girl reading book at bottom-left -->
            <img
                src="/images/girl_left_reading_book.png"
                alt="3D Cartoon Girl Student Reading Purple Book"
                class="asset-pop asset-girl-reading"
            />
        </aside>

        <aside class="scene-wing wing-right" aria-hidden="true">
            <!-- 4. Top Pop-Up Island Storybook with Mountains and River -->
            <img
                src="/images/book_right_mountains_river.png"
                alt="3D Pop-Up Island Storybook with Mountains and Sailboat"
                class="asset-pop asset-book-right"
            />
            <!-- 5. Middle Pop-Up Storybook with Message Bubble & Laptop Girl -->
            <img
                src="/images/book_with_message_and_laptop_girl.png"
                alt="3D Open Book with Speech Bubble and Girl on Laptop"
                class="asset-pop asset-book-laptop-girl"
            />
            <!-- 6. Girl using tablet at bottom-right -->
            <img
                src="/images/girl_right_using_tablet.png"
                alt="3D Cartoon Girl Sitting and Studying on Tablet"
                class="asset-pop asset-girl-tablet"
            />
        </aside>

        <!-- Ambient Floating Decorative Elements (Floating Gears, Stars, and Pencils) -->
        <div class="ambient-decorations" aria-hidden="true">
            <!-- Top Floating Gear -->
            <div class="float-gear gear-1">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="#FFD166" stroke="#E5A61A" stroke-width="1.5">
                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                </svg>
            </div>

            <!-- Purple Gear Near Title -->
            <div class="float-gear gear-2">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="#7C6CE6" stroke="#5E4ED6" stroke-width="1.5">
                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                </svg>
            </div>

            <!-- Sparkle Stars -->
            <div class="float-star star-1">&#10024;</div>
            <div class="float-star star-2">&#10024;</div>
            <div class="float-star star-3">&#10024;</div>
        </div>

        <!-- Central Interactive Login Column -->
        <main class="login-core">
            <!-- Header Section: "Welcome to WEBREAD" with 3D Icons -->
            <header class="webread-header">
                <div class="welcome-row">
                    <!-- Mini 3D Stacked Books Icon -->
                    <span class="mini-3d-books" aria-hidden="true">
                        <svg width="34" height="28" viewBox="0 0 34 28" fill="none">
                            <rect x="2" y="8" width="18" height="18" rx="3" fill="#FF7A59" transform="rotate(-12 2 8)" />
                            <rect x="12" y="4" width="18" height="20" rx="3" fill="#2395EB" />
                            <path d="M14 6H28" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <span class="welcome-text">Welcome to</span>

                    <!-- Mini Decorative Gears -->
                    <span class="mini-gears" aria-hidden="true">
                        <svg width="36" height="24" viewBox="0 0 36 24" fill="none">
                            <circle cx="10" cy="12" r="7" fill="#7C6CE6" opacity="0.9"/>
                            <circle cx="10" cy="12" r="3" fill="#FFFFFF"/>
                            <circle cx="24" cy="14" r="8" fill="#48B2FF"/>
                            <circle cx="24" cy="14" r="3" fill="#FFFFFF"/>
                        </svg>
                    </span>
                </div>

                <!-- Big 3D Bold WEBREAD Title -->
                <h1 class="brand-title">
                    <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                </h1>
            </header>

            <!-- Holographic Iridescent Pastel Card -->
            <div
                class="hologram-card"
                :class="{ 'card-shake': cardShaking }"
            >
                <!-- Error Message Banner -->
                <div v-if="errorMessage" class="error-banner" role="alert">
                    <span class="error-icon">&#9888;&#65039;</span>
                    <span class="error-text">{{ errorMessage }}</span>
                </div>

                <!-- Success State Overlay -->
                <div v-if="loginStatus === 'success'" class="success-banner" role="alert">
                    <div class="success-emoji">&#127881;</div>
                    <div class="success-title">Welcome to WebRead!</div>
                    <div class="success-sub">Logged in as {{ role === 'student' ? 'Student' : 'Instructor' }}. Loading modules...</div>
                    <button type="button" class="btn-reset" @click="resetDemo">Log Out / Reset</button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="login-form" novalidate>
                    <!-- Input 1: USERNAME -->
                    <div class="input-pill-wrapper">
                        <input
                            id="username"
                            v-model="username"
                            type="text"
                            class="input-pill"
                            placeholder="USERNAME"
                            autocomplete="username"
                            required
                        />
                    </div>

                    <!-- Input 2: PASSWORD -->
                    <div class="input-pill-wrapper password-pill-wrapper">
                        <input
                            id="password"
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="input-pill password-pill"
                            placeholder="PASSWORD"
                            autocomplete="current-password"
                            required
                        />
                        <!-- Subtle eye toggle button -->
                        <button
                            type="button"
                            class="pill-eye-toggle"
                            :title="showPassword ? 'Hide password' : 'Show password'"
                            @click="showPassword = !showPassword"
                        >
                            <svg v-if="showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7A8098" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A4ABBD" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- Vibrant 3D Pill CTA Button: LOGIN -->
                    <div class="btn-container">
                        <button
                            type="submit"
                            class="btn-login-pill"
                            :disabled="loginStatus === 'submitting' || loginStatus === 'success'"
                        >
                            <span v-if="loginStatus === 'idle' || loginStatus === 'error'" class="btn-text">
                                LOGIN
                            </span>
                            <span v-else-if="loginStatus === 'submitting'" class="btn-loading-dots">
                                <span class="dot"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </span>
                            <span v-else class="btn-text">
                                &#10003;
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
                            class="btn-signup-pill student-signup-pill"
                            @click="openRegisterModal('student')"
                            :disabled="loginStatus === 'submitting' || loginStatus === 'success'"
                        >
                            <span class="signup-icon">🎒</span>
                            <span>Create Student Account</span>
                        </button>

                        <button
                            type="button"
                            class="btn-signup-pill teacher-signup-pill"
                            @click="openRegisterModal('teacher')"
                            :disabled="loginStatus === 'submitting' || loginStatus === 'success'"
                        >
                            <span class="signup-icon">🍎</span>
                            <span>Create Teacher Account</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bottom Big Greeting: HI STUDENT / HI TEACHER (Clickable Role Switcher) -->
            <footer class="webread-footer">
                <button
                    type="button"
                    class="role-switch-btn"
                    @click="toggleRole"
                    :title="`Click to switch to ${role === 'student' ? 'Teacher' : 'Student'} mode`"
                >
                    <span class="greeting-text">HI {{ role.toUpperCase() }}</span>
                    <span class="role-badge-tag">Switch to {{ role === 'student' ? 'Teacher' : 'Student' }}</span>
                </button>

                <!-- Quick Test Credentials Pill Bar -->
                <div class="demo-pills-bar">
                    <span class="demo-pills-label">Quick autofill:</span>
                    <button type="button" class="demo-pill" @click="fillDemo('student')">
                        🎒 Student Demo
                    </button>
                    <button type="button" class="demo-pill" @click="fillDemo('teacher')">
                        🍎 Teacher Demo
                    </button>
                </div>
            </footer>
        </main>
    </div>

    <!-- Create Account Modal -->
    <Teleport to="body">
        <div
            v-if="showRegisterModal"
            class="modal-backdrop"
            @click.self="closeRegisterModal"
        >
            <div class="modal-card">
                <!-- Close Button -->
                <button
                    type="button"
                    class="modal-close-btn"
                    @click="closeRegisterModal"
                    aria-label="Close modal"
                >
                    &times;
                </button>

                <!-- Role Selector Tabs -->
                <div class="modal-header">
                    <div class="modal-role-pill-bar">
                        <button
                            type="button"
                            class="modal-role-pill"
                            :class="{ active: registerRole === 'student' }"
                            @click="setRegisterRole('student')"
                        >
                            🎒 Student
                        </button>
                        <button
                            type="button"
                            class="modal-role-pill"
                            :class="{ active: registerRole === 'teacher' }"
                            @click="setRegisterRole('teacher')"
                        >
                            🍎 Teacher
                        </button>
                    </div>
                    <h2 class="modal-title">
                        {{ registerRole === 'student' ? 'Create Student Account' : 'Create Teacher Account' }}
                    </h2>
                    <p class="modal-sub">
                        {{ registerRole === 'student' ? 'Join WebRead to embark on reading quests and earn badges!' : 'Manage interactive modules and inspire reading habits.' }}
                    </p>
                </div>

                <!-- Error Alert -->
                <div v-if="registerErrorMessage" class="error-banner" role="alert">
                    <span class="error-icon">&#9888;&#65039;</span>
                    <span class="error-text">{{ registerErrorMessage }}</span>
                </div>

                <!-- Modal Form -->
                <form @submit.prevent="handleRegisterSubmit" class="modal-form" novalidate>
                    <!-- Full Name -->
                    <div class="input-pill-wrapper">
                        <input
                            id="reg-name"
                            v-model="registerForm.name"
                            type="text"
                            class="input-pill"
                            placeholder="FULL NAME"
                            autocomplete="name"
                            required
                        />
                    </div>

                    <!-- Email -->
                    <div class="input-pill-wrapper">
                        <input
                            id="reg-email"
                            v-model="registerForm.email"
                            type="email"
                            class="input-pill"
                            placeholder="EMAIL ADDRESS"
                            autocomplete="email"
                            required
                        />
                    </div>

                    <!-- Password -->
                    <div class="input-pill-wrapper">
                        <input
                            id="reg-password"
                            v-model="registerForm.password"
                            type="password"
                            class="input-pill"
                            placeholder="PASSWORD (MIN 8 CHARS)"
                            autocomplete="new-password"
                            required
                        />
                    </div>

                    <!-- Confirm Password -->
                    <div class="input-pill-wrapper">
                        <input
                            id="reg-password-confirmation"
                            v-model="registerForm.password_confirmation"
                            type="password"
                            class="input-pill"
                            placeholder="CONFIRM PASSWORD"
                            autocomplete="new-password"
                            required
                        />
                    </div>

                    <!-- Submit Button -->
                    <div class="btn-container">
                        <button
                            type="submit"
                            class="btn-login-pill modal-submit-pill teacher-submit"
                            :class="{ 'teacher-submit': registerRole === 'teacher' }"
                            :disabled="registerStatus === 'submitting'"
                        >
                            <span v-if="registerStatus === 'submitting'" class="btn-loading-dots">
                                <span class="dot"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </span>
                            <span v-else class="btn-text">
                                {{ registerRole === 'student' ? 'CREATE STUDENT ACCOUNT' : 'CREATE TEACHER ACCOUNT' }}
                            </span>
                        </button>
                    </div>

                    <!-- Switch to Login -->
                    <div class="modal-footer-switch">
                        <span>Already have an account?</span>
                        <button type="button" class="link-back-login" @click="closeRegisterModal">
                            Log In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* ==========================================================================
   CSS THEME TOKENS & RESET
   ========================================================================== */
.webread-page {
    --brand-blue: #2395EB;
    --brand-blue-shadow: #1877C4;
    --brand-coral: #FF6B4A;
    --brand-coral-shadow: #D9492B;
    --greeting-navy: #1D2A68;
    --input-bg: #FFFFFF;
    --input-text: #242E5E;
    --input-placeholder: #A1A8BD;
    --btn-pill-gradient: linear-gradient(135deg, #FF6584 0%, #FF7B54 100%);
    --btn-pill-shadow: #D64665;
    --font-rounded: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-ui: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    min-height: 100vh;
    width: 100%;
    background-color: #FFFDF7;
    background-image: 
        radial-gradient(#E8E5DC 1.4px, transparent 1.4px),
        radial-gradient(#E8E5DC 1.4px, #FFFDF7 1.4px);
    background-size: 32px 32px;
    background-position: 0 0, 16px 16px;
    position: relative;
    overflow-x: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.25rem;
    box-sizing: border-box;
    font-family: var(--font-rounded);
}

/* ==========================================================================
   3D POP-UP SCENE WINGS (LEFT & RIGHT) WITH INDIVIDUAL ASSETS
   ========================================================================== */
.scene-wing {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 32vw;
    max-width: 480px;
    z-index: 2;
    pointer-events: none;
    transition: opacity 0.3s ease, transform 0.5s ease;
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
    filter: drop-shadow(0 14px 22px rgba(35, 42, 104, 0.12));
}

/* --- Left Wing Positions --- */
.asset-book-left {
    top: 10%;
    left: 4%;
    width: 78%;
    max-width: 330px;
    animation: floatBookLeft 7s ease-in-out infinite;
}

.asset-boy-laptop {
    top: 44%;
    right: 6%;
    width: 54%;
    max-width: 200px;
    animation: floatBoy 6.2s ease-in-out infinite 0.4s;
}

.asset-girl-reading {
    bottom: 0;
    /* left: 6%; */
    width: 80%;
    max-width: 1085px;
    animation: floatGirlLeft 6.6s ease-in-out infinite 0.8s;
}

/* --- Right Wing Positions --- */
.asset-book-right {
    top: 8%;
    right: 5%;
    width: 78%;
    max-width: 325px;
    animation: floatBookRight 7.2s ease-in-out infinite 0.2s;
}

.asset-book-laptop-girl {
    top: 42%;
    right: 8%;
    width: 80%;
    max-width: 335px;
    animation: floatBookLaptop 6.5s ease-in-out infinite 0.7s;
}

.asset-girl-tablet {
    bottom: 0;
    right: 0;
    width: 80%;
    max-width: 1075px;
    animation: floatGirlRight 6s ease-in-out infinite 1.2s;
}

@keyframes floatBookLeft {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-12px) rotate(-1.5deg);
    }
}

@keyframes floatBoy {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-10px) rotate(1deg);
    }
}

@keyframes floatGirlLeft {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes floatBookRight {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-12px) rotate(1.5deg);
    }
}

@keyframes floatBookLaptop {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-10px) rotate(-1deg);
    }
}

@keyframes floatGirlRight {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

/* ==========================================================================
   AMBIENT FLOATING DECORATIONS
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
    filter: drop-shadow(0 6px 10px rgba(0, 0, 0, 0.08));
}

.gear-1 {
    top: 12%;
    left: 28%;
    animation: spinSlow 18s linear infinite;
}

.gear-2 {
    top: 9%;
    right: 28%;
    animation: spinReverse 22s linear infinite;
}

.float-star {
    position: absolute;
    font-size: 1.6rem;
    color: #FFD166;
    filter: drop-shadow(0 2px 6px rgba(255, 209, 102, 0.6));
    animation: pulseStar 3s ease-in-out infinite alternate;
}

.star-1 {
    top: 7%;
    left: 48%;
    animation-delay: 0.2s;
}

.star-2 {
    bottom: 14%;
    left: 31%;
    animation-delay: 1.2s;
}

.star-3 {
    bottom: 12%;
    right: 32%;
    animation-delay: 0.8s;
}

@keyframes spinSlow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes spinReverse {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}

@keyframes pulseStar {
    0% { transform: scale(0.85) rotate(0deg); opacity: 0.7; }
    100% { transform: scale(1.15) rotate(15deg); opacity: 1; }
}

/* ==========================================================================
   CONFETTI CONTAINER
   ========================================================================== */
.confetti-container {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 9999;
}

.confetti-item {
    position: absolute;
    top: -20px;
    animation: confettiFall 2.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

.confetti-item.circle {
    border-radius: 50%;
}

.confetti-item.rect {
    border-radius: 2px;
}

@keyframes confettiFall {
    0% {
        top: -10px;
        opacity: 1;
        transform: rotate(0deg);
    }
    100% {
        top: 105vh;
        opacity: 0;
        transform: rotate(720deg);
    }
}

/* ==========================================================================
   CENTRAL CORE COLUMN
   ========================================================================== */
.login-core {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0 auto;
}

/* ==========================================================================
   HEADER: "Welcome to WEBREAD"
   ========================================================================== */
.webread-header {
    text-align: center;
    margin-bottom: 1.25rem;
}

.welcome-row {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: -0.2rem;
}

.welcome-text {
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--greeting-navy);
    letter-spacing: -0.01em;
}

.mini-3d-books {
    display: flex;
    align-items: center;
    filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.12));
}

.mini-gears {
    display: flex;
    align-items: center;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

/* Big 3D Bold WEBREAD */
.brand-title {
    font-size: 3.4rem;
    font-weight: 900;
    line-height: 1;
    margin: 0.1rem 0 0;
    letter-spacing: -0.01em;
    user-select: none;
}

.brand-web {
    color: var(--brand-blue);
    text-shadow: 
        0 4px 0 var(--brand-blue-shadow),
        0 8px 14px rgba(24, 119, 196, 0.25);
}

.brand-read {
    color: var(--brand-coral);
    text-shadow: 
        0 4px 0 var(--brand-coral-shadow),
        0 8px 14px rgba(217, 73, 43, 0.25);
}

/* ==========================================================================
   HOLOGRAPHIC IRIDESCENT PASTEL CARD
   ========================================================================== */
.hologram-card {
    width: 100%;
    border-radius: 32px;
    padding: 1.85rem 1.65rem 1.65rem;
    box-sizing: border-box;
    position: relative;

    /* Multi-stop pastel iridescent sunset gradient */
    background: linear-gradient(
        140deg,
        rgba(255, 190, 180, 0.88) 0%,
        rgba(255, 230, 190, 0.88) 22%,
        rgba(195, 245, 235, 0.88) 50%,
        rgba(195, 215, 255, 0.88) 75%,
        rgba(240, 185, 245, 0.88) 100%
    );
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    /* Soft glowing border */
    border: 3px solid rgba(255, 255, 255, 0.85);

    /* Lush 3D pastel clay depth */
    box-shadow: 
        0 20px 40px -10px rgba(95, 80, 175, 0.22),
        0 8px 16px rgba(0, 0, 0, 0.04),
        inset 0 3px 6px rgba(255, 255, 255, 0.95),
        inset 0 -3px 6px rgba(110, 80, 160, 0.08);

    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s ease;
}

.hologram-card.card-shake {
    animation: cardShake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes cardShake {
    10%, 90% { transform: translate3d(-3px, 0, 0); }
    20%, 80% { transform: translate3d(5px, 0, 0); }
    30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
    40%, 60% { transform: translate3d(6px, 0, 0); }
}

/* ==========================================================================
   ALERTS / BANNERS
   ========================================================================== */
.error-banner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #FFF1F1;
    border: 2px solid #FF8E8E;
    box-shadow: 0 4px 0 #E85B5B;
    border-radius: 9999px;
    padding: 0.5rem 1rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #C52222;
    margin-bottom: 1.15rem;
    animation: popIn 0.25s var(--spring-bounce);
}

.success-banner {
    text-align: center;
    background: #E8FAF2;
    border: 2.5px solid #06D6A0;
    box-shadow: 0 4px 12px rgba(6, 214, 160, 0.3);
    border-radius: 24px;
    padding: 1.25rem 1rem;
    margin-bottom: 1.25rem;
    animation: popIn 0.3s var(--spring-bounce);
}

.success-emoji {
    font-size: 2rem;
    margin-bottom: 0.25rem;
}

.success-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #026549;
    margin-bottom: 0.2rem;
}

.success-sub {
    font-size: 0.8rem;
    font-weight: 600;
    color: #08805E;
    margin-bottom: 0.75rem;
}

.btn-reset {
    background: #FFFFFF;
    border: 2px solid #026549;
    box-shadow: 0 3px 0 #026549;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 800;
    color: #026549;
    padding: 0.35rem 0.9rem;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.btn-reset:hover {
    transform: translateY(-1px);
}

@keyframes popIn {
    0% { transform: scale(0.92); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

/* ==========================================================================
   FORM & PILL INPUTS
   ========================================================================== */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 0.95rem;
}

.input-pill-wrapper {
    position: relative;
    width: 100%;
}

.input-pill {
    width: 100%;
    height: 48px;
    background: var(--input-bg);
    border: 2px solid transparent;
    border-radius: 9999px;
    padding: 0 1.4rem;
    font-family: var(--font-rounded);
    font-size: 0.875rem;
    font-weight: 800;
    color: var(--input-text);
    letter-spacing: 0.05em;
    box-sizing: border-box;
    outline: none;

    /* Clean white 3D pill shadow */
    box-shadow: 
        0 4px 10px rgba(70, 60, 130, 0.08),
        inset 0 2px 4px rgba(0, 0, 0, 0.03);

    transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s var(--spring-bounce), border-color 0.2s;
}

.input-pill::placeholder {
    color: var(--input-placeholder);
    font-weight: 800;
    letter-spacing: 0.06em;
}

.input-pill:focus {
    transform: translateY(-2px);
    border-color: #FFFFFF;
    box-shadow: 
        0 8px 16px rgba(70, 60, 130, 0.14),
        0 0 0 3px rgba(35, 149, 235, 0.4);
}

/* Password Pill with eye button */
.password-pill-wrapper {
    display: flex;
    align-items: center;
}

.password-pill {
    padding-right: 3rem;
}

.pill-eye-toggle {
    position: absolute;
    right: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem;
    border-radius: 50%;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.pill-eye-toggle:hover {
    transform: translateY(-50%) scale(1.15);
}

/* ==========================================================================
   VIBRANT 3D CORAL-PINK PILL BUTTON (LOGIN)
   ========================================================================== */
.btn-container {
    display: flex;
    justify-content: center;
    margin-top: 0.4rem;
}

.btn-login-pill {
    position: relative;
    min-width: 135px;
    height: 46px;
    padding: 0 1.85rem;
    background: var(--btn-pill-gradient);
    border: 2.5px solid rgba(255, 255, 255, 0.95);
    border-radius: 9999px;
    color: #FFFFFF;
    font-family: var(--font-rounded);
    font-size: 1rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    /* 3D button lip and glow */
    box-shadow: 
        0 5px 0 var(--btn-pill-shadow),
        0 8px 16px rgba(255, 101, 132, 0.4),
        inset 0 2px 2px rgba(255, 255, 255, 0.7);

    transition: transform 0.15s var(--spring-bounce), box-shadow 0.15s var(--spring-bounce), filter 0.15s;
}

.btn-login-pill:hover:not(:disabled) {
    transform: translateY(-2px);
    filter: brightness(1.05);
    box-shadow: 
        0 7px 0 var(--btn-pill-shadow),
        0 12px 22px rgba(255, 101, 132, 0.48),
        inset 0 2px 2px rgba(255, 255, 255, 0.85);
}

.btn-login-pill:active:not(:disabled) {
    transform: translateY(3px);
    box-shadow: 
        0 2px 0 var(--btn-pill-shadow),
        0 4px 8px rgba(255, 101, 132, 0.35),
        inset 0 1px 1px rgba(255, 255, 255, 0.6);
}

.btn-login-pill:disabled {
    opacity: 0.9;
    cursor: not-allowed;
}

.btn-text {
    text-shadow: 0 1.5px 0 rgba(0, 0, 0, 0.15);
}

/* Loading Dots */
.btn-loading-dots {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.btn-loading-dots .dot {
    width: 6px;
    height: 6px;
    background: #FFFFFF;
    border-radius: 50%;
    animation: dotBounce 0.55s ease-in-out infinite alternate;
}

.btn-loading-dots .dot:nth-child(2) {
    animation-delay: 0.18s;
}

.btn-loading-dots .dot:nth-child(3) {
    animation-delay: 0.36s;
}

@keyframes dotBounce {
    0% { transform: translateY(0); }
    100% { transform: translateY(-5px); }
}

/* ==========================================================================
   SIGNUP ACTIONS & DIVIDER
   ========================================================================== */
.auth-divider {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0.75rem 0 0.5rem 0;
}

.divider-line {
    flex: 1;
    height: 1.5px;
    background: rgba(160, 170, 200, 0.25);
    border-radius: 9999px;
}

.divider-text {
    font-size: 0.72rem;
    font-weight: 800;
    color: #8C96B0;
    letter-spacing: 0.06em;
}

.signup-buttons-container {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    width: 100%;
}

.btn-signup-pill {
    width: 100%;
    height: 44px;
    background: #FFFFFF;
    border: 2px solid rgba(255, 255, 255, 0.95);
    border-radius: 9999px;
    font-family: var(--font-rounded);
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    box-shadow: 
        0 4px 0 #D5D7E5,
        0 6px 14px rgba(70, 60, 130, 0.08),
        inset 0 1px 2px rgba(255, 255, 255, 0.8);
    transition: transform 0.15s var(--spring-bounce), box-shadow 0.15s var(--spring-bounce), background-color 0.15s, border-color 0.15s;
}

.student-signup-pill {
    color: #1A7BC7;
    background: #F4FAFF;
    border-color: #D3EAFA;
    box-shadow: 0 4px 0 #B9DBF5, 0 6px 14px rgba(35, 149, 235, 0.12);
}

.student-signup-pill:hover:not(:disabled) {
    transform: translateY(-2px);
    background: #FFFFFF;
    border-color: #2395EB;
    box-shadow: 0 6px 0 #B9DBF5, 0 10px 18px rgba(35, 149, 235, 0.18);
}

.teacher-signup-pill {
    color: #E24F30;
    background: #FFF7F5;
    border-color: #FCD8D0;
    box-shadow: 0 4px 0 #F5BFB3, 0 6px 14px rgba(255, 107, 74, 0.12);
}

.teacher-signup-pill:hover:not(:disabled) {
    transform: translateY(-2px);
    background: #FFFFFF;
    border-color: #FF6B4A;
    box-shadow: 0 6px 0 #F5BFB3, 0 10px 18px rgba(255, 107, 74, 0.18);
}

.btn-signup-pill:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 1px 0 currentColor;
}

.signup-icon {
    font-size: 1.1rem;
    line-height: 1;
}

/* ==========================================================================
   REGISTRATION MODAL
   ========================================================================== */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(18, 26, 68, 0.55);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    animation: fadeInModal 0.2s ease-out;
}

.modal-card {
    position: relative;
    width: 100%;
    max-width: 440px;
    background: #FFFFFF;
    border-radius: 28px;
    padding: 2.25rem 2rem 1.85rem 2rem;
    box-shadow: 0 20px 50px rgba(18, 26, 68, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.8);
    font-family: var(--font-rounded);
    animation: popInModal 0.3s var(--spring-bounce);
    box-sizing: border-box;
}

@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes popInModal {
    from {
        opacity: 0;
        transform: scale(0.92) translateY(12px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: #F0F2F8;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.4rem;
    line-height: 1;
    color: #727B94;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.modal-close-btn:hover {
    background: #FFE8E8;
    color: #E03131;
    transform: rotate(90deg);
}

.modal-header {
    text-align: center;
    margin-bottom: 1.25rem;
}

.modal-role-pill-bar {
    display: inline-flex;
    background: #F1F3F9;
    border-radius: 9999px;
    padding: 0.25rem;
    gap: 0.25rem;
    margin-bottom: 0.85rem;
}

.modal-role-pill {
    background: transparent;
    border: none;
    font-family: var(--font-rounded);
    font-size: 0.82rem;
    font-weight: 800;
    color: #7A849E;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-role-pill.active {
    background: #FFFFFF;
    color: #1D2A68;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.modal-title {
    font-size: 1.55rem;
    font-weight: 900;
    color: #1D2A68;
    margin: 0 0 0.35rem 0;
    line-height: 1.15;
}

.modal-sub {
    font-size: 0.84rem;
    color: #727B94;
    font-weight: 700;
    margin: 0;
    line-height: 1.4;
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.modal-submit-pill {
    width: 100%;
    height: 48px;
    font-size: 0.95rem;
}

.modal-submit-pill.teacher-submit {
    background: linear-gradient(135deg, #FF7A59 0%, #FF5A78 100%);
    box-shadow: 
        0 5px 0 #D9492B,
        0 8px 16px rgba(255, 107, 74, 0.4),
        inset 0 2px 2px rgba(255, 255, 255, 0.7);
}


.modal-footer-switch {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.5rem;
    font-size: 0.84rem;
    font-weight: 700;
    color: #727B94;
}

.link-back-login {
    background: none;
    border: none;
    color: var(--brand-blue);
    font-family: var(--font-rounded);
    font-weight: 800;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
    padding: 0;
}

.link-back-login:hover {
    color: var(--brand-blue-shadow);
}

/* ==========================================================================
   FOOTER: "HI STUDENT"
   ========================================================================== */
.webread-footer {
    margin-top: 1.5rem;
    text-align: center;
}



.role-switch-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 1rem;
    border-radius: 16px;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.role-switch-btn:hover {
    transform: translateY(-2px) scale(1.02);
}

.role-switch-btn:active {
    transform: translateY(1px) scale(0.98);
}

.greeting-text {
    font-size: 2.15rem;
    font-weight: 900;
    color: var(--greeting-navy);
    letter-spacing: 0.04em;
    line-height: 1.1;
    text-shadow: 0 2px 4px rgba(29, 42, 104, 0.08);
}

.role-badge-tag {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--brand-blue);
    background: #E8F4FD;
    border: 1.5px solid #C1E1FB;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    transition: background-color 0.2s;
}

.demo-pills-bar {
    margin-top: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.demo-pills-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #8C94A8;
}

.demo-pill {
    background: #FFFFFF;
    border: 1.5px solid #E2E4EC;
    color: #4B5578;
    font-family: var(--font-rounded);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
}

.demo-pill:hover {
    background: #F4F6FB;
    border-color: #2395EB;
    color: #2395EB;
    transform: translateY(-1px);
}



/* ==========================================================================
   RESPONSIVENESS (MOBILE, TABLET, DESKTOP)
   ========================================================================== */
@media (max-width: 1080px) {
    .scene-wing {
        width: 22vw;
        opacity: 0.75;
    }
}

@media (max-width: 860px) {
    /* On tablets and mobile, tuck side wings to avoid cluttering the form */
    .scene-wing {
        display: none;
    }

    .float-gear, .float-star {
        opacity: 0.6;
    }

    .brand-title {
        font-size: 2.8rem;
    }

    .welcome-text {
        font-size: 1.35rem;
    }

    .greeting-text {
        font-size: 1.85rem;
    }
}

@media (max-width: 480px) {
    .webread-page {
        padding: 1.5rem 1rem;
    }

    .hologram-card {
        padding: 1.5rem 1.25rem;
        border-radius: 28px;
    }

    .brand-title {
        font-size: 2.5rem;
    }

    .welcome-text {
        font-size: 1.25rem;
    }

    .greeting-text {
        font-size: 1.65rem;
    }
}
</style>
